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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('module_add') || permission_exists('module_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define the variables
	$module_label = '';
	$modules = '';
	$module_name = '';
	$module_description = '';
	$module_category = '';
	$module_order = '';
	$module_enabled = '';
	$module_default_enabled = '';

//determin the action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$module_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the http post variables to php variables
	if (!empty($_POST)) {
		$module_label = $_POST["module_label"];
		$module_name = $_POST["module_name"];
		$module_description = $_POST["module_description"];
		$module_category = $_POST["module_category"];
		$module_order = $_POST["module_order"];
		$module_enabled = $_POST["module_enabled"] ?? 'false';
		$module_default_enabled = $_POST["module_default_enabled"] ?? 'false';
	}

//process the data
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//get the uuid
			if ($action == "update") {
				$module_uuid = $_POST["module_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: modules.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (empty($module_label)) { $msg .= $text['message-required'].$text['label-label']."<br>\n"; }
			if (empty($module_name)) { $msg .= $text['message-required'].$text['label-module_name']."<br>\n"; }
			//if (empty($module_description)) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
			if (empty($module_category)) { $msg .= $text['message-required'].$text['label-module_category']."<br>\n"; }
			if (empty($module_enabled)) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			if (empty($module_default_enabled)) { $msg .= $text['message-required'].$text['label-default_enabled']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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
			if (empty($_POST["persistformvar"])) {
				if ($action == "add" && permission_exists('module_add')) {
					$module_uuid = uuid();
					$array['modules'][0]['module_uuid'] = $module_uuid;

					message::add($text['message-add']);
				}

				if ($action == "update" && permission_exists('module_edit')) {
					$array['modules'][0]['module_uuid'] = $module_uuid;

					message::add($text['message-update']);
				}

				//add common array elements and execute
				if (is_array($array) && @sizeof($array) != 0) {
					$array['modules'][0]['module_label'] = $module_label;
					$array['modules'][0]['module_name'] = $module_name;
					$array['modules'][0]['module_description'] = $module_description;
					$array['modules'][0]['module_category'] = $module_category;
					$array['modules'][0]['module_order'] = $module_order;
					$array['modules'][0]['module_enabled'] = $module_enabled;
					$array['modules'][0]['module_default_enabled'] = $module_default_enabled;

					$database = new database;
					$database->app_name = 'modules';
					$database->app_uuid = '5eb9cba1-8cb6-5d21-e36a-775475f16b5e';
					$database->save($array);
					unset($array);

					$module = new modules;
					$module->xml();

					header("Location: modules.php");
					exit;
				}
			}

	}

//pre-populate the form
	if (!empty($_GET) && empty($_POST["persistformvar"])) {
		$module_uuid = $_GET["id"];
		$sql = "select * from v_modules ";
		$sql .= "where module_uuid = :module_uuid ";
		$parameters['module_uuid'] = $module_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$module_label = $row["module_label"];
			$module_name = $row["module_name"];
			$module_description = $row["module_description"];
			$module_category = $row["module_category"];
			$module_order = $row["module_order"];
			$module_enabled = $row["module_enabled"];
			$module_default_enabled = $row["module_default_enabled"];
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	if (empty($module_enabled)) { $module_enabled = 'true'; }
	if (empty($module_default_enabled)) { $module_default_enabled = 'true'; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "add") {
		$document['title'] = $text['title-module_add'];
	}
	if ($action == "update") {
		$document['title'] = $text['title-module_edit'];
	}
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['header-module_add']."</b>";
	}
	if ($action == "update") {
		echo "<b>".$text['header-module_edit']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'modules.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-label']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='module_label' maxlength='255' value=\"".escape($module_label)."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-module_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='module_name' maxlength='255' value=\"".escape($module_name)."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='module_order' maxlength='255' value=\"".escape($module_order)."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-module_category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$table_name = 'v_modules';
	$field_name = 'module_category';
	$sql_where_optional = '';
	$field_current_value = $module_category;
	echo html_select_other($table_name, $field_name, $sql_where_optional, $field_current_value, $field_name.' asc', $text['label-other']);
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='module_enabled' name='module_enabled' value='true' ".($module_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='module_enabled' name='module_enabled'>\n";
		echo "		<option value='true' ".($module_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($module_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-default_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='module_default_enabled' name='module_default_enabled' value='true' ".($module_default_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='module_default_enabled' name='module_default_enabled'>\n";
		echo "		<option value='true' ".($module_default_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($module_default_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='module_description' maxlength='255' value=\"".escape($module_description)."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='module_uuid' value='".escape($module_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
