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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set the variables
	$profile = $_GET['profile'];
	$command = $_GET['cmd'];

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		//if reloadxml then run reloadacl, reloadxml and rescan the external profile for new gateways
			if ($command == "api reloadxml") {
				//reloadxml
					message::add(rtrim(event_socket_request($fp, $command)), 'alert');
					unset($command);

				//clear the apply settings reminder
					$_SESSION["reload_xml"] = false;

				//rescan the external profile to look for new or stopped gateways
					$command = 'api sofia profile external rescan';
					message::add(rtrim(event_socket_request($fp, $command)), 'alert');
					unset($command);
			}

		//cache flush
			if ($command == "api cache flush") {
				$cache = new cache;
				$response = $cache->flush();

				message::add($response, 'alert');
			}

		//reloadacl
			if ($command == "api reloadacl") {
				message::add(rtrim(event_socket_request($fp, $command)), 'alert');
				unset($command);
			}

		//sofia profile
			if (substr($command, 0, 17) == "api sofia profile") {
				message::add(($profile ? '<strong>'.$profile.'</strong>: ' : null).rtrim(event_socket_request($fp, $command)), 'alert', 3000);
			}

		//close the connection
			fclose($fp);
	}

//redirect the user
	header("Location: sip_status.php");

?>