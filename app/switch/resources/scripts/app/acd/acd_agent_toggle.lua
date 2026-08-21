--[[
	Advanced Call Distribution — Agent Login / Logout Toggle
	/usr/local/freeswitch/scripts/app/acd/acd_agent_toggle.lua

	Invoked by a feature code *86<queue_extension> from the domain dialplan, e.g.
	dialing *86771 logs the caller in/out of the queue whose extension is 771:
	  <action application="answer"/>
	  <action application="lua" data="app/acd/acd_agent_toggle.lua"/>

	Behaviour:
	  • Identify the CALLING extension (see resolution order below).
	  • *86<queue#>  — flip queue_member_enabled for that extension IN THAT QUEUE
	                   ONLY (true <-> false).
	  • *86          — master switch across ALL queues the extension belongs to:
	                   enabled in ANY queue  -> log OUT of all,
	                   disabled in every one -> log IN to all.
	  • Play "you are now logged in" / "you are now logged out" accordingly.

	The queue orchestrator reads queue_member_enabled live from the database on
	every call, so the toggle takes effect immediately — no XML reload or cache
	clear required.

	Caller-extension resolution order:
	  1. Microsoft Teams calls embed the extension as ";ext=NNN" inside the From
	     user (e.g. sip_from_user = "+14843351444;ext=214").
	  2. Local / registered extensions: caller_id_number IS the extension.
]]--

require "resources.functions.config"
local Database = require "resources.functions.database"
local api = freeswitch.API()

local function log_info(m) freeswitch.consoleLog("notice", "[acd_cc_toggle] " .. tostring(m) .. "\n") end
local function log_err(m)  freeswitch.consoleLog("err",    "[acd_cc_toggle] " .. tostring(m) .. "\n") end

if not session:ready() then return end

local domain_uuid = session:getVariable("domain_uuid")
local domain_name = session:getVariable("domain_name")

-- ── Helpers ──────────────────────────────────────────────────────────────────
local function play_and_hangup(file)
	-- Make sure the channel is answered with media before playback.
	if session:getVariable("channel_state") ~= "CS_EXCHANGE_MEDIA" then
		session:answer()
	end
	session:execute("sleep", "300")
	if session:ready() then session:streamFile(file) end
	session:execute("sleep", "300")
	if session:ready() then session:hangup("NORMAL_CLEARING") end
end

-- Resolve the calling agent's extension.
local function resolve_extension()
	-- 1) Teams: ";ext=NNN" carried in the From / identity headers.
	local header_vars = {
		"sip_from_user",
		"sip_from_uri",
		"sip_from_user_stripped",
		"sip_contact_user",
		"sip_h_P-Asserted-Identity",
		"sip_full_from",
		"sip_h_From",
	}
	for _, name in ipairs(header_vars) do
		local v = session:getVariable(name)
		if v then
			local ext = tostring(v):match("ext=(%d+)")
			if ext and ext ~= "" then
				log_info("resolved extension " .. ext .. " from Teams header '" .. name .. "'")
				return ext
			end
		end
	end

	-- 2) Local / registered extension: caller_id_number is the bare extension.
	local cid = session:getVariable("caller_id_number")
	         or session:getVariable("effective_caller_id_number") or ""
	cid = tostring(cid)
	if cid:match("^%d%d?%d?%d?%d?%d?$") then  -- 1–6 digit plain extension, no '+'
		log_info("resolved extension " .. cid .. " from caller_id_number")
		return cid
	end

	return nil
end

-- ── Connect to the database ──────────────────────────────────────────────────
local dbh = Database.new('system')
if not dbh then
	log_err("could not connect to database")
	play_and_hangup("ivr/ivr-that_was_an_invalid_entry.wav")
	return
end

-- ── Resolve domain (fallback to domain_name lookup) ──────────────────────────────
if not domain_uuid or domain_uuid == "" then
	domain_name = domain_name
		or session:getVariable("sip_req_host")
		or session:getVariable("sip_to_host")
		or session:getVariable("context")
	if domain_name and domain_name ~= "" then
		local row = dbh:first_row(
			"SELECT domain_uuid FROM v_domains WHERE domain_name = :domain_name",
			{ domain_name = domain_name }
		)
		if row and row.domain_uuid then domain_uuid = row.domain_uuid end
	end
