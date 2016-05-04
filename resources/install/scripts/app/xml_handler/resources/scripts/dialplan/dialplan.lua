--	xml_handler.lua
--	Part of FusionPBX
--	Copyright (C) 2013-2016 Mark J Crane <markjcrane@fusionpbx.com>
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

	local cache = require"resources.functions.cache"
	local log = require"resources.functions.log"["xml_handler"]

--needed for cli-command xml_locate dialplan
	if (call_context == nil) then
		call_context = "public";
	end

--get the cache
	XML_STRING, err = cache.get("dialplan:" .. call_context)

	if debug['cache'] then
		if XML_STRING then
			log.notice("dialplan:"..call_context.." source: memcache");
		elseif err ~= 'NOT FOUND' then
			log.notice("error get element from cache: " .. err);
		end
	end

--set the cache
	if not XML_STRING then

		--connect to the database
			require "resources.functions.database_handle";
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
			sql = "select * from v_dialplans as p, v_dialplan_details as s ";
			if (call_context == "public" or string.sub(call_context, 0, 7) == "public@" or string.sub(call_context, -7) == ".public") then
				sql = sql .. "where p.dialplan_context = '" .. call_context .. "' ";
			else
				sql = sql .. "where (p.dialplan_context = '" .. call_context .. "' or p.dialplan_context = '${domain_name}') ";
			end
			sql = sql .. "and p.dialplan_enabled = 'true' ";
			sql = sql .. "and p.dialplan_uuid = s.dialplan_uuid ";
			sql = sql .. "order by ";
			sql = sql .. "p.dialplan_order asc, ";
			sql = sql .. "p.dialplan_name asc, ";
			sql = sql .. "p.dialplan_uuid asc, ";
			sql = sql .. "s.dialplan_detail_group asc, ";
			sql = sql .. "CASE s.dialplan_detail_tag ";
			sql = sql .. "WHEN 'condition' THEN 1 ";
			sql = sql .. "WHEN 'action' THEN 2 ";
			sql = sql .. "WHEN 'anti-action' THEN 3 ";
			sql = sql .. "ELSE 100 END, ";
			sql = sql .. "s.dialplan_detail_order asc ";
			if (debug["sql"]) then
				log.notice("SQL: " .. sql);
			end
			local x = 0;
			local pass
			dbh:query(sql, function(row)
				--clear flag pass
					pass = false

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
							elseif (dialplan_detail_type == "time-of-day") then
								condition_type = 'time';
							elseif (dialplan_detail_type == "yday") then
								condition_type = 'time';
							elseif (dialplan_detail_type == "year") then
								condition_type = 'time';
							elseif (dialplan_detail_type == "wday") then
								condition_type = 'time';
							elseif (dialplan_detail_type == "week") then
								condition_type = 'time';
							elseif (dialplan_detail_type == "date-time") then
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

					if (call_context == "public" or string.sub(call_context, 0, 7) == "public@" or string.sub(call_context, -7) == ".public") then
						if (dialplan_detail_tag == "action") then
							if (first_action) then
								table.insert(xml, [[					<action application="set" data="call_direction=inbound"/>]]);
								if (domain_uuid ~= nil and domain_uuid ~= '') then
									domain_name = domains[domain_uuid];
									table.insert(xml, [[					<action application="set" data="domain_uuid=]] .. domain_uuid .. [["/>]]);
								end
								if (domain_name ~= nil and domain_name ~= '') then
									table.insert(xml, [[					<action application="set" data="domain_name=]] .. domain_name .. [["/>]]);
									table.insert(xml, [[					<action application="set" data="domain=]] .. domain_name .. [["/>]]);
								end
								first_action = false;
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

				--set flag pass
					pass = true
			end);

		-- prevent partial dialplan (pass=nil may be error in sql or empty resultset)
			if pass == false then
				--send a message to the log
					log.errf('context: %s, extension: %s, type: %s, data: %s ',
						call_context,
						dialplan_name or '----',
						dialplan_detail_tag or '----',
						dialplan_detail_data or '----'
					)

				--close the database connection
					dbh:release();

				--show an error
					error('error while build context: ' .. call_context)
			end

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
			if cache.support() then
				cache.set("dialplan:" .. call_context, XML_STRING, expire["dialplan"])
			end

		--send the xml to the console
			if (debug["xml_string"]) then
				local file = assert(io.open(temp_dir .. "/dialplan-" .. call_context .. ".xml", "w"));
				file:write(XML_STRING);
				file:close();
			end

		--send to the console
			if (debug["cache"]) then
				log.notice("dialplan:"..call_context.." source: database");
			end

		--close the database connection
			dbh:release();
	end
