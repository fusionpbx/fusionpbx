-- Start background service to support Lua-ODBC-Pool database backend

require "resources.functions.config"
require "resources.functions.file_exists"

local log      = require "resources.functions.log".dbpool
local odbc     = require "odbc"
local odbcpool = require "odbc.pool"

-- Configuration
local POLL_TIMEOUT = 5
local run_file = scripts_dir .. "/run/dbpool.tmp";

-- Pool ctor
local function run_odbc_pool(name, n)
  local connection_string = assert(database[name])

  local typ, dsn, user, password = connection_string:match("^(.-)://(.-):(.-):(.-)$")
  assert(typ == 'odbc', "unsupported connection string:" .. connection_string)

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

local function stop_odbc_pool(ctx)
  log.noticef("Stopping reconnect thread[%s] ...", ctx.name)
  ctx.thr:stop()
  log.noticef("Reconnect thread[%s] stopped", ctx.name)
end

local function main()
  local system_pool = run_odbc_pool("system", 10)
  local switch_pool = run_odbc_pool("switch", 10)

  local file = assert(io.open(run_file, "w"));
  file:write("remove this file to stop the script");
  file:close()

  while file_exists(run_file) do
    freeswitch.msleep(POLL_TIMEOUT*1000)
  end

  stop_odbc_pool(system_pool)
  stop_odbc_pool(switch_pool)
end

main()
