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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists("registration_domain") || permission_exists("registration_all") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the variables
	$cmd = check_str($_GET['cmd']);
	$rdr = check_str($_GET['rdr']);
	$profile = check_str($_GET['profile']);
	$domain = check_str($_GET['domain']);
	$show = check_str($_GET['show']);
	$user = check_str($_GET['user']);
	$agent = check_str($_GET['agent']);

//get the vendor
	if (preg_replace('/^.*?(aastra).*$/i', '$1', strtolower($agent)) == "aastra") {
		$vendor = "aastra";
	}
	if (preg_replace('/^.*?(cisco).*$/i', '$1', strtolower($agent)) == "cisco") {
		$vendor = "cisco";
	}
	if (preg_replace('/^.*?(grandstream).*$/i', '$1', strtolower($agent)) == "grandstream") {
		$vendor = "grandstream";
	}
	if (preg_replace('/^.*?(polycom).*$/i', '$1', strtolower($agent)) == "polycom") {
		$vendor = "polycom";
	}
	if (preg_replace('/^.*?(yealink).*$/i', '$1', strtolower($agent)) == "yealink") {
		$vendor = "yealink";
	}
	if (preg_replace('/^.*?(vp530p).*$/i', '$1', strtolower($agent)) == "vp530p") {
		$vendor = "yealink";
	}
	if (preg_replace('/^.*?(snom).*$/i', '$1', strtolower($agent)) == "snom") {
		$vendor = "snom";
	}

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		//prepare the command
			if ($cmd == "unregister") {
				$command = "sofia profile ".$profile." flush_inbound_reg ".$user." reboot";
			}
			else {
				$command = "lua app.lua event_notify ".$profile." ".$cmd." ".$user." ".$vendor;

				//if ($cmd == "check_sync") {
				//	$command = "sofia profile ".$profile." check_sync ".$user;
				//}
			}
		//send the command
			$response = event_socket_request($fp, "api ".$command);
			$response = event_socket_request($fp, "api log notice ".$command);
		//show the response
			$_SESSION['message'] = $text['label-event']." ".ucwords($cmd)."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$text['label-response'].$response;
		//close the connection
			fclose($fp);
	}

//redirect the user
	if ($rdr == "false") {
		//redirect false
		echo $response;
	}
	else {
		header("Location: status_registrations.php?profile=".$profile."&show=".$show);
	}

?>