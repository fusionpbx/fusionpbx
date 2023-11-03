--
--	FusionPBX - https://www.fusionpbx.com
--	Copyright (C) 2023 Mark J Crane <markjcrane@fusionpbx.com>
--
--	2-Clause BSD License
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
--	THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--include config.lua
	require "resources.functions.config";

--get the argv values
	file_name = argv[1];
	script_name = argv[2];

--validate the app_name
	if (script_name == 'contacts') then
		script_name = 'contacts';
	elseif (script_name == 'database') then
		script_name = 'database';
	else
		script_name = 'database';
	end

--route the request to the application
	if (#script_name > 0) then
		--freeswitch.consoleLog("notice", "["..file_name.."] file_name: " .. file_name .. " script_name: ".. script_name .."\n");
		dofile(scripts_dir .. "/app/caller_id/resources/scripts/"..script_name..".lua");
	end
