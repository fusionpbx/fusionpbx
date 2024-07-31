
--get the channel variables
if (session:ready()) then
	domain_uuid = session:getVariable("domain_uuid");
	domain_name = session:getVariable("domain_name");
	uuid = session:getVariable("uuid");
	event_subclass = session:getVariable("event_subclass");
	json_data = session:getVariable("json_data");
	caller_id_name = session:getVariable("caller_id_name");
	caller_id_number = session:getVariable("caller_id_number");
	destination_number = session:getVariable("destination_number");
	message = session:getVariable("message");
end

--set default values if empty
if (not domain_uuid) then domain_uuid = ''; end
if (not domain_name) then domain_name = ''; end
if (not event_subclass) then event_subclass = ''; end
if (not json_data) then json_data = ''; end
if (not caller_id_name) then caller_id_name = ''; end
if (not caller_id_number) then caller_id_number = ''; end
if (not message) then message = ''; end

--initialize and send the event
local event = freeswitch.Event("CUSTOM", event_subclass);
event:addHeader('domain_uuid', domain_uuid);
event:addHeader('domain_name', domain_name);
event:addHeader('uuid', uuid);
event:addHeader('event_subclass', event_subclass);
event:addHeader('caller_id_name', caller_id_name);
event:addHeader('caller_id_number', caller_id_number);
event:addHeader('destination_number', destination_number);
event:addHeader('message', message);
if (json_data) then
	event:addBody(json_data);
end
event:fire();
