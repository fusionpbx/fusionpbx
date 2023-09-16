--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2023 Mark J Crane <markjcrane@fusionpbx.com>
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
--	THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.


--include xml library
	local Xml = require "resources.functions.xml";

--get the cache
	local cache = require "resources.functions.cache"
	local event_socket_cache_key = "configuration:event_socket.conf"
	XML_STRING, err = cache.get(event_socket_cache_key)

--set the cache
	if not XML_STRING then

		--log cache error
			if (debug["cache"]) then
				freeswitch.consoleLog("warning", "[xml_handler] configuration:event_socket.conf can not get from the cache: " .. tostring(err) .. "\n");
			end

		--set a default value
			if (expire["acl"] == nil) then
				expire["acl"]= "3600";
			end

		--connect to the database
			local Database = require "resources.functions.database";
			dbh = Database.new('system');

		--include json library
			local json
			if (debug["sql"]) then
				json = require "resources.functions.lunajson"
			end

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--start the xml array
			local xml = Xml:new();
			xml:append([[<configuration name="event_socket.conf" description="Socket Client">]]);
			xml:append([[	<settings>]]);

		--run the query
			sql = "select default_setting_subcategory as name, default_setting_value as value from v_default_settings ";
			sql = sql .. "where default_setting_category = 'switch' ";
			sql = sql .. "and default_setting_subcategory like 'event_socket_%' ";
			sql = sql .. "and default_setting_enabled = 'true' ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			x = 0;
			dbh:query(sql, function(row)
				if (row.name == 'event_socket_nat_map') then 
					xml:append([[		<param name="nat-map" value="]] .. xml.sanitize(row.value) .. [[" />]]);
				end
				if (row.name == 'event_socket_ip_address') then 
					xml:append([[		<param name="listen-ip" value="]] .. xml.sanitize(row.value) .. [["/>]]);
				end
				if (row.name == 'event_socket_port') then 
					xml:append([[		<param name="listen-port" value="]] .. xml.sanitize(row.value) .. [["/>]]);
				end
				if (row.name == 'event_socket_password') then 
					xml:append([[		<param name="password" value="]] .. xml.sanitize(row.value) .. [["/>]]);
				end
				if (row.name == 'event_socket_acl') then 
					xml:append([[		<param name="apply-inbound-acl" value="]] .. xml.sanitize(row.value) .. [["/>]]);
				end
			end)

		--close the extension tag if it was left open
			xml:append([[	</settings>]]);
			xml:append([[</configuration>]]);
			XML_STRING = xml:build();
		--	if (debug["xml_string"]) then
		--		freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: " .. XML_STRING .. "\n");
		--	end

		--close the database connection
			dbh:release();

		--set the cache
			local ok, err = cache.set(event_socket_cache_key, XML_STRING, expire["acl"]);
			if debug["cache"] then
				if ok then
					freeswitch.consoleLog("notice", "[xml_handler] " .. event_socket_cache_key .. " stored in the cache\n");
				else
					freeswitch.consoleLog("warning", "[xml_handler] " .. event_socket_cache_key .. " can not be stored in the cache: " .. tostring(err) .. "\n");
				end
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. event_socket_cache_key .. " source: database\n");
			end
	else
		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. event_socket_cache_key .. " source: cache\n");
			end
	end --if XML_STRING

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .. "/event_socket.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
