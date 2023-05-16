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
--	Copyright (C) 2019
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set the debug level
	debug["sql"] = false;

--includes
	local cache = require"resources.functions.cache";
	local log = require"resources.functions.log"["call_block"];

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson";
	end

--include functions
	require "resources.functions.trim";
	require "resources.functions.explode";
	require "resources.functions.file_exists";

--get the variables
	if (session:ready()) then
		--session:setAutoHangup(false);
		domain_uuid = session:getVariable("domain_uuid");
		call_direction = session:getVariable("call_direction");
		caller_id_name = session:getVariable("caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");
		destination_number = session:getVariable("destination_number");
		context = session:getVariable("context");
		call_block = session:getVariable("call_block");
		extension_uuid = session:getVariable("extension_uuid");
		hold_music = session:getVariable("hold_music");
	end

--set default variables
	api = freeswitch.API();

--set the dialplan cache key
	local call_block_cache_key = "call_block:" .. caller_id_number;

--get the cache
	cached_value, err = cache.get(call_block_cache_key);
	if (debug['cache']) then
		if cached_value then
			log.notice(call_block_cache_key.." source: cache");
		elseif err ~= 'NOT FOUND' then
			log.notice("error cache: " .. err);
		end
	end

--disable the cache
	cached_value = nil;

