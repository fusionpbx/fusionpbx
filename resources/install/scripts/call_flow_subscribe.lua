require "resources.functions.config"
require "resources.functions.split"

local log           = require "resources.functions.log".call_flow_subscribe
local EventConsumer = require "resources.functions.event_consumer"
local presence_in   = require "resources.functions.presence_in"
local Database      = require "resources.functions.database"

local find_call_flow do

local find_call_flow_sql = [[select t1.call_flow_uuid, t1.call_flow_status
from v_call_flows t1 inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
where t2.domain_name = '%s' and t1.call_flow_feature_code = '%s'
]]

function find_call_flow(user)
	local ext, domain_name = split_first(user, '@', true)
	if not domain_name then return end
	local dbh = Database.new('system')
	if not dbh then return end
	local sql = string.format(find_call_flow_sql, dbh:escape(domain_name), dbh:escape(ext))
	local row = dbh:first_row(sql)
	dbh:release()
	if not row then return end
	return row.call_flow_uuid, row.call_flow_status
end

end

local pid_file = scripts_dir .. "/run/call_flow_subscribe.tmp"
local shutdown_event = "CUSTOM::fusion::flow::shutdown"

local events = EventConsumer.new(pid_file)

-- FS shutdown
events:bind("SHUTDOWN", function(self, name, event)
	log.notice("shutdown")
	return self:stop()
end)

-- shutdown command
if shutdown_event then
	events:bind(shutdown_event, function(self, name, event)
		log.notice("shutdown")
		return self:stop()
	end)
end

-- FS receive SUBSCRIBE to BLF from device
events:bind("PRESENCE_PROBE", function(self, name, event)
	--handle only blf with `flow+` prefix
	if event:getHeader('proto') ~= 'flow' then return end

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
end)

log.notice("start")

events:run()

log.notice("stop")
