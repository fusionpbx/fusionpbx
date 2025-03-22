-- load base64 if exists
	pcall(require, "resources.functions.base64")

-- load logger for file library
	local log = log or require "resources.functions.log"[app_name or 'file']

local base64 = base64

local function write_file(fname, data, mode)
	local file, err = io.open(fname, mode or "wb")
	if not file then
		-- log.err("Can not open file to write:" .. tostring(err))
		return nil, err
	end
	file:write(data)
	file:close()
	return true
end

-- decode and write to file
local function write_base64(fname, encoded, mode)
	return write_file(fname, base64.decode(encoded), mode)
end

local function read_file(fname, mode)
	local file, err = io.open(fname, mode or "rb")
	if not file then
		-- log.err("Can not open file to read:" .. tostring(err))
		return nil, err
	end
	local data = file:read("*all")
	file:close()
	return data
end

-- read file and encode
local function read_base64(fname, mode)
	local data, err = read_file(fname, mode)
	if not data then return nil, err end
	return base64.encode(data)
end

local function file_exists(name)
	local f = io.open(name, "r")
	if not f then return end
	f:close()
	return name
end

return {
	read         = read_file;
	read_base64  = read_base64;
	write        = write_file;
	write_base64 = write_base64;
	exists       = file_exists;
	remove       = os.remove;
	rename       = os.rename;
}