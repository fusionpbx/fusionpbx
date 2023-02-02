--
-- Native backend to FusionPBX database class
--

local log = require "resources.functions.log".database

assert(freeswitch, "Require FreeSWITCH environment")

-----------------------------------------------------------
local FsDatabase = {} do

require "resources.functions.file_exists"
require "resources.functions.database_handle"

FsDatabase.__index = FsDatabase
FsDatabase._backend_name = 'native'

function FsDatabase.new(name)
  local dbh = assert(name)
  if type(name) == 'string' then
    dbh = database_handle(name)
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

return FsDatabase
