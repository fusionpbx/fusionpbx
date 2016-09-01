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

		--connect to the database
			local db = dbh or Database.new('system');

		--load libraries
			local Database = require "resources.functions.database";
			local Settings = require "resources.functions.lazy_settings";

		--get the settings.
			local settings = Settings.new(db, domain_name, domain_uuid)
			--local max_len_seconds = settings:get('voicemail', 'forward_add_intro', 'boolean') or 300;

		--request whether to add the intro
			--To add an introduction to this message press 1
			add_intro_id = macro(session, "forward_add_intro", 20, 5000, '');
			if (add_intro_id == '1') then

				--record your message at the tone press any key or stop talking to end the recording
					if (session:ready()) then
						result = macro(session, "record_message", 0, 5000, '');
					end

				--set the file full path
					message_location = voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext;
					message_intro_location = voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext;

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

				--get the original voicemail message from the database and save it to the message location
					if (storage_type == "base64") then
							if (session:ready()) then
								sql = [[SELECT * FROM v_voicemail_messages
									WHERE domain_uuid = ']] .. domain_uuid ..[['
									AND voicemail_message_uuid = ']].. uuid.. [[' ]];
								if (debug["sql"]) then
									freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
								end
								status = dbh:query(sql, function(row)
									--add functions
										require "resources.functions.base64";

									--set the voicemail message path
										mkdir(voicemail_dir.."/"..voicemail_id);

									--save the recording to the file system
										if (string.len(row["message_base64"]) > 32) then
											local file = io.open(message_location, "w");
											file:write(base64.decode(row["message_base64"]));
											file:close();
										end
								end);
							end
					end

				--merge the intro and the voicemail recording
					cmd = "sox "..message_intro_location.." "..message_location;
					os.execute(cmd);

				--remove the intro file after it has been merged
					os.remove(message_intro_location);

				--save the merged file into the database as base64
					if (storage_type == "base64") then
						--get the content of the file
							local f = io.open(voicemail_name_location, "rb");
							local file_content = f:read("*all");
							f:close();

						--save the merged file as base64
							local sql = {}
							sql = [[UPDATE SET v_voicemail_messages
									SET message_base64 = ']].. base64.encode(file_content) ..[[' 
									WHERE domain_uuid = ']] .. domain_uuid ..[['
									AND voicemail_message_uuid = ']].. uuid.. [[' ]];
							sql = table.concat(sql, "\n");
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
							end
					end
		end

	end
