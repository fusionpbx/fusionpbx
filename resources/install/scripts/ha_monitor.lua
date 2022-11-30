--! @todo move `servers` table to `local.lua` file
--[[
servers = {}
servers[#servers + 1] = {
	method   = 'curl';
	username = "freeswitch";
	password = "freeswitch";
	hostname = "127.0.0.1";
	port     = "8080";
}
servers[#servers + 1] = {
	method   = 'ssh';
	username = "aaa";
	password = "***";
	hostname = "127.0.0.2";
	port     = "22";
}
]]

require "resources.functions.config"

local service_name = 'ha_monitor'

local log               = require "resources.functions.log"[service_name]
local BasicEventService = require "resources.functions.basic_event_service"
local api               = require "resources.functions.api"

local method   = 'curl' -- default method

local function trim(s)
	return s and (string.gsub(s, "^%s*(.-)%s*$", "%1"))
end

local function urlencode(s)
	s = string.gsub(s, "([^%w])",function(c)
		return string.format("%%%02X", string.byte(c))
	end)
	return s
end

local function emit(new_event_name)
	return function(self, name, event)
		self:emit(new_event_name, event)
	end
end

-- execute command on remote FS using mod_xml_rpc
local function curl(server, command, args)
	local url = 'http://'
	if server.username then
		url = url .. server.username .. ':' .. server.password .. '@'
	end

	url = url .. server.hostname

	if server.port then
		url = url .. ':' .. server.port
	end

	url = url .. '/webapi/' .. command

	if args then
		url = url .. '?' .. urlencode(args)
	end

	-- log.noticef('curl %s', url)

	local response, err = api:execute('curl', url)

	if err then log.warningf('[curl %s] error [%s]', url, err)
	else log.noticef('[curl %s] pass [%s]', url, response) end
end

-- execute command on remote FS using SSH access
local function ssh(server, command, args)
	local cmd = 'ssh '..server.username..'@'..server.hostname
	if server.port then
		cmd = cmd .. ':' .. server.port
	end
	cmd = cmd .. [[ "fs_cli -x ']] .. command
	if args then
		--! todo escape args
		cmd = cmd .. ' ' .. args
	end
	cmd = cmd .. [['"]]

	-- log.notice(cmd)

	local status = os.execute(cmd)

	if status ~= 0 then log.warningf('[%s] fail [%d]', cmd, status)
	else log.noticef('[%s] pass', cmd) end
end

local remote_execute_methods = {curl = curl; ssh = ssh}
local default_remote_execute = assert(remote_execute_methods[servers.method or method], 'Unknown execute method')

local function remote_execute(server, command, args)
	local exec = default_remote_execute
	if server.method then
		exec = assert(remote_execute_methods[server.method], 'Unknown execute method')
	end
	return exec(server, command, args)
end

-- execute command on all remotes FS
local function remote_execute_all(...)
	for _, server in ipairs(servers) do
		remote_execute(server, ...)
	end
end

local service = BasicEventService.new(log, service_name)

-- Lua code can not generate 'MEMCACHE' event so we use `CUSTOM::fusion::memcache`
-- and forward MEMCACHE to it for backward compatability
service:bind('MEMCACHE', emit'CUSTOM::fusion::memcache')

-- Memcache event
service:bind('CUSTOM::fusion::memcache', function(self, name, event)
	-- log.noticef('event: %s\n%s', name, event:serialize('xml'))

	local api_command = trim(event:getHeader('API-Command'))
	if api_command ~= 'memcache' then return end

	local api_command_argument = trim(event:getHeader('API-Command-Argument'))

	local memcache_updated = api_command_argument and (
		(api_command_argument == 'flush')
		or (api_command_argument:sub(1, 6) == 'delete')
	)

	if memcache_updated then
		remote_execute_all(api_command, api_command_argument)
	end
end)

-- start gateway
service:bind('CUSTOM::sofia::gateway_add', function(self, name, event)
	-- log.noticef('event: %s\n%s', name, event:serialize('xml'))

	local profile = event:getHeader('profile-name')
	local gateway = event:getHeader('Gateway')

	local cmd = 'profile ' .. profile .. ' start ' .. gateway
	remote_execute_all('sofia',  cmd)
end)

-- stop gateway
service:bind('CUSTOM::sofia::gateway_delete', function(self, name, event)
	-- log.noticef('event: %s\n%s', name, event:serialize('xml'))

	local profile = event:getHeader('profile-name')
	local gateway = event:getHeader('Gateway')

	local cmd = 'profile ' .. profile .. ' killgw ' .. gateway
	remote_execute_all('sofia',  cmd)
end)

log.notice('start')

service:run()

log.notice('stop')

