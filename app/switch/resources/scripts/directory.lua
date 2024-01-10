--	directory.lua
--	Part of FusionPBX
--	Copyright (C) 2012-2017 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	notice, this list of conditions and the following disclaimer in the
--	documentation and/or other materials provided with the distribution.
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

--set the defaults
	digit_max_length = 3;
	timeout_pin = 5000;
	max_tries = 3;
	digit_timeout = 5000;
	search_limit = 3;
	search_count = 0;

--include config.lua
	require "resources.functions.config";

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--include functions
	require "resources.functions.format_ringback"

--settings
	require "resources.functions.settings";
	settings = settings(domain_uuid);
	storage_type = "";
	storage_path = "";
	if (settings['voicemail'] ~= nil) then
		if (settings['voicemail']['storage_type'] ~= nil) then
			if (settings['voicemail']['storage_type']['text'] ~= nil) then
				storage_type = settings['voicemail']['storage_type']['text'];
			end
		end
		if (settings['voicemail']['speak_mod'] ~= nil) then
			if (settings['voicemail']['speak_mod']['text'] ~= nil) then
				speak_mod = settings['voicemail']['speak_mod']['text'];
			end
		end
		if (settings['voicemail']['speak_voice'] ~= nil) then
			if (settings['voicemail']['speak_voice']['text'] ~= nil) then
				speak_voice = settings['voicemail']['speak_voice']['text'];
			end
		end
		if (settings['voicemail']['storage_path'] ~= nil) then
			if (settings['voicemail']['storage_path']['text'] ~= nil) then
				storage_path = settings['voicemail']['storage_path']['text'];
				storage_path = storage_path:gsub("${domain_name}", domain_name);
				storage_path = storage_path:gsub("${voicemail_id}", voicemail_id);
				storage_path = storage_path:gsub("${voicemail_dir}", voicemail_dir);
			end
		end
	end

--prepare the api object
	api = freeswitch.API();

--get the session variables
	if ( session:ready() ) then
		--answer the session
			session:answer();

		--give time for the call to be ready
			session:streamFile("silence_stream://1000");

		--get the domain info
			domain_name = session:getVariable("domain_name");
			domain_uuid = session:getVariable("domain_uuid");

		--get the timeout destination
			timeout_destination = session:getVariable("timeout_destination");
			
		--get the previous menu location
			previous_menu = session:getVariable("rdnis");

		--set the sounds path for the language, dialect and voice
			default_language = session:getVariable("default_language");
			default_dialect = session:getVariable("default_dialect");
			default_voice = session:getVariable("default_voice");
			if (not default_language) then default_language = 'en'; end
			if (not default_dialect) then default_dialect = 'us'; end
			if (not default_voice) then default_voice = 'callie'; end

		--directory recording prompt
			directory_prompt = session:getVariable("directory_prompt");

		--set ringback
			directory_ringback = format_ringback(session:getVariable("ringback"));
			session:setVariable("ringback", directory_ringback);
			session:setVariable("transfer_ringback", directory_ringback);

		--set the sounds path for the language, dialect and voice
			session:setVariable("instant_ringback", "true");
			session:setVariable("ignore_early_media", "true");

		--define the sounds directory
			sounds_dir = session:getVariable("sounds_dir");
			sounds_dir = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice;
	end

--set the voicemail_dir
	voicemail_dir = settings['switch']['voicemail']['dir'].."/default/"..domain_name;
	if (debug["info"]) then
		freeswitch.consoleLog("notice", "[directory] voicemail_dir: " .. voicemail_dir .. "\n");
	end

--get the domain_uuid
	if (domain_uuid == nil) then
		if (domain_name ~= nil) then
			local sql = "SELECT domain_uuid FROM v_domains ";
			sql = sql .. "WHERE domain_name = :domain_name";
			local params = {domain_name = domain_name};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[directory] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params, function(rows)
				domain_uuid = string.lower(rows["domain_uuid"]);
			end);
		end
	end

--define the explode function
	require "resources.functions.explode"

