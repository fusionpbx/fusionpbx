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
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";

//check permissions
	if (permission_exists('hunt_group_add')) {
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

//set the http get/post variable(s) to a php variable
	if (isset($_REQUEST["id"])) {
		$hunt_group_uuid = check_str($_REQUEST["id"]);
		$hunt_group_extension_new = check_str($_REQUEST["ext"]);
	}

//get the v_hunt_group data
	$sql = "select * from v_hunt_groups ";
	$sql .= "where hunt_group_uuid = '$hunt_group_uuid' ";
	$sql .= "and domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$hunt_group_extension = $row["hunt_group_extension"];
		$hunt_group_name = $row["hunt_group_name"]."_copy";
		$hunt_group_type = $row["hunt_group_type"];
		$hunt_group_context = $row["hunt_group_context"];
		$hunt_group_timeout = $row["hunt_group_timeout"];
		$hunt_group_timeout_destination = $row["hunt_group_timeout_destination"];
		$hunt_group_timeout_type = $row["hunt_group_timeout_type"];
		$hunt_group_ringback = $row["hunt_group_ringback"];
		$hunt_group_cid_name_prefix = $row["hunt_group_cid_name_prefix"];
		$hunt_group_pin = $row["hunt_group_pin"];
		$hunt_group_caller_announce = $row["hunt_group_caller_announce"];
		$hunt_group_user_list = $row["hunt_group_user_list"];
		$hunt_group_enabled = $row["hunt_group_enabled"];
		$hunt_group_description = "copy: ".$row["hunt_group_description"];
		break; //limit to 1 row
	}
	unset ($prep_statement);

	//copy the hunt group
		$hunt_group_uuid_new = uuid();
		$sql = "insert into v_hunt_groups ";
		$sql .= "(";
		$sql .= "hunt_group_uuid, ";
		$sql .= "domain_uuid, ";
		$sql .= "hunt_group_extension, ";
		$sql .= "hunt_group_name, ";
		$sql .= "hunt_group_type, ";
		$sql .= "hunt_group_context, ";
		$sql .= "hunt_group_timeout, ";
		$sql .= "hunt_group_timeout_destination, ";
		$sql .= "hunt_group_timeout_type, ";
		$sql .= "hunt_group_ringback, ";
		$sql .= "hunt_group_cid_name_prefix, ";
		$sql .= "hunt_group_pin, ";
		$sql .= "hunt_group_caller_announce, ";
		$sql .= "hunt_group_user_list, ";
		$sql .= "hunt_group_enabled, ";
		$sql .= "hunt_group_description ";
		$sql .= ")";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$hunt_group_uuid_new', ";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$hunt_group_extension_new', ";
		$sql .= "'$hunt_group_name', ";
		$sql .= "'$hunt_group_type', ";
		$sql .= "'$hunt_group_context', ";
		$sql .= "'$hunt_group_timeout', ";
		$sql .= "'$hunt_group_timeout_destination', ";
		$sql .= "'$hunt_group_timeout_type', ";
		$sql .= "'$hunt_group_ringback', ";
		$sql .= "'$hunt_group_cid_name_prefix', ";
		$sql .= "'$hunt_group_pin', ";
		$sql .= "'$hunt_group_caller_announce', ";
		$sql .= "'$hunt_group_user_list', ";
		$sql .= "'$hunt_group_enabled', ";
		$sql .= "'$hunt_group_description' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

	//get the the hunt group destinations
		$sql = "select * from v_hunt_group_destinations ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and hunt_group_uuid = '$hunt_group_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$destination_data = $row["destination_data"];
			$destination_type = $row["destination_type"];
			$destination_profile = $row["destination_profile"];
			$destination_timeout = $row["destination_timeout"];
			$destination_order = $row["destination_order"];
			$destination_enabled = $row["destination_enabled"];
			$destination_description = $row["destination_description"];

			//copy the hunt group destinations
				$hunt_group_destination_uuid = uuid();
				$sql = "insert into v_hunt_group_destinations ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "hunt_group_uuid, ";
				$sql .= "hunt_group_destination_uuid, ";
				$sql .= "destination_data, ";
				$sql .= "destination_type, ";
				$sql .= "destination_profile, ";
				$sql .= "destination_timeout, ";
				$sql .= "destination_order, ";
				$sql .= "destination_enabled, ";
				$sql .= "destination_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$hunt_group_uuid_new', ";
				$sql .= "'$hunt_group_destination_uuid', ";
				$sql .= "'$destination_data', ";
				$sql .= "'$destination_type', ";
				$sql .= "'$destination_profile', ";
				$sql .= "'$destination_timeout', ";
				$sql .= "'$destination_order', ";
				$sql .= "'$destination_enabled', ";
				$sql .= "'$destination_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				//echo $sql."<br><br>";
				//exit();
				unset($sql);
		}
		unset ($prep_statement);

	//synchronize the xml config
		save_hunt_group_xml();

	//redirect the user
		require_once "resources/header.php";
		echo "<meta http-equiv=\"refresh\" content=\"2;url=hunt_groups.php\">\n";
		echo "<div align='center'>\n";
		echo $text['message-copy']."\n";
		echo "</div>\n";
		require_once "resources/footer.php";
		return;

?>