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
	run_file = scripts_dir .. "/run/voicemail-mwi.tmp";

--debug
	debug["sql"] = false;
	debug["info"] = false;

--only run the script a single time
	runonce = false;

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--used to stop the lua service
	local file = assert(io.open(run_file, "w"));
	file:write("remove this file to stop the script");

--define the trim function
	require "resources.functions.trim";

--check if a file exists
	require "resources.functions.file_exists";

--send MWI NOTIFY message
	require "app.voicemail.resources.functions.mwi_notify";

--get message count for mailbox
	require "app.voicemail.resources.functions.message_count";

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
			local sql = [[SELECT v.voicemail_id, v.voicemail_uuid, v.domain_uuid, d.domain_name, COUNT(*) AS message_count
				FROM v_voicemail_messages as m, v_voicemails as v, v_domains as d
				WHERE v.voicemail_uuid = m.voicemail_uuid
				AND v.domain_uuid = d.domain_uuid
				AND v.voicemail_enabled = 'true'
				GROUP BY v.voicemail_id, v.voicemail_uuid, v.domain_uuid, d.domain_name;]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			dbh:query(sql, function(row)

				--get saved and new message counts
					local new_messages, saved_messages = message_count_by_uuid(
						row["voicemail_uuid"], row["domain_uuid"]
					)

				--send the message waiting event
					local account = row["voicemail_id"].."@"..row["domain_name"]
					mwi_notify(account, new_messages, saved_messages)

				--log to console
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] mailbox: "..account.." messages: " .. (new_messages or "0") .. "/" .. (saved_messages or "0") .. " \n");
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
