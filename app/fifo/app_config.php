<?php

	//application details
		$apps[$x]['name'] = "FIFO";
		$apps[$x]['uuid'] = "16589224-c876-aeb3-f59f-523a1c0801f7";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Queues are used to setup waiting lines for callers. Also known as FIFO Queues.";
		$apps[$x]['description']['en-gb'] = "Queues are used to setup waiting lines for callers. Also known as FIFO Queues.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Warteschlangen werden verwendet um Warteschleifen für Anrufer bereit zu stellen. Diese sind auch als FIFO-Warteschlangen bekannt.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Warteschlangen werden verwendet um Warteschleifen für Anrufer bereit zu stellen. Diese sind auch als FIFO-Warteschlangen bekannt.";
		$apps[$x]['description']['es-cl'] = "Las colas son usadas para configurar líneas de espera. Son conocidas como colas FIFO.";
		$apps[$x]['description']['es-mx'] = "Las cosas son usadas para configurar líneas de espera.  Son conocidas como colas FIFO.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "Les queues sont utilisés pour configurer les salles d'attente (FIFO).";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "Le code sono usate per creare linee di attesa per i chiamanti. Anche dette Code FIFO.";
		$apps[$x]['description']['nl-nl'] = "Wachtrijen worden gebruikt om bellers op volgorde FIFO af te handelen.";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "As filas são usados​para configurar as filas de espera para chamadores. Também conhecida como filas FIFO.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "fifo_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "c535ac0b-1da1-0f9c-4653-7934c6f4732c";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "fifo_add";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "fifo_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "fifo_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

	//cache details
		$apps[$x]['cache']['key'] = "dialplan.\${domain_name}";

?>
