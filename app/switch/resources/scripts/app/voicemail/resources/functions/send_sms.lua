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

--define a function to send sms
	function send_sms(id, uuid)
		api = freeswitch.API();

		--get voicemail message details
			sql = [[SELECT * FROM v_voicemails
				WHERE domain_uuid = ']] .. domain_uuid ..[['
				AND voicemail_id = ']] .. id ..[[']]
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				db_voicemail_uuid = string.lower(row["voicemail_uuid"]);
				voicemail_sms_to = row["voicemail_sms_to"];
				voicemail_file = row["voicemail_file"];
				voicemail_uuid = row["voicemail_uuid"];
			end);

		--get the sms_body template
			if (settings['voicemail']['voicemail_sms_body'] ~= nil) then
				if (settings['voicemail']['voicemail_sms_body']['text'] ~= nil) then
					sms_body = settings['voicemail']['voicemail_sms_body']['text'];
				end
			else
				sms_body = 'You have a new voicemail from: ${caller_id_name} - ${caller_id_number} length ${message_length_formatted}';
			end
			local sms_body_template = sms_body;

		--require the sms address to send to
			if (string.len(voicemail_sms_to) > 2) then

				--get voicemail message details
					sql = [[SELECT * FROM v_voicemail_messages
						WHERE domain_uuid = ']] .. domain_uuid ..[['
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
						--get transcription if available
							if (row["message_transcription"] ~= nil and row["message_transcription"] ~= '') then
								transcription = row["message_transcription"];
							end
					end);

				--format the message length and date
					message_length_formatted = format_seconds(message_length);
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail-sms] message length: " .. message_length .. "\n");
						if (transcription ~= nil) then
							freeswitch.consoleLog("notice", "[voicemail-sms] transcription: " .. transcription .. "\n");
						end
						freeswitch.consoleLog("notice", "[voicemail-sms] domain_name: " .. domain_name .. "\n");
					end
					local message_date = os.date("%A, %d %b %Y %I:%M %p", created_epoch)

					sms_body = sms_body:gsub("${caller_id_name}", caller_id_name);
					sms_body = sms_body:gsub("${caller_id_number}", caller_id_number);
					sms_body = sms_body:gsub("${message_date}", message_date);
					sms_body = sms_body:gsub("${message_duration}", message_length_formatted);
					sms_body = sms_body:gsub("${message_length_formatted}", message_length_formatted);
					sms_body = sms_body:gsub("${account}", id);
					sms_body = sms_body:gsub("${domain_name}", domain_name);
					sms_body = sms_body:gsub("${sip_to_user}", id);
					sms_body = sms_body:gsub("${dialed_user}", id);
					sms_body = sms_body:gsub("${voicemail_uuid}", voicemail_uuid);
					sms_body = sms_body:gsub("${message_uuid}", uuid);
					if (transcription ~= nil) then
						sms_body = sms_body:gsub("${message_text}", transcription);
					end
					--if template doesn't include ${message_text} and transcription exists, append it
					if (transcription ~= nil and not sms_body_template:find("${message_text}", 1, true)) then
						sms_body = sms_body .. "\nTranscription: " .. transcription;
					end

					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail-sms] sms_body: " .. sms_body .. "\n");
					end

				--determine media file path for MMS when audio attachment is requested
					local media_file = nil;
					if (voicemail_file == 'attach') then
						local combined = voicemail_dir .. "/" .. id .. "/intro_msg_" .. uuid .. "." .. vm_message_ext;
						local msg_file  = voicemail_dir .. "/" .. id .. "/msg_" .. uuid .. "." .. vm_message_ext;
						if (file_exists(combined)) then
							media_file = combined;
						elseif (file_exists(msg_file)) then
							media_file = msg_file;
						end
						if (debug["info"] and media_file ~= nil) then
							freeswitch.consoleLog("notice", "[voicemail-sms] media_file: " .. media_file .. "\n");
						end
					end

				--look up provider_uuid via the FROM DID in v_destinations
					local provider_uuid_sms = nil;
					local sms_from = (voicemail_to_sms_did or ''):gsub("[^%+0-9]", "");
					sql = "select provider_uuid from v_destinations where "
						.. "( coalesce(destination_prefix,'') || coalesce(destination_area_code,'') || destination_number = '" .. sms_from .. "'"
						.. " or '+' || coalesce(destination_prefix,'') || coalesce(destination_area_code,'') || destination_number = '" .. sms_from .. "'"
						.. " or coalesce(destination_prefix,'') || destination_number = '" .. sms_from .. "'"
						.. " or '+' || coalesce(destination_prefix,'') || destination_number = '" .. sms_from .. "'"
						.. " or destination_number = '" .. sms_from .. "')"
						.. " and provider_uuid is not null and destination_enabled = 'true' limit 1";
					dbh:query(sql, function(row)
						provider_uuid_sms = row["provider_uuid"];
					end);

				if (provider_uuid_sms ~= nil) then
					--read media file once for MMS (same content sent to every recipient)
						local media_base64  = nil;
						local media_type    = 'sms';

						if (media_file ~= nil) then
							local f = io.open(media_file, "rb");
							if (f ~= nil) then
								local content = f:read("*all");
								f:close();
								if (content ~= nil and string.len(content) > 0) then
									media_base64 = base64.encode(content);
									media_type   = 'mms';
								end
							end
						end

					--send to each number in the comma-separated list
						local hostname = trim(api:execute("hostname", ""));
						for sms_to in string.gmatch(voicemail_sms_to, "[^,]+") do
							sms_to = trim(sms_to);
							if (sms_to ~= '') then
								local message_queue_uuid = trim(api:executeString("create_uuid"));

								--insert into v_message_queue
									dbh:query(
										"insert into v_message_queue "
										.. "(message_queue_uuid, domain_uuid, provider_uuid, hostname,"
										.. " message_status, message_type, message_direction, message_date,"
										.. " message_from, message_to, message_text)"
										.. " values (:message_queue_uuid, :domain_uuid, :provider_uuid, :hostname,"
										.. " 'waiting', :message_type, 'outbound', now(),"
										.. " :message_from, :message_to, :message_text)",
										{
											message_queue_uuid = message_queue_uuid,
											domain_uuid        = domain_uuid,
											provider_uuid      = provider_uuid_sms,
											hostname           = hostname,
											message_type       = media_type,
											message_from       = sms_from,
											message_to         = sms_to,
											message_text       = sms_body
										}
									);

								--insert MMS media record so the provider can fetch the audio via URL
									if (media_base64 ~= nil) then
										local message_media_uuid = trim(api:executeString("create_uuid"));
										local media_url = 'https://' .. domain_name
											.. '/app/messages/message_media_outbound.php?id=' .. message_media_uuid;
										dbh:query(
											"insert into v_message_media "
											.. "(message_media_uuid, message_uuid, domain_uuid,"
											.. " message_media_name, message_media_type, message_media_date, message_media_url, message_media_content)"
											.. " values (:message_media_uuid, :message_uuid, :domain_uuid,"
											.. " :message_media_name, :message_media_type, now(), :message_media_url, :message_media_content)",
											{
												message_media_uuid    = message_media_uuid,
												message_uuid          = message_queue_uuid,
												domain_uuid           = domain_uuid,
												message_media_name    = "voicemail." .. vm_message_ext,
												message_media_type    = vm_message_ext,
												message_media_url     = media_url,
												message_media_content = media_base64
											}
										);
									end

								if (debug["info"]) then
									freeswitch.consoleLog("notice", "[voicemail-sms] " .. media_type:upper() .. " queued to: " .. sms_to .. "\n");
								end
							end
						end
				else
					freeswitch.consoleLog("warning", "[voicemail-sms] no provider found for FROM " .. sms_from .. ", skipping\n");
				end

			end

	end
