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
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('ring_group_delete')) {
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

//get the http value and set it as a php variable
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
	}

//delete the user data
	if (strlen($id)>0) {
		//get the dialplan
			$sql = "select dialplan_uuid from v_ring_groups ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and ring_group_uuid = '".$id."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			foreach ($result as &$row) {
				$dialplan_uuid = $row["dialplan_uuid"];
				$ring_group_context = $row["ring_group_context"];
			}
			unset ($prep_statement);

		//delete the ring group
			$sql = "delete from v_ring_groups ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and ring_group_uuid = '".$id."' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//delete the ring group destinations
			$sql = "delete from v_ring_group_destinations ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and ring_group_uuid = '".$id."' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//delete the dialplan details
			$sql = "delete from v_dialplan_details ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//delete the dialplan
			$sql = "delete from v_dialplans ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//save the xml
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//delete the dialplan context from memcache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$switch_cmd = "memcache delete dialplan:".$ring_group_context;
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			}
	}

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: ring_groups.php");
	return;

?>