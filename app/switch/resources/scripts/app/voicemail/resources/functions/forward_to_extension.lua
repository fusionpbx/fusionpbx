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

--define a function to forward a message to an extension
	function forward_to_extension(voicemail_id, uuid)

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--save the voicemail message
			message_saved(voicemail_id, uuid);

		--request the forward_voicemail_id
			if (session:ready()) then
				dtmf_digits = '';
				forward_voicemail_id = macro(session, "forward_enter_extension", 20, 5000, '');
				if (session:ready()) then
					if (string.len(forward_voicemail_id) == 0) then
						dtmf_digits = '';
						forward_voicemail_id = macro(session, "forward_enter_extension", 20, 5000, '');
					end
				end
			end
			if (session:ready()) then
				if (string.len(forward_voicemail_id) == 0) then
					dtmf_digits = '';
					forward_voicemail_id = macro(session, "forward_enter_extension", 20, 5000, '');
				end
			end

		--get voicemail message details
			if (session:ready()) then
				sql = [[SELECT * FROM v_voicemail_messages
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_uuid = ']] .. voicemail_uuid ..[['
					AND voicemail_message_uuid = ']] .. uuid ..[[']]
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				status = dbh:query(sql, function(row)
					--get the values from the database
						created_epoch = row["created_epoch"];
						caller_id_name = row["caller_id_name"];
						caller_id_number = row["caller_id_number"];
						message_length = row["message_length"];
						message_status = row["message_status"];
						message_priority = row["message_priority"];
						message_base64 = row["message_base64"];
				end);
			end

		--get the voicemail settings
			sql = [[SELECT * FROM v_voicemails
				WHERE domain_uuid = ']] .. domain_uuid ..[['
				AND voicemail_id = ']] .. forward_voicemail_id ..[['
				AND voicemail_enabled = 'true' ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				forward_voicemail_uuid = string.lower(row["voicemail_uuid"]);
				forward_voicemail_mail_to = row["voicemail_mail_to"];
				forward_voicemail_attach_file = row["voicemail_attach_file"];
				forward_voicemail_local_after_email = row["voicemail_local_after_email"];
			end);

		--get a new uuid
			voicemail_message_uuid = session:get_uuid();

		--save the message to the voicemail messages
			local sql = {}
			table.insert(sql, "INSERT INTO v_voicemail_messages ");
			table.insert(sql, "(");
			table.insert(sql, "voicemail_message_uuid, ");
			table.insert(sql, "domain_uuid, ");
			table.insert(sql, "voicemail_uuid, ");
			if (storage_type == "base64") then
				table.insert(sql, "message_base64, ");
			end
			table.insert(sql, "created_epoch, ");
			table.insert(sql, "caller_id_name, ");
			table.insert(sql, "caller_id_number, ");
			table.insert(sql, "message_length ");
			--table.insert(sql, "message_status, ");
			--table.insert(sql, "message_priority, ");
			table.insert(sql, ") ");
			table.insert(sql, "VALUES ");
			table.insert(sql, "( ");
			table.insert(sql, "'".. voicemail_message_uuid .."', ");
			table.insert(sql, "'".. domain_uuid .."', ");
			table.insert(sql, "'".. forward_voicemail_uuid .."', ");
			if (storage_type == "base64") then
				table.insert(sql, "'".. message_base64 .."', ");
			end
			table.insert(sql, "'".. created_epoch .."', ");
			table.insert(sql, "'".. caller_id_name .."', ");
			table.insert(sql, "'".. caller_id_number .."', ");
			table.insert(sql, "'".. message_length .."' ");
			--table.insert(sql, "'".. message_status .."', ");
			--table.insert(sql, "'".. message_priority .."' ");
			table.insert(sql, ") ");
			sql = table.concat(sql, "\n");
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			if (storage_type == "base64") then
				array = explode("://", database["system"]);
				local luasql = require "luasql.postgres";
				local env = assert (luasql.postgres());
				local dbh = env:connect(array[2]);
				res, serr = dbh:execute(sql);
				dbh:close();
				env:close();
			else
				dbh:query(sql);
			end

		--set the message waiting event
			local event = freeswitch.Event("message_waiting");
			event:addHeader("MWI-Messages-Waiting", "yes");
			event:addHeader("MWI-Message-Account", "sip:"..forward_voicemail_id.."@"..domain_name);
			event:fire();

		--if local after email is true then copy the recording file
			if (storage_type ~= "base64") then
				mkdir(voicemail_dir.."/"..forward_voicemail_id);
				copy(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext, voicemail_dir.."/"..forward_voicemail_id.."/msg_"..voicemail_message_uuid.."."..vm_message_ext);
			end

		--send the email with the voicemail recording attached
			send_email(forward_voicemail_id, voicemail_message_uuid);

	end
