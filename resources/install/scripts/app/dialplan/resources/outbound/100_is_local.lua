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
	expire["is_local"] = "3600";

--get the variables
	domain_name = session:getVariable("domain_name");
	destination_number = session:getVariable("destination_number");

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--get the cache
	cache = trim(api:execute("memcache", "get app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name));

--get the ring group destinations
	if (cache == "-ERR NOT FOUND") then
		sql = "SELECT destination_number, destination_context "
		sql = sql .. "FROM v_destinations "
		sql = sql .. "WHERE destination_number like '%"..destination_number.."' "
		sql = sql .. "AND destination_type = 'inbound' "
		sql = sql .. "AND destination_enabled = 'true' "
		--freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");
		assert(dbh:query(sql, function(row)
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
			dofile(scripts_dir.."/resources/functions/explode.lua");

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
			freeswitch.consoleLog("notice", "[app:dialplan:outbound:is_local] " .. cache .. " source: memcache\n");

		--transfer the call
			session:transfer(var["destination_number"], "XML", var["destination_context"]);
	end