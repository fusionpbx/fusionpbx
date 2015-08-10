--
--      event_notify
--      Version: MPL 1.1
--
--      The contents of this file are subject to the Mozilla Public License Version
--      1.1 (the "License"); you may not use this file except in compliance with
--      the License. You may obtain a copy of the License at
--      http://www.mozilla.org/MPL/
--
--      Software distributed under the License is distributed on an "AS IS" basis,
--      WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--      for the specific language governing rights and limitations under the
--      License.
--
--      The Original Code is FusionPBX - event_notify
--
--      The Initial Developer of the Original Code is
--      Mark J Crane <markjcrane@fusionpbx.com>
--      Copyright (C) 2013 - 2014
--      the Initial Developer. All Rights Reserved.
--
--      Contributor(s):
--      Mark J Crane <markjcrane@fusionpbx.com>
--		Errol Samuels <voiptology@gmail.com>

--define explode
	function explode ( seperator, str )
		local pos, arr = 0, {}
		for st, sp in function() return string.find( str, seperator, pos, true ) end do -- for each divider found
			table.insert( arr, string.sub( str, pos, st-1 ) ) -- attach chars left of current divider
			pos = sp + 1 -- jump past current divider
		end
		table.insert( arr, string.sub( str, pos ) ) -- attach chars right of last divider
		return arr
	end

--usage
	--luarun app.lua event_notify internal reboot 1003@domain.fusionpbx.com yealink

--set the args as variables
	profile = argv[2];
	command = argv[3];
	user = argv[4];
	vendor = argv[5];

--log the args
	--freeswitch.consoleLog("notice", "[event_notify] profile "..profile.."\n");
	--freeswitch.consoleLog("notice", "[event_notify] command "..command.."\n");
	--freeswitch.consoleLog("notice", "[event_notify] user "..user.."\n");
	--freeswitch.consoleLog("notice", "[event_notify] vendor "..vendor.."\n");

--get the user and domain name from the user argv user@domain
	user_table = explode("@",user);
	user = user_table[1];
	domain = user_table[2];

--create the event notify object
	local event = freeswitch.Event('NOTIFY');

--add the headers
	event:addHeader('profile', profile);
	event:addHeader('user', user);
	event:addHeader('host', domain);
	event:addHeader('content-type', 'application/simple-message-summary');

--aastra
	if (vendor == "aastra") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
	end

--cisco
	if (vendor == "cisco") then
		if (command == "reboot") then
			event:addHeader('event-string', 'reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'reboot=true');
		end
	end

--grandstream
	if (vendor == "grandstream") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync;reboot=false');
		end
	end

--polycom
	if (vendor == "polycom") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync');
		end
	end

--yealink
	if (vendor == "yealink") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync;reboot=false');
		end
	end
--snom
	if (vendor == "snom") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
	end

--send the event
	event:fire();

--log the event
	freeswitch.consoleLog("notice", "[event_notify] command "..command.." "..user.."@"..domain.." vendor "..tostring(vendor).."\n");
