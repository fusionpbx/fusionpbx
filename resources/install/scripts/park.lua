--example usage
	--basic
		--condition		destination_number	5900
		--action	set		park_extension=5901
	--advanced
		--condition		destination_number	^59(\d{2})$
		--action	set		park_extension=$1
	--additional settings
		--action	set		park_range=5
		--action	set		park_direction=in (in/out/both)
		--action	set		park_announce=true (not implemented yet)
		--action	set		park_timeout_seconds=30 (not implemented yet)
		--action	set		park_timeout_extension=1001 (not implemented yet)
		--action	set		park_music=$${hold_music}
		--action	lua		park.lua

--include config.lua
	require "resources.functions.config";

--connect to the database
	--dbh = freeswitch.Dbh("core:core"); -- when using sqlite
	dbh = freeswitch.Dbh("sqlite://"..database_dir.."/park.db");
	--require "resources.functions.database_handle";
	--dbh = database_handle('system');

--exits the script if we didn't connect properly
	assert(dbh:connected());
--get the session variables
	sounds_dir = session:getVariable("sounds_dir");
	park_direction = session:getVariable("park_direction");
	uuid = session:getVariable("uuid");
	domain_name = session:getVariable("domain_name");
	park_extension = session:getVariable("park_extension");
	park_range = session:getVariable("park_range");
	park_announce = session:getVariable("park_announce");
	park_timeout_type = session:getVariable("park_timeout_type");
	park_timeout_destination = session:getVariable("park_timeout_destination");
	park_timeout_seconds = session:getVariable("park_timeout_seconds");
	park_music = session:getVariable("park_music");

--define the trim function
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

--if park_timeout_seconds is not defined set the timeout to 5 minutes
	if (not park_timeout_seconds) then
		park_timeout_seconds = 300;
	end

--if park_timeout_type is not defined set to transfer
	if (not park_timeout_type) then
		park_timeout_type = "transfer";
	end

--prepare the api
	api = freeswitch.API();

--answer the call
	session:answer();

