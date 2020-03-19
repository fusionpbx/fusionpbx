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
wakeup_destination = argv[2];
wakeup_call_sound = argv[3];

--define the trim function
require "resources.functions.trim";

--add is_numeric
function is_numeric(text)
	if type(text)~="string" and type(text)~="number" then return false end
	return tonumber(text) and true or false
end

--set the default values for the variables
pin_number = "";
max_tries = 3;
digit_timeout = 3000;

--check whether originate the wakeup call or to request details for it
if (wakeup_destination) then
	--begin the wakeup call
	if ( session:ready() ) then
		--prepare the api object
			api = freeswitch.API();

		--set session settings
			session:answer();
			session:setAutoHangup(false);

		--sleep
			session:sleep('500');

		--wakeup confirm press 1 to 3
			min_digits = 1;
			max_digits = 1;
			max_tries = 3;
			digit_timeout = 20000;
			digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", wakeup_call_sound, "", "\\d+");

		--get the dialplan variables and set them as local variables
			domain_name = session:getVariable("domain_name");
			sip_auto_answer = session:getVariable("sip_auto_answer");
			snooze_time = session:getVariable("snooze_time");
			wakeup_caller_id_name = session:getVariable("wakeup_caller_id_name");
			wakeup_caller_id_number = session:getVariable("wakeup_caller_id_number");

		--handle auto answer
			if (sip_auto_answer == "true") then
				auto_answer = "sip_auto_answer=true,sip_h_Alert-Info='Ring Answer'";
			else
				auto_answer = "sip_auto_answer=false";
			end

		--reschedule the call for snooze
			if (digits == "2") then
				freeswitch.consoleLog("NOTICE", "wakeup call: snooze selected - rescheduled the call\n");
				api = freeswitch.API();
				cmd_string = "sched_api +"..snooze_time.." wakeup-call-"..wakeup_destination.." originate {hangup_after_bridge=false,"..auto_answer..",snooze_time="..snooze_time..",origination_caller_id_name='"..wakeup_caller_id_name.."',origination_caller_id_number="..wakeup_caller_id_number..",wakeup_caller_id_name='".. wakeup_caller_id_name.."',wakeup_caller_id_number=".. wakeup_caller_id_number.."}user/"..wakeup_destination.."@"..domain_name.." &lua('wakeup.lua "..domain_name.." "..wakeup_destination.." "..wakeup_call_sound.."') ";
				freeswitch.consoleLog("NOTICE", "wakeup: "..cmd_string.."\n");
				reply = api:executeString(cmd_string);
			end
	end
