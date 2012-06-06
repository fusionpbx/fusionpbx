--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2010 Mark J Crane <markjcrane@fusionpbx.com>
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

--set the debug level
	debug["params"] = false;
	debug["sql"] = false;
	debug["xml_request"] = false;
	debug["xml_string"] = false;

--show param debug info
	if (debug["params"]) then
		freeswitch.consoleLog("notice", "[xml_handler] Params:\n" .. params:serialize() .. "\n");
	end

--get the params and set them as variables
	local domain_name = params:getHeader("domain");
	local purpose   = params:getHeader("purpose");
	local profile   = params:getHeader("profile");
	local key    = params:getHeader("key");
	local user   = params:getHeader("user");
	local user_context = params:getHeader("variable_user_context");
	local destination_number = params:getHeader("Caller-Destination-Number");
	local caller_id_number = params:getHeader("Caller-Caller-ID-Number");

--include the lua script
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	include = assert(loadfile(scripts_dir .. "/resources/config.lua"));
	include();

--connect to the database
	--ODBC - data source name
		if (dsn_name) then
			dbh = freeswitch.Dbh(dsn_name,dsn_username,dsn_password);
		end
	--FreeSWITCH core db handler
		if (db_type == "sqlite") then
			dbh = freeswitch.Dbh("core:"..db_path.."/"..db_name);
		end

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

--handle gateways
	if (purpose == "gateways" and profile) then
		--freeswitch.consoleLog("notice", "[xml_handler] Gateways: " .. profile .." profile\n"); --purpose
	end

