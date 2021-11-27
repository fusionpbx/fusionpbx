--
--      event_notify
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
--      The Original Code is FusionPBX - event_notify
--
--      The Initial Developer of the Original Code is
--      Mark J Crane <markjcrane@fusionpbx.com>
--      Copyright (C) 2013 - 2021
--      the Initial Developer. All Rights Reserved.
--
--      Contributor(s):
--      Mark J Crane <markjcrane@fusionpbx.com>
--		KonradSC <konrd@yahoo.com>

-- Start the script
	-- /etc/freeswitch/autoload_configs/lua.conf.xml
		--	<!-- Subscribe to events -->
		--	<hook event="PHONE_FEATURE_SUBSCRIBE" subclass="" script="app.lua feature_event"/>

--Enable Feature Sync
	-- Default Settings:
		-- Device -> feature_sync = true
		
	--Yealink
		-- Web Interface -> Features -> General Information -> Feature Key Synchronization set to Enabled
		-- Config Files ->  bw.feature_key_sync = 1
	--Polycom
		-- reg.{$row.line_number}.serverFeatureControl.cf="1"
		-- reg.{$row.line_number}.serverFeatureControl.dnd="1"
	-- Cisco SPA
		-- <Feature_Key_Sync_1_ group="Ext_1/Call_Feature_Settings">Yes</Feature_Key_Sync_1_>

--prepare the api object
	api = freeswitch.API();

--define the functions
	require "resources.functions.trim";
	require "resources.functions.explode";

--connect to the database
	local Database = require "resources.functions.database";
	local Settings = require "resources.functions.lazy_settings"
	local route_to_bridge = require "resources.functions.route_to_bridge"
	local blf = require "resources.functions.blf"
	local cache = require "resources.functions.cache"
	local notify = require "app.feature_event.resources.functions.feature_event_notify"
	dbh = Database.new('system');
	local settings = Settings.new(dbh, domain_name, domain_uuid);

