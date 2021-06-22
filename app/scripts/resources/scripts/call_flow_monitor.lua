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
	sleep = 60;

--set the debug level
	debug["log"] = false;
	debug["sql"] = false;

--include config.lua
	require "resources.functions.config";

--general functions
	require "resources.functions.file_exists";
	require "resources.functions.mkdir";

--initialize the objects
	local Database = require "resources.functions.database";
	local log = require "resources.functions.log".call_flow_monitor
	local presence_in = require "resources.functions.presence_in"

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
	file:close()

	log.notice("Start")

--monitor the call flows status
	local sql = "select d.domain_name, f.call_flow_uuid, f.call_flow_extension, f.call_flow_feature_code," ..
		"f.call_flow_status, f.call_flow_label, f.call_flow_alternate_label "..
		"from v_call_flows as f, v_domains as d " ..
		"where f.domain_uuid = d.domain_uuid " -- and call_flow_enabled = 'true'
	while true do
		-- debug print
			if (debug["sql"]) then
				log.notice("SQL:" .. sql);
			end

		--connect to the database
			local dbh = Database.new('system');

		--get the extension list
			if dbh:connected() then
				dbh:query(sql, function(row)
					local domain_name = row.domain_name;
					local call_flow_uuid = row.call_flow_uuid;
					--local call_flow_name = row.call_flow_name;
					--local call_flow_extension = row.call_flow_extension;
					local call_flow_feature_code = row.call_flow_feature_code;
					--local call_flow_context = row.call_flow_context;
					local call_flow_status = row.call_flow_status;
					--local pin_number = row.call_flow_pin_number;
					local call_flow_label = row.call_flow_label;
					local call_flow_alternate_label = row.call_flow_alternate_label;

					-- turn the lamp
						presence_in.turn_lamp( call_flow_status == "false",
							'flow+'..call_flow_feature_code.."@"..domain_name,
							call_flow_uuid
						);

					if (debug["log"]) then
						local label = (call_flow_status == "true") and call_flow_label or call_flow_alternate_label
						log.noticef("label=%s,status=%s,uuid=%s", label, call_flow_status, call_flow_uuid);
					end
				end);
			end

		-- release dbh
			dbh:release()

		--exit the loop when the file does not exist
			if (not file_exists(run_file)) then
				break;
			end

		--sleep a moment to prevent using unecessary resources
			freeswitch.msleep(sleep*1000);
	end

	log.notice("Stop")
