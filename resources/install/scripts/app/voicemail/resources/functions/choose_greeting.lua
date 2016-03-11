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

--define a function to choose the greeting
	function choose_greeting()

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--select the greeting
			if (session:ready()) then
				dtmf_digits = '';
				greeting_id = macro(session, "choose_greeting_choose", 1, 5000, '');
			end

		--check to see if the greeting file exists
			if (storage_type == "base64" or storage_type == "http_cache") then
				greeting_invalid = true;
				sql = [[SELECT * FROM v_voicemail_greetings
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_id = ']].. voicemail_id.. [['
					AND greeting_id = ']].. greeting_id.. [[' ]];
				status = dbh:query(sql, function(row)
					--greeting found
					greeting_invalid = false;
				end);
				if (greeting_invalid) then
					greeting_id = "invalid";
				end
			else
				if (greeting_id ~= "0") then
					if (not file_exists(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav")) then
						--invalid greeting_id file does not exist
						greeting_id = "invalid";
					end
				end
			end

		--validate the greeting_id
			if (greeting_id == "0"
				or greeting_id == "1"
				or greeting_id == "2"
				or greeting_id == "3"
				or greeting_id == "4"
				or greeting_id == "5"
				or greeting_id == "6"
				or greeting_id == "7"
				or greeting_id == "8"
				or greeting_id == "9") then

				--valid greeting_id update the database
					if (session:ready()) then
						if (greeting_id == "0") then
							sql = [[UPDATE v_voicemails SET greeting_id = null ]];
						else
							sql = [[UPDATE v_voicemails SET greeting_id = ']]..greeting_id..[[' ]];
						end
						sql = sql ..[[WHERE domain_uuid = ']] .. domain_uuid ..[[' ]]
						sql = sql ..[[AND voicemail_uuid = ']] .. voicemail_uuid ..[[' ]];
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
						dbh:query(sql);
					end

				--get the greeting from the database
					if (storage_type == "base64") then
						sql = [[SELECT * FROM v_voicemail_greetings
							WHERE domain_uuid = ']] .. domain_uuid ..[['
							AND voicemail_id = ']].. voicemail_id.. [['
							AND greeting_id = ']].. greeting_id.. [[' ]];
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
						status = dbh:query(sql, function(row)
							--add functions
								require "resources.functions.base64";

							--set the voicemail message path
								greeting_location = voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav"; --vm_message_ext;

							--save the greeting to the file system
								if (string.len(row["greeting_base64"]) > 32) then
									local file = io.open(greeting_location, "w");
									file:write(base64.decode(row["greeting_base64"]));
									file:close();
								end
						end);
					elseif (storage_type == "http_cache") then
						greeting_location = storage_path.."/"..voicemail_id.."/greeting_"..greeting_id..".wav"; --vm_message_ext;
					end

				--play the greeting
					if (session:ready()) then
						if (file_exists(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav")) then
							session:streamFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
						end
					end

				--greeting selected
					if (session:ready()) then
						dtmf_digits = '';
						macro(session, "greeting_selected", 1, 100, greeting_id);
					end

				--advanced menu
					if (session:ready()) then
						timeouts = 0;
						advanced();
					end
			else
				--invalid greeting_id
					if (session:ready()) then
						dtmf_digits = '';
						greeting_id = macro(session, "choose_greeting_fail", 1, 100, '');
					end

				--send back to choose the greeting
					if (session:ready()) then
						timeouts = timeouts + 1;
						if (timeouts < max_timeouts) then
							choose_greeting();
						else
							timeouts = 0;
							advanced();
						end
					end
			end

	end
