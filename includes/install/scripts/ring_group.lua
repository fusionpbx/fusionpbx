--	ring_groups.lua
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

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--get the variables
	if (session:ready()) then
		domain_name = session:getVariable("domain_name");
		ring_group_uuid = session:getVariable("ring_group_uuid");
		caller_id_name = session:getVariable("caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");
		recordings_dir = session:getVariable("recordings_dir");
		uuid = session:getVariable("uuid");
	end

--get the extension list
	sql = 
	[[ SELECT g.ring_group_extension_uuid, e.extension_uuid, e.extension, 
	r.ring_group_strategy, r.ring_group_timeout_sec, r.ring_group_timeout_app, g.extension_delay, g.extension_timeout, r.ring_group_timeout_data, r.ring_group_cid_name_prefix, r.ring_group_ringback
	FROM v_ring_groups as r, v_ring_group_extensions as g, v_extensions as e 
	where g.ring_group_uuid = r.ring_group_uuid 
	and g.ring_group_uuid = ']]..ring_group_uuid..[[' 
	and e.extension_uuid = g.extension_uuid 
	and r.ring_group_enabled = 'true' 
	order by g.extension_delay, e.extension asc ]]
	--freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");

	x = 0;
	dbh:query(sql, function(row)
		ring_group_timeout_sec = row.ring_group_timeout_sec;
		ring_group_timeout_app = row.ring_group_timeout_app;
		ring_group_timeout_data = row.ring_group_timeout_data;
		ring_group_cid_name_prefix = row.ring_group_cid_name_prefix;
		ring_group_ringback = row.ring_group_ringback;
		extension_delay = row.extension_delay;
		extension_timeout = row.extension_timeout;

		if (ring_group_ringback == "${uk-ring}") then
			ring_group_ringback = "%(400,200,400,450);%(400,2200,400,450)";
		end
		if (ring_group_ringback == "${us-ring}") then
			ring_group_ringback = "%(2000, 4000, 440.0, 480.0)";
		end
		if (ring_group_ringback == "${fr-ring}") then
			ring_group_ringback = "%(1500, 3500, 440.0, 0.0)";
		end
		if (ring_group_ringback == "${rs-ring}") then
			ring_group_ringback = "%(1000, 4000, 425.0, 0.0)";
		end
		session:setVariable("ringback", ring_group_ringback);
		session:setVariable("transfer_ringback", ring_group_ringback);

		if (string.len(ring_group_cid_name_prefix) > 0) then
			origination_caller_id_name = ring_group_cid_name_prefix .. "#" .. caller_id_name;
		else
			origination_caller_id_name = caller_id_name;
		end

		delimiter = ",";
		if (row.ring_group_strategy == "sequence") then
			delimiter = "|";
		end
		if (row.ring_group_strategy == "simultaneous") then
			delimiter = ",";
		end
		if (row.ring_group_strategy == "enterprise") then
			delimiter = ":_:";
		end

		if (x == 0) then
			app_data = ""; --{originate_timeout="..ring_group_timeout_sec.."}";
			app_data = app_data .. "[sip_invite_domain="..domain_name..",leg_timeout="..extension_timeout..",leg_delay_start="..extension_delay..",origination_caller_id_name="..origination_caller_id_name.."]user/" .. row.extension .. "@" .. domain_name;
		else
			app_data = app_data .. delimiter .. "[sip_invite_domain="..domain_name..",leg_timeout="..extension_timeout..",leg_delay_start="..extension_delay..",origination_caller_id_name="..origination_caller_id_name.."]user/" .. row.extension .. "@" .. domain_name;
		end
		x = x + 1;
	end);

--app_data
	--freeswitch.consoleLog("notice", "[ring group] app_data: " .. app_data .. "\n");

--session actions
	if (session:ready()) then
		session:preAnswer();
		session:execute("set", "hangup_after_bridge=true");
		session:execute("set", "continue_on_fail=true");
		session:execute("bind_meta_app", "1 ab s execute_extension::dx XML features");
		session:execute("bind_meta_app", "2 ab s record_session::"..recordings_dir.."}/archive/"..os.date("%Y").."/"..os.date("%m").."/"..os.date("%d").."}/"..uuid..".wav");
		session:execute("bind_meta_app", "3 ab s execute_extension::cf XML features");
		session:execute("bind_meta_app", "4 ab s execute_extension::att_xfer XML features");
		if (app_data) then
			session:execute("bridge", app_data);
		else
			--get the timeout app and data
				sql = [[SELECT ring_group_timeout_app, ring_group_timeout_data FROM v_ring_groups 
				where ring_group_uuid = ']]..ring_group_uuid..[[' 
				and ring_group_enabled = 'true' ]];
				--freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");
				dbh:query(sql, function(row)
					ring_group_timeout_app = row.ring_group_timeout_app;
					ring_group_timeout_data = row.ring_group_timeout_data;
				end);
		end
		if (session:getVariable("originate_disposition") == "ALLOTTED_TIMEOUT") then
			session:execute(ring_group_timeout_app, ring_group_timeout_data);
		end
	end

--actions
	--ACTIONS = {}
	--table.insert(ACTIONS, {"set", "hangup_after_bridge=true"});
	--table.insert(ACTIONS, {"set", "continue_on_fail=true"});
	--table.insert(ACTIONS, {"bridge", app_data});
	--table.insert(ACTIONS, {ring_group_timeout_app, ring_group_timeout_data});

