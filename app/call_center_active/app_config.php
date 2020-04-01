<?php

	//application details
		$apps[$x]['name'] = "Call Center Active";
		$apps[$x]['uuid'] = "3f159f62-ca2d-41b8-b3f0-c5519cebbc5a";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Shows active calls, and agents in the call center queue.";
		$apps[$x]['description']['en-gb'] = "Shows active calls, and agents in the call center queue.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Zeigt aktive Anrufe und Agenten in der Callcenter Warteschlange.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Zeigt aktive Anrufe und Agenten in der Callcenter Warteschlange.";
		$apps[$x]['description']['es-cl'] = "Muestra las llamadas activas y los agentes en la cola del centro de llamadas.";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "Il montre les appels actives et des agents en queue du centre d'appels.";
		$apps[$x]['description']['fr-fr'] = "Affiche les appels actifs et les agents en queue sur le centre d'appel.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Laat actieve oproepen en agenten in het Call-Center wachtrij zien";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "Mostra as chamadas ativas, e os agentes na fila do Call Center.";
		$apps[$x]['description']['pt-pt'] = "Mostra as chamadas ativas e agentes na fila do centro de chamadas.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "call_center_active_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "7fb0dd87-e984-9980-c512-2c76b887aeb2";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_center_active_options";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "7fb0dd87-e984-9980-c512-2c76b887aeb2";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
