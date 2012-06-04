<?php
	//application details
		$apps[$x]['name'] = "SIP Status";
		$apps[$x]['uuid'] = 'caca8695-9ca7-b058-56e7-4ea94ea1c0e8';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Displays system information such as RAM, CPU and Hard Drive information.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'SIP Status';
		$apps[$x]['menu'][0]['uuid'] = 'b7aea9f7-d3cf-711f-828e-46e56e2e5328';
		$apps[$x]['menu'][0]['parent_uuid'] = '0438b504-8613-7887-c420-c837ffb20cb1';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/sip_status/sip_status.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'system_status_sofia_status';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'system_status_sofia_status_profile';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'sip_status_switch_status';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';
?>