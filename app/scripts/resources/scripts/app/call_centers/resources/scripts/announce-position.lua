-- callcenter-announce-position.lua
-- Announce queue position to a member in a given mod_callcenter queue.
-- Arguments are, in order: caller uuid, queue_extension, interval (in milliseconds).
-- Usage: <action application="set" data="result=${luarun(callcenter-announce-position.lua ${uuid} ${queue_uuid} 30000)}"/>
api = freeswitch.API()
caller_uuid = argv[1]
queue_uuid = argv[2]
mseconds = argv[3]

-- set default variables
-- max_digits = 15;
-- digit_timeout = 5000;
debug["sql"] = true;

-- general functions
require "resources.functions.trim";

-- connect to the database
local Database = require "resources.functions.database";
dbh = Database.new('system');

-- include json library
local json
if (debug["sql"]) then
    json = require "resources.functions.lunajson"
end

if caller_uuid == nil or queue_uuid == nil or mseconds == nil then
    return
end

-- Get the queue_extension
local sql = "SELECT c.queue_extension, c.queue_callback_profile, d.domain_name FROM v_call_center_queues c ";
sql = sql .. "INNER JOIN v_domains d ON c.domain_uuid = d.domain_uuid "
sql = sql .. "WHERE c.call_center_queue_uuid = :queue_uuid";
local params = {
    queue_uuid = queue_uuid
};
local queue_details = dbh:query(sql, params, function(row)
    queue_extension = row.queue_extension .. "@" .. row.domain_name;
    callback_profile = row.queue_callback_profile;
end);

while (true) do
    -- Pause between announcements
    freeswitch.msleep(mseconds)
    local position_table = {};
    members = api:executeString("callcenter_config queue list members " .. queue_extension)
    exists = false

    for line in members:gmatch("[^\r\n]+") do
        if (string.find(line, "Trying") ~= nil or string.find(line, "Waiting") ~= nil) then
            -- Members have a position when their state is Waiting or Trying
            if string.find(line, "instance_id") == nil then --This is not the header row
                local line_delimit = {}
                for w in (line .. "|"):gmatch("([^|]*)|") do
                    table.insert(line_delimit, w)
                end
                table.insert(position_table, line_delimit[#line_delimit]) -- Score is the last field
            end
            if string.find(line, caller_uuid, 1, true) ~= nil then
                -- Member still in queue, so script must continue
                exists = true                
                -- We can break out of for
                break
            end
        end
    end
    -- If member was not found in queue, or it's status is Aborted - terminate script
    if exists == false then
        return
    else
        -- Calculate position including callbacks
        local number_pending = 0;
        local sql = "SELECT count(*) FROM v_call_center_callbacks "
        sql = sql .. "WHERE call_center_queue_uuid = :queue_uuid "
        sql = sql .. "AND status = 'pending' "
        sql = sql .. "AND (:current_time - start_epoch) >= :base_score "
        local params = {queue_uuid = queue_uuid, current_time = os.time(), base_score = position_table[#position_table]};
        dbh:query(sql, params, function(row)
            number_pending = row.count;
        end);
        api:executeString("uuid_broadcast " .. caller_uuid .. " ivr/ivr-you_are_number.wav aleg")
        api:executeString("uuid_broadcast " .. caller_uuid .. " digits/" .. #position_table + number_pending .. ".wav aleg")
        -- TODO: Waiting for a representitive
        if (callback_profile ~= nil and #position_table + number_pending > 1) then
            api:executeString("uuid_broadcast " .. caller_uuid .. " ivr/ivr-if_you_would_like_us_to_call_back.wav aleg")
            api:executeString("uuid_broadcast " .. caller_uuid .. " digits/1.wav aleg")
        end
    end
end
