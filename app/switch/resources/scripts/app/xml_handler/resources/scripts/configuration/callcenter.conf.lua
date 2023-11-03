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

--include xml library
	local Xml = require "resources.functions.xml";

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
			local xml = Xml:new();
			xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			xml:append([[<document type="freeswitch/xml">]]);
			xml:append([[    <section name="configuration">]]);
			xml:append([[            <configuration name="callcenter.conf" description="Call Center">]]);
			xml:append([[                    <settings>]]);
			if #dsn_callcenter > 0 then
				xml:append([[                            <param name="odbc-dsn" value="]] .. xml.sanitize(dsn_callcenter) .. [["/>]]);
			elseif #dsn > 0 then
				xml:append([[                            <param name="odbc-dsn" value="]] .. xml.sanitize(database["switch"]) .. [["/>]]);
			end
			xml:append([[                          <param name="cc-instance-id" value="]] .. xml.sanitize(hostname) .. [["/>]]);
			-- xml:append([[                          <param name="dbname" value="]] .. xml.sanitize(database_dir) .. [[/call_center.db"/>]]);
			xml:append([[                    </settings>]]);

		--write the queues
			xml:append([[                    <queues>]]);
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

				--sanitize the queue_record_template and allow specific variables
					queue_record_template = xml.sanitize(queue_record_template);
					queue_record_template = string.gsub(queue_record_template, "{strftime", "${strftime");
					queue_record_template = string.gsub(queue_record_template, "{uuid}", "${uuid}");
					queue_record_template = string.gsub(queue_record_template, "{record_ext}", "${record_ext}");

				--start the xml
					xml:append([[                            <queue name="]] .. xml.sanitize(queue_extension) .. [[@]] .. xml.sanitize(domain_name) .. [[" label="]] .. xml.sanitize(queue_name) .. [[@]] .. xml.sanitize(domain_name) .. [[">]]);
					xml:append([[                                    <param name="strategy" value="]] .. xml.sanitize(queue_strategy) .. [["/>]]);
				--set ringback
					queue_ringback = format_ringback(queue_moh_sound);
					xml:append([[                                    <param name="moh-sound" value="]] .. xml.sanitize(queue_ringback) .. [["/>]]);
					if (queue_record_template ~= nil) then
						xml:append([[                                    <param name="record-template" value="]] .. queue_record_template .. [["/>]]);
					end
					if (queue_time_base_score ~= nil) then
						xml:append([[                                    <param name="time-base-score" value="]] .. xml.sanitize(queue_time_base_score) .. [["/>]]);
					end
					if (queue_max_wait_time_with_no_agent ~= nil) then
						xml:append([[                                    <param name="max-wait-time" value="]] .. xml.sanitize(queue_max_wait_time) .. [["/>]]);
					end
					if (queue_max_wait_time_with_no_agent ~= nil) then
						xml:append([[                                    <param name="max-wait-time-with-no-agent" value="]] .. xml.sanitize(queue_max_wait_time_with_no_agent) .. [["/>]]);
					end
					if (queue_max_wait_time_with_no_agent_time_reached ~= nil) then
						xml:append([[                                    <param name="max-wait-time-with-no-agent-time-reached" value="]] .. xml.sanitize(queue_max_wait_time_with_no_agent_time_reached) .. [["/>]]);
					end
					if (queue_tier_rules_apply ~= nil) then
						xml:append([[                                    <param name="tier-rules-apply" value="]] .. xml.sanitize(queue_tier_rules_apply) .. [["/>]]);
					end
					if (queue_tier_rule_wait_second ~= nil) then
						xml:append([[                                    <param name="tier-rule-wait-second" value="]] .. xml.sanitize(queue_tier_rule_wait_second) .. [["/>]]);
					end
					if (queue_tier_rule_wait_multiply_level ~= nil) then
						xml:append([[                                    <param name="tier-rule-wait-multiply-level" value="]] .. xml.sanitize(queue_tier_rule_wait_multiply_level) .. [["/>]]);
					end
					if (queue_tier_rule_no_agent_no_wait ~= nil) then
						xml:append([[                                    <param name="tier-rule-no-agent-no-wait" value="]] .. xml.sanitize(queue_tier_rule_no_agent_no_wait) .. [["/>]]);
					end
					if (queue_discard_abandoned_after ~= nil) then
						xml:append([[                                    <param name="discard-abandoned-after" value="]] .. xml.sanitize(queue_discard_abandoned_after) .. [["/>]]);
					end
					if (queue_abandoned_resume_allowed ~= nil) then
						xml:append([[                                    <param name="abandoned-resume-allowed" value="]] .. xml.sanitize(queue_abandoned_resume_allowed) .. [["/>]]);
					end
					if (queue_announce_sound ~= nil) then
						xml:append([[                                    <param name="announce-sound" value="]] .. xml.sanitize(queue_announce_sound) .. [["/>]]);
					end
					if (queue_announce_frequency ~= nil) then
						xml:append([[                                    <param name="announce-frequency" value="]] .. xml.sanitize(queue_announce_frequency) .. [["/>]]);
					end
					xml:append([[                            </queue>]]);

				--increment the value of x
					x = x + 1;
			end)
			xml:append([[                    </queues>]]);

		--get the agents
			xml:append([[                    <agents>]]);
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

				--sanitize the agent_contact and allow specific variables
					agent_contact = xml.sanitize(agent_contact);
					agent_contact = string.gsub(agent_contact, "{caller_destination}", "${caller_destination}");
					agent_contact = string.gsub(agent_contact, "{strftime", "${strftime");
					agent_contact = string.gsub(agent_contact, "{uuid}", "${uuid}");
					agent_contact = string.gsub(agent_contact, "{record_ext}", "${record_ext}");

				--build the xml string
					xml:append([[                            <agent ]]);
					xml:append([[                            	name="]] .. xml.sanitize(agent_uuid) .. [[" ]]);
					xml:append([[                            	label="]] .. xml.sanitize(agent_name) .. [[@]] .. xml.sanitize(domain_name) .. [[" ]]);
					xml:append([[                            	type="]] .. xml.sanitize(agent_type) .. [[" ]]);
					xml:append([[                            	contact="]] .. agent_contact .. [[" ]]);
					xml:append([[                            	status="]] .. xml.sanitize(agent_status) .. [[" ]]);
					if (agent_no_answer_delay_time ~= nil) then
						xml:append([[                            	no-answer-delay-time="]] .. xml.sanitize(agent_no_answer_delay_time) .. [[" ]]);
					end
					if (agent_max_no_answer ~= nil) then
						xml:append([[                            	max-no-answer="]] .. xml.sanitize(agent_max_no_answer) .. [[" ]]);
					end
					if (agent_wrap_up_time ~= nil) then
						xml:append([[                            	wrap-up-time="]] .. xml.sanitize(agent_wrap_up_time) .. [[" ]]);
					end
					if (agent_reject_delay_time ~= nil) then
						xml:append([[                            	reject-delay-time="]] .. xml.sanitize(agent_reject_delay_time) .. [[" ]]);
					end
					if (agent_busy_delay_time ~= nil) then
						xml:append([[                            	busy-delay-time="]] .. xml.sanitize(agent_busy_delay_time) .. [[" ]]);
					end
					xml:append([[                            	/>]]);
			end)
			xml:append([[                    </agents>]]);

		--get the tiers
			sql = "select t.domain_uuid, d.domain_name, t.call_center_agent_uuid, t.call_center_queue_uuid, q.queue_extension, t.tier_level, t.tier_position ";
			sql = sql .. "from v_call_center_tiers as t, v_domains as d, v_call_center_queues as q ";
			sql = sql .. "where d.domain_uuid = t.domain_uuid ";
			sql = sql .. "and t.call_center_queue_uuid = q.call_center_queue_uuid; ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			xml:append([[                    <tiers>]]);
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
					xml:append([[                            <tier ]]);
					xml:append([[                            	agent="]] .. xml.sanitize(agent_uuid) .. [[" ]]);
					xml:append([[                            	queue="]] .. xml.sanitize(queue_extension) .. [[@]] .. xml.sanitize(domain_name) .. [[" ]]);
					xml:append([[                            	domain_name="]] .. xml.sanitize(domain_name) .. [[" ]]);
					--xml:append([[                            	agent_name="]] .. xml.sanitize(agent_name) .. [[" ]]);
					--xml:append([[                            	queue_name="]] .. xml.sanitize(queue_name) .. [[" ]]);
					xml:append([[                            	level="]] .. xml.sanitize(tier_level) .. [[" ]]);
					xml:append([[                            	position="]] .. xml.sanitize(tier_position) .. [[" ]]);
					xml:append([[                            />]]);
			end)
			xml:append([[                    </tiers>]]);

		--close the extension tag if it was left open
			xml:append([[            </configuration>]]);
			xml:append([[    </section>]]);
			xml:append([[</document>]]);
			XML_STRING = xml:build();
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
