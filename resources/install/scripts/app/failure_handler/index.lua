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

--debug
	debug["info"] = false;
	debug["sql"] = false;

--include config.lua
	dofile(scripts_dir .. "/resources/functions/config.lua");
	dofile(scripts_dir .. "/resources/functions/explode.lua");

--handle originate_disposition
	if (session ~= nil and
			session:ready()) then

		originate_disposition = session:getVariable("originate_disposition");
		originate_causes = session:getVariable("originate_causes");
		hangup_on_subscriber_absent = session:getVariable("hangup_on_subscriber_absent");
		hangup_on_call_reject = session:getVariable("hangup_on_call_reject");

		if (debug["info"] == true) then
			freeswitch.consoleLog("INFO", "[failure_handler] originate_causes: " .. tostring(originate_causes) .. "\n");
			freeswitch.consoleLog("INFO", "[failure_handler] originate_disposition: " .. tostring(originate_disposition) .. "\n");
			freeswitch.consoleLog("INFO", "[failure_handler] hangup_on_subscriber_absent: " .. tostring(hangup_on_subscriber_absent) .. "\n");
			freeswitch.consoleLog("INFO", "[failure_handler] hangup_on_call_reject: " .. tostring(hangup_on_call_reject) .. "\n");
		end

		if (originate_causes ~= nil) then
			array = explode("|",originate_causes);
			if (string.find(array[1], "USER_BUSY")) then
				originate_disposition = "USER_BUSY";
				session:setVariable("originate_disposition", originate_disposition);
			end
		end

		if (originate_disposition ~= nil) then
			if (originate_disposition == 'USER_BUSY') then

			--handle USER_BUSY

				dialed_extension = session:getVariable("dialed_extension");
				context = session:getVariable("context");
				domain_name = session:getVariable("domain_name");
				uuid = session:getVariable("uuid");
				last_busy_dialed_extension = session:getVariable("last_busy_dialed_extension");

				if (debug["info"] ) then
					freeswitch.consoleLog("INFO", "[failure_handler] last_busy_dialed_extension: " .. tostring(last_busy_dialed_extension) .. "\n");
				end

			--connect to the database
				dofile(scripts_dir .. "/resources/functions/database_handle.lua");
				dbh = database_handle('system');

			--get the domain_uuid
				domain_uuid = session:getVariable("domain_uuid");
				if (domain_uuid == nil) then
					--get the domain_uuid using the domain name required for multi-tenant
					if (domain_name ~= nil) then
						sql = "SELECT domain_uuid FROM v_domains ";
						sql = sql .. "WHERE domain_name = '" .. domain_name .. "' ";
						
						if (debug["sql"]) then
							freeswitch.consoleLog("INFO", "[failure_handler] SQL: " .. sql .. "\n");
						end
						
						dbh:query(sql, function(rows)
							domain_uuid = rows["domain_uuid"];
						end);
					end
				end
				domain_uuid = string.lower(domain_uuid);

				if (dialed_extension ~= nil and
						dialed_extension ~= last_busy_dialed_extension) then

				--get the information from the database
					sql = [[SELECT * FROM v_extensions
					WHERE domain_uuid = ']] .. domain_uuid .. [['
					AND extension = ']] .. dialed_extension .. [['
					AND forward_busy_enabled = 'true' ]];

					if (debug["sql"]) then
						freeswitch.consoleLog("INFO", "[failure_handler] SQL: " .. sql .. "\n");
					end

					dbh:query(sql, function(row)
						forward_busy_destination = string.lower(row["forward_busy_destination"]);

						if (forward_busy_destination ~= nil and
								string.len(forward_busy_destination) > 0 ) then

						--handle USER_BUSY - forwarding to number

							freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding on busy to: " .. forward_busy_destination .. "\n");
							session:setVariable("last_busy_dialed_extension", dialed_extension);
							session:transfer(forward_busy_destination, "XML", context);
						else

						--handle USER_BUSY - hangup

							freeswitch.consoleLog("NOTICE", "[failure_handler] forward on busy with empty destination: hangup(USER_BUSY)\n");
							session:hangup("USER_BUSY");
						end
					end);
				end

			--close the database connection
				dbh:release();

			elseif (originate_disposition == "ALLOTTED_TIMEOUT") then

			--handle ALLOTTED_TIMEOUT ( NO ANSWER )

				if (debug["info"] ) then
					freeswitch.consoleLog("NOTICE", "[failure_handler] - ALLOTTED_TIMEOUT - Doing nothing\n");
				end
			elseif (originate_disposition == "USER_NOT_REGISTERED") then

			--handle USER_NOT_REGISTERED

				if (debug["info"] ) then
					freeswitch.consoleLog("NOTICE", "[failure_handler] - USER_NOT_REGISTERED - Doing nothing\n");
				end
			elseif (originate_disposition == "SUBSCRIBER_ABSENT" and
				hangup_on_subscriber_absent == "true") then

			--handle SUBSCRIBER_ABSENT

				freeswitch.consoleLog("NOTICE", "[failure_handler] - SUBSCRIBER_ABSENT - hangup(UNALLOCATED_NUMBER)\n");
				session:hangup("UNALLOCATED_NUMBER");
			elseif (originate_disposition == "CALL_REJECTED" and
				hangup_on_call_reject =="true") then

			--handle CALL_REJECT

				freeswitch.consoleLog("NOTICE", "[failure_handler] - CALL_REJECT - hangup()\n");
				session:hangup();
			end
		end
	end
