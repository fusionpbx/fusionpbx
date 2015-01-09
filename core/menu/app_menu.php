<?php

	$apps[$x]['menu'][0]['title']['en-us'] = "Menu Manager";
	$apps[$x]['menu'][0]['title']['es-cl'] = "Gestor de Menú";
	$apps[$x]['menu'][0]['title']['de-de'] = "";
	$apps[$x]['menu'][0]['title']['de-ch'] = "";
	$apps[$x]['menu'][0]['title']['de-at'] = "";
	$apps[$x]['menu'][0]['title']['fr-fr'] = "Gestion des Menus";
	$apps[$x]['menu'][0]['title']['fr-ca'] = "";
	$apps[$x]['menu'][0]['title']['fr-ch'] = "";
	$apps[$x]['menu'][0]['title']['pt-pt'] = "Gestor de Menus";
	$apps[$x]['menu'][0]['title']['pt-br'] = "";
	$apps[$x]['menu'][0]['uuid'] = "da3a9ab4-c28e-ea8d-50cc-e8405ac8e76e";
	$apps[$x]['menu'][0]['parent_uuid'] = "02194288-6d56-6d3e-0b1a-d53a2bc10788";
	$apps[$x]['menu'][0]['category'] = "internal";
	$apps[$x]['menu'][0]['path'] = "/core/menu/menu.php";
	$apps[$x]['menu'][0]['groups'][] = "superadmin";

	$apps[$x]['menu'][1]['title']['en-us'] = "System";
	$apps[$x]['menu'][1]['title']['es-cl'] = "Sistema";
	$apps[$x]['menu'][1]['title']['de-de'] = "";
	$apps[$x]['menu'][1]['title']['de-ch'] = "";
	$apps[$x]['menu'][1]['title']['de-at'] = "";
	$apps[$x]['menu'][1]['title']['fr-fr'] = "Système";
	$apps[$x]['menu'][1]['title']['fr-ca'] = "";
	$apps[$x]['menu'][1]['title']['fr-ch'] = "";
	$apps[$x]['menu'][1]['title']['pt-pt'] = "Sistema";
	$apps[$x]['menu'][1]['title']['pt-br'] = "";
	$apps[$x]['menu'][1]['uuid'] = "02194288-6d56-6d3e-0b1a-d53a2bc10788";
	$apps[$x]['menu'][1]['parent_uuid'] = "";
	$apps[$x]['menu'][1]['category'] = "internal";
	$apps[$x]['menu'][1]['path'] = "";
	$apps[$x]['menu'][1]['order'] = "5";
	$apps[$x]['menu'][1]['groups'][] = "user";
	$apps[$x]['menu'][1]['groups'][] = "admin";
	$apps[$x]['menu'][1]['groups'][] = "superadmin";

	$apps[$x]['menu'][2]['title']['en-us'] = "Accounts";
	$apps[$x]['menu'][2]['title']['es-cl'] = "Cuentas";
	$apps[$x]['menu'][2]['title']['de-de'] = "";
	$apps[$x]['menu'][2]['title']['de-ch'] = "";
	$apps[$x]['menu'][2]['title']['de-at'] = "";
	$apps[$x]['menu'][2]['title']['fr-fr'] = "Comptes";
	$apps[$x]['menu'][2]['title']['fr-ca'] = "";
	$apps[$x]['menu'][2]['title']['fr-ch'] = "";
	$apps[$x]['menu'][2]['title']['pt-pt'] = "Contas";
	$apps[$x]['menu'][2]['title']['pt-br'] = "";
	$apps[$x]['menu'][2]['uuid'] = "bc96d773-ee57-0cdd-c3ac-2d91aba61b55";
	$apps[$x]['menu'][2]['parent_uuid'] = "";
	$apps[$x]['menu'][2]['category'] = "internal";
	$apps[$x]['menu'][2]['path'] = "";
	$apps[$x]['menu'][2]['order'] = "10";
	$apps[$x]['menu'][2]['groups'][] = "admin";
	$apps[$x]['menu'][2]['groups'][] = "superadmin";

	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/vars/app_config.php")) {
		$apps[$x]['menu'][3]['title']['en-us'] = "Dialplan";
		$apps[$x]['menu'][3]['uuid'] = "b94e8bd9-9eb5-e427-9c26-ff7a6c21552a";
		$apps[$x]['menu'][3]['parent_uuid'] = "";
		$apps[$x]['menu'][3]['category'] = "internal";
		$apps[$x]['menu'][3]['path'] = "";
		$apps[$x]['menu'][3]['order'] = "15";
		$apps[$x]['menu'][3]['groups'][] = "admin";
		$apps[$x]['menu'][3]['groups'][] = "superadmin";
	}

	$apps[$x]['menu'][4]['title']['en-us'] = "Status";
	$apps[$x]['menu'][4]['title']['es-cl'] = "Estado";
	$apps[$x]['menu'][4]['title']['de-de'] = "";
	$apps[$x]['menu'][4]['title']['de-ch'] = "";
	$apps[$x]['menu'][4]['title']['de-at'] = "";
	$apps[$x]['menu'][4]['title']['fr-fr'] = "Etat";
	$apps[$x]['menu'][4]['title']['fr-ca'] = "";
	$apps[$x]['menu'][4]['title']['fr-ch'] = "";
	$apps[$x]['menu'][4]['title']['pt-pt'] = "Estado";
	$apps[$x]['menu'][4]['title']['pt-br'] = "";
	$apps[$x]['menu'][4]['uuid'] = "0438b504-8613-7887-c420-c837ffb20cb1";
	$apps[$x]['menu'][4]['parent_uuid'] = "";
	$apps[$x]['menu'][4]['category'] = "internal";
	$apps[$x]['menu'][4]['path'] = "";
	$apps[$x]['menu'][4]['order'] = "25";
	$apps[$x]['menu'][4]['groups'][] = "user";
	$apps[$x]['menu'][4]['groups'][] = "admin";
	$apps[$x]['menu'][4]['groups'][] = "superadmin";

	$apps[$x]['menu'][5]['title']['en-us'] = "Advanced";
	$apps[$x]['menu'][5]['title']['es-cl'] = "Avanzado";
	$apps[$x]['menu'][5]['title']['de-de'] = "";
	$apps[$x]['menu'][5]['title']['de-ch'] = "";
	$apps[$x]['menu'][5]['title']['de-at'] = "";
	$apps[$x]['menu'][5]['title']['fr-fr'] = "Avancé";
	$apps[$x]['menu'][5]['title']['fr-ca'] = "";
	$apps[$x]['menu'][5]['title']['fr-ch'] = "";
	$apps[$x]['menu'][5]['title']['pt-pt'] = "Avançado";
	$apps[$x]['menu'][5]['title']['pt-br'] = "";
	$apps[$x]['menu'][5]['uuid'] = "594d99c5-6128-9c88-ca35-4b33392cec0f";
	$apps[$x]['menu'][5]['parent_uuid'] = "";
	$apps[$x]['menu'][5]['category'] = "internal";
	$apps[$x]['menu'][5]['path'] = "";
	$apps[$x]['menu'][5]['order'] = "30";
	$apps[$x]['menu'][5]['groups'][] = "superadmin";

?>