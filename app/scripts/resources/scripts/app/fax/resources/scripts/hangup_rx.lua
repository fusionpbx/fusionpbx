--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2015-2019
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--		Mark J. Crane

--set the debug options
	debug["sql"] = true;

--create the api object
	api = freeswitch.API();

--include config.lua
	require "resources.functions.config";

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--define the explode function
	require "resources.functions.explode";

--array count
	require "resources.functions.count";

	local IS_WINDOWS = (package.config:sub(1,1) == '\\')

	local function quote(s)
		local q = IS_WINDOWS and '"' or "'"
		if s:find('%s') or s:find(q, nil, true) then
			s = q .. s:gsub(q, q..q) .. q
		end
		return s
	end

-- escape shell arguments to prevent command injection
        local function shell_esc(x)
                return (x:gsub('\\', '\\\\')
                       :gsub('\'', '\\\''))
        end

-- set channel variables to lua variables
	domain_uuid = env:getHeader("domain_uuid");
	domain_name = env:getHeader("domain_name");

--get the domain_uuid using the domain name required for multi-tenant
	if (domain_name ~= nil) then
		sql = "SELECT domain_uuid FROM v_domains ";
		sql = sql .. "WHERE domain_name = :domain_name ";
		dbh:query(sql, {domain_name = domain_name}, function(rows)
			domain_uuid = rows["domain_uuid"];
		end);
	end

--settings
	require "resources.functions.settings";
	settings = settings(domain_uuid);
	storage_type = "";
	storage_path = "";
	if (settings['fax'] ~= nil) then
		if (settings['fax']['storage_type'] ~= nil) then
			if (settings['fax']['storage_type']['text'] ~= nil) then
				storage_type = settings['fax']['storage_type']['text'];
			end
		end
		if (settings['fax']['storage_path'] ~= nil) then
			if (settings['fax']['storage_path']['text'] ~= nil) then
				storage_path = settings['fax']['storage_path']['text'];
				storage_path = storage_path:gsub("${domain_name}", domain_name);
				storage_path = storage_path:gsub("${voicemail_id}", voicemail_id);
				storage_path = storage_path:gsub("${voicemail_dir}", voicemail_dir);
			end
		end
	end

-- show all channel variables
	serialized = env:serialize()
	freeswitch.consoleLog("INFO","[fax]\n" .. serialized .. "\n")

-- example channel variables relating to fax
	--variable_fax_success: 0
	--variable_fax_result_code: 49
	--variable_fax_result_text: The%20call%20dropped%20prematurely
	--variable_fax_ecm_used: off
	--variable_fax_local_station_id: SpanDSP%20Fax%20Ident
	--variable_fax_document_transferred_pages: 0
	--variable_fax_document_total_pages: 0
	--variable_fax_image_resolution: 0x0
	--variable_fax_image_size: 0
	--variable_fax_bad_rows: 0
	--variable_fax_transfer_rate: 14400

-- set channel variables to lua variables
	fax_uuid = env:getHeader("fax_uuid");
	uuid = env:getHeader("uuid");
	fax_success = env:getHeader("fax_success");
	fax_result_text = env:getHeader("fax_result_text");
	fax_local_station_id = env:getHeader("fax_local_station_id");
	fax_ecm_used = env:getHeader("fax_ecm_used");
	fax_uri = env:getHeader("fax_uri");
	fax_extension_number = env:getHeader("fax_extension_number");
	caller_id_name = env:getHeader("caller_id_name");
	caller_id_number = env:getHeader("caller_id_number");
	fax_bad_rows = env:getHeader("fax_bad_rows");
	fax_transfer_rate = env:getHeader("fax_transfer_rate");
	sip_to_user = env:getHeader("sip_to_user");
	bridge_hangup_cause = env:getHeader("bridge_hangup_cause");
	fax_result_code = env:getHeader("fax_result_code");
	fax_remote_station_id = env:getHeader("fax_remote_station_id");
	fax_document_total_pages = env:getHeader("fax_document_total_pages");
	hangup_cause_q850 = tonumber(env:getHeader("hangup_cause_q850"));
	fax_file = env:getHeader("fax_file");

