--	voicemail.lua
--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
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

--set default values
	min_digits = 1;
	max_digits = 8;
	max_tries = 3;
	max_timeouts = 3;
	digit_timeout = 3000;

--debug
	debug["info"] = false;
	debug["sql"] = false;

--starting values
	dtmf_digits = '';
	timeouts = 0;
	password_tries = 0;

--include the lua script
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	include = assert(loadfile(scripts_dir .. "/resources/config.lua"));
	include();

--connect to the database
	--ODBC - data source name
		if (dsn_name) then
			dbh = freeswitch.Dbh(dsn_name,dsn_username,dsn_password);
		end
	--FreeSWITCH core db handler
		if (db_type == "sqlite") then
			dbh = freeswitch.Dbh("core:"..db_path.."/"..db_name);
		end

--answer the session
	if (session:ready()) then
		session:answer();
	end

--get session variables
	context = session:getVariable("context");
	sounds_dir = session:getVariable("sounds_dir");
	domain_name = session:getVariable("domain_name");
	uuid = session:getVariable("uuid");
	voicemail_id = session:getVariable("voicemail_id");
	voicemail_action = session:getVariable("voicemail_action");
	base_dir = session:getVariable("base_dir");
	destination_number = session:getVariable("destination_number");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");
	skip_instructions = session:getVariable("skip_instructions");
	skip_greeting = session:getVariable("skip_greeting");
	vm_message_ext = session:getVariable("vm_message_ext");
	if (not vm_message_ext) then vm_message_ext = 'wav'; end

--set the sounds path for the language, dialect and voice
	default_language = session:getVariable("default_language");
	default_dialect = session:getVariable("default_dialect");
	default_voice = session:getVariable("default_voice");
	if (not default_language) then default_language = 'en'; end
	if (not default_dialect) then default_dialect = 'us'; end
	if (not default_voice) then default_voice = 'callie'; end

--get the domain_uuid
	domain_uuid = session:getVariable("domain_uuid");
	if (domain_uuid == nil) then
		--get the domain_uuid
			if (domain_name ~= nil) then
				sql = "SELECT domain_uuid FROM v_domains ";
				sql = sql .. "WHERE domain_name = '" .. domain_name .."' ";
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
				end
				status = dbh:query(sql, function(rows)
					domain_uuid = rows["domain_uuid"];
				end);
			end
	end
	domain_uuid = string.lower(domain_uuid);

--set the voicemail_dir
	voicemail_dir = base_dir.."/storage/voicemail/default/"..domain_name;
	if (debug["info"]) then
		freeswitch.consoleLog("notice", "[voicemail] voicemail_dir: " .. voicemail_dir .. "\n");
	end

--check if a file exists
	function file_exists(name)
		local f=io.open(name,"r")
		if f~=nil then io.close(f) return true else return false end
	end

--format seconds to 00:00:00
	function format_seconds(seconds)
		local seconds = tonumber(seconds);
		if seconds == 0 then
			return "00:00:00";
		else
			hours = string.format("%02.f", math.floor(seconds/3600));
			minutes = string.format("%02.f", math.floor(seconds/60 - (hours*60)));
			seconds = string.format("%02.f", math.floor(seconds - hours*3600 - minutes *60));
			return string.format("%02d:%02d:%02d", hours, minutes, seconds);
		end
	end

--define on_dtmf call back function
	function on_dtmf(s, type, obj, arg)
		if (type == "dtmf") then
			dtmf_digits = dtmf_digits .. obj['digit'];
			if (debug["info"]) then
				freeswitch.console_log("info", "[voicemail] dtmf digits: " .. dtmf_digits .. ", length: ".. string.len(dtmf_digits) .." max_digits: " .. max_digits .. "\n");
			end
			if (obj['digit'] == "#") then
				return 0;
			else
				if (string.len(dtmf_digits) >= max_digits) then
					if (debug["info"]) then
						freeswitch.console_log("info", "[voicemail] max_digits reached\n");
					end
					return 0;
				end
			end
			freeswitch.console_log("info", "[voicemail] dtmf digit: " .. obj['digit'] .. ", duration: " .. obj['duration'] .. "\n"); 
		end
		return 0;
	end
	if (session:ready()) then
		session:setInputCallback("on_dtmf", "");
	end

--get the voicemail id
	function get_voicemail_id()
		session:flushDigits();
		dtmf_digits = '';
		voicemail_id = macro(session, "voicemail_id", 20, 5000, '');
		if (string.len(voicemail_id) == 0) then
			if (session:ready()) then
				timeouts = timeouts + 1;
				if (timeouts < max_timeouts) then
					voicemail_id = get_voicemail_id();
				else
					macro(session, "goodbye", 1, 1000, '');
					session:hangup();
				end
			end
		end
		return voicemail_id;
	end

--check the voicemail password
	function check_password(voicemail_id, password_tries)
		if (session:ready()) then

			--flush dtmf digits from the input buffer
				session:flushDigits();

			--please enter your id followed by pound
				if (voicemail_id) then
					--do nothing
				else
					timeouts = 0;
					voicemail_id = get_voicemail_id();
					if (debug["info"]) then
						freeswitch.consoleLog("notice", "[voicemail] voicemail id: " .. voicemail_id .. "\n");
					end
				end

			--get the voicemail settings from the database
				if (voicemail_id) then
					if (session:ready()) then
						sql = [[SELECT * FROM v_voicemails
							WHERE domain_uuid = ']] .. domain_uuid ..[['
							AND voicemail_id = ']] .. voicemail_id ..[['
							AND voicemail_enabled = 'true' ]];
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
						status = dbh:query(sql, function(row)
							voicemail_uuid = string.lower(row["voicemail_uuid"]);
							voicemail_password = row["voicemail_password"];
							greeting_id = row["greeting_id"];
							voicemail_mail_to = row["voicemail_mail_to"];
							voicemail_attach_file = row["voicemail_attach_file"];
							voicemail_local_after_email = row["voicemail_local_after_email"];
						end);
					end
				end
			--please enter your password followed by pound
				dtmf_digits = '';
				password = macro(session, "voicemail_password", 20, 5000, '');
				--freeswitch.consoleLog("notice", "[voicemail] password: " .. password .. "\n");
			--compare the password from the database with the password provided by the user
				if (voicemail_password ~= password) then
					--incorrect password
					dtmf_digits = '';
					macro(session, "password_not_valid", 1, 1000, '');
					if (session:ready()) then
						password_tries = password_tries + 1;
						if (password_tries < max_tries) then
							check_password(voicemail_id, password_tries);
						else
							macro(session, "goodbye", 1, 1000, '');
							session:hangup();
						end
					end
				end
		end
	end

