function send_presence(uuid, from, state, direction)
    local event = freeswitch.Event('PRESENCE_IN');
    event:addHeader('Unique-ID', uuid);
    event:addHeader('proto', "any");
    event:addHeader('from', from);
    event:addHeader('login', from);
    event:addHeader('event_type', "presence");
    event:addHeader('alt_event_type', "dialog");
    event:addHeader('Presence-Call-Direction', direction or 'outbound');
    event:addHeader('answer-state', state);
    event:fire();
end