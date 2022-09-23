<?php

	//application details
		$apps[$x]['name'] = "Number Translation";
		$apps[$x]['uuid'] = "6ad54de6-4909-11e7-a919-92ebcb67fe33";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "0.1";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Manage mod_translation";
		$apps[$x]['description']['en-gb'] = "Manage mod_translation";
		$apps[$x]['description']['ar-eg'] = "إدارة mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['de-at'] = "Verwalte mod_translate";
		$apps[$x]['description']['de-ch'] = "Verwalte mod_translate";
		$apps[$x]['description']['de-de'] = "Verwalte mod_translate";
		$apps[$x]['description']['es-cl'] = "Administrar mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['es-mx'] = "Administrar mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['fr-ca'] = "Gérer mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['fr-fr'] = "Gérer mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['he-il'] = "נהל את mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['it-it'] = "Gestisci mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['nl-nl'] = "Beheer mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['pl-pl'] = "Zarządzaj mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['pt-br'] = "Gerenciar mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['pt-pt'] = "Gerenciar mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['ro-ro'] = "Gestionați mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['ru-ru'] = "Управление mod_translate";
		$apps[$x]['description']['sv-se'] = "Hantera mod_translate";  //translation provided by Google Translate
		$apps[$x]['description']['uk-ua'] = "управління mod_translate";  //translation provided by Google Translate
		$apps[$x]['minimum_version'] = "4.3.2";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "number_translation_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "6ad55156-4909-11e7-a919-92ebcb67fe33";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "number_translation_edit";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "6ad556c4-4909-11e7-a919-92ebcb67fe33";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "number_translation_add";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "6ad5585e-4909-11e7-a919-92ebcb67fe33";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "number_translation_delete";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "6ad555d4-4909-11e7-a919-92ebcb67fe33";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'number_translation_detail_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'number_translation_detail_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'number_translation_detail_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'number_translation_detail_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'number_translation_detail_all';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;

	//Number Translations
		$y = 3;
		$apps[$x]['db'][$y]['table']['name'] = 'v_number_translations';
		$apps[$x]['db'][$y]['table']['parent'] = '';
		$z = 0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the number translation name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['toggle'] = ['true','false'];
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the number translation enabled.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the number translation description.';
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

	//Number Translation Details
		$y = 4;
		$apps[$x]['db'][$y]['table']['name'] = 'v_number_translation_details';
		$apps[$x]['db'][$y]['table']['parent'] = 'v_number_translations';
		$z = 0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_detail_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_number_translations';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'number_translation_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_detail_regex';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the regular expression that identifies the number to replace.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_detail_replace';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the number translation detail replace.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'number_translation_detail_order';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Select the rule order.';
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
