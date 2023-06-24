require "resources.functions.config"

local EventConsumer = require "resources.functions.event_consumer".EventConsumer

local function class(base)
	local t = base and setmetatable({}, base) or {}
	t.__index = t
	t.__class = t
	t.__base  = base

	function t.new(...)
		local o = setmetatable({}, t)
		if o.__init then
			if t == ... then -- we call as Class:new()
				return o:__init(select(2, ...))
			else             -- we call as Class.new()
				return o:__init(...)
			end
		end
		return o
	end

	return t
end

local BasicEventService = class(EventConsumer) do

function BasicEventService:__init(log, service_name, timeout)
	local pid_file = scripts_dir .. "/run/" .. service_name .. ".tmp"

	self = BasicEventService.__base.__init(self, pid_file, timeout)

	-- FS shutdown
	self:bind("SHUTDOWN", function(self, name, event)
		log.notice("shutdown")
		return self:stop()
	end)

	-- Control commands from FusionPBX
	self:bind("CUSTOM::fusion::service::control", function(self, name, event)
		if service_name ~= event:getHeader('service-name') then return end

		local command = event:getHeader('service-command')
		if command == "stop" then
			log.notice("get stop command")
			return self:stop()
		end

		log.warningf('Unknown service command: %s', command or '<NONE>')
	end)

	return self 
end

end

return {
	BasicEventService = BasicEventService;
	new = BasicEventService.new;
}