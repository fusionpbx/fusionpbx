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
--	Copyright (C) 2010
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>

predefined_destination = "";
max_tries = "3";
digit_timeout = "5000";

--debug
	debug["sql"] = true;

--include config.lua
	require "resources.functions.config";

--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

	api = freeswitch.API();

--define the trim function
	require "resources.functions.trim";

--define the explode function
	require "resources.functions.explode";

if ( session:ready() ) then
	session:answer( );
	reference_number = session:getVariable("reference_number");
	pin_number = session:getVariable("pin_number");
	sounds_dir = session:getVariable("sounds_dir");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");
	predefined_destination = session:getVariable("predefined_destination");
	digit_min_length = session:getVariable("digit_min_length");
	digit_max_length = session:getVariable("digit_max_length");
	gateway = session:getVariable("gateway");
	context = session:getVariable("context");
	sound_reference = session:getVariable("sound_reference");
	sound_pin = session:getVariable("sound_pin");
	sound_callback = session:getVariable("sound_callback");
	pin_min_length = session:getVariable("pin_min_length");
	pin_max_length = session:getVariable("pin_max_length");

	pinless = session:getVariable("pinless");
	callback = session:getVariable("callback");

	channel_name = session:getVariable("channel_name");

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--set defaults
		if (digit_min_length) then
			--do nothing
		else
			digit_min_length = "2";
		end

		if (digit_max_length) then
			--do nothing
			digit_max_length = digit_max_length+1;
		else
			digit_max_length = "12";
		end

		if (pin_min_length) then
			--do nothing
		else
			pin_min_length = "1";
		end

		if (pin_max_length) then
			--do nothing
		else
			pin_max_length = "16";
		end

		if (sound_reference) then
			--do nothing
		else
			sound_reference = "/misc/provide_reference_number.wav";
		end

		if (sound_pin) then
			--do nothing
		else
			sound_pin = "ivr/ivr-please_enter_pin_followed_by_pound.wav";
		end

		if (sound_callback) then
			--do nothing
		else
			sound_callback = "ivr/ivr-we_will_return_your_call_at_this_number.wav";
		end

		if ((reference_number ~= nil) and (pin_number ~= nil)) then
			freeswitch.consoleLog("notice", "[disa] you can not set reference_number and pin_number at same time\n");
			session:hangup("NORMAL_CLEARING");
			return;
		end

		freeswitch.consoleLog("notice", "[disa] caller_id_number "..caller_id_number.."\n");
		cmd = "user_exists id ".. caller_id_number .." "..context;
		user_exists = trim(api:executeString(cmd));
		freeswitch.consoleLog("notice", "[disa] user_exists "..user_exists.."\n");

	--if pinless then look the caller number in contacts
		if (pinless) then
			-- look the caller number
			sql = "select v_contacts.* from v_contacts inner join v_contact_settings s1 using (contact_uuid)   where s1.contact_setting_category = 'calling card' and s1.contact_setting_subcategory='pinless' and s1.contact_setting_name='phonenumber' and s1.contact_setting_value='"..caller_id_number.."'";

			status = dbh:query(sql, function(row)
                               	domain_uuid = row.domain_uuid;
				contact_uuid = row.contact_uuid;
				freeswitch.consoleLog("NOTICE", "[disa] domain_uuid "..row.domain_uuid.."\n");
				freeswitch.consoleLog("NOTICE", "[disa] contact_uuid "..row.contact_uuid.."\n");
			end);

		else
			--else if the pin number is provided then require it

			if (reference_number) then
				--do nothing
			else
				reference_number = session:playAndGetDigits(pin_min_length, pin_max_length, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..sound_reference, "", "\\d+");
			end

			freeswitch.consoleLog("notice", "[disa] reference_number "..reference_number.."\n");
			pin_digits = session:playAndGetDigits(pin_min_length, pin_max_length, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..sound_pin, "", "\\d+");
			freeswitch.consoleLog("notice", "[disa] pig_digits "..pin_digits.."\n");

			if (pin_number) then
				--pin number is fixed
				freeswitch.consoleLog("notice", "[disa] pin_number "..pin_number.."\n");
				if (pin_digits == pin_number) then
					--pin is correct
				else
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-pin_or_extension_is-invalid.wav");
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-im_sorry.wav");
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-goodbye.wav");
					session:hangup("NORMAL_CLEARING");
					return;
				end
				sql = "select v_contacts.* from v_contacts inner join v_contact_settings s1 using (contact_uuid) where s1.contact_setting_category = 'calling card' and s1.contact_setting_subcategory='authentication' and s1.contact_setting_name='username' and s1.contact_setting_value='"..reference_number.."'";
			else
				sql = "select v_contacts.* from v_contacts inner join v_contact_settings s1 using (contact_uuid) inner join v_contact_settings s2 using (contact_uuid)   where s1.contact_setting_category = 'calling card' and s1.contact_setting_subcategory='authentication' and s1.contact_setting_name='username' and s1.contact_setting_value='"..reference_number.."' and s2.contact_setting_category='calling card' and s2.contact_setting_subcategory='authentication' and s2.contact_setting_name='password' and s2.contact_setting_value='"..pin_digits.."'";
			end

			-- look in db for correct pin number
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[disa] "..sql.."\n");
			end

			status = dbh:query(sql, function(row)
       	                       	domain_uuid = row.domain_uuid;
				contact_uuid = row.contact_uuid;
				freeswitch.consoleLog("NOTICE", "[disa] domain_uuid "..row.domain_uuid.."\n");
				freeswitch.consoleLog("NOTICE", "[disa] contact_uuid "..row.contact_uuid.."\n");
			end);

			if (contact_uuid == nil) then
				--if reference_number with pin_number does not exist, then we abort
				session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-pin_or_extension_is-invalid.wav");
				session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-im_sorry.wav");
				session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-goodbye.wav");
				session:hangup("NORMAL_CLEARING");
				return;
			end

			--contact exists
			--looks for caller_id_name and caller_id_name
		end

	--if a predefined_destination is provided then set the number to the predefined_destination
		if (predefined_destination) then
			destination_number = predefined_destination;
		else
			dtmf = ""; --clear dtmf digits to prepare for next dtmf request
			destination_number = session:playAndGetDigits(digit_min_length, digit_max_length, max_tries, digit_timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");
			--if (string.len(destination_number) == 10) then destination_number = "1"..destination_number; end
		end

		freeswitch.consoleLog("NOTICE", "[disa] destination_number "..destination_number.."\n");

	--set the caller id name and number
		if (user_exists == "true") then
			if (caller_id_name) then
				--caller id name provided do nothing
			else
				caller_id_number = session:getVariable("effective_caller_id_name");
			end
			if (caller_id_number) then
				--caller id number provided do nothing
			else
				caller_id_number = session:getVariable("effective_caller_id_number");
			end
		else
			if (caller_id_name) then
				--caller id name provided do nothing
			else
				caller_id_name = session:getVariable("outbound_caller_id_name");
			end
			if (caller_id_number) then
				--caller id number provided do nothing
			else
				caller_id_number = session:getVariable("outbound_caller_id_number");
			end
		end

		if (callback) then
			--do callback stuff
			session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..sound_callback);
			freeswitch.consoleLog("notice", "[disa] scheduling to call back "..caller_id_number.."\n");
			freeswitch.consoleLog("notice", "[disa] channel_name "..channel_name.."\n");

			cmd="sched_api (+2 none lua disa.callback.lua "..caller_id_number.."  "..destination_number.." "..context.." "..context..")";
			freeswitch.consoleLog("notice", "[disa] cmd "..cmd.."\n");
			api:executeString(cmd);
			session:hangup("NORMAL_CLEARING");
			return;
		end


	--transfer or bridge the call

		if (user_exists == "true") then
			--local call
			--session:execute("transfer", destination_number .. " XML " .. context);
			session:execute("bridge", "{domain_uuid="..domain_uuid.."}user/".. destination_number .. "@" .. context);
		else
			--remote call
			if (gateway) then
				gateway_table = explode(",",gateway);
				for index,value in pairs(gateway_table) do
					session:execute("bridge", "{domain_uuid="..domain_uuid..",continue_on_fail=true,hangup_after_bridge=true,origination_caller_id_name="..caller_id_name..",origination_caller_id_number="..caller_id_number.."}sofia/gateway/"..value.."/"..destination_number);
				end
			else
				if (domain_uuid) then
					session:execute("set", "domain_uuid="..domain_uuid);
				end
				session:execute("set", "effective_caller_id_name="..caller_id_name);
				session:execute("set", "effective_caller_id_number="..caller_id_number);
				session:execute("transfer", destination_number .. " XML " .. context);
			end
		end

		--alternate method
			--local session2 = freeswitch.Session("{ignore_early_media=true}sofia/gateway/flowroute.com/"..destination_number);
			--t1 = os.date('*t');
			--call_start_time = os.time(t1);
			--freeswitch.bridge(session, session2);
end

--function HangupHook(s, status, arg)
	--session:execute("info", "");
	--freeswitch.consoleLog("NOTICE", "HangupHook: " .. status .. "\n");
--end
--session:setHangupHook("HangupHook", "");
