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
--	Copyright (C) 2010 - 2022
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Errol W Samuels <ewsamuels@gmail.com>

--user defined variables
	local extension = argv[1];
	local direction = argv[2] or extension and 'inbound' or 'all';

-- we can use any number because other box should check sip_h_X_*** headers first
	local pickup_number = '*8' -- extension and '**' or '*8'

--include config.lua
	require "resources.functions.config";

--add the function
	require "resources.functions.explode";
	require "resources.functions.trim";
	require "resources.functions.channel_utils";

--prepare the api object
	api = freeswitch.API();

--Get intercept logger
	local log = require "resources.functions.log".intercept

--include database class
	local Database = require "resources.functions.database"

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--get the hostname
	local hostname = trim(api:execute("switchname", ""));

-- redirect call to another box
	local function make_proxy_call(destination, call_hostname)
		destination = destination .. "@" .. domain_name
		local profile, proxy = "internal", call_hostname;

		local sip_auth_username = session:getVariable("sip_auth_username");
		local sip_auth_password = api:execute("user_data", sip_auth_username .. "@" .. domain_name .." param password");
		local auth = "sip_auth_username="..sip_auth_username..",sip_auth_password='"..sip_auth_password.."'"
		dial_string = "{sip_invite_domain=" .. domain_name .. "," .. auth .. "}sofia/" .. profile .. "/" .. destination .. ";fs_path=sip:" .. proxy;
		log.notice("Send call to other host....");
		session:execute("bridge", dial_string);
	end

-- check pin number if defined
	local function pin(pin_number)
		if not pin_number then
			return true
		end

		--sleep
			session:sleep(500);
		--get the user pin number
			local min_digits = 2;
			local max_digits = 20;
			local max_tries = "3";
			local digit_timeout = "5000";
			local digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");

		--validate the user pin number
			local pin_number_table = explode(",",pin_number);
			for index,pin_number in pairs(pin_number_table) do
				if (digits == pin_number) then
					--set the authorized pin number that was used
						session:setVariable("pin_number", pin_number);
					--done
						return true;
				end
			end

		--if not authorized play a message and then hangup
			session:streamFile("phrase:voicemail_fail_auth:#");
			session:hangup("NORMAL_CLEARING");
			return;
	end

