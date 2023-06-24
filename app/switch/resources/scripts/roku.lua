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

predefined_destination = "";
max_tries = "3";
digit_timeout = "5000";
port = "8080";

if ( session:ready() ) then
	session:answer( );
	pin_number = session:getVariable("pin_number");
	sounds_dir = session:getVariable("sounds_dir");
	host = session:getVariable("host");

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (default_language) then else default_language = 'en'; end
		if (default_dialect) then else default_dialect = 'us'; end
		if (default_voice) then else default_voice = 'callie'; end

	digitmaxlength = 0;
	timeoutpin = 7500;
	timeouttransfer = 7500;

--sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."

	--if the pin number is provided then require it
	if (pin_number) then
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

	if (session:ready()) then
		session:answer();
		min_digits = 1;
		max_digits = 1;
		digitmaxlength = 1;
		digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");

		x = 0;
		while (session:ready() == true) do
			if (string.len(digits) == 0) then
				--getDigits(length, terminators, timeout, digit_timeout, abs_timeout)
				digits = session:getDigits(1, "#", 40000);
			end
			if (string.len(digits) > 0) then
				--press star to exit
					if (digits == "*") then
						break;
					end
				--send the command to php
					session:execute("system","/usr/local/bin/php /usr/local/www/fusionpbx/mod/roku/roku.php "..digits.." "..host.." "..port);
			end
			digits = "";
			if (x > 17500) then
				break;
			end
		end
		session:hangup("NORMAL_CLEARING");
	end
end