-- @usage local log = require"resources.functions.log"["xml_handler"]
-- log.notice("hello world")
-- log.noticef("%s %s", "hello", "world")
-- -- log if debug.SQL or debug.xml_handler.SQL then
-- log.tracef("SQL", "SQL is %s", sql)
local log if freeswitch then
	log = function (name, level, msg)
		freeswitch.consoleLog(level, "[" .. name .. "] " .. msg .. "\n")
	end
else
	log = function (name, level, msg)
		print(os.date("%Y-%m-%d %X") .. '[' .. level:upper() .. '] [' .. name .. '] ' .. msg)
	end
end

local function logf(name, level, ...)
	return log(name, level, string.format(...))
end

local function trace(type, name, ...)
	local t = debug[name]
	if t and t[type] ~= nil then
		if t[type] then
			return log(name, ...)
		end
	end
	if debug[type] then
		log(name, ...)
	end
end

local function tracef(type, name, level, ...)
	local t = debug[name]
	if t and t[type] ~= nil then
		if t[type] then
			return logf(name, ...)
		end
	end
	if debug[type] then
		logf(name, ...)
	end
end

local LEVELS = {
	'err',
	'warning',
	'notice',
	'info',
	'debug',
}

local TRACE_LEVEL = 'notice'

local function make_log(name)
	local logger = {}
	for i = 1, #LEVELS do
		logger[ LEVELS[i] ] = function(...) return log(name, LEVELS[i], ...) end;
		logger[ LEVELS[i] .. "f" ] = function(...) return logf(name, LEVELS[i], ...) end;
	end

	logger.trace = function(type, ...)
		trace(type, name, TRACE_LEVEL, ...)
	end

	logger.tracef = function(type, ...)
		tracef(type, name, TRACE_LEVEL, ...)
	end

	return logger
end

return setmetatable({}, {__index = function(self, name)
	local logger = make_log(name)
	self[name] = logger
	return logger
end})