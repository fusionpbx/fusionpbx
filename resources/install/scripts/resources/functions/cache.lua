-- @usage cache = require "resources.functions.cache"
-- value = cache.get(key)
-- if not value then
--   ...
--   cache.set(key, value, expire)
-- end
--

require "resources.functions.trim";

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
  local event = freeswitch.Event("MEMCACHE", action);
  event:addHeader("API-Command", "memcache");
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
  Cache._support = (trim(api:execute('module_exists', 'mod_memcache')) == 'true')
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
  local result, err = check_error(api:execute('memcache', 'get ' .. key))
  if not result then return nil, err end
  return (result:gsub("&#39;", "'"))
end

function Cache.set(key, value, expire)
  value = value:gsub("'", "&#39;"):gsub("\\", "\\\\")
  expire = expire and tostring(expire) or ""
  local ok, err = check_error(api:execute("memcache", "set " .. key .. " '" .. value .. "' " .. expire))
  if not ok then return nil, err end
  return ok == '+OK'
end

function Cache.del(key)
  send_event('delete', key)
  local result, err = check_error(api:execute("memcache", "delete " .. key))
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
