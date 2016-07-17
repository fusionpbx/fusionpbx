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

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

--exits the script if we didn't connect properly
	assert(dbh:connected());

--set the xml array
	local xml = {}
	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[	<section name="configuration">]]);
	table.insert(xml, [[		<configuration name="conference.conf" description="Audio Conference">]]);
	table.insert(xml, [[			<caller-controls>]]);
	table.insert(xml, [[				<group name="default">]]);
	table.insert(xml, [[					<control action="mute" digits=""/>]]);
	table.insert(xml, [[					<control action="deaf mute" digits=""/>]]);
	table.insert(xml, [[					<control action="energy up" digits="9"/>]]);
	table.insert(xml, [[					<control action="energy equ" digits="8"/>]]);
	table.insert(xml, [[					<control action="energy dn" digits="7"/>]]);
	table.insert(xml, [[					<control action="vol talk up" digits="3"/>]]);
	table.insert(xml, [[					<control action="vol talk zero" digits="2"/>]]);
	table.insert(xml, [[					<control action="vol talk dn" digits="1"/>]]);
	table.insert(xml, [[					<control action="vol listen up" digits="6"/>]]);
	table.insert(xml, [[					<control action="vol listen zero" digits="5"/>]]);
	table.insert(xml, [[					<control action="vol listen dn" digits="4"/>]]);
	table.insert(xml, [[					<control action="hangup" digits=""/>]]);
	table.insert(xml, [[				</group>]]);
	table.insert(xml, [[				<group name="moderator">]]);
	table.insert(xml, [[					<control action="mute" digits=""/>]]);
	table.insert(xml, [[					<control action="deaf mute" digits=""/>]]);
	table.insert(xml, [[					<control action="energy up" digits="9"/>]]);
	table.insert(xml, [[					<control action="energy equ" digits="8"/>]]);
	table.insert(xml, [[					<control action="energy dn" digits="7"/>]]);
	table.insert(xml, [[					<control action="vol talk up" digits="3"/>]]);
	table.insert(xml, [[					<control action="vol talk zero" digits="2"/>]]);
	table.insert(xml, [[					<control action="vol talk dn" digits="1"/>]]);
	table.insert(xml, [[					<control action="vol listen up" digits="6"/>]]);
	table.insert(xml, [[					<control action="vol listen zero" digits="5"/>]]);
	table.insert(xml, [[					<control action="vol listen dn" digits="4"/>]]);
	table.insert(xml, [[					<control action="hangup" digits=""/>]]);
	table.insert(xml, [[					<control action="execute_application" digits="0" data="lua app/conference_center/resources/scripts/mute.lua non_moderator"/>]]);
	table.insert(xml, [[					<control action="execute_application" digits="*" data="lua app/conference_center/resources/scripts/unmute.lua non_moderator"/>]]);
	table.insert(xml, [[				</group>]]);
	table.insert(xml, [[				<group name="page">]]);
	table.insert(xml, [[					<control action="mute" digits="0"/>]]);
	table.insert(xml, [[					<control action="deaf mute" digits=""/>]]);
	table.insert(xml, [[					<control action="energy up" digits="9"/>]]);
	table.insert(xml, [[					<control action="energy equ" digits="8"/>]]);
	table.insert(xml, [[					<control action="energy dn" digits="7"/>]]);
	table.insert(xml, [[					<control action="vol talk up" digits="3"/>]]);
	table.insert(xml, [[					<control action="vol talk zero" digits="2"/>]]);
	table.insert(xml, [[					<control action="vol talk dn" digits="1"/>]]);
	table.insert(xml, [[					<control action="vol listen up" digits="6"/>]]);
	table.insert(xml, [[					<control action="vol listen zero" digits="5"/>]]);
	table.insert(xml, [[					<control action="vol listen dn" digits="4"/>]]);
	table.insert(xml, [[					<control action="hangup" digits=""/>]]);
	table.insert(xml, [[				</group>]]);
	table.insert(xml, [[			</caller-controls>]]);

	--start the conference profiles
		table.insert(xml, [[			<profiles>]]);
		sql = [[SELECT * FROM v_conference_profiles
			WHERE profile_enabled = 'true' ]];
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[conference_profiles] SQL: " .. sql .. "\n");
		end

		status = dbh:query(sql, function(field)
			conference_profile_uuid = field["conference_profile_uuid"];
			table.insert(xml, [[				<profile name="]]..field["profile_name"]..[[">]]);

			--get the conference profile parameters from the database
			sql = [[SELECT * FROM v_conference_profile_params
				WHERE conference_profile_uuid = ']] .. conference_profile_uuid ..[['
				AND profile_param_enabled = 'true' ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[conference_profiles] SQL: " .. sql .. "\n");
			end

			status = dbh:query(sql, function(row)
				--conference_profile_uuid = row["conference_profile_uuid"];
				--conference_profile_param_uuid = row["conference_profile_param_uuid"];
				--profile_param_description = row["profile_param_description"];
				table.insert(xml, [[					<param name="]]..row["profile_param_name"]..[[" value="]]..row["profile_param_value"]..[["/>]]);
			end);
			table.insert(xml, [[				</profile>]]);
		end);

	table.insert(xml, [[			</profiles>]]);

--set the xml array and then concatenate the array to a string
	table.insert(xml, [[		</configuration>]]);
	table.insert(xml, [[	</section>]]);
	table.insert(xml, [[</document>]]);
	XML_STRING = table.concat(xml, "\n");
	if (debug["xml_string"]) then
		freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: " .. XML_STRING .. "\n");
	end

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .."/conference.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
