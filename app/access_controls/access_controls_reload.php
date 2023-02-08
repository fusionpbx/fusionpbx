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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
if (permission_exists('access_control_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set the variables
	$search = $_REQUEST['search'];

//create event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		//run the command
			$result = rtrim(event_socket_request($fp, 'api reloadacl'));

		//add message
			message::add($result, 'alert');

		//close the connection
			fclose($fp);
	}

//redirect
	$search = preg_replace('#[^a-zA-Z0-9_\-\.]# ', '', $search);
	$location = 'access_controls.php'.($search != '' ? "?search=".urlencode($search) : null);

	header("Location: ".$location);

?>