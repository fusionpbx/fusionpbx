<?php

	//application details
		$apps[$x]['name'] = "Outbound Routes";
		$apps[$x]['uuid'] = "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Outbound dialplans have one or more conditions that are matched to attributes of a call. When a call matches the conditions the call is then routed to the gateway.";
		$apps[$x]['description']['en-gb'] = "Outbound dialplans have one or more conditions that are matched to attributes of a call. When a call matches the conditions the call is then routed to the gateway.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Ausgehende Wählpläne haben eine oder mehrere Bedingungen welche mit den Atributen des Anrufes abgeglichen werden. Wenn die Bedingungen erfüllt sind, wird der Anruf über das Gateway geleitet.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Ausgehende Wählpläne haben eine oder mehrere Bedingungen welche mit den Atributen des Anrufes abgeglichen werden. Wenn die Bedingungen erfüllt sind, wird der Anruf über das Gateway geleitet.";
		$apps[$x]['description']['es-cl'] = "Los planes de marcado de salida tienen una o más condiciones que deben cumplirse.  Cuando las condiciones se cumplen, las llamada es dirigida con la pasarela seleccionada.";
		$apps[$x]['description']['es-mx'] = "Los planes de marcado de salida tienen una o más condiciones que deben cumplirse.  Cuando las condiciones se cumplen, las llamada es dirigida con la pasarela seleccionada.";
		$apps[$x]['description']['fr-ca'] = "Les routes sortantes ont une ou plus conditions qui devent répondre.  Quand les conditions son tout répondres, l'appel est dirigers vers la passarelle séléctioné.";
		$apps[$x]['description']['fr-fr'] = "Les routes sortantes acheminent un appel en fonction d'une ou plusieurs conditions. Quand les conditions sont remplies, l'appel est dirigés vers la passerelle correspondante.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Uitgaande kiesplannen hebben een of meer voorwaarden die met de attributen van een oproep worden vergeleken. Als een oproep aan de voorwaarden voldoet dan wordt de oproep naar de gateway gestuurd.";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "Rotas de saída tem uma ou mais condições que são verificadas junto aos atributos da chamada. Quando uma chamada combina com as condições ela é roteada para o tronco.";
		$apps[$x]['description']['pt-pt'] = "Dialplans de saída tem uma ou mais condições que são compatíveis com os atributos de uma chamada. Quando uma chamada coincide com as condições da chamada é então encaminhado para o gateway.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "outbound_route_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "17e14094-1d57-1106-db2a-a787d34015e9";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "outbound_route_add";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "outbound_route_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "outbound_route_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "outbound_route_copy";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "outbound_route_any_gateway";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['description'] = "Add outbound routes for any gateways on any domain.";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "outbound_route_pin_numbers";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

?>
