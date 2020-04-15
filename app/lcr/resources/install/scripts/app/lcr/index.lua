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

--set defaults
	expire = {}
	expire["lcr"] = "3600";

--get the variables
	domain_name = session:getVariable("domain_name");
	destination_number = session:getVariable("destination_number");
	outbound_caller_id_name = session:getVariable("outbound_caller_id_name");
	outbound_caller_id_number = session:getVariable("outbound_caller_id_number");
	lcr_profile = session:getVariable("lcr_profile");

	if (destination_number ~= nil) then
		destination_number = destination_number:gsub("+", '');
	end

--connect to the database
	local Database = require "resources.functions.database";

--include json library
	local json
	json = require "resources.functions.lunajson"

--prepare the api object
	api = freeswitch.API();

--define the trim function
	require "resources.functions.trim";

--get the cache
	cache = trim(api:execute("memcache", "get app:dialplan:outbound:lcr:" .. destination_number));

--get the destination number
	if (cache == "-ERR NOT FOUND") or (cache  == "-ERR CONNECTION FAILURE") or (cache == nil) then
		local dbh = Database.new('system');
		local sql = "SELECT json FROM v_xml_cdr WHERE direction = 'inbound' ";

		if (database["type"] == "mysql") then
			sql = sql .. "AND start_stamp >= NOW() - INTERVAL 1 WEEK ";
		else
			sql = sql .. "AND to_timestamp(start_epoch) >= NOW() - INTERVAL '1 WEEK' ";
		end

		sql = sql .. "ORDER BY start_stamp DESC";

		local transfer = 0;
		local params = {};

		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
		end

		dbh:query(sql, params, function(row)

			--set the outbound caller id
				if (outbound_caller_id_name ~= nil) then
					session:execute("export", "caller_id_name="..outbound_caller_id_name);
					session:execute("export", "effective_caller_id_name="..outbound_caller_id_name);
				end
				if (outbound_caller_id_number ~= nil) then
					session:execute("export", "caller_id_number="..outbound_caller_id_number);
					session:execute("export", "effective_caller_id_number="..outbound_caller_id_number);
				end

				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[app:dialplan:outbound:lcr] json" .. row.json  .. "\n");
				end

			--decode
				local payload = json.decode(row.json);
				local did = payload.variables['sip_req_user'];

				if (did ~= nil) then
					did = did:gsub("+", '');
				end

			--set the cache
				if (destination_number == did) then
					result = trim(api:execute("memcache", "set app:dialplan:outbound:lcr:" .. destination_number .. " '1' "..expire["lcr"]));
					session:execute("export", "call_direction=local");
					transfer = 1;
				else
					result = trim(api:execute("memcache", "set app:dialplan:outbound:lcr:" .. did .. " '1' "..expire["lcr"]));
				end
		end);
		
		if (transfer == 1) then
			freeswitch.consoleLog("notice", "[app:dialplan:outbound:lcr] " .. destination_number .. " XML publlic  source: database\n");
			session:transfer(destination_number, "XML", "public");
		else
			if (lcr_profile ~= nil) then
				freeswitch.consoleLog("notice", "[app:dialplan:outbound:lcr] bridge lcr/"..lcr_profile.."/"..destination_number.."  source: database\n");
				session:execute("bridge","lcr/"..lcr_profile.."/"..destination_number);
			else
				freeswitch.consoleLog("notice", "[app:dialplan:outbound:lcr] bridge lcr/default/"..destination_number.."  source: database\n");
				session:execute("bridge","lcr/default/"..destination_number);
			end
		end

	else
		--set the outbound caller id
			if (outbound_caller_id_name ~= nil) then
				session:execute("export", "caller_id_name="..outbound_caller_id_name);
				session:execute("export", "effective_caller_id_name="..outbound_caller_id_name);
			end
			if (outbound_caller_id_number ~= nil) then
				session:execute("export", "caller_id_number="..outbound_caller_id_number);
				session:execute("export", "effective_caller_id_number="..outbound_caller_id_number);
			end

		--send to the console
			freeswitch.consoleLog("notice", "[app:dialplan:outbound:lcr] " .. destination_number .. " source: memcache\n");

		--transfer the call
			session:transfer(destination_number, "XML", "public");
	end
