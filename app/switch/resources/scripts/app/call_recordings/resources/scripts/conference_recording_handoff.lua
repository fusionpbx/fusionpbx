-- Preserve a participant recording while handing a call to a conference
-- recorder. The preflight runs synchronously before the conference app; the
-- worker runs after the room exists. Every failure before the final exact-path
-- stop deliberately leaves the participant recorder running.

require "resources.functions.config"
require "resources.functions.mkdir"
require "resources.functions.file_exists"

local Database = require "resources.functions.database"
local api = freeswitch.API()
local worker = argv[1] == "--handoff"

local function valid_uuid(value)
	return type(value) == "string" and string.match(value, "^%x%x%x%x%x%x%x%x%-%x%x%x%x%-%x%x%x%x%-%x%x%x%x%-%x%x%x%x%x%x%x%x%x%x%x%x$") ~= nil
end

local function valid_token(value)
	return type(value) == "string" and string.match(value, "^[%w_.@-]+$") ~= nil
end

local function valid_path(value)
	return type(value) == "string" and string.match(value, "^/[%w%._/%-]+$") ~= nil
end

local function xml_unescape(value)
	value = string.gsub(value or "", "&amp;", "&")
	value = string.gsub(value, "&quot;", '"')
	value = string.gsub(value, "&apos;", "'")
	value = string.gsub(value, "&lt;", "<")
	return string.gsub(value, "&gt;", ">")
end

local function command(value)
	local result = api:executeString(value)
	if type(result) ~= "string" or result == "" or string.match(result, "^%s*%-ERR") then return nil end
	return result
end

local function diagnostic(channel_uuid, outcome)
	if valid_uuid(channel_uuid) then
		api:executeString("uuid_setvar " .. channel_uuid .. " recording_conference_handoff_outcome " .. outcome)
	end
	freeswitch.consoleLog("notice", "[recording_coordinator] conference handoff " .. outcome .. " channel=" .. tostring(channel_uuid) .. "\n")
end

local function channel_recorders(channel_uuid)
	local exists = command("uuid_exists " .. channel_uuid)
	if not exists then return nil, "channel_status_unknown" end
	if string.match(exists, "^%s*false%s*$") then return {}, nil end
	if not string.match(exists, "^%s*true%s*$") then return nil, "channel_status_unknown" end
	local bugs = command("uuid_buglist " .. channel_uuid)
	if not bugs or not string.match(bugs, "^%s*<media%-bugs[ >]") or not string.match(bugs, "</media%-bugs>%s*$") then
		return nil, "buglist_unknown"
	end
	local paths = {}
	for bug in string.gmatch(bugs, "<media%-bug[^>]*>(.-)</media%-bug>") do
		if string.match(bug, "<function>%s*session_record%s*</function>") then
			local path = xml_unescape(string.match(bug, "<target>(.-)</target>"))
			if not valid_path(path) then return nil, "recording_path_unknown" end
			table.insert(paths, path)
		end
	end
	return paths, nil
end

local function attribute(attributes, name)
	return xml_unescape(string.match(attributes, name .. '%s*=%s*"(.-)"'))
end

local function conference_status(conference_name)
	local response = command("conference '" .. conference_name .. "' xml_list")
	response = response and string.gsub(response, "^%s*<%?xml[^>]*%?>%s*", "", 1) or nil
	if not response or not string.match(response, "^%s*<conferences[ >]") or not string.match(response, "</conferences>%s*$") then
		return nil, "conference_status_unknown"
	end
	for attributes, body in string.gmatch(response, "<conference%s+([^>]*)>(.-)</conference>") do
		if attribute(attributes, "name") == conference_name then
			local occurrence = attribute(attributes, "uuid")
			if not valid_uuid(occurrence) then return nil, "conference_occurrence_unknown" end
			local paths = {}
			local members = {}
			for member_attributes, member in string.gmatch(body, "<member%s+([^>]*)>(.-)</member>") do
				if attribute(member_attributes, "type") == "recording_node" then
					local path = xml_unescape(string.match(member, "<record_path[^>]*>(.-)</record_path>"))
					if not valid_path(path) then return nil, "conference_recording_path_unknown" end
					table.insert(paths, path)
				elseif attribute(member_attributes, "type") == "caller" then
					local member_uuid = xml_unescape(string.match(member, "<uuid[^>]*>(.-)</uuid>"))
					if not valid_uuid(member_uuid) then return nil, "conference_member_unknown" end
					members[member_uuid] = true
				end
			end
			return {occurrence = occurrence, paths = paths, members = members}, nil
		end
	end
	return nil, "conference_not_found"
