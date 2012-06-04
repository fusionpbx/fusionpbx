<?php
	//application details
		$apps[$x]['name'] = "Log Viewer";
		$apps[$x]['uuid'] = '159a2724-77e1-2782-9366-db08b3750e06';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Display the switch logs.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Log Viewer';
		$apps[$x]['menu'][0]['uuid'] = '781ebbec-a55a-9d60-f7bb-f54ab2ee4e7e';
		$apps[$x]['menu'][0]['parent_uuid'] = '0438b504-8613-7887-c420-c837ffb20cb1';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/log_viewer/log_viewer.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'log_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][1]['name'] = 'log_download';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][2]['name'] = 'log_path_view';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';
?>