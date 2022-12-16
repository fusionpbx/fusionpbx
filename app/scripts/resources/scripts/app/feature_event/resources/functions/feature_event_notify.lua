local feature_event_notify = {}

function feature_event_notify.get_db_values(user, domain_name)
		--get the domain uuid from the host
			local Database = require "resources.functions.database";
			local dbh = Database.new('system');
			
			local sql = "select * from v_domains ";
			sql = sql .. "where domain_name = :domain_name ";
			local params = {domain_name = domain_name};
		--	if (debug["sql"]) then
		--		freeswitch.consoleLog("notice", "[feature_event] " .. sql .. "; params:" .. json.encode(params) .. "\n");
		--	end
			dbh:query(sql, params, function(row)
				domain_uuid = row.domain_uuid;
			end);
		
			--get extension information
				local sql = "select * from v_extensions ";
				sql = sql .. "where domain_uuid = :domain_uuid ";
				sql = sql .. "and (extension = :extension or number_alias = :extension) ";
				local params = {domain_uuid = domain_uuid, extension = user};
			--	if (debug["sql"]) then
			--		freeswitch.consoleLog("notice", "[feature_event] " .. sql .. "; params:" .. json.encode(params) .. "\n");
			--	end
				
				dbh:query(sql, params, function(row)
					extension_uuid                          = row.extension_uuid;
					extension                               = row.extension;
					number_alias                            = row.number_alias or '';
					accountcode                             = row.accountcode;
					follow_me_uuid                          = row.follow_me_uuid;
					do_not_disturb                          = row.do_not_disturb;
					forward_all_enabled                     = row.forward_all_enabled;
					forward_all_destination                 = row.forward_all_destination;
					forward_busy_enabled                    = row.forward_busy_enabled;
					forward_busy_destination                = row.forward_busy_destination;
					forward_no_answer_enabled               = row.forward_no_answer_enabled;
					forward_no_answer_destination           = row.forward_no_answer_destination;
					forward_user_not_registered_enabled     = row.forward_user_not_registered_enabled;
					forward_user_not_registered_destination = row.forward_user_not_registered_destination;
					toll_allow                              = row.toll_allow
					call_timeout                            = row.call_timeout
					--freeswitch.consoleLog("NOTICE", "[feature_event] extension "..row.extension.."\n");
					--freeswitch.consoleLog("NOTICE", "[feature_event] accountcode "..row.accountcode.."\n");
				end);
	
		--set some defaults if values in database are NULL
			if (forward_all_enabled == "") then forward_all_enabled = "false"; end
			--if (forward_all_destination == "") then forward_all_destination = nil; end
			if (forward_busy_enabled == "") then forward_busy_enabled = "false"; end
			if (forward_no_answer_enabled == "") then forward_no_answer_enabled = "false"; end
			if (do_not_disturb == "") then do_not_disturb = "false"; end
			if (call_timeout == "") then call_timeout = "30"; end
			
			return do_not_disturb, forward_all_enabled, forward_all_destination, forward_busy_enabled, forward_busy_destination, forward_no_answer_enabled, forward_no_answer_destination, call_timeout
end

---@return table, nil
function feature_event_notify.get_profiles(user, domain)
		--includes
		require "resources.functions.trim"

		local account = user.."@"..domain
		--create the api object
		api = freeswitch.API();
		local sofia_contact = trim(api:executeString("sofia_contact */"..account));
		--get all profiles for the user account
		local profile_iterator = string.gmatch(sofia_contact, "sofia/([^,]+)/[^,]+");

		--remove any duplicates and check if we have any profiles
		local unique_profiles = {}
		local has_profile = false
		for profile in profile_iterator do
			has_profile = true
			unique_profiles[profile] = 1
		end

		-- return nil if we have no profiles
		if not has_profile then return nil end

		return unique_profiles
end


