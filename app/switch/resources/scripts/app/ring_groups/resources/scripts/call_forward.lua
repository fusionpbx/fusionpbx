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
--	Copyright (C) 2010-2021
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set default variables
	local min_digits    = 3;
	local max_digits    = 11;
	local max_tries     = 3;
	local digit_timeout = 3000;

--include config.lua
	require "resources.functions.config";

--include libraries
	require "resources.functions.channel_utils";
	local log      = require "resources.functions.log".ring_group_call_forward
	local Database = require "resources.functions.database"
	local blf      = require "resources.functions.blf"

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
	local enabled             = session:getVariable("enabled");
	local pin_number          = session:getVariable("pin_number");
	local sounds_dir          = session:getVariable("sounds_dir");
	local domain_uuid         = session:getVariable("domain_uuid");
	local domain_name         = session:getVariable("domain_name");
	local ring_group_number   = session:getVariable("ring_group_number");
	local ring_group_uuid     = session:getVariable("ring_group_uuid");
	local forward_destination = session:getVariable("forward_destination");
	local forward_reset       = session:getVariable("forward_reset");

--set the sounds path for the language, dialect and voice
	local default_language = session:getVariable("default_language") or 'en';
	local default_dialect = session:getVariable("default_dialect") or 'us';
	local default_voice = session:getVariable("default_voice") or 'callie';

--a moment to sleep
	session:sleep(1000);

--connect to the database
	local dbh = Database.new('system');

--check pin code
	if not empty(pin_number) then
		--get the pin number
			local min_digits = 3;
			local max_digits = 20;
			local caller_pin_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
			if empty(caller_pin_number) then return end

		--check pin number
			if pin_number ~= caller_pin_number then return end

		--user hangup
			if not session:ready() then return end
	end

--get ring group number
	if empty(ring_group_number) then
		--get the ring group extension number
			local min_digits = 2;
			local max_digits = 20;
			ring_group_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");
			if empty(ring_group_number) then return end

		-- user hangup
			if not session:ready() then return end
	end

--user hangup
	if not session:ready() then return end

--search ring_group in database
	local sql = [[SELECT ring_group_uuid as uuid, ring_group_forward_enabled as forward_enabled,
		ring_group_forward_destination as forward_destination, ring_group_extension as extension
	FROM v_ring_groups
		WHERE domain_uuid = :domain_uuid
	]]
	local params = {domain_uuid = domain_uuid}
	if (not empty(ring_group_number) or empty(ring_group_uuid)) then
		sql = sql .. " AND ring_group_extension=:extension"
		params.extension = ring_group_number
	else
		sql = sql .. " AND ring_group_uuid=:ring_group_uuid"
		params.ring_group_uuid = ring_group_uuid
	end
	if (debug["sql"]) then
		log.noticef("SQL: %s; params: %s", sql, json.encode(params));
	end
	local ring_group = dbh:first_row(sql, params)

--if can not find ring group
	if (not ring_group) or (not ring_group.uuid) then return end

-- user hangup
	if not session:ready() then return end

-- get destination number from the database if it not provided
	if enabled == 'toggle' and empty(forward_destination) then
		forward_destination = ring_group.forward_destination
	end

--toggle enabled
	if enabled == 'toggle' then
		-- if we toggle CF and specify new destination number then just enable it
		if forward_destination == ring_group.forward_destination then
			enabled = (ring_group.forward_enabled == 'true') and 'false' or 'true'
		else
			enabled = 'true'
		end
	end

--get the forward destination
	if enabled == 'true' and empty(forward_destination) then
		-- get number
			forward_destination = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");
			if empty(forward_destination) then return end
		-- user hangup
			if not session:ready() then return end
	end

--set the values based on conditions
	forward_enabled = (enabled == 'true') and 'true' or 'false';
	if (forward_enabled == 'false' and forward_reset == 'true') then
		forward_destination = '';
	end

--update ring group call farward in database
	local sql = [[UPDATE v_ring_groups
	SET
		ring_group_forward_enabled = :forward_enabled,
		ring_group_forward_destination = :forward_destination
	WHERE
		ring_group_uuid = :ring_group_uuid]]
	local params = {
		forward_enabled = forward_enabled,
		forward_destination = forward_destination,
		ring_group_uuid = ring_group.uuid,
	}
	if (debug["sql"]) then
		log.noticef("SQL: %s; params: %s", sql, json.encode(params));
	end
	dbh:query(sql, params)

--disconnect from database
	dbh:release()

--notify caller
	if enabled == 'true' then
		--set forward_all_enabled
			channel_display(session:get_uuid(), "Activated")
		--say the destination number
			session:say(forward_destination, default_language, "number", "iterated");
		--notify the caller
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-call_forwarding_has_been_set.wav");
	else
		--set forward_all_enabled
			channel_display(session:get_uuid(), "Cancelled")
		--notify the caller
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-call_forwarding_has_been_cancelled.wav");
	end

-- BLF for display CF status
	blf.forward(enabled == 'true', ring_group.extension, nil, ring_group.forward_destination, forward_destination, domain_name)
