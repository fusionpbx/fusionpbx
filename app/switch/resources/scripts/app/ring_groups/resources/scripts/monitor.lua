--	uuid_hangup.lua
--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
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

--Description:
	--if the uuid does not exist
	--then run commands

--get the argv values
	script_name = argv[0];
	uuid = argv[1];
	timeout = argv[2];

--define the trim function
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

--prepare the api
	api = freeswitch.API();

--get the list of uuids
	cmd = "uuid_getvar "..uuid.." uuids";
	--freeswitch.consoleLog("NOTICE", "[confirm] cmd: "..cmd.."\n");
	uuids = trim(api:executeString(cmd));

--monitor the uuid
	x = 0
	while true do
		--sleep a moment to prevent using unecessary resources
			freeswitch.msleep(1000);

		--check if the uuid exists
			if (api:executeString("uuid_exists "..uuid) == "false") then
				--unschedule the timeout
					cmd = "sched_del ring_group:"..uuid;
					--freeswitch.consoleLog("NOTICE", "[confirm] cmd: "..cmd.."\n");
					results = trim(api:executeString(cmd));
				--end the other uuids
					u = explode(",", uuids);
					for k,v in pairs(u) do
						if (uuid ~= v) then
							cmd = "uuid_kill "..v;
							--freeswitch.consoleLog("NOTICE", "[confirm] cmd: "..cmd.."\n");
							result = trim(api:executeString(cmd));
						end
					end

				--end the loop
					break;
			end

		--timeout
			x = x + 1;
			if (x > tonumber(timeout)) then
				--end the loop
					break;
			end
	end