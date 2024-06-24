--	ivr_menu.lua
--	Part of FusionPBX
--	Copyright (C) 2012-2015 Mark J Crane <markjcrane@fusionpbx.com>
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
--	THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
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
	require "resources.functions.config";

--include Database class
	local Database = require "resources.functions.database";

--get logger
	local log = require "resources.functions.log".ivr_menu

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--include functions
	require "resources.functions.format_ringback"
	require "resources.functions.split"

--get the variables
	domain_name = session:getVariable("domain_name");
	context = session:getVariable("context");
	ivr_menu_uuid = session:getVariable("ivr_menu_uuid");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");
	domain_uuid = session:getVariable("domain_uuid");

	local recordings_dir = recordings_dir .. "/" .. domain_name

--connect to the database
	dbh = Database.new('system');

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

--define the trim function
	require "resources.functions.trim"

--check if a file exists
	require "resources.functions.file_exists"

--prepare the api object
	api = freeswitch.API();

--get the ivr menu from the database
	sql = [[SELECT * FROM v_ivr_menus
		WHERE ivr_menu_uuid = :ivr_menu_uuid
		AND ivr_menu_enabled = 'true' ]];
	local params = {ivr_menu_uuid = ivr_menu_uuid};
	if (debug["sql"]) then
		log.notice("SQL: " .. sql .. "; params: " .. json.encode(params));
	end
	dbh:query(sql, params, function(row)
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

--disconnect from db
	dbh:release()

--set the caller id name
	if caller_id_name and #caller_id_name > 0 and ivr_menu_cid_prefix and #ivr_menu_cid_prefix > 0 then
		caller_id_name = ivr_menu_cid_prefix .. "#" .. caller_id_name;
		session:setVariable("caller_id_name", caller_id_name);
		session:setVariable("effective_caller_id_name", caller_id_name);
	end

--set ringback
	ivr_menu_ringback = format_ringback(ivr_menu_ringback);
	session:setVariable("ringback", ivr_menu_ringback);
	session:setVariable("transfer_ringback", ivr_menu_ringback);

--get the sounds dir, language, dialect and voice
	sounds_dir = session:getVariable("sounds_dir");
	default_language = session:getVariable("default_language") or 'en';
	default_dialect = session:getVariable("default_dialect") or 'us';
	default_voice = session:getVariable("default_voice") or 'callie';

--make the path relative
	local strip_pattern = "^$${sounds_dir}/${default_language}/${default_dialect}/${default_voice}/"
	ivr_menu_greet_long    = string.gsub(ivr_menu_greet_long,    strip_pattern, "")
	ivr_menu_greet_short   = string.gsub(ivr_menu_greet_short,   strip_pattern, "")
	ivr_menu_invalid_sound = string.gsub(ivr_menu_invalid_sound, strip_pattern, "")
	ivr_menu_exit_sound    = string.gsub(ivr_menu_exit_sound,    strip_pattern, "")

--parse file names
	greet_long_file_name = ivr_menu_greet_long:match("([^/]+)$") or "";
	greet_short_file_name = ivr_menu_greet_short:match("([^/]+)$") or "";
	invalid_sound_file_name = ivr_menu_invalid_sound:match("([^/]+)$") or "";
	exit_sound_file_name = ivr_menu_exit_sound:match("([^/]+)$") or "";

--get the recordings from the database
	ivr_menu_greet_long_is_base64 = false;
	ivr_menu_greet_short_is_base64 = false;
	ivr_menu_invalid_sound_is_base64 = false;
	ivr_menu_exit_sound_is_base64 = false;
	if (storage_type == "base64") then
		--add functions
			require "resources.functions.mkdir";

		--connect to the database
			local dbh = Database.new('system', 'base64/read')

		--make sure the recordings directory exists
			mkdir(recordings_dir);

		--define function to load file from db
			local function load_file(file_name)
				local full_path = recordings_dir .. "/" .. file_name
				if file_exists(full_path) then
					return full_path
				end

				local sql = "SELECT * FROM v_recordings WHERE domain_uuid = :domain_uuid "
							.. "AND recording_filename = :file_name";

				local params = {domain_uuid = domain_uuid, file_name = file_name};

				if (debug["sql"]) then
					log.notice("SQL: " .. sql .. "; params: " .. json.encode(params));
				end

				local is_base64
				dbh:query(sql, params, function(row)
					if #row.recording_base64 > 32 then
						--include the file io
							local file = require "resources.functions.file"

						--write decoded string to file
							local ok, err = file.write_base64(full_path, row.recording_base64);
							if not ok then
								log.err("can not create file: "..full_path.."; Error - " .. tostring(err));
								return
							end

						is_base64 = true;
					end
				end);

				-- return path in any case
				return full_path, is_base64
			end

		--greet long
			if #ivr_menu_greet_long > 1 then
				ivr_menu_greet_long, ivr_menu_greet_long_is_base64 = load_file(greet_long_file_name)
			end

		--greet short
			if #ivr_menu_greet_short > 1 then
				ivr_menu_greet_short, ivr_menu_greet_short_is_base64 = load_file(greet_short_file_name)
			end

		--invalid sound
			if #ivr_menu_invalid_sound > 1 then
				ivr_menu_invalid_sound, ivr_menu_invalid_sound_is_base64 = load_file(invalid_sound_file_name)
			end

		--exit sound
			if #ivr_menu_exit_sound > 1 then
				ivr_menu_exit_sound, ivr_menu_exit_sound_is_base64 = load_file(exit_sound_file_name)
			end

			dbh:release()

	elseif (storage_type == "http_cache") then
		--add the path to file name
		ivr_menu_greet_long = storage_path.."/"..ivr_menu_greet_long;
		ivr_menu_greet_short = storage_path.."/"..ivr_menu_greet_short;
		ivr_menu_invalid_sound = storage_path.."/"..ivr_menu_invalid_sound;
		ivr_menu_exit_sound = storage_path.."/"..ivr_menu_exit_sound;
	end

--adjust file paths
	local function adjust_file_path(full_path, file_name)
		return file_exists(full_path)
			or file_exists(recordings_dir.."/"..file_name)
			or file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..file_name)
			or full_path
	end
	--greet long
		ivr_menu_greet_long = adjust_file_path(ivr_menu_greet_long, greet_long_file_name)
	--greet short
		if #ivr_menu_greet_short > 1 then
			ivr_menu_greet_short = adjust_file_path(ivr_menu_greet_short, greet_short_file_name)
		else
			ivr_menu_greet_short = ivr_menu_greet_long
		end
	--invalid sound
		ivr_menu_invalid_sound = adjust_file_path(ivr_menu_invalid_sound, invalid_sound_file_name)
	--exit sound
		ivr_menu_exit_sound = adjust_file_path(ivr_menu_exit_sound, exit_sound_file_name)

