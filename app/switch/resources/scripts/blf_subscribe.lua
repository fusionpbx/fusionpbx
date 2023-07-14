local proto = argv[1] or 'all'

local service_name
if proto == 'all' then
	service_name = 'blf'
else
	service_name = proto
end

require "resources.functions.config"
require "resources.functions.split"
require "resources.functions.trim";
require "resources.functions.mkdir";

--make sure the scripts/run dir exists
mkdir(scripts_dir .. "/run");

local log = require "resources.functions.log"[service_name]
local presence_in = require "resources.functions.presence_in"
local Database = require "resources.functions.database"
local BasicEventService = require "resources.functions.basic_event_service"

local find_voicemail do

	local find_voicemail_sql = [[select t1.voicemail_message_uuid
	from v_voicemail_messages t1
	inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
	inner join v_voicemails t3 on t1.voicemail_uuid = t3.voicemail_uuid
	where t2.domain_name = :domain_name 
	and t3.voicemail_id = :extension 
	and (t1.message_status is null or message_status = '')]]
	
	function find_voicemail(user)
		local ext, domain_name = split_first(user, '@', true)
		log.notice("ext: " .. ext);
		log.notice("domain_name: " .. domain_name);
		if not domain_name then return end
		local dbh = Database.new('system')
		if not dbh then return end
		local voicemail = dbh:first_row(find_voicemail_sql, {domain_name = domain_name, extension = ext})
		dbh:release()
		return voicemail
	end
	
end

local find_call_flow do

local find_call_flow_sql = [[select t1.call_flow_uuid, t1.call_flow_status
from v_call_flows t1 inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
where t2.domain_name = :domain_name and (t1.call_flow_feature_code = :feature_code
or t1.call_flow_feature_code = :short_feature_code)
]]

function find_call_flow(user)
	local ext, domain_name = split_first(user, '@', true)
	local _, short = split_first(ext, '+', true)
	if not domain_name then return end
	local dbh = Database.new('system')
	if not dbh then return end
	local row = dbh:first_row(find_call_flow_sql, {
		domain_name = domain_name, feature_code = ext, short_feature_code = short
	})
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

local find_call_forward do

local find_call_forward_sql = [[select t1.forward_all_destination, t1.forward_all_enabled
from v_extensions t1 inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
where t2.domain_name = :domain_name and (t1.extension = :extension or t1.number_alias=:extension)]]

find_call_forward = function(user)
	local ext, domain_name, number = split_first(user, '@', true)
	if not domain_name then return end
	ext, number = split_first(ext, '/', true)
	local dbh = Database.new('system')
	if not dbh then return end
	local row = dbh:first_row(find_call_forward_sql, {domain_name = domain_name, extension = ext})
	dbh:release()
	if not (row and row.forward_all_enabled) then return end
	if row.forward_all_enabled ~= 'true' then return 'false' end
	if number then
		return number == row.forward_all_destination and 'true' or 'false',
			row.forward_all_destination
	end
	return 'true', row.forward_all_destination
end

end

local find_agent_status do

local find_agent_uuid_sql = [[select t1.call_center_agent_uuid
from v_call_center_agents t1 inner join v_domains t2 on t1.domain_uuid = t2.domain_uuid
where t2.domain_name = :domain_name and t1.agent_name = :agent_name
]]

function find_agent_status(user)
	local agent_name, domain_name = split_first(user, '@', true)
	local _, short = split_first(agent_name, '+', true)
	if not domain_name then return end
	local dbh = Database.new('system')
	if not dbh then return end
	local row = dbh:first_row(find_agent_uuid_sql, {
		domain_name = domain_name, agent_name = agent_name
	})
	dbh:release()
	if not row then return end
	if row.call_center_agent_uuid then
		local cmd = "callcenter_config agent get status "..row.call_center_agent_uuid.."";
		freeswitch.consoleLog("notice", "[user status][login] "..cmd.."\n");
		user_status = trim(api:executeString(cmd));
	end
	return row.call_center_agent_uuid, user_status
end

end

local protocols = {}

protocols.voicemail = function(event)
	local from, to = event:getHeader('from'), event:getHeader('to')
	local expires = tonumber(event:getHeader('expires'))
	if expires and expires > 0 then
		local proto, user = split_first(to, '+', true)
		local voicemail_status = find_voicemail(user)
		if voicemail_status then
			log.noticef("Find VOICEMAIL: %s status: %s", to, tostring(voicemail_status))
			presence_in.turn_lamp(true, to)
		else
			log.warningf("Can not find VOICEMAIL: %s", to)
			presence_in.turn_lamp(false, to)
		end
	else
		log.noticef("%s UNSUBSCRIBE from %s", from, to)
	end
end

protocols.flow = function(event)
	local from, to = event:getHeader('from'), event:getHeader('to')
	local expires = tonumber(event:getHeader('expires'))
	if expires and expires > 0 then
		local call_flow_uuid, call_flow_status = find_call_flow(to)
		if call_flow_uuid then
			log.noticef("Find call flow: %s status: %s", to, tostring(call_flow_status))
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
			log.noticef("Find DND: %s status: %s", to, tostring(dnd_status))
			presence_in.turn_lamp(dnd_status == "true", to)
		else
			log.warningf("Can not find DND: %s", to)
		end
	else
		log.noticef("%s UNSUBSCRIBE from %s", from, to)
	end
end

protocols.forward = function(event)
	local from, to = event:getHeader('from'), event:getHeader('to')
	local expires = tonumber(event:getHeader('expires'))
	if expires and expires > 0 then
		local proto, user = split_first(to, '+', true)
		user = user or proto
		local status, number = find_call_forward(user)
		if status then
			if status == 'true' then
				log.noticef("CF: %s to number %s", to, tostring(number))
			else
				log.noticef("CF: %s disabled", to)
			end
			presence_in.turn_lamp(status == "true", to)
		else
			log.warningf("Can not find CF: %s", to)
		end
	else
		log.noticef("%s UNSUBSCRIBE from %s", from, to)
	end
end

protocols.agent = function(event)
	local from, to = event:getHeader('from'), event:getHeader('to')
	local expires = tonumber(event:getHeader('expires'))
	if expires and expires > 0 then
		local proto, user = split_first(to, '+', true)
		user = user or proto
		local call_center_agent_uuid, agent_status = find_agent_status(user)
		if agent_status then
			log.noticef("Find agent: %s status: %s", user, tostring(agent_status))
			presence_in.turn_lamp(agent_status == "Available", to)
		else
			log.warningf("Can not find agent status: %s", to)
		end
	else
		log.noticef("%s UNSUBSCRIBE from %s", from, to)
	end
end

if proto ~= 'all' then
	for name in pairs(protocols) do
		if proto ~= name then
			protocols[name] = nil
		end
	end
end

if not next(protocols) then
	log.errorf('Unknown subscribe protocol: %s', proto)
	return
end

for name in pairs(protocols) do
	log.noticef('add subscribe protocol: %s', name)
end

local service = BasicEventService.new(log, service_name)

-- FS receive SUBSCRIBE to BLF from device
service:bind("PRESENCE_PROBE", function(self, name, event)
	local proto = event:getHeader('proto')
	local handler = proto and protocols[proto]
	if not handler then return end
	return handler(event)
end)

log.notice("start")

service:run()

log.notice("stop")
