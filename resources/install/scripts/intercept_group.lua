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
--	Copyright (C) 2010 - 2014
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--user defined variables
	max_tries = "3";
	digit_timeout = "5000";
	extension = argv[1];

--set the debug options
	debug["sql"] = false;

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--add the function
	dofile(scripts_dir.."/resources/functions/explode.lua");

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--check if the session is ready
	if ( session:ready() ) then
		--answer the session
			session:answer();

		--get session variables
			domain_uuid = session:getVariable("domain_uuid");
			domain_name = session:getVariable("domain_name");
			pin_number = session:getVariable("pin_number");
			sounds_dir = session:getVariable("sounds_dir");
			context = session:getVariable("context");
			caller_id_number = session:getVariable("caller_id_number");
			sofia_profile_name = session:getVariable("sofia_profile_name");

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--set defaults
			if (digit_min_length) then
				--do nothing
			else
				digit_min_length = "2";
			end

			if (digit_max_length) then
				--do nothing
			else
				digit_max_length = "11";
			end

		--if the pin number is provided then require it
			if (pin_number) then
				--sleep
					session:sleep(500);
				--get the user pin number
					min_digits = 2;
					max_digits = 20;
					digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
				--validate the user pin number
					pin_number_table = explode(",",pin_number);
					for index,pin_number in pairs(pin_number_table) do
						if (digits == pin_number) then
							--set the variable to true
								auth = true;
							--set the authorized pin number that was used
								session:setVariable("pin_number", pin_number);
							--end the loop
								break;
						end
					end
				--if not authorized play a message and then hangup
					if (not auth) then
						session:streamFile("phrase:voicemail_fail_auth:#");
						session:hangup("NORMAL_CLEARING");
						return;
					end
			end

		--get the call groups the extension is a member of
			sql = "SELECT call_group FROM v_extensions ";
			sql = sql .. "WHERE domain_uuid = '"..domain_uuid.."' ";
			sql = sql .. "AND extension = '"..caller_id_number.."'";
			status = dbh:query(sql, function(row)
				call_group = row.call_group;
				freeswitch.consoleLog("NOTICE", "result "..call_group.."\n");
			end);
			call_groups = explode(",", call_group);

		--get the extensions in the call groups
			sql = "SELECT extension FROM v_extensions ";
			sql = sql .. "WHERE domain_uuid = '"..domain_uuid.."' ";
			sql = sql .. "AND (";
			x = 0;
			for key,call_group in pairs(call_groups) do
				if (x == 0) then
					if (string.len(call_group) > 0) then
						sql = sql .. "call_group like '%"..call_group.."%' ";
					else
						sql = sql .. "call_group = '' ";
					end
				else
					if (string.len(call_group) > 0) then
						sql = sql .. "OR call_group like '%"..call_group.."%' ";
					end
				end
				x = x + 1;
			end
			x = 0;
			sql = sql .. ") ";
			freeswitch.consoleLog("NOTICE", "result "..sql.."\n");
			extensions = {}
			status = dbh:query(sql, function(row)
				extensions[x] = row.extension;
				freeswitch.consoleLog("NOTICE", "result "..row.extension.."\n");
				x = x + 1;
			end);

		--connect to the database
			if (file_exists(database_dir.."/core.db")) then
				--dbh = freeswitch.Dbh("core:core"); -- when using sqlite
				dbh = freeswitch.Dbh("sqlite://"..database_dir.."/core.db");
			else
				dofile(scripts_dir.."/resources/functions/database_handle.lua");
				dbh = database_handle('switch');
			end

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--check the database to get the uuid of a ringing call
			call_hostname = "";
			sql = "SELECT call_uuid AS uuid, hostname, ip_addr FROM channels ";
			sql = sql .. "WHERE callstate = 'RINGING' ";
			sql = sql .. "AND (";
			x = 0;
			for key,extension in pairs(extensions) do
				if (x == 0) then
					sql = sql .. " presence_id = '"..extension.."@"..domain_name.."' ";
				else
					sql = sql .. "OR presence_id = '"..extension.."@"..domain_name.."' ";
				end
				x = x + 1;
			end
			sql = sql .. ") ";
			sql = sql .. "and call_uuid is not null ";
			--if (domain_count > 1) then
			--	sql = sql .. "and context = '"..context.."' ";
			--end
			sql = sql .. "limit 1 ";
			if (debug["sql"]) then
				freeswitch.consoleLog("NOTICE", "sql "..sql.."\n");
			end
			dbh:query(sql, function(row)
				--for key, val in pairs(row) do
				--	freeswitch.consoleLog("NOTICE", "row "..key.." "..val.."\n");
				--end
				uuid = row.uuid;
				call_hostname = row.hostname;
				ip_addr = row.ip_addr;
			end);
	end

--get the hostname
	hostname = freeswitch.getGlobalVariable("hostname");
	freeswitch.consoleLog("NOTICE", "Hostname:"..hostname.."  Call Hostname:"..call_hostname.."\n");

--intercept a call that is ringing
	if (uuid ~= nil) then
		if (session:getVariable("billmsec") == nil) then
			if (hostname == call_hostname) then
				session:execute("intercept", uuid);
			else
				session:execute("export", "sip_h_X-intercept_uuid="..uuid);
				session:execute("export", "sip_h_X-domain_uuid="..domain_uuid);
				session:execute("export", "sip_h_X-domain_name="..domain_name);
				port = freeswitch.getGlobalVariable(sofia_profile_name.."_sip_port");
				session:execute("bridge", "sofia/"..sofia_profile_name.."/*8@"..call_hostname..":"..port);
				freeswitch.consoleLog("NOTICE", "Send call to other host.... \n");
			end
		end
	end

--notes
	--originate a call
		--cmd = "originate user/1007@voip.example.com &intercept("..uuid..")";
		--api = freeswitch.API();
		--result = api:executeString(cmd);