--define the ivr menu
	local menu_options, menu
	local tries = 0;
	function menu()
		-- check number of failures
			if (tries > 0) and (tries >= tonumber(ivr_menu_max_failures)) then
				return
			end

		-- increment the tries
			tries = tries + 1;

		--log the dtmf digits
			if (debug["tries"]) then
				log.noticef("tries: %d/%d", tries, tonumber(ivr_menu_max_failures) or '-1');
			end

		-- set the minimum dtmf lengts
			local min_digits = 1;

		-- set sound file and number of attempts
			local sound, sound_type, attempts

			if tries == 1 then
				sound, sound_type, attempts = ivr_menu_greet_long or "", "long", 1
			else
				sound, sound_type, attempts = ivr_menu_greet_short or "", "short", tonumber(ivr_menu_max_timeouts) or 3
			end

			if (debug["tries"]) then
				log.notice("greet " .. sound_type .. ": " .. sound);
			end

		-- read dtmf
			local dtmf_digits
			if attempts > 0 then
				dtmf_digits = session:playAndGetDigits(min_digits, ivr_menu_digit_len, attempts, ivr_menu_timeout, ivr_menu_confirm_key, sound, "", ".*");
				-- need pause before stream file
				session:setVariable("slept", "false");
			end

		-- proceed dtmf
			if dtmf_digits and #dtmf_digits > 0 then
				if (debug["tries"]) then
					log.notice("dtmf_digits: " .. dtmf_digits);
				end
				return menu_options(session, dtmf_digits);
			end

			return menu();
	end

	function menu_options(session, digits)

		--log the dtmf digits
			if (debug["dtmf"]) then
				log.notice("dtmf: " .. digits);
			end

		--get the ivr menu options
			local sql = [[
				SELECT
					*
				FROM
					v_ivr_menu_options
				WHERE
					ivr_menu_uuid = :ivr_menu_uuid
					AND ivr_menu_option_enabled = 'true'
				ORDER BY
					ivr_menu_option_order asc
			]];
			local params = {ivr_menu_uuid = ivr_menu_uuid};
			if (debug["sql"]) then
				log.notice("SQL: " .. sql .. "; params: " .. json.encode(params));
			end

		--connect to the database
			local dbh = Database.new('system')

		--select actions to execute
			local actions = {}
			dbh:query(sql, params, function(row)
				-- declare vars
					local action, script, data

				--check for matching options
					if tonumber(row.ivr_menu_option_digits) then
						row.ivr_menu_option_digits = "^"..row.ivr_menu_option_digits.."$";
					end

					if api:execute("regex", "m:~"..digits.."~"..row.ivr_menu_option_digits) ~= "true" then
						return
					end

					if row.ivr_menu_option_action == "menu-exec-app" then
						--get the action and data
							action, data = split_first(row.ivr_menu_option_param, ' ', true)
							data = data or ""

						--check if the option uses a regex
							local regex = string.find(row.ivr_menu_option_digits, "(", 0, true);
							if regex then
								--get the regex result
									regex = "m:~"..digits.."~"..row.ivr_menu_option_digits.."~$1"
									local result = trim(api:execute("regex", regex));
									if (debug["regex"]) then
										log.notice("regex "..regex);
										log.notice("result: "..result);
									end

								--replace the $1 and the domain name
									data = data:gsub("$1", result);
									data = data:gsub("${domain_name}", domain_name);
							end --if regex
					end --if menu-exex-app

					if row.ivr_menu_option_action == "phrase" then
						action = 'phrase';
						data = row.ivr_menu_option_param;
					end

					if action == "lua" then
						script = split_first(data, " ", true)
					end

				-- break loop
					if action and #action > 0 then
						actions[#actions + 1] = {action, script, data}
						return
					end

				-- we have unsupported IVR action
					log.warning("invalid action in ivr: " .. row.ivr_menu_option_action);
			end); --end results
			dbh:release()

		--execute
			if #actions > 0 then
				for _, t in ipairs(actions) do
					local action, script, data = t[1],t[2],t[3]
				-- send to the log
					if (debug["action"]) then
						log.notice("action: " .. action .. " data: ".. data);
					end

				-- run the action (with return to menu)
					if action == 'phrase' or script == 'streamfile.lua' then
						session:execute(action, data);
					else
						if ivr_menu_exit_sound and #ivr_menu_exit_sound > 0 then
							session:streamFile(ivr_menu_exit_sound);
						end
						-- run the action (without return to menu)
							return session:execute(action, data);
					end
				end
				return menu();
			end

		--direct dial
			if ivr_menu_direct_dial == "true" and #digits > 0 and #digits < 6 then
				-- remove *#
					digits = digits:gsub("[*#]", "");

				-- check to see if the user extension exists
					local cmd = "user_exists id ".. digits .." "..domain_name;
					local result = api:executeString(cmd);
					log.notice("[direct dial] "..cmd.." "..result);
					if result == "true" then
						--log the action
							log.notice("[direct dial] "..digits.." XML "..context);
						--run the action
							return session:execute("transfer", digits.." XML "..context);
					end

				--run the menu again (without play ivr_menu_invalid_sound)
					return menu();
			end

		--invalid input try again
			if (debug["action"]) then
				log.notice("unrecgnized action");
			end
			if ivr_menu_invalid_sound and #ivr_menu_invalid_sound then
				session:streamFile(ivr_menu_invalid_sound);
			end
			return menu();
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