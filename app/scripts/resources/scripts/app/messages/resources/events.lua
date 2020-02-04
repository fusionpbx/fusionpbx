--
--      Version: MPL 1.1
--
--      The contents of this file are subject to the Mozilla Public License Version
--      1.1 (the "License"); you may not use this file except in compliance with
--      the License. You may obtain a copy of the License at
--      http://www.mozilla.org/MPL/
--
--      Software distributed under the License is distributed on an "AS IS" basis,
--      WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--      for the specific language governing rights and limitations under the
--      License.
--
--      The Original Code is FusionPBX
--
--      The Initial Developer of the Original Code is
--      Mark J Crane <markjcrane@fusionpbx.com>
--      Copyright (C) 2018


-- Start the script
	--	<!-- Subscribe to events -->
	--	<hook event="CUSTOM" subclass="SMS::SEND_MESSAGE" script="app/messages/resources/events.lua"/>

--prepare the api object
	api = freeswitch.API();

--define the functions
	require "resources.functions.trim";
	require "resources.functions.explode";
	require "resources.functions.base64";

--include the database class
	local Database = require "resources.functions.database";

--set debug
	debug["sql"] = false;

--get the events
	--serialize the data for the console
	--freeswitch.consoleLog("notice","[events] " .. event:serialize("xml") .. "\n");
	--freeswitch.consoleLog("notice","[evnts] " .. event:serialize("json") .. "\n");

--intialize settings
	--from_user = '';

--get the event variables
	uuid               = event:getHeader("Core-UUID");
	from_user          = event:getHeader("from_user");
	from_host          = event:getHeader("from_host");
	to_user            = event:getHeader("to_user");
	to_host            = event:getHeader("to_host");
	content_type       = event:getHeader("type");
	message_text       = event:getBody();

--set required variables
	if (from_user ~= nil and from_host ~= nil) then
		message_from   = from_user .. '@' .. from_host;
	end
	if (to_user ~= nil and to_host ~= nil) then
		message_to     = to_user .. '@' .. to_host;
	end
	message_type       = 'message';

--connect to the database
	dbh = Database.new('system');

--exits the script if we didn't connect properly
	assert(dbh:connected());

--set debug
	debug["sql"] = true;

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--check if the from user exits
	if (from_user ~= nil and from_host ~= nil) then
		cmd = "user_exists id ".. from_user .." "..from_host;
		freeswitch.consoleLog("notice", "[messages][from] user exists " .. cmd .. "\n");
		from_user_exists = api:executeString(cmd);
	else
		from_user_exists  = 'false';
	end

--check if the to user exits
	if (to_user ~= nil and to_host ~= nil) then
		cmd = "user_exists id ".. to_user .." "..to_host;
		freeswitch.consoleLog("notice", "[messages][to] user exists " .. cmd .. "\n");
		to_user_exists = api:executeString(cmd);
	else
		to_user_exists = 'false';
	end

