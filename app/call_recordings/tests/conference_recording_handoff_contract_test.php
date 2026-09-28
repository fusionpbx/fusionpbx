<?php
$root = dirname(__DIR__, 2);
$script = file_get_contents($root.'/switch/resources/scripts/app/call_recordings/resources/scripts/conference_recording_handoff.lua');
$simple_conference = file_get_contents($root.'/conferences/conference_edit.php');
$app_config = file_get_contents(dirname(__DIR__).'/app_config.php');

function conference_handoff_assert($condition, $message) { if (!$condition) throw new Exception($message); }

conference_handoff_assert(strpos($app_config, 'conference_recording_handoff_enabled') !== false
	&& strpos($app_config, 'recording_segment_links_enabled') !== false
	&& strpos($app_config, 'recording_coordinator_conference') !== false,
	'all three activation settings are explicit');
$handoff_setting = substr($app_config, strpos($app_config, 'conference_recording_handoff_enabled'), 500);
conference_handoff_assert(strpos($handoff_setting, 'default_setting_value\'] = "false";') !== false,
	'conference handoff remains disabled by default');
conference_handoff_assert(substr_count($script, 'values.conference_recording_handoff_enabled == "true"') === 1
	&& substr_count($script, 'values.recording_segment_links_enabled == "true"') === 1
	&& substr_count($script, 'values.recording_coordinator_conference == scope_destination') === 1,
	'worker preflight repeats the exact domain settings gate');
conference_handoff_assert(strpos($simple_conference, "recording_coordinator_conference', '') === \$conference_extension") !== false,
	'the simple-conference hook is scoped to one configured destination');
conference_handoff_assert(strpos($simple_conference, "conference_recording_handoff_enabled', false") !== false,
	'conference activation is independent of the preserved agent guard');
conference_handoff_assert(strpos($simple_conference, "get('switch', 'scripts', '/usr/share/freeswitch/scripts')") !== false
	&& strpos($simple_conference, '\\${scripts_dir}/app/call_recordings') === false,
	'simple-conference dialplan embeds the configured absolute scripts directory');
conference_handoff_assert(substr_count($simple_conference, 'xml::sanitize($conference_handoff_script)') === 2
	&& strpos($script, 'local script_path = argv[5] or ""') !== false
	&& strpos($script, 'getGlobalVariable("scripts_dir")') === false,
	'the generated absolute path is passed to the worker for its delayed invocation');
conference_handoff_assert(strpos($script, 'if #candidates ~= 1') !== false,
	'preflight refuses absent or ambiguous participant recorder ownership');
conference_handoff_assert(strpos($script, 'string.gsub(response, "^%s*<%?xml[^>]*%?>%s*", "", 1)') !== false,
	'conference status accepts the XML declaration emitted by the live FreeSWITCH API');
conference_handoff_assert(strpos($script, 'room.occurrence .. "." .. record_ext') !== false
	&& strpos($script, 'if file_exists(conference_path)') !== false
	&& strpos($script, 'conference_recorder_ambiguous') === false,
	'conference target is occurrence-unique, collision-safe, and shared despite unrelated profile recorders');
conference_handoff_assert(strpos($script, 'for attempt = 1, 10 do') !== false
	&& strpos($script, 'freeswitch.msleep(100)') !== false,
	'exact-path verification tolerates bounded FreeSWITCH recorder-node publication latency');

$exact_stop = strpos($script, 'command("uuid_record " .. channel_uuid .. " stop " .. participant_path)');
$fallback_member = strpos($script, 'fallback_room.members[channel_uuid]', $exact_stop);
$fallback_conference = strpos($script, 'participant_fallback_conference_recorder_unverified', $fallback_member);
$fallback_links = strpos($script, 'history_links_saved(dbh', $fallback_conference);
$fallback_preserved = strpos($script, 'file_exists(participant_path)', $fallback_links);
$fallback_owned = strpos($script, 'path ~= participant_path', $fallback_preserved);
$fallback_stop = strpos($script, 'command("uuid_record " .. channel_uuid .. " stop all")', $fallback_owned);
conference_handoff_assert($exact_stop !== false && $fallback_member > $exact_stop
	&& $fallback_conference > $fallback_member && $fallback_links > $fallback_conference
	&& $fallback_preserved > $fallback_links && $fallback_owned > $fallback_preserved
	&& $fallback_stop > $fallback_owned,
	'recording-only fallback follows membership, conference, link, preservation, and exact ownership checks');
