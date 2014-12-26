<?php
	//application details
		$apps[$x]['name'] = "Log Viewer";
		$apps[$x]['uuid'] = "159a2724-77e1-2782-9366-db08b3750e06";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Display the switch logs.";
		$apps[$x]['description']['es-cl'] = "Muestra los registros del switch";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Logs du switch.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Exibir os logs de switch.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "log_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "781ebbec-a55a-9d60-f7bb-f54ab2ee4e7e";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "log_download";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "log_path_view";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

?>