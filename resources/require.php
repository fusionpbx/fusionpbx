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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//add the document root to the include path
	$config_glob = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	$conf = parse_ini_file($config_glob[0]);
	set_include_path($conf['document.root']);

//config file
	$config_file = $config_glob[0] ?? '';

//check if the config file exists
	$config_exists = file_exists($config_file) ? true : false;

//set the server variables and define project path constant
	$_SERVER["DOCUMENT_ROOT"] = $conf['document.root'];
	$_SERVER["PROJECT_ROOT"] = $conf['document.root'];
	$_SERVER["PROJECT_PATH"]  = $conf['project.path'];
	if (isset($conf['project.path'])) {
		$_SERVER["PROJECT_ROOT"] = $conf['document.root'].'/'.$conf['project.path'];
		if (!defined('PROJECT_ROOT')) { define("PROJECT_ROOT", $conf['document.root'].'/'.$conf['project.path']); }
		if (!defined('PROJECT_PATH')) { define("PROJECT_PATH", $conf['project.path']); }
	}
	else {
		if (!defined('PROJECT_ROOT')) { define("PROJECT_ROOT", $conf['document.root']); }
		if (!defined('PROJECT_PATH')) { define("PROJECT_PATH", ''); }
	}

//set the error reporting
	ini_set('display_errors', '1');
	if (isset($conf['error.reporting'])) {
		$error_reporting_scope = $conf['error.reporting'];
	}
	else {
		$error_reporting_scope = 'user';
	}
	switch ($error_reporting_scope) {
	case 'user':
		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
		break;
	case 'dev':
		error_reporting(E_ALL ^ E_NOTICE);
		break;
	case 'all':
		error_reporting(E_ALL);
		break;
	default:
		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
	}

//get the database connection settings
	//$db_type = $settings['database']['type'];
	//$db_host = $settings['database']['host'];
	//$db_port = $settings['database']['port'];
	//$db_name = $settings['database']['name'];
	//$db_username = $settings['database']['username'];
	//$db_password = $settings['database']['password'];

//get the database connection settings
	$db_type = $conf['database.0.type'];
	$db_host = $conf['database.0.host'];
	$db_port = $conf['database.0.port'];
	$db_name = $conf['database.0.name'];
	$db_username = $conf['database.0.username'];
	$db_password = $conf['database.0.password'];

//debug info
	//echo "Include Path: ".get_include_path()."\n";
	//echo "Document Root: ".$_SERVER["DOCUMENT_ROOT"]."\n";
	//echo "Project Root: ".$_SERVER["PROJECT_ROOT"]."\n";

//class auto loader
	if (!class_exists('auto_loader')) {
		include "resources/classes/auto_loader.php";
		$autoload = new auto_loader();
	}

//additional includes
	require_once "resources/php.php";
	require_once "resources/functions.php";
	if (is_array($conf) && count($conf) > 0) {
		require_once "resources/pdo.php";
		require_once "resources/cidr.php";
		if (file_exists($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/resources/switch.php")) {
			require_once "resources/switch.php";
		}
	}

//change language on the fly - for translate tool (if available)
	if (isset($_REQUEST['view_lang_code']) && ($_REQUEST['view_lang_code']) != '') {
		$_SESSION['domain']['language']['code'] = $_REQUEST['view_lang_code'];
	}

?>