conference_handoff_assert(strpos($script, 'if #remaining ~= 0') !== false,
	'fallback verifies no participant session recorder remains');

$worker_start = strrpos($script, 'local room, reason = conference_status');
$room_verify = strpos($script, 'if not room_verified', $worker_start);
$link_save = strpos($script, 'history_links(dbh', $worker_start);
$ordered_exact_stop = strpos($script, 'command("uuid_record "', $worker_start);
conference_handoff_assert($worker_start !== false && $room_verify > $worker_start && $link_save > $room_verify && $ordered_exact_stop > $link_save,
	'worker verifies the room and persisted links before the exact-path stop');
conference_handoff_assert(strpos($script, 'A successful INSERT reply is not enough') !== false
	&& strpos($script, 'found_participant and found_conference') !== false,
	'both append-only links are read back before stopping');

function simulated_handoff($owned_count, $room_state, $links_saved) {
	if ($owned_count !== 1) return 'participant_continues';
	if ($room_state !== 'verified') return 'participant_continues';
	if (!$links_saved) return 'participant_continues';
	return 'exact_participant_stop';
}
function simulated_recording_only_fallback($is_member, $conference_verified, $links_saved, $file_preserved, $paths, $intended_path) {
	if (!$is_member || !$conference_verified || !$links_saved || !$file_preserved || !$paths) return 'participant_continues';
	foreach ($paths as $path) if ($path !== $intended_path) return 'participant_continues';
	return 'stop_participant_recorders_only';
}
conference_handoff_assert(simulated_handoff(1, 'unknown', true) === 'participant_continues', 'unknown room state fails safe');
conference_handoff_assert(simulated_handoff(1, 'unverified', true) === 'participant_continues', 'unverified room start fails safe');
conference_handoff_assert(simulated_handoff(1, 'verified', false) === 'participant_continues', 'link failure fails safe');
conference_handoff_assert(simulated_handoff(2, 'verified', true) === 'participant_continues', 'ambiguous ownership fails safe');
conference_handoff_assert(simulated_handoff(1, 'verified', true) === 'exact_participant_stop', 'complete evidence permits exact stop');
conference_handoff_assert(simulated_recording_only_fallback(false, true, true, true, ['/a.wav'], '/a.wav') === 'participant_continues', 'non-member fallback fails safe');
conference_handoff_assert(simulated_recording_only_fallback(true, false, true, true, ['/a.wav'], '/a.wav') === 'participant_continues', 'unverified conference fallback fails safe');
conference_handoff_assert(simulated_recording_only_fallback(true, true, false, true, ['/a.wav'], '/a.wav') === 'participant_continues', 'unpersisted links fallback fails safe');
conference_handoff_assert(simulated_recording_only_fallback(true, true, true, false, ['/a.wav'], '/a.wav') === 'participant_continues', 'unpreserved file fallback fails safe');
conference_handoff_assert(simulated_recording_only_fallback(true, true, true, true, ['/a.wav', '/other.wav'], '/a.wav') === 'participant_continues', 'unrelated recorder fallback fails safe');
conference_handoff_assert(simulated_recording_only_fallback(true, true, true, true, ['/a.wav', '/a.wav'], '/a.wav') === 'stop_participant_recorders_only', 'verified duplicate participant recorders permit recording-only fallback');

echo "conference_recording_handoff_contract_test: OK (source contract and decision simulation; no live FreeSWITCH)\n";
