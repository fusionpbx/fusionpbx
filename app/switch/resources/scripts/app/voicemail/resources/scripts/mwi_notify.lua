
--get the argv values
	voicemail_id = argv[1];
	domain_name = argv[2];
	new_messages = argv[3] or '0';
	saved_messages = argv[4] or '0';

--include the lua script
	require "resources.functions.config";

--send MWI NOTIFY message
	require "app.voicemail.resources.functions.mwi_notify";

--debug info
	--freeswitch.consoleLog("NOTICE", "voicemail_id="..voicemail_id.."\n");
	--freeswitch.consoleLog("NOTICE", "domain_name="..domain_name.."\n");
	--freeswitch.consoleLog("NOTICE", "new_messages="..new_messages.."\n");
	--freeswitch.consoleLog("NOTICE", "saved_messages="..saved_messages.."\n");

--send the message waiting event
	mwi_notify(voicemail_id..'@'..domain_name, domain_name, new_messages, saved_messages);

