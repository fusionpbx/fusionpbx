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
--      Copyright (C) 2013 - 2018
--      the Initial Developer. All Rights Reserved.
--
--      Contributor(s):
--      Mark J Crane <markjcrane@fusionpbx.com>
--		Errol Samuels <voiptology@gmail.com>

--define the explode function
	require "resources.functions.explode";

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
			event:addHeader('event-string', 'check-sync');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync');
		end
	end

--cisco-spa
	if (vendor == "cisco-spa") then
		if (command == "reboot") then
			event:addHeader('event-string', 'reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'reboot=true');
		end
	end

--digium
	if (vendor == "digium") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync');
		end
	end

--fanvil
	if (vendor == "fanvil") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'resync');
		end
	end

--grandstream
	if (vendor == "grandstream") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'resync');
		end
	end

--htek
	if (vendor == "htek") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'resync');
		end
	end

--sangoma
	if (vendor == "sangoma") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'resync');
		end
	end

--linksys
	if (vendor == "linksys") then
		if (command == "reboot") then
			event:addHeader('event-string', 'reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'reboot=true');
		end
	end

--panasonic
	if (vendor == "panasonic") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync;reboot=true');
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

--snom
	if (vendor == "snom") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
		if (command == "check_sync") then
			event:addHeader('event-string', 'check-sync;reboot=false');
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

--send the event
	event:fire();

--log the event
	freeswitch.consoleLog("notice", "[event_notify] command "..command.." "..user.."@"..domain.." vendor "..tostring(vendor).."\n");
