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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//ensure that $_SERVER["DOCUMENT_ROOT"] is defined
	include "root.php";

//find and include the config.php file
	$config_exists = false;
	if (file_exists("/etc/fusionpbx/config.php")) {
		$config_exists = true;
		include "/etc/fusionpbx/config.php";
	}
	elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
		$config_exists = true;
		include "/usr/local/etc/fusionpbx/config.php";
	}
	elseif (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/config.php")) {
		$config_exists = true;
		include "resources/config.php";
	}

//class auto loader
	include "resources/classes/auto_loader.php";
	$autoload = new auto_loader();

//additional includes
	require_once "resources/php.php";
	require_once "resources/functions.php";
	if ($config_exists) {
		require "resources/pdo.php";
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
