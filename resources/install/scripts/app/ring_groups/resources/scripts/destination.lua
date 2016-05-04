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
--	Copyright (C) 2010-2016
--	All Rights Reserved.
--
--	Contributor(s):
--	Koldo A. Marcos <koldo.aingeru@sarenet.es>


--include config.lua
	require "resources.functions.config";

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

--set default variables
	sounds_dir = "";
	recordings_dir = "";
	pin_number = "";
	max_tries = "3";
	digit_timeout = "3000";

--define uuid function
	local random = math.random;
	local function uuid()
		local template ='xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
		return string.gsub(template, '[xy]', function (c)
			local v = (c == 'x') and random(0, 0xf) or random(8, 0xb);
			return string.format('%x', v);
		end)
	end

--get session variables
	if (session:ready()) then
		session:answer();
		--session:execute("info", "");
		destination = session:getVariable("destination");
		pin_number = session:getVariable("pin_number");
		sounds_dir = session:getVariable("sounds_dir");
		ring_group_uuid = session:getVariable("ring_group_uuid");
		domain_uuid = session:getVariable("domain_uuid");
	end

--get the domain uuid and set other required variables
	if (session:ready()) then
		--get info for the ring group
			--sql = "SELECT * FROM v_ring_groups ";
			--sql = sql .. "where ring_group_uuid = '"..ring_group_uuid.."' ";
			--status = dbh:query(sql, function(row)
			--	domain_uuid = row["domain_uuid"];
			--end);

		--set destination defaults
			destination_timeout = 15;
			destination_delay = 0;

		--create the primary key uuid
			ring_group_destination_uuid = uuid();
	end

--if the pin number is provided then require it
	if (pin_number) then
		min_digits = string.len(pin_number);
		max_digits = string.len(pin_number)+1;
		digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-please_enter_pin_followed_by_pound.wav", "", "\\d+");
		if (digits == pin_number) then
			--pin is correct
		else
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-pin_or_extension_is-invalid.wav");
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-im_sorry.wav");
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-goodbye.wav");
			session:hangup("NORMAL_CLEARING");
			return;
		end
	end

--get the destination
	--if (session:ready()) then
	--	if string.len(destination) == 0) then
	--		destination = session:playAndGetDigits(1, 1, max_tries, digit_timeout, "#", "ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");
	--		freeswitch.consoleLog("NOTICE", "[ring_group] destination: "..destination.."\n");
	--	end
	--end

--login or logout
	if (session:ready()) then
		menu_selection = session:playAndGetDigits(1, 1, max_tries, digit_timeout, "#", "ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");
		freeswitch.consoleLog("NOTICE", "[ring_group] menu_selection: "..menu_selection.."\n");
		if (menu_selection == "1") then
			--first, check to see if the destination is already in this ring group
			sql = [[
				SELECT COUNT(*) AS in_group FROM
					v_ring_group_destinations
				WHERE
					domain_uuid = ']]..domain_uuid..[['
					AND ring_group_uuid = ']]..ring_group_uuid..[['
					AND destination_number = ']]..destination..[['
			]];
			--freeswitch.consoleLog("NOTICE", "[ring_group] SQL "..sql.."\n");

			assert(dbh:query(sql, function(row)
				if (row.in_group == "0") then
					sql = [[
						INSERT INTO
							v_ring_group_destinations
								(	ring_group_destination_uuid,
									domain_uuid,
									ring_group_uuid,
									destination_number,
									destination_delay,
									destination_timeout
								)
						VALUES
								(	']]..ring_group_destination_uuid..[[',
									']]..domain_uuid..[[',
									']]..ring_group_uuid..[[',
									']]..destination..[[',
									]]..destination_delay..[[,
									]]..destination_timeout..[[
								)]];
					freeswitch.consoleLog("NOTICE", "[ring_group][destination] SQL "..sql.."\n");
					dbh:query(sql);

					freeswitch.consoleLog("NOTICE", "[ring_group][destination] LOG IN\n");
					session:streamFile("ivr/ivr-you_are_now_logged_in.wav");
				else
					freeswitch.consoleLog("NOTICE", "[ring_group][destination] ALREADY LOGGED IN\n");
					session:streamFile("ivr/ivr-you_are_now_logged_in.wav");
				end
			end));
		end
		if (menu_selection == "2") then
			sql = [[
				DELETE FROM
					v_ring_group_destinations
				WHERE
					domain_uuid =']]..domain_uuid..[['
					AND ring_group_uuid=']]..ring_group_uuid..[['
					AND destination_number=']]..destination..[['
				]];
			freeswitch.consoleLog("NOTICE", "[ring_group][destination] SQL "..sql.."\n");
			dbh:query(sql);

			freeswitch.consoleLog("NOTICE", "[ring_group][destination] LOG OUT\n");
			session:streamFile("ivr/ivr-you_are_now_logged_out.wav");
		end
	end

--wait for the file to be written before proceeding
	if (session:ready()) then
		--session:sleep(1000);
	end

--hangup
	if (session:ready()) then
		session:hangup();
	end