-- do intercept if we get redirected request from another box
	local function proxy_intercept()
		-- Proceed calls from other boxes

		-- Check if this call from other box with setted intercept_uuid
			local intercept_uuid = session:getVariable("sip_h_X-intercept_uuid")

			if intercept_uuid and #intercept_uuid > 0 then
				log.notice("Get intercept_uuid from sip header. Do intercept....")
				session:execute("intercept", intercept_uuid)
				return true
			end

		-- Check if this call from other box and we need parent uuid for channel
			local child_intercept_uuid = session:getVariable("sip_h_X-child_intercept_uuid")
			if (not child_intercept_uuid) or (#child_intercept_uuid == 0) then
				return
			end

		-- search parent uuid
			log.notice("Get child_intercept_uuid from sip header.")
			local parent_uuid =
				channel_variable(child_intercept_uuid, 'ent_originate_aleg_uuid') or
				channel_variable(child_intercept_uuid, 'cc_member_session_uuid') or
				channel_variable(child_intercept_uuid, 'fifo_bridge_uuid') or
				child_intercept_uuid

			if parent_uuid == child_intercept_uuid then
				log.notice("Can not found parent call. Try intercept child.")
				session:execute("intercept", child_intercept_uuid)
				return true
			end

		-- search parent hostname
			call_hostname = hostname
		--[[ parent and child have to be on same box so we do not search it
			log.notice("Found parent channel try detect parent hostname")
			local dbh = Database.new('switch')
			local sql = "SELECT hostname FROM channels WHERE uuid='" .. parent_uuid .. "'"
			local call_hostname = dbh:first_value(sql)
			dbh:release()

			if not call_hostname then
				log.notice("Can not find host name. Channels is dead?")
				return true
			end
		--]]

			if hostname == call_hostname then
				log.notice("Found parent call on local machine. Do intercept....")
				session:execute("intercept", parent_uuid);
				return true
			end

			log.noticef("Found parent call on remote machine `%s`.", call_hostname)
			session:execute("export", "sip_h_X-intercept_uuid="..parent_uuid);
			make_proxy_call(pickup_number, call_hostname)
			return true
	end

-- return array of extensions for group
	local function select_group_extensions()
		-- connect to Fusion database
			local dbh = Database.new('system');

		--get the call groups the extension is a member of
			local sql = "SELECT call_group FROM v_extensions ";
			sql = sql .. "WHERE domain_uuid = :domain_uuid ";
			sql = sql .. "AND (extension = :caller_id_number ";
			sql = sql .. "OR  number_alias = :caller_id_number)";
			sql = sql .. "limit 1";
			local params = {domain_uuid = domain_uuid, caller_id_number = caller_id_number};
			if (debug["sql"]) then
				log.noticef("SQL: %s; params: %s", sql, json.encode(params));
			end
			local call_group = dbh:first_value(sql, params) or ''
			log.noticef("call_group: `%s`", call_group);
			call_groups = explode(",", call_group);

			params = {domain_uuid = domain_uuid};

		--get the extensions in the call groups
			sql = "SELECT extension, number_alias FROM v_extensions ";
			sql = sql .. "WHERE domain_uuid = :domain_uuid ";
			sql = sql .. "AND (";
			for key,call_group in ipairs(call_groups) do
				if key > 1 then sql = sql .. " OR " end
				if #call_group == 0 then
					sql = sql .. "call_group = '' or call_group is NULL";
				else
					local param_name = "call_group_" .. tostring(key)
					sql = sql .. "call_group like :" .. param_name;
					params[param_name] = '%' .. call_group .. '%';
				end
			end
			sql = sql .. ") ";
			if (debug["sql"]) then
				log.noticef("SQL: %s; params: %s", sql, json.encode(params));
			end
			local extensions = {}
			dbh:query(sql, params, function(row)
				local member = row.extension
				if row.number_alias and #row.number_alias > 0 then
					member = row.number_alias
				end
				extensions[#extensions+1] = member
				log.noticef("member `%s`", member)
			end);

		-- release Fusion database
			dbh:release()

		-- return result
			return extensions
	end

--check if the session is ready
	if ( session:ready() ) then
		--answer the session
			session:answer();
		--get session variables
			domain_uuid = session:getVariable("domain_uuid");
			domain_name = session:getVariable("domain_name");
			pin_number = session:getVariable("pin_number");
			context = session:getVariable("context");
			caller_id_number = session:getVariable("caller_id_number");
	end

--check if the session is ready
	if ( session:ready() ) then
		if proxy_intercept() then
			return
		end
	end

--check if the session is ready
	if ( session:ready() ) then
		--if the pin number is provided then require it
			if not pin(pin_number) then
				return
			end
	end

	if ( session:ready() ) then
		-- select intercept mode
			if not extension then
				log.notice("GROUP INTERCEPT")
				extensions = select_group_extensions()
			else
				log.noticef("INTERCEPT %s", extension)
				extensions = {extension}
			end

		--connect to FS database
			local dbh = Database.new('switch')

		--check the database to get the uuid of a ringing call
			call_hostname = "";
			sql = "SELECT uuid, call_uuid, hostname FROM channels ";
			sql = sql .. "WHERE callstate IN ('RINGING', 'EARLY') ";
			-- next check should prevent pickup call from extension
			-- e.g. if extension 100 dial some cell phone and some one else dial *8
			-- he can pickup this call.
			if not direction:find('all') then
				sql = sql .. "AND (1 <> 1 "
				-- calls from freeswitch to user
					if direction:find('inbound') then
						sql = sql .. "OR direction = 'outbound' ";
					end

				-- calls from user to freeswitch
					if direction:find('outbound') then
						sql = sql .. "OR direction = 'inbound' ";
					end
				sql = sql .. ") "
			end

			sql = sql .. "AND (1<>1 ";
			local params = {};
			for key,extension in pairs(extensions) do
				local param_name = "presence_id_" .. tostring(key);
				sql = sql .. "OR presence_id = :" .. param_name .. " ";
				params[param_name] = extension.."@"..domain_name;
			end
			sql = sql .. ") ";
			sql = sql .. "AND call_uuid IS NOT NULL ";
			sql = sql .. "LIMIT 1 ";
			if (debug["sql"]) then
				log.noticef("SQL: %s; params: %s", sql, json.encode(params));
			end
			local is_child
			dbh:query(sql, params, function(row)
				--for key, val in pairs(row) do
			 	--	log.notice("row "..key.." "..val);
				--end
				--log.notice("-----------------------");
				is_child = (row.uuid == row.call_uuid)
				uuid = row.call_uuid;
				call_hostname = row.hostname;
			end);
			--log.notice("uuid: "..uuid);
			--log.notice("call_hostname: "..call_hostname);
			if is_child then
				-- we need intercept `parent` call e.g. call in FIFO/CallCenter Queue
				if (call_hostname == hostname) then
					log.notice("Found child call on local machine. Try find parent channel.")
					local parent_uuid =
						channel_variable(uuid, 'ent_originate_aleg_uuid') or
						channel_variable(uuid, 'cc_member_session_uuid') or
						channel_variable(uuid, 'fifo_bridge_uuid') or
						uuid

					--[[ parent and child have to be on same box so we do not search it
					if parent_uuid ~= uuid then
						local sql = "SELECT hostname FROM channels WHERE uuid='" .. uuid .. "'"
						call_hostname = dbh:first_value(sql)
					end
					--]]

					if call_hostname then
						uuid = parent_uuid
						if call_hostname ~= hostname then
							log.noticef("Found parent call on remote machine `%s`.", call_hostname)
						else
							log.notice("Found parent call on local machine.")
						end
					end

				else
					log.noticef("Found child call on remote machine `%s`.", call_hostname)
					-- we can not find parent on this box because channel on other box so we have to
					-- forward call to this box
					session:execute("export", "sip_h_X-child_intercept_uuid="..uuid);
					return make_proxy_call(pickup_number, call_hostname)
				end
			end

		--release FS database
			dbh:release()
	end

	log.noticef( "Hostname: %s Call Hostname: %s", hostname, call_hostname);

--intercept a call that is ringing
	if (uuid ~= nil) then
		if (session:getVariable("billmsec") == nil) then
			if (hostname == call_hostname) then
				session:execute("intercept", uuid);
			else
				session:execute("export", "sip_h_X-intercept_uuid="..uuid);
				make_proxy_call(pickup_number, call_hostname)
			end
		end
	end

--get the call center channel variables and set in the intercepted call
	if (uuid ~= nil) then
		call_center_queue_uuid = api:executeString("uuid_getvar ".. uuid .." call_center_queue_uuid");
		if (call_center_queue_uuid ~= nil) then
			session:execute("set", "call_center_queue_uuid="..call_center_queue_uuid);
			session:execute("set", "cc_cause=answered");

			cc_side = api:executeString("uuid_getvar  ".. uuid .." cc_side");
			if (cc_side ~= nil) then
				session:execute("set", "cc_side="..cc_side);
			end

			cc_queue = api:executeString("uuid_getvar  ".. uuid .." cc_queue");
			if (cc_queue ~= nil) then
				session:execute("set", "cc_queue="..cc_queue);
			end

			cc_queue_joined_epoch = api:executeString("uuid_getvar  ".. uuid .." cc_queue_joined_epoch");
			if (cc_queue_joined_epoch ~= nil) then
				session:execute("set", "cc_queue_joined_epoch="..cc_queue_joined_epoch);
			end
		end
	end

--notes
	--originate a call
		--cmd = "originate user/1007@voip.example.com &intercept("..uuid..")";
		--api = freeswitch.API();
		--result = api:executeString(cmd);
