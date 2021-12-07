--	Part of FusionPBX
--	Copyright (C) 2015-2018 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	  this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	  notice, this list of conditions and the following disclaimer in the
--	  documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--set the debug options
	debug["sql"] = false;

--define the explode function
	require "resources.functions.explode";
	require "resources.functions.trim";

--set the defaults
	max_tries = 3;
	digit_timeout = 5000;
	max_retries = 3;
	tries = 0;

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--answer
	session:answer();

--sleep
	session:sleep(500);

--get the domain_uuid
	domain_uuid = session:getVariable("domain_uuid");

--get the variables
	action = session:getVariable("action");
	reboot = session:getVariable("reboot");

--set defaults
	if (not reboot) then reboot = 'true'; end

--set the sounds path for the language, dialect and voice
	default_language = session:getVariable("default_language");
	default_dialect = session:getVariable("default_dialect");
	default_voice = session:getVariable("default_voice");
	if (not default_language) then default_language = 'en'; end
	if (not default_dialect) then default_dialect = 'us'; end
	if (not default_voice) then default_voice = 'callie'; end

--get the user id
	min_digits = 2;
	max_digits = 20;
	user_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");
	--user_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-please_enter_extension_followed_by_pound.wav", "", "\\d+");

--get the user password
	min_digits = 2;
	max_digits = 20;
	password = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
	--password = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-please_enter_pin_followed_by_pound.wav", "", "\\d+");

--get the user and domain name from the user argv user@domain
	sip_from_uri = session:getVariable("sip_from_uri");
	user_table = explode("@",sip_from_uri);
	domain_table = explode(":",user_table[2]);
	user = user_table[1];
	domain = domain_table[1];

--show the phone that will be overridden
	if (sip_from_uri ~= nil) then
		freeswitch.consoleLog("NOTICE", "[provision] sip_from_uri: ".. sip_from_uri .. "\n");
	end
	if (user ~= nil) then
		freeswitch.consoleLog("NOTICE", "[provision] user: ".. user .. "\n");
	end
	if (domain ~= nil) then
		freeswitch.consoleLog("NOTICE", "[provision] domain: ".. domain .. "\n");
	end

--get the device uuid for the phone that will have its configuration overridden
	if (user ~= nil and domain ~= nil and domain_uuid ~= nil) then
		local sql = [[SELECT device_uuid FROM v_device_lines ]];
		sql = sql .. [[WHERE user_id = :user ]];
		sql = sql .. [[AND server_address = :domain ]];
		sql = sql .. [[AND domain_uuid = :domain_uuid ]];
		local params = {user = user, domain = domain, domain_uuid = domain_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "[provision] SQL: ".. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			--get device uuid
			device_uuid = row.device_uuid;
			freeswitch.consoleLog("NOTICE", "[provision] device_uuid: ".. device_uuid .. "\n");
		end);
	end

--get the alternate device uuid using the device username and password
	authorized = 'false';
	if (user_id ~= nil and password ~= nil and domain_uuid ~= nil) then
		local sql = [[SELECT device_uuid FROM v_devices ]];
		sql = sql .. [[WHERE device_username = :user_id ]];
		sql = sql .. [[AND device_password = :password ]]
		sql = sql .. [[AND domain_uuid = :domain_uuid ]];
		local params = {user_id = user_id, password = password, domain_uuid = domain_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "[provision] SQL: ".. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			--get the alternate device_uuid
				device_uuid_alternate = row.device_uuid;
				freeswitch.consoleLog("NOTICE", "[provision] alternate device_uuid: ".. device_uuid_alternate .. "\n");
			--authorize the user
				authorized = 'true';
		end);
	end

--authentication failed
	if (authorized == 'false') then
		result = session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-fail_auth.wav");
	end

--this device already has an alternate find the correct device_uuid and then override current one
	if (authorized == 'true' and action == "login" and device_uuid_alternate ~= nil and device_uuid ~= nil and domain_uuid ~= nil) then
		local sql = [[SELECT * FROM v_devices ]];
		sql = sql .. [[WHERE device_uuid_alternate = :device_uuid ]];
		sql = sql .. [[AND domain_uuid = :domain_uuid ]];
		local params = {device_uuid = device_uuid, domain_uuid = domain_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "[provision] SQL: ".. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			if (row.device_uuid_alternate ~= nil) then
				device_uuid = row.device_uuid;
			end
		end);
	end

