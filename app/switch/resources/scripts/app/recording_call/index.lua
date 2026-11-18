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
--	Copyright (C) 2026
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--	Record a new recording by phone. The web interface (app/recordings/recording_call.php)
--	originates a call to an extension and runs this script when the call is answered:
--	originate {recording_call_uuid=...,recording_call_filename=...}user/101@domain &lua('app.lua recording_call')
--	The caller records after the tone, then can listen (1), save (2), re-record (3) or delete (7).
--	Hanging up after recording saves the recording.
--	With recording_call_type=greeting it records a voicemail greeting instead, using
--	recording_call_voicemail_id and recording_call_greeting_id, and makes it the active greeting.

--include config.lua
	require "resources.functions.config";

--add functions
	require "resources.functions.mkdir";
	require "resources.functions.file_exists";

--make sure the session is ready
	if (not session or not session:ready()) then
		return;
	end

--set the state, the web interface reads it with uuid_getvar
	local function set_state(state)
		if (session:ready()) then
			session:setVariable("recording_call_state", state);
		end
	end

--get the variables
	local domain_uuid = session:getVariable("domain_uuid") or '';
	local domain_name = session:getVariable("domain_name") or '';
	local user_uuid = session:getVariable("recording_call_user_uuid");
	local recording_uuid = session:getVariable("recording_call_uuid") or '';
	local recording_filename = session:getVariable("recording_call_filename") or '';
	local recording_name = session:getVariable("recording_call_name") or '';
	local recording_type = session:getVariable("recording_call_type") or 'recording';
	local voicemail_id = session:getVariable("recording_call_voicemail_id") or '';
	local greeting_id = session:getVariable("recording_call_greeting_id") or '';
	local call_uuid = session:get_uuid();

--validate the variables
	local uuid_pattern = "^%x%x%x%x%x%x%x%x%-%x%x%x%x%-%x%x%x%x%-%x%x%x%x%-%x%x%x%x%x%x%x%x%x%x%x%x$";
	if (not domain_uuid:find(uuid_pattern)
		or not recording_uuid:find(uuid_pattern)
		or not (recording_filename:find("^[%w_%-%.]+%.wav$") or recording_filename:find("^[%w_%-%.]+%.mp3$"))
		or recording_filename:find("%.%.")
		or not domain_name:find("^[%w_%-%.]+$")
		or domain_name:find("%.%.")
		or not (recording_type == 'recording' or recording_type == 'greeting')
		or (recording_type == 'greeting' and (not voicemail_id:find("^[%w_%-]+$")
			or not greeting_id:find("^[1-9]$")
			or recording_filename ~= "greeting_" .. greeting_id .. ".wav"))) then
		freeswitch.consoleLog("err", "[recording_call] invalid variables\n");
		session:hangup("NORMAL_CLEARING");
		return;
	end
	if (recording_name == '') then
		recording_name = recording_filename:gsub("%.%w+$", "");
	end

--setup the database connection
	local Database = require "resources.functions.database";
	local db = dbh or Database.new('system');

--get the settings
	local Settings = require "resources.functions.lazy_settings";
	local settings = Settings.new(db, domain_name, domain_uuid, user_uuid);
	local storage_type, max_length, silence_threshold, silence_seconds, base_dir, domain_dir;
	if (recording_type == 'greeting') then
		storage_type = settings:get('voicemail', 'storage_type', 'text') or '';
		max_length = tonumber(settings:get('voicemail', 'greeting_max_length', 'numeric')) or 90;
		silence_threshold = tonumber(settings:get('voicemail', 'greeting_silence_threshold', 'numeric')) or 200;
		silence_seconds = tonumber(settings:get('voicemail', 'greeting_silence_seconds', 'numeric')) or 3;
		base_dir = settings:get('switch', 'voicemail', 'dir') or voicemail_dir;
		domain_dir = base_dir .. "/default/" .. domain_name .. "/" .. voicemail_id;
	else
		storage_type = settings:get('recordings', 'storage_type', 'text') or '';
		max_length = tonumber(settings:get('recordings', 'recording_max_length', 'numeric')) or 300;
		silence_threshold = tonumber(settings:get('recordings', 'recording_silence_threshold', 'numeric')) or 200;
		silence_seconds = tonumber(settings:get('recordings', 'recording_silence_seconds', 'numeric')) or 3;
		base_dir = settings:get('switch', 'recordings', 'dir') or recordings_dir;
		domain_dir = base_dir .. "/" .. domain_name;
	end

