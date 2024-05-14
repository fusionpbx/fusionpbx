-- Set the API endpoint URL and Bearer token from the argv values
domain_uuid = argv[1]

local Database = require "resources.functions.database";

--check if the session is ready
if not session:ready() then return end

local dbh = Database.new('system');

local sql = "select domain_setting_value ";
sql = sql .. " from  v_domain_settings";
sql = sql .. " where domain_setting_category = 'company' ";
sql = sql .. " AND domain_uuid = :domain_uuid ";
sql = sql .. " AND domain_setting_subcategory = 'billing_suspension' ";

local params = {domain_uuid=domain_uuid};
freeswitch.consoleLog("notice", "[BILLING SUSPENSION] sql: " .. sql);
local response = dbh:first_row(sql, params);


freeswitch.consoleLog("notice", "[BILLING SUSPENSION] value: " .. response.domain_setting_value);
session:setVariable("billing_suspended", response.domain_setting_value);

--session:execute("set", "billing_suspended=" .. response.domain_setting_value .. "");

--session:execute("set", "billing_suspended=true" .. response.domain_setting_value .. "");


--session:execute("export", "billing_suspended=" .. response.domain_setting_value .. "");

 --   session:destroy();
