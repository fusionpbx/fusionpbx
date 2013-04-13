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
					sql = [[SELECT * FROM v_voicemail_messages
						WHERE domain_uuid = ']] .. domain_uuid ..[['
						AND voicemail_uuid = ']] .. voicemail_uuid ..[[']]
					if (message_status == "new") then
						sql = sql .. [[AND (message_status is null or message_status = '') ]];
					elseif (message_status == "saved") then
						sql = sql .. [[AND message_status = 'saved' ]];
					end
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
					end
					status = dbh:query(sql, function(row)
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
								listen_to_recording(message_number, string.lower(row["voicemail_message_uuid"]), row["created_epoch"], row["caller_id_name"], row["caller_id_number"]);
							end
					end);
				end
			end

		--voicemail count if zero new messages set the mwi to no
			if (session:ready()) then
				if (voicemail_id ~= nil) then
					sql = [[SELECT count(*) as new_messages FROM v_voicemail_messages
						WHERE domain_uuid = ']] .. domain_uuid ..[['
						AND voicemail_uuid = ']] .. voicemail_uuid ..[['
						AND (message_status is null or message_status = '') ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
					end
					status = dbh:query(sql, function(row)
						--send the message waiting event
						local event = freeswitch.Event("message_waiting");
						if (row["new_messages"] == "0") then
							event:addHeader("MWI-Messages-Waiting", "no");
						else
							event:addHeader("MWI-Messages-Waiting", "yes");
						end
						event:addHeader("MWI-Message-Account", "sip:"..voicemail_id.."@"..domain_name);
						event:fire();
					end);
				end
			end

		--set the display
			if (session:ready()) then
				reply = api:executeString("uuid_display "..session:get_uuid().." "..destination_number);
			end

		--send back to the main menu
			if (session:ready()) then
				timeouts = 0;
				main_menu();
			end
	end
