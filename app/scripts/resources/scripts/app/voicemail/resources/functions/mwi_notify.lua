
--define a function to send mwi notify
	function mwi_notify(account, new_messages, saved_messages)

		--includes
		require "resources.functions.explode"
		require "resources.functions.trim"

		--create the api object
		api = freeswitch.API();

		local sofia_contacts = trim(api:executeString("sofia_contact */"..account));
		local sofia_contact_table = explode(",", sofia_contacts);

		local sip_profile_table = {};

		for key,value in pairs(sofia_contact_table) do
			f = explode("/", value);
			sip_profile = f[2];


			--check to see if a notify has already been sent to this profile
			new = "true";
			for profile_index, profile_table_value in pairs(sip_profile_table) do
				if profile_table_value == sip_profile then
					new = "false";
				end
			end

			if new == "true" then 
				--debug info
				--freeswitch.consoleLog("NOTICE", "sofia_contact */"..account.."\n");
				--freeswitch.consoleLog("NOTICE", "sip_profile="..sip_profile.."\n");
				--freeswitch.consoleLog("NOTICE", "sofia_contacts="..sofia_contacts.."\n");
		
				--set the variables
				new_messages   = tonumber(new_messages)   or 0
				saved_messages = tonumber(saved_messages) or 0
		
				--set the event and send it
				local event = freeswitch.Event("message_waiting")
				event:addHeader("MWI-Messages-Waiting", (new_messages == 0) and "no" or "yes")
				event:addHeader("MWI-Message-Account", "sip:" .. account)
				event:addHeader("MWI-Voice-Message", string.format("%d/%d (0/0)", new_messages, saved_messages))
				event:addHeader("sofia-profile", sip_profile)
				event:fire()
				
				table.insert(sip_profile_table,sip_profile);
			end
				
		end
		
	end

--return module value
	return mwi_notify
