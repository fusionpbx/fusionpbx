
--load libraries
local send_mail = require 'resources.functions.send_mail'
local Database = require "resources.functions.database"
local Settings = require "resources.functions.lazy_settings"
local Utils = require "resources.functions.channel_utils";

--connect to the database
local dbh = Database.new('system');

--get sessions info
if (session and session:ready()) then
	domain_uuid = session:getVariable("domain_uuid");
	domain_name = session:getVariable("domain_name");
	time_zone = session:getVariable("timezone");
	sounds_dir = session:getVariable("sounds_dir");
end

--set the sounds path for the language, dialect and voice
default_language = session:getVariable("default_language");
default_dialect = session:getVariable("default_dialect");
default_voice = session:getVariable("default_voice");
if (not default_language) then default_language = 'en'; end
if (not default_dialect) then default_dialect = 'us'; end
if (not default_voice) then default_voice = 'callie'; end

--add the default language voice and dialect to the sounds directory
sounds_dir = sounds_dir .. '/' .. default_language .. '/'.. default_dialect .. '/'.. default_voice;

--answer the session
if (session ~= nil and session:ready()) then
	session:answer();
	session:sleep('1000');
end

--set values for playAndGetDigits
min_digits = 1;
max_digits = 1;
max_tries = 1;

--get recent calls from the emergency_logs
local sql = "SELECT ";
sql = sql .. " domain_uuid, extension, event, ";
sql = sql .. " to_char(timezone(:time_zone, insert_date), 'DD Mon YYYY') as date_formatted, ";
sql = sql .. " to_char(timezone(:time_zone, insert_date), 'HH12:MI:SS am') as time_formatted, ";
sql = sql .. " extract(epoch from insert_date) as epoch, insert_date ";
sql = sql .. "FROM v_emergency_logs ";
sql = sql .. "WHERE domain_uuid = :domain_uuid ";
sql = sql .. "ORDER BY insert_date desc ";
sql = sql .. "LIMIT 10 ";
local params = {domain_uuid=domain_uuid,time_zone=time_zone}
dbh:query(sql, params, function(row)
	-- send a message to the console
	freeswitch.consoleLog("notice", "[emergency] recent calls, extension: " .. row.extension .. " date: " .. row.date_formatted .. " " .. row.time_formatted .. "\n");

	-- say the extension number has called emergency services
	if (row.extension) then
		session:say(row.extension, default_language, "name_spelled", "pronounced");
		session:execute("playback", "ivr/ivr-has_called_emergency_services.wav");
	end

	-- say the date and time
	if (row.epoch) then
		session:say(row.epoch, default_language, "current_date_time", "pronounced");
	end

	-- to continue
	digit_timeout = 100;
	dtmf_digits = session:playAndGetDigits(1, 20, max_tries, digit_timeout, "#", "phrase:pin_number_start:#", "", "\\d+");

	-- press
	if (string.len(dtmf_digits) == 0) then
		digit_timeout = 100;
		dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/voicemail/vm-press.wav", "", "\\d+|\\*");
	end

	-- 1
	if (string.len(dtmf_digits) == 0) then
		digit_timeout = 10000;
		dtmf_digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", sounds_dir.."/digits/1.wav", "", "\\d+|\\*");
	end

	-- to exit press *, if pressed then say goodbye and hangup the call
	if (string.len(dtmf_digits) > 0 and dtmf_digits == "*") then
		session:execute("playback", "phrase:voicemail_goodbye");
		session:hangup();
	end
	dtmf_digits = '';
end);

-- say goodbye and hangup the call
session:execute("playback", "phrase:voicemail_goodbye");
session:hangup();
