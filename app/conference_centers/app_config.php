<?php
	//application details
		$apps[$x]['name'] = 'Conference Center';
		$apps[$x]['uuid'] = '8d083f5a-f726-42a8-9ffa-8d28f848f10e';
		$apps[$x]['category'] = 'Switch';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Conferences Centers allows one or more audio and video conference rooms.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Conference Center';
		$apps[$x]['menu'][0]['title']['pt-pt'] = 'Conferencias';
		$apps[$x]['menu'][0]['uuid'] = '95f88726-4706-43f0-b52b-9504a0b8046f';
		$apps[$x]['menu'][0]['parent_uuid'] = 'fd29e39c-c936-f5fc-8e2b-611681b266b5';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/conference_centers/conference_centers.php';
		$apps[$x]['menu'][0]['groups'][] = 'user';
		$apps[$x]['menu'][0]['groups'][] = 'admin';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'conference_center_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'user';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'conference_center_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'conference_center_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$apps[$x]['permissions'][$y]['groups'][] = 'user';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'conference_center_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_conference_centers';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'domain_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'conference_center_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'dialplan_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_dialplans';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'dialplan_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'conference_center_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the conference center name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'conference_center_extension';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the conference center extension number.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'conference_center_profile';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Conference Profile is a collection of settings for the conference.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'conference_center_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the center description.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'conference_center_enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enable or disable the conference center.';

		/*
		$y = 1; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_conference_center_users';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'conference_user_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'conference_center_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_conference_centers';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'conference_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'user_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_users';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'user_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		*/

?>