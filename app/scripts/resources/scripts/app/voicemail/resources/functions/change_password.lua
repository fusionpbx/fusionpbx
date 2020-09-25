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

--check the voicemail password
	function change_password(voicemail_id, menu)
		if (session:ready()) then
			--flush dtmf digits from the input buffer
				session:flushDigits();
			--set password valitity in case of hangup
				valid_password = "false";
			--please enter your password followed by pound
				dtmf_digits = '';
				password = session:playAndGetDigits(1, 20, max_tries, 5000, "#", "phrase:voicemail_enter_new_pass", "", "\\d+");
				if (password_complexity ~= "true") then
					valid_password = "true";
				end
			--check password comlexity
				if (password_complexity == "true") then 
					--check for length
						if (string.len(password) < tonumber(password_min_length)) then
							password_error_flag = "1";
							dtmf_digits = '';
							--freeswitch.consoleLog("notice", "[voicemail] Not long enough \n");
							session:execute("playback", "phrase:voicemail_password_below_minimum:" .. password_min_length);
							timeouts = 0;
							if (menu == "tutorial") then
								change_password(voicemail_id, "tutorial");
							end
							if (menu == "advanced") then
								change_password(voicemail_id, "advanced");
							end
						end
					
					--check for repeating digits
						local repeating = {"000", "111", "222", "333", "444", "555", "666", "777", "888", "999"};
						for i = 1, 10 do
							if (string.match(password, repeating[i])) then
								password_error_flag = "1";
								dtmf_digits = '';
								--freeswitch.consoleLog("notice", "[voicemail] You can't use repeating digits like ".. repeating[i] .."  \n");
								session:execute("playback", "phrase:voicemail_password_not_secure");
								timeouts = 0;
								if (menu == "tutorial") then
									change_password(voicemail_id, "tutorial");
								end
								if (menu == "advanced") then
									change_password(voicemail_id, "advanced");
								end
							end	
						end

					--check for squential digits
						local sequential = {"012", "123", "234", "345", "456", "567", "678", "789", "987", "876", "765", "654", "543", "432", "321", "210"};
						for i = 1, 8 do
							if (string.match(password, sequential[i])) then
								password_error_flag = "1";
								dtmf_digits = '';
								--freeswitch.consoleLog("notice", "[voicemail] You can't use sequential digits like ".. sequential[i] .."  \n");
								session:execute("playback", "phrase:voicemail_password_not_secure");
								timeouts = 0;
								if (menu == "tutorial") then
									change_password(voicemail_id, "tutorial");
								end
								if (menu == "advanced") then
									change_password(voicemail_id, "advanced");
								end
							end	
						end
					--password is valid
						if (password_error_flag ~= "1") then 
							freeswitch.consoleLog("notice", "[voicemail] Password is valid! \n");
							valid_password = "true";
						end
				end
			--update the voicemail password
				if (valid_password == "true") then 
					local sql = [[UPDATE v_voicemails
						set voicemail_password = :password
						WHERE domain_uuid = :domain_uuid
						AND voicemail_id = :voicemail_id 
						AND voicemail_enabled = 'true' ]];
					local params = {password = password, domain_uuid = domain_uuid,
						voicemail_id = voicemail_id};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);
				end
			--has been changed to
				dtmf_digits = '';
				session:execute("playback", "phrase:voicemail_change_pass_repeat_success:" .. password);
			--advanced menu
				timeouts = 0;
				if (menu == "advanced") then
					advanced();
				end
				if (menu == "tutorial") then
					tutorial("record_greeting");
				end
		end
	end
