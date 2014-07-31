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
if (permission_exists('dialplan_delete')
	|| permission_exists('inbound_route_delete')
	|| permission_exists('outbound_route_delete')
	|| permission_exists('fifo_delete')
	|| permission_exists('time_condition_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//set the variables
	if (count($_GET) > 0) {
		$dialplan_detail_uuid = check_str($_GET["id"]);
		$dialplan_uuid = check_str($_REQUEST["dialplan_uuid"]);
		$app_uuid = check_str($_REQUEST["app_uuid"]);
	}

//delete the dialplan detail
	if (strlen($dialplan_detail_uuid) > 0) {
		//delete child data
			$sql = "delete from v_dialplan_details ";
			//$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "where dialplan_detail_uuid = '$dialplan_detail_uuid' ";
			$db->query($sql);
			unset($sql);

		//synchronize the xml config
			save_dialplan_xml();

		//delete the dialplan context from memcache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$switch_cmd = "memcache delete dialplan:".$_SESSION["context"];
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			}
	}

//save the message to a session variable
	$_SESSION['message'] = $text['message-delete'];

//redirect the browser
	header("Location: dialplan_edit.php?id=".$dialplan_uuid.(($app_uuid != '') ? "&app_uuid=".$app_uuid : null));
	exit;

?>