--check the voicemail password
	function change_password(voicemail_id)
		if (session:ready()) then
			--flush dtmf digits from the input buffer
				session:flushDigits();
			--please enter your password followed by pound
				dtmf_digits = '';
				password = macro(session, "password_new", 20, 5000, '');
			--update the voicemail password
				sql = [[UPDATE v_voicemails
					set voicemail_password = ']] .. password ..[['
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_id = ']] .. voicemail_id ..[['
					AND voicemail_enabled = 'true' ]];
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				dbh:query(sql);
			--has been changed to
				dtmf_digits = '';
				macro(session, "password_changed", 20, 3000, password);
			--advanced menu
				timeouts = 0;
				advanced();
		end
	end

--define the macro function
	function macro(session, name, max_digits, max_timeout, param)
		if (session:ready()) then

			--Please enter your id followed by
				if (name == "voicemail_id") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-enter_id.wav"});
					table.insert(actions, {app="streamFile",data="digits/pound.wav"});
				end
			 --Please enter your id followed by
				if (name == "voicemail_password") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-enter_pass.wav"});
					table.insert(actions, {app="streamFile",data="digits/pound.wav"});
				end
			--the person at extension 101 is not available record your message at the tone press any key or stop talking to end the recording
				if (name == "person_not_available_record_message") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-person.wav"});
					--pronounce the voicemail_id
					table.insert(actions, {app="say.number.iterated",data=voicemail_id});
					table.insert(actions, {app="streamFile",data="voicemail/vm-not_available.wav"});
				end
			--record your message at the tone press any key or stop talking to end the recording
				if (name == "record_message") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-record_message.wav"});
					table.insert(actions, {app="tone_stream",data="L=1;%(1000, 0, 640)"});
				end
			--beep
				if (name == "record_beep") then
					actions = {}
					table.insert(actions, {app="tone_stream",data="L=1;%(1000, 0, 640)"});
				end
			--to listen to the recording press 1
				if (name == "to_listen_to_recording") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-listen_to_recording.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/1.wav"});
				end
			--to save the recording press 2
				if (name == "to_save_recording") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-save_recording.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/2.wav"});
				end
			--to rerecord press 3
				if (name == "to_rerecord") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-rerecord.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/3.wav"});
				end
			--You have zero new messages
				if (name == "new_messages") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-you_have.wav"});
					table.insert(actions, {app="say.number.pronounced",data=param});
					table.insert(actions, {app="streamFile",data="voicemail/vm-new.wav"});
					if (param == "1") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-message.wav"});
					else
						table.insert(actions, {app="streamFile",data="voicemail/vm-messages.wav"});
					end
				end
			--You have zero saved messages
				if (name == "saved_messages") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-you_have.wav"});
					table.insert(actions, {app="say.number.pronounced",data=param});
					table.insert(actions, {app="streamFile",data="voicemail/vm-saved.wav"});
					if (param == "1") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-message.wav"});
					else
						table.insert(actions, {app="streamFile",data="voicemail/vm-messages.wav"});
					end
				end
			--To listen to new messages press 1
				if (name == "listen_to_new_messages") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-listen_new.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/1.wav"});
				end
			--To listen to saved messages press 2
				if (name == "listen_to_saved_messages") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-listen_saved.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/2.wav"});
				end

			--For advanced options press 5
				if (name == "advanced") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-advanced.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/5.wav"});
				end
			--Advanced Options Menu
				--To record a greeting press 1
					if (name == "to_record_greeting") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-to_record_greeting.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/1.wav"});
					end
					--Choose a greeting between 1 and 9
						if (name == "choose_greeting_choose") then
							actions = {}
							table.insert(actions, {app="streamFile",data="voicemail/vm-choose_greeting_choose.wav"});
						end
					--Greeting invalid value
						if (name == "choose_greeting_fail") then
							actions = {}
							table.insert(actions, {app="streamFile",data="voicemail/vm-choose_greeting_fail.wav"});
						end
					--Record your greeting at the tone press any key or stop talking to end the recording
						if (name == "record_greeting") then
							actions = {}
							table.insert(actions, {app="streamFile",data="voicemail/vm-record_greeting.wav"});
							table.insert(actions, {app="tone_stream",data="L=1;%(1000, 0, 640)"});
						end
				--To choose greeting press 2
					if (name == "choose_greeting") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-choose_greeting.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/2.wav"});
					end
					--Choose a greeting between 1 and 9
						--if (name == "choose_greeting_choose") then
						--	actions = {}
						--	table.insert(actions, {app="streamFile",data="voicemail/vm-choose_greeting_choose.wav"});
						--end
							--press 1 to listen to the recording
							--press 2 to save the recording
								--message saved
							--press 3 to re-record
					--Invalid greeting number
						--if (name == "choose_greeting_fail") then
						--	actions = {}
						--	table.insert(actions, {app="streamFile",data="voicemail/vm-choose_greeting_fail.wav"});
						--end
					--Greeting 1 selected
						if (name == "greeting_selected") then
							actions = {}
							table.insert(actions, {app="streamFile",data="voicemail/vm-greeting.wav"});
							table.insert(actions, {app="streamFile",data="digits/"..param..".wav"});
							table.insert(actions, {app="streamFile",data="voicemail/vm-selected.wav"});
						end

				--To record your name 3
					if (name == "to_record_name") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-record_name2.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/3.wav"});
					end
				--At the tone please record your name press any key or stop talking to end the recording 
					if (name == "record_name") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-record_name1.wav"});
						table.insert(actions, {app="tone_stream",data="L=1;%(1000, 0, 640)"});
					end
				--To change your password press 6
					if (name == "change_password") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-change_password.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/6.wav"});
					end
				--For the main menu press 0
					if (name == "main_menu") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-main_menu.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/0.wav"});
					end
			--To exit press *
				if (name == "to_exit_press") then
					actions = {}
					table.insert(actions, {app="streamFile",data="voicemail/vm-to_exit.wav"});
					table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="streamFile",data="digits/star.wav"});
				end
			--Additional Macros
				--Please enter your new password then press the # key #
					if (name == "password_new") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-enter_new_pin.wav"});
					end
				--Has been changed to
					if (name == "password_changed") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-has_been_changed_to.wav"});
						table.insert(actions, {app="say.number.iterated",data=param});
					end
				--Login Incorrect
					--if (name == "password_not_valid") then
					--	actions = {}
					--	table.insert(actions, {app="streamFile",data="voicemail/vm-password_not_valid.wav"});
					--end
				--Login Incorrect
					if (name == "password_not_valid") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-fail_auth.wav"});
					end
				--Too many failed attempts
					if (name == "too_many_failed_attempts") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-abort.wav"});
					end
				--Message number
					if (name == "message_number") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-message_number.wav"});
					end
				--To listen to the recording press 1
					if (name == "listen_to_recording") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-listen_to_recording.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/1.wav"});
					end
				--To save the recording press 2
					if (name == "save_recording") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-save_recording.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/2.wav"});
					end
				--To delete the recording press 7
					if (name == "delete_recording") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-delete_recording.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/7.wav"});
					end
				--Message deleted
					if (name == "message_deleted") then
						table.insert(actions, {app="streamFile",data="voicemail/vm-message.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-deleted.wav"});
					end
				--To return the call now press 5
					if (name == "return_call") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-return_call.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/5.wav"});
					end
				--To forward this message press 8
					if (name == "to_forward_message") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-to_forward.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/8.wav"});
					end
				--Please enter the extension to forward this message to followed by #
					if (name == "forward_enter_extension") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-forward_enter_ext.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-followed_by_pound.wav"});
					end
				--To forward this recording to your email press 9
					if (name == "forward_to_email") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-forward_to_email.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="streamFile",data="digits/9.wav"});
					end
				--Emailed
					if (name == "emailed") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-emailed.wav"});
					end
				--Please enter the extension to send this message to followed by #
					--if (name == "send_message_to_extension") then
					--	actions = {}
					--	table.insert(actions, {app="streamFile",data="voicemail/vm-zzz.wav"});
					--end
				--Message saved
					if (name == "message_saved") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-message.wav"});
						table.insert(actions, {app="streamFile",data="voicemail/vm-saved.wav"});
					end
				--Your recording is below the minimal acceptable length, please try again.
					if (name == "too_small") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-too-small.wav"});
					end
				--Goodbye
					if (name == "goodbye") then
						actions = {}
						table.insert(actions, {app="streamFile",data="voicemail/vm-goodbye.wav"});
					end

			--if actions table exists then process it
				if (actions) then
					--set default values
						tries = 1;
						timeout = 100;
					--loop through the action and data
						for key, row in pairs(actions) do
							--freeswitch.consoleLog("notice", "[voicemail] app: " .. row.app .. " data: " .. row.data .. "\n");
							if (session:ready()) then
								if (string.len(dtmf_digits) == 0) then
									if (row.app == "streamFile") then
										session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data);
									elseif (row.app == "playback") then
										session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data);
									elseif (row.app == "tone_stream") then
										session:streamFile("tone_stream://"..row.data);
									elseif (row.app == "silence_stream") then
										session:streamFile("silence_stream://100"..row.data);
									elseif (row.app == "playAndGetDigits") then
										--playAndGetDigits <min> <max> <tries> <timeout> <terminators> <file> <invalid_file> <var_name> <regexp> <digit_timeout>
										if (not file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data)) then
											dtmf_digits = session:playAndGetDigits(min_digits, max_digits, tries, timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data, "", "\\d+", max_timeout);
										else
											dtmf_digits = session:playAndGetDigits(min_digits, max_digits, tries, timeout, "#", row.data, "", "\\d+", max_timeout);
										end
									elseif (row.app == "say.number.pronounced") then
										session:say(row.data, default_language, "number", "pronounced");
									elseif (row.app == "say.number.iterated") then
										session:say(row.data, default_language, "number", "iterated");
									end
									session:streamFile("silence_stream://100");
								end --if
							end --session:ready
						end --for
					--get the remaining digits
						if (session:ready()) then
							if (string.len(dtmf_digits) < max_digits) then
								dtmf_digits = dtmf_digits .. session:getDigits(max_digits, "#", max_timeout);
							end
						end
					--return dtmf the digits
						return dtmf_digits;
			else
				--no dtmf digits to return
					return '';
			end
		end
	end

