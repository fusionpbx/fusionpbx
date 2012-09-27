<?php
	//application details
		$apps[$x]['name'] = 'Domains';
		$apps[$x]['guid'] = 'b31e723a-bf70-670c-a49b-470d2a232f71';
		$apps[$x]['category'] = 'Core';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Manage a single domain or multiple domains for multi-tenant.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Domains';
		$apps[$x]['menu'][0]['uuid'] = '4fa7e90b-6d6c-12d4-712f-62857402b801';
		$apps[$x]['menu'][0]['parent_uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/core/domains/domains.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'domain_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'domain_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'domain_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'domain_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'domain_select';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;

		$apps[$x]['permissions'][$y]['name'] = 'domain_setting_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'domain_setting_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'domain_setting_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'domain_setting_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'id';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'serial';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'integer';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'INT NOT NULL AUTO_INCREMENT';
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = 'true';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the domain name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the description.';

		$y = 1; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_domain_settings';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'id';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'serial';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'integer';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'INT NOT NULL AUTO_INCREMENT';
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = 'true';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_setting_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_setting_category';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the category.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_setting_subcategory';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the subcategory.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_setting_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_setting_value';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the value.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_setting_enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_setting_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
?>