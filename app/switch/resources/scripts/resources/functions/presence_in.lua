require "resources.functions.split"
local api = require "resources.functions.api"
local log = require "resources.functions.log".presence

local function turn_lamp(on, user, uuid)
	log.debugf('turn_lamp: %s - %s(%s)', tostring(user), tostring(on), type(on))

	-- Parse user in format: [proto+]extension@domain
	-- split_first returns: (part_before_separator, part_after_separator)
	local before_at, domain = split_first(user, "@", true)

	-- If no @ found, before_at equals user (split_first returns single value)
	if before_at == user and not domain then
		-- No @ in user string - invalid format
		log.warningf('turn_lamp: invalid user format: %s', tostring(user))
		return
	end

	-- Determine proto and extension from the part before @
	-- e.g., "voicemail+1234" -> proto="voicemail", ext="1234"
	-- e.g., "1234" -> proto="sip" (default), ext="1234"
	local proto, ext
	local plus_pos = string.find(before_at, "+", 1, true)
	if plus_pos then
		proto = string.sub(before_at, 1, plus_pos - 1)
		ext = string.sub(before_at, plus_pos + 1)
	else
		proto = "sip"
		ext = before_at
	end

	-- Reconstruct user as extension@domain (proto is used in the event header)
	user = ext .. "@" .. domain

	uuid = uuid or api:execute('create_uuid')

	local event = freeswitch.Event("PRESENCE_IN");
	event:addHeader("proto", proto);
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

	-- log.debug(event:serialize())

	event:fire();
end

return {
	turn_lamp = turn_lamp;
}
