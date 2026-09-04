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
	if (!(permission_exists('theme_add') || permission_exists('theme_edit'))) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set from session variables
	$button_icon_back = $settings->get('theme', 'button_icon_back', '');
	$button_icon_copy = $settings->get('theme', 'button_icon_copy', '');
	$button_icon_delete = $settings->get('theme', 'button_icon_delete', '');
	$button_icon_save = $settings->get('theme', 'button_icon_save', '');
	$input_toggle_style = $settings->get('theme', 'input_toggle_style', 'switch round');

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$theme_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
		$theme_uuid = '';
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$theme_name = $_POST["theme_name"] ?? null;
		$theme_enabled = $_POST["theme_enabled"] ?? null;
		$theme_description = $_POST["theme_description"] ?? null;
		$theme_settings = $_POST['theme_settings' ?? null];
	}

//process the data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: theme_edit.php?id='.urlencode($theme_uuid));
				exit;
			}

		//process the http post data by action
			if (!empty($action) && !empty($theme_settings)) {
				//process the http post data by action
				switch ($action) {
					case 'copy':
						if (permission_exists('theme_setting_add')) {
							$obj = new themes;
							$obj->copy_settings($theme_settings);
						}
						break;
					case 'toggle':
						if (permission_exists('theme_setting_edit')) {
							$obj = new themes;
							$obj->toggle_settings($theme_settings);
						}
						break;
					case 'delete':
						if (permission_exists('theme_setting_delete')) {
							$obj = new themes;
							$obj->delete_settings($theme_settings);
						}
						break;
				}

				//redirect the user
				header('Location: theme_edit.php?id='.urlencode($theme_uuid));
				exit;
			}

		//check for all required data
			$msg = '';
			// if (empty($theme_name)) { $msg .= $text['message-required']." ".$text['label-theme_name']."<br>\n"; }
			// if (empty($theme_enabled)) { $msg .= $text['message-required']." ".$text['label-theme_enabled']."<br>\n"; }
			// if (empty($theme_description)) { $msg .= $text['message-required']." ".$text['label-theme_description']."<br>\n"; }
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

		//add the theme_uuid
			if (!is_uuid($theme_uuid)) {
				$theme_uuid = uuid();
			}

		//prepare the array
			$array['themes'][0]['theme_uuid'] = $theme_uuid;
			$array['themes'][0]['theme_name'] = $theme_name;
			$array['themes'][0]['theme_enabled'] = $theme_enabled;
			$array['themes'][0]['theme_description'] = $theme_description;

		//save the data
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: theme_edit.php?id='.urlencode($theme_uuid));
				return;
			}

	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		$sql .= " theme_uuid, ";
		$sql .= " theme_name, ";
		$sql .= " theme_enabled , ";
		$sql .= " theme_description ";
		$sql .= "from v_themes ";
		$sql .= "where theme_uuid = :theme_uuid ";
		$parameters['theme_uuid'] = $theme_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$theme_name = $row["theme_name"];
			$theme_enabled = $row["theme_enabled"];
			$theme_description = $row["theme_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-theme'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";
	echo "<input class='formfld' type='hidden' name='theme_uuid' value='".escape($theme_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-theme']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$button_icon_back,'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'themes.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$button_icon_save,'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-themes']."\n";
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_name' maxlength='255' value='".escape($theme_name)."'>\n";
	echo "<br />\n";
	echo $text['description-theme_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='theme_enabled' name='theme_enabled'>\n";
	echo "		<option value='true' ".($theme_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($theme_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-theme_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_description' maxlength='255' value='".escape($theme_description)."'>\n";
	echo "<br />\n";
	echo $text['description-theme_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

	if ($action == "update") {
		require_once "core/themes/theme_setting_list.php";
	}

//include the footer
	require_once "resources/footer.php";

?>
