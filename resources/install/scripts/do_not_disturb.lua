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
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

--create the api object
	api = freeswitch.API();

--include config.lua
	require "resources.functions.config";

--check if the session is ready
	if ( session:ready() ) then
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

		--determine whether to update the dial string
			sql = "select * from v_extensions ";
			sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
			sql = sql .. "and extension_uuid = '"..extension_uuid.."' ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[do_not_disturb] "..sql.."\n");
			end
			status = dbh:query(sql, function(row)
				extension = row.extension;
				number_alias = row.number_alias;
				accountcode = row.accountcode;
				follow_me_uuid = row.follow_me_uuid;
				--freeswitch.consoleLog("NOTICE", "[do_not_disturb] extension "..row.extension.."\n");
				--freeswitch.consoleLog("NOTICE", "[do_not_disturb] accountcode "..row.accountcode.."\n");
			end);

		--set the dial string
			if (enabled == "true") then
				local user = (number_alias and #number_alias > 0) and number_alias or extension;
				dial_string = "loopback/*99"..user;
			end

		--set do not disturb
			if (enabled == "true") then
				--set do_not_disturb_enabled
					do_not_disturb_enabled = "true";
				--notify the caller
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-dnd_activated.wav");
			end

		--unset do not disturb
			if (enabled == "false") then
				--set fdo_not_disturb_enabled
					do_not_disturb_enabled = "false";
				--notify the caller
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-dnd_cancelled.wav");
			end

		--disable follow me
			if (follow_me_uuid ~= nil) then
				if (string.len(follow_me_uuid) > 0 and enabled == "true") then
					sql = "update v_follow_me set ";
					sql = sql .. "follow_me_enabled = 'false' ";
					sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
					sql = sql .. "and follow_me_uuid = '"..follow_me_uuid.."' ";
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[do_not_disturb] "..sql.."\n");
					end
					dbh:query(sql);
				end
			end

		--update the extension
			sql = "update v_extensions set ";
			if (enabled == "true") then
				sql = sql .. "dial_string = '"..dial_string.."', ";
				sql = sql .. "do_not_disturb = 'true', ";
			else
				sql = sql .. "dial_string = null, ";
				sql = sql .. "do_not_disturb = 'false', ";
			end
			sql = sql .. "forward_all_enabled = 'false' ";
			sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
			sql = sql .. "and extension_uuid = '"..extension_uuid.."' ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[do_not_disturb] "..sql.."\n");
			end
			dbh:query(sql);

		--clear the cache
			if (extension ~= nil) then
				api:execute("memcache", "delete directory:"..extension.."@"..domain_name);
			end

		--wait for the file to be written before proceeding
			session:sleep(1000);

		--end the call
			session:hangup();

	end