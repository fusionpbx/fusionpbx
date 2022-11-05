--	conference_center/index.lua
--	Part of FusionPBX
--	Copyright (C) 2013 - 2021 Mark J Crane <markjcrane@fusionpbx.com>
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
--	THIS SOFTWARE IS PROVIDED AS ''IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>

--set variables
	flags = "";
	max_tries = 3;
	digit_timeout = 5000;

--debug
	debug["sql"] = false;

--includes
	require "resources.functions.config";
	local Database = require "resources.functions.database";
	local Settings = require "resources.functions.lazy_settings"
	dbh = Database.new('system');
	local settings = Settings.new(dbh, domain_name, domain_uuid);

--get the settings
	session_enabled = settings:get('conference_center', 'session_enabled', 'boolean');
	account_code_enabled = settings:get('conference_center', 'account_code_enabled', 'boolean');
	http_protocol = settings:get('domain', 'http_protocol', 'text') or "https";

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--prepare the api object
	api = freeswitch.API();

--general functions
	require "resources.functions.base64";
	require "resources.functions.trim";
	require "resources.functions.file_exists";
	require "resources.functions.explode";
	require "resources.functions.format_seconds";
	require "resources.functions.mkdir";

--get the session variables
	uuid = session:getVariable("uuid");

--answer the call
	session:answer();

--get record_ext
	record_ext = session:getVariable("record_ext");
	if (not record_ext) then
			record_ext = "wav";
	end