-- prevent nil errors
	if (fax_file == nil) then
		fax_file = env:getHeader("fax_filename");
	end
	if (fax_uri == nil) then
		fax_uri = "";
	end
	if (fax_remote_station_id == nil) then
		fax_remote_station_id = "";
	end
	if (caller_id_name == nil) then
		caller_id_name = env:getHeader("Caller-Caller-ID-Name");
	end
	if (caller_id_number == nil) then
		caller_id_number = env:getHeader("Caller-Caller-ID-Number");
	end

--set default values
	if (not fax_success) then
		fax_success = "0";
		fax_result_code = 2;
	end
	if (hangup_cause_q850 == "17") then
		fax_success = "0";
		fax_result_code = 2;
	end
	if (not fax_result_text) then
		fax_result_text = "FS_NOT_SET";
	end

--get the fax settings from the database
	local sql = [[SELECT * FROM v_fax
		WHERE fax_uuid = :fax_uuid
		AND domain_uuid = :domain_uuid]];
	local params = {fax_uuid = fax_uuid, domain_uuid = domain_uuid};
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[fax] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
	end
	dbh:query(sql, params, function(row)
		dialplan_uuid = row["dialplan_uuid"];
		fax_extension = row["fax_extension"];
		fax_accountcode = row["accountcode"];
		fax_destination_number = row["fax_destination_number"];
		fax_name = row["fax_name"];
		fax_prefix = row["fax_prefix"];
		fax_email = row["fax_email"];
		fax_email_connection_type = row["fax_email_connection_type"];
		fax_email_connection_host = row["fax_email_connection_host"];
		fax_email_connection_port = row["fax_email_connection_port"];
		fax_email_connection_security = row["fax_email_connection_security"];
		fax_email_connection_validate = row["fax_email_connection_validate"];
		fax_email_connection_username = row["fax_email_connection_username"];
		fax_email_connection_password = row["fax_email_connection_password"];
		fax_email_connection_mailbox = row["fax_email_connection_mailbox"];
		fax_email_inbound_subject_tag = row["fax_email_inbound_subject_tag"];
		fax_email_outbound_subject_tag = row["fax_email_outbound_subject_tag"];
		fax_email_outbound_authorized_senders = row["fax_email_outbound_authorized_senders"];
		fax_caller_id_name = row["fax_caller_id_name"];
		fax_caller_id_number = row["fax_caller_id_number"];
		fax_forward_number = row["fax_forward_number"];
		fax_description = row["fax_description"];
	end);

--get the values from the fax file
	if (fax_file ~= nil) then
		array = explode("/", fax_file);
		fax_file_name = array[count(array)];
	end

--fax to email
	-- cmd = "lua" .. " " .. quote(scripts_dir .. "/fax_to_email.lua") .. " ";
	cmd = quote(shell_esc(php_dir).."/"..shell_esc(php_bin)).." "..quote(shell_esc(document_root).."/secure/fax_to_email.php").." ";
	cmd = cmd .. "email="..quote(shell_esc(fax_email)).." ";
	cmd = cmd .. "extension="..quote(shell_esc(fax_extension)).." ";
	cmd = cmd .. "name="..quote(shell_esc(fax_file)).." ";
	cmd = cmd .. "messages=" .. quote("result:"..shell_esc(fax_result_text).." sender:"..shell_esc(fax_remote_station_id).." pages:"..shell_esc(fax_document_total_pages)).." ";
	cmd = cmd .. "domain="..quote(shell_esc(domain_name)).." ";
	cmd = cmd .. "caller_id_name=" .. quote(shell_esc(caller_id_name) or '') .. " ";
	cmd = cmd .. "caller_id_number=" .. quote(shell_esc(caller_id_number) or '') .. " ";
	if #fax_forward_number > 0 then
		cmd = cmd .. "fax_relay=true ";
	else
		cmd = cmd .. "fax_relay=false ";
	end
	if #fax_prefix > 0 then
		cmd = cmd .. "fax_prefix=true ";
	else
		cmd = cmd .. "fax_prefix=false ";
	end
	freeswitch.consoleLog("notice", "[fax] command: " .. cmd .. "\n");
	result = api:execute("system", cmd);

