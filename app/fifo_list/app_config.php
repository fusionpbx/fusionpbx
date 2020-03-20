<?php

	//application details
		$apps[$x]['name'] = "FIFO List";
		$apps[$x]['uuid'] = "fcd0afab-164b-abd7-3971-d613598fe3da";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "List all the queues that are currently active with one or more callers.";
		$apps[$x]['description']['en-gb'] = "List all the queues that are currently active with one or more callers.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Führt alle Warteschlangen mit einem oder mehrere aktive Anrufer auf.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Führt alle Warteschlangen mit einem oder mehrere aktive Anrufer auf.";
		$apps[$x]['description']['es-cl'] = "Lista todas las colas que estan siendo actualmente usadas con una o más personas en espera.";
		$apps[$x]['description']['es-mx'] = "Lista todas las colas que estan siendo actualmente usadas con una o más personas en espera.";
		$apps[$x]['description']['fr-ca'] = "Il liste toutes les queues qui sont maintenant utilisés avec une ou plus des personnes en attend.";
		$apps[$x]['description']['fr-fr'] = "Liste toutes les files d'attente actuellement utilisées avec une ou plus des personnes en attend.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Toon alle wachtrijen waar aktieve bellers geplaatst zijn.";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "Liste todas as filas que estão atualmente ativas com um ou mais interlocutores.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "active_queue_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "450f1225-9187-49ac-a119-87bc26025f7d";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "active_queue_add";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "active_queue_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "active_queue_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