end
if not domain_uuid or domain_uuid == "" then
	log_err("could not resolve domain_uuid (domain_name='" .. tostring(domain_name) .. "'); aborting")
	play_and_hangup("ivr/ivr-that_was_an_invalid_entry.wav")
	return
end

-- ── Resolve the calling extension ────────────────────────────────────────────
local agent_ext = resolve_extension()
if not agent_ext then
	log_err("could not resolve calling extension (cid='" ..
		tostring(session:getVariable("caller_id_number")) .. "' from='" ..
		tostring(session:getVariable("sip_from_user")) .. "')")
	play_and_hangup("ivr/ivr-that_was_an_invalid_entry.wav")
	return
end

-- ── Read current membership state ────────────────────────────────────────────
-- Two modes, decided by what follows *86:
--   *86<queue_extension>  (e.g. *86771) — toggle membership in THAT ONE queue.
--   *86  (no extension)   — master switch: if the agent is enabled in ANY queue,
--                            log them OUT of ALL queues; if they are disabled in
--                            every queue, log them IN to ALL queues.
local destination = tostring(session:getVariable("destination_number") or "")
local queue_ext = destination:match("^%*86(%d*)$")
if not queue_ext then
	log_err("unrecognised dialed string '" .. destination .. "' (expected *86 or *86<queue#>)")
	play_and_hangup("ivr/ivr-that_was_an_invalid_entry.wav")
	return
end

-- Build the queue filter for the chosen mode.
local scope_sql, scope_params, scope_label
if queue_ext ~= "" then
	local queue = dbh:first_row(
		"SELECT queue_uuid, queue_name FROM v_acd_queues " ..
		"WHERE domain_uuid = :domain_uuid AND queue_extension = :queue_ext",
		{ domain_uuid = domain_uuid, queue_ext = queue_ext }
	)
	if not queue or not queue.queue_uuid then
		log_err("no queue with extension " .. queue_ext .. " in domain " .. tostring(domain_name))
		play_and_hangup("ivr/ivr-that_was_an_invalid_entry.wav")
		return
	end
	scope_sql    = " AND queue_uuid = :queue_uuid"
	scope_params = { queue_uuid = queue.queue_uuid }
	scope_label  = "queue " .. queue_ext .. " (" .. tostring(queue.queue_name) .. ")"
else
	scope_sql    = ""
	scope_params = {}
	scope_label  = "ALL queues"
end

-- ── Read current membership state across the chosen scope ──────────────────────
local member_count = 0
local any_enabled  = false
do
	local params = { domain_uuid = domain_uuid, ext = agent_ext }
	for k, v in pairs(scope_params) do params[k] = v end
	dbh:query(
		"SELECT queue_member_enabled FROM v_acd_queue_members " ..
		"WHERE domain_uuid = :domain_uuid AND queue_member_number = :ext" .. scope_sql,
		params,
		function(row)
			member_count = member_count + 1
			if row.queue_member_enabled == "true" then any_enabled = true end
		end
	)
end

if member_count == 0 then
	log_err("extension " .. agent_ext .. " is not a member of " .. scope_label ..
		" in domain " .. tostring(domain_name))
	play_and_hangup("ivr/ivr-that_was_an_invalid_entry.wav")
	return
end

-- ── Flip the state ───────────────────────────────────────────────────────────
local new_state = any_enabled and "false" or "true"
do
	local params = { state = new_state, domain_uuid = domain_uuid, ext = agent_ext }
	for k, v in pairs(scope_params) do params[k] = v end
	dbh:query(
		"UPDATE v_acd_queue_members " ..
		"SET queue_member_enabled = :state, update_date = NOW() " ..
		"WHERE domain_uuid = :domain_uuid AND queue_member_number = :ext" .. scope_sql,
		params
	)
end

log_info("extension " .. agent_ext .. " set enabled=" .. new_state .. " in " .. scope_label ..
	" (" .. member_count .. " row(s)) domain " .. tostring(domain_name))

if new_state == "true" then
	play_and_hangup("ivr/ivr-you_are_now_logged_in.wav")
else
	play_and_hangup("ivr/ivr-you_are_now_logged_out.wav")
end
