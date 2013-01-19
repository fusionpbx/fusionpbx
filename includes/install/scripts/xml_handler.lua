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

--set defaults
	expire = {}
	expire["directory"] = "3600";
	expire["dialplan"] = "300";
	expire["sofia.conf"] = "3600";

--set the debug options
	debug["params"] = false;
	debug["sql"] = false;
	debug["xml_request"] = false;
	debug["xml_string"] = false;
	debug["cache"] = false;

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

--add the trim function
	function trim(s)
		return s:gsub("^%s+", ""):gsub("%s+$", "")
	end

--add the explode function
	function explode ( seperator, str ) 
		local pos, arr = 0, {}
		for st, sp in function() return string.find( str, seperator, pos, true ) end do -- for each divider found
			table.insert( arr, string.sub( str, pos, st-1 ) ) -- attach chars left of current divider
			pos = sp + 1 -- jump past current divider
		end
		table.insert( arr, string.sub( str, pos ) ) -- attach chars right of last divider
		return arr
	end

--if the params class and methods do not exist then add them to prevent errors
	if (not params) then
		params = {}
		function params:getHeader(name)
			self.name = name
		end
		function params:serialize(name)
			self.name = name
		end
	end

--show param debug info
	if (debug["params"]) then
		freeswitch.consoleLog("notice", "[xml_handler] Params:\n" .. params:serialize() .. "\n");
	end

--get the params and set them as variables
	local domain_uuid = params:getHeader("variable_domain_uuid");
	if (domain_uuid == nil) then
		local domain_uuid = params:getHeader("domain_uuid");
	end
	local domain_name = params:getHeader("domain");
	if (domain_name == nil) then
		local domain_name = params:getHeader("domain_name");
	end
	if (domain_name == nil) then
		local domain_name = params:getHeader("variable_domain_name");
	end
	local purpose   = params:getHeader("purpose");
	local profile   = params:getHeader("profile");
	local key    = params:getHeader("key");
	local user   = params:getHeader("user");
	local user_context = params:getHeader("variable_user_context");
	local call_context = params:getHeader("Caller-Context");
	local destination_number = params:getHeader("Caller-Destination-Number");
	local caller_id_number = params:getHeader("Caller-Caller-ID-Number");
	local hunt_context = params:getHeader("Hunt-Context");
	if (hunt_context ~= nil) then
		call_context = hunt_context;
	end

--prepare the api object
	api = freeswitch.API();

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

