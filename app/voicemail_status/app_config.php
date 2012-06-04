<?php
	//application details
		$apps[$x]['name'] = "Voicemail Status";
		$apps[$x]['uuid'] = '9ecd085e-8c0e-92f6-e727-e90f6bb57773';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Shows which extensions have voicemails and how many.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Voicemail Status';
		$apps[$x]['menu'][0]['uuid'] = 'ff4ccd3d-e295-7875-04b4-54eb0c74adc5';
		$apps[$x]['menu'][0]['parent_uuid'] = '0438b504-8613-7887-c420-c837ffb20cb1';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/voicemail_status/v_voicemail.php';
		$apps[$x]['menu'][0]['groups'][] = 'admin';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'voicemail_status_view';
		$apps[$x]['permissions'][0]['groups'][] = 'admin';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'voicemail_status_delete';
		$apps[$x]['permissions'][1]['groups'][] = 'admin';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

?>