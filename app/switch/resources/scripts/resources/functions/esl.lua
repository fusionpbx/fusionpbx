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

local EventSocket = class() do

if not freeswitch then

local socket       = require "socket"
local ESLParser    = require "lluv.esl".ESLParser
local split_status = require "lluv.esl.utils".split_status
local Database     = require "resources.functions.database"

local EOL = '\n'

local host, port, auth

function EventSocket:__init()
  if not host then
    local db = Database.new('system')
    local settings, err = db:first_row("select event_socket_ip_address, event_socket_port, event_socket_password from v_settings")
    if not settings then return nil, err end
    host, port, auth = settings.event_socket_ip_address, settings.event_socket_port, settings.event_socket_password
  end

  return self:_connect(host, port, auth)
end

function EventSocket:_connect(host, port, password)
  local err
  self._cnn, err  = socket.connect(host, port)
  if not self._cnn then return nil, err end

  self._cnn:settimeout(1)

  self._parser = ESLParser.new()
  local auth
  while true do
    local event
    event, err = self:_recv_event()
    if not event then break end

    local ct = event:getHeader('Content-Type')
    if ct == 'auth/request' then
      self._cnn:send('auth ' .. password .. EOL .. EOL)
    elseif ct == 'command/reply' then
      local reply = event:getHeader('Reply-Text')
      if reply then
        local ok, status, msg = split_status(reply)
        if ok then auth = true else err = msg end
      else
        err = 'invalid response'
      end
      break
    end
  end

  if not auth then
    self._cnn:close()
    self._cnn = nil
    return nil, err
  end

  return self
end

function EventSocket:_recv_event()
  local event, err = self._parser:next_event()

  while event == true do
    local str, rst
    str, err, rst = self._cnn:receive("*l")
    if str then self._parser:append(str):append(EOL) end
    if rst then self._parser:append(rst) end
    if err and err ~= 'timeout' then
      break
    end
    event = self._parser:next_event()
  end

  if (not event) or (event == true) then
    return nil, err
  end

  return event
end

function EventSocket:_request(cmd)
  if not self._cnn then return nil, 'closed' end

  for str in (cmd .. '\n'):gmatch("(.-)\n") do
    self._cnn:send(str .. EOL)
  end
  self._cnn:send(EOL)

  return self:_recv_event()
end

function EventSocket:api(cmd)
  local event, err = self:_request('api ' .. cmd)
  if not event then return nil, err end
  local body = event:getBody()
  if body then
    local ok, status, msg = split_status(body)
    if ok == nil then return body end
    return ok, status, msg
  end
  return event:getReply()
end

function EventSocket:close()
  if self._cnn then
    self._cnn:close()
    self._cnn = nil
  end
end

end

if freeswitch then

local api

-- [+-][OK|ERR|USAGE|...][Message]
local function split_status(str)
  local ok, status, msg = string.match(str, "^%s*([-+])([^%s]+)%s*(.-)%s*$")
  if not ok then return nil, str end
  return ok == '+', status, msg
end

function EventSocket:__init()
  self._api = api or freeswitch.API()
  api = self._api
  return self
end

function EventSocket:api(cmd)
  local result = self._api:executeString(cmd)
  local ok, status, msg = split_status(result)
  if ok == nil then return result end
  return ok, status, msg
end

function EventSocket:close()
  self._api = nil
end

end

end

return EventSocket
