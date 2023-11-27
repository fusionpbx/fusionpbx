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
	function check_password(voicemail_id, password_tries)
		if (session:ready()) then

			--flush dtmf digits from the input buffer
				session:flushDigits();

			--please enter your id followed by pound
				if (voicemail_id) then
					--do nothing
				else
					timeouts = 0;
					voicemail_id = get_voicemail_id();
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] voicemail id: " .. voicemail_id .. "\n");
					end
				end

			--get the voicemail settings from the database
				if (voicemail_id) then
					if (session:ready()) then
						local sql = [[SELECT * FROM v_voicemails
							WHERE domain_uuid = :domain_uuid
							AND voicemail_id = :voicemail_id
							AND voicemail_enabled = 'true' ]];
						local params = {domain_uuid = domain_uuid, voicemail_id = voicemail_id};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params, function(row)
							voicemail_uuid = string.lower(row["voicemail_uuid"]);
							voicemail_password = row["voicemail_password"];
							greeting_id = row["greeting_id"];
							voicemail_mail_to = row["voicemail_mail_to"];
							voicemail_attach_file = row["voicemail_attach_file"];
							voicemail_local_after_email = row["voicemail_local_after_email"];
						end);
					end
				end

			--end the session if this is an invalid voicemail box
				if (not voicemail_uuid) or (#voicemail_uuid == 0) then
					return session:hangup();
				end

			--please enter your password followed by pound
				min_digits = 2;
				max_digits = 20;
				digit_timeout = 5000;
				max_tries = 3;
				password = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
				--freeswitch.consoleLog("notice", "[voicemail] password: " .. password .. "\n");

			--compare the password from the database with the password provided by the user
				if (voicemail_password ~= password) then
					--incorrect password
					dtmf_digits = '';
					session:execute("playback", "phrase:voicemail_fail_auth");
					if (session:ready()) then
						password_tries = password_tries + 1;
						if (password_tries < max_tries) then
							check_password(voicemail_id, password_tries);
						else
							session:execute("playback", "phrase:voicemail_goodbye");
							session:hangup();
						end
					end
				end
		end
	end
