--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
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

--determine the correction action to perform
	if (purpose == "gateways") then
		if (params:getHeader("profile") == "internal") then
			--process when the sip profile is rescanned or sofia is reloaded
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="directory">]]);
			sql = "SELECT * FROM v_domains ";
			dbh:query(sql, function(row)
				table.insert(xml, [[		<domain name="]]..row.domain_name..[[" />]]);
			end);
			table.insert(xml, [[	</section>]]);
			table.insert(xml, [[</document>]]);
			XML_STRING = table.concat(xml, "\n");
		end
	elseif (action == "message-count") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/message-count.lua");
	elseif (action == "group_call") then
		dofile(scripts_dir.."/app/xml_handler/resources/scripts/directory/action/group_call.lua");
	else 
		--handle action
			--all other directory actions: sip_auth, user_call 
			--except for the action: group_call

		--get the cache
			if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
				if (user == nil) then
					user = "";
				end
				if (domain_name) then
					XML_STRING = trim(api:execute("memcache", "get directory:" .. user .. "@" .. domain_name));
				end
				if (XML_STRING == "-ERR NOT FOUND") then
					continue = true;
				else
					continue = false;
				end
			else
				XML_STRING = "";
				continue = true;
			end

		--prevent processing for invalid user
			if (user == "*97") then
				continue = false;
			end

		--prevent processing for invalid domains
			if (domain_uuid == nil) then
				continue = false;
			end

		--get the extension from the database
			if (continue) then
				sql = "SELECT * FROM v_extensions WHERE domain_uuid = '" .. domain_uuid .. "' and extension = '" .. user .. "' and enabled = 'true' ";
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
						number_alias = "";
						if (string.len(row.number_alias) > 0) then
							number_alias = [[ number-alias="]] .. row.number_alias .. [["]];
						end
					--params
						password = row.password;
						vm_enabled = "true";
						if (string.len(row.vm_enabled) > 0) then
							vm_enabled = row.vm_enabled;
						end
						vm_password = row.vm_password;
						vm_attach_file = "true";
						if (string.len(row.vm_attach_file) > 0) then
							vm_attach_file = row.vm_attach_file;
						end
						vm_keep_local_after_email = "true";
						if (string.len(row.vm_keep_local_after_email) > 0) then
							vm_keep_local_after_email = row.vm_keep_local_after_email;
						end
						if (string.len(row.vm_mailto) > 0) then
							vm_mailto = row.vm_mailto;
						else
							vm_mailto = "";
						end
						mwi_account = row.mwi_account;
						auth_acl = row.auth_acl;
					--variables
						sip_from_user = row.extension;
						call_group = row.call_group;
						hold_music = row.hold_music;
						toll_allow = row.toll_allow;
						accountcode = row.accountcode;
						user_context = row.user_context;
						effective_caller_id_name = row.effective_caller_id_name;
						effective_caller_id_number = row.effective_caller_id_number;
						outbound_caller_id_name = row.outbound_caller_id_name;
						outbound_caller_id_number = row.outbound_caller_id_number;
						emergency_caller_id_number = row.emergency_caller_id_number;
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

					--set the dial_string
						if (string.len(row.dial_string) > 0) then
							dial_string = row.dial_string;
						else
							dial_string = "{sip_invite_domain=" .. domain_name .. ",leg_timeout=" .. call_timeout .. ",presence_id=" .. user .. "@" .. domain_name .. "}${sofia_contact(" .. user .. "@" .. domain_name .. ")}";
						end
				end);
			end
		
		--if the extension does not exist set continue to false;
			if (extension_uuid == nil) then
				continue = false;
			end

		--outbound hot desking - get the extension variables
			if (continue) then
				sql = "SELECT * FROM v_extensions WHERE dial_domain = '" .. domain_name .. "' and dial_user = '" .. user .. "' and enabled = 'true' ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
				end
				dbh:query(sql, function(row)
					--get the values from the database
					extension_uuid = row.extension_uuid;
					domain_uuid = row.domain_uuid;
					sip_from_user = row.extension;
					call_group = row.call_group;
					hold_music = row.hold_music;
					toll_allow = row.toll_allow;
					accountcode = row.accountcode;
					user_context = row.user_context;
					effective_caller_id_name = row.effective_caller_id_name;
					effective_caller_id_number = row.effective_caller_id_number;
					outbound_caller_id_name = row.outbound_caller_id_name;
					outbound_caller_id_number = row.outbound_caller_id_number;
					emergency_caller_id_number = row.emergency_caller_id_number;
					directory_full_name = row.directory_full_name;
					directory_visible = row.directory_visible;
					directory_exten_visible = row.directory_exten_visible;
					limit_max = row.limit_max;
					--call_timeout = row.call_timeout;
					limit_destination = row.limit_destination;
					sip_force_contact = row.sip_force_contact;
					sip_force_expires = row.sip_force_expires;
					nibble_account = row.nibble_account;
					sip_bypass_media = row.sip_bypass_media;
				end);
			end

		--set the xml array and then concatenate the array to a string
			if (continue and password) then
				--build the xml
					local xml = {}
					table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
					table.insert(xml, [[<document type="freeswitch/xml">]]);
					table.insert(xml, [[	<section name="directory">]]);
					table.insert(xml, [[		<domain name="]] .. domain_name .. [[" alias="true">]]);
					table.insert(xml, [[			<groups>]]);
					table.insert(xml, [[				<group name="default">]]);
					table.insert(xml, [[					<users>]]);
					if (number_alias) then
						if (cidr) then
							table.insert(xml, [[						<user id="]] .. extension .. [["]] .. cidr .. number_alias .. [[ type=>]]);
						else
							table.insert(xml, [[						<user id="]] .. extension .. [["]] .. number_alias .. [[>]]);
						end
					else
						if (cidr) then
							table.insert(xml, [[						<user id="]] .. extension .. [["]] .. cidr .. [[>]]);
						else
							table.insert(xml, [[						<user id="]] .. extension .. [[">]]);
						end
					end
					table.insert(xml, [[							<params>]]);
					table.insert(xml, [[									<param name="password" value="]] .. password .. [["/>]]);
					table.insert(xml, [[									<param name="vm-enabled" value="]] .. vm_enabled .. [["/>]]);
					if (string.len(vm_mailto) > 0) then
						table.insert(xml, [[								<param name="vm-password" value="]] .. vm_password  .. [["/>]]);
						table.insert(xml, [[								<param name="vm-email-all-messages" value="]] .. vm_enabled  ..[["/>]]);
						table.insert(xml, [[								<param name="vm-attach-file" value="]] .. vm_attach_file .. [["/>]]);
						table.insert(xml, [[								<param name="vm-keep-local-after-email" value="]] .. vm_keep_local_after_email .. [["/>]]);
						table.insert(xml, [[								<param name="vm-mailto" value="]] .. vm_mailto .. [["/>]]);
					end
					if (string.len(mwi_account) > 0) then
						table.insert(xml, [[								<param name="MWI-Account" value="]] .. mwi_account .. [["/>]]);
					end
					if (string.len(auth_acl) > 0) then
						table.insert(xml, [[								<param name="auth-acl" value="]] .. auth_acl .. [["/>]]);
					end
					table.insert(xml, [[								<param name="dial-string" value="]] .. dial_string .. [["/>]]);
					table.insert(xml, [[							</params>]]);
					table.insert(xml, [[							<variables>]]);
					table.insert(xml, [[								<variable name="domain_uuid" value="]] .. domain_uuid .. [["/>]]);
					table.insert(xml, [[								<variable name="domain_name" value="]] .. domain_name .. [["/>]]);
					table.insert(xml, [[								<variable name="extension_uuid" value="]] .. extension_uuid .. [["/>]]);
					--table.insert(xml, [[								<variable name="call_timeout" value="]] .. call_timeout .. [["/>]]);
					table.insert(xml, [[								<variable name="caller_id_name" value="]] .. sip_from_user .. [["/>]]);
					table.insert(xml, [[								<variable name="caller_id_number" value="]] .. sip_from_user .. [["/>]]);
					if (string.len(call_group) > 0) then
						table.insert(xml, [[								<variable name="call_group" value="]] .. call_group .. [["/>]]);
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
					if (string.len(emergency_caller_id_number) > 0) then
						table.insert(xml, [[								<variable name="emergency_caller_id_number" value="]] .. emergency_caller_id_number .. [["/>]]);
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

				--set the cache
					if (user and domain_name) then
						result = trim(api:execute("memcache", "set directory:" .. user .. "@" .. domain_name .. " '"..XML_STRING:gsub("'", "&#39;").."' "..expire["directory"]));
					end

				--send the xml to the console
					if (debug["xml_string"]) then
						local file = assert(io.open("/tmp/" .. user .. "@" .. domain_name .. ".xml", "w"));
						file:write(XML_STRING);
						file:close();
					end

				--send to the console
					if (debug["cache"]) then
						freeswitch.consoleLog("notice", "[xml_handler] directory:" .. user .. "@" .. domain_name .. " source: database\n");
					end
			else
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
		--send not found
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