end

local function enabled_for(dbh, domain_uuid, scope_destination)
	local values = {}
	local ok = dbh:query([[select domain_setting_subcategory, domain_setting_value
		from v_domain_settings
		where domain_uuid = :domain_uuid
		and domain_setting_category = 'call_recordings'
		and domain_setting_subcategory in ('conference_recording_handoff_enabled', 'recording_coordinator_conference', 'recording_segment_links_enabled')
		and domain_setting_enabled = 'true']], {domain_uuid = domain_uuid}, function(row)
		values[row.domain_setting_subcategory] = row.domain_setting_value
	end)
	return ok and values.conference_recording_handoff_enabled == "true"
		and values.recording_segment_links_enabled == "true"
		and values.recording_coordinator_conference == scope_destination
end

local function history_links(dbh, domain_uuid, cdr_uuid, occurrence, participant_path, conference_path)
	local params = {
		domain_uuid = domain_uuid,
		conversation_id = cdr_uuid,
		xml_cdr_uuid = cdr_uuid,
		conference_uuid = occurrence,
		participant_path = participant_path,
		conference_path = conference_path,
		participant_link_uuid = api:executeString("create_uuid"),
		conference_link_uuid = api:executeString("create_uuid")
	}
	if not valid_uuid(params.participant_link_uuid) or not valid_uuid(params.conference_link_uuid) then return false end
	local began = dbh:query("begin")
	if not began then return false end
	local first = dbh:query([[insert into v_recording_segment_links
		(recording_segment_link_uuid, domain_uuid, conversation_id, xml_cdr_uuid, conference_uuid, recording_path, recording_kind, insert_date)
		values (:participant_link_uuid, :domain_uuid, :conversation_id, :xml_cdr_uuid, :conference_uuid, :participant_path, 'preconference', current_timestamp)
		on conflict do nothing]], params)
	local second = first and dbh:query([[insert into v_recording_segment_links
		(recording_segment_link_uuid, domain_uuid, conversation_id, xml_cdr_uuid, conference_uuid, recording_path, recording_kind, insert_date)
		values (:conference_link_uuid, :domain_uuid, :conversation_id, :xml_cdr_uuid, :conference_uuid, :conference_path, 'conference', current_timestamp)
		on conflict do nothing]], params)
	if not first or not second or not dbh:query("commit") then
		dbh:query("rollback")
		return false
	end

	-- A successful INSERT reply is not enough. Re-read both exact associations
	-- before crossing the participant stop boundary.
	local found_participant = false
	local found_conference = false
	local verified = dbh:query([[select recording_path, recording_kind
		from v_recording_segment_links
		where domain_uuid = :domain_uuid and xml_cdr_uuid = :xml_cdr_uuid
		and conference_uuid = :conference_uuid
		and ((recording_path = :participant_path and recording_kind = 'preconference')
		or (recording_path = :conference_path and recording_kind = 'conference'))]], params, function(row)
		if row.recording_kind == "preconference" and row.recording_path == participant_path then found_participant = true end
		if row.recording_kind == "conference" and row.recording_path == conference_path then found_conference = true end
	end)
	return verified and found_participant and found_conference
end

