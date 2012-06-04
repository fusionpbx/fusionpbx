<?php
	//application details
		$apps[$x]['name'] = "Call Center Active";
		$apps[$x]['uuid'] = '3f159f62-ca2d-41b8-b3f0-c5519cebbc5a';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Shows active calls, and agents in the call center queue.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Active Call Center';
		$apps[$x]['menu'][0]['uuid'] = '7fb0dd87-e984-9980-c512-2c76b887aeb2';
		$apps[$x]['menu'][0]['parent_uuid'] = '0438b504-8613-7887-c420-c837ffb20cb1';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/call_center_active/v_call_center_queue.php';
		$apps[$x]['menu'][0]['groups'][] = 'admin';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'call_center_active_view';
		$apps[$x]['permissions'][0]['groups'][] = 'admin';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

?>