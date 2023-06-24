
--get the argv values
	script_name = argv[0];

--options all, last, non_moderator, member_id
	data = argv[1];

--prepare the api object
	api = freeswitch.API();

--get the session variables
	conference_name = session:getVariable("conference_name");

--send the conferenc mute command
	cmd = "conference " .. conference_name .. " unmute " .. data;
	response = api:executeString(cmd);