--define a function to convert dialpad letters to numbers
	function dialpad_to_digit(letter)
		letter = string.lower(letter);
		if (letter == "a" or letter == "b" or letter == "c") then
			digit = "2";
		elseif (letter == "d" or letter == "e" or letter == "f") then
			digit = "3";
		elseif (letter == "g" or letter == "h" or letter == "i") then
			digit = "4";
		elseif (letter == "j" or letter == "k" or letter == "l") then
			digit = "5";
		elseif (letter == "m" or letter == "n" or letter == "o") then
			digit = "6";
		elseif (letter == "p" or letter == "q" or letter == "r" or letter == "s") then
			digit = "7";
		elseif (letter == "t" or letter == "u" or letter == "v") then
			digit = "8";
		elseif (letter == "w" or letter == "x" or letter == "y" or letter == "z") then
			digit = "9";
		else
			digit = "0";
		end
		return digit;
	end
	--print(dialpad_to_digit("m"));

--define table_count
	function table_count(T)
		local count = 0
		for _ in pairs(T) do count = count + 1 end
		return count
	end

--define the trim function
	require "resources.functions.trim"

--check if a file exists
	require "resources.functions.file_exists"

--define select_entry function
	function select_entry()
		dtmf_digits = "";
		digit_timeout = "500";
		max_digits = 1;
		max_tries = 1;
		dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/directory/dir-to_select_entry.wav", "", "\\d+|\\*");
		if (string.len(dtmf_digits) == 0 and dtmf_digits~="*") then
			dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/voicemail/vm-press.wav", "", "\\d+|\\*");
		end
		if (string.len(dtmf_digits) == 0 and dtmf_digits~="*") then
			dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/digits/1.wav", "", "\\d+|\\*");
		end
		if (string.len(dtmf_digits) == 0 and dtmf_digits~="*") then
			dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/directory/dir-for_next.wav", "", "\\d+|\\*");
		end
		if (string.len(dtmf_digits) == 0 and dtmf_digits~="*") then
			dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/voicemail/vm-press.wav", "", "\\d+|\\*");
		end
		if (string.len(dtmf_digits) == 0 and dtmf_digits~="*") then
			dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/digits/6.wav", "", "\\d+|\\*");
		end
		if (string.len(dtmf_digits) == 0 and dtmf_digits~="*") then
			dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/ivr/ivr-to_return_to_previous_menu.wav", "", "\\d+|\\*");
		end
		if (string.len(dtmf_digits) == 0 and dtmf_digits~="*") then
			dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/voicemail/vm-press.wav", "", "\\d+|\\*");
		end
		if (string.len(dtmf_digits) == 0 and dtmf_digits~="*") then
			digit_timeout = "5000";
			dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/digits/star.wav", "", "\\d+|\\*");
		end
		return dtmf_digits;
	end

--define prompt_for_name function
	function prompt_for_name()
		dtmf_digits = "";
		min_digits=0; max_digits=3; max_tries=3; digit_timeout = "5000";
		directory_prompt = directory_prompt or sounds_dir.."/directory/dir-enter_person_first_or_last.wav";
		session:setVariable("read_terminator_used", "");
		
		dtmf_digits = session:read(min_digits, max_digits, directory_prompt, digit_timeout, "#*");
		
		--allow exit to previous menu
			if (session:getVariable("read_terminator_used")) then
				terminator = session:getVariable("read_terminator_used");
				if (terminator == "*") then
					session:execute("transfer",previous_menu);
				end
			end

		return dtmf_digits;
	end

