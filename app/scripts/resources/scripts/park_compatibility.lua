if event:getHeader("proto") ~= "park" then
    return
end

-- Get the domain name and parking lot if in the form of 59xx@domain_name
-- park+ gets added on downstream in sofia_presence.c#L2026 if proto == park
-- See https://github.com/signalwire/freeswitch/blob/master/src/mod/applications/mod_valet_parking/mod_valet_parking.c#L278
-- https://github.com/signalwire/freeswitch/blob/master/src/mod/endpoints/mod_sofia/sofia_presence.c#L2026
_, _, parking_lot, domain_name = string.find(event:getHeader("from"), "^(59%d%d)@(.+)$")
if parking_lot == nil or domain_name == nil then
    return
end

local newLot = "*" .. parking_lot
local newFrom = newLot .. "@" .. domain_name

freeswitch.consoleLog("NOTICE", "Forwarding " .. event:getHeader("from") .. " To " .. newFrom)

-- Delete the headers before we replace them with new ones
event:delHeader("from")
event:delHeader("login")

-- Set the new headers
event:addHeader("from", newFrom)
event:addHeader("login", newLot)

-- Fire the event
event:fire()