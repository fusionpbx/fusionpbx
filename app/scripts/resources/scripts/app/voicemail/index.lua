--	Part of FusionPBX
--	Copyright (C) 2013-2019 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
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

--set default values
	min_digits = 1;
	max_digits = 8;
	max_tries = 3;
	max_timeouts = 3;
	digit_timeout = 3000;
	stream_seek = false;

--direct dial
	direct_dial = {}
	direct_dial["enabled"] = "false";
	direct_dial["max_digits"] = 4;

--debug
	--debug["info"] = true;
	--debug["sql"] = true;

--get the argv values
	script_name = argv[1];
	voicemail_action = argv[2];

--starting values
	dtmf_digits = '';
	timeouts = 0;
	password_tries = 0;

--connect to the database
	Database = require "resources.functions.database";
	dbh = Database.new('system');

--include json library (as global object)
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--set the api
	api = freeswitch.API();

--if the session exists
	if (session ~= nil) then

		--get session variables
			context = session:getVariable("context");
			sounds_dir = session:getVariable("sounds_dir");
			domain_name = session:getVariable("domain_name");
			uuid = session:getVariable("uuid");
			voicemail_id = session:getVariable("voicemail_id");
			voicemail_action = session:getVariable("voicemail_action");
			destination_number = session:getVariable("destination_number");
			caller_id_name = session:getVariable("caller_id_name");
			caller_id_number = session:getVariable("caller_id_number");
			current_time_zone = session:getVariable("timezone");
			effective_caller_id_number = session:getVariable("effective_caller_id_number");
			effective_caller_id_name = session:getVariable("effective_caller_id_name");
			voicemail_greeting_number = session:getVariable("voicemail_greeting_number");
			skip_instructions = session:getVariable("skip_instructions");
			skip_greeting = session:getVariable("skip_greeting");
			vm_message_ext = session:getVariable("vm_message_ext");
			vm_say_caller_id_number = session:getVariable("vm_say_caller_id_number");
			vm_say_date_time = session:getVariable("vm_say_date_time");
			vm_disk_quota = session:getVariable("vm-disk-quota");
			record_silence_threshold = session:getVariable("record-silence-threshold");
			voicemail_authorized = session:getVariable("voicemail_authorized");
			sip_from_user = session:getVariable("sip_from_user");
			user_name = session:getVariable("user_name");
			sip_number_alias = session:getVariable("sip_number_alias");
			origination_callee_id_name = session:getVariable("origination_callee_id_name");

		--modify caller_id_number if effective_caller_id_number is set
			if (effective_caller_id_number ~= nil) then
				caller_id_number = effective_caller_id_number;
			end
		--modify caller_id_name if effective_caller_id_name is set
			if (effective_caller_id_name ~= nil) then
				caller_id_name = effective_caller_id_name;
			end
	
		--set default values
			if (string.sub(caller_id_number, 1, 1) == "/") then
				caller_id_number = string.sub(caller_id_number, 2, -1);
			end
			if (not record_silence_threshold) then
				record_silence_threshold = 300;
			end
			if (not vm_disk_quota) then
				vm_disk_quota = session:getVariable("vm_disk_quota");
			end
			if (not vm_message_ext) then
				vm_message_ext = 'wav';
			end
			if (not vm_say_caller_id_number) then
				vm_say_caller_id_number = "true";
			end
			if (not vm_say_date_time) then
				vm_say_date_time = "true";
			end

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--get the domain_uuid
			domain_uuid = session:getVariable("domain_uuid");
			if (domain_count > 1) then
				if (domain_uuid == nil) then
					--get the domain_uuid using the domain name required for multi-tenant
						if (domain_name ~= nil) then
							local sql = "SELECT domain_uuid FROM v_domains ";
							sql = sql .. "WHERE domain_name = :domain_name ";
							local params = {domain_name = domain_name};
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
							end
							dbh:query(sql, params, function(rows)
								domain_uuid = rows["domain_uuid"];
							end);
						end
				end
			end
			if (domain_uuid ~= nil) then
				domain_uuid = string.lower(domain_uuid);
			end

		--if voicemail_id is non numeric then get the number-alias
			if (voicemail_id ~= nil) then
				if tonumber(voicemail_id) == nil then
					 voicemail_id = api:execute("user_data", voicemail_id .. "@" .. domain_name .. " attr number-alias");
				end
			end

		--set the voicemail_dir
			voicemail_dir = voicemail_dir.."/default/"..domain_name;
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] voicemail_dir: " .. voicemail_dir .. "\n");
			end

		--settings
			require "resources.functions.settings";
			settings = settings(domain_uuid);
			if (settings['voicemail'] ~= nil) then
				storage_type = '';
				if (settings['voicemail']['storage_type'] ~= nil) then
					if (settings['voicemail']['storage_type']['text'] ~= nil) then
						storage_type = settings['voicemail']['storage_type']['text'];
					end
				end

				storage_path = '';
				if (settings['voicemail']['storage_path'] ~= nil) then
					if (settings['voicemail']['storage_path']['text'] ~= nil) then
						storage_path = settings['voicemail']['storage_path']['text'];
						storage_path = storage_path:gsub("${domain_name}", domain_name);
						storage_path = storage_path:gsub("${voicemail_id}", voicemail_id);
						storage_path = storage_path:gsub("${voicemail_dir}", voicemail_dir);
					end
				end

				message_order = '';
				if (settings['voicemail']['message_order'] ~= nil) then
					if (settings['voicemail']['message_order']['text'] ~= nil) then
						message_order = settings['voicemail']['message_order']['text'];
					end
				end

				remote_access = '';
				if (settings['voicemail']['remote_access'] ~= nil) then
					if (settings['voicemail']['remote_access']['boolean'] ~= nil) then
						remote_access = settings['voicemail']['remote_access']['boolean'];
					end
				end

				password_complexity = '';
				if (settings['voicemail']['password_complexity'] ~= nil) then
					if (settings['voicemail']['password_complexity']['boolean'] ~= nil) then
						password_complexity = settings['voicemail']['password_complexity']['boolean'];
					end
				end

				password_min_length = '';
				if (settings['voicemail']['password_min_length'] ~= nil) then
					if (settings['voicemail']['password_min_length']['numeric'] ~= nil) then
						password_min_length = settings['voicemail']['password_min_length']['numeric'];
					end
				end

				not_found_message = 'false';
				if (settings['voicemail']['not_found_message'] ~= nil) then
					if (settings['voicemail']['not_found_message']['boolean'] ~= nil) then
						not_found_message = settings['voicemail']['not_found_message']['boolean'];
					end
				end

			end

			if (settings['voicemail']) then
				if settings['voicemail']['voicemail_to_sms'] then
					voicemail_to_sms = (settings['voicemail']['voicemail_to_sms']['boolean'] == 'true');
				end
				if settings['voicemail']['voicemail_to_sms_did'] then
					voicemail_to_sms_did = settings['voicemail']['voicemail_to_sms_did']['text'];
				end
				voicemail_to_sms_did = voicemail_to_sms_did or '';
			end

			if (not temp_dir) or (#temp_dir == 0) then
				if (settings['server'] ~= nil) then
					if (settings['server']['temp'] ~= nil) then
						if (settings['server']['temp']['dir'] ~= nil) then
							temp_dir = settings['server']['temp']['dir'];
						end
					end
				end
			end

		--get the voicemail settings
			if (voicemail_id ~= nil) then
				if (session ~= nil and session:ready()) then
					--get the information from the database
						local sql = [[SELECT * FROM v_voicemails
							WHERE domain_uuid = :domain_uuid
							AND voicemail_id = :voicemail_id
							AND voicemail_enabled = 'true' ]];
						local params = {domain_uuid = domain_uuid, voicemail_id = voicemail_id};
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
						end
						dbh:query(sql, params, function(row)
							voicemail_uuid = string.lower(row["voicemail_uuid"]);
							voicemail_password = row["voicemail_password"];
							greeting_id = row["greeting_id"];
							voicemail_alternate_greet_id = row["voicemail_alternate_greet_id"];
							voicemail_mail_to = row["voicemail_mail_to"];
							voicemail_attach_file = row["voicemail_attach_file"];
							voicemail_local_after_email = row["voicemail_local_after_email"];
							voicemail_transcription_enabled = row["voicemail_transcription_enabled"];
							voicemail_tutorial = row["voicemail_tutorial"];
						end);

					--set default values
						if (voicemail_local_after_email == nil) then
							voicemail_local_after_email = "true";
						end
						if (voicemail_attach_file == nil) then
							voicemail_attach_file = "true";
						end

					--valid voicemail
						if (voicemail_uuid ~= nil and string.len(voicemail_uuid) > 0) then
						--answer the session
							if (session ~= nil and session:ready()) then
								session:answer();
								session:sleep('1000');
							end

						--unset bind meta app
							session:execute("unbind_meta_app", "");

						end
				end
			end
	end

--set the callback function
	if (session ~= nil and session:ready()) then
		session:setVariable("playback_terminators", "#");
		session:setInputCallback("on_dtmf", "");
	end

--general functions
	require "resources.functions.base64";
	require "resources.functions.trim";
	require "resources.functions.file_exists";
	require "resources.functions.explode";
	require "resources.functions.format_seconds";
	require "resources.functions.mkdir";
	require "resources.functions.copy";

--voicemail functions
	require "app.voicemail.resources.functions.on_dtmf";
	require "app.voicemail.resources.functions.get_voicemail_id";
	require "app.voicemail.resources.functions.check_password";
	require "app.voicemail.resources.functions.change_password";
	require "app.voicemail.resources.functions.macro";
	require "app.voicemail.resources.functions.play_greeting";
	require "app.voicemail.resources.functions.record_message";
	require "app.voicemail.resources.functions.record_menu";
	require "app.voicemail.resources.functions.forward_add_intro";
	require "app.voicemail.resources.functions.forward_to_extension";
	require "app.voicemail.resources.functions.main_menu";
	require "app.voicemail.resources.functions.listen_to_recording";
	require "app.voicemail.resources.functions.message_waiting";
	require "app.voicemail.resources.functions.send_email";
	require "app.voicemail.resources.functions.send_sms";
	require "app.voicemail.resources.functions.delete_recording";
	require "app.voicemail.resources.functions.message_saved";
	require "app.voicemail.resources.functions.return_call";
	require "app.voicemail.resources.functions.menu_messages";
	require "app.voicemail.resources.functions.advanced";
	require "app.voicemail.resources.functions.record_greeting";
	require "app.voicemail.resources.functions.choose_greeting";
	require "app.voicemail.resources.functions.record_name";
	require "app.voicemail.resources.functions.message_count"
	require "app.voicemail.resources.functions.mwi_notify";
	require "app.voicemail.resources.functions.tutorial";

--send a message waiting event
	if (voicemail_action == "mwi") then
		--get the mailbox info
			account = argv[3];
			array = explode("@", account);
			voicemail_id = array[1];
			domain_name = array[2];

		--send information the console
			debug["info"] = "true";

		--get voicemail message details
			local sql = [[SELECT * FROM v_domains WHERE domain_name = :domain_name]];
			local params = {domain_name = domain_name};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params, function(row)
				domain_uuid = string.lower(row["domain_uuid"]);
			end);

		--get voicemail message details
			if (voicemail_id) then
				local sql = [[SELECT * FROM v_voicemails
					WHERE domain_uuid = :domain_uuid
					AND voicemail_id = :voicemail_id]]
				local params = {domain_uuid = domain_uuid, voicemail_id = voicemail_id};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
				end
				dbh:query(sql, params, function(row)
					voicemail_local_after_email = row["voicemail_local_after_email"];
				end);

			--set default values
				if (voicemail_local_after_email == nil) then
					voicemail_local_after_email = "true";
				end

			--get the message count and send the mwi event
				if (voicemail_local_after_email == 'true') then
					message_waiting(voicemail_id, domain_uuid);
				end
			end
	end

