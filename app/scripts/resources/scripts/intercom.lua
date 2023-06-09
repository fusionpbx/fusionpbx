--define the explode function
require "resources.functions.explode";

--make sure the session is ready
	if ( session:ready() ) then

		--get the dialplan variables and set them as local variables
			domain_name = session:getVariable("domain_name");
			destination = session:getVariable("destination");

		--determine whether to check if the destination is available
			check_destination_status = session:getVariable("check_destination_status");
			if (not check_destination_status) then check_destination_status = 'false'; end

		--create the api object
			api = freeswitch.API();

		--get the channels
			if (check_destination_status == 'true') then
				cmd_string = "show channels";
				channel_result = api:executeString(cmd_string);
				--detect if the destination is available or busy
				destination_status = 'available';
				channel_array = explode("\n", channel_result);
				for index,row in pairs(channel_array) do
					if string.find(row, destination..'@'..domain_name, nil, true) then
						destination_status = 'busy';
						break;
					end
				end
				freeswitch.consoleLog("NOTICE", "[INTERCOM] destination_status =  "..destination_status.." \n");


				if (destination_status == 'available') then
					cmd_string = "user/"..destination.."@"..domain_name;			
					session:execute("bridge", cmd_string);
				else
					session:execute("playback", "tone_stream://%(500,500,480,620);loops=3");
				end
			end

	end
