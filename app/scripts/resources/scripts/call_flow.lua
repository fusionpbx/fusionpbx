--	call_flow.lua
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

--set the variables
	max_tries = "3";
	digit_timeout = "5000";

--include config.lua
	require "resources.functions.config";

--create logger object
	log = require "resources.functions.log".call_flow

--additional includes
	local presence_in = require "resources.functions.presence_in"
	local Database    = require "resources.functions.database"
	local play_file   = require "resources.functions.play_file"

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--connect to the database
	local dbh = Database.new('system');

--get the variables
	if not session:ready() then return end

	local domain_name = session:getVariable("domain_name");
	local domain_uuid = session:getVariable("domain_uuid");
	local call_flow_uuid = session:getVariable("call_flow_uuid");
	local feature_code = session:getVariable("feature_code");

	if not call_flow_uuid then
		log.warning('Can not get call flow uuid')
		return
	end

--get the call flow details
	local sql = "SELECT * FROM v_call_flows where call_flow_uuid = :call_flow_uuid"
		-- .. "and call_flow_enabled = 'true'"
	local params = {call_flow_uuid = call_flow_uuid};
	--log.notice("SQL: %s", sql);
	dbh:query(sql, params, function(row)
		call_flow_name = row.call_flow_name;
		call_flow_extension = row.call_flow_extension;
		call_flow_feature_code = row.call_flow_feature_code;
		--call_flow_context = row.call_flow_context;
		call_flow_status = row.call_flow_status;
		pin_number = row.call_flow_pin_number;
		call_flow_label = row.call_flow_label;
		call_flow_alternate_label = row.call_flow_alternate_label;
		call_flow_sound = row.call_flow_sound or '';
		call_flow_alternate_sound = row.call_flow_alternate_sound or '';

		if #call_flow_status == 0 then
			call_flow_status = "true";
		end
		if call_flow_status == "true" then
			app = row.call_flow_app;
			data = row.call_flow_data
		else
			app = row.call_flow_alternate_app;
			data = row.call_flow_alternate_data
		end
	end);

--if feature code toggle the status or send to the destination
	if (feature_code == "true") then
		--if the pin number is provided then require it
			if (session:ready()) then
				if #pin_number > 0 then
					local min_digits = #pin_number;
					local max_digits = #pin_number+1;
					session:answer();
					local digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
					if digits ~= pin_number then
						session:streamFile("phrase:voicemail_fail_auth:#");
						session:hangup("NORMAL_CLEARING");
						return;
					end
				end
			end

		--feature code - toggle the status
			local toggle = (call_flow_status == "true") and "false" or "true"

		-- turn the lamp
			presence_in.turn_lamp( toggle == "false",
				call_flow_feature_code.."@"..domain_name,
				call_flow_uuid
			);
			if string.find(call_flow_feature_code, 'flow+', nil, true) ~= 1 then
				presence_in.turn_lamp( toggle == "false",
					'flow+'..call_flow_feature_code.."@"..domain_name,
					call_flow_uuid
				);
			end

		--active label
			local active_flow_label = (toggle == "true") and call_flow_label or call_flow_alternate_label

		--play info message
			local audio_file = (toggle == "true") and call_flow_sound or call_flow_alternate_sound

		--show in the console
			log.noticef("label=%s,status=%s,uuid=%s,audio=%s", active_flow_label, toggle, call_flow_uuid, audio_file)

		--store in database
			dbh:query("UPDATE v_call_flows SET call_flow_status = :toggle WHERE call_flow_uuid = :call_flow_uuid", {
				toggle = toggle, call_flow_uuid = call_flow_uuid
			});

		--answer
			if (session:ready()) then
				session:answer();
			end

		--display label on Phone (if support)
			if (session:ready()) then
				if #active_flow_label > 0 then
					session:sleep(1000);
					local api = freeswitch.API();
					local reply = api:executeString("uuid_display "..session:get_uuid().." "..active_flow_label);
				end
			end

		--play the audio fil or tone
			if (session:ready()) then
				if #audio_file > 0 then
					session:sleep(1000);
					play_file(dbh, domain_name, domain_uuid, audio_file)
					session:sleep(1000);
				else
					session:sleep(2000);
					audio_file = "tone_stream://%(200,0,500,600,700)"
				end
			end

		--hangup the call
			if (session:ready()) then
				session:hangup();
			end
	else
		--send to the log
			log.notice("execute " .. app .. " " .. data);

		--execute the application
			if (session:ready()) then
				session:execute(app, data);
			end

		--timeout application
			--if (not session:answered()) then
			--	session:execute(ring_group_timeout_app, ring_group_timeout_data);
			--end
	end
