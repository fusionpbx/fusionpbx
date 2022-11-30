-- Decode result of api execute command to Lua way.
-- in case of error function returns `nil` and `error message`.
-- in other case function return result as is.
local function api_result(result)
	if string.find(result, '^%-ERR') or string.find(result, '^INVALID COMMAND!') then
		return nil, string.match(result, "(.-)%s*$")
	end

	return result
end

local function class(base)
  local t = base and setmetatable({}, base) or {}
  t.__index = t
  t.__class = t
  t.__base  = base

  function t.new(...)
    local o = setmetatable({}, t)
    if o.__init then
      if t == ... then -- we call as Class:new()
        return o:__init(select(2, ...))
      else             -- we call as Class.new()
        return o:__init(...)
      end
    end
    return o
  end

  return t
end

local API = class() do

function API:__init(...)
	self._api = freeswitch.API(...)
	return self
end

function API:execute(...)
	return api_result(self._api:execute(...))
end

function API:executeString(...)
	return api_result(self._api:executeString(...))
end

function API:getTime()
	return self._api:getTime()
end

end

return API.new()
