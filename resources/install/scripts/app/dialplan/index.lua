--	FusionPBX
--	Version: MPL 1.1

--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/

--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.

--	The Original Code is FusionPBX

--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Portions created by the Initial Developer are Copyright (C) 2014
--	the Initial Developer. All Rights Reserved.

--add functions
	dofile(scripts_dir.."/resources/functions/file_exists.lua");
	dofile(scripts_dir.."/resources/functions/trim.lua");

--set the api object
	api = freeswitch.API();

--windows (/ad show only directories)
	--dir "C:\program files\fusionpbx" /b
--unix
	-- dir /usr/local/freeswitch/scripts -1

--set local variables
	local context = session:getVariable("context");
	local destination_number = session:getVariable("destination_number");
	local call_direction = session:getVariable("call_direction");
	local domain_name = session:getVariable("domain_name");

--determine the call direction
	if (call_direction == nil) then
		--get the call directory
			if (context == "public") then
				call_direction = "inbound";
			else
				if (string.sub(context, 0, 9) == "outbound@") then
					call_direction = "outbound";
				else
					if (string.len(destination_number) > 6) then
						call_direction = "outbound";
					else
						call_direction = "local";
					end
				end
			end
		--set the call direction as a session variable
			session:setVariable("call_direction", call_direction);
			--freeswitch.consoleLog("notice", "[app:dialplan] set call_direction " .. call_direction .. "\n");
	end

--determine the directory to include
	if (context == "public") then
		dialplan_dir = "inbound";
	else
		if (context == "outbound@"..domain_name) then
			dialplan_dir = "outbound";
		else
			if (string.len(destination_number) > 6) then
				dialplan_dir = "outbound";
			else
				dialplan_dir = "local";
			end
		end
	end

--include the dialplans
	result = assert (io.popen ("dir " ..scripts_dir.."/app/dialplan/resources/"..dialplan_dir.." /b -1"));
	for file in result:lines() do
		if (string.sub(file, -4) == ".lua") then
			if file_exists(scripts_dir.."/app/dialplan/resources/"..dialplan_dir.."/"..file) then
				dofile(scripts_dir.."/app/dialplan/resources/"..dialplan_dir.."/"..file);
			end
			freeswitch.consoleLog("notice", "[app:dialplan] lua: "..dialplan_dir.."/" .. file .. "\n");
		end
	end
