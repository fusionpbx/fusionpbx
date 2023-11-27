-- Set the API endpoint URL and Bearer token from the argv values
url = argv[1]
token = argv[2]
orig = argv[3]
dest = argv[4]
call_uuid = argv[5]
cert_url = argv[6]

-- check for all variables being set and not nil
if not (url and token and orig and dest and call_uuid and cert_url) then
    -- One or more variables are nil or not set
    freeswitch.consoleLog("warning","[STIR SHAKEN] One or more variables are nil or not set. Cannot proceed with retrieving the Identity header.")
    return
end

-- Set the API endpoint URL and Bearer token
--freeswitch.consoleLog("notice", "[STIR SHAKEN] url: " .. url .. "\n");
--freeswitch.consoleLog("notice", "[STIR SHAKEN] token: " .. token .. "\n");
--freeswitch.consoleLog("notice", "[STIR SHAKEN] orig: " .. orig .. "\n");
--freeswitch.consoleLog("notice", "[STIR SHAKEN] dest: " .. dest .. "\n");

local data = '{"orig":"' .. orig .. '","dest":"' .. dest .. '","call_uuid":"' .. call_uuid .. '","cert_url":"' .. cert_url .. '"}'
--freeswitch.consoleLog("notice", "[STIR SHAKEN] data: " .. data .. "\n");
local headers = "-H 'Content-Type: application/json' -H 'Authorization: Bearer " .. token .. "'"
local timeout = 5

-- Build the curl command with the Authorization header
local curl_command = "curl -X POST " .. headers .. " -d '" .. data .. "' -m " .. timeout .. " " .. url

-- Execute the curl command and capture the response
local handle = io.popen(curl_command)
local response = handle:read("*a")
local success, err, code = handle:close()  -- Get the exit status of the command

if success then
    -- Command succeeded, handle the output
    freeswitch.consoleLog("notice", "[STIR SHAKEN] Curl response: " .. response .. "\n");
    
    if string.len(response) ~= 0 and string.find(response, "shaken") and string.find(response, "alg=ES256") then
    	-- Set the header variable using the session variable API
		session:setVariable("sip_h_Identity", response)
	end
	
else
    -- Command failed, handle the error
    freeswitch.consoleLog("warning","[STIR SHAKEN] Curl Error: Error retrieving Stir Shaken Identity header: " .. err .. ", exit code: " .. code)
end
