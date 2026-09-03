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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!(permission_exists('theme_setting_add') || permission_exists('theme_setting_edit'))) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//connect to the database
	$database = database::new();

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

//set from session variables
	$button_icon_back = $settings->get('theme', 'button_icon_back', '');
	$button_icon_copy = $settings->get('theme', 'button_icon_copy', '');
	$button_icon_delete = $settings->get('theme', 'button_icon_delete', '');
	$button_icon_save = $settings->get('theme', 'button_icon_save', '');
	$input_toggle_style = $settings->get('theme', 'input_toggle_style', 'switch round');

//get the theme uuid
	if (!empty($_REQUEST["theme_uuid"]) && is_uuid($_REQUEST["theme_uuid"])) {
		$theme_uuid = $_REQUEST["theme_uuid"] ?? '';
	}

//action add or update
	if ((!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) || !empty($_REQUEST["theme_setting_uuid"])) {
		$action = "update";
		$theme_setting_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
		$theme_setting_uuid = '';
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$theme_setting_category = $_POST["theme_setting_category"] ?? null;
		$theme_setting_subcategory = $_POST["theme_setting_subcategory"] ?? null;
		$theme_setting_name = $_POST["theme_setting_name"] ?? null;
		$theme_setting_value = $_POST["theme_setting_value"] ?? null;
		$theme_setting_order = $_POST["theme_setting_order"] ?? null;
		$theme_setting_enabled = $_POST["theme_setting_enabled"] ?? null;
		$theme_setting_description = $_POST["theme_setting_description"] ?? null;
	}

//process the data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: theme_settings.php');
				exit;
			}

		//process the http post data by submitted action
			if (!empty($_POST['action'])) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('theme_setting_add')) {
							$obj = new theme_settings;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('theme_setting_delete')) {
							$obj = new theme_settings;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('theme_setting_edit')) {
							$obj = new theme_settings;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: theme_setting_edit.php?id='.$theme_setting_uuid);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			if (empty($theme_setting_category)) { $msg .= $text['message-required']." ".$text['label-theme_setting_category']."<br>\n"; }
			if (empty($theme_setting_subcategory)) { $msg .= $text['message-required']." ".$text['label-theme_setting_subcategory']."<br>\n"; }
			if (empty($theme_setting_name)) { $msg .= $text['message-required']." ".$text['label-theme_setting_name']."<br>\n"; }
			if (empty($theme_setting_value)) { $msg .= $text['message-required']." ".$text['label-theme_setting_value']."<br>\n"; }
			if (empty($theme_setting_enabled)) { $msg .= $text['message-required']." ".$text['label-theme_setting_enabled']."<br>\n"; }
			//if (empty($theme_setting_description)) { $msg .= $text['message-required']." ".$text['label-theme_setting_description']."<br>\n"; }
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

		//add the theme_setting_uuid
			if (!is_uuid($_POST["theme_setting_uuid"])) {
				$theme_setting_uuid = uuid();
			}

		//prepare the array
			$array['theme_settings'][0]['theme_uuid'] = $theme_uuid;
			$array['theme_settings'][0]['theme_setting_uuid'] = $theme_setting_uuid;
			$array['theme_settings'][0]['theme_setting_category'] = $theme_setting_category;
			$array['theme_settings'][0]['theme_setting_subcategory'] = $theme_setting_subcategory;
			$array['theme_settings'][0]['theme_setting_name'] = $theme_setting_name;
			$array['theme_settings'][0]['theme_setting_value'] = $theme_setting_value;
			$array['theme_settings'][0]['theme_setting_order'] = $theme_setting_order;
			$array['theme_settings'][0]['theme_setting_enabled'] = $theme_setting_enabled;
			$array['theme_settings'][0]['theme_setting_description'] = $theme_setting_description;

		//save the data
			$database->app_name = 'theme settings';
			$database->app_uuid = '26b2a370-1769-4275-9ed7-a2e1a2b058bf';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: theme_settings.php');
				header('Location: theme_settings.php?id='.urlencode($theme_uuid));
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		$sql .= " theme_setting_uuid, ";
		$sql .= " theme_setting_category, ";
		$sql .= " theme_setting_subcategory, ";
		$sql .= " theme_setting_name, ";
		$sql .= " theme_setting_value, ";
		$sql .= " theme_setting_order, ";
		$sql .= " theme_setting_enabled , ";
		$sql .= " theme_setting_description ";
		$sql .= "from v_theme_settings ";
		$sql .= "where theme_setting_uuid = :theme_setting_uuid ";
		$parameters['theme_setting_uuid'] = $theme_setting_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$theme_setting_category = $row["theme_setting_category"];
			$theme_setting_subcategory = $row["theme_setting_subcategory"];
			$theme_setting_name = $row["theme_setting_name"];
			$theme_setting_value = $row["theme_setting_value"];
			$theme_setting_order = $row["theme_setting_order"];
			$theme_setting_enabled = $row["theme_setting_enabled"];
			$theme_setting_description = $row["theme_setting_description"];
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	$theme_setting_category = $theme_setting_category ?? 'theme';
	$theme_setting_enabled = $theme_setting_enabled ?? true;

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-theme_setting'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='theme_setting_uuid' value='".escape($theme_setting_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-theme_setting']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$button_icon_back,'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'theme_settings.php?id='.$theme_uuid]);
	if ($action == 'update') {
		if (permission_exists('theme_setting_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$button_icon_copy,'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('theme_setting_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$button_icon_delete,'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$button_icon_save,'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-theme_settings']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('theme_setting_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('theme_setting_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_setting_category' maxlength='255' value='".escape($theme_setting_category)."'>\n";
	echo "<br />\n";
	echo $text['description-theme_setting_category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_subcategory']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_setting_subcategory' maxlength='255' value='".escape($theme_setting_subcategory)."'>\n";
	echo "<br />\n";
	echo $text['description-theme_setting_subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$setting_types = ['Array','Boolean','Code','Dir','Name','Numeric','Text','UUID'];
	echo "	<select class='formfld' id='theme_setting_name' name='theme_setting_name' required='required'>\n";
	echo "		<option value=''></option>\n";
	if (!empty($setting_types)) {
		foreach ($setting_types as $setting_type) {
			echo "	<option value='".strtolower($setting_type)."' ".($theme_setting_name == strtolower($setting_type) ? "selected='selected'" : null).">".$setting_type."</option>\n";
		}
	}
	echo "	</select>\n";
	unset($setting_types, $setting_type);
	echo "<br />\n";
	echo $text['description-theme_setting_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_setting_value' maxlength='255' value='".escape($theme_setting_value)."'>\n";
	echo "<br />\n";
	echo $text['description-theme_setting_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr ".(($theme_setting_name != 'array') ? "style='display: none;'" : null).">\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<select name='theme_setting_order' class='formfld'>\n";
	$i=0;
	while($i<=999) {
		$selected = ($i == $theme_setting_order) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "		<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "		<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "		<option value='$i' ".$selected.">$i</option>\n";
		}
		$i++;
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo $text['description-order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='theme_setting_enabled' name='theme_setting_enabled'>\n";
	echo "		<option value='true' ".($theme_setting_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($theme_setting_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-theme_setting_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_setting_description' maxlength='255' value='".escape($theme_setting_description)."'>\n";
	echo "<br />\n";
	echo $text['description-theme_setting_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</div>";
	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
