<?php

	//application details
		$apps[$x]['name'] = 'Available Destinations';
		$apps[$x]['uuid'] = '3c8dac41-17e3-44b3-ae7d-e01b8aa6acdf';
		$apps[$x]['category'] = '';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = '';
		$apps[$x]['description']['en-gb'] = '';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'available_destination_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'golden';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destination_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'golden';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destination_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'golden';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destination_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'golden';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destination_all';
		$apps[$x]['permissions'][$y]['groups'][] = 'golden';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destination_destinations';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'golden';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;

	//destination details
		$y = 0;
		$apps[$x]['destinations'][$y]['type'] = "sql";
		$apps[$x]['destinations'][$y]['label'] = "available_destinations";
		$apps[$x]['destinations'][$y]['name'] = "available_destinations";
		$apps[$x]['destinations'][$y]['sql'] = "select destination_trunk_name, destination_number, destination_description from v_available_destinations ";
		$apps[$x]['destinations'][$y]['where'] = "where domain_uuid = '\${domain_uuid}' and destination_enabled = 'true'";
		$apps[$x]['destinations'][$y]['order_by'] = "destination_number asc";
		$apps[$x]['destinations'][$y]['field']['available_destination_uuid'] = "available_destination_uuid";
		$apps[$x]['destinations'][$y]['field']['destination_trunk_name'] = "destination_trunk_name";
		$apps[$x]['destinations'][$y]['field']['destination_description'] = "destination_description";
		$apps[$x]['destinations'][$y]['field']['destination_number'] = "destination_number";
		$apps[$x]['destinations'][$y]['select_value']['user_contact'] = "\${destination}";
		$apps[$x]['destinations'][$y]['select_value']['dialplan'] = "available_destinations:\${destination}";
		$apps[$x]['destinations'][$y]['select_value']['ivr'] = "menu-exec-app:available_destinations \${destination}";
		$apps[$x]['destinations'][$y]['select_label'] = "\${destination_trunk_name} \${destination_number} \${destination_description}";
		$y++;*/

	//Available Destinations
		$y = 0;
		$apps[$x]['db'][$y]['table']['name'] = 'v_available_destinations';
		$apps[$x]['db'][$y]['table']['parent'] = '';
		$z = 0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'available_destination_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'domain_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'destination_trunk_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'destination_number';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the destination.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'destination_enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Select to enable or disable.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'destination_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the description.';
		$z++;

?>
