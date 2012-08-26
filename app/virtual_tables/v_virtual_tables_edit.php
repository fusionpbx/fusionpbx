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
if (permission_exists('virtual_tables_add') || permission_exists('virtual_tables_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$virtual_table_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http post variables
	if (count($_POST)>0) {
		$virtual_table_category = check_str($_POST["virtual_table_category"]);
		$virtual_table_category_other = check_str($_POST["virtual_table_category_other"]);
		if (strlen($virtual_table_category_other) == 0) { $virtual_table_category = $virtual_table_category_other; }
		$virtual_table_label = check_str($_POST["virtual_table_label"]);
		$virtual_table_name = check_str($_POST["virtual_table_name"]);
		$virtual_table_auth = check_str($_POST["virtual_table_auth"]);
		$virtual_table_captcha = check_str($_POST["virtual_table_captcha"]);
		$virtual_table_parent_uuid = check_str($_POST["virtual_table_parent_uuid"]);
		$virtual_table_description = check_str($_POST["virtual_table_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$virtual_table_uuid = check_str($_POST["virtual_table_uuid"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($virtual_table_category) == 0) { $msg .= "Please provide: Table Category<br>\n"; }
		//if (strlen($virtual_table_label) == 0) { $msg .= "Please provide: Label<br>\n"; }
		if (strlen($virtual_table_name) == 0) { $msg .= "Please provide: Table Name<br>\n"; }
		//if (strlen($virtual_table_auth) == 0) { $msg .= "Please provide: Authentication<br>\n"; }
		//if (strlen($virtual_table_captcha) == 0) { $msg .= "Please provide: Captcha<br>\n"; }
		//if (strlen($virtual_table_parent_uuid) == 0) { $msg .= "Please provide: Parent Table<br>\n"; }
		//if (strlen($virtual_table_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
				$virtual_table_uuid = uuid();
				$sql = "insert into v_virtual_tables ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "virtual_table_uuid, ";
				$sql .= "virtual_table_category, ";
				$sql .= "virtual_table_label, ";
				$sql .= "virtual_table_name, ";
				$sql .= "virtual_table_auth, ";
				$sql .= "virtual_table_captcha, ";
				$sql .= "virtual_table_parent_uuid, ";
				$sql .= "virtual_table_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$virtual_table_uuid', ";
				$sql .= "'$virtual_table_category', ";
				$sql .= "'$virtual_table_label', ";
				$sql .= "'$virtual_table_name', ";
				$sql .= "'$virtual_table_auth', ";
				$sql .= "'$virtual_table_captcha', ";
				if (strlen($virtual_table_parent_uuid) == 0) {
					$sql .= "null, ";
				}
				else {
					$sql .= "'$virtual_table_parent_uuid', ";
				}
				$sql .= "'$virtual_table_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=v_virtual_tables.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_virtual_tables set ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "virtual_table_category = '$virtual_table_category', ";
				$sql .= "virtual_table_label = '$virtual_table_label', ";
				$sql .= "virtual_table_name = '$virtual_table_name', ";
				$sql .= "virtual_table_auth = '$virtual_table_auth', ";
				$sql .= "virtual_table_captcha = '$virtual_table_captcha', ";
				if (strlen($virtual_table_parent_uuid) == 0) {
					$sql .= "virtual_table_parent_uuid = null, ";
				}
				else {
					$sql .= "virtual_table_parent_uuid = '$virtual_table_parent_uuid', ";
				}
				$sql .= "virtual_table_description = '$virtual_table_description' ";
				$sql .= "where virtual_table_uuid = '$virtual_table_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=v_virtual_tables.php\">\n";
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
		$virtual_table_uuid = $_GET["id"];
		$sql = "select * from v_virtual_tables ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and virtual_table_uuid = '$virtual_table_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$virtual_table_category = $row["virtual_table_category"];
			$virtual_table_label = $row["virtual_table_label"];
			$virtual_table_name = $row["virtual_table_name"];
			$virtual_table_auth = $row["virtual_table_auth"];
			$virtual_table_captcha = $row["virtual_table_captcha"];
			$virtual_table_parent_uuid = $row["virtual_table_parent_uuid"];
			$virtual_table_description = $row["virtual_table_description"];
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
	echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Virtual Table Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Virtual Table Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'>\n";
	if (strlen($row[virtual_table_uuid]) > 0) {
		echo "		<input type='button' class='btn' name='' alt='view' onclick=\"window.location='v_virtual_table_data_view.php?id=".$row[virtual_table_uuid]."'\" value='view'>&nbsp;&nbsp;\n";
		echo "		<input type='button' class='btn' name='' alt='import' onclick=\"window.location='v_virtual_tables_import.php?id=".$row[virtual_table_uuid]."'\" value='import'>&nbsp;&nbsp;\n";
	}
	include "export/index.php";
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_virtual_tables.php'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo "Provides the ability to quickly define information to store and dynamically makes tools available to view, add, edit, delete, and search. <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Table Category:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//echo "	<input class='formfld' type='text' name='virtual_table_category' maxlength='255' value=\"$virtual_table_category\">\n";
	$table_name = 'v_virtual_tables';$field_name = 'virtual_table_category';$sql_where_optional = "";$field_current_value = $virtual_table_category;
	echo html_select_other($db, $table_name, $field_name, $sql_where_optional, $field_current_value);
	echo "<br />\n";
	echo "Enter the category.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Label:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='virtual_table_label' maxlength='255' value=\"$virtual_table_label\">\n";
	echo "<br />\n";
	echo "Enter the label.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Table Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='virtual_table_name' maxlength='255' value=\"$virtual_table_name\">\n";
	echo "<br />\n";
	echo "Enter the table name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Authentication:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='virtual_table_auth'>\n";
	echo "	<option value=''></option>\n";
	if ($virtual_table_auth == "yes") { 
		echo "	<option value='yes' SELECTED >yes</option>\n";
	}
	else {
		echo "	<option value='yes'>yes</option>\n";
	}
	if ($virtual_table_auth == "no") { 
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

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Captcha:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='virtual_table_captcha'>\n";
	echo "	<option value=''></option>\n";
	if ($virtual_table_captcha == "yes") { 
		echo "	<option value='yes' SELECTED >yes</option>\n";
	}
	else {
		echo "	<option value='yes'>yes</option>\n";
	}
	if ($virtual_table_captcha == "no") { 
		echo "	<option value='no' SELECTED >no</option>\n";
	}
	else {
		echo "	<option value='no'>no</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Choose whether to require captcha.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Parent Table:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "			<select name='virtual_table_parent_uuid' class='formfld'>\n";
	echo "			<option value=''></option>\n";
	$sql = "";
	$sql .= "select * from v_virtual_tables ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		if ($row["virtual_table_uuid"] == $virtual_table_parent_uuid) {
			echo "			<option value='".$row["virtual_table_uuid"]."' selected>".$row["virtual_table_name"]."</option>\n";
		}
		else {
			echo "			<option value='".$row["virtual_table_uuid"]."'>".$row["virtual_table_name"]."</option>\n";
		}
	}
	echo "			</select>\n";

	echo "<br />\n";
	echo "Select a parent table.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' name='virtual_table_description' rows='4'>$virtual_table_description</textarea>\n";
	echo "<br />\n";
	echo "Enter a description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='virtual_table_uuid' value='$virtual_table_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	if ($action == "update") {
		require "v_virtual_table_fields.php";
	}

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "includes/footer.php";
?>