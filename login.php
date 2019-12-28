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
	Mark J. Crane <markjcrane@fusionpbx.com>
*/
//includes
	include "root.php";
	require_once "resources/require.php";

//start session
	if (!isset($_SESSION)) { session_start(); }

//if config.php file does not exist then redirect to the install page
	if (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
		//original directory
	}
	else if (file_exists($_SERVER["PROJECT_ROOT"]."/includes/config.php")) {
		//move config.php from the includes to resources directory.
		rename($_SERVER["PROJECT_ROOT"]."/includes/config.php", $_SERVER["PROJECT_ROOT"]."/resources/config.php");
	}
	else if (file_exists("/etc/fusionpbx/config.php")) {
		//linux
	}
	else if (file_exists("/usr/local/etc/fusionpbx/config.php")) {
		//bsd
	}
	else {
		header("Location: ".PROJECT_PATH."/core/install/install.php");
		exit;
	}

//use custom login, if present, otherwise use default login
	if (file_exists($_SERVER["PROJECT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/login.php")) {
		require_once "themes/".$_SESSION['domain']['template']['name']."/login.php";
	}
	else {
		require_once "resources/login.php";
	}

?>
