require "resources.functions.split";
require "resources.functions.trim";

local s = event:serialize("xml")
local name = event:getHeader("Event-Name")
--freeswitch.consoleLog("NOTICE", "Got event! " .. name)

--freeswitch.consoleLog("NOTICE", "Serial!\n" .. s)


local name = event:getHeader("Event-Name");
local channel_state = event:getHeader("Channel-State") or '[nil]';
local original_channel_state = event:getHeader("Original-Channel-Call-State") or '[nil]';
local channel_name = event:getHeader("Channel-Name") or '[nil]';
local channel_call_uuid = event:getHeader("Channel-Call-UUID");
local channel_timestamp = event:getHeader("Event-Date-Timestamp");
local body = event:getBody();
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] event-name "  .. name);
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] original-channel-state " .. original_channel_state);
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] channel-state " .. channel_state);
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] channel-name " .. channel_name);
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] channel-call-uuid " .. channel_call_uuid);
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] channel_timestamp " .. channel_timestamp);
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] body " .. body);

session = freeswitch.Session(channel_call_uuid);
local v  = split(body,"\n",true);
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] type: " .. v[1]);
--freeswitch.consoleLog("NOTICE", "[dtmf_handler] type: " .. v[2]);
local vv = split(v[1],'=',true);
local dtmf_value = trim(vv[2]);
freeswitch.consoleLog("NOTICE", "[dtmf_handler] DTMF value: " .. dtmf_value);
local history = channel_timestamp .. ':' .. dtmf_value .. "\n";
session:execute("push", "dtmf_history="..history);

-- lua.conf.xml
-- <hook event="RECV_INFO" script="dtmf_handler.lua"/>
