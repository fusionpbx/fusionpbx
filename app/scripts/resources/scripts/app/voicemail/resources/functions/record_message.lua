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

	local function transcribe(file_path,settings,start_epoch)
		--transcription variables
		if (os.time() - start_epoch > 2) then
			local transcribe_provider = settings:get('voicemail', 'transcribe_provider', 'text') or '';
			transcribe_language = settings:get('voicemail', 'transcribe_language', 'text') or 'en-US';

			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] transcribe_provider: " .. transcribe_provider .. "\n");
				freeswitch.consoleLog("notice", "[voicemail] transcribe_language: " .. transcribe_language .. "\n");
			end

			if (transcribe_provider == "microsoft") then
			local api_key1 = settings:get('voicemail', 'microsoft_key1', 'text') or '';
			local api_key2 = settings:get('voicemail', 'microsoft_key2', 'text') or '';
			if (api_key1 ~= '' and api_key2 ~= '') then
				access_token_cmd = "curl -X POST \"https://api.cognitive.microsoft.com/sts/v1.0/issueToken\" -H \"Content-type: application/x-www-form-urlencoded\" -H \"Content-Length: 0\" -H \"Ocp-Apim-Subscription-Key: "..api_key1.."\""
				local handle = io.popen(access_token_cmd);
				local access_token_result = handle:read("*a");
				handle:close();
				if (debug["info"]) then
					freeswitch.consoleLog("notice", "[voicemail] CMD: " .. access_token_cmd .. "\n");
					freeswitch.consoleLog("notice", "[voicemail] RESULT: " .. access_token_result .. "\n");
				end
				--Access token request can fail
				if (access_token_result == '') then
					freeswitch.consoleLog("notice", "[voicemail] ACCESS TOKEN: (null) \n");
					return ''
					end
					transcribe_cmd = "curl -X POST \"https://speech.platform.bing.com/recognize?scenarios=smd&appid=D4D52672-91D7-4C74-8AD8-42B1D98141A5&locale=" .. transcribe_language .. "&device.os=Freeswitch&version=3.0&format=json&instanceid=" .. gen_uuid() .. "&requestid=" .. gen_uuid() .. "\" -H 'Authorization: Bearer " .. access_token_result .. "' -H 'Content-type: audio/wav; codec=\"audio/pcm\"; samplerate=8000; trustsourcerate=false' --data-binary @"..file_path
					local handle = io.popen(transcribe_cmd);
					local transcribe_result = handle:read("*a");
					handle:close();
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] CMD: " .. transcribe_cmd .. "\n");
						freeswitch.consoleLog("notice", "[voicemail] RESULT: " .. transcribe_result .. "\n");
					end
					--Trancribe request can fail
					if (transcribe_result == '') then
						freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
						return ''
					else
						status, transcribe_json = pcall(JSON.decode, transcribe_result);
						if not status then
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] error decoding bing json\n");
						end
						return '';
						end
					end

					if (debug["info"]) then
						if (transcribe_json["results"][1]["name"] == nil) then
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
						else
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: " .. transcribe_json["results"][1]["name"] .. "\n");
						end
						if (transcribe_json["results"][1]["confidence"] == nil) then
							freeswitch.consoleLog("notice", "[voicemail] CONFIDENCE: (null) \n");
						else
							freeswitch.consoleLog("notice", "[voicemail] CONFIDENCE: " .. transcribe_json["results"][1]["confidence"] .. "\n");
						end
					end

					transcription = transcribe_json["results"][1]["name"];
					transcription = transcription:gsub("<profanity>.*<%/profanity>","...");
					confidence = transcribe_json["results"][1]["confidence"];
					return transcription;
				end
			end

			if (transcribe_provider == "azure") then
				local api_key1 = settings:get('voicemail', 'azure_key1', 'text') or '';
				local api_server_region = settings:get('voicemail', 'azure_server_region', 'text') or '';
				if (api_server_region ~= '') then
					api_server_region = api_server_region .. ".";
				else
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] azure_server_region default setting must be set\n");
					end
					return '';
				end
				if (api_key1 ~= '') then
					-- search in memcache first, azure documentation claims that the access token is valid for 10 minutes
					local cache = require "resources.functions.cache";
					local key = "app:voicemail:azure:access_token";
					local access_token_result = cache.get(key)

					if access_token_result then
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] Azure access_token recovered from memcached\n");
						end
					else
						access_token_cmd = "curl -X POST \"https://"..api_server_region.."api.cognitive.microsoft.com/sts/v1.0/issueToken\" -H \"Content-type: application/x-www-form-urlencoded\" -H \"Content-Length: 0\" -H \"Ocp-Apim-Subscription-Key: "..api_key1.."\"";
						local handle = io.popen(access_token_cmd);
						access_token_result = handle:read("*a");
						handle:close();
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] CMD: " .. access_token_cmd .. "\n");
							freeswitch.consoleLog("notice", "[voicemail] ACCESS TOKEN: " .. access_token_result .. "\n");
						end
						--Access token request can fail
						if (access_token_result == '') then
							if (debug["info"]) then
								freeswitch.consoleLog("notice", "[voicemail] ACCESS TOKEN: (null) \n");
							end
							return ''
						end

						--Azure returns JSON when it has to report an error
						if (string.sub(access_token_result, 1, 1) == '{') then
							if (debug["info"]) then
								freeswitch.consoleLog("notice", "[voicemail] ERROR STRING: ".. access_token_result .. "\n");
							end
							return ''
						end

						cache.set(key, access_token_result, 120);
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] Azure access_token saved into memcached: " .. access_token_result .. "\n");
						end
					end

					transcribe_cmd = "curl -X POST \"https://"..api_server_region.."stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1?language=".. transcribe_language .."&format=detailed\" -H 'Authorization: Bearer " .. access_token_result .. "' -H 'Content-type: audio/wav; codec=\"audio/pcm\"; samplerate=8000; trustsourcerate=false' --data-binary @"..file_path
					local handle = io.popen(transcribe_cmd);
					local transcribe_result = handle:read("*a");
					handle:close();
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] CMD: " .. transcribe_cmd .. "\n");
						freeswitch.consoleLog("notice", "[voicemail] RESULT: " .. transcribe_result .. "\n");
					end
					--Trancribe request can fail
					if (transcribe_result == '') then
						freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
						return ''
					end
					local transcribe_json = JSON.decode(transcribe_result);
					if (debug["info"]) then
						if (transcribe_json["NBest"][1]["Display"] == nil) then
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
						else
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: " .. transcribe_json["NBest"][1]["Display"] .. "\n");
						end
						if (transcribe_json["NBest"][1]["Confidence"] == nil) then
							freeswitch.consoleLog("notice", "[voicemail] CONFIDENCE: (null) \n");
						else
							freeswitch.consoleLog("notice", "[voicemail] CONFIDENCE: " .. transcribe_json["NBest"][1]["Confidence"] .. "\n");
						end
					end

					transcription = transcribe_json["NBest"][1]["Display"];
					confidence = transcribe_json["NBest"][1]["Confidence"];
					return transcription;
				end
			end

			if (transcribe_provider == "watson") then
				local api_key = settings:get('voicemail', 'watson_key', 'text') or '';
				local transcription_server = settings:get('voicemail', 'watson_url', 'text') or '';
				if (api_key ~= '') then
					if (vm_message_ext == "mp3") then
						transcribe_cmd = [[ curl -X POST -u "apikey:]]..api_key..[[" --header "Content-type: audio/mp3" --data-binary @]]..file_path..[[ "]]..transcription_server..[[" ]]
					else
						transcribe_cmd = [[ curl -X POST -u "apikey:]]..api_key..[[" --header "Content-type: audio/wav" --data-binary @]]..file_path..[[ "]]..transcription_server..[[" ]]
					end
					local handle = io.popen(transcribe_cmd);
					local transcribe_result = handle:read("*a");
					transcribe_result = transcribe_result:gsub('%%HESITATION ', '');
					handle:close();
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] CMD: " .. transcribe_cmd .. "\n");
						freeswitch.consoleLog("notice", "[voicemail] RESULT: " .. transcribe_result .. "\n");
					end

					--Trancribe request can fail
					if (transcribe_result == '') then
						freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
						return ''
					else
						status, transcribe_json = pcall(JSON.decode, transcribe_result);
						if not status then
							if (debug["info"]) then
								freeswitch.consoleLog("notice", "[voicemail] error decoding watson json\n");
							end
							return '';
						end 
					end

					if (transcribe_json["results"] ~= nil) then
						--Transcription	
						if (transcribe_json["results"][1]["alternatives"][1]["transcript"] ~= nil) then
							transcription = '';
							for key, row in pairs(transcribe_json["results"]) do 
								transcription = transcription .. row["alternatives"][1]["transcript"];
							end
							if (debug["info"]) then
								freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: " .. transcription .. "\n");
							end
						else
							if (debug["info"]) then
								freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
							end
							return '';
						end
						--Confidence
						if (transcribe_json["results"][1]["alternatives"][1]["confidence"]) then
							if (debug["info"]) then
								freeswitch.consoleLog("notice", "[voicemail] CONFIDENCE: " .. transcribe_json["results"][1]["alternatives"][1]["confidence"] .. "\n");
							end
							confidence = transcribe_json["results"][1]["alternatives"][1]["confidence"];
						else
							if (debug["info"]) then
								freeswitch.consoleLog("notice", "[voicemail] CONFIDENCE: (null) \n");
							end
						end
						return transcription;
					else
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: json error \n");
						end
						return '';
					end
				end
			end

			if (transcribe_provider == "google") then
				local api_key = settings:get('voicemail', 'google_key', 'text') or '';
				local transcription_server = settings:get('voicemail', 'google_url', 'text') or '';
				transcribe_alternate_language = settings:get('voicemail', 'transcribe_alternate_language', 'text') or 'es-US';
				if (api_key ~= '') then
					transcribe_cmd = [[sox ]]..file_path..[[ ]]..file_path..[[.flac trim 0 00:59 && echo "{ 'config': { 'languageCode': ']]..transcribe_language..[[', 'enableWordTimeOffsets': false , 'enableAutomaticPunctuation': true , 'alternativeLanguageCodes': ']]..transcribe_alternate_language..[[' }, 'audio': { 'content': '`base64 -w 0 ]]..file_path..[[.flac`' } }" | curl -X POST -H "Content-Type: application/json" -d @- "]]..transcription_server..[[:recognize?key=]]..api_key..[[" && rm -f ]]..file_path..[[.flac]]
				end

				local handle = io.popen(transcribe_cmd);
				local transcribe_result = handle:read("*a");
				transcribe_result = transcribe_result:gsub('%%HESITATION ', '');
				handle:close();
				if (debug["info"]) then
					freeswitch.consoleLog("notice", "[voicemail] CMD: " .. transcribe_cmd .. "\n");
					freeswitch.consoleLog("notice", "[voicemail] RESULT: " .. transcribe_result .. "\n");
				end

				--Trancribe request can fail
				if (transcribe_result == '') then
					freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
					return ''
				else
					status, transcribe_json = pcall(JSON.decode, transcribe_result);
					if not status then
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] error decoding google json\n");
						end
						return '';
					end 
				end
				if (transcribe_json["results"] ~= nil) then
					--Transcription	
					if (transcribe_json["results"][1]["alternatives"][1]["transcript"] ~= nil) then
						transcription = '';
						for key, row in pairs(transcribe_json["results"]) do 
							transcription = transcription .. row["alternatives"][1]["transcript"];
						end
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: " .. transcription .. "\n");
						end
					else
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
						end
						return '';
					end
					--Confidence
					if (transcribe_json["results"][1]["alternatives"][1]["confidence"]) then
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] CONFIDENCE: " .. transcribe_json["results"][1]["alternatives"][1]["confidence"] .. "\n");
						end
						confidence = transcribe_json["results"][1]["alternatives"][1]["confidence"];
					else
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail] CONFIDENCE: (null) \n");
						end
					end
					return transcription;
				else
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: json error \n");
					end
					return '';
				end
			end

			if (transcribe_provider == "custom") then
				local transcription_server = settings:get('voicemail', 'transcription_server', 'text') or '';
				local api_key = settings:get('voicemail', 'api_key', 'text') or '';
				local json_enabled = settings:get('voicemail', 'json_enabled', 'boolean') or "false";
				if (transcription_server ~= '') then
					transcribe_cmd = "curl -X POST " .. transcription_server .. " -H 'Authorization: Bearer " .. api_key .. "' -F file=@"..file_path
					local handle = io.popen(transcribe_cmd);
					local transcribe_result = esc(handle:read("*a"));
					handle:close();

					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] CMD: " .. transcribe_cmd .. "\n");
						freeswitch.consoleLog("notice", "[voicemail] RESULT: " .. transcribe_result .. "\n");
					end
					--Trancribe request can fail
					if (transcribe_result == '') then
						freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: (null) \n");
						return ''
					end
					if (json_enabled == "true") then
						local transcribe_json = JSON.decode(transcribe_result);
						if (transcribe_json["message"] == nil) then
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: " .. transcribe_result .. "\n");
							transcribe_result = '';
						end
						if (transcribe_json["error"] ~= nil) then
							freeswitch.consoleLog("notice", "[voicemail] TRANSCRIPTION: " .. transcribe_result .. "\n");
							transcribe_result = '';
						end
						transcribe_result = transcribe_json["message"];
					end
					return transcribe_result;
				end
			end
		else
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] message too short for transcription.\n");
			end
		end
		return '';
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
			local transcribe_provider = settings:get('voicemail', 'transcribe_provider', 'text') or '';
			transcribe_enabled = settings:get('voicemail', 'transcribe_enabled', 'boolean') or "false";

		--debug information
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] transcribe_enabled: " .. transcribe_enabled .. "\n");
				freeswitch.consoleLog("notice", "[voicemail] voicemail_transcription_enabled: " .. voicemail_transcription_enabled .. "\n");
			end

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

		--if using the email queue disable inline transcription
			if (email_queue_enabled == 'true') then
				transcribe_enabled = 'false';
			end

		--save the recording
			-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
			if (storage_path == "http_cache") then
				result = session:recordFile(storage_path.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext, message_max_length, message_silence_threshold, message_silence_seconds);
			else
				mkdir(voicemail_dir.."/"..voicemail_id);
				if (vm_message_ext == "mp3") then
					shout_exists = trim(api:execute("module_exists", "mod_shout"));
					if (shout_exists == "true" and transcribe_enabled == "false") or (shout_exists == "true" and transcribe_enabled == "true" and voicemail_transcription_enabled ~= "true") then
						freeswitch.consoleLog("notice", "using mod_shout for mp3 encoding\n");
						--record in mp3 directly, no transcription
							result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3", message_max_length, message_silence_threshold, message_silence_seconds);
					elseif (shout_exists == "true" and transcribe_enabled == "true" and voicemail_transcription_enabled == "true" and transcribe_provider == "watson") then
						--record in mp3 directly with mp3 transcription if watson selected
							result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3", message_max_length, message_silence_threshold, message_silence_seconds);
							transcription = transcribe(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3",settings,start_epoch);
					else
						--create initial wav recording
							result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".wav", message_max_length, message_silence_threshold, message_silence_seconds);
							if (transcribe_enabled == "true" and voicemail_transcription_enabled == "true") then
								transcription = transcribe(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".wav",settings,start_epoch);
							end
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
					if (transcribe_enabled == "true" and voicemail_transcription_enabled == "true") then
						transcription = transcribe(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext,settings,start_epoch);
					end
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
