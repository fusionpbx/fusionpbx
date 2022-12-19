--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2022
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Joseph Nadiv <ynadiv@corpit.xyz>

api = freeswitch.API()
action = argv[1]
queue_uuid = argv[2]

if (action == nil) then
    return;
end

-- include config.lua
require "resources.functions.config";

-- load libraries
local Database = require "resources.functions.database";
dbh = Database.new('system');
local Settings = require "resources.functions.lazy_settings";
local file = require "resources.functions.file";

-- Initial callback request
if (action == "start") then
    cc_exit_key = session:getVariable("cc_exit_key");
    if cc_exit_key ~= "1" then
        -- Callback not requested
        return;
    end
    -- get the variables
domain_name = session:getVariable("domain_name");
caller_id_name = session:getVariable("caller_id_name");
caller_id_number = session:getVariable("caller_id_number");
domain_uuid = session:getVariable("domain_uuid");
uuid = session:getVariable("uuid");

-- get the sounds dir, language, dialect and voice
local sounds_dir = session:getVariable("sounds_dir");
local default_language = session:getVariable("default_language") or 'en';
local default_dialect = session:getVariable("default_dialect") or 'us';
local default_voice = session:getVariable("default_voice") or 'callie';

-- get the recordings settings
local settings = Settings.new(dbh, domain_name, domain_uuid);

-- set the storage type and path
storage_type = settings:get('recordings', 'storage_type', 'text') or '';
storage_path = settings:get('recordings', 'storage_path', 'text') or '';
if (storage_path ~= '') then
    storage_path = storage_path:gsub("${domain_name}", session:getVariable("domain_name"));
    storage_path = storage_path:gsub("${domain_uuid}", domain_uuid);
end
-- set the recordings directory
local recordings_dir = recordings_dir .. "/" .. domain_name;

-- Get when joined queue
local cc_queue_joined_epoch = session:getVariable("cc_queue_joined_epoch");

