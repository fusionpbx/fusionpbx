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
--	Portions created by the Initial Developer are Copyright (C) 2014-2020
--	the Initial Developer. All Rights Reserved.


--set defaults
	expire = {}
	expire["is_local"] = "3600";

--get the variables
	domain_name = session:getVariable("domain_name");
	destination_number = session:getVariable("destination_number");
	outbound_caller_id_name = session:getVariable("outbound_caller_id_name");
	outbound_caller_id_number = session:getVariable("outbound_caller_id_number");

--includes
	local cache = require"resources.functions.cache"

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--prepare the api object
	api = freeswitch.API();

--define the trim function
	require "resources.functions.trim";

--set the cache key
	key = "app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name;

--get the destination number
	value, err = cache.get(key);
	if (err == 'NOT FOUND') then

		--connect to the database
		local Database = require "resources.functions.database";
		local dbh = Database.new('system');

		--select data from the database
		local sql = "SELECT destination_number, destination_context ";
		sql = sql .. "FROM v_destinations ";
		sql = sql .. "WHERE ( ";
		sql = sql .. "	destination_prefix || destination_area_code || destination_number = :destination_number ";
		sql = sql .. "	OR destination_trunk_prefix || destination_area_code || destination_number = :destination_number ";
		sql = sql .. "	OR destination_prefix || destination_number = :destination_number ";
		sql = sql .. "	OR '+' || destination_prefix || destination_number = :destination_number ";
		sql = sql .. "	OR '+' || destination_prefix || destination_area_code || destination_number = :destination_number ";
		sql = sql .. "	OR destination_area_code || destination_number = :destination_number ";
		sql = sql .. "	OR destination_number = :destination_number ";
		sql = sql .. ") ";
		sql = sql .. "AND destination_type = 'inbound' ";
		sql = sql .. "AND destination_enabled = 'true' ";
		local params = {destination_number = destination_number};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)

			--set the local variables
				destination_context = row.destination_context;

			--set the cache
				if (destination_number == row.destination_number) then
					key = "app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name; 
					value = "destination_number=" .. row.destination_number .. "&destination_context=" .. destination_context;
					ok, err = cache.set(key, value, expire["is_local"]);
				else
					key = "app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name;
					value = "destination_number=" .. row.destination_number .. "&destination_context=" .. destination_context;
					ok, err = cache.set(key, value, expire["is_local"]);
				end

			--log the result
				freeswitch.consoleLog("notice", "[app:dialplan:outbound:is_local] " .. row.destination_number .. " XML " .. destination_context .. " source: database\n");

			--set the outbound caller id
				if (outbound_caller_id_name ~= nil) then
					session:execute("set", "caller_id_name="..outbound_caller_id_name);
					session:execute("set", "effective_caller_id_name="..outbound_caller_id_name);
				end
				if (outbound_caller_id_number ~= nil) then
					session:execute("set", "caller_id_number="..outbound_caller_id_number);
					session:execute("set", "effective_caller_id_number="..outbound_caller_id_number);
				end

			--transfer the call
				session:transfer(row.destination_number, "XML", row.destination_context);
		end);

	else
		--add the function
			require "resources.functions.explode";

		--define the array/table
			local var = {}

		--parse the cache
			key_pairs = explode("&", value);
			for k,v in pairs(key_pairs) do
				f = explode("=", v);
				key = f[1];
				value = f[2];
				var[key] = value;
			end

		--set the outbound caller id
			if (outbound_caller_id_name ~= nil) then
				session:execute("set", "caller_id_name="..outbound_caller_id_name);
				session:execute("set", "effective_caller_id_name="..outbound_caller_id_name);
			end
			if (outbound_caller_id_number ~= nil) then
				session:execute("set", "caller_id_number="..outbound_caller_id_number);
				session:execute("set", "effective_caller_id_number="..outbound_caller_id_number);
			end

		--send to the console
			freeswitch.consoleLog("notice", "[app:dialplan:outbound:is_local] " .. value .. " source: cache\n");

		--transfer the call
			session:transfer(var["destination_number"], "XML", var["destination_context"]);
	end
