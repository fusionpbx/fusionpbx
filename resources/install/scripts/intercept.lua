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
--	Errol W Samuels <ewsamuels@gmail.com>

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

if ( session:ready() ) then
	--answer the session
		session:answer();

	--get session variables
		pin_number = session:getVariable("pin_number");
		sounds_dir = session:getVariable("sounds_dir");
		domain_uuid = session:getVariable("domain_uuid");
		domain_name = session:getVariable("domain_name");
		context = session:getVariable("context");
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

	--check the database to get the uuid of a ringing call
		sql = "select call_uuid as uuid, hostname, callee_num, ip_addr from channels ";
		sql = sql .. "where callstate = 'RINGING' ";
		if (extension) then
			sql = sql .. "and presence_id = '"..extension.."@"..domain_name.."' ";
		else
			if (domain_count > 1) then
				sql = sql .. "and context = '"..context.."' ";
			end
		end
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "sql "..sql.."\n");
		end
			dbh:query(sql, function(result)
			--for key, val in pairs(result) do
			--	freeswitch.consoleLog("NOTICE", "result "..key.." "..val.."\n");
			--end
			uuid = result.uuid;
			call_hostname = result.hostname;
			callee_num = result.callee_num;
			ip_addr = result.ip_addr;
		end);
end

--get the hostname
	hostname = freeswitch.getGlobalVariable("hostname");
	freeswitch.consoleLog("NOTICE", "Hostname:"..hostname.."  Call Hostname:"..call_hostname.."\n");

--intercept a call that is ringing
	if (uuid) then
		if (hostname == call_hostname) then
			session:execute("intercept", uuid);
		else
			session:execute("export", "sip_h_X-intercept_uuid="..uuid);
			session:execute("export", "sip_h_X-domain_uuid="..domain_uuid);
			session:execute("export", "sip_h_X-domain_name="..domain_name);
			session:execute("export", "sip_h_X-callee_num="..callee_num);
			port = freeswitch.getGlobalVariable(sofia_profile_name.."_sip_port");
			session:execute("bridge", "sofia/"..sofia_profile_name.."/**@"..call_hostname..":"..port);
			freeswitch.consoleLog("NOTICE", "Send call to other host.... \n");
		end
	end

--notes
	--originate a call
		--cmd = "originate user/1007@voip.example.com &intercept("..uuid..")";
		--api = freeswitch.API();
		--result = api:executeString(cmd);
