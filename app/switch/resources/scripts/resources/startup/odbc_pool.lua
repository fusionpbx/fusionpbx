-- Start background service to support Lua-ODBC-Pool database backend

require "resources.functions.config"

local log           = require "resources.functions.log".odbcpool
local odbc          = require "odbc"
local odbcpool      = require "odbc.pool"
local EventConsumer = require "resources.functions.event_consumer"

-- Configuration
local service_name = 'odbcpool'
local pid_file     = scripts_dir .. "/run/" .. service_name .. ".tmp"
local pool_size    = 5

-- Pool ctor
local function run_odbc_pool(name, n)
  local connection_string = assert(database[name])

  local typ, dsn, user, password = connection_string:match("^(.-)://(.-):(.-):(.-)$")
  if typ ~= 'odbc' then
    return log.warningf("unsupported connection string type: %s", tostring(typ))
  end

  local cli = odbcpool.client(name)

  log.noticef("Starting reconnect thread[%s] ...", name)
  local rthread = odbcpool.reconnect_thread(cli, dsn, user, password)
  rthread:start()
  log.noticef("Reconnect thread[%s] started", name)

  local env = odbc.environment()

  local connections = {}
  for i = 1, (n or 10) do
    local cnn = odbc.assert(env:connection())
    connections[#connections+1] = cnn
    cli:reconnect(cnn)
  end

  return {
    name = name;
    cli  = cli;
    cnn  = connections;
    thr  = rthread;
  }
end

-- Pool dtor
local function stop_odbc_pool(ctx)
  log.noticef("Stopping reconnect thread[%s] ...", ctx.name)
  ctx.thr:stop()
  log.noticef("Reconnect thread[%s] stopped", ctx.name)
end

local system_pool = run_odbc_pool("system", pool_size)
local switch_pool = run_odbc_pool("switch", pool_size)

if not (system_pool or switch_pool) then
  log.errf('there no supported databases')
  return
end

local events = EventConsumer.new(pid_file)

-- FS shutdown
events:bind("SHUTDOWN", function(self, name, event)
  log.notice("shutdown")
  return self:stop()
end)

-- Control commands from FusionPBX
events:bind("CUSTOM::fusion::service::control", function(self, name, event)
  if service_name ~= event:getHeader('service-name') then return end

  local command = event:getHeader('service-command')
  if command == "stop" then
    log.notice("get stop command")
    return self:stop()
  end

  log.warningf('Unknown service command: %s', command or '<NONE>')
end)

-- Overwrite `events:stop` method to do cleanup
--! @todo find more elegant way
do local stop = events.stop
function events:stop(...)
  -- do not rise any error
  pcall(function()
    if system_pool then stop_odbc_pool(system_pool); system_pool = nil end
    if switch_pool then stop_odbc_pool(switch_pool); switch_pool = nil end
  end)
  stop(self, ...)
end
end

log.notice("start")

events:run()

log.notice("stop")
