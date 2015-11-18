require 'resources.functions.config'

-----------------------------------------------------------
local OdbcDatabase = {} if not freeswitch then
OdbcDatabase.__index = OdbcDatabase

local odbc = require "odbc.dba"

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
      local o = {}
      for k, v in pairs(row) do
        if v == odbc.NULL then 
          o[k] = nil
        else
          o[k] = tostring(v)
        end
      end
      return fn(o)
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
-----------------------------------------------------------

-----------------------------------------------------------
local FsDatabase = {} if freeswitch then

require "resources.functions.file_exists"
require "resources.functions.database_handle"

FsDatabase.__index = FsDatabase

function FsDatabase.new(name)
  local dbh = assert(name)
  if type(name) == 'string' then
    if name == 'switch' and file_exists(database_dir.."/core.db") then
      dbh = freeswitch.Dbh("sqlite://"..database_dir.."/core.db")
    else
      dbh = database_handle(name)
    end
  end
  assert(dbh:connected())

  local self = setmetatable({
    _dbh = dbh;
  }, FsDatabase)

  return self
end

function FsDatabase:query(sql, fn)
  if fn then
    return self._dbh:query(sql, fn)
  end
  return self._dbh:query(sql)
end

function FsDatabase:affected_rows()
  if self._dbh then
    return self._dbh:affected_rows()
  end
end

function FsDatabase:release()
  if self._dbh then
    self._dbh:release()
    self._dbh = nil
  end
end

function FsDatabase:connected()
  return self._dbh and self._dbh:connected()
end

end
-----------------------------------------------------------

-----------------------------------------------------------
local Database = {} do
Database.__index = Database
Database.__base = freeswitch and FsDatabase or OdbcDatabase
Database = setmetatable(Database, Database.__base)

function Database.new(...)
  local self = Database.__base.new(...)
  setmetatable(self, Database)
  return self
end

function Database:first_row(sql)
  local result
  local ok, err = self:query(sql, function(row)
    result = row
    return 1
  end)
  if not ok then return nil, err end
  return result
end

function Database:first_value(sql)
  local result, err = self:first_row(sql)
  if not result then return nil, err end
  local k, v = next(result)
  return v
end

function Database:first(sql, ...)
  local result, err = self:first_row(sql)
  if not result then return nil, err end
  local t, n = {}, select('#', ...)
  for i = 1, n do
    t[i] = result[(select(i, ...))]
  end
  return unpack(t, 1, n)
end

function Database:fetch_all(sql)
  local result = {}
  local ok, err = self:query(sql, function(row)
    result[#result + 1] = row
  end)
  if (not ok) and err then return nil, err end
  return result
end

function Database.__self_test__(...)
  local db = Database.new(...)
  assert(db:connected())

  assert("1" == db:first_value("select 1 as v union all select 2 as v"))

  local t = assert(db:first_row("select '1' as v union all select '2' as v"))
  assert(t.v == "1")

  t = assert(db:fetch_all("select '1' as v union all select '2' as v"))
  assert(#t == 2)
  assert(t[1].v == "1")
  assert(t[2].v == "2")

  local a, b = assert(db:first("select '1' as b, '2' as a", 'a', 'b'))
  assert(a == "2")
  assert(b == "1")

  -- assert(nil == db:first_value("some non sql query"))

  db:release()
  assert(not db:connected())
  print(" * databse - OK!")
end

end
-----------------------------------------------------------

return Database