--get the voicemail settings
	if (voicemail_id ~= nil) then
		if (session:ready()) then
			--get the information from the database
				sql = [[SELECT * FROM v_voicemails
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_id = ']] .. voicemail_id ..[['
					AND voicemail_enabled = 'true' ]];
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				status = dbh:query(sql, function(row)
					voicemail_uuid = string.lower(row["voicemail_uuid"]);
					voicemail_password = row["voicemail_password"];
					greeting_id = row["greeting_id"];
					voicemail_mail_to = row["voicemail_mail_to"];
					voicemail_attach_file = row["voicemail_attach_file"];
					voicemail_local_after_email = row["voicemail_local_after_email"];
				end);
			--set default values
				if (voicemail_local_after_email == nil) then
					voicemail_local_after_email = "true";
				end
				if (voicemail_attach_file == nil) then
					voicemail_attach_file = "true";
				end
		end
	end

--save the recording
	function record_message()

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--record your message at the tone press any key or stop talking to end the recording
			if (skip_greeting == "true") then
				dtmf_digits = '';
				result = macro(session, "record_beep", 1, 100);
			else
				dtmf_digits = '';
				result = macro(session, "record_message", 1, 100);
			end

		--start epoch
			start_epoch = os.time();
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] start epoch: " .. start_epoch .. "\n");
			end

		--save the recording
			-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
			max_len_seconds = 30;
			silence_threshold = 30;
			silence_seconds = 5;
			os.execute("mkdir -p " .. voicemail_dir.."/"..voicemail_id);
			result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext, max_len_seconds, silence_threshold, silence_seconds);
			--session:execute("record", voicemail_dir.."/"..uuid.." 180 200");

		--stop epoch
			stop_epoch = os.time();
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] start epoch: " .. stop_epoch .. "\n");
			end

		--calculate the message length
			message_length = stop_epoch - start_epoch;
			message_length_formatted = format_seconds(message_length);
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] message length: " .. message_length .. "\n");
			end

		--if the recording is below the minmal length then re-record the message
			if (message_length > 2) then
				--continue
			else
				if (session:ready()) then
					--your recording is below the minimal acceptable length, please try again
						dtmf_digits = '';
						macro(session, "too_small", 1, 100);
					--record your message at the tone
						timeouts = timeouts + 1;
						if (timeouts < max_timeouts) then
							record_message();
						else
							timeouts = 0;
							record_menu();
						end
				end
			end

		--instructions press 1 to listen to the recording, press 2 to save the recording, press 3 to re-record
			if (session:ready()) then
				if (skip_instructions == "true") then
					--save the message
						dtmf_digits = '';
						macro(session, "message_saved", 1, 100, '');
						macro(session, "goodbye", 1, 100, '');
					--hangup the call
						session:hangup();
				else
					timeouts = 0;
					record_menu();
				end
			end
	end

