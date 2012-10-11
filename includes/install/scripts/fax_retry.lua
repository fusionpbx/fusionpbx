--set default variables
	fax_retry_sleep = 300;
	fax_retry_limit = 5;

--include the lua script
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	include = assert(loadfile(scripts_dir .. "/resources/config.lua"));
	include();

--connect to the database
	--ODBC - data source name
		if (dsn_name) then
			dbh = freeswitch.Dbh(dsn_name,dsn_username,dsn_password);
		end
	--FreeSWITCH core db handler
		if (db_type == "sqlite") then
			dbh = freeswitch.Dbh("core:"..db_path.."/"..db_name);
		end

-- show all channel variables
	--dat = env:serialize()            
	--freeswitch.consoleLog("INFO","info:\n" .. dat .. "\n")

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
	domain_uuid = env:getHeader("domain_uuid");
	uuid = env:getHeader("uuid");
	fax_success = env:getHeader("fax_success");
	fax_result_code = env:getHeader("fax_result_code");
	fax_result_text = env:getHeader("fax_result_text");
	fax_ecm_used = env:getHeader("fax_ecm_used");
	fax_local_station_id = env:getHeader("fax_local_station_id");
	fax_document_transferred_pages = env:getHeader("fax_document_transferred_pages");
	fax_document_total_pages = env:getHeader("fax_document_total_pages");
	fax_image_resolution = env:getHeader("fax_image_resolution");
	fax_image_size = env:getHeader("fax_image_size");
	fax_bad_rows = env:getHeader("fax_bad_rows");
	fax_transfer_rate = env:getHeader("fax_transfer_rate");
	fax_retry_attempts = tonumber(env:getHeader("fax_retry_attempts"));
	fax_retry_limit = tonumber(env:getHeader("fax_retry_limit"));
	fax_retry_sleep = tonumber(env:getHeader("fax_retry_sleep"));
	fax_uri = env:getHeader("fax_uri");
	fax_file = env:getHeader("fax_file");
	fax_extension_number = env:getHeader("fax_extension_number");
	origination_caller_id_name = env:getHeader("origination_caller_id_name");
	origination_caller_id_number = env:getHeader("origination_caller_id_number");

--set default values
	if (not origination_caller_id_name) then
		origination_caller_id_name = '000000000000000';
	end
	if (not origination_caller_id_number) then
		origination_caller_id_number = '000000000000000';
	end

-- send the selected variables to the console
	if (fax_success) then
		freeswitch.consoleLog("INFO","fax_success: '" .. fax_success .. "'\n");
	end
	if (fax_result_text) then
		freeswitch.consoleLog("INFO","fax_result_text: '" .. fax_result_text .. "'\n");
	end
	if (fax_file) then
		freeswitch.consoleLog("INFO","fax_file: '" .. fax_file .. "'\n");
	end
	if (uuid) then
		freeswitch.consoleLog("INFO","uuid: '" .. uuid .. "'\n");
	end
	if (fax_ecm_used) then
		freeswitch.consoleLog("INFO","fax_ecm_used: '" .. fax_ecm_used .. "'\n");
	end
	if (fax_retry_attempts) then
		freeswitch.consoleLog("INFO","fax_retry_attempts: " .. fax_retry_attempts.. "\n");
	end
	if (fax_retry_limit) then
		freeswitch.consoleLog("INFO","fax_retry_limit: " .. fax_retry_limit.. "\n");
	end
	if (fax_retry_sleep) then
		freeswitch.consoleLog("INFO","fax_retry_sleep: " .. fax_retry_sleep.. "\n");
	end
	if (fax_uri) then
		freeswitch.consoleLog("INFO","fax_uri: '" .. fax_uri.. "'\n");
	end
	if (origination_caller_id_name) then
		freeswitch.consoleLog("INFO","origination_caller_id_name: " .. origination_caller_id_name .. "\n");
	end
	if (origination_caller_id_number) then
		freeswitch.consoleLog("INFO","origination_caller_id_number: " .. origination_caller_id_number .. "\n");
	end

-- if the fax failed then try again
	if (fax_success == "0") then
		if (fax_retry_attempts < fax_retry_limit) then 
			-- sleep
			freeswitch.msleep(fax_retry_sleep * 1000);
			--increment the retry attempts
			fax_retry_attempts = fax_retry_attempts + 1;
			cmd = "originate {origination_caller_id_name='"..origination_caller_id_name.. "',origination_caller_id_number="..origination_caller_id_number..",fax_uri="..fax_uri..",fax_retry_attempts="..fax_retry_attempts..",fax_retry_limit="..fax_retry_limit..",fax_retry_sleep="..fax_retry_sleep..",fax_verbose=true,fax_file='"..fax_file.."',fax_use_ecm=off,api_hangup_hook='lua fax_retry.lua'}"..fax_uri.." &txfax('"..fax_file.."')";
			--cmd = "sofia/internal/"..fax_number.."@"..domain_name.." &txfax('"..fax_file.."') XML default ";
			freeswitch.consoleLog("INFO","retry cmd: " .. cmd .. "\n");
			api = freeswitch.API();
			reply = api:executeString(cmd);
		end
	end
