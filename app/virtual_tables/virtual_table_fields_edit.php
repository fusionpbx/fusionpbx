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

//set the action as an add or update
if (isset($_REQUEST["id"])) {
	$action = "update";
	$virtual_table_field_uuid = check_str($_REQUEST["id"]);
}
else {
	$action = "add";
}

//get the http variables
	if (strlen($_GET["virtual_table_uuid"]) > 0) {
		$virtual_table_uuid = check_str($_GET["virtual_table_uuid"]);
	}

//get the http post variables
if (count($_POST)>0) {
	$virtual_field_label = check_str($_POST["virtual_field_label"]);
	$virtual_field_name = check_str($_POST["virtual_field_name"]);
	$virtual_field_type = check_str($_POST["virtual_field_type"]);
	$virtual_field_value = check_str($_POST["virtual_field_value"]);
	$virtual_field_list_hidden = check_str($_POST["virtual_field_list_hidden"]);
	$virtual_field_search_by = check_str($_POST["virtual_field_search_by"]);
	$virtual_field_column = check_str($_POST["virtual_field_column"]);
	$virtual_field_required = check_str($_POST["virtual_field_required"]);
	$virtual_field_order = check_str($_POST["virtual_field_order"]);
	$virtual_field_order_tab = check_str($_POST["virtual_field_order_tab"]);
	$virtual_field_description = check_str($_POST["virtual_field_description"]);
}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$virtual_table_field_uuid = check_str($_POST["virtual_table_field_uuid"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($virtual_field_label) == 0) { $msg .= "Please provide: Label<br>\n"; }
		if (strlen($virtual_field_name) == 0 && $virtual_field_type != "label") { $msg .= "Please provide: Name<br>\n"; }
		if (strlen($virtual_field_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($virtual_field_value) == 0) { $msg .= "Please provide: Value<br>\n"; }
		if (strlen($virtual_field_list_hidden) == 0) { $msg .= "Please provide: List Visibility<br>\n"; }
		//if (strlen($virtual_field_search_by) == 0) { $msg .= "Please provide: Search By<br>\n"; }
		if (strlen($virtual_field_column) == 0) { $msg .= "Please provide: Column<br>\n"; }
		if (strlen($virtual_field_required) == 0) { $msg .= "Please provide: Required<br>\n"; }
		if (strlen($virtual_field_order) == 0) { $msg .= "Please provide: Field Order<br>\n"; }
		if (strlen($virtual_field_order_tab) == 0) { $msg .= "Please provide: Tab Order<br>\n"; }
		//if (strlen($virtual_field_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
		if ($action == "add" && permission_exists('virtual_tables_add')) {
			$virtual_table_field_uuid = uuid();
			$sql = "insert into v_virtual_table_fields ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "virtual_table_uuid, ";
			$sql .= "virtual_table_field_uuid, ";
			$sql .= "virtual_field_label, ";
			$sql .= "virtual_field_name, ";
			$sql .= "virtual_field_type, ";
			$sql .= "virtual_field_value, ";
			$sql .= "virtual_field_list_hidden, ";
			$sql .= "virtual_field_search_by, ";
			$sql .= "virtual_field_column, ";
			$sql .= "virtual_field_required, ";
			$sql .= "virtual_field_order, ";
			$sql .= "virtual_field_order_tab, ";
			$sql .= "virtual_field_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$virtual_table_uuid', ";
			$sql .= "'$virtual_table_field_uuid', ";
			$sql .= "'$virtual_field_label', ";
			$sql .= "'$virtual_field_name', ";
			$sql .= "'$virtual_field_type', ";
			$sql .= "'$virtual_field_value', ";
			$sql .= "'$virtual_field_list_hidden', ";
			$sql .= "'$virtual_field_search_by', ";
			$sql .= "'$virtual_field_column', ";
			$sql .= "'$virtual_field_required', ";
			$sql .= "'$virtual_field_order', ";
			$sql .= "'$virtual_field_order_tab', ";
			$sql .= "'$virtual_field_description' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=virtual_tables_edit.php?id=$virtual_table_uuid\">\n";
			echo "<div align='center'>\n";
			echo "Add Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "add")

		if ($action == "update" && permission_exists('virtual_tables_edit')) {
			$sql = "update v_virtual_table_fields set ";
			$sql .= "virtual_field_label = '$virtual_field_label', ";
			$sql .= "virtual_field_name = '$virtual_field_name', ";
			$sql .= "virtual_field_type = '$virtual_field_type', ";
			$sql .= "virtual_field_value = '$virtual_field_value', ";
			$sql .= "virtual_field_list_hidden = '$virtual_field_list_hidden', ";
			$sql .= "virtual_field_search_by = '$virtual_field_search_by', ";
			$sql .= "virtual_field_column = '$virtual_field_column', ";
			$sql .= "virtual_field_required = '$virtual_field_required', ";
			$sql .= "virtual_field_order = '$virtual_field_order', ";
			$sql .= "virtual_field_order_tab = '$virtual_field_order_tab', ";
			$sql .= "virtual_field_description = '$virtual_field_description' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and virtual_table_uuid = '$virtual_table_uuid'";
			$sql .= "and virtual_table_field_uuid = '$virtual_table_field_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=virtual_tables_edit.php?id=$virtual_table_uuid\">\n";
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
		$virtual_table_uuid = $_GET["virtual_table_uuid"];
		$virtual_table_field_uuid = $_GET["id"];

		$sql = "select * from v_virtual_table_fields ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and virtual_table_uuid = '$virtual_table_uuid' ";
		$sql .= "and virtual_table_field_uuid = '$virtual_table_field_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$virtual_field_label = $row["virtual_field_label"];
			$virtual_field_name = $row["virtual_field_name"];
			$virtual_field_type = $row["virtual_field_type"];
			$virtual_field_value = $row["virtual_field_value"];
			$virtual_field_list_hidden = $row["virtual_field_list_hidden"];
			$virtual_field_search_by = $row["virtual_field_search_by"];
			$virtual_field_column = $row["virtual_field_column"];
			$virtual_field_required = $row["virtual_field_required"];
			$virtual_field_order = $row["virtual_field_order"];
			$virtual_field_order_tab = $row["virtual_field_order_tab"];
			$virtual_field_description = $row["virtual_field_description"];
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
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap=\"nowrap\"><b>Virtual Table Field Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap=\"nowrap\"><b>Virtual Table Field Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='virtual_tables_edit.php?id=$virtual_table_uuid'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan=\"2\">\n";
	echo "Lists the fields in the virtual database.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Label:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='virtual_field_label' maxlength='255' value=\"$virtual_field_label\">\n";
	echo "<br />\n";
	echo "Enter the field label.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='virtual_field_name' maxlength='255' value=\"$virtual_field_name\">\n";
	echo "<br />\n";
	echo "Enter field name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='virtual_field_type'>\n";
	echo "	<option value=''></option>\n";
	if ($virtual_field_type == "text") { 
		echo "	<option value='text' selected='selected'>Text</option>\n";
	}
	else {
		echo "	<option value='text'>Text</option>\n";
	}
	if ($virtual_field_type == "number") { 
		echo "	<option value='number' selected='selected'>Number</option>\n";
	}
	else {
		echo "	<option value='number'>Number</option>\n";
	}
	if ($virtual_field_type == "date") { 
		echo "	<option value='date' selected='selected'>Date</option>\n";
	}
	else {
		echo "	<option value='date'>Date</option>\n";
	}
	if ($virtual_field_type == "email") { 
		echo "	<option value='email' selected='selected'>Email</option>\n";
	}
	else {
		echo "	<option value='email'>Email</option>\n";
	}
	if ($virtual_field_type == "label") { 
		echo "	<option value='label' selected='selected'>Label</option>\n";
	}
	else {
		echo "	<option value='label'>Label</option>\n";
	}
	if ($virtual_field_type == "phone") { 
		echo "	<option value='phone' selected='selected'>Phone</option>\n";
	}
	else {
		echo "	<option value='phone'>Phone</option>\n";
	}
	//if ($virtual_field_type == "truefalse") { 
	//	echo "	<option value='truefalse' selected='selected'>True or False</option>\n";
	//}
	//else {
	//	echo "	<option value='truefalse'>True or False</option>\n";
	//}
	if ($virtual_field_type == "checkbox") { 
		echo "	<option value='checkbox' selected='selected'>Check Box</option>\n";
	}
	else {
		echo "	<option value='checkbox'>Check Box</option>\n";
	}
	//if ($virtual_field_type == "radiobutton") { 
	//	echo "	<option value='radiobutton' selected='selected' >Radio Button</option>\n";
	//}
	//else {
	//	echo "	<option value='radiobutton'>Radio Button</option>\n";
	//}
	if ($virtual_field_type == "textarea") { 
		echo "	<option value='textarea' selected='selected'>Textarea</option>\n";
	}
	else {
		echo "	<option value='textarea'>Textarea</option>\n";
	}
	if ($virtual_field_type == "select") { 
		echo "	<option value='select' selected='selected'>Select</option>\n";
	}
	else {
		echo "	<option value='select'>Select</option>\n";
	}
	if ($virtual_field_type == "hidden") { 
		echo "	<option value='hidden' selected='selected'>Hidden</option>\n";
	}
	else {
		echo "	<option value='hidden'>Hidden</option>\n";
	}
	if ($virtual_field_type == "uuid") { 
		echo "	<option value='uuid' selected='selected'>UUID</option>\n";
	}
	else {
		echo "	<option value='uuid'>UUID</option>\n";
	}
	//if ($virtual_field_type == "ipv4") { 
	//	echo "	<option value='ipv4' selected='selected'>IP version 4</option>\n";
	//}
	//else {
	//	echo "	<option value='ipv4'>IP version 4</option>\n";
	//}
	//if ($virtual_field_type == "ipv6") { 
	//	echo "	<option value='ipv6' selected='selected'>IP version 6</option>\n";
	//}
	//else {
	//	echo "	<option value='ipv6'>IP version 6</option>\n";
	//}
	//if ($virtual_field_type == "money") { 
	//	echo "	<option value='money' selected='selected'>Money</option>\n";
	//}
	//else {
	//	echo "	<option value='money'>Money</option>\n";
	//}
	if ($virtual_field_type == "password") { 
		echo "	<option value='password' selected='selected'>Password</option>\n";
	}
	else {
		echo "	<option value='password'>Password</option>\n";
	}
	if ($virtual_field_type == "pin_number") { 
		echo "	<option value='pin_number' selected='selected'>PIN Number</option>\n";
	}
	else {
		echo "	<option value='pin_number'>PIN Number</option>\n";
	}
	//if ($virtual_field_type == "upload_image") { 
	//	echo "	<option value='upload_image' selected='selected'>Upload Image</option>\n";
	//}
	//else {
	//	echo "	<option value='upload_image'>Upload Image</option>\n";
	//}
	//if ($virtual_field_type == "upload_file") { 
	//	echo "	<option value='upload_file' selected='selected'>Upload File</option>\n";
	//}
	//else {
	//	echo "	<option value='upload_file'>Upload File</option>\n";
	//}
	//if ($virtual_field_type == "yesno") { 
	//	echo "	<option value='yesno' selected='selected'>Yes or No</option>\n";
	//}
	//else {
	//	echo "	<option value='yesno'>Yes or No</option>\n";
	//}
	if ($virtual_field_type == "url") { 
		echo "	<option value='url' selected='selected'>URL</option>\n";
	}
	else {
		echo "	<option value='url'>URL</option>\n";
	}
	//if ($virtual_field_type == "add_date") { 
	//	echo "	<option value='add_date' selected='selected'>Add Date</option>\n";
	//}
	//else {
	//	echo "	<option value='add_date'>Add Date</option>\n";
	//}
	//if ($virtual_field_type == "add_user") { 
	//	echo "	<option value='add_user' selected='selected'>Add User</option>\n";
	//}
	//else {
	//	echo "	<option value='add_user'>Add User</option>\n";
	//}
	if ($virtual_field_type == "mod_date") { 
		echo "	<option value='mod_date' selected='selected'>Modified Date</option>\n";
	}
	else {
		echo "	<option value='mod_date'>Modified Date</option>\n";
	}
	if ($virtual_field_type == "mod_user") { 
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
	echo "	<input class='formfld' type='text' name='virtual_field_value' maxlength='255' value=\"$virtual_field_value\">\n";
	echo "<br />\n";
	echo "Enter the default value.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	List Visibility:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='virtual_field_list_hidden'>\n";
	echo "	<option value=''></option>\n";
	if ($virtual_field_list_hidden == "show") { 
		echo "	<option value='show'  selected='selected'>show</option>\n";
	}
	else {
		echo "	<option value='show'>show</option>\n";
	}
	if ($virtual_field_list_hidden == "hide") { 
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
	echo "	<select class='formfld' name='virtual_field_search_by'>\n";
	echo "	<option value=''></option>\n";
	if ($virtual_field_search_by == "yes") { 
		echo "	<option value='yes'  selected='selected'>yes</option>\n";
	}
	else {
		echo "	<option value='yes'>yes</option>\n";
	}
	if ($virtual_field_search_by == "no") { 
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
	echo "	<input class='formfld' type='text' name='virtual_field_column' maxlength='255' value=\"$virtual_field_column\">\n";
	echo "<br />\n";
	echo "Determines which column to show the field in.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Required:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='virtual_field_required'>\n";
	echo "	<option value=''></option>\n";
	if ($virtual_field_required == "yes") { 
		echo "	<option value='yes'  selected='selected'>yes</option>\n";
	}
	else {
		echo "	<option value='yes'>yes</option>\n";
	}
	if ($virtual_field_required == "no") { 
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
	echo "  <input class='formfld' type='text' name='virtual_field_order' maxlength='255' value='$virtual_field_order'>\n";
	echo "<br />\n";
	echo "Enter the order of the field.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Tab Order:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='virtual_field_order_tab' maxlength='255' value='$virtual_field_order_tab'>\n";
	echo "<br />\n";
	echo "Enter the HTML Tab Order.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='virtual_field_description' maxlength='255' value=\"$virtual_field_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='virtual_table_uuid' value='$virtual_table_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='virtual_table_field_uuid' value='$virtual_table_field_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	if ($action == "update") {
		if ($virtual_field_type == "select") {
			require "virtual_table_data_types_name_value.php";
		}
	}

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "includes/footer.php";
?>