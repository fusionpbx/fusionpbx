-- Initialization of variables
api = freeswitch.API()
-- First argument is caller uuid.
caller_uuid = argv[1]
if caller_uuid==nil then return end
-- Second argument is sound file name.
sound_file = argv[2]
if sound_file==nil then return end
-- Third argument is repeating interval in miliseconds.
mseconds = argv[3]
if mseconds==nil then return end
account = argv[4]
if account==nil then return end


debug["sql"] = true;

--include config.lua
scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
dofile(scripts_dir.."/resources/functions/config.lua");
dofile(config());

dofile(scripts_dir.."/resources/functions/database_handle.lua");
dbh = database_handle('system');

sql = "SELECT balance, currency FROM v_billings WHERE type_value='"..account.."'";
if (debug["sql"]) then
	freeswitch.consoleLog("notice", "[cidlookup] "..sql.."\n");
end

while (true) do
	line = api:executeString("show calls") 
	exists=false -- Variable to allow script termination when member leaves queue 

	if string.find(line,caller_uuid,1,true)~=nil then
		exists=true -- Member still in queue so script must continue
		cmd = "uuid_broadcast "..caller_uuid.." playback::"..sound_file.." aleg";
		freeswitch.consoleLog("NOTICE", "[low-balance] " .. cmd .. "\n");
		api:executeString(cmd);

		status = dbh:query(sql, function(row)
			balance = row.balance;
			currency = row.currency;
		end);

		freeswitch.consoleLog("NOTICE", "[low-balance] Current balance: "..balance.." "..currency.."\n");
		cmd = "uuid_display "..caller_uuid.." Low balance "..balance.." "..currency;
		freeswitch.consoleLog("NOTICE", "[low-balance] " .. cmd .. "\n");
		api:executeString(cmd);

	end

	if exists==false then return end -- If member was not found in queue, or it's status is Aborted - terminate script
	freeswitch.msleep(mseconds) -- Pause before announcing ding
end
