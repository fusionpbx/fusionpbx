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
	Portions created by the Initial Developer are Copyright (C) 2016-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";

//start the session
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

//check the domain cidr range 
	if (isset($_SESSION['cdr']["cidr"]) && !defined('STDIN')) {
		$found = false;
		foreach($_SESSION['cdr']["cidr"] as $cidr) {
			if (check_cidr($cidr, $_SERVER['REMOTE_ADDR'])) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			echo "access denied";
			exit;
		}
	}

//increase limits
	set_time_limit(3600);
	ini_set('memory_limit', '256M');
	ini_set("precision", 6);

//import the call detail records from HTTP POST or file system
	$cdr = new xml_cdr;
	$cdr->post();
	$cdr->read_files();

?>