--record message menu
	function record_menu()
		if (session:ready()) then
			--clear the dtmf digits variable
				dtmf_digits = '';
			--flush dtmf digits from the input buffer
				session:flushDigits();
			--to listen to the recording press 1
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "to_listen_to_recording", 1, 100, '');
					end
				end
			--to save the recording press 2
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "to_save_recording", 1, 100, '');
					end
				end
			--to re-record press 3
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "to_rerecord", 1, 3000, '');
					end
				end
			--process the dtmf
				if (session:ready()) then
					if (dtmf_digits == "1") then
						--listen to the recording
							session:streamFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
							--dtmf_digits = session:playAndGetDigits(min_digits, max_digits, tries, timeout, "#", voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext, "", "\\d+", 1000);
						--record menu 1 listen to the recording, 2 save the recording, 3 re-record
							record_menu();
					elseif (dtmf_digits == "2") then
						--save the message
							dtmf_digits = '';
							macro(session, "message_saved", 1, 100, '');
							macro(session, "goodbye", 1, 100, '');
						--hangup the call
							session:hangup();
					elseif (dtmf_digits == "3") then
						--rerecord the message
							timeouts = 0;
							record_message();
					elseif (dtmf_digits == "*") then
						--hangup
							if (session:ready()) then
								dtmf_digits = '';
								macro(session, "goodbye", 1, 100, '');
								session:hangup();
							end
					else
						if (session:ready()) then
							timeouts = timeouts + 1;
							if (timeouts < max_timeouts) then
								record_menu();
							else
								macro(session, "goodbye", 1, 1000, '');
								session:hangup();
							end
						end
					end
				end
		end
	end

