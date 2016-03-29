--get the scripts directory and include the config.lua
	require "resources.functions.config";

--additional includes
	require "resources.functions.file_exists";
	require "resources.functions.trim";
	require "resources.functions.mkdir";

--get the argv values
	script_name = argv[0];

--options all, last, non_moderator, member_id
	meeting_uuid = argv[1];
	domain_name = argv[2];

--prepare the api object
	api = freeswitch.API();

--check if the conference exists
	cmd = "conference "..meeting_uuid.."-"..domain_name.." xml_list";
	freeswitch.consoleLog("INFO","" .. cmd .. "\n");
	result = trim(api:executeString(cmd));
	if (string.sub(result, -9) == "not found") then
		conference_exists = false;
	else
		conference_exists = true;
	end

--start the recording
	if (conference_exists) then
		--get the conference session uuid
			result = string.match(result,[[<conference (.-)>]],1);
			conference_session_uuid = string.match(result,[[uuid="(.-)"]],1);
			freeswitch.consoleLog("INFO","[start-recording] conference_session_uuid: " .. conference_session_uuid .. "\n");

		--get the current time
			start_epoch = os.time();

		--add the domain name to the recordings directory
			recordings_dir = recordings_dir .. "/"..domain_name;
			recordings_dir = recordings_dir.."/archive/"..os.date("%Y", start_epoch).."/"..os.date("%b", start_epoch).."/"..os.date("%d", start_epoch);
			mkdir(recordings_dir);
			recording = recordings_dir.."/"..conference_session_uuid;

		--send a command to record the conference
			if (not file_exists(recording..".wav")) then
				cmd = "conference "..meeting_uuid.."-"..domain_name.." record "..recording..".wav";
				freeswitch.consoleLog("notice", "[start-recording] cmd: " .. cmd .. "\n");
				response = api:executeString(cmd);
			end
	end
