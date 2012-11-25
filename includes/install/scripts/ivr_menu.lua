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

--get the variables
	domain_name = session:getVariable("domain_name");
	ivr_menu_uuid = session:getVariable("ivr_menu_uuid");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");

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
		--ivr_menu_direct_dial = row["ivr_menu_direct_dial"];
		--ivr_menu_description = row["ivr_menu_description"];
		ivr_menu_ringback = row["ivr_menu_ringback"];
	end);

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
	if (not ivr_menu_greet_short) then
		ivr_menu_greet_short = ivr_menu_greet_long;
	end
	if (not file_exists(ivr_menu_greet_long)) then
		if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..ivr_menu_greet_long)) then
			ivr_menu_greet_long = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..ivr_menu_greet_long;
		end
	end
	if (not file_exists(ivr_menu_greet_short)) then
		if (file_exists(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..ivr_menu_greet_short)) then
			ivr_menu_greet_short = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/"..ivr_menu_greet_short;
		end
	end

--prepare the ivr menu data
	hash = {
		["main"] = undef,
		["name"] = ivr_menu_name,
		["greet_long"] = ivr_menu_greet_long,
		["greet_short"] = ivr_menu_greet_short,
		["invalid_sound"] = ivr_menu_invalid_sound,
		["exit_sound"] = ivr_menu_exit_sound,
		["confirm_macro"] = ivr_menu_confirm_macro,
		["confirm_key"] = ivr_menu_confirm_key,
		["tts_engine"] = ivr_menu_tts_engine,
		["tts_voice"] = ivr_menu_tts_voice,
		["max_timeouts"] = ivr_menu_max_timeouts,
		["confirm_attempts"] = ivr_menu_confirm_attempts,
		["inter_digit_timeout"] = ivr_menu_inter_digit_timeout,
		["digit_len"] = ivr_menu_digit_len,
		["timeout"] = ivr_menu_timeout,
		["max_failures"] = ivr_menu_max_failures
	} 

	top = freeswitch.IVRMenu(
		hash["main"],
		hash["name"],
		hash["greet_long"],
		hash["greet_short"],
		hash["invalid_sound"],
		hash["exit_sound"],
		hash["confirm_macro"],
		hash["confirm_key"],
		hash["tts_engine"],
		hash["tts_voice"],
		hash["max_timeouts"],
		hash["confirm_attempts"],
		hash["inter_digit_timeout"],
		hash["digit_len"],
		hash["timeout"],
		hash["max_failures"]);

--get the ivr menu options
	sql = [[SELECT * FROM v_ivr_menu_options WHERE ivr_menu_uuid = ']] .. ivr_menu_uuid ..[[' ORDER BY ivr_menu_option_order asc ]];
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
	end
	status = dbh:query(sql, function(row)
		 --top:bindAction("menu-exec-app", "playback /tmp/swimp.raw", "2");
		top:bindAction(row.ivr_menu_option_action, row.ivr_menu_option_param, row.ivr_menu_option_digits);
	end);

--execute the ivr menu
	top:execute(session, ivr_menu_name);
