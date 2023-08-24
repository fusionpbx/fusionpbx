--      xml_handler.lua
--      Part of FusionPBX
--      Copyright (C) 2016-2022 Mark J Crane <markjcrane@fusionpbx.com>
--      All rights reserved.
--
--      Redistribution and use in source and binary forms, with or without
--      modification, are permitted provided that the following conditions are met:
--
--      1. Redistributions of source code must retain the above copyright notice,
--         this list of conditions and the following disclaimer.
--
--      2. Redistributions in binary form must reproduce the above copyright
--         notice, this list of conditions and the following disclaimer in the
--         documentation and/or other materials provided with the distribution.
--
--      THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--      INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--      AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--      AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--      OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--      SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--      INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--      CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--      ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--      POSSIBILITY OF SUCH DAMAGE.

--get the ivr name
	ivr_menu_uuid = params:getHeader("Menu-Name");
	original_ivr_menu_uuid = ivr_menu_uuid

	local log = require "resources.functions.log".ivr_menu

--include xml library
	local Xml = require "resources.functions.xml";

--get the cache
	local cache = require "resources.functions.cache"
	local ivr_menu_cache_key = "configuration:ivr.conf:" .. ivr_menu_uuid
	XML_STRING, err = cache.get(ivr_menu_cache_key)

