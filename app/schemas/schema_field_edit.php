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

//set the action as an add or update
if (isset($_REQUEST["id"])) {
	$action = "update";
	$schema_field_uuid = check_str($_REQUEST["id"]);
}
else {
	$action = "add";
}

//get the http variables
	if (strlen($_GET["schema_uuid"]) > 0) {
		$schema_uuid = check_str($_GET["schema_uuid"]);
	}

//get the http post variables
if (count($_POST)>0) {
	$field_label = check_str($_POST["field_label"]);
	$field_name = check_str($_POST["field_name"]);
	$field_type = check_str($_POST["field_type"]);
	$field_value = check_str($_POST["field_value"]);
	$field_list_hidden = check_str($_POST["field_list_hidden"]);
	$field_search_by = check_str($_POST["field_search_by"]);
	$field_column = check_str($_POST["field_column"]);
	$field_required = check_str($_POST["field_required"]);
	$field_order = check_str($_POST["field_order"]);
	$field_order_tab = check_str($_POST["field_order_tab"]);
	$field_description = check_str($_POST["field_description"]);
}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$schema_field_uuid = check_str($_POST["schema_field_uuid"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($field_label) == 0) { $msg .= "Please provide: Label<br>\n"; }
		if (strlen($field_name) == 0 && $field_type != "label") { $msg .= "Please provide: Name<br>\n"; }
		if (strlen($field_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($field_value) == 0) { $msg .= "Please provide: Value<br>\n"; }
		if (strlen($field_list_hidden) == 0) { $msg .= "Please provide: List Visibility<br>\n"; }
		//if (strlen($field_search_by) == 0) { $msg .= "Please provide: Search By<br>\n"; }
		if (strlen($field_column) == 0) { $msg .= "Please provide: Column<br>\n"; }
		if (strlen($field_required) == 0) { $msg .= "Please provide: Required<br>\n"; }
		if (strlen($field_order) == 0) { $msg .= "Please provide: Field Order<br>\n"; }
		if (strlen($field_order_tab) == 0) { $msg .= "Please provide: Tab Order<br>\n"; }
		//if (strlen($field_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
		if ($action == "add" && permission_exists('schema_add')) {
			$schema_field_uuid = uuid();
			$sql = "insert into v_schema_fields ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "schema_uuid, ";
			$sql .= "schema_field_uuid, ";
			$sql .= "field_label, ";
			$sql .= "field_name, ";
			$sql .= "field_type, ";
			$sql .= "field_value, ";
			$sql .= "field_list_hidden, ";
			$sql .= "field_search_by, ";
			$sql .= "field_column, ";
			$sql .= "field_required, ";
			$sql .= "field_order, ";
			$sql .= "field_order_tab, ";
			$sql .= "field_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$schema_uuid', ";
			$sql .= "'$schema_field_uuid', ";
			$sql .= "'$field_label', ";
			$sql .= "'$field_name', ";
			$sql .= "'$field_type', ";
			$sql .= "'$field_value', ";
			$sql .= "'$field_list_hidden', ";
			$sql .= "'$field_search_by', ";
			$sql .= "'$field_column', ";
			$sql .= "'$field_required', ";
			$sql .= "'$field_order', ";
			$sql .= "'$field_order_tab', ";
			$sql .= "'$field_description' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=schema_edit.php?id=$schema_uuid\">\n";
			echo "<div align='center'>\n";
			echo "Add Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "add")

		if ($action == "update" && permission_exists('schema_edit')) {
			$sql = "update v_schema_fields set ";
			$sql .= "field_label = '$field_label', ";
			$sql .= "field_name = '$field_name', ";
			$sql .= "field_type = '$field_type', ";
			$sql .= "field_value = '$field_value', ";
			$sql .= "field_list_hidden = '$field_list_hidden', ";
			$sql .= "field_search_by = '$field_search_by', ";
			$sql .= "field_column = '$field_column', ";
			$sql .= "field_required = '$field_required', ";
			$sql .= "field_order = '$field_order', ";
			$sql .= "field_order_tab = '$field_order_tab', ";
			$sql .= "field_description = '$field_description' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and schema_uuid = '$schema_uuid'";
			$sql .= "and schema_field_uuid = '$schema_field_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=schema_edit.php?id=$schema_uuid\">\n";
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
		$schema_field_uuid = $_GET["id"];

		$sql = "select * from v_schema_fields ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and schema_uuid = '$schema_uuid' ";
		$sql .= "and schema_field_uuid = '$schema_field_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$field_label = $row["field_label"];
			$field_name = $row["field_name"];
			$field_type = $row["field_type"];
			$field_value = $row["field_value"];
			$field_list_hidden = $row["field_list_hidden"];
			$field_search_by = $row["field_search_by"];
			$field_column = $row["field_column"];
			$field_required = $row["field_required"];
			$field_order = $row["field_order"];
			$field_order_tab = $row["field_order_tab"];
			$field_description = $row["field_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "includes/header.php";

//begin the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap=\"nowrap\"><b>Field</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='schema_edit.php?id=$schema_uuid'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan=\"2\">\n";
	echo "Lists the fields in the database.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Label:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_label' maxlength='255' value=\"$field_label\">\n";
	echo "<br />\n";
	echo "Enter the field label.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_name' maxlength='255' value=\"$field_name\">\n";
	echo "<br />\n";
	echo "Enter field name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='field_type'>\n";
	echo "	<option value=''></option>\n";
	if ($field_type == "text") { 
		echo "	<option value='text' selected='selected'>Text</option>\n";
	}
	else {
		echo "	<option value='text'>Text</option>\n";
	}
	if ($field_type == "number") { 
		echo "	<option value='number' selected='selected'>Number</option>\n";
	}
	else {
		echo "	<option value='number'>Number</option>\n";
	}
	if ($field_type == "date") { 
		echo "	<option value='date' selected='selected'>Date</option>\n";
	}
	else {
		echo "	<option value='date'>Date</option>\n";
	}
	if ($field_type == "email") { 
		echo "	<option value='email' selected='selected'>Email</option>\n";
	}
	else {
		echo "	<option value='email'>Email</option>\n";
	}
	if ($field_type == "label") { 
		echo "	<option value='label' selected='selected'>Label</option>\n";
	}
	else {
		echo "	<option value='label'>Label</option>\n";
	}
	if ($field_type == "phone") { 
		echo "	<option value='phone' selected='selected'>Phone</option>\n";
	}
	else {
		echo "	<option value='phone'>Phone</option>\n";
	}
	//if ($field_type == "truefalse") { 
	//	echo "	<option value='truefalse' selected='selected'>True or False</option>\n";
	//}
	//else {
	//	echo "	<option value='truefalse'>True or False</option>\n";
	//}
	if ($field_type == "checkbox") { 
		echo "	<option value='checkbox' selected='selected'>Check Box</option>\n";
	}
	else {
		echo "	<option value='checkbox'>Check Box</option>\n";
	}
	//if ($field_type == "radiobutton") { 
	//	echo "	<option value='radiobutton' selected='selected' >Radio Button</option>\n";
	//}
	//else {
	//	echo "	<option value='radiobutton'>Radio Button</option>\n";
	//}
	if ($field_type == "textarea") { 
		echo "	<option value='textarea' selected='selected'>Textarea</option>\n";
	}
	else {
		echo "	<option value='textarea'>Textarea</option>\n";
	}
	if ($field_type == "select") { 
		echo "	<option value='select' selected='selected'>Select</option>\n";
	}
	else {
		echo "	<option value='select'>Select</option>\n";
	}
	if ($field_type == "hidden") { 
		echo "	<option value='hidden' selected='selected'>Hidden</option>\n";
	}
	else {
		echo "	<option value='hidden'>Hidden</option>\n";
	}
	if ($field_type == "uuid") { 
		echo "	<option value='uuid' selected='selected'>UUID</option>\n";
	}
	else {
		echo "	<option value='uuid'>UUID</option>\n";
	}
	//if ($field_type == "ipv4") { 
	//	echo "	<option value='ipv4' selected='selected'>IP version 4</option>\n";
	//}
	//else {
	//	echo "	<option value='ipv4'>IP version 4</option>\n";
	//}
	//if ($field_type == "ipv6") { 
	//	echo "	<option value='ipv6' selected='selected'>IP version 6</option>\n";
	//}
	//else {
	//	echo "	<option value='ipv6'>IP version 6</option>\n";
	//}
	//if ($field_type == "money") { 
	//	echo "	<option value='money' selected='selected'>Money</option>\n";
	//}
	//else {
	//	echo "	<option value='money'>Money</option>\n";
	//}
	if ($field_type == "password") { 
		echo "	<option value='password' selected='selected'>Password</option>\n";
	}
	else {
		echo "	<option value='password'>Password</option>\n";
	}
	if ($field_type == "pin_number") { 
		echo "	<option value='pin_number' selected='selected'>PIN Number</option>\n";
	}
	else {
		echo "	<option value='pin_number'>PIN Number</option>\n";
	}
	if ($field_type == "image") { 
		echo "	<option value='image' selected='selected'>Upload Image</option>\n";
	}
	else {
		echo "	<option value='image'>Upload Image</option>\n";
	}
	if ($field_type == "file") { 
		echo "	<option value='upload_file' selected='selected'>Upload File</option>\n";
	}
	else {
		echo "	<option value='file'>Upload File</option>\n";
	}
	//if ($field_type == "yesno") { 
	//	echo "	<option value='yesno' selected='selected'>Yes or No</option>\n";
	//}
	//else {
	//	echo "	<option value='yesno'>Yes or No</option>\n";
	//}
	if ($field_type == "url") { 
		echo "	<option value='url' selected='selected'>URL</option>\n";
	}
	else {
		echo "	<option value='url'>URL</option>\n";
	}
	//if ($field_type == "add_date") { 
	//	echo "	<option value='add_date' selected='selected'>Add Date</option>\n";
	//}
	//else {
	//	echo "	<option value='add_date'>Add Date</option>\n";
	//}
	//if ($field_type == "add_user") { 
	//	echo "	<option value='add_user' selected='selected'>Add User</option>\n";
	//}
	//else {
	//	echo "	<option value='add_user'>Add User</option>\n";
	//}
	if ($field_type == "mod_date") { 
		echo "	<option value='mod_date' selected='selected'>Modified Date</option>\n";
	}
	else {
		echo "	<option value='mod_date'>Modified Date</option>\n";
	}
	if ($field_type == "mod_user") { 
		echo "	<option value='mod_user' selected='selected'>Modified User</option>\n";
	}
	else {
		echo "	<option value='mod_user'>Modified User</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the field type.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Value:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_value' maxlength='255' value=\"$field_value\">\n";
	echo "<br />\n";
	echo "Enter the default value.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	List Visibility:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='field_list_hidden'>\n";
	echo "	<option value=''></option>\n";
	if ($field_list_hidden == "show") { 
		echo "	<option value='show'  selected='selected'>show</option>\n";
	}
	else {
		echo "	<option value='show'>show</option>\n";
	}
	if ($field_list_hidden == "hide") { 
		echo "	<option value='hide'  selected='selected'>hide</option>\n";
	}
	else {
		echo "	<option value='hide'>hide</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Choose whether the field is hidden from the list.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Search By:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='field_search_by'>\n";
	echo "	<option value=''></option>\n";
	if ($field_search_by == "yes") { 
		echo "	<option value='yes'  selected='selected'>yes</option>\n";
	}
	else {
		echo "	<option value='yes'>yes</option>\n";
	}
	if ($field_search_by == "no") { 
		echo "	<option value='no' selected='selected'>no</option>\n";
	}
	else {
		echo "	<option value='no'>no</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Choose whether the field will be used for searches.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Column:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_column' maxlength='255' value=\"$field_column\">\n";
	echo "<br />\n";
	echo "Determines which column to show the field in.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Required:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='field_required'>\n";
	echo "	<option value=''></option>\n";
	if ($field_required == "yes") { 
		echo "	<option value='yes'  selected='selected'>yes</option>\n";
	}
	else {
		echo "	<option value='yes'>yes</option>\n";
	}
	if ($field_required == "no") { 
		echo "	<option value='no' selected='selected'>no</option>\n";
	}
	else {
		echo "	<option value='no'>no</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Choose whether the field is required.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Field Order:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='field_order' maxlength='255' value='$field_order'>\n";
	echo "<br />\n";
	echo "Enter the order of the field.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Tab Order:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='field_order_tab' maxlength='255' value='$field_order_tab'>\n";
	echo "<br />\n";
	echo "Enter the tab order.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_description' maxlength='255' value=\"$field_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='schema_uuid' value='$schema_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='schema_field_uuid' value='$schema_field_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	if ($action == "update") {
		if ($field_type == "select") {
			require "schema_name_values.php";
		}
	}

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "includes/footer.php";
?>