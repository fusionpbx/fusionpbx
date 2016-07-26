require "resources.functions.config"
require "resources.functions.split"

local log           = require "resources.functions.log".mwi_subscribe
local EventConsumer = require "resources.functions.event_consumer"
local Database      = require "resources.functions.database"
local cache         = require "resources.functions.cache"
local mwi_notify    = require "app.voicemail.resources.functions.mwi_notify"

local service_name = "mwi"
local pid_file = scripts_dir .. "/run/mwi_subscribe.tmp"

local vm_message_count do

local vm_to_uuid_sql = [[SELECT v.voicemail_uuid
FROM v_voicemails as v inner join v_domains as d on v.domain_uuid = d.domain_uuid
WHERE v.voicemail_id = '%s' and d.domain_name = '%s']]

local vm_messages_sql = [[SELECT
( SELECT count(*)
	FROM v_voicemail_messages
	WHERE voicemail_uuid = %s
	AND (message_status is null or message_status = '')
) as new_messages,

( SELECT count(*)
	FROM v_voicemail_messages
	WHERE voicemail_uuid = %s
	AND message_status = 'saved'
) as saved_messages
]]

function vm_message_count(account, use_cache)
	local id, domain_name = split_first(account, '@', true)
	if not domain_name then return end

	-- FusionPBX support only numeric voicemail id
	if not tonumber(id) then
		log.warningf('non numeric voicemail id: %s', id)
		return
	end

	local dbh = Database.new('system')
	if not dbh then return end

	local uuid
	if use_cache and cache.support() then
		local uuid = cache.get('voicemail_uuid:' .. account)
		if not uuid then
			local sql = string.format(vm_to_uuid_sql,
				dbh:escape(id), dbh:escape(domain_name)
			)
			uuid = dbh:first_value(sql)

			if uuid and #uuid > 0 then
				cache.set('voicemail_uuid:' .. account, uuid, 3600)
			end
		end
	end

	local sql 
	if uuid and #uuid > 0 then
		sql = string.format(vm_messages_sql,
			dbh:quoted(uuid), dbh:quoted(uuid)
		)
	else
		local uuid_sql = '(' .. string.format(vm_to_uuid_sql,
			dbh:escape(id), dbh:escape(domain_name)
		) .. ')'

		sql = string.format(vm_messages_sql,
			uuid_sql, uuid_sql
		)
	end

	local row = sql and dbh:first_row(sql)

	dbh:release()

	if not row then return end

	return row.new_messages, row.saved_messages
end

end

local events = EventConsumer.new(pid_file)

-- FS shutdown
events:bind("SHUTDOWN", function(self, name, event)
	log.notice("shutdown")
	return self:stop()
end)

-- Control commands from FusionPBX
events:bind("CUSTOM::fusion::service::" .. service_name, function(self, name, event)
	local command = event:getHeader('service-command')
	if command == "stop" then
		log.notice("get stop command")
		return self:stop()
	end

	log.warningf('Unknown service command: %s', command or '<NONE>')
end)

-- MWI SUBSCRIBE
events:bind("MESSAGE_QUERY", function(self, name, event)
	local account_header = event:getHeader('Message-Account')
	if not account_header then
		return log.warningf("MWI message without `Message-Account` header")
	end

	local proto, account = split_first(account_header, ':', true)

	if (not account) or (proto ~= 'sip' and proto ~= 'sips') then
		return log.warningf("invalid format for voicemail id: %s", account_header)
	end

	local new_messages, saved_messages = vm_message_count(account)
	if not new_messages then
		return log.warningf('can not find voicemail: %s', account)
	end

	log.noticef('voicemail %s has %s/%s message(s)', account, new_messages, saved_messages)
	mwi_notify(account, new_messages, saved_messages)
end)

log.notice("start")

events:run()

log.notice("stop")
