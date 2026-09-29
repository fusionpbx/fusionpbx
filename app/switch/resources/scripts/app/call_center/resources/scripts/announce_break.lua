--
--	Stops the queue position announcement (uuid_broadcast) when an agent answers the call.
--
--	Hook config in /etc/freeswitch/autoload_configs/lua.conf.xml:
--	<hook event="CUSTOM" subclass="callcenter::info" script="app/call_center/resources/scripts/announce_break.lua"/>
--
--	When mod_callcenter bridges the member to the agent, it fires a CUSTOM event with
--	subclass "callcenter::info" and CC-Action: "bridge-agent-start". This script
--	immediately breaks the active uuid_broadcast so the caller hears the agent right away.
--

--prepare the api object
	api = freeswitch.API();

--get the event variables
	cc_action = event:getHeader("CC-Action");

--only act when the agent answers (bridge starts)
	if cc_action == "bridge-agent-start" then
		local member_session_uuid = event:getHeader("CC-Member-Session-UUID");
		local queue = event:getHeader("CC-Queue") or "unknown";
		local agent = event:getHeader("CC-Agent") or "unknown";

		if member_session_uuid and member_session_uuid ~= "" then
			freeswitch.consoleLog("notice", "[call_center_announce] Agent [" .. agent .. "] bridged in queue [" .. queue .. "], stopping broadcast on " .. member_session_uuid .. "\n");

			local result = api:executeString("uuid_break " .. member_session_uuid .. " all");
			freeswitch.consoleLog("notice", "[call_center_announce] uuid_break result: " .. tostring(result) .. "\n");
		end
	end
