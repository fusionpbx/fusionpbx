--define the trim function
	require "resources.functions.trim"

--define the explode function
	require "resources.functions.explode"

--create the api object
	api = freeswitch.API();
	uuid = argv[1];
	domain_uuid = argv[2];
	if not uuid or uuid == "" then return end;
	caller = api:executeString("uuid_getvar " .. uuid .. " caller_id_number");
	callee = api:executeString("uuid_getvar " .. uuid .. " destination_number");

--clean local country prefix from caller (ex: +39 or 0039 in Italy)
	exitCode    = api:executeString("uuid_getvar " .. uuid .. " default_exitcode");
	countryCode = api:executeString("uuid_getvar " .. uuid .. " default_countrycode");

	if ((countryCode ~= nil) and (string.len(countryCode) > 0)) then

		countryPrefix = "+" .. countryCode;

		if (string.sub(caller, 1, string.len(countryPrefix)) == countryPrefix) then
			cleanCaller = string.sub(caller, string.len(countryPrefix)+1);
			freeswitch.consoleLog("NOTICE", "[cidlookup] ignoring local international prefix " .. countryPrefix .. ": " .. caller .. " ==> " .. cleanCaller .. "\n");
			caller = cleanCaller;
		else
			if ((exitCode ~= nil) and (string.len(exitCode) > 0)) then

				countryPrefix = exitCode .. countryCode;

				if (string.sub(caller, 1, string.len(countryPrefix)) == countryPrefix) then
					cleanCaller = string.sub(caller, string.len(countryPrefix)+1);
					freeswitch.consoleLog("NOTICE", "[cidlookup] ignoring local international prefix " .. countryPrefix .. ": " .. caller .. " ==> " .. cleanCaller .. "\n");
					caller = cleanCaller;
				end;
			end;
		end;
	end;

--include config.lua
	require "resources.functions.config";

--include json library
	local json

	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--connect to the database and checked for cached value
	freeswitch.consoleLog("NOTICE", "[cidlookup] checking for cached CNAM value in database for " .. caller .. "\n");

	local Database = require "resources.functions.database";
	dbh = Database.new('system');

	sql = "SELECT cnam, extract (epoch from date)  as date from v_cnam WHERE phone_number LIKE :caller";

	local params;
	params = {caller = '%'..caller..'%'};

	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[cidlookup] SQL: "..sql.."; params:" .. json.encode(params) .. "\n");
	end

	dbh:query(sql, params, function(row)
		name = row.cnam;
		date = row.date;
	end);

	--freeswitch.consoleLog("NOTICE", "[cidlookup] Date is  " .. date .. "\n");
	--freeswitch.consoleLog("NOTICE", "[cidlookup] Current Date is  " .. os.time() .. "\n");
	--freeswitch.consoleLog("NOTICE", "[cidlookup] Diff Date is  " .. os.time()-date .. "\n");

	if (name == nil) then
		freeswitch.consoleLog("NOTICE", "[cidlookup] CNAM not found in db. Sending API request to get CNAM\n");
	-- if cnam found and it's created less than 90 days ago (7776000 seconds)
	elseif ((os.time() - date) < 7776000) then
		freeswitch.consoleLog("NOTICE", "[cidlookup] CNAM Found in DB. Age: " .. (os.time() - date)/86400 .. " days. Using cached value - "..name.."\n");
	-- if cnam found but is older than 90 days (7776000 seconds)
	elseif ((os.time() - date) >= 7776000) then
		freeswitch.consoleLog("NOTICE", "[cidlookup] CNAM Found in DB. Age: " .. (os.time() - date)/86400 .. " days. Seding API request to update\n");
		name = nil;

                -- Deleting cached CNAM from database
                sql = "delete from v_cnam where phone_number LIKE :phone_number"
                params = {phone_number = '%'..caller..'%'};

                if (debug["sql"]) then
                	freeswitch.consoleLog("WARNING", "[cidlookup]  NEMERALD_DEBUG SQL: "..sql.."; params:" .. json.encode(params) .. "\n");
                end

                dbh:query(sql, params)
	end

--check if there is a record, if it doesn't exist then use cidlookup
	if ((name == nil) or (string.len(name) == 0)) then
		cidlookup_exists = api:executeString("module_exists mod_cidlookup");
		if (cidlookup_exists == "true") then
		    name = api:executeString("cidlookup " .. caller);
		end

		-- if cnam retrieved from API add it to database
		if ((name ~= nil) and (string.len(name) > 0)) then

	                -- Inserting received CNAM to database
        	        cnam_uuid = api:executeString("create_uuid")
                	sql = "insert into v_cnam (cnam_uuid,phone_number,cnam,date) "
                	sql = sql .. "values (:cnam_uuid,:phone_number,:cnam,'NOW()')"

                	params = {cnam_uuid = cnam_uuid; phone_number = caller; cnam = name};

                	if (debug["sql"]) then
                        	freeswitch.consoleLog("WARNING", "[cidlookup]  NEMERALD_DEBUG SQL: "..sql.."; params:" .. json.encode(params) .. "\n");
                	end

                	dbh:query(sql, params)
                	freeswitch.consoleLog("NOTICE", "[cidlookup] CNAM retrieved from API and added to database : phone number - " .. caller .. "; name - " .. name .. "\n");
		end
	end
--set the caller id name
	if (name == 'UNKNOWN') then
		freeswitch.consoleLog("NOTICE", "[cidlookup] Cnam is UNKNOWN. Ignoring..")
	elseif ((name ~= nil) and (string.len(name) > 0)) then
		api:executeString("uuid_setvar " .. uuid .. " ignore_display_updates false");

		freeswitch.consoleLog("NOTICE", "[cidlookup] uuid_setvar " .. uuid .. " caller_id_name " .. name);
		api:executeString("uuid_setvar " .. uuid .. " caller_id_name " .. name);

		freeswitch.consoleLog("NOTICE", "[cidlookup] uuid_setvar " .. uuid .. " effective_caller_id_name " .. name);
		api:executeString("uuid_setvar " .. uuid .. " effective_caller_id_name " .. name);
	end
