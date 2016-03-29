--
-- Lua-ODBC backend to FusionPBX database class
--

local log  = require "resources.functions.log".database
local odbc = require "odbc.dba"

local function remove_null(row, null, null_value)
  local o = {}
  for k, v in pairs(row) do
    if v == null then
      o[k] = null_value
    else
      o[k] = tostring(v)
    end
  end
  return o
end

local OdbcDatabase = {} do
OdbcDatabase.__index = OdbcDatabase
OdbcDatabase._backend_name = 'ODBC'

function OdbcDatabase.new(name)
  local self = setmetatable({}, OdbcDatabase)

  local connection_string = assert(database[name])

  local typ, dsn, user, password = connection_string:match("^(.-)://(.-):(.-):(.-)$")
  assert(typ == 'odbc', "unsupported connection string:" .. connection_string)

  self._dbh = odbc.Connect(dsn, user, password)

  return self
end

function OdbcDatabase:query(sql, fn)
  self._rows_affected = nil
  if fn then
    return self._dbh:neach(sql, function(row)
      local n = tonumber((fn(remove_null(row, odbc.NULL, ""))))
      if n and n ~= 0 then
        return true
      end
    end)
  end
  local ok, err = self._dbh:exec(sql)
  if not ok then return nil, err end
  self._rows_affected = ok
  return self._rows_affected
end

function OdbcDatabase:affected_rows()
  return self._rows_affected;
end

function OdbcDatabase:release()
  if self._dbh then
    self._dbh:destroy()
    self._dbh = nil
  end
end

function OdbcDatabase:connected()
  return self._dbh and self._dbh:connected()
end

end

return OdbcDatabase