--include config.lua
	require "resources.functions.config";

--prepare the api
	api = freeswitch.API();

--define the trim function
	require "resources.functions.trim";
	require "resources.functions.file_exists";

--include functions
	local basename = require "resources.functions.basename";

--get the argv values
	script_name = argv[0];
	uuid = argv[1];
	announcement = (trim(api:executeString("uuid_getvar " .. uuid .. " ring_group_wait_announcement")));
	interval = tonumber((trim(api:executeString("uuid_getvar " .. uuid .. " ring_group_wait_interval")))) or 30;
	domain_name = (trim(api:executeString("uuid_getvar " .. uuid .. " ring_group_wait_domain_name")));
	domain_uuid = (trim(api:executeString("uuid_getvar " .. uuid .. " ring_group_wait_domain_uuid")));

--resolve the announcement to something uuid_broadcast can play
	local function resolve_announcement(file_name)
		if not file_name or #file_name == 0 then
			return nil
		end

		if string.sub(file_name, 1, 1) == "/" or string.match(file_name, "^[a-z]+:") then
			return file_name
		end

		if basename(file_name) == file_name then
			local recording_path = recordings_dir .. "/" .. domain_name .. "/" .. file_name;
			if file_exists(recording_path) then
				return recording_path
			end

			local sounds_dir = (trim(api:executeString("uuid_getvar " .. uuid .. " sounds_dir")));
			local default_language = (trim(api:executeString("uuid_getvar " .. uuid .. " default_language")));
			local default_dialect = (trim(api:executeString("uuid_getvar " .. uuid .. " default_dialect")));
			local default_voice = (trim(api:executeString("uuid_getvar " .. uuid .. " default_voice")));

			if not default_language or #default_language == 0 then default_language = 'en'; end
			if not default_dialect or #default_dialect == 0 then default_dialect = 'us'; end
			if not default_voice or #default_voice == 0 then default_voice = 'callie'; end

			if sounds_dir and #sounds_dir > 0 then
				local sound_path = sounds_dir .. "/" .. default_language .. "/" .. default_dialect .. "/" .. default_voice .. "/" .. file_name;
				if file_exists(sound_path) then
					return sound_path
				end
			end
		end

		return file_name
	end

--play the wait announcement and reschedule while the call is still active
	if trim(api:executeString("uuid_exists " .. uuid)) == "true" then
		local media_path = resolve_announcement(announcement);
		if media_path and #media_path > 0 then
			api:executeString("uuid_setvar " .. uuid .. " ringback ");
			api:executeString("uuid_setvar " .. uuid .. " transfer_ringback ");
			api:executeString("uuid_broadcast " .. uuid .. " " .. media_path .. " aleg");
		end

		wait_announcement_script = scripts_dir:gsub('\\','/') .. "/app/ring_groups/resources/scripts/wait_announcement.lua";
		command = "sched_api +" .. interval .. " " .. uuid .. " lua " .. wait_announcement_script .. " " .. uuid;
		api:executeString(command);
	end
