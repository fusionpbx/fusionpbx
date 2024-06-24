--get the argv values
	local script_name = argv[0];
	local file_name = table.concat(argv, " ");
	freeswitch.consoleLog("notice", "[streamfile] file_name: " .. file_name .. "\n");

--include config.lua
	require "resources.functions.config";

--load libraries
	local Database = require "resources.functions.database";
	local Settings = require "resources.functions.lazy_settings";
	local file     = require "resources.functions.file";
	local log      = require "resources.functions.log".streamfile;

--get the variables
	local domain_name = session:getVariable("domain_name");
	local domain_uuid = session:getVariable("domain_uuid");

--get the sounds dir, language, dialect and voice
	local sounds_dir = session:getVariable("sounds_dir");
	local default_language = session:getVariable("default_language") or 'en';
	local default_dialect = session:getVariable("default_dialect") or 'us';
	local default_voice = session:getVariable("default_voice") or 'callie';

--set the recordings directory
	local recordings_dir = recordings_dir .. "/" .. domain_name;

--parse file name
	local file_name_only = file_name:match("([^/]+)$");

--settings
	local dbh = Database.new('system');
	local settings = Settings.new(dbh, domain_name, domain_uuid);
	local storage_type = settings:get('recordings', 'storage_type', 'text') or '';

	if (not temp_dir) or (#temp_dir == 0) then
		temp_dir = settings:get('server', 'temp', 'dir') or '/tmp';
	end

	dbh:release()

--define the on_dtmf call back function
	-- luacheck: globals on_dtmf, ignore s arg
	function on_dtmf(s, type, obj, arg)
		if (type == "dtmf") then
			session:setVariable("dtmf_digits", obj['digit']);
			log.info("dtmf digit: " .. obj['digit'] .. ", duration: " .. obj['duration']);
			if (obj['digit'] == "*") then
				return("false"); --return to previous
			elseif (obj['digit'] == "0") then
				return("restart"); --start over
			elseif (obj['digit'] == "1") then
				return("volume:-1"); --volume down
			elseif (obj['digit'] == "3") then
				return("volume:+1"); -- volume up
			elseif (obj['digit'] == "4") then
				return("seek:-5000"); -- back
			elseif (obj['digit'] == "5") then
				return("pause"); -- pause toggle
			elseif (obj['digit'] == "6") then
				return("seek:+5000"); -- forward
			elseif (obj['digit'] == "7") then
				return("speed:-1"); -- increase playback
			elseif (obj['digit'] == "9") then
				return("speed:+1"); -- decrease playback
			end
		end
	end

--if base64, get from db, create temp file
	if (storage_type == "base64") then
		local full_path = recordings_dir .. "/" .. file_name_only
		if not file.exists(full_path) then
			local sql = "SELECT recording_base64 FROM v_recordings "
				.. "WHERE domain_uuid = '" .. domain_uuid .."'"
				.. "AND recording_filename = '".. file_name_only.. "' ";
			if (debug["sql"]) then
				log.notice("SQL: " .. sql);
			end

			local dbh = Database.new('system', 'base64/read');
			local recording_base64 = dbh:first_value(sql);
			dbh:release();

			if recording_base64 and #recording_base64 > 32 then
				file_name = full_path;
				file.write_base64(file_name, recording_base64);
			end
		else
			file_name = full_path;
		end
	end

--adjust file path
	if not file.exists(file_name) then
		file_name = file.exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..file_name_only)
			or file.exists(recordings_dir.."/"..file_name_only)
			or file_name
	end

--stream file if exists, If being called by luarun output filename to stream
	if (session:ready() and stream == nil) then
		session:answer();
		local slept = session:getVariable("slept");
		if (slept == nil or slept == "false") then
			log.notice("sleeping (1s)");
			session:sleep(1000);
			if (slept == "false") then
				session:setVariable("slept", "true");
			end
		end
		session:setInputCallback("on_dtmf", "");
		session:streamFile(file_name);
		session:unsetInputCallback();
	else
		stream:write(file_name);
	end

--if base64, remove temp file (increases responsiveness when files remain local)
	-- if (storage_type == "base64") then
	-- 	if (file.exists(file_name)) then
	-- 		file.remove(file_name);
	-- 	end
	-- end
