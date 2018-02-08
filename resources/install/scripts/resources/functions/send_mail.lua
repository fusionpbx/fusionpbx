--  FusionPBX
--  Version: MPL 1.1
--  
--  The contents of this file are subject to the Mozilla Public License Version
--  1.1 (the "License"); you may not use this file except in compliance with
--  the License. You may obtain a copy of the License at
--  http://www.mozilla.org/MPL/
--  
--  Software distributed under the License is distributed on an "AS IS" basis,
--  WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--  for the specific language governing rights and limitations under the
--  License.
--  
--  The Original Code is FusionPBX
--  
--  The Initial Developer of the Original Code is
--  Mark J Crane <markjcrane@fusionpbx.com>
--  Portions created by the Initial Developer are Copyright (C) 2008-2016
--  the Initial Developer. All Rights Reserved.
--  
--  Contributor(s):
--  Mark J Crane <markjcrane@fusionpbx.com>

--load libraries
	local Settings = require "resources.functions.lazy_settings"
	local Database = require "resources.functions.database"

--use sendmail if we don't have freeswitch API availible
	if not freeswitch then
		local log = require "resources.functions.log".sendmail
		local sendmail = require "sendmail"
		local uuid = require "uuid"

--define a function to send email
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
				--log the result if it failed
					log.warningf("Mailer Error: %s", err)

					local email_uuid = uuid.new()
					local sql = "insert into v_emails ( "
					sql = sql .. "email_uuid, "
					if call_uuid then sql = sql .. "call_uuid, " end
					sql = sql .. "domain_uuid, "
					sql = sql .. "sent_date, "
					sql = sql .. "type, "
					sql = sql .. "status, "
					sql = sql .. "email "
					sql = sql .. ") values ( "
					sql = sql .. ":email_uuid, "
					if call_uuid then sql = sql .. ":call_uuid, " end
					sql = sql .. ":domain_uuid, "
					sql = sql .. "now(),"
					sql = sql .. ":email_type, "
					sql = sql .. "'failed', "
					sql = sql .. "'' "
					sql = sql .. ") "

					local params = {
						email_uuid  = email_uuid;
						call_uuid   = call_uuid;
						domain_uuid = domain_uuid;
						email_type  = email_type;
					}

					db:query(sql, params)

					log.infof("Retained in v_emails as email_uuid = %s", email_uuid)
			else
				log.infof("Mail to %s sent!", address)
			end
		end
	end

--use freeswitch API
	if freeswitch then
--define a function to send email
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
