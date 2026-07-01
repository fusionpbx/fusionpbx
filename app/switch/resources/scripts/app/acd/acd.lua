--[[
	Advanced Call Distribution — Caller Entry
	/usr/local/freeswitch/scripts/app/acd/acd.lua

	Called by dialplan:
	  <action application="set" data="queue_uuid=UUID"/>
	  <action application="lua" data="app/acd/acd.lua"/>

	v3 architecture:
	  • Caller is answered and parked.
	  • Caller hears MOH via uuid_displace while waiting.
	  • Background orchestrator (`acd_orchestrator.lua`) asynchronously
	    originates agent legs tier-by-tier WITHOUT cancelling existing ringing legs.
	  • First answered agent leg runs `acd_agent_answer.lua`, atomically claims
	    winner in DB, stops caller MOH, and intercepts the parked caller.
	  • No conference rooms. No blocking bridge loop. No stop/re-ring on tier advance.
]]--

require "resources.functions.config"
local Database = require "resources.functions.database"
local api = freeswitch.API()

local function new_uuid()
	return api:executeString("create_uuid")
end

local function log_info(msg)
	freeswitch.consoleLog("notice", "[acd_cc] " .. tostring(msg) .. "\n")
end

local function log_err(msg)
	freeswitch.consoleLog("err", "[acd_cc] " .. tostring(msg) .. "\n")
end

-- Reliable channel-existence check. We deliberately avoid session:ready() for the
-- post-intercept wait below: while an agent leg intercepts the parked caller the
-- channel briefly passes through CS_HIBERNATE/CS_RESET, during which
-- session:ready() returns false even though the call is alive and bridging.
local function uuid_exists(uuid)
	return (tostring(api:executeString("uuid_exists " .. tostring(uuid))):gsub("%s+", "")) == "true"
end

if not session:ready() then return end

local queue_uuid  = session:getVariable("queue_uuid")
local domain_uuid = session:getVariable("domain_uuid")
local domain_name = session:getVariable("domain_name")
local caller_uuid = session:getVariable("uuid")

if not queue_uuid or queue_uuid == "" then
	log_err("queue_uuid channel variable not set — aborting")
	session:hangup("NORMAL_CLEARING")
	return
end

local dbh = Database.new('system')
if not dbh then
	log_err("could not connect to database")
	session:hangup("NORMAL_CLEARING")
	return
end

local queue = dbh:first_row(
	"SELECT * FROM v_acd_queues WHERE queue_uuid = :queue_uuid AND domain_uuid = :domain_uuid",
	{ queue_uuid = queue_uuid, domain_uuid = domain_uuid }
)

if not queue then
	log_err("Queue not found: " .. tostring(queue_uuid))
	session:hangup("NORMAL_CLEARING")
	return
end

local function build_moh_stream(hold_music)
	local moh = hold_music or "default"
	if not moh:find("local_stream://", 1, true) then
		moh = "local_stream://" .. moh
	end
	return moh
end

local moh_stream = build_moh_stream(queue.queue_hold_music)
local cc_session_uuid = new_uuid()

log_info("v3 Starting queue: " .. (queue.queue_name or queue_uuid) ..
	" | caller: " .. caller_uuid ..
	" | domain: " .. tostring(domain_name) ..
	" | cc_session: " .. cc_session_uuid)

-- Answer caller and track session before starting background orchestration.
session:answer()
session:execute("set", "hangup_after_bridge=true")
session:execute("set", "acd_cc_session_uuid=" .. cc_session_uuid)
session:execute("set", "acd_cc_queue_uuid=" .. queue_uuid)

dbh:query(
	"INSERT INTO v_acd_sessions " ..
	"(session_uuid, queue_uuid, domain_uuid, caller_channel_uuid, entered_at) " ..
	"VALUES (:session_uuid, :queue_uuid, :domain_uuid, :caller_channel_uuid, NOW())",
	{
		session_uuid        = cc_session_uuid,
		queue_uuid          = queue_uuid,
		domain_uuid         = domain_uuid,
		caller_channel_uuid = caller_uuid,
	}
)

local cleaned_up = false
local function cleanup()
	if cleaned_up then return end
	cleaned_up = true
	-- uuid_displace stop is a no-op if MOH already stopped or channel is gone.
	api:executeString("uuid_displace " .. caller_uuid .. " stop " .. moh_stream)
	dbh:query(
		"DELETE FROM v_acd_sessions WHERE session_uuid = :session_uuid",
		{ session_uuid = cc_session_uuid }
	)
	log_info("v3 caller cleanup complete: " .. cc_session_uuid)
end

