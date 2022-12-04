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

?>
