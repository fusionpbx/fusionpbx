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

--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Portions created by the Initial Developer are Copyright (C) 2016
--	the Initial Developer. All Rights Reserved.

-- load config
	require "resources.functions.config";

--set debug
	--debug["sql"] = false;

--get the variables
	domain_name = session:getVariable("domain_name");
	domain_uuid = session:getVariable("domain_uuid");
	destination_number = session:getVariable("destination_number");
	context = session:getVariable("context");

--load libraries
	local log = require "resources.functions.log"["app:dialplan:outbound:speed_dial"]
	local Database = require "resources.functions.database";
	local cache = require "resources.functions.cache";
	local json = require "resources.functions.lunajson";

-- search in memcache first
	local key = "app:dialplan:outbound:speed_dial:" .. destination_number .. "@" .. context
	local source = "memcache"
	local value = cache.get(key)

-- decode value from memcache
	if value then
		local t = json.decode(value)
		if not (t and t.phone_number and t.context) then
			log.warning("can not decode value from memcache: %s", value)
			value = nil
		else
			value = t
		end
	end

-- search in database
	if not value then
		-- set source flag
			source = "database"

		-- connect to database
			local dbh = Database.new('system');

		-- search real phone number in database
			local sql = "SELECT phone_number "
			sql = sql .. "FROM v_contact_phones "
			sql = sql .. "WHERE phone_speed_dial = :phone_speed_dial "
			sql = sql .. "AND domain_uuid = :domain_uuid "

			local params = {phone_speed_dial = destination_number, domain_uuid = domain_uuid};

			if (debug["sql"]) then
				log.noticef("SQL: %s; params: %s", sql, json.encode(params));
			end

			local phone_number = dbh:first_value(sql, params)

		-- release database connection
			dbh:release()

		-- set the cache
			if phone_number then
				value = {context = context, phone_number = phone_number}
				cache.set(key, json.encode(value), expire["speed_dial"])
			end
	end

-- transfer
	if value then
		--log the result
			log.noticef("%s XML %s source: %s", destination_number, value.context, source)

		--transfer the call
			session:transfer(value.phone_number, "XML", value.context);
	else
		log.warningf('can not find number: %s in domain: %s', destination_number, domain_name)
	end
