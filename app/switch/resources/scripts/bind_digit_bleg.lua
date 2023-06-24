--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2021
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Joseph A Nadiv <ynadiv@corpit.xyz>

--include config.lua
	require "resources.functions.config";

--create the api object
	api = freeswitch.API();
	require "resources.functions.channel_utils";

--get context variable
	local context = argv[1];

--bind to bleg
	session:execute("bind_digit_action", "local,*1,exec:execute_extension,dx XML ".. context .. ",self,self");
	session:execute("bind_digit_action", "local,*3,exec:execute_extension,cf XML ".. context .. ",self,self");
	session:execute("bind_digit_action", "local,*4,exec:execute_extension,att_xfer XML ".. context .. ",self,self");
	session:execute("digit_action_set_realm", "local");
