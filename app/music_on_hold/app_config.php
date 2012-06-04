<?php
	//application details
		$apps[$x]['name'] = "Music on Hold";
		$apps[$x]['uuid'] = '1dafe0f8-c08a-289b-0312-15baf4f20f81';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Add, Delete, or Play Music on hold files.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Music on Hold';
		$apps[$x]['menu'][0]['uuid'] = '1cd1d6cb-912d-db32-56c3-e0d5699feb9d';
		$apps[$x]['menu'][0]['parent_uuid'] = 'fd29e39c-c936-f5fc-8e2b-611681b266b5';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/music_on_hold/v_music_on_hold.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'music_on_hold_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'music_on_hold_add';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'music_on_hold_delete';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';
?>