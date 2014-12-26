<?php

	//application details
		$apps[$x]['name'] = "Inbound Routes";
		$apps[$x]['uuid'] = "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "The public dialplan is used to route incoming calls to destinations based on one or more conditions and context.";
		$apps[$x]['description']['es-cl'] = "El plan de marcado público es usado para dirigir llamadas entrantes a destinos basados en una o más condiciones y contexto.";
		$apps[$x]['description']['es-mx'] = "El plan de marcado público es usado para dirigir llamadas entrantes a destinos basados en una o más condiciones y contexto.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Les routes publiques sont utilisés pour diriger les appels entrants en fonction d'une ou plusieures conditions et contextes.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "O dialplan público é usado para encaminhar chamadas recebidas para destinos com base em uma ou mais condições e contexto.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "inbound_route_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "b64b2bbf-f99b-b568-13dc-32170515a687";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "inbound_route_add";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "inbound_route_advanced";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "inbound_route_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "inbound_route_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "inbound_route_copy";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>