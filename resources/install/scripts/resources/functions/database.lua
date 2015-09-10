require 'resources.config'
require 'resources.functions.file_exists'
require 'resources.functions.database_handle'

local unpack = unpack or table.unpack

local Database = {} do

Database.__index = Database

function Database.new(name)
	local dbh = assert(name)
	if type(name) == 'string' then
		if name == 'switch' and file_exists(database_dir.."/core.db") then
			dbh = freeswitch.Dbh("sqlite://"..database_dir.."/core.db")
		else
			dbh = database_handle(name)
		end
	end
	assert(dbh:connected())

	local self = setmetatable({
		_dbh = dbh;
	}, Database)

	return self
end

function Database:query(sql, fn)
	return self._dbh:query(sql, fn)
end

function Database:first_row(sql)
	local result
	local ok, err = self:query(sql, function(row)
		result = row
		return 1
	end)
	if not ok then return nil, err end
	return result
end

function Database:first_value(sql)
	local result, err = self:first_row(sql)
	if not result then return nil, err end
	local k, v = next(result)
	return v
end

function Database:first(sql, ...)
	local result, err = self:first_row(sql)
	if not result then return nil, err end
	local t, n = {}, select('#', ...)
	for i = 1, n do
		t[i] = result[(select(i, ...))]
	end
	return unpack(t, 1, n)
end

function Database:fetch_all(sql)
	local result = {}
	local ok, err = self:query(sql, function(row)
		result[#result + 1] = row
	end)
	if not ok then return nil, err end
	return result
end

function Database:release(sql)
	if self._dbh then
		self._dbh:release()
		self._dbh = nil
	end
end

function Database:connected(sql)
	return self._dbh and self._dbh:connected()
end

function Database.__self_test__(name)
	local db = Database.new(name or 'system')
	assert(db:connected())

	assert("1" == db:first_value("select 1 as v"))

	local t = assert(db:first_row("select 1 as v"))
	assert(t.v == "1")

	t = assert(db:fetch_all("select 1 as v union all select 2 as v"))
	assert(#t == 2)
	assert(t[1].v == "1")
	assert(t[2].v == "2")

	local a, b = assert(db:first("select 1 as b, 2 as a", 'a', 'b'))
	assert(a == "2")
	assert(b == "1")

	-- assert(nil == db:first_value("some non sql query"))

	db:release()
	assert(not db:connected())
end

end

-- Database.__self_test__()

return Database