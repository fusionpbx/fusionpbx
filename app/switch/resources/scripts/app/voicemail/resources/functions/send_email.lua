--
--	Part of FusionPBX
--	Copyright (C) 2013 - 2024 Mark J Crane <markjcrane@fusionpbx.com>
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

--load libraries
local send_mail = require 'resources.functions.send_mail'
local Database = require "resources.functions.database"
local Settings = require "resources.functions.lazy_settings"

--define a function to send email
function send_email(id, uuid)
	--prepare the database, settings and variables
		local db = dbh or Database.new('system');
		local settings = Settings.new(db, domain_name, domain_uuid);
		local http_protocol = settings:get('domain', 'http_protocol', 'text') or "https";
		local email_queue_enabled = "true";

	--get voicemail message details
		local sql = [[SELECT * FROM v_voicemails
			WHERE domain_uuid = :domain_uuid
			AND voicemail_id = :voicemail_id]]
		local params = {domain_uuid = domain_uuid, voicemail_id = id};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			db_voicemail_uuid = string.lower(row["voicemail_uuid"]);
			--voicemail_password = row["voicemail_password"];
			--greeting_id = row["greeting_id"];
			voicemail_mail_to = row["voicemail_mail_to"];
			voicemail_transcription_enabled = row["voicemail_transcription_enabled"];
			voicemail_file = row["voicemail_file"];
			voicemail_local_after_email = row["voicemail_local_after_email"];
			voicemail_description = row["voicemail_description"];
		end);

	--set default values
		if (voicemail_file == nil or voicemail_file == '') then
			voicemail_file = "listen";
		end
		if (voicemail_local_after_email == nil or voicemail_local_after_email == '') then
			voicemail_local_after_email = "true";
		end

	--require the email address to send the email
		if (string.len(voicemail_mail_to) > 2) then
			--include languages file
				local Text = require "resources.functions.text"
				local text = Text.new("app.voicemail.app_languages")
				local dbh = dbh

			--user setting time zone, if set
				local sql = [[
					select
						us.user_setting_value as time_zone
					from
						v_user_settings as us,
						v_extension_users as eu,
						v_extensions as e,
						v_voicemails as v
					where
						v.voicemail_id = :voicemail_id and
						v.domain_uuid = :domain_uuid and
						v.voicemail_id = e.extension and
						e.domain_uuid = :domain_uuid and
						e.extension_uuid = eu.extension_uuid and
						eu.domain_uuid = :domain_uuid and
						eu.user_uuid = us.user_uuid and
						us.domain_uuid = :domain_uuid and
						us.user_setting_category = 'domain' and
						us.user_setting_subcategory = 'time_zone' and
						us.user_setting_name = 'name' and
						us.user_setting_enabled = 'true'
					order by
						eu.insert_date asc
					limit 1
					]]
				local params = {domain_uuid = domain_uuid, voicemail_id = id};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					time_zone = row["time_zone"];
				end);

			--default/domain setting time zone
				if (time_zone == nil or time_zone == '') then
					time_zone = settings:get('domain', 'time_zone', 'name');
				end

			--default time zone
				if (time_zone == nil or time_zone == '') then
					time_zone = 'UTC';
				end

			--connect using other backend if needed
				if storage_type == "base64" then
					dbh = Database.new('system', 'base64/read')
				end

			--get voicemail message details
				local sql = [[SELECT to_char(timezone(:time_zone, to_timestamp(created_epoch)), 'Day DD Mon YYYY HH:MI:SS PM') as message_date, *
					FROM v_voicemail_messages
					WHERE domain_uuid = :domain_uuid
					AND voicemail_message_uuid = :uuid]]
				local params = {domain_uuid = domain_uuid, uuid = uuid, time_zone = time_zone};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					--get the values from the database
						--uuid = row["voicemail_message_uuid"];
						created_epoch = row["created_epoch"];
						caller_id_name = row["caller_id_name"];
						caller_id_number = row["caller_id_number"];
						message_date = row["message_date"];
						message_length = row["message_length"];
						--message_status = row["message_status"];
						--message_priority = row["message_priority"];
					--get the recordings from the database
						if (storage_type == "base64") then
							--set the voicemail intro and message paths
								message_location = voicemail_dir.."/"..id.."/msg_"..uuid.."."..vm_message_ext;
								intro_location = voicemail_dir.."/"..id.."/intro_"..uuid.."."..vm_message_ext;

							--save the recordings to the file system
								if (string.len(row["message_base64"]) > 32) then
									--save the value to a variable
										voicemail_base64 = row["message_base64"];

									--include the file io
										local file = require "resources.functions.file"

									--write decoded message string to file
										file.write_base64(message_location, row["message_base64"]);

									--write decoded intro string to file, if any
										if (string.len(row["message_intro_base64"]) > 32) then
											file.write_base64(intro_location, row["message_intro_base64"]);
										end
								end
						end
				end);

			--close temporary connection
				if storage_type == "base64" then
					dbh:release()
				end

			--format the message length and date
				message_length_formatted = format_seconds(message_length);
				if (debug["info"]) then
					freeswitch.consoleLog("notice", "[voicemail] message date: " .. message_date .. "\n");
					freeswitch.consoleLog("notice", "[voicemail] message length: " .. message_length .. "\n");
				end
				--local message_date = os.date("%A, %d %b %Y %I:%M %p", created_epoch);

			--connect to the database
				local dbh = Database.new('system');

			--get the templates
				local sql = "SELECT * FROM v_email_templates ";
				sql = sql .. "WHERE (domain_uuid = :domain_uuid or domain_uuid is null) ";
				sql = sql .. "AND template_language = :template_language ";
				sql = sql .. "AND template_category = 'voicemail' "
				if (voicemail_transcription_enabled == 'true') then
					sql = sql .. "AND template_subcategory = 'transcription' "
				else
					sql = sql .. "AND template_subcategory = 'default' "
				end
				sql = sql .. "AND template_enabled = 'true' "
				sql = sql .. "ORDER BY domain_uuid DESC "
				local params = {domain_uuid = domain_uuid, template_language = default_language.."-"..default_dialect};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					subject = row["template_subject"];
					body = row["template_body"];
				end);

			--get the link_address
				link_address = http_protocol.."://"..domain_name..project_path;

			--set proper delete status
				local local_after_email = '';
				if (voicemail_local_after_email == "false") then
					local_after_email = "false";
				else
					local_after_email = "true";
				end

			--prepare the headers
				local headers = {
					["X-FusionPBX-Domain-UUID"] = domain_uuid;
					["X-FusionPBX-Domain-Name"] = domain_name;
					["X-FusionPBX-Call-UUID"] = uuid;
					["X-FusionPBX-Email-Type"] = 'voicemail';
					["X-FusionPBX-local_after_email"] = local_after_email;
				}

			--prepare the voicemail_name_formatted
				voicemail_name_formatted = id;
				local display_domain_name = settings:get('voicemail', 'display_domain_name', 'boolean');

				if (display_domain_name == 'true') then
					voicemail_name_formatted = id.."@"..domain_name;
				end
				if (voicemail_description ~= nil and voicemail_description ~= "" and voicemail_description ~= id) then
					voicemail_name_formatted = voicemail_name_formatted.." ("..voicemail_description..")";
				end

			--prepare file
				file = voicemail_dir.."/"..id.."/msg_"..uuid.."."..vm_message_ext;

			--combine intro, if exists, with message for emailing (only)
				intro = voicemail_dir.."/"..id.."/intro_"..uuid.."."..vm_message_ext;
				combined = voicemail_dir.."/"..id.."/intro_msg_"..uuid.."."..vm_message_ext;
				if (file_exists(intro) and file_exists(file)) then
					os.execute("sox "..intro.." "..file.." "..combined);
				end

			--prepare the subject
				if (subject ~= nil) then
					subject = subject:gsub("${caller_id_name}", caller_id_name);
					subject = subject:gsub("${caller_id_number}", caller_id_number);
					subject = subject:gsub("${message_date}", message_date);
					subject = subject:gsub("${message_duration}", message_length_formatted);
					subject = subject:gsub("${account}", voicemail_name_formatted);
					subject = subject:gsub("${voicemail_id}", id);
					subject = subject:gsub("${voicemail_description}", voicemail_description);
					subject = subject:gsub("${voicemail_name_formatted}", voicemail_name_formatted);
					subject = subject:gsub("${domain_name}", domain_name);
					subject = subject:gsub("${new_messages}", new_messages);
					subject = trim(subject);
				else
					subject = text['label-voicemail'] .. ' ' .. caller_id_name .. ' <' .. caller_id_number .. '> ' .. message_length_formatted;
				end
				subject = '=?utf-8?B?'..base64.encode(subject)..'?=';

			--prepare the body
				if (body ~= nil) then
					body = body:gsub("${caller_id_name}", caller_id_name);
					body = body:gsub("${caller_id_number}", caller_id_number);
					body = body:gsub("${message_date}", message_date);
					if (transcription ~= nil) then
						transcription = transcription:gsub("%%", "*");
						body = body:gsub("${message_text}", transcription);
					end
					body = body:gsub("${message_duration}", message_length_formatted);
					body = body:gsub("${account}", voicemail_name_formatted);
					body = body:gsub("${voicemail_id}", id);
					body = body:gsub("${voicemail_description}", voicemail_description);
					body = body:gsub("${voicemail_name_formatted}", voicemail_name_formatted);
					body = body:gsub("${domain_name}", domain_name);
					body = body:gsub("${sip_to_user}", id);
					if (origination_callee_id_name ~= nil) then
						body = body:gsub("${origination_callee_id_name}", origination_callee_id_name);
					end
					body = body:gsub("${dialed_user}", id);
					if (voicemail_file == "attach" and file) then
						body = body:gsub("${message}", text['label-attached']);
					elseif (voicemail_file == "link") then
						body = body:gsub("${message}", "<a href='"..link_address.."/app/voicemails/voicemail_messages.php?action=download&id="..id.."&voicemail_uuid="..db_voicemail_uuid.."&uuid="..uuid.."&t=bin'>"..text['label-download'].."</a>");
					else
						body = body:gsub("${message}", "<a href='"..link_address.."/app/voicemails/voicemail_messages.php?action=autoplay&id="..db_voicemail_uuid.."&uuid="..uuid.."&vm="..id.."'>"..text['label-listen'].."</a>");
					end
					--body = body:gsub(" ", "&nbsp;");
					--body = body:gsub("%s+", "");
					--body = body:gsub("&nbsp;", " ");
					body = trim(body);
				else
					body = '<html><body>';
					if (caller_id_name ~= nil and caller_id_name ~= caller_id_number) then
						body = body .. caller_id_name .. '<br>';
					end
					body = body .. caller_id_number .. '<br>';
					body = body .. message_date .. '<br>';
					if (voicemail_file == "attach" and file) then
						body = body .. '<br>' .. text['label-attached'];
					elseif (voicemail_file == "link") then
						body = body .. "<br><a href='"..link_address.."/app/voicemails/voicemail_messages.php?action=download&id="..id.."&voicemail_uuid="..db_voicemail_uuid.."&uuid="..uuid.."&t=bin'>"..text['label-download'].."</a>";
					else
						body = body .. "<br><a href='"..link_address.."/app/voicemails/voicemail_messages.php?action=autoplay&id="..db_voicemail_uuid.."&uuid="..uuid.."&vm="..id.."'>"..text['label-listen'].."</a>";
					end
					body = body .. '</body></html>';
				end

			--get the smtp from address and name
				smtp_from = settings:get('voicemail', 'smtp_from', 'text');
				smtp_from_name = settings:get('voicemail', 'smtp_from_name', 'text');
				if (smtp_from == nil or smtp_from == '') then
					smtp_from = settings:get('email', 'smtp_from', 'text');
				end
				if (smtp_from_name == nil or smtp_from_name == '') then
					smtp_from_name = settings:get('email', 'smtp_from_name', 'text');
				end
				if (smtp_from_name and string.len(smtp_from_name) > 0 and smtp_from and string.len(smtp_from) > 2) then
					smtp_from = smtp_from_name.."<"..smtp_from..">";
				end

			--send the email with, or without, including the intro
				if (file_exists(combined)) then
					voicemail_path = combined
				else
					voicemail_path = file
				end

			--send the email
				send_mail(headers,
					smtp_from,
					voicemail_mail_to,
					{subject, body},
					(voicemail_file == "attach") and voicemail_path,
					voicemail_base64
				);

		end

	--whether to keep the voicemail message and details local after email
		if (string.len(voicemail_mail_to) > 2 and email_queue_enabled == 'false') then
			if (voicemail_local_after_email == "false") then
				--delete the voicemail message details
					local sql = [[DELETE FROM v_voicemail_messages
						WHERE domain_uuid = :domain_uuid
						AND voicemail_uuid = :voicemail_uuid
						AND voicemail_message_uuid = :uuid]]
					local params = {domain_uuid = domain_uuid,
						voicemail_uuid = db_voicemail_uuid, uuid = uuid};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);
				--delete voicemail recording files
					if (file_exists(file)) then
						os.remove(file);
					end
					if (file_exists(intro)) then
						os.remove(intro);
					end
					if (file_exists(combined)) then
						os.remove(combined);
					end
				--set message waiting indicator
					message_waiting(id, domain_uuid);
				--clear the variable
					db_voicemail_uuid = '';
			elseif (storage_type == "base64") then
				--delete voicemail recording files
					if (file_exists(file)) then
						os.remove(file);
					end
					if (file_exists(intro)) then
						os.remove(intro);
					end
					if (file_exists(combined)) then
						os.remove(combined);
					end
			end

		end

end
