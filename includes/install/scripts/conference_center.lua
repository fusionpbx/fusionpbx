--	conference.lua
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

--set variables
	flags = "";
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

--make sure the session is ready
if ( session:ready() ) then
	session:answer( );
	sounds_dir = session:getVariable("sounds_dir");
	domain_name = session:getVariable("domain_name");
	pin_number = session:getVariable("pin_number");

	--get the domain_uuid
		if (domain_name ~= nil) then
			sql = "SELECT domain_uuid FROM v_domains ";
			sql = sql .. "WHERE domain_name = '" .. domain_name .."' ";
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[conference] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(rows)
				domain_uuid = rows["domain_uuid"];
			end);
		end

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--get the variables
		domain_name = session:getVariable("domain_name");
		--meeting_uuid = session:getVariable("meeting_uuid");
		caller_id_name = session:getVariable("caller_id_name");
		caller_id_number = session:getVariable("caller_id_number");

	--if the pin number is provided then require it
		if (not pin_number) then 
			min_digits = 3;
			max_digits = 12;
			pin_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
		end
	--get the conference sessions
		sql = [[SELECT * FROM v_conference_sessions as s, v_meeting_pins as p
			WHERE s.domain_uuid = ']] .. domain_uuid ..[['
			AND s.meeting_uuid = p.meeting_uuid
			AND p.member_pin = ']] .. pin_number ..[['
			AND enabled = 'true' ]];
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[conference] SQL: " .. sql .. "\n");
		end
		status = dbh:query(sql, function(row)
			conference_session_uuid = row["conference_session_uuid"];
			conference_uuid = row["conference_uuid"];
			meeting_uuid = row["meeting_uuid"];
			max_members = row["max_members"];
			wait_mod = row["wait_mod"];
			member_type = row["member_type"];
			announce = row["announce"];
			enter_sound = row["enter_sound"];
			mute = row["mute"];
			created = row["created"];
			created_by = row["created_by"];
			enabled = row["enabled"];
			description = row["description"];
		end);

	if (conference_uuid ~= nil) then
		--set a conference parameter
			--conference <confname> set <parameter_name> <value>

		if (max_members ~= nil) then
			--max members must be 2 or more
			session:execute("set","conference_max_members="..max_members);
		end
		if (mute == "true") then
			flags = flags .. "mute";
		end
		if (enter_sound ~= nil) then
			session:execute("set","conference_enter_sound="..enter_sound);
		end
		if (exit_sound ~= nil) then
			session:execute("set","conference_exit_sound="..exit_sound);
		end

		--working
			--get number of peoople in the conference
				--conference Conference-Center-voip.fusionpbx.com get count
			--get number of seconds since the conference started
				--conference Conference-Center-voip.fusionpbx.com get run_time
			--get max members in a conference
				--conference Conference-Center-voip.fusionpbx.com get max_members
				--conference Conference-Center-voip.fusionpbx.com set max_members 3
			--sound
				--session:execute("set","conference_enter_sound="..enter_sound);
				--session:execute("set","conference_exit_sound="..exit_sound);
			--used when the first member joins the conference
				--session:execute("set","conference_max_members="..max_members);
			--if (wait_mod == "true") then
				--flags = flags .. "|wait_mod"; --not working
				--session:execute("conference","Conference-Center-voip.fusionpbx.com(set conference-flags=wait-mod)"); --not working
			--end
			if (member_type == "moderator") then
				--set as the moderator
					flags = flags .. "|moderator";
				--when the moderator leaves end the conference
					flags = flags .. "|endconf";
				--set the moderator controls
					session:execute("set","conference_controls=moderator");
			end

		--send the call to the conference
		--session:execute("conference","confname@profilename+[conference pin number]+flags{moderator}");
		cmd = meeting_uuid.."-"..domain_name.."@default+flags{".. flags .."}";
		freeswitch.consoleLog("notice", "[conference] ".. cmd .."\n");
		session:execute("conference",cmd);
		--alternative
			--uuid_transfer <uuid> conference:3000@default inline

		--freeswitch.consoleLog("notice", "[conference] line: 147\n");
	else
		session:streamFile("phrase:voicemail_fail_auth:#");
		session:hangup("NORMAL_CLEARING");
	end

end