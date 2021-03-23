--	Part of FusionPBX
--	Copyright (C) 2016 Mark J Crane <markjcrane@fusionpbx.com>
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

--define a function to forward a message to an extension
	function forward_add_intro(voicemail_id, uuid)

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--request whether to add the intro
			--To add an introduction to this message press 1
			add_intro_id = session:playAndGetDigits(1, 1, 3, 5000, "#*", "phrase:voicemail_forward_prepend:1:2", "phrase:invalid_entry", "\\d+");
			freeswitch.consoleLog("notice", "[voicemail][forward add intro] "..add_intro_id.."\n");
			if (add_intro_id == '1') then

				--load libraries
					local Database = require "resources.functions.database";
					local Settings = require "resources.functions.lazy_settings";

				--connect to the database
					local db = dbh or Database.new('system');

				--get the settings.
					local settings = Settings.new(db, domain_name, domain_uuid);
					local max_len_seconds = settings:get('voicemail', 'max_len_seconds', 'boolean') or 300;

				--record your message at the tone press any key or stop talking to end the recording
					if (session:ready()) then
						session:execute("playback", "phrase:voicemail_record_greeting");
						session:execute("sleep", "1000");
						session:streamFile("tone_stream://L=1;%(1000, 0, 640)");
					end

				--set the file full path
					message_location = voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext;
					message_intro_location = voicemail_dir.."/"..voicemail_id.."/intro_"..uuid.."."..vm_message_ext;

				--record the message introduction
					-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
					silence_seconds = 5;
					if (storage_path == "http_cache") then
						result = session:recordFile(message_intro_location, max_len_seconds, record_silence_threshold, silence_seconds);
					else
						mkdir(voicemail_dir.."/"..voicemail_id);
						if (vm_message_ext == "mp3") then
							shout_exists = trim(api:execute("module_exists", "mod_shout"));
							if (shout_exists == "true") then
								freeswitch.consoleLog("notice", "using mod_shout for mp3 encoding\n");
								--record in mp3 directly
									result = session:recordFile(message_intro_location, max_len_seconds, record_silence_threshold, silence_seconds);
							else
								--create initial wav recording
									result = session:recordFile(message_intro_location, max_len_seconds, record_silence_threshold, silence_seconds);
								--use lame to encode, if available
									if (file_exists("/usr/bin/lame")) then
										freeswitch.consoleLog("notice", "using lame for mp3 encoding\n");
										--convert the wav to an mp3 (lame required)
											resample = "/usr/bin/lame -b 32 --resample 8 -m s "..voicemail_dir.."/"..voicemail_id.."/intro_"..uuid..".wav "..message_intro_location;
											session:execute("system", resample);
										--delete the wav file, if mp3 exists
											if (file_exists(message_intro_location)) then
												os.remove(voicemail_dir.."/"..voicemail_id.."/intro_"..uuid..".wav");
											else
												vm_message_ext = "wav";
											end
									else
										freeswitch.consoleLog("notice", "neither mod_shout or lame found, defaulting to wav\n");
										vm_message_ext = "wav";
									end
							end
						else
							result = session:recordFile(message_intro_location, max_len_seconds, record_silence_threshold, silence_seconds);
						end
					end

				--save the merged file into the database as base64
					if (storage_type == "base64") then
							local file = require "resources.functions.file"

						--get the content of the file
							local file_content = assert(file.read_base64(message_intro_location));

						--save the merged file as base64
							local sql = [[UPDATE SET v_voicemail_messages
									SET message_intro_base64 = :file_content 
									WHERE domain_uuid = :domain_uuid
									AND voicemail_message_uuid = :uuid]];
							local params = {file_content = file_content, domain_uuid = domain_uuid, uuid = uuid};

							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
							end

							local dbh = Database.new('system', 'base64')
							dbh:query(sql, params)
							dbh:release()
					end
		end

	end
