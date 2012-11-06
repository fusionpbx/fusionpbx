--	conference.lua
--	Part of FusionPBX
--	Copyright (C) 2012 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	notice, this list of conditions and the following disclaimer in the
--	documentation and/or other materials provided with the distribution.
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

--set variables
	flags = "";
	max_tries = 3;
	digit_timeout = 5000;

--include the lua script
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	include = assert(loadfile(scripts_dir .. "/resources/config.lua"));
	include();

--connect to the database
	--ODBC - data source name
		if (dsn_name) then
			dbh = freeswitch.Dbh(dsn_name,dsn_username,dsn_password);
		end
	--FreeSWITCH core db handler
		if (db_type == "sqlite") then
			dbh = freeswitch.Dbh("core:"..db_path.."/"..db_name);
		end

--prepare the api object
	api = freeswitch.API();

--define the trim function
	function trim(s)
		return s:gsub("^%s+", ""):gsub("%s+$", "")
	end

--define the session hangup
	function session_hangup_hook()

		--get the session variables
			conference_session_detail_uuid = api:executeString("create_uuid");
			--conference_name = session:getVariable("conference_name");
			conference_session_uuid = session:getVariable("conference_uuid");
			conference_recording = session:getVariable("conference_recording");
			conference_moderator = session:getVariable("conference_moderator");
			--start_epoch = session:getVariable("start_epoch");

		--set the epoch
			end_epoch = os.time();

		--get the conference sessions
			sql = [[SELECT count(*) as num_rows 
				FROM v_conference_sessions
				WHERE conference_session_uuid = ']] .. conference_session_uuid ..[[']];
			status = dbh:query(sql, function(row)
			num_rows = string.lower(row["num_rows"]);
			end);
			freeswitch.consoleLog("notice", "[conference] SQL: " .. sql .. " Rows:"..num_rows.."\n");
			if (num_rows == "0") then
				local sql = {}
				table.insert(sql, "INSERT INTO v_conference_sessions ");
				table.insert(sql, "(");
				table.insert(sql, "conference_session_uuid, ");
				table.insert(sql, "domain_uuid, ");
				table.insert(sql, "meeting_uuid, ");
				table.insert(sql, "profile, ");
				if (conference_recording) then
					table.insert(sql, "recording, ");
				end
				--if (wait_mod) then
				--	table.insert(sql, "wait_mod, ");
				--end
				table.insert(sql, "start_epoch, ");
				table.insert(sql, "end_epoch ");
				table.insert(sql, ") ");
				table.insert(sql, "VALUES ");
				table.insert(sql, "( ");
				table.insert(sql, "'".. conference_session_uuid .."', ");
				table.insert(sql, "'".. domain_uuid .."', ");
				table.insert(sql, "'".. meeting_uuid .."', ");
				table.insert(sql, "'".. profile .."', ");
				if (conference_recording) then
					table.insert(sql, "'".. conference_recording .."', ");
				end
				--if (wait_mod) then
				--	table.insert(sql, "'".. wait_mod .."', ");
				--end
				table.insert(sql, "'".. start_epoch .."', ");
				table.insert(sql, "'".. end_epoch .."' ");
				table.insert(sql, ") ");
				SQL_STRING = table.concat(sql, "\n");
				dbh:query(SQL_STRING);
				freeswitch.consoleLog("notice", "[conference] SQL: " .. SQL_STRING .. "\n");
			else
				freeswitch.consoleLog("notice", "[conference] number is greater than 0 \n");
			end

			local sql = {}
			table.insert(sql, "INSERT INTO v_conference_session_details ");
			table.insert(sql, "(");
			table.insert(sql, "conference_session_detail_uuid, ");
			table.insert(sql, "domain_uuid, ");
			table.insert(sql, "conference_session_uuid, ");
			table.insert(sql, "meeting_uuid, ");
			table.insert(sql, "username, ");
			table.insert(sql, "caller_id_name, ");
			table.insert(sql, "caller_id_number, ");
			table.insert(sql, "network_addr, ");
			table.insert(sql, "uuid, ");
			if (conference_moderator) then
				table.insert(sql, "moderator, ");
			end
			table.insert(sql, "start_epoch, ");
			table.insert(sql, "end_epoch ");
			table.insert(sql, ") ");
			table.insert(sql, "VALUES ");
			table.insert(sql, "( ");
			table.insert(sql, "'".. conference_session_detail_uuid .."', ");
			table.insert(sql, "'".. domain_uuid .."', ");
			table.insert(sql, "'".. conference_session_uuid .."', ");
			table.insert(sql, "'".. meeting_uuid .."', ");
			table.insert(sql, "'".. username .."', ");
			table.insert(sql, "'".. caller_id_name .."', ");
			table.insert(sql, "'".. caller_id_number .."', ");
			table.insert(sql, "'".. network_addr .."', ");
			table.insert(sql, "'".. uuid .."', ");
			if (conference_moderator) then
				table.insert(sql, "'".. conference_moderator .."', ");
			end
			table.insert(sql, "'".. start_epoch .."', ");
			table.insert(sql, "'".. end_epoch .."' ");
			table.insert(sql, ") ");
			SQL_STRING = table.concat(sql, "\n");
			dbh:query(SQL_STRING);
	end

