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

--define function main menu
	function main_menu ()
		if (voicemail_uuid) then
			--clear the value
				dtmf_digits = '';
			--flush dtmf digits from the input buffer
				if (session ~= nil) then
					session:flushDigits();
				end
			--answer the session
				if (session ~= nil) then
					session:answer();
					session:execute("sleep", "1000");
				end

			--new voicemail count
				if (session:ready()) then
					local sql = [[SELECT count(*) as new_messages FROM v_voicemail_messages
						WHERE domain_uuid = :domain_uuid
						AND voicemail_uuid = :voicemail_uuid
						AND (message_status is null or message_status = '') ]];
					local params = {domain_uuid = domain_uuid, voicemail_uuid = voicemail_uuid};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params, function(row)
						new_messages = row["new_messages"];
					end);
					dtmf_digits = session:playAndGetDigits(0, 1, 1, 300, "#", "phrase:voicemail_message_count:" .. new_messages .. ":new", "", "\\d+");
				end
			--saved voicemail count
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						sql = [[SELECT count(*) as saved_messages FROM v_voicemail_messages
							WHERE domain_uuid = :domain_uuid
							AND voicemail_uuid = :voicemail_uuid
							AND message_status = 'saved' ]];
						local params = {domain_uuid = domain_uuid, voicemail_uuid = voicemail_uuid};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params, function(row)
							saved_messages = row["saved_messages"];
						end);
						dtmf_digits = session:playAndGetDigits(0, 1, 1, 300, "#", "phrase:voicemail_message_count:" .. saved_messages .. ":saved", "", "\\d+");
					end
				end

			--to listen to new message
				if (session:ready() and new_messages ~= '0') then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = session:playAndGetDigits(0, 1, 1, 100, "#", "phrase:voicemail_main_menu:new:1", "", "\\d+");
					end
				end
			--to listen to saved message
				if (session:ready() and saved_messages ~= '0') then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = session:playAndGetDigits(0, 1, 1, 100, "#", "phrase:voicemail_main_menu:saved:2", "", "\\d+");
					end
				end
			--for advanced options
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = session:playAndGetDigits(0, 1, 1, 3000, "#", "phrase:voicemail_main_menu:advanced:5", "", "\\d+");
					end
				end
			--to exit press #
				--if (session:ready()) then
				--	if (string.len(dtmf_digits) == 0) then
				--		dtmf_digits = macro(session, "to_exit_press", 1, 3000, '');
				--	end
				--end
			--process the dtmf
				if (session:ready()) then
					if (dtmf_digits == "1") then
						menu_messages("new");
					elseif (dtmf_digits == "2") then
						menu_messages("saved");
					elseif (dtmf_digits == "5") then
						timeouts = 0;
						advanced();
					elseif (dtmf_digits == "0") then
						main_menu();
					elseif (dtmf_digits == "*") then
						dtmf_digits = '';
						session:execute("playback", "phrase:voicemail_goodbye");
						session:hangup();
					else
						if (session:ready()) then
							timeouts = timeouts + 1;
							if (timeouts < max_timeouts) then
								main_menu();
							else
								session:execute("playback", "phrase:voicemail_goodbye");
								session:hangup();
							end
						end
					end
				end
		end
	end
