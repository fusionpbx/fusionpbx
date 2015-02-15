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
if (permission_exists('schema_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

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
		if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
		if (strlen($schema_uuid) == 0) { $msg .= $text['message-required']."schema_uuid<br>\n"; }
		if (strlen($schema_field_uuid) == 0) { $msg .= $text['message-required']."schema_field_uuid<br>\n"; }
		if (strlen($data_types_name) == 0) { $msg .= $text['message-required'].$text['label-name_value_name']."<br>\n"; }
		if (strlen($data_types_value) == 0) { $msg .= $text['message-required'].$text['label-name_value_value']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
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

				$_SESSION["message"] = $text['message-add'];
				header("Location: schema_field_edit.php?schema_uuid=".$schema_uuid."&id=".$schema_field_uuid);
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

				$_SESSION["message"] = $text['message-update'];
				header("Location: schema_field_edit.php?schema_uuid=".$schema_uuid."&id=".$schema_field_uuid);
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
	require_once "resources/header.php";
	$document['title'] = $text['title-name_value'];

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap=\"nowrap\"><b>".$text['header-name_value']." ".$text['button-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap=\"nowrap\"><b>".$text['header-name_value']." ".$text['button-edit']."</b></td>\n";
	}
	echo "<td width='70%' align=\"right\">";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"history.go(-1);return true;\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo $text['description-name_value']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-name_value_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='data_types_name' maxlength='255' value=\"$data_types_name\">\n";
	echo "<br />\n";
	echo $text['description-name_value_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-name_value_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='data_types_value' maxlength='255' value=\"$data_types_value\">\n";
	echo "<br />\n";
	echo $text['description-name_value_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='schema_uuid' value='$schema_uuid'>\n";
	echo "			<input type='hidden' name='schema_field_uuid' value='$schema_field_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='schema_name_value_uuid' value='$schema_name_value_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

require_once "resources/footer.php";
?>