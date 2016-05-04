--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	  this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	  notice, this list of conditions and the following disclaimer in the
--	  documentation and/or other materials provided with the distribution.
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
	require "resources.functions.config";

--define general settings
	sleep = 300;

--define the run file
	run_file = scripts_dir .. "/resources/run/voicemail-mwi.tmp";

--debug
	debug["sql"] = false;
	debug["info"] = false;

--only run the script a single time
	runonce = true
--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

--used to stop the lua service
	local file = assert(io.open(run_file, "w"));
	file:write("remove this file to stop the script");

--define the trim function
	require "resources.functions.trim";

--check if a file exists
	require "resources.functions.file_exists";

--create the api object
	api = freeswitch.API();

--run lua as a service
	while true do

		--exit the loop when the file does not exist
			if (not file_exists(run_file)) then
				freeswitch.consoleLog("NOTICE", run_file.." not found\n");
				break;
			end

		--Send MWI events for voicemail boxes with messages
			sql = [[SELECT v.voicemail_id, v.voicemail_uuid, v.domain_uuid, d.domain_name, COUNT(*) AS message_count
				FROM v_voicemail_messages as m, v_voicemails as v, v_domains as d
				WHERE v.voicemail_uuid = m.voicemail_uuid
				AND v.domain_uuid = d.domain_uuid
				GROUP BY v.voicemail_id, v.voicemail_uuid, v.domain_uuid, d.domain_name;]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)

				--get saved and new message counts
					sql = [[SELECT count(*) as new_messages FROM v_voicemail_messages
						WHERE domain_uuid = ']] .. row["domain_uuid"] ..[['
						AND voicemail_uuid = ']] .. row["voicemail_uuid"] ..[['
						AND (message_status is null or message_status = '') ]];
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
					status = dbh:query(sql, function(r)
						new_messages = r["new_messages"];
					end);
					sql = [[SELECT count(*) as saved_messages FROM v_voicemail_messages
						WHERE domain_uuid = ']] .. row["domain_uuid"] ..[['
						AND voicemail_uuid = ']] .. row["voicemail_uuid"] ..[['
						AND message_status = 'saved' ]];
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
					status = dbh:query(sql, function(r)
						saved_messages = r["saved_messages"];
					end);

				--send the message waiting event
					local event = freeswitch.Event("message_waiting");
					if (new_messages == "0") then
						event:addHeader("MWI-Messages-Waiting", "no");
					else
						event:addHeader("MWI-Messages-Waiting", "yes");
					end
					event:addHeader("MWI-Message-Account", "sip:"..row["voicemail_id"].."@"..row["domain_name"]);
					event:addHeader("MWI-Voice-Message", new_messages.."/"..saved_messages.." (0/0)");
					event:fire();
				--log to console
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] mailbox: "..row["voicemail_id"].."@"..row["domain_name"].." messages: " .. row["message_count"] .. " \n");
					end
			end);

		if (runonce) then
			freeswitch.consoleLog("notice", "mwi.lua has ended\n");
			break;
		else
			--slow the loop down
			os.execute("sleep "..sleep);
		end

	end
