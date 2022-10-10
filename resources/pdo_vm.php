<?php
/*
 FusionPBX
 Version: MPL 1.1

 The contents of this file are subject to the Mozilla Public License Version
 1.1 (the "License"); you may not use this file except in compliance with
 the License. You may obtain a copy of the License at
 http://www.mozilla.org/MPL/

 Software distributed under the License is distributed on an "AS IS" basis,
 WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 for the specific language governing rights and limitations under the
 License.

 The Original Code is FusionPBX

 The Initial Developer of the Original Code is
 Mark J Crane <markjcrane@fusionpbx.com>
 Portions created by the Initial Developer are Copyright (C) 2008-2012
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require "resources/require.php";

//get the contents of xml_cdr.conf.xml
	$conf_xml_string = file_get_contents($_SESSION['switch']['conf']['dir'].'/autoload_configs/voicemail.conf.xml');

//parse the xml to get the call detail record info
	try {
		$conf_xml = simplexml_load_string($conf_xml_string);
	}
	catch(Exception $e) {
		echo $e->getMessage();
	}

//define variables
	$odbc_dsn = '';
	$odbc_db_user = '';
	$odbc_db_pass = '';

//find the odbc info
	foreach ($conf_xml->profiles->profile->param as $row) {
		if ($row->attributes()->name == "odbc-dsn") {
			$odbc_array = explode(":", $row->attributes()->value);
			$odbc_dsn = $odbc_array[0];
			$odbc_db_user = $odbc_array[1];
			$odbc_db_pass = $odbc_array[2];
		}
	}

//database connection
	try {
		unset($db);
		if (strlen($odbc_dsn) == 0) {
			$db = new PDO('sqlite:'.$_SESSION['switch']['db']['dir'].'/voicemail_default.db'); //sqlite 3
		}
		else {
			$db = new PDO("odbc:$odbc_dsn", "$odbc_db_user", "$odbc_db_pass");
		}
	}
	catch (PDOException $e) {
	   echo 'Connection failed: ' . $e->getMessage();
	}

 ?>