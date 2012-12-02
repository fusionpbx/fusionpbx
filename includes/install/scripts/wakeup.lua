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

--get the argv values
	script_name = argv[0];
	domain_name = argv[1];
	wakeup_number = argv[2];

--add the trim function
	function trim(s)
		return s:gsub("^%s+", ""):gsub("%s+$", "")
	end

--add is_numeric
	function is_numeric(text)
		if type(text)~="string" and type(text)~="number" then return false end
		return tonumber(text) and true or false
	end

--set the default values for the variables
	pin_number = "";
	max_tries = "3";
	digit_timeout = "3000";
	sounds_dir = "";
	extension_type = ""; --number,caller_id_number,prompt
	extension_number = "";

if (wakeup_number) then
	--begin the wakeup call
	if ( session:ready() ) then
		--prepare the api object
			api = freeswitch.API();

		--set session settings
			session:answer();
			session:setAutoHangup(false);

		--wakeup confirm press 1 to 3
			min_digits = 1;
			max_digits = 1;
			digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:wakeup-call", "", "\\d+");

		--reschedule the call for snooze
			if (digits == "2") then
				freeswitch.consoleLog("NOTICE", "wakeup call: snooze selected - rescheduled the call\n");
				api = freeswitch.API();
				caller_id_name = "wakeup call";
				caller_id_number = wakeup_number;
				sched_api_time = "600";
				cmd_string = "sched_api +"..sched_api_time.." wakeup-call-"..wakeup_number.." originate {hangup_after_bridge=false,origination_caller_id_name='"..caller_id_name.."',origination_caller_id_number="..caller_id_number.."}user/"..wakeup_number.."@"..domain_name.." &lua('wakeup.lua "..domain_name.." "..wakeup_number.."') ";
				freeswitch.consoleLog("NOTICE", "wakeup: "..cmd_string.."\n");
				reply = api:executeString(cmd_string);
			end
	end
