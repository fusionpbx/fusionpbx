<?php
	//application details
		$apps[$x]['name'] = "Grammar Editor";
		$apps[$x]['uuid'] = '2d5db509-433d-1751-1740-eed43862b85f';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
                $apps[$x]['description']['en-us'] = 'Grammar editor is an AJAX based tool to edit speech recognition grammar files.';
		$apps[$x]['description']['es-mx'] = '';
		$apps[$x]['description']['de'] = '';
		$apps[$x]['description']['de-ch'] = '';
		$apps[$x]['description']['de-at'] = '';
		$apps[$x]['description']['fr'] = '';
		$apps[$x]['description']['fr-ca'] = '';
		$apps[$x]['description']['fr-ch'] = '';
		$apps[$x]['description']['pt-pt'] = 'Editor de gramática é uma ferramenta baseada em AJAX para editar arquivos de fala gramática de reconhecimento.';
		$apps[$x]['description']['pt-br'] = '';

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = 'Grammar Editor';
		$apps[$x]['menu'][0]['title']['es-mx'] = '';
		$apps[$x]['menu'][0]['title']['de'] = '';
		$apps[$x]['menu'][0]['title']['de-ch'] = '';
		$apps[$x]['menu'][0]['title']['de-at'] = '';
		$apps[$x]['menu'][0]['title']['fr'] = '';
		$apps[$x]['menu'][0]['title']['fr-ca'] = '';
		$apps[$x]['menu'][0]['title']['fr-ch'] = '';
		$apps[$x]['menu'][0]['title']['pt-pt'] = 'Editor Gramático';
		$apps[$x]['menu'][0]['title']['pt-br'] = '';
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