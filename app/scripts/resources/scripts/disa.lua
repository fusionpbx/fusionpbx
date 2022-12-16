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
--	Copyright (C) 2010-2022
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--predefined variables
	predefined_destination = '';
	fallback_destination = '';

--define the trim function
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

--prepare the api object
	api = freeswitch.API();

--answer the call
	if (session:ready()) then
		session:answer();
	end

--get and save the variables
	if (session:ready()) then
		sound_greeting = session:getVariable("sound_greeting");
		sound_pin = session:getVariable("sound_pin");
		sound_extension = session:getVariable("sound_extension");
		pin_number = session:getVariable("pin_number");
		sounds_dir = session:getVariable("sounds_dir");
		predefined_destination = session:getVariable("predefined_destination");
		fallback_destination = session:getVariable("fallback_destination");
		digit_min_length = session:getVariable("digit_min_length");
		digit_max_length = session:getVariable("digit_max_length");
		digit_timeout = session:getVariable("digit_timeout");
		context = session:getVariable("context");
		privacy = session:getVariable("privacy");
		max_tries = session:getVariable("max_tries");
		pin_tries = session:getVariable("pin_tries");
		extension_tries = session:getVariable("extension_tries");
	end

--set the sounds path for the language, dialect and voice
	if (session:ready()) then
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end
	end

--set defaults
	if (not digit_min_length) then
		digit_min_length = "7";
	end

	if (not digit_max_length) then
		digit_max_length = "11";
	end

	if (not digit_timeout) then
		digit_timeout = "5000";
	end

	if (not sound_pin) then
		sound_pin = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-please_enter_pin_followed_by_pound.wav";
	end

	if (not sound_extension) then
		sound_extension = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-enter_destination_telephone_number.wav";
	end

	if (not max_tries) then
		max_tries = "3";
	end

	if (not pin_tries) then
		pin_tries = max_tries;
	end

	if (not extension_tries) then
		extension_tries = max_tries;
	end

--if the sound_greeting is provided then play it
	if (session:ready() and sound_greeting) then
		session:streamFile(sound_greeting);
		session:sleep(200);
	end

--if the pin number is provided then require it
	if (session:ready() and pin_number) then
		min_digits = string.len(pin_number);
		max_digits = string.len(pin_number)+1;
		digits = session:playAndGetDigits(min_digits, max_digits, pin_tries, digit_timeout, "#", sound_pin, "", "\\d+");
		if (digits == pin_number) then
			--pin is correct
		else
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-pin_or_extension_is-invalid.wav");
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-im_sorry.wav");
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-goodbye.wav");
			session:hangup("NORMAL_CLEARING");
			return;
		end
	end

--if a predefined_destination is provided then set the number to the predefined_destination
	if (session:ready()) then
		if (predefined_destination) then
			destination_number = predefined_destination;
		else
			session:sleep(1000);
			dtmf = ""; --clear dtmf digits to prepare for next dtmf request
			destination_number = session:playAndGetDigits(digit_min_length, digit_max_length, extension_tries, digit_timeout, "#", sound_extension, "", "\\d+");
			if (string.len(destination_number) == 0 and fallback_destination) then
				destination_number = fallback_destination;
			end
			--if (string.len(destination_number) == 10) then destination_number = "1"..destination_number; end
		end
	end

--set privacy
	if (session:ready()) then
		if (privacy == "true") then
			session:execute("privacy", "full");
			session:execute("set", "sip_h_Privacy=id");
			session:execute("set", "privacy=yes");
		end
	end

--set the caller id name and number for external calls
	if (session:ready()) then
		cmd = "user_exists id ".. destination_number .." "..context;
		user_exists = trim(api:executeString(cmd));
		if (user_exists == "false") then
			--get the outbound caller id variables
			outbound_caller_id_name = session:getVariable("outbound_caller_id_name");
			outbound_caller_id_number = session:getVariable("outbound_caller_id_number");

			--get the outbound caller ID information if it is set otherwise keep the original caller id
			if (outbound_caller_id_name) then
				caller_id_name = session:getVariable("outbound_caller_id_name");
			else
				caller_id_name = session:getVariable("caller_id_name");
			end
			if (outbound_caller_id_number) then
				caller_id_number = session:getVariable("outbound_caller_id_number");
			else
				caller_id_number = session:getVariable("caller_id_number");
			end

			--set the outbound and effective caller ID information
			if (caller_id_name) then
				session:execute("set", "outbound_caller_id_name="..caller_id_name);
				session:execute("set", "effective_caller_id_name="..caller_id_name);
			end
			if (caller_id_number) then
				session:execute("set", "outbound_caller_id_number="..caller_id_number);
				session:execute("set", "effective_caller_id_number="..caller_id_number);
			end
		end
	end

--get the caller id number
	--if (session:ready()) then
	--	min_digits = 7;
	--	max_digits = 20;
	--	session:sleep(1000);
	--	caller_id_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-enter_source_telephone_number.wav", "", "\\d+");
	--	caller_id_name = '';
	--	if (string.len(caller_id_number) == 0) then
	--		sesssion:hangup();
	--	end
	--end

--send the destination
	if (session:ready()) then
		session:execute("set","disa_outbound=true");
		session:execute("transfer", destination_number .. " XML " .. context);
	end
