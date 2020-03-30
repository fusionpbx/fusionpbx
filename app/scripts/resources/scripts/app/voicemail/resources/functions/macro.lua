--	Part of FusionPBX
--	Copyright (C) 2013 - 2016 Mark J Crane <markjcrane@fusionpbx.com>
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

--define the macro function
	function macro(session, name, max_digits, max_timeout, param)
		if (session:ready()) then
			--create an empty table
				actions = {}

			--Please enter your id followed by
				if (name == "voicemail_id") then
					table.insert(actions, {app="streamFile",data="phrase:voicemail_enter_id:#"});
				end
			 --Please enter your id followed by
				if (name == "voicemail_password") then
					table.insert(actions, {app="streamFile",data="phrase:voicemail_enter_pass:#"});
				end
			--the person at extension 101 is not available record your message at the tone press any key or stop talking to end the recording
				if (name == "person_not_available_record_message") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-person.wav"});
					--pronounce the voicemail_id
					if (voicemail_alternate_greet_id and string.len(voicemail_alternate_greet_id) > 0) then
						table.insert(actions, {app="say.number.iterated",data=voicemail_alternate_greet_id});
					elseif (voicemail_greet_id and string.len(voicemail_greet_id) > 0) then
						table.insert(actions, {app="say.number.iterated",data=voicemail_greet_id});
					else
						table.insert(actions, {app="say.number.iterated",data=voicemail_id});
					end
					table.insert(actions, {app="streamFile",data="voicemail/vm-not_available.wav"});
				end
			--record your message at the tone press any key or stop talking to end the recording
				if (name == "record_message") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-record_message.wav"});
				end
			--beep
				if (name == "record_beep") then
					table.insert(actions, {app="tone_stream",data="L=1;%(1000, 0, 640)"});
				end
			--to listen to the recording press 1
				if (name == "to_listen_to_recording") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-listen_to_recording.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/1.wav"});
				end
			--to save the recording press 2
				if (name == "to_save_recording") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-save_recording.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/2.wav"});
				end
			--to rerecord press 3
				if (name == "to_rerecord") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-rerecord.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/3.wav"});
				end
			--You have zero new messages
				if (name == "new_messages") then
					table.insert(actions, {app="streamFile",data="phrase:voicemail_message_count:" .. param .. ":new"})
				end
			--You have zero saved messages
				if (name == "saved_messages") then
					table.insert(actions, {app="streamFile",data="phrase:voicemail_message_count:" .. param .. ":saved"})
				end
			--To listen to new messages press 1
				if (name == "listen_to_new_messages") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-listen_new.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/1.wav"});
				end
			--To listen to saved messages press 2
				if (name == "listen_to_saved_messages") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-listen_saved.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/2.wav"});
				end

			--For advanced options press 5
				if (name == "advanced") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-advanced.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/5.wav"});
				end
			--Advanced Options Menu
				--To record a greeting press 1
					if (name == "to_record_greeting") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-to_record_greeting.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/1.wav"});
					end
					--Choose a greeting between 1 and 9
						if (name == "choose_greeting_choose") then
							table.insert(actions, {app="streamFile",data="voicemail/vm-choose_greeting_choose.wav"});
						end
					--Greeting invalid value
						if (name == "choose_greeting_fail") then
							table.insert(actions, {app="streamFile",data="voicemail/vm-choose_greeting_fail.wav"});
						end
					--Record your greeting at the tone press any key or stop talking to end the recording
						if (name == "record_greeting") then
							table.insert(actions, {app="streamFile",data="voicemail/vm-record_greeting.wav"});
							table.insert(actions, {app="tone_stream",data="L=1;%(1000, 0, 640)"});
						end
				--To choose greeting press 2
					if (name == "choose_greeting") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-choose_greeting.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/2.wav"});
					end
					--Greeting 1 selected
						if (name == "greeting_selected") then
							table.insert(actions, {app="streamFile",data="voicemail/vm-greeting.wav"});
							table.insert(actions, {app="streamFile",data="digits/"..param..".wav"});
							table.insert(actions, {app="streamFile",data="voicemail/vm-selected.wav"});
						end

				--To record your name 3
					if (name == "to_record_name") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-record_name2.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/3.wav"});
					end
				--At the tone please record your name press any key or stop talking to end the recording
					if (name == "record_name") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-record_name1.wav"});
						table.insert(actions, {app="tone_stream",data="L=1;%(2000, 0, 640)"});
					end
				--To change your password press 6
					if (name == "change_password") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-change_password.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/6.wav"});
					end
				--For the main menu press 0
					if (name == "main_menu") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-main_menu.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/0.wav"});
					end
			--To exit press *
				if (name == "to_exit_press") then
					table.insert(actions, {app="streamFile",data="voicemail/vm-to_exit.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/star.wav"});
				end
			--Additional Macros
				--Please enter your new password then press the # key #
					if (name == "password_new") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-enter_new_pin.wav"});
					end
				--Has been changed to
					if (name == "password_changed") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-has_been_changed_to.wav"});
						table.insert(actions, {app="say.number.iterated",data=param});
					end
				--Login Incorrect
					--if (name == "password_not_valid") then
					--	table.insert(actions, {app="streamFile",data="voicemail/vm-password_not_valid.wav"});
					--end
				--Login Incorrect
					if (name == "password_not_valid") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-fail_auth.wav"});
					end
				--Too many failed attempts
					if (name == "too_many_failed_attempts") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-abort.wav"});
					end
				--Message number
					if (name == "message_number") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-message_number.wav"});
					end
				--To listen to the recording press 1
					if (name == "listen_to_recording") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-listen_to_recording.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/1.wav"});
					end
				--To save the recording press 2
					if (name == "save_recording") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-save_recording.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/2.wav"});
					end
				--To delete the recording press 7
					if (name == "delete_recording") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-delete_recording.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/7.wav"});
					end
				--Message deleted
					if (name == "message_deleted") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-message.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-deleted.wav"});
					end
				--To return the call now press 5
					if (name == "return_call") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-return_call.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/5.wav"});
					end
				--To add an introduction to this message press 1
					if (name == "forward_add_intro") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-forward_add_intro.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/1.wav"});
					end
				--To forward this message press 8
					if (name == "to_forward_message") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-to_forward.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/8.wav"});
					end
				--Please enter the extension to forward this message to followed by #
					if (name == "forward_enter_extension") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-forward_enter_ext.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-followed_by.wav"});
						table.insert(actions, {app="streamFile",data="ascii/35.wav"});
					end
				--To forward this recording to your email press 9
					if (name == "forward_to_email") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-forward_to_email.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/9.wav"});
					end
				--Emailed
					if (name == "emailed") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-emailed.wav"});
					end
				--Please enter the extension to send this message to followed by #
					--if (name == "send_message_to_extension") then
					--	table.insert(actions, {app="streamFile",data="voicemail/vm-zzz.wav"});
					--end
				--Message saved
					if (name == "message_saved") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-message.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-saved.wav"});
					end
				--Your recording is below the minimal acceptable length, please try again.
					if (name == "too_small") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-too-small.wav"});
					end
				--Goodbye
					if (name == "goodbye") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-goodbye.wav"});
					end
				--Password is not secure
					if (name == "password_not_secure") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-password_is_not_secure.wav"});
					end
				--Password is below minimum length
					if (name == "password_below_minimum") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-pin_below_minimum_length.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-minimum_pin_length_is.wav"});
						table.insert(actions, {app="streamFile",data="digits/"..param..".wav"});
					end
			--Tutorial
				--Tutorial intro
					if (name == "tutorial_intro") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-tutorial_yes_no.wav"});
					end
				
				--Tutorial to record your name 1
					if (name == "tutorial_to_record_name") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-tutorial_record_name.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-record_name2.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/1.wav"});
					end
					
				--Tutorial to change your password press 1
					if (name == "tutorial_change_password") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-tutorial_change_pin.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-change_password.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/1.wav"});
					end

				--Tutorial to record your greeting press 1
					if (name == "tutorial_record_greeting") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-to_record_greeting.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/1.wav"});
					end					
					
				--Tutorial To skip
					if (name == "tutorial_skip") then
						table.insert(actions, {app="streamFile",data="ivr/ivr-to_skip.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/2.wav"});
					end
		
			--if actions table exists then process it
				if (actions) then
					--set default values
						tries = 1;
						timeout = 100;
					--loop through the action and data
						for key, row in pairs(actions) do
							-- freeswitch.consoleLog("notice", "[voicemail] app: " .. row.app .. " data: " .. row.data .. "\n");
							if (session:ready()) then
								if (string.len(dtmf_digits) == 0) then
									if (row.app == "streamFile") then
										if string.find(row.data, ':', nil, true) then
											session:streamFile(row.data);
										else
											session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data);
										end
									elseif (row.app == "playback") then
										session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data);
									elseif (row.app == "tone_stream") then
										session:streamFile("tone_stream://"..row.data);
									elseif (row.app == "silence_stream") then
										session:streamFile("silence_stream://100"..row.data);
									elseif (row.app == "playAndGetDigits") then
										--playAndGetDigits <min> <max> <tries> <timeout> <terminators> <file> <invalid_file> <var_name> <regexp> <digit_timeout>
										if (not file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data)) then
											dtmf_digits = session:playAndGetDigits(min_digits, max_digits, tries, timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data, "", "\\d+", max_timeout);
										else
											dtmf_digits = session:playAndGetDigits(min_digits, max_digits, tries, timeout, "#", row.data, "", "\\d+", max_timeout);
										end
									elseif (row.app == "say.number.pronounced") then
										session:say(row.data, default_language, "number", "pronounced");
									elseif (row.app == "say.number.iterated") then
										session:say(row.data, default_language, "number", "iterated");
									end
									--session:streamFile("silence_stream://100");
								end --if
							end --session:ready
						end --for
					--get the remaining digits
						if (session:ready()) then
							if (string.len(dtmf_digits) < max_digits) then
								dtmf_digits = dtmf_digits .. session:getDigits(max_digits, "#", max_timeout);
							end
						end
					--return dtmf the digits
						return dtmf_digits;
			else
				--no dtmf digits to return
					return '';
			end
		end
	end
