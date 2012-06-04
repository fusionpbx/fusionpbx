<?php
	//application details
		$apps[$x]['name'] = "Menu Manager";
		$apps[$x]['uuid'] = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
		$apps[$x]['category'] = 'Core';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'The menu can be customized using this tool.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Menu Manager';
		$apps[$x]['menu'][0]['uuid'] = 'da3a9ab4-c28e-ea8d-50cc-e8405ac8e76e';
		$apps[$x]['menu'][0]['parent_uuid'] = '02194288-6d56-6d3e-0b1a-d53a2bc10788';
		$apps[$x]['menu'][0]['category'] = 'internal';
		//$apps[$x]['menu'][0]['path'] = '/core/menu/menu_list.php';
		$apps[$x]['menu'][0]['path'] = '/core/menu/menu.php';

		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

		$apps[$x]['menu'][1]['title']['en'] = 'System';
		$apps[$x]['menu'][1]['uuid'] = '02194288-6d56-6d3e-0b1a-d53a2bc10788';
		$apps[$x]['menu'][1]['parent_uuid'] = '';
		$apps[$x]['menu'][1]['category'] = 'internal';
		$apps[$x]['menu'][1]['path'] = '/index2.php';
		$apps[$x]['menu'][1]['order'] = '5';
		$apps[$x]['menu'][1]['groups'][] = 'user';
		$apps[$x]['menu'][1]['groups'][] = 'admin';
		$apps[$x]['menu'][1]['groups'][] = 'superadmin';

		$apps[$x]['menu'][2]['title']['en'] = 'Accounts';
		$apps[$x]['menu'][2]['uuid'] = 'bc96d773-ee57-0cdd-c3ac-2d91aba61b55';
		$apps[$x]['menu'][2]['parent_uuid'] = '';
		$apps[$x]['menu'][2]['category'] = 'internal';
		$apps[$x]['menu'][2]['path'] = '/app/extensions/v_extensions.php';
		$apps[$x]['menu'][2]['order'] = '10';
		$apps[$x]['menu'][2]['groups'][] = 'admin';
		$apps[$x]['menu'][2]['groups'][] = 'superadmin';

		$apps[$x]['menu'][3]['title']['en'] = 'Dialplan2';
		$apps[$x]['menu'][3]['uuid'] = 'b94e8bd9-9eb5-e427-9c26-ff7a6c21552a';
		$apps[$x]['menu'][3]['parent_uuid'] = '';
		$apps[$x]['menu'][3]['category'] = 'internal';
		$apps[$x]['menu'][3]['path'] = '/app/dialplan/dialplans.php';
		$apps[$x]['menu'][3]['order'] = '15';
		$apps[$x]['menu'][3]['groups'][] = 'admin';
		$apps[$x]['menu'][3]['groups'][] = 'superadmin';

		$apps[$x]['menu'][4]['title']['en'] = 'Status';
		$apps[$x]['menu'][4]['uuid'] = '0438b504-8613-7887-c420-c837ffb20cb1';
		$apps[$x]['menu'][4]['parent_uuid'] = '';
		$apps[$x]['menu'][4]['category'] = 'internal';
		$apps[$x]['menu'][4]['path'] = '/app/calls_active/v_calls_active_extensions.php';
		$apps[$x]['menu'][4]['order'] = '25';
		$apps[$x]['menu'][4]['groups'][] = 'user';
		$apps[$x]['menu'][4]['groups'][] = 'admin';
		$apps[$x]['menu'][4]['groups'][] = 'superadmin';

		$apps[$x]['menu'][5]['title']['en'] = 'Advanced';
		$apps[$x]['menu'][5]['uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][5]['parent_uuid'] = '';
		$apps[$x]['menu'][5]['category'] = 'internal';
		$apps[$x]['menu'][5]['path'] = '/app/exec/v_exec.php';
		$apps[$x]['menu'][5]['order'] = '30';
		$apps[$x]['menu'][5]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'menu_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'menu_add';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'menu_edit';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][3]['name'] = 'menu_delete';
		$apps[$x]['permissions'][3]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][4]['name'] = 'menu_restore';
		$apps[$x]['permissions'][4]['groups'][] = 'superadmin';

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_menus';
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'id';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_id';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'serial';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'integer';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'INT NOT NULL AUTO_INCREMENT';
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = 'true';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_guid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the name of the menu.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_language';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the language.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_description';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_desc';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'Enter the description.';
		$z++;

		$y = 1; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_menu_items';
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'id';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_item_id';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'serial';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'integer';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'INT NOT NULL AUTO_INCREMENT';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = 'true';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_item_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_item_guid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		//$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_guid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_menus';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'menu_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_item_parent_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_item_parent_guid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_item_title';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_item_link';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_item_str';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_item_category';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_item_protected';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_item_order';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'numeric';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_item_description';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_item_desc';
		
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_item_add_user';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_item_add_date';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_item_mod_user';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'menu_item_mod_date';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';

		$y = 2; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_menu_item_groups';
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'id';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_group_name';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'serial';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'integer';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'INT NOT NULL AUTO_INCREMENT';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = 'true';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_guid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_menus';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'menu_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'menu_item_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'menu_item_guid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_menu_items';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'menu_item_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'group_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';

?>