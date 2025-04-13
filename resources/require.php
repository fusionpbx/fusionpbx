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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//class auto loader
	if (!class_exists('auto_loader')) {
		require_once __DIR__ . "/classes/auto_loader.php";
		$autoload = new auto_loader();
	}

//load config file
	global $config;
	$config = config::load();

//config.conf file not found re-direct the request to the install
	if ($config->is_empty()) {
		header("Location: /core/install/install.php");
		exit;
	}

//compatibility settings - planned to deprecate
	global $conf, $db_type, $db_host, $db_port, $db_name, $db_username, $db_password;
	$conf = $config->configuration();
	$db_type = $config->get('database.0.type');
	$db_host = $config->get('database.0.host');
	$db_port = $config->get('database.0.port');
	$db_name = $config->get('database.0.name');
	$db_username = $config->get('database.0.username');
	$db_password = $config->get('database.0.password');

//set the error reporting
	ini_set('display_errors', '1');
	$error_reporting_scope = $config->get('error.reporting', 'user');
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

//debug info
	//echo "Include Path: ".get_include_path()."\n";
	//echo "Document Root: ".$_SERVER["DOCUMENT_ROOT"]."\n";
	//echo "Project Root: ".$_SERVER["PROJECT_ROOT"]."\n";


//include global functions
	require_once __DIR__ . "/functions.php";

//connect to the database
	global $database;
	$database = database::new(['config' => $config]);

//if not using the command line required files
	global $no_session;
	if (!defined('STDIN') && empty($no_session)) {
		require_once __DIR__ . '/php.php';
	}

//load settings
	global $settings;
	$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid'] ?? '', 'user_uuid' => $_SESSION['user_uuid'] ?? '']);

//check if the cidr range is valid
	global $no_cidr;
	if (!defined('STDIN') && empty($no_cidr)) {
		require_once __DIR__ . '/cidr.php';
	}

//include switch functions when available
	if (file_exists(__DIR__ . '/switch.php')) {
		require_once __DIR__ . '/switch.php';
	}

//change language on the fly - for translate tool (if available)
	if (!defined('STDIN') && isset($_REQUEST['view_lang_code']) && ($_REQUEST['view_lang_code']) != '') {
		$_SESSION['domain']['language']['code'] = $_REQUEST['view_lang_code'];
	}

//change the domain
	if (!empty($_GET["domain_uuid"]) && is_uuid($_GET["domain_uuid"]) && $_GET["domain_change"] == "true" && permission_exists('domain_select')) {

		//include domains
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/app_config.php") && !permission_exists('domain_all')) {
				include_once "app/domains/domains.php";
			}

		//update the domain session variables
			$domain_uuid = $_GET["domain_uuid"];
			$_SESSION["previous_domain_uuid"] = $_SESSION['domain_uuid'];
			$_SESSION['domain_uuid'] = $domain_uuid;

		//get the domain details
			$sql = "select * from v_domains ";
			$sql .= "order by domain_name asc ";
			$domains = $database->select($sql, null, 'all');
			if (!empty($domains)) {
				foreach($domains as $row) {
					$_SESSION['domains'][$row['domain_uuid']] = $row;
				}
			}
			unset($sql, $domains);

		//update the domain session variables
			$_SESSION["domain_name"] = $_SESSION['domains'][$domain_uuid]['domain_name'];
			$_SESSION['domain']['template']['name'] = $_SESSION['domains'][$domain_uuid]['template_name'] ?? null;
			$_SESSION["context"] = $_SESSION["domain_name"];

		//clear the extension array so that it is regenerated for the selected domain
			unset($_SESSION['extension_array']);

		//set the setting arrays
			$domain = new domains();
			$domain->set();
	}
