<?php

	$view['name'] = "view_call_recordings";
	$view['version'] = "20250919";
	$view['description'] = "Show the call recordings details from the call detail records database.";
	$view['sql'] = "    select domain_uuid, xml_cdr_uuid as call_recording_uuid, \n";
	$view['sql'] .= "	caller_id_name, caller_id_number, caller_destination, destination_number, \n";
	$view['sql'] .= "	record_name as call_recording_name, record_path as call_recording_path, \n";
	$view['sql'] .= "	record_transcription as call_recording_transcription, \n";
	$view['sql'] .= "	duration as call_recording_length, start_stamp as call_recording_date, direction as call_direction \n";
	$view['sql'] .= "	from v_xml_cdr \n";
	$view['sql'] .= "	where record_name is not null \n";
	$view['sql'] .= "	and record_path is not null \n";
	$view['sql'] .= "	order by start_stamp desc \n";