--set the paths, record to a temporary directory on the same file system, outside the domain directory
	local temp_dir = base_dir .. "/.recording_call";
	local temp_file = temp_dir .. "/" .. call_uuid .. "." .. recording_filename:match("%.(%w+)$");
	local real_file = domain_dir .. "/" .. recording_filename;
	mkdir(domain_dir);
	mkdir(temp_dir);

--save the recording to the file system and the database
	local function save_recording()
		--move the temporary file into place
			if (file_exists(real_file)) then
				os.remove(real_file);
			end
			local ok, err = os.rename(temp_file, real_file);
			if (not ok) then
				freeswitch.consoleLog("err", "[recording_call] unable to move " .. temp_file .. " to " .. real_file .. ": " .. tostring(err) .. "\n");
				return false;
			end

		--read the file as base64
			local recording_base64 = nil;
			if (storage_type == "base64") then
				local file = require "resources.functions.file";
				recording_base64 = file.read_base64(real_file);
			end

		--use the base64 database connection when needed
			local dbh_save = db;
			if (storage_type == "base64") then
				dbh_save = Database.new('system', 'base64');
			end

		--save the voicemail greeting, replace a greeting with the same id and make it the active greeting
			local has_user = user_uuid ~= nil and user_uuid:find(uuid_pattern) ~= nil;
			local has_base64 = recording_base64 ~= nil;
			if (recording_type == 'greeting') then
				local sql = "delete from v_voicemail_greetings ";
				sql = sql .. "where domain_uuid = :domain_uuid and voicemail_id = :voicemail_id and greeting_id = :greeting_id ";
				db:query(sql, {domain_uuid = domain_uuid, voicemail_id = voicemail_id, greeting_id = greeting_id});
				local params = {
					voicemail_greeting_uuid = recording_uuid;
					domain_uuid = domain_uuid;
					voicemail_id = voicemail_id;
					greeting_id = greeting_id;
					greeting_name = recording_name;
					greeting_filename = recording_filename;
				};
				if (has_user) then params.user_uuid = user_uuid; end
				if (has_base64) then params.greeting_base64 = recording_base64; end
				sql = "insert into v_voicemail_greetings ";
				sql = sql .. "(voicemail_greeting_uuid, domain_uuid, voicemail_id, greeting_id, greeting_name, greeting_filename, greeting_description, ";
				if (has_base64) then sql = sql .. "greeting_base64, "; end
				if (has_user) then sql = sql .. "insert_user, "; end
				sql = sql .. "insert_date) ";
				sql = sql .. "values (:voicemail_greeting_uuid, :domain_uuid, :voicemail_id, :greeting_id, :greeting_name, :greeting_filename, '', ";
				if (has_base64) then sql = sql .. ":greeting_base64, "; end
				if (has_user) then sql = sql .. ":user_uuid, "; end
				sql = sql .. "now()) ";
				dbh_save:query(sql, params);
				if (dbh_save ~= db) then
					dbh_save:release();
				end
				sql = "update v_voicemails set greeting_id = :greeting_id ";
				sql = sql .. "where domain_uuid = :domain_uuid and voicemail_id = :voicemail_id ";
				db:query(sql, {domain_uuid = domain_uuid, voicemail_id = voicemail_id, greeting_id = greeting_id});
				freeswitch.consoleLog("notice", "[recording_call] saved greeting " .. real_file .. "\n");
				return true;
			end

		--update the existing recording or add a new one
			local sql = "select count(*) as count from v_recordings ";
			sql = sql .. "where domain_uuid = :domain_uuid and recording_uuid = :recording_uuid ";
			local params = {domain_uuid = domain_uuid, recording_uuid = recording_uuid};
			local row = db:first_row(sql, params);
			params = {
				domain_uuid = domain_uuid;
				recording_uuid = recording_uuid;
				recording_filename = recording_filename;
			};
			if (has_user) then params.user_uuid = user_uuid; end
			if (has_base64) then params.recording_base64 = recording_base64; end
			if (row and tonumber(row.count) > 0) then
				sql = "update v_recordings set ";
				sql = sql .. "recording_filename = :recording_filename, ";
				if (has_base64) then
					sql = sql .. "recording_base64 = :recording_base64, ";
				end
				if (has_user) then
					sql = sql .. "update_user = :user_uuid, ";
				end
				sql = sql .. "recording_voice = null, recording_speed = null, recording_message = null, ";
				sql = sql .. "update_date = now() ";
				sql = sql .. "where domain_uuid = :domain_uuid and recording_uuid = :recording_uuid ";
			else
				params.recording_name = recording_name;
				sql = "insert into v_recordings ";
				sql = sql .. "(recording_uuid, domain_uuid, recording_filename, recording_name, recording_description, ";
				if (has_base64) then sql = sql .. "recording_base64, "; end
				if (has_user) then sql = sql .. "insert_user, "; end
				sql = sql .. "insert_date) ";
				sql = sql .. "values (:recording_uuid, :domain_uuid, :recording_filename, :recording_name, '', ";
				if (has_base64) then sql = sql .. ":recording_base64, "; end
				if (has_user) then sql = sql .. ":user_uuid, "; end
				sql = sql .. "now()) ";
			end
			dbh_save:query(sql, params);
			if (dbh_save ~= db) then
				dbh_save:release();
			end
			freeswitch.consoleLog("notice", "[recording_call] saved " .. real_file .. "\n");
			return true;
	end

