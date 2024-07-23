
--get the channel variables
if (session:ready()) then
	domain_uuid = session:getVariable("domain_uuid");
	uuid = session:getVariable("uuid");
	event_subclass = session:getVariable("event_subclass");
	json_data = session:getVariable("json_data");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");
end

--initialize and send the event
local event = freeswitch.Event("CUSTOM", event_subclass);
event:addHeader('domain_uuid', domain_uuid);
event:addHeader('uuid', uuid);
event:addHeader('caller_id_name', caller_id_name);
event:addHeader('caller_id_number', caller_id_number);
if (json_data) then
	event:addBody(json_data);
end
event:fire();
