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
	require "resources.functions.config";

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

	local log = require "resources.functions.log".call_flow

	local presence_in = require "resources.functions.presence_in"

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
		sql = "SELECT * FROM v_call_flows where call_flow_uuid = '"..call_flow_uuid.."'"
			-- .. "and call_flow_enabled = 'true'"
		--log.notice("SQL: %s", sql);

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

			if #call_flow_status == 0 then
				call_flow_status = "true";
			end
			if (call_flow_status == "true") then
				app = row.call_flow_app;
				data = row.call_flow_data
			else
				app = row.call_flow_anti_app;
				data = row.call_flow_anti_data
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
			toggle = (call_flow_status == "true") and "false" or "true"

		-- turn the lamp
			presence_in.turn_lamp( toggle == "false",
				call_flow_feature_code.."@"..domain_name,
				call_flow_uuid
			);

			local active_flow_label = (toggle == "true") and call_flow_label or call_flow_anti_label
		--answer and play a tone
			session:answer();
			if #active_flow_label > 0 then
				api = freeswitch.API();
				reply = api:executeString("uuid_display "..session:get_uuid().." "..active_flow_label);
			end
			session:execute("sleep", "2000");
			session:execute("playback", "tone_stream://%(200,0,500,600,700)");

		--show in the console
			log.noticef("label=%s,status=%s,uuid=%s", active_flow_label, toggle, call_flow_uuid);

		--store in database
			dbh:query("UPDATE v_call_flows SET call_flow_status = '"..toggle.."' WHERE call_flow_uuid = '"..call_flow_uuid.."'");

		--hangup the call
			session:hangup();
	else
		log.notice("execute " .. app .. " " .. data);

		--exucute the application
			session:execute(app, data);
		--timeout application
			--if (not session:answered()) then
			--	session:execute(ring_group_timeout_app, ring_group_timeout_data);
			--end
	end
end