-- Start async tier/originate engine. Args intentionally contain no spaces.
local cmd = "luarun app/acd/acd_orchestrator.lua " ..
	cc_session_uuid .. " " .. queue_uuid .. " " .. domain_uuid .. " " .. domain_name .. " " .. caller_uuid
log_info("v3 starting orchestrator: " .. cmd)
api:executeString(cmd)

-- Caller waits here on hold music until one of these occurs:
--   • a winning agent leg intercepts the caller → playback is broken and the
--     channel is pulled into the bridge. We detect the claimed winner below and
--     return immediately so intercept can complete (see notes further down).
--   • the orchestrator times out and uuid_breaks the playback.
--   • the caller hangs up.
--
-- IMPORTANT: we PLAY the hold music on the caller's own channel instead of
-- parking the channel and uuid_displace'ing MOH onto it. A parked channel
-- produces no outbound audio frames, so a write-replace media bug
-- (uuid_displace) has nothing to act on and the caller hears SILENCE. playback
-- writes real audio frames, so hold music is always audible. The winning
-- agent's `intercept` breaks this playback exactly like it broke park, so the
-- bridge handoff is unchanged.
session:setVariable("playback_terminators", "none")
while session:ready() do
	-- local_stream is an endless source: this blocks until intercept (winner),
	-- uuid_break (timeout), or caller hangup.
	session:execute("playback", moh_stream)

	-- playback returned — decide whether we are done.
	if not session:ready() then break end

	local row = dbh:first_row(
		"SELECT winner_leg_uuid FROM v_acd_sessions WHERE session_uuid = :session_uuid",
		{ session_uuid = cc_session_uuid }
	)
	if row and row.winner_leg_uuid and row.winner_leg_uuid ~= "" then break end

	if session:getVariable("acd_queue_timeout") == "true" then break end

	-- Unexpected early return (e.g. transient stream issue): guard against a hot
	-- loop, then replay the music.
	session:sleep(100)
end

-- Was the caller intercepted by an agent (i.e. did a winner get claimed)?
-- This decides whether park returned because of a successful bridge or because
-- of timeout / caller-hangup / something else.
local winner_claimed = false
do
	local row = dbh:first_row(
		"SELECT winner_leg_uuid FROM v_acd_sessions WHERE session_uuid = :session_uuid",
		{ session_uuid = cc_session_uuid }
	)
	if row and row.winner_leg_uuid and row.winner_leg_uuid ~= "" then
		winner_claimed = true
		log_info("v3 caller: bridged via winner " .. row.winner_leg_uuid .. "; awaiting bridge end")
	end
end

if winner_claimed then
	-- A winning agent leg has answered and is intercepting/bridging this caller.
	--
	-- We MUST return immediately. This is the caller's foreground (park) thread,
	-- and the intercept can only pull the channel into CS_EXCHANGE_MEDIA once this
	-- thread releases the channel. If we instead sit in a wait loop here, the
	-- channel is pinned in CS_HIBERNATE, never reaches CS_EXCHANGE_MEDIA, and the
	-- call connects with NO AUDIO. (The earlier session:ready() loop "worked" for
	-- audio only because it happened to exit instantly and return.)
	--
	-- We deliberately DO NOT cleanup()/delete the session row here:
	--   • MOH was already stopped by the agent-answer script before intercept.
	--   • The orchestrator owns the session-row lifecycle now: it still needs
	--     winner_leg_uuid to cancel the losing legs, then it waits for the bridged
	--     call to end and deletes the row itself. Deleting it here would make the
	--     orchestrator see "row gone" and cancel the winner, dropping the call.
	--
	-- hangup_after_bridge=true (set on both legs) tears the caller down cleanly
	-- when the conversation ends.
	log_info("v3 caller: winner claimed; releasing channel to bridge (orchestrator owns cleanup)")
	return
end

-- No winner. Either the orchestrator timed us out, or the caller hung up, or
-- something else broke the park. Handle timeout path first.
local timed_out = session:getVariable("acd_queue_timeout")
if session:ready() and timed_out == "true" then
	cleanup()
	local timeout_app  = queue.queue_timeout_app or ""
	local timeout_data = queue.queue_timeout_data or ""
	log_info("v3 queue timeout action: " .. tostring(timeout_app) .. " " .. tostring(timeout_data))
	if timeout_app ~= "" and timeout_data ~= "" then
		session:execute(timeout_app, timeout_data)
	else
		session:hangup("NORMAL_CLEARING")
	end
	return
end

cleanup()

-- Caller is still up but no bridge and no timeout — unexpected. Hang up cleanly.
if session:ready() then
	session:hangup("NORMAL_CLEARING")
end
