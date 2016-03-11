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

--play the greeting
	function play_greeting()
		--voicemail prompt
		if (skip_greeting == "true") then
			--skip the greeting
		else
			if (session:ready()) then
				--set the greeting based on the voicemail_greeting_number variable
					if (voicemail_greeting_number ~= nil) then
						if (string.len(voicemail_greeting_number) > 0) then
							greeting_id = voicemail_greeting_number;
						end
					end

				--play the greeting
					dtmf_digits = '';
					if (string.len(greeting_id) > 0) then

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
										mkdir(voicemail_dir.."/"..voicemail_id);
										greeting_location = voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav"; --vm_message_ext;

									--if not found, save greeting to local file system
										--if (not file_exists(greeting_location)) then
											if (string.len(row["greeting_base64"]) > 32) then
												local file = io.open(greeting_location, "w");
												file:write(base64.decode(row["greeting_base64"]));
												file:close();
											end
										--end

									--play the greeting
										session:streamFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");

									--delete the greeting (retain local for better responsiveness)
										--os.remove(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
								end);
							elseif (storage_type == "http_cache") then
								session:streamFile(storage_path.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
							else
								session:streamFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
							end

						--sleep
							session:streamFile("silence_stream://200");
					else
						--default greeting
						dtmf_digits = macro(session, "person_not_available_record_message", 1, 200);
					end
			end
		end
	end