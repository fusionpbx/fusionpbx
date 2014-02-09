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

--voicemail count if zero new messages set the mwi to no
	function message_waiting(voicemail_id, domain_uuid)
		sql = [[SELECT count(*) as message_count FROM v_voicemail_messages as m, v_voicemails as v
			WHERE v.domain_uuid = ']] .. domain_uuid ..[['
			AND v.voicemail_uuid = m.voicemail_uuid
			AND v.voicemail_id = ']] .. voicemail_id ..[['
			AND (m.message_status is null or m.message_status = '') ]];
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
		end
		status = dbh:query(sql, function(row)
			--send the message waiting event
				local event = freeswitch.Event("message_waiting");
				if (row["message_count"] == "0") then
					--freeswitch.consoleLog("notice", "[voicemail] mailbox: "..voicemail_id.."@"..domain_name.." messages: " .. row["message_count"] .. " no messages\n");
					event:addHeader("MWI-Messages-Waiting", "no");
				else
					event:addHeader("MWI-Messages-Waiting", "yes");
				end
				event:addHeader("MWI-Message-Account", "sip:"..voicemail_id.."@"..domain_name);
				event:addHeader("MWI-Voice-Message", row["message_count"].."/0 ("..row["message_count"].."/0)");
				event:fire();
			--log to console
				if (debug["info"]) then
					freeswitch.consoleLog("notice", "[voicemail] mailbox: "..voicemail_id.."@"..domain_name.." messages: " .. row["message_count"] .. " \n");
				end
		end);
	end
