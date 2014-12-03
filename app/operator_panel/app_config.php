<?php
	//application details
		$apps[$x]['name'] = "Operator Panel";
		$apps[$x]['uuid'] = '01b78ec9-5e12-4aca-95a3-893a98c80762';
		$apps[$x]['category'] = 'Switch';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = 'Operator panel shows the status.';
		$apps[$x]['description']['es-mx'] = '';
		$apps[$x]['description']['de'] = '';
		$apps[$x]['description']['de-ch'] = '';
		$apps[$x]['description']['de-at'] = '';
		$apps[$x]['description']['fr'] = '';
		$apps[$x]['description']['fr-ca'] = '';
		$apps[$x]['description']['fr-ch'] = '';
		$apps[$x]['description']['pt-pt'] = 'Canais ativos no sistema.';
		$apps[$x]['description']['pt-br'] = '';

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = 'Operator Panel';
		$apps[$x]['menu'][0]['title']['es-mx'] = '';
		$apps[$x]['menu'][0]['title']['de'] = '';
		$apps[$x]['menu'][0]['title']['de-ch'] = '';
		$apps[$x]['menu'][0]['title']['de-at'] = '';
		$apps[$x]['menu'][0]['title']['fr'] = '';
		$apps[$x]['menu'][0]['title']['fr-ca'] = '';
		$apps[$x]['menu'][0]['title']['fr-ch'] = '';
		$apps[$x]['menu'][0]['title']['pt-pt'] = '';
		$apps[$x]['menu'][0]['title']['pt-br'] = '';
		$apps[$x]['menu'][0]['uuid'] = '30fa146b-18a8-4d18-8576-16d3cbf25fd8';
		$apps[$x]['menu'][0]['parent_uuid'] = '0438b504-8613-7887-c420-c837ffb20cb1';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/operator_panel/index.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'calls_active_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'calls_active_transfer';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'calls_active_hangup';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][3]['name'] = 'calls_active_park';
		$apps[$x]['permissions'][3]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][4]['name'] = 'calls_active_rec';
		$apps[$x]['permissions'][4]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][5]['name'] = 'extensions_active_view';
		//$apps[$x]['permissions'][5]['groups'][] = 'user';
		$apps[$x]['permissions'][5]['groups'][] = 'admin';
		$apps[$x]['permissions'][5]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][6]['name'] = 'extensions_active_transfer';
		$apps[$x]['permissions'][6]['groups'][] = 'admin';
		$apps[$x]['permissions'][6]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][7]['name'] = 'extensions_active_hangup';
		$apps[$x]['permissions'][7]['groups'][] = 'admin';
		$apps[$x]['permissions'][7]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][8]['name'] = 'extensions_active_park';
		$apps[$x]['permissions'][8]['groups'][] = 'admin';
		$apps[$x]['permissions'][8]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][9]['name'] = 'extensions_active_rec';
		$apps[$x]['permissions'][9]['groups'][] = 'admin';
		$apps[$x]['permissions'][9]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][10]['name'] = 'extensions_active_list_view';
		//$apps[$x]['permissions'][10]['groups'][] = 'user';
		$apps[$x]['permissions'][10]['groups'][] = 'admin';
		$apps[$x]['permissions'][10]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][11]['name'] = 'extensions_active_assigned_view';
		//$apps[$x]['permissions'][11]['groups'][] = 'user';
		$apps[$x]['permissions'][11]['groups'][] = 'admin';
		$apps[$x]['permissions'][11]['groups'][] = 'superadmin';
?>