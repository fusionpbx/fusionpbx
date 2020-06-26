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
			local Database = require "resources.functions.database";
			dbh = Database.new('system');

		--include json library
			if (debug["sql"]) then
				json = require "resources.functions.lunajson"
			end
	end

--define the check pin number function
	function check_pin_number()

		--sleep
			session:sleep(500);

		--increment the number of tries
			tries = tries + 1;

		--get the domain_uuid
			domain_uuid = session:getVariable("domain_uuid");
			if (domain_uuid == nil) then
				--get the domain_name
				domain_name = session:getVariable("domain_name");
				--get the domain_uuid using the domain_name
				local sql = "SELECT domain_name FROM v_domains WHERE domain_name = :domain_name";
				local params = {domain_name = domain_name};
				if (debug["sql"]) then
					freeswitch.consoleLog("NOTICE", "[pin_number] SQL: "..sql.."; params: " .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					domain_uuid = row["domain_uuid"];
				end);
			end

		--introduce authentication process
			session:streamFile("phrase:pin_number_start:#");

		--get the user ext, if applicable
			if (pin_number == "voicemail") then
				min_digits = 1;
				max_digits = 6;
				user_ext = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:pin_number_enter_extension:#", "", "\\d+");
				if (not user_ext or user_ext == "") then
					session:streamFile("phrase:voicemail_fail_auth:#");
					session:hangup("NORMAL_CLEARING");
					return;
				end
			end

		--get the user pin number
			min_digits = 2;
			max_digits = 20;
			digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");

		--validate the user pin number
			if (pin_number == "database") then
				local sql = [[SELECT * FROM v_pin_numbers
					WHERE pin_number = :digits
					AND domain_uuid = :domain_uuid
					AND enabled = 'true' ]];
				local params = {digits = digits, domain_uuid = domain_uuid};
				if (debug["sql"]) then
					freeswitch.consoleLog("NOTICE", "[pin_number] SQL: "..sql.."; params: " .. json.encode(params) .. "\n");
				end
				auth = false;
				dbh:query(sql, params, function(row)
					--get the values from the database
						accountcode = row["accountcode"];
						description = row["description"];
					--set the variable to true
						auth = true;
					--set the accountcode
						if (accountcode ~= nil) then
							session:setVariable("sip_h_X-accountcode", accountcode);
						end
					--set the authorized pin number that was used
						session:setVariable("pin_number", digits);
						session:setVariable("pin_description", description);
				end);
			elseif (pin_number == "voicemail") then
				local sql = [[SELECT * FROM v_voicemails
					WHERE voicemail_id = :user_ext
					AND voicemail_password = :digits
					AND domain_uuid = :domain_uuid 
					AND voicemail_enabled = 'true' ]];
				local params = {user_ext = user_ext, digits = digits, domain_uuid = domain_uuid};
				if (debug["sql"]) then
					freeswitch.consoleLog("NOTICE", "[pin_number] SQL: "..sql.."; params: " .. json.encode(params) .. "\n");
				end
				auth = false;
				dbh:query(sql, params, function(row)
					--set the variable to true
						auth = true;
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
