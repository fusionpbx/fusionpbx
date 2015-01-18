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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('schema_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (count($_GET)>0) {

	//declare variable(s)
		$schema_parent_id = '';

	//get the http get and set them as php variables
		$data_row_uuid = check_str($_GET["data_row_uuid"]);
		$data_parent_row_uuid = check_str($_GET["data_parent_row_uuid"]);
		$schema_uuid = check_str($_GET["schema_uuid"]);

	//show the results and redirect
		require_once "resources/header.php";

	//get the schema_parent_id from the child table
		if (strlen($schema_parent_id) == 0) {
			$sql = "select * from v_schemas ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and schema_uuid = '$schema_uuid' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$schema_parent_id = $row["schema_parent_id"];
			}
		}

	//delete the child data
		$sql = "delete from v_schema_data ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and data_parent_row_uuid = '$data_row_uuid' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//delete the data
		$sql = "delete from v_schema_data ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and data_row_uuid = '$data_row_uuid' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//mark the the item as deleted and who deleted it
		//$sql  = "update v_schema_data set ";
		//$sql .= "data_del_date = now(), ";
		//$sql .= "data_del_user = '".$_SESSION["username"]."' ";
		//$sql .= "where domain_uuid = '$domain_uuid' ";
		//$sql .= "and data_row_uuid = '$data_row_uuid' ";
		//$db->exec(check_sql($sql));
		//$lastinsertid = $db->lastInsertId($id);
		//unset($sql);

	//redirect user
		$_SESSION["message"] = $text['message-delete'];
		if (strlen($data_parent_row_uuid) == 0) {
			header("Location: schema_data_view.php?id=".$schema_uuid."&data_row_uuid=".$data_row_uuid);
		}
		else {
			header("Location: schema_data_edit.php?schema_uuid=".$schema_parent_id."&data_row_uuid=".$data_parent_row_uuid);
		}
		return;
}

?>