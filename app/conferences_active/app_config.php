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
		$apps[$x]['description']['fr-fr'] = "Outil en AJAX pour voir et gerer toutes les conferences actives.";
		$apps[$x]['description']['fr-ca'] = "Outil en AJAX pour voir et gerer toutes les conferences actives aux chambres.";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "A ferramenta AJAX permite visualizar e gerir todas as chamadas ativas numa sala de conferências.";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "Active Conferences";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Conferencias Activas";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Conferences en cours";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Conferencias Activas";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "2d857bbb-43b9-b8f7-a138-642868e0453a";
		$apps[$x]['menu'][0]['parent_uuid'] = "0438b504-8613-7887-c420-c837ffb20cb1";
		$apps[$x]['menu'][0]['category'] = "internal";
		$apps[$x]['menu'][0]['path'] = "/app/conferences_active/conferences_active.php";
		$apps[$x]['menu'][0]['groups'][] = "admin";
		$apps[$x]['menu'][0]['groups'][] = "superadmin";
		$apps[$x]['menu'][0]['groups'][] = "user";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "conference_active_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "2d857bbb-43b9-b8f7-a138-642868e0453a";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "2d857bbb-43b9-b8f7-a138-642868e0453a";
		$apps[$x]['permissions'][0]['groups'][] = "user";
		$apps[$x]['permissions'][0]['groups'][] = "admin";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "conference_active_lock";
		$apps[$x]['permissions'][2]['groups'][] = "user";
		$apps[$x]['permissions'][2]['groups'][] = "admin";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "conference_active_kick";
		$apps[$x]['permissions'][3]['groups'][] = "user";
		$apps[$x]['permissions'][3]['groups'][] = "admin";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

		$apps[$x]['permissions'][4]['name'] = "conference_active_energy";
		//$apps[$x]['permissions'][4]['groups'][] = "user";
		//$apps[$x]['permissions'][4]['groups'][] = "admin";
		//$apps[$x]['permissions'][4]['groups'][] = "superadmin";

		$apps[$x]['permissions'][5]['name'] = "conference_active_volume";
		//$apps[$x]['permissions'][5]['groups'][] = "user";
		//$apps[$x]['permissions'][5]['groups'][] = "admin";
		//$apps[$x]['permissions'][5]['groups'][] = "superadmin";

		$apps[$x]['permissions'][6]['name'] = "conference_active_gain";
		//$apps[$x]['permissions'][6]['groups'][] = "user";
		//$apps[$x]['permissions'][6]['groups'][] = "admin";
		//$apps[$x]['permissions'][6]['groups'][] = "superadmin";

		$apps[$x]['permissions'][7]['name'] = "conference_active_mute";
		$apps[$x]['permissions'][7]['groups'][] = "user";
		$apps[$x]['permissions'][7]['groups'][] = "admin";
		$apps[$x]['permissions'][7]['groups'][] = "superadmin";

		$apps[$x]['permissions'][8]['name'] = "conferences_active_deaf";
		$apps[$x]['permissions'][8]['groups'][] = "user";
		$apps[$x]['permissions'][8]['groups'][] = "admin";
		$apps[$x]['permissions'][8]['groups'][] = "superadmin";

		$apps[$x]['permissions'][8]['name'] = "conference_active_video";
		$apps[$x]['permissions'][8]['groups'][] = "user";
		$apps[$x]['permissions'][8]['groups'][] = "admin";
		$apps[$x]['permissions'][8]['groups'][] = "superadmin";

		$apps[$x]['permissions'][9]['name'] = "conference_active_advanced_view";
		$apps[$x]['permissions'][9]['groups'][] = "admin";
		$apps[$x]['permissions'][9]['groups'][] = "superadmin";
?>
