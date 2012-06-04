<?php
	//application details
		$apps[$x]['name'] = "Grammar Editor";
		$apps[$x]['uuid'] = '2d5db509-433d-1751-1740-eed43862b85f';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Grammar editor is an AJAX based tool to edit speech recognition grammar files.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Grammar Editor';
		$apps[$x]['menu'][0]['uuid'] = 'c3db739e-89f9-0fa2-44ce-0f4c2ff43b1a';
		$apps[$x]['menu'][0]['parent_uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][0]['category'] = 'external';
		$apps[$x]['menu'][0]['path'] = '/app/grammar_edit/index.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'grammar_view';
		$apps[$x]['permissions'][0]['groups'][] = 'admin';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'grammar_save';
		$apps[$x]['permissions'][1]['groups'][] = 'admin';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';
?>