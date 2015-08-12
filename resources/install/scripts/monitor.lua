--	monitor.lua
--	Part of FusionPBX
--	Copyright (C) 2010 Mark J Crane <markjcrane@fusionpbx.com>
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

--define general settings
	tmp_file = "/usr/local/freeswitch/log/monitor.tmp";

--used to stop the lua service
	local file = assert(io.open(tmp_file, "w"));
	file:write("remove this file to stop the script");

--define the trim function
	require "resources.functions.trim"

--check if a file exists
	require "resources.functions.file_exists"

--create the api object
	api = freeswitch.API();

--run lua as a service
	while true do

		--exit the loop when the file does not exist
				if (not file_exists(tmp_file)) then
						break;
				end

		--check to see if the sip profile is running
				sip_profile = "internal";
				result = trim(api:execute("sofia", "status profile "..sip_profile));
				if (result == "Invalid Profile!") then
					--start the sip profile
						result = trim(api:execute("sofia", "profile "..sip_profile.." start"));
						freeswitch.consoleLog("NOTICE", "monitor.lua: "..sip_profile.." is not running starting the sip profile.\n");
					--run sofia recover
						api = freeswitch.API();
						result = api:execute("sofia", "recover");
						freeswitch.consoleLog("NOTICE", "monitor.lua - sofia recover\n");
				end
				result = "";

		--check to see if the sip profile is running
				sip_profile = "external";
				result = trim(api:execute("sofia", "status profile "..sip_profile));
				if (result == "Invalid Profile!") then
					--start the sip profile
						result = trim(api:execute("sofia", "profile "..sip_profile.." start"));
						freeswitch.consoleLog("NOTICE", "monitor.lua: "..sip_profile.." is not running starting the sip profile.\n");
					--run sofia recover
						api = freeswitch.API();
						result = api:execute("sofia", "recover");
						freeswitch.consoleLog("NOTICE", "monitor.lua - sofia recover\n");
				end
				result = "";

		--send a message to the log
				--freeswitch.consoleLog("NOTICE", "monitor.lua "..result.."\n");

		--slow the loop down
				os.execute("sleep 3");

		--testing exit immediately
				--break;
	end