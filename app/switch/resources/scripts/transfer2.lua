local api = freeswitch.API()
local uuid = assert(argv[1])
local t1   = assert(argv[2])
local t2   = assert(argv[3])

require "resources.functions.channel_utils"
require "resources.functions.split"
local log = require "resources.functions.log".transfer2

log.noticef("session %s\n", uuid);

local other_leg_uuid = channel_variable(uuid, "signal_bond")

log.noticef("other leg: %s\n", other_leg_uuid or '<NONE>')

if other_leg_uuid and #other_leg_uuid > 0 then
  if t2 ~= '::NONE::' then
    if t2 == '::KILL::' then 
      channel_kill(other_leg_uuid)
    else
      channel_transfer(other_leg_uuid, usplit(t2, '::'))
    end
  end
end

if t1 ~= '::NONE::' then 
  if t1 == '::KILL::' then 
    channel_kill(uuid)
  else
    channel_transfer(uuid, usplit(t1, '::'))
  end
end
