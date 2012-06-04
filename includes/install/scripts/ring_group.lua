--	intercom.lua
--	Part of FusionPBX
--	Copyright (C) 2010 Mark J Crane <markjcrane@fusionpbx.com>
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

--include the lua script
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	include = assert(loadfile(scripts_dir .. "/resources/config.lua"));
	include();

--connect to the database
	--ODBC - data source name
		if (dsn_name) then
			dbh = freeswitch.Dbh(dsn_name,dsn_username,dsn_password);
		end
	--FreeSWITCH core db handler
		if (db_type == "sqlite") then
			dbh = freeswitch.Dbh("core:"..db_path.."/"..db_name);
		end

--get the variables
	domain_name = session:getVariable("domain_name");
	ring_group_uuid = session:getVariable("ring_group_uuid");

--get the extension list
	sql = 
	[[ SELECT g.ring_group_extension_uuid, e.extension_uuid, e.extension, 
	r.ring_group_strategy, r.ring_group_timeout_sec, r.ring_group_timeout_app, r.ring_group_timeout_data
	FROM v_ring_groups as r, v_ring_group_extensions as g, v_extensions as e 
	where g.ring_group_uuid = r.ring_group_uuid 
	and g.ring_group_uuid = ']]..ring_group_uuid..[[' 
	and e.extension_uuid = g.extension_uuid 
	and r.ring_group_enabled = 'true' 
	order by e.extension asc ]]
	--freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");
	app_data = "";

	x = 0;
	dbh:query(sql, function(row)
		ring_group_timeout_sec = row.ring_group_timeout_sec;
		ring_group_timeout_app = row.ring_group_timeout_app;
		ring_group_timeout_data = row.ring_group_timeout_data;
		if (row.ring_group_strategy == "sequence") then
			delimiter = "|";
		end
		if (row.ring_group_strategy == "simultaneous") then
			delimiter = ",";
		end
		if (x == 0) then
			app_data = "[leg_timeout="..ring_group_timeout_sec.."]user/" .. row.extension .. "@" .. domain_name;
		else
			app_data = app_data .. delimiter .. "[leg_timeout="..ring_group_timeout_sec.."]user/" .. row.extension .. "@" .. domain_name;
		end
		x = x + 1;
	end);

--close the database connection
	dbh:release();

--app_data
	--freeswitch.consoleLog("notice", "Debug:\n" .. app_data .. "\n");

--session actions
	if (session:ready()) then
		session:answer();
		session:execute("set", "hangup_after_bridge=true");
		session:execute("set", "continue_on_fail=true");
		session:execute("bridge", app_data);
		session:execute(ring_group_timeout_app, ring_group_timeout_data);
	end

--actions
	--ACTIONS = {}
	--table.insert(ACTIONS, {"set", "hangup_after_bridge=true"});
	--table.insert(ACTIONS, {"set", "continue_on_fail=true"});
	--table.insert(ACTIONS, {"bridge", app_data});
	--table.insert(ACTIONS, {ring_group_timeout_app, ring_group_timeout_data});