--define the directory_search function
	function directory_search()

		--get the digits for the name
			dtmf_digits = prompt_for_name();

		--show the dtmf digits
			freeswitch.consoleLog("notice", "[directory] first 3 letters of first or last name: " .. dtmf_digits .. "\n");

		--loop through the extensions to find matches
			search_dtmf_digits = dtmf_digits;
			found = false;
			for key,row in pairs(directory) do

				--if (row.first_name and row.last_name) then
				--	freeswitch.consoleLog("notice", "[directory] ext: " .. row.extension .. " context " .. row.context .. " name " .. row.first_name .. " "..row.first_name_digits.." ".. row.last_name .. " "..row.last_name_digits.." "..row.directory_exten_visible.."\n");
				--else
				--	freeswitch.consoleLog("notice", "[directory] ext: " .. row.extension .. " context " .. row.context .. "\n");
				--end

				if (search_dtmf_digits == row.last_name_digits) or (search_dtmf_digits == row.first_name_digits) then
					if (row.first_name) then
						--play the recorded name
							if (storage_type == "base64") then
								local dbh = Database.new('system', 'base64/read')

								local sql = [[SELECT * FROM v_voicemails
									WHERE domain_uuid = :domain_uuid
									AND voicemail_id = :voicemail_id]];
								local params = {domain_uuid = domain_uuid, voicemail_id = row.extension};
								if (debug["sql"]) then
									freeswitch.consoleLog("notice", "[directory] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
								end
								dbh:query(sql, params, function(field)
									--set the voicemail message path
										file_location = voicemail_dir.."/"..row.extension.."/recorded_name.wav";

									--save the recording to the file system
										if (string.len(field["voicemail_name_base64"]) > 32) then
											--include the file io
												local file = require "resources.functions.file"

											--write decoded string to file
												file.write_base64(file_location, field["voicemail_name_base64"]);
										end

									--play the recorded name
										if (file_exists(file_location)) then
											session:streamFile(file_location);
										else
											--announce the first and last names
											if (row.first_name ~= nil and row.first_name ~= '') then
												if (speak_mod ~= nil and speak_voice ~= nil) then
													session:execute("speak", speak_mod.."|"..speak_voice.."|"..row.first_name);
												else
													session:execute("say", default_language.." name_spelled iterated "..row.first_name);
												end
											end
											--session:execute("sleep", "500");
											if (row.last_name ~= nil and row.last_name ~= '') then
												if (speak_mod ~= nil and speak_voice ~= nil) then
													session:execute("speak", speak_mod.."|"..speak_voice.."|"..row.last_name);
												else
													session:execute("say", default_language.." name_spelled iterated "..row.last_name);
												end
											end
										end
								end);
								dbh:release()
							elseif (storage_type == "http_cache") then
								file_location = storage_path.."/"..row.extension.."/recorded_name.wav";
								if (file_exists(file_location)) then
									session:streamFile(file_location);
								end
							else
								if (debug["info"]) then
									freeswitch.consoleLog("notice", "[directory] path: "..voicemail_dir.."/"..row.extension.."/recorded_name.wav\n");
								end
								if (file_exists(voicemail_dir.."/"..row.extension.."/recorded_name.wav")) then
									session:streamFile(voicemail_dir.."/"..row.extension.."/recorded_name.wav");
								else
									--announce the first and last names
										if (row.first_name ~= nil and row.first_name ~= '') then
											if (speak_mod ~= nil and speak_voice ~= nil) then
												session:execute("speak",speak_mod.."|"..speak_voice.."|"..row.first_name);
											else
												session:execute("say", default_language.." name_spelled iterated "..row.first_name);
											end
										end
										if (row.last_name ~= nil and row.last_name ~= '') then
											--session:execute("sleep", "500");
											if (speak_mod ~= nil and speak_voice ~= nil) then
												session:execute("speak",speak_mod.."|"..speak_voice.."|"..row.last_name);
											else
												session:execute("say", default_language.." name_spelled iterated "..row.last_name);
											end
										end
								end
							end

						--announce the extension number
							--if (row.directory_exten_visible == "false") then
								--invisible extension number
							if (row.extension ~= nil and row.extension ~= '' and row.directory_exten_visible == "true") then
								session:streamFile(sounds_dir.."/directory/dir-at_extension.wav");
								session:execute("say", default_language.." NAME_SPELLED iterated "..row.extension);
							end

						--select this entry press 1
							dtmf_digits = select_entry();

						--if 1 is pressed then transfer the call
							if (dtmf_digits == "1") then
								session:execute("transfer", row.extension.." XML "..row.context);
							end
							
						--if * is pressed, transfer to referred extension
							if (dtmf_digits == "*") then
								session:execute("transfer",previous_menu);
							end
					end
					found = true;
				end
			end
			if (found ~= true) then
				session:streamFile(sounds_dir.."/directory/dir-no_matching_results.wav");
			end
			search_count = search_count + 1;
			if (search_count < search_limit) then
				directory_search();
			end
	end

--get the extensions from the database
	local sql = "SELECT * FROM v_extensions WHERE domain_uuid = :domain_uuid AND enabled = 'true' AND (directory_visible is null or directory_visible = 'true') ORDER BY directory_first_name, effective_caller_id_name asc; ";
	local params = {domain_uuid = domain_uuid};
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[directory] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
	end
	x = 1;
	directory = {}
	dbh:query(sql, params, function(row)
		--show all key value pairs
			--for key, val in pairs(row) do
			--	freeswitch.consoleLog("notice", "[directory] Key: " .. key .. " Value: " .. val .. "\n");
			--end
		--add the entire row to the directory table array
			--directory[x] = row;
		--variables
			effective_caller_id_name = row.effective_caller_id_name;
			if (row.directory_first_name ~= nil) and (row.directory_first_name ~= "") then
				first_name = row.directory_first_name;
				last_name = row.directory_last_name;
			else
				if (string.len(effective_caller_id_name) > 0) then
					name = effective_caller_id_name;
					name_table = explode(" ",name);
					first_name = name_table[1];
					last_name = name_table[2];
				end
			end
		--get the digits
			if (first_name) then
				if (string.len(first_name) > 0) then
					--freeswitch.consoleLog("notice", "[directory] first_name: --" .. first_name .. "--\n");
					first_name_digits = dialpad_to_digit(string.sub(first_name, 1, 1))..dialpad_to_digit(string.sub(first_name, 2, 2))..dialpad_to_digit(string.sub(first_name, 3, 3));
				end
			end
			if (last_name) then
				if (string.len(last_name) > 0) then
					--freeswitch.consoleLog("notice", "[directory] last_name: --" .. last_name .. "--\n");
					last_name_digits = dialpad_to_digit(string.sub(last_name, 1, 1))..dialpad_to_digit(string.sub(last_name, 2, 2))..dialpad_to_digit(string.sub(last_name, 3, 3));
				end
			end

		--add the row to the array
			--freeswitch.consoleLog("notice", "[directory] extension="..row.extension..",context="..row.user_context..",first_name="..name_table[1]..",last_name="..name_table[2]..",first_name_digits="..first_name_digits..",last_name_digits="..last_name_digits..",directory_exten_visible="..row.directory_exten_visible.."\n");
			table.insert(directory, {extension=row.extension,context=row.user_context,first_name=first_name,last_name=last_name,first_name_digits=first_name_digits,last_name_digits=last_name_digits,directory_exten_visible=row.directory_exten_visible});

		--increment x
			x = x + 1;
	end);

--call the directory search function
	if (session:ready()) then
		directory_search();
	end

--timeout action
	if (timeout_destination == nil) then
		session:streamFile(sounds_dir.."/voicemail/vm-goodbye.wav");
	else
		session:execute("transfer", timeout_destination.." XML "..row.context);
	end

--notes
	--session:execute("say", "en name_spelled pronounced mark");
	--<action application="say" data="en name_spelled iterated ${destination_number}"/>
	--session:execute("say", "en number iterated 12345");
	--session:execute("say", "en number pronounced 1001");
	--session:execute("say", "en short_date_time pronounced [timestamp]");
	--session:execute("say", "en CURRENT_TIME pronounced CURRENT_TIME");
	--session:execute("say", "en CURRENT_DATE pronounced CURRENT_DATE");
	--session:execute("say", "en CURRENT_DATE_TIME pronounced CURRENT_DATE_TIME");
