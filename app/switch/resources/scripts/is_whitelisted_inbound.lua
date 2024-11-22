local number = argv[1]  -- Number to be dialed

--get the variables
if (session:ready()) then
    domain_uuid = session:getVariable("domain_uuid");
    -- extension_uuid = session:getVariable("extension_uuid");
end

if (session:ready()) then
    freeswitch.consoleLog("INFO", "[is_whitelisted] Getting Caller ID info \n")
    caller_id_number = session:getVariable("caller_id_number");
    freeswitch.consoleLog("INFO", "[is_whitelisted] Caller ID is " .. caller_id_number .. " \n")

    -- extension_uuid = session:getVariable("extension_uuid");
end


--connect to the database
local Database = require "resources.functions.database";
dbh = Database.new('system');

-- Function to check if a number is whitelisted
function is_whitelisted(number)
    local is_allowed = false
    -- local sql = string.format("SELECT COUNT(*) AS count FROM whitelisted_numbers WHERE number = '%s' AND domain_uuid = '%s'", number, domain_uuid)

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

-- Check whitelist
if is_whitelisted(number) then
    freeswitch.consoleLog("INFO", "[is_whitelisted] Call from " .. number .. " is allowed. \n")
    return "allowed"  -- Allow the call to proceed
else
    freeswitch.consoleLog("NOTICE", "[is_whitelisted] " .. number .. " is not in the whitelist. Call is rejected \n")
    
    -- If session is active, hang up
    if session ~= nil and session:ready() then

        -- Answer the call to ensure a CDR is generated
        session:answer()

        session:execute("playback", "silence_stream://1000")

        session:streamFile("/usr/share/freeswitch/sounds/inbound_not_allowed.wav")

        session:sleep(1000) -- Wait for 1 second (1000 milliseconds)

        session:hangup("CALL_REJECTED")  -- Hang up with the specified cause
    end

    return "not_whitelisted"
end