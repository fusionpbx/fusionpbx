extension_uuid = session:getVariable("extension_uuid");
destination_number = session:getVariable("destination_number");

if extension_uuid == nil then
    return
end

-- freeswitch.consoleLog("notice", "[check-ext-suspension] extension_uuid: " .. extension_uuid)
-- freeswitch.consoleLog("notice", "[check-ext-suspension] destination_number: " .. destination_number)

local Database = require "resources.functions.database"
local dbh = Database.new('system')

local sql = [[
    select v_extensions.extension, extension_advanced_settings.suspended
    from v_extensions
    left join extension_advanced_settings  on v_extensions.extension_uuid = extension_advanced_settings.extension_uuid
    where v_extensions.extension_uuid = :extension_uuid
]]

local params = {extension_uuid = extension_uuid}
local response = dbh:first_row(sql, params)
dbh:release()

-- freeswitch.consoleLog("notice", "[check-ext-suspension] suspended: " .. response.suspended)
-- freeswitch.consoleLog("notice", "[check-ext-suspension] extension: " .. response.extension)


--inbound
    if response.suspended == 't' and response.extension == destination_number then
        freeswitch.consoleLog("warning", "[check-ext-suspension] Extension " .. response.extension .. " is suspended. Ending the call")

        if session:ready() then
            session:execute("playback", "silence_stream://1000")
        end
        if session:ready() then
            session:streamFile("ivr/ivr-no_route_destination.wav")
        end
        if session:ready() then
            session:sleep(1000) -- Wait for 1 second (1000 milliseconds)
        end

        if session:ready() then
            freeswitch.consoleLog("notice", "[check-suspended] HANGUP ")
            session:hangup("CALL_REJECTED")        
        end
    end


--outbound
if response.suspended == 't' and response.extension ~= destination_number then
    freeswitch.consoleLog("warning", "[check-ext-suspension] Extension " .. response.extension .. " is suspended. Ending the call")

    if session:ready() then
        session:execute("playback", "silence_stream://1000")
    end
    if session:ready() then
        session:streamFile("ivr/ivr-phone_not_make_external_calls.wav")
    end
    if session:ready() then
        session:streamFile("ivr/ivr-please_contact.wav")
    end
    if session:ready() then
        session:streamFile("ivr/ivr-the_billing_department.wav")
    end
    if session:ready() then
        session:streamFile("currency/and.wav")
    end

    if session:ready() then
        session:streamFile("ivr/ivr-speak_to_a_customer_service_representative.wav")
    end

    if session:ready() then
        session:sleep(1000) -- Wait for 1 second (1000 milliseconds)
    end

    if session:ready() then
        freeswitch.consoleLog("notice", "[check-suspended] HANGUP ")
        session:hangup("CALL_REJECTED")        
    end

end
