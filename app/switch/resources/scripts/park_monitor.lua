--	park_monitor.lua
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

--Description:
	--if the call has been answered
	--then send presence terminate, and delete from the database

--include config.lua
	require "resources.functions.config";

--connect to the database
	--dbh = freeswitch.Dbh("core:core"); -- when using sqlite
	dbh = freeswitch.Dbh("sqlite://"..database_dir.."/park.db");
	--require "resources.functions.database_handle";

--get the argv values
	script_name = argv[0];
	uuid = argv[1];
	domain_name = argv[2];
	park_extension = argv[3];
	park_timeout_type = argv[4];
	park_timeout_seconds = argv[5];
	park_timeout_destination = argv[6];

--prepare the api
	api = freeswitch.API();

--define the trim function
	require "resources.functions.trim";

--monitor the parking lot if the call has hungup send a terminated event, and delete from the db
	x = 0
	while true do
		--sleep a moment to prevent using unecessary resources
			freeswitch.msleep(1000);

		if (api:executeString("uuid_exists "..uuid) == "false") then
			--set the presence to terminated
				event = freeswitch.Event("PRESENCE_IN");
				event:addHeader("proto", "sip");
				event:addHeader("event_type", "presence");
				event:addHeader("alt_event_type", "dialog");
				event:addHeader("Presence-Call-Direction", "outbound");
				event:addHeader("state", "Active (1 waiting)");
				event:addHeader("from", park_extension.."@"..domain_name);
				event:addHeader("login", park_extension.."@"..domain_name);
				event:addHeader("unique-id", uuid);
				event:addHeader("answer-state", "terminated");
				event:fire();

			--set the park status
				cmd = "uuid_setvar "..uuid.." park_status cancelled";
				result = trim(api:executeString(cmd));
				freeswitch.consoleLog("NOTICE", "Park Status: cancelled\n");

			--delete the lot from the database
				dbh:query("DELETE from park WHERE lot = '"..park_extension.."' and domain = '"..domain_name.."' ");

			--end the loop
				break;
		else
			cmd = "uuid_getvar "..uuid.." park_status";
			result = trim(api:executeString(cmd));
			--freeswitch.consoleLog("notice", "" .. result .. "\n");
			if (result == "parked") then --_undef_
				--set presence in
					event = freeswitch.Event("PRESENCE_IN");
					event:addHeader("proto", "sip"); --park
					event:addHeader("login", park_extension.."@"..domain_name);
					event:addHeader("from", park_extension.."@"..domain_name);
					event:addHeader("status", "Active (1 waiting)");
					event:addHeader("rpid", "unknown");
					event:addHeader("event_type", "presence");
					event:addHeader("alt_event_type", "dialog");
					event:addHeader("event_count", "1");
					event:addHeader("unique-id", uuid);
					--event:addHeader("Presence-Call-Direction", "outbound")
					event:addHeader("answer-state", "confirmed");
					event:fire();
			else
				--set the presence to terminated
					event = freeswitch.Event("PRESENCE_IN");
					event:addHeader("proto", "sip");
					event:addHeader("event_type", "presence");
					event:addHeader("alt_event_type", "dialog");
					event:addHeader("Presence-Call-Direction", "outbound");
					event:addHeader("state", "Active (1 waiting)");
					event:addHeader("from", park_extension.."@"..domain_name);
					event:addHeader("login", park_extension.."@"..domain_name);
					event:addHeader("unique-id", uuid);
					event:addHeader("answer-state", "terminated");
					event:fire();

				--delete the lot from the database
					dbh:query("DELETE from park WHERE lot = '"..park_extension.."' and domain = '"..domain_name.."' ");
					--freeswitch.consoleLog("NOTICE", "Affected rows: park ext "..park_extension.." " .. dbh:affected_rows() .. "\n");

				--set the park status
					cmd = "uuid_setvar "..uuid.." park_status unparked";
					result = trim(api:executeString(cmd));
					freeswitch.consoleLog("NOTICE", "Park Status: unparked "..park_extension.."\n");

				--end the loop
					break;
			end
		end

		--limit the monitor to watching 60 seconds
			x = x + 1;
			if (x > tonumber(park_timeout_seconds)) then
				--set the presence to terminated
					event = freeswitch.Event("PRESENCE_IN");
					event:addHeader("proto", "sip");
					event:addHeader("event_type", "presence");
					event:addHeader("alt_event_type", "dialog");
					event:addHeader("Presence-Call-Direction", "outbound");
					event:addHeader("state", "Active (1 waiting)");
					event:addHeader("from", park_extension.."@"..domain_name);
					event:addHeader("login", park_extension.."@"..domain_name);
					event:addHeader("unique-id", uuid);
					event:addHeader("answer-state", "terminated");
					event:fire();

				--delete the lot from the database
					dbh:query("DELETE from park WHERE lot = '"..park_extension.."' and domain = '"..domain_name.."' ");

				--set the park status
					cmd = "uuid_setvar "..uuid.." park_status timeout";
					result = trim(api:executeString(cmd));
					freeswitch.consoleLog("NOTICE", "Park Status: timeout\n");

				--end the loop
					break;
			end
	end

	--if the timeout was reached transfer the call
		--get the park status
			cmd = "uuid_getvar "..uuid.." park_status";
			park_status = trim(api:executeString(cmd));
		--get and set the the caller id prefix
			cmd = "uuid_getvar "..uuid.." park_caller_id_prefix";
			park_caller_id_prefix = trim(api:executeString(cmd));
			if (park_caller_id_prefix) then
				--get the caller id name
					cmd = "uuid_getvar "..uuid.." effective_caller_id_name";
					caller_id_name = trim(api:executeString(cmd));
				--set the caller id name and prefix
					cmd = "uuid_setvar "..uuid.." effective_caller_id_name '"..park_caller_id_prefix.."#"..caller_id_name.."'";
					result = trim(api:executeString(cmd));
			end
		--transfer the call to the timeout destination
			if (park_timeout_type == "transfer" and park_status == "timeout") then
				--set the park status
					cmd = "uuid_setvar "..uuid.." park_status unparked";
					result = trim(api:executeString(cmd));
					freeswitch.consoleLog("NOTICE", "Park Status: unparked\n");
				--transfer the call when it has timed out
					cmd = "uuid_transfer "..uuid.." "..park_timeout_destination;
					result = trim(api:executeString(cmd));
			end
