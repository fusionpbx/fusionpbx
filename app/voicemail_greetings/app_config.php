<?php
	//application details
		$apps[$x]['name'] = "Voicemail Greetings";
		$apps[$x]['uuid'] = "e4b4fbee-9e4d-8e46-3810-91ba663db0c2";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Manager voicemail greetings for extensions.";
		$apps[$x]['description']['es-cl'] = "Administrador de mensajes de bienvenida de correo de voz para extensiones.";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Accueil messagerie Vocale";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Gestor de saudações de correio de voz para extensões.";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		//$apps[$x]['menu'][0]['title']['en-us'] = "Voicemail Greetings";
		//$apps[$x]['menu'][0]['uuid'] = "71197938-224b-3a90-c076-3979cabb3ee9";
		//$apps[$x]['menu'][0]['parent_uuid'] = "fd29e39c-c936-f5fc-8e2b-611681b266b5";
		//$apps[$x]['menu'][0]['category'] = "internal";
		//$apps[$x]['menu'][0]['path'] = "/app/voicemail_greetings/voicemail_greetings.php";
		//$apps[$x]['menu'][0]['groups'][] = "admin";
		//$apps[$x]['menu'][0]['groups'][] = "superadmin";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "voicemail_greeting_view";
		$apps[$x]['permissions'][0]['groups'][] = "user";
		$apps[$x]['permissions'][0]['groups'][] = "admin";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "voicemail_greeting_add";
		$apps[$x]['permissions'][1]['groups'][] = "user";
		$apps[$x]['permissions'][1]['groups'][] = "admin";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "voicemail_greeting_edit";
		$apps[$x]['permissions'][2]['groups'][] = "user";
		$apps[$x]['permissions'][2]['groups'][] = "admin";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "voicemail_greeting_delete";
		$apps[$x]['permissions'][3]['groups'][] = "user";
		$apps[$x]['permissions'][3]['groups'][] = "admin";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

		$apps[$x]['permissions'][4]['name'] = "voicemail_greeting_upload";
		$apps[$x]['permissions'][4]['groups'][] = "user";
		$apps[$x]['permissions'][4]['groups'][] = "admin";
		$apps[$x]['permissions'][4]['groups'][] = "superadmin";

		$apps[$x]['permissions'][5]['name'] = "voicemail_greeting_play";
		$apps[$x]['permissions'][5]['groups'][] = "user";
		$apps[$x]['permissions'][5]['groups'][] = "admin";
		$apps[$x]['permissions'][5]['groups'][] = "superadmin";

		$apps[$x]['permissions'][6]['name'] = "voicemail_greeting_download";
		$apps[$x]['permissions'][6]['groups'][] = "user";
		$apps[$x]['permissions'][6]['groups'][] = "admin";
		$apps[$x]['permissions'][6]['groups'][] = "superadmin";

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = "v_voicemail_greetings";
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "id";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "greeting_id";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "serial";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "integer";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "INT NOT NULL AUTO_INCREMENT";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = "true";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "voicemail_greeting_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "greeting_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "domain_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_domains";
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "domain_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "v_id";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = "true";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "voicemail_id";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "user_id";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "greeting_name";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "greeting_description";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";

?>
