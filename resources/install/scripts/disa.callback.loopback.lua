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
	debug["sql"] = true;

--include config.lua
	require "resources.functions.config";

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

	api = freeswitch.API();

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

a_dialstring = "{direction=outbound,origination_caller_id_number=*3472,outbound_caller_id_number=*3472,call_timeout=30,context="..context..",domain_name="..context..",domain="..context..",accountcode="..accountcode..",domain_uuid="..domain_uuid.."}loopback/"..aleg_number.."/"..context;
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
		freeswitch.consoleLog("debug", "[disa.callback] session is not yet answered for " .. aleg_number .. "\n");
		freeswitch.msleep(500);
	end
end

if session1:ready() and session1:answered() then
	session1:answer( );
	freeswitch.consoleLog("info", "[disa.callback] calling " .. bleg_number .. "\n");

	t_started2 = os.date('*t');
	b_dialstring = "{context="..context..",domain_name="..context..",domain="..context..",accountcode="..accountcode..",domain_uuid="..domain_uuid.."}loopback/"..bleg_number.."/"..context;
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

