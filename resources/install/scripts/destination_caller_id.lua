--	intercom.lua
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

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--add a trim function
	function trim (s)
		return (string.gsub(s, "^%s*(.-)%s*$", "%1"))
	end

--get the variables
	domain_name = session:getVariable("domain_name");
	outbound_number = session:getVariable("destination_number");
	freeswitch.consoleLog("notice", "outbound_number: --" .. outbound_number .. "--\n");
	outbound_area_code = string.sub(outbound_number,3,5);
	freeswitch.consoleLog("notice", "Area Code: " .. outbound_area_code .. "\n");

	--caller_id_name = session:getVariable("caller_id_name");
	--caller_id_number = session:getVariable("caller_id_number");

--get the destination_number
	sql = [[ SELECT * FROM v_destinations
	where destination_number like '1]].. outbound_area_code ..[[%']]
	freeswitch.consoleLog("notice", "SQL:" .. sql .. "\n");
	x = 0;
	dbh:query(sql, function(row)
		destination_number = row.destination_number;
		--destination_caller_id_name = row.destination_caller_id_name;
		--destination_caller_id_number = row.destination_caller_id_number;
		x = x + 1;
	end);

--session actions
	if (session:ready()) then
		if (destination_number) then
			freeswitch.consoleLog("notice", "effective_caller_id_number="..destination_number.."\n");
			session:execute("set", "effective_caller_id_number="..destination_number);
		end
		--session:hangup();
	end