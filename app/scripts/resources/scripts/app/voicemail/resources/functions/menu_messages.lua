--	Part of FusionPBX
--	Copyright (C) 2013-2017 Mark J Crane <markjcrane@fusionpbx.com>
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
--	THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--define function for messages menu
	function menu_messages (message_status)

		--set default values
			max_timeout = 2000;
			min_digits = 1;
			max_digits = 1;
			tries = 1;
			timeout = 2000;
		--clear the dtmf
			dtmf_digits = '';
		--flush dtmf digits from the input buffer
			--session:flushDigits();
		--set the message number
			message_number = 0;
		--message_status new,saved
			if (session:ready()) then
				if (voicemail_id ~= nil) then
					--get the voicemail_id
					--fix for extensions that start with 0 (Ex: 0712)
							sql = [[SELECT voicemail_id FROM v_voicemails WHERE voicemail_uuid = :voicemail_uuid]];
							local params = {voicemail_uuid = voicemail_uuid};
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
							end
							dbh:query(sql, params, function(result)
								voicemail_id_copy = result["voicemail_id"];
							end);

					local sql = [[SELECT voicemail_message_uuid, created_epoch, caller_id_name, caller_id_number 
						FROM v_voicemail_messages
						WHERE domain_uuid = :domain_uuid
						AND voicemail_uuid = :voicemail_uuid ]]
					if (message_status == "new") then
						sql = sql .. [[AND (message_status is null or message_status = '') ]];
					elseif (message_status == "saved") then
						sql = sql .. [[AND message_status = 'saved' ]];
					end
					sql = sql .. [[ORDER BY created_epoch ]]..message_order;
					local params = {domain_uuid = domain_uuid, voicemail_uuid = voicemail_uuid};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params, function(row)
						--get the values from the database
							--row["voicemail_message_uuid"];
							--row["created_epoch"];
							--row["caller_id_name"];
							--row["caller_id_number"];
							--row["message_length"];
							--row["message_status"];
							--row["message_priority"];
						--increment the message count
							message_number = message_number + 1;
						--listen to the message
							if (session:ready()) then
								if (debug["info"]) then
									freeswitch.consoleLog("notice", message_number.." "..string.lower(row["voicemail_message_uuid"]).." "..row["created_epoch"]);
								end
								return listen_to_recording(message_number, string.lower(row["voicemail_message_uuid"]), row["created_epoch"], row["caller_id_name"], row["caller_id_number"], message_status);
							end
					end);
				end
			end

		--voicemail count if zero new messages set the mwi to no
			if session:ready() and voicemail_id and voicemail_uuid and #voicemail_uuid > 0 then
				--get new and saved message counts
					local new_messages, saved_messages = message_count_by_uuid(
						voicemail_uuid, domain_uuid
					)
				--send the message waiting event
					mwi_notify(voicemail_id.."@"..domain_name, new_messages, saved_messages)
					--fix for extensions that start with 0 (Ex: 0712)
						if (voicemail_id_copy ~= voicemail_id  and voicemail_id_copy ~= nil) then
							message_waiting(voicemail_id_copy, domain_uuid);
						end
			end

		--set the display
			if (session:ready()) then
				reply = api:executeString("uuid_display "..session:get_uuid().." "..destination_number);
			end

		--send back to the main menu
			if (session:ready()) then
				timeouts = 0;
				return main_menu();
			end
	end
