--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	  this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	  notice, this list of conditions and the following disclaimer in the
--	  documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.
--
--	Contributor(s):
--	Salvatore Caruso <salvatore.caruso@nems.it>
--	Riccardo Granchi <riccardo.granchi@nems.it>

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(scripts_dir.."/resources/functions/explode.lua");
	dofile(config());

--get the argv values
	script_name = argv[0];
	app_name = argv[1];

--set the default variables
	forward_on_busy = false;
	send_to_voicemail = true;

--example use command
	--luarun app.lua app_name 'a' 'b 123' 'c'

--for loop through arguments
	arguments = "";
	for key,value in pairs(argv) do
		if (key > 1) then
			arguments = arguments .. " '" .. value .. "'";
			--freeswitch.consoleLog("notice", "[app.lua] argv["..key.."]: "..argv[key].."\n");
		end
	end

--if the session exists then check originate disposition and causes
	if (session ~= nil) then    
		originate_disposition = session:getVariable("originate_disposition");
		originate_causes = session:getVariable("originate_causes");
		if (originate_causes ~= nil) then
			array = explode("|",originate_causes);
			if (string.find(array[1], "USER_BUSY")) then
				originate_disposition = "USER_BUSY";
				session:setVariable("originate_disposition", originate_disposition);
			end
		end
		if (originate_disposition ~= nil) then
			send_to_voicemail = session:getVariable("send_to_voicemail");
			if (originate_disposition == 'USER_BUSY' and send_to_voicemail ~= "true") then
				freeswitch.consoleLog("notice", "[app] forward on busy: ".. scripts_dir .. "/app/forward_on_busy/index.lua" .. arguments .."\n");
				forward_on_busy = loadfile(scripts_dir .. "/app/forward_on_busy/index.lua")(argv);      
				freeswitch.consoleLog("notice", "[app] forward on busy: ".. tostring(forward_on_busy) .. "\n");
			end
		end
	end

--hangup on subscriber absent
	if (originate_disposition == "SUBSCRIBER_ABSENT") then
		--return 404 UNALLOCATED_NUMBER if extension doesn't exist
		freeswitch.consoleLog("notice", "[app] lua route: ".. scripts_dir .. "/app/" .. app_name .. "/index.lua" .. arguments ..". HANGUP.\n");
		session:hangup("UNALLOCATED_NUMBER");
	end

--route the request to the application
	if (not forward_on_busy and originate_disposition ~= "CALL_REJECTED") then
		--freeswitch.consoleLog("notice", "[app] lua route: ".. scripts_dir .. "/app/" .. app_name .. "/index.lua" .. arguments .."\n");
		loadfile(scripts_dir .. "/app/" .. app_name .. "/index.lua")(argv);
	end
