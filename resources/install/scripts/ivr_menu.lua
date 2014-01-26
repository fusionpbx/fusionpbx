--	ivr_menu.lua
--	Part of FusionPBX
--	Copyright (C) 2012 Mark J Crane <markjcrane@fusionpbx.com>
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

--set the debug options
	debug["action"] = false;
	debug["sql"] = false;
	debug["regex"] = false;
	debug["dtmf"] = false;
	debug["tries"] = false;

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

--get the variables
	domain_name = session:getVariable("domain_name");
	context = session:getVariable("context");
	ivr_menu_uuid = session:getVariable("ivr_menu_uuid");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");

--set default variable(s)
	tries = 0;

--add the trim function
	function trim(s)
		return s:gsub("^%s+", ""):gsub("%s+$", "")
	end

--check if a file exists
	function file_exists(name)
		local f=io.open(name,"r")
		if f~=nil then io.close(f) return true else return false end
	end

--get the ivr menu from the database
	sql = [[SELECT * FROM v_ivr_menus 
		WHERE ivr_menu_uuid = ']] .. ivr_menu_uuid ..[['
		AND ivr_menu_enabled = 'true' ]];
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
	end
	status = dbh:query(sql, function(row)
		ivr_menu_name = row["ivr_menu_name"];
		--ivr_menu_extension = row["ivr_menu_extension"];
		ivr_menu_greet_long = row["ivr_menu_greet_long"];
		ivr_menu_greet_short = row["ivr_menu_greet_short"];
		ivr_menu_invalid_sound = row["ivr_menu_invalid_sound"];
		ivr_menu_exit_sound = row["ivr_menu_exit_sound"];
		ivr_menu_confirm_macro = row["ivr_menu_confirm_macro"];
		ivr_menu_confirm_key = row["ivr_menu_confirm_key"];
		ivr_menu_tts_engine = row["ivr_menu_tts_engine"];
		ivr_menu_tts_voice = row["ivr_menu_tts_voice"];
		ivr_menu_confirm_attempts = row["ivr_menu_confirm_attempts"];
		ivr_menu_timeout = row["ivr_menu_timeout"];
		--ivr_menu_exit_app = row["ivr_menu_exit_app"];
		--ivr_menu_exit_data = row["ivr_menu_exit_data"];
		ivr_menu_inter_digit_timeout = row["ivr_menu_inter_digit_timeout"];
		ivr_menu_max_failures = row["ivr_menu_max_failures"];
		ivr_menu_max_timeouts = row["ivr_menu_max_timeouts"];
		ivr_menu_digit_len = row["ivr_menu_digit_len"];
		ivr_menu_direct_dial = row["ivr_menu_direct_dial"];
		--ivr_menu_description = row["ivr_menu_description"];
		ivr_menu_ringback = row["ivr_menu_ringback"];
		ivr_menu_cid_prefix = row["ivr_menu_cid_prefix"];
	end);

--set the caller id name
	if (caller_id_name) then
		if (string.len(ivr_menu_cid_prefix) > 0) then
			caller_id_name = ivr_menu_cid_prefix .. "#" .. caller_id_name;
			session:setVariable("caller_id_name", caller_id_name);
			session:setVariable("effective_caller_id_name", caller_id_name);
		end
	end

--get the sounds dir, language, dialect and voice
	sounds_dir = session:getVariable("sounds_dir");
	default_language = session:getVariable("default_language");
	default_dialect = session:getVariable("default_dialect");
	default_voice = session:getVariable("default_voice");
	if (not default_language) then default_language = 'en'; end
	if (not default_dialect) then default_dialect = 'us'; end
	if (not default_voice) then default_voice = 'callie'; end

--make the path relative
	if (string.sub(ivr_menu_greet_long,0,71) == "$${sounds_dir}/${default_language}/${default_dialect}/${default_voice}/") then
		ivr_menu_greet_long = string.sub(ivr_menu_greet_long,72);
	end
	if (string.sub(ivr_menu_greet_short,0,71) == "$${sounds_dir}/${default_language}/${default_dialect}/${default_voice}/") then
		ivr_menu_greet_short = string.sub(ivr_menu_greet_short,72);
	end

--adjust the file path
	if (ivr_menu_greet_short) then
		--do nothing
	else
		ivr_menu_greet_short = ivr_menu_greet_long;
	end
	if (not file_exists(ivr_menu_greet_long)) then
		if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..ivr_menu_greet_long)) then
			ivr_menu_greet_long = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..ivr_menu_greet_long;
		end
	end
	if (string.len(ivr_menu_greet_short) > 1) then
		if (not file_exists(ivr_menu_greet_short)) then
			if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..ivr_menu_greet_short)) then
				ivr_menu_greet_short = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..ivr_menu_greet_short;
			end
		end
	else
		ivr_menu_greet_short = ivr_menu_greet_long;
	end
	ivr_menu_invalid_entry = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/ivr/ivr-that_was_an_invalid_entry.wav";

