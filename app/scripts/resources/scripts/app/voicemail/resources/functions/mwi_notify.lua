
--define a function to send email
	function mwi_notify(account, new_messages, saved_messages)

		--includes
		require "resources.functions.explode"
		require "resources.functions.trim"

		--create the api object
		api = freeswitch.API();
		local sofia_contact = trim(api:executeString("sofia_contact */"..account));
		array = explode("/", sofia_contact);
		sip_profile = array[2];

		--debug info
		--freeswitch.consoleLog("NOTICE", "sofia_contact */"..account.."\n");
		--freeswitch.consoleLog("NOTICE", "sip_profile="..sip_profile.."\n");
		--freeswitch.consoleLog("NOTICE", "sofia_contact="..sofia_contact.."\n");

		--set the variables
		new_messages   = tonumber(new_messages)   or 0
		saved_messages = tonumber(saved_messages) or 0

		--set the event and send it
		local event = freeswitch.Event("message_waiting")
		event:addHeader("MWI-Messages-Waiting", (new_messages == 0) and "no" or "yes")
		event:addHeader("MWI-Message-Account", "sip:" .. account)
		event:addHeader("MWI-Voice-Message", string.format("%d/%d (0/0)", new_messages, saved_messages))
		event:addHeader("sofia-profile", sip_profile)
		return event:fire()
	end

--return module value
	return mwi_notify
