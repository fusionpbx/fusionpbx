--	ivr_menu.lua
--	Part of FusionPBX
--	Copyright (C) 2012 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	notice, this list of conditions and the following disclaimer in the
--	documentation and/or other materials provided with the distribution.
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

--set the debug options
	debug["action"] = false;
	debug["sql"] = false;
	debug["regex"] = false;
	debug["dtmf"] = false;
	debug["tries"] = false;

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--get the variables
	domain_name = session:getVariable("domain_name");
	context = session:getVariable("context");
	ivr_menu_uuid = session:getVariable("ivr_menu_uuid");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");
	domain_uuid = session:getVariable("domain_uuid");

--settings
	dofile(scripts_dir.."/resources/functions/settings.lua");
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
	temp_dir = "";
	if (settings['server'] ~= nil) then
		if (settings['server']['temp'] ~= nil) then
			if (settings['server']['temp']['dir'] ~= nil) then
				temp_dir = settings['server']['temp']['dir'];
			end
		end
	end

--set the recordings directory
	if (domain_count > 1) then
		recordings_dir = recordings_dir .. "/"..domain_name;
	end

--set default variable(s)
	tries = 0;

--add the trim function
	function trim(s)
		return s:gsub("^%s+", ""):gsub("%s+$", "")
	end

--check if a file exists
	function file_exists(name)
		local f=io.open(name,"r")
		if f~=nil then io.close(f) return true else return false end
	end

--prepare the api object
	api = freeswitch.API();

--get the ivr menu from the database
	sql = [[SELECT * FROM v_ivr_menus 
		WHERE ivr_menu_uuid = ']] .. ivr_menu_uuid ..[['
		AND ivr_menu_enabled = 'true' ]];
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
	end
	status = dbh:query(sql, function(row)
		domain_uuid = row["domain_uuid"];
		ivr_menu_name = row["ivr_menu_name"];
		--ivr_menu_extension = row["ivr_menu_extension"];
		ivr_menu_greet_long = row["ivr_menu_greet_long"];
		ivr_menu_greet_short = row["ivr_menu_greet_short"];
		ivr_menu_invalid_sound = row["ivr_menu_invalid_sound"];
		ivr_menu_exit_sound = row["ivr_menu_exit_sound"];
		ivr_menu_confirm_macro = row["ivr_menu_confirm_macro"];
		ivr_menu_confirm_key = row["ivr_menu_confirm_key"];
		ivr_menu_tts_engine = row["ivr_menu_tts_engine"];
		ivr_menu_tts_voice = row["ivr_menu_tts_voice"];
		ivr_menu_confirm_attempts = row["ivr_menu_confirm_attempts"];
		ivr_menu_timeout = row["ivr_menu_timeout"];
		--ivr_menu_exit_app = row["ivr_menu_exit_app"];
		--ivr_menu_exit_data = row["ivr_menu_exit_data"];
		ivr_menu_inter_digit_timeout = row["ivr_menu_inter_digit_timeout"];
		ivr_menu_max_failures = row["ivr_menu_max_failures"];
		ivr_menu_max_timeouts = row["ivr_menu_max_timeouts"];
		ivr_menu_digit_len = row["ivr_menu_digit_len"];
		ivr_menu_direct_dial = row["ivr_menu_direct_dial"];
		--ivr_menu_description = row["ivr_menu_description"];
		ivr_menu_ringback = row["ivr_menu_ringback"];
		ivr_menu_cid_prefix = row["ivr_menu_cid_prefix"];
	end);

--set the caller id name
	if (caller_id_name) then
		if (string.len(ivr_menu_cid_prefix) > 0) then
			caller_id_name = ivr_menu_cid_prefix .. "#" .. caller_id_name;
			session:setVariable("caller_id_name", caller_id_name);
			session:setVariable("effective_caller_id_name", caller_id_name);
		end
	end

