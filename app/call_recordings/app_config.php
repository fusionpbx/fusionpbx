<?php

	//application details
		$apps[$x]['name'] = 'Call Recordings';
		$apps[$x]['uuid'] = '56165644-598d-4ed8-be01-d960bcb8ffed';
		$apps[$x]['category'] = '';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '1.1';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = 'Call Recordings';
		$apps[$x]['description']['en-gb'] = 'Call Recordings';
		$apps[$x]['description']['nl-nl'] = 'Gespreksopnamen';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_add';
		//$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_edit';
		//$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_all';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_play';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_download';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_transcribe';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y = 0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "95cb740e-e377-4852-8894-06441c61e78b";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "call_recordings";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "filesystem_retention_days";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "90";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Number of days to retain the maintenance logs in the database.";
?>
