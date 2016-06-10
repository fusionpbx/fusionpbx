--	intercom.lua
--	Part of FusionPBX
--	Copyright (C) 2010 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
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

--set the variables
	max_tries = "3";
	digit_timeout = "5000";

--include config.lua
	require "resources.functions.config";

	local presence_in = require "resources.functions.presence_in"
	local Database    = require "resources.functions.database"
	local Settings    = require "resources.functions.lazy_settings"
	local file        = require "resources.functions.file"
	local log         = require "resources.functions.log".call_flow

--connect to the database
	local dbh = Database.new('system');

--! @todo move to library
local function basename(file_name)
	return (string.match(file_name, "([^/]+)$"))
end

--! @todo move to library
local function isabspath(file_name)
	return string.sub(file_name, 1, 1) == '/' or string.sub(file_name, 2, 1) == ':'
end

--! @todo move to library
local function find_file(dbh, domain_name, domain_uuid, file_name)
	-- if we specify e.g. full path
	if isabspath(file_name) and file.exists(file_name) then
		log.debugf('found file `%s` in file system', file_name)
		return file_name
	end

	local file_name_only = basename(file_name)

	local is_base64, found

	if file_name_only == file_name then -- this can be recordings
		local full_path = recordings_dir .. "/" .. domain_name .. "/" .. file_name
		if file.exists(full_path) then
			log.debugf('resolve `%s` as recording `%s`', file_name, full_path)
			file_name, found = full_path, true
		else -- recordings may be in database
			local settings = Settings.new(dbh, domain_name, domain_uuid)
			local storage_type = settings:get('recordings', 'storage_type', 'text') or ''
			if storage_type == 'base64' then
				local sql = "SELECT recording_base64 FROM v_recordings "
					.. "WHERE domain_uuid = '" .. domain_uuid .."'"
					.. "AND recording_filename = '".. file_name.. "' "
				if (debug["sql"]) then
					log.notice("SQL: " .. sql)
				end

				local dbh = Database.new('system', 'base64/read')
				local recording_base64 = dbh:first_value(sql);
				dbh:release();

				if recording_base64 and #recording_base64 > 32 then
					log.debugf('resolve `%s` as recording `%s`(base64)', file_name, full_path)
					file_name, found, is_base64 = full_path, true, true
					file.write_base64(file_name, recording_base64)
				end
			end
		end
	end

	if not found then
		local sounds_dir
		if session then
			-- Implemented based on stream.lua. But seems it never works.
			-- because if we have file like `digits/1.wav` but full_path is `sounds_dir/digits/XXXX/1.wav`
			sounds_dir = session:getVariable("sounds_dir")
			local default_language = session:getVariable("default_language") or 'en'
			local default_dialect = session:getVariable("default_dialect") or 'us'
			local default_voice = session:getVariable("default_voice") or 'callie'

			sounds_dir = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice
		else
			--! @todo implement for not sessoin call.
		end

		if sounds_dir then
			found = file.exists(sounds_dir.. "/" ..file_name_only)
			if found then
				log.debugf('resolve `%s` as sound `%s`', file_name, found)
				file_name, found = found, true
			end
		end
	end

	if not found then
		return
	end

	return file_name, is_base64
end

--! @todo move to library
local function play_file(dbh, domain_name, domain_uuid, file_name)
	local full_path, is_base64 = find_file(dbh, domain_name, domain_uuid, file_name)
	if not full_path then
		log.warningf('Can not find audio file: %s. Try using it in raw mode.', file_name)
		full_path = file_name
	else
		log.noticef('Found `%s` as `%s`%s', file_name, full_path, is_base64 and '(BASE64)' or '')
	end

	session:execute("playback", full_path);
end

if (session:ready()) then
	--get the variables
		local domain_name = session:getVariable("domain_name");
		local domain_uuid = session:getVariable("domain_uuid");
		local call_flow_uuid = session:getVariable("call_flow_uuid");
		local sounds_dir = session:getVariable("sounds_dir");
		local feature_code = session:getVariable("feature_code");

	--set the sounds path for the language, dialect and voice
		local default_language = session:getVariable("default_language") or 'en';
		local default_dialect = session:getVariable("default_dialect") or 'us';
		local default_voice = session:getVariable("default_voice") or 'callie';

	--get the extension list
		local sql = "SELECT * FROM v_call_flows where call_flow_uuid = '"..call_flow_uuid.."'"
			-- .. "and call_flow_enabled = 'true'"
		--log.notice("SQL: %s", sql);

		dbh:query(sql, function(row)
			call_flow_name = row.call_flow_name;
			call_flow_extension = row.call_flow_extension;
			call_flow_feature_code = row.call_flow_feature_code;
			--call_flow_context = row.call_flow_context;
			call_flow_status = row.call_flow_status;
			pin_number = row.call_flow_pin_number;
			call_flow_label = row.call_flow_label;
			call_flow_anti_label = row.call_flow_anti_label;
			call_flow_sound_on = row.call_flow_sound_on or '';
			call_flow_sound_off = row.call_flow_sound_off or '';

			if #call_flow_status == 0 then
				call_flow_status = "true";
			end
			if call_flow_status == "true" then
				app = row.call_flow_app;
				data = row.call_flow_data
			else
				app = row.call_flow_anti_app;
				data = row.call_flow_anti_data
			end
		end);

	if (feature_code == "true") then
		--if the pin number is provided then require it
			if #pin_number > 0 then
				local min_digits = #pin_number;
				local max_digits = #pin_number+1;
				session:answer();
				local digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
				if digits ~= pin_number then
					session:streamFile("phrase:voicemail_fail_auth:#");
					session:hangup("NORMAL_CLEARING");
					return;
				end
			end

		--feature code - toggle the status
			local toggle = (call_flow_status == "true") and "false" or "true"

		-- turn the lamp
			presence_in.turn_lamp( toggle == "false",
				call_flow_feature_code.."@"..domain_name,
				call_flow_uuid
			);

		--active label
			local active_flow_label = (toggle == "true") and call_flow_label or call_flow_anti_label

		--play info message
			local audio_file = (toggle == "false") and call_flow_sound_on or call_flow_sound_off

		--show in the console
			log.noticef("label=%s,status=%s,uuid=%s,audio=%s", active_flow_label, toggle, call_flow_uuid, audio_file)

		--store in database
			dbh:query("UPDATE v_call_flows SET call_flow_status = '"..toggle.."' WHERE call_flow_uuid = '"..call_flow_uuid.."'");

		--answer
			session:answer();

		--display label on Phone (if support)
			if #active_flow_label > 0 then
				local api = freeswitch.API();
				local reply = api:executeString("uuid_display "..session:get_uuid().." "..active_flow_label);
			end

			if #audio_file > 0 then
				session:sleep(1000);
				play_file(dbh, domain_name, domain_uuid, audio_file)
				session:sleep(1000);
			else
				session:sleep(2000);
				audio_file = "tone_stream://%(200,0,500,600,700)"
			end

		--hangup the call
			session:hangup();
	else
		log.notice("execute " .. app .. " " .. data);

		--exucute the application
			session:execute(app, data);
		--timeout application
			--if (not session:answered()) then
			--	session:execute(ring_group_timeout_app, ring_group_timeout_data);
			--end
	end
end
