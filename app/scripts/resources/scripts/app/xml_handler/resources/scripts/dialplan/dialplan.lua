--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013-2020 Mark J Crane <markjcrane@fusionpbx.com>
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

--includes
	local cache = require"resources.functions.cache"
	local log = require"resources.functions.log"["xml_handler"]

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--needed for cli-command xml_locate dialplan
	if (call_context == nil) then
		call_context = "public";
	end

--get the dialplan mode from the cache
	dialplan_mode_key = "dialplan:mode";
	dialplan_mode, err = cache.get(dialplan_mode_key);

--if not found in the cache then get it from the database
	if (err == 'NOT FOUND') then
		--get the mode from default settings
		sql = "select default_setting_value from v_default_settings "
		sql = sql .. "where default_setting_category = 'destinations' ";
		sql = sql .. "and default_setting_subcategory = 'dialplan_mode' ";
		dialplan_mode = dbh:first_value(sql, nil);
		if (dialplan_mode) then
			local ok, err = cache.set(dialplan_mode_key, dialplan_mode, expire["dialplan"]);
		end

		--send a message to the log
		if (debug['cache']) then
			log.notice(dialplan_mode_key.." source: database mode: "..dialplan_mode);
		end
	else
		--send a message to the log
		if (debug['cache']) then
			log.notice(dialplan_mode_key.." source: cache mode: "..dialplan_mode);
		end
	end

--set the defaults
	if (dialplan_mode == nil or dialplan_mode == '') then
		dialplan_mode = "multiple";
	end
	domain_name = 'global';

--get the context
	local context_name = call_context;
	if (call_context == "public" or string.sub(call_context, 0, 7) == "public@" or string.sub(call_context, -7) == ".public") then
		context_name = 'public';
	end
	--freeswitch.consoleLog("notice", "[xml_handler] ".. dialplan_mode .. " key:" .. dialplan_cache_key .. "\n");

--set the dialplan cache key
	local dialplan_cache_key = "dialplan:" .. call_context;
	if (context_name == 'public' and dialplan_mode == "single") then
		dialplan_cache_key = "dialplan:" .. call_context .. ":" .. destination_number;
	end

--get the cache
	XML_STRING, err = cache.get(dialplan_cache_key);
	if (debug['cache']) then
		if XML_STRING then
			log.notice(dialplan_cache_key.." source: cache");
		elseif err ~= 'NOT FOUND' then
			log.notice("get element from the cache: " .. err);
		end
	end

--set the cache
	if (not XML_STRING) then

		--include json library
			local json
			if (debug["sql"]) then
				json = require "resources.functions.lunajson"
			end

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--get the hostname
			hostname = trim(api:execute("switchname", ""));

		--set the xml array and then concatenate the array to a string
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="dialplan" description="">]]);
			table.insert(xml, [[		<context name="]] .. call_context .. [[">]]);

		--get the dialplan xml
			if (context_name == 'public' and dialplan_mode == 'single') then
				sql = "SELECT (select domain_name from v_domains where domain_uuid = p.domain_uuid) as domain_name, dialplan_xml FROM v_dialplans AS p ";
				sql = sql .. "WHERE ( ";
				sql = sql .. "	p.dialplan_uuid IN ( ";
				sql = sql .. "		SELECT dialplan_uuid FROM v_destinations "
				sql = sql .. "		WHERE ( ";
				sql = sql .. "			destination_prefix || destination_area_code || destination_number = :destination_number ";
				sql = sql .. "			OR destination_trunk_prefix || destination_area_code || destination_number = :destination_number ";
				sql = sql .. "			OR destination_prefix || destination_number = :destination_number ";
				sql = sql .. "			OR '+' || destination_prefix || destination_number = :destination_number ";
				sql = sql .. "			OR '+' || destination_prefix || destination_area_code || destination_number = :destination_number ";
				sql = sql .. "			OR destination_area_code || destination_number = :destination_number ";
				sql = sql .. "			OR destination_number = :destination_number ";
				sql = sql .. "		) ";
				sql = sql .. "	) ";
				sql = sql .. "	or (p.dialplan_context like '%public%' and p.domain_uuid IS NULL) ";
				sql = sql .. ") ";
				sql = sql .. "AND (p.hostname = :hostname OR p.hostname IS NULL) ";
				sql = sql .. "AND p.dialplan_enabled = 'true' ";
				sql = sql .. "ORDER BY p.dialplan_order ASC ";
				local params = {destination_number = destination_number, hostname = hostname};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[dialplan] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				local found = false;
				dbh:query(sql, params, function(row)
					if (row.domain_uuid ~= nil) then
						domain_name = row.domain_name;
					end
					table.insert(xml, row.dialplan_xml);
					found = true;
				end);
				if (not found) then
					table.insert(xml, [[		<extension name="not-found" continue="false" uuid="9913df49-0757-414b-8cf9-bcae2fd81ae7">]]);
					table.insert(xml, [[			<condition field="" expression="">]]);
					table.insert(xml, [[				<action application="set" data="call_direction=inbound" inline="true"/>]]);
					table.insert(xml, [[				<action application="log" data="WARNING [inbound routes] 404 not found ${sip_network_ip}" inline="true"/>]]);
					table.insert(xml, [[			</condition>]]);
					table.insert(xml, [[		</extension>]]);
				end
			else
				sql = "select dialplan_xml from v_dialplans as p ";
				if (context_name == "public" or string.match(context_name, "@")) then
					sql = sql .. "where p.dialplan_context = :call_context ";
				else
					sql = sql .. "where p.dialplan_context in (:call_context, '${domain_name}', 'global') ";
				end
				sql = sql .. "and (p.hostname = :hostname or p.hostname is null) ";
				sql = sql .. "and p.dialplan_enabled = 'true' ";
				sql = sql .. "order by p.dialplan_order asc ";
				local params = {call_context = call_context, hostname = hostname};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[dialplan] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					table.insert(xml, row.dialplan_xml);
				end);
			end

		--set the xml array and then concatenate the array to a string
			table.insert(xml, [[		</context>]]);
			table.insert(xml, [[	</section>]]);
			table.insert(xml, [[</document>]]);
			XML_STRING = table.concat(xml, "\n");

		--close the database connection
			dbh:release();

		--set the cache
			local ok, err = cache.set(dialplan_cache_key, XML_STRING, expire["dialplan"]);
			if debug["cache"] then
				if ok then
					freeswitch.consoleLog("notice", "[xml_handler] " .. dialplan_cache_key .. " stored in the cache\n");
				else
					freeswitch.consoleLog("warning", "[xml_handler] " .. dialplan_cache_key .. " can not be stored in the cache: " .. tostring(err) .. "\n");
				end
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. dialplan_cache_key .. " source: database\n");
			end
	else
		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. dialplan_cache_key .. " source: cache\n");
			end
	end --if XML_STRING

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .. "/" .. dialplan_cache_key .. ".xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
