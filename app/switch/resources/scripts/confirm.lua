--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2010-2024
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--include config.lua
require "resources.functions.config";

--set variables
	digit_timeout = "5000";

--check if a file exists
	require "resources.functions.file_exists"

--run if the session is ready
	if ( session:ready() ) then
		--answer the call
			session:answer();

		--add short delay before playing the audio
			--session:sleep(1000);

		--get the variables
			call_uuid = session:getVariable("call_uuid");
			domain_name = session:getVariable("domain_name");
			context = session:getVariable("context");
			sounds_dir = session:getVariable("sounds_dir");
			destination_number = session:getVariable("destination_number");
			caller_id_number = session:getVariable("caller_id_number");
			record_ext = session:getVariable("record_ext");

		--confirm or not to confirm
			if (session:getVariable("confirm")) then
				confirm = session:getVariable("confirm");
			end

		--prepare the api
			api = freeswitch.API();

		--get the domain_name with a different variable if the domain_name is not set
			if (not domain_name) then 
				domain_name = session:getVariable("sip_invite_domain");
			end

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--create the settings object
			--local Settings = require "resources.functions.lazy_settings";
			--local settings = Settings.new(dbh, domain_name, domain_uuid);

		--get the recordings dir
			--recordings_dir = settings:get('switch', 'recordings', 'dir');

		--set the default record extension
			if (record_ext == nil) then
				record_ext = 'wav';
			end

		--prepare the recording path
			record_path = recordings_dir .. "/" .. domain_name .. "/archive/" .. os.date("%Y/%b/%d");
			record_path = record_path:gsub("\\", "/");

		--if the screen file is found then set confirm to true
			if (domain_name ~= nil) then
				if (file_exists(temp_dir .. "/" .. domain_name .. "-" .. caller_id_number .. "." .. record_ext)) then
					call_screen_file = temp_dir .. "/" .. domain_name .. "-" .. caller_id_number .. "." .. record_ext;
					confirm = "true";
				end
				if (file_exists(record_path.."/call_screen."..call_uuid .."."..record_ext)) then
					call_screen_file = record_path.."/call_screen."..call_uuid .."."..record_ext;
					confirm = "true";
				end
			end

		--confirm the calls
			--prompt for digits
				if (confirm == "true") then
					--send to the log
						--freeswitch.consoleLog("NOTICE", "[confirm] prompt\n");
					--get the digit
						min_digits = 1;
						max_digits = 1;
						digit = '';
						if (call_screen_file ~= nil) then
							max_tries = "1";
							digit = session:playAndGetDigits(min_digits, max_digits, max_tries, "500", "#", call_screen_file:gsub("\\","/"), "", "\\d+");
						end
						if (string.len(digit) == 0) then
							max_tries = "3";
							digit = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-accept_reject_voicemail.wav", "", "\\d+");
						end
					--process the response
						if (digit == "1") then
							--freeswitch.consoleLog("NOTICE", "[confirm] accept\n");
						elseif (digit == "2") then
							--freeswitch.consoleLog("NOTICE", "[confirm] reject\n");
							session:hangup("CALL_REJECTED"); --LOSE_RACE
						elseif (digit == "3") then
							--freeswitch.consoleLog("NOTICE", "[confirm] voicemail\n");
							session:hangup("NO_ANSWER");
						else
							--freeswitch.consoleLog("NOTICE", "[confirm] no answer\n");
							session:hangup("NO_ANSWER");
						end
				else
					--send to the log
						--freeswitch.consoleLog("NOTICE", "[confirm] automatically accepted\n");
				end
	end
