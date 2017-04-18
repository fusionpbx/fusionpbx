<?php

	//application details
		$apps[$x]['name'] = 'Access Controls';
		$apps[$x]['uuid'] = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
		$apps[$x]['category'] = '';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = '';

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = 'access_control_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'access_control_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'access_control_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'access_control_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'access_control_node_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'access_control_node_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'access_control_node_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'access_control_node_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;

	//schema details
		$y=0;
		$apps[$x]['db'][$y]['table']['name'] = "v_access_controls";
		$apps[$x]['db'][$y]['table']['parent'] = "";
		$z=0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'access_control_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'access_control_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the name.';
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = 'Insira o nome';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'access_control_default';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Select the default type.';
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = 'Selecione o tipo padrão.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'access_control_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the description';
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = 'Insira com uma descrição';

		$y=1;
		$apps[$x]['db'][$y]['table']['name'] = "v_access_control_nodes";
		$apps[$x]['db'][$y]['table']['parent'] = "v_access_controls";
		$z=0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'access_control_node_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'access_control_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_access_control';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'access_control_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'node_type';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Select the type.';
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = 'Selecione o tipo.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'node_cidr';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the IP CIDR range.';
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = 'Insira o intervalo IP CIDR.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'node_domain';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the domain.';
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = 'Insira com o domínio.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'node_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the description.';
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = 'Insira a descrição.';
		$z++;

?>
