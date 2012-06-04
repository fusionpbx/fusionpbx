--set default variables
	fax_retry_sleep = 10;
	fax_retry_limit = 3;

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
	uuid = env:getHeader("uuid");
	fax_success = env:getHeader("fax_success");
	fax_result_text = env:getHeader("fax_result_text");
	fax_ecm_used = env:getHeader("fax_ecm_used");
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
	freeswitch.consoleLog("INFO","fax_success: '" .. fax_success .. "'\n");
	freeswitch.consoleLog("INFO","fax_result_text: '" .. fax_result_text .. "'\n");
	freeswitch.consoleLog("INFO","fax_file: '" .. fax_file .. "'\n");
	freeswitch.consoleLog("INFO","uuid: '" .. uuid .. "'\n");
	freeswitch.consoleLog("INFO","fax_ecm_used: '" .. fax_ecm_used .. "'\n");
	freeswitch.consoleLog("INFO","fax_retry_attempts: " .. fax_retry_attempts.. "\n");
	freeswitch.consoleLog("INFO","fax_retry_limit: " .. fax_retry_limit.. "\n");
	freeswitch.consoleLog("INFO","fax_retry_sleep: " .. fax_retry_sleep.. "\n");
	freeswitch.consoleLog("INFO","fax_uri: '" .. fax_uri.. "'\n");
	freeswitch.consoleLog("INFO","origination_caller_id_name: " .. origination_caller_id_name .. "\n");
	freeswitch.consoleLog("INFO","origination_caller_id_number: " .. origination_caller_id_number .. "\n");

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