-- Get the callback_profile
local sql = "SELECT c.queue_extension, p.caller_id_number, p.caller_id_name, p.callback_dialplan, p.callback_request_prompt, "
sql = sql .. "p.callback_confirm_prompt, p.callback_force_cid, p.callback_retries, p.callback_timeout, p.callback_retry_delay "
sql = sql .. "FROM v_call_center_queues c INNER JOIN v_call_center_callback_profile p ON c.queue_callback_profile = p.id ";
sql = sql .. "WHERE c.call_center_queue_uuid = :queue_uuid";
local params = {
    queue_uuid = queue_uuid
};
dbh:query(sql, params, function(row)
    queue_extension = row.queue_extension;
    callback_cid_number = row.caller_id_number;
    callback_cid_name = row.caller_id_name;
    callback_dialplan = row.callback_dialplan;
    callback_request_prompt = row.callback_request_prompt;
    callback_confirm_prompt = row.callback_confirm_prompt;
    callback_force_cid = row.callback_force_cid;
    callback_retries = row.callback_retries;
    callback_timeout = row.callback_timeout;
    callback_retry_delay = row.callback_retry_delay;
end);

    local valid_callback = api:execute("regex", "m:|" .. caller_id_number .. "|" .. callback_dialplan);
    if valid_callback == "false" and callback_force_cid == "true" then
        -- We can't service you
        session:streamFile("ivr/ivr-please_check_number_try_again.wav")
        session:setVariable("cc_base_score", os.time() - cc_queue_joined_epoch);
        session:execute("transfer", queue_extension .. " XML " .. domain_name);
    end
    if (valid_callback == "true") then
        if (string.len(callback_request_prompt) > 0) then
            if (file_exists(recordings_dir .. "/" .. callback_request_prompt)) then
                dtmf_digits = session:playAndGetDigits(1, 1, 3, 3000, "#",
                recordings_dir .. "/" .. callback_request_prompt, "", "[12]");
            else
                dtmf_digits = session:playAndGetDigits(1, 1, 3, 3000, "#",
                callback_request_prompt, "", "[12]");
            end
        else
            session:streamFile("ivr/ivr-it_appears_that_your_phone_number_is.wav");
            session:say(caller_id_number, "en", "telephone_number", "iterated");
            session:streamFile("ivr/ivr-would_you_like_to_receive_a_call_at_this_number.wav");
            dtmf_digits = session:playAndGetDigits(1, 1, 3, 3000, "#",
                sounds_dir .. "/" .. default_language .. "/" .. default_dialect .. "/" .. default_voice ..
                "/ivr/ivr-one_yes_two_no.wav", "", "[12]");
        end
        if ((tonumber(dtmf_digits) == nil) or callback_force_cid == "true" and dtmf_digits == "2") then
            session:setVariable("cc_base_score", os.time() - cc_queue_joined_epoch);
            session:transfer(queue_extension, "XML", domain_name);
        end
    end

    if (callback_force_cid == "false" and dtmf_digits == "2") or valid_callback == "false" then
        invalid = 0;
        local accepted = false
        while (session:ready() and invalid < 3 and accepted == false) do
            caller_id_number = session:playAndGetDigits(10, 14, 3, 3000, "#", "ivr/ivr-please_enter_the_number_where_we_can_reach_you.wav", "", "\\d+");
            valid_callback = api:execute("regex", "m:|" .. caller_id_number .. "|" .. callback_dialplan);
            if (valid_callback == "true") then
                session:say(caller_id_number, "en", "telephone_number", "iterated");
                -- To accept this number press 1, to enter a different number press 2
                dtmf_digits = session:playAndGetDigits(1, 1, 3, 3000, "#",
                sounds_dir .. "/" .. default_language .. "/" .. default_dialect .. "/" .. default_voice ..
                    "/ivr/ivr-accept_reject.wav", "", "[12]");
                if dtmf_digits == "1" then
                    accepted = true
                end
            else
                session:streamFile("ivr/ivr-please_check_number_try_again.wav")
                invalid = invalid + 1;
                if invalid == 3 then
                    session:setVariable("cc_base_score", os.time() - cc_queue_joined_epoch);
                    session:execute("transfer", queue_extension .. " XML " .. domain_name);
                    return;
                end
            end
        end
    end

    -- Save the confirm prompt since we can't get it later
    if (string.len(callback_confirm_prompt) > 0) then
        if (file_exists(recordings_dir .. "/" .. callback_confirm_prompt)) then
            confirm_prompt_path = recordings_dir .. "/" .. callback_request_prompt;
        else
            confirm_prompt_path = callback_request_prompt;
        end
    end

    if (dtmf_digits ~= nil and dtmf_digits == "1") then
        sql = "INSERT INTO v_call_center_callbacks (call_center_queue_uuid, domain_uuid, ";
        sql = sql .. "call_uuid, start_epoch, caller_id_name, caller_id_number, retry_count, ";
        sql = sql .. "next_retry_epoch, status) ";
        sql = sql .. "SELECT :queue_uuid, :domain_uuid, :uuid, :cc_queue_joined_epoch, :caller_id_name, "
        sql = sql .. ":caller_id_number, 0, 0, 'pending' ";
        -- Cannot request another callback in the same queue
        sql = sql .. "WHERE NOT EXISTS (SELECT caller_id_number, call_center_queue_uuid FROM v_call_center_callbacks "
        sql = sql .. "WHERE caller_id_number = :caller_id_number AND call_center_queue_uuid = :queue_uuid and status = 'pending') "
        local params = {
            queue_uuid = queue_uuid,
            domain_uuid = domain_uuid,
            uuid = uuid,
            cc_queue_joined_epoch = cc_queue_joined_epoch,
            caller_id_name = caller_id_name,
            caller_id_number = caller_id_number,
            confirm_prompt = confirm_prompt_path
        }
        dbh:query(sql, params);
        session:streamFile("ivr/ivr-we_will_return_your_call_at_this_number.wav");
        session:hangup();
    else
        session:setVariable("cc_base_score", os.time() - cc_queue_joined_epoch);
        session:execute("transfer", queue_extension .. " XML " .. domain_name);
    end