--set ringback
	if (ivr_menu_ringback == "${uk-ring}") then
		ivr_menu_ringback = "tone_stream://%(400,200,400,450);%(400,2200,400,450);loops=-1";
	end
	if (ivr_menu_ringback == "${us-ring}") then
		ivr_menu_ringback = "tone_stream://%(2000,4000,440.0,480.0);loops=-1";
	end
	if (ivr_menu_ringback == "${pt-ring}") then
		ivr_menu_ringback = "tone_stream://%(1000,5000,400.0,0.0);loops=-1";
	end
	if (ivr_menu_ringback == "${fr-ring}") then
		ivr_menu_ringback = "tone_stream://%(1500,3500,440.0,0.0);loops=-1";
	end
	if (ivr_menu_ringback == "${rs-ring}") then
		ivr_menu_ringback = "tone_stream://%(1000,4000,425.0,0.0);loops=-1";
	end
	if (ivr_menu_ringback == "${it-ring}") then
		ivr_menu_ringback = "tone_stream://%(1000,4000,425.0,0.0);loops=-1";
	end
	if (ivr_menu_ringback == nil or ivr_menu_ringback == "") then
		ivr_menu_ringback = "local_stream://default";
	end
	session:setVariable("ringback", ivr_menu_ringback);
	session:setVariable("transfer_ringback", ivr_menu_ringback);

--get the sounds dir, language, dialect and voice
	sounds_dir = session:getVariable("sounds_dir");
	default_language = session:getVariable("default_language");
	default_dialect = session:getVariable("default_dialect");
	default_voice = session:getVariable("default_voice");
	if (not default_language) then default_language = 'en'; end
	if (not default_dialect) then default_dialect = 'us'; end
	if (not default_voice) then default_voice = 'callie'; end

--make the path relative
	if (string.sub(ivr_menu_greet_long,0,71) == "$${sounds_dir}/${default_language}/${default_dialect}/${default_voice}/") then
		ivr_menu_greet_long = string.sub(ivr_menu_greet_long,72);
	end
	if (string.sub(ivr_menu_greet_short,0,71) == "$${sounds_dir}/${default_language}/${default_dialect}/${default_voice}/") then
		ivr_menu_greet_short = string.sub(ivr_menu_greet_short,72);
	end
	if (string.sub(ivr_menu_invalid_sound,0,71) == "$${sounds_dir}/${default_language}/${default_dialect}/${default_voice}/") then
		ivr_menu_invalid_sound = string.sub(ivr_menu_invalid_sound,72);
	end
	if (string.sub(ivr_menu_exit_sound,0,71) == "$${sounds_dir}/${default_language}/${default_dialect}/${default_voice}/") then
		ivr_menu_exit_sound = string.sub(ivr_menu_exit_sound,72);
	end

--parse file names
	greet_long_file_name = ivr_menu_greet_long:match("([^/]+)$");
	greet_short_file_name = ivr_menu_greet_short:match("([^/]+)$");
	invalid_sound_file_name = ivr_menu_invalid_sound:match("([^/]+)$");
	exit_sound_file_name = ivr_menu_exit_sound:match("([^/]+)$");

--prevent nil concatenation errors
	if (greet_long_file_name == nil) then greet_long_file_name = ""; end
	if (greet_short_file_name == nil) then greet_short_file_name = ""; end
	if (invalid_sound_file_name == nil) then invalid_sound_file_name = ""; end
	if (exit_sound_file_name == nil) then exit_sound_file_name = ""; end

