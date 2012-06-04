<?php
	//application details
		$apps[$x]['name'] = "Script Editor";
		$apps[$x]['uuid'] = '17e628ee-ccfa-49c0-29ca-9894a0384b9b';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Script Editor can be used to edit lua, javascript or other scripts.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Script Editor';
		$apps[$x]['menu'][0]['uuid'] = 'f1905fec-0577-daef-6045-59d09b7d3f94';
		$apps[$x]['menu'][0]['parent_uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][0]['category'] = 'external';
		$apps[$x]['menu'][0]['path'] = '/app/script_edit/index.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'script_editor_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'script_editor_save';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';
?>