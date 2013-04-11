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
if (permission_exists('schema_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$schema_name_value_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["schema_field_uuid"]) > 0) {
	$schema_field_uuid = check_str($_GET["schema_field_uuid"]);
}

//POST to PHP variables
	if (count($_POST)>0) {
		//$domain_uuid = check_str($_POST["domain_uuid"]);
		$data_types_name = check_str($_POST["data_types_name"]);
		$data_types_value = check_str($_POST["data_types_value"]);
		$schema_uuid = $_REQUEST["schema_uuid"];
		$schema_field_uuid = $_REQUEST["schema_field_uuid"];
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$schema_name_value_uuid = check_str($_POST["schema_name_value_uuid"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($schema_uuid) == 0) { $msg .= "Please provide: schema_uuid<br>\n"; }
		if (strlen($schema_field_uuid) == 0) { $msg .= "Please provide: schema_field_uuid<br>\n"; }
		if (strlen($data_types_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		if (strlen($data_types_value) == 0) { $msg .= "Please provide: Value<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add") {
				$sql = "insert into v_schema_name_values ";
				$sql .= "(";
				$sql .= "schema_name_value_uuid, ";
				$sql .= "domain_uuid, ";
				$sql .= "schema_uuid, ";
				$sql .= "schema_field_uuid, ";
				$sql .= "data_types_name, ";
				$sql .= "data_types_value ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".uuid()."', ";
				$sql .= "'".$_SESSION['domain_uuid']."', ";
				$sql .= "'$schema_uuid', ";
				$sql .= "'$schema_field_uuid', ";
				$sql .= "'$data_types_name', ";
				$sql .= "'$data_types_value' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=schema_field_edit.php?schema_uuid=$schema_uuid&id=$schema_field_uuid\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_schema_name_values set ";
				$sql .= "data_types_name = '$data_types_name', ";
				$sql .= "data_types_value = '$data_types_value' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and schema_uuid = '$schema_uuid' ";
				$sql .= "and schema_field_uuid = '$schema_field_uuid' ";
				$sql .= "and schema_name_value_uuid = '$schema_name_value_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=schema_field_edit.php?schema_uuid=$schema_uuid&id=$schema_field_uuid\">\n";
				echo "<div align='center'>\n";
				echo "Update Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$schema_uuid = $_GET["schema_uuid"];
		$schema_field_uuid = $_GET["schema_field_uuid"];
		$schema_name_value_uuid = $_GET["id"];
		$sql = "select * from v_schema_name_values ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		//$sql .= "and schema_uuid = '$schema_uuid' ";
		$sql .= "and schema_field_uuid = '$schema_field_uuid' ";
		$sql .= "and schema_name_value_uuid = '$schema_name_value_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$data_types_name = $row["data_types_name"];
			$data_types_value = $row["data_types_value"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap=\"nowrap\"><b>Table Data Types Name Value Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap=\"nowrap\"><b>Table Data Types Name Value Edit</b></td>\n";
	}
	echo "<td width='70%' align=\"right\"><input type='button' class='btn' name='' alt='back' onclick=\"history.go(-1);return true;\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo "Stores the name and value pairs.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='data_types_name' maxlength='255' value=\"$data_types_name\">\n";
	echo "<br />\n";
	echo "Enter the name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Value:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='data_types_value' maxlength='255' value=\"$data_types_value\">\n";
	echo "<br />\n";
	echo "Enter the value.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='schema_uuid' value='$schema_uuid'>\n";
	echo "			<input type='hidden' name='schema_field_uuid' value='$schema_field_uuid'>\n";
	if ($action == "update") {
		echo "			<input type='hidden' name='schema_name_value_uuid' value='$schema_name_value_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

require_once "includes/footer.php";
?>