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
--	Portions created by the Initial Developer are Copyright (C) 2014
--	the Initial Developer. All Rights Reserved.

--set defaults
	expire = {}
	expire["is_local"] = "3600";

--get the variables
	domain_name = session:getVariable("domain_name");
	destination_number = session:getVariable("destination_number");
	outbound_caller_id_name = session:getVariable("outbound_caller_id_name");
	outbound_caller_id_number = session:getVariable("outbound_caller_id_number");

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

--prepare the api object
	api = freeswitch.API();

--define the trim function
	require "resources.functions.trim";

--get the cache
	cache = trim(api:execute("memcache", "get app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name));

--get the destination number
	if (cache == "-ERR NOT FOUND") then
		sql = "SELECT destination_number, destination_context "
		sql = sql .. "FROM v_destinations "
		sql = sql .. "WHERE destination_number = '"..destination_number.."' "
		sql = sql .. "AND destination_type = 'inbound' "
		sql = sql .. "AND destination_enabled = 'true' "
		--freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");
		assert(dbh:query(sql, function(row)

			--set the outbound caller id
				if (outbound_caller_id_name ~= nil) then
					session:execute("export", "caller_id_name="..outbound_caller_id_name);
					session:execute("export", "effective_caller_id_name="..outbound_caller_id_name);
				end
				if (outbound_caller_id_number ~= nil) then
					session:execute("export", "caller_id_number="..outbound_caller_id_number);
					session:execute("export", "effective_caller_id_number="..outbound_caller_id_number);
				end

			--set the local variables
				destination_context = row.destination_context;

			--set the cache
				if (destination_number == row.destination_number) then
					result = trim(api:execute("memcache", "set app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name .. " 'destination_number=" .. row.destination_number .. "&destination_context=" .. destination_context .. "' "..expire["is_local"]));
				else
					result = trim(api:execute("memcache", "set app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name .. " 'destination_number=" .. row.destination_number .. "&destination_context=" .. destination_context .. "' "..expire["is_local"]));
					result = trim(api:execute("memcache", "set app:dialplan:outbound:is_local:" .. row.destination_number .. "@" .. domain_name .. " 'destination_number=" .. row.destination_number .. "&destination_context=" .. destination_context .. "' "..expire["is_local"]));
				end

			--log the result
				freeswitch.consoleLog("notice", "[app:dialplan:outbound:is_local] " .. destination_number .. " XML " .. destination_context .. " source: database\n");

			--transfer the call
				session:transfer(row.destination_number, "XML", row.destination_context);
		end));
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

		--set the outbound caller id
			if (outbound_caller_id_name ~= nil) then
				session:execute("export", "caller_id_name="..outbound_caller_id_name);
				session:execute("export", "effective_caller_id_name="..outbound_caller_id_name);
			end
			if (outbound_caller_id_number ~= nil) then
				session:execute("export", "caller_id_number="..outbound_caller_id_number);
				session:execute("export", "effective_caller_id_number="..outbound_caller_id_number);
			end

		--send to the console
			freeswitch.consoleLog("notice", "[app:dialplan:outbound:is_local] " .. cache .. " source: memcache\n");

		--transfer the call
			session:transfer(var["destination_number"], "XML", var["destination_context"]);
	end