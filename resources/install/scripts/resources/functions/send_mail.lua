local Settings = require "resources.functions.lazy_settings"
local Database = require "resources.functions.database"

if not freeswitch then
	local log = require "resources.functions.log".sendmail
	local sendmail = require "sendmail"
	local uuid = require "uuid"

	function send_mail(headers, address, message, file)
		local domain_uuid = headers["X-FusionPBX-Domain-UUID"]
		local domain_name = headers["X-FusionPBX-Domain-Name"]
		local email_type = headers["X-FusionPBX-Email-Type"] or 'info'
		local call_uuid = headers["X-FusionPBX-Email-Call-UUID"]
		local db = dbh or Database.new('system')
		local settings = Settings.new(db, domain_name, domain_uuid)

		local ssl = settings:get('email', 'smtp_secure', 'text');

		local ok, err = sendmail{
			server = {
				address = settings:get('email','smtp_host','text');
				user = settings:get('email','smtp_username','text');
				password = settings:get('email','smtp_password','text');
				ssl = (ssl == 'true') and { verify = {"none"} };
			},

			from = {
				title = settings:get('email', 'smtp_from_name', 'text');
				address = settings:get('email', 'smtp_from', 'text');
			},

			to = {
				address = address;
			},

			message = message;
			file = file;
		}

		if not ok then
			log.warningf("Mailer Error: %s", err)

			local email_uuid = uuid.new()
			local sql = "insert into v_email_logs ( "
			sql = sql .. "email_log_uuid, "
			if call_uuid then sql = sql .. "call_uuid, " end
			sql = sql .. "domain_uuid, "
			sql = sql .. "sent_date, "
			sql = sql .. "type, "
			sql = sql .. "status, "
			sql = sql .. "email "
			sql = sql .. ") values ( "
			sql = sql .. ":email_log_uuid, "
			if call_uuid then sql = sql .. ":call_uuid, " end
			sql = sql .. ":domain_uuid, "
			sql = sql .. "now(),"
			sql = sql .. ":email_type, "
			sql = sql .. "'failed', "
			sql = sql .. "'' "
			sql = sql .. ") "

			local params = {
				email_log_uuid  = email_log_uuid;
				call_uuid   = call_uuid;
				domain_uuid = domain_uuid;
				email_type  = email_type;
			}

			db:query(sql, params)

			log.infof("Retained in v_email_logs as email_log_uuid = %s", email_log_uuid)
		else
			log.infof("Mail to %s sent!", address)
		end
	end
end

if freeswitch then
	function send_mail(headers, address, message, file)
		local domain_uuid = headers["X-FusionPBX-Domain-UUID"]
		local domain_name = headers["X-FusionPBX-Domain-Name"]
		local email_type = headers["X-FusionPBX-Email-Type"] or 'info'
		local call_uuid = headers["X-FusionPBX-Email-Call-UUID"]
		local db = dbh or Database.new('system')
		local settings = Settings.new(db, domain_name, domain_uuid)
		local xheaders = "{"
		for k,v in pairs(headers) do
			xheaders = xheaders .. ('"%s":"%s",'):format(k, v)
		end
		xheaders = xheaders:sub(1,-2) .. '}'

		local from = settings:get('email', 'smtp_from', 'text')
		local from_name = settings:get('email', 'smtp_from_name', 'text')
		if from == nil or from == "" then
			from = address
		elseif from_name ~= nil and from_name ~= "" then
			from = from_name .. "<" .. from .. ">"
		end
		local subject = message[1]
		local body = message[2] or ''

		local mail_headers =
			"To: ".. address .. "\n" ..
			"From: " .. from .. "\n" ..
			"Subject: " .. subject .. "\n" ..
			"X-Headers: " .. xheaders

		if file then
			freeswitch.email(address, from, mail_headers, body, file)
		else
			freeswitch.email(address, from, mail_headers, body)
		end
	end
end

return send_mail

--local headers = {
--	["X-FusionPBX-Domain-UUID"] = '2d171c4c-b237-49ca-9d76-9cffc1618fa7';
--	["X-FusionPBX-Domain-Name"] = 'domain.com';
--	["X-FusionPBX-Email-Type"]	= 'voicemail';
--}
--send_mail(headers, 'alexey@domain.com', {'hello', 'world'})
