--	Part of FusionPBX
--	Copyright (C) 2013-2020 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	  this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	  notice, this list of conditions and the following disclaimer in the
--	  documentation and/or other materials provided with the distribution.
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

--load libraries
	local Database = require "resources.functions.database"
	local Settings = require "resources.functions.lazy_settings"
	local JSON = require "resources.functions.lunajson"

--define uuid function
	local random = math.random;
	local function gen_uuid()
		local template ='xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
		return string.gsub(template, '[xy]', function (c)
			local v = (c == 'x') and random(0, 0xf) or random(8, 0xb);
			return string.format('%x', v);
		end)
	end

--define escape function (prevents lua injection attacks)
	local function esc(x)
	return (x:gsub('%%', '%%%%')
		:gsub('^%^', '%%^')
		:gsub('%$$', '%%$')
		:gsub('%(', '%%(')
		:gsub('%)', '%%)')
		:gsub('%.', '%%.')
		:gsub('%[', '%%[')
		:gsub('%]', '%%]')
		:gsub('%*', '%%*')
		:gsub('%+', '%%+')
		:gsub('%-', '%%-')
		:gsub('%?', '%%?'))
	end

--save the recording
	function record_message()

		--set the variables
			local db = dbh or Database.new('system')
			local settings = Settings.new(db, domain_name, domain_uuid)
			local message_max_length = settings:get('voicemail', 'message_max_length', 'numeric') or 300;
			local message_silence_threshold = settings:get('voicemail', 'message_silence_threshold', 'numeric') or 200;
			local message_silence_seconds = settings:get('voicemail', 'message_silence_seconds', 'numeric') or 3;
			local email_queue_enabled = settings:get('email_queue', 'enabled', 'boolean') or "false";

		--record your message at the tone press any key or stop talking to end the recording
			if (skip_instructions == "true") then
				--skip the instructions
			else
				if (dtmf_digits and string.len(dtmf_digits) == 0) then
					dtmf_digits = session:playAndGetDigits(0, 1, 1, 500, "#", "phrase:voicemail_record_message", "", "\\d+");
				end
			end
		
		--voicemail ivr options
			if (session:ready()) then
				if (dtmf_digits == nil) then
					dtmf_digits = session:getDigits(max_digits, "#", 1000);
				else
					dtmf_digits = dtmf_digits .. session:getDigits(max_digits, "#", 1000);
				end
			end
			if (dtmf_digits) then
				if (string.len(dtmf_digits) > 0) then
					if (session:ready()) then
						if (direct_dial["enabled"] == "true") then
							if (string.len(dtmf_digits) < max_digits) then
								dtmf_digits = dtmf_digits .. session:getDigits(direct_dial["max_digits"], "#", 3000);
							end
						end
					end
					if (session:ready()) then
						freeswitch.consoleLog("notice", "[voicemail] dtmf_digits: " .. string.sub(dtmf_digits, 0, 1) .. "\n");
						if (dtmf_digits == "*") then
							if (remote_access == "true") then
								--check the voicemail password
									check_password(voicemail_id, password_tries);
								--send to the main menu
									timeouts = 0;
									main_menu();
							else
								--remote access is false
								freeswitch.consoleLog("notice", "[voicemail] remote access is disabled.\n");
								session:hangup();
							end
						elseif (string.sub(dtmf_digits, 0, 1) == "*") then
							--do not allow dialing numbers prefixed with *
							session:hangup();
						else
							--get the voicemail options
								local sql = [[SELECT * FROM v_voicemail_options WHERE voicemail_uuid = :voicemail_uuid ORDER BY voicemail_option_order asc ]];
								local params = {voicemail_uuid = voicemail_uuid};
								if (debug["sql"]) then
									freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
								end
								count = 0;
								dbh:query(sql, params, function(row)
									--check for matching options
										if (tonumber(row.voicemail_option_digits) ~= nil) then
											row.voicemail_option_digits = "^"..row.voicemail_option_digits.."$";
										end
										if (api:execute("regex", "m:~"..dtmf_digits.."~"..row.voicemail_option_digits) == "true") then
											if (row.voicemail_option_action == "menu-exec-app") then
												--get the action and data
													pos = string.find(row.voicemail_option_param, " ", 0, true);
													action = string.sub( row.voicemail_option_param, 0, pos-1);
													data = string.sub( row.voicemail_option_param, pos+1);

												--check if the option uses a regex
													regex = string.find(row.voicemail_option_digits, "(", 0, true);
													if (regex) then
														--get the regex result
															result = trim(api:execute("regex", "m:~"..digits.."~"..row.voicemail_option_digits.."~$1"));
															if (debug["regex"]) then
																freeswitch.consoleLog("notice", "[voicemail] regex m:~"..digits.."~"..row.voicemail_option_digits.."~$1\n");
																freeswitch.consoleLog("notice", "[voicemail] result: "..result.."\n");
															end

														--replace the $1 and the domain name
															data = data:gsub("$1", result);
															data = data:gsub("${domain_name}", domain_name);
													end --if regex
											end --if menu-exex-app
										end --if regex match

									--execute
										if (action) then
											if (string.len(action) > 0) then
												--send to the log
													if (debug["action"]) then
														freeswitch.consoleLog("notice", "[voicemail] action: " .. action .. " data: ".. data .. "\n");
													end
												--run the action
													session:execute(action, data);
											end
										end

									--clear the variables
										action = "";
										data = "";

									--inrement the option count
										count = count + 1;
								end); --end results

							--direct dial
								if (session:ready()) then
									if (direct_dial["enabled"] == "true" and count == 0) then
										if (string.len(dtmf_digits) < max_digits) then
											dtmf_digits = dtmf_digits .. session:getDigits(direct_dial["max_digits"], "#", 5000);
											session:transfer(dtmf_digits.." XML "..context);
										end
									end
								end
						end
					end
				end
			end

		--play the beep
			dtmf_digits = '';
			session:execute("playback","silence_stream://200");
			session:streamFile("tone_stream://L=1;%(1000, 0, 640)");

		--start epoch
			start_epoch = os.time();

		--save the recording
			-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
			if (storage_path == "http_cache") then
				result = session:recordFile(storage_path.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext, message_max_length, message_silence_threshold, message_silence_seconds);
			else
				if (vm_message_ext == "mp3") then
					shout_exists = trim(api:execute("module_exists", "mod_shout"));
					if (shout_exists == "true" and transcribe_enabled == "false") or (shout_exists == "true" and transcribe_enabled == "true" and voicemail_transcription_enabled ~= "true") then
						freeswitch.consoleLog("notice", "using mod_shout for mp3 encoding\n");
						--record in mp3 directly, no transcription
							result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3", message_max_length, message_silence_threshold, message_silence_seconds);
					elseif (shout_exists == "true" and transcribe_enabled == "true" and voicemail_transcription_enabled == "true" and transcribe_provider == "watson") then
						--record in mp3 directly with mp3 transcription if watson selected
							result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3", message_max_length, message_silence_threshold, message_silence_seconds);
					else
						--create initial wav recording
							result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".wav", message_max_length, message_silence_threshold, message_silence_seconds);
						--use lame to encode, if available
							if (file_exists("/usr/bin/lame")) then
								freeswitch.consoleLog("notice", "using lame for mp3 encoding\n");
								--convert the wav to an mp3 (lame required)
									resample = "/usr/bin/lame -b 32 --resample 8 -m s "..voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".wav "..voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3";
									session:execute("system", resample);
								--delete the wav file, if mp3 exists
									if (file_exists(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3")) then
										os.remove(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".wav");
									else
										vm_message_ext = "wav";
									end
							else
								freeswitch.consoleLog("notice", "neither mod_shout or lame found, defaulting to wav\n");
								vm_message_ext = "wav";
							end
					end
				else
					result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext, message_max_length, message_silence_threshold, message_silence_seconds);
				end
			end

		--stop epoch
			stop_epoch = os.time();

		--calculate the message length
			message_length = stop_epoch - start_epoch;
			message_length_formatted = format_seconds(message_length);

		--if the recording is below the minimal length then re-record the message
			if (message_length > 2) then
				session:setVariable("voicemail_message_seconds", message_length);
			else
				if (session:ready()) then
					--your recording is below the minimal acceptable length, please try again
						dtmf_digits = '';
						session:execute("playback", "phrase:voicemail_ack:too-small");
						session:execute("sleep", "500");
					--record your message at the tone
						timeouts = timeouts + 1;
						if (timeouts < max_timeouts) then
							record_message();
						else
							timeouts = 0;
							record_menu("message", voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
						end
				end
			end

		--instructions press 1 to listen to the recording, press 2 to save the recording, press 3 to re-record
			if (session:ready()) then
				if (skip_instructions == "true") then
					--save the message
						dtmf_digits = '';
						session:execute("playback", "phrase:voicemail_ack:saved");
						session:execute("sleep", "300");
						session:execute("playback", "phrase:voicemail_goodbye");
					--hangup the call
						session:hangup();
				else
					timeouts = 0;
					record_menu("message", voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
				end
			end
	end