--handle the configuration
	if (XML_REQUEST["section"] == "configuration") then
		--sofia.conf - profiles and gateways
			if (XML_REQUEST["key_value"] == "sofia.conf") then

				--get the cache
				if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
					XML_STRING = trim(api:execute("memcache", "get configuration:sofia.conf"));
				else
					XML_STRING = "-ERR NOT FOUND";
				end

				if (XML_STRING == "-ERR NOT FOUND") then

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
						sql = sql .. "where p.sip_profile_uuid = s.sip_profile_uuid ";
						sql = sql .. "and s.sip_profile_setting_enabled = 'true' ";
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
											sql = sql .. "and g.domain_uuid = d.domain_uuid ";
										else
											sql = "select * from v_gateways ";
											sql = sql .. "where profile = '"..sip_profile_name.."' and enabled = 'true' ";
										end
										if (debug["sql"]) then
											freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
										end
										x = 0;
										dbh:query(sql, function(field)
											--set as variables
											gateway = field.gateway;
											gateway = gateway:gsub(" ", "_");

											if (domain_count > 1) then
												table.insert(xml, [[						<gateway name="]] .. field.domain_name .."-".. gateway .. [[">]]);
											else
												table.insert(xml, [[						<gateway name="]] .. gateway .. [[">]]);
											end

											if (string.len(field.username) > 0) then
												table.insert(xml, [[							<param name="username" value="]] .. field.username .. [["/>]]);
											else
												table.insert(xml, [[							<param name="username" value="register:false"/>]]);
											end
											if (string.len(field.distinct_to) > 0) then
												table.insert(xml, [[							<param name="distinct-to" value="]] .. field.distinct_to .. [["/>]]);
											end
											if (string.len(field.auth_username) > 0) then
												table.insert(xml, [[							<param name="auth-username" value="]] .. field.auth_username .. [["/>]]);
											end
											if (string.len(field.password) > 0) then
												table.insert(xml, [[							<param name="password" value="]] .. field.password .. [["/>]]);
											else
												table.insert(xml, [[							<param name="password" value="register:false"/>]]);
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

					--set the cache
						result = trim(api:execute("memcache", "set configuration:sofia.conf '"..XML_STRING:gsub("'", "&#39;").."' "..expire["sofia.conf"]));

					--send the xml to the console
						if (debug["xml_string"]) then
							local file = assert(io.open("/tmp/sofia.conf.xml", "w"));
							file:write(XML_STRING);
							file:close();
						end

					--send to the console
						if (debug["cache"]) then
							freeswitch.consoleLog("notice", "[xml_handler] configuration:sofia.conf source: database\n");
						end
				else
					--replace the &#39 back to a single quote
						XML_STRING = XML_STRING:gsub("&#39;", "'");

					--send to the console
						if (debug["cache"]) then
							freeswitch.consoleLog("notice", "[xml_handler] configuration:sofia.conf source: memcache\n");
						end
				end --if XML_STRING
			end --sofia.conf

		--conference.conf - conference controls, and conference profiles
			if (XML_REQUEST["key_value"] == "conference.conf") then

				--start the xml array
					local xml = {}
					table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
					table.insert(xml, [[<document type="freeswitch/xml">]]);
					table.insert(xml, [[	<section name="configuration">]]);
					table.insert(xml, [[		<configuration name="conference.conf" description="Audio Conference">]]);
					table.insert(xml, [[			<caller-controls>]]);
					table.insert(xml, [[				<group name="default">]]);
					table.insert(xml, [[					<control action="mute" digits=""/>]]);
					table.insert(xml, [[					<control action="deaf mute" digits=""/>]]);
					table.insert(xml, [[					<control action="energy up" digits="9"/>]]);
					table.insert(xml, [[					<control action="energy equ" digits="8"/>]]);
					table.insert(xml, [[					<control action="energy dn" digits="7"/>]]);
					table.insert(xml, [[					<control action="vol talk up" digits="3"/>]]);
					table.insert(xml, [[					<control action="vol talk zero" digits="2"/>]]);
					table.insert(xml, [[					<control action="vol talk dn" digits="1"/>]]);
					table.insert(xml, [[					<control action="vol listen up" digits="6"/>]]);
					table.insert(xml, [[					<control action="vol listen zero" digits="5"/>]]);
					table.insert(xml, [[					<control action="vol listen dn" digits="4"/>]]);
					table.insert(xml, [[					<control action="hangup" digits=""/>]]);
					table.insert(xml, [[				</group>]]);
					table.insert(xml, [[				<group name="moderator">]]);
					table.insert(xml, [[					<control action="mute" digits="#"/>]]);
					table.insert(xml, [[					<control action="deaf mute" digits=""/>]]);
					table.insert(xml, [[					<control action="energy up" digits="9"/>]]);
					table.insert(xml, [[					<control action="energy equ" digits="8"/>]]);
					table.insert(xml, [[					<control action="energy dn" digits="7"/>]]);
					table.insert(xml, [[					<control action="vol talk up" digits="3"/>]]);
					table.insert(xml, [[					<control action="vol talk zero" digits="2"/>]]);
					table.insert(xml, [[					<control action="vol talk dn" digits="1"/>]]);
					table.insert(xml, [[					<control action="vol listen up" digits="6"/>]]);
					table.insert(xml, [[					<control action="vol listen zero" digits="5"/>]]);
					table.insert(xml, [[					<control action="vol listen dn" digits="4"/>]]);
					table.insert(xml, [[					<control action="hangup" digits=""/>]]);
					table.insert(xml, [[				</group>]]);
					table.insert(xml, [[			</caller-controls>]]);
					table.insert(xml, "");
					table.insert(xml, [[			<profile name="default">]]);
					table.insert(xml, [[				<param name="cdr-log-dir" value="auto"/>]]);
					table.insert(xml, [[				<param name="conference-flags" value="wait-mod" />]]);
					table.insert(xml, [[				<param name="domain" value="$${domain}"/>]]);
					table.insert(xml, [[				<param name="rate" value="16000"/>]]);
					table.insert(xml, [[				<param name="interval" value="20"/>]]);
					table.insert(xml, [[				<param name="energy-level" value="15"/>]]);
					table.insert(xml, [[				<param name="auto-gain-level" value="50"/>]]);
					table.insert(xml, [[				<param name="caller-controls" value="default"/>]]);
					table.insert(xml, [[				<param name="moderator-controls" value="default"/>]]);
					table.insert(xml, [[				<param name="muted-sound" value="conference/conf-muted.wav"/>]]);
					table.insert(xml, [[				<param name="unmuted-sound" value="conference/conf-unmuted.wav"/>]]);
					table.insert(xml, [[				<param name="alone-sound" value="conference/conf-alone.wav"/>]]);
					table.insert(xml, [[				<param name="moh-sound" value="$${hold_music}"/>]]);
					table.insert(xml, [[				<param name="enter-sound" value="tone_stream://%(200,0,500,600,700)"/>]]);
					table.insert(xml, [[				<param name="exit-sound" value="tone_stream://%(500,0,300,200,100,50,25)"/>]]);
					table.insert(xml, [[				<param name="kicked-sound" value="conference/conf-kicked.wav"/>]]);
					table.insert(xml, [[				<param name="locked-sound" value="conference/conf-locked.wav"/>]]);
					table.insert(xml, [[				<param name="is-locked-sound" value="conference/conf-is-locked.wav"/>]]);
					table.insert(xml, [[				<param name="is-unlocked-sound" value="conference/conf-is-unlocked.wav"/>]]);
					table.insert(xml, [[				<param name="pin-sound" value="conference/conf-pin.wav"/>]]);
					table.insert(xml, [[				<param name="bad-pin-sound" value="conference/conf-bad-pin.wav"/>]]);
					table.insert(xml, [[				<param name="caller-id-name" value="$${outbound_caller_name}"/>]]);
					table.insert(xml, [[				<param name="caller-id-number" value="$${outbound_caller_id}"/>]]);
					table.insert(xml, [[				<param name="comfort-noise" value="true"/>]]);
					table.insert(xml, [[				<param name="auto-record" value="/tmp/test.wav"/>]]);
					table.insert(xml, [[			</profile>]]);

				--set the xml array and then concatenate the array to a string
					table.insert(xml, [[		</configuration>]]);
					table.insert(xml, [[	</section>]]);
					table.insert(xml, [[</document>]]);
					XML_STRING = table.concat(xml, "\n");

				--send the xml to the console
					if (debug["xml_string"]) then
						local file = assert(io.open("/tmp/conference.conf.xml", "w"));
						file:write(XML_STRING);
						file:close();
					end
			end --conference.conf
	end --section configuration

--handle the directory
	if (XML_REQUEST["section"] == "directory") then

		--set the default
			continue = true;

		--get the action
			action = params:getHeader("action");
				--sip_auth - registration
				--group_call - call group has been called
				--user_call - user has been called

		--additional information
				--event_calling_function = params:getHeader("Event-Calling-Function");

		--determine the correction action to perform
			if (action == "message-count") then
				--Event-Calling-Line-Number: 102
				--Event-Sequence: 4173
				--action: message-count
				--key: id
				--user: *98
				--domain: example.com
			elseif (action == "group_call") then
				--handles action
					--group_call

				--attempt to use the cache
					if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
						XML_STRING = trim(api:execute("memcache", "get directory:groups"));
					else
						XML_STRING = "-ERR NOT FOUND";
					end
					if (XML_STRING == "-ERR NOT FOUND") then
						--build the call group array
							sql = [[
							select * from v_extensions 
							where domain_uuid = ']]..domain_uuid..[[' 
							order by call_group asc 
							]];
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
							end
							call_group_array = {};
							status = dbh:query(sql, function(row)
								call_group = row['call_group'];
								--call_group = str_replace(";", ",", call_group);
								tmp_array = explode(",", call_group);
								for key,value in pairs(tmp_array) do
									value = trim(value);
									--freeswitch.consoleLog("notice", "[directory] Key: " .. key .. " Value: " .. value .. " " ..row['extension'] .."\n");
									if (string.len(value) == 0) then
									
									else
										if (call_group_array[value] == nil) then
											call_group_array[value] = row['extension'];
										else
											call_group_array[value] = call_group_array[value]..','..row['extension'];
										end
									end
								end
							end);
							--for key,value in pairs(call_group_array) do
							--	freeswitch.consoleLog("notice", "[directory] Key: " .. key .. " Value: " .. value .. "\n");
							--end

						--build the xml array
							local xml = {}
							table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
							table.insert(xml, [[<document type="freeswitch/xml">]]);
							table.insert(xml, [[	<section name="directory">]]);
							table.insert(xml, [[		<domain name="]] .. domain_name .. [[">]]);
							table.insert(xml, [[		<groups>]]);
							previous_call_group = "";
							for key, value in pairs(call_group_array) do
								call_group = trim(key);
								extension_list = trim(value);
								if (string.len(call_group) > 0) then
									freeswitch.consoleLog("notice", "[directory] call_group: " .. call_group .. "\n");
									freeswitch.consoleLog("notice", "[directory] extension_list: " .. extension_list .. "\n");
									if (previous_call_group ~= call_group) then
										table.insert(xml, [[			<group name="]]..call_group..[[">]]);
										table.insert(xml, [[				<users>]]);
										extension_array = explode(",", extension_list);
										for index,tmp_extension in pairs(extension_array) do
												table.insert(xml, [[					<user id="]]..tmp_extension..[[" type="pointer"/>]]);
										end
										table.insert(xml, [[				</users>]]);
										table.insert(xml, [[			</group>]]);
									end
									previous_call_group = call_group;
								end
							end
							table.insert(xml, [[		</groups>]]);
							table.insert(xml, [[		</domain>]]);
							table.insert(xml, [[	</section>]]);
							table.insert(xml, [[</document>]]);
							XML_STRING = table.concat(xml, "\n");

						--set the cache
							result = trim(api:execute("memcache", "set directory:groups '"..XML_STRING:gsub("'", "&#39;").."' "..expire["directory"]));

						--send to the console
							if (debug["cache"]) then
								freeswitch.consoleLog("notice", "[xml_handler] directory:groups source: database\n");
							end

					else
						--replace the &#39 back to a single quote
							XML_STRING = XML_STRING:gsub("&#39;", "'");

						--send to the console
							if (debug["cache"]) then
								if (XML_STRING) then
									freeswitch.consoleLog("notice", "[xml_handler] directory:groups source: memcache\n");
								end
							end
					end

				--send the xml to the console
					if (debug["xml_string"]) then
						freeswitch.consoleLog("notice", "[directory] Groups XML_STRING: \n" .. XML_STRING .. "\n");
					end
			else 
				--handle action
					--all other directory actions: sip_auth, user_call 
					--except for the action: group_call

				--get the cache
					if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
						XML_STRING = trim(api:execute("memcache", "get directory:" .. user .. "@" .. domain_name));
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

				--get the extension from the database
					if (continue) then
						sql = "SELECT * FROM v_extensions WHERE domain_uuid = '" .. domain_uuid .. "' and extension = '" .. user .. "' and enabled = 'true' ";
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
						end
						dbh:query(sql, function(row)
							--general
								domain_uuid = row.domain_uuid;
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
								limit_destination = row.limit_destination;
								sip_force_contact = row.sip_force_contact;
								sip_force_expires = row.sip_force_expires;
								nibble_account = row.nibble_account;
								sip_bypass_media = row.sip_bypass_media;

							--set the dial_string
								if (string.len(row.dial_string) > 0) then
									dial_string = row.dial_string;
								else
									dial_string = "{sip_invite_domain=" .. domain_name .. ",presence_id=" .. user .. "@" .. domain_name .. "}${sofia_contact(" .. user .. "@" .. domain_name .. ")}";
								end
						end);
					end

				--outbound hot desking - get the extension variables
					if (continue) then
						sql = "SELECT * FROM v_extensions WHERE dial_domain = '" .. domain_name .. "' and dial_user = '" .. user .. "' and enabled = 'true' ";
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
						end
						dbh:query(sql, function(row)
							--variables
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
							table.insert(xml, [[		<domain name="]] .. domain_name .. [[">]]);
							if (number_alias) then
								if (cidr) then
									table.insert(xml, [[			<user id="]] .. extension .. [["]] .. cidr .. number_alias .. [[>]]);
								else
									table.insert(xml, [[			<user id="]] .. extension .. [["]] .. number_alias .. [[>]]);
								end
							else
								if (cidr) then
									table.insert(xml, [[			<user id="]] .. extension .. [["]] .. cidr .. [[>]]);
								else
									table.insert(xml, [[			<user id="]] .. extension .. [[">]]);
								end
							end
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
							table.insert(xml, [[				<variable name="domain_uuid" value="]] .. domain_uuid .. [["/>]]);
							table.insert(xml, [[				<variable name="domain_name" value="]] .. domain_name .. [["/>]]);
							table.insert(xml, [[				<variable name="caller_id_name" value="]] .. sip_from_user .. [["/>]]);
							table.insert(xml, [[				<variable name="caller_id_number" value="]] .. sip_from_user .. [["/>]]);
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
							if (string.len(effective_caller_id_name) > 0) then
								table.insert(xml, [[				<variable name="effective_caller_id_name" value="]] .. effective_caller_id_name.. [["/>]]);
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

						--set the cache
							result = trim(api:execute("memcache", "set directory:" .. user .. "@" .. domain_name .. " '"..XML_STRING:gsub("'", "&#39;").."' "..expire["directory"]));

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
							XML_STRING = XML_STRING:gsub("&#39;", "'");

						--send to the console
							if (debug["cache"]) then
								if (XML_STRING) then
									freeswitch.consoleLog("notice", "[xml_handler] directory:" .. user .. "@" .. domain_name .. " source: memcache \n");
								end
							end
					end
			end --if action

		--if the extension does not exist send "not found"
			if (trim(XML_STRING) == "-ERR NOT FOUND") then
				--send not found
					XML_STRING = [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>
					<document type="freeswitch/xml">
						<section name="result">
							<result status="not found" />
						</section>
					</document>]];
				--set the cache
					result = trim(api:execute("memcache", "set directory:" .. user .. "@" .. domain_name .. " '"..XML_STRING:gsub("'", "&#39;").."' "..expire["directory"]));
			end

		--send the xml to the console
			if (debug["xml_string"]) then
				freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: \n" .. XML_STRING .. "\n");
			end
	end

--handle the dialplan
	if (XML_REQUEST["section"] == "dialplan") then
		if (debug["params"]) then
			freeswitch.consoleLog("notice", "[xml_handler] Params:\n" .. params:serialize() .. "\n");
		end

		--get the cache
		if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
			XML_STRING = trim(api:execute("memcache", "get dialplan:" .. call_context));
		else
			XML_STRING = "-ERR NOT FOUND";
		end
		if (XML_STRING == "-ERR NOT FOUND") then
			--set the xml array and then concatenate the array to a string
				local xml = {}
				table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
				table.insert(xml, [[<document type="freeswitch/xml">]]);
				table.insert(xml, [[	<section name="dialplan" description="">]]);
				table.insert(xml, [[		<context name="]] .. call_context .. [[">]]);

			--set defaults
				previous_dialplan_uuid = "";
				previous_dialplan_detail_group = "";
				previous_dialplan_detail_tag = "";
				previous_dialplan_detail_type = "";
				previous_dialplan_detail_data = "";
				dialplan_tag_status = "closed";
				condition_tag_status = "closed";

			--get the dialplan and related details
				sql = "select * from v_dialplans as d, v_dialplan_details as s ";
				sql = sql .. "where d.dialplan_context = '" .. call_context .. "' ";
				sql = sql .. "and d.dialplan_enabled = 'true' ";
				sql = sql .. "and d.dialplan_uuid = s.dialplan_uuid ";
				--if (call_context ~= "public") then
				--	sql = sql .. "and d.domain_uuid = '" .. domain_uuid .. "' ";
				--end
				sql = sql .. "order by ";
				sql = sql .. "d.dialplan_order asc, ";
				sql = sql .. "d.dialplan_name asc, ";
				sql = sql .. "d.dialplan_uuid asc, ";
				sql = sql .. "s.dialplan_detail_group asc, ";
				sql = sql .. "CASE s.dialplan_detail_tag ";
				sql = sql .. "WHEN 'condition' THEN 1 ";
				sql = sql .. "WHEN 'action' THEN 2 ";
				sql = sql .. "WHEN 'anti-action' THEN 3 ";
				sql = sql .. "ELSE 100 END, ";
				sql = sql .. "s.dialplan_detail_order asc ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
				end
				x = 0;
				dbh:query(sql, function(row)
					--get the dialplan
						--domain_uuid = row.domain_uuid;
						dialplan_uuid = row.dialplan_uuid;
						--app_uuid = row.app_uuid;
						--dialplan_context = row.dialplan_context;
						dialplan_name = row.dialplan_name;
						--dialplan_number = row.dialplan_number;
						dialplan_continue = row.dialplan_continue;
						--dialplan_order = row.dialplan_order;
						--dialplan_enabled = row.dialplan_enabled;
						--dialplan_description = row.dialplan_description;
					--get the dialplan details
						--dialplan_detail_uuid = row.dialplan_detail_uuid;
						dialplan_detail_tag = row.dialplan_detail_tag;
						dialplan_detail_type = row.dialplan_detail_type;
						dialplan_detail_data = row.dialplan_detail_data;
						dialplan_detail_break = row.dialplan_detail_break;
						dialplan_detail_inline = row.dialplan_detail_inline;
						dialplan_detail_group = row.dialplan_detail_group;
						--dialplan_detail_order = row.dialplan_detail_order;

					--remove $$ and replace with $
						dialplan_detail_data = dialplan_detail_data:gsub("%$%$", "$");

					--get the dialplan detail inline
						detail_inline = "";
						if (dialplan_detail_inline) then
							if (string.len(dialplan_detail_inline) > 0) then
								detail_inline = [[ inline="]] .. dialplan_detail_inline .. [["]];
							end
						end

					--close the tags
						if (condition_tag_status ~= "closed") then
							if (previous_dialplan_uuid ~= dialplan_uuid) then
								table.insert(xml, [[				</condition>]]);
								table.insert(xml, [[			</extension>]]);
								dialplan_tag_status = "closed";
								condition_tag_status = "closed";
							else
								if (previous_dialplan_detail_group ~= dialplan_detail_group and previous_dialplan_detail_tag == "condition") then
									table.insert(xml, [[			</condition>]]);
									condition_tag_status = "closed";
								end
							end
						end

					--open the tags
						if (dialplan_tag_status == "closed") then
							table.insert(xml, [[			<extension name="]] .. dialplan_name .. [[" continue="]] .. dialplan_continue .. [[" uuid="]] .. dialplan_uuid .. [[">]]);
							dialplan_tag_status = "open";
						end
						if (dialplan_detail_tag == "condition") then
							--determine the type of condition
								if (dialplan_detail_type == "hour") then
									condition_type = 'time';
								elseif (dialplan_detail_type == "minute") then 
									condition_type = 'time';
								elseif (dialplan_detail_type == "minute-of-day") then 
									condition_type = 'time';
								elseif (dialplan_detail_type == "mday") then 
									condition_type = 'time';
								elseif (dialplan_detail_type == "mweek") then 
									condition_type = 'time';
								elseif (dialplan_detail_type == "mon") then 
									condition_type = 'time';
								elseif (dialplan_detail_type == "yday") then 
									condition_type = 'time';
								elseif (dialplan_detail_type == "year") then 
									condition_type = 'time';
								elseif (dialplan_detail_type == "wday") then 
									condition_type = 'time';
								elseif (dialplan_detail_type == "week") then 
									condition_type = 'time';
								else
									condition_type = 'default';
								end

							--get the condition break attribute
								condition_break = "";
								if (dialplan_detail_break) then
									if (string.len(dialplan_detail_break) > 0) then
										condition_break = [[ break="]] .. dialplan_detail_break .. [["]];
									end
								end

							if (condition_tag_status == "open") then
								if (previous_dialplan_detail_tag == "condition") then
									--add the condition self closing tag
									if (condition) then
										if (string.len(condition) > 0) then
											table.insert(xml, condition .. [[/>]]);
										end
									end
								end
								if (previous_dialplan_detail_tag == "action" or previous_dialplan_detail_tag == "anti-action") then
									table.insert(xml, [[				</condition>]]);
									condition_tag_status = "closed";
									condition_type = "";
									condition_attribute = "";
									condition_expression = "";
								end
							end

							--condition tag but leave off the ending
							if (condition_type == "default") then
								condition = [[				<condition field="]] .. dialplan_detail_type .. [[" expression="]] .. dialplan_detail_data .. [["]] .. condition_break;
							elseif (condition_type == "time") then
								if (condition_attribute) then
									condition_attribute = condition_attribute .. dialplan_detail_type .. [[="]] .. dialplan_detail_data .. [[" ]];
								else
									condition_attribute = dialplan_detail_type .. [[="]] .. dialplan_detail_data .. [[" ]];
								end
								condition_expression = "";
								condition = ""; --prevents a duplicate time condition
							else
								condition = [[				<condition field="]] .. dialplan_detail_type .. [[" expression="]] .. dialplan_detail_data .. [["]] ..  condition_break;
							end
							condition_tag_status = "open";
						end
						if (dialplan_detail_tag == "action" or dialplan_detail_tag == "anti-action") then
							if (previous_dialplan_detail_tag == "condition") then
								--add the condition ending
								if (condition_type == "time") then
									condition = [[				<condition ]] .. condition_attribute .. condition_break;
									condition_attribute = ""; --prevents the condition attribute from being used on every condition
								else
									if (previous_dialplan_detail_type) then
										condition = [[				<condition field="]] .. previous_dialplan_detail_type .. [[" expression="]] .. previous_dialplan_detail_data .. [["]] .. condition_break;
									end
								end
								table.insert(xml, condition .. [[>]]);
								condition = ""; --prevents duplicate time conditions
							end
						end
						if (dialplan_detail_tag == "action") then
							table.insert(xml, [[					<action application="]] .. dialplan_detail_type .. [[" data="]] .. dialplan_detail_data .. [["]] .. detail_inline .. [[/>]]);
						end
						if (dialplan_detail_tag == "anti-action") then
							table.insert(xml, [[					<anti-action application="]] .. dialplan_detail_type .. [[" data="]] .. dialplan_detail_data .. [["]] .. detail_inline .. [[/>]]);
						end

					--save the previous values
						previous_dialplan_uuid = dialplan_uuid;
						previous_dialplan_detail_group = dialplan_detail_group;
						previous_dialplan_detail_tag = dialplan_detail_tag;
						previous_dialplan_detail_type = dialplan_detail_type;
						previous_dialplan_detail_data = dialplan_detail_data;

					--increment the x
						x = x + 1;
				end);

			--close the extension tag if it was left open
				if (dialplan_tag_status == "open") then
					table.insert(xml, [[				</condition>]]);
					table.insert(xml, [[			</extension>]]);
				end

			--set the xml array and then concatenate the array to a string
				table.insert(xml, [[		</context>]]);
				table.insert(xml, [[	</section>]]);
				table.insert(xml, [[</document>]]);
				XML_STRING = table.concat(xml, "\n");

			--set the cache
				tmp = XML_STRING:gsub("\\", "\\\\");
				result = trim(api:execute("memcache", "set dialplan:" .. call_context .. " '"..tmp:gsub("'", "&#39;").."' "..expire["dialplan"]));

			--send the xml to the console
				if (debug["xml_string"]) then
					local file = assert(io.open("/tmp/dialplan-" .. call_context .. ".xml", "w"));
					file:write(XML_STRING);
					file:close();
				end

			--send to the console
				if (debug["cache"]) then
					freeswitch.consoleLog("notice", "[xml_handler] dialplan:"..call_context.." source: database\n");
				end
		else
			--replace the &#39 back to a single quote
				XML_STRING = XML_STRING:gsub("&#39;", "'");

			--send to the console
				if (debug["cache"]) then
					freeswitch.consoleLog("notice", "[xml_handler] dialplan:"..call_context.." source: memcache\n");
				end
		end
	end

--send debug info to the console
	if (debug["xml_request"]) then
		freeswitch.consoleLog("notice", "[xml_handler] Section: " .. XML_REQUEST["section"] .. "\n");
		freeswitch.consoleLog("notice", "[xml_handler] Tag Name: " .. XML_REQUEST["tag_name"] .. "\n");
		freeswitch.consoleLog("notice", "[xml_handler] Key Name: " .. XML_REQUEST["key_name"] .. "\n");
		freeswitch.consoleLog("notice", "[xml_handler] Key Value: " .. XML_REQUEST["key_value"] .. "\n");
	end

--close the database connection
	dbh:release();
