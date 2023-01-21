--	Part of FusionPBX
--	Copyright (C) 2010-2022 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>
--  Gill Abada <gill.abada@gmail.com>

--include the log
	log = require "resources.functions.log".ring_group

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--include functions
	require "resources.functions.trim";
	require "resources.functions.explode";
	require "resources.functions.base64";
	require "resources.functions.file_exists";
	require "resources.functions.channel_utils"
	require "resources.functions.format_ringback"
	require "resources.functions.send_presence";

--- include libs
	local route_to_bridge = require "resources.functions.route_to_bridge"
	local play_file   = require "resources.functions.play_file"
	local send_mail = require 'resources.functions.send_mail'

--define the session hangup
	function session_hangup_hook()

		--send info to the log
			--freeswitch.consoleLog("notice","[ring_groups] originate_disposition: " .. session:getVariable("originate_disposition") .. "\n");

		--status
			status = 'answered'

		--run the missed called function
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
				or session:getVariable("originate_disposition") == "ORIGINATOR_CANCEL"
				or session:getVariable("originate_disposition") == "UNALLOCATED_NUMBER"
			) then
				--set the status
					status = 'missed'
				--send missed call notification
					missed();
			end

		--send the ring group event
		    event = freeswitch.Event("CUSTOM", "RING_GROUPS");
			event:addHeader("domain_uuid", domain_uuid);
			event:addHeader("domain_name", domain_name);
			event:addHeader("ring_group_uuid", ring_group_uuid);
			event:addHeader("user_uuid", user_uuid);
			event:addHeader("ring_group_name", ring_group_name);
			event:addHeader("ring_group_extension", ring_group_extension);
			event:addHeader("status", status);
			event:addHeader("call_uuid", uuid);
			event:addHeader("caller_id_name", caller_id_name);
			event:addHeader("caller_id_number", caller_id_number);
			event:fire();

	end

--define iterator function to iterate over key/value pairs in string
	local function split_vars_pairs(str)
		local last_pos = 1
		return function()
			-- end of string
			if not str then return end

			-- handle case when there exists comma after kv pair
			local action, next_pos = string.match(str, "([^=]+=%b''),()", last_pos)
			if not action then
				action, next_pos = string.match(str, "([^=]+=[^'][^,]-),()", last_pos)
				if not action then
					action, next_pos = string.match(str, "([^=]+=),()", last_pos)
				end
			end
			if action then
				last_pos = next_pos
				return action
			end

			-- last kv pair may not have comma after it
			if last_pos < #str then
				action = string.match(str, "([^=]+=%b'')$", last_pos)
				if not action then
					action = string.match(str, "([^=]+=[^,]-)$", last_pos)
				end
				str = nil -- end of iteration
			end

			return action
		end
	end

--set the hangup hook function
	if (session:ready()) then
		session:setHangupHook("session_hangup_hook");
	end

--get the variables
	if (session:ready()) then
		session:setAutoHangup(false);
		ring_group_uuid = session:getVariable("ring_group_uuid");
		recordings_dir = session:getVariable("recordings_dir");
		sounds_dir = session:getVariable("sounds_dir");
		username = session:getVariable("username");
		dialplan = session:getVariable("dialplan");
		caller_id_name = session:getVariable("caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");
		outbound_caller_id_name = session:getVariable("outbound_caller_id_name");
		outbound_caller_id_number = session:getVariable("outbound_caller_id_number");
		effective_caller_id_name = session:getVariable("effective_caller_id_name");
		effective_caller_id_number = session:getVariable("effective_caller_id_number");
		network_addr = session:getVariable("network_addr");
		ani = session:getVariable("ani");
		aniii = session:getVariable("aniii");
		rdnis = session:getVariable("rdnis");
		destination_number = session:getVariable("destination_number");
		source = session:getVariable("source");
		uuid = session:getVariable("uuid");
		context = session:getVariable("context");
		call_direction = session:getVariable("call_direction");
		accountcode = session:getVariable("accountcode");
		local_ip_v4 = session:getVariable("local_ip_v4")
		hold_music = session:getVariable("hold_music");
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

--set ring ready
	if (session:ready()) then
		session:execute("ring_ready", "");
	end

--define additional variables
	external = "false";

--set the sounds path for the language, dialect and voice
	if (session:ready()) then
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end
	end

--get record_ext
	record_ext = session:getVariable("record_ext");
	if (not record_ext) then
		record_ext = "wav";
	end

--prepare the api object
	api = freeswitch.API();

--define the session hangup
	--function on_hangup(s,status)
	--	freeswitch.consoleLog("NOTICE","---- on_hangup: "..status.."\n");
	--	error();
	--end

--get current switchname
	hostname = trim(api:execute("hostname", ""))

