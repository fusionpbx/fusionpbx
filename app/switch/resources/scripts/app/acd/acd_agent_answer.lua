--[[
	Advanced Call Distribution — Agent Answer Handler

	Runs as the application on an originated agent leg:
	  &lua(app/acd/acd_agent_answer.lua <session_uuid> <caller_uuid> <queue_uuid> <domain_uuid> <agent_ext> <destination> <leg_uuid> <moh_stream>)

	When this script runs, the agent leg has answered. It atomically claims the
	queue session as the winner, stops caller MOH, and intercepts the parked caller.
]]--

require "resources.functions.config"
local Database = require "resources.functions.database"
local api = freeswitch.API()

local cc_session_uuid = argv and argv[1] or ""
local caller_uuid     = argv and argv[2] or ""
local queue_uuid      = argv and argv[3] or ""
local domain_uuid     = argv and argv[4] or ""
local agent_ext       = argv and argv[5] or ""
local destination     = argv and argv[6] or ""
local leg_uuid        = argv and argv[7] or ""
local moh_stream      = argv and argv[8] or "local_stream://default"

local function trim(s)
	return (tostring(s or ""):gsub("^%s+", ""):gsub("%s+$", ""))
end

local function uuid_exists(uuid)
	return trim(api:executeString("uuid_exists " .. uuid)) == "true"
end

local function log_info(msg)
	freeswitch.consoleLog("notice", "[acd_cc_answer] " .. tostring(msg) .. "\n")
end

local function log_err(msg)
	freeswitch.consoleLog("err", "[acd_cc_answer] " .. tostring(msg) .. "\n")
end

if not session:ready() then return end

if cc_session_uuid == "" or caller_uuid == "" or leg_uuid == "" then
	log_err("missing argv; session=" .. tostring(cc_session_uuid) .. " caller=" .. tostring(caller_uuid) .. " leg=" .. tostring(leg_uuid))
	session:hangup("NORMAL_CLEARING")
	return
end

local dbh = Database.new('system')
if not dbh then
	log_err("could not connect to database")
	session:hangup("NORMAL_CLEARING")
	return
end

-- Atomically claim winner. Only the first answered leg wins.
local won = false
dbh:query(
	"UPDATE v_acd_sessions " ..
	"SET winner_leg_uuid = :leg_uuid, " ..
	"    winner_agent_extension = :agent_ext, " ..
	"    winner_destination = :destination, " ..
	"    answered_at = NOW() " ..
	"WHERE session_uuid = :session_uuid " ..
	"AND winner_leg_uuid IS NULL " ..
	"RETURNING session_uuid",
	{
		leg_uuid     = leg_uuid,
		agent_ext    = agent_ext,
		destination  = destination,
		session_uuid = cc_session_uuid,
	},
	function(row)
		won = true
	end
)

if not won then
	log_info("leg " .. leg_uuid .. " answered but did not win; hanging up")
	dbh:query("DELETE FROM v_acd_busy WHERE leg_uuid = :leg_uuid", { leg_uuid = leg_uuid })
	session:hangup("ORIGINATOR_CANCEL")
	return
end

if not uuid_exists(caller_uuid) then
	log_info("leg " .. leg_uuid .. " won but caller is gone; cleanup")
	dbh:query("DELETE FROM v_acd_busy WHERE leg_uuid = :leg_uuid", { leg_uuid = leg_uuid })
	session:hangup("ORIGINATOR_CANCEL")
	return
end

log_info("winner leg " .. leg_uuid .. " agent=" .. tostring(agent_ext) .. " dest=" .. tostring(destination) .. " intercepting caller " .. caller_uuid)

-- Stop caller MOH before bridge/intercept. Safe no-op if already stopped.
api:executeString("uuid_displace " .. caller_uuid .. " stop " .. moh_stream)

-- Ensure both sides end together when either hangs up.
session:execute("set", "hangup_after_bridge=true")
api:executeString("uuid_setvar " .. caller_uuid .. " hangup_after_bridge true")

-- Intercept the parked caller. This should create a normal two-party bridge
-- from the answering agent leg to the caller without conference rooms and
-- without externally forcing uuid_bridge from the caller's lua thread.
session:execute("intercept", caller_uuid)

-- When intercept returns, bridged conversation is over (or intercept failed).
log_info("intercept finished for leg " .. leg_uuid .. "; cleaning busy row")
dbh:query("DELETE FROM v_acd_busy WHERE leg_uuid = :leg_uuid", { leg_uuid = leg_uuid })

-- Caller entry script will remove the session row when park returns / caller ends.
