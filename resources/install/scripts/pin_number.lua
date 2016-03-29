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

max_tries = 3;
digit_timeout = 5000;
max_retries = 3;
tries = 0;

--define the trim function
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

function check_pin_number()
	--sleep
		session:sleep(500);
	--increment the number of tries
		tries = tries + 1;
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
			if (tries < max_tries) then
				session:streamFile("phrase:voicemail_fail_auth:#");

				check_pin_number();
			else
				session:streamFile("phrase:voicemail_fail_auth:#");
				session:hangup("NORMAL_CLEARING");
				return;
			end
		end
end

if ( session:ready() ) then
	session:answer( );
	pin_number = session:getVariable("pin_number");
	sounds_dir = session:getVariable("sounds_dir");

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--set defaults
		if (digit_min_length) then
			--do nothing
		else
			digit_min_length = "2";
		end

		if (digit_max_length) then
			--do nothing
		else
			digit_max_length = "11";
		end

	--if the pin number is provided then require it
		if (pin_number) then
			check_pin_number();
		end
end