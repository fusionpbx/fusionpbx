local ievents = function(events, ...)
	if type(events) == 'string' then
		events = freeswitch.EventConsumer(events)
	elseif type(events) == 'table' then
		local array = events
		events = freeswitch.EventConsumer()
		for _, event in ipairs(array) do
			local base, sub
			if type(event) == 'table' then
				base, sub = event[1], event[2]
			else
				base = event
			end
			if not sub then events:bind(base)
			else events:bind(base, sub) end
		end
	end

	local block, timeout = ...
	if timeout and (timeout == 0) then block, timeout = 0, 0 end
	timeout = timeout or 0

	return function()
		local event = events:pop(block, timeout)
		if not event then return false end
		return event
	end
end

return ievents