<?php
	//application details
		$apps[$x]['name'] = "Account Settings";
		$apps[$x]['uuid'] = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'User account settings can be changed by the user.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Account Settings';
		$apps[$x]['menu'][0]['uuid'] = '4d532f0b-c206-c39d-ff33-fc67d668fb69';
		$apps[$x]['menu'][0]['parent_uuid'] = '02194288-6d56-6d3e-0b1a-d53a2bc10788';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/users/usersupdate.php';
		$apps[$x]['menu'][0]['groups'][] = 'user';
		$apps[$x]['menu'][0]['groups'][] = 'admin';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'user_account_settings_view';
		$apps[$x]['permissions'][0]['groups'][] = 'user';
		$apps[$x]['permissions'][0]['groups'][] = 'admin';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'user_account_settings_edit';
		$apps[$x]['permissions'][1]['groups'][] = 'user';
		$apps[$x]['permissions'][1]['groups'][] = 'admin';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

?>