--make sure the session is ready
	if (session:ready()) then
		session:answer();
		session:setHangupHook("session_hangup_hook");
		sounds_dir = session:getVariable("sounds_dir");
		hold_music = session:getVariable("hold_music");
		domain_name = session:getVariable("domain_name");
		pin_number = session:getVariable("pin_number");

		--get the domain_uuid
			if (domain_name ~= nil) then
				sql = "SELECT domain_uuid FROM v_domains ";
				sql = sql .. "WHERE domain_name = '" .. domain_name .."' ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[conference] SQL: " .. sql .. "\n");
				end
				status = dbh:query(sql, function(rows)
					domain_uuid = string.lower(rows["domain_uuid"]);
				end);
			end

		--add the domain to the recording directory
			if (domain_count > 1) then
				recordings_dir = recordings_dir.."/"..domain_name;
			end

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--get the variables
			username = session:getVariable("username");
			caller_id_name = session:getVariable("caller_id_name");
			caller_id_number = session:getVariable("caller_id_number");
			callee_id_name = session:getVariable("callee_id_name");
			callee_id_number = session:getVariable("callee_id_number");
			dialplan = session:getVariable("dialplan");
			network_addr = session:getVariable("network_addr");
			uuid = session:getVariable("uuid");
			--context = session:getVariable("context");
			chan_name = session:getVariable("chan_name");

		--if the pin number is provided then require it
			if (not pin_number) then 
				min_digits = 3;
				max_digits = 12;
				pin_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
			end

		--get the conference sessions
			sql = [[SELECT * FROM v_conference_rooms as s, v_meeting_pins as p
				WHERE s.domain_uuid = ']] .. domain_uuid ..[['
				AND s.meeting_uuid = p.meeting_uuid
				AND p.domain_uuid = ']] .. domain_uuid ..[['
				AND p.member_pin = ']] .. pin_number ..[['
				AND enabled = 'true' ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[conference] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				conference_room_uuid = string.lower(row["conference_room_uuid"]);
				conference_center_uuid = string.lower(row["conference_center_uuid"]);
				meeting_uuid = string.lower(row["meeting_uuid"]);
				record = string.lower(row["record"]);
				profile = string.lower(row["profile"]);
				max_members = row["max_members"];
				wait_mod = row["wait_mod"];
				member_type = row["member_type"];
				announce = row["announce"];
				enter_sound = row["enter_sound"];
				mute = row["mute"];
				created = row["created"];
				created_by = row["created_by"];
				enabled = row["enabled"];
				description = row["description"];
			end);

		--set the meeting uuid
			session:setVariable("meeting_uuid", meeting_uuid);

		if (conference_center_uuid == nil) then
			--invalid pin number
			session:streamFile("phrase:voicemail_fail_auth:#");
			session:hangup("NORMAL_CLEARING");
		else
			--check if the conference exists
					cmd = "conference "..meeting_uuid.."-"..domain_name.." xml_list";
					result = trim(api:executeString(cmd));
					if (string.sub(result, -9) == "not found") then
						conference_exists = false;
					else
						conference_exists = true;
					end

			--set a conference parameter
				if (max_members ~= nil) then
					if (tonumber(max_members) > 0) then
						--max members must be 2 or more
						session:execute("set","conference_max_members="..max_members);
						if (conference_exists) then
							cmd = "conference "..meeting_uuid.."-"..domain_name.." get count";
							count = trim(api:executeString(cmd));
							if (count ~= nil) then
								if (tonumber(count) >= tonumber(max_members)) then
									session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-locked.wav");
									session:hangup("CALL_REJECTED");
								end
							end
						end
					end
				end

			--announce the caller
				if (announce == "true") then
					--prompt for the name of the caller
						session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-say_name.wav");
						session:execute("playback", "tone_stream://v=-7;%%(500,0,500.0)");
					--record the response
						max_len_seconds = 5;
						silence_threshold = "500";
						silence_secs = "2";
						session:recordFile("/tmp/conference-"..uuid..".wav", max_len_seconds, silence_threshold, silence_secs);
				end

			--play a message that the conference is being a recorded
				if (record == "true") then
					session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-recording_started.wav");
				end

			--wait for moderator
				if (wait_mod == "true") then
					if (conference_exists) then
						--continue
					else
						if (member_type == "participant") then
							--set music on hold variable
								cmd = "uuid_setvar "..uuid.." hold_music "..hold_music;
								result = api:executeString(cmd);
							--put the call on hold
								--cmd = "uuid_hold "..uuid;
								--result = api:executeString(cmd);

							--loop until the conference exists
								x = 0;
								y = 0;
								while true do
									--sleep a moment to prevent using unecessary resources
										session:sleep(3000);

									--play a tone
										if (x > 2) then
											session:execute("playback", "tone_stream://L=2;%(25,200,440,40);");
											--session:execute("playback", "ivr/ivr-stay_on_line_call_answered_momentarily.wav");
											x = 1;
										end

									--check if the conference exists
											cmd = "conference "..meeting_uuid.."-"..domain_name.." xml_list";
											result = trim(api:executeString(cmd));
											if (string.sub(result, -9) == "not found") then
												--continue the loop
											else
												--set hold to off
													--cmd = "uuid_hold off "..uuid;
													--result = api:executeString(cmd);
												--exit the loop;
													break;
											end

									--end the conference when the limit is reached
										park_timeout_seconds = 1200;
										if (y > tonumber(park_timeout_seconds)) then
											break;
										end
									--increment the value of x and y
										x = x + 1;
										y = y + 1;
								end
						end
					end
				end

			--set the exit sound
				if (exit_sound ~= nil) then
					session:execute("set","conference_exit_sound="..exit_sound);
				end
				--else
				--	session:execute("set","conference_exit_sound=");
				--end

			--set flags and moderator controls
				if (mute == "true") then
					flags = flags .. "mute";
				end
				if (member_type == "moderator") then
					--set as the moderator
						flags = flags .. "|moderator";
					--when the moderator leaves end the conference
						flags = flags .. "|endconf";
					--set the moderator controls
						session:execute("set","conference_controls=moderator");
				end

			--set the start epoch
				start_epoch = os.time();

			--get the session uuid
				if (record == "true") then
					--get the conference xml_list
					cmd = "conference "..meeting_uuid.."-"..domain_name.." xml_list";
					result = trim(api:executeString(cmd));
					if (conference_exists) then
						--get the content to the <conference> tag
							result = string.match(result,[[<conference (.-)>]],1);
						--get the uuid out of the xml tag contents
							conference_session_uuid = string.match(result,[[uuid="(.-)"]],1);
						--log entry
							freeswitch.consoleLog("INFO","conference_session_uuid: " .. conference_session_uuid .. "\n");
						--record the conference
							cmd = "conference "..meeting_uuid.."-"..domain_name.." record "..recordings_dir.."/archive/"..os.date("%Y").."/"..os.date("%b").."/"..os.date("%d") .."/"..conference_session_uuid..".wav";
							response = api:executeString(cmd);
						--play a message that the conference is being a recorded
							cmd = "conference "..meeting_uuid.."-"..domain_name.." play "..sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-recording_started.wav";
							freeswitch.consoleLog("notice", "[conference] ".. cmd .."\n");
							response = api:executeString(cmd);
					end
				end

			--announce the caller
				if (announce == "true") then
					--announce the caller - play the recording
						cmd = "conference "..meeting_uuid.."-"..domain_name.." play /tmp/conference-"..uuid..".wav";
						freeswitch.consoleLog("notice", "[conference] ".. cmd .."\n");
						response = api:executeString(cmd);
					--play has entered the conference
						cmd = "conference "..meeting_uuid.."-"..domain_name.." play "..sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-has_joined.wav";
						freeswitch.consoleLog("notice", "[conference] ".. cmd .."\n");
						response = api:executeString(cmd);
				else
					if (enter_sound ~= nil) then
						cmd = "conference "..meeting_uuid.."-"..domain_name.." play "..enter_sound;
						response = api:executeString(cmd);
					end
				end

			--send the call to the conference
				cmd = meeting_uuid.."-"..domain_name.."@"..profile.."+flags{".. flags .."}";
				session:execute("conference", cmd);
		end

	end
