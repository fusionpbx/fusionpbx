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
--	Copyright (C) 2010-2014
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
	require "resources.functions.trim"

--create the api object
	api = freeswitch.API();

--include config.lua
	require "resources.functions.config";

--include config.lua
	require "resources.functions.settings";

	require "resources.functions.channel_utils";

	local log = require "resources.functions.log".call_forward
	local cache = require "resources.functions.cache"
	local Database = require "resources.functions.database"

	local function opt(t, ...)
		if select('#', ...) == 0 then
			return t
		end
		if type(t) ~= 'table' then
			return nil
		end
		return opt(t[...], select(2, ...))
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
	local extension, dial_string

--set the sounds path for the language, dialect and voice
	local default_language = session:getVariable("default_language") or 'en';
	local default_dialect = session:getVariable("default_dialect") or 'us';
	local default_voice = session:getVariable("default_voice") or 'callie';

--a moment to sleep
	session:sleep(1000);

--connect to the database
	dbh = Database.new('system');

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
			sql = sql .. "WHERE domain_uuid = '" .. domain_uuid .."' ";
			sql = sql .. "AND voicemail_id = '" .. extension .."' ";
			if (debug["sql"]) then
				log.notice(sql);
			end
			local voicemail_password = dbh:first_value(sql)
			if (voicemail_password ~= caller_pin_number) then
				--access denied
				session:streamFile("phrase:voicemail_fail_auth:#");
				return session:hangup("NORMAL_CLEARING");
			end
	end

--determine whether to update the dial string
	if not session:ready() then return end

	local sql = "select * from v_extensions ";
	sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
	if (extension_uuid ~= nil) then
		sql = sql .. "and extension_uuid = '"..extension_uuid.."' ";
	else
		sql = sql .. "and (extension = '"..extension.."' or number_alias = '"..extension.."') ";
	end
	if (debug["sql"]) then
		log.notice(sql);
	end
	local row = dbh:first_row(sql)
	if not row then return end

	extension_uuid = row.extension_uuid;
	extension = row.extension;
	local number_alias = row.number_alias or '';
	local accountcode = row.accountcode;
	local forward_all_enabled = row.forward_all_enabled;
	local forward_all_destination = row.forward_all_destination;
	local follow_me_uuid = row.follow_me_uuid;
	local toll_allow = row.toll_allow or '';
	local forward_caller_id_uuid = row.forward_caller_id_uuid;

--toggle enabled
	if enabled == "toggle" then
		enabled = (forward_all_enabled == "true") and "false" or "true";
	end

	if not session:ready() then return end

--get the forward destination
	if enabled == "true" and empty(forward_all_destination) then
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

--get the caller_id for outbound call
	local forward_caller_id = ""
	if enabled == "true" and not empty(forward_caller_id_uuid) then
		local sql = "select destination_number, destination_description,"..
			"destination_caller_id_number, destination_caller_id_name " ..
			"from v_destinations where domain_uuid = '" .. domain_uuid .. "' and " ..
			"destination_type = 'inbound' and destination_uuid = '" .. forward_caller_id_uuid .. "'";
		local row = dbh:first_row(sql)
		if row then
			local caller_id_number = row.destination_caller_id_number
			if empty(caller_id_number) then
				caller_id_number = row.destination_number
			end

			local caller_id_name = row.destination_caller_id_name
			if empty(caller_id_name) then
				caller_id_name = row.destination_description
			end

			if not empty(caller_id_number) then
				forward_caller_id = forward_caller_id ..
					 ",outbound_caller_id_number=" .. caller_id_number ..
					 ",origination_caller_id_number=" .. caller_id_number
			end

			if not empty(caller_id_name) then
				forward_caller_id = forward_caller_id ..
					 ",outbound_caller_id_name=" .. caller_id_name ..
					 ",origination_caller_id_name=" .. caller_id_name
			end
		end
	end

--set the dial string
	if enabled == "true" then
		local destination_extension, destination_number_alias

		--used for number_alias to get the correct user
		local sql = "select extension, number_alias from v_extensions ";
		sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
		sql = sql .. "and number_alias = '"..forward_all_destination.."' ";
		dbh:query(sql, function(row)
			destination_user = row.extension;
			destination_extension = row.extension;
			destination_number_alias = row.number_alias or '';
		end);

		local presence_id
		if destination_extension then
			if (#destination_number_alias > 0) and (opt(settings(domain_uuid), 'provision', 'number_as_presence_id', 'boolean') == 'true') then
				presence_id = destination_number_alias
			else
				presence_id = destination_extension
			end
		elseif extension then
			-- setting here presence_id equal extension not dialed number allows work BLF and intercept.
			-- $presence_id = extension_presence_id($this->extension, $this->number_alias);
			if (#number_alias > 0) and (opt(settings(domain_uuid), 'provision', 'number_as_presence_id', 'boolean') == 'true') then
				presence_id = number_alias
			else
				presence_id = extension
			end
		else
			presence_id = forward_all_destination
		end

		--set the dial_string
		dial_string = "{presence_id="..presence_id.."@"..domain_name;
		dial_string = dial_string .. ",instant_ringback=true";
		dial_string = dial_string .. ",domain_uuid="..domain_uuid;
		dial_string = dial_string .. ",sip_invite_domain="..domain_name;
		dial_string = dial_string .. ",domain_name="..domain_name;
		dial_string = dial_string .. ",domain="..domain_name;
		dial_string = dial_string .. ",toll_allow='"..toll_allow.."'";
		if (accountcode ~= nil) then
			dial_string = dial_string .. ",accountcode="..accountcode;
		end
		dial_string = dial_string .. forward_caller_id
		dial_string = dial_string .. "}";

		if (destination_user ~= nil) then
			cmd = "user_exists id ".. destination_user .." "..domain_name;
		else
			cmd = "user_exists id ".. forward_all_destination .." "..domain_name;
		end
		user_exists = trim(api:executeString(cmd));
		if (user_exists == "true") then
			if (destination_user ~= nil) then
				dial_string = dial_string .. "user/"..destination_user.."@"..domain_name;
			else
				dial_string = dial_string .. "user/"..forward_all_destination.."@"..domain_name;
			end
		else
			dial_string = dial_string .. "loopback/"..forward_all_destination;
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
		sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
		sql = sql .. "and follow_me_uuid = '"..follow_me_uuid.."' ";
		if (debug["sql"]) then
			log.notice(sql);
		end
		dbh:query(sql);
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
			sql = sql .. "forward_all_destination = '"..forward_all_destination.."', ";
			sql = sql .. "dial_string = '"..dial_string:gsub("'", "''").."', ";
			sql = sql .. "do_not_disturb = 'false', ";
		else
			sql = sql .. "forward_all_destination = null, ";
			sql = sql .. "dial_string = null, ";
		end
		sql = sql .. "forward_all_enabled = '"..forward_all_enabled.."' ";
		sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
		sql = sql .. "and extension_uuid = '"..extension_uuid.."' ";
		if (debug["sql"]) then
			log.notice(sql);
		end
		dbh:query(sql);
	end

--disconnect from database
	dbh:release()

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
