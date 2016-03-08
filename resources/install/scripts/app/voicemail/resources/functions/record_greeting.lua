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

--define a function to record the greeting
	function record_greeting(greeting_id)

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--choose a greeting between 1 and 9
			if (greeting_id == nil) then
				if (session:ready()) then
					dtmf_digits = '';
					greeting_id = macro(session, "choose_greeting_choose", 1, 5000, '');
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
						macro(session, "record_greeting", 1, 100, '');
					end

				--store the voicemail greeting
					if (storage_type == "http_cache") then
						freeswitch.consoleLog("notice", "[voicemail] ".. storage_type .. " ".. storage_path .."\n");
						storage_path = storage_path:gsub("${domain_name}", domain_name);
						session:execute("record", storage_path .."/"..recording_name);
					else
						--prepare to record the greeting
							if (session:ready()) then
								max_len_seconds = 30;
								silence_seconds = 5;
								mkdir(voicemail_dir.."/"..voicemail_id);
								-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
								result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".tmp.wav", max_len_seconds, record_silence_threshold, silence_seconds);
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
						record_menu("greeting", voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".tmp.wav", greeting_id);
					end
			else
				--invalid greeting_id
					if (session:ready()) then
						dtmf_digits = '';
						macro(session, "choose_greeting_fail", 1, 100, '');
					end

				--send back to choose the greeting
					if (session:ready()) then
						timeouts = timeouts + 1;
						if (timeouts < max_timeouts) then
							record_greeting();
						else
							timeouts = 0;
							advanced();
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