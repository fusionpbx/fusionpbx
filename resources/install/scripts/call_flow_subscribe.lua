require "resources.functions.config"
require "resources.functions.split"

local log         = require "resources.functions.log".call_flow_subscribe
local file        = require "resources.functions.file"
local presence_in = require "resources.functions.presence_in"
local Database    = require "resources.functions.database"

local unpack = unpack or table.unpack

local ievents = function(events, ...)
	local n, a = select("#", ...), {...}
	return function()
		local block, timeout = unpack(a, 1, n)
		if timeout and (timeout == 0) then
			block, timeout = 0, 0
		end
		local event = events:pop(block, timeout or 0)
		return not event, event
	end
end

local find_call_flow_sql = [[select t1.call_flow_uuid, t1.call_flow_status
from v_call_flows t1 inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
where t2.domain_name = '%s' and t1.call_flow_feature_code = '%s'
]]

local function find_call_flow(user)
	local ext, domain_name = split_first(user, '@', true)
	if not domain_name then return end
	local sql = string.format(find_call_flow_sql, domain_name, ext)
	local dbh = Database.new('system')
	if not dbh then return end
	local row = dbh:first_row(sql)
	dbh:release()
	if not row then return end
	return row.call_flow_uuid, row.call_flow_status
end

local IntervalTimer = {} do
IntervalTimer.__index = IntervalTimer

function IntervalTimer.new(interval)
	local o = setmetatable({}, IntervalTimer)
	o._interval = interval
	return o
end

function IntervalTimer:rest()
	local d = self._interval - os.difftime(os.time(), self._begin)
	if d < 0 then d = 0 end
	return d
end

function IntervalTimer:start()
	self._begin = os.time()
	return self
end

function IntervalTimer:stop()
	self._begin = nil
	return self
end

end

local events = freeswitch.EventConsumer("PRESENCE_PROBE");
local api = freeswitch.API();

local pid_file = scripts_dir .. "/run/call_flow_subscribe.tmp";

file.write(pid_file, "remove this file to stop the script")

log.notice("start call_flow_subscribe");

local timer = IntervalTimer.new(60):start()

for timeout, event in ievents(events, 1, timer:rest() * 1000) do
	if timeout or timer:rest() == 0 then
		if not file.exists(pid_file) then break end
		timer:start()
	end

	if event then
		-- log.notice("event:" .. event:serialize("xml"));
		if event:getHeader('Event-Calling-Function') == 'sofia_presence_handle_sip_i_subscribe' then
			local from, to = event:getHeader('from'), event:getHeader('to')
			if to and string.find(to, '^flow%+') then
				local expires = event:getHeader('expires')
				expires = expires and tonumber(expires)
				if expires and expires > 0 then
					local call_flow_uuid, call_flow_status = find_call_flow(to)
					if call_flow_uuid then
						log.debugf("Find call flow: %s", to)
						presence_in.turn_lamp(call_flow_status == "false", to, call_flow_uuid);
					else
						log.warningf("Can not find call flow: %s", to)
					end
				else
					log.debugf("%s UNSUBSCRIBE from %s", from, to)
				end
			end
		end
	end
end

file.remove(pid_file)

log.notice("stop call_flow_subscribe")
