--	voicemail.lua
--	Part of FusionPBX
--	Copyright (C) 2012 Mark J Crane <markjcrane@fusionpbx.com>
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
	digit_timeout = 5000;

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
	sounds_dir = session:getVariable("sounds_dir");
	domain_name = session:getVariable("domain_name");
	uuid = session:getVariable("uuid");
	voicemail_id = session:getVariable("voicemail_id");
	base_dir = session:getVariable("base_dir");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");

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

--set the voicemail_dir
	voicemail_dir = base_dir.."/storage/voicemail/default/"..domain_name;
	freeswitch.consoleLog("notice", "[voicemail] voicemail_dir: " .. voicemail_dir .. "\n");

--check if a file exists
	function file_exists(name)
		local f=io.open(name,"r")
		if f~=nil then io.close(f) return true else return false end
	end

--define on_dtmf
	function on_dtmf(s, type, obj, arg)
		if (arg) then
			freeswitch.console_log("info", "\ntype: " .. type .. "\n" .. "arg: " .. arg .. "\n");
		else
			freeswitch.console_log("info", "\ntype: " .. type .. "\n");
		end

		if (type == "dtmf") then
			freeswitch.console_log("info", "\ndigit: [" .. obj['digit'] .. "]\nduration: [" .. obj['duration'] .. "]\n"); 
		else
			freeswitch.console_log("info", obj:serialize("xml"));
		end
	end
	--session:setInputCallback("on_dtmf", "");

--define the macro function
	function macro(session, name, macro_timeout)
		--Please enter your id followed by
			if (name == "voicemail_id") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-enter_id.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/pound.wav"});
			end
		 --Please enter your id followed by
			if (name == "voicemail_password") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-enter_pass.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/pound.wav"});
			end
		--the person at extension 101 is not available record your message at the tone press any key or stop talking to end the recording
			if (name == "record_message") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-person.wav"});
				--pronounce the voicemail_id
				table.insert(actions, {app="playAndGetDigits",data="digits/1.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/0.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/1.wav"});
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-not_available.wav"});
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-record_message.wav"});
			end
		--You have zero new messages
			if (name == "new_messages") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-you_have.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/0.wav"});
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-messages.wav"});
			end
		--You have zero saved messages
			if (name == "saved_messages") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-you_have.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/0.wav"});
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-messages.wav"});
			end
		--To listen to new messages press 1
			if (name == "listen_to_new_messages") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-listen_new.wav"});
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/1.wav"});
			end
		--To listen to saved messages press 2
			if (name == "listen_to_saved_messages") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-listen_saved.wav"});
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/2.wav"});
			end

		--For advanced options press 5
			if (name == "advanced") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-advanced.wav"});
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/5.wav"});
			end
		--Advanced Options Menu
			--To record a greeting press 1
				if (name == "record_greeting") then
					actions = {}
					table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
					table.insert(actions, {app="playAndGetDigits",data="digits/1.wav"});
				end
				--Choose a greeting between 1 and 9
					if (name == "choose_greeting_choose") then
						actions = {}
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-choose_greeting_choose.wav"});
					end
				--Greeting invalid value
					if (name == "choose_greeting_fail") then
						actions = {}
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-choose_greeting_fail.wav"});
					end
				--recording your greeting at the tone press any key or stop talking to end the recording
					if (name == "record_greeting") then
						actions = {}
						table.insert(actions, {app="playAndGetDigits",data="voicemail/record_greeting.wav"});
					end
				--To choose greeting press 2
					if (name == "choose_greeting") then
						actions = {}
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-choose_greeting.wav"});
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="playAndGetDigits",data="digits/2.wav"});
					end
				--Choose a greeting between 1 and 9
					--if (name == "choose_greeting_choose") then
					--	actions = {}
					--	table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-choose_greeting_choose"});
					--end
				--Invalid greeting number
					--if (name == "choose_greeting_fail") then
					--	actions = {}
					--	table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-choose_greeting_fail"});
					--end
				--To record your name 3
					if (name == "record_name") then
						actions = {}
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-record_name2.wav"});
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="playAndGetDigits",data="digits/3.wav"});
					end
				--To change your password press 6
					if (name == "change_password") then
						actions = {}
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-change_password.wav"});
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="playAndGetDigits",data="digits/6.wav"});
					end
				--For the main menu press 0
					if (name == "main_menu") then
						actions = {}
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-main_menu.wav"});
						table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
						table.insert(actions, {app="playAndGetDigits",data="digits/0.wav"});
					end
		--To exit press #
			if (name == "to_exit") then
				actions = {}
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-to_exit.wav"});
				table.insert(actions, {app="playAndGetDigits",data="voicemail/vm-press.wav"});
				table.insert(actions, {app="playAndGetDigits",data="digits/pound.wav"});
			end
		--if actions table exists then process it
			if (actions) then
				--set default values
					dtmf_digits = '';
					tries = 1;
					timeout = 200;
					max_digits = 5;
				--loop through the action and data
					for key, row in pairs(actions) do
						freeswitch.consoleLog("notice", "[directory] app: " .. row.app .. " data: " .. row.data .. "\n");
						if (string.len(dtmf_digits) == 0) then
							if (row.app == "playback") then
								session:execute(row.app, sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data);
							elseif (row.app == "playAndGetDigits") then
								--playAndGetDigits <min> <max> <tries> <timeout> <terminators> <file> <invalid_file> <var_name> <regexp> <digit_timeout>
								if (not file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data)) then
									dtmf_digits = session:playAndGetDigits(min_digits, max_digits, tries, timeout, "#", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..row.data, "", "\\d+", macro_timeout);
								else
									dtmf_digits = session:playAndGetDigits(min_digits, max_digits, tries, timeout, "#", row.data, "", "\\d+", macro_timeout);
								end
							else
								session:execute(row.app, row.data);
							end
						end
					end
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = session:getDigits(5, "#", macro_timeout);
					end
				--return dtmf the digits
					return dtmf_digits;
			else
				--no dtmf digits to return
					return '';
			end
	end

