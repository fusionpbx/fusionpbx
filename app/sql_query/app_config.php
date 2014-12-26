<?php

	//application details
		$apps[$x]['name'] = "SQL Query";
		$apps[$x]['uuid'] = "a8b8ca29-083d-fb9b-5552-cc272de18ea6";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Run Structur Query Language commands.";
		$apps[$x]['description']['es-cl'] = "Ejecuta comandos SQL";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Executer des requêtes SQL";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Executar comandos SQL.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "sql_query_execute";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "a894fed7-5a17-f695-c3de-e32ce58b3794";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "sql_query_backup";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

?>