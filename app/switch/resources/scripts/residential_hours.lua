-- Retrieve variables passed from the dial plan
local wday = session:getVariable("weekday")  -- e.g., "1-7"
local time_of_day = session:getVariable("time_of_day")  -- e.g., "17:00-22:00"

-- Get the current time and day
local current_time = os.date("*t")
local current_wday = current_time.wday  -- Current day of the week (1=Sunday, ...)
local current_hour = current_time.hour
local current_minute = current_time.min

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
local is_valid_day = current_wday >= start_wday and current_wday <= end_wday

-- Check if the call is within the allowed time range
local is_valid_time = (current_hour > start_hour or (current_hour == start_hour and current_minute >= start_minute)) and
                      (current_hour < end_hour or (current_hour == end_hour and current_minute <= end_minute))

-- Route based on the conditions
if is_valid_day and is_valid_time then
  -- Within allowed time and day
  freeswitch.consoleLog("INFO", "[residential_hours] Within allowed hours.\n")
else
    -- Outside allowed time and day
    freeswitch.consoleLog("INFO", "[residential_hours] Outside allowed hours.\n")
    -- Answer the call to ensure a CDR is generated
    session:answer()

    -- session:execute("playback", "silence_stream://1000")

    -- session:streamFile("/usr/share/freeswitch/sounds/inbound_not_allowed.wav")

    -- session:sleep(1000) -- Wait for 1 second (1000 milliseconds)

    session:hangup("CALL_REJECTED")  -- Hang up with the specified cause
end
