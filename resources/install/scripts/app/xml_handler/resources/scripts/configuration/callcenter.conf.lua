--      xml_handler.lua
--      Part of FusionPBX
--      Copyright (C) 2015 Mark J Crane <markjcrane@fusionpbx.com>
--      All rights reserved.
--
--      Redistribution and use in source and binary forms, with or without
--      modification, are permitted provided that the following conditions are met:
--
--      1. Redistributions of source code must retain the above copyright notice,
--         this list of conditions and the following disclaimer.
--
--      2. Redistributions in binary form must reproduce the above copyright
--         notice, this list of conditions and the following disclaimer in the
--         documentation and/or other materials provided with the distribution.
--
--      THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--      INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--      AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--      AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--      OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--      SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--      INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--      CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--      ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--      POSSIBILITY OF SUCH DAMAGE.

--get the cache
	hostname = trim(api:execute("switchname", ""));
	if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
		XML_STRING = trim(api:execute("memcache", "get configuration:callcenter.conf:" .. hostname));
	else
		XML_STRING = "-ERR NOT FOUND";
	end

--set the cache
	if (XML_STRING == "-ERR NOT FOUND") or (XML_STRING == "-ERR CONNECTION FAILURE") then

		--connect to the database
			dofile(scripts_dir.."/resources/functions/database_handle.lua");
			dbh = database_handle('system');

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--get the variables
			dsn = trim(api:execute("global_getvar", "dsn"));

		--start the xml array
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[    <section name="configuration">]]);
			table.insert(xml, [[            <configuration name="callcenter.conf" description="Call Center">]]);
			table.insert(xml, [[                    <settings>]]);
			if (string.len(dsn) > 0) then
				table.insert(xml, [[                            <param name="odbc-dsn" value="]]..database["switch"]..[["/>]]);
			end
			--table.insert(xml, [[                          <param name="dbname" value="/usr/local/freeswitch/db/call_center.db"/>]]);
			table.insert(xml, [[                    </settings>]]);

		--write the queues
			table.insert(xml, [[                    <queues>]]);
			sql = "select * from v_call_center_queues as q, v_domains as d ";
			sql = sql .. "where d.domain_uuid = q.domain_uuid; ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			x = 0;
			dbh:query(sql, function(row)
				--set as variables
					domain_uuid = row.domain_uuid;
					domain_name = row.domain_name;
					queue_name = row.queue_name;
					queue_extension = row.queue_extension;
					queue_strategy = row.queue_strategy;
					queue_moh_sound = row.queue_moh_sound;
					queue_record_template = row.queue_record_template;
					queue_time_base_score = row.queue_time_base_score;
					queue_max_wait_time = row.queue_max_wait_time;
					queue_max_wait_time_with_no_agent = row.queue_max_wait_time_with_no_agent;
					queue_tier_rules_apply = row.queue_tier_rules_apply;
					queue_tier_rule_wait_second = row.queue_tier_rule_wait_second;
					queue_tier_rule_wait_multiply_level = row.queue_tier_rule_wait_multiply_level;
					queue_tier_rule_no_agent_no_wait = row.queue_tier_rule_no_agent_no_wait;
					queue_discard_abandoned_after = row.queue_discard_abandoned_after;
					queue_abandoned_resume_allowed = row.queue_abandoned_resume_allowed;
					queue_announce_sound = row.queue_announce_sound;
					queue_announce_frequency = row.queue_announce_frequency;
					queue_description = row.queue_description;

					table.insert(xml, [[                            <queue name="]]..queue_name..[[@]]..domain_name..[[">]]);
					table.insert(xml, [[                                    <param name="strategy" value="]]..queue_strategy..[["/>]]);
					if (string.len(queue_moh_sound) == 0) then
						table.insert(xml, [[                                    <param name="moh-sound" value=local_stream://default"/>]]);
					else
						if (string.sub(queue_moh_sound, 0, 15) == 'local_stream://') then
								table.insert(xml, [[                                    <param name="moh-sound" value="]]..queue_moh_sound..[["/>]]);
						elseif (string.sub(queue_moh_sound, 0, 2) == '${' and string.sub(queue_moh_sound, -5) == 'ring}') then
								table.insert(xml, [[                                    <param name="moh-sound" value="tone_stream://]]..queue_moh_sound..[[;loops=-1"/>]]);
						else
								table.insert(xml, [[                                    <param name="moh-sound" value="]]..queue_moh_sound..[["/>]]);
						end
					end
					if (queue_record_template ~= nil) then
							table.insert(xml, [[                                    <param name="record-template" value="]]..queue_record_template..[["/>]]);
					end
					if (queue_time_base_score ~= nil) then
						table.insert(xml, [[                                    <param name="time-base-score" value="]]..queue_time_base_score..[["/>]]);
					end
					if (queue_max_wait_time_with_no_agent ~= nil) then
						table.insert(xml, [[                                    <param name="max-wait-time" value="]]..queue_max_wait_time..[["/>]]);
					end
					if (queue_max_wait_time_with_no_agent ~= nil) then
						table.insert(xml, [[                                    <param name="max-wait-time-with-no-agent" value="]]..queue_max_wait_time_with_no_agent..[["/>]]);
					end
					if (queue_max_wait_time_with_no_agent_time_reached ~= nil) then
						table.insert(xml, [[                                    <param name="max-wait-time-with-no-agent-time-reached" value="]]..queue_max_wait_time_with_no_agent_time_reached..[["/>]]);
					end
					if (queue_tier_rules_apply ~= nil) then
						table.insert(xml, [[                                    <param name="tier-rules-apply" value="]]..queue_tier_rules_apply..[["/>]]);
					end
					if (queue_tier_rule_wait_second ~= nil) then
						table.insert(xml, [[                                    <param name="tier-rule-wait-second" value="]]..queue_tier_rule_wait_second..[["/>]]);
					end
					if (queue_tier_rule_wait_multiply_level ~= nil) then
						table.insert(xml, [[                                    <param name="tier-rule-wait-multiply-level" value="]]..queue_tier_rule_wait_multiply_level..[["/>]]);
					end
					if (queue_tier_rule_no_agent_no_wait ~= nil) then
						table.insert(xml, [[                                    <param name="tier-rule-no-agent-no-wait" value="]]..queue_tier_rule_no_agent_no_wait..[["/>]]);
					end
					if (queue_discard_abandoned_after ~= nil) then
						table.insert(xml, [[                                    <param name="discard-abandoned-after" value="]]..queue_discard_abandoned_after..[["/>]]);
					end
					if (queue_abandoned_resume_allowed ~= nil) then
						table.insert(xml, [[                                    <param name="abandoned-resume-allowed" value="]]..queue_abandoned_resume_allowed..[["/>]]);
					end
					if (queue_announce_sound ~= nil) then
						table.insert(xml, [[                                    <param name="announce-sound" value="]]..queue_announce_sound..[["/>]]);
					end
					if (queue_announce_frequency ~= nil) then
						table.insert(xml, [[                                    <param name="announce-frequency" value="]]..queue_announce_frequency..[["/>]]);
					end
					table.insert(xml, [[                            </queue>]]);

				--increment the value of x
					x = x + 1;
			end)
			table.insert(xml, [[                    </queues>]]);

		--get the agents
				table.insert(xml, [[                    <agents>]]);
				sql = "select * from v_call_center_agents ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
				end
				x = 0;
				dbh:query(sql, function(row)
					--get the values from the database and set as variables
						agent_name = row.agent_name;
						agent_type = row.agent_type;
						agent_call_timeout = row.agent_call_timeout;
						agent_contact = row.agent_contact;
						agent_status = row.agent_status;
						agent_no_answer_delay_time = row.agent_no_answer_delay_time;
						agent_max_no_answer = row.agent_max_no_answer;
						agent_wrap_up_time = row.agent_wrap_up_time;
						agent_reject_delay_time = row.agent_reject_delay_time;
						agent_busy_delay_time = row.agent_busy_delay_time;

					--get and then set the complete agent_contact with the call_timeout and when necessary confirm
							--confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1";
							--if you change this variable also change app/call_center/call_center_agent_edit.php
							confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1,group_confirm_read_timeout=2000,leg_timeout="..agent_call_timeout;
							if (string.find(agent_contact, '}') == nil) then
								--not found
								if (string.find(agent_contact, 'sofia/gateway') == nil) then
									--add the call_timeout
									agent_contact = "{call_timeout="..agent_call_timeout.."}"..agent_contact;
								else
									--add the call_timeout and confirm
									tmp_pos = string.find(agent_contact, "}");
									tmp_first = string.sub(agent_contact, 0, tmp_pos);
									tmp_last = string.sub(agent_contact, tmp_pos);
									agent_contact = tmp_first..',call_timeout='..agent_call_timeout..tmp_last;
									agent_contact = "{"..confirm..",call_timeout="..agent_call_timeout.."}"..agent_contact;
								end
							else
								--found
								if (string.find(agent_contact, 'sofia/gateway') == nil) then
									--not found
									if (string.find(agent_contact, 'call_timeout') == nil) then
											--add the call_timeout
											pos = string.find(agent_contact, "}");
											first = string.sub(agent_contact, 0, pos);
											last = string.sub(agent_contact, tmp_pos);
											agent_contact = first..[[,call_timeout=]]..agent_call_timeout..last;
									else
											--the string has the call timeout
											agent_contact = agent_contact;
									end
								else
									--found
									pos = string.find(agent_contact, "}");
									first = string.sub(agent_contact, 0, pos);
									last = string.sub(agent_contact, pos);
									if (stristr(agent_contact, 'call_timeout') == FALSE) then
										--add the call_timeout and confirm
										agent_contact = first..','..confirm..',call_timeout='..agent_call_timeout..last;
									else
										--add confirm
										agent_contact = tmp_first..','..confirm..tmp_last;
									end
								end
							end

					--build the xml string
						table.insert(xml, [[                            <agent ]]);
						table.insert(xml, [[                            	name="]]..agent_name..[[@]]..domain_name..[[" ]]);
						table.insert(xml, [[                            	type="]]..agent_type..[[" ]]);
						table.insert(xml, [[                            	contact="]]..agent_contact..[[" ]]);
						table.insert(xml, [[                            	status="]]..agent_status..[[" ]]);
						if (agent_no_answer_delay_time ~= nil) then
							table.insert(xml, [[                            	no-answer-delay-time="]]..agent_no_answer_delay_time..[[" ]]);
						end
						if (agent_max_no_answer ~= nil) then
							table.insert(xml, [[                            	max-no-answer="]]..agent_max_no_answer..[[" ]]);
						end
						if (agent_wrap_up_time ~= nil) then
							table.insert(xml, [[                            	wrap-up-time="]]..agent_wrap_up_time..[[" ]]);
						end
						if (agent_reject_delay_time ~= nil) then
							table.insert(xml, [[                            	reject-delay-time="]]..agent_reject_delay_time..[[" ]]);
						end
						if (agent_busy_delay_time ~= nil) then
							table.insert(xml, [[                            	busy-delay-time="]]..agent_busy_delay_time..[[" ]]);
						end
						table.insert(xml, [[                            	/>]]);
				end)
				table.insert(xml, [[                    </agents>]]);

		--get the tiers
				sql = "select * from v_call_center_tiers ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
				end
				table.insert(xml, [[                    <tiers>]]);
				dbh:query(sql, function(row)
					--get the values from the database and set as variables
						agent_name = row.agent_name;
						queue_name = row.queue_name;
						tier_level = row.tier_level;
						tier_position = row.tier_position;
					--build the xml
						table.insert(xml, [[                            <tier ]]);
						table.insert(xml, [[				agent="]]..agent_name..[[@]]..domain_name..[[" ]]);
						table.insert(xml, [[				queue="]]..queue_name..[[@]]..domain_name..[[" ]]);
						table.insert(xml, [[				level="]]..tier_level..[[" ]]);
						table.insert(xml, [[				position="]]..tier_position..[[" ]]);
						table.insert(xml, [[				/>]]);
				end)
				table.insert(xml, [[                    </tiers>]]);

		--close the extension tag if it was left open
				table.insert(xml, [[            </configuration>]]);
				table.insert(xml, [[    </section>]]);
				table.insert(xml, [[</document>]]);
				XML_STRING = table.concat(xml, "\n");
				if (debug["xml_string"]) then
						freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: " .. XML_STRING .. "\n");
				end

		--close the database connection
				dbh:release();
				--freeswitch.consoleLog("notice", "[xml_handler]"..api:execute("eval ${dsn}"));

		--set the cache
			result = trim(api:execute("memcache", "set configuration:callcenter.conf:" .. hostname .." '"..XML_STRING:gsub("'", "&#39;").."' ".."expire['callcenter.conf']"));

		--send the xml to the console
			if (debug["xml_string"]) then
				local file = assert(io.open("/tmp/callcenter.conf.xml", "w"));
				file:write(XML_STRING);
				file:close();
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] configuration:callcenter.conf:" .. hostname .." source: database\n");
			end
	else
		--replace the &#39 back to a single quote
			XML_STRING = XML_STRING:gsub("&#39;", "'");
		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] configuration:callcenter.conf source: memcache\n");
			end
	end --if XML_STRING
