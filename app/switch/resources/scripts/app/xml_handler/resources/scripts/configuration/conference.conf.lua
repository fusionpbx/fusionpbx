--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2016 Mark J Crane <markjcrane@fusionpbx.com>
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

--include xml library
	local Xml = require "resources.functions.xml";

--set the xml array
	local xml = Xml:new();
	xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	xml:append([[<document type="freeswitch/xml">]]);
	xml:append([[	<section name="configuration">]]);
	xml:append([[		<configuration name="conference.conf" description="Audio Conference">]]);

--start the conference controls
	xml:append([[			<caller-controls>]]);
	sql = [[SELECT * FROM v_conference_controls
		WHERE control_enabled = 'true' ]];
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[conference_control] SQL: " .. sql .. "\n");
	end
	dbh:query(sql, function(field)
		conference_control_uuid = field["conference_control_uuid"];
		xml:append([[				<group name="]] .. xml.sanitize(field["control_name"]) .. [[">]]);

		--get the conference control details from the database
		sql = [[SELECT * FROM v_conference_control_details
			WHERE conference_control_uuid = :conference_control_uuid
			AND control_enabled = 'true' ]];
		local params = {conference_control_uuid = conference_control_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[conference_control] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end

		dbh:query(sql, params, function(row)
			--conference_control_uuid = row["conference_control_uuid"];
			--conference_control_detail_uuid = row["conference_control_detail_uuid"];
			xml:append([[					<control digits="]] .. xml.sanitize(row["control_digits"]) .. [[" action="]] .. xml.sanitize(row["control_action"]) .. [[" data="]] .. xml.sanitize(row["control_data"]) .. [["/>]]);
		end);
		xml:append([[				</group>]]);
	end);
	xml:append([[			</caller-controls>]]);


--start the conference profiles
	xml:append([[			<profiles>]]);
	sql = [[SELECT * FROM v_conference_profiles
		WHERE profile_enabled = 'true' ]];
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[conference_profiles] SQL: " .. sql .. "\n");
	end
	dbh:query(sql, function(field)
		conference_profile_uuid = field["conference_profile_uuid"];
		xml:append([[				<profile name="]] .. xml.sanitize(field["profile_name"]) .. [[">]]);

		--get the conference profile parameters from the database
		sql = [[SELECT * FROM v_conference_profile_params
			WHERE conference_profile_uuid = :conference_profile_uuid
			AND profile_param_enabled = 'true' ]];
		local params = {conference_profile_uuid = conference_profile_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[conference_profiles] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end

		dbh:query(sql, params, function(row)
			--conference_profile_uuid = row["conference_profile_uuid"];
			--conference_profile_param_uuid = row["conference_profile_param_uuid"];
			--profile_param_description = row["profile_param_description"];
			xml:append([[					<param name="]] .. xml.sanitize(row["profile_param_name"]) .. [[" value="]] .. xml.sanitize(row["profile_param_value"]) .. [["/>]]);
		end);
		xml:append([[				</profile>]]);
	end);
	xml:append([[			</profiles>]]);

--set the xml array and then concatenate the array to a string
	xml:append([[		</configuration>]]);
	xml:append([[	</section>]]);
	xml:append([[</document>]]);
	XML_STRING = xml:build();
	if (debug["xml_string"]) then
		freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: " .. XML_STRING .. "\n");
	end

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .."/conference.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
