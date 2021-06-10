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
--	Copyright (C) 2010-2015
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	KonradSC <konrd@yahoo.com>
--
--	Instructions:
--	Simply add an action to your emergency outbound route. Make sure
--	the order is a lower number than your bridge statement.
--
--	Tag: action
--	Type: lua
--	Data: app.lua emergency_notify [to_email_address] [from_email_address]
--

--debug
	debug["info"] = false;
	debug["sql"] = false;

--include config.lua
	require "resources.functions.config";
	require "resources.functions.explode";
	require "resources.functions.trim";
	require "resources.functions.base64";

--get arguments
	to_email = argv[2];
	from_email = argv[3];
	
--check the missed calls
	function send_mail()

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--prepare the files
			file_subject = scripts_dir.."/app/emergency_notify/resources/templates/"..default_language.."/"..default_dialect.."/email_subject.tpl";
			file_body = scripts_dir.."/app/emergency_notify/resources/templates/"..default_language.."/"..default_dialect.."/email_body.tpl";
			if (not file_exists(file_subject)) then
				file_subject = scripts_dir.."/app/emergency_notify/resources/templates/en/us/email_subject.tpl";
				file_body = scripts_dir.."/app/emergency_notify/resources/templates/en/us/email_body.tpl";
			end

		--prepare the headers
			headers = '{"X-FusionPBX-Domain-UUID":"'..domain_uuid..'",';
			headers = headers..'"X-FusionPBX-Domain-Name":"'..domain_name..'",';
			headers = headers..'"X-FusionPBX-Call-UUID":"'..uuid..'",';
			headers = headers..'"X-FusionPBX-Email-Type":"emergency_call"}';

		--remove quotes from caller id name and number
			caller_id_name = caller_id_name:gsub("'", "&#39;");
			caller_id_name = caller_id_name:gsub([["]], "&#34;");
			caller_id_number = caller_id_number:gsub("'", "&#39;");
			caller_id_number = caller_id_number:gsub([["]], "&#34;");

		--prepare the subject
			local f = io.open(file_subject, "r");
			local subject = f:read("*all");
			f:close();
			subject = subject:gsub("${caller_id_name}", caller_id_name);
			subject = subject:gsub("${caller_id_number}", caller_id_number);
			subject = subject:gsub("${sip_to_user}", sip_to_user);
			subject = subject:gsub("${caller_destination}", caller_destination);
			subject = trim(subject);
			subject = '=?utf-8?B?'..base64.encode(subject)..'?=';

		--prepare the body
			local f = io.open(file_body, "r");
			local body = f:read("*all");
			f:close();
			body = body:gsub("${caller_id_name}", caller_id_name);
			body = body:gsub("${caller_id_number}", caller_id_number);
			body = body:gsub("${sip_to_user}", sip_to_user);
			body = body:gsub("${caller_destination}", caller_destination);
			body = body:gsub(" ", "&nbsp;");
			body = body:gsub("%s+", "");
			body = body:gsub("&nbsp;", " ");
			body = body:gsub("\n", "");
			body = body:gsub("\n", "");
			body = trim(body);

		--send the email
			cmd = "luarun email.lua "..to_email.." "..from_email.." "..headers.." '"..subject.."' '"..body.."'";
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[emergency call] cmd: " .. cmd .. "\n");
			end
			api = freeswitch.API();
			result = api:executeString(cmd);
	end

--handle originate_disposition
	if (session ~= nil and session:ready()) then
		uuid = session:getVariable("uuid");
		domain_uuid = session:getVariable("domain_uuid");
		domain_name = session:getVariable("domain_name");
		context = session:getVariable("context");
		caller_id_name = session:getVariable("outbound_caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");
		sip_to_user = session:getVariable("sip_to_user");
		caller_destination = session:getVariable("caller_destination");


		if (debug["info"] == true) then
			freeswitch.consoleLog("INFO", "[emergency_notify] caller_id_number: " .. tostring(caller_id_number) .. "\n");
			freeswitch.consoleLog("INFO", "[emergency_notify] caller_id_number: " .. tostring(caller_id_number) .. "\n");
			freeswitch.consoleLog("INFO", "[emergency_notify] caller_destination: " .. tostring(caller_destination) .. "\n");
			freeswitch.consoleLog("INFO", "[emergency_notify] to_email: " .. tostring(to_email) .. "\n");
			freeswitch.consoleLog("INFO", "[emergency_notify] from_email: " .. tostring(from_email) .. "\n");
		end

		send_mail();

	end
