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

--debug the cache
	debug["cache"] = false;
	debug["sql"] = false;

--predefined variables
	predefined_destination = '';
	fallback_destination = '';

--define the trim function
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

--includes
	local cache = require "resources.functions.cache";

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson";
	end

--prepare the api object
	api = freeswitch.API();

--get the session variables
	if (session:ready()) then
		domain_name = session:getVariable("domain_name");
		domain_uuid = session:getVariable("domain_uuid");
		caller_id_number = session:getVariable("caller_id_number");
	end

--set the cache key
	key = "app:caller_id:lookup:domain_name:" .. domain_name .. ":" .. caller_id_number;

--get the cache
	cached_value, err = cache.get(key)
	if not cached_value  then
		--log cache error
			if (debug["cache"]) then
				freeswitch.consoleLog("warning", "[caller_id] " .. key .. " can not be get from the cache: " .. tostring(err) .. "\n");
			end

		--connect to the database
			local Database = require "resources.functions.database";
			local dbh = Database.new('system');

		--find the contact using the caller id number
			local sql = "select contact_organization, contact_name_family, contact_name_given ";
			sql = sql .. "from v_contacts ";
			sql = sql .. "where contact_uuid in ( ";
			sql = sql .. "	select contact_uuid from v_contact_phones ";
			sql = sql .. "	where domain_uuid = :domain_uuid ";
			sql = sql .. "	and ( ";
			sql = sql .. "		phone_number = :caller_id_number ";
			sql = sql .. "		or phone_country_code || phone_number = :caller_id_number ";
			sql = sql .. "		or '+' || phone_country_code || phone_number = :caller_id_number ";
			sql = sql .. "	) ";
			sql = sql .. "); ";
			local params = {caller_id_number = caller_id_number, domain_uuid = domain_uuid};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "SQL:" .. sql .. "; params: " .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params, function(row)
				contact_organization = row["contact_organization"];
				contact_name_family = row["contact_name_family"];
				contact_name_given = row["contact_name_given"];
			end);

		--build the caller id
			if (not contact_organization) then contact_organization = ''; end
			if (not contact_name_family) then contact_name_family = ''; end
			if (not contact_name_given) then contact_name_given = ''; end
			if (#contact_organization > 0) then
				caller_id_name = contact_organization;
			else
				caller_id_name = trim(contact_name_family ..' '.. contact_name_given);
			end

		--set the cache
			local ok, err = cache.set(key, caller_id_name, 7200);
			if debug["cache"] then
				if ok then
					freeswitch.consoleLog("notice", "[caller_id] " .. key .. " " .. caller_id_name .. " stored in the cache\n");
				else
					freeswitch.consoleLog("warning", "[caller_id] " .. key .. " " .. caller_id_name .. " can not be stored in the cache: " .. tostring(err) .. "\n");
				end
			end
	else
		--set the caller id name from the cached value
			caller_id_name = cached_value;

		--send to the log
			freeswitch.consoleLog("notice", "[caller_id] " .. key .. " " .. caller_id_name .. " found in the cache\n");
	end

--set the caller ID name
	if (session:ready() and caller_id_name and #caller_id_name > 0) then
		session:execute("set", "effective_caller_id_name="..caller_id_name);
	end
