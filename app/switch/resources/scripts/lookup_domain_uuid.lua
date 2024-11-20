local Database = require "resources.functions.database";

-- Function to lookup domain UUID by domain code (first 4 digits)
function lookup_domain_uuid_by_code(domain_code)
    -- Connect to the database
    local dbh = Database.new('system')
    local domain_uuid = nil -- Initialize domain_uuid
    local domain_name = nil -- Initialize domain_name

    local sql = string.format([[
        SELECT domain_uuid, domain_name 
        FROM v_domains 
        WHERE domain_name LIKE '%s.%%'
    ]], domain_code) -- Query for domain_name starting with the domain_code

    -- Execute the query and process the result
    dbh:query(sql, function(row)
        domain_uuid = row["domain_uuid"]
        domain_name = row["domain_name"]
        freeswitch.consoleLog("INFO", "[inter_tenant_dialing] Found domain: " .. domain_name .. " with UUID: " .. domain_uuid .. "\n")
    end)

    -- Close the database connection
    dbh:release()

    -- Check if the domain_uuid was found
    if not domain_uuid then
        freeswitch.consoleLog("ERROR", "[inter_tenant_dialing] Domain not found for code: " .. domain_code .. "\n")
    end

    return domain_uuid, domain_name
end

-- Main script execution
if (session:ready()) then
    local domain_code = argv[1] -- Argument passed from dialplan
    local domain_uuid, domain_name = lookup_domain_uuid_by_code(domain_code) -- Fetch domain UUID and name

    if domain_uuid and domain_name then
        -- Set domain_uuid and domain_name as channel variables
        session:execute('set', 'domain_uuid=' .. domain_uuid)
        session:execute('set', 'domain_name=' .. domain_name)
        freeswitch.consoleLog("INFO", "[inter_tenant_dialing] Domain UUID set to: " .. domain_uuid .. "\n")
        freeswitch.consoleLog("INFO", "[inter_tenant_dialing] Domain name set to: " .. domain_name .. "\n")
    else
        -- Handle case where domain_uuid or domain_name is not found
        session:execute('set', 'domain_uuid=none')
        session:execute('set', 'domain_name=none')
        freeswitch.consoleLog("ERROR", "[inter_tenant_dialing] Failed to retrieve domain. Defaulting to none.\n")
    end
end