--define a function to send email
	function send_email(id, uuid)
		--get voicemail message details
			sql = [[SELECT * FROM v_voicemails
				WHERE domain_uuid = ']] .. domain_uuid ..[['
				AND voicemail_id = ']] .. id ..[[']]
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				--voicemail_uuid = string.lower(row["voicemail_uuid"]);
				--voicemail_password = row["voicemail_password"];
				--greeting_id = row["greeting_id"];
				voicemail_mail_to = row["voicemail_mail_to"];
				voicemail_attach_file = row["voicemail_attach_file"];
				voicemail_local_after_email = row["voicemail_local_after_email"];
			end);

		--get voicemail message details
			sql = [[SELECT * FROM v_voicemail_messages
				WHERE domain_uuid = ']] .. domain_uuid ..[['
				AND voicemail_message_uuid = ']] .. uuid ..[[']]
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				--get the values from the database
					--uuid = row["voicemail_message_uuid"];
					created_epoch = row["created_epoch"];
					caller_id_name = row["caller_id_name"];
					caller_id_number = row["caller_id_number"];
					message_length = row["message_length"];
					--message_status = row["message_status"];
					--message_priority = row["message_priority"];
			end);

		--calculate the message length
			message_length_formatted = format_seconds(message_length);
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] message length: " .. message_length .. "\n");
			end

		--send the email
			subject = [[Voicemail from ]]..caller_id_name..[[ <]]..caller_id_number..[[> ]]..message_length_formatted;
			local message = {}
			table.insert(message, [[<font face="arial">]]);
			table.insert(message, [[<b>Message From "]]..caller_id_name..[[" <A HREF="tel:]]..caller_id_number..[[">]]..caller_id_number..[[</A></b><br>]]);
			table.insert(message, [[<hr noshade="noshade" size="1">]]);
			table.insert(message, [[Created: ]]..os.date("%A, %d %b %Y %I:%M %p", created_epoch)..[[<br>]]);
			table.insert(message, [[Duration: ]]..message_length_formatted..[[<br>]]);
			table.insert(message, [[Account: ]]..id..[[@]]..domain_name..[[<br>]]);
			table.insert(message, [[</font>]]);
			body = table.concat(message, "");
			body = body:gsub("'", "&#39;");
			body = body:gsub([["]], "&#34;");
			if (voicemail_attach_file == "true") then
				file = voicemail_dir.."/"..id.."/msg_"..uuid.."."..vm_message_ext;
				cmd = "luarun email.lua "..voicemail_mail_to.." "..voicemail_mail_to.." '"..subject.."' '"..body.."' '"..file.."'";
			else
				cmd = "luarun email.lua "..voicemail_mail_to.." "..voicemail_mail_to.." '"..subject.."' '"..body.."'";
			end
			api = freeswitch.API();
			if (debug["info"]) then
				freeswitch.consoleLog("notice", "[voicemail] cmd: " .. cmd .. "\n");
			end
			result = api:executeString(cmd);

		--local after email is false so delete the recording file
			if (voicemail_local_after_email == "false") then
				os.remove(voicemail_dir.."/"..id.."/msg_"..uuid.."."..vm_message_ext);
			end

		--emailed
			if (session:ready()) then
				dtmf_digits = '';
				macro(session, "emailed", 1, 1000, '');
			end
	end

--define a function to forward a message to an extension
	function forward_to_extension(uuid)

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--get voicemail message details
			if (session:ready()) then
				sql = [[SELECT * FROM v_voicemail_messages
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_message_uuid = ']] .. uuid ..[[']]
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				status = dbh:query(sql, function(row)
					--get the values from the database
						created_epoch = row["created_epoch"];
						caller_id_name = row["caller_id_name"];
						caller_id_number = row["caller_id_number"];
						message_length = row["message_length"];
						message_status = row["message_status"];
						message_priority = row["message_priority"];
				end);
			end

		--request the forward_voicemail_id
			if (session:ready()) then
				dtmf_digits = '';
				forward_voicemail_id = macro(session, "forward_enter_extension", 20, 5000, '');
				if (session:ready()) then
					if (string.len(forward_voicemail_id) == 0) then
						dtmf_digits = '';
						forward_voicemail_id = macro(session, "forward_enter_extension", 20, 5000, '');
					end
				end
			end
			if (session:ready()) then
				if (string.len(forward_voicemail_id) == 0) then
					dtmf_digits = '';
					forward_voicemail_id = macro(session, "forward_enter_extension", 20, 5000, '');
				end
			end

		--get the voicemail settings using the voicemail_uuid
			sql = [[SELECT * FROM v_voicemails
				WHERE domain_uuid = ']] .. domain_uuid ..[['
				AND voicemail_id = ']] .. forward_voicemail_id ..[['
				AND voicemail_enabled = 'true' ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				forward_voicemail_uuid = string.lower(row["voicemail_uuid"]);
				forward_voicemail_mail_to = row["voicemail_mail_to"];
				forward_voicemail_attach_file = row["voicemail_attach_file"];
				forward_voicemail_local_after_email = row["voicemail_local_after_email"];
			end);

		--set default values
			if (forward_voicemail_attach_file == nil) then
				forward_voicemail_attach_file = "true";
			end
			if (forward_voicemail_local_after_email == nil) then
				forward_voicemail_local_after_email = "true";
			end

		--save the message to the voicemail messages
			local sql = {}
			table.insert(sql, "INSERT INTO v_voicemail_messages ");
			table.insert(sql, "(");
			table.insert(sql, "voicemail_message_uuid, ");
			table.insert(sql, "domain_uuid, ");
			table.insert(sql, "voicemail_uuid, ");
			table.insert(sql, "created_epoch, ");
			table.insert(sql, "caller_id_name, ");
			table.insert(sql, "caller_id_number, ");
			table.insert(sql, "message_length ");
			--table.insert(sql, "message_status, ");
			--table.insert(sql, "message_priority, ");
			table.insert(sql, ") ");
			table.insert(sql, "VALUES ");
			table.insert(sql, "( ");
			table.insert(sql, "'".. uuid .."', ");
			table.insert(sql, "'".. domain_uuid .."', ");
			table.insert(sql, "'".. forward_voicemail_uuid .."', ");
			table.insert(sql, "'".. created_epoch .."', ");
			table.insert(sql, "'".. caller_id_name .."', ");
			table.insert(sql, "'".. caller_id_number .."', ");
			table.insert(sql, "'".. message_length .."' ");
			--table.insert(sql, "'".. message_status .."', ");
			--table.insert(sql, "'".. message_priority .."' ");
			table.insert(sql, ") ");
			if (forward_voicemail_local_after_email == "true") then
				sql = table.concat(sql, "\n");
			end
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			dbh:query(sql);

		--set the message waiting event
			local event = freeswitch.Event("message_waiting");
			event:addHeader("MWI-Messages-Waiting", "yes");
			event:addHeader("MWI-Message-Account", "sip:"..forward_voicemail_id.."@"..domain_name);
			event:fire();

		--if local after email is true then copy the recording file
			os.execute("mkdir -p " .. voicemail_dir.."/"..forward_voicemail_id);
			os.execute("cp "..voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext.." "..voicemail_dir.."/"..forward_voicemail_id.."/msg_"..uuid.."."..vm_message_ext);

		--send the email with the voicemail recording attached
			if (string.len(forward_voicemail_mail_to) > 2) then
				send_email(forward_voicemail_id, uuid);
			end
	end

--leave a voicemail
	if (voicemail_action == "save") then

		--voicemail prompt
			if (skip_greeting == "true") then
				--skip the greeting
			else
				if (session:ready()) then
					if (string.len(greeting_id) > 0) then
						--play the greeting
						session:streamFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
					else
						--if there is no greeting then play digits of the voicemail_id
						dtmf_digits = '';
						result = macro(session, "person_not_available_record_message", 1, 100);
					end
				end
			end

		--save the recording
			timeouts = 0;
			record_message();

		--save the message to the voicemail messages
			if (message_length > 2) then
				local sql = {}
				table.insert(sql, "INSERT INTO v_voicemail_messages ");
				table.insert(sql, "(");
				table.insert(sql, "voicemail_message_uuid, ");
				table.insert(sql, "domain_uuid, ");
				table.insert(sql, "voicemail_uuid, ");
				table.insert(sql, "created_epoch, ");
				table.insert(sql, "caller_id_name, ");
				table.insert(sql, "caller_id_number, ");
				table.insert(sql, "message_length ");
				--table.insert(sql, "message_status, ");
				--table.insert(sql, "message_priority, ");
				table.insert(sql, ") ");
				table.insert(sql, "VALUES ");
				table.insert(sql, "( ");
				table.insert(sql, "'".. uuid .."', ");
				table.insert(sql, "'".. domain_uuid .."', ");
				table.insert(sql, "'".. voicemail_uuid .."', ");
				table.insert(sql, "'".. start_epoch .."', ");
				table.insert(sql, "'".. caller_id_name .."', ");
				table.insert(sql, "'".. caller_id_number .."', ");
				table.insert(sql, "'".. message_length .."' ");
				--table.insert(sql, "'".. message_status .."', ");
				--table.insert(sql, "'".. message_priority .."' ");
				table.insert(sql, ") ");
				if (voicemail_local_after_email == "true") then
					sql = table.concat(sql, "\n");
				end
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				dbh:query(sql);
			end

		--set the message waiting event
			if (message_length > 2) then
				local event = freeswitch.Event("message_waiting");
				event:addHeader("MWI-Messages-Waiting", "yes");
				event:addHeader("MWI-Message-Account", "sip:"..voicemail_id.."@"..domain_name);
				event:fire();
			end

		--send the email with the voicemail recording attached
			if (message_length > 2) then
				if (string.len(voicemail_mail_to) > 3) then
					send_email(voicemail_id, uuid);
				end
			end

	end

function main_menu ()
	if (voicemail_uuid) then
		--clear the value
			dtmf_digits = '';
		--flush dtmf digits from the input buffer
			session:flushDigits();
		--new voicemail count
			if (session:ready()) then
				sql = [[SELECT count(*) as new_messages FROM v_voicemail_messages
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_uuid = ']] .. voicemail_uuid ..[['
					AND (message_status is null or message_status = '') ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
					end
				status = dbh:query(sql, function(row)
					new_messages = row["new_messages"];
				end);
				dtmf_digits = macro(session, "new_messages", 1, 100, new_messages);
			end
		--saved voicemail count
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					sql = [[SELECT count(*) as saved_messages FROM v_voicemail_messages
						WHERE domain_uuid = ']] .. domain_uuid ..[['
						AND voicemail_uuid = ']] .. voicemail_uuid ..[['
						AND message_status = 'saved' ]];
						if (debug["sql"]) then
							freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
						end
					status = dbh:query(sql, function(row)
						saved_messages = row["saved_messages"];
					end);
					dtmf_digits = macro(session, "saved_messages", 1, 100, saved_messages);
				end
			end
		--to listen to new message
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "listen_to_new_messages", 1, 100, '');
				end
			end
		--to listen to saved message
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "listen_to_saved_messages", 1, 100, '');
				end
			end
		--for advanced options
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "advanced", 1, 100, '');
				end
			end
		--to exit press #
			if (session:ready()) then
				if (string.len(dtmf_digits) == 0) then
					dtmf_digits = macro(session, "to_exit_press", 1, 3000, '');
				end
			end
		--process the dtmf
			if (session:ready()) then
				if (dtmf_digits == "1") then
					menu_messages("new");
				elseif (dtmf_digits == "2") then
					menu_messages("saved");
				elseif (dtmf_digits == "5") then
					timeouts = 0;
					advanced();
				elseif (dtmf_digits == "0") then
					session:transfer("0", "XML", context);
				elseif (dtmf_digits == "*") then
					dtmf_digits = '';
					macro(session, "goodbye", 1, 100, '');
					session:hangup();
				else
					if (session:ready()) then
						timeouts = timeouts + 1;
						if (timeouts < max_timeouts) then
							main_menu();
						else
							macro(session, "goodbye", 1, 1000, '');
							session:hangup();
						end
					end
				end
			end
	end
end

function listen_to_recording (message_number, uuid, created_epoch, caller_id_name, caller_id_number)

	--set default values
		dtmf_digits = '';
		max_digits = 1;
	--flush dtmf digits from the input buffer
		session:flushDigits();
	--set the display
		if (session:ready()) then
			api = freeswitch.API();
			reply = api:executeString("uuid_display "..session:get_uuid().." "..caller_id_number);
		end
	--say the message number
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "message_number", 1, 100, '');
			end
		end
	--say the number
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				session:say(message_number, default_language, "NUMBER", "pronounced");
			end
		end
	--say the message date
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				session:say(created_epoch, default_language, "CURRENT_DATE_TIME", "pronounced");
			end
		end
	--play the message
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				session:streamFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
				session:streamFile("silence_stream://1000");
			end
		end
	--to listen to the recording press 1
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "listen_to_recording", 1, 100, '');
			end
		end
	--to save the recording press 2
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "save_recording", 1, 100, '');
			end
		end
	--to return the call now press 5
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "return_call", 1, 100, '');
			end
		end
	--to delete the recording press 7
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "delete_recording", 1, 100, '');
			end
		end
	--to forward this message press 8
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "to_forward_message", 1, 100, '');
			end
		end
	--to forward this recording to your email press 9
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "forward_to_email", 1, 3000, '');
			end
		end
	--wait for more digits
		--if (session:ready()) then
		--	if (string.len(dtmf_digits) == 0) then
		--		dtmf_digits = session:getDigits(max_digits, "#", 1, 3000);
		--	end
		--end
	--process the dtmf
		if (session:ready()) then
			if (dtmf_digits == "1") then
				listen_to_recording(message_number, uuid, created_epoch, caller_id_name, caller_id_number);
			elseif (dtmf_digits == "2") then
				message_saved(voicemail_id, uuid);
				macro(session, "message_saved", 1, 100, '');
			elseif (dtmf_digits == "5") then
				message_saved(voicemail_id, uuid);
				return_call(caller_id_number);
			elseif (dtmf_digits == "7") then
				delete_recording(uuid);
				message_waiting(voicemail_id);
			elseif (dtmf_digits == "8") then
				forward_to_extension(uuid);
				dtmf_digits = '';
				macro(session, "message_saved", 1, 100, '');
			elseif (dtmf_digits == "9") then
				send_email(voicemail_id, uuid);
			elseif (dtmf_digits == "*") then
				timeouts = 0;
				main_menu();
			elseif (dtmf_digits == "0") then
				message_saved(voicemail_id, uuid);
				session:transfer("0", "XML", context);
			else
				message_saved(voicemail_id, uuid);
				macro(session, "message_saved", 1, 100, '');
			end
		end
