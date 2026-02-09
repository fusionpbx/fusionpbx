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
--	Copyright (C) 2010-2025
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Push Gateway Helper Function
--	Called from failure_handler when extension is not registered
--	Sends push notifications to wake mobile apps for incoming calls
--

local push_gateway = {}

-- Configuration - CHANGE THIS URL FOR YOUR ENVIRONMENT
push_gateway.url = "https://api.davincitechsolutions.com/api/v1/internal/push-gateway/incoming-call"

-- Trigger push notification via Laravel
function push_gateway.trigger(session, uuid, caller_number, caller_name, extension, domain_name, domain_uuid)
	freeswitch.consoleLog("NOTICE", "[push_gateway] Triggering for extension: " .. tostring(extension) .. "\n")

	-- Escape caller name for JSON
	local safe_caller_name = (caller_name or "Unknown"):gsub('"', '\\"')

	-- Build JSON payload
	local json_data = '{"call_uuid":"' .. (uuid or "") .. '","caller_number":"' .. (caller_number or "") .. '","caller_name":"' .. safe_caller_name .. '","extension_number":"' .. (extension or "") .. '","domain":"' .. (domain_name or "") .. '","domain_uuid":"' .. (domain_uuid or "") .. '"}'

	freeswitch.consoleLog("INFO", "[push_gateway] URL: " .. push_gateway.url .. "\n")
	freeswitch.consoleLog("DEBUG", "[push_gateway] Payload: " .. json_data .. "\n")

	-- Use curl to call webhook
	local cmd = 'curl -s -X POST -H "Content-Type: application/json" -m 10 -d \'' .. json_data .. '\' "' .. push_gateway.url .. '" 2>&1'
	local handle = io.popen(cmd)
	local result = handle:read("*a")
	handle:close()

	freeswitch.consoleLog("INFO", "[push_gateway] Response: " .. tostring(result) .. "\n")

	-- Check if push was sent
	if result and result:find('"success":true') then
		freeswitch.consoleLog("NOTICE", "[push_gateway] Push notification sent successfully\n")
		return true
	elseif result and result:find('"error":"No mobile devices"') then
		freeswitch.consoleLog("NOTICE", "[push_gateway] No mobile devices registered\n")
		return false
	else
		freeswitch.consoleLog("WARNING", "[push_gateway] Unexpected response: " .. tostring(result) .. "\n")
		return false
	end
end

-- Notify Laravel when caller hangs up (to dismiss CallKit on mobile)
function push_gateway.notify_hangup(uuid)
	local hangup_url = push_gateway.url:gsub("incoming%-call", "caller-hangup")
	local json_data = '{"call_uuid":"' .. (uuid or "") .. '","hangup_cause":"ORIGINATOR_CANCEL"}'

	freeswitch.consoleLog("INFO", "[push_gateway] Notifying hangup for: " .. tostring(uuid) .. "\n")

	local cmd = 'curl -s -X POST -H "Content-Type: application/json" -m 5 -d \'' .. json_data .. '\' "' .. hangup_url .. '" 2>&1'
	local handle = io.popen(cmd)
	local result = handle:read("*a")
	handle:close()

	freeswitch.consoleLog("DEBUG", "[push_gateway] Hangup response: " .. tostring(result) .. "\n")
end

return push_gateway
