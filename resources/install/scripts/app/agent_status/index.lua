

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
			agent_id = session:getVariable("agent_id");
			agent_password = session:getVariable("agent_password");

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

--get the agent_id from the caller
	if (agent_id == nil) then
		min_digits = 2;
		max_digits = 20;
		max_tries = 3;
		agent_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");
	end

--get the pin number from the caller
	if (agent_password == nil) then
		min_digits = 3;
		max_digits = 20;
		max_tries = 3;
		agent_password = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
	end

--set default as access denied
	authorized = 'false';

--get the agent password
	sql = "SELECT * FROM v_call_center_agents ";
	sql = sql .. "WHERE domain_uuid = '" .. domain_uuid .."' ";
	sql = sql .. "AND agent_id = '" .. agent_id .."' ";
	sql = sql .. "AND agent_password = '" .. agent_password .."' ";
	freeswitch.consoleLog("notice", "[user status] sql: " .. sql .. "\n");
	dbh:query(sql, function(row)
		--set the variables
			agent_name = row.agent_name;
			agent_id = row.agent_id;
		--authorize the user
			authorized = 'true';
	end);

--show the results
	if (agent_id) then
		freeswitch.consoleLog("notice", "[user status][login] agent_id: " .. agent_id .. " authorized " .. authorized .. "\n");
	end
	if (agent_password and debug["password"]) then
		freeswitch.consoleLog("notice", "[user status][login] agent_password: " .. agent_password .. "\n");
	end

--get the user_uuid
	if (authorized == 'true') then
		sql = "SELECT user_uuid, user_status FROM v_users ";
		sql = sql .. "WHERE username = '".. agent_name .."' ";
		sql = sql .. "AND domain_uuid = '" .. domain_uuid .."' ";
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "[call_center] sql: ".. sql .. "\n");
		end
		dbh:query(sql, function(row)
			--get the user info
				user_uuid = row.user_uuid;
				user_status = row.user_status;
				if (user_status == "Available") then
					action = "logout";
					status = 'Logged Out';
				else
					action = "login";
					status = 'Available';
				end

			--show the status in the log
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
				cmd = "callcenter_config agent set status "..agent_name.."@"..domain_name.." '"..status.."'";
				freeswitch.consoleLog("notice", "[user status][login] "..cmd.."\n");
				result = api:executeString(cmd);

			--set the presence to terminated - turn the lamp off:
				if (action == "logout") then
					event = freeswitch.Event("PRESENCE_IN");
					event:addHeader("proto", "sip");
					event:addHeader("event_type", "presence");
					event:addHeader("alt_event_type", "dialog");
					event:addHeader("Presence-Call-Direction", "outbound");
					event:addHeader("state", "Active (1 waiting)");
					event:addHeader("from", agent_name.."@"..domain_name);
					event:addHeader("login", agent_name.."@"..domain_name);
					event:addHeader("unique-id", user_uuid);
					event:addHeader("answer-state", "terminated");
					event:fire();
				end

			--set presence in - turn lamp on
				if (action == "login") then
					event = freeswitch.Event("PRESENCE_IN");
					event:addHeader("proto", "sip");
					event:addHeader("login", agent_name.."@"..domain_name);
					event:addHeader("from", agent_name.."@"..domain_name);
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
	end

--unauthorized
	if (authorized == 'false') then
		result = session:streamFile(sounds_dir.."/voicemail/vm-fail_auth.wav");
		status = "Invalid ID or Password";
	end

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
