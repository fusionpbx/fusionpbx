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
				forward_voicemail_id = session:playAndGetDigits(1, 20, max_tries, digit_timeout, "#", "phrase:voicemail_forward_message_enter_extension:#", "", "\\d+");
				if (session:ready()) then
					if (string.len(forward_voicemail_id) == 0) then
						dtmf_digits = '';
						forward_voicemail_id = session:playAndGetDigits(1, 20, max_tries, digit_timeout, "#", "phrase:voicemail_forward_message_enter_extension:#", "", "\\d+");
					end
				end
			end
			if (session:ready()) then
				if (string.len(forward_voicemail_id) == 0) then
					dtmf_digits = '';
					forward_voicemail_id = session:playAndGetDigits(1, 20, max_tries, digit_timeout, "#", "phrase:voicemail_forward_message_enter_extension:#", "", "\\d+");
				end
			end

		--get voicemail message details
			if (session:ready()) then
				local sql = [[SELECT * FROM v_voicemail_messages
					WHERE domain_uuid = :domain_uuid
					AND voicemail_uuid = :voicemail_uuid
					AND voicemail_message_uuid = :uuid]]
				local params = {domain_uuid = domain_uuid, voicemail_uuid = voicemail_uuid, uuid = uuid};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
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
			local sql = [[SELECT * FROM v_voicemails
				WHERE domain_uuid = :domain_uuid
				AND voicemail_id = :voicemail_id
				AND voicemail_enabled = 'true' ]];
			local params = {domain_uuid = domain_uuid, voicemail_id = forward_voicemail_id};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params, function(row)
				forward_voicemail_uuid = string.lower(row["voicemail_uuid"]);
				forward_voicemail_mail_to = row["voicemail_mail_to"];
				forward_voicemail_attach_file = row["voicemail_attach_file"];
				forward_voicemail_local_after_email = row["voicemail_local_after_email"];
			end);

		--get a new uuid
			api = freeswitch.API();
			voicemail_message_uuid = trim(api:execute("create_uuid", ""));

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
			table.insert(sql, ":voicemail_message_uuid, ");
			table.insert(sql, ":domain_uuid, ");
			table.insert(sql, ":forward_voicemail_uuid, ");
			if (storage_type == "base64") then
				table.insert(sql, ":message_base64, ");
			end
			table.insert(sql, ":created_epoch, ");
			table.insert(sql, ":caller_id_name, ");
			table.insert(sql, ":caller_id_number, ");
			table.insert(sql, ":message_length ");
			--table.insert(sql, ":message_status, ");
			--table.insert(sql, ":message_priority ");
			table.insert(sql, ") ");
			sql = table.concat(sql, "\n");
			local params = {
				voicemail_message_uuid = voicemail_message_uuid;
				domain_uuid = domain_uuid;
				forward_voicemail_uuid = forward_voicemail_uuid;
				message_base64 = message_base64;
				created_epoch = created_epoch;
				caller_id_name = caller_id_name;
				caller_id_number = caller_id_number;
				message_length = message_length;
				-- message_status = message_status;
				-- message_priority = message_priority;
			};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			if (storage_type == "base64") then
				local dbh = Database.new('system', 'base64')
				dbh:query(sql, params);
				dbh:release();
			else
				dbh:query(sql, params);
			end

		--offer to add an intro to the forwarded message
			forward_add_intro(forward_voicemail_id, voicemail_message_uuid);

		--get new and saved message counts
			local new_messages, saved_messages = message_count_by_id(
				forward_voicemail_id, domain_uuid
			)

		--send the message waiting event
			mwi_notify(forward_voicemail_id.."@"..domain_name, new_messages, saved_messages)

		--if local after email is true then copy the recording file
			if (storage_type ~= "base64") then
				mkdir(voicemail_dir.."/"..forward_voicemail_id);
				copy(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext, voicemail_dir.."/"..forward_voicemail_id.."/msg_"..voicemail_message_uuid.."."..vm_message_ext);
			end

		--send the email with the voicemail recording attached
			send_email(forward_voicemail_id, voicemail_message_uuid);

	end
