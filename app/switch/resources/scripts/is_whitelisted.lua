--get the variables
if (session:ready()) then
    --session:setAutoHangup(false);
    domain_uuid = session:getVariable("domain_uuid");
    call_direction = session:getVariable("call_direction");
    caller_id_name = session:getVariable("caller_id_name");
    caller_id_number = session:getVariable("caller_id_number");
    destination_number = session:getVariable("destination_number");
    context = session:getVariable("context");
    call_block = session:getVariable("call_block");
    extension_uuid = session:getVariable("extension_uuid");
    hold_music = session:getVariable("hold_music");
end