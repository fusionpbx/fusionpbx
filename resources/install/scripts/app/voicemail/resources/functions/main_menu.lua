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
				session:flushDigits();
			--new voicemail count
				if (session:ready()) then
					sql = [[SELECT count(*) as new_messages FROM v_voicemail_messages
						WHERE domain_uuid = ']] .. domain_uuid ..[['
						AND voicemail_uuid = ']] .. voicemail_uuid ..[['
						AND (message_status is null or message_status = '') ]];
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
					status = dbh:query(sql, function(row)
						new_messages = row["new_messages"];
					end);
					dtmf_digits = macro(session, "new_messages", 1, 100, new_messages);
				end
			--saved voicemail count
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						sql = [[SELECT count(*) as saved_messages FROM v_voicemail_messages
							WHERE domain_uuid = ']] .. domain_uuid ..[['
							AND voicemail_uuid = ']] .. voicemail_uuid ..[['
							AND message_status = 'saved' ]];
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
							end
						status = dbh:query(sql, function(row)
							saved_messages = row["saved_messages"];
						end);
						dtmf_digits = macro(session, "saved_messages", 1, 100, saved_messages);
					end
				end
			--to listen to new message
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "listen_to_new_messages", 1, 100, '');
					end
				end
			--to listen to saved message
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "listen_to_saved_messages", 1, 100, '');
					end
				end
			--for advanced options
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "advanced", 1, 100, '');
					end
				end
			--to exit press #
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "to_exit_press", 1, 3000, '');
					end
				end
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
						macro(session, "goodbye", 1, 100, '');
						session:hangup();
					else
						if (session:ready()) then
							timeouts = timeouts + 1;
							if (timeouts < max_timeouts) then
								main_menu();
							else
								macro(session, "goodbye", 1, 1000, '');
								session:hangup();
							end
						end
					end
				end
		end
	end
