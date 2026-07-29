--
--	sla_barge.lua
--
--	Shared-line "barge / join live call" feature for FusionPBX / FreeSWITCH.
--
--	Given an extension number, finds that extension's currently ACTIVE call
--	(excluding the caller's own channel) and joins it in full duplex, so every
--	party hears every party -- like picking up a shared POTS line.
--
--	Usage from the dialplan:
--		<action application="answer"/>
--		<action application="lua" data="sla_barge.lua $1"/>
--	where $1 is the captured extension, e.g. condition ^\*34(\d{2,7})$ -> $1.
--
--	Optional channel variables (set in the dialplan before the lua action):
--		barge_pin       - if set and non-empty, the caller must enter this PIN
--		barge_mode      - "full" (default) talk to both parties immediately,
--		                  "listen" join muted (press 3 on the keypad to talk)
--
--	Licensed MPL 1.1, same as the bundled FusionPBX scripts.

--set defaults
	max_tries = "3";
	digit_timeout = "5000";

--get the params
	extension = argv[1];

--includes
	require "resources.functions.config";
	require "resources.functions.file_exists";
	local Database = require "resources.functions.database"
	local dbh = Database.new('switch')

--json only needed for debug logging
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--bail out if the database is unreachable
	assert(dbh:connected());

--answer the call
	if (session:ready()) then
		session:answer();
	end

--get session variables
	if (session:ready()) then
		barge_pin = session:getVariable("barge_pin");
		barge_mode = session:getVariable("barge_mode");
		sounds_dir = session:getVariable("sounds_dir");
		domain_name = session:getVariable("domain_name");
		my_uuid = session:getVariable("uuid");
		--authenticated (digest) identity -- used for the security gate below.
		--note: sip_from_user is the caller-id number, NOT the extension, so it
		--must not be trusted here; sip_auth_username is the registered user.
		sip_authorized = session:getVariable("sip_authorized");
		auth_user = session:getVariable("sip_auth_username");
	end

--fall back to the auth realm for the domain
	if (session:ready() and (domain_name == nil or #domain_name == 0)) then
		domain_name = session:getVariable("sip_auth_realm");
	end

--set the sounds path for the language, dialect and voice
	if (session:ready()) then
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end
	end

--default the barge mode
	if (barge_mode == nil or #barge_mode == 0) then
		barge_mode = "full";
	end

--security gate: shared-line model.
--the caller must be an authenticated (registered) endpoint, and must be
--authenticated as the SAME extension it is trying to join.  this means you
--can only barge a line you are a member of -- e.g. another phone registered
--as 1001 may join 1001's call, but extension 2005 cannot dial *341001 and
--listen in.  for a non-shared extension there is no second leg to join, so
--this also confines the feature to genuine shared lines.
	if (session:ready()) then
		if (sip_authorized ~= "true" or auth_user == nil or auth_user ~= extension) then
			freeswitch.consoleLog("warning", "[sla_barge] denied: caller '"..tostring(auth_user)
				.."' (authorized="..tostring(sip_authorized)..") may only join its own line, not '"
				..tostring(extension).."'\n");
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/misc/error.wav");
			session:hangup("CALL_REJECTED");
			return;
		end
	end

--require a pin only when one is configured and non-empty
	if (session:ready() and barge_pin ~= nil and #barge_pin > 0) then
		min_digits = string.len(barge_pin);
		max_digits = string.len(barge_pin) + 1;
		digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#",
			sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-please_enter_pin_followed_by_pound.wav",
			"", "\\d+");
		if (digits == barge_pin) then
			freeswitch.consoleLog("notice", "[sla_barge] pin is correct\n");
		else
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-fail_auth.wav");
			session:hangup("NORMAL_CLEARING");
			return;
		end
	end

--find the active call for this extension, excluding our own channel.
--order by created_epoch so we join the oldest established leg (the real
--ongoing conversation) rather than a freshly created one.
	target_uuid = nil;
	if (session:ready() and extension ~= nil and #extension > 0 and domain_name ~= nil) then
		local presence_id = extension.."@"..domain_name;
		local sql = "select uuid, created_epoch from channels "
			.. "where presence_id = :presence_id "
			.. "and callstate = 'ACTIVE' "
			.. "and uuid <> :my_uuid "
			.. "order by created_epoch asc "
			.. "limit 1";
		local params = {presence_id = presence_id, my_uuid = my_uuid or ""};
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[sla_barge] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end
		dbh:query(sql, params, function(row)
			target_uuid = row.uuid;
		end);
	end

--no live call to join
	if (session:ready() and target_uuid == nil) then
		freeswitch.consoleLog("notice", "[sla_barge] no active call found for "..tostring(extension).."@"..tostring(domain_name).."\n");
		session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/misc/error.wav");
		session:hangup("NO_ROUTE_DESTINATION");
		return;
	end

--join the call.  whisper to both legs => full-duplex barge (everyone hears
--everyone).  in listen mode start muted; the caller can press 3 to talk.
	if (session:ready() and target_uuid ~= nil) then
		session:setVariable("eavesdrop_enable_dtmf", "true");
		if (barge_mode == "full") then
			session:setVariable("eavesdrop_whisper_aleg", "true");
			session:setVariable("eavesdrop_whisper_bleg", "true");
		end
		freeswitch.consoleLog("notice", "[sla_barge] joining "..target_uuid.." mode="..barge_mode.."\n");
		session:execute("eavesdrop", target_uuid);
	end
