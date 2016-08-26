<?php

	//application details
		$apps[$x]['name'] = "Conferences Active";
		$apps[$x]['uuid'] = "c168c943-833a-c29c-7ef9-d1ee78810b71";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "AJAX tool to view and manage all active callers in a conference room.";
		$apps[$x]['description']['es-cl'] = "Herramienta AJAX para ver y administrar todas las llamadas activas en una sala de conferencia.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Outil en AJAX pour voir et gérer toutes les conférences actives.";
		$apps[$x]['description']['fr-ca'] = "Outil en AJAX pour voir et gerer toutes les conferences actives aux chambres.";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "A ferramenta AJAX permite visualizar e gerir todas as chamadas ativas numa sala de conferências.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "conference_active_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "2d857bbb-43b9-b8f7-a138-642868e0453a";
		$apps[$x]['permissions'][0]['groups'][] = "admin";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "conference_interactive_view";
		$apps[$x]['permissions'][1]['groups'][] = "user";
		$apps[$x]['permissions'][1]['groups'][] = "admin";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "conference_interactive_lock";
		$apps[$x]['permissions'][2]['groups'][] = "user";
		$apps[$x]['permissions'][2]['groups'][] = "admin";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "conference_interactive_kick";
		$apps[$x]['permissions'][3]['groups'][] = "user";
		$apps[$x]['permissions'][3]['groups'][] = "admin";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

		$apps[$x]['permissions'][4]['name'] = "conference_interactive_energy";
		//$apps[$x]['permissions'][4]['groups'][] = "user";
		//$apps[$x]['permissions'][4]['groups'][] = "admin";
		//$apps[$x]['permissions'][4]['groups'][] = "superadmin";

		$apps[$x]['permissions'][5]['name'] = "conference_interactive_volume";
		//$apps[$x]['permissions'][5]['groups'][] = "user";
		//$apps[$x]['permissions'][5]['groups'][] = "admin";
		//$apps[$x]['permissions'][5]['groups'][] = "superadmin";

		$apps[$x]['permissions'][6]['name'] = "conference_interactive_gain";
		//$apps[$x]['permissions'][6]['groups'][] = "user";
		//$apps[$x]['permissions'][6]['groups'][] = "admin";
		//$apps[$x]['permissions'][6]['groups'][] = "superadmin";

		$apps[$x]['permissions'][7]['name'] = "conference_interactive_mute";
		$apps[$x]['permissions'][7]['groups'][] = "user";
		$apps[$x]['permissions'][7]['groups'][] = "admin";
		$apps[$x]['permissions'][7]['groups'][] = "superadmin";

		$apps[$x]['permissions'][8]['name'] = "conference_interactive_deaf";
		$apps[$x]['permissions'][8]['groups'][] = "user";
		$apps[$x]['permissions'][8]['groups'][] = "admin";
		$apps[$x]['permissions'][8]['groups'][] = "superadmin";

		$apps[$x]['permissions'][9]['name'] = "conference_interactive_video";
		$apps[$x]['permissions'][9]['groups'][] = "user";
		$apps[$x]['permissions'][9]['groups'][] = "admin";
		$apps[$x]['permissions'][9]['groups'][] = "superadmin";

?>