<?php
	//application details
		$apps[$x]['name'] = "Registrations";
		$apps[$x]['uuid'] = '5d9e7cd7-629e-3553-4cf5-f26e39fefa39';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Displays registrations from endpoints.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Registrations';
		$apps[$x]['menu'][0]['uuid'] = '17dbfd56-291d-8c1c-bc43-713283a9dd5a';
		$apps[$x]['menu'][0]['parent_uuid'] = '0438b504-8613-7887-c420-c837ffb20cb1';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/registrations/v_status_registrations.php?show_reg=1&profile=internal';
		$apps[$x]['menu'][0]['groups'][] = 'admin';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'registrations_domain';
		$apps[$x]['permissions'][0]['groups'][] = 'admin';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'registrations_all';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

?>