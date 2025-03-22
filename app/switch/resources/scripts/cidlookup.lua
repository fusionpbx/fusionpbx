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
--	Copyright (C) 2010-2016
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
--	Riccardo Granchi <riccardo.granchi@nems.it>
--	Adrian Fretwell <adrian.fretwell@topgreen.co.uk>
--
--	add this in Inbound Routes before transfer to use it:
--	action set caller_id_name=${luarun cidlookup.lua ${uuid} ${domain_uuid}}

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

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');
	if (database["type"] == "mysql") then
		sql = "SELECT CONCAT(v_contacts.contact_name_given, ' ', v_contacts.contact_name_family) AS name FROM v_contacts ";
	elseif (database["type"] == "pgsql") then
		sql = "SELECT CASE WHEN contact_name_given = '' THEN v_contacts.contact_organization ELSE v_contacts.contact_name_given || ' ' || v_contacts.contact_name_family END AS name FROM v_contacts ";
	else
		sql = "SELECT v_contacts.contact_name_given || ' ' || v_contacts.contact_name_family AS name FROM v_contacts ";
	end
	sql = sql .. "INNER JOIN v_contact_phones ON v_contact_phones.contact_uuid = v_contacts.contact_uuid ";
	sql = sql .. "INNER JOIN v_destinations ON v_destinations.domain_uuid = v_contacts.domain_uuid AND v_destinations.destination_number = :callee ";
	
	local params;
	if ((not domain_uuid) or (domain_uuid == "")) then
		sql = sql .. "WHERE  v_contact_phones.phone_number = :caller ";
		params = {caller = caller, callee = callee};
	else
		sql = sql .. "WHERE  v_contacts.domain_uuid = :domain_uuid and v_contact_phones.phone_number = :caller ";
		params = {caller = caller, domain_uuid = domain_uuid, callee = callee};
	end

	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[cidlookup] SQL: "..sql.."; params:" .. json.encode(params) .. "\n");
	end

	dbh:query(sql, params, function(row)
		name = row.name;
	end);

	if (name == nil) then
		freeswitch.consoleLog("NOTICE", "[cidlookup] caller name from contacts db is nil\n");
	else
		freeswitch.consoleLog("NOTICE", "[cidlookup] caller name from contacts db: "..name.."\n");
	end

--check if there is a record, if it not then use cidlookup
	if ((name == nil) or (string.len(name) == 0)) then
		cidlookup_exists = api:executeString("module_exists mod_cidlookup");
		if (cidlookup_exists == "true") then
		    name = api:executeString("cidlookup " .. caller);
		end
	end

--set the caller id name
	if ((name ~= nil) and (string.len(name) > 0)) then
		api:executeString("uuid_setvar " .. uuid .. " ignore_display_updates false");
	
		freeswitch.consoleLog("NOTICE", "[cidlookup] uuid_setvar " .. uuid .. " caller_id_name " .. name .. "\n");
		api:executeString("uuid_setvar " .. uuid .. " caller_id_name " .. name);

		freeswitch.consoleLog("NOTICE", "[cidlookup] uuid_setvar " .. uuid .. " effective_caller_id_name " .. name .. "\n");
		api:executeString("uuid_setvar " .. uuid .. " effective_caller_id_name " .. name);
	end
