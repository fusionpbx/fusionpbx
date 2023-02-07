--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013 - 2022 Mark J Crane <markjcrane@fusionpbx.com>
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

--get the cache
	local cache = require "resources.functions.cache"
	local hostname = trim(api:execute("switchname", ""))
	local sofia_cache_key = "configuration:sofia.conf:" .. hostname
	XML_STRING, err = cache.get(sofia_cache_key)

--set the cache
	if not XML_STRING then
		--log cache error
			if (debug["cache"]) then
				freeswitch.consoleLog("warning", "[xml_handler] " .. sofia_cache_key .. " can not be get from the cache: " .. tostring(err) .. "\n");
			end

		--set a default value
			if (expire["sofia"] == nil) then
				expire["sofia"]= "3600";
			end

		--connect to the database
			local Database = require "resources.functions.database";
			dbh = Database.new('system');

		--include json library
			local json
			if (debug["sql"]) then
				json = require "resources.functions.lunajson"
			end

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--get the variables
			vars = trim(api:execute("global_getvar", ""));

		--start the xml array
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="configuration">]]);
			table.insert(xml, [[		<configuration name="sofia.conf" description="sofia Endpoint">]]);

		--gt the global settings
			sql = "select * from v_sofia_global_settings ";
			sql = sql .. "where global_setting_enabled = 'true' ";
			sql = sql .. "order by global_setting_name asc ";
			local params = {};
			x = 0;
			table.insert(xml, [[			<global_settings>]]);
			dbh:query(sql, params, function(row)
					table.insert(xml, [[				<param name="]]..row.global_setting_name..[[" value="]]..row.global_setting_value..[["/>]]);
			end)
			table.insert(xml, [[			</global_settings>]]);

		--set defaults
			previous_sip_profile_name = "";
			profile_tag_status = "closed";

		--run the query
			sql = "select p.sip_profile_uuid, p.sip_profile_name, p.sip_profile_description, s.sip_profile_setting_name, s.sip_profile_setting_value ";
			sql = sql .. "from v_sip_profiles as p, v_sip_profile_settings as s ";
			sql = sql .. "where s.sip_profile_setting_enabled = 'true' ";
			sql = sql .. "and p.sip_profile_enabled = 'true' ";
			sql = sql .. "and (p.sip_profile_hostname = :hostname or p.sip_profile_hostname is null or p.sip_profile_hostname = '') ";
			sql = sql .. "and p.sip_profile_uuid = s.sip_profile_uuid ";
			sql = sql .. "order by p.sip_profile_name asc ";
			local params = {hostname = hostname};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
			end
			x = 0;
			table.insert(xml, [[			<profiles>]]);
			dbh:query(sql, params, function(row)
				--set as variables
					sip_profile_uuid = row.sip_profile_uuid;
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
							sql = "select * from v_gateways ";
							sql = sql .. "where profile = :profile ";
							sql = sql .. "and enabled = 'true' ";
							sql = sql .. "and (hostname = :hostname or hostname is null or hostname = '') ";
							local params = {profile = sip_profile_name, hostname = hostname};
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
							end
							x = 0;
							dbh:query(sql, params, function(field)
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
										
									else
										table.insert(xml, [[							<param name="register-transport" value="udp"/>]]);
									end
								end

								if (field.contact_params) then
									table.insert(xml, [[							<param name="contact-params" value="]] .. field.contact_params .. [["/>]]);
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
								if (string.len(field.ping_min) > 0) then
									table.insert(xml, [[							<param name="ping-min" value="]] .. field.ping_min .. [["/>]]);
								end
								if (string.len(field.ping_max) > 0) then
									table.insert(xml, [[							<param name="ping-max" value="]] .. field.ping_max .. [["/>]]);
								end
								if (string.len(field.contact_in_ping) > 0) then
									table.insert(xml, [[							<param name="contact-in-ping" value="]] .. field.contact_in_ping .. [["/>]]);
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
								if (string.len(field.extension_in_contact) > 0) then
									table.insert(xml, [[							<param name="extension-in-contact" value="]] .. field.extension_in_contact .. [["/>]]);
								end
								table.insert(xml, [[							<variables>]]);
								if (string.len(field.sip_cid_type) > 0) then
									table.insert(xml, [[								<variable name="sip_cid_type" value="]] .. field.sip_cid_type .. [["/>]]);
								end
								table.insert(xml, [[							</variables>]]);
								table.insert(xml, [[						</gateway>]]);
							end)

						table.insert(xml, [[					</gateways>]]);
						table.insert(xml, [[					<domains>]]);

						--add sip profile domain: name, alias, and parse
						table.insert(xml, [[						<!-- indicator to parse the directory for domains with parse="true" to get gateways-->]]);
						table.insert(xml, [[						<!--<domain name="$${domain}" parse="true"/>-->]]);
						table.insert(xml, [[						<!-- indicator to parse the directory for domains with parse="true" to get gateways and alias every domain to this profile -->]]);
						table.insert(xml, [[						<!--<domain name="all" alias="true" parse="true"/>-->]]);
						sql = "SELECT sip_profile_domain_name, sip_profile_domain_alias, sip_profile_domain_parse FROM v_sip_profile_domains ";
						sql = sql .. "WHERE sip_profile_uuid = :sip_profile_uuid";
						local params = {sip_profile_uuid = sip_profile_uuid};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; sip_profile_uuid:" .. sip_profile_uuid .. "\n");
						end
						dbh:query(sql, params, function(row)
							name = row.sip_profile_domain_name;
							alias = row.sip_profile_domain_alias;
							parse = row.sip_profile_domain_parse;
							if (name == nil or name == '') then name = 'false'; end
							if (alias == nil or alias == '') then alias = 'false'; end
							if (parse == nil or parse == '') then parse = 'false'; end
							table.insert(xml, [[						<domain name="]] .. name .. [[" alias="]] .. alias .. [[" parse="]] .. parse .. [["/>]]);
						end);
						
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
			local ok, err = cache.set(sofia_cache_key, XML_STRING, expire["sofia"])
			if debug["cache"] then
				if ok then
					freeswitch.consoleLog("notice", "[xml_handler] " .. sofia_cache_key .. " stored in the cache\n");
				else
					freeswitch.consoleLog("warning", "[xml_handler] " .. sofia_cache_key .. " can not be stored in the cache: " .. tostring(err) .. "\n");
				end
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. sofia_cache_key .. " source: database\n");
			end
	else
		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. sofia_cache_key .. " source: cache\n");
			end
	end --if XML_STRING

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .. "/sofia.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
