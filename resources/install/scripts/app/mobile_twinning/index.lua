

--set default variables
	max_digits = 15;
	digit_timeout = 5000;
	--debug["sql"] = true;

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

--set the api
	api = freeswitch.API();

--get the hostname
	local hostname = trim(api:execute("switchname", ""));
	
--Get intercept logger
	local log = require "resources.functions.log".mobile_twinning
	
--get the argv values
	call_direction = argv[2];
	call_uuid = "";
--get the session variables
	if (session:ready()) then
		session:answer();
		session:execute("sleep", "1000");
	end

--get the session variables
	if (session:ready()) then
		--general variables
			domain_uuid = session:getVariable("domain_uuid");
			domain_name = session:getVariable("domain_name");
			context = session:getVariable("context");
			uuid = session:get_uuid();
			caller_id_number = session:getVariable("caller_id_number");


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

--lookup caller-id to see if it is an associated mobile_number
	mobile_caller_id_number = string.sub(caller_id_number,-10);
	local params = {domain_uuid = domain_uuid, caller_id_number = mobile_caller_id_number }
	local sql = "SELECT m.mobile_twinning_number, m.extension_uuid, m.mobile_twinning_uuid, e.extension ";
	sql = sql .. "FROM  v_mobile_twinnings as m ";
	sql = sql .. "LEFT OUTER JOIN v_extensions AS e ON e.extension_uuid = m.extension_uuid ";
	sql = sql .. "WHERE m.domain_uuid = :domain_uuid ";
	sql = sql .. "AND m.mobile_twinning_number = :caller_id_number ";
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[mobile_twinning] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
	end

	dbh:query(sql, params, function(row)
		--set the variables
			mobile_twinning_number = row.mobile_twinning_number;
			extension_uuid = row.extension_uuid;
			mobile_twinning_uuid = row.mobile_twinning_uuid;
			extension = row.extension;
	end);

--Call is from mobile
	if (mobile_twinning_uuid ~= nil and caller_id_number ~= nil) then
		min_digits = 1;
		max_digits = 1;
		max_tries = 1;
		session:streamFile("ivr/ivr-to_accept_press_one.wav");
		dtmf_digits = session:getDigits(max_digits, "#", 7000);

		if (dtmf_digits == "1") then
			local dbh = Database.new('switch')
			--check the database to the uuid of the desk call
				presence_id = extension.."@"..domain_name;
				call_hostname = "";
				sql = "SELECT uuid, call_uuid, direction, hostname FROM channels ";
				sql = sql .. "WHERE presence_id = '" .. presence_id .. "' ";
				sql = sql .. "AND call_uuid IS NOT NULL ";
				sql = sql .. "AND callstate = 'ACTIVE' ";
			if (debug["sql"]) then
				log.noticef("SQL: %s; params: %s", sql, json.encode(params));
			end
			local is_child
			dbh:query(sql, params, function(row)
				is_child = (row.uuid == row.call_uuid)
				call_uuid = row.call_uuid;
				call_hostname = row.hostname;
				direction = row.direction;
			end);
			log.notice("call_uuid: "..uuid .. " direction: " .. direction);
			if (direction == 'inbound') then
				leg = "bleg"
			end
		end
	end

--if the call is from an extension, lookup the mobile number and try to pull the call off the mobile phone
	if (mobile_twinning_uuid == nil and caller_id_number ~= nil) then
		--lookup the extension_uuid
				local params = {domain_uuid = domain_uuid, caller_id_number = caller_id_number }
				local sql = "SELECT extension_uuid FROM v_extensions ";
				sql = sql .. "WHERE domain_uuid = :domain_uuid ";
				sql = sql .. "AND extension = :caller_id_number ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[mobile_twinning] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end

				dbh:query(sql, params, function(row)
					--set the variables
						extension_uuid = row.extension_uuid;
				end);
				
		--lookup the mobile number associated to the extension
				local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid }
				local sql = "SELECT m.mobile_twinning_number, e.extension ";
				sql = sql .. "FROM  v_mobile_twinnings as m ";
				sql = sql .. "LEFT OUTER JOIN v_extensions AS e ON e.extension_uuid = m.extension_uuid ";
				sql = sql .. "WHERE m.domain_uuid = :domain_uuid ";
				sql = sql .. "AND m.extension_uuid = :extension_uuid ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[mobile_twinning] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
			
				dbh:query(sql, params, function(row)
					--set the variables
						mobile_twinning_number = row.mobile_twinning_number;
						extension = row.extension;
				end);
		
		if (mobile_twinning_number ~= nil) then
			--lookup active calls to the mobile (for follow-me)
				--connect to FS database
					local dbh = Database.new('switch')
					
				--check the database for the uuid of the mobile call 
					presence_id = caller_id_number.."@"..domain_name;
					call_hostname = "";
					sql = "SELECT uuid, call_uuid, hostname FROM channels ";
					sql = sql .. "WHERE callstate = 'ACTIVE' ";
					sql = sql .. "AND direction = 'outbound' ";
					sql = sql .. "AND dest = " .. mobile_twinning_number .. " ";
					sql = sql .. "AND presence_id = '" .. presence_id .. "' ";
					sql = sql .. "AND call_uuid IS NOT NULL ";
				if (debug["sql"]) then
					log.noticef("SQL: %s; params: %s", sql, json.encode(params));
				end
				local is_child
				dbh:query(sql, params, function(row)
					is_child = (row.uuid == row.call_uuid)
					call_uuid = row.call_uuid;
					call_hostname = row.hostname;
				end);
				log.notice("call_uuid from follow-me: "..call_uuid);
			
			--lookup active calls to the mobile (for instances where the mobile switched the call from the extension)
				if (call_uuid == nil or call_uuid == '') then
				--connect to FS database
					local dbh = Database.new('switch')
					
				--check the database for the uuid of the mobile call 
					call_hostname = "";
					sql = "SELECT uuid, call_uuid, hostname FROM channels ";
					sql = sql .. "WHERE callstate = 'ACTIVE' ";
					sql = sql .. "AND direction = 'inbound' ";
					sql = sql .. "AND dest = " .. extension .. " ";
					sql = sql .. "AND call_uuid IS NOT NULL ";
					if (debug["sql"]) then
						log.noticef("SQL: %s; params: %s", sql, json.encode(params));
					end
					local is_child
					dbh:query(sql, params, function(row)
						is_child = (row.uuid == row.call_uuid)
						call_uuid = row.call_uuid;
						call_hostname = row.hostname;
					end);
					log.notice("call_uuid from other: "..call_uuid);
					leg = "bleg"
				end
		end
	end

--intercept a call that is on your mobile
	if (call_uuid ~= nil) then
		if (hostname == call_hostname) then
			if (leg == 'bleg') then
				session:execute("intercept", "-bleg " .. call_uuid);
			else
				session:execute("intercept", call_uuid);
			end	

--		else
--			session:execute("export", "sip_h_X-intercept_uuid="..uuid);
--			make_proxy_call(pickup_number, call_hostname)
		end
	else
		log.notice("No active call to transfer.");
	end
