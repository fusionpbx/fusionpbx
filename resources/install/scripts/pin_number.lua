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
--	Copyright (C) 2010-2016
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set the preset variables
	max_tries = 3;
	digit_timeout = 5000;
	max_retries = 3;
	tries = 0;

--include config.lua
	require "resources.functions.config";

--define the functions
	require "resources.functions.trim";
	require "resources.functions.explode";

--make sure the session is ready
	if ( session:ready() ) then
		--answer the call
			session:answer( );

		--get the variables
			pin_number = session:getVariable("pin_number");
			sounds_dir = session:getVariable("sounds_dir");

		--connect to the database
			if (pin_number == "database") then
				require "resources.functions.database_handle";
				dbh = database_handle('system');
			end
	end

--define the check pin number function
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
			if (pin_number == "database") then
				sql = [[SELECT * FROM v_pin_numbers
					WHERE pin_number = ']] .. digits ..[['
					AND enabled = 'true' ]];
				if (debug["sql"]) then
					freeswitch.consoleLog("NOTICE", "SQL: "..sql.."\n");
				end
				auth = false;
				dbh:query(sql, function(row)
					--get the values from the database
						domain_uuid = row["domain_uuid"];
						accountcode = row["accountcode"];
					--set the variable to true
						auth = true;
					--set the accountcode
						if (accountcode ~= nil) then
							session:setVariable("accountcode", accountcode);
						end
					--set the authorized pin number that was used
						session:setVariable("pin_number", digits);
				end);
			else
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

--make sure the session is ready
	if ( session:ready() ) then

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