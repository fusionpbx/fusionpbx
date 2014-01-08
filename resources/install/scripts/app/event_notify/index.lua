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
--      Copyright (C) 2013
--      the Initial Developer. All Rights Reserved.
--
--      Contributor(s):
--      Mark J Crane <markjcrane@fusionpbx.com>

--usage
	--luarun app.lua event_notify reboot 1003 domain.fusionpbx.com yealink

--set the args as variables
	command = argv[2];
	user = argv[3];
	domain = argv[4];
	vendor = argv[5];

--create the event notify object
	local event = freeswitch.Event('NOTIFY');

--add the headers
	event:addHeader('profile', 'internal');
	event:addHeader('user', user);
	event:addHeader('host', domain);
	event:addHeader('content-type', 'application/simple-message-summary');

--cisco
	if (vendor == "cisco") then
		if (command == "reboot") then
			event:addHeader('event-string', 'reboot=true');
		end
	end

--grandstream
	if (vendor == "grandstream") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
	end

--polycom
	if (vendor == "polycom") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
	end

--yealink
	if (vendor == "yealink") then
		if (command == "reboot") then
			event:addHeader('event-string', 'check-sync;reboot=true');
		end
	end

--send the event
	event:fire();

--log the event
	freeswitch.consoleLog("notice", "[event_notify] command "..command.." "..user.."@"..domain.." vendor "..vendor.."\n");
