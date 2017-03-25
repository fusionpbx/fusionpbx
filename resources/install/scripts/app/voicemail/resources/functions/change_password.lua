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
			--please enter your password followed by pound
				dtmf_digits = '';
				password = macro(session, "password_new", 20, 5000, '');
			--update the voicemail password
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
			--has been changed to
				dtmf_digits = '';
				macro(session, "password_changed", 20, 3000, password);
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
