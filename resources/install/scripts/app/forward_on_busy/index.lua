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
--	Salvatore Caruso <salvatore.caruso@nems.it>
--	Riccardo Granchi <riccardo.granchi@nems.it>

--set default values
	forward = false;

--debug
	debug["info"] = false;
	debug["sql"] = false;

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

	if (session ~= nil) then    
		originate_disposition = session:getVariable("originate_disposition");
	  
		if( originate_disposition=='USER_BUSY' ) then

			dialed_extension = session:getVariable("dialed_extension");
			context = session:getVariable("context");
			domain_name = session:getVariable("domain_name");
			uuid = session:getVariable("uuid");

			--get the domain_uuid
			domain_uuid = session:getVariable("domain_uuid");
			if (domain_uuid == nil) then
				--get the domain_uuid using the domain name required for multi-tenant
				if (domain_name ~= nil) then
					sql = "SELECT domain_uuid FROM v_domains ";
					sql = sql .. "WHERE domain_name = '" .. domain_name .. "' ";
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[forward_on_busy] SQL: " .. sql .. "\n");
					end
					status = dbh:query(sql, function(rows)
						domain_uuid = rows["domain_uuid"];
						end);
				end
			end
			domain_uuid = string.lower(domain_uuid);

			if( debug["info"] ) then
				freeswitch.consoleLog("info", "[forward_on_busy] originate_disposition: " .. originate_disposition .. "\n");
				freeswitch.consoleLog("info", "[forward_on_busy] dialed_extension     : " .. dialed_extension .. "\n");
			end

			if (dialed_extension ~= nil) then
				if (session:ready()) then
					
					--get the information from the database
					sql = [[SELECT * FROM v_extensions
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND extension = ']] .. dialed_extension ..[['
					AND forward_busy_enabled = 'true' ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[forward_on_busy] SQL: " .. sql .. "\n");
					end
					status = dbh:query(sql, function(row)
					forward_busy_destination = string.lower(row["forward_busy_destination"]);
						end);

					--set default values
					if (forward_busy_destination ~= nil and string.len(forward_busy_destination)>0 ) then
						if( debug["info"] ) then
							freeswitch.consoleLog("notice", "[forward_on_busy] forward_busy_destination: " .. forward_busy_destination .. "\n");
						end

						session:transfer(forward_busy_destination, "XML", context);
						forward = true;
					else
						if( debug["info"] ) then
							freeswitch.consoleLog("notice", "[forward_on_busy] forward on busy disabled or destination unsetted - HANGUP WITH USER BUSY \n");
						end

						session:hangup("USER_BUSY");
						forward = false;
					end
				end
			end
		end
	end

	--close the database connection
	dbh:release();
  
	return forward;
