--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013 - 2015 Mark J Crane <markjcrane@fusionpbx.com>
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
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
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
--	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx> 

--set the default
	continue = true;

--get the action
	action = params:getHeader("action");
	purpose = params:getHeader("purpose");
	--sip_auth - registration
	--group_call - call group has been called
	--user_call - user has been called

--additional information
	--event_calling_function = params:getHeader("Event-Calling-Function");

--set the variables as a string
	number_alias = "";
	number_alias_string = "";
	vm_mailto = "";

--determine the correction action to perform
	if (purpose == "gateways") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/domains.lua");
	elseif (action == "message-count") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/message-count.lua");
	elseif (action == "group_call") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/group_call.lua");
	elseif (action == "reverse-auth-lookup") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/reverse-auth-lookup.lua");
	elseif (params:getHeader("Event-Calling-Function") == "switch_xml_locate_domain") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/domains.lua");
	else
		--handle action
			--all other directory actions: sip_auth, user_call 
			--except for the action: group_call

			if (user == nil) then
				user = "";
			end

		--get the cache
			if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
				if (domain_name) then
					XML_STRING = trim(api:execute("memcache", "get directory:" .. user .. "@" .. domain_name));
				end
				if (XML_STRING == "-ERR NOT FOUND") or (XML_STRING == "-ERR CONNECTION FAILURE") then
					source = "database";
					continue = true;
				else
					source = "cache";
					continue = true;
				end
			else
				XML_STRING = "";
				source = "database";
				continue = true;
			end

		--prevent processing for invalid user
			if (user == "*97") then
				source = "";
				continue = false;
			end

		--show the params in the console
			--if (params:serialize() ~= nil) then
			--	freeswitch.consoleLog("notice", "[xml_handler-directory.lua] Params:\n" .. params:serialize() .. "\n");
			--end

		--set the variable from the params
			dialed_extension = params:getHeader("dialed_extension");
			if (dialed_extension == nil) then
				--freeswitch.consoleLog("notice", "[xml_handler-directory.lua] dialed_extension is null\n");
				load_balancing = false;
			else
				--freeswitch.consoleLog("notice", "[xml_handler-directory.lua] dialed_extension is " .. dialed_extension .. "\n");
			end

		--build the XML string from the database
			if (source == "database") or (load_balancing) then
				--database connection
					if (continue) then
						--connect to the database
							require "resources.functions.database_handle";
							dbh = database_handle('system');

						--exits the script if we didn't connect properly
							assert(dbh:connected());

						--get the domain_uuid
							if (domain_uuid == nil) then
								--get the domain_uuid
									if (domain_name ~= nil) then
										sql = "SELECT domain_uuid FROM v_domains ";
										sql = sql .. "WHERE domain_name = '" .. domain_name .."' ";
										if (debug["sql"]) then
											freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
										end
										status = dbh:query(sql, function(rows)
											domain_uuid = rows["domain_uuid"];
										end);
									end
							end
					end

				--prevent processing for invalid domains
					if (domain_uuid == nil) then
						continue = false;
					end

				--if load balancing is set to true then get the hostname
					if (continue) then
						if (load_balancing) then

							--get the domain_name from domains
								if (domain_name == nil) then
									sql = "SELECT domain_name FROM v_domains ";
									sql = sql .. "WHERE domain_uuid = '" .. domain_uuid .. "' ";
									status = dbh:query(sql, function(row)
										domain_name = row["domain_name"];
									end);
								end

							--get the caller hostname
								local_hostname = trim(api:execute("hostname", ""));
								--freeswitch.consoleLog("notice", "[xml_handler-directory.lua] local_hostname is " .. local_hostname .. "\n");

							--add the file_exists function
								require "resources.functions.file_exists";

							--connect to the switch database
								if (file_exists(database_dir.."/core.db")) then
									--dbh_switch = freeswitch.Dbh("core:core"); -- when using sqlite
									dbh_switch = freeswitch.Dbh("sqlite://"..database_dir.."/core.db");
								else
									require "resources.functions.database_handle";
									dbh_switch = database_handle('switch');
								end

							--get the destination hostname from the registration
								sql = "SELECT hostname FROM registrations ";
								sql = sql .. "WHERE reg_user = '"..dialed_extension.."' ";
								sql = sql .. "AND realm = '"..domain_name.."' ";
								if (database["type"] == "mysql") then
									now = os.time();
									sql = sql .. "AND expires > "..now;
								else
									sql = sql .. "AND to_timestamp(expires) > NOW()";
								end
								status = dbh_switch:query(sql, function(row)
									database_hostname = row["hostname"];
								end);
								--freeswitch.consoleLog("notice", "[xml_handler] sql: " .. sql .. "\n");
								--freeswitch.consoleLog("notice", "[xml_handler-directory.lua] database_hostname is " .. database_hostname .. "\n");

							--hostname was not found set load_balancing to false to prevent a database_hostname concatenation error
								if (database_hostname == nil) then
									load_balancing = false;
								end

							--close the database connection
								dbh_switch:release();
						end
					end

				--get the extension from the database
					if (continue) then
						sql = "SELECT * FROM v_extensions WHERE domain_uuid = '" .. domain_uuid .. "' and (extension = '" .. user .. "' or number_alias = '" .. user .. "') and enabled = 'true' ";
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
						end
						dbh:query(sql, function(row)
							--general
								domain_uuid = row.domain_uuid;
								extension_uuid = row.extension_uuid;
								extension = row.extension;
								cidr = "";
								if (string.len(row.cidr) > 0) then
									cidr = [[ cidr="]] .. row.cidr .. [["]];
								end
								if (string.len(row.number_alias) > 0) then
									number_alias = row.number_alias;
									number_alias_string = [[ number-alias="]] .. row.number_alias .. [["]];
								end
							--params
								password = row.password;
								mwi_account = row.mwi_account;
								auth_acl = row.auth_acl;
							--variables
								sip_from_user = row.extension;
								sip_from_number = (#number_alias > 0) and number_alias or row.extension;
								call_group = row.call_group;
								call_screen_enabled = row.call_screen_enabled;
								user_record = row.user_record;
								hold_music = row.hold_music;
								toll_allow = row.toll_allow;
								accountcode = row.accountcode;
								user_context = row.user_context;
								effective_caller_id_name = row.effective_caller_id_name;
								effective_caller_id_number = row.effective_caller_id_number;
								outbound_caller_id_name = row.outbound_caller_id_name;
								outbound_caller_id_number = row.outbound_caller_id_number;
								emergency_caller_id_name = row.emergency_caller_id_name;
								emergency_caller_id_number = row.emergency_caller_id_number;
								missed_call_app = row.missed_call_app;
								missed_call_data = row.missed_call_data;
								directory_full_name = row.directory_full_name;
								directory_visible = row.directory_visible;
								directory_exten_visible = row.directory_exten_visible;
								limit_max = row.limit_max;
								call_timeout = row.call_timeout;
								limit_destination = row.limit_destination;
								sip_force_contact = row.sip_force_contact;
								sip_force_expires = row.sip_force_expires;
								nibble_account = row.nibble_account;
								sip_bypass_media = row.sip_bypass_media;
								forward_all_enabled = row.forward_all_enabled;
								forward_all_destination = row.forward_all_destination;
								forward_busy_enabled = row.forward_busy_enabled;
								forward_busy_destination = row.forward_busy_destination;
								forward_no_answer_enabled = row.forward_no_answer_enabled;
								forward_no_answer_destination = row.forward_no_answer_destination;
								do_not_disturb = row.do_not_disturb;

							--set the dial_string
								if (string.len(row.dial_string) > 0) then
									dial_string = row.dial_string;
								else
									--set a default dial string
										if (dial_string == null) then
											local username = (#number_alias > 0) and number_alias or extension
											dial_string = "{sip_invite_domain=" .. domain_name .. ",presence_id=" .. user .. "@" .. domain_name .. "}${sofia_contact(" .. username .. "@" .. domain_name .. ")}";
										end
									--set the an alternative dial string if the hostnames don't match
										if (load_balancing) then
											if (local_hostname == database_hostname) then
												freeswitch.consoleLog("notice", "[xml_handler-directory.lua] local_host and database_host are the same\n");
											else
												--sofia/internal/${user_data(${destination_number}@${domain_name} attr id)}@${domain_name};fs_path=sip:server
												user_id = trim(api:execute("user_data", user .. "@" .. domain_name .. " attr id"));
												dial_string = "{sip_invite_domain=" .. domain_name .. ",presence_id=" .. user .. "@" .. domain_name .. "}sofia/internal/" .. user_id .. "@" .. domain_name .. ";fs_path=sip:" .. database_hostname;
												--freeswitch.consoleLog("notice", "[xml_handler-directory.lua] dial_string " .. dial_string .. "\n");
											end
										else
											--freeswitch.consoleLog("notice", "[xml_handler-directory.lua] seems balancing is false??" .. tostring(load_balancing) .. "\n");
										end

									--show debug informationa
										--if (load_balancing) then
										--	freeswitch.consoleLog("notice", "[xml_handler] local_hostname: " .. local_hostname.. " database_hostname: " .. database_hostname .. " dial_string: " .. dial_string .. "\n");
										--end
								end
						end);
					end

				--get the voicemail from the database
					if (continue) then
						vm_enabled = "true";
						if number_alias and #number_alias > 0 then
							sql = "SELECT * FROM v_voicemails WHERE domain_uuid = '" .. domain_uuid .. "' and voicemail_id = '" .. number_alias .. "' ";
						else
							sql = "SELECT * FROM v_voicemails WHERE domain_uuid = '" .. domain_uuid .. "' and voicemail_id = '" .. user .. "' ";
						end
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
						end
						dbh:query(sql, function(row)
							if (string.len(row.voicemail_enabled) > 0) then
								vm_enabled = row.voicemail_enabled;
							end
							vm_password = row.voicemail_password;
							vm_attach_file = "true";
							if (string.len(row.voicemail_attach_file) > 0) then
								vm_attach_file = row.voicemail_attach_file;
							end
							vm_keep_local_after_email = "true";
							if (string.len(row.voicemail_local_after_email) > 0) then
								vm_keep_local_after_email = row.voicemail_local_after_email;
							end
							if (string.len(row.voicemail_mail_to) > 0) then
								vm_mailto = row.voicemail_mail_to;
							else
								vm_mailto = "";
							end
						end);
					end

				--if the extension does not exist set continue to false;
					if (extension_uuid == nil) then
						continue = false;
					end

				--set the xml array and then concatenate the array to a string
					if (continue and password) then
						--build the xml
							local xml = {}
							table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
							table.insert(xml, [[<document type="freeswitch/xml">]]);
							table.insert(xml, [[	<section name="directory">]]);
							table.insert(xml, [[		<domain name="]] .. domain_name .. [[" alias="true">]]);
							table.insert(xml, [[            <params>]]);
							table.insert(xml, [[                    <param name="jsonrpc-allowed-methods" value="verto"/>]]);
							table.insert(xml, [[                    <param name="jsonrpc-allowed-event-channels" value="demo,conference,presence"/>]]);
							table.insert(xml, [[            </params>]]);
							table.insert(xml, [[			<groups>]]);
							table.insert(xml, [[				<group name="default">]]);
							table.insert(xml, [[					<users>]]);
							if (number_alias) then
								if (cidr) then
									table.insert(xml, [[						<user id="]] .. extension .. [["]] .. cidr .. number_alias_string .. [[ type=>]]);
								else
									table.insert(xml, [[						<user id="]] .. extension .. [["]] .. number_alias_string .. [[>]]);
								end
							else
								if (cidr) then
									table.insert(xml, [[						<user id="]] .. extension .. [["]] .. cidr .. [[>]]);
								else
									table.insert(xml, [[						<user id="]] .. extension .. [[">]]);
								end
							end
							table.insert(xml, [[							<params>]]);
							table.insert(xml, [[								<param name="password" value="]] .. password .. [["/>]]);
							table.insert(xml, [[								<param name="vm-enabled" value="]] .. vm_enabled .. [["/>]]);
							if (string.len(vm_mailto) > 0) then
								table.insert(xml, [[								<param name="vm-password" value="]] .. vm_password  .. [["/>]]);
								table.insert(xml, [[								<param name="vm-email-all-messages" value="]] .. vm_enabled  ..[["/>]]);
								table.insert(xml, [[								<param name="vm-attach-file" value="]] .. vm_attach_file .. [["/>]]);
								table.insert(xml, [[								<param name="vm-keep-local-after-email" value="]] .. vm_keep_local_after_email .. [["/>]]);
								table.insert(xml, [[								<param name="vm-mailto" value="]] .. vm_mailto .. [["/>]]);
							end
							if (string.len(mwi_account) > 0) then
								table.insert(xml, [[							<param name="MWI-Account" value="]] .. mwi_account .. [["/>]]);
							end
							if (string.len(auth_acl) > 0) then
								table.insert(xml, [[							<param name="auth-acl" value="]] .. auth_acl .. [["/>]]);
							end
							table.insert(xml, [[								<param name="dial-string" value="]] .. dial_string .. [["/>]]);
							table.insert(xml, [[								<param name="verto-context" value="]] .. user_context .. [["/>]]);
							table.insert(xml, [[								<param name="verto-dialplan" value="XML"/>]]);
							table.insert(xml, [[								<param name="jsonrpc-allowed-methods" value="verto"/>]]);
							table.insert(xml, [[								<param name="jsonrpc-allowed-event-channels" value="demo,conference,presence"/>]]);
							table.insert(xml, [[							</params>]]);
							table.insert(xml, [[							<variables>]]);
							table.insert(xml, [[								<variable name="domain_uuid" value="]] .. domain_uuid .. [["/>]]);
							table.insert(xml, [[								<variable name="domain_name" value="]] .. domain_name .. [["/>]]);
							table.insert(xml, [[								<variable name="extension_uuid" value="]] .. extension_uuid .. [["/>]]);
							table.insert(xml, [[								<variable name="call_timeout" value="]] .. call_timeout .. [["/>]]);
							table.insert(xml, [[								<variable name="caller_id_name" value="]] .. sip_from_user .. [["/>]]);
							table.insert(xml, [[								<variable name="caller_id_number" value="]] .. sip_from_number .. [["/>]]);
							if (string.len(call_group) > 0) then
								table.insert(xml, [[								<variable name="call_group" value="]] .. call_group .. [["/>]]);
							end
							if (string.len(call_screen_enabled) > 0) then
								table.insert(xml, [[								<variable name="call_screen_enabled" value="]] .. call_screen_enabled .. [["/>]]);
							end
							if (string.len(user_record) > 0) then
								table.insert(xml, [[								<variable name="user_record" value="]] .. user_record .. [["/>]]);
							end
							if (string.len(hold_music) > 0) then
								table.insert(xml, [[								<variable name="hold_music" value="]] .. hold_music .. [["/>]]);
							end
							if (string.len(toll_allow) > 0) then
								table.insert(xml, [[								<variable name="toll_allow" value="]] .. toll_allow .. [["/>]]);
							end
							if (string.len(accountcode) > 0) then
								table.insert(xml, [[								<variable name="accountcode" value="]] .. accountcode .. [["/>]]);
							end
							table.insert(xml, [[								<variable name="user_context" value="]] .. user_context .. [["/>]]);
							if (string.len(effective_caller_id_name) > 0) then
								table.insert(xml, [[								<variable name="effective_caller_id_name" value="]] .. effective_caller_id_name.. [["/>]]);
							end
							if (string.len(effective_caller_id_number) > 0) then
								table.insert(xml, [[								<variable name="effective_caller_id_number" value="]] .. effective_caller_id_number.. [["/>]]);
							end
							if (string.len(outbound_caller_id_name) > 0) then
								table.insert(xml, [[								<variable name="outbound_caller_id_name" value="]] .. outbound_caller_id_name .. [["/>]]);
							end
							if (string.len(outbound_caller_id_number) > 0) then
								table.insert(xml, [[								<variable name="outbound_caller_id_number" value="]] .. outbound_caller_id_number .. [["/>]]);
							end
							if (string.len(emergency_caller_id_name) > 0) then
								table.insert(xml, [[								<variable name="emergency_caller_id_name" value="]] .. emergency_caller_id_name .. [["/>]]);
							end
							if (string.len(emergency_caller_id_number) > 0) then
								table.insert(xml, [[								<variable name="emergency_caller_id_number" value="]] .. emergency_caller_id_number .. [["/>]]);
							end
							if (string.len(missed_call_app) > 0) then
								table.insert(xml, [[								<variable name="missed_call_app" value="]] .. missed_call_app .. [["/>]]);
							end
							if (string.len(missed_call_data) > 0) then
								table.insert(xml, [[								<variable name="missed_call_data" value="]] .. missed_call_data .. [["/>]]);
							end
							if (string.len(directory_full_name) > 0) then
								table.insert(xml, [[								<variable name="directory_full_name" value="]] .. directory_full_name .. [["/>]]);
							end
							if (string.len(directory_visible) > 0) then
								table.insert(xml, [[								<variable name="directory-visible" value="]] .. directory_visible .. [["/>]]);
							end
							if (string.len(directory_exten_visible) > 0) then
								table.insert(xml, [[								<variable name="directory-exten-visible" value="]] .. directory_exten_visible .. [["/>]]);
							end
							if (string.len(limit_max) > 0) then
								table.insert(xml, [[								<variable name="limit_max" value="]] .. limit_max .. [["/>]]);
							else
								table.insert(xml, [[								<variable name="limit_max" value="5"/>]]);
							end
							if (string.len(limit_destination) > 0) then
								table.insert(xml, [[								<variable name="limit_destination" value="]] .. limit_destination .. [["/>]]);
							end
							if (string.len(sip_force_contact) > 0) then
								table.insert(xml, [[								<variable name="sip_force_contact" value="]] .. sip_force_contact .. [["/>]]);
							end
							if (string.len(sip_force_expires) > 0) then
								table.insert(xml, [[								<variable name="sip-force-expires" value="]] .. sip_force_expires .. [["/>]]);
							end
							if (string.len(nibble_account) > 0) then
								table.insert(xml, [[								<variable name="nibble_account" value="]] .. nibble_account .. [["/>]]);
							end
							if (sip_bypass_media == "bypass-media") then
								table.insert(xml, [[								<variable name="bypass_media" value="true"/>]]);
							end
							if (sip_bypass_media == "bypass-media-after-bridge") then
								table.insert(xml, [[								<variable name="bypass_media_after_bridge" value="true"/>]]);
							end
							if (sip_bypass_media == "proxy-media") then
								table.insert(xml, [[								<variable name="proxy_media" value="true"/>]]);
							end
							if (string.len(forward_all_enabled) > 0) then
								table.insert(xml, [[								<variable name="forward_all_enabled" value="]] .. forward_all_enabled .. [["/>]]);
							end
							if (string.len(forward_all_destination) > 0) then
								table.insert(xml, [[								<variable name="forward_all_destination" value="]] .. forward_all_destination .. [["/>]]);
							end
							if (string.len(forward_busy_enabled) > 0) then
								table.insert(xml, [[								<variable name="forward_busy_enabled" value="]] .. forward_busy_enabled .. [["/>]]);
							end
							if (string.len(forward_busy_destination) > 0) then
								table.insert(xml, [[								<variable name="forward_busy_destination" value="]] .. forward_busy_destination .. [["/>]]);
							end
							if (string.len(forward_no_answer_enabled) > 0) then
								table.insert(xml, [[								<variable name="forward_no_answer_enabled" value="]] .. forward_no_answer_enabled .. [["/>]]);
							end
							if (string.len(forward_no_answer_destination) > 0) then
								table.insert(xml, [[								<variable name="forward_no_answer_destination" value="]] .. forward_no_answer_destination .. [["/>]]);
							end
							if (string.len(do_not_disturb) > 0) then
								table.insert(xml, [[								<variable name="do_not_disturb" value="]] .. do_not_disturb .. [["/>]]);
							end
							table.insert(xml, [[								<variable name="record_stereo" value="true"/>]]);
							table.insert(xml, [[								<variable name="transfer_fallback_extension" value="operator"/>]]);
							table.insert(xml, [[								<variable name="export_vars" value="domain_name"/>]]);
							table.insert(xml, [[							</variables>]]);
							table.insert(xml, [[						</user>]]);
							table.insert(xml, [[					</users>]]);
							table.insert(xml, [[				</group>]]);
							table.insert(xml, [[			</groups>]]);
							table.insert(xml, [[		</domain>]]);
							table.insert(xml, [[	</section>]]);
							table.insert(xml, [[</document>]]);
							XML_STRING = table.concat(xml, "\n");

						--close the database connection
							dbh:release();

						--set the cache
							if (user and domain_name) then
								result = trim(api:execute("memcache", "set directory:" .. user .. "@" .. domain_name .. " '"..XML_STRING:gsub("'", "&#39;").."' "..expire["directory"]));
							end

						--send the xml to the console
							if (debug["xml_string"]) then
								local file = assert(io.open(temp_dir .. "/" .. user .. "@" .. domain_name .. ".xml", "w"));
								file:write(XML_STRING);
								file:close();
							end

						--send to the console
							if (debug["cache"]) then
								freeswitch.consoleLog("notice", "[xml_handler] directory:" .. user .. "@" .. domain_name .. " source: database\n");
							end
					end
			end

		--disable registration for number-alias
			if (params:getHeader("sip_auth_method") == "REGISTER") then
				if (api:execute("user_data", user .. "@" .. domain_name .." attr id") ~= user) then
					XML_STRING = nil;
				end
			end

		--get the XML string from the cache
			if (source == "cache") then
				--replace the &#39 back to a single quote
					if (XML_STRING) then
						XML_STRING = XML_STRING:gsub("&#39;", "'");
					end

				--send to the console
					if (debug["cache"]) then
						if (XML_STRING) then
							freeswitch.consoleLog("notice", "[xml_handler] directory:" .. user .. "@" .. domain_name .. " source: memcache \n");
						end
					end
			end
	end --if action

--if the extension does not exist send "not found"
	if (trim(XML_STRING) == "-ERR NOT FOUND" or XML_STRING == nil) then
		--send not found but do not cache it
			XML_STRING = [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>
			<document type="freeswitch/xml">
				<section name="result">
					<result status="not found" />
				</section>
			</document>]];
	end

--send the xml to the console
	if (debug["xml_string"]) then
		freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: \n" .. XML_STRING .. "\n");
	end
