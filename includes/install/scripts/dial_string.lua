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
--	Copyright (C) 2010
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set default variables
	max_tries = "3";
	digit_timeout = "5000";

--include the lua script
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	include = assert(loadfile(scripts_dir .. "/resources/config.lua"));
	include();

--connect to the database
	--ODBC - data source name
		if (dsn_name) then
			dbh = freeswitch.Dbh(dsn_name,dsn_username,dsn_password);
		end
	--FreeSWITCH core db handler
		if (db_type == "sqlite") then
			dbh = freeswitch.Dbh("core:"..db_path.."/"..db_name);
		end

if ( session:ready() ) then
	session:answer();
	domain_name = session:getVariable("domain_name");
	pin_number = session:getVariable("pin_number");
	sounds_dir = session:getVariable("sounds_dir");
	sip_from_user = session:getVariable("sip_from_user");

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--get the unique_id
		min_digits = 1;
		max_digits = 15;
		unique_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");

	--get the vm_password
		min_digits = 1;
		max_digits = 12;
		vm_password = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");

		freeswitch.consoleLog("NOTICE", "unique_id ".. unique_id .. " vm_password " .. vm_password .. "\n");

	--get the dial_string
		sql = "SELECT * FROM v_extensions as e, v_domains as d ";
		sql = sql .. "WHERE e.domain_uuid = d.domain_uuid ";
		sql = sql .. "AND e.unique_id = '" .. unique_id .."' ";
		sql = sql .. "AND e.vm_password = '" .. vm_password .."' ";
		sql = sql .. "AND d.domain_name = '" .. domain_name .."' ";
		dbh:query(sql, function(row)
			domain_uuid = row.domain_uuid;
			--domain_name = row.domain_name;
			--extension = row.extension;
			extension_uuid = row.extension_uuid;
			dial_string = row.dial_string;
		end);
		if (extension_uuid) then
			if (string.len(dial_string) > 1) then
				--if the the dial_string has a value then clear the dial string
				sql = "UPDATE v_extensions SET ";
				sql = sql .. "dial_string = null, ";
				sql = sql .. "dial_user = null, ";
				sql = sql .. "dial_domain = null ";
				sql = sql .. "WHERE extension_uuid = '" .. extension_uuid .."' ";
				freeswitch.consoleLog("NOTICE", "sql: ".. sql .. "-\n");
				dbh:query(sql);
			else
				--if the dial string is empty then set the dial string
				dial_string = [[{sip_invite_domain=]] .. domain_name .. [[,presence_id=]] .. sip_from_user .. [[@]] .. domain_name .. [[}${sofia_contact(]] .. sip_from_user .. [[@]] .. domain_name .. [[)}]];
				sql = "UPDATE v_extensions SET ";
				sql = sql .. "dial_string = '" .. dial_string .."', ";
				sql = sql .. "dial_user = '" .. sip_from_user .."', ";
				sql = sql .. "dial_domain = '" .. domain_name .."' ";
				sql = sql .. "WHERE extension_uuid = '" .. extension_uuid .."' ";
				freeswitch.consoleLog("NOTICE", "sql: ".. sql .. "-\n");
				dbh:query(sql);
			end
		else
			max_tries = 1;
			digit_timeout = "1000";
			result = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_fail_auth:#", "", "\\d+");
			session:hangup("NORMAL_CLEARING");
			return;
		end

	--log to the console
		freeswitch.consoleLog("NOTICE", "domain_name: ".. domain_name .. "-\n");
		freeswitch.consoleLog("NOTICE", "extension_uuid: ".. extension_uuid .. "-\n");
		freeswitch.consoleLog("NOTICE", "dial_string: ".. dial_string .. "-\n");

	--show call variables
		--session:execute("info", "");

	--log to the console
--		freeswitch.consoleLog("NOTICE", "SQL ".. sql .. "\n");
end
