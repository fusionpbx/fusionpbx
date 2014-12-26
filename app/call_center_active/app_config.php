<?php

	//application details
		$apps[$x]['name'] = "Call Center Active";
		$apps[$x]['uuid'] = "3f159f62-ca2d-41b8-b3f0-c5519cebbc5a";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Shows active calls, and agents in the call center queue.";
		$apps[$x]['description']['es-cl'] = "Muestra las llamadas activas y los agentes en la cola del centro de llamadas.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Affiche les appels actifs et les agents en queue sur le centre d'appel.";
		$apps[$x]['description']['fr-ca'] = "Il montre les appels actives et des agents en queue du centre d'appels.";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Mostra as chamadas ativas e agentes na fila do centro de chamadas.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "call_center_active_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "7fb0dd87-e984-9980-c512-2c76b887aeb2";
		$apps[$x]['permissions'][0]['groups'][] = "admin";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "call_center_active_options";
		$apps[$x]['permissions'][1]['menu']['uuid'] = "7fb0dd87-e984-9980-c512-2c76b887aeb2";
		$apps[$x]['permissions'][1]['groups'][] = "admin";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

?>