end

--voicemail count if zero new messages set the mwi to no
	function message_waiting(voicemail_id)
		if (voicemail_id ~= nil) then
			sql = [[SELECT count(*) as new_messages FROM v_voicemail_messages as m, v_voicemails as v
				WHERE v.domain_uuid = ']] .. domain_uuid ..[['
				AND v.voicemail_uuid = m.voicemail_uuid
				AND v.voicemail_id = ']] .. voicemail_id ..[['
				AND (m.message_status is null or m.message_status = '') ]];
			if (debug["sql"]) then
				if (session:ready()) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
			end
			status = dbh:query(sql, function(row)
				if (row["new_messages"] == "0") then
					--send the message waiting event
						local event = freeswitch.Event("message_waiting");
						event:addHeader("MWI-Messages-Waiting", "no");
						event:addHeader("MWI-Message-Account", "sip:"..voicemail_id.."@"..domain_name);
						event:fire();
					--log to console
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail][message-waiting] id: "..voicemail_id.."@"..domain_name.." status: no\n");
						end
				else
					--set the message waiting event
						local event = freeswitch.Event("message_waiting");
						event:addHeader("MWI-Messages-Waiting", "yes");
						event:addHeader("MWI-Message-Account", "sip:"..voicemail_id.."@"..domain_name);
						event:fire();
					--log to console
						if (debug["info"]) then
							freeswitch.consoleLog("notice", "[voicemail][message-waiting] id: "..voicemail_id.."@"..domain_name.." status: yes\n");
						end
				end
			end);
		end
	end

