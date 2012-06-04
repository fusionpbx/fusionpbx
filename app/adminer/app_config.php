<?php
	//application details
		$apps[$x]['name'] = "Adminer";
		$apps[$x]['uuid'] = '214b9f02-547b-d49d-f4e9-02987d9581c5';
		$apps[$x]['category'] = 'System';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '3.2.2';
		$apps[$x]['license'] = 'http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0';
		$apps[$x]['url'] = 'http://www.adminer.org/';
		$apps[$x]['description']['en'] = 'Adminer (formerly phpMinAdmin) is a full-featured database management tool written in PHP. Adminer is available for MySQL, PostgreSQL, SQLite, MS SQL and Oracle.';
	
	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'Adminer';
		$apps[$x]['menu'][0]['uuid'] = '1f59d07b-b4f7-4f9e-bde9-312cf491d66e';
		$apps[$x]['menu'][0]['parent_uuid'] = '594d99c5-6128-9c88-ca35-4b33392cec0f';
		$apps[$x]['menu'][0]['category'] = 'external';
		$apps[$x]['menu'][0]['path'] = '<!--{project_path}-->/app/adminer/index.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'adminer';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

?>