
local send_mail

if not freeswitch then
  local Settings = require "resources.functions.lazy_settings"
  local Database = require "resources.functions.database"
  local log      = require "resources.functions.log".sendmail
  local sendmail = require "sendmail"
  local uuid     = require "uuid"

  function send_mail(headers, address, message, file)
    local domain_uuid = headers["X-FusionPBX-Domain-UUID"]
    local domain_name = headers["X-FusionPBX-Domain-Name"]
    local email_type  = headers["X-FusionPBX-Email-Type"] or 'info'
    local call_uuid   = headers["X-FusionPBX-Email-Type"]
    local db          = dbh or Database.new('system')
    local settings    = Settings.new(db, domain_name, domain_uuid)

    local ssl = settings:get('email', 'smtp_secure', 'var');

    local ok, err = sendmail{
      server = {
        address  = settings:get('email','smtp_host','var');
        user     = settings:get('email','smtp_username','var');
        password = settings:get('email','smtp_password','var');
        ssl      = (ssl == 'true') and { verify = {"none"} };
      },

      from = {
        title    = settings:get('email', 'smtp_from_name', 'var');
        address  = settings:get('email', 'smtp_from', 'var');
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
      local sql  = "insert into v_emails ( "
      sql = sql .. "email_uuid, "
      if call_uuid then sql = sql .. "call_uuid, " end
      sql = sql .. "domain_uuid, "
      sql = sql .. "sent_date, "
      sql = sql .. "type, "
      sql = sql .. "status, "
      sql = sql .. "email "
      sql = sql .. ") values ( "
      sql = sql .. "'" .. email_uuid                         .. "', "
      if call_uuid then sql = sql .. "'" .. call_uuid   .. "', " end
      sql = sql .. "'" .. domain_uuid .. "', "
      sql = sql .. "now(),"
      sql = sql .. "'" .. email_type  .. "', "
      sql = sql .. "'failed', "
      sql = sql .. "'' "
      sql = sql .. ") "

      db:query(sql)

      log.infof("Retained in v_emails as email_uuid = %s", email_uuid)
    else
      log.infof("Mail to %s sent!", address)
    end
  end
end

if freeswitch then
  function send_mail(headers, address, message, file)
    local xheaders = "{"
    for k,v in pairs(headers) do
      xheaders = xheaders .. ('"%s":"%s",'):format(k, v)
    end
    xheaders = xheaders:sub(1,-2) .. '}'

    local subject = message[1]
    local body    = message[2] or ''

    local mail_headers =
      "To: "        .. address  .. "\n" ..
      "From: "      .. address  .. "\n" ..
      "Subject: "   .. subject  .. "\n" ..
      "X-Headers: " .. xheaders

    if file then
      freeswitch.email(address, address, mail_headers, body, file)
    else
      freeswitch.email(address, address, mail_headers, body, file)
    end
  end
end

return send_mail

-- local headers = {
--   ["X-FusionPBX-Domain-UUID"] = '2d171c4c-b237-49ca-9d76-9cffc1618fa7';
--   ["X-FusionPBX-Domain-Name"] = 'domain.com';
--   ["X-FusionPBX-Email-Type"]  = 'voicemail';
-- }
-- send_mail(headers, 'alexey@domain.com', {'hello', 'world'})


