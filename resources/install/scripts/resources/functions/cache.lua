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

-- include file class
local File = require "resources.functions.file";

-- get logger
local log = require "resources.functions.log".cache;

-- get method for cache from config
local cache_method = cache and cache.method or 'memcache'

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

local send_event
if freeswitch then
  send_event = function (action, key)
    local event = freeswitch.Event("CUSTOM", "fusion::" .. cache_method)
    event:addHeader("API-Command", cache_method)
    event:addHeader("API-Command-Argument", action .. " " .. key)
    event:fire()
  end
else
  send_event = function() end
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

-- convert cache key to file path
local function key2file(key)
  return cache.location .. '/' .. string.gsub(key, '[:\\/]', {
    [':']  = '.',
    ['\\'] = '_',
    ['/']  = '_',
  })
end

-- generate random file name
local function tmp_file(key)
  -- @todo may be it worth fallback to `os.tmpname`
  local uuid = check_error(api:execute("create_uuid", ""))
  if uuid then key = key .. '.' .. uuid end
  return key .. '.tmp'
end

-- convert cache key to memcache key
local function key2key(key)
  if (key) then
    key = string.gsub(key, "\\", "\\\\");
  else
    key = '';
  end
  return key;
end

-- encode value to be able store it in memcache
local function memcache_encode(value)
  if (value) then
    value = string.gsub(value, "'", "&#39;"):gsub("\\", "\\\\");
  else
    value = '';
  end
  return value;
end

-- decode value retrived from memcache
local function memcache_decode(value)
  if (value) then
    value = string.gsub(value, "&#39;", "'");
  else
    value = '';
  end
  return value;
end

function Cache.support()
  -- assume it is not unloadable
  if Cache._support then
    return true
  end
  if (cache_method == "memcache") then
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
  local result, err = nil, 'UNSUPPORTTED'

  if (cache_method == "memcache") then
    result, err = check_error(api:execute('memcache', 'get ' .. key2key(key)))
    if result then
      result = memcache_decode(result)
    end
  end

  if (cache_method == "file") then
    key = key2file(key)
    -- log.noticef('location: %s', key)
    result, err = File.read(key)
    if not result then
      err = 'NOT FOUND';
    end
  end

  -- log.noticef('result: %s',  tostring(result or err))
  return result, err
end

function Cache.set(key, value, expire)
  if (cache_method == "file") then
    -- To make write to cache atomic we first write to some
    -- temp file with uniq random name. So if there more than
    -- one writers all of then write to its own temp file.
    -- After it done we do rename and this operation will
    -- leave only one file. If rename fail we assume that
    -- some one else write this cache item so we just remove our
    -- temp file. This should works because all writers should
    -- write same values and it does not metter which one do this first.
    key = key2file(key)
    if (not File.exists(key)) then
      -- get random name for the temp file
      local key_tmp = tmp_file(key)
      --write the temp file
      local ok, err = File.write(key_tmp, value)
      if not ok then
        log.errf('can not write file `%s`: %s', key_tmp, tostring(err))
        return nil, err
      end
      --move the temp file
      ok, err = File.rename(key_tmp, key)
      -- if we can not rename file then assume that key already exists,
      -- so we have to remove our temp file.
      if not ok then File.remove(key_tmp) end
      return ok, err
    end
    --! @todo returns special code to show reuse value?
    return true
  end

  if (cache_method == "memcache") then
    value = memcache_encode(value)
    expire = expire and tostring(expire) or ""
    local ok, err = check_error(api:execute("memcache", "set " .. key2key(key) .. " '" .. value .. "' " .. expire))
    if not ok then return nil, err end
    return ok == '+OK'
  end

  return nil, 'UNSUPPORTTED'
end

function Cache.del(key)
  send_event('delete', key)

  if (cache_method == "memcache") then
    local result, err = check_error(api:execute("memcache", "delete " .. key2key(key)))
    if not result then
      if err == 'NOT FOUND' then
        return true
      end
      return nil, err
    end
    return result == '+OK'
  end

  if (cache_method == "file") then
    key = key2file(key)
    --! @todo remove file exists check. This check needs only for return `NOT FOUND` code.
    local result, err = not File.exists(key)
    if not result then
      result, err = File.remove(key)
      if not result then
        log.errf('can not remove file `%s`: %s', key, tostring(err))
      end
    end
    File.remove(key .. ".tmp")
    return result, err
  end

  return nil, 'UNSUPPORTTED'
end

function Cache._self_test()
  print('cache mode: ', cache_method)
  assert(Cache.support())
  Cache.del("a")

  local ok, err = Cache.get("a")
  assert(nil == ok)
  assert(err == "NOT FOUND")

  local s = "hello \\ ' world"
  assert(true == Cache.set("a", s))
  assert(s == Cache.get("a"))

  assert(true == Cache.del("a"))

  local k = 'a/b\\c/d'
  Cache.del(k)

  assert(true == Cache.set(k, s))
  assert(s == Cache.get(k))
  assert(true == Cache.del(k))

  ok, err = Cache.get(k)
  assert(nil == ok)
  assert(err == "NOT FOUND")

  print('done')
end

if debug.self_test then
  Cache._self_test()
end

return Cache
