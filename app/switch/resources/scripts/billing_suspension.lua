-- Set the API endpoint URL and Bearer token from the argv values

domain_uuid = argv[1]

if not domain_uuid then
    freeswitch.consoleLog("error", "[check-suspended] Missing domain_uuid argument")
    return
end

local Database = require "resources.functions.database"

local dbh = Database.new('system')

local sql = [[
    select domain_setting_value from v_domain_settings where domain_setting_category = 'company' and domain_uuid = :domain_uuid and domain_setting_subcategory = 'billing_suspension'
]]

local params = {domain_uuid = domain_uuid}
freeswitch.consoleLog("notice", "[check-suspended] sql: " .. sql)
local response = dbh:first_row(sql, params)
dbh:release()

if response then
    freeswitch.consoleLog("notice", "[check-suspended] value: " .. response.domain_setting_value)
    -- session:setVariable("billing_suspended", response.domain_setting_value)
    stream:write(response.domain_setting_value)
else
    freeswitch.consoleLog("notice", "[check-suspended] No result found for domain_uuid: " .. domain_uuid)
end

--session:execute("set", "billing_suspended=" .. response.domain_setting_value .. "");

--session:execute("set", "billing_suspended=true" .. response.domain_setting_value .. "");


--session:execute("export", "billing_suspended=" .. response.domain_setting_value .. "");

 --   session:destroy();
