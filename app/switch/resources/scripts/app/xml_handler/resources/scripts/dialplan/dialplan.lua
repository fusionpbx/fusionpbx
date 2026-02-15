--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013-2024 Mark J Crane <markjcrane@fusionpbx.com>
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

--include xml library
	local Xml = require "resources.functions.xml";

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--needed for cli-command xml_locate dialplan
	if (call_context == nil) then
		call_context = "public";
	end

--Kamailio integration: when call arrives from Kamailio (context=public)
--and the network source is localhost, derive tenant domain from the SIP
--request host (R-URI domain). This is multi-tenant safe: each INVITE
--carries the correct tenant domain in the R-URI.
	-- (Kamailio domain override applied below after context_name is set)

--get the dialplan mode from the cache
	dialplan_destination_key = "dialplan:destination";
	dialplan_destination, err = cache.get(dialplan_destination_key);

--if not found in the cache then get it from the database
	if (err == 'NOT FOUND') then
		--get the mode from default settings
		sql = "select default_setting_value from v_default_settings "
		sql = sql .. "where default_setting_category = 'dialplan' ";
		sql = sql .. "and default_setting_subcategory = 'destination' ";
		dialplan_destination = dbh:first_value(sql, nil);
		if (dialplan_destination) then
			local ok, err = cache.set(dialplan_destination_key, dialplan_destination, expire["dialplan"]);
		end

		--set the default
		if (dialplan_destination == nil or dialplan_destination == '') then
			dialplan_destination = "destination_number";
		end

		--send a message to the log
		if (debug['cache']) then
			log.notice(dialplan_destination_key.." source: database destination: "..dialplan_destination);
		end
	else
		--send a message to the log
		if (debug['cache']) then
			log.notice(dialplan_destination_key.." source: cache destination: "..dialplan_destination);
		end
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

		--set the default
		if (dialplan_mode == nil or dialplan_mode == '') then
			dialplan_mode = "multiple";
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

--Kamailio integration: when context is public, derive tenant from R-URI domain.
--Override both call_context and context_name so the correct tenant dialplan
--is queried AND the XML output context matches. Multi-tenant safe.
	if (context_name == "public") then
		local net_ip = params:getHeader("variable_sip_network_ip");
		-- Only override context for calls from Kamailio (localhost/server IP)
		-- NOT for external inbound (e.g. Twilio)
		if (net_ip == "127.0.0.1" or net_ip == "5.161.71.60") then
			local req_host = params:getHeader("variable_sip_req_host");
			local to_host = params:getHeader("variable_sip_to_host");
			local domain = req_host or to_host;
			if (domain ~= nil and domain ~= ""
				and not string.match(domain, "^%d+%.%d+%.%d+%.%d+$")
				and domain ~= "127.0.0.1") then
				call_context = domain;
				context_name = domain;
			end
		end
	end

--use alternative sip_to_user instead of the default
	if (dialplan_destination == '${sip_to_user}' or dialplan_destination == 'sip_to_user') then
		destination_number = api:execute("url_decode", sip_to_user);
	end

--use alternative sip_req_user instead of the default
	if (dialplan_destination == '${sip_req_user}' or dialplan_destination == 'sip_req_user') then
		destination_number = api:execute("url_decode", sip_req_user);
	end

--set the dialplan cache key
	local dialplan_cache_key = "dialplan:" .. call_context;
	if (context_name == 'public' and dialplan_mode == "single") then
		dialplan_cache_key = "dialplan:" .. call_context .. ":" .. destination_number;
	end

