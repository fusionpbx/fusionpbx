--	emergency.lua
--	Part of FusionPBX
--	Copyright (C) 2010 - 2022 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--Description:
	--purpose: send an email queue or email for 911 calls
	--freeswitch.email(to, from, headers, body, file, convert_cmd, convert_ext)
		--to (mandatory) a valid email address
		--from (mandatory) a valid email address
		--headers (mandatory) for example "subject: you've got mail!\n"
		--body (optional) your regular mail body
		--file (optional) a file to attach to your mail
		--convert_cmd (optional) convert file to a different format before sending
		--convert_ext (optional) to replace the file's extension

--Example
	--luarun emergency.lua to@domain.com from@domain.com 'headers' 'subject' 'body'

--load libraries
        local send_mail = require 'resources.functions.send_mail'
        local Database = require "resources.functions.database"
        local Settings = require "resources.functions.lazy_settings"
	local Utils = require "resources.functions.channel_utils";

--define a function to send email
	local db = dbh or Database.new('system')
	local settings = Settings.new(db, domain_name, domain_uuid)
	local email_queue_enabled = settings:get('email_queue', 'enabled', 'boolean') or "false";

--get the argv values
	script_name = argv[0];
	delete = argv[1];

--prepare the api object
        api = freeswitch.API();

--get sessions info
if (session and session:ready()) then
	domain_uuid = session:getVariable("domain_uuid");
	domain_name = session:getVariable("domain_name");
	call_uuid = session:getVariable("uuid");
	headers = {
		["X-FusionPBX-Domain-UUID"] = domain_uuid;
		["X-FusionPBX-Domain-Name"] = domain_name;
		["X-FusionPBX-Email-Type"]	= 'app';
		["X-FusionPBX-Call-UUID"]	= call_uuid;
	}
else
	headers = {}
end

function escapeCSV(s)
	if string.find(s, '[,"]') then
		s = '"' .. string.gsub(s, '"', '""') .. '"'
	end
	return s
end

function toCSV(tt)
	local s = ""
	for _,p in ipairs(tt) do
		s = s .. "," .. escapeCSV(p)
	end
	return string.sub(s, 2)
end

--connect to the database
local dbh = Database.new('system');

--get the templates
local sql = "SELECT * FROM v_email_templates ";
	sql = sql .. "WHERE template_category = :category ";
	sql = sql .. "AND template_subcategory = :subcategory ";
	sql = sql .. "AND template_enabled = :status ";
	local params = {category = 'plugins', subcategory = 'emergency', status = 'true'}
	dbh:query(sql, params, function(row)
		subject = row.template_subject;
		body = row.template_body;
		language = row.template_language;
	end);
        if (debug["sql"]) then
                freeswitch.consoleLog("info", "[emergency] SQL: " .. sql .. "\n");
        end
	--freeswitch.consoleLog("info", "[template] SQL: " .. sql .. "body: " .. body ..  "\n");

--get email from
local sql = "SELECT * FROM v_default_settings ";
	sql = sql .. "WHERE default_setting_category = 'email' ";
	sql = sql .. "AND (default_setting_subcategory = 'smtp_from' ";
	sql = sql .. "OR default_setting_subcategory = 'smtp_from_name') ";

        if (debug["sql"]) then
                freeswitch.consoleLog("notice", "[emergency] SQL: " .. sql .. "\n");
        end

	dbh:query(sql, function(row)
		if (row.default_setting_subcategory == "smtp_from") then
			from = row.default_setting_value;
		end
		if (row.default_setting_subcategory == "smtp_from_name") then
			from_name = row.default_setting_value;
		end
        end);



-- get vars
domain_uuid = session:getVariable("domain_uuid");
call_date = session:getVariable("call_date");
caller_id_name = session:getVariable("caller_id_name");
caller_id_number = session:getVariable("caller_id_number");
sip_from_user = session:getVariable("sip_from_user");
emergency_caller_id_name = session:getVariable("emergency_caller_id_name");
emergency_caller_id_number = session:getVariable("emergency_caller_id_number");
call_duration = session:getVariable("call_duration");

--domain level check
	result = {}