--get the ring group
	ring_group_forward_enabled = '';
	ring_group_forward_destination = '';
	sql = "SELECT d.domain_name, r.* FROM v_ring_groups as r, v_domains as d ";
	sql = sql .. "where r.ring_group_uuid = :ring_group_uuid ";
	sql = sql .. "and r.domain_uuid = d.domain_uuid ";
	local params = {ring_group_uuid = ring_group_uuid};
	status = dbh:query(sql, params, function(row)
		domain_uuid = row["domain_uuid"];
		domain_name = row["domain_name"];
		ring_group_name = row["ring_group_name"];
		ring_group_extension = row["ring_group_extension"];
		ring_group_greeting = row["ring_group_greeting"];
		ring_group_forward_enabled = row["ring_group_forward_enabled"];
		ring_group_forward_destination = row["ring_group_forward_destination"];
		ring_group_forward_toll_allow = row["ring_group_forward_toll_allow"];
		ring_group_call_timeout = row["ring_group_call_timeout"];
		ring_group_caller_id_name = row["ring_group_caller_id_name"];
		ring_group_caller_id_number = row["ring_group_caller_id_number"];
		ring_group_cid_name_prefix = row["ring_group_cid_name_prefix"];
		ring_group_cid_number_prefix = row["ring_group_cid_number_prefix"];
		ring_group_call_forward_enabled = row["ring_group_call_forward_enabled"];
		ring_group_follow_me_enabled = row["ring_group_follow_me_enabled"];
		missed_call_app = row["ring_group_missed_call_app"];
		missed_call_data = row["ring_group_missed_call_data"];
	end);

--set the recording path
	record_path = recordings_dir .. "/" .. domain_name .. "/archive/" .. os.date("%Y/%b/%d");
	record_path = record_path:gsub("\\", "/");

--set the recording file name
	if (session:ready()) then
		--record_name = session:getVariable("record_name");
		--sip_from_user = session:getVariable("sip_from_user");
		--sip_to_user = session:getVariable("sip_to_user");

		--record_name = record_name:gsub("${caller_id_name}", caller_id_name);
		--record_name = record_name:gsub("${caller_id_number}", caller_id_number);
		--record_name = record_name:gsub("${sip_from_user}", sip_from_user);
		--record_name = record_name:gsub("${sip_to_user}", sip_to_user);
		--record_name = record_name:gsub("${dialed_user}", ring_group_extension);

		--if (not record_name) then
			record_name = uuid .. "." .. record_ext;
		--end
	end

