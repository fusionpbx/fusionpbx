--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2010
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>

--debug
	debug["sql"] = false;

--include config.lua
	require "resources.functions.config";

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

	api = freeswitch.API();

--other libs
	require "resources.functions.trim";

aleg_number = argv[1];
bleg_number = argv[2];
context = argv[3];
accountcode = argv[4];
t_started = os.time();

sql = "SELECT domain_uuid FROM v_domains WHERE domain_name='"..context.."'";
if (debug["sql"]) then
	freeswitch.consoleLog("debug", "[disa.callback] "..sql.."\n");
end

status = dbh:query(sql, function(row)
	domain_uuid = row.domain_uuid;
	end);

cmd = "user_exists id ".. aleg_number .." "..context;
a_user_exists = trim(api:executeString(cmd));
freeswitch.consoleLog("notice", "[disa] a_user_exists "..a_user_exists.."\n");

--Lets build correct dialstring
if (a_user_exists == "true") then
	cmd = "user_data ".. aleg_number .."@"..context.." var extension_uuid";
	extension_uuid = trim(api:executeString(cmd));
	a_dialstring = "[origination_caller_id_number=*3472,outbound_caller_id_number=*3472,call_timeout=30,context="..context..",sip_invite_domain="..context..",domain_name="..context..",domain="..context..",accountcode="..accountcode..",domain_uuid="..domain_uuid.."]user/"..aleg_number.."@"..context;
else
	sql = [[select * from v_dialplans as d, v_dialplan_details as s
			where (d.domain_uuid = ']] .. domain_uuid .. [[' or d.domain_uuid is null)
				and d.app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'
				and d.dialplan_enabled = 'true'
				and d.dialplan_uuid = s.dialplan_uuid
				order by
				d.dialplan_order asc,
				d.dialplan_name asc,
				d.dialplan_uuid asc,
				s.dialplan_detail_group asc,
				CASE s.dialplan_detail_tag
				WHEN 'condition' THEN 1
				WHEN 'action' THEN 2
				WHEN 'anti-action' THEN 3
				ELSE 100 END,
				s.dialplan_detail_order asc ]]
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[disa ] sql for dialplans:" .. sql .. "\n");
	end
	dialplans = {};
	x = 1;
	assert(dbh:query(sql, function(row)
		dialplans[x] = row;
		x = x + 1;
		end));

	y = 0;
	previous_dialplan_uuid = '';
	for k, r in pairs(dialplans) do
		if (y > 0) then
			if (previous_dialplan_uuid ~= r.dialplan_uuid) then
				regex_match = false;
				bridge_match = false;
				square = square .. "]";
				y = 0;
			end
		end
		if (r.dialplan_detail_tag == "condition") then
			if (r.dialplan_detail_type == "destination_number") then
				if (api:execute("regex", "m:~"..aleg_number.."~"..r.dialplan_detail_data) == "true") then
					--get the regex result
					destination_result = trim(api:execute("regex", "m:~"..aleg_number.."~"..r.dialplan_detail_data.."~$1"));
					regex_match = true
				end
			end
		end
		if (r.dialplan_detail_tag == "action") then
			if (regex_match) then
				--replace $1
				dialplan_detail_data = r.dialplan_detail_data:gsub("$1", destination_result);
				--if the session is set then process the actions
				if (y == 0) then
					square = "[direction=outbound,origination_caller_id_number="..bleg_number..",outbound_caller_id_number="..bleg_number..",call_timeout=30,context="..context..",sip_invite_domain="..context..",domain_name="..context..",domain="..context..",accountcode="..accountcode..",domain_uuid="..domain_uuid..",";
				end
				if (r.dialplan_detail_type == "set") then
					if (dialplan_detail_data == "sip_h_X-accountcode=${accountcode}") then
						square = square .. "sip_h_X-accountcode="..accountcode..",";
					elseif (dialplan_detail_data == "effective_caller_id_name=${outbound_caller_id_name}") then
					elseif (dialplan_detail_data == "effective_caller_id_number=${outbound_caller_id_number}") then
					else
						square = square .. dialplan_detail_data..",";
					end
				elseif (r.dialplan_detail_type == "bridge") then
					if (bridge_match) then
						dial_string = dial_string .. "," .. square .."]"..dialplan_detail_data;
						 square = "[";
					else
						dial_string = square .."]"..dialplan_detail_data;
					end
					bridge_match = true;
				end
			y = y + 1;
			end
		end
		previous_dialplan_uuid = r.dialplan_uuid;
	end
	--end for
	a_dialstring = dial_string;
end

freeswitch.consoleLog("info", "[disa.callback] a_dialstring " .. a_dialstring .. "\n");

session1 = freeswitch.Session(a_dialstring);
session1:execute("export", "domain_uuid="..domain_uuid);
freeswitch.consoleLog("info", "[disa.callback] calling " .. aleg_number .. "\n");
freeswitch.msleep(2000);

