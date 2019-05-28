--
--      call_block
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
	12 Feb, 2018: Added support for regular expressions and SQL "like" matching  on the phone number
]]

--set defaults
	expire = {}
	expire["call_block"] = "900";
	source = "";

--includes
	local cache = require"resources.functions.cache"
	local log = require"resources.functions.log"["call_block"];
	--json = require "resources.functions.lunajson";

-- Command line parameters
	local params = {
		cid_num = string.match(tostring(session:getVariable("caller_id_number")), "%d+"),
		cid_name = session:getVariable("caller_id_name"),
		domain_name = session:getVariable("domain_name"),
		userid = "", -- session:getVariable("id")
		loglevel = "W" -- Warning, Debug, Info
		}

--check if cid_num is numeric
	if (tonumber(params["cid_num"]) == nil) then
		return
	end

-- local storage
	local sql = nil

--define the functions
	local Settings = require "resources.functions.lazy_settings"
	local Database = require "resources.functions.database"
	require "resources.functions.trim";

--define the logger function
	local function logger(level, log, data)
		-- output data to console 'log' if debug level is on
		if string.find(params["loglevel"], level) then
			freeswitch.consoleLog(log, "[call_block] " .. data .. "\n")
		end
	end

--set the api object
	api = freeswitch.API();

-- ensure that we have a fresh status on exit
	session:setVariable("call_block", "");

-- get the configuration variables from the DB
	local db = dbh or Database.new('system')
	local settings = Settings.new(db, domain_name, domain_uuid)
	local call_block_matching = settings:get('call_block', 'call_block_matching', 'text');

--send to the log
	logger("D", "NOTICE", "params are: " .. string.format("'%s', '%s', '%s', '%s'", params["cid_num"],
			params["cid_name"], params["userid"], params["domain_name"]));

--set the dialplan cache key
	local cache_key = "app:call_block:" .. params["domain_name"] .. ":" .. params["cid_num"];

--get the cache
	cache_data, err = cache.get(cache_key);
	if (debug['cache']) then
		if cache_data then
			log.notice(cache_key.." source: cache");
		elseif (not cache_data) then
			log.notice("error get element from cache: " .. err);
		end
	end

--connect to the database
	Database = require "resources.functions.database";
	dbh = Database.new('system');

--log if not connect
	if dbh:connected() == false then
		logger("W", "NOTICE", "db was not connected")
	end

--check if the the call block is blocked
	sql = "SELECT * FROM v_call_block as c "
	sql = sql .. "JOIN v_domains as d ON c.domain_uuid=d.domain_uuid "
	if ((database["type"] == "pgsql") and (call_block_matching == "regex")) then
		logger("W", "NOTICE", "call_block using regex match on cid_num")
		sql = sql .. "WHERE :cid_num ~ c.call_block_number AND d.domain_name = :domain_name "
	elseif (((database["type"] == "mysql") or (database["type"] == "sqlite")) and (call_block_matching == "regex")) then
		logger("W", "NOTICE", "call_block using regex match on cid_num")
		sql = sql .. "WHERE :cid_num REGEXP c.call_block_number AND d.domain_name = :domain_name "
	elseif call_block_matching == "like" then
		logger("W", "NOTICE", "call_block using like match on cid_num")
		sql = sql .. "WHERE :cid_num LIKE c.call_block_number AND d.domain_name = :domain_name "
	else
		logger("W", "NOTICE", "call_block using exact match on cid_num")
		sql = sql .. "WHERE :cid_num = c.call_block_number AND d.domain_name = :domain_name "
	end
	--freeswitch.consoleLog("notice", "[call_block] " .. sql .. "\n");
	dbh:query(sql, params, function(rows)
		found_cid_num = rows["call_block_number"];
		found_uuid = rows["call_block_uuid"];
		found_enabled = rows["call_block_enabled"];
		found_action = rows["call_block_action"];
		found_count = rows["call_block_count"];
	end)
	-- dbh:affected_rows() doesn't do anything if using core:db so this is the workaround:

--check if number is in call_block list then increment the counter and block the call
	--if not cached then get the information from the database
	if (not cache_data) then
		--set the cache
			if (found_cid_num) then	-- caller id exists
				if (found_enabled == "true") then
					--set the cache
					cache_data = "found_cid_num=" .. found_cid_num .. "&found_uuid=" .. found_uuid .. "&found_enabled=" .. found_enabled .. "&found_action=" .. found_action;
					local ok, err = cache.set(cache_key, cache_data, expire["call_block"]);
					if debug["cache"] then
						if ok then
							freeswitch.consoleLog("notice", "[call_block] " .. cache_key .. " stored in the cache\n");
						else
							freeswitch.consoleLog("warning", "[call_block] " .. cache_key .. " can not be stored in the cache: " .. tostring(err) .. "\n");
						end
					end

					--set the source
					source = "database";
				end
			end

	else
		--get from the cache
			--add the function
				require "resources.functions.explode";

			--parse the cache
				array = explode("&", cache_data);

			--define the array/table and variables
				local var = {}
				local key = "";
				local value = "";

			--parse the cache
				key_pairs = explode("&", cache_data);
				for k,v in pairs(key_pairs) do
					f = explode("=", v);
					key = f[1];
					value = f[2];
					var[key] = value;
				end

			--set the variables
				found_cid_num = var["found_cid_num"];
				found_uuid = var["found_uuid"];
				found_enabled = var["found_enabled"];
				found_action = var["found_action"];

			--set the source
				source = "cache";
	end

--debug information
	--freeswitch.consoleLog("error", "[call_block] " .. cache_data .. "\n");
	--freeswitch.consoleLog("error", "[call_block] found_cid_num = " .. found_cid_num  .. "\n");
	--freeswitch.consoleLog("error", "[call_block] found_enabled = " .. found_enabled  .. "\n");
	--freeswitch.consoleLog("error", "[call_block] source = " .. source  .. "\n");
	--freeswitch.consoleLog("error", "[call_block] found_count = " .. found_count  .. "\n");

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
			logger("W", "NOTICE", "number " .. params["cid_num"] .. " blocked with " .. found_count .. " previous hits, domain_name: " .. params["domain_name"])

			-- Command line parameters
			local params = {
				call_block_count = found_count + 1,
				call_block_uuid = found_uuid
			}
			--update the call block count
			local sql = "UPDATE v_call_block SET call_block_count = :call_block_count WHERE call_block_uuid = :call_block_uuid";
			dbh:query(sql, params);
			freeswitch.consoleLog("error", "[call_block] udpate\n");
			--freeswitch.consoleLog("error", "[call_block] sql = " .. sql  .. "\n");
			--freeswitch.consoleLog("error", "[call_block] " .. json.encode(params) .. "\n");

			session:execute("set", "call_blocked=true");
			if (found_action == "Reject") then
				session:hangup("CALL_REJECTED")
			elseif (found_action == "Busy") then
				session:hangup("USER_BUSY")
			elseif (found_action =="Hold") then
				session:setAutoHangup(false)
				session:execute("transfer", "*9664")
			elseif (details[0] =="Voicemail") then
				session:setAutoHangup(false)
				session:execute("transfer", "*99" .. details[2] .. " XML  " .. details[1])
			end
		end
	end