--run call block one time
	if (call_block == nil and call_block ~= 'true') then

		--set the cache
		if (not cached_value) then

			--connect to the database
				local Database = require "resources.functions.database";
				dbh = Database.new('system');

			--include json library
				local json
				if (debug["sql"]) then
					json = require "resources.functions.lunajson";
				end

			--exits the script if we didn't connect properly
				assert(dbh:connected());

			--check to see if the call should be blocked
				sql = "select * from v_call_block\n";
				sql = sql .. "where (domain_uuid = :domain_uuid or domain_uuid is null) \n";
				sql = sql .. "and call_block_enabled = 'true' \n";
				sql = sql .. "and call_block_direction = :call_block_direction \n";
				sql = sql .. "and ( \n";
				sql = sql .. "	(\n";
				sql = sql .. "		call_block_name = :call_block_name \n";
				sql = sql .. "		and ( \n";
				sql = sql .. "			'+' || call_block_country_code || call_block_number = :call_block_number \n";
				sql = sql .. "			or call_block_country_code || call_block_number = :call_block_number \n";
				sql = sql .. "			or call_block_number = :call_block_number \n";
				sql = sql .. "		) \n";
				sql = sql .. "	) \n";
				sql = sql .. "	or (\n";
				sql = sql .. "		call_block_name is null \n";
				sql = sql .. "		and ( \n";
				sql = sql .. "			'+' || call_block_country_code || call_block_number = :call_block_number \n";
				sql = sql .. "			or call_block_country_code || call_block_number = :call_block_number \n";
				sql = sql .. "			or call_block_number = :call_block_number \n";
				sql = sql .. "		) \n";
				sql = sql .. "	) \n";
				sql = sql .. "	or (call_block_name = :call_block_name and call_block_number is null) \n";
				sql = sql .. ") \n";
				if (extension_uuid == nil) then
					sql = sql .. "and extension_uuid is null ";
				else
					sql = sql .. "and (extension_uuid is null or extension_uuid = :extension_uuid) ";
				end
				if (extension_uuid ~= nil) then
					if (call_direction == 'inbound') then
						params = {domain_uuid = domain_uuid, call_block_direction = call_direction, call_block_name = caller_id_name, call_block_number = caller_id_number, extension_uuid = extension_uuid};
					end
					if (call_direction == 'outbound') then
						params = {domain_uuid = domain_uuid, call_block_direction = call_direction, call_block_name = caller_id_name, call_block_number = destination_number, extension_uuid = extension_uuid};
					end
				else
					if (call_direction == 'inbound') then
						params = {domain_uuid = domain_uuid, call_block_direction = call_direction, call_block_name = caller_id_name, call_block_number = caller_id_number};
					end
					if (call_direction == 'outbound') then
						params = {domain_uuid = domain_uuid, call_block_direction = call_direction, call_block_name = caller_id_name, call_block_number = destination_number};
					end
				end
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[dialplan] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				local found = false;
				dbh:query(sql, params, function(row)
					found = true;

					--get the values from the database
					call_block_uuid = row.call_block_uuid;
					call_block_app = row.call_block_app;
					call_block_data = row.call_block_data;
					call_block_count = row.call_block_count;
					extension_uuid = row.extension_uuid;

					--cached_value = domain_uuid..','..caller_id_number;
				end);

			--set call block default to false
				call_block = false;
				if (call_block_app ~= nil) then
					call_block = true;
					if (session:ready()) then
						session:execute('set', 'call_block=true');
					end
				end

			--call block action
				if (call_block_app ~= nil and call_block_app == 'busy') then
					if (session:ready()) then
						session:execute("respond", '486');
						session:execute('set', 'call_block_uuid='..call_block_uuid);
						session:execute('set', 'call_block_app=busy');
						freeswitch.consoleLog("notice", "[call_block] caller id number " .. caller_id_number .. " action: Busy\n");
					end
				end
				if (call_block_app ~= nil and call_block_app == 'hold') then
					if (session:ready()) then
						session:execute('set', 'call_block_uuid='..call_block_uuid);
						session:execute('set', 'call_block_app=hold');
						session:execute("playback", hold_music);
						freeswitch.consoleLog("notice", "[call_block] caller id number " .. caller_id_number .. " action: Hold\n");
					end
				end
				if (call_block_app ~= nil and call_block_app == 'reject') then
					if (session:ready()) then
						session:execute("respond", '600');
						session:execute('set', 'call_block_uuid='..call_block_uuid);
						session:execute('set', 'call_block_app=reject');
						freeswitch.consoleLog("notice", "[call_block] caller id number " .. caller_id_number .. " action: Reject\n");
					end
				end
				if (call_block_app ~= nil and call_block_data ~= nil) then
					if (call_block_app == 'extension') then
						if (session:ready()) then
							session:execute('set', 'call_block_uuid='..call_block_uuid);
							session:execute('set', 'call_block_app='..call_block_app);
							session:execute('set', 'call_block_data='..call_block_data);
							session:execute("transfer", call_block_data..' XML '.. context);
							freeswitch.consoleLog("notice", "[call_block] caller id number " .. caller_id_number .. " action: extension ".. call_block_data.."\n");
						end
					end
					if (call_block_app == 'ivr') then
						if (session:ready()) then
							session:execute('set', 'call_block_uuid='..call_block_uuid);
							session:execute('set', 'call_block_app='..call_block_app);
							session:execute('set', 'call_block_data='..call_block_data);
							session:execute("transfer", call_block_data..' XML '.. context);
							freeswitch.consoleLog("notice", "[call_block] caller id number " .. caller_id_number .. " action: extension ".. call_block_data.."\n");
						end
					end
					if (call_block_app == 'ring_group') then
						if (session:ready()) then
							session:execute('set', 'call_block_uuid='..call_block_uuid);
							session:execute('set', 'call_block_app='..call_block_app);
							session:execute('set', 'call_block_data='..call_block_data);
							session:execute("transfer", call_block_data..' XML '.. context);
							freeswitch.consoleLog("notice", "[call_block] caller id number " .. caller_id_number .. " action: extension ".. call_block_data.."\n");
						end
					end
					if (call_block_app == 'voicemail') then
						if (session:ready()) then
							session:execute('set', 'call_block_uuid='..call_block_uuid);
							session:execute('set', 'call_block_app='..call_block_app);
							session:execute('set', 'call_block_data='..call_block_data);
							session:execute("transfer", '*99'..call_block_data..' XML '.. context);
							freeswitch.consoleLog("notice", "[call_block] caller id number " .. caller_id_number .. " action: voicemail *99".. call_block_data.."\n");
						end
					end
				end

			--update the call block count
				if (call_block) then
					sql = "update v_call_block ";
					sql = sql .. "set call_block_count = :call_block_count ";
					sql = sql .. "where call_block_uuid = :call_block_uuid ";
					local params = {call_block_uuid = call_block_uuid, call_block_count = call_block_count + 1};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[dialplan] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params);
				end

			--close the database connection
				dbh:release();

			--set the cache
				if (cached_value ~= nil) then
					local ok, err = cache.set(call_block_cache_key, cached_value, '3600');
				end
				if debug["cache"] then
					if ok then
						freeswitch.consoleLog("notice", "[call_block] " .. call_block_cache_key .. " stored in the cache\n");
					else
						freeswitch.consoleLog("warning", "[call_block] " .. call_block_cache_key .. " can not be stored in the cache: " .. tostring(err) .. "\n");
					end
				end

			--send to the console
				if (debug["cache"]) then
					freeswitch.consoleLog("notice", "[call_block] " .. call_block_cache_key .. " source: database\n");
				end
		else
			--send to the console
				if (debug["cache"]) then
					freeswitch.consoleLog("notice", "[call_block] " .. call_block_cache_key .. " source: cache\n");
				end
		end
	end
