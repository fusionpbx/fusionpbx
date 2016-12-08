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

--set defaults
	expire = {}
	expire["speed_dial"] = "3600";

--set debug
	--debug["sql"] = false;

--get the variables
	domain_name = session:getVariable("domain_name");
	domain_uuid = session:getVariable("domain_uuid");
	destination_number = session:getVariable("destination_number");
	context = session:getVariable("context");

--connect to the database
	local Database = require "resources.functions.database";

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--prepare the api object
	api = freeswitch.API();

--define the trim function
	require "resources.functions.trim";

--get the cache
	cache = trim(api:execute("memcache", "get app:dialplan:outbound:speed_dial:" .. destination_number .. "@" .. context));

--get the destination number
	if (cache == "-ERR NOT FOUND") then
		local dbh = Database.new('system');

		local sql = "SELECT phone_number "
		sql = sql .. "FROM v_contact_phones "
		sql = sql .. "WHERE phone_speed_dial = :phone_speed_dial "
		--sql = sql .. "AND domain_uuid = :domain_uuid "
		local params = {phone_speed_dial = destination_number};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)

			--set the local variables
				phone_number = row.phone_number;

			--set the cache
				result = trim(api:execute("memcache", "set app:dialplan:outbound:speed_dial:" .. destination_number .. "@" .. context .. " 'destination_number=" .. destination_number .. "&phone_number=" .. phone_number.. "&context=" .. context .. "' "..expire["speed_dial"]));

			--log the result
				freeswitch.consoleLog("notice", "[app:dialplan:outbound:speed_dial] " .. destination_number .. " XML " .. context .. " source: database\n");

			--transfer the call
				session:transfer(row.phone_number, "XML", context);
		end);

	else
		--add the function
			require "resources.functions.explode";

		--define the array/table and variables
			local var = {}
			local key = "";
			local value = "";

		--parse the cache
			key_pairs = explode("&", cache);
			for k,v in pairs(key_pairs) do
				f = explode("=", v);
				key = f[1];
				value = f[2];
				var[key] = value;
			end

		--send to the console
			freeswitch.consoleLog("notice", "[app:dialplan:outbound:speed_dial] " .. cache .. " source: memcache\n");

		--transfer the call
			session:transfer(var["phone_number"], "XML", var["context"]);
	end
 