--delete the recording
	function delete_recording(uuid)
		if (session:ready()) then
			--flush dtmf digits from the input buffer
				session:flushDigits();
			--delete the file
				os.remove(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
			--delete from the database
				sql = [[DELETE FROM v_voicemail_messages
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_message_uuid = ']] .. uuid ..[[']];
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				dbh:query(sql);
			--log to console
				if (debug["info"]) then
					freeswitch.consoleLog("notice", "[voicemail][deleted] message: " .. uuid .. "\n");
				end
			--message deleted
				dtmf_digits = '';
				macro(session, "message_deleted", 1, 100, '');
		end
	end

--save the message
	function message_saved(voicemail_id, uuid)
		if (session:ready()) then
			--clear the dtmf
				dtmf_digits = '';
			--flush dtmf digits from the input buffer
				session:flushDigits();
			--delete from the database
				sql = [[UPDATE v_voicemail_messages SET message_status = 'saved'
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_message_uuid = ']] .. uuid ..[[']];
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				dbh:query(sql);
			--log to console
				if (debug["info"]) then
					freeswitch.consoleLog("notice", "[voicemail][saved] id: " .. voicemail_id .. " message: "..uuid.."\n");
				end
			--check the message waiting status
				message_waiting(voicemail_id);
		end
	end

--return the call
	function return_call(destination)
		if (session:ready()) then
			--clear the dtmf
				dtmf_digits = '';
			--flush dtmf digits from the input buffer
				session:flushDigits();
			--transfer the call
				session:transfer(destination, "XML", context);
		end
	end

function menu_messages (message_status)

	--set default values
		max_timeout = 2000;
		min_digits = 1;
		max_digits = 1;
		tries = 1;
		timeout = 2000;
	--clear the dtmf
		dtmf_digits = '';
	--flush dtmf digits from the input buffer
		session:flushDigits();
	--set the message number
		message_number = 0;
	--message_status new,saved
		if (session:ready()) then
			if (voicemail_id ~= nil) then
				sql = [[SELECT * FROM v_voicemail_messages
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_uuid = ']] .. voicemail_uuid ..[[']]
				if (message_status == "new") then
					sql = sql .. [[AND (message_status is null or message_status = '') ]];
				elseif (message_status == "saved") then
					sql = sql .. [[AND message_status = 'saved' ]];
				end
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				status = dbh:query(sql, function(row)
					--get the values from the database
						--row["voicemail_message_uuid"];
						--row["created_epoch"];
						--row["caller_id_name"];
						--row["caller_id_number"];
						--row["message_length"];
						--row["message_status"];
						--row["message_priority"];
					--increment the message count
						message_number = message_number + 1;
					--listen to the message
						if (session:ready()) then
							if (debug["info"]) then
								freeswitch.consoleLog("notice", message_number.." "..string.lower(row["voicemail_message_uuid"]).." "..row["created_epoch"]);
							end
							listen_to_recording(message_number, string.lower(row["voicemail_message_uuid"]), row["created_epoch"], row["caller_id_name"], row["caller_id_number"]);
						end
				end);
			end
		end

	--voicemail count if zero new messages set the mwi to no
		if (session:ready()) then
			if (voicemail_id ~= nil) then
				sql = [[SELECT count(*) as new_messages FROM v_voicemail_messages
					WHERE domain_uuid = ']] .. domain_uuid ..[['
					AND voicemail_uuid = ']] .. voicemail_uuid ..[['
					AND (message_status is null or message_status = '') ]];
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
				end
				status = dbh:query(sql, function(row)
					if (row["new_messages"] == "0") then
						--send the message waiting event
						local event = freeswitch.Event("message_waiting");
						event:addHeader("MWI-Messages-Waiting", "no");
						event:addHeader("MWI-Message-Account", "sip:"..voicemail_id.."@"..domain_name);
						event:fire();
					end
				end);
			end
		end

	--set the display
		if (session:ready()) then
			api = freeswitch.API();
			reply = api:executeString("uuid_display "..session:get_uuid().." "..destination_number);
		end

	--send back to the main menu
		if (session:ready()) then
			timeouts = 0;
			main_menu();
		end
end

function advanced ()
	--clear the dtmf
		dtmf_digits = '';
	--flush dtmf digits from the input buffer
		session:flushDigits();
	--To record a greeting press 1
		if (session:ready()) then
			dtmf_digits = macro(session, "to_record_greeting", 1, 100, '');
		end
	--To choose greeting press 2
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "choose_greeting", 1, 100, '');
			end
		end
	--To record your name 3
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "to_record_name", 1, 100, '');
			end
		end
	--To change your password press 6
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "change_password", 1, 100, '');
			end
		end
	--For the main menu press 0
		if (session:ready()) then
			if (string.len(dtmf_digits) == 0) then
				dtmf_digits = macro(session, "main_menu", 1, 5000, '');
			end
		end
	--process the dtmf
		if (session:ready()) then
			if (dtmf_digits == "1") then
				--To record a greeting press 1
				timeouts = 0;
				record_greeting();
			elseif (dtmf_digits == "2") then
				--To choose greeting press 2
				timeouts = 0;
				choose_greeting();
			elseif (dtmf_digits == "3") then
				--To record your name 3
				record_name();
			elseif (dtmf_digits == "6") then
				--To change your password press 6
				change_password(voicemail_id);
			elseif (dtmf_digits == "0") then
				--For the main menu press 0
				timeouts = 0;
				main_menu();
			else
				timeouts = timeouts + 1;
				if (timeouts <= max_timeouts) then
					advanced();
				else
					macro(session, "goodbye", 1, 1000, '');
					session:hangup();
				end
			end
		end