local function history_links_saved(dbh, domain_uuid, cdr_uuid, occurrence, participant_path, conference_path)
	local found_participant = false
	local found_conference = false
	local verified = dbh:query([[select recording_path, recording_kind
		from v_recording_segment_links
		where domain_uuid = :domain_uuid and xml_cdr_uuid = :xml_cdr_uuid
		and conference_uuid = :conference_uuid
		and ((recording_path = :participant_path and recording_kind = 'preconference')
		or (recording_path = :conference_path and recording_kind = 'conference'))]], {
		domain_uuid = domain_uuid,
		xml_cdr_uuid = cdr_uuid,
		conference_uuid = occurrence,
		participant_path = participant_path,
		conference_path = conference_path
	}, function(row)
		if row.recording_kind == "preconference" and row.recording_path == participant_path then found_participant = true end
		if row.recording_kind == "conference" and row.recording_path == conference_path then found_conference = true end
	end)
	return verified and found_participant and found_conference
end

if not worker then
	local channel_uuid = argv[1] or (session and session:get_uuid()) or ""
	local domain_uuid = argv[2] or (session and session:getVariable("domain_uuid")) or ""
	local conference_name = argv[3] or ""
	local scope_destination = argv[4] or ""
	if not valid_uuid(channel_uuid) or not valid_uuid(domain_uuid) or not valid_token(conference_name) or not valid_token(scope_destination) then return end
	local dbh = Database.new("system")
	if not dbh:connected() or not enabled_for(dbh, domain_uuid, scope_destination) then return end

	local candidates = {}
	local candidate_uuids = {channel_uuid}
	local bridge_uuid = session and session:getVariable("bridge_uuid") or ""
	if valid_uuid(bridge_uuid) and bridge_uuid ~= channel_uuid then table.insert(candidate_uuids, bridge_uuid) end
	for _, uuid in ipairs(candidate_uuids) do
		local paths, reason = channel_recorders(uuid)
		if not paths then diagnostic(channel_uuid, reason); return end
		for _, path in ipairs(paths) do table.insert(candidates, {uuid = uuid, path = path}) end
	end
	if #candidates ~= 1 then diagnostic(channel_uuid, #candidates == 0 and "participant_recorder_absent" or "participant_recorder_ambiguous"); return end

	local cdr_uuid = string.match(candidates[1].path, "/(%x%x%x%x%x%x%x%x%-%x%x%x%x%-%x%x%x%x%-%x%x%x%x%-%x%x%x%x%x%x%x%x%x%x%x%x)%.[^/]+$") or channel_uuid
	local record_ext = (session and session:getVariable("record_ext")) or "wav"
	if not string.match(record_ext, "^[%w]+$") then record_ext = "wav" end
	local script_path = argv[5] or ""
	if not valid_path(script_path) then diagnostic(channel_uuid, "scripts_path_unknown"); return end
	local scheduled = command("sched_api +3 none lua " .. script_path .. " --handoff " .. candidates[1].uuid .. " " .. domain_uuid .. " " .. cdr_uuid .. " " .. conference_name .. " " .. candidates[1].path .. " " .. record_ext .. " " .. scope_destination)
	if scheduled then diagnostic(channel_uuid, "scheduled") else diagnostic(channel_uuid, "schedule_failed") end
	return
end

local channel_uuid = argv[2] or ""
local domain_uuid = argv[3] or ""
local cdr_uuid = argv[4] or ""
local conference_name = argv[5] or ""
local participant_path = argv[6] or ""
local record_ext = argv[7] or "wav"
local scope_destination = argv[8] or ""
if not valid_uuid(channel_uuid) or not valid_uuid(domain_uuid) or not valid_uuid(cdr_uuid)
	or not valid_token(conference_name) or not valid_path(participant_path) or not string.match(record_ext, "^[%w]+$")
	or not valid_token(scope_destination) then return end

-- Re-check after the scheduler delay so rollback cancels an already queued
-- worker before it starts a room recorder or stops a participant recorder.
local dbh = Database.new("system")
if not dbh:connected() or not enabled_for(dbh, domain_uuid, scope_destination) then return end

