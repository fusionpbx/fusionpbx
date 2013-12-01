

--include config.lua
	--scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	--dofile(scripts_dir.."/resources/functions/config.lua");
	--dofile(config());

--add functions
	dofile(scripts_dir.."/resources/functions/file_exists.lua");
	dofile(scripts_dir.."/resources/functions/trim.lua");

--set the api object
	api = freeswitch.API();

--windows (/ad show only directories)
	--dir "C:\program files\fusionpbx" /b
--unix
	-- dir /usr/local/freeswitch/scripts -1


--set local variables
	local context = session:getVariable("context");
	local destination_number = session:getVariable("destination_number");

--determine the call direction
	if (context == "public") then
		call_direction = "inbound";
	else
		if (string.len(destination_number) > 6) then
			call_direction = "outbound";
		else
			call_direction = "local";
		end
	end

--set the call direction as a session variable
	session:setVariable("call_direction", call_direction);
	--freeswitch.consoleLog("notice", "[app:dialplan] set call_direction " .. call_direction .. "\n");

--include the dialplans
	result = assert (io.popen ("dir " ..scripts_dir.."/app/dialplan/resources/"..call_direction.." /b -1"));
	for file in result:lines() do
		if (string.sub(file, -4) == ".lua") then
			--order = string.match(file, "%d+");
			--if (order == nil) then order = file; end
			if file_exists(scripts_dir.."/app/dialplan/resources/"..call_direction.."/"..file) then
				dofile(scripts_dir.."/app/dialplan/resources/"..call_direction.."/"..file);
			end
			freeswitch.consoleLog("notice", "[app:dialplan] lua: " .. file .. "\n");
		end
	end
