--[[
	Advanced Call Distribution — Async Queue Orchestrator

	Started by caller entry script:
	  luarun app/acd/acd_orchestrator.lua <session_uuid> <queue_uuid> <domain_uuid> <domain_name> <caller_uuid>

	Responsibilities:
	  • Load queue config, members, follow-me destinations
	  • Ring agents asynchronously with bgapi originate
	  • Add tiers without stopping existing ringing legs
	  • Respect busy_handling: always / never_queue / never_any
	  • Detect first answered/winning leg via session row updated by agent answer script
	  • Cancel loser legs and clean busy rows
	  • Timeout caller back to main script for timeout action
]]--

require "resources.functions.config"
local Database = require "resources.functions.database"
local api = freeswitch.API()

local cc_session_uuid = argv and argv[1] or ""
local queue_uuid      = argv and argv[2] or ""
local domain_uuid     = argv and argv[3] or ""
local domain_name     = argv and argv[4] or ""
local caller_uuid     = argv and argv[5] or ""

local function trim(s)
	return (tostring(s or ""):gsub("^%s+", ""):gsub("%s+$", ""))
end

local function uuid_exists(uuid)
	return trim(api:executeString("uuid_exists " .. uuid)) == "true"
end

local function new_uuid()
	return trim(api:executeString("create_uuid"))
end

local function log_info(msg)
	freeswitch.consoleLog("notice", "[acd_cc_orch] " .. tostring(msg) .. "\n")
end

local function log_err(msg)
	freeswitch.consoleLog("err", "[acd_cc_orch] " .. tostring(msg) .. "\n")
end

if cc_session_uuid == "" or queue_uuid == "" or domain_uuid == "" or domain_name == "" or caller_uuid == "" then
	log_err("missing argv; got session=" .. tostring(cc_session_uuid) .. " queue=" .. tostring(queue_uuid) .. " domain=" .. tostring(domain_uuid) .. " domain_name=" .. tostring(domain_name) .. " caller=" .. tostring(caller_uuid))
	return
end

local dbh = Database.new('system')
if not dbh then
	log_err("could not connect to database")
	return
end

local queue = dbh:first_row(
	"SELECT * FROM v_acd_queues WHERE queue_uuid = :queue_uuid AND domain_uuid = :domain_uuid",
	{ queue_uuid = queue_uuid, domain_uuid = domain_uuid }
)
if not queue then
	log_err("queue not found " .. queue_uuid)
	return
end

local tier_advance_secs = tonumber(queue.queue_tier_advance_seconds) or 20
local ring_lower_tiers  = (queue.queue_ring_lower_tiers == "true")
local queue_timeout     = tonumber(queue.queue_timeout) or 0
local hold_music        = queue.queue_hold_music or "default"
local cid_name_prefix   = queue.queue_cid_name_prefix or ""

local function build_moh_stream(moh)
	moh = moh or "default"
	if not moh:find("local_stream://", 1, true) then moh = "local_stream://" .. moh end
	return moh
end
local moh_stream = build_moh_stream(hold_music)

local orig_cid_name   = trim(api:executeString("uuid_getvar " .. caller_uuid .. " caller_id_name"))
local orig_cid_number = trim(api:executeString("uuid_getvar " .. caller_uuid .. " caller_id_number"))
if orig_cid_name == "_undef_" then orig_cid_name = "" end
if orig_cid_number == "_undef_" then orig_cid_number = "" end

-- Encode for originate {vars}; spaces in var values break originate parser.
local function safe_var_value(v)
	v = tostring(v or "")
	v = v:gsub("[,%[%]{}']", "")
	v = v:gsub(" ", "%%20")
	return v
end

