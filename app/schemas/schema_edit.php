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
if (permission_exists('schema_add') || permission_exists('schema_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

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
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($schema_category) == 0) { $msg .= "Please provide: Schema Category<br>\n"; }
		//if (strlen($schema_label) == 0) { $msg .= "Please provide: Label<br>\n"; }
		if (strlen($schema_name) == 0) { $msg .= "Please provide: Schema Name<br>\n"; }
		//if (strlen($schema_auth) == 0) { $msg .= "Please provide: Authentication<br>\n"; }
		//if (strlen($schema_captcha) == 0) { $msg .= "Please provide: Captcha<br>\n"; }
		//if (strlen($schema_parent_uuid) == 0) { $msg .= "Please provide: Parent Schema<br>\n"; }
		//if (strlen($schema_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=schemas.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
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

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=schemas.php\">\n";
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
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>Schema</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	if (strlen($schema_uuid) > 0) {
		echo "		<input type='button' class='btn' name='' alt='view' onclick=\"window.location='schema_data_view.php?id=".$row["schema_uuid"]."'\" value='View'>&nbsp;&nbsp;\n";
		echo "		<input type='button' class='btn' name='' alt='import' onclick=\"window.location='schema_import.php?id=".$row["schema_uuid"]."'\" value='Import'>&nbsp;&nbsp;\n";
	}
	include "export/index.php";
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='schemas.php'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo "Provides the ability to quickly define information to store and dynamically makes tools available to view, add, edit, delete, and search. <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Category:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$select_name = 'v_schemas';$field_name = 'schema_category';$sql_where_optional = "";$field_current_value = $schema_category;
	echo html_select_other($db, $select_name, $field_name, $sql_where_optional, $field_current_value);
	echo "Select the category.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Label:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='schema_label' maxlength='255' value=\"$schema_label\">\n";
	echo "<br />\n";
	echo "Enter the label.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='schema_name' maxlength='255' value=\"$schema_name\">\n";
	echo "<br />\n";
	echo "Enter the schema name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Authentication:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='schema_auth'>\n";
	echo "	<option value=''></option>\n";
	if ($schema_auth == "yes") { 
		echo "	<option value='yes' SELECTED >yes</option>\n";
	}
	else {
		echo "	<option value='yes'>yes</option>\n";
	}
	if ($schema_auth == "no") { 
		echo "	<option value='no' SELECTED >no</option>\n";
	}
	else {
		echo "	<option value='no'>no</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Choose whether to require authentication.\n";
	echo "</td>\n";
	echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	//echo "	Captcha:\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "	<select class='formfld' name='schema_captcha'>\n";
	//echo "	<option value=''></option>\n";
	//if ($schema_captcha == "yes") { 
	//	echo "	<option value='yes' SELECTED >yes</option>\n";
	//}
	//else {
	//	echo "	<option value='yes'>yes</option>\n";
	//}
	//if ($schema_captcha == "no") { 
	//	echo "	<option value='no' SELECTED >no</option>\n";
	//}
	//else {
	//	echo "	<option value='no'>no</option>\n";
	//}
	//echo "	</select>\n";
	//echo "<br />\n";
	//echo "Choose whether to require captcha.\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Parent Schema:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "			<select name='schema_parent_uuid' class='formfld'>\n";
	echo "			<option value=''></option>\n";
	$sql = "select * from v_schemas ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
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
	echo "Select a parent schema.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' name='schema_description' rows='4'>$schema_description</textarea>\n";
	echo "<br />\n";
	echo "Enter a description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='schema_uuid' value='$schema_uuid'>\n";
	}
	echo "				<input type='hidden' name='schema_captcha' value='$schema_captcha'>\n";
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	if ($action == "update") {
		require "schema_fields.php";
	}

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "includes/footer.php";
?>