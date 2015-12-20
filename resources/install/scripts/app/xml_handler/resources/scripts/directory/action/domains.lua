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

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

--exits the script if we didn't connect properly
	assert(dbh:connected());

--set the variables as a string
	number_alias = "";
	number_alias_string = "";

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

--process when the sip profile is rescanned, sofia is reloaded, or sip redirect
	sql = "SELECT * FROM v_domains as d, v_extensions as e ";
	if (domain_name ~= nil) then
		sql = sql .. "where d.domain_name = '"..domain_name.."' ";
		sql = sql .. "and d.domain_uuid = e.domain_uuid ";
	end
	--freeswitch.consoleLog("notice", "[xml_handler-directory.lua] sql "..sql.."\n");
	dbh:query(sql, function(row)

		--variables
		cidr = "";
		if (string.len(row.cidr) > 0) then
			cidr = [[ cidr="]] .. row.cidr .. [["]];
		end
		if (string.len(row.number_alias) > 0) then
			number_alias = row.number_alias;
			number_alias_string = [[ number-alias="]] .. row.number_alias .. [["]];
		end
		row.sip_from_user = row.extension;
		row.sip_from_number = (#number_alias > 0) and number_alias or row.extension;

		--continue building the xml
		if (number_alias) then
			if (cidr) then
				table.insert(xml, [[						<user id="]] .. row.extension .. [["]] .. cidr .. number_alias_string .. [[>]]);
			else
				table.insert(xml, [[						<user id="]] .. row.extension .. [["]] .. number_alias_string .. [[>]]);
			end
		else
			if (cidr) then
				table.insert(xml, [[						<user id="]] .. row.extension .. [["]] .. cidr .. [[>]]);
			else
				table.insert(xml, [[						<user id="]] .. row.extension .. [[">]]);
			end
		end
		table.insert(xml, [[							<params>]]);
		table.insert(xml, [[								<param name="password" value="]] .. row.password .. [["/>]]);
		--table.insert(xml, [[								<param name="vm-enabled" value="]] .. vm_enabled .. [["/>]]);
		--if (string.len(vm_mailto) > 0) then
		--	table.insert(xml, [[								<param name="vm-password" value="]] .. vm_password  .. [["/>]]);
		--	table.insert(xml, [[								<param name="vm-email-all-messages" value="]] .. vm_enabled  ..[["/>]]);
		--	table.insert(xml, [[								<param name="vm-attach-file" value="]] .. vm_attach_file .. [["/>]]);
		--	table.insert(xml, [[								<param name="vm-keep-local-after-email" value="]] .. vm_keep_local_after_email .. [["/>]]);
		--	table.insert(xml, [[								<param name="vm-mailto" value="]] .. vm_mailto .. [["/>]]);
		--end
		if (string.len(row.mwi_account) > 0) then
			table.insert(xml, [[							<param name="MWI-Account" value="]] .. row.mwi_account .. [["/>]]);
		end
		if (string.len(row.auth_acl) > 0) then
			table.insert(xml, [[							<param name="auth-acl" value="]] .. row.auth_acl .. [["/>]]);
		end
		table.insert(xml, [[								<param name="dial-string" value="]] .. row.dial_string .. [["/>]]);
		table.insert(xml, [[								<param name="verto-context" value="]] .. row.user_context .. [["/>]]);
		table.insert(xml, [[								<param name="verto-dialplan" value="XML"/>]]);
		table.insert(xml, [[								<param name="jsonrpc-allowed-methods" value="verto"/>]]);
		table.insert(xml, [[								<param name="jsonrpc-allowed-event-channels" value="demo,conference,presence"/>]]);
		table.insert(xml, [[							</params>]]);
		table.insert(xml, [[							<variables>]]);
		table.insert(xml, [[								<variable name="domain_uuid" value="]] .. row.domain_uuid .. [["/>]]);
		table.insert(xml, [[								<variable name="domain_name" value="]] .. domain_name .. [["/>]]);
		table.insert(xml, [[								<variable name="extension_uuid" value="]] .. row.extension_uuid .. [["/>]]);
		table.insert(xml, [[								<variable name="call_timeout" value="]] .. row.call_timeout .. [["/>]]);
		table.insert(xml, [[								<variable name="caller_id_name" value="]] .. row.sip_from_user .. [["/>]]);
		table.insert(xml, [[								<variable name="caller_id_number" value="]] .. row.sip_from_number .. [["/>]]);
		if (string.len(row.call_group) > 0) then
			table.insert(xml, [[								<variable name="call_group" value="]] .. row.call_group .. [["/>]]);
		end
		if (string.len(row.call_screen_enabled) > 0) then
			table.insert(xml, [[								<variable name="call_screen_enabled" value="]] .. row.call_screen_enabled .. [["/>]]);
		end
		if (string.len(row.user_record) > 0) then
			table.insert(xml, [[								<variable name="user_record" value="]] .. row.user_record .. [["/>]]);
		end
		if (string.len(row.hold_music) > 0) then
			table.insert(xml, [[								<variable name="hold_music" value="]] .. row.hold_music .. [["/>]]);
		end
		if (string.len(row.toll_allow) > 0) then
			table.insert(xml, [[								<variable name="toll_allow" value="]] .. row.toll_allow .. [["/>]]);
		end
		if (string.len(row.accountcode) > 0) then
			table.insert(xml, [[								<variable name="accountcode" value="]] .. row.accountcode .. [["/>]]);
		end
		table.insert(xml, [[								<variable name="user_context" value="]] .. row.user_context .. [["/>]]);
		if (string.len(row.effective_caller_id_name) > 0) then
			table.insert(xml, [[								<variable name="effective_caller_id_name" value="]] .. row.effective_caller_id_name.. [["/>]]);
		end
		if (string.len(row.effective_caller_id_number) > 0) then
			table.insert(xml, [[								<variable name="effective_caller_id_number" value="]] .. row.effective_caller_id_number.. [["/>]]);
		end
		if (string.len(row.outbound_caller_id_name) > 0) then
			table.insert(xml, [[								<variable name="outbound_caller_id_name" value="]] .. row.outbound_caller_id_name .. [["/>]]);
		end
		if (string.len(row.outbound_caller_id_number) > 0) then
			table.insert(xml, [[								<variable name="outbound_caller_id_number" value="]] .. row.outbound_caller_id_number .. [["/>]]);
		end
		if (string.len(row.emergency_caller_id_name) > 0) then
			table.insert(xml, [[								<variable name="emergency_caller_id_name" value="]] .. row.emergency_caller_id_name .. [["/>]]);
		end
		if (string.len(row.emergency_caller_id_number) > 0) then
			table.insert(xml, [[								<variable name="emergency_caller_id_number" value="]] .. row.emergency_caller_id_number .. [["/>]]);
		end
		if (string.len(row.missed_call_app) > 0) then
			table.insert(xml, [[								<variable name="missed_call_app" value="]] .. row.missed_call_app .. [["/>]]);
		end
		if (string.len(row.missed_call_data) > 0) then
			table.insert(xml, [[								<variable name="missed_call_data" value="]] .. row.missed_call_data .. [["/>]]);
		end
		if (string.len(row.directory_full_name) > 0) then
			table.insert(xml, [[								<variable name="directory_full_name" value="]] .. row.directory_full_name .. [["/>]]);
		end
		if (string.len(row.directory_visible) > 0) then
			table.insert(xml, [[								<variable name="directory-visible" value="]] .. row.directory_visible .. [["/>]]);
		end
		if (string.len(row.directory_exten_visible) > 0) then
			table.insert(xml, [[								<variable name="directory-exten-visible" value="]] .. row.directory_exten_visible .. [["/>]]);
		end
		if (string.len(row.limit_max) > 0) then
			table.insert(xml, [[								<variable name="limit_max" value="]] .. row.limit_max .. [["/>]]);
		else
			table.insert(xml, [[								<variable name="limit_max" value="5"/>]]);
		end
		if (string.len(row.limit_destination) > 0) then
			table.insert(xml, [[								<variable name="limit_destination" value="]] .. row.limit_destination .. [["/>]]);
		end
		if (string.len(row.sip_force_contact) > 0) then
			table.insert(xml, [[								<variable name="sip-force-contact" value="]] .. row.sip_force_contact .. [["/>]]);
		end
		if (string.len(row.sip_force_expires) > 0) then
			table.insert(xml, [[								<variable name="sip-force-expires" value="]] .. row.sip_force_expires .. [["/>]]);
		end
		if (string.len(row.nibble_account) > 0) then
			table.insert(xml, [[								<variable name="nibble_account" value="]] .. row.nibble_account .. [["/>]]);
		end
		if (string.len(row.absolute_codec_string) > 0) then
			table.insert(xml, [[								<variable name="absolute_codec_string" value="]] .. row.absolute_codec_string .. [["/>]]);
		end
		if (row.sip_bypass_media == "bypass-media") then
			table.insert(xml, [[								<variable name="bypass_media" value="true"/>]]);
		end
		
		if (row.sip_bypass_media == "bypass-media-after-bridge") then
			table.insert(xml, [[								<variable name="bypass_media_after_bridge" value="true"/>]]);
		end
		if (row.sip_bypass_media == "proxy-media") then
			table.insert(xml, [[								<variable name="proxy_media" value="true"/>]]);
		end
		if (string.len(row.forward_all_enabled) > 0) then
			table.insert(xml, [[								<variable name="forward_all_enabled" value="]] .. row.forward_all_enabled .. [["/>]]);
		end
		if (string.len(row.forward_all_destination) > 0) then
			table.insert(xml, [[								<variable name="forward_all_destination" value="]] .. row.forward_all_destination .. [["/>]]);
		end
		if (string.len(row.forward_busy_enabled) > 0) then
			table.insert(xml, [[								<variable name="forward_busy_enabled" value="]] .. row.forward_busy_enabled .. [["/>]]);
		end
		if (string.len(row.forward_busy_destination) > 0) then
			table.insert(xml, [[								<variable name="forward_busy_destination" value="]] .. row.forward_busy_destination .. [["/>]]);
		end
		if (string.len(row.forward_no_answer_enabled) > 0) then
			table.insert(xml, [[								<variable name="forward_no_answer_enabled" value="]] .. row.forward_no_answer_enabled .. [["/>]]);
		end
		if (string.len(row.forward_no_answer_destination) > 0) then
			table.insert(xml, [[								<variable name="forward_no_answer_destination" value="]] .. row.forward_no_answer_destination .. [["/>]]);
		end
		if (string.len(row.forward_user_not_registered_enabled) > 0) then
			table.insert(xml, [[								<variable name="forward_user_not_registered_enabled" value="]] .. row.forward_user_not_registered_enabled .. [["/>]]);
		end
		if (string.len(row.forward_user_not_registered_destination) > 0) then
			table.insert(xml, [[								<variable name="forward_user_not_registered_destination" value="]] .. row.forward_user_not_registered_destination .. [["/>]]);
		end

		if (string.len(row.do_not_disturb) > 0) then
			table.insert(xml, [[								<variable name="do_not_disturb" value="]] .. row.do_not_disturb .. [["/>]]);
		end
		table.insert(xml, [[								<variable name="record_stereo" value="true"/>]]);
		table.insert(xml, [[								<variable name="transfer_fallback_extension" value="operator"/>]]);
		table.insert(xml, [[								<variable name="export_vars" value="domain_name"/>]]);
		table.insert(xml, [[							</variables>]]);
		table.insert(xml, [[						</user>]]);
	end);
	table.insert(xml, [[					</users>]]);
	table.insert(xml, [[				</group>]]);
	table.insert(xml, [[			</groups>]]);
	table.insert(xml, [[		</domain>]]);
	table.insert(xml, [[	</section>]]);
	table.insert(xml, [[</document>]]);
	XML_STRING = table.concat(xml, "\n");
	--freeswitch.consoleLog("notice", "[xml_handler-directory.lua] XML_STRING "..XML_STRING.."\n");

--close the database connection
	dbh:release();
