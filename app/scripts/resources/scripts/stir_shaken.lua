-- Set the API endpoint URL and Bearer token from the argv values
url = argv[1];
token = argv[2];
orig = argv[3];
dest = argv[4];
call_uuid = argv[5];

-- Set the API endpoint URL and Bearer token
freeswitch.consoleLog("notice", "[STIR SHAKEN] url: " .. url .. "\n");
freeswitch.consoleLog("notice", "[STIR SHAKEN] token: " .. token .. "\n");
freeswitch.consoleLog("notice", "[STIR SHAKEN] orig: " .. orig .. "\n");
freeswitch.consoleLog("notice", "[STIR SHAKEN] dest: " .. dest .. "\n");

local data = '{"orig":"' .. orig .. '","dest":"' .. dest .. '","call_uuid":"' .. call_uuid .. '"}'
freeswitch.consoleLog("notice", "[STIR SHAKEN] data: " .. data .. "\n");
local headers = "-H 'Content-Type: application/json' -H 'Authorization: Bearer " .. token .. "'"

-- Build the curl command with the Authorization header
local curl_command = "curl -X POST " .. headers .. " -d '" .. data .. "' " .. url

-- Execute the curl command and capture the response
local handle = io.popen(curl_command)
local response = handle:read("*a")
local success, err, code = handle:close()  -- Get the exit status of the command

if success then
    -- Command succeeded, handle the output
    freeswitch.consoleLog("notice", "[STIR SHAKEN] response: " .. response .. "\n");
    -- Set the header variable using the session variable API
	session:setVariable("sip_h_Identity", response)
else
    -- Command failed, handle the error
    freeswitch.consoleLog("warning","Error retrieving Stir Shaken Identity header: " .. err .. ", exit code: " .. code)
end
