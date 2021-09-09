<?php

	//application details
		$apps[$x]['name'] = 'sofia_global_settings';
		$apps[$x]['uuid'] = '240c25a3-a2cf-44ea-a300-0626eca5b945';
		$apps[$x]['category'] = '';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = '';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'sofia_global_setting_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'sofia_global_setting_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'sofia_global_setting_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'sofia_global_setting_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'sofia_global_setting_all';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;

	//sofia_global_settings
		$y = 0;
		$apps[$x]['db'][$y]['table']['name'] = 'v_sofia_global_settings';
		$apps[$x]['db'][$y]['table']['parent'] = '';
		$z = 0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'sofia_global_setting_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'global_setting_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search_by'] = '';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the global setting name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'global_setting_value';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search_by'] = '';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the global setting value.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'global_setting_enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'boolean';
		$apps[$x]['db'][$y]['fields'][$z]['toggle'] = ['true','false'];
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the global setting enabled.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'global_setting_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search_by'] = '';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the global setting description.';
		$z++;

?>