---set the call_timeout to a higher value to prevent the early timeout of the ring group
	if (session:ready()) then
		if (ring_group_call_timeout and #ring_group_call_timeout == 0) then
			ring_group_call_timeout = '300';
		end
		session:setVariable("call_timeout", ring_group_call_timeout);
	end

--play the greeting
	if (session:ready()) then
		if (ring_group_greeting and #ring_group_greeting > 0) then
			session:answer();
			session:sleep(1000);
			play_file(dbh, domain_name, domain_uuid, ring_group_greeting)
			session:sleep(1000);
		end
	end

--get the ring group user
	sql = "SELECT r.*, u.user_uuid FROM v_ring_groups as r, v_ring_group_users as u ";
	sql = sql .. "where r.ring_group_uuid = :ring_group_uuid ";
	sql = sql .. "and r.ring_group_uuid = u.ring_group_uuid ";
	local params = {ring_group_uuid = ring_group_uuid};
	status = dbh:query(sql, params, function(row)
		user_uuid = row["user_uuid"];
	end);

--set the caller id
	if (session:ready()) then
		if (ring_group_cid_name_prefix ~= nil and string.len(ring_group_cid_name_prefix) > 0) then
			session:execute("export", "effective_caller_id_name="..ring_group_cid_name_prefix.."#"..caller_id_name);
		end
		if (ring_group_cid_number_prefix ~= nil and string.len(ring_group_cid_number_prefix) > 0) then
			session:execute("export", "effective_caller_id_number="..ring_group_cid_number_prefix..caller_id_number);
		end
	end

--check the missed calls
	function missed()
		--add missed call channel variable
			if (session) then
				session:setVariable("missed_call", 'true');
			end

		--send missed call email
		if (missed_call_app ~= nil and missed_call_data ~= nil) then
			if (missed_call_app == "email") then
				--prepare the email address
					mail_to = missed_call_data;

				--set the sounds path for the language, dialect and voice
					default_language = session:getVariable("default_language");
					default_dialect = session:getVariable("default_dialect");
					default_voice = session:getVariable("default_voice");
					if (not default_language) then default_language = 'en'; end
					if (not default_dialect) then default_dialect = 'us'; end
					if (not default_voice) then default_voice = 'callie'; end

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
					dbh = Database.new('system');
					dbh:query(sql, params, function(row)
						subject = row["template_subject"];
						body = row["template_body"];
					end);

				--prepare the headers
					local headers = {
						["X-FusionPBX-Domain-UUID"] = domain_uuid;
						["X-FusionPBX-Domain-Name"] = domain_name;
						["X-FusionPBX-Call-UUID"]   = uuid;
						["X-FusionPBX-Email-Type"]  = 'missed';
					}

				--remove quotes from caller id name and number
					caller_id_name = caller_id_name:gsub("'", "&#39;");
					caller_id_name = caller_id_name:gsub([["]], "&#34;");
					caller_id_number = caller_id_number:gsub("'", "&#39;");
					caller_id_number = caller_id_number:gsub([["]], "&#34;");
			
				--prepare the subject
					subject = subject:gsub("${caller_id_name}", caller_id_name);
					subject = subject:gsub("${caller_id_number}", caller_id_number);
					subject = subject:gsub("${ring_group_name}", ring_group_name);
					subject = subject:gsub("${ring_group_extension}", ring_group_extension);
					subject = subject:gsub("${sip_to_user}", ring_group_name);
					subject = subject:gsub("${dialed_user}", ring_group_extension);
					subject = trim(subject);
					subject = '=?utf-8?B?'..base64.encode(subject)..'?=';

				--prepare the body
					body = body:gsub("${caller_id_name}", caller_id_name);
					body = body:gsub("${caller_id_number}", caller_id_number);
					body = body:gsub("${ring_group_name}", ring_group_name);
					body = body:gsub("${ring_group_extension}", ring_group_extension);
					body = body:gsub("${sip_to_user}", ring_group_name);
					body = body:gsub("${dialed_user}", ring_group_extension);
					body = body:gsub(" ", "&nbsp;");
					body = body:gsub("%s+", "");
					body = body:gsub("&nbsp;", " ");
					body = body:gsub("\n", "");
					body = body:gsub("\n", "");
					body = trim(body);

				--send the email
					send_mail(headers,
						nil,
						mail_to,
						{subject, body}
					);

				--send the debug info
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[missed call]: "..mail_to.." '"..subject.."' '"..body.."'\n");
					end
			end
		end
	end

--get the destination and follow the forward
	function get_forward_all(count, destination_number, domain_name)
		cmd = "user_exists id ".. destination_number .." "..domain_name;
		--freeswitch.consoleLog("notice", "[ring groups][call forward all] " .. cmd .. "\n");
		user_exists = api:executeString(cmd);
		if (user_exists == "true") then
			---check to see if the new destination is forwarded - third forward
				cmd = "user_data ".. destination_number .."@" ..domain_name.." var forward_all_enabled";
				if (api:executeString(cmd) == "true") then
					--get the toll_allow var
						cmd = "user_data ".. destination_number .."@" ..leg_domain_name.." var toll_allow";
						toll_allow = api:executeString(cmd);
						--freeswitch.consoleLog("notice", "[ring groups][call forward all] " .. destination_number .. " toll_allow is ".. toll_allow .."\n");

					--get the new destination - third foward
						cmd = "user_data ".. destination_number .."@" ..domain_name.." var forward_all_destination";
						destination_number = api:executeString(cmd);
						--freeswitch.consoleLog("notice", "[ring groups][call forward all] " .. count .. " " .. cmd .. " ".. destination_number .."\n");
						count = count + 1;
						if (count < 3) then
							count, destination_number = get_forward_all(count, destination_number, domain_name);
						end
				end
		end
		return count, destination_number, toll_allow;
	end

--process the ring group
	if (ring_group_forward_enabled == "true" and string.len(ring_group_forward_destination) > 0) then

		--set the outbound caller id
			if (caller_is_local == 'true' and outbound_caller_id_name ~= nil) then
				caller_id_name = outbound_caller_id_name;
			end
			if (caller_is_local == 'true' and outbound_caller_id_number ~= nil) then
				caller_id_number = outbound_caller_id_number;
			end
			if (ring_group_caller_id_name ~= nil and ring_group_caller_id_name ~= '') then
				caller_id_name = ring_group_caller_id_name;
			end
			if (ring_group_caller_id_number ~= nil and ring_group_caller_id_number ~= '') then
				caller_id_number = ring_group_caller_id_number;
			end

		--forward the ring group
			if (caller_id_name) then
				session:setVariable("effective_caller_id_name", caller_id_name);
				session:setVariable("outbound_caller_id_name", caller_id_name);
			end
			if (caller_id_number) then
				session:setVariable("effective_caller_id_number", caller_id_number);
				session:setVariable("outbound_caller_id_number", caller_id_number);
			end
			if (ring_group_forward_toll_allow) then
				session:setVariable("toll_allow", ring_group_forward_toll_allow);
			end
			session:execute("transfer", ring_group_forward_destination.." XML "..context);
	else
		--get the strategy of the ring group, if random, we use random() to order the destinations
			local sql = [[
				SELECT
					r.ring_group_strategy
				FROM
					v_ring_groups as r
				WHERE
					ring_group_uuid = :ring_group_uuid
					AND r.domain_uuid = :domain_uuid
					AND r.ring_group_enabled = 'true'
			]];

			local params = {ring_group_uuid = ring_group_uuid, domain_uuid = domain_uuid};

			dbh:query(sql, params, function(row)
				if (row.ring_group_strategy == "random") then
					if (database["type"] == "mysql") then
						sql_order = 'rand()'
					else
						sql_order = 'random() * 1000000' --both postgresql and sqlite uses random() instead of rand()
					end
				else
					sql_order='d.destination_delay, d.destination_number asc'
				end
			end);

		--get the ring group destinations
			sql = [[
				SELECT
					r.ring_group_strategy, r.ring_group_timeout_app, r.ring_group_distinctive_ring,
					d.destination_number, d.destination_delay, d.destination_timeout, d.destination_prompt,
					r.ring_group_caller_id_name, r.ring_group_caller_id_number, 
					r.ring_group_cid_name_prefix, r.ring_group_cid_number_prefix, 
					r.ring_group_timeout_data, r.ring_group_ringback
				FROM
					v_ring_groups as r, v_ring_group_destinations as d
				WHERE
					d.ring_group_uuid = r.ring_group_uuid
					AND d.ring_group_uuid = :ring_group_uuid
					AND r.domain_uuid = :domain_uuid
					AND r.ring_group_enabled = 'true'
					AND d.destination_enabled = 'true'
				ORDER BY
					]]..sql_order..[[
			]];
			if debug["sql"] then
				freeswitch.consoleLog("notice", "[ring group] SQL:" .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			destinations = {};
			x = 1;
			dbh:query(sql, params, function(row)
				if (row.destination_prompt == "1" or row.destination_prompt == "2") then
					prompt = "true";
				end

				local array = explode("@",row.destination_number);
				if (array[2] == nil) then
					-- no @
					leg_domain_name = domain_name;
				else
					leg_domain_name = array[2];
				end

				--follow the forwards
				if (ring_group_call_forward_enabled == "true") then
					count, destination_number, toll_allow = get_forward_all(0, row.destination_number, leg_domain_name);
				else
					destination_number = row.destination_number;
				end

				--update values
				row['destination_number'] = destination_number
				row['toll_allow'] = toll_allow;

				--check if the user exists
				cmd = "user_exists id ".. destination_number .." "..domain_name;
				user_exists = api:executeString(cmd);

				--cmd = "user_exists id ".. destination_number .." "..leg_domain_name;
				if (user_exists == "true") then
					--add user_exists true or false to the row array
						row['user_exists'] = "true";
					--handle do_not_disturb
						cmd = "user_data ".. destination_number .."@" ..leg_domain_name.." var do_not_disturb";
						if (api:executeString(cmd) ~= "true") then
							--add the row to the destinations array
							destinations[x] = row;
						end
				else
					--set the values
						external = "true";
						row['user_exists'] = "false";
					--add the row to the destinations array
						destinations[x] = row;
				end
				row['domain_name'] = leg_domain_name;
				x = x + 1;
			end);
			--freeswitch.consoleLog("NOTICE", "[ring_group] external "..external.."\n");

		--get the dialplan data and save it to a table
			if (external == "true") then
				dialplans = route_to_bridge.preload_dialplan(
					dbh, domain_uuid, {hostname = hostname, context = context}
				)
			end

		---add follow me destinations
			for key, row in pairs(destinations) do

				if (ring_group_follow_me_enabled == "true") then
					cmd = "user_data ".. row.destination_number .."@" ..row.domain_name.." var follow_me_enabled";
					if (api:executeString(cmd) == "true") then

						--set the default value to null
						follow_me_uuid = nil;

						--select data from the database
						local sql = "select follow_me_uuid, toll_allow ";
						sql = sql .. "from v_extensions ";
						sql = sql .. "where domain_uuid = :domain_uuid ";
						sql = sql .. "and ( ";
						sql = sql .. "	extension = :destination_number ";
						sql = sql .. "	OR number_alias = :destination_number ";
						sql = sql .. ") ";
						local params = {domain_uuid = domain_uuid, destination_number = row.destination_number};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
						end
						status = dbh:query(sql, params, function(field)
							follow_me_uuid = field["follow_me_uuid"];
							toll_allow = field["toll_allow"];
						end);
						--dbh:query(sql, params, function(row);

						--get the follow me destinations
						if (follow_me_uuid ~= nil and row.is_follow_me_destination ~= "true") then
							sql = "select d.domain_uuid, d.domain_name, f.follow_me_destination as destination_number, ";
							sql = sql .. "f.follow_me_delay as destination_delay, f.follow_me_timeout as destination_timeout, ";
							sql = sql .. "f.follow_me_prompt as destination_prompt ";
							sql = sql .. "from v_follow_me_destinations as f, v_domains as d ";
							sql = sql .. "where f.follow_me_uuid = :follow_me_uuid ";
							sql = sql .. "and f.domain_uuid = d.domain_uuid ";
							sql = sql .. "order by f.follow_me_order; ";
							local params = {follow_me_uuid = follow_me_uuid};
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
							end
							x = 1;
							dbh:query(sql, params, function(field)
								--check if the user exists
								cmd = "user_exists id ".. field.destination_number .." "..row.domain_name;
								user_exists = api:executeString(cmd);

								--prepare the key
								if (x == 1) then
									new_key = key;
								else
									new_key = #destinations + 1;
								end

								--Calculate the destination_timeout for follow-me destinations.
								--The call should honor ring group timeouts with rg delays, follow-me timeouts and follow-me delays factored in.
								--Destinations with a timeout of 0 or negative numbers should be ignored.
								if (tonumber(field.destination_timeout) < (tonumber(row.destination_timeout) - tonumber(field.destination_delay))) then
									new_destination_timeout = field.destination_timeout;
								else
									new_destination_timeout = row.destination_timeout - field.destination_delay;
								end

								--add to the destinations array
								destinations[new_key] = {}
								destinations[new_key]['ring_group_strategy'] = row.ring_group_strategy;
								destinations[new_key]['ring_group_timeout_app'] = row.ring_group_timeout_app;
								destinations[new_key]['ring_group_timeout_data'] = row.ring_group_timeout_data;
								destinations[new_key]['ring_group_caller_id_name'] = row.ring_group_caller_id_name;
								destinations[new_key]['ring_group_caller_id_number'] = row.ring_group_caller_id_number;
								destinations[new_key]['ring_group_cid_name_prefix'] = row.ring_group_cid_name_prefix;
								destinations[new_key]['ring_group_cid_number_prefix'] = row.ring_group_cid_number_prefix;
								destinations[new_key]['ring_group_distinctive_ring'] = row.ring_group_distinctive_ring;
								destinations[new_key]['ring_group_ringback'] = row.ring_group_ringback;
								destinations[new_key]['domain_name'] = field.domain_name;
								destinations[new_key]['destination_number'] = field.destination_number;
								destinations[new_key]['destination_delay'] = field.destination_delay + row.destination_delay;
								destinations[new_key]['destination_timeout'] = new_destination_timeout;
								destinations[new_key]['destination_prompt'] = field.destination_prompt;
								destinations[new_key]['group_confirm_key'] = row.group_confirm_key;
								destinations[new_key]['group_confirm_file'] = row.group_confirm_file;
								destinations[new_key]['toll_allow'] = toll_allow;
								destinations[new_key]['user_exists'] = user_exists;
								destinations[new_key]['is_follow_me_destination'] = "true";

								--increment x
								x = x + 1;
							end);
						end

					end
				end
			end

		--prepare the array of destinations
			for key, row in pairs(destinations) do
				--determine if the user is registered if not registered then lookup
				if (row.user_exists == "true") then
					cmd = "sofia_contact */".. row.destination_number .."@" ..domain_name;
					if (api:executeString(cmd) == "error/user_not_registered") then
						freeswitch.consoleLog("NOTICE", "[ring_group] "..cmd.."\n");
						cmd = "user_data ".. row.destination_number .."@" ..domain_name.." var forward_user_not_registered_enabled";
						freeswitch.consoleLog("NOTICE", "[ring_group] "..cmd.."\n");
						if (api:executeString(cmd) == "true") then
							--get the new destination number
							cmd = "user_data ".. row.destination_number .."@" ..domain_name.." var forward_user_not_registered_destination";
							freeswitch.consoleLog("NOTICE", "[ring_group] "..cmd.."\n");
							not_registered_destination_number = api:executeString(cmd);
							freeswitch.consoleLog("NOTICE", "[ring_group] "..not_registered_destination_number.."\n");
							if (not_registered_destination_number ~= nil) then
								destination_number = not_registered_destination_number;
								destinations[key]['destination_number'] = destination_number;
							end
						end
					end
				end
			end

		--add the array to the logs
			for key, row in pairs(destinations) do
				freeswitch.consoleLog("NOTICE", "[ring group] domain_name: "..row.domain_name.."\n");
				freeswitch.consoleLog("NOTICE", "[ring group] destination_number: "..row.destination_number.."\n");
				freeswitch.consoleLog("NOTICE", "[ring group] destination_delay: "..row.destination_delay.."\n");
				freeswitch.consoleLog("NOTICE", "[ring group] destination_timeout: "..row.destination_timeout.."\n");
				freeswitch.consoleLog("NOTICE", "[ring group] destination_prompt: "..row.destination_prompt.."\n");
			end

		--process the destinations
			x = 1;
			for key, row in pairs(destinations) do
				if (tonumber(row.destination_timeout) > 0) then
					--set the values from the database as variables
						ring_group_strategy = row.ring_group_strategy;
						ring_group_timeout_app = row.ring_group_timeout_app;
						ring_group_timeout_data = row.ring_group_timeout_data;
						ring_group_caller_id_name = row.ring_group_caller_id_name;
						ring_group_caller_id_number = row.ring_group_caller_id_number;
						ring_group_cid_name_prefix = row.ring_group_cid_name_prefix;
						ring_group_cid_number_prefix = row.ring_group_cid_number_prefix;
						ring_group_distinctive_ring = row.ring_group_distinctive_ring;
						ring_group_ringback = row.ring_group_ringback;
						destination_number = row.destination_number;
						destination_delay = row.destination_delay;
						destination_timeout = row.destination_timeout;
						destination_prompt = row.destination_prompt;
						group_confirm_key = row.group_confirm_key;
						group_confirm_file = row.group_confirm_file;
						toll_allow = row.toll_allow;
						user_exists = row.user_exists;

					--follow the forwards
						if (row.ring_group_call_forward_enabled == "true") then
							count, destination_number = get_forward_all(0, destination_number, leg_domain_name);
						end

					--check if the user exists
						cmd = "user_exists id ".. destination_number .." "..domain_name;
						user_exists = api:executeString(cmd);

					--set ringback
						ring_group_ringback = format_ringback(ring_group_ringback);
						session:setVariable("ringback", ring_group_ringback);
						session:setVariable("transfer_ringback", ring_group_ringback);

					--set the timeout if there is only one destination
						if (#destinations == 1) then
							session:execute("set", "call_timeout="..row.destination_timeout);
						end

					--setup the delimiter
						delimiter = ",";
						if (ring_group_strategy == "rollover") then
							delimiter = "|";
						end
						if (ring_group_strategy == "sequence") then
							delimiter = "|";
						end
						if (ring_group_strategy == "random") then
							delimiter = "|";
						end
						if (ring_group_strategy == "simultaneous") then
							delimiter = ",";
						end
						if (ring_group_strategy == "enterprise") then
							delimiter = ":_:";
						end

					--leg delay settings
						if (ring_group_strategy == "enterprise") then
							delay_name = "originate_delay_start";
							destination_delay = destination_delay * 500;
						else
							delay_name = "leg_delay_start";
						end

					--export the ringback
						if (ring_group_distinctive_ring and #ring_group_distinctive_ring > 0) then
							if (local_ip_v4 ~= nil) then
								ring_group_distinctive_ring = ring_group_distinctive_ring:gsub("${local_ip_v4}", local_ip_v4);
							end
							if (domain_name ~= nil) then
								ring_group_distinctive_ring = ring_group_distinctive_ring:gsub("${domain_name}", domain_name);
							end
							session:execute("export", "sip_h_Alert-Info="..ring_group_distinctive_ring);
						end

					--set confirm
						if (ring_group_strategy == "simultaneous"
							or ring_group_strategy == "sequence"
							or ring_group_strategy == "rollover"
							or ring_group_strategy == "random") then
								session:execute("set", "group_confirm_key=exec");
								session:execute("set", "group_confirm_file=lua ".. scripts_dir:gsub('\\','/') .."/confirm.lua");
						end

					--determine confirm prompt
						if (destination_prompt == nil) then
							group_confirm = "confirm=false,";
						elseif (destination_prompt == "1") then
							group_confirm = "group_confirm_key=exec,group_confirm_file=lua ".. scripts_dir:gsub('\\','/') .."/confirm.lua,confirm=true,";
						elseif (destination_prompt == "2") then
							group_confirm = "group_confirm_key=exec,group_confirm_file=lua ".. scripts_dir:gsub('\\','/') .."/confirm.lua,confirm=true,";
						else
							group_confirm = "confirm=false,";
						end

					--get user_record value and determine whether to record the session
						cmd = "user_data ".. destination_number .."@"..domain_name.." var user_record";
						user_record = trim(api:executeString(cmd));
						--set the record_session variable
						record_session = false;
						if (user_record == "all") then
							record_session = true;
						end
						if (user_record == "inbound" and call_direction == "inbound") then
							record_session = true;
						end
						if (user_record == "outbound" and call_direction == "outbound") then
							record_session = true;
						end
						if (user_record == "local" and call_direction == "local") then
							record_session = true;
						end

					--record the session
						if (record_session) then
							record_session = ",api_on_answer='uuid_record "..uuid.." start ".. record_path .. "/" .. record_name .. "',record_path='".. record_path .."',record_name="..record_name;
						else
							record_session = ""
						end
						row.record_session = record_session

					--process according to user_exists, sip_uri, external number
						if (user_exists == "true") then
							--get the extension_uuid
							cmd = "user_data ".. destination_number .."@"..domain_name.." var extension_uuid";
							extension_uuid = trim(api:executeString(cmd));

							--set hold music
							if (hold_music == nil) then
								hold_music = '';
							else
								hold_music = ",hold_music="..hold_music;
							end

							--send to user
							local dial_string_to_user = "[sip_invite_domain="..domain_name..",domain_name="..domain_name..",call_direction="..call_direction..","..group_confirm.."leg_timeout="..destination_timeout..","..delay_name.."="..destination_delay..",dialed_extension=" .. row.destination_number .. ",extension_uuid=".. extension_uuid .. row.record_session .. hold_music .."]user/" .. row.destination_number .. "@" .. domain_name;
							dial_string = dial_string_to_user;
						elseif (tonumber(destination_number) == nil) then
							--sip uri
							dial_string = "[sip_invite_domain="..domain_name..",domain_name="..domain_name..",call_direction="..call_direction..","..group_confirm.."leg_timeout="..destination_timeout..","..delay_name.."="..destination_delay.."]" .. row.destination_number;
						else
							--external number
								-- have to double destination_delay here due a FS bug requiring a 50% delay value for internal externsions, but not external calls. 
								destination_delay = destination_delay * 2;

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

							--set the caller id
								caller_id = '';

							--set the outbound caller id
								if (caller_is_local == 'true' and outbound_caller_id_name ~= nil) then
									caller_id = "origination_caller_id_name='"..outbound_caller_id_name.."'";
								end
								if (caller_is_local == 'true' and outbound_caller_id_number ~= nil) then
									caller_id = caller_id .. ",origination_caller_id_number='"..outbound_caller_id_number.."'";
								end
								if (ring_group_caller_id_name ~= nil and ring_group_caller_id_name ~= '') then
									caller_id = "origination_caller_id_name='"..ring_group_caller_id_name.."'";
								end
								if (ring_group_caller_id_number ~= nil and ring_group_caller_id_number ~= '') then
									caller_id = caller_id .. ",origination_caller_id_number="..ring_group_caller_id_number..",";
								end

							--set the destination dial string
								dial_string = "[toll_allow=".. toll_allow ..",".. caller_id ..",sip_invite_domain="..domain_name..",domain_name="..domain_name..",domain_uuid="..domain_uuid..",call_direction="..call_direction..","..group_confirm.."leg_timeout="..destination_timeout..","..delay_name.."="..destination_delay.."]"..route_bridge
						end

					--add a delimiter between destinations
						if (dial_string ~= nil) then
							--freeswitch.consoleLog("notice", "[ring group] dial_string: " .. dial_string .. "\n");
							if (x == 1) then
								if (ring_group_strategy == "enterprise") then
									app_data = dial_string;
								else
									app_data = "{ignore_early_media=true}"..dial_string;
								end
							else
								if (app_data == nil) then
									if (ring_group_strategy == "enterprise") then
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
			end

		--release dbh before bridge
				dbh:release();
				
		--session execute
			if (session:ready()) then
				--set the variables
					session:execute("set", "hangup_after_bridge=true");
					session:execute("set", "continue_on_fail=true");

				-- support conf-xfer feature
					-- do
					-- 	local uuid = api:executeString("create_uuid")
					-- 	session:execute("export", "conf_xfer_number=xfer-" .. uuid .. "-" .. domain_name)
					-- end

				--set bind digit action
					local bind_target = 'local'
					if session:getVariable("sip_authorized") == "true" then
						bind_target = 'peer';
					end
					local bindings = {
						"local,*2,exec:record_session," .. record_path .. "/" .. record_name,
						"local,*5,api:uuid_record," .. uuid .. " mask " .. record_path .. "/" .. record_name,
						"local,*6,api:uuid_record," .. uuid .. " unmask " .. record_path .. "/" .. record_name,
						-- "local,*0,exec:execute_extension,conf_xfer_from_dialplan XML conf-xfer@" .. context
					}
					for _, str in ipairs(bindings) do
						session:execute("bind_digit_action", str .. "," .. bind_target)
					end
					session:execute("digit_action_set_realm", "local");

				--if the user is busy rollover to the next destination
					if (ring_group_strategy == "rollover") then
						timeout = 0;
						x = 0;
						for key, row in pairs(destinations) do

							--set the app data
								app_data = '{ignore_early_media=true}';

							--set the values from the database as variables
								user_exists = row.user_exists;
								destination_number = row.destination_number;
								domain_name = row.domain_name;
								destination_prompt = row.destination_prompt;

							--determine confirm prompt
								if (destination_prompt == nil) then
									group_confirm = "confirm=false,";
								elseif (destination_prompt == "1") then
									group_confirm = "group_confirm_key=exec,group_confirm_file=lua ".. scripts_dir:gsub('\\','/') .."/confirm.lua,confirm=true,";
								elseif (destination_prompt == "2") then
									group_confirm = "group_confirm_key=exec,group_confirm_file=lua ".. scripts_dir:gsub('\\','/') .."/confirm.lua,confirm=true,";
								else
									group_confirm = "confirm=false,";
								end

							--if the timeout was reached exit the loop and go to the timeout action
								if (tonumber(ring_group_call_timeout) == timeout) then
									break;
								end

							--send the call to the destination
								if (user_exists == "true") then
									dial_string = "["..group_confirm.."sip_invite_domain="..domain_name..",originate_timeout="..destination_timeout..",call_direction="..call_direction..",dialed_extension=" .. destination_number .. ",domain_name="..domain_name..",domain_uuid="..domain_uuid..row.record_session.."]user/" .. destination_number .. "@" .. domain_name;
								elseif (tonumber(destination_number) == nil) then
									dial_string = "["..group_confirm.."sip_invite_domain="..domain_name..",originate_timeout="..destination_timeout..",call_direction=outbound,domain_name="..domain_name..",domain_uuid="..domain_uuid.."]" .. destination_number;
								else
									dial_string = "["..group_confirm.."sip_invite_domain="..domain_name..",originate_timeout="..destination_timeout..",domain_name="..domain_name..",domain_uuid="..domain_uuid..",call_direction=outbound]loopback/" .. destination_number;
								end

							--add the delimiter
								app_data = app_data .. dial_string;
								freeswitch.consoleLog("NOTICE", "[ring group] app_data: "..app_data.."\n");
								session:execute("bridge", app_data);

								if (session:getVariable("originate_disposition") == "NO_ANSWER" ) then
									timeout = timeout + destination_timeout;
								end

							--increment the value of x
								x = x + 1;
						end
					end

				--execute the bridge
					if (app_data ~= nil) then
						if (ring_group_strategy == "enterprise") then
							app_data = app_data:gsub("%[", "{");
							app_data = app_data:gsub("%]", "}");
						end
						freeswitch.consoleLog("NOTICE", "[ring group] app_data: "..app_data.."\n");
						-- log.noticef("bridge begin: originate_disposition:%s answered:%s ready:%s bridged:%s", session:getVariable("originate_disposition"), session:answered() and "true" or "false", session:ready() and "true" or "false", session:bridged() and "true" or "false")
						if (ring_group_strategy ~= "rollover") then
							if (session:getVariable("ring_group_send_presence") == "true") then
								session:setVariable("presence_id", ring_group_extension.."@"..domain_name);
								send_presence(uuid, ring_group_extension.."@"..domain_name, "early");
							end
							
							session:execute("bridge", app_data);
							
							--set the presence to terminated and unset presence_id
							if (session:getVariable("ring_group_send_presence") == "true") then
								session:setVariable("presence_id", "");
								send_presence(uuid, ring_group_extension.."@"..domain_name, "terminated");
							end
						end
						-- log.noticef("bridge done: originate_disposition:%s answered:%s ready:%s bridged:%s", session:getVariable("originate_disposition"), session:answered() and "true" or "false", session:ready() and "true" or "false", session:bridged() and "true" or "false")
					end

				--timeout destination
					if (app_data ~= nil) then
						if session:ready() and (
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
							--execute the time out action
								if ring_group_timeout_app and #ring_group_timeout_app > 0 then
									session:execute(ring_group_timeout_app, ring_group_timeout_data);
								end
							--check and report missed call
								missed();
						end
					else
						if (ring_group_timeout_app ~= nil) then
							--execute the time out action
								if ring_group_timeout_app and #ring_group_timeout_app > 0 then
									session:execute(ring_group_timeout_app, ring_group_timeout_data);
								end
						else
							dbh = Database.new('system');
							local sql = "SELECT ring_group_timeout_app, ring_group_timeout_data FROM v_ring_groups ";
							sql = sql .. "where ring_group_uuid = :ring_group_uuid";
							local params = {ring_group_uuid = ring_group_uuid};
							if debug["sql"] then
								freeswitch.consoleLog("notice", "[ring group] SQL:" .. sql .. "; params:" .. json.encode(params) .. "\n");
							end
							dbh:query(sql, params, function(row)
								--execute the time out action
									if row.ring_group_timeout_app and #row.ring_group_timeout_app > 0 then
										session:execute(row.ring_group_timeout_app, row.ring_group_timeout_data);
									end
							end);
						end
					end
			end

	end

--actions
	--ACTIONS = {}
	--table.insert(ACTIONS, {"set", "hangup_after_bridge=true"});
	--table.insert(ACTIONS, {"set", "continue_on_fail=true"});
	--table.insert(ACTIONS, {"bridge", app_data});
	--table.insert(ACTIONS, {ring_group_timeout_app, ring_group_timeout_data});