--remove the alternate device from another device so that it can be added to this device
	if (authorized == 'true' and action == "login" and device_uuid_alternate ~= nil and domain_uuid ~= nil) then
		local sql = [[SELECT * FROM v_device_lines ]];
		sql = sql .. [[WHERE device_uuid = :device_uuid ]];
		sql = sql .. [[AND domain_uuid = :domain_uuid ]];
		local params = {device_uuid = device_uuid_alternate, domain_uuid = domain_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "[provision] SQL: ".. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			--remove the previous alternate device uuid
				local sql = [[UPDATE v_devices SET device_uuid_alternate = null ]];
				sql = sql .. [[WHERE device_uuid_alternate = :device_uuid_alternate ]];
				sql = sql .. [[AND domain_uuid = :domain_uuid ]];
				local params = {device_uuid_alternate = device_uuid_alternate, domain_uuid = domain_uuid};
				if (debug["sql"]) then
					freeswitch.consoleLog("NOTICE", "[provision] SQL: ".. sql .. "; params: " .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params);
			--get the sip profile
				api = freeswitch.API();
				local sofia_contact = trim(api:executeString("sofia_contact */"..row.user_id.."@"..row.server_address));
				array = explode("/", sofia_contact);
				profile = array[2];
				freeswitch.consoleLog("NOTICE", "[provision] profile: ".. profile .. "\n");
			--send a sync command to the previous device
				--create the event notify object
					local event = freeswitch.Event('NOTIFY');
				--add the headers
					event:addHeader('profile', profile);
					event:addHeader('user', row.user_id);
					event:addHeader('host', row.server_address);
					event:addHeader('content-type', 'application/simple-message-summary');
				--check sync
					event:addHeader('event-string', 'check-sync;reboot='..reboot);
					--event:addHeader('event-string', 'resync');
				--send the event
					event:fire();
		end);
	end

--add the override to the device uuid (login)
	if (authorized == 'true' and action == "login") then
		if (device_uuid_alternate ~= nil and device_uuid ~= nil and domain_uuid ~= nil) then
			--send a hangup
				session:hangup();
			--add the new alternate
				local sql = [[UPDATE v_devices SET device_uuid_alternate = :device_uuid_alternate ]];
				sql = sql .. [[WHERE device_uuid = :device_uuid ]];
				sql = sql .. [[AND domain_uuid = :domain_uuid ]];
				local params = {device_uuid_alternate = device_uuid_alternate, 
					device_uuid = device_uuid, domain_uuid = domain_uuid};
				if (debug["sql"]) then
					freeswitch.consoleLog("NOTICE", "[provision] SQL: ".. sql .. "; params: " .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params);
		end
	end

--remove the override to the device uuid (logout)
	if (authorized == 'true' and action == "logout") then
		if (device_uuid_alternate ~= nil and device_uuid ~= nil and domain_uuid ~= nil) then
			local sql = [[UPDATE v_devices SET device_uuid_alternate = null ]];
			sql = sql .. [[WHERE device_uuid_alternate = :device_uuid ]];
			sql = sql .. [[AND domain_uuid = :domain_uuid ]];
			local params = {device_uuid = device_uuid, domain_uuid = domain_uuid};
			if (debug["sql"]) then
				freeswitch.consoleLog("NOTICE", "[provision] sql: ".. sql .. "; params: " .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params);
		end
	end

--found the device send a sync command
	if (authorized == 'true') then
		--get the sip profile
			api = freeswitch.API();
			local sofia_contact = trim(api:executeString("sofia_contact */"..user.."@"..domain));
			array = explode("/", sofia_contact);
			profile = array[2];
			freeswitch.consoleLog("NOTICE", "[provision] profile: ".. profile .. "\n");
		--send a hangup
			session:hangup();
		--create the event notify object
			local event = freeswitch.Event('NOTIFY');
		--add the headers
			event:addHeader('profile', profile);
			event:addHeader('user', user);
			event:addHeader('host', domain);
			event:addHeader('content-type', 'application/simple-message-summary');
		--check sync
			event:addHeader('event-string', 'check-sync;reboot='..reboot);
			--event:addHeader('event-string', 'resync');
		--send the event
			event:fire();
	end
