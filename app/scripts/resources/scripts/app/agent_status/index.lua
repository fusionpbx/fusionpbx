

--set default variables
	max_digits = 15;
	digit_timeout = 5000;
	debug["sql"] = true;

	--general functions
	require "resources.functions.trim";

	--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

	--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

	local presence_in = require "resources.functions.presence_in"

	--set the api
	api = freeswitch.API();

	--get the argv values
	action = argv[2];

	--get the session variables
	if (session:ready()) then
		session:answer();
		session:sleep('1000');
	end

	--get the session variables
	if (session:ready()) then
		--general variables
		domain_uuid = session:getVariable("domain_uuid");
		domain_name = session:getVariable("domain_name");
		context = session:getVariable("context");
		uuid = session:get_uuid();
		agent_authorized = session:getVariable("agent_authorized");
		agent_action = session:getVariable("agent_action");
		agent_id = session:getVariable("agent_id");
		agent_name = session:getVariable("agent_name");
		agent_password = session:getVariable("agent_password");

		--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end
	end

	--set default as access denied
	if (agent_authorized == nil or agent_authorized ~= 'true') then
		agent_authorized = 'false';
	end

	--define the sounds directory
	sounds_dir = session:getVariable("sounds_dir");
	sounds_dir = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice;

	--get the agent_id from the caller
	if (agent_id == nil and agent_name == nil) then
		min_digits = 2;
		max_digits = 20;
		max_tries = 3;
		agent_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");
	end

	--get the pin number from the caller
	if (agent_password == nil and agent_authorized ~= 'true') then
		min_digits = 3;
		max_digits = 20;
		max_tries = 3;
		agent_password = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
	end

	--get the agent password
	local params = {domain_uuid = domain_uuid, agent_id = agent_id, agent_name = agent_name}
	local sql = "SELECT * FROM v_call_center_agents ";
	sql = sql .. "WHERE domain_uuid = :domain_uuid ";
	if (agent_id ~= nil) then
		sql = sql .. "AND agent_id = :agent_id ";
	else
		sql = sql .. "AND agent_name = :agent_name ";
	end
	if (agent_authorized ~= 'true') then
		sql = sql .. "AND agent_password = :agent_password ";
		params.agent_password = agent_password;
	end
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[user status] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
	end

	dbh:query(sql, params, function(row)
	          --set the variables
	          agent_uuid = row.call_center_agent_uuid;
	         agent_name = row.agent_name;
	         agent_id = row.agent_id;
	         user_uuid = row.user_uuid;
	         --authorize the user
	         agent_authorized = 'true';
	         end);

	--show the results
	if (agent_id) then
		freeswitch.consoleLog("notice", "[user status][login] agent id: " .. agent_id .. " authorized: " .. agent_authorized .. "\n");
	end
	if (agent_password and debug["password"]) then
		freeswitch.consoleLog("notice", "[user status][login] agent password: " .. agent_password .. "\n");
	end

	--get the user_uuid
	if (agent_authorized == 'true') then

		--get the agent status from mod_callcenter
		cmd = "callcenter_config agent get status "..agent_uuid.."";
		freeswitch.consoleLog("notice", "[user status][login] "..cmd.."\n");
		user_status = trim(api:executeString(cmd));

		--get the user info
		if (agent_action == nil) then
			if (user_status == "Available") then
				action = "logout";
				status = 'Logged Out';
			else
				action = "login";
				status = 'Available';
			end
		elseif (agent_action == "break") then
			if (user_status == "On Break") then
				action = "login";
				status = 'Available';
			else
				action = "break";
				status = 'On Break';
			end
		elseif (agent_action == "login") then
			action = "login";
			status = 'Available';
		elseif (agent_action == "logout") then
			action = "logout";
			status = 'Logged Out';
		end

		--send a login or logout to mod_callcenter
		cmd = "sched_api +5 none callcenter_config agent set status "..agent_uuid.." '"..status.."'";
		freeswitch.consoleLog("notice", "[user status][login] "..cmd.."\n");
		result = api:executeString(cmd);

		--update the user status
		if (user_uuid ~= nil and user_uuid ~= '') then
			local sql = "SELECT user_status FROM v_users ";
			sql = sql .. "WHERE user_uuid = :user_uuid ";
			sql = sql .. "AND domain_uuid = :domain_uuid ";
			local params = {user_uuid = user_uuid, domain_uuid = domain_uuid};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[call_center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params, function(row)

			          --set the user_status in the users table
			          local sql = "UPDATE v_users SET ";
			         sql = sql .. "user_status = :status ";
			         sql = sql .. "WHERE user_uuid = :user_uuid ";
			         local params = {status = status, user_uuid = user_uuid};
			         if (debug["sql"]) then
			         freeswitch.consoleLog("notice", "[call_center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			         end
			         dbh:query(sql, params);
			         end);
		end

		--set the presence to terminated - turn the lamp off:
		if (action == "logout" or action == "break") then
			event = freeswitch.Event("PRESENCE_IN");
			event:addHeader("proto", "sip");
			event:addHeader("event_type", "presence");
			event:addHeader("alt_event_type", "dialog");
			event:addHeader("Presence-Call-Direction", "outbound");
			event:addHeader("state", "Active (1 waiting)");
			event:addHeader("from", agent_name.."@"..domain_name);
			event:addHeader("login", agent_name.."@"..domain_name);
			event:addHeader("unique-id", agent_uuid);
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
			event:addHeader("unique-id", agent_uuid);
			event:addHeader("Presence-Call-Direction", "outbound");
			event:addHeader("answer-state", "confirmed");
			event:fire();
		end

		if (action == "login") then
			blf_status = "false"
		end
		if string.find(agent_name, 'agent+', nil, true) ~= 1 then
			presence_in.turn_lamp( blf_status,
			                       'agent+'..agent_name.."@"..domain_name,
			                       uuid
			                     );
		end
	end

	--unauthorized
	if (agent_authorized == 'false') then
		result = session:streamFile(sounds_dir.."/voicemail/vm-fail_auth.wav");
		status = "Invalid ID or Password";
	end

	--set the status and presence
	if (session:ready()) then
		if (action == "login") then
			session:execute("playback", sounds_dir.."/ivr/ivr-you_are_now_logged_in.wav");
		end
		if (action == "logout") then
			session:execute("playback", sounds_dir.."/ivr/ivr-you_are_now_logged_out.wav");
		end
		if (action == "break") then
			session:execute("playback", sounds_dir.."/ivr/ivr-thank_you.wav");
		end
	end

	--send the status to the display
	if (status ~= nil) then
		reply = api:executeString("uuid_display "..uuid.." '"..status.."'");
	end

	--set the session sleep to give time to see the display
	if (session:ready()) then
		session:execute("sleep", "2000");
	end