while (session1:ready() and not session1:answered()) do
	if os.time() > t_started + 30 then
		freeswitch.consoleLog("info", "[disa.callback] timed out for " .. aleg_number .. "\n");
		session1:hangup();
	else
		freeswitch.consoleLog("info", "[disa.callback] session is not yet answered for " .. aleg_number .. "\n");
		freeswitch.msleep(500);
	end
end

if session1:ready() and session1:answered() then
	session1:answer( );
	freeswitch.consoleLog("info", "[disa.callback] calling " .. bleg_number .. "\n");

	t_started2 = os.date();

	cmd = "user_exists id ".. bleg_number .." "..context;
	b_user_exists = trim(api:executeString(cmd));
	freeswitch.consoleLog("notice", "[disa] b_user_exists "..b_user_exists.."\n");

	--Lets build correct dialstring
	if (b_user_exists == "true") then
		cmd = "user_data ".. bleg_number .."@"..context.." var extension_uuid";
		extension_uuid = trim(api:executeString(cmd));
		b_dialstring = "[origination_caller_id_number=*3472,outbound_caller_id_number=*3472,call_timeout=30,context="..context..",sip_invite_domain="..context..",domain_name="..context..",domain="..context..",accountcode="..accountcode..",domain_uuid="..domain_uuid.."]user/"..bleg_number.."@"..context;
	else
		sql = [[select * from v_dialplans as d, v_dialplan_details as s
			where (d.domain_uuid = ']] .. domain_uuid .. [[' or d.domain_uuid is null)
				and d.app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'
				and d.dialplan_enabled = 'true'
				and d.dialplan_uuid = s.dialplan_uuid
				order by
				d.dialplan_order asc,
				d.dialplan_name asc,
				d.dialplan_uuid asc,
				s.dialplan_detail_group asc,
				CASE s.dialplan_detail_tag
				WHEN 'condition' THEN 1
				WHEN 'action' THEN 2
				WHEN 'anti-action' THEN 3
				ELSE 100 END,
				s.dialplan_detail_order asc ]]
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[disa ] sql for dialplans:" .. sql .. "\n");
		end
		dialplans = {};
		x = 1;
		assert(dbh:query(sql, function(row)
			dialplans[x] = row;
			x = x + 1;
			end));
		y = 0;
		previous_dialplan_uuid = '';
		for k, r in pairs(dialplans) do
			if (y > 0) then
				if (previous_dialplan_uuid ~= r.dialplan_uuid) then
					regex_match = false;
					bridge_match = false;
					square = square .. "]";
					y = 0;
				end
			end
			if (r.dialplan_detail_tag == "condition") then
				if (r.dialplan_detail_type == "destination_number") then
					if (api:execute("regex", "m:~"..bleg_number.."~"..r.dialplan_detail_data) == "true") then
						--get the regex result
						destination_result = trim(api:execute("regex", "m:~"..bleg_number.."~"..r.dialplan_detail_data.."~$1"));
						regex_match = true
					end
				end
			end
			if (r.dialplan_detail_tag == "action") then
				if (regex_match) then
					--replace $1
					dialplan_detail_data = r.dialplan_detail_data:gsub("$1", destination_result);
					--if the session is set then process the actions
					if (y == 0) then
						square = "[direction=outbound,origination_caller_id_number="..aleg_number..",outbound_caller_id_number="..aleg_number..",call_timeout=30,context="..context..",sip_invite_domain="..context..",domain_name="..context..",domain="..context..",accountcode="..accountcode..",domain_uuid="..domain_uuid..",";
					end
					if (r.dialplan_detail_type == "set") then
						if (dialplan_detail_data == "sip_h_X-accountcode=${accountcode}") then
							square = square .. "sip_h_X-accountcode="..accountcode..",";
						elseif (dialplan_detail_data == "effective_caller_id_name=${outbound_caller_id_name}") then
						elseif (dialplan_detail_data == "effective_caller_id_number=${outbound_caller_id_number}") then
						else
							square = square .. dialplan_detail_data..",";
						end
					elseif (r.dialplan_detail_type == "bridge") then
						if (bridge_match) then
							dial_string = dial_string .. "," .. square .."]"..dialplan_detail_data;
							 square = "[";
						else
							dial_string = square .."]"..dialplan_detail_data;
						end
						bridge_match = true;
					end
				y = y + 1;
				end
			end
			previous_dialplan_uuid = r.dialplan_uuid;
		end
		--end for
		b_dialstring = dial_string;
	end
	freeswitch.consoleLog("info", "[disa.callback] b_dialstring " .. b_dialstring .. "\n");

	session2 = freeswitch.Session(b_dialstring);
	while (session2:ready() and not session2:answered()) do
		if os.time() > t_started2 + 30 then
			freeswitch.consoleLog("info", "[disa.callback] timed out for " .. bleg_number .. "\n");
			session2:hangup();
		else
			freeswitch.consoleLog("debug", "[disa.callback] session is not yet answered for " .. bleg_number .. "\n");
			freeswitch.msleep(500);
		end
	end
	freeswitch.bridge(session1, session2);
else
	freeswitch.consoleLog("info", "[disa.callback] session is not functional for " .. aleg_number .. "\n");
	session1:hangup();
end

