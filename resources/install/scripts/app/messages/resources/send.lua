--	FusionPBX
--	Version: MPL 1.1

--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/

--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.

--	The Original Code is FusionPBX

--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Portions created by the Initial Developer are Copyright (C) 2018
--	the Initial Developer. All Rights Reserved.

--get the argv values
	script_name = argv[0];
	message_from = argv[1];
	message_to = argv[2];
	message_text = argv[3];

--send a message to the console
	freeswitch.consoleLog("NOTICE",[[[message] from ]]..message_from);
	freeswitch.consoleLog("NOTICE",[[[message] to ]] .. message_to);
	freeswitch.consoleLog("NOTICE",[[[message] from ]]..message_text);

--connect to the database
	--local Database = require "resources.functions.database";
	--dbh = Database.new('system');

--include functions
	require "resources.functions.trim";
	require "resources.functions.explode";
	--require "resources.functions.file_exists";

--create the api object
	api = freeswitch.API();

--get the domain name for the destination
	array = explode('@', message_to);
	domain_name = array[2];
	freeswitch.consoleLog("NOTICE",[[[message] domain_name ]]..domain_name);

--get the sip profile name
	local sofia_contact = trim(api:executeString("sofia_contact */"..message_to));
	local array = explode("/", sofia_contact);
	local sip_profile = array[2];

--send the sms message
	local event = freeswitch.Event("CUSTOM", "SMS::SEND_MESSAGE");
	event:addHeader("proto", "sip");
	event:addHeader("dest_proto", "sip");
	event:addHeader("from", message_from);
	event:addHeader("from_full", "sip:"..message_from);
	event:addHeader("to", message_to);
	event:addHeader("subject", "sip:"..message_to);
	--event:addHeader("type", "text/html");
	event:addHeader("type", "text/plain");
	event:addHeader("hint", "the hint");
	event:addHeader("replying", "true");
	event:addHeader("sip_profile", sip_profile);
	event:addBody(message_text);

--send info to the console
	freeswitch.consoleLog("info", event:serialize());

--send the event
	event:fire();
