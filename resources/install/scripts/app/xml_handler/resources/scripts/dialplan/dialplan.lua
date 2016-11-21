--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013-2016 Mark J Crane <markjcrane@fusionpbx.com>
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

	local cache = require"resources.functions.cache"
	local log = require"resources.functions.log"["xml_handler"]

--needed for cli-command xml_locate dialplan
	if (call_context == nil) then
		call_context = "public";
	end

--get the cache
	XML_STRING, err = cache.get("dialplan:" .. call_context)

	if debug['cache'] then
		if XML_STRING then
			log.notice("dialplan:"..call_context.." source: memcache");
		elseif err ~= 'NOT FOUND' then
			log.notice("error get element from cache: " .. err);
		end
	end

--set the cache
	if not XML_STRING then

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

		--set the xml array and then concatenate the array to a string
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="dialplan" description="">]]);
			table.insert(xml, [[		<context name="]] .. call_context .. [[">]]);

		--get the dialplan xml
			sql = "select dialplan_xml from v_dialplans as p ";
			if (call_context == "public" or string.sub(call_context, 0, 7) == "public@" or string.sub(call_context, -7) == ".public") then
				sql = sql .. "where p.dialplan_context = :call_context ";
			else
				sql = sql .. "where (p.dialplan_context = :call_context or p.dialplan_context = '${domain_name}') ";
			end
			sql = sql .. "and p.dialplan_enabled = 'true' ";
			sql = sql .. "order by ";
			sql = sql .. "p.dialplan_order asc ";
			local params = {call_context = call_context};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[dialplan] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			local x = 0;
			local pass
			dbh:query(sql, params, function(row)
				table.insert(xml, row.dialplan_xml);
			end);

		--set the xml array and then concatenate the array to a string
			table.insert(xml, [[		</context>]]);
			table.insert(xml, [[	</section>]]);
			table.insert(xml, [[</document>]]);
			XML_STRING = table.concat(xml, "\n");

		--set the cache
			if cache.support() then
				cache.set("dialplan:" .. call_context, XML_STRING, expire["dialplan"])
			end

		--send the xml to the console
			if (debug["xml_string"]) then
				local file = assert(io.open(temp_dir .. "/dialplan-" .. call_context .. ".xml", "w"));
				file:write(XML_STRING);
				file:close();
			end

		--send to the console
			if (debug["cache"]) then
				log.notice("dialplan:"..call_context.." source: database");
			end

		--close the database connection
			dbh:release();
	end
