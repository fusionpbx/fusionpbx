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
	  Portions created by the Initial Developer are Copyright (C) 2008-2023
	  the Initial Developer. All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	 */

//load the config object to handle all config file functions
	require_once __DIR__ . '/classes/config.php';

//allow the config object to be accessed globally
	global $config;

//create a new configuration object to get/set settings
	$config = new config();

//load the configuration
	$config->load();

//class auto loader
	if (!class_exists('auto_loader')) {
		include "resources/classes/auto_loader.php";
		new auto_loader();
	}

//check if composer is configured
	if (file_exists(__DIR__ . '/autoload.php')) {
		require_once __DIR__ . '/autoload.php';
	}

//set super globals
	$_SERVER["DOCUMENT_ROOT"] = PROJECT_ROOT;
	$_SERVER["PROJECT_ROOT"] = PROJECT_ROOT;
	$_SERVER["PROJECT_PATH"] = PROJECT_PATH;

//set the error reporting
	ini_set('display_errors', '1');
	if (isset($conf['error.reporting'])) {
		$error_reporting_scope = $conf['error.reporting'];
	} else {
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

//additional includes
	require_once "resources/php.php";
	require_once "resources/functions.php";
	if ($config->lines() > 0) {
		require_once "resources/pdo.php";
		if (!defined('STDIN')) {
			require_once "resources/cidr.php";
		}
		if (file_exists($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/resources/switch.php")) {
			require_once "resources/switch.php";
		}
	}

//change language on the fly - for translate tool (if available)
	if (!defined('STDIN') && isset($_REQUEST['view_lang_code']) && ($_REQUEST['view_lang_code']) != '') {
		$_SESSION['domain']['language']['code'] = $_REQUEST['view_lang_code'];
	}
?>
