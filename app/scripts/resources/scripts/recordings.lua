--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2010 - 2019
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set the variables
	pin_number = "";
	max_tries = 3;
	digit_timeout = 3000;
	sounds_dir = "";
	recordings_dir = "";
	file_name = "";
	recording_number = "";
	recording_id = "";
	recording_prefix = "";

--include config.lua
	require "resources.functions.config";

--add functions
	require "resources.functions.mkdir";
	require "resources.functions.explode";

--setup the database connection
	local Database = require "resources.functions.database";
	local db = dbh or Database.new('system');

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--get the domain_uuid
	if (session:ready()) then
		domain_uuid = session:getVariable("domain_uuid");
	end

--initialize the recordings
	api = freeswitch.API();

--load lazy settings library
	local Settings = require "resources.functions.lazy_settings";

--get the recordings settings
	local settings = Settings.new(db, domain_name, domain_uuid);

--set the storage type and path
	storage_type = settings:get('recordings', 'storage_type', 'text') or '';
	storage_path = settings:get('recordings', 'storage_path', 'text') or '';
	if (storage_path ~= '') then
		storage_path = storage_path:gsub("${domain_name}",  session:getVariable("domain_name"));
		storage_path = storage_path:gsub("${domain_uuid}", domain_uuid);
	end

--set the recordings variables
	local recording_max_length = settings:get('recordings', 'recording_max_length', 'numeric') or 90;
	local recording_silence_threshold = settings:get('recordings', 'recording_silence_threshold', 'numeric') or 200;
	local recording_silence_seconds = settings:get('recordings', 'recording_silence_seconds', 'numeric') or 3;

--set the temp directory
	temp_dir = settings:get('server', 'temp', 'dir') or nil;

--dtmf call back function detects the "#" and ends the call
	function onInput(s, type, obj)
		if (type == "dtmf" and obj['digit'] == '#') then
			return "break";
		end
	end

