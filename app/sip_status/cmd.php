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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set the variables
	$cmd = check_str($_GET['cmd']);
	$rdr = check_str($_GET['rdr']);

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		//if reloadxml then run reloadacl, reloadxml and rescan the external profile for new gateways
			if ($cmd == "api reloadxml") {
				//reloadxml
					if ($cmd == "api reloadxml") {
						$response = event_socket_request($fp, $cmd);
						unset($cmd);
					}

				//clear the apply settings reminder
					$_SESSION["reload_xml"] = false;

				//rescan the external profile to look for new or stopped gateways
					$tmp_cmd = 'api sofia profile external rescan';
					$response = event_socket_request($fp, $tmp_cmd);
					unset($tmp_cmd);
			}

		//memcache flush
			if ($cmd == "api memcache flush") {
				$response = event_socket_request($fp, $cmd);
				unset($cmd);
			}

		//reloadacl
			if ($cmd == "api reloadacl") {
				$response = event_socket_request($fp, $cmd);
				unset($cmd);
			}

		//sofia profile
			if (substr($cmd, 0, 17) == "api sofia profile") {
				$response = event_socket_request($fp, $cmd);
			}

		//close the connection
			fclose($fp);
	}

//redirect the user
	if ($rdr == "false") {
		//redirect false
		echo $response;
	}
	else {
		$_SESSION["message"] = $response;
		header("Location: sip_status.php");
	}

?>