--database
	--exits the script if we didn't connect properly
		assert(dbh:connected());

	--create the table if it doesn't exist
		--pgsql
			dbh:test_reactive("SELECT * FROM park",	"",	"CREATE TABLE park (id SERIAL, lot TEXT, domain TEXT, uuid TEXT, CONSTRAINT park_pk PRIMARY KEY(id))");
		--sqlite
			dbh:test_reactive("SELECT * FROM park",	"",	"CREATE TABLE park (id INTEGER PRIMARY KEY, lot TEXT, domain TEXT, uuid TEXT)");
		--mysql
			dbh:test_reactive("SELECT * FROM park",	"",	"CREATE TABLE park (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, lot TEXT, domain TEXT, uuid TEXT)");

	--if park_range is defined then loop through the range to find an available parking lot
		if (park_range) then
			park_extension_start = park_extension;
			park_extension_end = ((park_extension+park_range)-1);
			extension = park_extension_start;
			while true do
				--exit the loop at the end of the range
					if (tonumber(extension) > park_extension_end) then
						break;
					end
				--check the database for an available slot
					lot_status = "available";
					sql = "SELECT count(*) as count FROM park WHERE lot = '"..extension.."' and domain = '"..domain_name.."' ";
					dbh:query(sql, function(result)
						--for key, val in pairs(result) do
						--	freeswitch.consoleLog("NOTICE", "parking result "..key.." "..val.."\n");
						--end
						count = result.count;
					end);
				--if count is 0 then the parking lot is available end the loop
					if (count == "0") then
						lot_status = "available";
						park_extension = ""..extension;
						break;
					end
				--increment the value
					extension = extension + 1;
			end
		end

	--check the database to see if the slot is available or unavailable
		lot_status = "available";
		sql = "SELECT id, lot, uuid FROM park WHERE lot = '"..park_extension.."' and domain = '"..domain_name.."' ";
		dbh:query(sql, function(row)
			lot_uuid = row.uuid;
			lot_status = "unavailable";
		end);

	--if park direction is set to out then unpark by bridging it to the caller
		if (park_direction == "out") then
			if (lot_uuid) then
				--set the park status
					cmd = "uuid_setvar "..lot_uuid.." park_status unparked";
					result = trim(api:executeString(cmd));
					freeswitch.consoleLog("NOTICE", "Park Status: unparked "..park_extension.."\n");
				--unpark the call with bridge
					cmd = "uuid_bridge "..uuid.." "..lot_uuid;
					result = trim(api:executeString(cmd));
			end
		else
			--check if the uuid_exists, if it does not exist then delete the uuid from the db and set presence to terminated
				if (lot_uuid) then
					cmd = "uuid_exists "..lot_uuid;
					result = trim(api:executeString(cmd));
					if (result == "false") then
						--set presence out
							event = freeswitch.Event("PRESENCE_IN");
							event:addHeader("proto", "sip");
							event:addHeader("event_type", "presence");
							event:addHeader("alt_event_type", "dialog");
							event:addHeader("Presence-Call-Direction", "outbound");
							event:addHeader("state", "Active (1 waiting)");
							event:addHeader("from", park_extension.."@"..domain_name);
							event:addHeader("login", park_extension.."@"..domain_name);
							event:addHeader("unique-id", lot_uuid);
							event:addHeader("answer-state", "terminated");
							event:fire();

						--delete from the database
							dbh:query("DELETE from park WHERE lot = '"..park_extension.."' and domain = '"..domain_name.."' ");
							--freeswitch.consoleLog("NOTICE", "Park - Affected rows: " .. dbh:affected_rows() .. "\n");

						--set the status to available
							lot_status = "available";
					end
				end

			--check if the parking lot is available, if it is then add it to the db, set presenence to confirmed and park the call
				if (lot_status == "available") then
					--park the call
						cmd = "uuid_park "..uuid;
						result = trim(api:executeString(cmd));
						if (park_music) then
							cmd = "uuid_broadcast "..uuid.." "..park_music.." aleg";
							result = trim(api:executeString(cmd));
						end

					--set the park status
						cmd = "uuid_setvar "..uuid.." park_status parked";
						result = trim(api:executeString(cmd));
						freeswitch.consoleLog("NOTICE", "Park Status: parked "..park_extension.."\n");

					--add to the database
						dbh:query("INSERT INTO park (lot, domain, uuid) VALUES ('"..park_extension.."', '"..domain_name.."', '"..uuid.."')");

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

					--start the fifo monitor on its own so that it doesn't block the script execution
						api = freeswitch.API();
						cmd = "luarun park_monitor.lua "..uuid.." "..domain_name.." "..park_extension.." "..park_timeout_type.." "..park_timeout_seconds.." "..park_timeout_destination;
						result = api:executeString(cmd);
				else
					context = session:getVariable("context");
					caller_id_number = session:getVariable("caller_id_number");
					dialed_extension = session:getVariable("dialed_extension");
					dialed_user = session:getVariable("dialed_user");
					cmd = "user_exists id ".. caller_id_number .." "..domain_name;
					if (api:executeString(cmd) == "true") then
						--bridge the current call to the call that is parked
						--set the presence to terminated
							event = freeswitch.Event("PRESENCE_IN");
							event:addHeader("proto", "sip");
							event:addHeader("event_type", "presence");
							event:addHeader("alt_event_type", "dialog");
							event:addHeader("Presence-Call-Direction", "outbound");
							--event:addHeader("state", "Active (1 waiting)");
							event:addHeader("from", park_extension.."@"..domain_name);
							event:addHeader("login", park_extension.."@"..domain_name);
							event:addHeader("unique-id", uuid);
							event:addHeader("answer-state", "terminated");
							event:fire();

						--delete the lot from the database
							dbh:query("DELETE from park WHERE lot = '"..park_extension.."' and domain = '"..domain_name.."' ");
							--freeswitch.consoleLog("NOTICE", "Park 200- Affected rows: " .. dbh:affected_rows() .. "\n");

						--set the park status
							cmd = "uuid_setvar "..lot_uuid.." park_status unparked";
							result = trim(api:executeString(cmd));
							freeswitch.consoleLog("NOTICE", "Park Status: unparked "..park_extension.."\n");

						--connect the calls
							cmd = "uuid_bridge "..uuid.." "..lot_uuid;
							result = trim(api:executeString(cmd));
					else
						--transfer the call back to the callee
							session:execute("transfer", dialed_user .." XML "..context);
					end
				end

			--continue running when the session ends
				session:setAutoHangup(false);

		end
