--	Part of FusionPBX
--	Copyright (C) 2013-2015 Mark J Crane <markjcrane@fusionpbx.com>
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
		--initialize the array and add the voicemail_id
		 	local accounts = {}
			table.insert(accounts, voicemail_id);
		--get the voicemail id and all related mwi accounts
			sql = [[SELECT extension, number_alias from v_extensions
				WHERE domain_uuid = ']] .. domain_uuid ..[['
				AND (mwi_account = ']]..voicemail_id..[[' or mwi_account = ']]..voicemail_id..[[@]]..domain_name..[[')]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				if (string.len(row["number_alias"]) > 0) then
					table.insert(accounts, row["number_alias"]);
				else
					table.insert(accounts, row["extension"]);
				end
			end);

		--get the message count
			sql = [[SELECT count(*) as message_count FROM v_voicemail_messages as m, v_voicemails as v
				WHERE v.domain_uuid = ']] .. domain_uuid ..[['
				AND v.voicemail_uuid = m.voicemail_uuid
				AND v.voicemail_id = ']] .. voicemail_id ..[['
				AND (m.message_status is null or m.message_status = '') ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				message_count = row["message_count"];
			end);

		--send the message waiting event
			for key,value in pairs(accounts) do
				local event = freeswitch.Event("message_waiting");
				if (message_count == "0") then
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] mailbox: "..value.."@"..domain_name.." messages: " .. message_count .. " no messages\n");
					end
					event:addHeader("MWI-Messages-Waiting", "no");
				else
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] mailbox: "..voicemail_id.."@"..domain_name.." messages: " .. message_count .. " \n");
					end
					event:addHeader("MWI-Messages-Waiting", "yes");
				end
				event:addHeader("MWI-Message-Account", "sip:"..value.."@"..domain_name);
				event:addHeader("MWI-Voice-Message", message_count.."/0 ("..message_count.."/0)");
				event:fire();
			end
	end
