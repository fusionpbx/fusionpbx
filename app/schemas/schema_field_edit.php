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
		if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
		if (strlen($field_name) == 0 && $field_type != "label") { $msg .= $text['message-required'].$text['label-field_name']."<br>\n"; }
		if (strlen($field_type) == 0) { $msg .= $text['message-required'].$text['label-field_type']."<br>\n"; }
		if (strlen($field_list_hidden) == 0) { $msg .= $text['message-required'].$text['label-field_visibility']."<br>\n"; }
		if (strlen($field_column) == 0) { $msg .= $text['message-required'].$text['label-field_column']."<br>\n"; }
		if (strlen($field_required) == 0) { $msg .= $text['message-required'].$text['label-field_required']."<br>\n"; }
		if (strlen($field_order) == 0) { $msg .= $text['message-required'].$text['label-field_order']."<br>\n"; }
		if (strlen($field_order_tab) == 0) { $msg .= $text['message-required'].$text['label-field_tab_order']."<br>\n"; }
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

			$_SESSION["message"] = $text['message-add'];
			header("Location: schema_edit.php?id=".$schema_uuid);
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

			$_SESSION["message"] = $text['message-update'];
			header("Location: schema_edit.php?id=".$schema_uuid);
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
	require_once "resources/header.php";
	$document['title'] = $text['title-field'];

