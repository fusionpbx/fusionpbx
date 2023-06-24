-- absolute timer
local fs_time if freeswitch then
	local api = require "resources.functions.api"
	fs_time = {
		now        = function()   return api:getTime()      end;
		elapsed    = function(t)  return api:getTime() - t  end;
		ms_to_time = function(ms) return ms                 end;
		time_to_ms = function(t)  return t                  end;
	}
end

-- absolute timer
local os_time = {
	now        = function()   return os.time()                 end;
	elapsed    = function(t)  return os.difftime(os.time(), t) end;
	ms_to_time = function(ms) return ms / 1000                 end;
	time_to_ms = function(t)  return t * 1000                  end;
}

-- monotonic timer (not work on my test Debian system)
local os_clock = {
	now        = function()   return os.clock()     end;
	elapsed    = function(t)  return os.clock() - t end;
	ms_to_time = function(ms) return ms / 1000      end;
	time_to_ms = function(t)  return t * 1000       end;
}

local timers = {
	freeswitch = fs_time;
	time       = os_time;
	clock      = os_clock;
}

local IntervalTimer = {} do
IntervalTimer.__index = IntervalTimer

function IntervalTimer.new(interval, timer)
	local o = setmetatable({}, IntervalTimer)
	o._interval = interval
	o._timer    = timer and assert(timers[timer], "unknown timer: " .. timer) or os_time

	return o
end

function IntervalTimer:start()
	assert(not self:started())
	return self:restart()
end

function IntervalTimer:restart()
	self._begin = self._timer.now()
	return self
end

function IntervalTimer:started()
	return not not self._begin
end

function IntervalTimer:elapsed()
	assert(self:started())
	local e = self._timer.elapsed(self._begin)
	return self._timer.time_to_ms(e)
end

function IntervalTimer:rest()
	local d = self._interval - self:elapsed()
	if d < 0 then d = 0 end
	return d
end

function IntervalTimer:reset(interval)
	self._interval = interval
	if self._begin then self:restart() end
	return self
end

function IntervalTimer:stop()
	if self:started() then
		local d = self:elapsed()
		self._begin = nil
		return d
	end
end

end

return {
	new = IntervalTimer.new;
}