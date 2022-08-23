--	FusionPBX
--	Version: MPL 1.1

--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/

--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.

--	The Original Code is FusionPBX

--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Portions created by the Initial Developer are Copyright (C) 2019 - 2020
--	the Initial Developer. All Rights Reserved.

--includes
	local Database = require "resources.functions.database";
	local route_to_bridge = require "resources.functions.route_to_bridge"
	local Settings = require "resources.functions.lazy_settings"
	require "resources.functions.trim";
	require 'resources.functions.send_mail';

--get the variables
	if (session:ready()) then
		domain_name = session:getVariable("domain_name");
		domain_uuid = session:getVariable("domain_uuid");
		uuid = session:getVariable("uuid");
		destination_number = session:getVariable("destination_number");
		caller_id_name = session:getVariable("caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");
		outbound_caller_id_name = session:getVariable("outbound_caller_id_name");
		outbound_caller_id_number = session:getVariable("outbound_caller_id_number");
		call_direction = session:getVariable("call_direction");
		original_destination_number = session:getVariable("destination_number");
		missed_call_app = session:getVariable("missed_call_app");
		missed_call_data = session:getVariable("missed_call_data");
		sip_to_user = session:getVariable("sip_to_user");
		dialed_user = session:getVariable("dialed_user") or '';
	end

--set caller id
	if (effective_caller_id_name ~= nil) then
		caller_id_name = effective_caller_id_name;
	end
	if (effective_caller_id_number ~= nil) then
		caller_id_number = effective_caller_id_number;
	end

--default to local if nil
	if (call_direction == nil) then
		call_direction = "local";
	end

--connect to the database
	local dbh = Database.new('system');

--set the strategy
	local settings = Settings.new(dbh, domain_name, domain_uuid);
	local follow_me_strategy = settings:get('follow_me', 'strategy', 'text') or 'enterprise'; --simultaneous, enterprise

--include json library
	debug["sql"] = false;
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson";
	end

