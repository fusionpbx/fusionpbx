--	Part of FusionPBX
--	Copyright (C) 2015-2023 Mark J Crane <markjcrane@fusionpbx.com>
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

-- Contributor: Joseph Nadiv <ynadiv@corpit.xyz>

--set the debug options
debug["sql"] = false;

--define the explode function
	require "resources.functions.explode";
	require "resources.functions.trim";

--set the defaults
	max_tries = 3;
	digit_timeout = 5000;
	max_retries = 3;
	tries = 0;

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--get the domain_uuid
	domain_uuid = session:getVariable("domain_uuid");

--get the user and domain name from the user argv user@domain
	sip_from_uri = session:getVariable("sip_from_uri");
	user_table = explode("@",sip_from_uri);
	domain_table = explode(":",user_table[2]);
	user = user_table[1];
	domain = domain_table[1];

--show the phone that we're looking up
	if (sip_from_uri ~= nil) then
		freeswitch.consoleLog("NOTICE", "[e911] sip_from_uri: ".. user .. "@" .. domain .. "\n");
	end

--get the device uuid for the phone that will have its configuration overridden
	if (user ~= nil and domain ~= nil and domain_uuid ~= nil) then
		local sql = [[SELECT device_uuid FROM v_device_lines ]];
		sql = sql .. [[WHERE user_id = :user ]];
		sql = sql .. [[AND server_address = :domain ]];
		sql = sql .. [[AND domain_uuid = :domain_uuid ]];
		local params = {user = user, domain = domain, domain_uuid = domain_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "[e911] SQL: ".. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			--get device uuid
			device_uuid = row.device_uuid;
			freeswitch.consoleLog("NOTICE", "[e911] device_uuid: ".. device_uuid .. "\n");
		end);
	end

-- If this device logged in as a hotdesk, get the real device id
	if (device_uuid ~= nil and domain_uuid ~= nil) then
		local sql = [[SELECT * FROM v_devices ]];
		sql = sql .. [[WHERE device_uuid_alternate = :device_uuid ]];
		sql = sql .. [[AND domain_uuid = :domain_uuid ]];
		local params = {device_uuid = device_uuid, domain_uuid = domain_uuid};
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "[e911] SQL: ".. sql .. "; params: " .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			if (row.device_uuid_alternate ~= nil) then
				device_mac_address = row.device_mac_address;
                device_emergency_cid = row.device_emergency_cid;
                freeswitch.consoleLog("NOTICE", 
                    "[e911] device_mac_address: ".. device_mac_address .. ", device_emergency_cid " .. device_emergency_cid .. "\n");
			end
		end);
	end

-- If we got a device_emergency_cid then set it
    if (device_emergency_cid ~= nil and string.len(device_emergency_cid) > 0) then
        session:setVariable('effective_caller_id_number', device_emergency_cid);
    end