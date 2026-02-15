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

--include xml library
	local Xml = require "resources.functions.xml";

--get the cache
	local cache = require "resources.functions.cache"
	local hostname = trim(api:execute("hostname", ""))
	local sofia_cache_key = hostname .. ":configuration:sofia.conf"; 
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
			local xml = Xml:new();
			xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			xml:append([[<document type="freeswitch/xml">]]);
			xml:append([[	<section name="configuration">]]);
			xml:append([[		<configuration name="sofia.conf" description="sofia Endpoint">]]);

		--gt the global settings
			sql = "select * from v_sofia_global_settings ";
			sql = sql .. "where global_setting_enabled = true ";
			sql = sql .. "order by global_setting_name asc ";
			local params = {};
			x = 0;
			xml:append([[			<global_settings>]]);
			dbh:query(sql, params, function(row)
					xml:append([[				<param name="]] .. xml.sanitize(row.global_setting_name) .. [[" value="]] .. xml.sanitize(row.global_setting_value) .. [["/>]]);
			end)
			xml:append([[			</global_settings>]]);

		--set defaults
			previous_sip_profile_name = "";
			profile_tag_status = "closed";

		--run the query
			sql = "select p.sip_profile_uuid, p.sip_profile_name, p.sip_profile_description, s.sip_profile_setting_name, s.sip_profile_setting_value ";
			sql = sql .. "from v_sip_profiles as p, v_sip_profile_settings as s ";
			sql = sql .. "where s.sip_profile_setting_enabled = true ";
			sql = sql .. "and p.sip_profile_enabled = true ";
			sql = sql .. "and (p.sip_profile_hostname = :hostname or p.sip_profile_hostname is null or p.sip_profile_hostname = '') ";
			sql = sql .. "and p.sip_profile_uuid = s.sip_profile_uuid ";
			sql = sql .. "order by p.sip_profile_name asc ";
			local params = {hostname = hostname};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
			end
			x = 0;
			xml:append([[			<profiles>]]);
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
							xml:append([[					</settings>]]);
							xml:append([[				</profile>]]);
						end
						xml:append([[				<profile name="]] .. xml.sanitize(sip_profile_name) .. [[">]]);
						xml:append([[					<aliases>]]);
						xml:append([[					</aliases>]]);
						xml:append([[					<gateways>]]);
						--xml:append([[						<X-PRE-PROCESS cmd="include" data="]] .. xml.sanitize(sip_profile_name) .. [[/*.xml"/>]]);

						--get the gateways
							sql = "select ";
							sql = sql .. "gateway_uuid, domain_uuid, gateway, username, password, ";
							sql = sql .. "cast(distinct_to as text), auth_username, realm, from_user, from_domain, ";
							sql = sql .. "proxy, register_proxy,outbound_proxy,expire_seconds, ";
							sql = sql .. "register, register_transport, contact_params, retry_seconds, ";
							sql = sql .. "extension, ping, ping_min, ping_max, ";
							sql = sql .. "cast(contact_in_ping as text) , ";
							sql = sql .. "cast(caller_id_in_from as text), ";
							sql = sql .. "cast(supress_cng as text), ";
							sql = sql .. "sip_cid_type, codec_prefs, channels, ";
							sql = sql .. "cast(extension_in_contact as text), ";
							sql = sql .. "context, profile, hostname, ";
							sql = sql .. "cast(enabled as text), ";
							sql = sql .. "description ";
							sql = sql .. "from v_gateways ";
							sql = sql .. "where profile = :profile ";
							sql = sql .. "and enabled = true ";
							sql = sql .. "and (hostname = :hostname or hostname is null or hostname = '') ";
							local params = {profile = sip_profile_name, hostname = hostname};
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
							end
							x = 0;
							dbh:query(sql, params, function(field)
								xml:append([[						<gateway name="]] .. xml.sanitize(string.lower(field.gateway_uuid)) .. [[">]]);

								if (string.len(field.username) > 0) then
									xml:append([[							<param name="username" value="]] .. xml.sanitize(field.username) .. [["/>]]);
								end
								if (string.len(field.distinct_to) > 0) then
									xml:append([[							<param name="distinct-to" value="]] .. xml.sanitize(field.distinct_to) .. [["/>]]);
								end
								if (string.len(field.auth_username) > 0) then
									xml:append([[							<param name="auth-username" value="]] .. xml.sanitize(field.auth_username) .. [["/>]]);
								end
								if (string.len(field.password) > 0) then
									xml:append([[							<param name="password" value="]] .. xml.sanitize(field.password) .. [["/>]]);
								end
								if (string.len(field.realm) > 0) then
									xml:append([[							<param name="realm" value="]] .. xml.sanitize(field.realm) .. [["/>]]);
								end
								if (string.len(field.from_user) > 0) then
									xml:append([[							<param name="from-user" value="]] .. xml.sanitize(field.from_user) .. [["/>]]);
								end
								if (string.len(field.from_domain) > 0) then
									xml:append([[							<param name="from-domain" value="]] .. xml.sanitize(field.from_domain) .. [["/>]]);
								end
								if (string.len(field.proxy) > 0) then
									xml:append([[							<param name="proxy" value="]] .. xml.sanitize(field.proxy) .. [["/>]]);
								end
								if (string.len(field.register_proxy) > 0) then
									xml:append([[							<param name="register-proxy" value="]] .. xml.sanitize(field.register_proxy) .. [["/>]]);
								end
								if (string.len(field.outbound_proxy) > 0) then
									xml:append([[							<param name="outbound-proxy" value="]] .. xml.sanitize(field.outbound_proxy) .. [["/>]]);
								end
								if (string.len(field.expire_seconds) > 0) then
									xml:append([[							<param name="expire-seconds" value="]] .. xml.sanitize(field.expire_seconds) .. [["/>]]);
								end
								if (string.len(field.register) > 0) then
									xml:append([[							<param name="register" value="]] .. xml.sanitize(field.register) .. [["/>]]);
								end

								if (field.register_transport) then
									if (field.register_transport == "udp") then
										xml:append([[							<param name="register-transport" value="udp"/>]]);
									elseif (field.register_transport ==  "tcp") then
										xml:append([[							<param name="register-transport" value="tcp"/>]]);
									elseif (field.register_transport == "tls") then
										xml:append([[							<param name="register-transport" value="tls"/>]]);
										
									else
										xml:append([[							<param name="register-transport" value="udp"/>]]);
									end
								end

								if (field.contact_params) then
									xml:append([[							<param name="contact-params" value="]] .. xml.sanitize(field.contact_params) .. [["/>]]);
								end

								if (string.len(field.retry_seconds) > 0) then
									xml:append([[							<param name="retry-seconds" value="]] .. xml.sanitize(field.retry_seconds) .. [["/>]]);
								end
								if (string.len(field.extension) > 0) then
									xml:append([[							<param name="extension" value="]] .. xml.sanitize(field.extension) .. [["/>]]);
								end
								if (string.len(field.ping) > 0) then
									xml:append([[							<param name="ping" value="]] .. xml.sanitize(field.ping) .. [["/>]]);
								end
								if (string.len(field.ping_min) > 0) then
									xml:append([[							<param name="ping-min" value="]] .. xml.sanitize(field.ping_min) .. [["/>]]);
								end
								if (string.len(field.ping_max) > 0) then
									xml:append([[							<param name="ping-max" value="]] .. xml.sanitize(field.ping_max) .. [["/>]]);
								end
								if (string.len(field.contact_in_ping) > 0) then
									xml:append([[							<param name="contact-in-ping" value="]] .. xml.sanitize(field.contact_in_ping) .. [["/>]]);
								end
								if (string.len(field.context) > 0) then
									xml:append([[							<param name="context" value="]] .. xml.sanitize(field.context) .. [["/>]]);
								end
								if (string.len(field.caller_id_in_from) > 0) then
									xml:append([[							<param name="caller-id-in-from" value="]] .. xml.sanitize(field.caller_id_in_from) .. [["/>]]);
								end
								if (string.len(field.supress_cng) > 0) then
									xml:append([[							<param name="supress-cng" value="]] .. xml.sanitize(field.supress_cng) .. [["/>]]);
								end
								if (string.len(field.extension_in_contact) > 0) then
									xml:append([[							<param name="extension-in-contact" value="]] .. xml.sanitize(field.extension_in_contact) .. [["/>]]);
								end
								xml:append([[							<variables>]]);
								xml:append([[<!-- DEBUG from_user=]] .. tostring(field.from_user) .. [[ type=]] .. type(field.from_user) .. [[ -->]]);
								if (field.sip_cid_type ~= nil and string.len(field.sip_cid_type) > 0) then
									xml:append([[								<variable name="sip_cid_type" value="]] .. xml.sanitize(field.sip_cid_type) .. [["/>]]);
								end
								if (field.from_user ~= nil and string.len(field.from_user) > 0) then
									xml:append([[								<variable name="sip_from_user" value="]] .. xml.sanitize(field.from_user) .. [["/>]]);
									xml:append([[								<variable name="sip_from_display" value="]] .. xml.sanitize(field.from_user) .. [["/>]]);
								end
								xml:append([[							</variables>]]);
								xml:append([[						</gateway>]]);
							end)

						xml:append([[					</gateways>]]);
						xml:append([[					<domains>]]);

						--add sip profile domain: name, alias, and parse
						xml:append([[						<!-- indicator to parse the directory for domains with parse="true" to get gateways-->]]);
						xml:append([[						<!--<domain name="$${domain}" parse="true"/>-->]]);
						xml:append([[						<!-- indicator to parse the directory for domains with parse="true" to get gateways and alias every domain to this profile -->]]);
						xml:append([[						<!--<domain name="all" alias="true" parse="true"/>-->]]);
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
							xml:append([[						<domain name="]] .. xml.sanitize(name) .. [[" alias="]] .. xml.sanitize(alias) .. [[" parse="]] .. xml.sanitize(parse) .. [["/>]]);
						end);
						
						xml:append([[					</domains>]]);
						xml:append([[					<settings>]]);
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

				--sanitize the sip profile setting value, allow specific safe variables
					sip_profile_setting_value = xml.sanitize(sip_profile_setting_value);
					sip_profile_setting_value = string.gsub(sip_profile_setting_value, "{domain_name}", "${domain_name}"); 
					sip_profile_setting_value = string.gsub(sip_profile_setting_value, "{strftime", "${strftime");
					sip_profile_setting_value = string.gsub(sip_profile_setting_value, "{uuid}", "${uuid}");
					sip_profile_setting_value = string.gsub(sip_profile_setting_value, "{record_ext}", "${record_ext}");

				--set the parameters
					if (sip_profile_setting_name) then
						xml:append([[						<param name="]] .. xml.sanitize(sip_profile_setting_name) .. [[" value="]] .. sip_profile_setting_value .. [["/>]]);
					end

				--set the previous value
					previous_sip_profile_name = sip_profile_name;

				--increment the value of x
					x = x + 1;
			end)

		--close the extension tag if it was left open
			if (profile_tag_status == "open") then
				xml:append([[					</settings>]]);
				xml:append([[				</profile>]]);
				profile_tag_status = "close";
			end
			xml:append([[			</profiles>]]);
			xml:append([[		</configuration>]]);
			xml:append([[	</section>]]);
			xml:append([[</document>]]);
			XML_STRING = xml:build();
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

--send the XML to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .. "/sofia.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
