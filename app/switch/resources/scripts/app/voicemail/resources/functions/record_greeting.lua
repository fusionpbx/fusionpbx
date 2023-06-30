--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
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

--load libraries
	local Database = require "resources.functions.database"
	local Settings = require "resources.functions.lazy_settings"

--define a function to record the greeting
	function record_greeting(greeting_id, menu)

		--setup the database connection
			local db = dbh or Database.new('system')
		
		--get the voicemail settings
			local settings = Settings.new(db, domain_name, domain_uuid)

		--set the maximum greeting length
			local greeting_max_length = settings:get('voicemail', 'greeting_max_length', 'numeric') or 90;
			local greeting_silence_threshold = settings:get('voicemail', 'greeting_silence_threshold', 'numeric') or 200;
			local greeting_silence_seconds = settings:get('voicemail', 'greeting_silence_seconds', 'numeric') or 3;

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--disable appending to the recording
			session:setVariable("record_append", "false");

		--choose a greeting between 1 and 9
			if (greeting_id == nil) then
				if (session:ready()) then
					dtmf_digits = '';
					greeting_id = session:playAndGetDigits(1, 1, max_tries, 5000, "#", "phrase:voicemail_choose_greeting", "", "\\d+");
					freeswitch.consoleLog("notice", "[voicemail] greeting_id: " .. greeting_id .. "\n");
				end
			end

		--validate the greeting_id
			if (greeting_id == "1"
				or greeting_id == "2"
				or greeting_id == "3"
				or greeting_id == "4"
				or greeting_id == "5"
				or greeting_id == "6"
				or greeting_id == "7"
				or greeting_id == "8"
				or greeting_id == "9") then
				--record your greeting at the tone press any key or stop talking to end the recording
					if (session:ready()) then
						dtmf_digits = '';
						session:execute("playback", "phrase:voicemail_record_greeting");
						session:execute("sleep", "1000");
						session:streamFile("tone_stream://L=1;%(1000, 0, 640)");
					end

				--store the voicemail greeting
					if (storage_type == "http_cache") then
						freeswitch.consoleLog("notice", "[voicemail] ".. storage_type .. " ".. storage_path .."\n");
						storage_path = storage_path:gsub("${domain_name}", domain_name);
						session:execute("record", storage_path .."/"..recording_name);
					else
						--prepare to record the greeting
							if (session:ready()) then
								silence_seconds = 5;
								mkdir(voicemail_dir.."/"..voicemail_id);
								-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_seconds)
								result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".tmp.wav", greeting_max_length, greeting_silence_threshold, greeting_silence_seconds);
								--session:execute("record", voicemail_dir.."/"..uuid.." 180 200");
							end
					end

				--play the greeting
					--if (session:ready()) then
					--	if (file_exists(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav")) then
					--		session:streamFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
					--	end
					--end

				--option to play, save, and re-record the greeting
					if (session:ready()) then
						timeouts = 0;
						record_menu("greeting", voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".tmp.wav", greeting_id, menu);
					end
			else
				--invalid greeting_id
					if (session:ready()) then
						dtmf_digits = '';
						session:execute("playback", "phrase:voicemail_choose_greeting_fail");
						session:execute("sleep", "500");
					end

				--send back to choose the greeting
					if (session:ready()) then
						timeouts = timeouts + 1;
						if (timeouts < max_timeouts) then
							record_greeting(nil, menu);
						else
							timeouts = 0;
							if (menu == "tutorial") then
								tutorial("finish")
							end
							if (menu == "advanced") then
								advanced();
							else
								advanced();	
							end
						end
					end
			end

		--clean up any tmp greeting files
			for gid = 1, 9, 1 do
				if (file_exists(voicemail_dir.."/"..voicemail_id.."/greeting_"..gid..".tmp.wav")) then
					os.remove(voicemail_dir.."/"..voicemail_id.."/greeting_"..gid..".tmp.wav");
				end
			end

	end