local sql = "SELECT count(domain_setting_value) ";
	sql = sql .. "AS total ";
        sql = sql .. "FROM v_domain_settings ";
        sql = sql .. "WHERE domain_uuid = :domain_uuid ";
        sql = sql .. "AND domain_setting_subcategory = :emergency_email_address ";
        sql = sql .. "AND domain_setting_enabled = :status ";

	local params = {domain_uuid = domain_uuid, emergency_email_address = 'emergency_email_address', status = 't'}

	dbh:query(sql, params, function(result)
		total = result.total;
		--no emergency emails found under domain, using default
		if (total == 0 or total == nil) then
			to = {}
			local sql = "SELECT default_setting_value ";
				sql = sql .. "FROM v_default_settings ";
 				sql = sql .. "WHERE default_setting_category = :category ";
				sql = sql .. "AND default_setting_subcategory = :emergency_email_address ";
				sql = sql .. "AND default_setting_enabled = :status ";
				sql = sql .. "LIMIT 5 ";
			local params = {category = 'dialplan', emergency_email_address = 'emergency_email_address', status = 't'}
			dbh:query(sql, params, function(result)
                                for key,row in pairs(result) do
                                        table.insert(to, row);
                                        freeswitch.consoleLog("info", "[emergency] Inserted into table from default settings " .. row .. "\n");
                                end
				--add some details
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[emergency] SQL: " .. sql .. " result " .. result .. "\n");
				end
			end);
		--domain level emails max 5
		else if (tonumber(total) <= 5) then
			to = {}
			local   sql = "SELECT domain_setting_value ";
				sql = sql .. "FROM v_domain_settings ";
				sql = sql .. "WHERE domain_uuid = :domain_uuid ";
				sql = sql .. "AND domain_setting_subcategory = :emergency_email_address ";
				sql = sql .. "AND domain_setting_enabled = :status ";
			local params = {domain_uuid = domain_uuid, emergency_email_address = 'emergency_email_address', status = 't'}
			dbh:query(sql, params, function(result)
				for key,row in pairs(result) do
					table.insert(to, row);
					freeswitch.consoleLog("info", "[template] Inserted into table " .. row .. "\n");
				end
			end);
			end
		end

	end);

dbh:release()

if (#to > 0) then
	--set event
	destination_number = session:getVariable("destination_number");
	if (tonumber(destination_number) == 933) then
        	event = '933 Emergency Address Validation Service';
	else if (tonumber(destination_number) == 911) then
        	event = '911 Emergency Call';
        	end
	end

	--prepare the body
	if (body ~= nil) then
		body = body:gsub("${caller_id_name}", caller_id_name);
		body = body:gsub("${caller_id_number}", caller_id_number);
		body = body:gsub("${emergency_caller_id_name}", emergency_caller_id_name);
		body = body:gsub("${emergency_caller_id_number}", emergency_caller_id_number);
		body = body:gsub("${sip_from_user}", sip_from_user);
		body = body:gsub("${caller_id_number}", caller_id_number);
		body = body:gsub("${message_date}", call_date);
		body = body:gsub("${event}", event);
		body = trim(body);
	end

	for key,row in ipairs(to) do
		freeswitch.consoleLog("info", "[emergency] Sending to row " .. row .. " key " .. key ..  "\n");
		--send the email
		send_mail(headers,
			from,
			row,
			{subject, body}
		);
	end
end

-- Insert into Emergency Logs
emergency_logs_uuid = api:executeString("create_uuid");
domain_uuid = session:getVariable("domain_uuid");

-- Set time and date
local delimiter = " ";
local y = 0;
local tab = {}

while true do
	local endindex = call_date:find(delimiter,y);
	if not endindex then
		break
	end
	table.insert(tab,call_date:sub(y,endindex-1))
	y = endindex + 1;
end

table.insert(tab,call_date:sub(y));
local time = tab[2] .. " " .. tab[3];

freeswitch.consoleLog("info", "[emergency] Getting Date " .. tab[1]  .. " Time " .. tab[2] ..  " Format " .. tab[3] .. "\n");

--connect to the database
local dbh = Database.new('system');

local sql = "INSERT INTO v_emergency_logs ( ";
	sql = sql .. "  log_uuid, ";
	sql = sql .. "  domain_uuid, ";
	sql = sql .. "  date, ";
	sql = sql .. "  time, ";
	sql = sql .. "  extension, ";
	sql = sql .. "  event ";
	sql = sql .. ") ";
	sql = sql .. "VALUES ( ";
	sql = sql .. "  :emergency_logs_uuid, ";
	sql = sql .. "  :domain_uuid, ";
	sql = sql .. "  :date, ";
	sql = sql .. "  :time, ";
	sql = sql .. "  :extension, ";
	sql = sql .. "  :event ";
	sql = sql .. ") ";

	local params = {emergency_logs_uuid = emergency_logs_uuid,domain_uuid = domain_uuid, date = tab[1], time = time, extension = caller_id_number, event = event}

	if (debug["sql"]) then
                freeswitch.consoleLog("info", "[emergency] SQL: " .. sql .. "\n");
        end

	dbh:query(sql, params);
	dbh:release();