else
	--prompt for the wakeup call information

	if ( session:ready() ) then
		session:answer();
		session:setAutoHangup(false);

		--get the dialplan variables and set them as local variables
			sounds_dir = session:getVariable("sounds_dir");
			domain_name = session:getVariable("domain_name");
			extension_number = session:getVariable("extension_number");
			extension_type = session:getVariable("extension_type");
			time_zone_offset = session:getVariable("time_zone_offset");
			sip_number_alias = session:getVariable("sip_number_alias");
			sip_from_user = session:getVariable("sip_from_user");
			if (is_numeric(sip_number_alias)) then
				wakeup_number = sip_number_alias;
			else
				wakeup_number = sip_from_user;
			end

		--get the extension number
			if (extension_type == "prompt") then
				min_digits = 1;
				max_digits = 11;
				wakeup_time = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:wakeup-get-extension", "", "\\d+");
			end

		--get the wakeup time
			min_digits = 4;
			max_digits = 4;
			wakeup_time = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:wakeup-greeting", "", "\\d+");
			freeswitch.consoleLog("NOTICE", "wakeup time: "..wakeup_time.."\n");

		--get the current time
			current_hours = tonumber(os.date("%H"));
			current_minutes = tonumber(os.date("%M"));
			current_seconds = tonumber(os.date("%S"));

		--adjust the time zone offset
			if (time_zone_offset) then
				current_hours = time_zone_offset + current_hours;
				if (current_hours < 0) then
					current_hours = current_hours + 24;
				end
				if (current_hours > 23) then
					current_hours = current_hours - 24;
				end
			end

		--show the current hours minutes and seconds to the log
			--freeswitch.consoleLog("NOTICE", "Hours: " .. current_hours .. "\n");
			--freeswitch.consoleLog("NOTICE", "Mins: " .. current_minutes .. "\n");
			--freeswitch.consoleLog("NOTICE", "Seconds: " .. current_seconds .. "\n");

		--prepare the current time
			current_time = (current_hours * 100) + current_minutes;

		--get the wakeup hours and minutes
			wakeup_hours = string.sub(wakeup_time, 1, 2);
			wakeup_minutes = string.sub(wakeup_time, 3);

		--show the wakeup time, hours, and minutes to the log
			--freeswitch.consoleLog("NOTICE", "wakeup_time "..wakeup_time.."\n");
			--freeswitch.consoleLog("NOTICE", "wakeup_hours "..wakeup_hours.."\n");
			--freeswitch.consoleLog("NOTICE", "wakeup_minutes "..wakeup_minutes.."\n");

		--convert the time, hours and minutes to numbers
			wakeup_time = tonumber(wakeup_time);
			wakeup_hours = tonumber(wakeup_hours);
			wakeup_minutes = tonumber(wakeup_minutes);
		if (current_time > wakeup_time) then
			--get the current_time_in_seconds
				current_time_in_seconds = (current_hours * 3600) + (current_minutes * 60);
				--freeswitch.consoleLog("NOTICE", "sched_api_time = ("..current_hours.." * 3600) + ("..current_minutes.." * 60)\n");
			--get the seconds until midnight
				seconds_until_midnight = (24 * 3600) - current_time_in_seconds;
				--freeswitch.consoleLog("NOTICE", "sched_api_time = (24 * 3600) - "..current_time_in_seconds.."\n");
			--get the wakeup_time_in_seconds
				wakeup_time_in_seconds = (wakeup_hours * 3600) + (wakeup_minutes * 60);
				--freeswitch.consoleLog("NOTICE", "sched_api_time = ("..wakeup_hours.." * 3600) + ("..wakeup_minutes.." * 60)\n");
			--add the seconds_until_midnight to the wakeup_time_in_seconds
				sched_api_time = wakeup_time_in_seconds + seconds_until_midnight;
				--freeswitch.consoleLog("NOTICE", "sched_api_time = "..wakeup_time_in_seconds.." + "..seconds_until_midnight.."\n");
		else
			--get the current_time_in_seconds
				current_time_in_seconds = (current_hours * 3600) + (current_minutes * 60);
				--freeswitch.consoleLog("NOTICE", "current_time_in_seconds = ("..current_hours.." * 3600) + ("..current_minutes.." * 60);\n");
			--get the wakeup_time_in_seconds
				wakeup_time_in_seconds = (wakeup_hours * 3600) + (wakeup_minutes * 60);
				--freeswitch.consoleLog("NOTICE", "wakeup_time_in_seconds = ("..wakeup_hours.." * 3600) + ("..wakeup_minutes.." * 60);\n");
			--subtract the current time from wakeup_time_in_seconds
				sched_api_time =  wakeup_time_in_seconds - current_time_in_seconds;
				--freeswitch.consoleLog("NOTICE", "sched_api_time = "..wakeup_time_in_seconds.." - "..current_time_in_seconds.."\n");
		end
		--freeswitch.consoleLog("NOTICE", "sched_api_time "..sched_api_time.."\n");

		--wakeup call has been scheduled
			session:streamFile("phrase:wakeup-scheduled");
			session:say(wakeup_time, "en", "number", "ITERATED");

		--wakeup confirm press 1 to 3
			min_digits = 1;
			max_digits = 1;
			wakeup_accept = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:wakeup-accept", "", "\\d+");
			--accept
				if (wakeup_accept == "1") then
					--send a message to the console
						freeswitch.consoleLog("NOTICE", "wakeup: accepted\n");
					--schedule the wakeup call
						caller_id_name = "wakeup call";
						caller_id_number = wakeup_number;
						cmd_string = "sched_api +"..sched_api_time.." wakeup-call-"..wakeup_number.." originate {hangup_after_bridge=false,origination_caller_id_name='"..caller_id_name.."',origination_caller_id_number="..caller_id_number.."}user/"..wakeup_number.."@"..domain_name.." &lua('wakeup.lua "..domain_name.." "..wakeup_number.."') ";
						freeswitch.consoleLog("NOTICE", "wakeup: "..cmd_string.."\n");
						api = freeswitch.API();
						reply = api:executeString(cmd_string);
					--hangup
						session:hangup();
				end
			--cancel
				if (wakeup_accept == "2") then
					--send a message to the console
						freeswitch.consoleLog("NOTICE", "wakeup: cancelled\n");
					--hangup
						session:hangup();
				end
	end
end