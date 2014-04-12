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
		$apps[$x]['description']['fr-fr'] = "Affiche les appels actifs et les agents en file sur le centre d'appels.";
		$apps[$x]['description']['fr-ca'] = "Il montre les appels actives et des agents en queue du centre d'appels.";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Mostra as chamadas ativas e agentes na fila do centro de chamadas.";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "Active Call Center";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Centro de Llamada Activo";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Centre Appels Actifs";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "Centre d'Appels Active";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Centro de Chamadas Activo";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "7fb0dd87-e984-9980-c512-2c76b887aeb2";
		$apps[$x]['menu'][0]['parent_uuid'] = "0438b504-8613-7887-c420-c837ffb20cb1";
		$apps[$x]['menu'][0]['category'] = "internal";
		$apps[$x]['menu'][0]['path'] = "/app/call_center_active/call_center_queue.php";
		$apps[$x]['menu'][0]['groups'][] = "admin";
		$apps[$x]['menu'][0]['groups'][] = "superadmin";

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