local room, reason = conference_status(conference_name)
if not room then diagnostic(channel_uuid, reason); return end
local domain_name = string.match(conference_name, "@(.+)$")
local recordings_dir = freeswitch.getGlobalVariable("recordings_dir")
if not valid_token(domain_name) or not valid_path(recordings_dir) then diagnostic(channel_uuid, "conference_path_unknown"); return end
local directory = recordings_dir .. "/" .. domain_name .. "/archive/" .. os.date("%Y") .. "/" .. os.date("%b") .. "/" .. os.date("%d")
local conference_path = directory .. "/" .. room.occurrence .. "." .. record_ext
if not valid_path(conference_path) then diagnostic(channel_uuid, "conference_path_unknown"); return end

local matched = false
for _, path in ipairs(room.paths) do if path == conference_path then matched = true end end
if not matched then
	-- Never reuse an arbitrary profile auto-record path and never overwrite a
	-- prior file. Every room occurrence owns one UUID-named managed target.
	if file_exists(conference_path) then diagnostic(channel_uuid, "conference_path_collision"); return end
	mkdir(directory)
	if not command("conference '" .. conference_name .. "' record " .. conference_path) then
		diagnostic(channel_uuid, "conference_start_failed")
		return
	end
end

-- FreeSWITCH can acknowledge the recorder just before publishing its recording
-- node. Poll briefly, but still require the exact occurrence-owned path.
local room_verified = false
for attempt = 1, 10 do
	room, reason = conference_status(conference_name)
	if room then
		for _, path in ipairs(room.paths) do if path == conference_path then room_verified = true end end
	end
	if room_verified then break end
	if attempt < 10 then freeswitch.msleep(100) end
end
if not room_verified then diagnostic(channel_uuid, "conference_recorder_unverified"); return end

if not history_links(dbh, domain_uuid, cdr_uuid, room.occurrence, participant_path, conference_path) then
	diagnostic(channel_uuid, "history_link_failed")
	return
end

local stop_reply = command("uuid_record " .. channel_uuid .. " stop " .. participant_path)
if not stop_reply then
	-- recording_follow_transfer moves the media bug but FreeSWITCH 1.10 does
	-- not move the path-keyed private pointer used by an exact-path stop. The
	-- recording-only fallback is allowed only after repeating every ownership,
	-- conference, persistence, and preservation check on this one channel.
	local fallback_room, fallback_reason = conference_status(conference_name)
	if not fallback_room then diagnostic(channel_uuid, fallback_reason); return end
	if fallback_room.occurrence ~= room.occurrence then diagnostic(channel_uuid, "participant_fallback_wrong_occurrence"); return end
	if not fallback_room.members[channel_uuid] then diagnostic(channel_uuid, "participant_fallback_not_member"); return end
	local fallback_conference_verified = false
	for _, path in ipairs(fallback_room.paths) do
		if path == conference_path then fallback_conference_verified = true end
	end
	if not fallback_conference_verified then diagnostic(channel_uuid, "participant_fallback_conference_recorder_unverified"); return end
	if not history_links_saved(dbh, domain_uuid, cdr_uuid, room.occurrence, participant_path, conference_path) then
		diagnostic(channel_uuid, "participant_fallback_links_unverified")
		return
	end
	if not file_exists(participant_path) then diagnostic(channel_uuid, "participant_fallback_recording_unpreserved"); return end
	local fallback_paths, fallback_path_reason = channel_recorders(channel_uuid)
	if not fallback_paths then diagnostic(channel_uuid, fallback_path_reason); return end
	if #fallback_paths < 1 then diagnostic(channel_uuid, "participant_fallback_recorder_absent"); return end
	for _, path in ipairs(fallback_paths) do
		if path ~= participant_path then diagnostic(channel_uuid, "participant_fallback_recorder_unsafe"); return end
	end
	if not command("uuid_record " .. channel_uuid .. " stop all") then
		diagnostic(channel_uuid, "participant_fallback_stop_failed")
		return
	end
	stop_reply = "recording_only_fallback"
end
local remaining, stop_reason = channel_recorders(channel_uuid)
if not remaining then diagnostic(channel_uuid, stop_reason); return end
if #remaining ~= 0 then diagnostic(channel_uuid, "participant_stop_unverified"); return end
diagnostic(channel_uuid, stop_reply == "recording_only_fallback" and "complete_recording_only_fallback" or "complete")
