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
--	Copyright (C) 2010-2014
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--include config.lua
	require "resources.functions.config";

--create the api object
	api = freeswitch.API();

	require "resources.functions.channel_utils";
	local log = require "resources.functions.log".follow_me
	local cache = require "resources.functions.cache"
	local Database = require "resources.functions.database"

--check if the session is ready
	if not session:ready() then return end

--answer the call
	session:answer();

--get the variables
	local domain_uuid = session:getVariable("domain_uuid");
	local domain_name = session:getVariable("domain_name");
	local extension_uuid = session:getVariable("extension_uuid");

--set the sounds path for the language, dialect and voice
	local sounds_dir = session:getVariable("sounds_dir");
	local default_language = session:getVariable("default_language") or 'en';
	local default_dialect = session:getVariable("default_dialect") or 'us';
	local default_voice = session:getVariable("default_voice") or 'callie';

--a moment to sleep
	session:sleep(1000);

--check if the session is ready
	if not session:ready() then return end

--connect to the database
	local dbh = Database.new('system');

--determine whether to update the dial string
	local sql = "select extension, number_alias, accountcode, follow_me_uuid ";
	sql = sql .. "from v_extensions ";
	sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
	sql = sql .. "and extension_uuid = '"..extension_uuid.."' ";
	if (debug["sql"]) then
		log.notice(sql);
	end

	local row = dbh:first_row(sql)
	if not row then return end

	local extension = row.extension;
	local number_alias = row.number_alias or '';
	local accountcode = row.accountcode;
	local follow_me_uuid = row.follow_me_uuid;

--determine whether to update the dial string
	sql = "select follow_me_enabled, call_prompt, cid_name_prefix, cid_number_prefix, dial_string "
	sql = sql .. "from v_follow_me ";
	sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
	sql = sql .. "and follow_me_uuid = '"..follow_me_uuid.."' ";
	if (debug["sql"]) then
		log.notice(sql);
	end

	row = dbh:first_row(sql)
	if not row then return end

	local enabled = row.follow_me_enabled;
	local call_prompt = row.call_prompt;
	local cid_name_prefix = row.cid_name_prefix;
	local cid_number_prefix = row.cid_number_prefix;
	local dial_string = row.dial_string;

--set follow me
	if (enabled == "false") then
		--play a tone
			channel_display(session:get_uuid(), "Activated")

			session:execute("sleep", "2000");
			session:execute("playback", "tone_stream://%(200,0,500,600,700)");
		--notify the caller
			--session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-call_forwarding_has_been_set.wav");
	end

--unset follow me
	if (enabled == "true") then
		--play a tone
			channel_display(session:get_uuid(), "Cancelled")

			session:execute("sleep", "2000");
			session:execute("playback", "tone_stream://%(500,0,300,200,100,50,25)");
		--notify the caller
			--session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-call_forwarding_has_been_cancelled.wav");
	end

--enable or disable follow me
	sql = "update v_follow_me set ";
	if (enabled == "true") then
		sql = sql .. "follow_me_enabled = 'false' ";
	else
		sql = sql .. "follow_me_enabled = 'true' ";
	end
	sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
	sql = sql .. "and follow_me_uuid = '"..follow_me_uuid.."' ";
	if (debug["sql"]) then
		log.notice(sql);
	end
	dbh:query(sql);

--update the extension
	sql = "update v_extensions set ";
	if (enabled == "true") then
		sql = sql .. "dial_string = null, ";
	else
		sql = sql .. "dial_string = '"..dial_string:gsub("'", "''").."', ";
	end
	sql = sql .. "do_not_disturb = 'false', ";
	sql = sql .. "forward_all_enabled= 'false' ";
	sql = sql .. "where domain_uuid = '"..domain_uuid.."' ";
	sql = sql .. "and extension_uuid = '"..extension_uuid.."' ";
	if (debug["sql"]) then
		log.notice(sql);
	end
	dbh:query(sql);

--clear the cache
	if (extension ~= nil) and cache.support() then
		cache.del("directory:"..extension.."@"..domain_name);
		if #number_alias > 0 then
			cache.del("directory:"..number_alias.."@"..domain_name);
		end
	end

--wait for the file to be written before proceeding
	session:sleep(1000);

--end the call
	session:hangup();