-- ── Load members + follow-me destinations ────────────────────────────────────
local members = {}
dbh:query(
	"SELECT m.queue_member_number, m.queue_member_tier, " ..
	"m.queue_member_honor_follow_me, m.queue_member_busy_handling " ..
	"FROM v_acd_queue_members m " ..
	"WHERE m.queue_uuid = :queue_uuid AND m.domain_uuid = :domain_uuid " ..
	"AND m.queue_member_enabled = 'true' " ..
	"ORDER BY m.queue_member_tier ASC, m.queue_member_number ASC",
	{ queue_uuid = queue_uuid, domain_uuid = domain_uuid },
	function(row)
		table.insert(members, {
			number          = row.queue_member_number,
			tier            = tonumber(row.queue_member_tier) or 1,
			honor_follow_me = (row.queue_member_honor_follow_me ~= "false"),
			busy_handling   = row.queue_member_busy_handling or "always",
			watch_numbers   = {},
		})
	end
)

for _, m in ipairs(members) do
	m.watch_numbers[m.number] = true
	dbh:query(
		"SELECT fmd.follow_me_destination " ..
		"FROM v_extensions e " ..
		"JOIN v_follow_me fm ON fm.follow_me_uuid = e.follow_me_uuid " ..
		"JOIN v_follow_me_destinations fmd ON fmd.follow_me_uuid = fm.follow_me_uuid " ..
		"WHERE e.extension = :ext AND e.domain_uuid = :domain_uuid " ..
		"AND e.follow_me_enabled = 'true' AND fm.follow_me_enabled = 'true'",
		{ ext = m.number, domain_uuid = domain_uuid },
		function(row)
			if row.follow_me_destination and row.follow_me_destination ~= "" then
				m.watch_numbers[row.follow_me_destination] = true
				log_info("agent " .. m.number .. " follow-me dest " .. row.follow_me_destination)
			end
		end
	)
end

if #members == 0 then
	log_err("no members in queue " .. queue_uuid)
	api:executeString("uuid_setvar " .. caller_uuid .. " acd_queue_timeout true")
	api:executeString("uuid_break " .. caller_uuid)
	return
end

local tier_set, tier_order = {}, {}
for _, m in ipairs(members) do
	if not tier_set[m.tier] then
		tier_set[m.tier] = true
		table.insert(tier_order, m.tier)
	end
end

-- ── Live-channel scanner ─────────────────────────────────────────────────────
-- Returns true if any of the given numbers (an agent's extension + follow-me
-- destinations) is involved in a live channel — whether the agent is the
-- CALLEE (dest/callee/forward leg) OR the CALLER. A Teams agent who places a
-- call is only identifiable by ";ext=NNN" in the SIP From, which is not exposed
-- by `show channels`, so for each active channel we also read sip_from_user.
local function any_active_channel_for_numbers(numbers_set)
	local out = api:executeString("show channels as delim |")
	if not out or out == "" then return false end

	local lines = {}
	for line in out:gmatch("[^\r\n]+") do
		if not line:match("^%s*$") and not line:match("^%s*%d+ total%.?%s*$") then
			table.insert(lines, line)
		end
	end
	if #lines < 2 then return false end

	local function split_pipe(line)
		local cells, pos = {}, 1
		while true do
			local nx = line:find("|", pos, true)
			if nx then
				table.insert(cells, line:sub(pos, nx - 1))
				pos = nx + 1
			else
				table.insert(cells, line:sub(pos))
				return cells
			end
		end
	end

	local header = split_pipe(lines[1])
	local col = {}
	for i, name in ipairs(header) do col[name] = i end

	local i_callstate = col["callstate"]
	local i_presence  = col["presence_id"]
	local i_dest      = col["dest"]
	local i_callee    = col["callee_num"]
	local i_initial   = col["initial_dest"]
	local i_name      = col["name"]
	local i_uuid      = col["uuid"]
	local i_cid       = col["cid_num"]

	for i = 2, #lines do
		local cells = split_pipe(lines[i])
		if i_callstate and cells[i_callstate] == "ACTIVE" then
			local presence_ext = nil
			if i_presence then presence_ext = (cells[i_presence] or ""):match("^([^@]+)@") end
			local dest_v    = i_dest    and (cells[i_dest]    or "") or ""
			local callee_v  = i_callee  and (cells[i_callee]  or "") or ""
			local initial_v = i_initial and (cells[i_initial] or "") or ""
			local name_v    = i_name    and (cells[i_name]    or "") or ""
			local cid_v     = i_cid     and (cells[i_cid]     or "") or ""
			-- ext=NNN embedded in the callee (e.g. "+1...;ext=213")
			local callee_ext = callee_v:match("ext=(%d+)")

			-- Match the agent as CALLEE / forward target via the show-channels fields.
			for num, _ in pairs(numbers_set) do
				local why
				if     presence_ext == num then why = "presence"
				elseif dest_v      == num then why = "dest"
				elseif callee_v    == num then why = "callee"
				elseif callee_ext  == num then why = "callee_ext"
				elseif initial_v   == num then why = "initial_dest"
				elseif cid_v       == num then why = "cid_num"
				elseif name_v:match("/" .. num .. "@") then why = "name"
				end
				if why then
					freeswitch.consoleLog("notice", "[acd_cc_orch] busy-match num=" .. num .. " via " .. why ..
						" chan=" .. name_v .. " dest=" .. dest_v .. " cid=" .. cid_v .. "\n")
					return true
				end
			end

			-- Match the agent as CALLER: their identity is in the SIP From, which
			-- isn't a show-channels column, so fetch it for this active channel.
			if i_uuid then
				local cu = cells[i_uuid]
				if cu and cu ~= "" then
					local from_user = trim(api:executeString("uuid_getvar " .. cu .. " sip_from_user"))
					if from_user == "_undef_" or from_user == "" then
						from_user = trim(api:executeString("uuid_getvar " .. cu .. " sip_from_uri"))
					end
					if from_user and from_user ~= "" and from_user ~= "_undef_" then
						local from_ext = from_user:match("ext=(%d+)")
						for num, _ in pairs(numbers_set) do
							if from_user == num or from_ext == num then
								freeswitch.consoleLog("notice", "[acd_cc_orch] busy-match num=" .. num ..
									" via sip_from(" .. tostring(from_user) .. ") chan=" .. name_v .. "\n")
								return true
							end
						end
					end
				end
			end
		end
	end
	return false
