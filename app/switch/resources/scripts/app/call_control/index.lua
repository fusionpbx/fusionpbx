--	call_control.lua
--	Part of FusionPBX
--	Copyright (C) 2024 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--[[

Summary

	Used to enable or disable calling based on the call group.

Dialplan

	<extension name="call_control" continue="false" uuid="3f239929-634a-4931-b2b2-137773934a32">
		<condition field="destination_number" expression="^\*33$">
			<action application="set" data="pin_number="/>
			<action application="set" data="audio_prompt=ivr/ivr-call_control.wav"/>
			<action application="set" data="target_group=support"/>
			<action application="set" data="context_enabled=domain"/>
			<action application="set" data="context_disabled=limit"/>
			<action application="lua" data="app.lua call_control"/>
		</condition>
	</extension>

Audio Prompt

	The audio prompt variable can be a phrase or a sound file placed in the sounds directory.

	audio_prompt=phrase:agent_status:#
	ivr/ivr-call_control.wav

]]

--set default settings
pin_number = "";
max_tries = "3";
digit_timeout = "3000";

--define the trim function
require "resources.functions.trim";

--define the explode function
require "resources.functions.explode";

--define the split function
require "resources.functions.split";

--connect to the database
local Database = require "resources.functions.database";
dbh = Database.new('system');

--exits the script if we didn't connect properly
assert(dbh:connected());

--answer the call
if (session:ready()) then
	session:answer();
end

--get the session variables
if (session:ready()) then
	--get the dialplan variables and set them as local variables
	target_group = session:getVariable("target_group");
	audio_prompt = session:getVariable("audio_prompt");
	pin_number = session:getVariable("pin_number");
	domain_name = session:getVariable("domain_name");
	domain_uuid = session:getVariable("domain_uuid");
	sounds_dir = session:getVariable("sounds_dir");
	context_enabled = session:getVariable("context_enabled");
	context_disabled = session:getVariable("context_disabled");

	--set the sounds path for the language, dialect and voice
	default_language = session:getVariable("default_language");
	default_dialect = session:getVariable("default_dialect");
	default_voice = session:getVariable("default_voice");
end

--set the defaults
if (not audio_prompt) then audio_prompt = 'phrase:agent_status:#'; end
if (not default_language) then default_language = 'en'; end
if (not default_dialect) then default_dialect = 'us'; end
if (not default_voice) then default_voice = 'callie'; end
if (not context_enabled) then context_enabled = 'domain'; end
if (not context_disabled) then context_disabled = 'limit'; end

--if domain is set then use the domain name
if (context_enabled == 'domain' or context_enabled == 'domain_name') then
	context_enabled = domain_name;
end

--if the pin number is provided then require it
if (pin_number) then
	--sleep
	session:sleep(500);

	--get the user pin number
	min_digits = 2;
	max_digits = 20;
	digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");

	--validate the user pin number
	pin_number_table = explode(",",pin_number);
	for index,pin_number in pairs(pin_number_table) do
		if (digits == pin_number) then
			--set the variable to true
			auth = true;

			--set the authorized pin number that was used
			session:setVariable("pin_number", pin_number);

			--end the loop
			break;
		end
	end

	--if not authorized play a message and then hangup
	if (not auth) then
		session:streamFile("phrase:voicemail_fail_auth:#");
		session:hangup("NORMAL_CLEARING");
		return;
	end
end

--get the user pin number
pressed_digit = session:playAndGetDigits(1, 1, 1, digit_timeout, "#", audio_prompt, "", "\\d+");

--update the database and flush the cache
if (session:ready() and pressed_digit) then

	--set the default context
	context = domain_name;

	--allow calling
	if (pressed_digit == '1') then
		call_control = 'enabled';
		user_context = context_enabled;
		call_display = 'Calls Enabled';
		session:setVariable("call_control", 'enabled');
	end

	--block calling
	if (pressed_digit == '2') then
		call_control = 'disabled';
		user_context = context_disabled;
		call_display = 'Call Disabled';
	end

	--add channel variables for call detail records
	session:setVariable("call_control_context", user_context);
	session:setVariable("call_control_group", target_group);
	if (call_control == 'enabled') then
		session:setVariable("call_control_status", 'enabled');
	end
	if (call_control == 'disabled') then
		session:setVariable("call_control_status", 'disabled');
	end

	--log the destinations
	freeswitch.consoleLog("NOTICE", "[call_control] context "..user_context.."\n");

	--update the extensions in the call group
	local sql = "update v_extensions set ";
	sql = sql .. "user_context = :user_context ";
	sql = sql .. "where domain_uuid = :domain_uuid ";
	sql = sql .. "and call_group = :call_group ";
	local params = {
		user_context = user_context;
		domain_uuid = domain_uuid;
		call_group = target_group;
	}
	if (debug["sql"]) then
		log.noticef("SQL: %s; params: %s", sql, json.encode(params));
	end
	dbh:query(sql, params);

	--clear the cache
	os.execute('rm -f /var/cache/fusionpbx/directory.*@'..domain_name);

end

--display label on Phone (if support)
if (session:ready()) then
	session:sleep(1000);
	local api = freeswitch.API();
	local reply = api:executeString("uuid_display "..session:get_uuid().." "..call_display);
end

--play the audio fil or tone
if (session:ready()) then
	session:sleep(2000);
	audio_file = "tone_stream://%(200,0,500,600,700)"
end

