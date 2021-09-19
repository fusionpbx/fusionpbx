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
--	Copyright (C) 2010-2019
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set default variables
	min_digits = "1";
	max_digits = "11";
	max_tries = "3";
	digit_timeout = "3000";

--define the trim function
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

--create the api object
	api = freeswitch.API();

--include config.lua
	require "resources.functions.config";

	local blf = require "resources.functions.blf"
	local cache = require "resources.functions.cache"
	local Settings = require "resources.functions.lazy_settings"
	local notify = require "app.feature_event.resources.functions.feature_event_notify"

--answer the call
	if (session:ready()) then
		session:answer();
	end

--get the variables
	if (session:ready()) then
		enabled = session:getVariable("enabled");
		pin_number = session:getVariable("pin_number");
		sounds_dir = session:getVariable("sounds_dir");
		domain_uuid = session:getVariable("domain_uuid");
		domain_name = session:getVariable("domain_name");
		extension_uuid = session:getVariable("extension_uuid");
	end

--set the sounds path for the language, dialect and voice
	if (session:ready()) then
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end
	end

--wait a moment to sleep
	if (session:ready()) then
		session:sleep(1000);
	end

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

	local settings = Settings.new(dbh, domain_name, domain_uuid);

--include json library
	--debug["sql"] = true;
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--determine whether to update the dial string
	local sql = "select * from v_extensions ";
	sql = sql .. "where domain_uuid = :domain_uuid ";
	sql = sql .. "and extension_uuid = :extension_uuid ";
	local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid};
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[do_not_disturb] " .. sql .. "; params:" .. json.encode(params) .. "\n");
	end
	dbh:query(sql, params, function(row)
		extension = row.extension;
		number_alias = row.number_alias or '';
		accountcode = row.accountcode;
		follow_me_uuid = row.follow_me_uuid or nil;
		do_not_disturb = row.do_not_disturb;
		forward_all_destination = row.forward_all_destination;
		forward_all_enabled = row.forward_all_enabled;
		context = row.user_context;
	end);

--send information to the console
	if (session:ready()) then
		freeswitch.consoleLog("NOTICE", "[do_not_disturb] do_not_disturb "..do_not_disturb.."\n");
		freeswitch.consoleLog("NOTICE", "[do_not_disturb] extension "..extension.."\n");
		freeswitch.consoleLog("NOTICE", "[do_not_disturb] follow_me_uuid "..follow_me_uuid.."\n");
		freeswitch.consoleLog("NOTICE", "[do_not_disturb] accountcode "..accountcode.."\n");
		--freeswitch.consoleLog("NOTICE", "[do_not_disturb] enabled before "..enabled.."\n");
	end

--toggle do not disturb
	if (enabled == "toggle") then
		if (do_not_disturb == "true") then
			enabled = "false";
		else
			enabled = "true";
		end
	end

--send information to the console
	if (session:ready()) then
		freeswitch.consoleLog("NOTICE", "[do_not_disturb] enabled "..enabled.."\n");
	end

