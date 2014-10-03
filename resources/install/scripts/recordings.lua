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
--	Copyright (C) 2010
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set the variables
	pin_number = "";
	max_tries = "3";
	digit_timeout = "3000";
	sounds_dir = "";
	recordings_dir = "";
	file_name = "";
	recording_number = "";
	recording_slots = "";
	recording_prefix = "";

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

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
			recording_slots = session:getVariable("recording_slots");
			recording_prefix = session:getVariable("recording_prefix");
			recording_name = session:getVariable("recording_name");

		--select the recording number
			if (recording_slots) then
				min_digits = 1;
				max_digits = 20;
				recording_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-id_number.wav", "", "\\d+");
				recording_name = recording_prefix..recording_number..".wav";
			end

		--set the default recording name if one was not provided
			if (recording_name) then
				--recording name is provided do nothing
			else
				--set a default recording_name
				recording_name = "temp_"..session:get_uuid()..".wav";
			end

		--prompt for the recording
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-recording_started.wav");
			session:execute("set", "playback_terminators=#");

		--begin recording
			session:execute("record", "'"..recordings_dir.."/"..recording_name.."' 10800 500 500");

		--preview the recording
			session:streamFile(recordings_dir.."/"..recording_name);

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
				--delete the old recording
					os.remove (recordings_dir.."/"..recording_name);
					--session:execute("system", "rm "..);
				--make a new recording
					begin_record(session, sounds_dir, recordings_dir);
			else
				--recording saved, hangup
					session:streamFile("voicemail/vm-saved.wav");
				return;
			end
	end

if ( session:ready() ) then
	session:answer();

	--get the dialplan variables and set them as local variables
		pin_number = session:getVariable("pin_number");
		sounds_dir = session:getVariable("sounds_dir");
		domain_name = session:getVariable("domain_name");
	
	--set the base recordings dir
		base_recordings_dir = recordings_dir;

	--use the recording_dir when the variable is set
		if (session:getVariable("recordings_dir")) then
			if (base_recordings_dir ~= session:getVariable("recordings_dir")) then
				recordings_dir = session:getVariable("recordings_dir");
			end
		end

	--get the recordings from the config.lua and append the domain_name if the system is multi-tenant
		if (domain_count > 1) then
			recordings_dir = recordings_dir .. "/" .. domain_name;
		end
	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--if the pin number is provided then require it
		if (pin_number) then
			min_digits = string.len(pin_number);
			max_digits = string.len(pin_number)+1;
			digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
			if (digits == pin_number) then
				--pin is correct
			else
				session:streamFile("phrase:voicemail_fail_auth:#");
				session:hangup("NORMAL_CLEARING");
				return;
			end
		end

	--start recording
		begin_record(session, sounds_dir, recordings_dir);

	session:hangup();
end