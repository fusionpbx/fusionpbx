require "resources.functions.config"
require "resources.functions.split"

local log           = require "resources.functions.log".call_flow_subscribe
local EventConsumer = require "resources.functions.event_consumer"
local presence_in   = require "resources.functions.presence_in"
local Database      = require "resources.functions.database"

local find_call_flow do

local find_call_flow_sql = [[select t1.call_flow_uuid, t1.call_flow_status
from v_call_flows t1 inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
where t2.domain_name = :domain_name and t1.call_flow_feature_code = :feature_code
]]

function find_call_flow(user)
	local ext, domain_name = split_first(user, '@', true)
	if not domain_name then return end
	local dbh = Database.new('system')
	if not dbh then return end
	local row = dbh:first_row(find_call_flow_sql, {domain_name = domain_name, feature_code = ext})
	dbh:release()
	if not row then return end
	return row.call_flow_uuid, row.call_flow_status
end

end

local find_dnd do
local find_dnd_sql = [[select t1.do_not_disturb
from v_extensions t1 inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
where t2.domain_name = :domain_name and (t1.extension = :extension or t1.number_alias=:extension)]]

find_dnd = function(user)
	local ext, domain_name = split_first(user, '@', true)
	if not domain_name then return end
	local dbh = Database.new('system')
	if not dbh then return end
	local dnd = dbh:first_value(find_dnd_sql, {domain_name = domain_name, extension = ext})
	dbh:release()
	return dnd
end

end

local service_name = "call_flow"
local pid_file = scripts_dir .. "/run/" .. service_name .. ".tmp"

local events = EventConsumer.new(pid_file)

-- FS shutdown
events:bind("SHUTDOWN", function(self, name, event)
	log.notice("shutdown")
	return self:stop()
end)

-- Control commands from FusionPBX
events:bind("CUSTOM::fusion::service::control", function(self, name, event)
	if service_name ~= event:getHeader('service-name') then return end

	local command = event:getHeader('service-command')
	if command == "stop" then
		log.notice("get stop command")
		return self:stop()
	end

	log.warningf('Unknown service command: %s', command or '<NONE>')
end)

local protocols = {}

protocols.flow = function(event)
	local from, to = event:getHeader('from'), event:getHeader('to')
	local expires = tonumber(event:getHeader('expires'))
	if expires and expires > 0 then
		local call_flow_uuid, call_flow_status = find_call_flow(to)
		if call_flow_uuid then
			log.noticef("Find call flow: %s staus: %s", to, tostring(call_flow_status))
			presence_in.turn_lamp(call_flow_status == "false", to, call_flow_uuid)
		else
			log.warningf("Can not find call flow: %s", to)
		end
	else
		log.noticef("%s UNSUBSCRIBE from %s", from, to)
	end
end

protocols.dnd = function(event)
	local from, to = event:getHeader('from'), event:getHeader('to')
	local expires = tonumber(event:getHeader('expires'))
	if expires and expires > 0 then
		local proto, user = split_first(to, '+', true)
		user = user or proto
		local dnd_status = find_dnd(user)
		if dnd_status then
			log.noticef("Find DND: %s staus: %s", to, tostring(dnd_status))
			presence_in.turn_lamp(dnd_status == "true", to)
		else
			log.warningf("Can not find DND: %s", to)
		end
	else
		log.noticef("%s UNSUBSCRIBE from %s", from, to)
	end
end

-- FS receive SUBSCRIBE to BLF from device
events:bind("PRESENCE_PROBE", function(self, name, event)
	local proto = event:getHeader('proto')
	local handler = proto and protocols[proto]
	if not handler then return end
	return handler(event)
end)

log.notice("start")

events:run()

log.notice("stop")
