--
-- Native backend to FusionPBX database class
--

local log = require "resources.functions.log".database

assert(freeswitch, "Require FreeSWITCH environment")

-----------------------------------------------------------
local FsDatabase = {} do

require "resources.functions.trim"
require "resources.functions.file_exists"
require "resources.functions.database_handle"

FsDatabase.__index = FsDatabase
FsDatabase._backend_name = 'native'

function FsDatabase.new(name)
  local dbh = assert(name)
  if (type(name) == 'string') then
    --debug information
  	--freeswitch.consoleLog("notice","name " .. name .. "\n");
  	--freeswitch.consoleLog("notice","database.type " .. database.type .. "\n");
  	--freeswitch.consoleLog("notice","database.name " .. database.name .. "\n");
  	--freeswitch.consoleLog("notice","database.path " .. database.path .. "\n");

  	--handle switch sqlite
    if (name == 'switch' and database.type == 'sqlite' and database.path ~= nil and database.name ~= nil) then
      dbh = freeswitch.Dbh("sqlite://"..trim(database.path).."/"..trim(database.name))
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

return FsDatabase
