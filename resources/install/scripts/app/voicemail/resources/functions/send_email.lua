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

--define a function to send email
	function send_email(id, uuid)
		--get voicemail message details
			sql = [[SELECT * FROM v_voicemails
				WHERE domain_uuid = ']] .. domain_uuid ..[['
				AND voicemail_id = ']] .. id ..[[']]
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				db_voicemail_uuid = string.lower(row["voicemail_uuid"]);
				--voicemail_password = row["voicemail_password"];
				--greeting_id = row["greeting_id"];
				voicemail_mail_to = row["voicemail_mail_to"];
				voicemail_attach_file = row["voicemail_attach_file"];
				voicemail_local_after_email = row["voicemail_local_after_email"];
			end);

		--set default values
			if (voicemail_local_after_email == nil) then
				voicemail_local_after_email = "true";
			end
			if (voicemail_attach_file == nil) then
				voicemail_attach_file = "true";
			end

		--require the email address to send the email
			if (string.len(voicemail_mail_to) > 2) then
				--get voicemail message details
					sql = [[SELECT * FROM v_voicemail_messages
						WHERE domain_uuid = ']] .. domain_uuid ..[['
						AND voicemail_message_uuid = ']] .. uuid ..[[']]
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
					end
					status = dbh:query(sql, function(row)
						--get the values from the database
							--uuid = row["voicemail_message_uuid"];
							created_epoch = row["created_epoch"];
							caller_id_name = row["caller_id_name"];
							caller_id_number = row["caller_id_number"];
							message_length = row["message_length"];
							--message_status = row["message_status"];
							--message_priority = row["message_priority"];
					end);

				--format the message length and date
					message_length_formatted = format_seconds(message_length);
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] message length: " .. message_length .. "\n");
					end
					local message_date = os.date("%A, %d %b %Y %I:%M %p", created_epoch)

				--prepare the files
					file_subject = scripts_dir.."/app/voicemail/resources/templates/"..default_language.."/"..default_dialect.."/email_subject.tpl";
					file_body = scripts_dir.."/app/voicemail/resources/templates/"..default_language.."/"..default_dialect.."/email_body.tpl";
					if (not file_exists(file_subject)) then
						file_subject = scripts_dir.."/app/voicemail/resources/templates/en/us/email_subject.tpl";
						file_body = scripts_dir.."/app/voicemail/resources/templates/en/us/email_body.tpl";
					end

				--prepare the headers
					headers = '{"X-FusionPBX-Domain-UUID":"'..domain_uuid..'",';
					headers = headers..'"X-FusionPBX-Domain-Name":"'..domain_name..'",';
					headers = headers..'"X-FusionPBX-Call-UUID":"'..uuid..'",';
					headers = headers..'"X-FusionPBX-Email-Type":"voicemail"}';

				--prepare the subject
					local f = io.open(file_subject, "r");
					local subject = f:read("*all");
					f:close();
					subject = subject:gsub("${caller_id_name}", caller_id_name);
					subject = subject:gsub("${caller_id_number}", caller_id_number);
					subject = subject:gsub("${message_date}", message_date);
					subject = subject:gsub("${message_duration}", message_length_formatted);
					subject = subject:gsub("${account}", id);
					subject = subject:gsub("${domain_name}", domain_name);
					subject = trim(subject);
					subject = '=?utf-8?B?'..base64.enc(subject)..'?=';

				--prepare the body
					local f = io.open(file_body, "r");
					body = f:read("*all");
					f:close();
					body = body:gsub("${caller_id_name}", caller_id_name);
					body = body:gsub("${caller_id_number}", caller_id_number);
					body = body:gsub("${message_date}", message_date);
					body = body:gsub("${message_duration}", message_length_formatted);
					body = body:gsub("${account}", id);
					body = body:gsub("${domain_name}", domain_name);
					body = body:gsub(" ", "&nbsp;");
					body = body:gsub("%s+", "");
					body = body:gsub("&nbsp;", " ");
					body = body:gsub("\n", "");
					body = body:gsub("\n", "");
					body = body:gsub("'", "&#39;");
					body = body:gsub([["]], "&#34;");
					body = trim(body);

				--send the email
					if (voicemail_attach_file == "true") then
						if (voicemail_local_after_email == "false") then
							delete = "true";
						else
							delete = "false";
						end
						file = voicemail_dir.."/"..id.."/msg_"..uuid.."."..vm_message_ext;
						cmd = "luarun email.lua "..voicemail_mail_to.." "..voicemail_mail_to.." "..headers.." '"..subject.."' '"..body.."' '"..file.."' "..delete;
					else
						cmd = "luarun email.lua "..voicemail_mail_to.." "..voicemail_mail_to.." "..headers.." '"..subject.."' '"..body.."'";
					end
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] cmd: " .. cmd .. "\n");
					end
					result = api:executeString(cmd);

			end

		--whether to keep the voicemail message and details local after email
			if (voicemail_mail_to) then
				if (voicemail_local_after_email == "false" and string.len(voicemail_mail_to) > 0) then
					--delete the voicemail message details
						sql = [[DELETE FROM v_voicemail_messages
							WHERE domain_uuid = ']] .. domain_uuid ..[['
							AND voicemail_uuid = ']] .. db_voicemail_uuid ..[['
							AND voicemail_message_uuid = ']] .. uuid ..[[']]
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
						status = dbh:query(sql);
					--set message waiting indicator
						message_waiting(id, domain_uuid);
					--clear the variable
						db_voicemail_uuid = '';
				end
			end
	end
