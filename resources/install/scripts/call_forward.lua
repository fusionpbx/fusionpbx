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

pin_number = "";
max_tries = "3";
digit_timeout = "3000";

function file_exists(fname)
	local f = io.open(fname, "r")
	if (f and f:read()) then return true end
end

if ( session:ready() ) then
	session:answer();
	--session:execute("info", "");
	extension = session:getVariable("user_name");
	pin_number = session:getVariable("pin_number");
	sounds_dir = session:getVariable("sounds_dir");
	dialplan_default_dir = session:getVariable("dialplan_default_dir");
	call_forward_number = session:getVariable("call_forward_number");
	extension_required = session:getVariable("extension_required");
	context = session:getVariable("context");
	if (not context ) then context = 'default'; end

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--if the pin number is provided then require it
	if (pin_number) then
		min_digits = string.len(pin_number);
		max_digits = string.len(pin_number)+1;
		digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/custom/please_enter_the_pin_number.wav", "", "\\d+");
		if (digits == pin_number) then
			--pin is correct
		else
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/custom/your_pin_number_is_incorect_goodbye.wav");
			session:hangup("NORMAL_CLEARING");
			return;
		end
	end

	--if extension_requires is true then get the extension number
	if (extension_required) then
		if (extension_required == "true") then
			extension = session:playAndGetDigits(3, 6, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/custom/please_enter_the_extension_number.wav", "", "\\d+");
		end
	end


	if (file_exists(dialplan_default_dir.."/000_call_forward_"..extension..".xml")) then
		--file exists

		--remove the call forward dialplan entry
		os.remove (dialplan_default_dir.."/000_call_forward_"..extension..".xml");

		--stream file
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/custom/call_forward_has_been_deleted.wav");

		--wait for the file to be written before proceeding
			session:sleep(1000);

	else
		--file does not exist

		dtmf = ""; --clear dtmf digits to prepare for next dtmf request
		if (call_forward_number) then
			-- do nothing
		else
			-- get the call forward number
			call_forward_number = session:playAndGetDigits(3, 15, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/custom/please_enter_the_phone_number.wav", "", "\\d+");
		end
		if (string.len(call_forward_number) > 0) then
		--write the xml file
			xml = "<extension name=\"call_forward_"..extension.."\" >\n";
			xml = xml .. "	<condition field=\"destination_number\" expression=\"^"..extension.."$\">\n";
			xml = xml .. "		<action application=\"transfer\" data=\""..call_forward_number.." XML "..context.."\"/>\n";
			xml = xml .. "	</condition>\n";
			xml = xml .. "</extension>\n";
			session:execute("log", xml);
			local file = assert(io.open(dialplan_default_dir.."/000_call_forward_"..extension..".xml", "w"));
			file:write(xml);
			file:close();

		--wait for the file to be written before proceeding
			--session:sleep(20000); 

		--stream file
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/custom/call_forward_has_been_set.wav");
		end
	end

	--reloadxml
		api = freeswitch.API();
		reply = api:executeString("reloadxml");

	--wait for the file to be written before proceeding
		session:sleep(1000);

	session:hangup();

end