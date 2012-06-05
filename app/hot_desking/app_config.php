<?php
	//application details
		$apps[$x]['name'] = "Hot Desking";
		$apps[$x]['uuid'] = 'f4ae30f0-68ff-46d2-afd3-34caff2887c9';
		$apps[$x]['category'] = 'Switch';;
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Login into hot desking with an ID and your voicemail password to direct your calls to a remote extension. Then make and receive calls as if you were at your extension.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Hot Desking';
		$apps[$x]['menu'][0]['uuid'] = 'baa57691-37d4-4c7d-b227-f2929202b480';
		$apps[$x]['menu'][0]['parent_uuid'] = 'fd29e39c-c936-f5fc-8e2b-611681b266b5';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/hot_desking/index.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'hot_desk_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'hot_desk_add';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'hot_desk_edit';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][3]['name'] = 'hot_desk_delete';
		$apps[$x]['permissions'][3]['groups'][] = 'superadmin';

?>