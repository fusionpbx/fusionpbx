local presence_in   = require "resources.functions.presence_in"

local function blf(enabled, proto, id, domain)
	local user = string.format('%s+%s@%s', proto, id, domain)
	presence_in.turn_lamp(enabled, user)
end

local function dnd(enabled, extension, number_alias, domain)
	blf(enabled, 'dnd', extension, domain)
	if number_alias and #number_alias > 0 then
		blf(enabled, 'dnd', number_alias, domain)
	end
end

local function forward(enabled, extension, number_alias, number, domain)
	if number then
		extension = extension .. '/' .. number
		if number_alias and #number_alias > 0 then
			number_alias = number_alias .. '/' .. number
		end
	end
	blf(enabled, 'forward', extension, domain)
	if number_alias and #number_alias > 0 then
		blf(enabled, 'forward', number_alias, domain)
	end
end

local function forward_toggle(enabled, extension, number_alias, old_number, new_number, domain)
	-- turn off previews BLF number
		if old_number and #old_number > 0 and old_number ~= new_number then
			forward(false, extension, number_alias, old_number, domain)
		end

	-- set common BLF status
		forward(enabled, extension, number_alias, nil, domain)

	-- set destination specifc status
		if new_number and #new_number > 0 then
			forward(enabled, extension, number_alias, new_number, domain)
		end
end

return {
	dnd = dnd;
	forward = forward_toggle;
}
