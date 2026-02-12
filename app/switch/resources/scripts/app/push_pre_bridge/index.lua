--
--	FusionPBX
--	Version: MPL 1.1
--
--	Push Pre-Bridge Script
--	Runs BEFORE the local_extension bridge for internal calls.
--	If the destination extension is not registered (or has a stale
--	WebSocket contact), sends a push notification and waits for
--	the extension to register before the bridge attempt.
--
--	Dialplan continues to local_extension (890) after this script.
--

--load push gateway module
	local push_gateway = nil
	pcall(function()
		push_gateway = require "resources.functions.push_gateway"
	end)

	if (push_gateway == nil) then
		freeswitch.consoleLog("WARNING", "[push_pre_bridge] push_gateway module not available\n")
		return
	end

--get session variables
	if (session == nil or not session:ready()) then
		return
	end

	local destination_number = session:getVariable("destination_number")
	local domain_name = session:getVariable("domain_name")
	local domain_uuid = session:getVariable("domain_uuid")

	if (destination_number == nil or domain_name == nil) then
		return
	end

--check if push was already triggered (prevent loops from transfer)
	local already_triggered = session:getVariable("push_pre_bridge_done")
	if (already_triggered == "true") then
		freeswitch.consoleLog("DEBUG", "[push_pre_bridge] Already triggered, skipping\n")
		return
	end
	session:setVariable("push_pre_bridge_done", "true")

--helpers
	local function trim(value)
		if (value == nil) then
			return ""
		end
		return (tostring(value):gsub("^%s+", ""):gsub("%s+$", ""))
	end

	local function get_contact(api_handle, extension, domain)
		local command = "sofia_contact */" .. extension .. "@" .. domain
		return trim(api_handle:executeString(command))
	end

	local function has_active_registration(api_handle, extension, domain)
		local command = "sofia status profile internal reg " .. extension .. "@" .. domain
		local output = trim(api_handle:executeString(command))
		if (output == "") then
			return false
		end
		if (string.find(output, "Total items returned:%s*0")) then
			return false
		end
		if (string.find(output, "No registrations")) then
			return false
		end
		if (string.find(output, "error/")) then
			return false
		end
		if (string.find(output, "Total items returned:%s*[1-9]")) then
			return true
		end
		if (string.find(output, extension .. "@" .. domain)) then
			return true
		end
		return false
	end

--check the registration status of the destination
	local api = freeswitch.API()
	local sofia_contact_result = get_contact(api, destination_number, domain_name)
	local registration_exists = has_active_registration(api, destination_number, domain_name)

	freeswitch.consoleLog("INFO", "[push_pre_bridge] sofia_contact for " .. destination_number .. ": " .. tostring(sofia_contact_result) .. "\n")
	freeswitch.consoleLog("INFO", "[push_pre_bridge] registration_exists for " .. destination_number .. ": " .. tostring(registration_exists) .. "\n")

--determine if push is needed
	local needs_push = false
	local push_reason = ""

	if (sofia_contact_result == nil or sofia_contact_result == "" or sofia_contact_result == "error/user_not_registered") then
		needs_push = true
		push_reason = "not_registered"
	elseif (sofia_contact_result ~= nil) then
		-- Check for WebSocket contacts (likely stale when app is backgrounded on iOS)
		if (string.find(sofia_contact_result, "transport=ws") or string.find(sofia_contact_result, "transport=wss")) then
			needs_push = true
			push_reason = "websocket_contact"
		end
	end

	-- Treat stale contacts with no active registration as offline.
	if (not needs_push and not registration_exists) then
		needs_push = true
		push_reason = "stale_contact_no_registration"
	end

	if (not needs_push) then
		freeswitch.consoleLog("DEBUG", "[push_pre_bridge] Extension " .. destination_number .. " is registered normally, no push needed\n")
		return
	end

	freeswitch.consoleLog("NOTICE", "[push_pre_bridge] Sending wake push for " .. destination_number .. " reason=" .. push_reason .. "\n")

--send push notification (wake only - backend won't store pending_call)
	local uuid = session:getVariable("uuid") or ""
	local caller_id_number = session:getVariable("caller_id_number") or ""
	local caller_id_name = session:getVariable("caller_id_name") or ""

	local push_success = false
	pcall(function()
		push_success = push_gateway.wake(
			session,
			uuid,
			caller_id_number,
			caller_id_name,
			destination_number,
			domain_name,
			domain_uuid
		)
	end)

	if (not push_success) then
		freeswitch.consoleLog("NOTICE", "[push_pre_bridge] Push failed or no mobile devices - continuing to normal bridge\n")
		return
	end

	freeswitch.consoleLog("NOTICE", "[push_pre_bridge] Push sent for " .. destination_number .. " - waiting for registration\n")

--play ringback while waiting
	session:execute("ring_ready")

--wait for the extension to register (up to 15 seconds, check every 2 seconds)
	local max_wait = 12
	local check_interval = 2
	local waited = 0

	while (waited < max_wait) do
		session:execute("sleep", tostring(check_interval * 1000))
		waited = waited + check_interval

		-- Check if caller hung up
		if (not session:ready()) then
			freeswitch.consoleLog("NOTICE", "[push_pre_bridge] Caller hung up while waiting for registration\n")
			return
		end

		-- Check if extension is now registered
		if (has_active_registration(api, destination_number, domain_name)) then
			freeswitch.consoleLog("NOTICE", "[push_pre_bridge] Extension " .. destination_number .. " registered after " .. waited .. "s - continuing to bridge\n")
			return
		end
	end

	freeswitch.consoleLog("NOTICE", "[push_pre_bridge] Extension " .. destination_number .. " did not register within " .. max_wait .. "s - continuing to failure_handler path\n")
	-- Let the dialplan continue to local_extension -> bridge -> failure_handler
	-- The failure_handler will do the full push+park+device-ready flow
