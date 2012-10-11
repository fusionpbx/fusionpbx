<?php
	//application details
		$apps[$x]['name'] = "Inbound Routes";
		$apps[$x]['uuid'] = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
		$apps[$x]['category'] = 'Switch';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = 'The public dialplan is used to route incoming calls to destinations based on one or more conditions and context.';
		$apps[$x]['description']['es-mx'] = '';
		$apps[$x]['description']['de'] = '';
		$apps[$x]['description']['de-ch'] = '';
		$apps[$x]['description']['de-at'] = '';
		$apps[$x]['description']['fr'] = '';
		$apps[$x]['description']['fr-ca'] = '';
		$apps[$x]['description']['fr-ch'] = '';
		$apps[$x]['description']['pt-pt'] = 'O dialplan público é usado para encaminhar chamadas recebidas para destinos com base em uma ou mais condições e contexto.';
		$apps[$x]['description']['pt-br'] = '';

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = 'Inbound Routes';
		$apps[$x]['menu'][0]['title']['es-mx'] = '';
		$apps[$x]['menu'][0]['title']['de'] = '';
		$apps[$x]['menu'][0]['title']['de-ch'] = '';
		$apps[$x]['menu'][0]['title']['de-at'] = '';
		$apps[$x]['menu'][0]['title']['fr'] = '';
		$apps[$x]['menu'][0]['title']['fr-ca'] = '';
		$apps[$x]['menu'][0]['title']['fr-ch'] = '';
		$apps[$x]['menu'][0]['title']['pt-pt'] = 'Rotas de Entrada';
		$apps[$x]['menu'][0]['title']['pt-br'] = '';
		$apps[$x]['menu'][0]['uuid'] = 'b64b2bbf-f99b-b568-13dc-32170515a687';
		$apps[$x]['menu'][0]['parent_uuid'] = 'b94e8bd9-9eb5-e427-9c26-ff7a6c21552a';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/dialplan/dialplans.php?app_uuid=c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'inbound_route_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'inbound_route_add';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'inbound_route_edit';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][3]['name'] = 'inbound_route_delete';
		$apps[$x]['permissions'][3]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][4]['name'] = 'inbound_route_copy';
		$apps[$x]['permissions'][4]['groups'][] = 'superadmin';

?>