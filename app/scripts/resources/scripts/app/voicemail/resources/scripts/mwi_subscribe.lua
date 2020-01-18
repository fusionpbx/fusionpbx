require "resources.functions.config"
require "resources.functions.split"

local service_name = "mwi"

local log               = require "resources.functions.log"[service_name]
local BasicEventService = require "resources.functions.basic_event_service"
local Database          = require "resources.functions.database"
local cache             = require "resources.functions.cache"
local mwi_notify        = require "app.voicemail.resources.functions.mwi_notify"

local vm_message_count do

local vm_to_uuid_sql = [[SELECT v.voicemail_uuid
FROM v_voicemails as v inner join v_domains as d on v.domain_uuid = d.domain_uuid
WHERE v.voicemail_id = :voicemail_id and d.domain_name = :domain_name]]

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
			uuid = dbh:first_value(vm_to_uuid_sql, {
				voicemail_id = id, domain_name = domain_name
			})

			if uuid and #uuid > 0 then
				cache.set('voicemail_uuid:' .. account, uuid, 3600)
			end
		end
	end

	local row
	if uuid and #uuid > 0 then
		local sql = string.format(vm_messages_sql, ":voicemail_uuid", ":voicemail_uuid")
		row = dbh:first_row(sql, {voicemail_uuid = uuid})
	else
		local uuid_sql = '(' .. vm_to_uuid_sql .. ')'

		local sql = string.format(vm_messages_sql,
			uuid_sql, uuid_sql
		)

		row = dbh:first_row(sql, {
			voicemail_id = id, domain_name = domain_name
		})
	end

	dbh:release()

	if not row then return end

	return row.new_messages, row.saved_messages
end

end

local service = BasicEventService.new(log, service_name)

-- MWI SUBSCRIBE
service:bind("MESSAGE_QUERY", function(self, name, event)
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

service:run()

log.notice("stop")
