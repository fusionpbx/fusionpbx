<?php
	//application details
		$apps[$x]['name'] = "Click to Call";
		$apps[$x]['uuid'] = 'eb221c9b-cb13-5542-9140-dff924816dc4';
		$apps[$x]['category'] = 'Switch';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Originate calls with a URL.';

	//menu details
		//$apps[$x]['menu'][0]['title']['en'] = 'Click to Call';
		//$apps[$x]['menu'][0]['uuid'] = 'f862556f-9ddd-2697-fdf4-bed08ec63aa5';
		//$apps[$x]['menu'][0]['parent_uuid'] = 'fd29e39c-c936-f5fc-8e2b-611681b266b5';
		//$apps[$x]['menu'][0]['category'] = 'internal';
		//$apps[$x]['menu'][0]['path'] = '/app/click_to_call/click_to_call.php';
		//$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'click_to_call_view';
		$apps[$x]['permissions'][0]['groups'][] = 'user';
		$apps[$x]['permissions'][0]['groups'][] = 'admin';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'click_to_call_call';
		$apps[$x]['permissions'][1]['groups'][] = 'user';
		$apps[$x]['permissions'][1]['groups'][] = 'admin';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';
?>