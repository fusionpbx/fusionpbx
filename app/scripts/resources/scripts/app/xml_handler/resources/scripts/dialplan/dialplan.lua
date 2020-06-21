--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013-2018 Mark J Crane <markjcrane@fusionpbx.com>
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

--needed for cli-command xml_locate dialplan
	if (call_context == nil) then
		call_context = "public";
	end

--set the defaults
	if (context_type == nil) then
		context_type = "multiple";
	end
	domain_name = 'global';

--get the context
	local context_name = call_context;
	if (call_context == "public" or string.sub(call_context, 0, 7) == "public@" or string.sub(call_context, -7) == ".public") then
		context_name = 'public';
	end
	--freeswitch.consoleLog("notice", "[xml_handler] ".. context_type .. " key:" .. dialplan_cache_key .. "\n");

--set the dialplan cache key
	local dialplan_cache_key = "dialplan:" .. call_context;
	if (context_name == 'public' and context_type == "single") then
		dialplan_cache_key = "dialplan:" .. call_context .. ":" .. destination_number;
	end

--get the cache
	XML_STRING, err = cache.get(dialplan_cache_key);
	if (debug['cache']) then
		if XML_STRING then
			log.notice(dialplan_cache_key.." source: cache");
		elseif err ~= 'NOT FOUND' then
			log.notice("error get element from cache: " .. err);
		end
	end

--set the cache
	if (not XML_STRING) then

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

		--get the hostname
			hostname = trim(api:execute("switchname", ""));

		--set the xml array and then concatenate the array to a string
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="dialplan" description="">]]);
			table.insert(xml, [[		<context name="]] .. call_context .. [[">]]);

		--get the dialplan xml
			if (context_name == 'public' and context_type == 'single') then
				sql = "select d.domain_name, dialplan_xml from v_dialplans as p, v_domains as d ";
				sql = sql .. "where ( ";
				sql = sql .. "	p.dialplan_uuid in (select dialplan_uuid from v_destinations where (destination_number = :destination_number or destination_prefix || destination_number = :destination_number)) ";
				sql = sql .. "	or (p.dialplan_context like '%public%' and p.domain_uuid is null) ";
				sql = sql .. ") ";
				sql = sql .. "and p.domain_uuid = d.domain_uuid ";
				sql = sql .. "and (p.hostname = :hostname or p.hostname is null) ";
				sql = sql .. "and p.dialplan_enabled = 'true' ";
				sql = sql .. "order by p.dialplan_order asc ";
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
