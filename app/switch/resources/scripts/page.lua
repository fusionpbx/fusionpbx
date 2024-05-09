--	page.lua
--	Part of FusionPBX
--	Copyright (C) 2010-2022 Mark J Crane <markjcrane@fusionpbx.com>
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

--set default settings
pin_number = "";
max_tries = "3";
digit_timeout = "3000";

--define the trim function
require "resources.functions.trim";

--define the explode function
require "resources.functions.explode";

--define the split function
require "resources.functions.split";

--iterator over numbers.
local function each_number(value)
	local begin_value, end_value = split_first(value, "-", true)
	if (not end_value) or (begin_value == end_value) then
		return function()
			local result = begin_value
			begin_value = nil
			return result
		end
	end

	if string.find(begin_value, "^0") then
		assert(#begin_value == #end_value, "number in range with leading `0` should have same length")
	end

	local number_length = ("." .. tostring(#begin_value))
	begin_value, end_value = tonumber(begin_value), tonumber(end_value)
	assert(begin_value and end_value and (begin_value <= end_value), "Invalid range: " .. value)

	return function()
		value, begin_value = begin_value, begin_value + 1
		if value > end_value then return end
		return string.format("%" .. number_length .. "d", value)
	end
end

--make sure the session is ready
if ( session:ready() ) then
	--answer the call
		session:answer();

	--get the dialplan variables and set them as local variables
		destination_number = session:getVariable("destination_number");
		pin_number = session:getVariable("pin_number");
		domain_name = session:getVariable("domain_name");
		domain_uuid = session:getVariable("domain_uuid");
		sounds_dir = session:getVariable("sounds_dir");
		destinations = session:getVariable("destinations");
		rtp_secure_media = session:getVariable("rtp_secure_media");
		if (destinations == nil) then
			destinations = session:getVariable("extension_list");
		end
		destination_table = explode(",",destinations);
		caller_id_name = session:getVariable("caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");
		sip_from_user = session:getVariable("sip_from_user");
		mute = session:getVariable("mute");
		delay = session:getVariable("delay");

	--if the call is transferred then return the call backe to the referred by user
		referred_by = session:getVariable("sip_h_Referred-By");
		if (referred_by ~= nil) then
			--get the uuid of the call
				uuid = session:getVariable("uuid");

			--find the referred by user
				referred_by_user = referred_by:match("<sip:(%d+)@");

			--log the destinations
				freeswitch.consoleLog("NOTICE", "[page] referred_by ".. referred_by ..", user "..referred_by_user.." call was tranferred\n");

			--create the api object
				api = freeswitch.API();
				cmd_string = "uuid_transfer "..uuid.." "..referred_by_user;
				channel_result = api:executeString(cmd_string);
		end

	--referredy by is nill
	if (referred_by == nil) then

		--determine whether to check if the destination is available
			check_destination_status = session:getVariable("check_destination_status");
			if (not check_destination_status) then check_destination_status = 'true'; end

		--set the type of auto answer
			auto_answer = session:getVariable("auto_answer");
			if (not auto_answer) then auto_answer = 'call_info'; end
			if (auto_answer == 'call_info') then
				auto_answer = "sip_h_Call-Info=<sip:"..domain_name..">;answer-after=0";
			end
			if (auto_answer == 'sip_auto_answer') then
				auto_answer = "sip_auto_answer=true";
			end

		--set sip header Alert-Info
			alert_info = session:getVariable("alert_info");
			if (not alert_info) then alert_info = 'ring_answer'; end
			if (alert_info == 'auto_answer') then
				alert_info = "sip_h_Alert-Info='Auto Answer'";
			elseif (alert_info == 'ring_answer') then
				alert_info = "sip_h_Alert-Info='Ring Answer'";
			else
				alert_info = "sip_h_Alert-Info='"..alert_info.."'";
			end

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--set rtp_secure_media to an empty string if not provided.
			if (rtp_secure_media == nil) then
				rtp_secure_media = 'false';
			end

		--setup the database connection
			local Database = require "resources.functions.database";
			local db = dbh or Database.new('system');

		--load lazy settings library
			local Settings = require "resources.functions.lazy_settings";

		--get the recordings settings
			local settings = Settings.new(db, domain_name, domain_uuid, nil);

		--set the recordings variables
			recording_max_length = settings:get('recordings', 'recording_max_length', 'numeric') or 90;
			silence_threshold = settings:get('recordings', 'recording_silence_threshold', 'numeric') or 200;
			silence_seconds = settings:get('recordings', 'recording_silence_seconds', 'numeric') or 3;

		--define the conference name
			local conference_profile = "page";
			local conference_name = "page-"..destination_number.."@"..domain_name;
			local conference_bridge = conference_name.."@"..conference_profile;

		--set the caller id
			if (caller_id_name) then
				--caller id name provided do nothing
			else
				effective_caller_id_name = session:getVariable("effective_caller_id_name");
				caller_id_name = effective_caller_id_name;
			end

			if (caller_id_number) then
				--caller id number provided do nothing
			else
				effective_caller_id_number = session:getVariable("effective_caller_id_number");
				caller_id_number = effective_caller_id_number;
			end

		--set conference flags
			if (mute == "true") then
				flags = "flags{mute}";
			else
				flags = "flags{}";
			end

		--if annouce delay is active then prompt for recording
			if (delay == "true") then
				--callback function for the delayed recording
					function onInputCBF(s, _type, obj, arg)
						local k, v = nil, nil
						if (_type == "dtmf") then
							dtmf_entered = 1; --set this variable to know that the user entered DTMF
							return 'break'
						else
							return ''
						end
					end

				--sleep
					session:sleep(500);

				--set variables for page recording
					recording_dir = '/tmp/';
					filename = "page-"..destination_number.."@"..domain_name..".wav";
					recording_filename = string.format('%s%s', recording_dir, filename);
					dtmf_entered = 0;
					silence_triggered = 0;

				--ask user to record
					session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-record_message.wav")
					session:streamFile("tone_stream://L=1;%(1000, 0, 640)");

				--set callback function for when a user clicks DTMF
					session:setInputCallback('onInputCBF', '');

				--time before starting the recording
					startUTCTime = os.time(os.date('!*t'));

				--record the page message
					silence_triggered = session:recordFile(recording_filename, recording_max_length, silence_threshold, silence_seconds);

				--time after starting the recording
					endUTCTime = os.time(os.date('!*t'));

				--total recording time
					recording_length = endUTCTime - startUTCTime;
			end

		--if the pin number is provided then require it
			if (pin_number) then
				--sleep
					session:sleep(500);

				--get the user pin number
					min_digits = 2;
					max_digits = 20;
					digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");

				--validate the user pin number
					pin_number_table = explode(",",pin_number);
					for index,pin_number in pairs(pin_number_table) do
						if (digits == pin_number) then
							--set the variable to true
								auth = true;
							--set the authorized pin number that was used
								session:setVariable("pin_number", pin_number);
							--end the loop
								break;
						end
					end

				--if not authorized play a message and then hangup
					if (not auth) then
						session:streamFile("phrase:voicemail_fail_auth:#");
						session:hangup("NORMAL_CLEARING");
						return;
					end
			end

		--log the destinations
			freeswitch.consoleLog("NOTICE", "[page] destinations "..destinations.." available\n");

		--create the api object
			api = freeswitch.API();

		--get the channels
			if (check_destination_status == 'true') then
				cmd_string = "show channels";
				channel_result = api:executeString(cmd_string);
			end

		--originate the calls
			destination_count = 0;
			if (delay ~= "true" or (dtmf_entered == 1 or silence_triggered == 1)) then

				for index,value in pairs(destination_table) do
					for destination in each_number(value) do

						--get the destination required for number-alias
						destination = api:execute("user_data", destination .. "@" .. domain_name .. " attr id");

						--prevent calling the user that initiated the page
						if (sip_from_user ~= destination) then
							if (check_destination_status == 'true') then
								--detect if the destination is available or busy
								destination_status = 'available';
								channel_array = explode("\n", channel_result);
								for index,row in pairs(channel_array) do
									if string.find(row, destination..'@'..domain_name, nil, true) then
										destination_status = 'busy';
										break;
									end
								end

								--if available then page then originate the call with auto answer
								if (destination_status == 'available') then
									freeswitch.consoleLog("NOTICE", "[page] destination "..destination.." available\n");
									if destination == sip_from_user then
										--this destination is the caller that initated the page
									else
										--originate the call
										cmd_string = "bgapi originate {"..auto_answer..","..alert_info..",hangup_after_bridge=false,rtp_secure_media="..rtp_secure_media..",origination_caller_id_name='"..caller_id_name.."',origination_caller_id_number="..caller_id_number.."}user/"..destination.."@"..domain_name.." conference:"..conference_bridge.."+"..flags.." inline";
										api:executeString(cmd_string);
										destination_count = destination_count + 1;
									end
								end
							else
								--endpoint determines what to do with the call when the destination is active
								freeswitch.consoleLog("NOTICE", "[page] endpoint determines what to do if the it has an active call.\n");
								if destination == sip_from_user then
									--this destination is the caller that initated the page
								else
									--originate the call
									cmd_string = "bgapi originate {"..auto_answer..","..alert_info..",hangup_after_bridge=false,rtp_secure_media="..rtp_secure_media..",origination_caller_id_name='"..caller_id_name.."',origination_caller_id_number="..caller_id_number.."}user/"..destination.."@"..domain_name.." conference:"..conference_bridge.."+"..flags.." inline";
									api:executeString(cmd_string);
									destination_count = destination_count + 1;
								end
							end
						end
					end
				end
			end

		--send main call to the conference room
			if (destination_count > 0) then
				--set moderator flag
					if (session:getVariable("moderator") == "true") then
						moderator_flag = ",moderator";
					else
						moderator_flag = "";
					end

				--check if delay is true
					if (delay == "true" and (dtmf_entered == 1 or silence_triggered == 1)) then
						--play the recorded file into the page/conference. Need to wait for the page/conference to actually be started before we can end it.
							response = api:executeString("sched_api +2 none conference "..conference_name.." play "..recording_filename);

						--wait for recording to finish then end page/conference
							response = api:executeString("sched_api +"..tostring(recording_length+4).." none conference "..conference_name.." hup all");
					else
						--join the moderator into the page
							session:execute("conference", conference_bridge.."+flags{endconf,mintwo"..moderator_flag.."}");
					end
			else
				--error tone due to no destinations
					session:execute("playback", "tone_stream://%(500,500,480,620);loops=3");
			end
	end
end
