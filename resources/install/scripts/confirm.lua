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
--	Copyright (C) 2010-2014
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set variables
	max_tries = "3";
	digit_timeout = "5000";

--run if the session is ready
	if ( session:ready() ) then
		--answer the call
			session:answer();

		--get the variables
			context = session:getVariable("context");
			sounds_dir = session:getVariable("sounds_dir");
			destination_number = session:getVariable("destination_number");

		--confirm or not to confirm
			if (session:getVariable("confirm")) then
				confirm = session:getVariable("confirm");
			end

		--prepare the api
			api = freeswitch.API();

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--confirm the calls
			--prompt for digits
				if (confirm == "true") then
					--send to the log
						--freeswitch.consoleLog("NOTICE", "[confirm] prompt\n");
					--get the digit
						min_digits = 1;
						max_digits = 1;
						digit = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-accept_reject_voicemail.wav", "", "\\d+");
					--process the response
						if (digit == "1") then
							--freeswitch.consoleLog("NOTICE", "[confirm] accept\n");
						elseif (digit == "2") then
							--freeswitch.consoleLog("NOTICE", "[confirm] reject\n");
							session:hangup("CALL_REJECTED"); --LOSE_RACE
						elseif (digit == "3") then
							--freeswitch.consoleLog("NOTICE", "[confirm] voicemail\n");
							session:hangup("NO_ANSWER");
						else
							--freeswitch.consoleLog("NOTICE", "[confirm] no answer\n");
							session:hangup("NO_ANSWER");
						end
				else
					--send to the log
						--freeswitch.consoleLog("NOTICE", "[confirm] automatically accepted\n");
				end
	end