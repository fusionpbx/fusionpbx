<?php
	//application details
		$apps[$x]['name'] = 'FAX Logs';
		$apps[$x]['uuid'] = '02ffd6e8-c7d5-4f56-812e-5c1b4fd3fe02';
		$apps[$x]['category'] = '';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = '';

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = 'FAX Logs';
		$apps[$x]['menu'][0]['uuid'] = 'f91b1476-e7e9-40f5-a181-2f3e1e5bc16b';
		$apps[$x]['menu'][0]['parent_uuid'] = 'fd29e39c-c936-f5fc-8e2b-611681b266b5';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/fax_logs/fax_logs.php';
		//$apps[$x]['menu'][0]['groups'][] = 'user';
		//$apps[$x]['menu'][0]['groups'][] = 'admin';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'fax_log_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'user';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'fax_log_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'user';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'fax_log_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;

?>