--check messages
	if (voicemail_action == "check") then
		if (session ~= nil and session:ready()) then
			--check the voicemail password
				if (voicemail_id) then
					if (voicemail_authorized) then
						if (voicemail_authorized == "true") then
							if (voicemail_id == user_name or voicemail_id == sip_number_alias) then
								--skip the password check
							else
								check_password(voicemail_id, password_tries);
							end
						else
							check_password(voicemail_id, password_tries);
						end
					else
						check_password(voicemail_id, password_tries);
					end
				else
					check_password(voicemail_id, password_tries);
				end

			--send to the main menu
				timeouts = 0;
				if (voicemail_tutorial == "true") then 
					tutorial("intro");
				else
					main_menu();
				end
		end
	end

--leave a message
	if (voicemail_action == "save") then

		--set the variables
			if (session ~= nil and session:ready()) then
				session:setVariable("missed_call", "true");
				session:setVariable("voicemail_answer_stamp", api:execute("strftime"));
				session:setVariable("voicemail_answer_epoch", api:execute("strepoch"));
			end

		--check the voicemail quota
			if (voicemail_uuid ~= nil and vm_disk_quota ~= nil) then
				--get voicemail message seconds
					local sql = [[SELECT coalesce(sum(message_length), 0) as message_sum FROM v_voicemail_messages
						WHERE domain_uuid = :domain_uuid
						AND voicemail_uuid = :voicemail_uuid]]
					local params = {domain_uuid = domain_uuid, voicemail_uuid = voicemail_uuid};
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
					end
					dbh:query(sql, params, function(row)
						message_sum = row["message_sum"];
					end);
					if (tonumber(vm_disk_quota) <= tonumber(message_sum)) then
						--play message mailbox full
							session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-mailbox_full.wav")
						--hangup
							session:hangup("NORMAL_CLEARING");
						--set the voicemail_uuid to nil to prevent saving the voicemail
							voicemail_uuid = nil;
					end
			end

		--valid voicemail
			if (voicemail_uuid ~= nil) then

				--play the greeting
					timeouts = 0;
					play_greeting();

				--save the message
					record_message();

				--process base64
					if (storage_type == "base64") then
						--show the storage type
							freeswitch.consoleLog("notice", "[voicemail] ".. storage_type .. "\n");

						--include the file io
							local file = require "resources.functions.file"

						-- build full path to file
							local full_path = voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext

							if file_exists(full_path) then
								--read file content as base64 string
									message_base64 = file.read_base64(full_path);
									--freeswitch.consoleLog("notice", "[voicemail] ".. message_base64 .. "\n");

								--delete the file
									os.remove(full_path);
							end
					end

				--get the voicemail destinations
					sql = [[select * from v_voicemail_destinations
					where voicemail_uuid = :voicemail_uuid]]
					params = {voicemail_uuid=voicemail_uuid};
					--freeswitch.consoleLog("notice", "[voicemail][destinations] SQL:" .. sql .. "; params:" .. json.encode(params) .. "\n");
					destinations = {};
					x = 1;
					
					dbh:query(sql, params, function(row)
						destinations[x] = row;
						x = x + 1;
					end);
					table.insert(destinations, {domain_uuid=domain_uuid,voicemail_destination_uuid=voicemail_uuid,voicemail_uuid=voicemail_uuid,voicemail_uuid_copy=voicemail_uuid});
				--show the storage type
					freeswitch.consoleLog("notice", "[voicemail] ".. storage_type .. "\n");
					
					count = 0
					for k,v in pairs(destinations) do
						count = count + 1
					end
					
				--loop through the voicemail destinations
					y = 1;
					for key,row in pairs(destinations) do
						--determine uuid
							if (y == count) then
								voicemail_message_uuid = uuid;
							else
								voicemail_message_uuid = api:execute("create_uuid");
							end
							y = y + 1;
						--save the message to the voicemail messages
							if (tonumber(message_length) > 2) then
								caller_id_name = string.gsub(caller_id_name,"'","''");
								local sql = {}
								table.insert(sql, "INSERT INTO v_voicemail_messages ");
								table.insert(sql, "(");
								table.insert(sql, "voicemail_message_uuid, ");
								table.insert(sql, "domain_uuid, ");
								table.insert(sql, "voicemail_uuid, ");
								table.insert(sql, "created_epoch, ");
								table.insert(sql, "caller_id_name, ");
								table.insert(sql, "caller_id_number, ");
								if (storage_type == "base64") then
									table.insert(sql, "message_base64, ");
								end
								if (transcribe_enabled == "true") and (voicemail_transcription_enabled == "true") then
									table.insert(sql, "message_transcription, ");
								end
								table.insert(sql, "message_length ");
								--table.insert(sql, "message_status, ");
								--table.insert(sql, "message_priority, ");
								table.insert(sql, ") ");
								table.insert(sql, "VALUES ");
								table.insert(sql, "( ");
								table.insert(sql, ":voicemail_message_uuid, ");
								table.insert(sql, ":domain_uuid, ");
								table.insert(sql, ":voicemail_uuid, ");
								table.insert(sql, ":start_epoch, ");
								table.insert(sql, ":caller_id_name, ");
								table.insert(sql, ":caller_id_number, ");
								if (storage_type == "base64") then
									table.insert(sql, ":message_base64, ");
								end
								if (transcribe_enabled == "true") and (voicemail_transcription_enabled == "true") then
									table.insert(sql,  ":transcription, ");
								end
								table.insert(sql, ":message_length ");
								--table.insert(sql, ":message_status, ");
								--table.insert(sql, ":message_priority ");
								table.insert(sql, ") ");
								sql = table.concat(sql, "\n");
								local params = {
									voicemail_message_uuid = voicemail_message_uuid;
									domain_uuid = domain_uuid;
									voicemail_uuid = row.voicemail_uuid_copy;
									start_epoch = start_epoch;
									caller_id_name = caller_id_name;
									caller_id_number = caller_id_number;
									message_base64 = message_base64;
									transcription = transcription;
									message_length = message_length;
									--message_status = message_status;
									--message_priority = message_priority;
								};
								if (debug["sql"]) then
									freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
								end
								if (storage_type == "base64") then
									local Database = require "resources.functions.database"
									local dbh = Database.new('system', 'base64');
									dbh:query(sql, params);
									dbh:release();
								else
									dbh:query(sql, params);
								end
							end

							local params = {domain_uuid = domain_uuid, voicemail_uuid = row.voicemail_uuid_copy};

						--get new message count
							sql = [[SELECT count(*) as new_messages FROM v_voicemail_messages
								WHERE domain_uuid = :domain_uuid
								AND voicemail_uuid = :voicemail_uuid
								AND (message_status is null or message_status = '') ]];
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
							end
							dbh:query(sql, params, function(result)
								new_messages = result["new_messages"];
							end);

						--get saved message count
							sql = [[SELECT count(*) as saved_messages FROM v_voicemail_messages
								WHERE domain_uuid = :domain_uuid
								AND voicemail_uuid = :voicemail_uuid
								AND message_status = 'saved' ]];
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
							end
							dbh:query(sql, params, function(result)
								saved_messages = result["saved_messages"];
							end);

						--get the voicemail_id
							sql = [[SELECT voicemail_id FROM v_voicemails WHERE voicemail_uuid = :voicemail_uuid]];
							if (debug["sql"]) then
								freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
							end
							dbh:query(sql, params, function(result)
								voicemail_id_copy = result["voicemail_id"];
							end);

						--make sure the voicemail directory exists
							mkdir(voicemail_dir.."/"..voicemail_id_copy);

						--copy the voicemail to each destination
							if (file_exists(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext)) then
								local src = voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext
								local dst = voicemail_dir.."/"..voicemail_id_copy.."/msg_"..voicemail_message_uuid.."."..vm_message_ext
								if src ~= dst then
									copy(src, dst)
								end
							end

						--send the message waiting event
							if (tonumber(message_length) > 2) then
								message_waiting(voicemail_id_copy, domain_uuid);
							end

						--send the email with the voicemail recording attached
							if (tonumber(message_length) > 2) then
								send_email(voicemail_id_copy, voicemail_message_uuid);
								if (voicemail_to_sms) then
									send_sms(voicemail_id_copy, voicemail_message_uuid);
								end
							end
					end --for

			else
				--voicemail not enabled or does not exist
					if (session ~= nil and session:ready()) then
						referred_by = session:getVariable("sip_h_Referred-By");
						if (referred_by) then
							referred_by = referred_by:match('[%d]+');
							session:transfer(referred_by, "XML", context);
						else
							if (not_found_message == "true") then
								session:answer();
								session:execute("sleep", "1000");
								session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-no_answer_no_vm.wav");
								session:hangup();
							end
						end
					end
			end
	end

