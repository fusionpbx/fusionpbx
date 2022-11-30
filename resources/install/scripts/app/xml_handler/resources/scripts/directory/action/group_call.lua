--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
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

--get the cache
	if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
		XML_STRING = trim(api:execute("memcache", "get directory:groups:"..domain_name));
	else
		XML_STRING = "-ERR NOT FOUND";
	end

--set the cache
	if (XML_STRING == "-ERR NOT FOUND") then
		--connect to the database
			local Database = require "resources.functions.database";
			local dbh = Database.new('system');

		--include json library
			local json
			if (debug["sql"]) then
				json = require "resources.functions.lunajson"
			end

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--get the domain_uuid
			if (domain_uuid == nil) then
				--get the domain_uuid
					if (domain_name ~= nil) then
						local sql = "SELECT domain_uuid FROM v_domains ";
						sql = sql .. "WHERE domain_name = :domain_name ";
						local params = {domain_name = domain_name};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params, function(rows)
							domain_uuid = rows["domain_uuid"];
						end);
					end
			end

			if not domain_uuid then
				freeswitch.consoleLog("warning", "[xml_handler] Can not find domain name: " .. tostring(domain_name) .. "\n");
				return
			end

		--build the call group array
			local sql = [[
			select * from v_extensions
			where domain_uuid = :domain_uuid
			order by call_group asc
			]];
			local params = {domain_uuid = domain_uuid};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
			end
			call_group_array = {};
			dbh:query(sql, params, function(row)
				call_group = row['call_group'];
				--call_group = str_replace(";", ",", call_group);
				tmp_array = explode(",", call_group);
				for key,value in pairs(tmp_array) do
					value = trim(value);
					--freeswitch.consoleLog("notice", "[directory] Key: " .. key .. " Value: " .. value .. " " ..row['extension'] .."\n");
					if (string.len(value) == 0) then
						--do nothing
					else
						if (call_group_array[value] == nil) then
							call_group_array[value] = row['extension'];
						else
							call_group_array[value] = call_group_array[value]..','..row['extension'];
						end
					end
				end
			end);
			--for key,value in pairs(call_group_array) do
			--	freeswitch.consoleLog("notice", "[directory] Key: " .. key .. " Value: " .. value .. "\n");
			--end

		--build the xml array
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="directory">]]);
			table.insert(xml, [[		<domain name="]] .. domain_name .. [[">]]);
			table.insert(xml, [[		<groups>]]);
			previous_call_group = "";
			for key, value in pairs(call_group_array) do
				call_group = trim(key);
				extension_list = trim(value);
				if (string.len(call_group) > 0) then
					freeswitch.consoleLog("notice", "[directory] call_group: " .. call_group .. "\n");
					freeswitch.consoleLog("notice", "[directory] extension_list: " .. extension_list .. "\n");
					if (previous_call_group ~= call_group) then
						table.insert(xml, [[			<group name="]]..call_group..[[">]]);
						table.insert(xml, [[				<users>]]);
						extension_array = explode(",", extension_list);
						for index,tmp_extension in pairs(extension_array) do
								table.insert(xml, [[					<user id="]]..tmp_extension..[[" type="pointer"/>]]);
						end
						table.insert(xml, [[				</users>]]);
						table.insert(xml, [[			</group>]]);
					end
					previous_call_group = call_group;
				end
			end
			table.insert(xml, [[		</groups>]]);
			table.insert(xml, [[		</domain>]]);
			table.insert(xml, [[	</section>]]);
			table.insert(xml, [[</document>]]);
			XML_STRING = table.concat(xml, "\n");

		--close the database connection
			dbh:release();

		--set the cache
			result = trim(api:execute("memcache", "set directory:groups:"..domain_name.." '"..XML_STRING:gsub("'", "&#39;").."' "..expire["directory"]));

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] directory:groups:"..domain_name.." source: database\n");
			end

	else
		--replace the &#39 back to a single quote
			XML_STRING = XML_STRING:gsub("&#39;", "'");

		--send to the console
			if (debug["cache"]) then
				if (XML_STRING) then
					freeswitch.consoleLog("notice", "[xml_handler] directory:groups:"..domain_name.." source: memcache\n");
				end
			end
	end

--send the xml to the console
	if (debug["xml_string"]) then
		freeswitch.consoleLog("notice", "[xml_handler] directory:groups:"..domain_name.." XML_STRING: \n" .. XML_STRING .. "\n");
	end
