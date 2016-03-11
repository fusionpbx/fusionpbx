--get the argv values
	script_name = argv[0];
	file_name = argv[1];

--include config.lua
	require "resources.functions.config";

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

--get the variables
	domain_name = session:getVariable("domain_name");
	domain_uuid = session:getVariable("domain_uuid");

--get the sounds dir, language, dialect and voice
	sounds_dir = session:getVariable("sounds_dir");
	default_language = session:getVariable("default_language");
	default_dialect = session:getVariable("default_dialect");
	default_voice = session:getVariable("default_voice");
	if (not default_language) then default_language = 'en'; end
	if (not default_dialect) then default_dialect = 'us'; end
	if (not default_voice) then default_voice = 'callie'; end

--settings
	require "resources.functions.settings";
	settings = settings(domain_uuid);
	storage_type = "";
	storage_path = "";
	if (settings['recordings'] ~= nil) then
		if (settings['recordings']['storage_type'] ~= nil) then
			if (settings['recordings']['storage_type']['text'] ~= nil) then
				storage_type = settings['recordings']['storage_type']['text'];
			end
		end
		if (settings['recordings']['storage_path'] ~= nil) then
			if (settings['recordings']['storage_path']['text'] ~= nil) then
				storage_path = settings['recordings']['storage_path']['text'];
				storage_path = storage_path:gsub("${domain_name}", domain_name);
				storage_path = storage_path:gsub("${voicemail_id}", voicemail_id);
				storage_path = storage_path:gsub("${voicemail_dir}", voicemail_dir);
			end
		end
	end

	if (not temp_dir) or (#temp_dir == 0) then
		if (settings['server'] ~= nil) then
			if (settings['server']['temp'] ~= nil) then
				if (settings['server']['temp']['dir'] ~= nil) then
					temp_dir = settings['server']['temp']['dir'];
				end
			end
		end
	end

--set the recordings directory
	recordings_dir = recordings_dir .. "/"..domain_name;

--check if a file exists
	require "resources.functions.file_exists";

--define the on_dtmf call back function
	function on_dtmf(s, type, obj, arg)
		if (type == "dtmf") then
			session:setVariable("dtmf_digits", obj['digit']);
			freeswitch.console_log("info", "[streamfile] dtmf digit: " .. obj['digit'] .. ", duration: " .. obj['duration'] .. "\n");
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

--parse file name
	file_name_only = file_name:match("([^/]+)$");

--if base64, get from db, create temp file
	if (storage_type == "base64") then
		if (not file_exists(recordings_dir.."/"..file_name_only)) then
			sql = [[SELECT * FROM v_recordings
				WHERE domain_uuid = ']] .. domain_uuid ..[['
				AND recording_filename = ']].. file_name_only.. [[' ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				--add functions
					require "resources.functions.base64";
				--add the path to filename
					file_name = recordings_dir.."/"..file_name_only;
				--save the recording to the file system
					if (string.len(row["recording_base64"]) > 32) then
						local file = io.open(file_name, "w");
						file:write(base64.decode(row["recording_base64"]));
						file:close();
					end
			end);
		else
			file_name = recordings_dir.."/"..file_name_only;
		end
	end

--adjust file path
	if (not file_exists(file_name)) then
		if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..file_name_only)) then
			file_name = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..file_name_only;
		elseif (file_exists(recordings_dir.."/"..file_name_only)) then
			file_name = recordings_dir.."/"..file_name_only;
		end
	end

--stream file if exists
	if (session:ready()) then
		session:answer();
		slept = session:getVariable("slept");
		if (slept == nil or slept == "false") then
			freeswitch.consoleLog("notice", "[ivr_menu] sleeping (1s)\n");
			session:sleep(1000);
			if (slept == "false") then
				session:setVariable("slept", "true");
			end
		end
		session:setInputCallback("on_dtmf", "");
		session:streamFile(file_name);
		session:unsetInputCallback();
	end

--if base64, remove temp file (increases responsiveness when files remain local)
	if (storage_type == "base64") then
		if (file_exists(file_name)) then
			--os.remove(file_name);
		end
	end