function feature_event_notify.dnd(user, host, sip_profiles, do_not_disturb)
	--set the event and send it to each profile
	for sip_profile, _ in pairs(sip_profiles) do
		local event = freeswitch.Event("SWITCH_EVENT_PHONE_FEATURE")
		event:addHeader("profile", sip_profile)
		event:addHeader("user", user)
		event:addHeader("host", host)
		event:addHeader("device", "")
		event:addHeader("Feature-Event", "DoNotDisturbEvent")
		event:addHeader("doNotDisturbOn", do_not_disturb)
		--freeswitch.consoleLog("notice","[events] " .. event:serialize("xml") .. "\n");
		freeswitch.msleep(300);
		event:fire()
	end
end

function feature_event_notify.forward_immediate(user, host, sip_profiles, forward_immediate_enabled, forward_immediate_destination)
	--set the event and send it to each profile
	for sip_profile, _ in pairs(sip_profiles) do
		local event = freeswitch.Event("SWITCH_EVENT_PHONE_FEATURE")
		event:addHeader("profile", sip_profile)
		event:addHeader("user", user)
		event:addHeader("host", host)
		event:addHeader("device", "")
		event:addHeader("Feature-Event", "ForwardingEvent")
		event:addHeader("forward_immediate_enabled", forward_immediate_enabled)
		event:addHeader("forward_immediate", forward_immediate_destination);
		freeswitch.consoleLog("notice","[events] " .. event:serialize("xml") .. "\n");
		freeswitch.msleep(300);
		event:fire()
	end
end

function feature_event_notify.forward_busy(user, host, sip_profiles, forward_busy_enabled, forward_busy_destination)
	--set the event and send it to each profile
	for sip_profile, _ in pairs(sip_profiles) do
		local event = freeswitch.Event("SWITCH_EVENT_PHONE_FEATURE")
		event:addHeader("profile", sip_profile)
		event:addHeader("user", user)
		event:addHeader("host", host)
		event:addHeader("device", "")
		event:addHeader("Feature-Event", "ForwardingEvent")
		event:addHeader("forward_busy", forward_busy_destination)
		event:addHeader("forward_busy_enabled", forward_busy_enabled)
		freeswitch.msleep(300);
		event:fire()
	end
end

function feature_event_notify.forward_no_answer(user, host, sip_profiles, forward_no_answer_enabled, forward_no_answer_destination, ring_count)
	--set the event and send it to each profile
	for sip_profile, _ in pairs(sip_profiles) do
		local event = freeswitch.Event("SWITCH_EVENT_PHONE_FEATURE")
		event:addHeader("profile", sip_profile)
		event:addHeader("user", user)
		event:addHeader("host", host)
		event:addHeader("device", "")
		event:addHeader("Feature-Event", "ForwardingEvent")
		event:addHeader("forward_no_answer", forward_no_answer_destination)
		event:addHeader("forward_no_answer_enabled", forward_no_answer_enabled)
		event:addHeader("ringCount", ring_count)
		freeswitch.msleep(300);
		event:fire()
	end
end

function feature_event_notify.init(user, host, sip_profiles, forward_immediate_enabled, forward_immediate_destination, forward_busy_enabled, forward_busy_destination, forward_no_answer_enabled, forward_no_answer_destination, ring_count, do_not_disturb)
	--set the event and send it to each profile
	for sip_profile, _ in pairs(sip_profiles) do
		local event = freeswitch.Event("SWITCH_EVENT_PHONE_FEATURE")
		event:addHeader("profile", sip_profile)
		event:addHeader("user", user)
		event:addHeader("host", host)
		event:addHeader("device", "")
		event:addHeader("Feature-Event", "init")
		event:addHeader("forward_immediate_enabled", forward_immediate_enabled)
		event:addHeader("forward_immediate", forward_immediate_destination);		
		event:addHeader("forward_busy", forward_busy_destination)
		event:addHeader("forward_busy_enabled", forward_busy_enabled)
		event:addHeader("Feature-Event", "ForwardingEvent")
		event:addHeader("forward_no_answer", forward_no_answer_destination)
		event:addHeader("forward_no_answer_enabled", forward_no_answer_enabled)
		event:addHeader("ringCount", ring_count)		
		event:addHeader("Feature-Event", "DoNotDisturbEvent")
		event:addHeader("doNotDisturbOn", do_not_disturb)
		freeswitch.msleep(300);
		event:fire()
	end
end

return feature_event_notify
