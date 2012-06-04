<?php
	//application details
		$apps[$x]['name'] = "Sign Up";
		$apps[$x]['uuid'] = 'd308e9c6-d907-5ba7-b3be-6d3e09cf01aa';
		$apps[$x]['category'] = 'System';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Allows customers on the internet to signup for a user account.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Sign Up';
		$apps[$x]['menu'][0]['uuid'] = 'a8f49f02-9bfb-65ff-4cd3-85dc3354e4c1';
		$apps[$x]['menu'][0]['parent_uuid'] = '';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/users/usersupdate.php';
		$apps[$x]['menu'][0]['groups'][] = 'disabled';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'signup';
?>
