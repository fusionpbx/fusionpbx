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
--	Alexey Melnichuck <alexeymelnichuck@gmail.com>

--Configuration
	-- Define known template names
		local known_templates = {
			["mobile"       ] = true,
			["landline"     ] = true,
			["international"] = true,
			["tollfree"     ] = true,
			["sharedcharge" ] = true,
			["premium"      ] = true,
			["unknown"      ] = true,
		}

	--Define templates for every toll type for your country
		local templates = {
			IT = {
				{"mobile",        "[35]%d%d%d%d%d%d+"                                                                                                     },
				{"landline",      "0[123456789]%d+"                                                                                                       },
				{"international", "00%d+"                                                                                                                 },
				{"tollfree",      "119|1[3456789]%d|19[24]%d|192[01]%d%d|800%d%d%d%d%d+|803%d%d%d+|456%d%d%d%d%d%d+|11[2345678]|15%d%d|116%d%d%d|196%d%d" },
				{"sharedcharge",  "84[0178]%d%d%d%d+|199%d%d%d%d%d+|178%d%d%d%d%d+|12%d%d|10%d%d%d+|1482|149%d+|4[012]%d+|70%d%d%d%d%d+"                  },
				{"premium",       "89[2459]%d%d%d+|16[456]%d%d%d+|144%d%d%d+|4[346789]%d%d+"                                                              },
				{"unknown",       "%d%d+"                                                                                                                 },
			};
			FR = {
				{"mobile",        "0[67]%d%d%d%d%d%d%d%d"                                                },
				{"landline",      "0[1234589]%d%d%d%d%d%d%d%d"                                           },
				{"international", "00%d+"                                                                },
				{"tollfree",      "15|17|18|112|114|115|116%d%d%d|118%d%d%d|119|19[16]|1[06]%d%d|080%d+" },
				{"sharedcharge",  "081%d+|082[0156]%d+|0884%d+|089[0123789]%d+"                          },
				{"premium",       "%d%d+"                                                                },
				{"unknown",       "%d%d+"                                                                },
			};
			US = {
				{"unknown",       "%d+"},
			};
			RU = {
				{"international", "810%d+|8[89]40%d+|87%d%d%d%d%d%d%d%d%d"};
				{"mobile",        "89%d%d%d%d%d%d%d%d%d"                          };
				{"tollfree",      "8800%d%d%d%d%d%d%d|10[1-9]"                    };
				{"landline",      "8[3-68]%d%d%d%d%d%d%d%d%d"                     };
				{"unknown",       ""                                              };
			};
			ZA = {
                                {"international", "00%d+"         };
                                {"mobile",        "0[6-8]%d%d%d%d%d%d%d%d"                      };
                                {"landline",      "0[1-5]%d%d%d%d%d%d%d%d"                      };
                                {"unknown",       ""                                            };
                        };

		}

	--Set to true to allow all calls for extensions without toll_allow
		local ACCEPT_EMPTY_TOLL_ALLOW = false

	--debug
		debug["toll_type"] = false

	require "resources.functions.explode";

--create the api object and get variables
	local api = freeswitch.API()
	local uuid = argv[2]

	if not uuid or uuid == "" then
		return
	end

	local function hungup()
		session:hangup("OUTGOING_CALL_BARRED")
	end

	local function log(level, msg)
		freeswitch.consoleLog(level, "[toll_allow] " .. msg .. "\n")
	end

	local function logf(level, ...)
		return log(level, string.format(...))
	end

	local function trace(type, ...)
		if debug[type] then log(...) end
	end

	local function tracef(type, ...)
		if debug[type] then logf(...) end
	end

	local function channel_variable(uuid, name)
		local result = api:executeString("uuid_getvar " .. uuid .. " " .. name)

		tracef("toll_type", "NOTICE", "channel_variable %s - %s", name, result)

		if result:sub(1, 4) == '-ERR' then return nil end
		return result
	end

	local function template_match(prefix, template, called)
		local parts = explode("|", template)

		for index,part in ipairs(parts) do
			local pattern = "^" .. prefix .. part .. "$"
			if ( string.match(called, pattern) ~= nil ) then
				return pattern
			end
		end
	end

	local function get_toll_type(prefix, templates, called)
		for _,params in ipairs(templates) do
			local label, template = params[1], params[2]
			if not known_templates[label] then
				logf("WARNING", "unknown template name: %s in country template array", label)
			end

			trace("toll_type", "NOTICE", "checking toll type " .. label .. " template: " .. template)

			local pattern = template_match(prefix, template, called)
			if pattern then
				trace("toll_type", "NOTICE", "destination number " .. called .. " matches " .. label .. " pattern: " .. pattern)
				return label
			end
		end
	end

	local function is_undef(str)
		return (not str) or (#str == 0) or (str == "_undef_")
	end

	local called     = channel_variable(uuid, "destination_number") or ""
	local caller     = channel_variable(uuid, "caller_id_number")   or ""
	local prefix     = channel_variable(uuid, "outbound_prefix")    or ""
	local country    = channel_variable(uuid, "default_country")    or ""
	local toll_allow = channel_variable(uuid, "toll_allow")         or ""

	if (debug["toll_type"]) then
		logf("NOTICE", "called: %s", called)
		logf("NOTICE", "prefix: %s", prefix)
		logf("NOTICE", "country: %s", country)
		logf("NOTICE", "tollAllow: %s", toll_allow)
	end

	if is_undef(toll_allow) then
		if ACCEPT_EMPTY_TOLL_ALLOW then
			logf("NOTICE", "unknown call authorized from %s to %s", caller, called)
			return
		end
		logf("WARNING", "unknown call not authorized from %s to %s : OUTGOING_CALL_BARRED", caller, called)
		return hungup()
	end

	if is_undef(prefix) then
		prefix = ""
	end

	if is_undef(country) then
		log("WARNING", "undefined country")
		return
	end

	local templates = templates[country]
	if not templates then
		log("WARNING", "undefined templates")
		return
	end

--set toll_type
	local toll_type = get_toll_type(prefix, templates, called) or "unknown"

	log("NOTICE", "toll type: " .. toll_type)

	local parts = explode(",", toll_allow)

	for i,part in ipairs(parts) do
		if not known_templates[part] then
			logf("WARNING", "unknown toll_allow name: %s in extension", part)
		end

		tracef("toll_type", "NOTICE", "checking toll allow part " .. part)
		if ( part == toll_type ) then
			logf("NOTICE", "%s call authorized from %s to %s", toll_type, caller, called)
			return
		end
	end

	logf("WARNING", "%s call not authorized from %s to %s : OUTGOING_CALL_BARRED", toll_type, caller, called)
	return hungup()
