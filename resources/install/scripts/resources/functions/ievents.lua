local ievents = function(events, ...)
	if type(events) == 'string' then
		events = freeswitch.EventConsumer(events)
	end

	local block, timeout = ...
	if timeout == 0 then block, timeout = 0, 0 end
	timeout = timeout or 0

	return function()
		local event = events:pop(block, timeout)
		if not event then return false end
		return event
	end
end

return ievents