end

-- ── Busy detection ───────────────────────────────────────────────────────────

-- Reliable "is this agent already on a queue call?" check.
--
-- We read it from v_acd_sessions (winner_agent_extension with
-- the caller channel still live) rather than from v_acd_busy.
-- The _busy row is deleted the instant the agent-answer script's intercept
-- returns — which happens as soon as the loopback→endpoint media path is up,
-- i.e. right after the agent answers — even though the conversation continues.
-- The session row, by contrast, lives for the entire call and is only deleted
-- on hangup, so it is the trustworthy signal that an agent is mid-call.
local function agent_on_queue_call(agent_ext)
	local caller_uuids = {}
	dbh:query(
		"SELECT caller_channel_uuid FROM v_acd_sessions " ..
		"WHERE domain_uuid = :domain_uuid AND winner_agent_extension = :agent_ext " ..
		"AND winner_leg_uuid IS NOT NULL",
		{ domain_uuid = domain_uuid, agent_ext = agent_ext },
		function(row) table.insert(caller_uuids, row.caller_channel_uuid) end
	)
	for _, cuuid in ipairs(caller_uuids) do
		if cuuid and cuuid ~= "" and uuid_exists(cuuid) then return true end
	end
	return false
end

-- Busy detection is governed entirely by the member's "Busy Handling" setting,
-- chosen per member in the queue admin:
--   • always      → "Always Ring"                   — ring even if on a call (never skipped)
--   • never_any   → "Never Ring (Any Call)"         — skip if on ANY call
--   • never_queue → "Never Ring (Queue Calls Only)" — skip only if on another QUEUE call
local function is_busy(member)
	-- "Always Ring": offer the call even when the agent is already on one.
	if member.busy_handling == "always" then return false end

	-- "Never Ring (Any Call)": BUSY if the agent's own extension OR ANY of their
	-- follow-me destinations is currently on a live channel.
	--
	-- Read straight from FreeSWITCH's active-channel list, so it is always
	-- current and needs no stored state: it reflects queue calls, direct calls,
	-- outbound calls, and calls that were TRANSFERRED IN to the agent. A transfer
	-- that moves a call off an agent frees them as soon as their leg disappears,
	-- and busies whoever the call landed on once their number/forward shows up on
	-- a channel. (A Teams-internal transfer that never creates a new leg is the
	-- one case this cannot see -- accepted for now.)
	if member.busy_handling == "never_any" then
		if any_active_channel_for_numbers(member.watch_numbers) then
			log_info("is_busy(" .. member.number .. ", never_any): extension/follow-me active on a live channel")
			return true
		end
	end

	-- Both "never_any" and "never_queue": BUSY if already the winner of a still-live
	-- QUEUE call. The session row lives for the whole call (unlike the _busy row,
	-- which clears the moment the intercept completes), so it is the trustworthy
	-- signal that the agent is mid-queue-call. For "never_queue" this is the ONLY
	-- thing that makes the agent busy — a non-queue call does not skip them.
	if agent_on_queue_call(member.number) then
		log_info("is_busy(" .. member.number .. ", " .. tostring(member.busy_handling) .. "): on a queue call (session winner)")
		return true
	end

	-- Both modes: a queue leg is already ringing this agent for another caller.
	local leg_uuids = {}
	dbh:query(
		"SELECT leg_uuid FROM v_acd_busy WHERE domain_uuid = :domain_uuid AND agent_extension = :agent_ext",
		{ domain_uuid = domain_uuid, agent_ext = member.number },
		function(row) table.insert(leg_uuids, row.leg_uuid) end
	)
	for _, leg_uuid in ipairs(leg_uuids) do
		if uuid_exists(leg_uuid) then return true end
		dbh:query("DELETE FROM v_acd_busy WHERE leg_uuid = :leg_uuid", { leg_uuid = leg_uuid })
	end

	return false
