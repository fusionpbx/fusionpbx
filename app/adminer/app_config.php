<?php
	//application details
		$apps[$x]['name'] = "Adminer";
		$apps[$x]['uuid'] = "214b9f02-547b-d49d-f4e9-02987d9581c5";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "3.2.2";
		$apps[$x]['license'] = "http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0";
		$apps[$x]['url'] = "http://www.adminer.org/";
		$apps[$x]['description']['en-us'] = "Adminer (formerly phpMinAdmin) is a full-featured database management tool written in PHP. Adminer is available for MySQL, PostgreSQL, SQLite, MS SQL and Oracle.";
		$apps[$x]['description']['es-cl'] = "Adminer (anteriormente phpMinAdmin) es una herramienta completa para la gestión de bases de datos escrita en PHP. Adminer está disponible para MySQL, PostgreSQL, SQLite, MS SQL y Oracle)";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Adminer (précédemment phpMinAdmin) est un outil gestion de base de données complet Ã©crite en php. Adminer est disponible pour MySQL, PostgreSQL, SQLite, MS SQL et Oracle.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Adminer (anteriormente phpMinAdmin) é uma ferramenta completa para gestão de bases de dados escrita em PHP. O Adminer está disponível para MySQL, PostgreSQL, SQLite, MS SQL e Oracle.";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "Adminer";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Administrador";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Admin BDD";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Administrador";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "1f59d07b-b4f7-4f9e-bde9-312cf491d66e";
		$apps[$x]['menu'][0]['parent_uuid'] = "594d99c5-6128-9c88-ca35-4b33392cec0f";
		$apps[$x]['menu'][0]['category'] = "external";
		$apps[$x]['menu'][0]['path'] = "<!--{project_path}-->/app/adminer/index.php";
		$apps[$x]['menu'][0]['groups'][] = "superadmin";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "adminer";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "1f59d07b-b4f7-4f9e-bde9-312cf491d66e";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

?>
