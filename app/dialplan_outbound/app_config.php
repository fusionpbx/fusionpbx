<?php
	//application details
		$apps[$x]['name'] = "Outbound Routes";
		$apps[$x]['uuid'] = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';
		$apps[$x]['category'] = 'Switch';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Outbound dialplans have one or more conditions that are matched to attributes of a call. When a call matches the conditions the call is then routed to the gateway.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Outbound Routes';
		$apps[$x]['menu'][0]['uuid'] = '17e14094-1d57-1106-db2a-a787d34015e9';
		$apps[$x]['menu'][0]['parent_uuid'] = 'b94e8bd9-9eb5-e427-9c26-ff7a6c21552a';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/dialplan/dialplans.php?app_uuid=8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'outbound_route_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'outbound_route_add';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'outbound_route_edit';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][3]['name'] = 'outbound_route_delete';
		$apps[$x]['permissions'][3]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][4]['name'] = 'outbound_route_copy';
		$apps[$x]['permissions'][4]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][5]['name'] = 'outbound_route_any_gateway';
		$apps[$x]['permissions'][5]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][5]['description'] = 'Add outbound routes for any gateways on any domain.';

?>