end

local function resolve_endpoint(member, dest, loopback_context)
	if dest == member.number then
		local contact = api:executeString("sofia_contact internal/" .. dest .. "@" .. domain_name) or ""
		if contact == "" or contact:find("error", 1, true) or contact:sub(1, 1) == "%" then
			log_info("skip bare extension " .. dest .. " for agent " .. member.number .. " — no SIP registration")
			return nil
		end
		return "user/" .. dest .. "@" .. domain_name
	end
	return "loopback/" .. dest .. "/" .. loopback_context
end

-- ── Pending leg bookkeeping ─────────────────────────────────────────────────
local pending = {} -- leg_uuid -> {agent_ext, destination, endpoint, tier}

local function has_pending_agent(agent_ext)
	for leg_uuid, leg in pairs(pending) do
		if leg.agent_ext == agent_ext then
			if uuid_exists(leg_uuid) then
				return true
			else
				dbh:query("DELETE FROM v_acd_busy WHERE leg_uuid = :leg_uuid", { leg_uuid = leg_uuid })
				pending[leg_uuid] = nil
			end
		end
	end
	return false
end

local function clean_leg(leg_uuid)
	dbh:query("DELETE FROM v_acd_busy WHERE leg_uuid = :leg_uuid", { leg_uuid = leg_uuid })
	pending[leg_uuid] = nil
end

local function cancel_leg(leg_uuid, cause)
	cause = cause or "ORIGINATOR_CANCEL"
	if uuid_exists(leg_uuid) then
		api:executeString("uuid_kill " .. leg_uuid .. " " .. cause)
	end
	clean_leg(leg_uuid)
end

local function cancel_all_except(winner_leg_uuid)
	for leg_uuid, _ in pairs(pending) do
		if leg_uuid ~= winner_leg_uuid then cancel_leg(leg_uuid, "ORIGINATOR_CANCEL") end
	end
end

local function cancel_all(cause)
	for leg_uuid, _ in pairs(pending) do
		cancel_leg(leg_uuid, cause or "ORIGINATOR_CANCEL")
	end
end

local function prune_dead_pending()
	for leg_uuid, _ in pairs(pending) do
		if not uuid_exists(leg_uuid) then clean_leg(leg_uuid) end
	end
