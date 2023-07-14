require "resources.functions.mkdir";
require "resources.functions.split"

local IntervalTimer = require "resources.functions.interval_timer"
local file          = require "resources.functions.file"
local api           = require "resources.functions.api"

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

local function callable(f)
	return type(f) == 'function'
end

local function basename(p)
	return (string.match(p, '^(.-)[/\\][^/\\]+$'))
end

local function split_event(event_name)
	local name, class = split_first(event_name, "::", true)
	if class then return name, class end
	return name
end

local function append(t, v)
	t[#t+1]=v return t
end

local function remove(t, i)
	table.remove(t, i)
	return t
end

-------------------------------------------------------------------------------
local BasicEventEmitter = class() do

local ANY_EVENT = {}

BasicEventEmitter.ANY = ANY_EVENT

function BasicEventEmitter:__init()
	-- map of array of listeners
	self._handlers = {}
	-- map to convert user's listener to internal wrapper
	self._once     = {}

	return self
end

function BasicEventEmitter:on(event, handler)
	local list = self._handlers[event] or {}

	for i = 1, #list do
		if list[i] == handler then
			return self
		end
	end

	list[#list + 1] = handler
	self._handlers[event] = list

	return self
end

function BasicEventEmitter:many(event, ttl, handler)
	self:off(event, handler)

	local function listener(...)
		ttl = ttl - 1
		if ttl == 0 then self:off(event, handler) end
		handler(...)
	end

	self:on(event, listener)
	self._once[handler] = listener

	return self
end

function BasicEventEmitter:once(event, handler)
	return self:many(event, 1, handler)
end

function BasicEventEmitter:off(event, handler)
	local list = self._handlers[event]

	if not list then return self end

	if handler then

		local listener = self._once[handler] or handler
		self._once[handler] = nil

		for i = 1, #list do
			if list[i] == listener then
				table.remove(list, i)
				break
			end
		end

		if #list == 0 then self._handlers[event] = nil end

	else

		for handler, listener in pairs(self._once) do
			for i = 1, #list do
				if list[i] == listener then
					self._once[handler] = nil
					break
				end
			end
		end

		self._handlers[event] = nil

	end

	return self
end

function BasicEventEmitter:onAny(handler)
	return self:on(ANY_EVENT, handler)
end

function BasicEventEmitter:manyAny(ttl, handler)
	return self:many(ANY_EVENT, ttl, handler)
end

function BasicEventEmitter:onceAny(handler)
	return self:once(ANY_EVENT, handler)
end

function BasicEventEmitter:offAny(handler)
	return self:off(ANY_EVENT, handler)
end

function BasicEventEmitter:_emit_impl(call_any, event, ...)
	local ret = false

	if call_any and ANY_EVENT ~= event then
		ret = self:_emit_impl(false, ANY_EVENT, ...) or ret
	end

	local list = self._handlers[event]

	if list then
		for i = #list, 1, -1 do
			if list[i] then
				-- we need this check because cb could remove some listeners
				list[i](...)
				ret = true
			end
		end
	end

	return ret
end

function BasicEventEmitter:emit(event, ...)
	return self:_emit_impl(true, event, ...)
end

function BasicEventEmitter:_emit_all(...)
	-- we have to copy because cb can remove/add some events
	-- and we do not need call new one or removed one
	local names = {}
	for name in pairs(self._handlers) do
		names[#names+1] = name
	end

	local ret = false
	for i = 1, #names do
		ret = self:_emit_impl(false, names[i], ...) or ret
	end

	return ret
end

function BasicEventEmitter:_empty()
	return nil == next(self._handlers)
end

function BasicEventEmitter:removeAllListeners(eventName)
	if not eventName then
		self._handlers = {}
		self._once     = {}
	else
		self:off(eventName)
	end

	return self
end

end
-------------------------------------------------------------------------------

-------------------------------------------------------------------------------
local EventEmitter = class() do

function EventEmitter:__init(opt)
	if opt and opt.wildcard then
		assert('`EventEmitter::wildcard` not supported')
		-- self._EventEmitter = TreeEventEmitter.new(opt.delimiter)
	else
		self._EventEmitter = BasicEventEmitter.new()
	end
	self._EventEmitter_self = opt and opt.self or self

	return self
end

function EventEmitter:on(event, listener)
	assert(event, 'event expected')
	assert(callable(listener), 'function expected')

	self._EventEmitter:on(event, listener)
	return self
end

function EventEmitter:many(event, ttl, listener)
	assert(event, 'event expected')
	assert(type(ttl) == 'number', 'number required')
	assert(callable(listener), 'function expected')

	self._EventEmitter:many(event, ttl, listener)
	return self
end

function EventEmitter:once(event, listener)
	assert(event, 'event expected')
	assert(callable(listener), 'function expected')

	self._EventEmitter:once(event, listener)
	return self
end

function EventEmitter:off(event, listener)
	assert(event, 'event expected')
	assert((listener == nil) or callable(listener), 'function expected')

	self._EventEmitter:off(event, listener)
	return self
end

function EventEmitter:emit(event, ...)
	assert(event, 'event expected')

	return self._EventEmitter:emit(event, self._EventEmitter_self, event, ...)
end

function EventEmitter:onAny(listener)
	assert(callable(listener), 'function expected')

	self._EventEmitter:onAny(listener)
	return self
end

function EventEmitter:manyAny(ttl, listener)
	assert(type(ttl) == 'number', 'number required')
	assert(callable(listener), 'function expected')

	self._EventEmitter:manyAny(ttl, listener)
	return self
end

function EventEmitter:onceAny(listener)
	assert(callable(listener), 'function expected')

	self._EventEmitter:onceAny(listener)
	return self
end

function EventEmitter:offAny(listener)
	assert((listener == nil) or callable(listener), 'function expected')

	self._EventEmitter:offAny(listener)
	return self
end

function EventEmitter:removeAllListeners(eventName)
	self._EventEmitter:removeAllListeners(eventName)
	return self
end

-- aliases

EventEmitter.addListener    = EventEmitter.on

EventEmitter.removeListener = EventEmitter.off

end
-------------------------------------------------------------------------------

-------------------------------------------------------------------------------
local TimeEvent = class() do

function TimeEvent:__init(interval, callback, once)
	self._timer = IntervalTimer.new(interval):start()
	self._callback = callback
	self._once     = once

	return self
end

function TimeEvent:started()
	return self._timer:started()
end

function TimeEvent:restart()
	return self._timer:restart()
end

function TimeEvent:rest()
	return self._timer:rest()
end

function TimeEvent:reset(interval)
	self._timer:reset(interval)
	return self
end

function TimeEvent:stop()
	return self._timer:stop()
end

function TimeEvent:once()
	return self._once
end

function TimeEvent:fire(...)
	-- !!! do not pass self
	return self._callback(...)
end

end
-------------------------------------------------------------------------------

-------------------------------------------------------------------------------
local TimeEvents = class() do

function TimeEvents:__init()
	self._events = {}
	return self
end

function TimeEvents:sleepInterval(max_interval)
	local events = self._events
	for i = 1, #events do
		local event = events[i]
		if event:started() then
			local rest = event:rest()
			if max_interval > rest then max_interval = rest end
		end
	end
	return max_interval
end

function TimeEvents:fire(this, ...)
	self._lock = true
	local events = self._events
	for i = 1, #events do
		local event = events[i]
		if event:rest() == 0 then
			if event:once() then
				event:stop()
			else
				event:restart()
			end
			event:fire(this, event, ...)
		end
	end
	self._lock = false

	for i = #events, 1, -1 do
		local event = events[i]
		if not event:started() then
			remove(events, i)
		end
	end
end

function TimeEvents:setInterval(interval, callback)
	local event = TimeEvent.new(interval, callback, false)
	append(self._events, event)
	return event
end

function TimeEvents:setIntervalOnce(interval, callback)
	local event = TimeEvent.new(interval, callback, true)
	append(self._events, event)
	return event
end

function TimeEvents:removeInterval(timer)
	local events = self._events
	for i = #events, 1, -1 do
		if events[i] == timer then
			if self._lock then
				events[i]:stop()
			else
				remove(events, i)
			end
			return true
		end
	end
end

end
-------------------------------------------------------------------------------

-------------------------------------------------------------------------------
local EventConsumer = class(EventEmitter) do

local default_timeout       = 60000
local default_poll_interval = 60000 * 30

function EventConsumer:__init(pid_file, timeout)
	self = EventConsumer.__base.__init(self)

	if pid_file then
		assert(type(pid_file) == 'string')
		timeout = timeout or default_timeout
	end

	if timeout then assert(timeout > 0) end

	self._bound    = {}
	self._running  = false
	self._consumer = freeswitch.EventConsumer()
	self._timeout  = timeout
	self._timers   = TimeEvents.new()
	if pid_file then
		self._pid      = api:execute("create_uuid") or tostring(api:getTime())
		self._pid_file = pid_file
	end

	if self._timeout then
		self:onInterval(self._timeout, function(self)
			if not self:_check_pid_file() then return self:stop() end
		end)
	end

	return self
end

function EventConsumer:_check_pid_file()
	if not self._pid_file then
		return true
	end
	if not file.exists(self._pid_file) then
		return false
	end

	local stored = file.read(self._pid_file)
	if stored and stored ~= self._pid then
		return false
	end

	return true
end

function EventConsumer:_reset_pid_file()
	if self._pid_file then
		local pid_path = basename(self._pid_file)
		mkdir(pid_path)
		assert(file.write(self._pid_file, self._pid))
	end
end

function EventConsumer:bind(event_name, cb)
	if not self._bound[event_name] then
		local name, class = split_event(event_name)

		local ok, err
		if not class then ok, err = self._consumer:bind(name)
		else ok, err = self._consumer:bind(name, class) end

		if ok then self._bound[event_name] = true end
	end

	if self._bound[event_name] and cb then
		if event_name == 'ALL' then
			self:onAny(function(self, name, event)
				if event then return cb(self, name, event) end
			end)
		else
			self:on(event_name, cb)
		end
	end
end

function EventConsumer:_run()
	self:_reset_pid_file()

	self._running = true

	-- set some huge default interval
	-- if there no time events then we wait this amount of time
	local max_interval = self._timeout or default_poll_interval

	while self._running do
		self._timers:fire(self)
		if not self._running then break end
		local timeout = self._timers:sleepInterval(max_interval)

		local event
		if timeout == 0 then
			-- we have some time based events.
			-- so we just try get fs event without wait
			event = self._consumer:pop(0)
		else
			event = self._consumer:pop(1, timeout)
		end

		if event then
			local event_name = event:getHeader('Event-Name')
			if self._bound[event_name] then
				self:emit(event_name, event)
				if not self._running then break end
			end
			local event_class = event:getHeader('Event-Subclass')
			if event_class and #event_class > 0 then
				event_name = event_name .. '::' .. event_class
				self:emit(event_name, event)
				if not self._running then break end
			end
		end
	end

	self._running = false
end

function EventConsumer:run()
	local ok, err = xpcall(function()
		self:_run()
	end, debug.traceback)

	if not ok then
		-- ensure we stop loop and remove pid file
		self:stop()
		error(err)
	end
end

function EventConsumer:stop()
	self._running = false
	if self._pid_file and self:_check_pid_file() then
		file.remove(self._pid_file)
	end
end

function EventConsumer:onInterval(interval, callback)
	return self._timers:setInterval(interval, callback)
end

function EventConsumer:onIntervalOnce(interval, callback)
	return self._timers:setIntervalOnce(interval, callback)
end

function EventConsumer:offInterval(timer)
	return self._timers:removeInterval(timer)
end

end
-------------------------------------------------------------------------------

---
--
-- @param events [string|array]- array of events to subscribe. To specify subclass you
--   can use string like `<EVENT>::<SUBCLASS>` or array like `{<EVENT>, <SUBCLASS>}`.
--   If `events` is string then it specify single event.
-- @param block [booolean?] - by default it use block
-- @param timeout [number?] - by default it 0. If set `block` that means infinity wait.
--
-- @usage
-- -- do blocked itarate over 'MEMCACHE' and 'SHUTDOWN' events
-- for event in ievents{'MEMCACHE','SHUTDOWN'} do ... end
--
-- -- do blocked iterate with timeout 1 sec
-- for event in ievents('SHUTDOWN', 1000) do
--   if event then -- has event
--   else -- timeout
--   end
-- end
local ievents = function(events, block, timeout)
	if type(events) == 'string' then
		events = freeswitch.EventConsumer(split_event(events))
	elseif type(events) == 'table' then
		local array = events
		events = freeswitch.EventConsumer()
		for _, event in ipairs(array) do
			local name, class
			if type(event) == 'table' then
				name, class = event[1], event[2]
			else
				name, class = split_event(event)
			end
			if not class then events:bind(name)
			else events:bind(name, class) end
		end
	end

	if type(block) == 'number' then
		block, timeout = true, block
	end

	timeout = timeout or 0
	block   = block and 1 or 0

	return function()
		local event = events:pop(block, timeout)
		if not event then return false end
		return event
	end
end

return {
	EventConsumer = EventConsumer;
	new = EventConsumer.new;
	ievents = ievents;
}
