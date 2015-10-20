require 'resources.config'
require 'resources.functions.trim'
require 'resources.functions.file_exists'
require 'resources.functions.database_handle'

local function create_dbh(name)
	local dbh = assert(name)
	if type(name) == 'string' then
		if name == 'switch' and file_exists(database_dir.."/core.db") then
			dbh = freeswitch.Dbh("sqlite://"..database_dir.."/core.db")
		else
			dbh = database_handle(name)
		end
	end
	assert(dbh:connected())
	return dbh
end

local function dbh_fetch_all(dbh, sql)
	local result = {}
	local ok, err = dbh:query(sql, function(row)
		result[#result + 1] = row
	end)
	if not ok then return nil, err end
	return result
end

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

local function switchname()
	local result = api:executeString("switchname")

	if result:sub(1, 4) == '-ERR' then return nil, result end
	if result == '_undef_' then return false end

	return result
end

function channels_by_number(number, domain)
	local hostname = assert(switchname())
	local dbh = create_dbh('switch')

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

	local rows = assert(dbh_fetch_all(dbh, sql))

	dbh:release()
	return rows
end