end

function record_greeting()

	--flush dtmf digits from the input buffer
		session:flushDigits();

	--Choose a greeting between 1 and 9
		if (session:ready()) then
			dtmf_digits = '';
			greeting_id = macro(session, "choose_greeting_choose", 1, 5000, '');
			freeswitch.consoleLog("notice", "[voicemail] greeting_id: " .. greeting_id .. "\n");
		end

	--validate the greeting_id
		if (greeting_id == "1" 
			or greeting_id == "2" 
			or greeting_id == "3" 
			or greeting_id == "4" 
			or greeting_id == "5" 
			or greeting_id == "6" 
			or greeting_id == "7" 
			or greeting_id == "8" 
			or greeting_id == "9") then
			--record your greeting at the tone press any key or stop talking to end the recording
				if (session:ready()) then
					dtmf_digits = '';
					macro(session, "record_greeting", 1, 100, '');
				end

			--record the greeting
				if (session:ready()) then
					max_len_seconds = 30;
					silence_threshold = 30;
					silence_seconds = 5;
					os.execute("mkdir -p " .. voicemail_dir.."/"..voicemail_id);
					-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
					result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav", max_len_seconds, silence_threshold, silence_seconds);
					--session:execute("record", voicemail_dir.."/"..uuid.." 180 200");
				end

			--play the greeting
				if (session:ready()) then
					if (file_exists(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav")) then
						session:streamFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
					end
				end

			--advanced menu
				if (session:ready()) then
					timeouts = 0;
					advanced();
				end
		else
			--invalid greeting_id
				if (session:ready()) then
					dtmf_digits = '';
					macro(session, "choose_greeting_fail", 1, 100, '');
				end

			--send back to choose the greeting
				if (session:ready()) then
					timeouts = timeouts + 1;
					if (timeouts < max_timeouts) then
						record_greeting();
					else
						timeouts = 0;
						advanced();
					end
				end
		end
end

function choose_greeting()

	--flush dtmf digits from the input buffer
		session:flushDigits();

	--select the greeting
		if (session:ready()) then
			dtmf_digits = '';
			greeting_id = macro(session, "choose_greeting_choose", 1, 5000, '');
		end

	--check to see if the greeting file exists
		if (greeting_id ~= "0") then
			if (not file_exists(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav")) then
				--invalid greeting_id file does not exist
				greeting_id = "invalid";
			end
		end

	--validate the greeting_id
		if (greeting_id == "0" 
			or greeting_id == "1" 
			or greeting_id == "2" 
			or greeting_id == "3" 
			or greeting_id == "4" 
			or greeting_id == "5" 
			or greeting_id == "6" 
			or greeting_id == "7" 
			or greeting_id == "8" 
			or greeting_id == "9") then

			--valid greeting_id update the database
				if (session:ready()) then
					if (greeting_id == "0") then 
						sql = [[UPDATE v_voicemails SET greeting_id = null ]];
					else
						sql = [[UPDATE v_voicemails SET greeting_id = ']]..greeting_id..[[' ]];
					end
					sql = sql ..[[WHERE domain_uuid = ']] .. domain_uuid ..[[' ]]
					sql = sql ..[[AND voicemail_uuid = ']] .. voicemail_uuid ..[[' ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
					end
					dbh:query(sql);
				end

			--play the greeting
				if (session:ready()) then
					if (file_exists(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav")) then
						session:streamFile(voicemail_dir.."/"..voicemail_id.."/greeting_"..greeting_id..".wav");
					end
				end

			--greeting selected
				if (session:ready()) then
					dtmf_digits = '';
					macro(session, "greeting_selected", 1, 100, greeting_id);
				end

			--advanced menu
				if (session:ready()) then
					timeouts = 0;
					advanced();
				end
		else
			--invalid greeting_id
				if (session:ready()) then
					dtmf_digits = '';
					greeting_id = macro(session, "choose_greeting_fail", 1, 100, '');
				end

			--send back to choose the greeting
				if (session:ready()) then
					timeouts = timeouts + 1;
					if (timeouts < max_timeouts) then
						choose_greeting();
					else
						timeouts = 0;
						advanced();
					end
				end
		end

end

function record_name()
	if (session:ready()) then

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--play the name record
			dtmf_digits = '';
			macro(session, "record_name", 1, 100, '');

		--save the recording
			-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
			max_len_seconds = 30;
			silence_threshold = 30;
			silence_seconds = 5;
			os.execute("mkdir -p " .. voicemail_dir.."/"..voicemail_id);
			result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/recorded_name.wav", max_len_seconds, silence_threshold, silence_seconds);
			--session:execute("record", voicemail_dir.."/"..uuid.." 180 200");

		--play the greeting
			session:streamFile(voicemail_dir.."/"..voicemail_id.."/recorded_name.wav");

		--message saved
			dtmf_digits = '';
			macro(session, "message_saved", 1, 100, '');

		--advanced menu
			timeouts = 0;
			advanced();
	end
end

--check voicemail
	if (voicemail_action == "check") then
		if (session:ready()) then
			--check the voicemail password
			check_password(voicemail_id, password_tries);
			timeouts = 0;
			main_menu();
		end
	end

--notes
	--record the video
		--records audio only
			--result = session:execute("set", "enable_file_write_buffering=false");
			--os.execute("mkdir -p " .. voicemail_dir.."/"..voicemail_id);
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
		--os.execute("mkdir -p " .. voicemail_dir.."/"..voicemail_id);
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
