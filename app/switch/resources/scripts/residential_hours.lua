-- Retrieve variables passed from the dial plan
local local_wday = tonumber(session:getVariable("local_weekday"))  -- e.g., 1-7
local local_hour = tonumber(session:getVariable("local_hour"))  -- e.g., 17
local local_minute = tonumber(session:getVariable("local_minute"))  -- e.g., 30
local wday = session:getVariable("weekday")  -- e.g., "1-7"
local time_of_day = session:getVariable("time_of_day")  -- e.g., "17:00-22:00"
local destination_number = session:getVariable("destination_number") 
local domain_name = session:getVariable("domain_name") 
local call_direction = session:getVariable("call_direction")
freeswitch.consoleLog("INFO", "[residential_hours] Destination Number: " .. destination_number ..".\n")

-- Check if the call is outbound and the destination number is either 911 or 933
if call_direction == "outbound" and (destination_number == "911" or destination_number == "933") then
    freeswitch.consoleLog("INFO", "[residential_hours] Outbound call to emergency number are always allowed. Exiting script.\n")
    return
end

local user_exists = session:getVariable("user_exists") 
freeswitch.consoleLog("INFO", "[residential_hours] User exists: " .. user_exists ..".\n")


-- Log retrieved variables for debugging
freeswitch.consoleLog("INFO", "[residential_hours] Retrieved variables - local_wday: " .. tostring(local_wday) .. ", local_hour: " .. tostring(local_hour) .. ", local_minute: " .. tostring(local_minute) .. ", wday: " .. tostring(wday) .. ", time_of_day: " .. tostring(time_of_day) .. ".\n")

-- Validate required variables
if not local_wday or not local_hour or not local_minute or not wday or not time_of_day then
    freeswitch.consoleLog("ERROR", "[residential_hours] Missing required variables. Check dial plan.\n")
    session:hangup("INVALID_ARGUMENT")
    return
end

freeswitch.consoleLog("INFO", "[residential_hours] Current time - " .. local_hour .. ":" .. local_minute .. ".\n")

-- Parse the wday and time_of_day variables
local start_wday, end_wday = wday:match("(%d+)-(%d+)")
local start_hour, start_minute, end_hour, end_minute = time_of_day:match("(%d+):(%d+)-(%d+):(%d+)")

-- Convert parsed values to numbers for comparison
start_wday = tonumber(start_wday)
end_wday = tonumber(end_wday)
start_hour = tonumber(start_hour)
start_minute = tonumber(start_minute)
end_hour = tonumber(end_hour)
end_minute = tonumber(end_minute)

-- Check if the call is within the allowed day range
local is_valid_day = local_wday >= start_wday and local_wday <= end_wday

-- Check if the call is within the allowed time range
local is_valid_time = (local_hour > start_hour or (local_hour == start_hour and local_minute >= start_minute)) and
                      (local_hour < end_hour or (local_hour == end_hour and local_minute <= end_minute))

-- Route based on the conditions
if is_valid_day and is_valid_time then
  -- Within allowed time and day
  freeswitch.consoleLog("INFO", "[residential_hours] Within allowed hours.\n")
else
    freeswitch.consoleLog("INFO", "[residential_hours] Outside allowed hours.\n")
    if user_exists == "true" then
        -- Transfer call to the voicemail extension
        freeswitch.consoleLog("INFO", "[residential_hours] Transferring call to voicemail extension.\n")
        session:setVariable("record_append", "false")
        session:setVariable("voicemail_action", "save")
        session:setVariable("voicemail_id", destination_number)
        session:setVariable("voicemail_profile", "default")
        session:execute("lua", "app.lua voicemail")
    else
        -- If user does not exist, reject the call
        -- Answer the call to ensure a CDR is generated
        session:answer()

        session:execute("playback", "silence_stream://1000")

        -- session:streamFile("/usr/share/freeswitch/sounds/inbound_not_allowed.wav")

        -- session:sleep(1000) -- Wait for 1 second (1000 milliseconds)

        session:hangup("CALL_REJECTED")  -- Hang up with the specified cause
    end
end