--define a function to send email
	function send_email(email, attachment, default_language, default_dialect)

		--require the email address to send the email
			if (string.len(email) > 2) then

				--format the message length and date
					--message_length_formatted = format_seconds(message_length);
					--if (debug["info"]) then
					--	freeswitch.consoleLog("notice", "[conference_center] message length: " .. message_length .. "\n");
					--end
					local conference_date_end = os.date("%A, %d %b %Y %I:%M %p");
					--os.time();

				--prepare the files
					file_subject = scripts_dir.."/app/conference_center/resources/templates/"..default_language.."/"..default_dialect.."email_subject.tpl";
					file_body = scripts_dir.."/app/conference_center/resources/templates/"..default_language.."/"..default_dialect.."/email_body.tpl";
					if (not file_exists(file_subject)) then
						file_subject = scripts_dir.."/app/conference_center/resources/templates/en/us/email_subject.tpl";
						file_body = scripts_dir.."/app/conference_center/resources/templates/en/us/email_body.tpl";
					end

				--get the moderator_pin
					local sql = "SELECT moderator_pin FROM v_conference_rooms WHERE conference_room_uuid = :conference_room_uuid";
					local params = {conference_room_uuid = conference_room_uuid}
					freeswitch.consoleLog("notice", "[voicemail] sql: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					dbh:query(sql, params, function(row)
						moderator_pin = string.lower(row["moderator_pin"]);
					end);

				--get the link_address
					link_address = http_protocol.."://"..domain_name..project_path;

				--prepare the headers
					headers = {}
					headers["X-FusionPBX-Domain-UUID"] = domain_uuid;
					headers["X-FusionPBX-Domain-Name"] = domain_name;
					headers["X-FusionPBX-Call-UUID"]   = 'na';
					headers["X-FusionPBX-Email-Type"]  = 'conference';

				--remove quotes from caller id name and number
					caller_id_name = caller_id_name:gsub("'", "&#39;");
					caller_id_name = caller_id_name:gsub([["]], "&#34;");
					caller_id_number = caller_id_number:gsub("'", "&#39;");
					caller_id_number = caller_id_number:gsub([["]], "&#34;");

				--prepare the subject
					local f = io.open(file_subject, "r");
					local subject = f:read("*all");
					f:close();
					subject = subject:gsub("${moderator_pin}", moderator_pin);
					subject = subject:gsub("${conference_date_end}", conference_date_end);
					--subject = subject:gsub("${conference_duration}", message_length_formatted);
					subject = subject:gsub("${domain_name}", domain_name);
					subject = subject:gsub("${link_address}", link_address);
					subject = trim(subject);
					subject = '=?utf-8?B?'..base64.enc(subject)..'?=';

				--prepare the body
					local f = io.open(file_body, "r");
					local body = f:read("*all");
					f:close();
					body = body:gsub("${moderator_pin}", moderator_pin);
					body = body:gsub("${conference_date_end}", conference_date_end);
					body = body:gsub("${conference_uuid}", conference_session_uuid);
					--body = body:gsub("${conference_duration}", message_length_formatted);
					body = body:gsub("${domain_name}", domain_name);
					body = body:gsub("${link_address}", link_address);
					body = body:gsub(" ", "&nbsp;");
					body = body:gsub("%s+", "");
					body = body:gsub("&nbsp;", " ");
					body = body:gsub("\n", "");
					body = body:gsub("\n", "");
					body = trim(body);

				--send the email
					if (string.len(attachment) > 4) then
						send_mail(headers,
							email,
							email,
							{subject, body},
							attachment
							);
					else
						send_mail(headers,
							email,
							email,
							{subject, body}
							);
					end
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] cmd: " .. email .. " " .. subject .. " " .. body .. "\n");
					end
			end

	end

--define the session hangup
	function session_hangup_hook()

		--get the session variables
			conference_session_detail_uuid = api:executeString("create_uuid");
			--conference_name = session:getVariable("conference_name");
			conference_session_uuid = session:getVariable("conference_uuid");
			--conference_recording = session:getVariable("conference_recording");
			conference_moderator = session:getVariable("conference_moderator");
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			--recording = session:getVariable("recording");
			domain_name = session:getVariable("domain_name");

		--set the end epoch
			end_epoch = os.time();

		--connect to the database
			dbh = Database.new('system');

		--get the conference sessions
			if (conference_session_uuid) then
				local sql = [[SELECT count(*) as num_rows
					FROM v_conference_sessions
					WHERE conference_session_uuid = :conference_session_uuid]];
				local params = {conference_session_uuid = conference_session_uuid};
				dbh:query(sql, params, function(row)
					num_rows = string.lower(row["num_rows"]);
				end);
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[conference center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "; Rows: "..num_rows.."\n");
				end
				if (tonumber(num_rows) == 0) then
					local sql = {}
					table.insert(sql, "INSERT INTO v_conference_sessions ");
					table.insert(sql, "(");
					table.insert(sql, "conference_session_uuid, ");
					table.insert(sql, "domain_uuid, ");
					table.insert(sql, "meeting_uuid, ");
					--if (conference_recording) then
					--	table.insert(sql, "recording, ");
					--end
					--if (wait_mod) then
					--	table.insert(sql, "wait_mod, ");
					--end
					--table.insert(sql, "start_epoch, ");
					table.insert(sql, "profile ");
					table.insert(sql, ") ");
					table.insert(sql, "VALUES ");
					table.insert(sql, "( ");
					table.insert(sql, ":conference_session_uuid, ");
					table.insert(sql, ":domain_uuid, ");
					table.insert(sql, ":meeting_uuid, ");
					--if (conference_recording) then
					--	table.insert(sql, ":conference_recording, ");
					--end
					--if (wait_mod) then
					--	table.insert(sql, ":wait_mod, ");
					--end
					--table.insert(sql, ":start_epoch, ");
					table.insert(sql, ":profile ");
					table.insert(sql, ") ");

					sql = table.concat(sql, "\n");

					local params = {
						conference_session_uuid = conference_session_uuid;
						domain_uuid = domain_uuid;
						meeting_uuid = conference_room_uuid;
						-- conference_recording = conference_recording;
						-- wait_mod = wait_mod;
						-- start_epoch = start_epoch;
						profile = profile;
					};

					dbh:query(sql, params);

					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[conference center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
				end
			end

		--add the conference sessions details
			if (conference_session_uuid) then
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
				table.insert(sql, ":conference_session_detail_uuid, ");
				table.insert(sql, ":domain_uuid, ");
				table.insert(sql, ":conference_session_uuid, ");
				table.insert(sql, ":meeting_uuid, ");
				table.insert(sql, ":username, ");
				table.insert(sql, ":caller_id_name, ");
				table.insert(sql, ":caller_id_number, ");
				table.insert(sql, ":network_addr, ");
				table.insert(sql, ":uuid, ");
				if (conference_moderator) then
					table.insert(sql, ":conference_moderator, ");
				end
				table.insert(sql, ":start_epoch, ");
				table.insert(sql, ":end_epoch ");
				table.insert(sql, ") ");
				sql = table.concat(sql, "\n");
				local params = {
					conference_session_detail_uuid = conference_session_detail_uuid;
					domain_uuid = domain_uuid;
					conference_session_uuid = conference_session_uuid;
					meeting_uuid = conference_room_uuid;
					username = username;
					caller_id_name = caller_id_name;
					caller_id_number = caller_id_number;
					network_addr = network_addr;
					uuid = uuid;
					conference_moderator = conference_moderator;
					start_epoch = start_epoch;
					end_epoch = end_epoch;
				};
				dbh:query(sql, params);
			end

		--if the conference is empty
			if (conference_session_uuid) then
				cmd = "conference "..conference_room_uuid.."@"..domain_name.." xml_list";
				result = trim(api:executeString(cmd));
				if (string.sub(result, -9) == "not found") then
					--get the conference start_epoch
						local sql = [[SELECT start_epoch
							FROM v_conference_session_details
							WHERE conference_session_uuid = :conference_session_uuid
							ORDER BY start_epoch ASC
							LIMIT 1]];
						local params = {conference_session_uuid = conference_session_uuid};
						dbh:query(sql, params, function(row)
							start_epoch = string.lower(row["start_epoch"]);
						end);
						--freeswitch.consoleLog("notice", "[conference center] <conference_start_epoch> sql: " .. sql .. "; params:" .. json.encode(params) .. "\n");

					--set the conference_recording
						conference_recording = recordings_dir.."/archive/"..os.date("%Y", start_epoch).."/"..os.date("%b", start_epoch).."/"..os.date("%d", start_epoch) .."/"..conference_session_uuid .. "." .. record_ext;
						freeswitch.consoleLog("notice", "[conference center] conference_recording: "..conference_recording.."\n");
					--conference has ended set the end_epoch
						local sql = {}
						table.insert(sql, "update v_conference_sessions set ");
						table.insert(sql, "recording = :conference_recording, ");
						table.insert(sql, "start_epoch = :start_epoch, ");
						table.insert(sql, "end_epoch = :end_epoch ");
						table.insert(sql, "where conference_session_uuid = :conference_session_uuid ");
						sql = table.concat(sql, "\n");
						local params = {
							conference_recording = conference_recording;
							start_epoch = start_epoch;
							end_epoch = end_epoch;
							conference_session_uuid = conference_session_uuid;
						};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[conference center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params);
					--convert the wav to an mp3
						if (record == "true") then
							--cmd = "sox "..conference_recording..".wav -r 16000 -c 1 "..conference_recording..".mp3";
							cmd = "/usr/bin/lame -b 32 --resample 8 -a "..conference_recording..".wav "..conference_recording..".mp3";
							freeswitch.consoleLog("notice", "[conference center] cmd: " .. cmd .. "\n");
							os.execute(cmd);
							--if (file_exists(conference_recording..".mp3")) then
							--	cmd = "rm "..conference_recording..".wav";
							--	os.execute(cmd);
							--end
						end
				end
			end

		--close the database connection
			if (conference_session_uuid) then
				dbh:release();
			end
	end

--make sure the session is ready
	if (session:ready()) then

		--answer the call
			session:preAnswer();

		--set the session sleep
			session:sleep(1000);

		--get session variables
			sounds_dir = session:getVariable("sounds_dir");
			hold_music = session:getVariable("hold_music");
			domain_name = session:getVariable("domain_name");
			pin_number = session:getVariable("pin_number");
			domain_uuid = session:getVariable("domain_uuid");
			destination_number = session:getVariable("destination_number");
			caller_id_number = session:getVariable("caller_id_number");
			--freeswitch.consoleLog("notice", "[conference center] destination_number: " .. destination_number .. "\n");
			--freeswitch.consoleLog("notice", "[conference center] caller_id_number: " .. caller_id_number .. "\n");

		--add the domain name to the recordings directory
			recordings_dir = recordings_dir .. "/"..domain_name;

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--get the domain_uuid
			if (domain_name ~= nil and domain_uuid == nil) then
				local sql = "SELECT domain_uuid FROM v_domains ";
				sql = sql .. "WHERE domain_name = :domain_name ";
				local params = {domain_name = domain_name};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[conference center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(rows)
					domain_uuid = string.lower(rows["domain_uuid"]);
				end);
			end

		--conference center details
			local sql = [[SELECT * FROM v_conference_centers
				WHERE domain_uuid = :domain_uuid
				AND conference_center_extension = :destination_number]];
			local params = {domain_uuid = domain_uuid, destination_number = destination_number};
			dbh:query(sql, params, function(row)
				conference_center_uuid = string.lower(row["conference_center_uuid"]);
				conference_center_greeting = row["conference_center_greeting"];
			end);
			if (conference_center_greeting == '') then
				conference_center_greeting = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-pin.wav";
			end

		--connect to the switch database
			local dbh_switch = Database.new('switch')

		--check if someone has already joined the conference
			local_hostname = trim(api:execute("switchname", ""));
			freeswitch.consoleLog("notice", "[conference center] local_hostname is " .. local_hostname .. "\n");
			sql = "SELECT hostname FROM channels WHERE application = 'conference' "
				.. "AND dest = :destination_number AND cid_num <> :caller_id_number LIMIT 1";
			params = {destination_number = destination_number, caller_id_number = caller_id_number};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[conference center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			dbh_switch:query(sql, params, function(rows)
				conference_hostname = rows["hostname"];
			end);

		--close the database connection
			dbh_switch:release();

		--if conference hosntame exist, then we bridge there
			if (conference_hostname ~= nil) then
				freeswitch.consoleLog("notice", "[conference center] conference_hostname is " .. conference_hostname .. "\n");
				if (conference_hostname ~= local_hostname) then
					session:execute("bridge","sofia/internal/" .. destination_number .. "@" .. domain_name .. ";fs_path=sip:" .. conference_hostname);
				end
			end

		--call not bridged, so we answer
			session:answer();

		--set the hangup hook function
			if (session_enabled == 'true') then
				freeswitch.consoleLog("notice", "[conference center] session_enabled: true\n");
				session:setHangupHook("session_hangup_hook");
			else
				freeswitch.consoleLog("notice", "[conference center] session_enabled: false\n");
			end

		--sounds
			enter_sound = "tone_stream://v=-20;%(100,1000,100);v=-20;%(90,60,440);%(90,60,620)";
			exit_sound = "tone_stream://v=-20;%(90,60,620);/%(90,60,440)";

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

		--define the function get_pin_number
			function get_pin_number(domain_uuid, prompt_audio_file)
				--if the pin number is provided then require it
					if (not pin_number) then
						min_digits = 2;
						max_digits = 20;
						max_tries = 1;
						digit_timeout = 5000;
						pin_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", prompt_audio_file, "", "\\d+");
						--remove non numerics
						pin_number = pin_number:gsub("%p","")
					end
				--use the pin_number to find the conference room
					if (pin_number ~= "") then
						local sql = [[SELECT * FROM v_conference_rooms 
							WHERE domain_uuid = :domain_uuid
							AND (moderator_pin = :pin_number or participant_pin = :pin_number)
							AND enabled = 'true'
							AND (
									( start_datetime <> '' AND start_datetime is not null AND start_datetime <= :timestamp ) OR
									( start_datetime = '' OR start_datetime is null )
								)
							AND (
									( stop_datetime <> '' AND stop_datetime is not null AND stop_datetime > :timestamp ) OR
									( stop_datetime = '' OR stop_datetime is null )
								) ]];
						local params = {
							domain_uuid = domain_uuid;
							pin_number = pin_number;
							timestamp = os.date("%Y-%m-%d %X");
						};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[conference center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params, function(row)
							conference_room_uuid = string.lower(row["conference_room_uuid"]);
						end);
					end
				--if the conference room was not found then return nil
					if (conference_room_uuid == nil) then
						return nil;
					else
						return pin_number;
					end
			end

		--get the pin
			pin_number = session:getVariable("pin_number");
			pin_number = get_pin_number(domain_uuid, conference_center_greeting);
			if (pin_number == nil) then
				pin_number = get_pin_number(domain_uuid, conference_center_greeting);
			end
			if (pin_number == nil) then
				session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-bad-pin.wav");
				pin_number = get_pin_number(domain_uuid, conference_center_greeting);
			end
			if (pin_number == nil) then
				session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-bad-pin.wav");
				pin_number = get_pin_number(domain_uuid, conference_center_greeting);
			end
			if (pin_number ~= nil) then
				local sql = [[SELECT * FROM v_conference_rooms
					WHERE domain_uuid = :domain_uuid
					AND conference_center_uuid = :conference_center_uuid
					AND (moderator_pin = :pin_number or participant_pin = :pin_number)
					AND enabled = 'true'
				]];
				local params = {
					domain_uuid = domain_uuid;
					conference_center_uuid = conference_center_uuid;
					pin_number = pin_number;
				};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[conference center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					conference_room_uuid = string.lower(row["conference_room_uuid"]);
					conference_room_name = string.lower(row["conference_room_name"]);
					--meeting_uuid = string.lower(row["meeting_uuid"]);
					record = string.lower(row["record"]);
					profile = string.lower(row["profile"]);
					max_members = row["max_members"];
					wait_mod = row["wait_mod"];
					moderator_endconf = row["moderator_endconf"];
					moderator_pin = row["moderator_pin"];
					participant_pin = row["participant_pin"];
					announce_name = row["announce_name"];
					announce_count = row["announce_count"];
					announce_recording = row["announce_recording"];
					mute = row["mute"];
					sounds = row["sounds"];
					created = row["created"];
					created_by = row["created_by"];
					enabled = row["enabled"];
					description = row["description"];
					return pin_number;
				end);
				freeswitch.consoleLog("INFO","conference_room_uuid: " .. conference_room_uuid .. "\n");
			end

		--set the member type
			if (pin_number == moderator_pin) then
				member_type = "moderator";
			end
			if (pin_number == participant_pin) then
				member_type = "participant";
			end

		--get the account code
			if (account_code_enabled == 'true' and member_type == 'moderator') then
				--request the account code
					min_digits = 2;
					max_digits = 20;
					account_code = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");
					--user_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-please_enter_extension_followed_by_pound.wav", "", "\\d+");

				--update the account code
					local sql = {}
					table.insert(sql, "update v_conference_rooms ");
					table.insert(sql, "set account_code = :account_code ");
					table.insert(sql, "where conference_center_uuid = :conference_center_uuid ");
					sql = table.concat(sql, "\n");
					local params = {
						conference_center_uuid = conference_center_uuid;
						account_code = account_code;
					};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[conference center] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);
			end

		--close the database connection
			dbh:release();

		--set the conference_room_uuid
			if (conference_room_uuid) then
				session:setVariable("conference_room_uuid", conference_room_uuid);
			end

		--set the conference_room_name
			if (conference_room_name) then
				session:setVariable("conference_room_name", conference_room_name);
			end

		--set the meeting uuid
			if (meeting_uuid) then
				session:setVariable("meeting_uuid", meeting_uuid);
			end

		--check if the conference exists
			if (conference_room_uuid and domain_name) then
				cmd = "conference "..conference_room_uuid.."@"..domain_name.." xml_list";
				result = trim(api:executeString(cmd));
				if (string.sub(result, -9) == "not found") then
					conference_exists = false;
				else
					conference_exists = true;
				end
			end

		--check if the conference is locked
			if (string.find(result, [[locked="true"]]) == nil) then
				conference_locked = false;
			else
				conference_locked = true;
			end

		--set a conference parameter
			if (max_members ~= nil) then
				if (tonumber(max_members) > 0) then
					--max members must be 2 or more
					session:execute("set","conference_max_members="..max_members);
					if (conference_exists) then
						cmd = "conference "..conference_room_uuid.."@"..domain_name.." get count";
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
			if (conference_locked) then
				announce = "false";
			end
			if (announce_name == "true") then
				--prompt for the name of the caller
					session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-say_name.wav");
					session:execute("playback", "tone_stream://v=-7;%%(500,0,500.0)");
				--record the response
					max_len_seconds = 5;
					silence_threshold = "500";
					silence_secs = "3";
					session:recordFile(temp_dir:gsub("\\","/") .. "/conference-"..uuid..".wav", max_len_seconds, silence_threshold, silence_secs);
			end

		--play a message that the conference is being a recorded
			--if (record == "true") then
				--session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-recording_started.wav");
			--end

		--wait for moderator
			if (wait_mod == "true") then
				if (conference_exists) then
					--continue
				else
					if (member_type == "participant") then
						profile = "wait-mod";
					end
				end
			end

		--set the exit sound
			if (sounds == "true") then
				session:execute("set","conference_exit_sound="..exit_sound);
			else
				session:execute("set","conference_enter_sound=none");
				session:execute("set","conference_exit_sound=none");
			end

		--set flags and moderator controls
			if (wait_mod == "true") then
				if (member_type == "participant") then
					flags = flags .. "wait-mod";
				end
			end
			if (mute == "true") then
				if (member_type == "participant") then
					flags = flags .. "|mute";
				end
			end
			
			if (member_type == "moderator") then
				--set as the moderator
					flags = flags .. "|moderator";
				--when the moderator leaves end the conference
					if (moderator_endconf == "true") then
						flags = flags .. "|endconf";
					end
			end

		--get the conference xml_list
			cmd = "conference "..conference_room_uuid.."@"..domain_name.." xml_list";
			freeswitch.consoleLog("INFO","" .. cmd .. "\n");
			result = trim(api:executeString(cmd));

		--get the content to the <conference> tag
			result = string.match(result,[[<conference (.-)>]],1);

		--get the uuid out of the xml tag contents
			if (result ~= nil) then
				conference_session_uuid = string.match(result,[[uuid="(.-)"]],1);
			end

		--log entry
			if (conference_session_uuid ~= nil) then
				freeswitch.consoleLog("INFO","conference_session_uuid: " .. conference_session_uuid .. "\n");
			end

		--set the start epoch
			start_epoch = os.time();

		--set the recording variable
			if (conference_session_uuid ~= nil) then
				if (record == "true") then
					recordings_dir_2 = recordings_dir.."/archive/"..os.date("%Y", start_epoch).."/"..os.date("%b", start_epoch).."/"..os.date("%d", start_epoch);
					mkdir(recordings_dir_2);
					recording = recordings_dir_2.."/"..conference_session_uuid;
					session:execute("set","recording="..recording);
					session:execute("set","conference_session_uuid="..conference_session_uuid);
				end
			end

		--record the conference
			if (announce_recording == "true") then
				if (record == "true") then
					--play a message that the conference is being a recorded
						session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-recording_started.wav");
					--play a message that the conference is being a recorded
						--cmd = "conference "..meeting_uuid.."@"..domain_name.." play "..sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-recording_started.wav";
						--freeswitch.consoleLog("notice", "[conference center] ".. cmd .."\n");
						--response = api:executeString(cmd);
				end
			end

		--announce the caller
			if (announce_name == "true") then
				--announce the caller - play the recording
					cmd = "conference "..conference_room_uuid.."@"..domain_name.." play " .. temp_dir:gsub("\\", "/") .. "/conference-"..uuid..".wav";
					--freeswitch.consoleLog("notice", "[conference center] ".. cmd .."\n");
					response = api:executeString(cmd);
				--play has entered the conference
					cmd = "conference "..conference_room_uuid.."@"..domain_name.." play "..sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-has_joined.wav";
					--freeswitch.consoleLog("notice", "[conference center] ".. cmd .."\n");
					response = api:executeString(cmd);
			else
				if (not conference_locked) then
					if (sounds == "true") then
						cmd = "conference "..conference_room_uuid.."@"..domain_name.." play "..enter_sound;
						response = api:executeString(cmd);
					end
				end
			end

		--get the conference member count
			cmd = "conference "..conference_room_uuid.."@"..domain_name.." list count";
			--freeswitch.consoleLog("notice", "[conference center] cmd: ".. cmd .."\n");
			member_count = api:executeString(cmd);
			if (string.sub(trim(member_count), -9) == "not found") then
				member_count = "0";
			end

		--play member count
			if (announce_count == "true") then
				if (member_count == "1") then
					--there is one other member in this conference
						session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-one_other_member_conference.wav");
				elseif (member_count == "0") then
					--conference profile defines the alone sound file
				else
					--say the count
						session:execute("say", default_language.." number pronounced "..member_count);
					--members in this conference
						session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-members_in_conference.wav");
				end
			end
		--record the conference
			if (record == "true") then
				if (wait_mod == "true" and member_type ~= "moderator") then
					--don't start recording yet
				else
					cmd="sched_api +3 none lua "..scripts_dir.."/app/conference_center/resources/scripts/start_recording.lua "..conference_room_uuid.." "..domain_name.." "..record_ext;
					api:executeString(cmd);
				end
			end

		--use default profile if not provided
			if (profile == null) then
				profile = 'default';
			end

		--send the call to the conference
			cmd = conference_room_uuid.."@"..domain_name.."@"..profile.."+flags{".. flags .."}";
			freeswitch.consoleLog("INFO","[conference center] conference " .. cmd .. "\n");
			session:execute("conference", cmd);
	end
