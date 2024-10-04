--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2010-2024
--	All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--define variables
sounds_dir = "";
pin_number = "";
max_tries = "3";
digit_timeout = "3000";

--debug sql
debug["sql"] = false;

--check if the session is ready
if not session:ready() then return end

--include json library
local json
if (debug["sql"]) then
	json = require "resources.functions.lunajson"
end

--include the lua classes
local log = require "resources.functions.log".fifo
local Database = require "resources.functions.database"
local cache = require "resources.functions.cache"

--connect to the database
local dbh = Database.new('system');

--get the session variables
if (session:ready()) then
	session:answer();
	domain_uuid = session:getVariable("domain_uuid");
	domain_name = session:getVariable("domain_name");
	fifo_uuid = session:getVariable("fifo_uuid");
	fifo_name = session:getVariable("fifo_name");
	fifo_simo = session:getVariable("fifo_simo");
	fifo_timeout = session:getVariable("fifo_timeout");
	fifo_lag = session:getVariable("fifo_lag");
	user_name = session:getVariable("user_name");
	pin_number = session:getVariable("pin_number");
	sounds_dir = session:getVariable("sounds_dir");
end

--number of simultaneous calls for one extension with multiple registrations
if (fifo_simo == nil) then
	fifo_simo = '5';
end

--how long to ring the fifo member (agent)
if (fifo_timeout == nil) then
	fifo_timeout = '20';
end

--wrap up time for the fifo member
if (fifo_lag == nil) then
	fifo_lag = '';
end

--sleep
if (session:ready()) then
	session:sleep(500);
end

--check the pin number when a value is set
if (session:ready() and pin_number) then
	--get the user pin number
	min_digits = 2;
	max_digits = 20;
	digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");

	--validate the user pin number
	pin_number_table = explode(",",pin_number);
	for index,pin_number in pairs(pin_number_table) do
		if (digits == pin_number) then
			--pin is correct
			auth = true;

			--set the authorized pin number that was used
			session:setVariable("pin_number", pin_number);

			--sleep
			session:sleep(500);

			--end the loop
			break;
		else
			--pin is not valid
			session:streamFile("phrase:voicemail_fail_auth:#");
			session:hangup("NORMAL_CLEARING");
			return;
		end
	end
end

--get the fifo members from the database
sql = [[SELECT * FROM v_fifo_members
	WHERE fifo_uuid = :fifo_uuid
	AND member_contact like :fifo_member]];
local params = {fifo_uuid = fifo_uuid, fifo_member = '%'..user_name..'%'};
if (debug["sql"]) then
	log.notice("SQL: " .. sql .. "; params: " .. json.encode(params));
end
dbh:query(sql, params, function(row)
	fifo_member_uuid = row["fifo_member_uuid"];
	member_contact = row["member_contact"];
	member_call_timeout = row["member_call_timeout"];
	fifo_lag = row["member_wrap_up_time"];
end);

--press 1 to login and 2 to logout
if (session:ready()) then
	menu_selection = session:playAndGetDigits(1, 1, max_tries, digit_timeout, "#", "phrase:agent_status:#", "", "\\d+");
	freeswitch.consoleLog("NOTICE", "menu_selection: "..menu_selection.."\n");
	if (menu_selection == "1") then
		--login the agent into the queue
		session:execute("set", "fifo_member_add_result=${fifo_member(add "..fifo_name.." {fifo_member_wait=nowait}user/"..user_name.." "..fifo_simo.." "..fifo_timeout.." "..fifo_lag.."} )"); --simo timeout lag

		--send the result to the log
		fifo_member_add_result = session:getVariable("fifo_member_add_result");
		freeswitch.consoleLog("NOTICE", "fifo_member_add_result: "..fifo_member_add_result.."\n");

		--enable or disable follow me
		sql = "update v_fifo_members ";
		sql = sql .. "set member_enabled = 'true' ";
		sql = sql .. "where fifo_member_uuid = :fifo_member_uuid ";
		local params = {domain_uuid=domain_uuid, fifo_member_uuid=fifo_member_uuid};
		if (debug["sql"]) then
			log.notice("SQL: %s; params: %s", sql, json.encode(params));
		end
		dbh:query(sql, params);

		--play logged in audio
		session:streamFile("ivr/ivr-you_are_now_logged_in.wav");
	end
	if (menu_selection == "2") then
		--log the agent out of the queue
		session:execute("set", "fifo_member_del_result=${fifo_member(del "..fifo_name.." {fifo_member_wait=nowait}user/"..user_name.."} )");

		--enable or disable follow me
		sql = "update v_fifo_members ";
		sql = sql .. "set member_enabled = 'false' ";
		sql = sql .. "where fifo_member_uuid = :fifo_member_uuid ";
		local params = {domain_uuid=domain_uuid, fifo_member_uuid=fifo_member_uuid};
		if (debug["sql"]) then
			log.notice("SQL: %s; params: %s", sql, json.encode(params));
		end
		dbh:query(sql, params);

		--play logged out audio
		session:streamFile("ivr/ivr-you_are_now_logged_out.wav");
	end

	--clear the cache
	if (cache.support()) then
		cache.del("dialplan."..domain_name);
	end

	--hangup
	if (session:ready()) then
		session:sleep(1000);
		session:hangup();
	end

end
