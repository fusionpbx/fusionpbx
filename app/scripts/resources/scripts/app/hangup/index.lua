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
--	Copyright (C) 2015 - 2018
--	the Initial Developer. All Rights Reserved.

--set the debug options
	debug["params"] = false;
	debug["info"] = false;
	debug["sql"] = false;

--general functions
	require "resources.functions.config";
	require "resources.functions.explode";
	require "resources.functions.trim";
	require "resources.functions.base64";
	require "resources.functions.file_exists";

--load libraries
	require 'resources.functions.send_mail'

--create the api object
	api = freeswitch.API();

--check the missed calls
	function missed()
		if (missed_call_app ~= nil and missed_call_data ~= nil) then
			if (missed_call_app == "email") then
				--set the sounds path for the language, dialect and voice
					default_language = env:getHeader("default_language");
					default_dialect = env:getHeader("default_dialect");
					default_voice = env:getHeader("default_voice");
					if (not default_language) then default_language = 'en'; end
					if (not default_dialect) then default_dialect = 'us'; end
					if (not default_voice) then default_voice = 'callie'; end

				--connect to the database
					local Database = require "resources.functions.database";
					local dbh = Database.new('system');

				--prepare to get the settings
					local Settings = require "resources.functions.lazy_settings"
					local settings = Settings.new(dbh, domain_name, domain_uuid)
					local from_address = settings:get('email', 'smtp_from', 'text');

				--get the templates
					local sql = "SELECT * FROM v_email_templates ";
					sql = sql .. "WHERE (domain_uuid = :domain_uuid or domain_uuid is null) ";
					sql = sql .. "AND template_language = :template_language ";
					sql = sql .. "AND template_category = 'missed' "
					sql = sql .. "AND template_enabled = 'true' "
					sql = sql .. "ORDER BY domain_uuid DESC "
					local params = {domain_uuid = domain_uuid, template_language = default_language.."-"..default_dialect};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params, function(row)
						subject = row["template_subject"];
						body = row["template_body"];
					end);

				--prepare the headers
					local headers = {}
					headers["X-FusionPBX-Domain-UUID"] = domain_uuid;
					headers["X-FusionPBX-Domain-Name"] = domain_name;
					headers["X-FusionPBX-Call-UUID"]   = uuid;
					headers["X-FusionPBX-Email-Type"]  = 'missed';

				--remove quotes from caller id name and number
					caller_id_name = caller_id_name:gsub("'", "&#39;");
					caller_id_name = caller_id_name:gsub([["]], "&#34;");
					caller_id_number = caller_id_number:gsub("'", "&#39;");
					caller_id_number = caller_id_number:gsub([["]], "&#34;");

				--prepare the subject
					subject = subject:gsub("${caller_id_name}", caller_id_name);
					subject = subject:gsub("${caller_id_number}", caller_id_number);
					subject = subject:gsub("${sip_to_user}", sip_to_user);
					subject = subject:gsub("${dialed_user}", dialed_user);
					subject = trim(subject);
					subject = '=?utf-8?B?'..base64.encode(subject)..'?=';

				--prepare the body
					body = body:gsub("${caller_id_name}", caller_id_name);
					body = body:gsub("${caller_id_number}", caller_id_number);
					body = body:gsub("${sip_to_user}", sip_to_user);
					body = body:gsub("${dialed_user}", dialed_user);
					body = body:gsub(" ", "&nbsp;");
					body = body:gsub("%s+", "");
					body = body:gsub("&nbsp;", " ");
					body = body:gsub("\n", "");
					body = body:gsub("\n", "");
					body = trim(body);

				--send the emails
					send_mail(headers,
						from_address,
						missed_call_data,
						{subject, body}
					);

					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[missed call] " .. caller_id_number .. "->" .. sip_to_user .. "emailed to " .. missed_call_data .. "\n");
					end
			end
		end
	end

-- show all channel variables
	--serialized = env:serialize()
	--freeswitch.consoleLog("INFO","[hangup]\n" .. serialized .. "\n")

-- set channel variables to lua variables
	originate_disposition = env:getHeader("originate_disposition");
	originate_causes = env:getHeader("originate_causes");
	uuid = env:getHeader("uuid");
	domain_uuid = env:getHeader("domain_uuid");
	domain_name = env:getHeader("domain_name");
	sip_to_user = env:getHeader("sip_to_user");
	dialed_user = env:getHeader("dialed_user");
	missed_call_app = env:getHeader("missed_call_app");
	missed_call_data = env:getHeader("missed_call_data");

-- get the Caller ID
	caller_id_name = env:getHeader("caller_id_name");
	caller_id_number = env:getHeader("caller_id_number");
	if (caller_id_name == nil) then
		caller_id_name = env:getHeader("Caller-Caller-ID-Name");
	end
	if (caller_id_number == nil) then
		caller_id_number = env:getHeader("Caller-Caller-ID-Number");
	end

--show the logs
	if (debug["info"] == true) then
		freeswitch.consoleLog("INFO", "[hangup] originate_causes: " .. tostring(originate_causes) .. "\n");
		freeswitch.consoleLog("INFO", "[hangup] originate_disposition: " .. tostring(originate_disposition) .. "\n");
	end

--handle originate disposition
	if (originate_disposition ~= nil) then
		if (originate_disposition == "ORIGINATOR_CANCEL") then
			missed();
		end
	end
