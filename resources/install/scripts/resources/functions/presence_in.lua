require "resources.functions.split"

local function turn_lamp(on, user, uuid)
	local userid, domain = split_first(user, "@", true)
	local proto
	if userid then
		proto, userid = split_first(userid, "+", true)
		if userid then
			user = userid  .. "@" .. domain
		end
	end

	local event = freeswitch.Event("PRESENCE_IN");
	event:addHeader("proto", proto or "sip");
	event:addHeader("event_type", "presence");
	event:addHeader("alt_event_type", "dialog");
	event:addHeader("Presence-Call-Direction", "outbound");
	event:addHeader("from", user);
	event:addHeader("login", user);
	event:addHeader("unique-id", uuid);
	event:addHeader("status", "Active (1 waiting)");
	if on then
		event:addHeader("answer-state", "confirmed");
		event:addHeader("rpid", "unknown");
		event:addHeader("event_count", "1");
	else
		event:addHeader("answer-state", "terminated");
	end
	event:fire();
end

return {
	turn_lamp = turn_lamp;
}