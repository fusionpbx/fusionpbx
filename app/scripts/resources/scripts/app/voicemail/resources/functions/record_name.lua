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

--define a function to record the name
	function record_name(menu)
		if (session:ready()) then

			--flush dtmf digits from the input buffer
				session:flushDigits();

			--play the name record
				dtmf_digits = '';
				session:execute("playback", "phrase:voicemail_record_name");
				session:execute("sleep", "1000");
				session:streamFile("tone_stream://L=1;%(1000, 0, 640)");

			--prepate to record
				-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
				max_len_seconds = 30;
				silence_threshold = 30;
				silence_seconds = 5;
				mkdir(voicemail_dir.."/"..voicemail_id);

			--record and save the file
				if (storage_type == "base64") then
					--set the location
						voicemail_name_location = voicemail_dir.."/"..voicemail_id.."/recorded_name.wav";

					--record the file to the file system
						-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs);
						result = session:recordFile(voicemail_name_location, max_len_seconds, silence_threshold, silence_seconds);
						--session:execute("record", voicemail_dir.."/"..uuid.." 180 200");

					--show the storage type
						freeswitch.consoleLog("notice", "[recordings] ".. storage_type .. "\n");

					--base64 encode the file
						--include the file io
							local file = require "resources.functions.file"

						--read file content as base64 string
							voicemail_name_base64 = assert(file.read_base64(voicemail_name_location));

					--update the voicemail name
						local sql = "UPDATE v_voicemails ";
						sql = sql .. "set voicemail_name_base64 = :voicemail_name_base64 ";
						sql = sql .. "where domain_uuid = :domain_uuid ";
						sql = sql .. "and voicemail_id = :voicemail_id";
						local params = {voicemail_name_base64 = voicemail_name_base64,
							domain_uuid = domain_uuid, voicemail_id = voicemail_id};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[recording] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						if (storage_type == "base64") then
							local dbh = Database.new('system', 'base64');
							dbh:query(sql, params);
							dbh:release();
						else
							dbh:query(sql, params);
						end
				elseif (storage_type == "http_cache") then
					freeswitch.consoleLog("notice", "[voicemail] ".. storage_type .. " ".. storage_path .."\n");
					session:execute("record", storage_path .."/"..recording_name);
				else
					-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs);
					result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/recorded_name.wav", max_len_seconds, silence_threshold, silence_seconds);
				end

			--play the name
				--session:streamFile(voicemail_dir.."/"..voicemail_id.."/recorded_name.wav");

			--option to play, save, and re-record the name
				if (session:ready()) then
					timeouts = 0;
					record_menu("name", voicemail_dir.."/"..voicemail_id.."/recorded_name.wav",nil, menu);
					if (storage_type == "base64") then
						--delete the greeting
						os.remove(voicemail_dir.."/"..voicemail_id.."/recorded_name.wav");
					end
				end
		end
	end
