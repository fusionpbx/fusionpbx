--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013-2015 Mark J Crane <markjcrane@fusionpbx.com>
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

--get the action
	action = params:getHeader("action");
	purpose = params:getHeader("purpose");
		--sip_auth - registration
		--group_call - call group has been called
		--user_call - user has been called

--get logger
	local log = require "resources.functions.log".xml_handler;

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--include xml library
	local Xml = require "resources.functions.xml";

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--exits the script if we didn't connect properly
	assert(dbh:connected());

--get the domain_uuid
	if (domain_uuid == nil) then
		if (domain_name ~= nil) then
			local sql = "SELECT domain_uuid FROM v_domains ";
			sql = sql .. "WHERE domain_name = :domain_name ";
			local params = {domain_name = domain_name}
			if (debug["sql"]) then
				log.noticef("SQL: %s; params %s", sql, json.encode(params));
			end
			dbh:query(sql, params, function(rows)
				domain_uuid = rows["domain_uuid"];
			end);
		end
	end

--get the extension information
	if (domain_uuid ~= nil) then
		local sql = "SELECT * FROM v_extensions WHERE domain_uuid = :domain_uuid "
			.. "and (extension = :user or number_alias = :user) "
			.. "and enabled = 'true' ";
		local params = {domain_uuid=domain_uuid, user=user};
		if (debug["sql"]) then
			log.noticef("SQL: %s; params %s", sql, json.encode(params));
		end
		dbh:query(sql, params, function(row)
			--general
				domain_uuid = row.domain_uuid;
				extension_uuid = row.extension_uuid;
				extension = row.extension;
				cidr = "";
				if (string.len(row.cidr) > 0) then
					cidr = [[ cidr="]] .. row.cidr .. [["]];
				end
				number_alias = "";
				if (string.len(row.number_alias) > 0) then
					number_alias = [[ number-alias="]] .. row.number_alias .. [["]];
				end
			--params
				password = row.password;
		end);
	end

--build the xml
	if (domain_name ~= nil and extension ~= nil and password ~= nil) then
		local xml = Xml:new();
		--xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
		xml:append([[<document type="freeswitch/xml">]]);
		xml:append([[	<section name="directory">]]);
		xml:append([[		<domain name="]] .. xml.sanitize(domain_name) .. [[" alias="true">]]);
		xml:append([[			<user id="]] .. xml.sanitize(extension) .. [["]] .. xml.sanitize(number_alias) .. [[>]]);
		xml:append([[				<params>]]);
		xml:append([[					<param name="reverse-auth-user" value="]] .. xml.sanitize(extension) .. [["/>]]);
		xml:append([[					<param name="reverse-auth-pass" value="]] .. xml.sanitize(password) .. [["/>]]);
		xml:append([[				</params>]]);
		xml:append([[			</user>]]);
		xml:append([[		</domain>]]);
		xml:append([[	</section>]]);
		xml:append([[</document>]]);
		XML_STRING = xml:build();
	end

--close the database connection
	dbh:release();

--send the xml to the console
	if (debug["xml_string"]) then
		freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: \n" .. XML_STRING .. "\n");
	end
