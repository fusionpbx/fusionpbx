--subscribe to the events
	events = freeswitch.EventConsumer("CUSTOM","avmd::beep");

--prepare the api object
	api = freeswitch.API();

--get the events
	for event in (function() return events:pop(1) end) do
		--serialize the data for the console
			--freeswitch.consoleLog("notice","event:" .. event:serialize("xml") .. "\n");
			--freeswitch.consoleLog("notice","event:" .. event:serialize("json") .. "\n");

		--get the uuid
			local uuid = event:getHeader("Unique-ID");
			freeswitch.consoleLog("[avmd] uuid: ", uuid .. "\n");

		--end the call
			reply = api:executeString("uuid_kill "..uuid.." NORMAL_CLEARING");
	end