--prepare the api object
	api = freeswitch.API();

--define the ivr menu
	function menu()
		--increment the tries
		tries = tries + 1;
		dtmf_digits = "";
		min_digits = 1;
		if (tries == 1) then
			freeswitch.consoleLog("notice", "[ivr_menu] greet long: " .. ivr_menu_greet_long .. "\n");
			dtmf_digits = session:playAndGetDigits(min_digits, ivr_menu_digit_len, 1, ivr_menu_timeout, ivr_menu_confirm_key, ivr_menu_greet_long, "", ".*");
		else
			freeswitch.consoleLog("notice", "[ivr_menu] greet long: " .. ivr_menu_greet_short .. "\n");
			dtmf_digits = session:playAndGetDigits(min_digits, ivr_menu_digit_len, ivr_menu_max_timeouts, ivr_menu_timeout, ivr_menu_confirm_key, ivr_menu_greet_short, "", ".*");
		end
		if (string.len(dtmf_digits) > 0) then
			freeswitch.consoleLog("notice", "[ivr_menu] dtmf_digits: " .. dtmf_digits .. "\n");
			menu_options(session, dtmf_digits);
		else
			if (tries < tonumber(ivr_menu_max_failures)) then
				--log the dtmf digits
					if (debug["tries"]) then
						freeswitch.consoleLog("notice", "[ivr_menu] tries: " .. tries .. "\n");
					end
				--run the menu again
					menu();
			end
		end
	end

	function menu_options(session, digits)

		--log the dtmf digits
			if (debug["dtmf"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] dtmf: " .. digits .. "\n");
			end

		--get the ivr menu options
			sql = [[SELECT * FROM v_ivr_menu_options WHERE ivr_menu_uuid = ']] .. ivr_menu_uuid ..[[' ORDER BY ivr_menu_option_order asc ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				--check for matching options
					if (tonumber(row.ivr_menu_option_digits) ~= nil) then
						row.ivr_menu_option_digits = "^"..row.ivr_menu_option_digits.."$";
					end
					if (api:execute("regex", "m:~"..digits.."~"..row.ivr_menu_option_digits) == "true") then
						if (row.ivr_menu_option_action == "menu-exec-app") then
							--get the action and data
								pos = string.find(row.ivr_menu_option_param, " ", 0, true);
								action = string.sub( row.ivr_menu_option_param, 0, pos-1);
								data = string.sub( row.ivr_menu_option_param, pos+1);

							--check if the option uses a regex
								regex = string.find(row.ivr_menu_option_digits, "(", 0, true);
								if (regex) then
									--get the regex result
										result = trim(api:execute("regex", "m:~"..digits.."~"..row.ivr_menu_option_digits.."~$1"));
										if (debug["regex"]) then
											freeswitch.consoleLog("notice", "[ivr_menu] regex m:~"..digits.."~"..row.ivr_menu_option_digits.."~$1\n");
											freeswitch.consoleLog("notice", "[ivr_menu] result: "..result.."\n");
										end

									--replace the $1 and the domain name
										data = data:gsub("$1", result);
										data = data:gsub("${domain_name}", domain_name);
								end --if regex
						end --if menu-exex-app
					end --if regex match

				--execute
					if (action) then
						if (string.len(action) > 0) then
							--send to the log
								if (debug["action"]) then
									freeswitch.consoleLog("notice", "[ivr_menu] action: " .. action .. " data: ".. data .. "\n");
								end
							--run the action
								session:execute(action, data);
						end
					end

				--clear the variables
					action = "";
					data = "";
			end); --end results

		--direct dial
			if (ivr_menu_direct_dial == "true") then
				if (string.len(digits) < 6) then
					--replace the $1 and the domain name
						digits = digits:gsub("*", "");
					--check to see if the user extension exists
						cmd = "user_exists id ".. digits .." "..domain_name;
						result = api:executeString(cmd);
						freeswitch.consoleLog("NOTICE", "[ivr_menu] "..cmd.." "..result.."\n");
						if (result == "true") then
							--run the action
								session:execute("transfer", digits.." XML "..context);
						else
							--run the menu again
								menu();	
						end
				end
			end

		--execute
			if (action) then
				if (string.len(action) == 0) then
					session:streamFile(ivr_menu_invalid_entry);
					menu();
				end
			else
				session:streamFile(ivr_menu_invalid_entry);
				menu();
			end
	end --end function

--answer the session
	if ( session:ready() ) then
		session:answer();
		menu();
	end
