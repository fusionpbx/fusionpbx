<?php

	//application details
		$apps[$x]['name'] = 'Emergency Logs';
		$apps[$x]['uuid'] = 'de63b1ae-7750-11ee-b3a5-005056a27559';
		$apps[$x]['category'] = 'Emergency Logs';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '1.0.1';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['contact_url'] = 'http://austen-it.com';
		$apps[$x]['description']['en-us'] = '';
		$apps[$x]['description']['es-mx'] = '';
		$apps[$x]['description']['de'] = '';
		$apps[$x]['description']['de-ch'] = '';
		$apps[$x]['description']['de-at'] = '';
		$apps[$x]['description']['fr'] = '';
		$apps[$x]['description']['fr-ca'] = '';
		$apps[$x]['description']['fr-ch'] = '';
		$apps[$x]['description']['pt-pt'] = '';
		$apps[$x]['description']['pt-br'] = '';

	//default settings
		$y=0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "9317ddfd-6cb1-4294-9c57-4061dde66fe4";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "dialplan";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "emergency_email_address";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Email address used to send emergency call notifications.";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "emergency_logs_view";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "23a6fc8a-77f1-11ee-88b0-005056a27559";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "emergency_logs_view_all";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

	//schema details
		$z=0;
		$apps[$x]['db'][$y]['table']['name'] = "v_emergency_logs";
		$apps[$x]['db'][$y]['table']['parent'] = "";
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'emergency_log_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$apps[$x]['db'][$y]['fields'][$z]['description'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'domain_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'extension';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'numeric';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['description'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'event';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "insert_date";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "insert_user";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "update_date";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "update_user";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";

?>
