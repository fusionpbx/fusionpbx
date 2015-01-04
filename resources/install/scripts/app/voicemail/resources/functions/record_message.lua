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

--save the recording
	function record_message()

		--record your message at the tone press any key or stop talking to end the recording
			if (skip_instructions == "true") then
				--skip the instructions
			else
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "record_message", 1, 100);
				end
			end

		--direct dial
			if (dtmf_digits) then
				if (string.len(dtmf_digits) > 0) then
					if (session:ready()) then
						if (direct_dial["enabled"] == "true") then
							if (string.len(dtmf_digits) < max_digits) then
								dtmf_digits = dtmf_digits .. session:getDigits(direct_dial["max_digits"], "#", 5000);
							end
						end
					end
					if (session:ready()) then
						freeswitch.consoleLog("notice", "[voicemail] dtmf_digits: " .. string.sub(dtmf_digits, 0, 1) .. "\n");
						if (dtmf_digits == "*") then
							--check the voicemail password
								check_password(voicemail_id, password_tries);
							--send to the main menu
								timeouts = 0;
								main_menu();
						elseif (string.sub(dtmf_digits, 0, 1) == "*") then
							--do not allow dialing numbers prefixed with *
							session:hangup();
						else
							session:transfer(dtmf_digits, "XML", context);
						end
					end
				end
			end

		--play the beep
			dtmf_digits = '';
			result = macro(session, "record_beep", 1, 100);

		--start epoch
			start_epoch = os.time();

		--save the recording
			-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
			max_len_seconds = 300;
			silence_threshold = 30;
			silence_seconds = 5;
			mkdir(voicemail_dir.."/"..voicemail_id);
			if (vm_message_ext == "mp3" and trim(api:execute("module_exists", "mod_shout")) == "false") then
				--make the recording
					--session:execute("record", "vlc://#standard{access=file,mux=mp3,dst="..voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext.."}");
					result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".wav", max_len_seconds, silence_threshold, silence_seconds);
				--convert the wav to an mp3
					--apt-get install lame
					resample = "/usr/bin/lame -b 32 --resample 8 -m s "..voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".wav "..voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3";
					session:execute("system", resample);
				--delete the wav file
					if (file_exists(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".mp3")) then
						os.remove(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid..".wav");
					end
			else
				result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext, max_len_seconds, silence_threshold, silence_seconds);
			end
			--session:execute("record", voicemail_dir.."/"..uuid.." 180 200");

		--stop epoch
			stop_epoch = os.time();

		--calculate the message length
			message_length = stop_epoch - start_epoch;
			message_length_formatted = format_seconds(message_length);

		--if the recording is below the minimal length then re-record the message
			if (message_length > 2) then
				--continue
			else
				if (session:ready()) then
					--your recording is below the minimal acceptable length, please try again
						dtmf_digits = '';
						macro(session, "too_small", 1, 100);
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
						macro(session, "message_saved", 1, 100, '');
						macro(session, "goodbye", 1, 100, '');
					--hangup the call
						session:hangup();
				else
					timeouts = 0;
					record_menu("message", voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
				end
			end
	end
