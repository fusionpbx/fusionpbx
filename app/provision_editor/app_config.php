<?php
	//application details
		$apps[$x]['name'] = "Provision Editor";
		$apps[$x]['uuid'] = 'a1fd4caf-c3c2-af10-9630-2f3c62050b02';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = 'Provision Editor is an easy ajax based editor.';
		$apps[$x]['description']['es-mx'] = '';
		$apps[$x]['description']['de'] = '';
		$apps[$x]['description']['de-ch'] = '';
		$apps[$x]['description']['de-at'] = '';
		$apps[$x]['description']['fr'] = '';
		$apps[$x]['description']['fr-ca'] = '';
		$apps[$x]['description']['fr-ch'] = '';
		$apps[$x]['description']['pt-pt'] = 'Editor de disposição é um editor simples baseado em ajax.';
		$apps[$x]['description']['pt-br'] = '';

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = 'Provision Editor';
		$apps[$x]['menu'][0]['title']['es-mx'] = '';
		$apps[$x]['menu'][0]['title']['de'] = '';
		$apps[$x]['menu'][0]['title']['de-ch'] = '';
		$apps[$x]['menu'][0]['title']['de-at'] = '';
		$apps[$x]['menu'][0]['title']['fr'] = '';
		$apps[$x]['menu'][0]['title']['fr-ca'] = '';
		$apps[$x]['menu'][0]['title']['fr-ch'] = '';
		$apps[$x]['menu'][0]['title']['pt-pt'] = 'Editor de Provisionamento';
		$apps[$x]['menu'][0]['title']['pt-br'] = '';
		$apps[$x]['menu'][0]['uuid'] = '57773542-a565-1a29-605d-6535da1a0870';
		$apps[$x]['menu'][0]['parent_uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][0]['category'] = 'external';
		$apps[$x]['menu'][0]['path'] = '/app/provision_editor/';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'provision_editor_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';
		
		$apps[$x]['permissions'][1]['name'] = 'provision_editor_save';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';
?>