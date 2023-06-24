if freeswitch then

function sleep(ms)
  freeswitch.msleep(ms)
end

else

local socket = require "socket"

function sleep(ms)
  socket.sleep(ms/1000)
end

end
