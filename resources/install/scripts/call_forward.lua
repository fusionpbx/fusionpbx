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
--	Copyright (C) 2010-2014
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set default variables
	min_digits = "1";
	max_digits = "11";
	max_tries = "3";
	digit_timeout = "3000";

--debug
	debug["sql"] = false;

--define the trim function
	require "resources.functions.trim"

--define the explode function
	require "resources.functions.explode"

--create the api object
	api = freeswitch.API();

--include config.lua
	require "resources.functions.config";

--check if the session is ready
	if (session:ready()) then
		--answer the call
			session:answer();

		--get the variables
			enabled = session:getVariable("enabled");
			pin_number = session:getVariable("pin_number");
			sounds_dir = session:getVariable("sounds_dir");
			domain_uuid = session:getVariable("domain_uuid");
			domain_name = session:getVariable("domain_name");
			extension_uuid = session:getVariable("extension_uuid");
			context = session:getVariable("context");
			if (not context ) then context = 'default'; end
			request_id = session:getVariable("request_id");

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--a moment to sleep
			session:sleep(1000);

		--connect to the database
			require "resources.functions.database_handle";
			dbh = database_handle('system');

		--request id is true
			if (request_id == "true") then
				--unset extension uuid
					extension_uuid = nil;

				--get the id
					if (session:ready()) then
						min_digits = 2;
						max_digits = 20;
						id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");
					end

				--get the pin number
					if (session:ready()) then
						min_digits = 3;
						max_digits = 20;
						caller_pin_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
					end

				--check to see if the pin number is correct
					if (session:ready()) then
						sql = "SELECT * FROM v_voicemails ";
						sql = sql .. "WHERE domain_uuid = '" .. domain_uuid .."' ";
						sql = sql .. "AND voicemail_id = '" .. id .."' ";
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[call_forward] "..sql .."\n");
						end
						dbh:query(sql, function(row)
							voicemail_password = row.voicemail_password;
							--freeswitch.consoleLog("notice", "[call_forward] "..voicemail_password .."\n");
						end);
						if (voicemail_password ~= caller_pin_number) then
							--access denied
							session:streamFile("phrase:voicemail_fail_auth:#");
							session:hangup("NORMAL_CLEARING");
						end
					end
			end

		--determine whether to update the dial string
			if (session:ready()) then
				sql = "select * from v_extensions ";
				sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
				if (extension_uuid ~= nil) then
					sql = sql .. "and extension_uuid = '"..extension_uuid.."' ";
				else
					sql = sql .. "and (extension = '"..id.."' or number_alias = '"..id.."') ";
				end
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[call_forward] "..sql.."\n");
				end
				status = dbh:query(sql, function(row)
					extension_uuid = row.extension_uuid;
					extension = row.extension;
					number_alias = row.number_alias;
					accountcode = row.accountcode;
					forward_all_enabled = row.forward_all_enabled;
					forward_all_destination = row.forward_all_destination;
					follow_me_uuid = row.follow_me_uuid;
					toll_allow = row.toll_allow or '';
					--freeswitch.consoleLog("NOTICE", "[call forward] extension "..row.extension.."\n");
					--freeswitch.consoleLog("NOTICE", "[call forward] accountcode "..row.accountcode.."\n");
				end);
			end

		--toggle enabled
			if (session:ready() and enabled == "toggle") then
				if (forward_all_enabled == "true") then
					enabled = "false";
				else
					enabled = "true";
				end
			end

		--get the forward destination
			if (session:ready() and (enabled == "true" or enabled == "toggle") ) then
				if (string.len(forward_all_destination) == 0) then
					forward_all_destination = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");
				end
			end

		--set the dial string
			if (session:ready() and enabled == "true") then
				--used for number_alias to get the correct user
				sql = "select * from v_extensions ";
				sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
				sql = sql .. "and number_alias = '"..forward_all_destination.."' ";
				status = dbh:query(sql, function(row)
					destination_user = row.extension;
				end);

				--set the dial_string
				dial_string = "{presence_id="..forward_all_destination.."@"..domain_name;
				dial_string = dial_string .. ",instant_ringback=true";
				dial_string = dial_string .. ",domain_uuid="..domain_uuid;
				dial_string = dial_string .. ",sip_invite_domain="..domain_name;
				dial_string = dial_string .. ",domain_name="..domain_name;
				dial_string = dial_string .. ",domain="..domain_name;
				dial_string = dial_string .. ",toll_allow='"..toll_allow.."'";
				if (accountcode ~= nil) then
					dial_string = dial_string .. ",accountcode="..accountcode;
				end
				dial_string = dial_string .. "}";

				if (destination_user ~= nil) then
					cmd = "user_exists id ".. destination_user .." "..domain_name;
				else
					cmd = "user_exists id ".. forward_all_destination .." "..domain_name;
				end
				user_exists = trim(api:executeString(cmd));
				if (user_exists == "true") then
					if (destination_user ~= nil) then
						dial_string = dial_string .. "user/"..destination_user.."@"..domain_name;
					else
						dial_string = dial_string .. "user/"..forward_all_destination.."@"..domain_name;
					end
				else
					dial_string = dial_string .. "loopback/"..forward_all_destination;
				end
			end

		--set call forward
			if (session:ready() and enabled == "true") then
				--set forward_all_enabled
					forward_all_enabled = "true";
				--say the destination number
					session:say(forward_all_destination, default_language, "number", "iterated");
				--notify the caller
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-call_forwarding_has_been_set.wav");
			end

		--unset call forward
			if (session:ready() and enabled == "false") then
				--set forward_all_enabled
					forward_all_enabled = "false";
				--notify the caller
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-call_forwarding_has_been_cancelled.wav");
			end

		--disable the follow me
			if (session:ready() and enabled == "true" and follow_me_uuid ~= nil) then
				if (string.len(follow_me_uuid) > 0) then
					sql = "update v_follow_me set ";
					sql = sql .. "follow_me_enabled = 'false' ";
					sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
					sql = sql .. "and follow_me_uuid = '"..follow_me_uuid.."' ";
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[call_forward] "..sql.."\n");
					end
					dbh:query(sql);
				end
			end

		--check the destination
			if (forward_all_destination == nil) then
				enabled = false;
				forward_all_enabled = "false";
			else
				if (string.len(forward_all_destination) == 0) then
					enabled = false;
					forward_all_enabled = "false";
				end	
			end

		--update the extension
			if (session:ready()) then
				sql = "update v_extensions set ";
				if (enabled == "true") then
					sql = sql .. "forward_all_destination = '"..forward_all_destination.."', ";
					sql = sql .. "dial_string = '"..dial_string:gsub("'", "''").."', ";
					sql = sql .. "do_not_disturb = 'false', ";
				else
					sql = sql .. "forward_all_destination = null, ";
					sql = sql .. "dial_string = null, ";
				end
				sql = sql .. "forward_all_enabled = '"..forward_all_enabled.."' ";
				sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
				sql = sql .. "and extension_uuid = '"..extension_uuid.."' ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[call_forward] "..sql.."\n");
				end
				dbh:query(sql);
			end

		--clear the cache and hangup
			if (session:ready()) then
				--clear the cache
					if (extension ~= nil) then
						api:execute("memcache", "delete directory:"..extension.."@"..domain_name);
					end

				--wait for the file to be written before proceeding
					session:sleep(100);

				--end the call
					session:hangup();
			end
	end