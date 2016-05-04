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

--get the cache
	hostname = trim(api:execute("switchname", ""));
	if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
		XML_STRING = trim(api:execute("memcache", "get configuration:sofia.conf:" .. hostname));
	else
		XML_STRING = "-ERR NOT FOUND";
	end

--set the cache
	if (XML_STRING == "-ERR NOT FOUND") or (XML_STRING == "-ERR CONNECTION FAILURE") then

		--set a default value
			if (expire["sofia"] == nil) then
				expire["sofia"]= "3600";
			end

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

		--get the variables
			vars = trim(api:execute("global_getvar", ""));

		--start the xml array
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="configuration">]]);
			table.insert(xml, [[		<configuration name="sofia.conf" description="sofia Endpoint">]]);
			table.insert(xml, [[			<global_settings>]]);
			table.insert(xml, [[				<param name="log-level" value="0"/>]]);
			--table.insert(xml, [[				<param name="auto-restart" value="false"/>]]);
			table.insert(xml, [[				<param name="debug-presence" value="0"/>]]);
			--table.insert(xml, [[				<param name="capture-server" value="udp:homer.domain.com:5060"/>]]);
			table.insert(xml, [[			</global_settings>]]);
			table.insert(xml, [[			<profiles>]]);

		--set defaults
			previous_sip_profile_name = "";
			profile_tag_status = "closed";

		--run the query
			sql = "select p.sip_profile_name, p.sip_profile_description, s.sip_profile_setting_name, s.sip_profile_setting_value ";
			sql = sql .. "from v_sip_profiles as p, v_sip_profile_settings as s ";
			sql = sql .. "where s.sip_profile_setting_enabled = 'true' ";
			sql = sql .. "and p.sip_profile_enabled = 'true' ";
			sql = sql .. "and (p.sip_profile_hostname = '" .. hostname.. "' or p.sip_profile_hostname is null or p.sip_profile_hostname = '') ";
			sql = sql .. "and p.sip_profile_uuid = s.sip_profile_uuid ";
			sql = sql .. "order by p.sip_profile_name asc ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			x = 0;
			dbh:query(sql, function(row)
				--set as variables
					sip_profile_name = row.sip_profile_name;
					--sip_profile_description = row.sip_profile_description;
					sip_profile_setting_name = row.sip_profile_setting_name;
					sip_profile_setting_value = row.sip_profile_setting_value;

				--open xml tag
					if (sip_profile_name ~= previous_sip_profile_name) then
						if (x > 1) then
							table.insert(xml, [[					</settings>]]);
							table.insert(xml, [[				</profile>]]);
						end
						table.insert(xml, [[				<profile name="]]..sip_profile_name..[[">]]);
						table.insert(xml, [[					<aliases>]]);
						table.insert(xml, [[					</aliases>]]);
						table.insert(xml, [[					<gateways>]]);
						--table.insert(xml, [[						<X-PRE-PROCESS cmd="include" data="]]..sip_profile_name..[[/*.xml"/>]]);

						--get the gateways
							if (domain_count > 1) then
								sql = "select * from v_gateways as g, v_domains as d ";
								sql = sql .. "where g.profile = '"..sip_profile_name.."' ";
								sql = sql .. "and g.enabled = 'true' ";
								sql = sql .. "and (g.domain_uuid = d.domain_uuid or g.domain_uuid is null) ";
							else
								sql = "select * from v_gateways as g ";
								sql = sql .. "where g.enabled = 'true' and g.profile = '"..sip_profile_name.."' ";
							end
							sql = sql .. "and (g.hostname = '" .. hostname.. "' or g.hostname is null or g.hostname = '') ";
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
							end
							x = 0;
							dbh:query(sql, function(field)
								table.insert(xml, [[						<gateway name="]] .. string.lower(field.gateway_uuid) .. [[">]]);

								if (string.len(field.username) > 0) then
									table.insert(xml, [[							<param name="username" value="]] .. field.username .. [["/>]]);
								end
								if (string.len(field.distinct_to) > 0) then
									table.insert(xml, [[							<param name="distinct-to" value="]] .. field.distinct_to .. [["/>]]);
								end
								if (string.len(field.auth_username) > 0) then
									table.insert(xml, [[							<param name="auth-username" value="]] .. field.auth_username .. [["/>]]);
								end
								if (string.len(field.password) > 0) then
									table.insert(xml, [[							<param name="password" value="]] .. field.password .. [["/>]]);
								end
								if (string.len(field.realm) > 0) then
									table.insert(xml, [[							<param name="realm" value="]] .. field.realm .. [["/>]]);
								end
								if (string.len(field.from_user) > 0) then
									table.insert(xml, [[							<param name="from-user" value="]] .. field.from_user .. [["/>]]);
								end
								if (string.len(field.from_domain) > 0) then
									table.insert(xml, [[							<param name="from-domain" value="]] .. field.from_domain .. [["/>]]);
								end
								if (string.len(field.proxy) > 0) then
									table.insert(xml, [[							<param name="proxy" value="]] .. field.proxy .. [["/>]]);
								end
								if (string.len(field.register_proxy) > 0) then
									table.insert(xml, [[							<param name="register-proxy" value="]] .. field.register_proxy .. [["/>]]);
								end
								if (string.len(field.outbound_proxy) > 0) then
									table.insert(xml, [[							<param name="outbound-proxy" value="]] .. field.outbound_proxy .. [["/>]]);
								end
								if (string.len(field.expire_seconds) > 0) then
									table.insert(xml, [[							<param name="expire-seconds" value="]] .. field.expire_seconds .. [["/>]]);
								end
								if (string.len(field.register) > 0) then
									table.insert(xml, [[							<param name="register" value="]] .. field.register .. [["/>]]);
								end

								if (field.register_transport) then
									if (field.register_transport == "udp") then
										table.insert(xml, [[							<param name="register-transport" value="udp"/>]]);
									elseif (field.register_transport ==  "tcp") then
										table.insert(xml, [[							<param name="register-transport" value="tcp"/>]]);
									elseif (field.register_transport == "tls") then
										table.insert(xml, [[							<param name="register-transport" value="tls"/>]]);
										table.insert(xml, [[							<param name="contact-params" value="transport=tls"/>]]);
									else
										table.insert(xml, [[							<param name="register-transport" value="udp"/>]]);
									end
								end

								if (string.len(field.retry_seconds) > 0) then
									table.insert(xml, [[							<param name="retry-seconds" value="]] .. field.retry_seconds .. [["/>]]);
								end
								if (string.len(field.extension) > 0) then
									table.insert(xml, [[							<param name="extension" value="]] .. field.extension .. [["/>]]);
								end
								if (string.len(field.ping) > 0) then
									table.insert(xml, [[							<param name="ping" value="]] .. field.ping .. [["/>]]);
								end
								if (string.len(field.context) > 0) then
									table.insert(xml, [[							<param name="context" value="]] .. field.context .. [["/>]]);
								end
								if (string.len(field.caller_id_in_from) > 0) then
									table.insert(xml, [[							<param name="caller-id-in-from" value="]] .. field.caller_id_in_from .. [["/>]]);
								end
								if (string.len(field.supress_cng) > 0) then
									table.insert(xml, [[							<param name="supress-cng" value="]] .. field.supress_cng .. [["/>]]);
								end
								if (string.len(field.sip_cid_type) > 0) then
									table.insert(xml, [[							<param name="sip_cid_type" value="]] .. field.sip_cid_type .. [["/>]]);
								end
								if (string.len(field.extension_in_contact) > 0) then
									table.insert(xml, [[							<param name="extension-in-contact" value="]] .. field.extension_in_contact .. [["/>]]);
								end
								table.insert(xml, [[						</gateway>]]);
							end)

						table.insert(xml, [[					</gateways>]]);
						table.insert(xml, [[					<domains>]]);
						table.insert(xml, [[						<domain name="all" alias="false" parse="true"/>]]);
						table.insert(xml, [[					</domains>]]);
						table.insert(xml, [[					<settings>]]);
						profile_tag_status = "open";
					end

				--loop through the var array
					for line in (vars.."\n"):gmatch"(.-)\n" do
						if (line) then
							pos = string.find(line, "=", 0, true);
							--name = string.sub( line, 0, pos-1);
							--value = string.sub( line, pos+1);
							sip_profile_setting_value = sip_profile_setting_value:gsub("%$%${"..string.sub( line, 0, pos-1).."}", string.sub( line, pos+1));
						end
					end

				--remove $ and replace with ""
					--if (sip_profile_setting_value) then
					--	sip_profile_setting_value = sip_profile_setting_value:gsub("%$", "");
					--end

				--set the parameters
					if (sip_profile_setting_name) then
						table.insert(xml, [[						<param name="]]..sip_profile_setting_name..[[" value="]]..sip_profile_setting_value..[["/>]]);
					end

				--set the previous value
					previous_sip_profile_name = sip_profile_name;

				--increment the value of x
					x = x + 1;
			end)

		--close the extension tag if it was left open
			if (profile_tag_status == "open") then
				table.insert(xml, [[					</settings>]]);
				table.insert(xml, [[				</profile>]]);
				profile_tag_status = "close";
			end
			table.insert(xml, [[			</profiles>]]);
			table.insert(xml, [[		</configuration>]]);
			table.insert(xml, [[	</section>]]);
			table.insert(xml, [[</document>]]);
			XML_STRING = table.concat(xml, "\n");
			if (debug["xml_string"]) then
				freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: " .. XML_STRING .. "\n");
			end

		--close the database connection
			dbh:release();

		--set the cache
			result = trim(api:execute("memcache", "set configuration:sofia.conf:" .. hostname .." '"..XML_STRING:gsub("'", "&#39;").."' "..expire["sofia"]));

		--send the xml to the console
			if (debug["xml_string"]) then
				local file = assert(io.open(temp_dir .. "/sofia.conf.xml", "w"));
				file:write(XML_STRING);
				file:close();
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] configuration:sofia.conf:" .. hostname .." source: database\n");
			end
	else
		--replace the &#39 back to a single quote
			XML_STRING = XML_STRING:gsub("&#39;", "'");

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] configuration:sofia.conf source: memcache\n");
			end
	end --if XML_STRING