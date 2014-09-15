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

--get the cache
	if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
		XML_STRING = trim(api:execute("memcache", "get dialplan:" .. call_context));
	else
		XML_STRING = "-ERR NOT FOUND";
	end

--set the cache
	if (XML_STRING == "-ERR NOT FOUND") then

		--connect to the database
			dofile(scripts_dir.."/resources/functions/database_handle.lua");
			dbh = database_handle('system');

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--get the domains
			x = 1;
			domains = {}
			sql = "SELECT * FROM v_domains;";
			dbh:query(sql, function(row)
				--add items to the domains array
					domains[row["domain_name"]] = row["domain_uuid"];
					domains[row["domain_uuid"]] = row["domain_name"];
				--increment x
					x = x + 1;
			end);

		--get the domain_uuid
			if (domain_uuid == nil) then
				--get the domain_uuid
					if (domain_name ~= nil) then
						domain_uuid = domains[domain_name];
					end
			end

	
		--get the domain name
			function get_domain_name(domains, domain_uuid)
				for key,value in ipairs(domains) do
					if (value.domain_uuid == domain_uuid) then
						return value.domain_name;
					end
				end
			  	return nil;
			end	

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
			if (call_context == "public") then
				sql = sql .. "where d.dialplan_context = '" .. call_context .. "' ";
			else
				sql = sql .. "where (d.dialplan_context = '" .. call_context .. "' or d.dialplan_context = '${domain_name}') ";
				sql = sql .. "and (d.domain_uuid = '" .. domain_uuid .. "' or d.domain_uuid is null )";
			end
			sql = sql .. "and d.dialplan_enabled = 'true' ";
			sql = sql .. "and d.dialplan_uuid = s.dialplan_uuid ";
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
					domain_uuid = row.domain_uuid;
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
						first_action = true;
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
					
					if (call_context == "public") then
						if (dialplan_detail_tag == "action") then
							if (first_action) then
								if (domain_uuid ~= nil and domain_uuid ~= '') then
									--domain_name = get_domain_name(domains, domain_uuid);
									domain_name = domains[domain_uuid];
									table.insert(xml, [[					<action application="set" data="call_direction=inbound"/>]]);
									table.insert(xml, [[					<action application="set" data="domain_uuid=]] .. domain_uuid .. [["/>]]);
									table.insert(xml, [[					<action application="set" data="domain_name=]] .. domain_name .. [["/>]]);			
									table.insert(xml, [[					<action application="set" data="domain=]] .. domain_name .. [["/>]]);			
									first_action = false;
								end
							end
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

		--close the database connection
			dbh:release();
	else
		--replace the &#39 back to a single quote
			XML_STRING = XML_STRING:gsub("&#39;", "'");

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] dialplan:"..call_context.." source: memcache\n");
			end
	end
