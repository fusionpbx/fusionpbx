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
--	Copyright (C) 2010-2018
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Salvatore Caruso <salvatore.caruso@nems.it>
--	Riccardo Granchi <riccardo.granchi@nems.it>
--	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>

--debug
	debug["info"] = false;
	debug["sql"] = false;

--include config.lua
	require "resources.functions.config";
	require "resources.functions.explode";
	require "resources.functions.trim";
	require "resources.functions.base64";

--load libraries
	require 'resources.functions.send_mail'

--check the missed calls
	function missed()
		if (missed_call_app ~= nil and missed_call_data ~= nil) then
			if (missed_call_app == "email") then
				--set the sounds path for the language, dialect and voice
					default_language = session:getVariable("default_language");
					default_dialect = session:getVariable("default_dialect");
					default_voice = session:getVariable("default_voice");
					if (not default_language) then default_language = 'en'; end
					if (not default_dialect) then default_dialect = 'us'; end
					if (not default_voice) then default_voice = 'callie'; end

				--connect to the database
					local Database = require "resources.functions.database";
					local dbh = Database.new('system');

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

				--send the email
					send_mail(headers,
						nil,
						missed_call_data,
						{subject, body}
					);

					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[missed call] " .. caller_id_number .. "->" .. sip_to_user .. "emailed to " .. missed_call_data .. "\n");
					end
			end
		end
	end

--handle originate_disposition
	if (session ~= nil and session:ready()) then
		uuid = session:getVariable("uuid");
		domain_uuid = session:getVariable("domain_uuid");
		domain_name = session:getVariable("domain_name");
		context = session:getVariable("context");
		originate_disposition = session:getVariable("originate_disposition");
		originate_causes = session:getVariable("originate_causes");
		hangup_on_subscriber_absent = session:getVariable("hangup_on_subscriber_absent");
		hangup_on_call_reject = session:getVariable("hangup_on_call_reject");
		caller_id_name = session:getVariable("caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");
		call_direction = session:getVariable("call_direction");
		if (caller_direction == "local") then
			caller_id_name = session:getVariable("effective_caller_id_name");
		end
		sip_to_user = session:getVariable("sip_to_user");
		dialed_user = session:getVariable("dialed_user");
		missed_call_app = session:getVariable("missed_call_app");
		missed_call_data = session:getVariable("missed_call_data");
		sip_code = session:getVariable("last_bridge_proto_specific_hangup_cause");

		if (debug["info"] == true) then
			freeswitch.consoleLog("INFO", "[failure_handler] originate_causes: " .. tostring(originate_causes) .. "\n");
			freeswitch.consoleLog("INFO", "[failure_handler] originate_disposition: " .. tostring(originate_disposition) .. "\n");
			freeswitch.consoleLog("INFO", "[failure_handler] hangup_on_subscriber_absent: " .. tostring(hangup_on_subscriber_absent) .. "\n");
			freeswitch.consoleLog("INFO", "[failure_handler] hangup_on_call_reject: " .. tostring(hangup_on_call_reject) .. "\n");
			freeswitch.consoleLog("INFO", "[failure_handler] sip_code: " .. tostring(sip_code) .. "\n");
		end

		if (originate_causes ~= nil) then
			array = explode("|",originate_causes);
			if (string.find(array[1], "USER_BUSY")) or (sip_code == "sip:486") then
				originate_disposition = "USER_BUSY";
				session:setVariable("originate_disposition", originate_disposition);
			end
		end

		if (originate_disposition ~= nil) then
			if (originate_disposition == 'USER_BUSY') then

				--handle USER_BUSY
					dialed_extension = session:getVariable("dialed_extension");
					last_busy_dialed_extension = session:getVariable("last_busy_dialed_extension");
					if (debug["info"] ) then
						freeswitch.consoleLog("INFO", "[failure_handler] last_busy_dialed_extension: " .. tostring(last_busy_dialed_extension) .. "\n");
					end

				--transfer to the forward busy destination
					if (dialed_extension ~= nil and dialed_extension ~= last_busy_dialed_extension) then
						forward_busy_enabled = session:getVariable("forward_busy_enabled");
						if (forward_busy_enabled == "true") then
							forward_busy_destination = session:getVariable("forward_busy_destination");
							if (forward_busy_destination ~= nil and string.len(forward_busy_destination) > 0) then
								--handle USER_BUSY - forwarding to number
								session:setVariable("last_busy_dialed_extension", dialed_extension);
								if (forward_busy_destination == nil) then
									freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding on busy to hangup\n");
									session:hangup("USER_BUSY");
								else
									freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding on busy to: " .. forward_busy_destination .. "\n");
									session:transfer(forward_busy_destination, "XML", context);
								end
							else
								--send missed call notification
								missed();

								--handle USER_BUSY - hangup
								freeswitch.consoleLog("NOTICE", "[failure_handler] forward on busy with empty destination: hangup(USER_BUSY)\n");
								session:hangup("USER_BUSY");
							end
						end
					end

			elseif (originate_disposition == "NO_ANSWER") or (sip_code == "sip:480") then

				--handle NO_ANSWER
				forward_no_answer_enabled = session:getVariable("forward_no_answer_enabled");
				if (forward_no_answer_enabled == "true") then
					forward_no_answer_destination = session:getVariable("forward_no_answer_destination");
					if (forward_no_answer_destination == nil) then
						freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding no answer to hangup\n");
						session:hangup("NO_ANSWER");
					else
						freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding no answer to: " .. forward_no_answer_destination .. "\n");
						session:transfer(forward_no_answer_destination, "XML", context);
					end
				else
					--send missed call notification
					missed();
				end
				if (debug["info"] ) then
					freeswitch.consoleLog("NOTICE", "[failure_handler] - NO_ANSWER\n");
				end

			elseif (originate_disposition == "USER_NOT_REGISTERED") then

				--handle USER_NOT_REGISTERED
				forward_user_not_registered_enabled = session:getVariable("forward_user_not_registered_enabled");
				if (forward_user_not_registered_enabled == "true") then
					forward_user_not_registered_destination = session:getVariable("forward_user_not_registered_destination");
					if (forward_user_not_registered_destination == nil) then
						freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding user not registered to hangup\n");
						session:hangup("NO_ANSWER");
					else
						freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding user not registerd to: " .. forward_user_not_registered_destination .. "\n");
						session:transfer(forward_user_not_registered_destination, "XML", context);
					end
				else
					--send missed call notification
					missed();
				end

				--send missed call notification
				--missed();

				--handle USER_NOT_REGISTERED
				if (debug["info"] ) then
					freeswitch.consoleLog("NOTICE", "[failure_handler] - USER_NOT_REGISTERED - Doing nothing\n");
				end

			elseif (originate_disposition == "SUBSCRIBER_ABSENT" and hangup_on_subscriber_absent == "true") then
				--send missed call notification
				missed();

				--handle SUBSCRIBER_ABSENT
				freeswitch.consoleLog("NOTICE", "[failure_handler] - SUBSCRIBER_ABSENT - hangup(UNALLOCATED_NUMBER)\n");
				session:hangup("UNALLOCATED_NUMBER");

			elseif (originate_disposition == "CALL_REJECTED" and hangup_on_call_reject =="true") then
				--send missed call notification
				missed();

				--handle CALL_REJECT
				freeswitch.consoleLog("NOTICE", "[failure_handler] - CALL_REJECT - hangup()\n");
				session:hangup();

			end
		end
	end
