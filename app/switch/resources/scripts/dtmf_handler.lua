--local s = event:serialize("xml")
--local name = event:getHeader("Event-Name")
--freeswitch.consoleLog("NOTICE", "Got event! " .. name)
--freeswitch.consoleLog("NOTICE", "Serial!\n" .. s)

local call_uuid = event:getHeader("Caller-Unique-ID");
--local channel_timestamp = event:getHeader("Event-Date-Timestamp");
local channel_timestamp = os.time();
local dtmf_value = event:getHeader("DTMF-Digit");

local session = freeswitch.Session(call_uuid);
local history = channel_timestamp .. ':' .. dtmf_value .. "\n";
session:execute("push", "dtmf_history="..history);

-- lua.conf.xml
-- <hook event="DTMF" script="dtmf_handler.lua"/>
