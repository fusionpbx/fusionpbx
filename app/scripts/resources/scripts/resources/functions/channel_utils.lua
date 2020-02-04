require 'resources.functions.config'
require 'resources.functions.trim'

local Database = require 'resources.functions.database'

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

function channel_transfer(uuid, ext, typ, ctx)
	local cmd = ("uuid_transfer %s %s %s %s"):format(uuid, ext, typ, ctx)
	local result = trim(api:executeString(cmd))
	if result:sub(1, 4) == '-ERR' then return nil, result end
	if result == '_undef_' then return false end
	return result
end

function channel_kill(uuid, cause)
	cause = cause or 'CALL_REJECTED'
	local res = trim(api:executeString("uuid_kill " .. uuid .. " " .. cause))
	return res == '+OK'
end

function channel_display(uuid, text)
	local cmd = ("uuid_display %s '%s'"):format(uuid, text)
	local result = trim(api:executeString(cmd))
	if result:sub(1, 4) == '-ERR' then return nil, result end
	if result == '_undef_' then return false end
	return result
end

local _switchname
local function switchname()
	if _switchname then return _switchname end

	local result = api:executeString("switchname")

	if result:sub(1, 4) == '-ERR' then return nil, result end
	if result == '_undef_' then return false end

	_switchname = result
	return result
end

function channels_by_number(number, domain)
	local hostname = assert(switchname())
	local dbh = Database.new('switch')

	local full_number = number .. '@' .. (domain or '%')

	local sql = ([[select * from channels where hostname='%s' and (
		(context = '%s' and (cid_name = '%s' or cid_num = '%s'))
		or name like '%s' or presence_id like '%s' or presence_data like '%s'
		)
		order by created_epoch
	]]):format(hostname,
		domain, number, number,
		full_number, full_number, full_number
	)

	local rows = assert(dbh:fetch_all(sql))

	dbh:release()
	return rows
end