--prepare the api object
	api = freeswitch.API();

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
                                        body = body:gsub("'", "&#39;");
                                        body = body:gsub([["]], "&#34;");
                                        body = trim(body);

                                --send the emails
                                        send_mail(headers,
                                                missed_call_data,
                                                {subject, body}
                                        );

                                        if (debug["info"]) then
                                                freeswitch.consoleLog("notice", "[missed call] " .. caller_id_number .. "->" .. sip_to_user .. "emailed to " .. missed_call_data .. "\n");
                                        end
                        end
                end
        end

--get the destination and follow the forward
	function get_forward_all(count, destination_number, domain_name)
		cmd = "user_exists id ".. destination_number .." "..domain_name;
		--freeswitch.consoleLog("notice", "[follow me][call forward all] " .. cmd .. "\n");
		user_exists = api:executeString(cmd);
		if (user_exists == "true") then
			---check to see if the new destination is forwarded
				cmd = "user_data ".. destination_number .."@" ..domain_name.." var forward_all_enabled";
				if (api:executeString(cmd) == "true") then
					--get the toll_allow var	
						cmd = "user_data ".. destination_number .."@" ..domain_name.." var toll_allow";
						toll_allow = api:executeString(cmd);
						--freeswitch.consoleLog("notice", "[follow me][call forward all] " .. destination_number .. " toll_allow is ".. toll_allow .."\n");
					
					--get the extensions accountcode
						cmd = "user_data ".. destination_number .."@" ..domain_name.." var accountcode";
						accountcode = api:executeString(cmd);

					--get the new destination 
						cmd = "user_data ".. destination_number .."@" ..domain_name.." var forward_all_destination";
						destination_number = api:executeString(cmd);

						--freeswitch.consoleLog("notice", "[follow me][call forward all] " .. count .. " " .. cmd .. " ".. destination_number .."\n");
						count = count + 1;
						if (count < 5) then
							count, destination_number, toll_allow, accountcode = get_forward_all(count, destination_number, domain_name);
						end
				end
		end
		return count, destination_number, toll_allow, accountcode;
	end

--get the forward busy
	--cmd = "user_data ".. destination_number .."@"..domain_name.." var forward_busy_enabled=";
	--forward_busy_enabled = trim(api:executeString(cmd));
	--cmd = "user_data ".. destination_number .."@"..domain_name.." var forward_busy_destination=";
	--forward_busy_destination = trim(api:executeString(cmd));

--get the domain_uuid
	if (domain_uuid == nil) then
		if (domain_name ~= nil) then
			local sql = "SELECT domain_uuid FROM v_domains "
				.. "WHERE domain_name = :domain_name ";
			local params = {domain_name = domain_name};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params, function(rows)
				domain_uuid = rows["domain_uuid"];
			end);
		end
	end

--select data from the database
	local sql = "select follow_me_uuid, toll_allow, accountcode ";
	sql = sql .. "from v_extensions ";
	sql = sql .. "where domain_uuid = :domain_uuid ";
	sql = sql .. "and ( ";
	sql = sql .. "	extension = :destination_number ";
	sql = sql .. "	OR number_alias = :destination_number ";
	sql = sql .. ") ";
	local params = {domain_uuid = domain_uuid,destination_number = destination_number};
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
	end
	status = dbh:query(sql, params, function(row)
		follow_me_uuid = row["follow_me_uuid"];
		extension_toll_allow = row["toll_allow"];
		accountcode = row["accountcode"];
	end);
	--dbh:query(sql, params, function(row);

--get the follow me data
	if (follow_me_uuid ~= nil) then
		local sql = "select cid_name_prefix, cid_number_prefix, ";
		sql = sql .. "follow_me_enabled, follow_me_ignore_busy ";
		sql = sql .. "from v_follow_me ";
		sql = sql .. "where domain_uuid = :domain_uuid ";
		sql = sql .. "and follow_me_uuid = :follow_me_uuid; ";
		local params = {domain_uuid = domain_uuid,follow_me_uuid = follow_me_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		status = dbh:query(sql, params, function(row)
			caller_id_name_prefix = row["cid_name_prefix"];
			caller_id_number_prefix = row["cid_number_prefix"];
			follow_me_enabled = row["follow_me_enabled"];
			follow_me_ignore_busy = row["follow_me_ignore_busy"];
		end);
		--dbh:query(sql, params, function(row);
	end

--get the follow me destinations
	if (follow_me_uuid ~= nil) then
		sql = "select d.domain_uuid, d.domain_name, f.follow_me_destination as destination_number, ";
		sql = sql .. "f.follow_me_delay as destination_delay, f.follow_me_timeout as destination_timeout, ";
		sql = sql .. "f.follow_me_prompt as destination_prompt ";
		sql = sql .. "from v_follow_me_destinations as f, v_domains as d ";
		sql = sql .. "where f.follow_me_uuid = :follow_me_uuid ";
		sql = sql .. "and f.domain_uuid = d.domain_uuid ";
		sql = sql .. "order by f.follow_me_order; ";
		local params = {follow_me_uuid = follow_me_uuid};
		destinations = {};
		destination_count = 0;
		x = 1;
		dbh:query(sql, params, function(row)
			domain_uuid = row["domain_uuid"];
			domain_name = row["domain_name"];

			if (row.destination_prompt == "1" or row.destination_prompt == "2") then
				prompt = "true";
			end

			--follow the forwards
			count, destination_number, toll_allow, accountcode = get_forward_all(0, row.destination_number, domain_name);

			--update values
			row['destination_number'] = destination_number
			--row['toll_allow'] = toll_allow;

			--check if the user exists
			cmd = "user_exists id ".. destination_number .." "..domain_name;
			user_exists = api:executeString(cmd);

			--cmd = "user_exists id ".. destination_number .." "..domain_name;
			if (user_exists == "true") then
				--add user_exists true or false to the row array
					row['user_exists'] = "true";
				--handle do_not_disturb
					cmd = "user_data ".. destination_number .."@" ..domain_name.." var do_not_disturb";
					if (api:executeString(cmd) ~= "true") then
						--add the row to the destinations array
						destinations[x] = row;
					end
			else
				--set the values
					external = "true";
					row['user_exists'] = "false";
					if (accountcode ~= nil) then
						row['accountcode'] = accountcode;
					else
						row['accountcode'] = domain_name;
					end
				--add the row to the destinations array
					destinations[x] = row;
			end
			row['domain_name'] = domain_name;
			destination_count = destination_count + 1;
			x = x + 1;
		end);
	end

--get the dialplan data and save it to a table
	if (external == "true") then
		dialplans = route_to_bridge.preload_dialplan(
			dbh, domain_uuid, {hostname = hostname, context = context}
		)
	end

--prepare the array of destinations
	x = 1;
	for key, row in pairs(destinations) do
		--set the values from the database as variables
		destination_number = row.destination_number;

		--determine if the user is registered if not registered then lookup
		if (user_exists == "true") then
			cmd = "sofia_contact */".. destination_number .."@" ..domain_name;
			if (api:executeString(cmd) == "error/user_not_registered") then
				freeswitch.consoleLog("NOTICE", "[follow_me] "..cmd.."\n");
				cmd = "user_data ".. destination_number .."@" ..domain_name.." var forward_user_not_registered_enabled";
				freeswitch.consoleLog("NOTICE", "[follow_me] "..cmd.."\n");
				if (api:executeString(cmd) == "true") then
					--get the new destination number
					cmd = "user_data ".. destination_number .."@" ..domain_name.." var forward_user_not_registered_destination";
					freeswitch.consoleLog("NOTICE", "[follow_me] "..cmd.."\n");
					not_registered_destination_number = api:executeString(cmd);
					freeswitch.consoleLog("NOTICE", "[follow_me] "..not_registered_destination_number.."\n");
					if (not_registered_destination_number ~= nil) then
						destination_number = not_registered_destination_number;
						destinations[key]['destination_number'] = destination_number;
					end
				end
			end
		end
	end

--process the destinations
	x = 1;
	for key, row in pairs(destinations) do
		freeswitch.consoleLog("NOTICE", "[follow me] destination_number: "..row.destination_number.."\n");
	end

--process the destinations
	x = 1;
	for key, row in pairs(destinations) do
		--set the values from the database as variables
			domain_uuid = row.domain_uuid;
			destination_number = row.destination_number;
			destination_delay = row.destination_delay;
			destination_timeout = row.destination_timeout;
			destination_prompt = row.destination_prompt;
			group_confirm_key = row.group_confirm_key;
			group_confirm_file = row.group_confirm_file;
			toll_allow = row.toll_allow;
			accountcode = row.accountcode;
			user_exists = row.user_exists;

		--follow the forwards
			count, destination_number = get_forward_all(0, destination_number, domain_name);

		--check if the user exists
			cmd = "user_exists id ".. destination_number .." "..domain_name;
			user_exists = api:executeString(cmd);

		--set ringback
			--follow_me_ringback = format_ringback(follow_me_ringback);
			--session:setVariable("ringback", follow_me_ringback);
			--session:setVariable("transfer_ringback", follow_me_ringback);

		--set the timeout if there is only one destination
			if (session:ready() and destination_count == 1) then
				session:execute("set", "call_timeout="..row.destination_timeout);
			end

		--setup the delimiter
			delimiter = ",";
			if (follow_me_strategy == "simultaneous") then
				delimiter = ",";
			end
			if (follow_me_strategy == "enterprise") then
				delimiter = ":_:";
			end

		--leg delay settings
			if (follow_me_strategy == "enterprise") then
				timeout_name = "originate_timeout";
				delay_name = "originate_delay_start";
				destination_delay = destination_delay * 500;
			else
				timeout_name = "leg_timeout";
				delay_name = "leg_delay_start";
			end

		--set confirm
			if (session:ready() and follow_me_strategy == "simultaneous") then
					session:execute("set", "group_confirm_key=exec");
					session:execute("set", "group_confirm_file=lua ".. scripts_dir:gsub('\\','/') .."/confirm.lua");
			end

		--determine confirm prompt
			if (destination_prompt == nil) then
				group_confirm = "confirm=false,";
			elseif (destination_prompt == "1") then
				group_confirm = "group_confirm_key=exec,group_confirm_file=lua ".. scripts_dir:gsub('\\','/') .."/confirm.lua,confirm=true";
			elseif (destination_prompt == "2") then
				group_confirm = "group_confirm_key=exec,group_confirm_file=lua ".. scripts_dir:gsub('\\','/') .."/confirm.lua,confirm=true";
			else
				group_confirm = "confirm=false";
			end

		--process according to user_exists, sip_uri, external number
			if (user_exists == "true") then
				--get the extension_uuid
				cmd = "user_data ".. destination_number .."@"..domain_name.." var extension_uuid";
				extension_uuid = trim(api:executeString(cmd));

				local record_session = "";
				--if session is already recording then skip
				if (session:getVariable("record_session") ~= "true") then
					local cmd = "user_data "..destination_number.."@"..domain_name.." var user_record";
					local user_record = api:executeString(cmd);
					if (user_record == "all" or user_record == call_direction) then 
						local recordings_dir = session:getVariable("recordings_dir");
						local record_ext = session:getVariable("record_ext") or "wav";
						local record_name = uuid.."."..record_ext;
						local record_path = recordings_dir .. "/" .. domain_name .. "/archive/" .. os.date("%Y/%b/%d");
						record_session = ",api_on_answer='uuid_record "..uuid.." start ".. record_path .. "/" .. record_name .. "',record_path='".. record_path .."',record_name="..record_name;
					end
				end

				--send to user
				local dial_string_to_user = "[sip_invite_domain="..domain_name..",call_direction="..call_direction..","..group_confirm..","..timeout_name.."="..destination_timeout..","..delay_name.."="..destination_delay..",dialed_extension=" .. row.destination_number .. ",extension_uuid="..extension_uuid..record_session.."]user/" .. row.destination_number .. "@" .. domain_name;
				dial_string = dial_string_to_user;
			elseif (tonumber(destination_number) == nil) then
				--sip uri
				dial_string = "[sip_invite_domain="..domain_name..",call_direction="..call_direction..","..group_confirm..","..timeout_name.."="..destination_timeout..","..delay_name.."="..destination_delay.."]" .. row.destination_number;
			else
				--external number
					route_bridge = 'loopback/'..destination_number;
					if (extension_toll_allow ~= nil) then
						toll_allow = extension_toll_allow:gsub(",", ":");
					end

				--set the toll allow to an empty string
					if (toll_allow == nil) then
						toll_allow = '';
					end

				--check if the user exists
					if tonumber(caller_id_number) ~= nil then
						cmd = "user_exists id ".. caller_id_number .." "..domain_name;
						caller_is_local = api:executeString(cmd);
					end

				--set the outbound caller id
					ignore_outbound_caller_id = session:getVariable("follow_me_ignore_outbound_caller_id");
					if (session:ready() and caller_is_local and ignore_outbound_caller_id ~= "true") then
						if (outbound_caller_id_name ~= nil) then
							caller_id_name = outbound_caller_id_name;
						end
						if (outbound_caller_id_number ~= nil) then
							caller_id_number = outbound_caller_id_number;
						end
					end

				--set the caller id
					caller_id = '';
					if (caller_id_name ~= nil) then
						caller_id = "origination_caller_id_name='"..caller_id_name.."'"
					end
					if (caller_id_number ~= nil) then
						caller_id = caller_id .. ",origination_caller_id_number="..caller_id_number;
					end

				--set the destination dial string
					-- have to double destination_delay here due a FS bug requiring a 50% delay value for internal extensions, but not external calls. 
					destination_delay = destination_delay * 2;
					dial_string = "[toll_allow=".. toll_allow ..",accountcode="..accountcode..",".. caller_id ..",sip_invite_domain="..domain_name..",domain_uuid="..domain_uuid..",call_direction="..call_direction..","..group_confirm..","..timeout_name.."="..destination_timeout..","..delay_name.."="..destination_delay.."]"..route_bridge
			end

		--add a delimiter between destinations
			if (dial_string ~= nil) then
				--freeswitch.consoleLog("notice", "[follow me] dial_string: " .. dial_string .. "\n");
				if (x == 1) then
					if (follow_me_strategy == "enterprise") then
						app_data = dial_string;
					else
						app_data = "{ignore_early_media=true}"..dial_string;
					end
				else
					if (app_data == nil) then
						if (follow_me_strategy == "enterprise") then
							app_data = dial_string;
						else
							app_data = "{ignore_early_media=true}"..dial_string;
						end
					else
						app_data = app_data .. delimiter .. dial_string;
					end
				end
			end

		--increment the value of x
			x = x + 1;
	end

--set ring ready
	if (session:ready()) then
		session:execute("ring_ready", "");
	end

--send to the console
	freeswitch.consoleLog("notice", "[app:follow_me] " .. destination_number .. "\n");

--session execute
	if (session:ready()) then
		--set the variables
			session:execute("set", "hangup_after_bridge=true");
			session:execute("set", "continue_on_fail=true");

		--execute the bridge
			if (app_data ~= nil) then
				if (follow_me_strategy == "enterprise") then
					app_data = app_data:gsub("%[", "{");
					app_data = app_data:gsub("%]", "}");
				end
				freeswitch.consoleLog("NOTICE", "[follow me] app_data: "..app_data.."\n");
				session:execute("bridge", app_data);
			end

		--timeout destination
			if (app_data ~= nil) then
				if (
					session:getVariable("originate_disposition")  == "ALLOTTED_TIMEOUT"
					or session:getVariable("originate_disposition") == "NO_ANSWER"
					or session:getVariable("originate_disposition") == "NO_USER_RESPONSE"
					or session:getVariable("originate_disposition") == "USER_NOT_REGISTERED"
					or session:getVariable("originate_disposition") == "NORMAL_TEMPORARY_FAILURE"
					or session:getVariable("originate_disposition") == "NO_ROUTE_DESTINATION"
					or session:getVariable("originate_disposition") == "USER_BUSY"
					or session:getVariable("originate_disposition") == "RECOVERY_ON_TIMER_EXPIRE"
					or session:getVariable("originate_disposition") == "failure"
				) then
					if session:ready() then
						--get the forward no answer
							cmd = "user_data ".. original_destination_number .."@"..domain_name.." var forward_no_answer_enabled";
							forward_no_answer_enabled = trim(api:executeString(cmd));

							cmd = "user_data ".. original_destination_number .."@"..domain_name.." var forward_no_answer_destination";
							forward_no_answer_destination = trim(api:executeString(cmd));

							cmd = "user_data ".. original_destination_number .."@"..domain_name.." var user_context";
							user_context = trim(api:executeString(cmd));

						--execute the time out action
							if (forward_no_answer_enabled == 'true') then
								session:transfer(forward_no_answer_destination, 'XML', user_context);
							else
								session:transfer('*99' .. original_destination_number, 'XML', user_context);
							end
					end

					--check and report missed call
						missed();
				end
			end
	end
