<?php

	//application details
		$apps[$x]['name'] = "Exec";
		$apps[$x]['uuid'] = "1dd98ca6-95f1-e728-7e8f-137fe18dc23c";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Provides a conventient way to execute system, PHP, switch and SQL commands.";
		$apps[$x]['description']['es-cl'] = "Provee un modo conveniente de ejecutar comandos de sistema, PHP o del switch.";
		$apps[$x]['description']['es-mx'] = "Provee un modo conveniente de ejecutar comandos de sistema, PHP o del switch.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Offre un mode pour exécuter des commandes système, PHP ou switch.";
		$apps[$x]['description']['fr-ca'] = "Il offre un mode d'exécuter des commandes du système, PHP ou switch.";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Ofereçe uma forma conveniente para executar comandos de sistema, PHP e switch.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "exec_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "06493580-9131-ce57-23cd-d42d69dd8526";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";
	
		$apps[$x]['permissions'][1]['name'] = "exec_command";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "exec_php";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "exec_switch";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

		$apps[$x]['permissions'][4]['name'] = "exec_sql";
		$apps[$x]['permissions'][4]['groups'][] = "superadmin";
		
		$apps[$x]['permissions'][5]['name'] = "exec_sql_backup";
		$apps[$x]['permissions'][5]['groups'][] = "superadmin";

?>