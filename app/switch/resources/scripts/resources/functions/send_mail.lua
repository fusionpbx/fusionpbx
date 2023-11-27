local Database = require "resources.functions.database";
local Settings = require "resources.functions.lazy_settings";
local cache = require"resources.functions.cache";
local log = require "resources.functions.log".send_mail

local db = dbh or Database.new('system');
local settings = Settings.new(db, domain_name, domain_uuid)
local email_queue_enabled = "true";

if (email_queue_enabled == 'true') then
	function send_mail(headers, email_from, email_address, email_message, email_file)

		--include json library
		local json
		if (debug["sql"]) then
			json = require "resources.functions.lunajson"
		end

		local domain_uuid = headers["X-FusionPBX-Domain-UUID"];
		local domain_name = headers["X-FusionPBX-Domain-Name"];
		local email_type = headers["X-FusionPBX-Email-Type"] or 'info';
		local call_uuid = headers["X-FusionPBX-Call-UUID"];
		local local_after_email = headers["X-FusionPBX-local_after_email"] or '';

		if (call_uuid ~= nil) then
			email_uuid = call_uuid;
		else
			api = freeswitch.API();
			email_uuid = api:executeString("create_uuid");
		end

		if (local_after_email == 'false') then
			email_action_after = 'delete';
		else
			email_action_after = '';
		end

		local db = dbh or Database.new('system');
		local settings = Settings.new(db, domain_name, domain_uuid);

		if (email_from == nil or email_from == "") then
			email_from = settings:get('email', 'smtp_from', 'text');
			from_name = settings:get('email', 'smtp_from_name', 'text');
		end

		if (email_from == nil or email_from == "") then
			email_from = address;
		elseif (from_name ~= nil and from_name ~= "") then
			email_from = from_name .. "<" .. email_from .. ">";
		end
		local email_subject = email_message[1];
		local email_body = email_message[2] or '';
		local email_status = 'waiting';

		api = freeswitch.API();
		local email_queue_uuid = api:executeString("create_uuid");
		local hostname = api:executeString("hostname");

		local sql = "insert into v_email_queue ( ";
		sql = sql .. "	email_queue_uuid, ";
		sql = sql .. "	domain_uuid, ";
		sql = sql .. "	hostname, ";
		sql = sql .. "	email_date, ";
		sql = sql .. "	email_from, ";
		sql = sql .. "	email_to, ";
		sql = sql .. "	email_subject, ";
		sql = sql .. "	email_body, ";
		sql = sql .. "	email_status, ";
		sql = sql .. "	email_uuid, ";
		sql = sql .. "	email_action_after, ";
		sql = sql .. "	insert_date ";
		sql = sql .. ") ";
		sql = sql .. "values ( ";
		sql = sql .. "	:email_queue_uuid, ";
		sql = sql .. "	:domain_uuid, ";
		sql = sql .. "	:hostname, ";
		sql = sql .. "	now(), ";
		sql = sql .. "	:email_from, ";
		sql = sql .. "	:email_to, ";
		sql = sql .. "	:email_subject, ";
		sql = sql .. "	:email_body, ";
		sql = sql .. "	:email_status, ";
		sql = sql .. "	:email_uuid, ";
		sql = sql .. "	:email_action_after, ";
		sql = sql .. "	now() ";
		sql = sql .. ") ";
		local params = {
			email_queue_uuid = email_queue_uuid;
			domain_uuid = domain_uuid;
			hostname = hostname;
			email_from = email_from;
			email_to = email_address;
			email_subject = email_subject;
			email_body = email_body;
			email_status = email_status;
			email_uuid = email_uuid;
			email_action_after = email_action_after;
		}
		db:query(sql, params);

		if (email_file) then
			email_attachment_type = string.sub(email_file, -3);
			email_attachment_path = email_file;
			email_attachment_name = '';
			email_attachment_base64 = '';

			--set the mime type
			if (email_attachment_type == 'jpg' or email_attachment_type == 'peg') then --jpeg
				email_attachment_mime_type = 'image/jpeg';
			elseif (email_attachment_type == 'gif') then
				email_attachment_mime_type = 'image/gif';
			elseif (email_attachment_type == 'png') then
				email_attachment_mime_type = 'image/png';
			elseif (email_attachment_type == 'pdf') then
				email_attachment_mime_type = 'application/pdf';
			elseif (email_attachment_type == 'tif' or email_attachment_type == 'iff') then --tiff
				email_attachment_mime_type = 'image/tiff';
			elseif (email_attachment_type == 'mp3') then
				email_attachment_mime_type = 'audio/mpeg';
			elseif (email_attachment_type == 'wav') then
				email_attachment_mime_type = 'audio/x-wav';
			elseif (email_attachment_type == 'pus') then --opus
				email_attachment_mime_type = 'audio/opus';
			elseif (email_attachment_type == 'ogg') then
				email_attachment_mime_type = 'audio/ogg';
			else
				email_attachment_mime_type = 'binary/octet-stream';
			end

			require "resources.functions.split"
			local email_table = split(email_file, '/', true)
			email_attachment_name = email_table[#email_table]

			email_attachment_path = email_file.sub(email_file, 0, (string.len(email_file) - string.len(email_attachment_name)) - 1);
			--freeswitch.consoleLog("notice", "[send_email] voicemail path: " .. email_attachment_path .. "/" .. email_attachment_name .. "\n");

			--base64 encode the file
			--local file = require "resources.functions.file"
			--email_attachment_base64 = assert(file.read_base64(email_file));

			local email_queue_attachment_uuid = api:executeString("create_uuid");

			local sql = "insert into v_email_queue_attachments ( ";
			sql = sql .. "	email_queue_attachment_uuid, ";
			sql = sql .. "	email_queue_uuid, ";
			sql = sql .. "	domain_uuid, ";
			sql = sql .. "	email_attachment_mime_type, ";
			sql = sql .. "	email_attachment_type, ";
			sql = sql .. "	email_attachment_path, ";
			sql = sql .. "	email_attachment_name, ";
			sql = sql .. "	email_attachment_base64, ";
			sql = sql .. "	insert_date ";
			sql = sql .. ") ";
			sql = sql .. "values ( ";
			sql = sql .. "	:email_queue_attachment_uuid, ";
			sql = sql .. "	:email_queue_uuid, ";
			sql = sql .. "	:domain_uuid, ";
			sql = sql .. "	:email_attachment_mime_type, ";
			sql = sql .. "	:email_attachment_type, ";
			sql = sql .. "	:email_attachment_path, ";
			sql = sql .. "	:email_attachment_name, ";
			sql = sql .. "	:email_attachment_base64, ";
			sql = sql .. "	now() ";
			sql = sql .. ") ";
			local params = {
				email_queue_attachment_uuid = email_queue_attachment_uuid;
				email_queue_uuid = email_queue_uuid;
				domain_uuid = domain_uuid;
				email_attachment_mime_type = email_attachment_mime_type;
				email_attachment_type = email_attachment_type;
				email_attachment_path = email_attachment_path;
				email_attachment_name = email_attachment_name;
				email_attachment_base64 = email_attachment_base64;
			}
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[send_email] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			db:query(sql, params);
		end

		log.infof("Email added to the queue as email_queue_uuid = %s", email_queue_uuid);
	end

else

	--local headers = {
	--	["X-FusionPBX-Domain-UUID"] = '2d171c4c-b237-49ca-9d76-9cffc1618fa7';
	--	["X-FusionPBX-Domain-Name"] = 'domain.com';
	--	["X-FusionPBX-Email-Type"]	= 'voicemail';
	--}
	--send_mail(headers, 'alexey@domain.com', {'hello', 'world'})

	if not freeswitch then
		local log = require "resources.functions.log".sendmail
		local sendmail = require "sendmail"
		local uuid = require "uuid"

		function send_mail(headers, from, address, message, file)
			local domain_uuid = headers["X-FusionPBX-Domain-UUID"]
			local domain_name = headers["X-FusionPBX-Domain-Name"]
			local email_type = headers["X-FusionPBX-Email-Type"] or 'info'
			local call_uuid = headers["X-FusionPBX-Call-UUID"]
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

			log.infof("Mail to %s sent!", address)
		end
	end

	if freeswitch then
		function send_mail(headers, from, address, message, file)
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

			if (from == nil or from == "") then
				from = settings:get('email', 'smtp_from', 'text')
				from_name = settings:get('email', 'smtp_from_name', 'text')
			end
			if from == nil or from == "" then
				from = address
			elseif from_name ~= nil and from_name ~= "" then
				from = from_name .. "<" .. from .. ">"
			end

			local subject = message[1]
			local body = message[2] or ''
			
			--debug info
			--freeswitch.consoleLog("notice", "[voicemail] from: " .. from .. "\n");
			--freeswitch.consoleLog("notice", "[voicemail] subject: " .. subject .. "\n");

			local mail_headers =
				"To: ".. address .. "\n" ..
				"From: " .. from .. "\n" ..
				"Subject: " .. subject .. "\n" ..
				"X-Headers: " .. xheaders

			if file then
				ok = freeswitch.email(address, from, mail_headers, body, file)
			else
				ok = freeswitch.email(address, from, mail_headers, body)
			end
			return ok
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