--add the message
	if (from_user_exists == 'true') then
		--set the direction
		message_direction = 'send';

		--get the from user_uuid
		cmd = "user_data ".. from_user .."@"..from_host.." var domain_uuid";
		domain_uuid = trim(api:executeString(cmd));

		--get the from user_uuid
		cmd = "user_data ".. from_user .."@"..from_host.." var user_uuid";
		user_uuid = trim(api:executeString(cmd));

		--get the from contact_uuid
		cmd = "user_data ".. to_user .."@"..to_host.." var contact_uuid";
		contact_uuid = trim(api:executeString(cmd));

		--create a new uuid and add it to the uuid list
		message_uuid = api:executeString("create_uuid");

		--sql statement
		sql = "INSERT INTO v_messages ";
		sql = sql .."( ";
		sql = sql .."domain_uuid, ";
		sql = sql .."message_uuid, ";
		sql = sql .."user_uuid, ";
		if (contact_uuid ~= null and string.len(contact_uuid) > 0) then
			sql = sql .."contact_uuid, ";
		end
		sql = sql .."message_direction, ";
		sql = sql .."message_date, ";
		sql = sql .."message_type, ";
		if (message_from ~= null and string.len(message_from) > 0) then
			sql = sql .."message_from, ";
		end
		sql = sql .."message_to, ";
		sql = sql .."message_text ";
		sql = sql ..") ";
		sql = sql .."VALUES ( ";
		sql = sql ..":domain_uuid, ";
		sql = sql ..":message_uuid, ";
		sql = sql ..":user_uuid, ";
		if (contact_uuid ~= null and string.len(contact_uuid) > 0) then
			sql = sql ..":contact_uuid, ";
		end
		sql = sql ..":message_direction, ";
		sql = sql .."now(), ";
		sql = sql ..":message_type, ";
		if (message_from ~= null and string.len(message_from) > 0) then
			sql = sql ..":message_from, ";
		end
		sql = sql ..":message_to, ";
		sql = sql ..":message_text ";
		sql = sql ..") ";

		--set the parameters
		local params= {}
		params['domain_uuid'] = domain_uuid;
		params['message_uuid'] = message_uuid;
		params['user_uuid'] = user_uuid;
		if (contact_uuid ~= null and string.len(contact_uuid) > 0) then
			params['contact_uuid'] = contact_uuid;
		end
		params['message_direction'] = message_direction;
		params['message_type'] = message_type;
		if (message_from ~= null) then
			params['message_from'] = message_from;
		end
		params['message_to'] = message_to;
		params['message_text'] = message_text;

		--show debug info
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[call_center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end

		--run the query
		dbh:query(sql, params);
	end
	if (to_user_exists == 'true') then
		--sql statement
		sql = "INSERT INTO v_messages ";
		sql = sql .."( ";
		sql = sql .."domain_uuid, ";
		sql = sql .."message_uuid, ";
		sql = sql .."user_uuid, ";
		if (contact_uuid ~= null and string.len(contact_uuid) > 0) then
			sql = sql .."contact_uuid, ";
		end
		sql = sql .."message_direction, ";
		sql = sql .."message_date, ";
		sql = sql .."message_type, ";
		if (message_from ~= null and string.len(message_from) > 0) then
			sql = sql .."message_from, ";
		end
		sql = sql .."message_to, ";
		sql = sql .."message_text ";
		sql = sql ..") ";
		sql = sql .."VALUES ( ";
		sql = sql ..":domain_uuid, ";
		sql = sql ..":message_uuid, ";
		sql = sql ..":user_uuid, ";
		if (contact_uuid ~= null and string.len(contact_uuid) > 0) then
			sql = sql ..":contact_uuid, ";
		end
		sql = sql ..":message_direction, ";
		sql = sql .."now(), ";
		sql = sql ..":message_type, ";
		if (message_from ~= null and string.len(message_from) > 0) then
			sql = sql ..":message_from, ";
		end
		sql = sql ..":message_to, ";
		sql = sql ..":message_text ";
		sql = sql ..") ";

		--set the direction
		message_direction = 'receive';

		--get the from user_uuid
		cmd = "user_data ".. to_user .."@"..to_host.." var domain_uuid";
		domain_uuid = trim(api:executeString(cmd));

		--get the from user_uuid
		cmd = "user_data ".. to_user .."@"..to_host.." var user_uuid";
		user_uuid = trim(api:executeString(cmd));

		--get the from contact_uuid
		cmd = "user_data ".. to_user .."@"..to_host.." var contact_uuid";
		contact_uuid = trim(api:executeString(cmd));

		--create a new uuid and add it to the uuid list
		message_uuid = api:executeString("create_uuid");

		--set the parameters
		local params= {}
		params['domain_uuid'] = domain_uuid;
		params['message_uuid'] = message_uuid;
		params['user_uuid'] = user_uuid;
		if (contact_uuid ~= null and string.len(message_from) > 0) then
			params['contact_uuid'] = contact_uuid;
		end
		params['message_direction'] = message_direction;
		params['message_type'] = message_type;
		params['message_from'] = message_from;
		params['message_to'] = message_to;
		params['message_text'] = message_text;

		--show debug info
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[call_center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end

		--run the query
		dbh:query(sql, params);

	else

		--get setttings needed to send the message
		require "resources.functions.settings";
		settings = settings(domain_uuid);
		if (settings['message'] ~= nil) then
			http_method = '';
			if (settings['message']['http_method'] ~= nil) then
				if (settings['message']['http_method']['text'] ~= nil) then
					http_method = settings['message']['http_method']['text'];
				end
			end

			http_content_type = '';
			if (settings['message']['http_content_type'] ~= nil) then
				if (settings['message']['http_content_type']['text'] ~= nil) then
					http_content_type = settings['message']['http_content_type']['text'];
				end
			end

			http_destination = '';
			if (settings['message']['http_destination'] ~= nil) then
				if (settings['message']['http_destination']['text'] ~= nil) then
					http_destination = settings['message']['http_destination']['text'];
				end
			end

			http_auth_enabled = 'false';
			if (settings['message']['http_auth_enabled'] ~= nil) then
				if (settings['message']['http_auth_enabled']['boolean'] ~= nil) then
					http_auth_enabled = settings['message']['http_auth_enabled']['boolean'];
				end
			end

			http_auth_type = '';
			if (settings['message']['http_auth_type'] ~= nil) then
				if (settings['message']['http_auth_type']['text'] ~= nil) then
					http_auth_type = settings['message']['http_auth_type']['text'];
				end
			end

			http_auth_user = '';
			if (settings['message']['http_auth_user'] ~= nil) then
				if (settings['message']['http_auth_user']['text'] ~= nil) then
					http_auth_user = settings['message']['http_auth_user']['text'];
				end
			end

			http_auth_password = '';
			if (settings['message']['http_auth_password'] ~= nil) then
				if (settings['message']['http_auth_password']['text'] ~= nil) then
					http_auth_password = settings['message']['http_auth_password']['text'];
				end
			end
		end

		--get the sip user outbound_caller_id
		if (from_user ~= nil and from_host ~= nil) then
			cmd = "user_data ".. from_user .."@"..from_host.." var outbound_caller_id_number";
			from = trim(api:executeString(cmd));
		else
			from = '';
		end

		--replace variables for their value
		http_destination = http_destination:gsub("${from}", from);
		
		--send to the provider using curl
		if (to_user ~= nil) then
			cmd = [[curl ]].. http_destination ..[[ ]]
			cmd = cmd .. [[-H "Content-Type: ]]..http_content_type..[[" ]];
			if (http_auth_type == 'basic') then
				cmd = cmd .. [[-H "Authorization: Basic ]]..base64.encode(http_auth_user..":"..http_auth_password)..[[" ]];
			end
			cmd = cmd .. [[-d '{"to":"]]..to_user..[[","text":"]]..message_text..[["}']]
			result = api:executeString("system "..cmd);
			--status = os.execute (cmd);

			--debug - log the command
			freeswitch.consoleLog("notice", "[message] " .. cmd.. "\n");
		end

	end