--set the dial string
	if (enabled == "true") then
		local user = (number_alias and #number_alias > 0) and number_alias or extension;
	end

--set do not disturb
	if (enabled == "true") then
		--set do_not_disturb_enabled
			do_not_disturb_enabled = "true";
		--notify the caller
			if (session:ready()) then
				session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-dnd_activated.wav");
			end
	end

--unset do not disturb
	if (enabled == "false") then
		--set fdo_not_disturb_enabled
			do_not_disturb_enabled = "false";
		--notify the caller
			if (session:ready()) then
				session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-dnd_cancelled.wav");
			end
	end

--update follow me
	if (follow_me_uuid ~= nil and enabled == 'true') then
		local sql = "update v_follow_me ";
		sql = sql .. "set follow_me_enabled = 'false' ";
		sql = sql .. "where domain_uuid = :domain_uuid ";
		sql = sql .. "and follow_me_uuid = :follow_me_uuid ";
		local params = {domain_uuid = domain_uuid, follow_me_uuid = follow_me_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[do_not_disturb] "..sql.."; params:" .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params);
	end

--update the extension
	sql = "update v_extensions set ";
	if (enabled == "true") then
		sql = sql .. "follow_me_enabled = 'false', ";
		sql = sql .. "dial_string = '!USER_BUSY', ";
		sql = sql .. "do_not_disturb = 'true', ";
		sql = sql .. "forward_all_enabled = 'false' ";
	else
		sql = sql .. "dial_string = null, ";
		sql = sql .. "do_not_disturb = 'false' ";
	end
	sql = sql .. "where domain_uuid = :domain_uuid ";
	sql = sql .. "and extension_uuid = :extension_uuid ";
	local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid};
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[do_not_disturb] "..sql.."; params:" .. json.encode(params) .. "\n");
	end
	dbh:query(sql, params);

--determine whether to update the dial string
	sql = "select * from v_extension_users as e, v_users as u ";
	sql = sql .. "where e.extension_uuid = :extension_uuid ";
	sql = sql .. "and e.user_uuid = u.user_uuid ";
	sql = sql .. "and e.domain_uuid = :domain_uuid ";
	local params = {domain_uuid = domain_uuid, extension_uuid = extension_uuid};
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[do_not_disturb] "..sql.."; params:" .. json.encode(params) .. "\n");
	end
	dbh:query(sql, params, function(row)
		--update the call center status
			if (enabled == "true") then
				user_status = "Logged Out";
				api:execute("callcenter_config", "agent set status "..row.username.."@"..domain_name.." '"..user_status.."'");
			end

		--update the database user_status
			if (enabled == "true") then
				user_status = "Do Not Disturb";
			else
				user_status = "Available";
			end
			local sql = "update v_users set ";
			sql = sql .. "user_status = :user_status ";
			sql = sql .. "where domain_uuid = :domain_uuid ";
			sql = sql .. "and user_uuid = :user_uuid ";
			local params = {user_status = user_status, domain_uuid = domain_uuid, user_uuid = row.user_uuid};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[do_not_disturb] "..sql.."; params:" .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params);
	end);

--send notify to phone if feature sync is enabled
	if settings:get('device', 'feature_sync', 'boolean') == 'true' then
		-- Get values from the database
		do_not_disturb, forward_all_enabled, forward_all_destination, forward_busy_enabled, forward_busy_destination, forward_no_answer_enabled, forward_no_answer_destination, call_timeout = notify.get_db_values(extension, domain_name)

		-- Get the sip_profile
		if (extension ~= nil and domain_name ~= nil) then
			sip_profiles = notify.get_profiles(extension, domain_name);
		end

		-- check if not nil
		if (sip_profiles ~= nil) then
				freeswitch.consoleLog("NOTICE", "[feature_event] SIP NOTIFY: CFWD set to "..forward_all_enabled.."\n");

			--Do Not Disturb
				notify.dnd(extension, domain_name, sip_profiles, do_not_disturb);

			--Forward all
				forward_immediate_enabled = forward_all_enabled;
				forward_immediate_destination = forward_all_destination;

				--workaround for freeswitch not sending NOTIFY when destination values are nil. Send 0.
					if (string.len(forward_immediate_destination) < 1) then 
						forward_immediate_destination = '0';
					end

				notify.forward_immediate(extension, domain_name, sip_profiles, forward_immediate_enabled, forward_immediate_destination);

			--Forward busy
				--workaround for freeswitch not sending NOTIFY when destination values are nil. Send 0.
					if (string.len(forward_busy_destination) < 1) then 
						forward_busy_destination = '0';
					end

				notify.forward_busy(extension, domain_name, sip_profiles, forward_busy_enabled, forward_busy_destination);

			--Forward No Answer
				ring_count = math.ceil (call_timeout / 6);
				--workaround for freeswitch not sending NOTIFY when destination values are nil. Send 0.
					if (string.len(forward_no_answer_destination) < 1) then 
						forward_no_answer_destination = '0';
					end

				notify.forward_no_answer(extension, domain_name, sip_profiles, forward_no_answer_enabled, forward_no_answer_destination, ring_count);
		end
	end

--clear the cache
	if extension and #extension > 0 and cache.support() then
		cache.del("directory:"..extension.."@"..context);
		if #number_alias > 0 then
			cache.del("directory:"..number_alias.."@"..context);
		end
	end

--wait for the file to be written before proceeding
	if (session:ready()) then
		session:sleep(1000);
	end

--end the call
	if (session:ready()) then
		session:hangup();
	end

-- BLF for display DND status
	blf.dnd(enabled == "true", extension, number_alias, domain_name)

-- Turn off BLF for call forward
	if forward_all_enabled == 'true' and enabled == 'true' then
		blf.forward(false, extension, number_alias,
			forward_all_destination, nil, domain_name
		)
	end

--disconnect from database
	dbh:release()