--add to fax logs
	sql = "insert into v_fax_logs ";
	sql = sql .. "(";
	sql = sql .. "fax_log_uuid, ";
	sql = sql .. "domain_uuid, ";
	if (fax_uuid ~= nil) then
		sql = sql .. "fax_uuid, ";
	end
	sql = sql .. "fax_success, ";
	sql = sql .. "fax_result_code, ";
	sql = sql .. "fax_result_text, ";
	sql = sql .. "fax_file, ";
	if (fax_ecm_used ~= nil) then
		sql = sql .. "fax_ecm_used, ";
	end
	if (fax_local_station_id ~= nil) then
		sql = sql .. "fax_local_station_id, ";
	end
	sql = sql .. "fax_document_transferred_pages, ";
	sql = sql .. "fax_document_total_pages, ";
	if (fax_image_resolution ~= nil) then
		sql = sql .. "fax_image_resolution, ";
	end
	if (fax_image_size ~= nil) then
		sql = sql .. "fax_image_size, ";
	end
	if (fax_bad_rows ~= nil) then
		sql = sql .. "fax_bad_rows, ";
	end
	if (fax_transfer_rate ~= nil) then
		sql = sql .. "fax_transfer_rate, ";
	end
	if (fax_uri ~= nil) then
		sql = sql .. "fax_uri, ";
	end
	sql = sql .. "fax_date, ";
	sql = sql .. "fax_epoch ";
	sql = sql .. ") ";
	sql = sql .. "values ";
	sql = sql .. "(";
	sql = sql .. ":uuid, ";
	sql = sql .. ":domain_uuid, ";
	if (fax_uuid ~= nil) then
		sql = sql .. ":fax_uuid, ";
	end
	sql = sql .. ":fax_success, ";
	sql = sql .. ":fax_result_code, ";
	sql = sql .. ":fax_result_text, ";
	sql = sql .. ":fax_file, ";
	if (fax_ecm_used ~= nil) then
		sql = sql .. ":fax_ecm_used, ";
	end
	if (fax_local_station_id ~= nil) then
		sql = sql .. ":fax_local_station_id, ";
	end
	sql = sql .. ":fax_document_transferred_pages, ";
	sql = sql .. ":fax_document_total_pages, ";
	if (fax_image_resolution ~= nil) then
		sql = sql .. ":fax_image_resolution, ";
	end
	if (fax_image_size ~= nil) then
		sql = sql .. ":fax_image_size, ";
	end
	if (fax_bad_rows ~= nil) then
		sql = sql .. ":fax_bad_rows, ";
	end
	if (fax_transfer_rate ~= nil) then
		sql = sql .. ":fax_transfer_rate, ";
	end
	if (fax_uri ~= nil) then
		sql = sql .. ":fax_uri, ";
	end
	if (database["type"] == "sqlite") then
		sql = sql .. ":fax_date, ";
	else
		sql = sql .. "now(), ";
	end
	sql = sql .. ":fax_time ";
	sql = sql .. ")";

	local params = {
		uuid = uuid;
		domain_uuid = domain_uuid;
		fax_uuid = fax_uuid;
		fax_success = fax_success;
		fax_result_code = fax_result_code;
		fax_result_text = fax_result_text;
		fax_file = fax_file;
		fax_ecm_used = fax_ecm_used;
		fax_local_station_id = fax_local_station_id;
		fax_document_transferred_pages = fax_document_transferred_pages or '0';
		fax_document_total_pages = fax_document_total_pages or '0';
		fax_image_resolution = fax_image_resolution;
		fax_image_size = fax_image_size;
		fax_bad_rows = fax_bad_rows;
		fax_transfer_rate = fax_transfer_rate;
		fax_uri = fax_uri;
		fax_date = os.date("%Y-%m-%d %X");
		fax_time = os.time();
	};

	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[fax] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
	end

	dbh:query(sql, params);

