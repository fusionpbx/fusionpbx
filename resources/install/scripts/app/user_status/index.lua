

--set default variables
	max_digits = 15;
	digit_timeout = 5000;
	debug["sql"] = true;

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--set the api
	api = freeswitch.API();

--get the argv values
	action = argv[2];

--get the session variables
	if (session:ready()) then
		session:answer();
	end

--get the session variables
	if (session:ready()) then
		--general variables
			domain_uuid = session:getVariable("domain_uuid");
			domain_name = session:getVariable("domain_name");
			context = session:getVariable("context");
			uuid = session:get_uuid();
			sip_from_user = session:getVariable("sip_from_user");
			sip_from_host = session:getVariable("sip_from_host");

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end
	end

--define the sounds directory
	sounds_dir = session:getVariable("sounds_dir");
	sounds_dir = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice;

--set the user_id from the user if its a local call
	require_user_id = true;
	require_pin_number = true;
	if (sip_from_host == domain_name) then
		user_id = sip_from_user;
		require_user_id = false;
		require_pin_number = false;
	end

--get the user_id from the caller
	if (require_user_id) then
		min_digits = 2;
		max_tries = 3;
		user_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");
	end

--get the pin number from the caller
	if (require_pin_number) then
		min_digits = 3;
		max_digits = 12;
		pin_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
	end

--get the voicemail password
	if (require_pin_number) then
		sql = "SELECT * FROM v_voicemails ";
		sql = sql .. "WHERE domain_uuid = '" .. domain_uuid .."' ";
		sql = sql .. "AND voicemail_id = '" .. user_id .."' ";
		dbh:query(sql, function(row)
			voicemail_password = row.voicemail_password;
		end);
	end

--show the results
	if (context) then
		freeswitch.consoleLog("notice", "[call center][login] context: " .. context .. "\n");
	end
	freeswitch.consoleLog("notice", "[call center][login] voicemail_id: " .. user_id .. "\n");
	if (pin_number) then
		freeswitch.consoleLog("notice", "[call center][login] pin_number: " .. pin_number .. "\n");
	end
	if (voicemail_password) then
		freeswitch.consoleLog("notice", "[call center][login] voicemail_password: " .. voicemail_password .. "\n");
	end

--check to see if the pin number is correct
	if (require_pin_number) then
		if (pin_number) then
			if (voicemail_password == pin_number) then
				--access granted
				access = 1;
			else
				--access denied
				access = 0;
			end
		else
			--access denied
			access = 0;
		end
	else 
		access = 1;
	end

--show the results
	if (access == 0) then
		freeswitch.consoleLog("notice", "[call center][login] access denied\n");
	end
	if (access == 1) then
		freeswitch.consoleLog("notice", "[call center][login] access granted\n");
	end

--use the voicemail_id to get the list of users assigned to an extension
	sql = "SELECT extension_uuid FROM v_extensions ";
	sql = sql .. "WHERE domain_uuid = '" .. domain_uuid .."' ";
	sql = sql .. "AND (extension = '" .. user_id .."' ";
	sql = sql .. "or number_alias = '" .. user_id .."') ";
	dbh:query(sql, function(row)
		extension_uuid = row.extension_uuid;
	end);

--set the status
	if (action == "login") then
		status = 'Available';
	end
	if (action == "logout") then
		status = 'Logged Out';
	end

--get the dial_string, and extension_uuid
	sql = "SELECT u.user_uuid, u.username, u.user_status FROM v_extension_users as e, v_users as u ";
	sql = sql .. "WHERE e.extension_uuid = '" .. extension_uuid .."' ";
	sql = sql .. "AND e.user_uuid = u.user_uuid ";
	if (debug["sql"]) then
		freeswitch.consoleLog("NOTICE", "[call_center] sql: ".. sql .. "\n");
	end
	dbh:query(sql, function(row)
		--get the user info
			user_uuid = row.user_uuid;
			username = row.username;
			user_status = row.user_status;
			if (user_status == "Available") then
				action = "logout";
				status = 'Logged Out';
			else
				action = "login";
				status = 'Available';
			end
			freeswitch.consoleLog("NOTICE", "[call_center] user_status: ".. status .. "\n");

		--set the user_status in the users table
			sql = "UPDATE v_users SET ";
			sql = sql .. "user_status = '"..status.."' ";
			sql = sql .. "WHERE user_uuid = '" .. user_uuid .."' ";
			if (debug["sql"]) then
				freeswitch.consoleLog("NOTICE", "[call_center] sql: ".. sql .. "\n");
			end
			dbh:query(sql);

		--send a login or logout to mod_callcenter
			cmd = "callcenter_config agent set status "..username.."@"..domain_name.." '"..status.."'";
			freeswitch.consoleLog("notice", "[call center][login] "..cmd.."\n");
			result = api:executeString(cmd);

		--set the presence to terminated - turn the lamp off:
			if (action == "logout") then
				event = freeswitch.Event("PRESENCE_IN");
				event:addHeader("proto", "sip");
				event:addHeader("event_type", "presence");
				event:addHeader("alt_event_type", "dialog");
				event:addHeader("Presence-Call-Direction", "outbound");
				event:addHeader("state", "Active (1 waiting)");
				event:addHeader("from", username.."@"..domain_name);
				event:addHeader("login", username.."@"..domain_name);
				event:addHeader("unique-id", user_uuid);
				event:addHeader("answer-state", "terminated");
				event:fire();
			end
		--set presence in - turn lamp on
			if (action == "login") then
				event = freeswitch.Event("PRESENCE_IN");
				event:addHeader("proto", "sip");
				event:addHeader("login", username.."@"..domain_name);
				event:addHeader("from", username.."@"..domain_name);
				event:addHeader("status", "Active (1 waiting)");
				event:addHeader("rpid", "unknown");
				event:addHeader("event_type", "presence");
				event:addHeader("alt_event_type", "dialog");
				event:addHeader("event_count", "1");
				event:addHeader("unique-id", user_uuid);
				event:addHeader("Presence-Call-Direction", "outbound");
				event:addHeader("answer-state", "confirmed");
				event:fire();
			end
	end);

--send the status to the display
	if (status ~= nil) then
		reply = api:executeString("uuid_display "..uuid.." '"..status.."'");
	end

--set the session sleep to give time to see the display
	if (session:ready()) then
		session:execute("sleep", "2000");
	end

--set the status and presence
	if (session:ready()) then
		if (action == "login") then
			session:execute("playback", sounds_dir.."/ivr/ivr-you_are_now_logged_in.wav");
			--session:execute("playback", "tone_stream://%(500,0,300,200,100,50,25)");
		end
		if (action == "logout") then
			session:execute("playback", sounds_dir.."/ivr/ivr-you_are_now_logged_out.wav");
			--session:execute("playback", "tone_stream://%(200,0,500,600,700)");
		end
	end
