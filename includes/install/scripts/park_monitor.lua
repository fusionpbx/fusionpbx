--park_monitor.lua
	--Date: 4 Oct. 2011
	--Description: 
		--if the call has been answered
		--then send presence terminate, and delete from the database

--connect to the database
	--ODBC - data source name
		--local dbh = freeswitch.Dbh("name","user","pass");
	--FreeSWITCH core db
		local dbh = freeswitch.Dbh("core:park");

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

--add a trim function
	function trim (s)
		return (string.gsub(s, "^%s*(.-)%s*$", "%1"))
	end

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
		cmd = "uuid_getvar "..uuid.." park_status";
		park_status = trim(api:executeString(cmd));
		if (park_timeout_type == "transfer" and park_status == "timeout") then
			--set the park status
				cmd = "uuid_setvar "..uuid.." park_status unparked";
				result = trim(api:executeString(cmd));
				freeswitch.consoleLog("NOTICE", "Park Status: unparked\n");
			--transfer the call when it has timed out
				cmd = "uuid_transfer "..uuid.." "..park_timeout_destination;
				result = trim(api:executeString(cmd));
		end
