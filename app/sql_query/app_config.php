<?php
	//application details
		$apps[$x]['name'] = "SQL Query";
		$apps[$x]['uuid'] = 'a8b8ca29-083d-fb9b-5552-cc272de18ea6';
		$apps[$x]['category'] = 'System';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Run Structur Query Language commands.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'SQL Query';
		$apps[$x]['menu'][0]['uuid'] = 'a894fed7-5a17-f695-c3de-e32ce58b3794';
		$apps[$x]['menu'][0]['parent_uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/sql_query/v_sql_query.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'sql_query_execute';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'sql_query_backup';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';
?>