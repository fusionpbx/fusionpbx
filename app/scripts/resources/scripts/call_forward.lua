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
--	Copyright (C) 2010-2017
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set default variables
	min_digits = "1";
	max_digits = "17";
	max_tries = "3";
	digit_timeout = "5000";

--define the trim function
	require "resources.functions.trim"

--create the api object
	api = freeswitch.API();

--includes
	require "resources.functions.config";
	require "resources.functions.channel_utils";
	local log = require "resources.functions.log".call_forward
	local cache = require "resources.functions.cache"
	local Database = require "resources.functions.database"
	local Settings = require "resources.functions.lazy_settings"
	local blf = require "resources.functions.blf"
	local notify = require "app.feature_event.resources.functions.feature_event_notify"	

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

	local function empty(t)
		return (not t) or (#t == 0)
	end

--check if the session is ready
	if not session:ready() then return end

--answer the call
	session:answer();

--get the variables
	local enabled = session:getVariable("enabled");
	local pin_number = session:getVariable("pin_number");
	local sounds_dir = session:getVariable("sounds_dir");
	local domain_uuid = session:getVariable("domain_uuid");
	local domain_name = session:getVariable("domain_name");
	local extension_uuid = session:getVariable("extension_uuid");
	local request_id = session:getVariable("request_id");
	local forward_all_destination = session:getVariable("forward_all_destination") or '';
	local extension;

--set the sounds path for the language, dialect and voice
	local default_language = session:getVariable("default_language") or 'en';
	local default_dialect = session:getVariable("default_dialect") or 'us';
	local default_voice = session:getVariable("default_voice") or 'callie';

--a moment to sleep
	session:sleep(1000);

--connect to the database
	local dbh = Database.new('system');

	local settings = Settings.new(dbh, domain_name, domain_uuid);

--request id is true
	if (request_id == "true") then
		--unset extension uuid
			extension_uuid = nil;

		--get the extension
			if not session:ready() then return end
			local min_digits = 2;
			local max_digits = 20;
			extension = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");
			if empty(extension) then return end

		--get the pin number
			if not session:ready() then return end
			min_digits = 3;
			max_digits = 20;
			local caller_pin_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
			if empty(caller_pin_number) then return end

		--check to see if the pin number is correct
			if not session:ready() then return end
			local sql = "SELECT voicemail_password FROM v_voicemails ";
			sql = sql .. "WHERE domain_uuid = :domain_uuid ";
			sql = sql .. "AND voicemail_id = :extension ";
			local params = {domain_uuid = domain_uuid, extension = extension};
			if (debug["sql"]) then
				log.noticef("SQL: %s; params: %s", sql, json.encode(params));
			end
			local voicemail_password = dbh:first_value(sql, params)
			if (voicemail_password ~= caller_pin_number) then
				--access denied
				session:streamFile("phrase:voicemail_fail_auth:#");
				return session:hangup("NORMAL_CLEARING");
			end
	end

--determine whether to update the dial string
	if not session:ready() then return end

	local sql = "select * from v_extensions ";
	sql = sql .. "where domain_uuid = :domain_uuid ";
	local params = {domain_uuid = domain_uuid};
	if (extension_uuid ~= nil) then
		sql = sql .. "and extension_uuid = :extension_uuid ";
		params.extension_uuid = extension_uuid;
	else
		sql = sql .. "and (extension = :extension or number_alias = :extension) ";
		params.extension = extension;
	end
	if (debug["sql"]) then
		log.noticef("SQL: %s; params: %s", sql, json.encode(params));
	end
	local row = dbh:first_row(sql, params)
	if not row then return end

	extension_uuid = row.extension_uuid;
	extension = row.extension;
	local number_alias = row.number_alias or '';
	local accountcode = row.accountcode;
	local forward_all_enabled = row.forward_all_enabled;
	local last_forward_all_destination = row.forward_all_destination;
	local follow_me_uuid = row.follow_me_uuid;
	local toll_allow = row.toll_allow or '';

--toggle enabled
	if enabled == "toggle" then
		-- if we toggle CF and specify new destination number then just enable it
		if (#forward_all_destination == 0) or (forward_all_destination == row.forward_all_destination) then
			enabled = (forward_all_enabled == "true") and "false" or "true";
		else
			enabled = 'true'
		end
	end

-- get destination number form database if it not provided
	if enabled == 'true' and #forward_all_enabled == 0 then
		forward_all_destination = row.forward_all_destination
	end

	if not session:ready() then return end

--get the destination by argument and set the forwarding destination 
	destination_by_arg = argv[1];
	
	if enabled == "true" and destination_by_arg then
		forward_all_destination = destination_by_arg;
	end
	
--get the forward destination by IVR if destination has not been passed by argument
	if enabled == "true" and empty(forward_all_destination) and not destination_by_arg then
		forward_all_destination = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");
		if empty(forward_all_destination) then return end
	end

--set call forward
	if enabled == "true" then
		--set forward_all_enabled
			forward_all_enabled = "true";
			channel_display(session:get_uuid(), "Activated")
		--say the destination number
			session:say(forward_all_destination, default_language, "number", "iterated");
		--notify the caller
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-call_forwarding_has_been_set.wav");
	end

--get default caller_id for outbound call
	if enabled == "true" and settings:get('cdr', 'call_forward_fix', 'boolean') == 'true' then
		if not empty(row.outbound_caller_id_number) then
			forward_caller_id = forward_caller_id ..
				 ",outbound_caller_id_number=" .. row.outbound_caller_id_number ..
				 ",origination_caller_id_number=" .. row.outbound_caller_id_number
		end
		if not empty(row.outbound_caller_id_name) then
			forward_caller_id = forward_caller_id ..
				 ",outbound_caller_id_name=" .. row.outbound_caller_id_name ..
				 ",origination_caller_id_name=" .. row.outbound_caller_id_name
		end
	end

--unset call forward
	if session:ready() and enabled == "false" then
		--set forward_all_enabled
			forward_all_enabled = "false";
			channel_display(session:get_uuid(), "Cancelled")
		--notify the caller
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-call_forwarding_has_been_cancelled.wav");
	end

--disable the follow me
	if enabled == "true" and not empty(follow_me_uuid) then
		local sql = "update v_follow_me set ";
		sql = sql .. "follow_me_enabled = 'false' ";
		sql = sql .. "where domain_uuid = :domain_uuid ";
		sql = sql .. "and follow_me_uuid = :follow_me_uuid ";
		local params = {domain_uuid = domain_uuid, follow_me_uuid = follow_me_uuid};
		if (debug["sql"]) then
			log.noticef("SQL: %s; params: %s", sql, json.encode(params));
		end
		dbh:query(sql, params);
	end

--check the destination
	if empty(forward_all_destination) then
		enabled = "false";
		forward_all_enabled = "false";
	end

--update the extension
	do
		local sql = "update v_extensions set ";
		if (enabled == "true") then
			sql = sql .. "forward_all_destination = :forward_all_destination, ";
			sql = sql .. "do_not_disturb = 'false', ";
		else
			sql = sql .. "forward_all_destination = null, ";
		end
		sql = sql .. "forward_all_enabled = :forward_all_enabled ";
		sql = sql .. "where domain_uuid = :domain_uuid ";
		sql = sql .. "and extension_uuid = :extension_uuid ";
		local params = {
			forward_all_destination = forward_all_destination;
			forward_all_enabled = forward_all_enabled;
			domain_uuid = domain_uuid;
			extension_uuid = extension_uuid;
		}
		if (debug["sql"]) then
			log.noticef("SQL: %s; params: %s", sql, json.encode(params));
		end
		dbh:query(sql, params);
	end

--send notify to phone if feature sync is enabled
	if settings:get('device', 'feature_sync', 'boolean') == 'true' then
		-- Get values from the database
			do_not_disturb, forward_all_enabled, forward_all_destination, forward_busy_enabled, forward_busy_destination, forward_no_answer_enabled, forward_no_answer_destination, call_timeout = notify.get_db_values(extension, domain_name);

		-- Get the sip_profile
			if (extension ~= nil and domain_name ~= nil) then
				sip_profiles = notify.get_profiles(extension, domain_name);
			end

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

				freeswitch.consoleLog("NOTICE", "[feature_event] forward_immediate_destination "..forward_immediate_destination.."\n");
				notify.forward_immediate(extension, domain_name, sip_profiles, forward_immediate_enabled, forward_immediate_destination);

			--Forward busy
				--workaround for freeswitch not sending NOTIFY when destination values are nil. Send 0.
					if (string.len(forward_busy_destination) < 1) then
						forward_busy_destination = '0';
					end

				freeswitch.consoleLog("NOTICE", "[feature_event] forward_busy_destination "..forward_busy_destination.."\n");
				notify.forward_busy(extension, domain_name, sip_profiles, forward_busy_enabled, forward_busy_destination);

			--Forward No Answer
				ring_count = math.ceil (call_timeout / 6);
				--workaround for freeswitch not sending NOTIFY when destination values are nil. Send 0.
					if (string.len(forward_no_answer_destination) < 1) then 
						forward_no_answer_destination = '0';
					end

				freeswitch.consoleLog("NOTICE", "[feature_event] forward_no_answer_destination "..forward_no_answer_destination.."\n");
				notify.forward_no_answer(extension, domain_name, sip_profiles, forward_no_answer_enabled, forward_no_answer_destination, ring_count);
		end
	end

--disconnect from database
	dbh:release();

--clear the cache
	if extension and #extension > 0 and cache.support() then
		cache.del("directory:"..extension.."@"..domain_name);
		if #number_alias > 0 then
			cache.del("directory:"..number_alias.."@"..domain_name);
		end
	end

--hangup
	if (session:ready()) then
		--wait for the file to be written before proceeding
			session:sleep(100);
		--end the call
			session:hangup();
	end

-- BLF for display CF status
	blf.forward(enabled == 'true', extension, number_alias, 
		last_forward_all_destination, forward_all_destination, domain_name);

-- turn off DND BLF
	if (enabled == 'true') then
		blf.dnd(false, extension, number_alias, domain_name);
	end
