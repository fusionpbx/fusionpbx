-- Agent-side synchronous recording guard. Invoked by execute_on_pre_bridge.
-- It intentionally fails closed: unknown channel/bug state never means absent.
local self_test = argv[1] == "--self-test"
local self_case = argv[2] or "active-peer"
local target_uuid = self_test and "target" or (argv[1] or session:get_uuid())
local own_uuid = self_test and "own" or session:get_uuid()
local peer_uuid = self_test and "peer" or (session:getVariable("bridge_uuid") or "")
local scoped_agent = self_test and "agent" or (argv[2] or "")
local scoped_queue = self_test and "queue" or (argv[3] or "")

local function diagnostic(outcome, recheck)
	session:setVariable("recording_coordinator_outcome", outcome)
	if recheck then
		local attempts = tonumber(session:getVariable("recording_coordinator_recheck_attempt") or "0") or 0
		if attempts < 2 then
			session:setVariable("recording_coordinator_recheck_attempt", tostring(attempts + 1))
			session:setVariable("recording_coordinator_recheck_needed", "true")
		else
			session:setVariable("recording_coordinator_recheck_needed", "false")
		end
	end
	freeswitch.consoleLog("warning", "[recording_coordinator] agent guard " .. outcome .. " own=" .. own_uuid .. " peer=" .. peer_uuid .. "\n")
end

local function api(command)
	if self_test then
		if string.match(command, "^uuid_exists ") then return "true" end
		if self_case == "malformed" then return "<media-bugs><media-bug>" end
		if self_case == "active-peer" and command == "uuid_buglist peer" then return "<media-bugs><media-bug><function>session_record</function><target>/queue.wav</target></media-bug></media-bugs>" end
		if self_case == "duplicate-own" and command == "uuid_buglist own" then return "<media-bugs><media-bug><function>tone_detect</function><target>/session_record.wav</target></media-bug><media-bug><function>session_record</function><target>/agent.wav</target></media-bug><media-bug><function>session_record</function><target>/other.wav</target></media-bug></media-bugs>" end
		return "<media-bugs></media-bugs>"
	end
	local result = freeswitch.API():executeString(command)
	if not result or result == "" or string.match(result, "^%-ERR") then return nil end
	return result
end

local function recorder_state(uuid)
	if uuid == "" or not string.match(api("uuid_exists " .. uuid) or "", "^%s*true%s*$") then return "unknown", nil end
	local bugs = api("uuid_buglist " .. uuid)
	if not bugs or not string.match(bugs, "^%s*<media%-bugs[ >]") or not string.match(bugs, "</media%-bugs>%s*$") then return "unknown", nil end
	for bug in string.gmatch(bugs, "<media%-bug[^>]*>(.-)</media%-bug>") do
		if string.match(bug, "<function>%s*session_record%s*</function>") then
			local path = string.match(bug, "<target>(.-)</target>")
			if not path or path == "" then return "unknown", nil end
			return "active", path
		end
	end
	return "absent", nil
end

local function native_record(target)
	local recordings_dir = freeswitch.getGlobalVariable("recordings_dir")
	local domain_name = session:getVariable("domain_name")
	local record_ext = session:getVariable("record_ext") or "wav"
	if not recordings_dir or not domain_name then
		diagnostic("path_unknown", true)
		return false
	end
	local path = string.format("%s/%s/archive/%s/%s/%s/%s.%s", recordings_dir, domain_name, os.date("%Y"), os.date("%b"), os.date("%d"), target, record_ext)
	session:setVariable("recording_coordinator_start_requested", "true")
	session:execute("record_session", path)
	return true
end

if self_test then
	local own_state, own_target = recorder_state(own_uuid)
	local peer_state, peer_target = recorder_state(peer_uuid)
	local expected = self_case == "malformed" and "unknown" or (self_case == "absent" and "absent" or "active")
	if own_state ~= expected and peer_state ~= expected then error("unexpected self-test state") end
	local expected_target = self_case == "active-peer" and "/queue.wav" or (self_case == "duplicate-own" and "/agent.wav" or nil)
	if expected_target and own_target ~= expected_target and peer_target ~= expected_target then error("unexpected self-test target") end
	freeswitch.consoleLog("notice", "[recording_coordinator] agent guard self-test " .. self_case .. " OK\n")
	return "+OK agent_recording_guard self-test " .. self_case
else
-- This is an agent-specific hook. The runtime pair is checked because a
-- callback agent may be a member of more than one queue. An unmatched call
-- takes the same native start path it had before the guard was installed.
local actual_agent = session:getVariable("cc_agent") or ""
local actual_queue = session:getVariable("cc_queue") or ""
if scoped_agent == "" or scoped_queue == "" or actual_agent ~= scoped_agent or actual_queue ~= scoped_queue then
	diagnostic("scope_unmatched", false)
	native_record(target_uuid)
	return
end

local own_state = recorder_state(own_uuid)
local peer_state = recorder_state(peer_uuid)
if own_state == "unknown" or peer_state == "unknown" then
	-- Do not start on uncertainty. A future event worker may reinvoke the
	-- guard at most twice and must perform the same two-leg observation.
	diagnostic("status_unknown", true)
	return
end
if own_state == "active" or peer_state == "active" then
	diagnostic("already_active", false)
	return
end

if not native_record(target_uuid) then return end
local verified = recorder_state(own_uuid)
if verified == "active" then diagnostic("active_verified", false) else diagnostic("start_unverified", true) end
end
