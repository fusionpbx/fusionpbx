-- @usage cache = require "resources.functions.cache"
-- value = cache.get(key)
-- if not value then
--   ...
--   cache.set(key, value, expire)
-- end
--

--include config.lua
require "resources.functions.config";

-- include functions
require "resources.functions.trim";
require "resources.functions.file_exists";

local api = api
if not api then
  if freeswitch then
    api = freeswitch.API()
  else
    api = {}
    function api:execute()
      return '-ERR UNSUPPORTTED'
    end
  end
end

local function send_event(action, key)
    if (cache.method == "memcache") then
      local event = freeswitch.Event("CUSTOM", "fusion::memcache");
      event:addHeader("API-Command", "memcache");
    end
    if (cache.method == "file") then
      local event = freeswitch.Event("CUSTOM", "fusion::file");
      event:addHeader("API-Command", "file");
    end
    event:addHeader("API-Command-Argument", action .. " " .. key);
    event:fire()
end

local Cache = {}

local function check_error(result)
  result = trim(result or '')

  if result and result:sub(1, 4) == '-ERR' then
    return nil, trim(result:sub(5))
  end

  if result == 'INVALID COMMAND!' and not Cache.support() then
    return nil, 'INVALID COMMAND'
  end

  return result
end

function Cache.support()
  -- assume it is not unloadable
  if Cache._support then
    return true
  end
  if (cache.method == "memcache") then
    Cache._support = (trim(api:execute('module_exists', 'mod_memcache')) == 'true')
  else
  	Cache._support = true;
  end
  return Cache._support
end

--- Get element from cache
--
-- @tparam key string
-- @return[1] string value
-- @return[2] nil
-- @return[2] error string `e.g. 'NOT FOUND'
-- @note error string does not contain `-ERR` prefix
function Cache.get(key)
  local key = key:gsub(":", ".")
  if (cache.method == "memcache") then
    local result, err = check_error(api:execute('memcache', 'get ' .. key))
  end
  if (cache.method == "file") then
    if (file_exists(cache.location .. "/" .. key)) then
      local result, err = io.open(cache.location .. "/" .. key,  "rb")
    end
  end
  if not result then return nil, err end
  return (result:gsub("&#39;", "'"))
end

function Cache.set(key, value, expire)
  key = key:gsub(":", ".")
  value = value:gsub("'", "&#39;"):gsub("\\", "\\\\")
  --local ok, err = check_error(write_file(cache.location .. "/" .. key, value))
  if (cache.method == "file") then
    if (not file_exists(cache.location .. "/" .. key .. ".tmp")) then
      --write the temp file
      local file, err = io.open(cache.location .. "/" .. key .. ".tmp", "wb")
      if not file then
        log.err("Can not open file to write:" .. tostring(err))
        return nil, err
      end
      file:write(value)
      file:close()
      --move the temp file
      os.rename(cache.location .. "/" .. key .. ".tmp", cache.location .. "/" .. key)
    end
  end
  if (cache.method == "memcache") then
    expire = expire and tostring(expire) or ""
    local ok, err = check_error(api:execute("memcache", "set " .. key .. " '" .. value .. "' " .. expire))
    if not ok then return nil, err end
    return ok == '+OK'
  end
end

function Cache.del(key)
  key = key:gsub(":", ".")
  send_event('delete', key)
  if (cache.method == "memcache") then
    local result, err = check_error(api:execute("memcache", "delete " .. key))
  end
  if (cache.method == "file") then
    if (file_exists(cache.location .. "/" .. key)) then
      os.remove(cache.location .. "/" .. key)
      if (file_exists(cache.location .. "/" .. key .. ".tmp")) then
        os.remove(cache.location .. "/" .. key .. ".tmp")
      end
    else
      err = 'NOT FOUND'
    end
  end
  if not result then
    if err == 'NOT FOUND' then
      return true
    end
    return nil, err
  end
  return result == '+OK'
end

function Cache._self_test()
  assert(Cache.support())
  Cache.del("a")

  local ok, err = Cache.get("a")
  assert(nil == ok)
  assert(err == "NOT FOUND")

  local s = "hello \\ ' world"
  assert(true == Cache.set("a", s))
  assert(s == Cache.get("a"))

  assert(true == Cache.del("a"))
end

-- if debug.self_test then
--   Cache._self_test()
-- end

return Cache
