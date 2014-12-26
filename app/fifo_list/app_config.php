<?php

	//application details
		$apps[$x]['name'] = "FIFO List";
		$apps[$x]['uuid'] = "fcd0afab-164b-abd7-3971-d613598fe3da";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "List all the queues that are currently active with one or more callers.";
		$apps[$x]['description']['es-cl'] = "Lista todas las colas que estan siendo actualmente usadas con una o más personas en espera.";
		$apps[$x]['description']['es-mx'] = "Lista todas las colas que estan siendo actualmente usadas con una o más personas en espera.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Liste toutes les files d'attente actuellement utilisées avec une ou plus des personnes en attend.";
		$apps[$x]['description']['fr-ca'] = "Il liste toutes les queues qui sont maintenant utilisés avec une ou plus des personnes en attend.";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Liste todas as filas que estão atualmente ativas com um ou mais interlocutores.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "active_queue_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "450f1225-9187-49ac-a119-87bc26025f7d";
		$apps[$x]['permissions'][0]['groups'][] = "admin";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "active_queue_add";
		$apps[$x]['permissions'][1]['groups'][] = "admin";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "active_queue_edit";
		$apps[$x]['permissions'][2]['groups'][] = "admin";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "active_queue_delete";
		$apps[$x]['permissions'][3]['groups'][] = "admin";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

?>