--add the fax files
	if (fax_success ~= nil) then
		if (fax_success =="1") then
			if (storage_type == "base64") then
				--include the file io
					local file = require "resources.functions.file"

				--read file content as base64 string
					fax_base64 = assert(file.read_base64(fax_file));
			end

			local sql = {}
			table.insert(sql, "insert into v_fax_files ");
			table.insert(sql, "(");
			table.insert(sql, "fax_file_uuid, ");
			table.insert(sql, "fax_uuid, ");
			table.insert(sql, "fax_mode, ");
			table.insert(sql, "fax_file_type, ");
			table.insert(sql, "fax_file_path, ");
			if (caller_id_name ~= nil) then
				table.insert(sql, "fax_caller_id_name, ");
			end
			if (caller_id_number ~= nil) then
				table.insert(sql, "fax_caller_id_number, ");
			end
			table.insert(sql, "fax_date, ");
			table.insert(sql, "fax_epoch, ");
			if (storage_type == "base64") then
				table.insert(sql, "fax_base64, ");
			end
			table.insert(sql, "domain_uuid");
			table.insert(sql, ") ");
			table.insert(sql, "values ");
			table.insert(sql, "(");
			table.insert(sql, ":uuid, ");
			table.insert(sql, ":fax_uuid, ");
			table.insert(sql, "'rx', ");
			table.insert(sql, "'tif', ");
			table.insert(sql, ":fax_file, ");
			if (caller_id_name ~= nil) then
				table.insert(sql, ":caller_id_name, ");
			end
			if (caller_id_number ~= nil) then
				table.insert(sql, ":caller_id_number, ");
			end
			if (database["type"] == "sqlite") then
				table.insert(sql, ":fax_date, ");
			else
				table.insert(sql, "now(), ");
			end
			table.insert(sql, ":fax_time, ");
			if (storage_type == "base64") then
				table.insert(sql, ":fax_base64, ");
			end
			table.insert(sql, ":domain_uuid");
			table.insert(sql, ")");
			sql = table.concat(sql, "\n");
			local params = {
				uuid = uuid;
				domain_uuid = domain_uuid;
				fax_uuid = fax_uuid;
				fax_file = fax_file;
				caller_id_name = caller_id_name;
				caller_id_number = caller_id_number;
				fax_base64 = fax_base64;
				fax_date = os.date("%Y-%m-%d %X");
				fax_time = os.time();
			};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[fax] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			if (storage_type == "base64") then
				local dbh = Database.new('system', 'base64');
				dbh:query(sql, params);
				dbh:release();
			else
				result = dbh:query(sql, params);
			end
		end
	end

-- send the selected variables to the console
	if (fax_success ~= nil) then
		freeswitch.consoleLog("INFO","fax_success: '" .. fax_success .. "'\n");
	end
	freeswitch.consoleLog("INFO","domain_uuid: '" .. domain_uuid .. "'\n");
	freeswitch.consoleLog("INFO","domain_name: '" .. domain_name .. "'\n");
	freeswitch.consoleLog("INFO","fax_uuid: '" .. fax_uuid .. "'\n");
	freeswitch.consoleLog("INFO","fax_extension: '" .. fax_extension .. "'\n");
	freeswitch.consoleLog("INFO","fax_result_text: '" .. fax_result_text .. "'\n");
	freeswitch.consoleLog("INFO","fax_file: '" .. fax_file .. "'\n");
	freeswitch.consoleLog("INFO","uuid: '" .. uuid .. "'\n");
	--freeswitch.consoleLog("INFO","fax_ecm_used: '" .. fax_ecm_used .. "'\n");
	freeswitch.consoleLog("INFO","fax_uri: '" .. fax_uri.. "'\n");
	if (caller_id_name ~= nil) then
		freeswitch.consoleLog("INFO","caller_id_name: " .. caller_id_name .. "\n");
	end
	if (caller_id_number ~= nil) then
		freeswitch.consoleLog("INFO","caller_id_number: " .. caller_id_number .. "\n");
	end
	freeswitch.consoleLog("INFO","fax_result_code: ".. fax_result_code .."\n");
	--freeswitch.consoleLog("INFO","mailfrom_address: ".. from_address .."\n");
	--freeswitch.consoleLog("INFO","mailto_address: ".. email_address .."\n");
	freeswitch.consoleLog("INFO","hangup_cause_q850: '" .. hangup_cause_q850 .. "'\n");
