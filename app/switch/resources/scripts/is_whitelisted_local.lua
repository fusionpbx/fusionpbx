local destination_domain_code = argv[1]
local destination_extension = argv[2]

local number = destination_domain_code .. destination_extension

--get the variables
if (session:ready()) then
    domain_uuid = session:getVariable("domain_uuid");
    caller_id_number = session:getVariable("caller_id_number") -- Retrieve the caller ID
    local domain_name = session:getVariable("domain_name")
    local dot_position = domain_name:find("%.") -- Find the position of the first dot
    if dot_position then
        current_domain_code = domain_name:sub(1, dot_position - 1) -- Extract everything before the dot
    else
        current_domain_code = domain_name -- Fallback in case there's no dot
    end
    freeswitch.consoleLog("INFO", "[inter_tenant_dialing] Current domain: " .. current_domain_code .. "\n")

end


--connect to the database
local Database = require "resources.functions.database";
dbh = Database.new('system');

-- Function to check if a number is whitelisted
function is_whitelisted(number, domain_uuid)
    local is_allowed = false

    dbh = Database.new('system');
     -- SQL query to handle both full-length numbers and extensions
     local sql = string.format([[
        SELECT COUNT(*) AS count 
        FROM whitelisted_numbers 
        WHERE 
            (
                -- Full-length numbers: remove leading '1' for comparison
                (LENGTH(number) > 9 AND 
                 CASE 
                     WHEN number LIKE '1%%' THEN SUBSTR(number, 2) 
                     ELSE number 
                 END = '%s')
                OR 
                -- Short numbers (extensions): match exactly
                (LENGTH(number) <= 9 AND number = '%s')
            )
        AND domain_uuid = '%s'
    ]], number, number, domain_uuid)

    
    dbh:query(sql, function(row)
        if row["count"] == "1" then
            is_allowed = true
        end
    end)

    -- Close the database connection
    dbh:release()

    return is_allowed
end


-- Function to lookup domain UUID by domain code (first 4 digits)
function lookup_domain_uuid_by_code(domain_code)

    local domain_uuid = nil -- Initialize domain_uuid
    local domain_name = nil -- Initialize domain_name

    local sql = string.format([[
        SELECT domain_uuid, domain_name 
        FROM v_domains 
        WHERE domain_name LIKE '%s.%%'
    ]], domain_code) -- Query for domain_name starting with the domain_code

    -- Execute the query and process the result
    dbh:query(sql, function(row)
        domain_uuid = row["domain_uuid"]
        domain_name = row["domain_name"]
        freeswitch.consoleLog("INFO", "[inter_tenant_dialing] Found domain: " .. domain_name .. " with UUID: " .. domain_uuid .. "\n")
    end)

    -- Close the database connection
    dbh:release()

    -- Check if the domain_uuid was found
    if not domain_uuid then
        freeswitch.consoleLog("ERROR", "[inter_tenant_dialing] Domain not found for code: " .. domain_code .. "\n")
    end

    return domain_uuid, domain_name
end

local destination_domain_uuid, destination_domain_name = lookup_domain_uuid_by_code(destination_domain_code) -- Fetch domain UUID and name

-- Create the combination for the destination domain check
local caller_id = current_domain_code .. caller_id_number

-- Check whitelist in both current and destination domains
local is_allowed_current_domain = is_whitelisted(number, domain_uuid)
local is_allowed_destination_domain = destination_domain_uuid and is_whitelisted(caller_id, destination_domain_uuid)

-- Final decision based on both domains
if is_allowed_current_domain and is_allowed_destination_domain then
    freeswitch.consoleLog("INFO", "[is_whitelisted] Call to " .. number .. " is allowed. \n")
    return "allowed"  -- Allow the call to proceed
else
    -- Determine the reason for rejection
    if not is_allowed_current_domain then
        freeswitch.consoleLog("NOTICE", "[is_whitelisted] Call to " .. number .. " is not whitelisted in the current domain.\n")
        
        -- If session is active, play the rejection message for the current domain
        if session ~= nil and session:ready() then
            session:answer()
            session:execute("playback", "silence_stream://1000")
            session:streamFile("/usr/share/freeswitch/sounds/outbound_not_allowed.wav")
            session:sleep(1000) -- Wait for 1 second
            session:hangup("CALL_REJECTED")
        end
    elseif not is_allowed_destination_domain then
        freeswitch.consoleLog("NOTICE", "[is_whitelisted] Call to " .. number .. ". Your caller ID " .. caller_id .. " is not whitelisted in the destination domain.\n")
        
        -- If session is active, play the rejection message for the destination domain
        if session ~= nil and session:ready() then
            session:answer()
            session:execute("playback", "silence_stream://1000")
            session:streamFile("/usr/share/freeswitch/sounds/inbound_not_allowed.wav")
            session:sleep(1000) -- Wait for 1 second
            session:hangup("CALL_REJECTED")
        end
    end

    return "not_whitelisted"
end