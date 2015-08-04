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

	//destination details
		$y = 0;
		$apps[$x]['destinations'][$y]['type'] = "sql";
		$apps[$x]['destinations'][$y]['label'] = "time_conditions";
		$apps[$x]['destinations'][$y]['name'] = "time_conditions";
		$apps[$x]['destinations'][$y]['sql'] = "select dialplan_name as name, dialplan_number as destination, dialplan_description as description from v_dialplans ";
		$apps[$x]['destinations'][$y]['where'] = "where domain_uuid = '${domain_uuid}' and app_uuid = '4b821450-926b-175a-af93-a03c441818b1' and dialplan_enabled = 'true' ";
		$apps[$x]['destinations'][$y]['order_by'] = "dialplan_number asc";
		$apps[$x]['destinations'][$y]['field']['context'] = "dialplan_context";
		$apps[$x]['destinations'][$y]['field']['name'] = "dialplan_name";
		$apps[$x]['destinations'][$y]['field']['destination'] = "dialplan_number";
		$apps[$x]['destinations'][$y]['field']['description'] = "dialplan_description";
		$apps[$x]['destinations'][$y]['select_value']['dialplan'] = "transfer:\${destination} XML \${context}";
		$apps[$x]['destinations'][$y]['select_value']['ivr'] = "menu-exec-app:transfer \${destination} XML \${context}";
		$apps[$x]['destinations'][$y]['select_label'] = "\${destination} \${name} \${description}";

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