<?php

	//application details
		$apps[$x]['name'] = "Active Calls";
		$apps[$x]['uuid'] = "ec8530a9-903a-469d-3717-281f798b9ef6";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Active channels on the system.";
		$apps[$x]['description']['es-cl'] = "Canales activos en el sistema.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Channels actifs sur le système";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Canais ativos no sistema.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "call_active_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "eba3d07f-dd5c-6b7b-6880-493b44113ade";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "call_active_transfer";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "call_active_hangup";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "call_active_park";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

		$apps[$x]['permissions'][4]['name'] = "call_active_rec";
		$apps[$x]['permissions'][4]['groups'][] = "superadmin";

		$apps[$x]['permissions'][5]['name'] = "extension_active_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "6dd85c19-cb6b-5cca-bf32-499bbe936f79";
		//$apps[$x]['permissions'][5]['groups'][] = "user";
		$apps[$x]['permissions'][5]['groups'][] = "admin";
		$apps[$x]['permissions'][5]['groups'][] = "superadmin";

		$apps[$x]['permissions'][6]['name'] = "extension_active_transfer";
		$apps[$x]['permissions'][6]['groups'][] = "admin";
		$apps[$x]['permissions'][6]['groups'][] = "superadmin";

		$apps[$x]['permissions'][7]['name'] = "extension_active_hangup";
		$apps[$x]['permissions'][7]['groups'][] = "admin";
		$apps[$x]['permissions'][7]['groups'][] = "superadmin";

		$apps[$x]['permissions'][8]['name'] = "extension_active_park";
		$apps[$x]['permissions'][8]['groups'][] = "admin";
		$apps[$x]['permissions'][8]['groups'][] = "superadmin";

		$apps[$x]['permissions'][9]['name'] = "extension_active_rec";
		$apps[$x]['permissions'][9]['groups'][] = "admin";
		$apps[$x]['permissions'][9]['groups'][] = "superadmin";

		$apps[$x]['permissions'][10]['name'] = "extension_active_list_view";
		$apps[$x]['permissions'][10]['menu']['uuid'] = "6dd85c19-cb6b-5cca-bf32-499bbe936f79";
		//$apps[$x]['permissions'][10]['groups'][] = "user";
		$apps[$x]['permissions'][10]['groups'][] = "admin";
		$apps[$x]['permissions'][10]['groups'][] = "superadmin";

		$apps[$x]['permissions'][11]['name'] = "extension_active_assigned_view";
		$apps[$x]['permissions'][11]['menu']['uuid'] = "6dd85c19-cb6b-5cca-bf32-499bbe936f79";
		//$apps[$x]['permissions'][11]['groups'][] = "user";
		$apps[$x]['permissions'][11]['groups'][] = "admin";
		$apps[$x]['permissions'][11]['groups'][] = "superadmin";

?>