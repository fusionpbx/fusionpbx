--	call_flow_monitor.lua
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

--set the time between loops in seconds
	sleep = 300;

--set the debug level
	debug["log"] = false;
	debug["sql"] = false;

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--general functions
	dofile(scripts_dir.."/resources/functions/file_exists.lua");
	dofile(scripts_dir.."/resources/functions/mkdir.lua");

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--make sure the scripts/run dir exists
	mkdir(scripts_dir .. "/run");

--define the run file
	run_file = scripts_dir .. "/run/call_flow_monitor.tmp";

--define the functions
	--shell return results
	function shell(c)
		local o, h
		h = assert(io.popen(c,"r"))
		o = h:read("*all")
		h:close()
		return o
	end

--used to stop the lua service
	local file = assert(io.open(run_file, "w"));
	file:write("remove this file to stop the script");

--monitor the call flows status
	x = 0
	while true do
		--get the extension list
			sql = [[select d.domain_name, f.call_flow_uuid, f.call_flow_extension, f.call_flow_feature_code, f.call_flow_status, f.call_flow_label, f.call_flow_anti_label
			from v_call_flows as f, v_domains as d 
			where f.domain_uuid = d.domain_uuid]]
			--and call_flow_enabled = 'true'
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");
			end
			x = 0;
			dbh:query(sql, function(row)
				domain_name = row.domain_name;
				call_flow_uuid = row.call_flow_uuid;
				--call_flow_name = row.call_flow_name;
				call_flow_extension = row.call_flow_extension;
				call_flow_feature_code = row.call_flow_feature_code;
				--call_flow_context = row.call_flow_context;
				call_flow_status = row.call_flow_status;
				--pin_number = row.call_flow_pin_number;
				call_flow_label = row.call_flow_label;
				call_flow_anti_label = row.call_flow_anti_label;

				if (call_flow_status == "true") then
					--set the presence to terminated - turn the lamp off:
						event = freeswitch.Event("PRESENCE_IN");
						event:addHeader("proto", "sip");
						event:addHeader("event_type", "presence");
						event:addHeader("alt_event_type", "dialog");
						event:addHeader("Presence-Call-Direction", "outbound");
						event:addHeader("state", "Active (1 waiting)");
						event:addHeader("from", call_flow_feature_code.."@"..domain_name);
						event:addHeader("login", call_flow_feature_code.."@"..domain_name);
						event:addHeader("unique-id", call_flow_uuid);
						event:addHeader("answer-state", "terminated");
						event:fire();
					--show in the console
						if (debug["log"]) then
							freeswitch.consoleLog("notice", "Call Flow: label="..call_flow_label..",status=true,uuid="..call_flow_uuid.."\n");
						end
				else
					--set presence in - turn lamp on
						event = freeswitch.Event("PRESENCE_IN");
						event:addHeader("proto", "sip");
						event:addHeader("login", call_flow_feature_code.."@"..domain_name);
						event:addHeader("from", call_flow_feature_code.."@"..domain_name);
						event:addHeader("status", "Active (1 waiting)");
						event:addHeader("rpid", "unknown");
						event:addHeader("event_type", "presence");
						event:addHeader("alt_event_type", "dialog");
						event:addHeader("event_count", "1");
						event:addHeader("unique-id", call_flow_uuid);
						event:addHeader("Presence-Call-Direction", "outbound");
						event:addHeader("answer-state", "confirmed");
						event:fire();
					--show in the console
						if (debug["log"]) then
							freeswitch.consoleLog("notice", "Call Flow: label="..call_flow_anti_label..",status=false,uuid="..call_flow_uuid.."\n");
						end
				end
			end);

		--exit the loop when the file does not exist
			if (not file_exists(run_file)) then
				break;
			end

		--sleep a moment to prevent using unecessary resources
			freeswitch.msleep(sleep*1000);
	end