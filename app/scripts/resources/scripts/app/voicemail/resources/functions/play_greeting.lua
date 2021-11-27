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

	local Database = require"resources.functions.database"

--play the greeting
	function play_greeting()
		timeout = 100;
		tries = 1;
		max_timeout = 200;

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
					if (string.len(greeting_id) > 0 and greeting_id ~= "default") then

						--sleep
							session:execute("playback","silence_stream://200");

						--get the greeting from the database
							if (storage_type == "base64") then
								local dbh = Database.new('system', 'base64/read')

								local sql = [[SELECT * FROM v_voicemail_greetings
									WHERE domain_uuid = :domain_uuid
									AND voicemail_id = :voicemail_id
									AND greeting_id = :greeting_id ]];
								local params = {domain_uuid = domain_uuid, voicemail_id = voicemail_id,
									greeting_id = greeting_id};
								if (debug["sql"]) then
									freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
								end
								local saved
								dbh:query(sql, params, function(row)
									--set the voicemail message path
										mkdir(voicemail_dir.."/"..voicemail_id);
										greeting_location = voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav"; --vm_message_ext;

									--if not found, save greeting to local file system
										--saved = file_exists(greeting_location)
										--if not saved then
											if (string.len(row["greeting_base64"]) > 32) then
												--include the file io
													local file = require "resources.functions.file"

												--write decoded string to file
													saved = file.write_base64(greeting_location, row["greeting_base64"]);
											end
										--end
								end);
								dbh:release();

								if saved then
									--play the greeting
										dtmf_digits = session:playAndGetDigits(0, max_digits, tries, timeout, "#", voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav", "", ".*", max_timeout);
										--session:execute("playback",voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");

									--delete the greeting (retain local for better responsiveness)
										--os.remove(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
								end
							elseif (storage_type == "http_cache") then
								dtmf_digits = session:playAndGetDigits(0, max_digits, tries, timeout, "#", voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav", "", ".*", max_timeout);
								--session:execute("playback",storage_path.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
							else
								dtmf_digits = session:playAndGetDigits(0, max_digits, tries, timeout, "#", voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav", "",".*", max_timeout);
								--session:execute("playback",voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
							end

					else
						--default greeting
						session:execute("playback","silence_stream://200");
						--determine the voicemail_id to say
						if (voicemail_alternate_greet_id and string.len(voicemail_alternate_greet_id) > 0) then
							voicemail_id_say = voicemail_alternate_greet_id;
						elseif (voicemail_greet_id and string.len(voicemail_greet_id) > 0) then
							voicemail_id_say = voicemail_greet_id;
						else
							voicemail_id_say = voicemail_id;
						end
						dtmf_digits = session:playAndGetDigits(0, 1, 1, 200, "#", "phrase:voicemail_play_greeting:" .. voicemail_id_say, "", ".*");
					end
			end
		end
	end
