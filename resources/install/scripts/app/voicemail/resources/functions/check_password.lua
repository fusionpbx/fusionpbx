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
						sql = [[SELECT * FROM v_voicemails
							WHERE domain_uuid = ']] .. domain_uuid ..[['
							AND voicemail_id = ']] .. voicemail_id ..[['
							AND voicemail_enabled = 'true' ]];
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
						status = dbh:query(sql, function(row)
							voicemail_uuid = string.lower(row["voicemail_uuid"]);
							voicemail_password = row["voicemail_password"];
							greeting_id = row["greeting_id"];
							voicemail_mail_to = row["voicemail_mail_to"];
							voicemail_attach_file = row["voicemail_attach_file"];
							voicemail_local_after_email = row["voicemail_local_after_email"];
						end);
					end
				end
			--please enter your password followed by pound
				dtmf_digits = '';
				password = macro(session, "voicemail_password", 20, 5000, '');
				--freeswitch.consoleLog("notice", "[voicemail] password: " .. password .. "\n");
			--compare the password from the database with the password provided by the user
				if (voicemail_password ~= password) then
					--incorrect password
					dtmf_digits = '';
					macro(session, "password_not_valid", 1, 1000, '');
					if (session:ready()) then
						password_tries = password_tries + 1;
						if (password_tries < max_tries) then
							check_password(voicemail_id, password_tries);
						else
							macro(session, "goodbye", 1, 1000, '');
							session:hangup();
						end
					end
				end
		end
	end
--dofile(scripts_dir.."/app/voicemail/resources/functions/check_password.lua");