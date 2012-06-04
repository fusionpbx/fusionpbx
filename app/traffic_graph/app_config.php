<?php
	//application details
		$apps[$x]['name'] = "Traffic Graph";
		$apps[$x]['uuid'] = '99932b6e-6560-a472-25dd-22e196262187';
		$apps[$x]['category'] = 'System';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Uses SVG to show the network traffic.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Traffic Graph';
		$apps[$x]['menu'][0]['uuid'] = '05ac3828-dc2b-c0e2-282c-79920f5349e0';
		$apps[$x]['menu'][0]['parent_uuid'] = '0438b504-8613-7887-c420-c837ffb20cb1';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/traffic_graph/status_graph.php?width=660&height=330';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'traffic_graph_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';
?>