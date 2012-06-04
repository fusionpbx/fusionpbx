<?php
	//application details
		$apps[$x]['name'] = "Exec";
		$apps[$x]['uuid'] = '1dd98ca6-95f1-e728-7e8f-137fe18dc23c';
		$apps[$x]['category'] = 'System';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Provides a conventient way to execute system, PHP, and switch commands.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Command';
		$apps[$x]['menu'][0]['uuid'] = '06493580-9131-ce57-23cd-d42d69dd8526';
		$apps[$x]['menu'][0]['parent_uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/exec/v_exec.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'exec_command_line';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'exec_php_command';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'exec_switch';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';
?>