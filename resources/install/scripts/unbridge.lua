scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1))

dofile(scripts_dir.."/resources/functions/config.lua")
dofile(config())

dofile(scripts_dir.."/resources/functions/database_handle.lua")
local dbh = database_handle('switch')

local api   = freeswitch.API()
local uuid  = argv[1]
local cause = argv[2] or 'CALL_REJECTED'

assert(uuid and #uuid > 0, "No A-Leg uuid provided")

freeswitch.consoleLog("NOTICE", "[unbridge] session " .. tostring(uuid) .. "\n");

local sql = ("select uuid from channels where call_uuid='%s' and uuid<>'%s'"):format(uuid, uuid)

dbh:query(sql, function(row)
  local res = api:executeString("uuid_kill " .. row.uuid .. " " .. cause)
  freeswitch.consoleLog("NOTICE", "[unbridge] kill " .. tostring(row.uuid) .. ":" .. tostring(res) .. "\n");
end)