end
-- digit = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-accept_reject_voicemail.wav", "", "\\d+")
-- cc_queue_canceled_epoch

if action == "service" then

    local function start_queue_callback(callback)
        -- Originate the call - get outbound dialplan
        local sql = [[select * from v_dialplans as d, v_dialplan_details as s
        where (d.domain_uuid = :domain_uuid or d.domain_uuid is null)
        and d.app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'
        and d.dialplan_enabled = 'true'
        and d.dialplan_uuid = s.dialplan_uuid
        order by
        d.dialplan_order asc,
        d.dialplan_name asc,
        d.dialplan_uuid asc,
        s.dialplan_detail_group asc,
        CASE s.dialplan_detail_tag
        WHEN 'condition' THEN 1
        WHEN 'action' THEN 2
        WHEN 'anti-action' THEN 3
        ELSE 100 END,
        s.dialplan_detail_order asc ]]
        local params = {domain_uuid = callback.domain_uuid};
        if (debug["sql"]) then
            freeswitch.consoleLog("notice", "[queue_callback] sql for dialplans:" .. sql .. "; params: " .. json.encode(params) .. "\n");
        end
        dialplans = {};
        x = 1;
        dbh:query(sql, params, function(row)
            dialplans[x] = row;
            x = x + 1;
        end);

        y = 0;
        previous_dialplan_uuid = '';
        for k, r in pairs(dialplans) do
            if (y > 0) then
                if (previous_dialplan_uuid ~= r.dialplan_uuid) then
                    regex_match = false;
                    bridge_match = false;
                    square = square .. "]";
                    y = 0;
                end
            end
            if (r.dialplan_detail_tag == "condition") then
                if (r.dialplan_detail_type == "destination_number") then
                    if (api:execute("regex", "m:~"..callback.caller_id_number.."~"..r.dialplan_detail_data) == "true") then
                        --get the regex result
                        destination_result = trim(api:execute("regex", "m:~"..callback.caller_id_number.."~"..r.dialplan_detail_data.."~$1"));
                        regex_match = true
                    end
                end
            end
            if (r.dialplan_detail_tag == "action") then
                if (regex_match) then
                    --replace $1
                    dialplan_detail_data = r.dialplan_detail_data:gsub("$1", destination_result);
                    --if the session is set then process the actions
                    if (y == 0) then
                        square = "[direction=outbound,origination_caller_id_number="..callback_cid_number..",outbound_caller_id_number="..callback_cid_number..",call_timeout=" .. callback_timeout ..",domain_name="..domain_name..",sip_invite_domain="..domain_name..",domain_name="..domain_name..",domain="..domain_name..",domain_uuid="..domain_uuid..",";
                    end
                    if (r.dialplan_detail_type == "set") then
                        if (dialplan_detail_data == "sip_h_X-accountcode=${accountcode}") then
                            square = square .. "sip_h_X-accountcode=0,";
                        elseif (dialplan_detail_data == "effective_caller_id_name=${outbound_caller_id_name}") then
                        elseif (dialplan_detail_data == "effective_caller_id_number=${outbound_caller_id_number}") then
                        else
                            square = square .. dialplan_detail_data..",";
                        end
                    elseif (r.dialplan_detail_type == "bridge") then
                        if (bridge_match) then
                            dial_string = dial_string .. "," .. square .."]"..dialplan_detail_data;
                            square = "[";
                        else
                            dial_string = square .."]"..dialplan_detail_data;
                        end
                        bridge_match = true;
                    end
                y = y + 1;
                end
            end
            previous_dialplan_uuid = r.dialplan_uuid;
        end

        freeswitch.consoleLog("info", "[queue_callback] dial_string " .. dial_string .. "\n");

        t_started = os.time();
        session1 = freeswitch.Session(dial_string);
        session1:execute("export", "domain_uuid="..domain_uuid);
        freeswitch.consoleLog("info", "[queue_callback] calling " .. callback.caller_id_number .. "\n");
        freeswitch.msleep(2000);

        while (session1:ready() and not session1:answered()) do
            if os.time() > t_started + callback_timeout then
                freeswitch.consoleLog("info", "[queue_callback] timed out for " .. callback.caller_id_number .. "\n");
                -- Table is updated later in the next else block
                session1:hangup();
                break;
            else
                --freeswitch.consoleLog("info", "[disa.callback] session is not yet answered for " .. callback_cid_number .. "\n");
                freeswitch.msleep(500);
            end
        end

        -- Play confirmation prompt
        if session1:ready() and session1:answered() then
            session1:answer();

            -- get the sounds dir, language, dialect and voice
            local sounds_dir = session1:getVariable("sounds_dir");
            local default_language = session1:getVariable("default_language") or 'en';
            local default_dialect = session1:getVariable("default_dialect") or 'us';
            local default_voice = session1:getVariable("default_voice") or 'callie';

            -- get the recordings settings
            local settings = Settings.new(dbh, domain_name, domain_uuid);

            -- set the storage type and path
            storage_type = settings:get('recordings', 'storage_type', 'text') or '';
            storage_path = settings:get('recordings', 'storage_path', 'text') or '';
            if (storage_path ~= '') then
                storage_path = storage_path:gsub("${domain_name}", session:getVariable("domain_name"));
                storage_path = storage_path:gsub("${domain_uuid}", domain_uuid);
            end
            -- set the recordings directory
            local recordings_dir = recordings_dir .. "/" .. domain_name;

            if (string.len(callback_confirm_prompt) > 0) then
                if (file_exists(recordings_dir .. "/" .. callback_confirm_prompt)) then
                    callback_confirm_prompt = recordings_dir .. "/" .. callback_confirm_prompt;
                end
            else
                session1:streamFile("ivr/ivr-this_is_a_call_from.wav")
                session1:say(callback_cid_number, "en", "telephone_number", "iterated");
                callback_confirm_prompt = sounds_dir .. "/" .. default_language .. "/" .. default_dialect .. "/" .. default_voice ..
                    "/ivr/ivr-accept_reject.wav"
            end
            local dtmf_digits = session1:playAndGetDigits(1, 1, 3, 3000, "#", callback_confirm_prompt, "", "[12]");
            if dtmf_digits == "1" then
                -- Update table with complete status
                local sql = "UPDATE v_call_center_callbacks SET status = 'complete', completed_epoch = :now ";
                sql = sql .. "WHERE call_uuid = :call_uuid"
                dbh:query(sql, {now = os.time(), call_uuid = callback.call_uuid})
                -- Check if still listed as abandoned, if so don't modify base score
                -- Above probably not necessary: https://github.com/signalwire/freeswitch/blob/master/src/mod/applications/mod_callcenter/mod_callcenter.c#L3108
                -- Join to queue with correct base score
                session1:setVariable("cc_base_score", os.time() - callback.start_epoch);
                session1:transfer(queue_extension, "XML", domain_name);
            elseif dtmf_digits == "2" then
                -- Update table with declined status
                local sql = "UPDATE v_call_center_callbacks SET status = 'declined', completed_epoch = :now ";
                sql = sql .. "WHERE call_uuid = :call_uuid"
                dbh:query(sql, {now = os.time(), call_uuid = callback.call_uuid})
                session1:hangup();
            else
                if callback.retry_count < callback_retries then
                    local sql = "UPDATE v_call_center_callbacks SET retry_count = :retry_count, completed_epoch = :now, ";
                    sql = sql .. "next_retry_epoch = :next_retry WHERE call_uuid = :call_uuid"
                    dbh:query(sql, {now = os.time(), 
                                    retry_count = callback.retry_count + 1,
                                    next_retry = os.time() + callback_retry_delay,
                                    call_uuid = callback.call_uuid});
                else
                    local sql = "UPDATE v_call_center_callbacks SET status = 'timeout', completed_epoch = :now ";
                    sql = sql .. "WHERE call_uuid = :call_uuid"
                    dbh:query(sql, {now = os.time(), call_uuid = callback.call_uuid})
                end
            session1:hangup();
            end
        else
            -- Update table that timeout
            if callback.retry_count < callback_retries then
                local sql = "UPDATE v_call_center_callbacks SET retry_count = :retry_count, completed_epoch = :now, ";
                    sql = sql .. "next_retry_epoch = :next_retry WHERE call_uuid = :call_uuid"
                    dbh:query(sql, {now = os.time(), 
                                    retry_count = callback.retry_count + 1,
                                    next_retry = os.time() + callback_retry_delay,
                                    call_uuid = callback.call_uuid});
            else
                local sql = "UPDATE v_call_center_callbacks SET status = 'timeout', completed_epoch = :now ";
                sql = sql .. "WHERE call_uuid = :call_uuid"
                dbh:query(sql, {now = os.time(), call_uuid = callback.call_uuid})
            end
            session1:hangup();
        end
    end

    --define the run file
	run_file = scripts_dir .. "/run/queue-callback-daemon.tmp";

    --used to stop the lua service
	local file = assert(io.open(run_file, "w"));
	file:write("remove this file to stop the script");


    while(true) do

        --exit the loop when the file does not exist
        if (not file_exists(run_file)) then
            freeswitch.consoleLog("NOTICE", "queue_callback" .. run_file.." not found\n");
            break;
        end

        -- Get longest pending callback for each queue uuid
        pending_callbacks = {};
        local sql = "SELECT DISTINCT ON (call_center_queue_uuid) * FROM v_call_center_callbacks ";
        sql = sql .. "WHERE status = 'pending' AND next_retry_epoch < :now ORDER BY call_center_queue_uuid, start_epoch ASC";
        dbh:query(sql, {now = os.time()}, function(row)
            table.insert(pending_callbacks, row);
        end);
        -- For each
        for i, callback in ipairs(pending_callbacks) do
            -- get queue details
            -- Get the callback_profile
            local sql = "SELECT c.queue_extension, d.domain_name, d.domain_uuid, p.caller_id_number, p.caller_id_name, "
            sql = sql .. "p. callback_confirm_prompt, p.callback_retries, p.callback_timeout, p.callback_retry_delay "
            sql = sql .. "FROM v_call_center_queues c INNER JOIN v_call_center_callback_profile p ON c.queue_callback_profile = p.id ";
            sql = sql .. "INNER JOIN v_domains d ON p.domain_uuid = d.domain_uuid "
            sql = sql .. "WHERE c.call_center_queue_uuid = :queue_uuid";
            local params = {
                queue_uuid = callback.call_center_queue_uuid
            };
            dbh:query(sql, params, function(row)
                queue_extension = row.queue_extension;
                callback_cid_number = row.caller_id_number;
                callback_cid_name = row.caller_id_name;
                callback_confirm_prompt = row.callback_confirm_prompt;
                callback_retries = row.callback_retries;
                callback_timeout = row.callback_timeout;
                callback_retry_delay = row.callback_retry_delay;
                domain_name = row.domain_name;
                domain_uuid = row.domain_uuid;
            end);

            -- Check member list of queue
            local cmd = "callcenter_config queue list members " .. queue_extension .. "@" .. domain_name;
            freeswitch.consoleLog("NOTICE", "queue_callback member cmd " .. cmd);
            members = trim(api:executeString(cmd));
            -- Check longest hold time and compare to longest callback
            for count, line in members:gmatch("[^\r\n]+") do
                if line == nil then
                    start_queue_callback(callback);
                    break;
                end
                if (string.find(line, "Trying") ~= nil or string.find(line, "Waiting") ~= nil) then
                -- Members have a position when their state is Waiting or Trying
                    local line_delimit = {}
                    for w in (line .. "|"):gmatch("([^|]*)|") do
                        table.insert(line_delimit, w)
                    end
                    if line_delimit[#line_delimit] < (os.time() - callback.start_epoch) then
                    -- This callback is next in line
                        start_queue_callback(callback);
                    end
                    -- we need to break here otherwise we always get callback if anyone is holding less
                    break;
                elseif count == #members:gmatch("[^\r\n]+") then
                    -- The queue is empty
                    start_queue_callback(callback);
                end
            end
        end
    freeswitch.msleep(20000);
    end
end
