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

--save the message
	function message_saved(voicemail_id, uuid)
		--clear the dtmf
			dtmf_digits = '';
		--flush dtmf digits from the input buffer
			session:flushDigits();
		--get the voicemail_uuid
			local sql = [[SELECT * FROM v_voicemails
				WHERE domain_uuid = :domain_uuid
				AND voicemail_id = :voicemail_id]];
			local params = {domain_uuid = domain_uuid, voicemail_id = voicemail_id};
			dbh:query(sql, params, function(row)
				db_voicemail_uuid = row["voicemail_uuid"];
			end);
		--delete from the database
			sql = [[UPDATE v_voicemail_messages SET message_status = 'saved'
				WHERE domain_uuid = :domain_uuid
				AND voicemail_uuid = :voicemail_uuid
				AND voicemail_message_uuid = :uuid]];
			params = {domain_uuid = domain_uuid, voicemail_uuid = db_voicemail_uuid, uuid = uuid};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params);
		--log to console
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail][saved] id: " .. voicemail_id .. " message: "..uuid.."\n");
			end
		--check the message waiting status
			message_waiting(voicemail_id, domain_uuid);
		--clear the variable
			db_voicemail_uuid = '';
	end
