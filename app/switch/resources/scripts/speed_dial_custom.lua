--	FusionPBX
--	Version: MPL 1.1

--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/

--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.

--	The Original Code is FusionPBX

-- load config
	require "resources.functions.config";

--set debug
	-- debug["sql"] = true;

--load libraries
	local log = require "resources.functions.log"["app:dialplan:outbound:speed_dial"]
	local Database = require "resources.functions.database";
	local json = require "resources.functions.lunajson";

--get the variables
	domain_name = session:getVariable("domain_name");
	domain_uuid = session:getVariable("domain_uuid");
	context = session:getVariable("context");
	user = session:getVariable("sip_auth_username")
		or session:getVariable("username");

--get the argv values
	destination = argv[1];

		-- set source flag
			source = "database"

		-- connect to database
			local dbh = Database.new('system');

		-- search for the phone number in database using the speed dial
			local sql = [[
				-- find all contacts with correct user or withot users and groups at all
				select t0.phone_number 
				from v_contact_phones t0
				where t0.domain_uuid = :domain_uuid and t0.phone_speed_dial = :phone_speed_dial

			]];
			local params = {phone_speed_dial = destination, domain_uuid = domain_uuid};

			if (debug["sql"]) then
				log.noticef("SQL: %s; params: %s", sql, json.encode(params));
			end

			local phone_number = dbh:first_value(sql, params)

		-- release database connection
			dbh:release()

			if phone_number then
				value = {phone_number = phone_number}
			end

-- transfer
	if value then
		--log the result
			log.noticef("Speed Dial: %s. Destination: %s. XML: %s. Source: %s", destination, phone_number, context, source)

		--transfer the call
			session:transfer(value.phone_number, "XML", context);
	else
		log.warningf('can not find number: %s in domain: %s', destination, domain_name)
	end
