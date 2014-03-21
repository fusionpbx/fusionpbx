--	intercom.lua
--	Part of FusionPBX
--	Copyright (C) 2010 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--set the variables
	max_tries = "3";
	digit_timeout = "5000";

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

if (session:ready()) then
	--get the variables
		domain_name = session:getVariable("domain_name");
		call_flow_uuid = session:getVariable("call_flow_uuid");
		sounds_dir = session:getVariable("sounds_dir");
		feature_code = session:getVariable("feature_code");

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--get the extension list
		sql = [[SELECT * FROM v_call_flows
		where call_flow_uuid = ']]..call_flow_uuid..[[']]
		--and call_flow_enabled = 'true' 
		--freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");
		app_data = "";

		x = 0;
		dbh:query(sql, function(row)
			call_flow_name = row.call_flow_name;
			call_flow_extension = row.call_flow_extension;
			call_flow_feature_code = row.call_flow_feature_code;
			--call_flow_context = row.call_flow_context;
			call_flow_status = row.call_flow_status;
			pin_number = row.call_flow_pin_number;
			call_flow_label = row.call_flow_label;
			call_flow_anti_label = row.call_flow_anti_label;

			if (string.len(call_flow_status) == 0) then
				app = row.call_flow_app;
				data = row.call_flow_data
			else
				if (call_flow_status == "true") then
					app = row.call_flow_app;
					data = row.call_flow_data
				else
					app = row.call_flow_anti_app;
					data = row.call_flow_anti_data
				end
			end
		end);

	if (feature_code == "true") then
		--if the pin number is provided then require it
			if (string.len(pin_number) > 0) then
				min_digits = string.len(pin_number);
				max_digits = string.len(pin_number)+1;
				session:answer();
				digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
				if (digits == pin_number) then
					--pin is correct
				else
					session:streamFile("phrase:voicemail_fail_auth:#");
					session:hangup("NORMAL_CLEARING");
					return;
				end
			end

		--feature code - toggle the status
			if (string.len(call_flow_status) == 0) then
				toggle = "false";
			else
				if (call_flow_status == "true") then
					toggle = "false";
				else
					toggle = "true";
				end
			end
			if (toggle == "true") then
				--set the presence to terminated - turn the lamp off:
					event = freeswitch.Event("PRESENCE_IN");
					event:addHeader("proto", "sip");
					event:addHeader("event_type", "presence");
					event:addHeader("alt_event_type", "dialog");
					event:addHeader("Presence-Call-Direction", "outbound");
					event:addHeader("state", "Active (1 waiting)");
					event:addHeader("from", call_flow_feature_code.."@"..domain_name);
					event:addHeader("login", call_flow_feature_code.."@"..domain_name);
					event:addHeader("unique-id", call_flow_uuid);
					event:addHeader("answer-state", "terminated");
					event:fire();
				--answer and play a tone
					session:answer();
					if (string.len(call_flow_label) > 0) then
						api = freeswitch.API();
						reply = api:executeString("uuid_display "..session:get_uuid().." "..call_flow_label);
					end
					session:execute("sleep", "2000");
					session:execute("playback", "tone_stream://%(200,0,500,600,700)");
				--show in the console
					freeswitch.consoleLog("notice", "Call Flow: label="..call_flow_label..",status=true,uuid="..call_flow_uuid.."\n");
			else
				--set presence in - turn lamp on
					event = freeswitch.Event("PRESENCE_IN");
					event:addHeader("proto", "sip");
					event:addHeader("login", call_flow_feature_code.."@"..domain_name);
					event:addHeader("from", call_flow_feature_code.."@"..domain_name);
					event:addHeader("status", "Active (1 waiting)");
					event:addHeader("rpid", "unknown");
					event:addHeader("event_type", "presence");
					event:addHeader("alt_event_type", "dialog");
					event:addHeader("event_count", "1");
					event:addHeader("unique-id", call_flow_uuid);
					event:addHeader("Presence-Call-Direction", "outbound")
					event:addHeader("answer-state", "confirmed");
					event:fire();
				--answer and play a tone
					session:answer();
					if (string.len(call_flow_anti_label) > 0) then
						api = freeswitch.API();
						reply = api:executeString("uuid_display "..session:get_uuid().." "..call_flow_anti_label);
					end
					session:execute("sleep", "2000");
					session:execute("playback", "tone_stream://%(500,0,300,200,100,50,25)");
				--show in the console
					freeswitch.consoleLog("notice", "Call Flow: label="..call_flow_anti_label..",status=false,uuid="..call_flow_uuid.."\n");
			end
			dbh:query("UPDATE v_call_flows SET call_flow_status = '"..toggle.."' WHERE call_flow_uuid = '"..call_flow_uuid.."'");
		--hangup the call
			session:hangup();
	else 
		--app_data
			freeswitch.consoleLog("notice", "Call Flow: " .. app .. " " .. data .. "\n");

		--exucute the application
			session:execute(app, data);
		--timeout application
			--if (not session:answered()) then
			--	session:execute(ring_group_timeout_app, ring_group_timeout_data);
			--end
	end
end