--start the recording
	function begin_record(session, sounds_dir, recordings_dir)

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end
			recording_id = session:getVariable("recording_id");
			recording_prefix = session:getVariable("recording_prefix");
			recording_name = session:getVariable("recording_name");
			record_ext = session:getVariable("record_ext");
			domain_name = session:getVariable("domain_name");
			time_limit_secs = session:getVariable("time_limit_secs");
			silence_thresh = session:getVariable("silence_thresh");
			silence_hits = session:getVariable("silence_hits");
			if (not time_limit_secs) then time_limit_secs = '10800'; end
			if (not silence_thresh) then silence_thresh = '200'; end
			if (not silence_hits) then silence_hits = '10'; end

		--select the recording number and set the recording filename
			if (recording_id == nil) then
				min_digits = 1;
				max_digits = 20;
				session:sleep(1000);
				recording_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-id_number.wav", "", "\\d+");
				session:setVariable("recording_id", recording_id);
				recording_filename = recording_prefix..recording_id.."."..record_ext;
			elseif (tonumber(recording_id) ~= nil) then
				recording_filename = recording_prefix..recording_id.."."..record_ext;
			else
				recording_filename = recording_prefix.."."..record_ext;
			end

		--set the default recording name if one was not provided
			if (recording_name) then
				--recording name is provided do nothing
			else
				--set a default recording_name
				recording_name = recording_filename;
			end

		--prompt for the recording
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-recording_started.wav");
			session:execute("set", "playback_terminators=#");

		--make the directory
			mkdir(recordings_dir);

		--begin recording
			if (storage_type == "base64") then
				--include the file io
					local file = require "resources.functions.file"

				--record the file to the file system
					-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs);
					session:execute("record", recordings_dir .."/".. recording_filename);

				--show the storage type
					freeswitch.consoleLog("notice", "[recordings] ".. storage_type .. "\n");

				--read file content as base64 string
					recording_base64 = assert(file.read_base64(recordings_dir .. "/" .. recording_filename));

			elseif (storage_type == "http_cache") then
				freeswitch.consoleLog("notice", "[recordings] ".. storage_type .. " ".. storage_path .."\n");
				session:execute("record", storage_path .."/"..recording_filename);
			else
				freeswitch.consoleLog("notice", "[recordings] ".. storage_type .. " ".. recordings_dir .."\n");
				-- record,Record File,<path> [<time_limit_secs>] [<silence_thresh>] [<silence_hits>]
				session:execute("record", "'"..recordings_dir.."/"..recording_filename.."' "..time_limit_secs.." "..silence_thresh.." "..silence_hits);
			end

		--setup the database connection
			local Database = require "resources.functions.database";
			local db = dbh or Database.new('system');

		--get the description of the previous recording
			sql = "SELECT recording_description, recording_name ";
			sql = sql .. " FROM v_recordings ";
			sql = sql .. "where domain_uuid = :domain_uuid ";
			sql = sql .. "and recording_filename = :recording_filename ";
			sql = sql .. "limit 1";
			local params = {domain_uuid = domain_uuid, recording_filename = recording_filename};
			local row = db:first_row(sql, params);
			if (row) then
				recording_description = row.recording_description;
				recording_name = row.recording_name;
			end

		--delete the previous recording
			sql = "delete from v_recordings ";
			sql = sql .. "where domain_uuid = :domain_uuid ";
			sql = sql .. "and recording_filename = :recording_filename";
			db:query(sql, {domain_uuid = domain_uuid, recording_filename = recording_filename});

		--get a new uuid
			recording_uuid = api:execute("create_uuid");

		--save the message to the voicemail messages
			local array = {}
			table.insert(array, "INSERT INTO v_recordings ");
			table.insert(array, "(");
			table.insert(array, "recording_uuid, ");
			table.insert(array, "domain_uuid, ");
			table.insert(array, "recording_filename, ");
			table.insert(array, "recording_description, ");
			if (storage_type == "base64") then
				table.insert(array, "recording_base64, ");
			end
			table.insert(array, "recording_name ");
			table.insert(array, ") ");
			table.insert(array, "VALUES ");
			table.insert(array, "( ");
			table.insert(array, ":recording_uuid, ");
			table.insert(array, ":domain_uuid, ");
			table.insert(array, ":recording_filename, ");
			table.insert(array, ":recording_description, ");
			if (storage_type == "base64") then
				table.insert(array, ":recording_base64, ");
			end
			table.insert(array, ":recording_name ");
			table.insert(array, ") ");
			sql = table.concat(array, "\n");

			local params = {
				recording_uuid = recording_uuid;
				domain_uuid = domain_uuid;
				recording_filename = recording_filename;
				recording_name = recording_name;
				recording_description = recording_description;
				recording_base64 = recording_base64;
			};

			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[recording] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
			end

			if (storage_type == "base64") then
				local Database = require "resources.functions.database"
				local dbh = Database.new('system', 'base64');
				dbh:query(sql, params);
				dbh:release();
			else
				--setup the database connection
				local Database = require "resources.functions.database";
				local db = dbh or Database.new('system');
				db:query(sql, params);
			end

		--preview the recording
			session:streamFile(recordings_dir.."/"..recording_filename);

		--approve the recording, to save the recording press 1 to re-record press 2
			min_digits="0" max_digits="1" max_tries = "1"; digit_timeout = "100";
			digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "voicemail/vm-save_recording.wav", "", "\\d+");

			if (string.len(digits) == 0) then
				min_digits="0" max_digits="1" max_tries = "1"; digit_timeout = "100";
				digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "voicemail/vm-press.wav", "", "\\d+");
			end

			if (string.len(digits) == 0) then
				min_digits="0" max_digits="1" max_tries = "1"; digit_timeout = "100";
				digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "digits/1.wav", "", "\\d+");
			end

			if (string.len(digits) == 0) then
				min_digits="0" max_digits="1" max_tries = "1"; digit_timeout = "100";
				digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "voicemail/vm-rerecord.wav", "", "\\d+");
			end

			if (string.len(digits) == 0) then
				min_digits="0" max_digits="1" max_tries = "1"; digit_timeout = "100";
				digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "voicemail/vm-press.wav", "", "\\d+");
			end

			if (string.len(digits) == 0) then
				min_digits="1" max_digits="1" max_tries = "1"; digit_timeout = "5000";
				digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "digits/2.wav", "", "\\d+");
			end

			if (digits == "1") then
				--recording saved, hangup
				session:streamFile("voicemail/vm-saved.wav");
				return;
			elseif (digits == "2") then
				--reset the digit timeout
					digit_timeout = "3000";
				--delete the old recording
					os.remove (recordings_dir.."/"..recording_filename);
					--session:execute("system", "rm "..);
				--make a new recording
					begin_record(session, sounds_dir, recordings_dir);
			else
				--recording saved, hangup
					session:streamFile("voicemail/vm-saved.wav");
				return;
			end
	end

if (session:ready()) then
	session:answer();

	--get the dialplan variables and set them as local variables
		pin_number = session:getVariable("pin_number");
		sounds_dir = session:getVariable("sounds_dir");
		domain_name = session:getVariable("domain_name");
		domain_uuid = session:getVariable("domain_uuid");

	--add the domain name to the recordings directory
		recordings_dir = recordings_dir .. "/"..domain_name;

	--if a recording directory is specified, use that instead
		if (storage_path ~= nil and string.len(storage_path) > 0) then recordings_dir = storage_path; end

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--if the pin number is provided then require it
		if (pin_number) then
			freeswitch.consoleLog("notice", "[recordings] pin_number: ".. pin_number .. "\n");
			min_digits = string.len(pin_number);
			max_digits = string.len(pin_number)+1;
			digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
			if (digits == pin_number) then
				--pin is correct
				freeswitch.consoleLog("notice", "[recordings] pin_number: correct \n");
			else
				freeswitch.consoleLog("notice", "[recordings] pin_number: incorrect \n");
				session:streamFile("phrase:voicemail_fail_auth:#");
				session:hangup("NORMAL_CLEARING");
				return;
			end
		end

	--start recording
		begin_record(session, sounds_dir, recordings_dir);

	--hangup the call
		session:hangup();

end