--log the dialplan mode and dialplan cache key
	freeswitch.consoleLog("notice", "[xml_handler] ".. dialplan_mode .. " key:" .. dialplan_cache_key .. "\n");

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

		-- set the start time of the query
			local start_time = os.time();

		-- set the timeout value as needed
			local timeout_seconds = 10;

		--get the hostname
			hostname = trim(api:execute("hostname", ""));

		--set the xml array and then concatenate the array to a string
			local xml = Xml:new();
			xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			xml:append([[<document type="freeswitch/xml">]]);
			xml:append([[	<section name="dialplan" description="">]]);
			xml:append([[		<context name="]] .. xml.sanitize(call_context) .. [[" destination_number="]] .. xml.sanitize(destination_number) .. [[" hostname="]] .. xml.sanitize(hostname) .. [[">]]);

		--Kamailio: inject domain_name from sip_req_host at top of context
		--so user_exists and other conditions work even without FS registration
			if (context_name ~= "public") then
				xml:append([[<extension name="kamailio_set_domain" continue="true">]]);
				xml:append([[	<condition field="${domain_name}" expression="^$" break="on-false">]]);
				xml:append([[		<action application="set" data="domain_name=]] .. xml.sanitize(call_context) .. [[" inline="true"/>]]);
				xml:append([[	</condition>]]);
				xml:append([[</extension>]]);
			end

		--get the dialplan xml
			if (context_name == 'public' and dialplan_mode == 'single') then
				--get the single inbound destination dialplan xml  from the database
				sql = "WITH p AS ("
				sql = sql .. "SELECT (SELECT domain_name FROM v_domains WHERE domain_uuid = p.domain_uuid) AS domain_name, ";
				sql = sql .. "(SELECT domain_enabled FROM v_domains WHERE domain_uuid = p.domain_uuid) AS domain_enabled, ";
				sql = sql .. "p.dialplan_xml, ";
				sql = sql .. "p.dialplan_order ";
				sql = sql .. "FROM v_dialplans AS p ";
				sql = sql .. "WHERE ( ";
				sql = sql .. "	p.dialplan_uuid IN ( ";
				sql = sql .. "		SELECT dialplan_uuid FROM v_destinations ";
				sql = sql .. "		WHERE ( ";
				sql = sql .. "			(COALESCE(destination_prefix, '') || COALESCE(destination_area_code, '') || COALESCE(destination_number, '')) = :destination_number ";
				sql = sql .. "			OR (COALESCE(destination_trunk_prefix, '') || COALESCE(destination_area_code, '') || COALESCE(destination_number, '')) = :destination_number ";
				sql = sql .. "			OR (COALESCE(destination_prefix, '') || COALESCE(destination_number, '')) = :destination_number ";
				sql = sql .. "			OR ('+' || COALESCE(destination_prefix, '') || COALESCE(destination_number, '')) = :destination_number ";
				sql = sql .. "			OR ('+' || COALESCE(destination_prefix, '') || COALESCE(destination_area_code, '') || COALESCE(destination_number, '')) = :destination_number ";
				sql = sql .. "			OR (COALESCE(destination_area_code, '') || COALESCE(destination_number, '')) = :destination_number ";
				sql = sql .. "			OR destination_number = :destination_number ";
				sql = sql .. "		) ";
				sql = sql .. "	) ";
				sql = sql .. ") ";
				sql = sql .. "AND (p.hostname = :hostname OR p.hostname IS NULL) ";
				sql = sql .. "AND p.dialplan_enabled = true ";
				sql = sql .. "UNION ";
				sql = sql .. "SELECT ";
				sql = sql .. "		(SELECT domain_name FROM v_domains WHERE domain_uuid = p.domain_uuid) AS domain_name, ";
				sql = sql .. "		(SELECT domain_enabled FROM v_domains WHERE domain_uuid = p.domain_uuid) AS domain_enabled, ";
				sql = sql .. "		p.dialplan_xml, ";
				sql = sql .. "		p.dialplan_order ";
				sql = sql .. "FROM v_dialplans p ";
				sql = sql .. "WHERE ";
				sql = sql .. "		p.dialplan_context LIKE '%public%' ";
				sql = sql .. "		AND p.domain_uuid IS NULL ";
				sql = sql .. "		AND (p.hostname = :hostname OR p.hostname IS NULL) ";
				sql = sql .. "		AND p.dialplan_enabled = true ";
				sql = sql .. ") ";
				sql = sql .. "SELECT domain_name, domain_enabled, dialplan_xml ";
				sql = sql .. "FROM p ";
				sql = sql .. "ORDER BY p.dialplan_order ASC ";
				local params = {destination_number = destination_number, hostname = hostname};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[dialplan] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					dialplan_found = true;
					freeswitch.consoleLog("WARNING", "[INBOUND DEBUG] row found: domain_name=" .. tostring(row.domain_name) .. " domain_enabled=" .. tostring(row.domain_enabled) .. " xml_len=" .. tostring(string.len(row.dialplan_xml or "")) .. "\n");
					if (row.domain_uuid ~= nil) then
						domain_name = row.domain_name;
					else
						xml:append(row.dialplan_xml);
					end
					if (row.domain_enabled == "true") then
						xml:append(row.dialplan_xml);
					end
				end);

				--handle not found
				if (dialplan_found == nil) then
					--check if the sql query timed out
					local current_time = os.time();
					local elapsed_time = current_time - start_time;
					if elapsed_time > timeout_seconds then
						--sql query timed out - unset the xml object to prevent the xml not found
						xml = nil;
					end

					if (xml ~= nil) then
						--sanitize the destination if not numeric
						if (type(destination_number) == "string") then
							destination_number = destination_number:gsub("^%+", "");
							destination_number = tonumber(destination_number);
							if (type(tonumber(destination_number)) ~= "number") then
								destination_number = 'not numeric';
							end
						end
						if (type(destination_number) == "numeric") then
							destination_number = tostring(destination_number);
						end

						--build 404 not found XML
						xml:append([[		<extension name="not-found" continue="false" uuid="9913df49-0757-414b-8cf9-bcae2fd81ae7">]]);
						xml:append([[			<condition field="" expression="">]]);
						xml:append([[				<action application="set" data="call_direction=inbound" inline="true"/>]]);
						xml:append([[				<action application="log" data="WARNING [inbound routes] 404 not found ${sip_network_ip} ]]..destination_number..[[" inline="true"/>]]);
						xml:append([[			</condition>]]);
						xml:append([[		</extension>]]);
					end
				end
			else
				--get the domain diaplan xml from the database
				sql = "select dialplan_xml from v_dialplans as p ";
				if (context_name == "public" or string.match(context_name, "@")) then
					sql = sql .. "where p.dialplan_context = :call_context ";
				else
					sql = sql .. "where p.dialplan_context in (:call_context, '${domain_name}', 'global') ";
				end
				sql = sql .. "and (p.hostname = :hostname or p.hostname is null) ";
				sql = sql .. "and p.dialplan_enabled = true ";
				sql = sql .. "order by p.dialplan_order asc ";
				local params = {call_context = call_context, hostname = hostname};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[dialplan] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					dialplan_found = true;
					xml:append(row.dialplan_xml);
				end);
			end

		--set the xml array and then concatenate the array to a string
			if (dialplan_found ~= nil and dialplan_found) then
				xml:append([[		</context>]]);
				xml:append([[	</section>]]);
				xml:append([[</document>]]);
				XML_STRING = xml:build();
				freeswitch.consoleLog("WARNING", "[DIALPLAN XML OUTPUT] context=" .. tostring(call_context) .. " dest=" .. tostring(destination_number) .. " xml_len=" .. tostring(string.len(XML_STRING or "")) .. "\n");
			end

		--close the database connection
			dbh:release();

		--set the cache
			if (XML_STRING ~= nil) then
				local ok, err = cache.set(dialplan_cache_key, XML_STRING, expire["dialplan"]);
				if debug["cache"] then
					if ok then
						freeswitch.consoleLog("notice", "[xml_handler] " .. dialplan_cache_key .. " stored in the cache\n");
					else
						freeswitch.consoleLog("warning", "[xml_handler] " .. dialplan_cache_key .. " can not be stored in the cache: " .. tostring(err) .. "\n");
					end
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
