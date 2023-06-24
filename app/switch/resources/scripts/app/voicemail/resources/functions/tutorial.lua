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

--define function main menu
	function tutorial (menu)
		if (voicemail_uuid) then
			--intro menu
				if (menu == "intro") then 
					--clear the value
						dtmf_digits = '';
					--flush dtmf digits from the input buffer
						session:flushDigits();
					--play the tutorial press 1, to skip 2
						if (session:ready()) then
							if (string.len(dtmf_digits) == 0) then
								dtmf_digits = session:playAndGetDigits(0, 1, 1, 3000, "#", "phrase:tutorial_intro", "", "\\d+");
							end
						end
					--process the dtmf
						if (session:ready()) then
							if (dtmf_digits == "1") then
								timeouts = 0;
								tutorial("record_name");
							elseif (dtmf_digits == "2") then
								timeouts = 0;
								tutorial("finish");
							else
								if (session:ready()) then
									timeouts = timeouts + 1;
									if (timeouts < max_timeouts) then
										tutorial("intro");
									else
										timeouts = 0;
										tutorial("finish");
									end
								end
							end
						end
				end	
			--record name menu
				if (menu == "record_name") then 
					--clear the value
						dtmf_digits = '';
					--flush dtmf digits from the input buffer
						session:flushDigits();
					--play the record name press 1
						if (session:ready()) then
							if (string.len(dtmf_digits) == 0) then
								dtmf_digits = session:playAndGetDigits(0, 1, 1, 200, "#", "phrase:tutorial_record_name:1", "", "\\d+");
							end
						end
					--skip the name and go to password press 2
						if (session:ready()) then
							if (string.len(dtmf_digits) == 0) then
								dtmf_digits = session:playAndGetDigits(0, 1, 1, 3000, "#", "phrase:tutorial_skip:2", "", "\\d+");
							end
						end
					--process the dtmf
						if (session:ready()) then
							if (dtmf_digits == "1") then
								timeouts = 0;
								record_name("tutorial");
							elseif (dtmf_digits == "2") then
								timeouts = 0;
								tutorial("change_password");
							else
								if (session:ready()) then
									timeouts = timeouts + 1;
									if (timeouts < max_timeouts) then
										tutorial("record_name");
									else
										tutorial("change_password");
									end
								end
							end
						end
				end
			--change password menu
				if (menu == "change_password") then 
					--clear the value
						dtmf_digits = '';
					--flush dtmf digits from the input buffer
						session:flushDigits();
					--to change your password press 1
						if (session:ready()) then
							if (string.len(dtmf_digits) == 0) then
								dtmf_digits = session:playAndGetDigits(0, 1, 1, 200, "#", "phrase:tutorial_change_password:1", "", "\\d+");
							end
						end
					--skip the password and go to greeting press 2
						if (session:ready()) then
							if (string.len(dtmf_digits) == 0) then
								dtmf_digits = session:playAndGetDigits(0, 1, 1, 3000, "#", "phrase:tutorial_skip:2", "", "\\d+");
							end
						end
					--process the dtmf
						if (session:ready()) then
							if (dtmf_digits == "1") then
								timeouts = 0;
								change_password(voicemail_id, "tutorial");
							elseif (dtmf_digits == "2") then
								timeouts = 0;
								tutorial("record_greeting");
							else
								if (session:ready()) then
									timeouts = timeouts + 1;
									if (timeouts < max_timeouts) then
										tutorial("change_password");
									else
										tutorial("record_greeting");
									end
								end
							end
						end
				end				
			--change greeting menu
				if (menu == "record_greeting") then 
					--clear the value
						dtmf_digits = '';
					--flush dtmf digits from the input buffer
						session:flushDigits();
					--to record a greeting press 1
						if (session:ready()) then
							if (string.len(dtmf_digits) == 0) then
								dtmf_digits = session:playAndGetDigits(0, 1, 1, 200, "#", "phrase:tutorial_record_greeting:1", "", "\\d+");
							end
						end
					--skip the record greeting press 2. finishes the tutorial and routes to main menu
						if (session:ready()) then
							if (string.len(dtmf_digits) == 0) then
								dtmf_digits = session:playAndGetDigits(0, 1, 1, 3000, "#", "phrase:tutorial_skip:2", "", "\\d+");
							end
						end
					--process the dtmf
						if (session:ready()) then
							if (dtmf_digits == "1") then
								timeouts = 0;
								record_greeting(nil, "tutorial");
							elseif (dtmf_digits == "2") then
								timeouts = 0;
								tutorial("finish");
							else
								if (session:ready()) then
									timeouts = timeouts + 1;
									if (timeouts < max_timeouts) then
										tutorial("record_greeting");
									else
										tutorial("finish");
									end
								end
							end
						end
				end
				if (menu == "finish") then 
					--clear the value
						dtmf_digits = '';
					--flush dtmf digits from the input buffer
						session:flushDigits();
					--update play tutorial in the datebase
						local sql = [[UPDATE v_voicemails
							set voicemail_tutorial = 'false'
							WHERE domain_uuid = :domain_uuid
							AND voicemail_id = :voicemail_id 
							AND voicemail_enabled = 'true' ]];
						local params = {domain_uuid = domain_uuid,
							voicemail_id = voicemail_id};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params);
					--go to main menu
						main_menu();
				end					
		end
	end