--set the cache
	if not XML_STRING  then
		--log cache error
			if (debug["cache"]) then
				freeswitch.consoleLog("warning", "[xml_handler] " .. ivr_menu_cache_key .. " can not be get from the cache: " .. tostring(err) .. "\n");
			end

		--required includes
			local Database = require "resources.functions.database"
			local Settings = require "resources.functions.lazy_settings"
			local json
			if (debug["sql"]) then
				json = require "resources.functions.lunajson"
			end

		--start the xml array
			local xml = Xml:new();
			xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			xml:append([[<document type="freeswitch/xml">]]);
			xml:append([[	<section name="configuration">]]);
			xml:append([[		<configuration name="ivr.conf" description="IVR Menus">]]);
			xml:append([[			<menus>]]);

		--set the sound prefix
			sound_prefix = sounds_dir.."/${default_language}/${default_dialect}/${default_voice}/";
			sound_prefix = xml.sanitize(sound_prefix);
			sound_prefix = string.gsub(sound_prefix, "{default_language}", "${default_language}");
			sound_prefix = string.gsub(sound_prefix, "{default_dialect}", "${default_dialect}"); 
			sound_prefix = string.gsub(sound_prefix, "{default_voice}", "${default_voice}"); 

		--connect to the database
			local dbh = Database.new('system');

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--get the ivr menu from the database
			local sql = [[
				with recursive ivr_menus as (
					select * 
						from v_ivr_menus 
						where ivr_menu_uuid = :ivr_menu_uuid
						and ivr_menu_enabled = 'true'
						union all 
						select child.*
						from v_ivr_menus as child, ivr_menus as parent 
						where child.ivr_menu_parent_uuid = parent.ivr_menu_uuid
						and child.ivr_menu_enabled = 'true'
					)
					select * from ivr_menus
			]];
			local params = {ivr_menu_uuid = ivr_menu_uuid};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end

			dbh:query(sql, params, function(row)

				--set the variables
					domain_uuid = row["domain_uuid"];
					ivr_menu_uuid = row["ivr_menu_uuid"];
					ivr_menu_name = row["ivr_menu_name"];
					ivr_menu_extension = row["ivr_menu_extension"];
					ivr_menu_greet_long = row["ivr_menu_greet_long"];
					ivr_menu_greet_short = row["ivr_menu_greet_short"];
					ivr_menu_invalid_sound = row["ivr_menu_invalid_sound"];
					ivr_menu_exit_sound = row["ivr_menu_exit_sound"];
					ivr_menu_pin_number = row["ivr_menu_pin_number"];
					ivr_menu_confirm_macro = row["ivr_menu_confirm_macro"];
					ivr_menu_confirm_key = row["ivr_menu_confirm_key"];
					ivr_menu_tts_engine = row["ivr_menu_tts_engine"];
					ivr_menu_tts_voice = row["ivr_menu_tts_voice"];
					ivr_menu_confirm_attempts = row["ivr_menu_confirm_attempts"];
					ivr_menu_timeout = row["ivr_menu_timeout"];
					ivr_menu_exit_app = row["ivr_menu_exit_app"];
					ivr_menu_exit_data = row["ivr_menu_exit_data"];
					ivr_menu_inter_digit_timeout = row["ivr_menu_inter_digit_timeout"];
					ivr_menu_max_failures = row["ivr_menu_max_failures"];
					ivr_menu_max_timeouts = row["ivr_menu_max_timeouts"];
					ivr_menu_digit_len = row["ivr_menu_digit_len"];
					ivr_menu_direct_dial = row["ivr_menu_direct_dial"];
					ivr_menu_ringback = row["ivr_menu_ringback"];
					ivr_menu_cid_prefix = row["ivr_menu_cid_prefix"];
					ivr_menu_description = row["ivr_menu_description"];

				--set variables from settings
					local settings = Settings.new(dbh, domain_name, domain_uuid)

				--direct dial length
					local direct_dial_digits_min = settings:get('ivr_menu', 'direct_dial_digits_min', 'numeric') or 2;
					local direct_dial_digits_max = settings:get('ivr_menu', 'direct_dial_digits_max', 'numeric') or 11;

				--storage path
					local storage_type = settings:get('recordings', 'storage_type', 'text')
					local storage_path = settings:get('recordings', 'storage_path', 'text')
					if (storage_path ~= nil) then
						storage_path = storage_path:gsub("${domain_name}", domain_name)
						storage_path = storage_path:gsub("${domain_uuid}", domain_uuid)
					end

				--get the recordings from the database
					ivr_menu_greet_long_is_base64 = false;
					ivr_menu_greet_short_is_base64 = false;
					ivr_menu_invalid_sound_is_base64 = false;
					ivr_menu_exit_sound_is_base64 = false;
					if (storage_type == "base64") then
						--include the file io
							local file = require "resources.functions.file"

						--connect to db
							local dbh = Database.new('system', 'base64/read');

						--base path for recordings
							local base_path = recordings_dir.."/"..domain_name

						--function to get recording to local fs
							local function load_record(name)
								local path = base_path .. "/" .. name;
								local is_base64 = false;
		
								if not file_exists(path) then
									local sql = "SELECT recording_base64 FROM v_recordings " .. 
										"WHERE domain_uuid = :domain_uuid " ..
										"AND recording_filename = :name "
									local params = {domain_uuid = domain_uuid, name = name};
									if (debug["sql"]) then
										freeswitch.consoleLog("notice", "[ivr_menu] SQL: "..sql.."; params:" .. json.encode(params) .. "\n");
									end

									dbh:query(sql, params, function(row)
										--save the recording to the file system
										if #row.recording_base64 > 32 then
											is_base64 = true;
											file.write_base64(path, row.recording_base64);
											--add the full path and file name
											name = path;
										end
									end);
								end
								return name, is_base64
							end

						--greet long
							if #ivr_menu_greet_long > 1 then
								ivr_menu_greet_long, ivr_menu_greet_long_is_base64 = load_record(ivr_menu_greet_long)
							end

						--greet short
							if #ivr_menu_greet_short > 1 then
								ivr_menu_greet_short, ivr_menu_greet_short_is_base64 = load_record(ivr_menu_greet_short)
							end

						--invalid sound
							if #ivr_menu_invalid_sound > 1 then
								ivr_menu_invalid_sound, ivr_menu_invalid_sound_is_base64 = load_record(ivr_menu_invalid_sound)
							end

						--exit sound
							if #ivr_menu_exit_sound > 1 then
								ivr_menu_exit_sound, ivr_menu_exit_sound_is_base64 = load_record(ivr_menu_exit_sound)
							end

							dbh:release()
					elseif (storage_type == "http_cache") then
						--add the path to file name
						ivr_menu_greet_long = storage_path.."/"..ivr_menu_greet_long;
						ivr_menu_greet_short = storage_path.."/"..ivr_menu_greet_short;
						ivr_menu_invalid_sound = storage_path.."/"..ivr_menu_invalid_sound;
						ivr_menu_exit_sound = storage_path.."/"..ivr_menu_exit_sound;
					end

				--greet long
					if (not ivr_menu_greet_long_is_base64 and not file_exists(ivr_menu_greet_long)) then
						if (file_exists(recordings_dir.."/"..domain_name.."/"..ivr_menu_greet_long)) then
							ivr_menu_greet_long = recordings_dir.."/"..domain_name.."/"..ivr_menu_greet_long;
						--elseif (file_exists(sounds_dir.."/en/us/callie/8000/"..ivr_menu_greet_long)) then
						--	ivr_menu_greet_long = sounds_dir.."/${default_language}/${default_dialect}/${default_voice}/"..ivr_menu_greet_long;
						end
					end

				--greet short
					if (string.len(ivr_menu_greet_short) > 1) then
						if (not ivr_menu_greet_short_is_base64 and not file_exists(ivr_menu_greet_short)) then
							if (file_exists(recordings_dir.."/"..domain_name.."/"..ivr_menu_greet_short)) then
								ivr_menu_greet_short = recordings_dir.."/"..domain_name.."/"..ivr_menu_greet_short;
							--elseif (file_exists(sounds_dir.."/en/us/callie/8000/"..ivr_menu_greet_short)) then
							--	ivr_menu_greet_short = sounds_dir.."/${default_language}/${default_dialect}/${default_voice}/"..ivr_menu_greet_short;
							end
						end
					else
						ivr_menu_greet_short = ivr_menu_greet_long;
					end

				--invalid sound
					if (not ivr_menu_invalid_sound_is_base64 and not file_exists(ivr_menu_invalid_sound)) then
						if (file_exists(recordings_dir.."/"..domain_name.. "/"..ivr_menu_invalid_sound)) then
							ivr_menu_invalid_sound = recordings_dir.."/"..domain_name.."/"..ivr_menu_invalid_sound;
						elseif (file_exists(sounds_dir.."/en/us/callie/8000/"..ivr_menu_invalid_sound)) then
							ivr_menu_invalid_sound = sounds_dir.."/${default_language}/${default_dialect}/${default_voice}/"..ivr_menu_invalid_sound;
						end
					end

				--exit sound
					if (not ivr_menu_exit_sound_is_base64 and not file_exists(ivr_menu_exit_sound)) then
						if (file_exists(recordings_dir.."/"..ivr_menu_exit_sound)) then
							if (ivr_menu_exit_sound ~= nil and ivr_menu_exit_sound ~= "") then
								ivr_menu_exit_sound = recordings_dir.."/"..domain_name.."/"..ivr_menu_exit_sound;
							end
						elseif (file_exists(sounds_dir.."/en/us/callie/8000/"..ivr_menu_exit_sound)) then
							ivr_menu_exit_sound = sounds_dir.."/${default_language}/${default_dialect}/${default_voice}/"..ivr_menu_exit_sound;
						end
					end

				--add xml to the array
					xml:append([[				<menu name="]] .. xml.sanitize(ivr_menu_uuid) .. [[" description="]] .. xml.sanitize(ivr_menu_name) .. [[" ]]);
					xml:append([[				greet-long="]] .. xml.sanitize(ivr_menu_greet_long) .. [[" ]]);
					xml:append([[				greet-short="]] .. xml.sanitize(ivr_menu_greet_short) .. [[" ]]);
					xml:append([[				invalid-sound="]] .. xml.sanitize(ivr_menu_invalid_sound) .. [[" ]]);
					xml:append([[				exit-sound="]] .. xml.sanitize(ivr_menu_exit_sound) .. [[" ]]);
					xml:append([[				pin="]] .. xml.sanitize(ivr_menu_pin_number) .. [[" ]]);
					xml:append([[				confirm-macro="]] .. xml.sanitize(ivr_menu_confirm_macro) .. [[" ]]);
					xml:append([[				confirm-key="]] .. xml.sanitize(ivr_menu_confirm_key) .. [[" ]]);
					xml:append([[				tts-engine="]] .. xml.sanitize(ivr_menu_tts_engine) .. [[" ]]);
					xml:append([[				tts-voice="]] .. xml.sanitize(ivr_menu_tts_voice) .. [[" ]]);
					xml:append([[				confirm-attempts="]] .. xml.sanitize(ivr_menu_confirm_attempts) .. [[" ]]);
					xml:append([[				timeout="]] .. xml.sanitize(ivr_menu_timeout) .. [[" ]]);
					xml:append([[				inter-digit-timeout="]] .. xml.sanitize(ivr_menu_inter_digit_timeout) .. [[" ]]);
					xml:append([[				max-failures="]] .. xml.sanitize(ivr_menu_max_failures) .. [[" ]]);
					xml:append([[				max-timeouts="]] .. xml.sanitize(ivr_menu_max_timeouts) .. [[" ]]);
					xml:append([[				digit-len="]] .. xml.sanitize(ivr_menu_digit_len) .. [[" ]]);
					xml:append([[				ivr_menu_exit_app="]] .. xml.sanitize(ivr_menu_exit_app) .. [[" ]]);
					xml:append([[				ivr_menu_exit_data="]] .. xml.sanitize(ivr_menu_exit_data) .. [[" ]]);
					xml:append([[				>]]);

				--get the ivr menu options
					local sql = [[ SELECT * FROM v_ivr_menu_options WHERE ivr_menu_uuid = :ivr_menu_uuid AND ivr_menu_option_enabled = 'true' ORDER BY ivr_menu_option_order asc ]];
					local params = {ivr_menu_uuid = ivr_menu_uuid};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					local direct_dial_exclude = {};
					dbh:query(sql, params, function(r)
						ivr_menu_option_digits = r.ivr_menu_option_digits
						ivr_menu_option_action = r.ivr_menu_option_action
						ivr_menu_option_param = r.ivr_menu_option_param
						ivr_menu_option_description = r.ivr_menu_option_description
						if (#ivr_menu_option_action > 0) then
							ivr_menu_option_param = xml.sanitize(ivr_menu_option_param);
							ivr_menu_option_param = string.gsub(ivr_menu_option_param, "{accountcode}", "${accountcode}");

							xml:append([[					<entry action="]] .. xml.sanitize(ivr_menu_option_action) .. [[" digits="]] .. ivr_menu_option_digits .. [[" param="]] .. ivr_menu_option_param .. [[" description="]] .. xml.sanitize(ivr_menu_option_description) .. [["/>]]);
							if (tonumber(ivr_menu_option_digits) and #ivr_menu_option_digits >= tonumber(direct_dial_digits_min)) then
								table.insert(direct_dial_exclude, ivr_menu_option_digits);
							end
						end
					end);

				--direct dial
					if (ivr_menu_direct_dial == "true") then
						local negative_lookahead = "";
						if (#direct_dial_exclude > 0) then
							negative_lookahead = "(?!^("..table.concat(direct_dial_exclude, "|")..")$)";
						end
						local direct_dial_regex = string.format("/^(%s\\d{%s,%s})$/", negative_lookahead, direct_dial_digits_min, direct_dial_digits_max);
						xml:append([[					<entry action="menu-exec-app" digits="]] .. direct_dial_regex .. [[" param="set ${cond(${user_exists id $1 ]] .. xml.sanitize(domain_name) .. [[} == true ? user_exists=true : user_exists=false)}" description="direct dial"/>\n]]);
						--xml:append([[					<entry action="menu-exec-app" digits="]] .. xml.sanitize(direct_dial_regex) .. [[" param="set ${cond(${user_exists} == true ? user_exists=true : ivr_max_failures=${system(expr ${ivr_max_failures} + 1)})}" description="increment max failures"/>\n]]);
						xml:append([[					<entry action="menu-exec-app" digits="]] .. direct_dial_regex .. [[" param="playback ${cond(${user_exists} == true ? ]] .. sound_prefix .. [[ivr/ivr-call_being_transferred.wav : ]] .. sound_prefix .. [[ivr/ivr-that_was_an_invalid_entry.wav)}" description="play sound"/>\n]]);
						--xml:append([[					<entry action="menu-exec-app" digits="]] .. xml.sanitize(direct_dial_regex) .. [[" param="transfer ${cond(${ivr_max_failures} == ]] .. xml.sanitize(ivr_menu_max_failures) .. [[ ? ]] .. xml.sanitize(ivr_menu_exit_data) .. [[)}" description="max fail transfer"/>\n]]);
						xml:append([[					<entry action="menu-exec-app" digits="]] .. direct_dial_regex .. [[" param="transfer ${cond(${user_exists} == true ? $1 XML ]] .. xml.sanitize(domain_name) .. [[)}" description="direct dial transfer"/>\n]]);
					end

				--close the extension tag if it was left open
					xml:append([[				</menu>]]);

			end);

		--add the xml closing tags
			xml:append([[			</menus>]]);
			xml:append([[		</configuration>]]);
			xml:append([[	</section>]]);
			xml:append([[</document>]]);

		--save the xml into a string
			XML_STRING = xml:build();

		--optinonal debug message
			if (debug["xml_string"]) then
					freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: " .. XML_STRING .. "\n");
			end

		--close the database connection
			dbh:release();
			--freeswitch.consoleLog("notice", "[xml_handler]"..api:execute("eval ${dsn}"));

		--set the cache
			local ok, err = cache.set("configuration:ivr.conf:" .. original_ivr_menu_uuid, XML_STRING, expire["ivr"]);
			if debug["cache"] then
				if ok then
					freeswitch.consoleLog("notice", "[xml_handler] " .. ivr_menu_uuid .. " stored in the cache\n");
				else
					freeswitch.consoleLog("warning", "[xml_handler] " .. ivr_menu_uuid .. " can not be stored in the cache: " .. tostring(err) .. "\n");
				end
			end

		--send the xml to the console
			if (debug["xml_string"]) then
				local file = assert(io.open(temp_dir .. "/ivr-"..ivr_menu_uuid..".conf.xml", "w"));
				file:write(XML_STRING);
				file:close();
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. ivr_menu_cache_key .. " source: database\n");
			end

	else
		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. ivr_menu_cache_key .. " source: cache\n");
			end
	end --if XML_STRING
