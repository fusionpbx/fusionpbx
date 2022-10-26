--      xml_handler.lua
--      Part of FusionPBX
--      Copyright (C) 2015-2022 Mark J Crane <markjcrane@fusionpbx.com>
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
--      THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--      INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--      AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--      AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--      OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--      SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--      INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--      CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--      ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--      POSSIBILITY OF SUCH DAMAGE.

--include functions
	require "resources.functions.format_ringback"

--get the cache
	local cache = require "resources.functions.cache"
	hostname = trim(api:execute("switchname", ""));
	local cc_cache_key = "configuration:callcenter.conf:" .. hostname
	XML_STRING, err = cache.get(cc_cache_key)

--set the cache
	if not XML_STRING then
		--log cache error
			if (debug["cache"]) then
				freeswitch.consoleLog("warning", "[xml_handler] " .. cc_cache_key .. " can not be get from the cache: " .. tostring(err) .. "\n");
			end

		--connect to the database
			local Database = require "resources.functions.database";
			dbh = Database.new('system');

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--get the variables
			dsn = freeswitch.getGlobalVariable("dsn") or ''
			dsn_callcenter = freeswitch.getGlobalVariable("dsn_callcenter") or ''

		--start the xml array
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[    <section name="configuration">]]);
			table.insert(xml, [[            <configuration name="callcenter.conf" description="Call Center">]]);
			table.insert(xml, [[                    <settings>]]);
			if #dsn_callcenter > 0 then
				table.insert(xml, [[                            <param name="odbc-dsn" value="]]..dsn_callcenter..[["/>]]);
			elseif #dsn > 0 then
				table.insert(xml, [[                            <param name="odbc-dsn" value="]]..database["switch"]..[["/>]]);
			end
			table.insert(xml, [[                          <param name="cc-instance-id" value="]]..hostname..[["/>]]);
			-- table.insert(xml, [[                          <param name="dbname" value="]]..database_dir..[[/call_center.db"/>]]);
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
					queue_uuid = row.call_center_queue_uuid;
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
					queue_max_wait_time_with_no_agent_time_reached = row.queue_max_wait_time_with_no_agent_time_reached;
					queue_tier_rules_apply = row.queue_tier_rules_apply;
					queue_tier_rule_wait_second = row.queue_tier_rule_wait_second;
					queue_tier_rule_wait_multiply_level = row.queue_tier_rule_wait_multiply_level;
					queue_tier_rule_no_agent_no_wait = row.queue_tier_rule_no_agent_no_wait;
					queue_discard_abandoned_after = row.queue_discard_abandoned_after;
					queue_abandoned_resume_allowed = row.queue_abandoned_resume_allowed;
					queue_announce_sound = row.queue_announce_sound;
					queue_announce_frequency = row.queue_announce_frequency;
					queue_description = row.queue_description;

				--replace the space with a dash
					queue_name = queue_name:gsub(" ", "-");

				--start the xml
					table.insert(xml, [[                            <queue name="]]..queue_extension..[[@]]..domain_name..[[" label="]]..queue_name..[[@]]..domain_name..[[">]]);
					table.insert(xml, [[                                    <param name="strategy" value="]]..queue_strategy..[["/>]]);
				--set ringback
					queue_ringback = format_ringback(queue_moh_sound);
					table.insert(xml, [[                                    <param name="moh-sound" value="]]..queue_ringback..[["/>]]);
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
			sql = "select SPLIT_PART(SPLIT_PART(a.agent_contact, '/', 2), '@', 1) as extension,  ";
			sql = sql .. "(select extension_uuid from v_extensions where domain_uuid = a.domain_uuid ";
			sql = sql .. "and extension = SPLIT_PART(SPLIT_PART(a.agent_contact, '/', 2), '@', 1) limit 1) as extension_uuid, ";
			sql = sql .. "a.*, d.domain_name  ";
			sql = sql .. "from v_call_center_agents as a, v_domains as d ";
			sql = sql .. "where d.domain_uuid = a.domain_uuid; ";
			--sql = "select * from v_call_center_agents as a, v_domains as d ";
			--sql = sql .. "where d.domain_uuid = a.domain_uuid; ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			x = 0;
			dbh:query(sql, function(row)
				--get the values from the database and set as variables
					agent_uuid = row.call_center_agent_uuid;
					domain_uuid = row.domain_uuid;
					domain_name = row.domain_name;
					extension_uuid = row.extension_uuid;
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
					agent_record = row.agent_record;

				--get and then set the complete agent_contact with the call_timeout and when necessary confirm
						--confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1";
						--if you change this variable also change app/call_center/call_center_agent_edit.php
						confirm = "group_confirm_file=ivr/ivr-accept_reject_voicemail.wav,group_confirm_key=1,group_confirm_read_timeout=2000,leg_timeout="..agent_call_timeout;
						local record = "";
						if (agent_record == "true") then
							record = string.format(",execute_on_pre_bridge='record_session %s/%s/archive/${strftime(%%Y)}/${strftime(%%b)}/${strftime(%%d)}/${uuid}.${record_ext}'", recordings_dir, domain_name)
						end
						if (string.find(agent_contact, '}') == nil) then
							--not found
							if (string.find(agent_contact, 'sofia/gateway') == nil) then
								--add the call_timeout
								agent_contact = "{call_timeout="..agent_call_timeout..",domain_name="..domain_name..",domain_uuid="..domain_uuid..",extension_uuid="..extension_uuid..",sip_h_caller_destination=${caller_destination}"..record.."}"..agent_contact;
							else
								--add the call_timeout and confirm
								agent_contact = "{"..confirm..",call_timeout="..agent_call_timeout..",domain_name="..domain_name..",domain_uuid="..domain_uuid..",sip_h_caller_destination=${caller_destination}}"..agent_contact;
							end
						else
							--found
							if (string.find(agent_contact, 'sofia/gateway') == nil) then
								--not found
								if (string.find(agent_contact, 'call_timeout') == nil) then
										--add the call_timeout
										pos = string.find(agent_contact, "}");
										first = string.sub(agent_contact, 0, pos -1);
										last = string.sub(agent_contact, pos);
										agent_contact = first..[[,domain_name=]]..domain_name..[[,domain_uuid=]]..domain_uuid..[[,sip_h_caller_destination=${caller_destination},call_timeout=]]..agent_call_timeout..last;
								else
										--add the call_timeout
										pos = string.find(agent_contact, "}");
										first = string.sub(agent_contact, 0, pos - 1);
										last = string.sub(agent_contact, pos);
										agent_contact = first..[[,sip_h_caller_destination=${caller_destination},call_timeout=]]..agent_call_timeout..last;
								end
						else
								--found
								pos = string.find(agent_contact, "}");
								first = string.sub(agent_contact, 0, pos - 1);
								last = string.sub(agent_contact, pos);
								if (string.find(agent_contact, 'call_timeout') == nil) then
									--add the call_timeout and confirm
									agent_contact = first..','..confirm..',sip_h_caller_destination=${caller_destination},domain_name="..domain_name..",domain_uuid="..domain_uuid..",sip_h_caller_destination=${caller_destination},call_timeout='..agent_call_timeout..last;
								else
									--add confirm
									agent_contact = tmp_first..',domain_name="..domain_name..",domain_uuid="..domain_uuid..",sip_h_caller_destination=${caller_destination},'..confirm..tmp_last;
								end
							end
						end

				--build the xml string
					table.insert(xml, [[                            <agent ]]);
					table.insert(xml, [[                            	name="]]..agent_uuid..[[" ]]);
					table.insert(xml, [[                            	label="]]..agent_name..[[@]]..domain_name..[[" ]]);
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
			sql = "select t.domain_uuid, d.domain_name, t.call_center_agent_uuid, t.call_center_queue_uuid, q.queue_extension, t.tier_level, t.tier_position ";
			sql = sql .. "from v_call_center_tiers as t, v_domains as d, v_call_center_queues as q ";
			sql = sql .. "where d.domain_uuid = t.domain_uuid ";
			sql = sql .. "and t.call_center_queue_uuid = q.call_center_queue_uuid; ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			table.insert(xml, [[                    <tiers>]]);
			dbh:query(sql, function(row)
				--get the values from the database and set as variables
					domain_uuid = row.domain_uuid;
					domain_name = row.domain_name;
					agent_uuid = row.call_center_agent_uuid;
					queue_uuid = row.call_center_queue_uuid;
					queue_extension = row.queue_extension;
					tier_level = row.tier_level;
					tier_position = row.tier_position;
				--build the xml
					table.insert(xml, [[                            <tier ]]);
					table.insert(xml, [[                            	agent="]]..agent_uuid..[[" ]]);
					table.insert(xml, [[                            	queue="]]..queue_extension..[[@]]..domain_name..[[" ]]);
					table.insert(xml, [[                            	domain_name="]]..domain_name..[[" ]]);
					--table.insert(xml, [[                            	agent_name="]]..agent_name..[[" ]]);
					--table.insert(xml, [[                            	queue_name="]]..queue_name..[[" ]]);
					table.insert(xml, [[                            	level="]]..tier_level..[[" ]]);
					table.insert(xml, [[                            	position="]]..tier_position..[[" ]]);
					table.insert(xml, [[                            />]]);
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
			local ok, err = cache.set(cc_cache_key, XML_STRING, expire["callcenter"]);
			if debug["cache"] then
				if ok then
					freeswitch.consoleLog("notice", "[xml_handler] " .. cc_cache_key .. " stored in the cache\n");
				else
					freeswitch.consoleLog("warning", "[xml_handler] " .. cc_cache_key .. " can not be stored in the cache: " .. tostring(err) .. "\n");
				end
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. cc_cache_key .. " source: database\n");
			end
	else
		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. cc_cache_key .. " source: cache\n");
			end
	end --if XML_STRING

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .. "/callcenter.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