--get the recordings from the database
	ivr_menu_greet_long_is_base64 = false;
	ivr_menu_greet_short_is_base64 = false;
	ivr_menu_invalid_sound_is_base64 = false;
	ivr_menu_exit_sound_is_base64 = false;
	if (storage_type == "base64") then
		--greet long
			if (string.len(ivr_menu_greet_long) > 1) then
				if (not file_exists(recordings_dir.."/"..greet_long_file_name)) then
					sql = [[SELECT * FROM v_recordings 
						WHERE domain_uuid = ']]..domain_uuid..[['
						AND recording_filename = ']]..greet_long_file_name..[[' ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[ivr_menu] SQL: "..sql.."\n");
					end
					status = dbh:query(sql, function(row)
						--add functions
							dofile(scripts_dir.."/resources/functions/base64.lua");
						--add the path to filename
							ivr_menu_greet_long = recordings_dir.."/"..greet_long_file_name;
							ivr_menu_greet_long_is_base64 = true;
						--save the recording to the file system
							if (string.len(row["recording_base64"]) > 32) then
								local file = io.open(ivr_menu_greet_long, "w");
								file:write(base64.decode(row["recording_base64"]));
								file:close();
							end
					end);
				end
			end
		--greet short
			if (string.len(ivr_menu_greet_short) > 1) then
				if (not file_exists(recordings_dir.."/"..greet_short_file_name)) then
					sql = [[SELECT * FROM v_recordings 
						WHERE domain_uuid = ']]..domain_uuid..[['
						AND recording_filename = ']]..greet_short_file_name..[[' ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[ivr_menu] SQL: "..sql.."\n");
					end
					status = dbh:query(sql, function(row)
						--add functions
							dofile(scripts_dir.."/resources/functions/base64.lua");
						--add the path to filename
							ivr_menu_greet_short = recordings_dir.."/"..greet_short_file_name;
							ivr_menu_greet_short_is_base64 = true;
						--save the recording to the file system
							if (string.len(row["recording_base64"]) > 32) then
								local file = io.open(ivr_menu_greet_short, "w");
								file:write(base64.decode(row["recording_base64"]));
								file:close();
							end
					end);
				end
			end
		--invalid sound
			if (string.len(ivr_menu_invalid_sound) > 1) then
				if (not file_exists(recordings_dir.."/"..invalid_sound_file_name)) then
					sql = [[SELECT * FROM v_recordings 
						WHERE domain_uuid = ']]..domain_uuid..[['
						AND recording_filename = ']]..invalid_sound_file_name..[[' ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[ivr_menu] SQL: "..sql.."\n");
					end
					status = dbh:query(sql, function(row)
						--add functions
							dofile(scripts_dir.."/resources/functions/base64.lua");
						--add the path to filename
							ivr_menu_invalid_sound = recordings_dir.."/"..invalid_sound_file_name;
							ivr_menu_invalid_sound_is_base64 = true;
						--save the recording to the file system
							if (string.len(row["recording_base64"]) > 32) then
								local file = io.open(ivr_menu_invalid_sound, "w");
								file:write(base64.decode(row["recording_base64"]));
								file:close();
							end
					end);
				end
			end
		--exit sound
			if (string.len(ivr_menu_exit_sound) > 1) then
				if (not file_exists(recordings_dir.."/"..exit_sound_file_name)) then
					sql = [[SELECT * FROM v_recordings 
						WHERE domain_uuid = ']]..domain_uuid..[['
						AND recording_filename = ']]..exit_sound_file_name..[[' ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[ivr_menu] SQL: "..sql.."\n");
					end
					status = dbh:query(sql, function(row)
						--add functions
							dofile(scripts_dir.."/resources/functions/base64.lua");
						--add the path to filename
							ivr_menu_exit_sound = recordings_dir.."/"..exit_sound_file_name;
							ivr_menu_exit_sound_is_base64 = true;
						--save the recording to the file system
							if (string.len(row["recording_base64"]) > 32) then
								local file = io.open(ivr_menu_exit_sound, "w");
								file:write(base64.decode(row["recording_base64"]));
								file:close();
							end
					end);
				end
			end
	elseif (storage_type == "http_cache") then
		--add the path to file name
		ivr_menu_greet_long = storage_path.."/"..ivr_menu_greet_long;
		ivr_menu_greet_short = storage_path.."/"..ivr_menu_greet_short;
		ivr_menu_invalid_sound = storage_path.."/"..ivr_menu_invalid_sound;
		ivr_menu_exit_sound = storage_path.."/"..ivr_menu_exit_sound;
	end

--adjust file paths
	--greet long
		if (not file_exists(ivr_menu_greet_long)) then
			if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..greet_long_file_name)) then
				ivr_menu_greet_long = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..greet_long_file_name;
			elseif (file_exists(recordings_dir.."/"..greet_long_file_name)) then
				ivr_menu_greet_long = recordings_dir.."/"..greet_long_file_name;
			end
		end
	--greet short
		if (string.len(ivr_menu_greet_short) > 1) then
			if (not file_exists(ivr_menu_greet_short)) then
				if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..greet_short_file_name)) then
					ivr_menu_greet_short = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..greet_short_file_name;
				elseif (file_exists(recordings_dir.."/"..greet_short_file_name)) then
					ivr_menu_greet_short = recordings_dir.."/"..greet_short_file_name;
				end
			end
		else
			ivr_menu_greet_short = ivr_menu_greet_long;
		end
	--invalid sound
		if (not file_exists(ivr_menu_invalid_sound)) then
			if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..invalid_sound_file_name)) then
				ivr_menu_invalid_sound = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..invalid_sound_file_name;
			elseif (file_exists(recordings_dir.."/"..invalid_sound_file_name)) then
				ivr_menu_invalid_sound = recordings_dir.."/"..invalid_sound_file_name;
			end
		end
	--exit sound
		if (not file_exists(ivr_menu_exit_sound)) then
			if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..exit_sound_file_name)) then
				ivr_menu_exit_sound = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..exit_sound_file_name;
			elseif (file_exists(recordings_dir.."/"..exit_sound_file_name)) then
				ivr_menu_exit_sound = recordings_dir.."/"..exit_sound_file_name;
			end
		end

