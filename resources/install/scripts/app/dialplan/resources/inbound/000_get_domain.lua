--	ring_groups.lua
--	Part of FusionPBX
--	Copyright (C) 2010-2013 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--set defaults
	expire = {}
	expire["get_domain"] = "3600";
	source = "";

--get the variables
	local destination_number = session:getVariable("destination_number");

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
		sql = sql .. "WHERE n.destination_number like '%"..destination_number.."' "
		sql = sql .. "AND n.destination_type = 'inbound' "
		sql = sql .. "AND n.destination_enabled = 'true' "
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

--set the call direction as a session variable
	session:setVariable("domain_name", domain_name);
	session:setVariable("domain", domain_name);
	session:setVariable("domain_uuid", domain_uuid);

--send information to the console
	freeswitch.consoleLog("notice", "[app:dialplan:inbound:get_domain] " .. cache .. " source: ".. source .."\n");