--answer the call
	set_state("answered");
	session:answer();
	session:setVariable("record_append", "false");
	session:sleep(1000);

--check if the temporary recording has audio, a header only file is not saved
	local function has_audio()
		local f = io.open(temp_file, "rb");
		if (not f) then
			return false;
		end
		local size = f:seek("end");
		f:close();
		return size ~= nil and size > 8000;
	end

--record, then listen (1), save (2), re-record (3) or delete (7)
--hanging up or not choosing an option saves the recording
	local action = "record";
	local saved = false;
	while (session:ready()) do
		if (action == "record") then
			--record your message at the tone, press any key or stop talking to end the recording
				session:flushDigits();
				session:execute("playback", "phrase:voicemail_record_message");
				session:streamFile("tone_stream://L=1;%(1000, 0, 640)");
				if (not session:ready()) then
					break;
				end
				set_state("recording");
				if (file_exists(temp_file)) then
					os.remove(temp_file);
				end
				--record,<path> [<time_limit_secs>] [<silence_thresh>] [<silence_hits>], any key ends the recording
				session:setVariable("playback_terminators", "0123456789*#");
				session:execute("record", temp_file .. " " .. max_length .. " " .. silence_threshold .. " " .. silence_seconds);
				session:setVariable("playback_terminators", "#");
				action = "menu";
		elseif (action == "menu") then
			set_state("review");
			session:flushDigits();
			local digit = '';
			local tries = 0;
			while (session:ready() and digit == '' and tries < 3) do
				digit = session:playAndGetDigits(1, 1, 1, 5000, "#", "phrase:voicemail_record_file_options:1:2:3:7", "", "^[1237]$");
				tries = tries + 1;
			end
			if (not session:ready()) then
				--the caller hung up, the recording is saved below
					break;
			elseif (digit == "1") then
				--listen to the recording
					session:sleep(500);
					if (file_exists(temp_file)) then
						session:streamFile(temp_file);
					end
					session:sleep(500);
			elseif (digit == "3") then
				--re-record
					action = "record";
			elseif (digit == "7") then
				--delete the recording
					if (file_exists(temp_file)) then
						os.remove(temp_file);
					end
					set_state("deleted");
					session:execute("playback", "phrase:voicemail_record_file_deleted");
					session:execute("playback", "phrase:voicemail_goodbye");
					session:hangup("NORMAL_CLEARING");
			else
				--save the recording, option 2 or no option chosen
					if (has_audio() and save_recording()) then
						saved = true;
						set_state("saved");
						session:execute("playback", "phrase:voicemail_ack:saved");
					else
						set_state("failed");
					end
					session:execute("playback", "phrase:voicemail_goodbye");
					session:hangup("NORMAL_CLEARING");
			end
		end
	end

--the caller hung up after recording, save the recording
	if (not saved and file_exists(temp_file) and has_audio()) then
		save_recording();
	end

--remove the temporary file
	if (file_exists(temp_file)) then
		os.remove(temp_file);
	end
