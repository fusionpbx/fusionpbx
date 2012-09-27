<?php
	//application details
		$apps[$x]['name'] = 'Default Settings';
		$apps[$x]['uuid'] = '2c2453c0-1bea-4475-9f44-4d969650de09';
		$apps[$x]['category'] = 'Core';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Default settings that apply to all domains.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Default Settings';
		$apps[$x]['menu'][0]['uuid'] = '834b2739-9e99-4345-9b0b-7ec3ca332b67';
		$apps[$x]['menu'][0]['parent_uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/core/default_settings/default_settings.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'default_setting_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'default_setting_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'default_setting_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'default_setting_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_default_settings';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'default_setting_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'default_setting_category';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the category.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'default_setting_subcategory';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the subcategory.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'default_setting_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'default_setting_value';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the value.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'default_setting_enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'default_setting_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
?>