end

local function session_row()
	return dbh:first_row(
		"SELECT winner_leg_uuid, timeout_at FROM v_acd_sessions WHERE session_uuid = :session_uuid",
		{ session_uuid = cc_session_uuid }
	)
end

local function delete_session_row()
	dbh:query(
		"DELETE FROM v_acd_sessions WHERE session_uuid = :session_uuid",
		{ session_uuid = cc_session_uuid }
	)
end

local function start_member_legs(member, tier_num)
	if has_pending_agent(member.number) then
		log_info("agent " .. member.number .. " already ringing — not re-originating")
		return 0
	end
	if is_busy(member) then
		log_info("agent " .. member.number .. " busy by policy " .. tostring(member.busy_handling) .. " — skipping")
		return 0
	end

	local destinations = {}
	if member.honor_follow_me then
		for num, _ in pairs(member.watch_numbers) do table.insert(destinations, num) end
	else
		table.insert(destinations, member.number)
	end

	local started = 0
	local loopback_context = queue.queue_context or domain_name
	local cid_display = safe_var_value(cid_name_prefix .. orig_cid_name)
	local cid_number  = safe_var_value(orig_cid_number)

	for _, dest in ipairs(destinations) do
		local endpoint = resolve_endpoint(member, dest, loopback_context)
		if endpoint then
			local leg_uuid = new_uuid()
			dbh:query(
				"INSERT INTO v_acd_busy " ..
				"(busy_uuid, domain_uuid, queue_uuid, agent_extension, destination, leg_uuid, started_at) " ..
				"VALUES (:busy_uuid, :domain_uuid, :queue_uuid, :agent_ext, :destination, :leg_uuid, NOW())",
				{
					busy_uuid   = new_uuid(),
					domain_uuid = domain_uuid,
					queue_uuid  = queue_uuid,
					agent_ext   = member.number,
					destination = dest,
					leg_uuid    = leg_uuid,
				}
			)

			pending[leg_uuid] = { agent_ext = member.number, destination = dest, endpoint = endpoint, tier = tier_num }

			-- loopback_bowout=false keeps the loopback relay (loopback-a <-> loopback-b
			-- <-> real endpoint, e.g. a Teams gateway) intact rather than collapsing to
			-- a direct bridge. This is the configuration we have actually observed reach
			-- CS_EXCHANGE_MEDIA via the agent-answer intercept, and it keeps the tracked
			-- loopback-a uuid alive for ringing-phase busy detection.
			--
			-- NOTE: the real "no audio" cause was NOT this flag. It was the caller-entry
			-- script holding its park thread in a wait loop, which pinned the caller in
			-- CS_HIBERNATE so intercept could never pull it into CS_EXCHANGE_MEDIA. That
			-- script now returns immediately on a winner (releasing the channel to the
			-- bridge), and THIS orchestrator owns the session-row cleanup.
			local vars = "{origination_uuid=" .. leg_uuid ..
				",origination_caller_id_name=" .. cid_display ..
				",origination_caller_id_number=" .. cid_number ..
				",acd_cc_session_uuid=" .. cc_session_uuid ..
				",acd_queue_uuid=" .. queue_uuid ..
				",acd_agent_ext=" .. member.number ..
				",acd_destination=" .. safe_var_value(dest) ..
				",ignore_early_media=true" ..
				",loopback_bowout=false" ..
				",hangup_after_bridge=true" ..
				",leg_timeout=90" ..
				"}"

			-- IMPORTANT: the &lua(...) app args must NOT contain unquoted spaces.
			-- FreeSWITCH's originate command tokenizes its arguments on whitespace
			-- BEFORE seeing the parens, so `&lua(script a b c)` is read as
			-- four positional args (exten, dialplan, context, cid_name) and the
			-- originate is rejected with -USAGE. The fix is to wrap the whole
			-- `&lua(...)` block in single quotes so FreeSWITCH treats it as one
			-- token. Single quotes are FreeSWITCH's grouping mechanism; they
			-- are stripped before the app dispatcher runs the script.
			local app_args = cc_session_uuid .. " " .. caller_uuid .. " " .. queue_uuid .. " " .. domain_uuid .. " " .. member.number .. " " .. dest .. " " .. leg_uuid .. " " .. moh_stream
			local cmd = "bgapi originate " .. vars .. endpoint .. " '&lua(app/acd/acd_agent_answer.lua " .. app_args .. ")'"
			log_info("originate agent " .. member.number .. " → " .. endpoint .. " leg " .. leg_uuid)
			api:executeString(cmd)
			started = started + 1
		end
	end
	return started
