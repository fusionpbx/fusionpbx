<?php

	$view['name'] = "view_call_recordings";
	$view['version'] = "20251210";
	$view['description'] = "Show the call recordings details from the call detail records database.";
	$view['sql'] = "	SELECT c.domain_uuid, c.xml_cdr_uuid AS call_recording_uuid, \n";
	$view['sql'] .= "		caller_id_name, caller_id_number, caller_destination, destination_number, \n";
	$view['sql'] .= "		record_name AS call_recording_name, record_path AS call_recording_path, \n";
	$view['sql'] .= "		t.transcript_json AS call_recording_transcription, \n";
	$view['sql'] .= "		duration AS call_recording_length, start_stamp AS call_recording_date, direction AS call_direction \n";
	$view['sql'] .= "	FROM v_xml_cdr as c \n";
	$view['sql'] .= "	LEFT JOIN v_xml_cdr_transcripts as t ON c.xml_cdr_uuid = t.xml_cdr_uuid \n";
	$view['sql'] .= "	WHERE record_name IS NOT NULL \n";
	$view['sql'] .= "	AND record_path is not null \n";
	$view['sql'] .= "	AND hangup_cause <> 'LOSE_RACE' \n";
	$view['sql'] .= "	ORDER BY start_stamp desc \n";

