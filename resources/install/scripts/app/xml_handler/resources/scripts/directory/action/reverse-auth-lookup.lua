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

--get the action
	action = params:getHeader("action");
	purpose = params:getHeader("purpose");
		--sip_auth - registration
		--group_call - call group has been called
		--user_call - user has been called

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--exits the script if we didn't connect properly
	assert(dbh:connected());

--get the domain_uuid
	if (domain_uuid == nil) then
		--get the domain_uuid
			if (domain_name ~= nil) then
				sql = "SELECT domain_uuid FROM v_domains ";
				sql = sql .. "WHERE domain_name = '" .. domain_name .."' ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
				end
				status = dbh:query(sql, function(rows)
					domain_uuid = rows["domain_uuid"];
				end);
			end
		--if the domain_uuid is null then set an empty string
			if (domain_uuid == nil) then
				domain_uuid = " ";
			end
			
	end

--get the extension information
	sql = "SELECT * FROM v_extensions WHERE domain_uuid = '" .. domain_uuid .. "' and (extension = '" .. user .. "' or number_alias = '" .. user .. "') and enabled = 'true' ";
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
	end
	dbh:query(sql, function(row)
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

--build the xml
	local xml = {}
	--table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[	<section name="directory">]]);
	table.insert(xml, [[		<domain name="]] .. domain_name .. [[" alias="true">]]);
	table.insert(xml, [[			<user id="]] .. extension .. [[">]]);
	table.insert(xml, [[				<params>]]);
	table.insert(xml, [[					<param name="reverse-auth-user" value="]] .. extension .. [["/>]]);
	table.insert(xml, [[					<param name="reverse-auth-pass" value="]] .. password .. [["/>]]);
	table.insert(xml, [[				</params>]]);
	table.insert(xml, [[			</user>]]);
	table.insert(xml, [[		</domain>]]);
	table.insert(xml, [[	</section>]]);
	table.insert(xml, [[</document>]]);
	XML_STRING = table.concat(xml, "\n");

--close the database connection
	dbh:release();

--send the xml to the console
	if (debug["xml_string"]) then
		freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: \n" .. XML_STRING .. "\n");
	end