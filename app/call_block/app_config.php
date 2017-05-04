<?php

	//application details
		$apps[$x]['name'] = "Call Block";
		$apps[$x]['uuid'] = "9ed63276-e085-4897-839c-4f2e36d92d6c";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "A tool to block incoming numbers.";
		$apps[$x]['description']['es-cl'] = "Una herramineta para bloquear números entrantes";
		$apps[$x]['description']['de-de'] = "Ein Werkzeug um eingehende Rufnummern zu sperren.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "Ein Werkzeug um eingehende Rufnummern zu sperren.";
		$apps[$x]['description']['fr-fr'] = "Outil pour bloquer les numéro d'appelant";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Uma ferramenta para bloquear números indesejados";
		$apps[$x]['description']['pt-br'] = "Uma ferramenta para bloquear números que entram.";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "call_block_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "29295c90-b1b9-440b-9c7E-c8363c6e8975";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_block_add";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_block_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_block_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;

	//schema details
		$y=0;
		$apps[$x]['db'][$y]['table']['name'] = "v_call_block";
		$apps[$x]['db'][$y]['table']['parent'] = "";
		$z=0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "domain_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_domains";
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "domain_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "call_block_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "blocked_caller_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "call_block_name";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "blocked_caller_name";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Enter the name.";
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Insira o nome.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "call_block_number";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "blocked_caller_number";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Enter the full phone number.";
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Insira o número de telefone completo.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "call_block_count";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "blocked_call_count";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "numeric";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Number of calls.";
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Número de chamadas.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "call_block_action";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "blocked_call_action";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Action for call.";
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Ação para a chamada.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "date_added";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Date/Time number was added.";
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Data/Hora que o número foi adicionado.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "call_block_enabled";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "block_call_enabled";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Enable/disable blocking the call.";
		$apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Habilitar/desabilitar bloqueamento da chamada.";
		$z++;

?>
