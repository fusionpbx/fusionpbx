<?php

	//application details
		$apps[$x]['name'] = "FIFO";
		$apps[$x]['uuid'] = "16589224-c876-aeb3-f59f-523a1c0801f7";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Queues are used to setup waiting lines for callers. Also known as FIFO Queues.";
		$apps[$x]['description']['es-cl'] = "Las colas son usadas para configurar líneas de espera. Son conocidas como colas FIFO.";
		$apps[$x]['description']['es-mx'] = "Las cosas son usadas para configurar líneas de espera.  Son conocidas como colas FIFO.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Les queues sont utilisés pour configurer les salles d'attente (FIFO).";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "As filas são usados​para configurar as filas de espera para chamadores. Também conhecida como filas FIFO.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "fifo_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "c535ac0b-1da1-0f9c-4653-7934c6f4732c";
		$apps[$x]['permissions'][0]['groups'][] = "admin";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "fifo_add";
		$apps[$x]['permissions'][1]['groups'][] = "admin";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "fifo_edit";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "fifo_delete";
		$apps[$x]['permissions'][3]['groups'][] = "admin";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

?>