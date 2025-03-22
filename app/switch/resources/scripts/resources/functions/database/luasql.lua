--
-- LuaSQL backend to FusionPBX database class
--

require "resources.functions.split"
local log  = require "resources.functions.log".database

local LuaSQLDatabase = {} do
LuaSQLDatabase.__index = LuaSQLDatabase
LuaSQLDatabase._backend_name = 'LuaSQL'

local map = {
  pgsql = 'postgres';
}

local function apply_names(row, colnames, null_value)
  for _, name in pairs(colnames) do
    if row[name] == nil then
      row[name] = null_value
    else
      row[name] = tostring(row[name])
    end
  end
  return row
end

function LuaSQLDatabase.new(name)
  local self = setmetatable({}, OdbcDatabase)

  local connection_string = assert(database[name])

  local typ, args = split_first(database[name], "://", true);
  typ = map[typ] or typ

  local luasql = require ("luasql." .. typ)
  local env = assert (luasql[typ]())
  local dbh = assert (env:connect( usplit(args, ':', true) ))

  self._env, self._dbh = env, dbh
  return self
end

function LuaSQLDatabase:query(sql, fn)
  self._rows_affected = nil

  if fn then
    local cur, err = self._dbh:execute(sql)
    if err and not cur then
      log.errf("Can not execute sql: %s\n%s", tostring(err), sql)
    end

    local colnames = cur:getcolnames()
    while true do
      local row, err = cur:fetch({}, "a")
      if not row then break end
      local ok, ret = pcall(fn, apply_names(row, colnames, ""))
      ret = tonumber(ret)
      if (not ok) or (ret and ret ~= 0) then
        break
      end
    end
    cur:close()

    return true
  end

  local ok, err = self._dbh:execute(sql)
  if err and not ok then
    log.errf("Can not execute sql: %s\n%s", tostring(err), sql)
  end

  if not ok then return nil, err end

  if type(ok) ~= 'number' then
    ok:close()
    log.warning('SQL return recordset')
  else
    self._rows_affected = ok
  end

  self._rows_affected = ok
  return self._rows_affected
end

function LuaSQLDatabase:affected_rows()
  return self._rows_affected;
end

function LuaSQLDatabase:release()
  if self._dbh then
    self._dbh:close()
    self._env:close()
    self._env, self._dbh = nil
  end
end

function LuaSQLDatabase:connected()
  if not self._dbh then
    return false
  end
  local str = tostring(self._dbh)
  return not string.find(str, 'closed')
end

end

return LuaSQLDatabase