else
	--prompt for the wakeup call information
	if (session:ready()) then
		--session commands
			session:answer();
			session:setAutoHangup(false);

		--get the dialplan variables and set them as local variables
			domain_name = session:getVariable("domain_name");
			time_zone_offset = session:getVariable("time_zone_offset");
			sip_number_alias = session:getVariable("sip_number_alias");
			destination_number = session:getVariable("destination_number");
			wakeup_destination = session:getVariable("wakeup_destination");
			sip_from_user = session:getVariable("sip_from_user");
			sip_auto_answer = session:getVariable("sip_auto_answer");
			snooze_time = session:getVariable("snooze_time");
			wakeup_caller_id_name = session:getVariable("wakeup_caller_id_name");
			wakeup_caller_id_number = session:getVariable("wakeup_caller_id_name");
			wakeup_destination_sound = session:getVariable("wakeup_destination_sound");
			wakeup_greeting_sound = session:getVariable("wakeup_greeting_sound");
			wakeup_call_sound = session:getVariable("wakeup_call_sound");
			wakeup_scheduled_sound = session:getVariable("wakeup_scheduled_sound");
			wakeup_accept_sound = session:getVariable("wakeup_accept_sound");
			wakeup_cancelled_sound = session:getVariable("wakeup_cancelled_sound");

		--set the defaults
			if (not time_zone_offset) then time_zone_offset = ''; end
			if (not sip_number_alias) then sip_number_alias = ''; end
			if (not sip_auto_answer) then sip_auto_answer = ''; end
			if (not snooze_time) then snooze_time = '600'; end
			if (not wakeup_caller_id_name) then wakeup_caller_id_name = 'wakeup call'; end
			if (not wakeup_caller_id_number) then wakeup_caller_id_number = destination_number; end
			if (not wakeup_destination_sound) then wakeup_destination_sound = 'phrase:wakeup-destination'; end
			if (not wakeup_greeting_sound) then wakeup_greeting_sound = 'phrase:wakeup-greeting'; end
			if (not wakeup_call_sound) then wakeup_call_sound = 'phrase:wakeup-call'; end
			if (not wakeup_scheduled_sound) then wakeup_scheduled_sound = 'phrase:wakeup-scheduled'; end
			if (not wakeup_accept_sound) then wakeup_accept_sound = 'phrase:wakeup-accept'; end
			if (not wakeup_cancelled_sound) then wakeup_cancelled_sound = 'phrase:wakeup-cancelled'; end

		--sleep
			session:sleep('500');
	end
	if (session:ready()) then
		--set the wakeup destination
			if (wakeup_destination == "prompt") then
				min_digits = 1;
				max_digits = 11;
				--if (wakeup_destination_sound == nil and string.len(wakeup_destination_sound) == 0) then
				--	wakeup_destination_sound = "phrase:wakeup-destination";
				--end
				wakeup_destination = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", wakeup_destination_sound, "", "\\d+");
			else
				if (is_numeric(wakeup_destination)) then
					--use the provided wakeup id
				else
					if (is_numeric(sip_number_alias)) then
						wakeup_destination = sip_number_alias;
					else
						wakeup_destination = sip_from_user;
					end
				end
			end

		--sleep
			session:sleep('500');
	end
	if (session:ready()) then
		--play the wakeup greeting
			min_digits = 0;
			max_digits = 1;
			max_tries = 1;
			digit_timeout = '1000';
			session:streamFile(wakeup_greeting_sound);
			--wakeup_greeting = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", wakeup_greeting_sound, "", "\\d+");
	end
	if (session:ready()) then
		--get the wakeup hours
			min_digits = 2;
			max_digits = 2;
			max_tries = 3;
			digit_timeout = '3000';
			if (wakeup_hours_sound == nil) then
				wakeup_hours_sound = "phrase:wakeup-hours";
			end
			wakeup_hours = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", wakeup_hours_sound, "", "\\d+");
	end
	if (session:ready()) then
		--get the wakeup minutes
			min_digits = 2;
			max_digits = 2;
			max_tries = 3;
			if (wakeup_minutes_sound == nil) then
				wakeup_minutes_sound = "phrase:wakeup-minutes";
			end
			wakeup_minutes = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", wakeup_minutes_sound, "", "\\d+");
	end
	if (session:ready()) then
		--send the time to the log
			freeswitch.consoleLog("NOTICE", "wakeup time: "..wakeup_hours.."\n");

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
			--wakeup_hours = string.sub(wakeup_time, 1, 2);
			--wakeup_minutes = string.sub(wakeup_time, 3);

		--show the wakeup time, hours, and minutes to the log
			--freeswitch.consoleLog("NOTICE", "wakeup_time "..wakeup_time.."\n");
			--freeswitch.consoleLog("NOTICE", "wakeup_hours "..wakeup_hours.."\n");
			--freeswitch.consoleLog("NOTICE", "wakeup_minutes "..wakeup_minutes.."\n");

		--convert the time, hours and minutes to numbers
			wakeup_time = tonumber(tostring(wakeup_hours)..tostring(wakeup_minutes));
			--wakeup_hours = tonumber(wakeup_hours);
			--wakeup_minutes = tonumber(wakeup_minutes);

		--set the time
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
	end
	if (session:ready()) then
		--wakeup call has been scheduled
			session:streamFile(wakeup_scheduled_sound);
			session:say(wakeup_time, "en", "number", "ITERATED");

		--handle auto answer
			if (sip_auto_answer == "true") then
				auto_answer = "sip_auto_answer=true,sip_h_Alert-Info='Ring Answer'";
			else
				auto_answer = "sip_auto_answer=false";
			end

		--schedule the wakeup call
			cmd_string = "sched_api +"..sched_api_time.." wakeup-call-"..wakeup_destination.." originate {hangup_after_bridge=false,"..auto_answer..",snooze_time="..snooze_time..",origination_caller_id_name='".. wakeup_caller_id_name.."',origination_caller_id_number=".. wakeup_caller_id_number..",wakeup_caller_id_name='".. wakeup_caller_id_name.."',wakeup_caller_id_number=".. wakeup_caller_id_number.."}user/"..wakeup_destination.."@"..domain_name.." &lua('wakeup.lua "..domain_name.." "..wakeup_destination.." "..wakeup_call_sound.."') ";
			freeswitch.consoleLog("NOTICE", "wakeup: "..cmd_string.."\n");
			api = freeswitch.API();
			reply = api:executeString(cmd_string);

		--wakeup confirm press 1 to 3
			min_digits = 1;
			max_digits = 1;
			wakeup_accept = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", wakeup_accept_sound, "", "\\d+");

		--accept
			if (wakeup_accept == "1") then
				--send a message to the console
					freeswitch.consoleLog("NOTICE", "wakeup: accepted\n");
				--hangup
					session:hangup();
			end

		--cancel
			if (wakeup_accept == "2") then
				--send a message to the console
					freeswitch.consoleLog("NOTICE", "wakeup: cancelled\n");

				--unschedule the call
					cmd_string = "sched_del wakeup-call-"..wakeup_destination;
					freeswitch.consoleLog("NOTICE", "wakeup: "..cmd_string.."\n");
					api = freeswitch.API();
					reply = api:executeString(cmd_string);

				--play the cancel message
					session:streamFile(wakeup_cancelled_sound);

				--hangup
					session:hangup();
			end
	end
end
