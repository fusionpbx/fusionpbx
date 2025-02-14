<?php

	//application details
		$apps[$x]['name'] = "Active Calls";
		$apps[$x]['uuid'] = "ec8530a9-903a-469d-3717-281f798b9ef6";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Active channels on the system.";
		$apps[$x]['description']['en-gb'] = "Active channels on the system.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Aktive Kanäle auf dem System.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Aktive Kanäle auf dem System.";
		$apps[$x]['description']['es-cl'] = "Canales activos en el sistema.";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "Channels actifs sur le système";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['ka-ge'] = "აქტიური არხები სისტემაში.";
		$apps[$x]['description']['nl-nl'] = "Aktieve kanalen in het systeem";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "Canais ativos no sistema.";
		$apps[$x]['description']['pt-pt'] = "Canais ativos no sistema.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "Активные каналы в системе";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "call_active_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "eba3d07f-dd5c-6b7b-6880-493b44113ade";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		//$y++;
		//$apps[$x]['permissions'][$y]['name'] = "call_active_transfer";
		//$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_active_eavesdrop";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_active_hangup";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		//$y++;
		//$apps[$x]['permissions'][$y]['name'] = "call_active_park";
		//$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		//$y++;
		//$apps[$x]['permissions'][$y]['name'] = "call_active_rec";
		//$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_active_all";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
