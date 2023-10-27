--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013 - 2021 Mark J Crane <markjcrane@fusionpbx.com>
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
	vm_mailto = "";

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--include xml library
	local Xml = require "resources.functions.xml";

--include cache library
	local cache = require "resources.functions.cache"

-- event source
	local event_calling_function = params:getHeader("Event-Calling-Function")
	local event_calling_file = params:getHeader("Event-Calling-File")

--determine the correction action to perform
	if (purpose == "gateways") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/domains.lua");
	elseif (action == "message-count") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/message-count.lua");
	elseif (action == "group_call") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/group_call.lua");
	elseif (action == "reverse-auth-lookup") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/reverse-auth-lookup.lua");
	elseif (event_calling_function == "switch_xml_locate_domain") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/domains.lua");
	elseif (event_calling_function == "switch_load_network_lists") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/acl.lua");
	elseif (event_calling_function == "populate_database") and (event_calling_file == "mod_directory.c") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/directory.lua");
	else
		--handle action
			--all other directory actions: sip_auth, user_call
			--except for the action: group_call

		-- Do we need use proxy to make call to ext. reged on different FS
		--   true - send call to FS where ext reged
		--   false - send call directly to ext
			local USE_FS_PATH = xml_handler and xml_handler["fs_path"]
			if (USE_FS_PATH == 'true') then
				USE_FS_PATH = true;
			else
				USE_FS_PATH = false;
			end

		-- Make sance only for extensions with number_alias
		--  false - you should register with AuthID=UserID=Extension (default)
		--  true  - you should register with AuthID=Extension and UserID=Number Alias
		-- 	also in this case you need 2 records in cache for one extension
			local DIAL_STRING_BASED_ON_USERID = xml_handler and xml_handler["reg_as_number_alias"]

		-- Use number as presence_id
		-- When you have e.g. extension like `user-100` with number-alias `100`
		-- by default presence_id is `user-100`. This option allow use `100` as presence_id
			local NUMBER_AS_PRESENCE_ID = xml_handler and xml_handler["number_as_presence_id"]

			local sip_auth_method = params:getHeader("sip_auth_method")
			if sip_auth_method then
				sip_auth_method = sip_auth_method:upper();
			end

		-- Get UserID. If used UserID ~= AuthID then we have to disable `inbound-reg-force-matching-username`
		-- on sofia profile and check UserID=Number-Alias and AuthID=Extension on register manually.
		-- But in load balancing mode in proxy INVITE we have UserID equal to origin UserID but 
		-- AuthID equal to callee AuthID. (e.g. 105 call to 100 and one FS forward call to other FS
		-- then we have UserID=105 but AuthID=100).
		-- Because we do not verify source of INVITE (FS or user device) we have to accept any UserID
		-- for INVITE in such mode. So we just substitute correct UserID for check.
		-- !!! NOTE !!! do not change USE_FS_PATH before this check.
			local from_user = params:getHeader("sip_from_user")
			if USE_FS_PATH and sip_auth_method == 'INVITE' then
				from_user = user
			end

		-- Check eather we need build dial-string. Before request dial-string FusionPBX set `dialed_extension`
		-- variable. So if we have no such variable we do not need build dial-string.
			dialed_extension = params:getHeader("dialed_extension");
			if (dialed_extension == nil) then
				-- freeswitch.consoleLog("notice", "[xml_handler][directory] dialed_extension is null\n");
				USE_FS_PATH = false;
			else
				-- freeswitch.consoleLog("notice", "[xml_handler][directory] dialed_extension is " .. dialed_extension .. "\n");
			end

			-- verify from_user and number alias for this methods
			local METHODS = {
				-- _ANY_    = true,
				REGISTER = true,
				-- INVITE   = true,
			}

			if (user == nil) then
				user = "";
			end

			if (from_user == "") or (from_user == nil) then
				from_user = user
			end

		--prevent processing for invalid user
			if (user == "*97") or (user == "") then
				source = "";
				continue = false;
			end

		-- cleanup
			XML_STRING = nil;

		-- get the cache. We can use cache only if we do not use `fs_path`
		-- or we do not need dial-string. In other way we have to use database.
			if (continue) and (not USE_FS_PATH) then
				if (cache.support() and domain_name) then
					local key, err = "directory:" .. (from_user or user) .. "@" .. domain_name
					XML_STRING, err = cache.get(key);

					if debug['cache'] then
						if not XML_STRING then
							freeswitch.consoleLog("notice", "[xml_handler][directory][cache] get key: " .. key .. " fail: " .. tostring(err) .. "\n")
						else
							freeswitch.consoleLog("notice", "[xml_handler][directory][cache] get key: " .. key .. " pass!" .. "\n")
						end
					end
				end
				source = XML_STRING and "cache" or "database";
			end

		--show the params in the console
			--if (params:serialize() ~= nil) then
			--	freeswitch.consoleLog("notice", "[xml_handler][directory] Params:\n" .. params:serialize() .. "\n");
			--end

			local loaded_from_db = false
		--build the XML string from the database
			if (source == "database") or (USE_FS_PATH) then
				loaded_from_db = true

				--include Database class
					local Database = require "resources.functions.database";

				--database connection
					if (continue) then
						--connect to the database
							dbh = Database.new('system');

						--exits the script if we didn't connect properly
							assert(dbh:connected());

						--get the domain_uuid
							if (domain_uuid == nil) then
								--get the domain_uuid
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
					end

				--get the dial_string from default settings
					local Settings = require "resources.functions.lazy_settings"
					local settings = Settings.new(dbh, domain_name, domain_uuid);
					dial_string = settings:get('domain', 'dial_string', 'text');
 
				--prevent processing for invalid domains
					if (domain_uuid == nil) then
						continue = false;
					end

				--if load balancing is set to true then get the hostname
					if (continue) then
						if (USE_FS_PATH) then

							--get the domain_name from domains
								if (domain_name == nil) then
									local sql = "SELECT domain_name FROM v_domains "
										.. "WHERE domain_uuid = :domain_uuid ";
									local params = {domain_uuid = domain_uuid};
									if (debug["sql"]) then
										freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
									end
									dbh:query(sql, params, function(row)
										domain_name = row["domain_name"];
									end);
								end

							--get the caller hostname
								local_hostname = trim(api:execute("switchname", ""));
								--freeswitch.consoleLog("notice", "[xml_handler][directory] local_hostname is " .. local_hostname .. "\n");

							--add the file_exists function
								require "resources.functions.file_exists";

							--connect to the switch database
								dbh_switch = Database.new('switch');

							--get register name
								local reg_user = dialed_extension
								if not DIAL_STRING_BASED_ON_USERID then
									reg_user = trim(api:execute("user_data", dialed_extension .. "@" .. domain_name .. " attr id"));
								end

							--get the destination hostname from the registration
								local params = {reg_user=reg_user, domain_name=domain_name}
								local sql = "SELECT hostname FROM registrations "
									.. "WHERE reg_user = :reg_user "
									.. "AND realm = :domain_name ";
								if (database["type"] == "mysql") then
									params.now = os.time();
									sql = sql .. "AND expires > :now ";
								elseif (database["type"] == "pgsql") then
									sql = sql .. "AND to_timestamp(expires) > NOW()";
								end
								if (debug["sql"]) then
									freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
								end
								dbh_switch:query(sql, params, function(row)
									database_hostname = row["hostname"];
								end);
								--freeswitch.consoleLog("notice", "[xml_handler] sql: " .. sql .. "\n");
								--freeswitch.consoleLog("notice", "[xml_handler][directory] database_hostname is " .. database_hostname .. "\n");

							--hostname was not found set USE_FS_PATH to false to prevent a database_hostname concatenation error
								if (database_hostname == nil and USE_FS_PATH) then
									USE_FS_PATH = false;
								end

							--close the database connection
								dbh_switch:release();
						end
					end

				--get the extension from the database
					if (continue) then
						local sql = "SELECT e.*, random() FROM v_extensions as e, v_domains as d "
							.. "WHERE e.domain_uuid = :domain_uuid "
							.. "AND d.domain_uuid = :domain_uuid "
							.. "AND d.domain_enabled = 'true' "
							.. "AND (e.extension = :user or e.number_alias = :user) "
							.. "AND e.enabled = 'true' ";
						local params = {domain_uuid=domain_uuid, user=user};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						continue = false;
						dbh:query(sql, params, function(row)
							--general
								continue = true;
								domain_uuid = row.domain_uuid;
								extension_uuid = row.extension_uuid;
								extension = row.extension;
								cidr = "";
								if (string.len(row.cidr) > 0) then
									cidr = row.cidr;
								end
								number_alias = ""
								if (string.len(row.number_alias) > 0) then
									number_alias = row.number_alias;
								end

							--get the user_uuid
								local sql = "SELECT user_uuid FROM v_extension_users WHERE domain_uuid = :domain_uuid and extension_uuid = :extension_uuid "
								local params = {domain_uuid=domain_uuid, extension_uuid=extension_uuid};
								user_uuid = dbh:first_value(sql, params);

							--get the contact_uuid
								if (user_uuid ~= nil) and (string.len(user_uuid) > 0) then
									local sql = "SELECT contact_uuid FROM v_users WHERE domain_uuid = :domain_uuid and user_uuid = :user_uuid "
									local params = {domain_uuid=domain_uuid, user_uuid=user_uuid};
									contact_uuid = dbh:first_value(sql, params);
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
								directory_first_name = row.directory_first_name;
								directory_last_name = row.directory_last_name;
								directory_visible = row.directory_visible;
								directory_exten_visible = row.directory_exten_visible;
								limit_max = row.limit_max;
								call_timeout = row.call_timeout;
								max_registrations = row.max_registrations;
								limit_destination = row.limit_destination;
								sip_force_contact = row.sip_force_contact;
								sip_force_expires = row.sip_force_expires;
								nibble_account = row.nibble_account;
								sip_bypass_media = row.sip_bypass_media;
								absolute_codec_string = row.absolute_codec_string;
								force_ping = row.force_ping;
								forward_all_enabled = row.forward_all_enabled;
								forward_all_destination = row.forward_all_destination;
								forward_busy_enabled = row.forward_busy_enabled;
								forward_busy_destination = row.forward_busy_destination;
								forward_no_answer_enabled = row.forward_no_answer_enabled;
								forward_no_answer_destination = row.forward_no_answer_destination;
								forward_user_not_registered_enabled = row.forward_user_not_registered_enabled;
								forward_user_not_registered_destination = row.forward_user_not_registered_destination;
								do_not_disturb = row.do_not_disturb;

							--if the extension is virtual set register to false
								if (row.extension_type == 'virtual') then
									auth_acl = 'virtual.' .. row.random;
								end

							-- get the follow me information
								if (row.follow_me_uuid ~= nil and string.len(row.follow_me_uuid) > 0) then
									follow_me_uuid = row.follow_me_uuid;
									if (do_not_disturb == "true" or forward_all_enabled == "true") then
										follow_me_enabled = "false";
									else
										follow_me_enabled = row.follow_me_enabled;
									end
								end

							--set the presence_id
								presence_id = (NUMBER_AS_PRESENCE_ID and sip_from_number or sip_from_user) .. "@" .. domain_name;

							--set the dial_string
								if (do_not_disturb == "true") then
									dial_string = "error/user_busy";
								elseif (string.len(row.dial_string) > 0) then
									dial_string = row.dial_string;
								else
									--set the destintion
										local destination = (DIAL_STRING_BASED_ON_USERID and sip_from_number or sip_from_user) .. "@" .. domain_name;
									--set a default dial string
										if (dial_string == null) then
											dial_string = "{sip_invite_domain=" .. domain_name .. ",presence_id=" .. presence_id .. "}${sofia_contact(*/" .. destination .. ")}";
										end
									--set the an alternative dial string if the hostnames don't match
										if (USE_FS_PATH) then
											if (local_hostname == database_hostname) then
												freeswitch.consoleLog("notice", "[xml_handler][directory] local_host and database_host are the same\n");
											else
												contact = trim(api:execute("sofia_contact", destination));
												array = explode('/',contact);
												local profile, proxy = array[2], database_hostname;
												if (profile == 'user_not_registered') then
													profile = 'internal';
												end
												dial_string = "{sip_invite_domain=" .. domain_name .. ",presence_id=" .. presence_id .."}sofia/" .. profile .. "/" .. destination .. ";fs_path=sip:" .. proxy;
												--freeswitch.consoleLog("notice", "[xml_handler][directory] dial_string " .. dial_string .. "\n");
											end
										else
											--freeswitch.consoleLog("notice", "[xml_handler][directory] seems balancing is false??" .. tostring(USE_FS_PATH) .. "\n");
										end

									--show debug informationa
										if (USE_FS_PATH) then
											freeswitch.consoleLog("notice", "[xml_handler] local_hostname: " .. local_hostname.. " database_hostname: " .. database_hostname .. " dial_string: " .. dial_string .. "\n");
										end
								end
						end);
					end

				--get the extension settings from the database
					if (extension_uuid) then
						local sql = "SELECT * FROM v_extension_settings "
							.. "WHERE extension_uuid = :extension_uuid "
							.. "and extension_setting_enabled = 'true' ";
						local params = {extension_uuid=extension_uuid};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						extension_settings = {}
						dbh:query(sql, params, function(row)
							table.insert(extension_settings, {
								extension_setting_type = row.extension_setting_type,
								extension_setting_name = row.extension_setting_name,
								extension_setting_value = row.extension_setting_value
							});
						end);
					end

				--get the voicemail from the database
					if (continue) then
						vm_enabled = "true";
						local sql = "SELECT * FROM v_voicemails WHERE domain_uuid = :domain_uuid and voicemail_id = :voicemail_id ";
						local params = {domain_uuid = domain_uuid};
						if number_alias and #number_alias > 0 then
							params.voicemail_id = number_alias;
						else
							params.voicemail_id = user;
						end
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params, function(row)
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

						--set the directory full name
							directory_full_name = '';
							if (string.len(directory_first_name) > 0) then
								directory_full_name = directory_first_name;
								if (string.len(directory_last_name) > 0) then
									directory_full_name = directory_first_name.. [[ ]] .. directory_last_name;
								end
							end

						--build the xml
							local xml = Xml:new();
							xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
							xml:append([[<document type="freeswitch/xml">]]);
							xml:append([[	<section name="directory">]]);
							xml:append([[		<domain name="]] ..  xml.sanitize(domain_name) .. [[" alias="true">]]);
							xml:append([[			<params>]]);
							xml:append([[				<param name="jsonrpc-allowed-methods" value="verto"/>]]);
							xml:append([[				<param name="jsonrpc-allowed-event-channels" value="demo,conference,presence"/>]]);
							xml:append([[			</params>]]);
							xml:append([[			<groups>]]);
							xml:append([[				<group name="default">]]);
							xml:append([[					<users>]]);
							if (number_alias) then
								if (cidr) then
									xml:append([[						<user id="]] ..  xml.sanitize(extension) .. [[" cidr="]] ..  xml.sanitize(cidr) .. [[" number-alias="]] ..  xml.sanitize(number_alias) .. [[" type="">]]);
								else
									xml:append([[						<user id="]] ..  xml.sanitize(extension) .. [[" number-alias="]] ..  xml.sanitize(number_alias) .. [[">]]);
								end
							else
								if (cidr) then
									xml:append([[						<user id="]] ..  xml.sanitize(extension) .. [[" cidr="]] .. cidr .. [[">]]);
								else
									xml:append([[						<user id="]] ..  xml.sanitize(extension) .. [[">]]);
								end
							end
							xml:append([[							<params>]]);
							xml:append([[								<param name="password" value="]] .. password .. [["/>]]);
							xml:append([[								<param name="vm-enabled" value="]] ..  xml.sanitize(vm_enabled) .. [["/>]]);
							if (string.len(vm_mailto) > 0) then
								xml:append([[								<param name="vm-password" value="]] ..  xml.sanitize(vm_password)  .. [["/>]]);
								xml:append([[								<param name="vm-email-all-messages" value="]] ..  xml.sanitize(vm_enabled)  ..[["/>]]);
								xml:append([[								<param name="vm-attach-file" value="]] ..  xml.sanitize(vm_attach_file) .. [["/>]]);
								xml:append([[								<param name="vm-keep-local-after-email" value="]] ..  xml.sanitize(vm_keep_local_after_email) .. [["/>]]);
								xml:append([[								<param name="vm-mailto" value="]] ..  xml.sanitize(vm_mailto) .. [["/>]]);
							end
							if (string.len(mwi_account) > 0) then
								xml:append([[							<param name="MWI-Account" value="]] ..  xml.sanitize(mwi_account) .. [["/>]]);
							end
							if (string.len(auth_acl) > 0) then
								xml:append([[								<param name="auth-acl" value="]] ..  xml.sanitize(auth_acl) .. [["/>]]);
							end
							xml:append([[								<param name="dial-string" value="]] .. dial_string .. [["/>]]);
							xml:append([[								<param name="verto-context" value="]] ..  xml.sanitize(user_context) .. [["/>]]);
							xml:append([[								<param name="verto-dialplan" value="XML"/>]]);
							xml:append([[								<param name="jsonrpc-allowed-methods" value="verto"/>]]);
							xml:append([[								<param name="jsonrpc-allowed-event-channels" value="demo,conference,presence"/>]]);
							xml:append([[								<param name="max-registrations-per-extension" value="]] ..  xml.sanitize(max_registrations) .. [["/>]]);

							for key,row in pairs(extension_settings) do
								if (row.extension_setting_type == 'param') then
									xml:append([[								<param name="]].. xml.sanitize(row.extension_setting_name)..[[" value="]].. xml.sanitize(row.extension_setting_value)..[["/>]]);
								end
							end
							xml:append([[							</params>]]);
							xml:append([[							<variables>]]);
							xml:append([[								<variable name="domain_uuid" value="]] ..  xml.sanitize(domain_uuid) .. [["/>]]);
							xml:append([[								<variable name="domain_name" value="]] ..  xml.sanitize(domain_name) .. [["/>]]);
							xml:append([[								<variable name="extension_uuid" value="]] ..  xml.sanitize(extension_uuid) .. [["/>]]);
							if (user_uuid ~= nil) and (string.len(user_uuid) > 0) then
								xml:append([[								<variable name="user_uuid" value="]] ..  xml.sanitize(user_uuid) .. [["/>]]);
							end
							if (contact_uuid ~= nil) and (string.len(contact_uuid) > 0) then
								xml:append([[								<variable name="contact_uuid" value="]] ..  xml.sanitize(contact_uuid) .. [["/>]]);
							end
							xml:append([[								<variable name="call_timeout" value="]] ..  xml.sanitize(call_timeout) .. [["/>]]);
							xml:append([[								<variable name="caller_id_name" value="]] ..  xml.sanitize(sip_from_user) .. [["/>]]);
							xml:append([[								<variable name="caller_id_number" value="]] ..  xml.sanitize(sip_from_number) .. [["/>]]);
							xml:append([[								<variable name="presence_id" value="]] ..  xml.sanitize(presence_id) .. [["/>]]);
							if (call_group ~= nil) and (string.len(call_group) > 0) then
								xml:append([[								<variable name="call_group" value="]] ..  xml.sanitize(call_group) .. [["/>]]);
							end
							if (call_screen_enabled ~= nil) and (string.len(call_screen_enabled) > 0) then
								xml:append([[								<variable name="call_screen_enabled" value="]] ..  xml.sanitize(call_screen_enabled) .. [["/>]]);
							end
							if (user_record ~= nil) and (string.len(user_record) > 0) then
								xml:append([[								<variable name="user_record" value="]] .. xml.sanitize(user_record) .. [["/>]]);
							end
							if (hold_music ~= nil) and (string.len(hold_music) > 0) then
								xml:append([[								<variable name="hold_music" value="]] .. xml.sanitize(hold_music) .. [["/>]]);
							end
							if (toll_allow ~= nil) and (string.len(toll_allow) > 0) then
								xml:append([[								<variable name="toll_allow" value="]] .. xml.sanitize(toll_allow) .. [["/>]]);
							end
							if (accountcode ~= nil) and (string.len(accountcode) > 0) then
								xml:append([[								<variable name="accountcode" value="]] .. xml.sanitize(accountcode) .. [["/>]]);
							end
							xml:append([[								<variable name="user_context" value="]] .. xml.sanitize(user_context) .. [["/>]]);
							if (effective_caller_id_name ~= nil) and (string.len(effective_caller_id_name) > 0) then
								xml:append([[								<variable name="effective_caller_id_name" value="]] .. xml.sanitize(effective_caller_id_name).. [["/>]]);
							end
							if (effective_caller_id_number ~= nil) and (string.len(effective_caller_id_number) > 0) then
								xml:append([[								<variable name="effective_caller_id_number" value="]] ..  xml.sanitize(effective_caller_id_number).. [["/>]]);
							end
							if (outbound_caller_id_name ~= nil) and (string.len(outbound_caller_id_name) > 0) then
								xml:append([[								<variable name="outbound_caller_id_name" value="]] ..  xml.sanitize(outbound_caller_id_name) .. [["/>]]);
							end
							if (outbound_caller_id_number ~= nil) and (string.len(outbound_caller_id_number) > 0) then
								xml:append([[								<variable name="outbound_caller_id_number" value="]] ..  xml.sanitize(outbound_caller_id_number) .. [["/>]]);
							end
							if (emergency_caller_id_name ~= nil) and (string.len(emergency_caller_id_name) > 0) then
								xml:append([[								<variable name="emergency_caller_id_name" value="]] ..  xml.sanitize(emergency_caller_id_name) .. [["/>]]);
							end
							if (emergency_caller_id_number ~= nil) and (string.len(emergency_caller_id_number) > 0) then
								xml:append([[								<variable name="emergency_caller_id_number" value="]] ..  xml.sanitize(emergency_caller_id_number) .. [["/>]]);
							end
							if (missed_call_app ~= nil) and (string.len(missed_call_app) > 0) then
								xml:append([[								<variable name="missed_call_app" value="]] ..  xml.sanitize(missed_call_app) .. [["/>]]);
							end
							if (missed_call_data ~= nil) and (string.len(missed_call_data) > 0) then
								xml:append([[								<variable name="missed_call_data" value="]] ..  xml.sanitize(missed_call_data) .. [["/>]]);
							end
							if (directory_full_name ~= nil) and (string.len(directory_full_name) > 0) then
								xml:append([[								<variable name="directory_full_name" value="]] ..  xml.sanitize(directory_full_name) .. [["/>]]);
							end
							if (directory_visible ~= nil) and (string.len(directory_visible) > 0) then
								xml:append([[								<variable name="directory-visible" value="]] ..  xml.sanitize(directory_visible) .. [["/>]]);
							end
							if (directory_exten_visible ~= nil) and (string.len(directory_exten_visible) > 0) then
								xml:append([[								<variable name="directory-exten-visible" value="]] ..  xml.sanitize(directory_exten_visible) .. [["/>]]);
							end
							if (limit_max ~= nil) and (string.len(limit_max) > 0) then
								xml:append([[								<variable name="limit_max" value="]] ..  xml.sanitize(limit_max) .. [["/>]]);
							else
								xml:append([[								<variable name="limit_max" value="5"/>]]);
							end
							if (limit_destination ~= nil) and (string.len(limit_destination) > 0) then
								xml:append([[								<variable name="limit_destination" value="]] ..  xml.sanitize(limit_destination) .. [["/>]]);
							end
							if (sip_force_contact ~= nil) and (string.len(sip_force_contact) > 0) then
								xml:append([[								<variable name="sip-force-contact" value="]] ..  xml.sanitize(sip_force_contact) .. [["/>]]);
							end
							if (sip_force_expires ~= nil) and (string.len(sip_force_expires) > 0) then
								xml:append([[								<variable name="sip-force-expires" value="]] ..  xml.sanitize(sip_force_expires) .. [["/>]]);
							end
							if (nibble_account ~= nil) and (string.len(nibble_account) > 0) then
								xml:append([[								<variable name="nibble_account" value="]] ..  xml.sanitize(nibble_account) .. [["/>]]);
							end
							if (absolute_codec_string ~= nil) and (string.len(absolute_codec_string) > 0) then
								xml:append([[								<variable name="absolute_codec_string" value="]] ..  xml.sanitize(absolute_codec_string) .. [["/>]]);
							end
							if (force_ping ~= nil) and (string.len(force_ping) > 0) then
								xml:append([[								<variable name="force_ping" value="]] ..  xml.sanitize(force_ping) .. [["/>]]);
							end
							if (sip_bypass_media ~= nil) and (sip_bypass_media == "bypass-media") then
								xml:append([[								<variable name="bypass_media" value="true"/>]]);
							end
							if (sip_bypass_media ~= nil) and (sip_bypass_media == "bypass-media-after-bridge") then
								xml:append([[								<variable name="bypass_media_after_bridge" value="true"/>]]);
							end
							if (sip_bypass_media ~= nil) and (sip_bypass_media == "proxy-media") then
								xml:append([[								<variable name="proxy_media" value="true"/>]]);
							end
							if (forward_all_enabled ~= nil) and (string.len(forward_all_enabled) > 0) then
								xml:append([[								<variable name="forward_all_enabled" value="]] ..  xml.sanitize(forward_all_enabled) .. [["/>]]);
							end
							if (forward_all_destination ~= nil) and (string.len(forward_all_destination) > 0) then
								xml:append([[								<variable name="forward_all_destination" value="]] ..  xml.sanitize(forward_all_destination) .. [["/>]]);
							end
							if (forward_busy_enabled ~= nil) and (string.len(forward_busy_enabled) > 0) then
								xml:append([[								<variable name="forward_busy_enabled" value="]] ..  xml.sanitize(forward_busy_enabled) .. [["/>]]);
							end
							if (forward_busy_destination ~= nil) and (string.len(forward_busy_destination) > 0) then
								xml:append([[								<variable name="forward_busy_destination" value="]] ..  xml.sanitize(forward_busy_destination) .. [["/>]]);
							end
							if (forward_no_answer_enabled ~= nil) and (string.len(forward_no_answer_enabled) > 0) then
								xml:append([[								<variable name="forward_no_answer_enabled" value="]] ..  xml.sanitize(forward_no_answer_enabled) .. [["/>]]);
							end
							if (forward_no_answer_destination ~= nil) and (string.len(forward_no_answer_destination) > 0) then
								xml:append([[								<variable name="forward_no_answer_destination" value="]] ..  xml.sanitize(forward_no_answer_destination) .. [["/>]]);
							end
							if (forward_user_not_registered_enabled ~= nil) and (string.len(forward_user_not_registered_enabled) > 0) then
								xml:append([[								<variable name="forward_user_not_registered_enabled" value="]] ..  xml.sanitize(forward_user_not_registered_enabled) .. [["/>]]);
							end
							if (forward_user_not_registered_destination ~= nil) and (string.len(forward_user_not_registered_destination) > 0) then
								xml:append([[								<variable name="forward_user_not_registered_destination" value="]] ..  xml.sanitize(forward_user_not_registered_destination) .. [["/>]]);
							end
							if (follow_me_enabled ~= nil) and (string.len(follow_me_enabled) > 0) then
								xml:append([[								<variable name="follow_me_enabled" value="]] ..  xml.sanitize(follow_me_enabled) .. [["/>]]);
							end
							--if (follow_me_destinations ~= nil) and (string.len(follow_me_destinations) > 0) then
							--	xml:append([[								<variable name="follow_me_destinations" value="]] .. follow_me_destinations .. [["/>]]);
							--end
							if (do_not_disturb ~= nil) and (string.len(do_not_disturb) > 0) then
								xml:append([[								<variable name="do_not_disturb" value="]] .. xml.sanitize(do_not_disturb) .. [["/>]]);
							end
							xml:append([[								<variable name="record_stereo" value="true"/>]]);
							xml:append([[								<variable name="transfer_fallback_extension" value="operator"/>]]);
							xml:append([[								<variable name="export_vars" value="domain_name,domain_uuid"/>]]);
							for key,row in pairs(extension_settings) do
								if (row.extension_setting_type == 'variable') then
									xml:append([[								<variable name="]].. xml.sanitize(row.extension_setting_name)..[[" value="]].. xml.sanitize(row.extension_setting_value)..[["/>]]);
								end
							end
							xml:append([[							</variables>]]);
							xml:append([[						</user>]]);
							xml:append([[					</users>]]);
							xml:append([[				</group>]]);
							xml:append([[			</groups>]]);
							xml:append([[		</domain>]]);
							xml:append([[	</section>]]);
							xml:append([[</document>]]);
							XML_STRING = xml:build();

						--close the database connection
							dbh:release();

						--set the cache
							if cache.support() then
								local key = "directory:" .. sip_from_number .. "@" .. domain_name
								if debug['cache'] then
									freeswitch.consoleLog("notice", "[xml_handler][directory][cache] set key: " .. key .. "\n")
								end
								local ok, err = cache.set(key, XML_STRING, expire["directory"])
								if debug["cache"] and not ok then
									freeswitch.consoleLog("warning", "[xml_handler][directory][cache] set key: " .. key .. " fail: " .. tostring(err) .. "\n");
								end

								if sip_from_number ~= sip_from_user then
									key = "directory:" .. sip_from_user .. "@" .. domain_name
									if debug['cache'] then
										freeswitch.consoleLog("notice", "[xml_handler][directory][cache] set key: " .. key .. "\n")
									end
									ok, err = cache.set(key, XML_STRING, expire["directory"])
									if debug["cache"] and not ok then
										freeswitch.consoleLog("warning", "[xml_handler][directory][cache] set key: " .. key .. " fail: " .. tostring(err) .. "\n");
									end
								end
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

		--get the XML string from the cache
			if (source == "cache") then
				--send to the console
					if (debug["cache"]) then
						if (XML_STRING) then
							freeswitch.consoleLog("notice", "[xml_handler] directory:" .. user .. "@" .. domain_name .. " source: cache \n");
						end
					end
			end
	end --if action

--if the extension does not exist send "not found"
	if not XML_STRING then
		--send not found but do not cache it
			XML_STRING = [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>
			<document type="freeswitch/xml">
				<section name="result">
					<result status="not found" />
				</section>
			</document>]];
		--set the cache
			--local key = "directory:" .. user .. "@" .. domain_name;
			--ok, err = cache.set(key, XML_STRING, expire["directory"]);
			--freeswitch.consoleLog("notice", "[xml_handler] " .. user .. "@" .. domain_name .. "\n");
	end

--send the xml to the console
	if (debug["xml_string"]) then
		freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: \n" .. XML_STRING .. "\n");
	end
