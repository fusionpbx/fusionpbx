require "resources.functions.config"
require "resources.functions.split"

local log           = require "resources.functions.log".call_flow_subscribe
local file          = require "resources.functions.file"
local presence_in   = require "resources.functions.presence_in"
local Database      = require "resources.functions.database"
local ievents       = require "resources.functions.ievents"
local IntervalTimer = require "resources.functions.interval_timer"
local api           = require "resources.functions.api"

local find_call_flow_sql = [[select t1.call_flow_uuid, t1.call_flow_status
from v_call_flows t1 inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
where t2.domain_name = '%s' and t1.call_flow_feature_code = '%s'
]]

local function find_call_flow(user)
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

local sleep    = 60000
local pid_file = scripts_dir .. "/run/call_flow_subscribe.tmp"

local pid = api:execute("create_uuid") or tostring(api:getTime())

file.write(pid_file, pid)

log.notice("start call_flow_subscribe");

local timer = IntervalTimer.new(sleep):start()

for event in ievents("PRESENCE_PROBE", 1, timer:rest()) do
	if (not event) or (timer:rest() < 1000) then
		if not file.exists(pid_file) then break end
		local stored = file.read(pid_file)
		if stored and stored ~= pid then break end
		timer:restart()
	end

	if event then
		-- log.notice("event:" .. event:serialize("xml"));
		if event:getHeader('proto') == 'flow' and
			event:getHeader('Event-Calling-Function') == 'sofia_presence_handle_sip_i_subscribe'
		then
			local from, to = event:getHeader('from'), event:getHeader('to')
			local expires = tonumber(event:getHeader('expires'))
			if expires and expires > 0 then
				local call_flow_uuid, call_flow_status = find_call_flow(to)
				if call_flow_uuid then
					log.debugf("Find call flow: %s", to)
					presence_in.turn_lamp(call_flow_status == "false", to, call_flow_uuid);
				else
					log.warningf("Can not find call flow: %s", to)
				end
			else
				log.noticef("%s UNSUBSCRIBE from %s", from, to)
			end
		end
	end
end

log.notice("stop call_flow_subscribe")
