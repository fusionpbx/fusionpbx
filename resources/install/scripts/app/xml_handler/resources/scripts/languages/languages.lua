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
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--general functions
	require "resources.functions.is_uuid";

--set the default
	continue = true;

--get the action
	--action = params:getHeader("action");
	language = params:getHeader("lang");
	macro_name = params:getHeader("macro_name");

--additional information
	--event_calling_function = params:getHeader("Event-Calling-Function");

--determine the correction action to perform
	--get the cache
		if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
			XML_STRING = trim(api:execute("memcache", "get languages:" .. language..":" .. macro_name));
			--freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. XML_STRING .. "\n");
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

	--show the params in the console
		--if (params:serialize() ~= nil) then
		--	freeswitch.consoleLog("notice", "[xml_handler-languages.lua] Params:\n" .. params:serialize() .. "\n");
		--end

	--build the XML string from the database
		if (source == "database") then

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

			--set the xml array and then concatenate the array to a string
				if (continue) then

					-- if macro_name is a uuid get from the phrase details
						if (is_uuid(macro_name)) then
							--define the xml table
								local xml = {}

							--get hte xml
								table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
								table.insert(xml, [[<document type="freeswitch/xml">]]);
								table.insert(xml, [[	<section name="languages">]]);
								table.insert(xml, [[		<language name="]]..language..[[" say-module="]]..language..[[" sound-prefix="]]..sounds_dir..[[/]]..language..[[/us/callie" tts-engine="cepstral" tts-voice="callie">]]);
								table.insert(xml, [[			<phrases>]]);
								table.insert(xml, [[				<macros>]]);

								sql = "SELECT * FROM v_phrases as p, v_phrase_details as d ";
								sql = sql .. "WHERE d.domain_uuid = '" .. domain_uuid .. "' ";
								sql = sql .. "AND p.phrase_uuid = '".. macro_name .."' ";
								sql = sql .. "AND p.phrase_language = '".. language .."' ";
								sql = sql .. "AND p.phrase_uuid = d.phrase_uuid ";
								sql = sql .. "AND p.phrase_enabled = 'true' ";
								sql = sql .. "ORDER BY d.domain_uuid, p.phrase_uuid, d.phrase_detail_order ASC ";
								if (debug["sql"]) then
									freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
								end
								previous_phrase_uuid = "";
								match_tag = "open";
								x = 0;
								dbh:query(sql, function(row)
									--phrase_uuid,domain_uuid,phrase_name,phrase_language
									--phrase_description,phrase_enabled,phrase_detail_uuid
									--phrase_detail_group,phrase_detail_tag,phrase_detail_pattern
									--phrase_detail_function,phrase_detail_data,phrase_detail_method
									--phrase_detail_type,phrase_detail_order
									if (previous_phrase_uuid ~= row.phrase_uuid) then
										if (x > 0) then
											table.insert(xml, [[							</match>]]);
											table.insert(xml, [[						</input>]]);
											table.insert(xml, [[					</macro>]]);
										end
										table.insert(xml, [[					<macro name="]]..row.phrase_uuid..[[">]]);
										table.insert(xml, [[						<input pattern=\"(.*)\">]]);
										table.insert(xml, [[							<match>]]);
										match_open_tag = true
									end
									table.insert(xml, [[								<action function="]]..row.phrase_detail_function..[[" data="]]..row.phrase_detail_data..[["/>]]);
									previous_phrase_uuid = row.phrase_uuid;
									x = x + 1;
								end);
								if (x > 0) then
									table.insert(xml, [[							</match>]]);
									table.insert(xml, [[						</input>]]);
									table.insert(xml, [[					</macro>]]);
								end
								table.insert(xml, [[				</macros>]]);

							--output xml & close previous file
								table.insert(xml, [[			</phrases>]]);
								table.insert(xml, [[		</language>]]);
								table.insert(xml, [[	</section>]]);
								table.insert(xml, [[</document>]]);
								XML_STRING = table.concat(xml, "\n");
						end

					--log to the console
						--freeswitch.consoleLog("notice", "[xml_handler] language " .. XML_STRING .. " \n");

					--close the database connection
						dbh:release();

					--set the cache
						if (domain_name) then
							result = trim(api:execute("memcache", "set languages:" .. language .. ":" .. macro_name .." '"..XML_STRING:gsub("'", "&#39;").."' "..expire["languages"]));
						end

					--send the xml to the console
						if (debug["xml_string"]) then
							local file = assert(io.open(temp_dir .. "/xml_handler-" .. language .. "-" .. macro_name ..".xml", "w"));
							file:write(XML_STRING);
							file:close();
						end

					--send to the console
						if (debug["cache"]) then
							freeswitch.consoleLog("notice", "[xml_handler] languages:" .. language .. ":" .. macro_name .." source: database\n");
						end
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
						freeswitch.consoleLog("notice", "[xml_handler] language:" .. language .. ":" .. macro_name .." source: memcache \n");
					end
				end
		end

--if the macro does not exist send "not found", don't cache the not found
	if (XML_STRING == nil or trim(XML_STRING) == "-ERR NOT FOUND") then
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
