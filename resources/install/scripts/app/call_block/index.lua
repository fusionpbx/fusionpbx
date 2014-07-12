--
--      call_block-FS
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
--      The Original Code is call_block-FS
--
--      The Initial Developer of the Original Code is
--      Gerrit Visser <gerrit308@gmail.com>
--      Copyright (C) 2011
--      the Initial Developer. All Rights Reserved.
--
--      Contributor(s):
--      Gerrit Visser <gerrit308@gmail.com>
--      Mark J Crane <markjcrane@fusionpbx.com>
--[[
This module provides for Blacklisting of phone numbers. Essentially these are numbers that you do not want to hear from again!

To call this script and pass it arguments:
1. On the command line, e.g. in a FS incoming dialplan: <action application="lua" data="call_block.lua C"/>
This method causes the script to get its manadatory arguments directly from the Session
]]
--[[ Change Log:
	15 Jun, 2011: initial release > FusionPBX
	15 Jun, 2011: Added loglevel parameter and logger function to simplify control of debug output
	4 May, 2012: tested with FusionPBX V3
	4 May, 2012: added per_tenant capability (domain based)
	12 Jun, 2013: update the database connection, change table name from v_callblock to v_call_block
	14 Jun, 2013: Change Voicemail option to use Transfer, avoids mod_voicemail dependency
	27 Sep, 2013: Changed the name of the fields to conform with the table name
]]

--set defaults
	expire = {}
	expire["call_block"] = "3600";
	source = "";

-- Command line parameters
	local	params = {
				cid_num = string.gsub(tostring(session:getVariable("caller_id_number")), "+", ""),
				cid_name = session:getVariable("caller_id_name"),
				domain = session:getVariable("domain"),
				userid = "", -- session:getVariable("id")
				loglevel = "W" -- Warning, Debug, Info
				}

-- local storage
	local sql = nil

--define the functions
	dofile(scripts_dir.."/resources/functions/trim.lua");

--define the logger function
	local function logger(level, log, data)
		-- output data to console 'log' if debug level is on
		if string.find(params["loglevel"], level) then
			freeswitch.consoleLog(log, "[Call Block]: " .. data .. "\n")
		end
	end

--set the api object
	api = freeswitch.API();

-- ensure that we have a fresh status on exit
	session:setVariable("call_block", "")

--send to the log
	logger("D", "NOTICE", "params are: " .. string.format("'%s', '%s', '%s', '%s'", params["cid_num"], 
			params["cid_name"], params["userid"], params["domain"]));

--get the cache
	if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
		cache = trim(api:execute("memcache", "get app:call_block:" .. params["domain"] .. ":" .. params["cid_num"]));
	else
		cache = "-ERR NOT FOUND";
	end

--check if number is in call_block list then increment the counter and block the call
	--if not cached then get the information from the database.

--debug information
	--freeswitch.consoleLog("error", "[call_block] " .. cache .. "\n");
	--freeswitch.consoleLog("error", "[call_block] found_cid_num = " .. found_cid_num  .. "\n");
	--freeswitch.consoleLog("error", "[call_block] found_enabled = " .. found_enabled  .. "\n");
	--freeswitch.consoleLog("error", "[call_block] source = " .. source  .. "\n");

--block the call
	if found_cid_num then	-- caller id exists
		if (found_enabled == "true") then
			details = {}
			k = 0
			for v in string.gmatch(found_action, "[%w%.]+") do
				details[k] = v
				--logger("W", "INFO", "Details: " .. details[k])
				k = k + 1
			end
			if (source == "database") then
				dbh:query("UPDATE v_call_block SET call_block_count = " .. found_count + 1 .. " WHERE call_block_uuid = '" .. found_uuid .. "'")
			end
			session:setVariable("call_block", "block")
			logger("W", "NOTICE", "number " .. params["cid_num"] .. " blocked with " .. found_count .. " previous hits, domain: " .. params["domain"])
			if (found_action == "Reject") then
				session:hangup("CALL_REJECTED")
			end
			if (found_action == "Busy") then
				session:hangup("USER_BUSY")
			end
			if (details[0] =="Voicemail") then
				session:setAutoHangup(false)
				session:execute("transfer", "*99" .. details[2] .. " XML  " .. details[1])
			end
		end
	end
