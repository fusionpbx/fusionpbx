
--define a function to send email
	function mwi_notify(account, new_messages, saved_messages)
		new_messages   = tonumber(new_messages)   or 0
		saved_messages = tonumber(saved_messages) or 0

		local event = freeswitch.Event("message_waiting")
		event:addHeader("MWI-Messages-Waiting", (new_messages == 0) and "no" or "yes")
		event:addHeader("MWI-Message-Account", "sip:" .. account)
		event:addHeader("MWI-Voice-Message", string.format("%d/%d (0/0)", new_messages, saved_messages))
		return event:fire()
	end

--return module value
	return mwi_notify