--handle the directory
	if (XML_REQUEST["section"] == "directory" and key and user and domain_name) then
		--get the extension from the database
			sql = "SELECT * FROM v_extensions WHERE domain_uuid = '" .. domain_uuid .. "' and extension = '" .. user .. "'";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			dbh:query(sql, function(row)
				--general
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
					limit_destination = row.limit_destination;
					sip_force_contact = row.sip_force_contact;
					sip_force_expires = row.sip_force_expires;
					nibble_account = row.nibble_account;
					sip_bypass_media = row.sip_bypass_media;

				--set the dial_string
					if (string.len(row.dial_string) > 0) then
						dial_string = row.dial_string;
					else
						dial_string = "{sip_invite_domain=${domain_name},presence_id=${dialed_user}@${dialed_domain}}${sofia_contact(${dialed_user}@${dialed_domain})}";
					end
			end);

		--outbound hot desking - get the extension variables
			sql = "SELECT * FROM v_extensions WHERE dial_domain = '" .. domain_name .. "' and dial_user = '" .. user .. "'";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			dbh:query(sql, function(row)
				--variables
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
				limit_destination = row.limit_destination;
				sip_force_contact = row.sip_force_contact;
				sip_force_expires = row.sip_force_expires;
				nibble_account = row.nibble_account;
				sip_bypass_media = row.sip_bypass_media;
			end);

		--set the xml array and then concatenate the array to a string
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="directory">]]);
			table.insert(xml, [[		<domain name="]] .. domain_name .. [[">]]);
			table.insert(xml, [[			<user id="]] .. extension .. [["]] .. cidr .. number_alias .. [[>]]);
			table.insert(xml, [[			<params>]]);
			table.insert(xml, [[				<param name="password" value="]] .. password .. [["/>]]);
			table.insert(xml, [[				<param name="vm-enabled" value="]] .. vm_enabled .. [["/>]]);
			if (string.len(vm_mailto) > 0) then
				table.insert(xml, [[				<param name="vm-password" value="]] .. vm_password  .. [["/>]]);
				table.insert(xml, [[				<param name="vm-email-all-messages" value="]] .. vm_enabled  ..[["/>]]);
				table.insert(xml, [[				<param name="vm-attach-file" value="]] .. vm_attach_file .. [["/>]]);
				table.insert(xml, [[				<param name="vm-keep-local-after-email" value="]] .. vm_keep_local_after_email .. [["/>]]);
				table.insert(xml, [[				<param name="vm-mailto" value="]] .. vm_mailto .. [["/>]]);
			end
			if (string.len(mwi_account) > 0) then
				table.insert(xml, [[				<param name="MWI-Account" value="]] .. mwi_account .. [["/>]]);
			end
			if (string.len(auth_acl) > 0) then
				table.insert(xml, [[				<param name="auth-acl" value="]] .. auth_acl .. [["/>]]);
			end
			table.insert(xml, [[				<param name="dial-string" value="]] .. dial_string .. [["/>]]);
			table.insert(xml, [[			</params>]]);
			table.insert(xml, [[			<variables>]]);
			if (string.len(call_group) > 0) then
				table.insert(xml, [[				<variable name="call_group" value="]] .. call_group .. [["/>]]);
			end
			if (string.len(hold_music) > 0) then
				table.insert(xml, [[				<variable name="hold_music" value="]] .. hold_music .. [["/>]]);
			end
			if (string.len(toll_allow) > 0) then
				table.insert(xml, [[				<variable name="toll_allow" value="]] .. toll_allow .. [["/>]]);
			end
			if (string.len(accountcode) > 0) then
				table.insert(xml, [[				<variable name="accountcode" value="]] .. accountcode .. [["/>]]);
			end
			table.insert(xml, [[				<variable name="user_context" value="]] .. user_context .. [["/>]]);
			if (string.len(effective_caller_id_name) > 0) then  --
				if (extension == "1003") then
					table.insert(xml, [[				<variable name="effective_caller_id_name" value="1002"/>]]);
				else
					table.insert(xml, [[				<variable name="effective_caller_id_name" value="]] .. effective_caller_id_name.. [["/>]]);
				end
			end
			if (string.len(effective_caller_id_number) > 0) then
				table.insert(xml, [[				<variable name="effective_caller_id_number" value="]] .. effective_caller_id_number.. [["/>]]);
			end
			if (string.len(outbound_caller_id_name) > 0) then
				table.insert(xml, [[				<variable name="outbound_caller_id_name" value="]] .. outbound_caller_id_name .. [["/>]]);
			end
			if (string.len(outbound_caller_id_number) > 0) then
				table.insert(xml, [[				<variable name="outbound_caller_id_number" value="]] .. outbound_caller_id_number .. [["/>]]);
			end
			if (string.len(emergency_caller_id_number) > 0) then
				table.insert(xml, [[				<variable name="emergency_caller_id_number" value="]] .. emergency_caller_id_number .. [["/>]]);
			end
			if (string.len(directory_full_name) > 0) then
				table.insert(xml, [[				<variable name="directory_full_name" value="]] .. directory_full_name .. [["/>]]);
			end
			if (string.len(directory_visible) > 0) then
				table.insert(xml, [[				<variable name="directory-visible" value="]] .. directory_visible .. [["/>]]);
			end
			if (string.len(directory_exten_visible) > 0) then
				table.insert(xml, [[				<variable name="directory-exten-visible" value="]] .. directory_exten_visible .. [["/>]]);
			end
			if (string.len(limit_max) > 0) then
				table.insert(xml, [[				<variable name="limit_max" value="]] .. limit_max .. [["/>]]);
			else
				table.insert(xml, [[				<variable name="limit_max" value="5"/>]]);
			end
			if (string.len(limit_destination) > 0) then
				table.insert(xml, [[				<variable name="limit_destination" value="]] .. limit_destination .. [["/>]]);
			end
			if (string.len(sip_force_contact) > 0) then
				table.insert(xml, [[				<variable name="sip_force_contact" value="]] .. sip_force_contact .. [["/>]]);
			end
			if (string.len(sip_force_expires) > 0) then
				table.insert(xml, [[				<variable name="sip-force-expires" value="]] .. sip_force_expires .. [["/>]]);
			end
			if (string.len(nibble_account) > 0) then
				table.insert(xml, [[				<variable name="nibble_account" value="]] .. nibble_account .. [["/>]]);
			end
			if (sip_bypass_media == "bypass-media") then
				table.insert(xml, [[				<variable name="bypass_media" value="true"/>]]);
			end
			if (sip_bypass_media == "bypass-media-after-bridge") then
				table.insert(xml, [[				<variable name="bypass_media_after_bridge" value="true"/>]]);
			end
			if (sip_bypass_media == "proxy-media") then
				table.insert(xml, [[				<variable name="proxy_media" value="true"/>]]);
			end
			table.insert(xml, [[				<variable name="record_stereo" value="true"/>]]);
			table.insert(xml, [[				<variable name="transfer_fallback_extension" value="operator"/>]]);
			table.insert(xml, [[				<variable name="export_vars" value="domain_name"/>]]);
			table.insert(xml, [[			</variables>]]);
			table.insert(xml, [[			</user>]]);
			table.insert(xml, [[		</domain>]]);
			table.insert(xml, [[	</section>]]);
			table.insert(xml, [[</document>]]);
			XML_STRING = table.concat(xml, "\n");

		--send the xml to the console
			if (debug["xml_string"]) then
				freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: \n" .. XML_STRING .. "\n");
			end
	end

--handle the dialplan
	if (XML_REQUEST["section"] == "dialplan") then
		--freeswitch.consoleLog("notice", "[xml_handler] Dialplan: " .. purpose .. " " .. profile .."\n");
		XML_STRING_disabled = [[
			<?xml version="1.0" encoding="UTF-8" standalone="no"?>
			<document type="freeswitch/xml">
				<section name="dialplan" description="">
					<context name="www.fusionpbx.com">
						<extension name="example" continue="true">
							<condition break="never">
								<action application="info" data=""/>
							</condition>
						</extension>
					</context>
				</section>
			</document>
		]]
	end

--send debug info to the console
	if (debug["xml_request"]) then
		freeswitch.consoleLog("notice", "[xml_handler] Section: " .. XML_REQUEST["section"] .. "\n");
		freeswitch.consoleLog("notice", "[xml_handler] Tag Name: " .. XML_REQUEST["tag_name"] .. "\n");
		freeswitch.consoleLog("notice", "[xml_handler] Key Name: " .. XML_REQUEST["key_name"] .. "\n");
		freeswitch.consoleLog("notice", "[xml_handler] Key Value: " .. XML_REQUEST["key_value"] .. "\n");
	end
