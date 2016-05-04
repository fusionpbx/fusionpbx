---
-- @usage
--  -- Use default backend
--  dbh = Database.new("system")
--  .....
--
-- @usage
--  -- Use LuaSQL backend
--  dbh = Database.backend.luasql("system")
--  .....

require 'resources.functions.config'

local log = require "resources.functions.log".database

local BACKEND = database and database.backend
if type(BACKEND) ~= 'table' then BACKEND = {main = BACKEND} end
BACKEND.main = BACKEND.main or 'native'

local unpack = unpack or table.unpack

-----------------------------------------------------------
local installed_classes = {}
local default_backend = FsDatabase
local function new_database(backend, backend_name)
  local class = installed_classes[backend]
  if class then return class end

  local Database = {} do
  Database.__index = Database
  Database.__base = backend or default_backend
  Database = setmetatable(Database, Database.__base)

  function Database.new(...)
    local self = Database.__base.new(...)
    setmetatable(self, Database)
    return self
  end

  function Database:backend_name()
    return backend_name
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
    log.info('self_test Database - ' ..  Database._backend_name)
    local db = Database.new(...)

    assert(db:connected())

    do local x = 0
    db:query("select 1 as v union all select 2 as v", function(row)
      x = x + 1
      return 1
    end)
    assert(x == 1, ("Got %d expected %d"):format(x, 1))
    end

    do local x = 0
    db:query("select 1 as v union all select 2 as v", function(row)
      x = x + 1
      return -1
    end)
    assert(x == 1, ("Got %d expected %d"):format(x, 1))
    end

    do local x = 0
    db:query("select 1 as v union all select 2 as v", function(row)
      x = x + 1
      return 0
    end)
    assert(x == 2, ("Got %d expected %d"):format(x, 2))
    end

    do local x = 0
    db:query("select 1 as v union all select 2 as v", function(row)
      x = x + 1
      return true
    end)
    assert(x == 2, ("Got %d expected %d"):format(x, 2))
    end

    do local x = 0
    db:query("select 1 as v union all select 2 as v", function(row)
      x = x + 1
      return false
    end)
    assert(x == 2, ("Got %d expected %d"):format(x, 2))
    end

    do local x = 0
    db:query("select 1 as v union all select 2 as v", function(row)
      x = x + 1
      return "1"
    end)
    assert(x == 1, ("Got %d expected %d"):format(x, 2))
    end

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

    -- select NULL
    local a = assert(db:first_value("select NULL as a"))
    assert(a == "")

    db:release()
    assert(not db:connected())

    -- second close
    db:release()
    assert(not db:connected())

    log.info('self_test Database - pass')
  end

  end

  installed_classes[backend] = Database
  return Database
end
-----------------------------------------------------------

-----------------------------------------------------------
local Database = {} do

local backend_loader = setmetatable({}, {__index = function(self, backend)
  local class = require("resources.functions.database." .. backend)
  local database = new_database(class, backend)
  self[backend] = function(...)
    return database.new(...)
  end
  return self[backend]
end})

Database.backend = backend_loader

function Database.new(dbname, role)
  local backend = role and BACKEND[role] or BACKEND.main
  return Database.backend[backend](dbname)
end

Database.__self_test__ = function(backends, ...)
  for _, backend in ipairs(backends) do
    local t = Database.backend[backend]
    t(...).__self_test__(...)
  end
end;

end
-----------------------------------------------------------

return Database