
local api = api or freeswitch.API()

function channel_variable(uuid, name)
	local result = api:executeString("uuid_getvar " .. uuid .. " " .. name)

	if result:sub(1, 4) == '-ERR' then return nil, result end
	if result == '_undef_' then return false end

	return result
end

function channel_evalute(uuid, cmd)
	local result = api:executeString("eval uuid:" .. uuid .. " " .. cmd)

	if result:sub(1, 4) == '-ERR' then return nil, result end
	if result == '_undef_' then return false end

	return result
end
