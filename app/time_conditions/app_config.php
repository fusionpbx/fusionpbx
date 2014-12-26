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
		$apps[$x]['description']['fr-fr'] = "Redirige les appels en fonction de l'heure.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Chamada directa com base na hora do dia.";
		$apps[$x]['description']['pt-br'] = "";

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