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
--	Copyright (C) 2010-2014
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
--	add this like to use it: action set caller_id_name=${luarun cidlookup.lua ${uuid}}
--debug
	debug["sql"] = true;

--define the trim function
	function trim (s)
		return (string.gsub(s, "^%s*(.-)%s*$", "%1"))
	end

--define the explode function
	function explode ( seperator, str ) 
		local pos, arr = 0, {}
		for st, sp in function() return string.find( str, seperator, pos, true ) end do -- for each divider found
			table.insert( arr, string.sub( str, pos, st-1 ) ) -- attach chars left of current divider
			pos = sp + 1 -- jump past current divider
		end
		table.insert( arr, string.sub( str, pos ) ) -- attach chars right of last divider
		return arr
	end

--create the api object
	api = freeswitch.API();
	uuid = argv[1];
	if not uuid or uuid == "" then return end;
	caller = api:executeString("uuid_getvar " .. uuid .. " caller_id_number");
	callee = api:executeString("uuid_getvar " .. uuid .. " destination_number");

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--check if the session is ready
	
		--connect to the database
			dofile(scripts_dir.."/resources/functions/database_handle.lua");
			dbh = database_handle('system');
	
		--determine whether to update the dial string
			sql = "SELECT CONCAT(v_contacts.contact_name_given, ' ', v_contacts.contact_name_family,' (',v_contact_phones.phone_type,')') AS name FROM v_contacts ";
			sql = sql .. "INNER JOIN v_contact_phones ON v_contact_phones.contact_uuid = v_contacts.contact_uuid ";
			sql = sql .. "INNER JOIN v_destinations ON v_destinations.domain_uuid = v_contacts.domain_uuid ";
			sql = sql .. "WHERE  v_contact_phones.phone_number = '"..caller.."' AND v_destinations.destination_number='"..callee.."'";

			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[call_forward] "..sql.."\n");
			end
			status = dbh:query(sql, function(row)
				name = row.name;
				--freeswitch.consoleLog("NOTICE", "[cidlookup] caller name from contacts db "..row.name.."\n");
			end);

		--check if there is a record, if it doesnt, then use common cidlookup
			if (string.len(name) == 0) then
				name = api:executeString("cidlookup " .. number);
			end	
			
			api:executeString("uuid_setvar " .. uuid .. " effective_caller_id_name " .. name);
	