//begin the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap=\"nowrap\"><b>".$text['header-field']."</b></td>\n";
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='schema_edit.php?id=$schema_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan=\"2\">\n";
	echo $text['description-field']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_label' maxlength='255' value=\"$field_label\">\n";
	echo "<br />\n";
	echo $text['description-field_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_name' maxlength='255' value=\"$field_name\">\n";
	echo "<br />\n";
	echo $text['description-field_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='field_type'>\n";
	echo "	<option value=''></option>\n";
	if ($field_type == "text") {
		echo "	<option value='text' selected='selected'>".$text['option-text']."</option>\n";
	}
	else {
		echo "	<option value='text'>".$text['option-text']."</option>\n";
	}
	if ($field_type == "numeric") {
		echo "	<option value='numeric' selected='selected'>".$text['option-number']."</option>\n";
	}
	else {
		echo "	<option value='numeric'>".$text['option-number']."</option>\n";
	}
	if ($field_type == "date") {
		echo "	<option value='date' selected='selected'>".$text['option-date']."</option>\n";
	}
	else {
		echo "	<option value='date'>".$text['option-date']."</option>\n";
	}
	if ($field_type == "email") {
		echo "	<option value='email' selected='selected'>".$text['option-email']."</option>\n";
	}
	else {
		echo "	<option value='email'>".$text['option-email']."</option>\n";
	}
	if ($field_type == "label") {
		echo "	<option value='label' selected='selected'>".$text['option-label']."</option>\n";
	}
	else {
		echo "	<option value='label'>".$text['option-label']."</option>\n";
	}
	if ($field_type == "phone") {
		echo "	<option value='phone' selected='selected'>".$text['option-phone']."</option>\n";
	}
	else {
		echo "	<option value='phone'>".$text['option-phone']."</option>\n";
	}
	if ($field_type == "checkbox") {
		echo "	<option value='checkbox' selected='selected'>".$text['option-check_box']."</option>\n";
	}
	else {
		echo "	<option value='checkbox'>".$text['option-check_box']."</option>\n";
	}
	if ($field_type == "textarea") {
		echo "	<option value='textarea' selected='selected'>".$text['option-text_area']."</option>\n";
	}
	else {
		echo "	<option value='textarea'>".$text['option-text_area']."</option>\n";
	}
	if ($field_type == "select") {
		echo "	<option value='select' selected='selected'>".$text['option-select']."</option>\n";
	}
	else {
		echo "	<option value='select'>".$text['option-select']."</option>\n";
	}
	if ($field_type == "hidden") {
		echo "	<option value='hidden' selected='selected'>".$text['option-hidden']."</option>\n";
	}
	else {
		echo "	<option value='hidden'>".$text['option-hidden']."</option>\n";
	}
	if ($field_type == "uuid") {
		echo "	<option value='uuid' selected='selected'>".$text['option-uuid']."</option>\n";
	}
	else {
		echo "	<option value='uuid'>".$text['option-uuid']."</option>\n";
	}
	if ($field_type == "password") {
		echo "	<option value='password' selected='selected'>".$text['option-password']."</option>\n";
	}
	else {
		echo "	<option value='password'>".$text['option-password']."</option>\n";
	}
	if ($field_type == "pin_number") {
		echo "	<option value='pin_number' selected='selected'>".$text['option-pin_number']."</option>\n";
	}
	else {
		echo "	<option value='pin_number'>".$text['option-pin_number']."</option>\n";
	}
	if ($field_type == "image") {
		echo "	<option value='image' selected='selected'>".$text['option-image_upload']."</option>\n";
	}
	else {
		echo "	<option value='image'>".$text['option-image_upload']."</option>\n";
	}
	if ($field_type == "file") {
		echo "	<option value='upload_file' selected='selected'>".$text['option-file_upload']."</option>\n";
	}
	else {
		echo "	<option value='file'>".$text['option-file_upload']."</option>\n";
	}
	if ($field_type == "url") {
		echo "	<option value='url' selected='selected'>".$text['option-url']."</option>\n";
	}
	else {
		echo "	<option value='url'>".$text['option-url']."</option>\n";
	}
	if ($field_type == "mod_date") {
		echo "	<option value='mod_date' selected='selected'>".$text['option-modified_date']."</option>\n";
	}
	else {
		echo "	<option value='mod_date'>".$text['option-modified_date']."</option>\n";
	}
	if ($field_type == "mod_user") {
		echo "	<option value='mod_user' selected='selected'>".$text['option-modified_user']."</option>\n";
	}
	else {
		echo "	<option value='mod_user'>".$text['option-modified_user']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-field_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_value' maxlength='255' value=\"$field_value\">\n";
	echo "<br />\n";
	echo $text['description-field_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_visibility']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='field_list_hidden'>\n";
	echo "	<option value=''></option>\n";
	if ($field_list_hidden == "show") {
		echo "	<option value='show'  selected='selected'>".$text['option-visible']."</option>\n";
	}
	else {
		echo "	<option value='show'>".$text['option-visible']."</option>\n";
	}
	if ($field_list_hidden == "hide") {
		echo "	<option value='hide'  selected='selected'>".$text['option-hidden']."</option>\n";
	}
	else {
		echo "	<option value='hide'>".$text['option-hidden']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-field_visibility']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_search_by']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='field_search_by'>\n";
	echo "	<option value=''></option>\n";
	if ($field_search_by == "yes") {
		echo "	<option value='yes'  selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='yes'>".$text['option-true']."</option>\n";
	}
	if ($field_search_by == "no") {
		echo "	<option value='no' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='no'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-field_search_by']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_column']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_column' maxlength='255' value=\"$field_column\">\n";
	echo "<br />\n";
	echo $text['description-field_column']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_required']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='field_required'>\n";
	echo "	<option value=''></option>\n";
	if ($field_required == "yes") {
		echo "	<option value='yes'  selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='yes'>".$text['option-true']."</option>\n";
	}
	if ($field_required == "no") {
		echo "	<option value='no' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='no'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-field_required']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='field_order' maxlength='255' value='$field_order'>\n";
	echo "<br />\n";
	echo $text['description-field_order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_tab_order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='field_order_tab' maxlength='255' value='$field_order_tab'>\n";
	echo "<br />\n";
	echo $text['description-field_tab_order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap=\"nowrap\">\n";
	echo "	".$text['label-field_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='field_description' maxlength='255' value=\"$field_description\">\n";
	echo "<br />\n";
	echo $text['description-field_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='schema_uuid' value='$schema_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='schema_field_uuid' value='$schema_field_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	if ($action == "update") {
		if ($field_type == "select") {
			require "schema_name_values.php";
		}
	}

//show the footer
	require_once "resources/footer.php";
?>