end

local function start_tier(tier_num)
	local count = 0
	for _, member in ipairs(members) do
		if member.tier == tier_num then
			count = count + start_member_legs(member, tier_num)
		end
	end
	log_info("tier " .. tostring(tier_num) .. " started " .. tostring(count) .. " leg(s); pending=" .. tostring((function() local c=0; for _ in pairs(pending) do c=c+1 end; return c end)()))
	return count
end

-- ── Main loop ────────────────────────────────────────────────────────────────
log_info("orchestrator start session=" .. cc_session_uuid .. " caller=" .. caller_uuid .. " queue=" .. queue_uuid)

local entered_at = os.time()
local current_tier_index = 1
local next_tier_at = os.time()
local all_tiers_started = false

while true do
	if not uuid_exists(caller_uuid) then
		log_info("caller gone; cancel pending and exit")
		cancel_all("ORIGINATOR_CANCEL")
		delete_session_row()
		return
	end

	local row = session_row()
	if not row then
		log_info("session row gone; cancel pending and exit")
		cancel_all("ORIGINATOR_CANCEL")
		return
	end

	if row.winner_leg_uuid and row.winner_leg_uuid ~= "" then
		log_info("winner claimed: " .. row.winner_leg_uuid .. " — canceling loser legs")
		cancel_all_except(row.winner_leg_uuid)
		-- The caller-entry script has already returned to release the channel into
		-- the bridge so audio can flow. We now OWN the session-row lifecycle: wait
		-- for the bridged conversation to actually end, THEN delete the row. We must
		-- not delete it any earlier — the caller script reads winner_leg_uuid right
		-- after park returns, and an early delete elsewhere is exactly what used to
		-- cancel the winner and drop the call.
		while uuid_exists(caller_uuid) do
			freeswitch.msleep(1000)
		end
		log_info("winner bridge ended; cleaning session row " .. cc_session_uuid)
		delete_session_row()
		return
	end

	prune_dead_pending()

	if queue_timeout > 0 and (os.time() - entered_at) >= queue_timeout then
		log_info("queue timeout reached; breaking caller park")
		cancel_all("ORIGINATOR_CANCEL")
		dbh:query("UPDATE v_acd_sessions SET timeout_at = NOW() WHERE session_uuid = :session_uuid", { session_uuid = cc_session_uuid })
		api:executeString("uuid_setvar " .. caller_uuid .. " acd_queue_timeout true")
		api:executeString("uuid_break " .. caller_uuid)
		return
	end

	if os.time() >= next_tier_at then
		local tier_num = tier_order[current_tier_index]
		if tier_num then
			if not ring_lower_tiers and current_tier_index > 1 then
				log_info("ring_lower_tiers=false; canceling previous tier legs before tier " .. tier_num)
				cancel_all("ORIGINATOR_CANCEL")
			end
			start_tier(tier_num)
			current_tier_index = current_tier_index + 1
			if current_tier_index > #tier_order then all_tiers_started = true end
			next_tier_at = os.time() + tier_advance_secs
		end
	end

	-- If all tiers were tried and every leg died/no-answered, start cycle over.
	local pending_count = 0
	for _ in pairs(pending) do pending_count = pending_count + 1 end
	if all_tiers_started and pending_count == 0 then
		log_info("all tiers exhausted with no live pending legs; restarting tier cycle")
		current_tier_index = 1
		all_tiers_started = false
		next_tier_at = os.time() + 1
	end

	freeswitch.msleep(250)
end