--set debug
	debug["sql"] = true;

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end
	
	local function empty(t)
		return (not t) or (#t == 0)
	end

--get the events
	--if (user == nil) then
		--serialize the data for the console
			--freeswitch.consoleLog("notice","[events] " .. event:serialize("xml") .. "\n");
			--freeswitch.consoleLog("notice","[evnts] " .. event:serialize("json") .. "\n");

		--get the event variables
			user            = event:getHeader("user");
			host            = event:getHeader("host");
			domain_name     = event:getHeader("host");
			contact         = event:getHeader("contact");
			feature_action  = event:getHeader("Feature-Action");
			feature_enabled = event:getHeader("Feature-Enabled");
			action_name     = event:getHeader("Action-Name");
			action_value    = event:getHeader("Action-Value")
			ring_count      = event:getHeader("ringCount")

		--send to the log
			--freeswitch.consoleLog("notice","[events] user: " .. user .. "\n");
			--freeswitch.consoleLog("notice","[events] host: " .. host .. "\n");
			--if (feature_action ~= nil) then freeswitch.consoleLog("notice","[events] feature_action: " .. feature_action .. "\n");	end
			--if (feature_enabled ~= nil) then freeswitch.consoleLog("notice","[events] feature_enabled: " .. feature_enabled .. "\n"); end
			--if (action_name ~= nil) then freeswitch.consoleLog("notice","[events] action_name: " .. action_name .. "\n"); end
			--if (action_value ~= nil) then freeswitch.consoleLog("notice","[events] action_value: " .. action_value .. "\n"); end
	--end

	--get the domain uuid from the host
		local sql = "select * from v_domains ";
		sql = sql .. "where domain_name = :domain_name ";
		local params = {domain_name = domain_name};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[feature_event] " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			domain_uuid = row.domain_uuid;
		end);

	--get extension information	
		if (user ~= nil and domain_name ~= nil) then
			do_not_disturb, forward_all_enabled, forward_all_destination, forward_busy_enabled, forward_busy_destination, forward_no_answer_enabled, forward_no_answer_destination, call_timeout = notify.get_db_values(user, domain_name)
		end

	--get sip profile
		if (user ~= nil and host ~= nil) then
			sip_profiles = notify.get_profiles(user, host);
		end

	--DND
		if (sip_profiles ~= nil) then
		--DND enabled
			if (feature_action == "SetDoNotDisturb" and feature_enabled == "true") then
				--set a variable
					dial_string = "!USER_BUSY";
					do_not_disturb = "true";

				--update the extension
					sql = "update v_extensions set ";
					sql = sql .. "do_not_disturb = :do_not_disturb, ";
					sql = sql .. "forward_all_enabled = 'false', ";
					sql = sql .. "dial_string = :dial_string ";
					sql = sql .. "where domain_uuid = :domain_uuid ";
					sql = sql .. "and extension_uuid = :extension_uuid ";
					local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid, do_not_disturb = do_not_disturb, dial_string = dial_string};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);

				--update follow me
					if (follow_me_uuid ~= nil) then
						if (string.len(follow_me_uuid) > 0) then
							local sql = "update v_follow_me set ";
							sql = sql .. "follow_me_enabled = 'false' ";
							sql = sql .. "where domain_uuid = :domain_uuid ";
							sql = sql .. "and follow_me_uuid = :follow_me_uuid ";
							local params = {domain_uuid = domain_uuid, follow_me_uuid = follow_me_uuid};
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
							end
							dbh:query(sql, params);
						end
					end

				--send notify to the phone
					notify.dnd(user, host, sip_profiles, do_not_disturb);
			end

		--DND disabled
			if (feature_action == "SetDoNotDisturb" and feature_enabled == "false") then
					--set a variable
						do_not_disturb = "false";

					--update the extension
						sql = "update v_extensions set ";
						sql = sql .. "do_not_disturb = :do_not_disturb, ";
						sql = sql .. "forward_all_enabled = 'false', ";
						sql = sql .. "dial_string = null ";
						sql = sql .. "where domain_uuid = :domain_uuid ";
						sql = sql .. "and extension_uuid = :extension_uuid ";
						local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid, do_not_disturb = do_not_disturb};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params);

				--send notify to the phone
					notify.dnd(user, host, sip_profiles, do_not_disturb);

			end

	--Call Forward

		--Call Formward All enabled
			if (feature_action == "SetCallForward" and feature_enabled == "true" and action_name == "forward_immediate") then
				--set a variable
					forward_all_destination = action_value;
					forward_all_enabled = "true";
					forward_immediate_destination = action_value;
					forward_immediate_enabled = "true";

				--set the dial string
					if feature_enabled == "true" then
						local destination_extension, destination_number_alias

						--used for number_alias to get the correct user
						local sql = "select extension, number_alias from v_extensions ";
						sql = sql .. "where domain_uuid = :domain_uuid ";
						sql = sql .. "and number_alias = :number_alias ";
						local params = {domain_uuid = domain_uuid; number_alias = forward_all_destination}
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params, function(row)
							destination_user = row.extension;
							destination_extension = row.extension;
							destination_number_alias = row.number_alias or '';
						end);

						if (destination_user ~= nil) then
							cmd = "user_exists id ".. destination_user .." "..domain_name;
						else
							cmd = "user_exists id ".. forward_all_destination .." "..domain_name;
						end
						local user_exists = trim(api:executeString(cmd));
					end

				--update the extension
					sql = "update v_extensions set ";
					sql = sql .. "do_not_disturb = 'false', ";
					sql = sql .. "forward_all_enabled = 'true', ";
					sql = sql .. "forward_all_destination = :forward_all_destination, ";
					sql = sql .. "dial_string = null ";
					sql = sql .. "where domain_uuid = :domain_uuid ";
					sql = sql .. "and extension_uuid = :extension_uuid ";
					local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid, forward_all_destination = forward_all_destination};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);

				--update follow me
					if (follow_me_uuid ~= nil) then
						if (string.len(follow_me_uuid) > 0) then
							local sql = "update v_follow_me set ";
							sql = sql .. "follow_me_enabled = 'false' ";
							sql = sql .. "where domain_uuid = :domain_uuid ";
							sql = sql .. "and follow_me_uuid = :follow_me_uuid ";
							local params = {domain_uuid = domain_uuid, follow_me_uuid = follow_me_uuid};
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
							end
							dbh:query(sql, params);
						end
					end

				--send notify to the phone
					notify.forward_immediate(user, host, sip_profiles, forward_immediate_enabled, forward_immediate_destination);
			end

		--Call Formward All disable
			if (feature_action == "SetCallForward" and feature_enabled == "false" and action_name == "forward_immediate") then
				--set a variable				
					forward_all_destination = action_value;
					forward_all_enabled = "false";
					forward_immediate_enabled = "false";
					forward_immediate_destination = action_value;

				--update the extension
					sql = "update v_extensions set ";
					sql = sql .. "do_not_disturb = 'false', ";
					sql = sql .. "forward_all_enabled = 'false', ";
					if (forward_all_destination ~= nil) then
						sql = sql .. "forward_all_destination = :forward_all_destination, ";
					else
						sql = sql .. "forward_all_destination = null, ";
					end
					sql = sql .. "dial_string = null ";
					sql = sql .. "where domain_uuid = :domain_uuid ";
					sql = sql .. "and extension_uuid = :extension_uuid ";
					local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid, forward_all_destination = forward_all_destination};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);

				--send notify to the phone
					if (forward_immediate_destination == nil) then
						forward_immediate_destination = " ";
					end
					notify.forward_immediate(user, host, sip_profiles, forward_immediate_enabled, forward_immediate_destination);
			end

		--Call Formward BUSY enable
			if (feature_action == "SetCallForward" and feature_enabled == "true" and action_name == "forward_busy") then
				--set a variable
					forward_busy_destination = action_value;
					forward_busy_enabled = "true";

				--update the extension
					sql = "update v_extensions set ";
					sql = sql .. "do_not_disturb = 'false', ";
					sql = sql .. "forward_busy_enabled = 'true', ";
					sql = sql .. "forward_busy_destination = :forward_busy_destination ";
					sql = sql .. "where domain_uuid = :domain_uuid ";
					sql = sql .. "and extension_uuid = :extension_uuid ";
					local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid, forward_busy_destination = forward_busy_destination};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);

				--send notify to the phone
					notify.forward_busy(user, host, sip_profiles, forward_busy_enabled, forward_busy_destination);
			end

		--Call Formward BUSY disable
			if (feature_action == "SetCallForward" and feature_enabled == "false" and action_name == "forward_busy") then
				--set a variable
					forward_busy_destination = action_value;
					forward_busy_enabled = "false";

				--update the extension
					sql = "update v_extensions set ";
					sql = sql .. "do_not_disturb = 'false', ";
					sql = sql .. "forward_busy_enabled = 'false', ";
					if (forward_busy_destination ~= nil) then
						sql = sql .. "forward_busy_destination = :forward_busy_destination ";
					else
						sql = sql .. "forward_busy_destination = null ";
					end					
					sql = sql .. "where domain_uuid = :domain_uuid ";
					sql = sql .. "and extension_uuid = :extension_uuid ";
					local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid, forward_busy_destination = forward_busy_destination};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);

				--send notify to the phone
					notify.forward_busy(user, host, sip_profiles, forward_busy_enabled, forward_busy_destination);
			end

		--Call Formward NO ANSWER enable
			if (feature_action == "SetCallForward" and feature_enabled == "true" and action_name == "forward_no_answer") then
				--set a variable
					forward_no_answer_destination = action_value;
					forward_no_answer_enabled = "true";
					call_timeout = ring_count * 6;

				--update the extension
					sql = "update v_extensions set ";
					sql = sql .. "do_not_disturb = 'false', ";
					sql = sql .. "call_timeout = :call_timeout, ";
					sql = sql .. "forward_no_answer_enabled = 'true', ";
					sql = sql .. "forward_no_answer_destination = :forward_no_answer_destination ";
					sql = sql .. "where domain_uuid = :domain_uuid ";
					sql = sql .. "and extension_uuid = :extension_uuid ";
					local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid, forward_no_answer_destination = forward_no_answer_destination, call_timeout = call_timeout};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);

				--send notify to the phone
					notify.forward_no_answer(user, host, sip_profiles, forward_no_answer_enabled, forward_no_answer_destination, ring_count);
			end

		--Call Formward NO ANSWER disable
			if (feature_action == "SetCallForward" and feature_enabled == "false" and action_name == "forward_no_answer") then
				--set a variable
					forward_no_answer_destination = action_value;
					forward_no_answer_enabled = "false";

				--update the extension
					sql = "update v_extensions set ";
					sql = sql .. "do_not_disturb = 'false', ";
					sql = sql .. "forward_no_answer_enabled = 'false', ";
					if (forward_no_answer_destination ~= nil) then
						sql = sql .. "forward_no_answer_destination = :forward_no_answer_destination ";
					else
						sql = sql .. "forward_no_answer_destination = null ";
					end						
					sql = sql .. "where domain_uuid = :domain_uuid ";
					sql = sql .. "and extension_uuid = :extension_uuid ";
					local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid, forward_no_answer_destination = forward_no_answer_destination, call_timeout = call_timeout};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[feature_event] "..sql.."; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);

				--send notify to the phone
					notify.forward_no_answer(user, host, sip_profiles, forward_no_answer_enabled, forward_no_answer_destination, ring_count);
			end			
	
	--No feature event (phone boots): Send all values
		if (feature_enabled == nil) then
			--Do Not Disturb
				--notify.dnd(user, host, sip_profiles, do_not_disturb);

			--Forward all
				forward_immediate_enabled = forward_all_enabled;
				forward_immediate_destination = forward_all_destination;
				--notify.forward_immediate(user, host, sip_profiles, forward_immediate_enabled, forward_immediate_destination);

			--Forward busy
				--notify.forward_busy(user, host, sip_profiles, forward_busy_enabled, forward_busy_destination);

			--Forward No Answer
				ring_count = math.ceil (call_timeout / 6);
				--notify.forward_no_answer(user, host, sip_profiles, forward_no_answer_enabled, forward_no_answer_destination, ring_count);
			notify.init(user, 
				host, 
				sip_profiles,
				forward_immediate_enabled, 
				forward_immediate_destination, 
				forward_busy_enabled, 
				forward_busy_destination, 
				forward_no_answer_enabled,
				forward_no_answer_destination, 
				ring_count, 
				do_not_disturb);
		end

--		feature_event_notify.init(user, host, sip_profiles, forward_immediate_enabled, forward_immediate_destination, forward_busy_enabled, forward_busy_destination, forward_no_answer_enabled, forward_no_answer_destination, ring_count, do_not_disturb)
	end
	--clear the cache
		if (feature_enabled ~= nil) then
			cache.del("directory:"..user.."@"..host)
		end
