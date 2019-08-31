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
--	Portions created by the Initial Developer are Copyright (C) 2019
--	the Initial Developer. All Rights Reserved.

--includes
  local Database = require "resources.functions.database";
  local route_to_bridge = require "resources.functions.route_to_bridge"
  require "resources.functions.trim";

--get the variables
	if (session:ready()) then
		domain_name = session:getVariable("domain_name");
		domain_uuid = session:getVariable("domain_uuid");
		destination_number = session:getVariable("destination_number");
		caller_id_name = session:getVariable("caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");
		outbound_caller_id_name = session:getVariable("outbound_caller_id_name");
		outbound_caller_id_number = session:getVariable("outbound_caller_id_number");
	end

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--prepare the api object
	api = freeswitch.API();

--connect to the database
	local dbh = Database.new('system');

--select data from the database
	local sql = "select follow_me_uuid ";
	sql = sql .. "from v_extensions ";
	sql = sql .. "where domain_uuid = :domain_uuid ";
	sql = sql .. "and ( ";
	sql = sql .. "	extension = :destination_number ";
	sql = sql .. "	OR number_alias = :destination_number ";
	sql = sql .. ") ";
	local params = {domain_uuid = domain_uuid,destination_number = destination_number};
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
	end
	dbh:query(sql, params, function(row)

--get the follow me destinations
	sql = "select domain_uuid, follow_me_destination, follow_me_delay, follow_me_timeout, follow_me_prompt ";
	sql = sql .. "from v_follow_me_destinations ";
	sql = sql .. "where follow_me_uuid = :follow_me_uuid ";
	sql = sql .. "order by follow_me_order; ";
	local params = {follow_me_uuid = follow_me_uuid};
	status = dbh:query(sql, params, function(row)
		domain_uuid = row["domain_uuid"];
		follow_me_destination = row["follow_me_destination"];
		follow_me_delay = row["follow_me_delay"];
		follow_me_timeout = row["follow_me_timeout"];
		follow_me_prompt = row["ring_group_extension"];
		

	end);

--execute the time out action
	if ring_group_timeout_app and #ring_group_timeout_app > 0 then
		session:execute(ring_group_timeout_app, ring_group_timeout_data);
	end

--set ring ready
	if (session:ready()) then
		session:execute("ring_ready", "");
	end

--set the outbound caller id
    session:execute("set", "caller_id_name="..outbound_caller_id_name);
    session:execute("set", "effective_caller_id_name="..outbound_caller_id_name);

--send to the console
  --freeswitch.consoleLog("notice", "[app:follow_me] " .. value .. "\n");

--execute the time out action
	timeout_app = 'transfer';
	timeout_data = '*99' .. destination_number .. ' XML '..domain_name;
	if timeout_data and #timeout_data > 0 then
		session:execute(timeout_app, timeout_data);
	end