--close the database connection
	dbh:release();

--notes
	--record the video
		--records audio only
			--result = session:execute("set", "enable_file_write_buffering=false");
			--mkdir(voicemail_dir.."/"..voicemail_id);
			--session:recordFile("/tmp/recording.fsv", 200, 200, 200);
		--records audio and video
			--result = session:execute("record_fsv", "file.fsv");
			--freeswitch.consoleLog("notice", "[voicemail] SQL: " .. result .. "\n");

	--play the video recording
		--plays the video
			--result = session:execute("play_fsv", "/tmp/recording.fsv");
		--plays the file but without the video
			--dtmf = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "/tmp/recording.fsv", "", "\\d+");
		--freeswitch.consoleLog("notice", "[voicemail] SQL: " .. result .. "\n");

	--callback (works with DTMF)
		--http://wiki.freeswitch.org/wiki/Mod_fsv
		--mkdir(voicemail_dir.."/"..voicemail_id);
		--session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
		--session:sayPhrase(macro_name [,macro_data] [,language]);
		--session:sayPhrase("voicemail_menu", "1:2:3:#", default_language);
		--session:streamFile("directory/dir-to_select_entry.wav"); --works with setInputCallback
		--session:streamFile("tone_stream://L=1;%(1000, 0, 640)");
		--session:say("12345", default_language, "number", "pronounced");

		--speak
			--session:set_tts_parms("flite", "kal");
			--session:speak("Please say the name of the person you're trying to contact");

	--callback (execute and executeString does not work with DTMF)
		--session:execute(api_string);
		--session:executeString("playback "..mySound);

	--uuid_video_refresh
		--uuid_video_refresh,<uuid>,Send video refresh.,mod_commands
		--may be used to clear video buffer before using record_fsv
