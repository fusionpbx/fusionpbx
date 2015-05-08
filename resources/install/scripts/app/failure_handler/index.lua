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
	if (session ~= nil and session:ready()) then
		context = session:getVariable("context");
		domain_name = session:getVariable("domain_name");
		uuid = session:getVariable("uuid");
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
					last_busy_dialed_extension = session:getVariable("last_busy_dialed_extension");
					if (debug["info"] ) then
						freeswitch.consoleLog("INFO", "[failure_handler] last_busy_dialed_extension: " .. tostring(last_busy_dialed_extension) .. "\n");
					end

				--transfer to the forward busy destination
					if (dialed_extension ~= nil and dialed_extension ~= last_busy_dialed_extension) then
						forward_busy_enabled = session:getVariable("forward_busy_enabled");
						if (forward_busy_enabled == "true") then
							forward_busy_destination = session:getVariable("forward_busy_destination");
							if (forward_busy_destination ~= nil and string.len(forward_busy_destination) > 0) then
								--handle USER_BUSY - forwarding to number
								session:setVariable("last_busy_dialed_extension", dialed_extension);
								if (forward_busy_destination == nil) then
									freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding on busy to hangup\n");
									session:hangup("USER_BUSY");
								else
									freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding on busy to: " .. forward_busy_destination .. "\n");
									session:transfer(forward_busy_destination, "XML", context);
								end
							else
								--handle USER_BUSY - hangup
								freeswitch.consoleLog("NOTICE", "[failure_handler] forward on busy with empty destination: hangup(USER_BUSY)\n");
								session:hangup("USER_BUSY");
							end
						end
					end

			elseif (originate_disposition == "NO_ANSWER") then

				--handle NO_ANSWER
				forward_no_answer_enabled = session:getVariable("forward_no_answer_enabled");
				if (forward_no_answer_enabled == "true") then
					forward_no_answer_destination = session:getVariable("forward_no_answer_destination");
					if (forward_no_answer_destination == nil) then
						freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding no answer to hangup\n");
						session:hangup("NO_ANSWER");
					else
						freeswitch.consoleLog("NOTICE", "[failure_handler] forwarding no answer to: " .. forward_no_answer_destination .. "\n");
						session:transfer(forward_no_answer_destination, "XML", context);
					end
				end
				if (debug["info"] ) then
					freeswitch.consoleLog("NOTICE", "[failure_handler] - NO_ANSWER\n");
				end

			elseif (originate_disposition == "USER_NOT_REGISTERED") then

				--handle USER_NOT_REGISTERED
				if (debug["info"] ) then
					freeswitch.consoleLog("NOTICE", "[failure_handler] - USER_NOT_REGISTERED - Doing nothing\n");
				end

			elseif (originate_disposition == "SUBSCRIBER_ABSENT" and hangup_on_subscriber_absent == "true") then

				--handle SUBSCRIBER_ABSENT
				freeswitch.consoleLog("NOTICE", "[failure_handler] - SUBSCRIBER_ABSENT - hangup(UNALLOCATED_NUMBER)\n");
				session:hangup("UNALLOCATED_NUMBER");

			elseif (originate_disposition == "CALL_REJECTED" and hangup_on_call_reject =="true") then

				--handle CALL_REJECT
				freeswitch.consoleLog("NOTICE", "[failure_handler] - CALL_REJECT - hangup()\n");
				session:hangup();

			end
		end
	end
