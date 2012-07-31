<?php
	//application details
		$apps[$x]['name'] = 'Ring Groups';
		$apps[$x]['uuid'] = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
		$apps[$x]['category'] = 'Switch';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'A tool to call multiple extensions.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Ring Groups';
		$apps[$x]['menu'][0]['uuid'] = 'b30f085f-3ec6-2819-7e62-53dfba5cb8d5';
		$apps[$x]['menu'][0]['parent_uuid'] = 'fd29e39c-c936-f5fc-8e2b-611681b266b5';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/ring_groups/ring_groups.php';
		//$apps[$x]['menu'][0]['groups'][] = 'user';
		//$apps[$x]['menu'][0]['groups'][] = 'admin';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'ring_group_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'ring_group_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'ring_group_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'ring_group_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_ring_groups';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key'] = 'foreign';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_extension';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the extension.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_context';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the context.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_cid_name_prefix';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the caller ID prefix.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_strategy';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Select the strategy.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_timeout_sec';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'numeric';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the timeout in seconds.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_timeout_app';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Select the timeout destination.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_timeout_data';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Select the timeout destination.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Select enable or disable the ring group.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the description.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'dialplan_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
	//schema details
		$y = 1; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_extension_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['table'] = 'v_ring_group_extensions';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key'] = 'foreign';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ring_group_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key'] = 'foreign';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'extension_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Select the extension.';
		$z++;
?>