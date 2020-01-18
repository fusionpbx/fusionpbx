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
--	Copyright (C) 2010-2013
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--include config.lua
	require "resources.functions.config";

--set variables
	max_tries = "3";
	digit_timeout = "5000";

--define the trim function
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

--get the argv values
	script_name = argv[0];
	argv_uuid = argv[1];
	prompt = argv[2];

--prepare the api
	api = freeswitch.API();

--answer the call
	session:answer();

--get the variables
	context = session:getVariable("context");
	sounds_dir = session:getVariable("sounds_dir");
	destination_number = session:getVariable("destination_number");
	uuid = session:getVariable("uuid");

--set the sounds path for the language, dialect and voice
	default_language = session:getVariable("default_language");
	default_dialect = session:getVariable("default_dialect");
	default_voice = session:getVariable("default_voice");
	if (not default_language) then default_language = 'en'; end
	if (not default_dialect) then default_dialect = 'us'; end
	if (not default_voice) then default_voice = 'callie'; end

--if an extension answer the call
	-- user_exists id 1005 voip.fusionpbx.com
	--		cmd = "user_exists id ".. destination_number .." "..context;
	--		result = api:executeString(cmd);
	--		freeswitch.consoleLog("NOTICE", "[confirm] "..cmd.." --"..result.."--\n");
	--		if (result == "true") then
	--			prompt = false;
	--		end

--prompt for digits
	if (prompt == "true") then
		--get the digit
			min_digits = 1;
			max_digits = 1;

			--check if the original call exists
			cmd = "uuid_exists "..argv_uuid;
			if (trim(api:executeString(cmd)) == "false") then
				session:hangup("NO_ANSWER");
			end

			--digit = session:playAndGetDigits(2, 5, 3, 3000, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-accept_reject.wav", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-that_was_an_invalid_entry.wav", "\\d+")
			digit = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-accept_reject.wav", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-that_was_an_invalid_entry.wav", "\\d+");
		--process the response
			if (digit == "1") then
				--confirmed call accepted
				confirmed = true;
			elseif (digit == "2") then
				freeswitch.consoleLog("NOTICE", "[confirm] reject\n");
				session:hangup("CALL_REJECTED"); --LOSE_RACE
			else
				--freeswitch.consoleLog("NOTICE", "[confirm] no answer\n");
				session:hangup("NO_ANSWER");
			end
	else
		freeswitch.consoleLog("NOTICE", "[confirm] automatically accepted\n");
		confirmed = true;
	end

	if (confirmed) then
		--cmd = "bgapi sched_transfer +1 "..uuid.." *5901";
		--freeswitch.consoleLog("NOTICE", "[ring_group] uuid: "..cmd.."\n");
		--result = api:executeString(cmd);
		freeswitch.consoleLog("NOTICE", "[confirm] accepted\n");

		--check if the original call exists
			cmd = "uuid_exists "..argv_uuid;
			if (trim(api:executeString(cmd)) == "false") then
				session:hangup("NO_ANSWER");
			end

		--unschedule the timeout
			cmd = "sched_del ring_group:"..argv_uuid;
			freeswitch.consoleLog("NOTICE", "[confirm] cmd: "..cmd.."\n");
			results = trim(api:executeString(cmd));

		--get the uuids and remove the other calls
			cmd = "uuid_getvar "..argv_uuid.." uuids";
			freeswitch.consoleLog("NOTICE", "[confirm] cmd: "..cmd.."\n");
			uuids = trim(api:executeString(cmd));
			u = explode(",", uuids);
			for k,v in pairs(u) do
				if (uuid ~= v) then
					cmd = "uuid_kill "..v;
					freeswitch.consoleLog("NOTICE", "[confirm] cmd: "..cmd.."\n");
					result = trim(api:executeString(cmd));
				end
			end

		--bridge the call
			cmd = "uuid_bridge "..uuid.." "..argv_uuid;
			result = trim(api:executeString(cmd));
			session:execute("valet_park", "confirm "..argv_uuid);
	end
