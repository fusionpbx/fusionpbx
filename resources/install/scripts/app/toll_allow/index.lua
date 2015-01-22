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
--	Copyright (C) 2010-2014
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Riccardo Granchi <riccardo.granchi@nems.it>
--	Philippe Rioual <bhouba@gmail.com>

--debug
	debug["toll_type"] = false
	
	dofile(scripts_dir.."/resources/functions/explode.lua");

--create the api object and get variables
	api = freeswitch.API()
	uuid = argv[2]
	
	if not uuid or uuid == "" then
		return
	end
	
	template_indexes = { "mobile", "landline", "international", "tollfree", "sharedcharge", "premium", "unknown"}

--Define templates for every toll type for your country
	function get_toll_types_it()
		if (debug["toll_type"]) then
			freeswitch.consoleLog("NOTICE", "[toll_allow] using IT toll types\n")
		end
		
		templates["mobile"]        = "[35]%d%d%d%d%d%d+"
		templates["landline"]      = "0[123456789]%d+"
		templates["international"] = "00%d+"
		templates["tollfree"]      = "119|1[3456789]%d|19[24]%d|192[01]%d%d|800%d%d%d%d%d+|803%d%d%d+|456%d%d%d%d%d%d+|11[2345678]|15%d%d|116%d%d%d|196%d%d"
		templates["sharedcharge"]  = "84[0178]%d%d%d%d+|199%d%d%d%d%d+|178%d%d%d%d%d+|12%d%d|10%d%d%d+|1482|149%d+|4[012]%d+|70%d%d%d%d%d+"
		templates["premium"]       = "89[2459]%d%d%d+|16[456]%d%d%d+|144%d%d%d+|4[346789]%d%d+"
		templates["unknown"]       = "%d%d+"
	end

	function get_toll_types_us()
		if (debug["toll_type"]) then
			freeswitch.consoleLog("NOTICE", "[toll_allow] using US toll types\n")
		end
		
		templates["unknown"]       = "%d+"
	end

	function get_toll_types_fr()
		if (debug["toll_type"]) then
			freeswitch.consoleLog("NOTICE", "[toll_allow] using FR toll types\n")
		end 
      
		templates["mobile"]        = "0[67]%d%d%d%d%d%d%d%d"
		templates["landline"]      = "0[1234589]%d%d%d%d%d%d%d%d"
		templates["international"] = "00%d+"
		templates["tollfree"]      = "15|17|18|112|114|115|116%d%d%d|118%d%d%d|119|19[16]|1[06]%d%d|080%d+"
		templates["sharedcharge"]  = "081%d+|082[0156]%d+|0884%d+|089[0123789]%d+"
		templates["premium"]       = "%d%d+"
		templates["unknown"]       = "%d%d+" 
	end
   
	called  = api:executeString("uuid_getvar " .. uuid .. " destination_number")
	prefix  = api:executeString("uuid_getvar " .. uuid .. " outbound_prefix")
	country = api:executeString("uuid_getvar " .. uuid .. " default_country")
	toll_allow = api:executeString("uuid_getvar " .. uuid .. " toll_allow")
	domain_name = api:executeString("uuid_getvar " .. uuid .. " domain_name")
	caller = api:executeString("uuid_getvar " .. uuid .. " caller_id_number")
		
	if (debug["toll_type"]) then
		freeswitch.consoleLog("NOTICE", "[toll_allow] called: " .. called .. "\n")
		freeswitch.consoleLog("NOTICE", "[toll_allow] prefix: " .. prefix .. "\n")
		freeswitch.consoleLog("NOTICE", "[toll_allow] country: " .. country .. "\n")
		freeswitch.consoleLog("NOTICE", "[toll_allow] tollAllow: " .. toll_allow .. "\n")
	end
	
	templates = {}
	local toll_type = "unknown"
		
	if ((prefix == nil) or (string.len(prefix) == 0) or (prefix == "_undef_") ) then
		prefix = ""
	end
		

	if ((country ~= nil) and (string.len(country) > 0)) then		
	--set templates for default country
		if     country == "IT" then get_toll_types_it()
		elseif country == "US" then get_toll_types_us()
		elseif country == "FR" then get_toll_types_fr()
		else
			if (debug["toll_type"]) then
				freeswitch.consoleLog("NOTICE", "[toll_allow] toll type: " .. toll_type .. "\n")
			end
			return toll_type
		end
		
	--set toll_type
		local found = false
		for i,label in pairs(template_indexes) do
			template = templates[label]
			if (debug["toll_type"]) then
				freeswitch.consoleLog("NOTICE", "[toll_allow] checking toll type " .. label .. " template: " .. template .. "\n")
			end
			
		--Doing split on | character
			parts = explode("|", template)

			for index,part in pairs(parts) do
				pattern = "^" .. prefix .. part .. "$"
				if (debug["toll_type"]) then
					--freeswitch.consoleLog("NOTICE", "[toll_allow] checking toll type " .. label .. " pattern: " .. pattern .. "\n")
				end
				
				if ( string.match(called, pattern) ~= nil ) then
					if (debug["toll_type"]) then
						freeswitch.consoleLog("NOTICE", "[toll_allow] destination number " .. called .. " matches " .. label .. " pattern: " .. pattern .. "\n")
					end
					toll_type = label
					found = true
					break
				end
			end
			
			if (found) then
				break
			end
		end
	
		freeswitch.consoleLog("NOTICE", "[toll_allow] toll type: " .. toll_type .. "\n")
	--	api:executeString("uuid_setvar " .. uuid .. " toll_type " .. toll_type);
	--	session:setVariable('toll_type', toll_type);
		
		
	--check toll allow
		allow = false
		
		if ((toll_allow ~= nil) and (string.len(toll_allow) > 0) and (toll_allow ~= "_undef_")) then
			parts = explode(",", toll_allow)
			
			for i,part in pairs(parts) do
				if (debug["toll_type"]) then
					freeswitch.consoleLog("NOTICE", "[toll_allow] checking toll allow part " .. part .. "\n")
				end
				
				if ( part == toll_type ) then
					allow = true
					break
				end
			end
		else
			freeswitch.consoleLog("WARNING", "[toll_allow] toll_allow not defined for extension " .. caller .. "\n")
			
			-- Uncomment following line to allow all calls for extensions without toll_allow
			-- allow = true
		end
		
	--hangup not allowed calls
		if ( not allow ) then
			freeswitch.consoleLog("WARNING", "[toll_allow] " .. toll_type .. " call not authorized from " .. caller .. " to " .. called .. " : OUTGOING_CALL_BARRED\n")
			session:hangup("OUTGOING_CALL_BARRED")
		else
			freeswitch.consoleLog("NOTICE", "[toll_allow] " .. toll_type .. " call authorized from " .. caller .. " to " .. called .. "\n")
		end
	end
	
