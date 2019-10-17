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
	James Rose <james.o.rose@gmail.com>
*/
//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('ring_group_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http value and set it as a php variable
	$ring_group_uuid = $_GET["id"];

//delete the user data
	if (is_uuid($ring_group_uuid)) {
		
		//get the dialplan_uuid
			$sql = "select * from v_ring_groups ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and ring_group_uuid = :ring_group_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['ring_group_uuid'] = $ring_group_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($array) && @sizeof($array) != 0) {
				$dialplan_uuid = $row["dialplan_uuid"];
				$ring_group_context = $row["ring_group_context"];
			}
			unset($sql, $parameters, $row);

		//add the dialplan permission
			$p = new permissions;
			$p->add('dialplan_delete', 'temp');

		//delete the data
			$array['dialplan_details'][]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][]['dialplan_uuid'] = $dialplan_uuid;
			$array['ring_group_destinations'][]['ring_group_uuid'] = $ring_group_uuid;
			$array['ring_group_users'][]['ring_group_uuid'] = $ring_group_uuid;
			$array['ring_groups'][]['ring_group_uuid'] = $ring_group_uuid;
			$database = new database;
			$database->app_name = 'ring_groups';
			$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
			$database->delete($array);
			//$message = $database->message;

		//remove the temporary permission
			$p->delete('dialplan_delete', 'temp');

		//save the xml
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$ring_group_context);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header("Location: ring_groups.php");
	exit;

?>