--get the voicemail settings from the database
	if (voicemail_id ~= nil) then
		sql = [[SELECT * FROM v_voicemails
			WHERE domain_uuid = ']] .. domain_uuid ..[['
			AND voicemail_id = ']] .. voicemail_id ..[['
			AND voicemail_enabled = 'true' ]];
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
		end
		status = dbh:query(sql, function(row)
			voicemail_uuid = row["voicemail_uuid"];
			voicemail_password = row["voicemail_password"];
			greeting_id = row["greeting_id"];
		end);
	end

--set the action
	action = "voicemail.save";

--leave a voicemail
	if (action == "voicemail.save") then

		--voicemail prompt
--			if (greeting_id) then
				--play the greeting
--			else
				--if there is no greeting then play digits of the voicemail_id
				result = macro(session, "record_message", 200);
--			end

		--set the epoch
			start_epoch = os.time();
			freeswitch.consoleLog("notice", "[voicemail] start epoch: " .. start_epoch .. "\n");

		--save the recording
			-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
			max_len_seconds = 30;
			silence_threshold = 30;
			silence_seconds = 5;
			result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/"..uuid..".wav", max_len_seconds, silence_threshold, silence_seconds);
			--session:execute("record", voicemail_dir.."/"..uuid.." 180 200");

		--set the epoch
			stop_epoch = os.time();
			freeswitch.consoleLog("notice", "[voicemail] start epoch: " .. stop_epoch .. "\n");

		--calculate the message length
			message_length = stop_epoch - start_epoch;
			freeswitch.consoleLog("notice", "[voicemail] message length: " .. message_length .. "\n");

		--send the email with the voicemail recording attached
			--[[freeswitch.email("",
				"",
				"To: "..email_address.."\nFrom: "..from_address.."\nSubject: Fax to: "..number_dialed.." SENT",
				email_message_success ,
				fax_file
			);]]

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
			table.insert(sql, "'".. voicemail_uuid .."', ");
			table.insert(sql, "'".. start_epoch .."', ");
			table.insert(sql, "'".. caller_id_name .."', ");
			table.insert(sql, "'".. caller_id_number .."', ");
			table.insert(sql, "'".. message_length .."' ");
			--table.insert(sql, "'".. message_status .."', ");
			--table.insert(sql, "'".. message_priority .."' ");
			table.insert(sql, ") ");
			sql = table.concat(sql, "\n");
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "\n");
			end
			dbh:query(sql);
	end

--check voicemail
	if (action == "voicemail.check") then
		--please enter your id followed by pound
			if (not voicemail_id) then
				voicemail_id = macro(session, "voicemail_id", 5000);
				freeswitch.consoleLog("notice", "[voicemail] voicemail id: " .. voicemail_id .. "\n");
			end
		--please enter your password followed by pound
			user_voicemail_password = macro(session, "voicemail_password", 5000);
			freeswitch.consoleLog("notice", "[voicemail] voicemail id: " .. voicemail_password .. "\n");
		--compare the password from the database with the password provided by the user
			if (voicemail_password ~= user_voicemail_password) then
				--voicemail menu
					--voicemail_menu();
			else
				--password was incorrect
			end
	end

--notes
	--record the video
		--records audio only
			--session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs);
			--result = session:execute("set", "enable_file_write_buffering=false");
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
		--session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs) 
		--session:sayPhrase(macro_name [,macro_data] [,language]);
		--session:sayPhrase("voicemail_menu", "1:2:3:#", "en");
		--session:streamFile("directory/dir-to_select_entry.wav"); --works with setInputCallback
		--session:say("12345", "en", "number", "pronounced");
		--speak
			--session:set_tts_parms("flite", "kal");
			--session:speak("Please say the name of the person you're trying to contact");

	--callback (execute and executeString does not work with DTMF)
		--session:execute(api_string);
		--session:executeString("playback "..mySound);

	--uuid_video_refresh
		--uuid_video_refresh,<uuid>,Send video refresh.,mod_commands
		--may be used to clear video buffer before using record_fsv