--define the ivr menu
	function menu()
		--increment the tries
		tries = tries + 1;
		min_digits = 1;
		session:setVariable("slept", "false");
		if (tries == 1) then
			if (debug["tries"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] greet long: " .. ivr_menu_greet_long .. "\n");
			end
			--check if phrase
			pos = string.find(ivr_menu_greet_long, ":", 0, true);
			if (pos ~= nil and string.sub(ivr_menu_greet_long, 0, pos-1) == 'phrase') then
				freeswitch.consoleLog("notice", "[ivr_menu] phrase detected\n");
				session:playAndGetDigits(min_digits, ivr_menu_digit_len, 1, ivr_menu_timeout, ivr_menu_confirm_key, ivr_menu_greet_long, "", ".*");
				dtmf_digits = session:getVariable("dtmf_digits");
				session:setVariable("slept", "false");
			else 
				dtmf_digits = session:playAndGetDigits(min_digits, ivr_menu_digit_len, 1, ivr_menu_timeout, ivr_menu_confirm_key, ivr_menu_greet_long, "", ".*");				
			end
		else
			if (debug["tries"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] greet long: " .. ivr_menu_greet_short .. "\n");
			end
			dtmf_digits = session:playAndGetDigits(min_digits, ivr_menu_digit_len, ivr_menu_max_timeouts, ivr_menu_timeout, ivr_menu_confirm_key, ivr_menu_greet_short, "", ".*");
		end
		if (dtmf_digits ~= nil and string.len(dtmf_digits) > 0) then
			if (debug["tries"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] dtmf_digits: " .. dtmf_digits .. "\n");
			end
			menu_options(session, dtmf_digits);
		else
			if (tries < tonumber(ivr_menu_max_failures)) then
				--log the dtmf digits
					if (debug["tries"]) then
						freeswitch.consoleLog("notice", "[ivr_menu] tries: " .. tries .. "\n");
					end
				--run the menu again
					menu(); 
			end
		end
	end

	function menu_options(session, digits)

		--log the dtmf digits
			if (debug["dtmf"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] dtmf: " .. digits .. "\n");
			end

		--get the ivr menu options
			sql = [[SELECT * FROM v_ivr_menu_options WHERE ivr_menu_uuid = ']] .. ivr_menu_uuid ..[[' ORDER BY ivr_menu_option_order asc ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				--check for matching options
					if (tonumber(row.ivr_menu_option_digits) ~= nil) then
						row.ivr_menu_option_digits = "^"..row.ivr_menu_option_digits.."$";
					end
					if (api:execute("regex", "m:~"..digits.."~"..row.ivr_menu_option_digits) == "true") then
						if (row.ivr_menu_option_action == "menu-exec-app") then
							--get the action and data
								pos = string.find(row.ivr_menu_option_param, " ", 0, true);
								action = string.sub(row.ivr_menu_option_param, 0, pos-1);
								data = string.sub(row.ivr_menu_option_param, pos+1);

							--check if the option uses a regex
								regex = string.find(row.ivr_menu_option_digits, "(", 0, true);
								if (regex) then
									--get the regex result
										result = trim(api:execute("regex", "m:~"..digits.."~"..row.ivr_menu_option_digits.."~$1"));
										if (debug["regex"]) then
											freeswitch.consoleLog("notice", "[ivr_menu] regex m:~"..digits.."~"..row.ivr_menu_option_digits.."~$1\n");
											freeswitch.consoleLog("notice", "[ivr_menu] result: "..result.."\n");
										end

									--replace the $1 and the domain name
										data = data:gsub("$1", result);
										data = data:gsub("${domain_name}", domain_name);
								end --if regex
						end --if menu-exex-app
						if (row.ivr_menu_option_action == "phrase") then
							action = 'phrase';
							data = row.ivr_menu_option_param;
						end
						if (action == "lua") then
							pos = string.find(data, " ", 0, true);
							script = string.sub(data, 0, pos-1);
						end
					end --if regex match

				--execute
					if (action) then
						if (string.len(action) > 0) then
							--send to the log
								if (debug["action"]) then
									freeswitch.consoleLog("notice", "[ivr_menu] action: " .. action .. " data: ".. data .. "\n");
								end
							--run the action
								if (action == 'phrase' or (script ~= nil and script == 'streamfile.lua')) then
									session:execute(action, data);
									menu();
								else 
									if (ivr_menu_exit_sound ~= nil) then
										session:streamFile(ivr_menu_exit_sound);
									end
									session:execute(action, data);
								end
						end
					end

				--clear the variables
					action = "";
					data = "";
			end); --end results

		--direct dial
			if (ivr_menu_direct_dial == "true") then
				if (string.len(digits) < 6) then
					--replace the $1 and the domain name
						digits = digits:gsub("*", "");
					--check to see if the user extension exists
						cmd = "user_exists id ".. digits .." "..domain_name;
						result = api:executeString(cmd);
						freeswitch.consoleLog("NOTICE", "[ivr_menu] "..cmd.." "..result.."\n");
						if (result == "true") then
							--run the action
								session:execute("transfer", digits.." XML "..context);
						else
							--run the menu again
								menu();
						end
				end
			end

		--execute
			if (action) then
				if (string.len(action) == 0) then
					session:streamFile(ivr_menu_invalid_sound);
					menu();
				end
			else
				if (action ~= 'phrase' and (script == nil or script ~= 'streamfile.lua')) then
					session:streamFile(ivr_menu_invalid_sound);
				end
				menu();
			end
			
	end --end function

--answer the session
	if (session:ready()) then
		session:answer();
		menu();
	end

--if base64, remove temporary audio files (increases responsiveness when files remain local)
	if (storage_type == "base64") then
		if (ivr_menu_greet_long_is_base64 and file_exists(ivr_menu_greet_long)) then
			--os.remove(ivr_menu_greet_long);
		end 
		if (ivr_menu_greet_short_is_base64 and file_exists(ivr_menu_greet_short)) then
			--os.remove(ivr_menu_greet_short);
		end 
		if (ivr_menu_invalid_sound_is_base64 and file_exists(ivr_menu_invalid_sound)) then
			--os.remove(ivr_menu_invalid_sound);
		end 
	end