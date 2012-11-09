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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('virtual_tables_data_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (count($_GET)>0) {

	//declare variable(s)
		$virtual_table_parent_id = '';

	//get the http get and set them as php variables
		$virtual_data_row_uuid = check_str($_GET["virtual_data_row_uuid"]);
		$virtual_data_parent_row_uuid = check_str($_GET["virtual_data_parent_row_uuid"]);
		$virtual_table_uuid = check_str($_GET["virtual_table_uuid"]);

	//show the results and redirect
		require_once "includes/header.php";

	//get the virtual_table_parent_id from the child table
		if (strlen($virtual_table_parent_id) == 0) {
			$sql = "select * from v_virtual_tables ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and virtual_table_uuid = '$virtual_table_uuid' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$virtual_table_parent_id = $row["virtual_table_parent_id"];
			}
		}

	//delete the child data
		$sql = "delete from v_virtual_table_data ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and virtual_data_parent_row_uuid = '$virtual_data_row_uuid' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//delete the data
		$sql = "delete from v_virtual_table_data ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and virtual_data_row_uuid = '$virtual_data_row_uuid' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//mark the the item as deleted and who deleted it
		//$sql  = "update v_virtual_table_data set ";
		//$sql .= "virtual_data_del_date = now(), ";
		//$sql .= "virtual_data_del_user = '".$_SESSION["username"]."' ";
		//$sql .= "where domain_uuid = '$domain_uuid' ";
		//$sql .= "and virtual_data_row_uuid = '$virtual_data_row_uuid' ";
		//$db->exec(check_sql($sql));
		//$lastinsertid = $db->lastInsertId($id);
		//unset($sql);

	//set the meta redirect
		if (strlen($virtual_data_parent_row_uuid) == 0) {
			echo "<meta http-equiv=\"refresh\" content=\"2;url=virtual_table_data_view.php?id=$virtual_table_uuid&virtual_data_row_uuid=$virtual_data_row_uuid\">\n";
		}
		else {
			echo "<meta http-equiv=\"refresh\" content=\"2;url=virtual_table_data_edit.php?virtual_table_uuid=$virtual_table_parent_id&virtual_data_row_uuid=$virtual_data_parent_row_uuid\">\n";
		}

	//show a message to the user before the redirect
		echo "<div align='center'>\n";
		echo "Delete Complete\n";
		echo "</div>\n";
		require_once "includes/footer.php";
		return;
}

?>