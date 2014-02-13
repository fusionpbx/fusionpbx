<?php
	//application details
		$apps[$x]['name'] = "Time Conditions";
		$apps[$x]['uuid'] = "4b821450-926b-175a-af93-a03c441818b1";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Direct calls based on the time of day.";
		$apps[$x]['description']['es-cl'] = "Direcciona llamadas basada en hora del día";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Redirige les appels en fonction de l'heure";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Chamada directa com base na hora do dia.";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "Time Conditions";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Condic. de Tiempo";
		$apps[$x]['menu'][0]['title']['es-mx'] = "";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Conditions Temporelles";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Condições Temporais";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "67aede56-8623-df2d-6338-ecfbde5825f7";
		$apps[$x]['menu'][0]['parent_uuid'] = "fd29e39c-c936-f5fc-8e2b-611681b266b5";
		$apps[$x]['menu'][0]['category'] = "internal";
		$apps[$x]['menu'][0]['path'] = "/app/dialplan/dialplans.php?app_uuid=4b821450-926b-175a-af93-a03c441818b1";
		$apps[$x]['menu'][0]['groups'][] = "admin";
		$apps[$x]['menu'][0]['groups'][] = "superadmin";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "time_condition_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "67aede56-8623-df2d-6338-ecfbde5825f7";
		$apps[$x]['permissions'][0]['groups'][] = "admin";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "time_condition_add";
		$apps[$x]['permissions'][1]['groups'][] = "admin";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "time_condition_edit";
		$apps[$x]['permissions'][2]['groups'][] = "admin";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "time_condition_delete";
		$apps[$x]['permissions'][3]['groups'][] = "admin";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";
?>