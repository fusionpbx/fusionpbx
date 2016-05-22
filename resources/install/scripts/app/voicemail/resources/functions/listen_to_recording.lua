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

--define function to listen to the recording
	function listen_to_recording (message_number, uuid, created_epoch, caller_id_name, caller_id_number)

		--set default values
			dtmf_digits = '';
			max_digits = 1;
		--flush dtmf digits from the input buffer
			session:flushDigits();
		--set the callback function
			if (session:ready()) then
				session:setVariable("playback_terminators", "#");
				session:setInputCallback("on_dtmf", "");
			end
		--set the display
			if (session:ready()) then
				reply = api:executeString("uuid_display "..session:get_uuid().." "..caller_id_number);
			end
		--say the message number
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "message_number", 1, 100, '');
				end
			end
		--say the number
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					session:say(message_number, default_language, "number", "pronounced");
				end
			end
		--say the caller id number
			if (session:ready() and caller_id_number ~= nil) then
				if (vm_say_caller_id_number ~= nil) then
					if (vm_say_caller_id_number == "true") then
						session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-message_from.wav");
						session:say(caller_id_number, default_language, "name_spelled", "iterated");
					end
				end
			end
		--say the message date
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					if (current_time_zone ~= nil) then
						session:execute("set", "timezone="..current_time_zone.."");
					end
					session:say(created_epoch, default_language, "current_date_time", "pronounced");
				end
			end
		--get the recordings from the database
			if (storage_type == "base64") then
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
						message_location = voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext;

					--save the recording to the file system
						if (string.len(row["message_base64"]) > 32) then
							local file = io.open(message_location, "w");
							file:write(base64.decode(row["message_base64"]));
							file:close();
						end
				end);
			elseif (storage_type == "http_cache") then
				message_location = storage_path.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext;
			end

		--play the message
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					stream_seek = true;
					if (storage_type == "http_cache") then
						message_location = storage_path.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext;
						session:streamFile(storage_path.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
					else
						if (vm_message_ext == "mp3") then
							if (api:executeString("module_exists mod_vlc") == "true") then
								session:streamFile("vlc://"..voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
							else
								session:streamFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
							end
						else
							session:streamFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
						end
					end
					stream_seek = false;
					session:streamFile("silence_stream://1000");
				end
			end
		--remove the voicemail message
			if (storage_type == "base64") then
				os.remove(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
			end
		--to listen to the recording press 1
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "listen_to_recording", 1, 100, '');
				end
			end
		--to save the recording press 2
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "save_recording", 1, 100, '');
				end
			end
		--to return the call now press 5
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "return_call", 1, 100, '');
				end
			end
		--to delete the recording press 7
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "delete_recording", 1, 100, '');
				end
			end
		--to forward this message press 8
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "to_forward_message", 1, 100, '');
				end
			end
		--to forward this recording to your email press 9
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "forward_to_email", 1, 3000, '');
				end
			end
		--wait for more digits
			--if (session:ready()) then
			--	if (string.len(dtmf_digits) == 0) then
			--		dtmf_digits = session:getDigits(max_digits, "#", 1, 3000);
			--	end
			--end
		--process the dtmf
			if (session:ready()) then
				if (dtmf_digits == "1") then
					listen_to_recording(message_number, uuid, created_epoch, caller_id_name, caller_id_number);
				elseif (dtmf_digits == "2") then
					message_saved(voicemail_id, uuid);
					macro(session, "message_saved", 1, 100, '');
				elseif (dtmf_digits == "5") then
					message_saved(voicemail_id, uuid);
					return_call(caller_id_number);
				elseif (dtmf_digits == "7") then
					delete_recording(voicemail_id, uuid);
					message_waiting(voicemail_id, domain_uuid);
				elseif (dtmf_digits == "8") then
					forward_to_extension(voicemail_id, uuid);
					dtmf_digits = '';
					macro(session, "message_saved", 1, 100, '');
				elseif (dtmf_digits == "9") then
					send_email(voicemail_id, uuid);
					dtmf_digits = '';
					macro(session, "emailed", 1, 100, '');
				elseif (dtmf_digits == "*") then
					timeouts = 0;
					main_menu();
				elseif (dtmf_digits == "0") then
					message_saved(voicemail_id, uuid);
					session:transfer("0", "XML", context);
				else
					message_saved(voicemail_id, uuid);
					macro(session, "message_saved", 1, 100, '');
				end
			end
	end