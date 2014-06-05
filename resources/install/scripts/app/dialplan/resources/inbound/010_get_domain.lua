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
	expire["get_domain"] = "3600";
	source = "";

--get the variables
	local destination_number = session:getVariable("destination_number");

--remove the plus if it exists
	if (string.sub(destination_number, 0, 1) == "+") then
		destination_number = string.sub(destination_number, 2, (string.len(destination_number)));
	end

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--get the cache
	freeswitch.consoleLog("notice", "[app:dialplan:inbound:get_domain] memcache get app:dialplan:inbound:get_domain:" .. destination_number .. "\n");
	cache = trim(api:execute("memcache", "get app:dialplan:inbound:get_domain:" .. destination_number));

--get the ring group destinations
	if (cache == "-ERR NOT FOUND") then
		sql = "SELECT d.domain_uuid, d.domain_name, n.destination_number, n.destination_context "
		sql = sql .. "FROM v_destinations as n, v_domains as d "
		sql = sql .. "WHERE n.destination_number = '"..destination_number.."' "
		sql = sql .. "AND n.destination_type = 'inbound' "
		sql = sql .. "AND n.domain_uuid = d.domain_uuid "
		--freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");
		assert(dbh:query(sql, function(row)
			--set the local variables
				domain_uuid = row.domain_uuid;
				domain_name = row.domain_name;
				--local destination_number = row.destination_number;
				--local destination_context = row.destination_context;

			--set the cache
				cache = "domain_uuid=" .. domain_uuid .. "&domain_name=" .. domain_name;
				result = trim(api:execute("memcache", "set app:dialplan:inbound:get_domain:" .. destination_number .. " '"..cache.."' "..expire["get_domain"]));

			--set the source
				source = "database";
			end));

	else
		--add the function
			dofile(scripts_dir.."/resources/functions/explode.lua");

		--parse the cache
			array = explode("&", cache);

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

		--set the variables
			domain_uuid = var["domain_uuid"];
			domain_name = var["domain_name"];

		--set the source
			source = "memcache";
	end

	if (domain_name ~= nil) then
		--set the call direction as a session variable
			session:setVariable("domain_name", domain_name);
			session:setVariable("domain", domain_name);
			session:setVariable("domain_uuid", domain_uuid);
		--send information to the console
			freeswitch.consoleLog("notice", "[app:dialplan:inbound:get_domain] " .. cache .. " source: ".. source .."\n");
	end
