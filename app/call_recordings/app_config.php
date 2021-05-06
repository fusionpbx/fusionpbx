<?php

	//application details
		$apps[$x]['name'] = 'Call Recordings';
		$apps[$x]['uuid'] = '56165644-598d-4ed8-be01-d960bcb8ffed';
		$apps[$x]['category'] = '';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
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
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_play';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'call_recording_download';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';

	//Call Recordings
		$y = 2;
		$apps[$x]['db'][$y]['table']['name'] = 'v_call_recordings';
		$apps[$x]['db'][$y]['table']['parent'] = '';
		$z = 0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'call_recording_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'domain_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'call_recording_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'call_recording_path';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'call_recording_length';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'call_recording_date';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'call_direction';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'call_recording_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'call_recording_base64';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = '';
		$z++;

?>
