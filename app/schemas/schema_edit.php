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
if (permission_exists('schema_add') || permission_exists('schema_edit')) {
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
		$schema_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http post variables
	if (count($_POST)>0) {
		$schema_category = check_str($_POST["schema_category"]);
		$schema_category_other = check_str($_POST["schema_category_other"]);
		if (strlen($schema_category_other) > 0) { $schema_category = $schema_category_other; }
		$schema_label = check_str($_POST["schema_label"]);
		$schema_name = check_str($_POST["schema_name"]);
		$schema_auth = check_str($_POST["schema_auth"]);
		$schema_captcha = check_str($_POST["schema_captcha"]);
		$schema_parent_uuid = check_str($_POST["schema_parent_uuid"]);
		$schema_description = check_str($_POST["schema_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$schema_uuid = check_str($_POST["schema_uuid"]);
	}

	//check for all required data
		if (strlen($schema_name) == 0) { $msg .= $text['message-required'].$text['label-schema_name']."<br>\n"; }
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
				$schema_uuid = uuid();
				$sql = "insert into v_schemas ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "schema_uuid, ";
				$sql .= "schema_category, ";
				$sql .= "schema_label, ";
				$sql .= "schema_name, ";
				$sql .= "schema_auth, ";
				$sql .= "schema_captcha, ";
				$sql .= "schema_parent_uuid, ";
				$sql .= "schema_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$schema_uuid', ";
				$sql .= "'$schema_category', ";
				$sql .= "'$schema_label', ";
				$sql .= "'$schema_name', ";
				$sql .= "'$schema_auth', ";
				$sql .= "'$schema_captcha', ";
				if (strlen($schema_parent_uuid) == 0) {
					$sql .= "null, ";
				}
				else {
					$sql .= "'$schema_parent_uuid', ";
				}
				$sql .= "'$schema_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-add'];
				header("Location: schemas.php");
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_schemas set ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "schema_category = '$schema_category', ";
				$sql .= "schema_label = '$schema_label', ";
				$sql .= "schema_name = '$schema_name', ";
				$sql .= "schema_auth = '$schema_auth', ";
				$sql .= "schema_captcha = '$schema_captcha', ";
				if (strlen($schema_parent_uuid) == 0) {
					$sql .= "schema_parent_uuid = null, ";
				}
				else {
					$sql .= "schema_parent_uuid = '$schema_parent_uuid', ";
				}
				$sql .= "schema_description = '$schema_description' ";
				$sql .= "where schema_uuid = '$schema_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-update'];
				header("Location: schemas.php");
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$schema_uuid = $_GET["id"];
		$sql = "select * from v_schemas ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and schema_uuid = '$schema_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$schema_category = $row["schema_category"];
			$schema_label = $row["schema_label"];
			$schema_name = $row["schema_name"];
			$schema_auth = $row["schema_auth"];
			$schema_captcha = $row["schema_captcha"];
			$schema_parent_uuid = $row["schema_parent_uuid"];
			$schema_description = $row["schema_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-schema'];

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-schema']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='schemas.php'\" value='".$text['button-back']."'>\n";
	if (strlen($schema_uuid) > 0) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-view']."' onclick=\"window.location='schema_data_view.php?id=".$row["schema_uuid"]."'\" value='".$text['button-view']."'>\n";
		echo "	<input type='button' class='btn' name='' alt='".$text['button-import']."' onclick=\"window.location='schema_import.php?id=".$row["schema_uuid"]."'\" value='".$text['button-import']."'>\n";
	}
	include "export/index.php";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo $text['description-schema']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$select_name = 'v_schemas';$field_name = 'schema_category';$sql_where_optional = "";$field_current_value = $schema_category;
	echo html_select_other($db, $select_name, $field_name, $sql_where_optional, $field_current_value);
	echo $text['description-category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='schema_label' maxlength='255' value=\"$schema_label\">\n";
	echo "<br />\n";
	echo $text['description-label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='schema_name' maxlength='255' value=\"$schema_name\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-authentication']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='schema_auth'>\n";
	echo "	<option value=''></option>\n";
	if ($schema_auth == "yes") {
		echo "	<option value='yes' SELECTED >".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='yes'>".$text['option-true']."</option>\n";
	}
	if ($schema_auth == "no") {
		echo "	<option value='no' SELECTED >".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='no'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-authentication']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-parent_schema']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "			<select name='schema_parent_uuid' class='formfld'>\n";
	echo "			<option value=''></option>\n";
	$sql = "select * from v_schemas ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and schema_name not like '".$schema_name."'";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		if ($row["schema_uuid"] == $schema_parent_uuid) {
			echo "			<option value='".$row["schema_uuid"]."' selected>".$row["schema_name"]."</option>\n";
		}
		else {
			echo "			<option value='".$row["schema_uuid"]."'>".$row["schema_name"]."</option>\n";
		}
	}
	echo "			</select>\n";

	echo "<br />\n";
	echo $text['description-parent_schema']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' name='schema_description' rows='4'>$schema_description</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='schema_uuid' value='$schema_uuid'>\n";
	}
	echo "			<input type='hidden' name='schema_captcha' value='$schema_captcha'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	if ($action == "update") {
		require "schema_fields.php";
	}


//show the footer
	require_once "resources/footer.php";
?>