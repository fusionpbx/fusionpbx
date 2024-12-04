local number = argv[1]

--get the variables
if (session:ready()) then
    domain_uuid = session:getVariable("domain_uuid");
    -- caller_id_number = session:getVariable("caller_id_number") -- Retrieve the caller ID
    -- current_domain_code = session:getVariable("domain_name"):sub(1, 4) -- Extract the current domain code
    -- extension_uuid = session:getVariable("extension_uuid");
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


-- Check if the number is whitelisted
local whitelisted = is_whitelisted(number, domain_uuid)

-- Final decision based on both domains
if whitelisted then
    freeswitch.consoleLog("INFO", "[is_whitelisted] Call to " .. number .. " is allowed. \n")
    return "allowed"  -- Allow the call to proceed
else

    freeswitch.consoleLog("NOTICE", "[is_whitelisted] Call to " .. number .. " is not whitelisted in the current domain.\n")
    
    -- If session is active, play the rejection message for the current domain
    if session ~= nil and session:ready() then
        session:answer()
        session:execute("playback", "silence_stream://1000")
        session:streamFile("/usr/share/freeswitch/sounds/outbound_not_allowed.wav")
        session:sleep(1000) -- Wait for 1 second
        session:hangup("CALL_REJECTED")
    end

    return "not_whitelisted"
end