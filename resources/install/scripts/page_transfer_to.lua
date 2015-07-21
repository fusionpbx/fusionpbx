local api = freeswitch.API()
local uuid = argv[1]

freeswitch.consoleLog("NOTICE", "[page_enter_to] session " .. tostring(uuid) .. "\n");

local other_leg_uuid = api:executeString("uuid_getvar "..uuid.." signal_bond")
local domain_name    = api:executeString("uuid_getvar "..uuid.." domain_name")

freeswitch.consoleLog("NOTICE", "[page_enter_to] " ..
  tostring(other_leg_uuid) ..
  " => " ..
  tostring(domain_name) ..
  "\n"
)

if (not other_leg_uuid) or other_leg_uuid == '' then return end

local res = api:executeString("uuid_transfer " .. other_leg_uuid .. " page_enter_to XML " .. domain_name)

freeswitch.consoleLog("NOTICE", "[page_enter_to] transfer:" .. tostring(res) .. "\n")
