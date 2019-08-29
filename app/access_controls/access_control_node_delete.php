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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.
	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('access_control_node_delete')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete access control node
	if (is_uuid($_GET['id']) && is_uuid($_GET['access_control_uuid'])) {
		$access_control_node_uuid = $_GET["id"];
		$access_control_uuid = $_GET["access_control_uuid"];

		$array['access_control_nodes'][0]['access_control_node_uuid'] = $access_control_node_uuid;
		$array['access_control_nodes'][0]['access_control_uuid'] = $access_control_uuid;
		$database = new database;
		$database->app_name = 'access_control';
		$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
		$database->delete($array);
		unset($array);

		//clear the cache
		$cache = new cache;
		$cache->delete("configuration:acl.conf");

		//create the event socket connection
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) { event_socket_request($fp, "api reloadacl"); }

		//set message
		message::add($text['message-delete']);
	}

//redirect the browser
	header('Location: access_control_edit.php?id='.$access_control_uuid);

?>
