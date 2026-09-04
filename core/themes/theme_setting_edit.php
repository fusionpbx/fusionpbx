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
	echo $text['description-category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_subcategory']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_setting_subcategory' maxlength='255' value='".escape($theme_setting_subcategory)."'>\n";
	echo "<br />\n";
	echo $text['description-subcategory']."\n";
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
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	$category = $theme_setting_category;
	$subcategory = $theme_setting_subcategory;
	$name = $theme_setting_name;
	if ($category == "cdr" && $subcategory == "format" && $name == "text" ) {
		echo "		<select class='formfld' id='theme_setting_value' name='theme_setting_value' style=''>\n";
		if ($theme_setting_value == "json") {
			echo "		<option value='json' selected='selected'>json</option>\n";
		}
		else {
			echo "		<option value='json'>json</option>\n";
		}
		if ($theme_setting_value == "xml") {
			echo "		<option value='xml' selected='selected'>xml</option>\n";
		}
		else {
			echo "		<option value='xml'>xml</option>\n";
		}
		echo "		</select>\n";
	}
	elseif ($category == "cdr" && $subcategory == "storage" && $name == "text" ) {
		echo "		<select class='formfld' id='theme_setting_value' name='theme_setting_value' style=''>\n";
		if ($theme_setting_value == "db") {
			echo "		<option value='db' selected='selected'>db</option>\n";
		}
		else {
			echo "		<option value='db'>db</option>\n";
		}
		if ($theme_setting_value == "dir") {
			echo "		<option value='dir' selected='selected'>dir</option>\n";
		}
		else {
			echo "		<option value='dir'>dir</option>\n";
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
		echo "		<select class='formfld' id='theme_setting_value' name='theme_setting_value' style=''>\n";
		$sql = "select * from v_menus ";
		$sql .= "order by menu_language, menu_name asc ";
		$sub_result = $database->select($sql, null, 'all');
		if (is_array($sub_result) && sizeof($sub_result) != 0) {
			foreach ($sub_result as $sub_row) {
				$selected = strtolower($theme_setting_value) == strtolower($sub_row["menu_uuid"]) ? "selected='selected'" : null;
				echo "		<option value='".strtolower(escape($sub_row["menu_uuid"]))."' ".$selected.">".escape($sub_row["menu_language"])." - ".escape($sub_row["menu_name"])."</option>\n";
			}
		}
		unset($sql, $sub_result, $sub_row, $selected);
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "template") {
		echo "		<select class='formfld' id='theme_setting_value' name='theme_setting_value' style=''>\n";
		//add all the themes to the list
		$theme_dir = dirname(__DIR__, 2).'/themes';
		if ($handle = opendir(dirname(__DIR__, 2).'/themes')) {
			while (false !== ($dir_name = readdir($handle))) {
				if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && $dir_name != ".git" && is_dir($theme_dir.'/'.$dir_name)) {
					$dir_label = str_replace('_', ' ', $dir_name);
					$dir_label = str_replace('-', ' ', $dir_label);
					if ($dir_name == $theme_setting_value) {
						echo "		<option value='".escape($dir_name)."' selected='selected'>".ucwords(escape($dir_label))."</option>\n";
					}
					else {
						echo "		<option value='".escape($dir_name)."'>".ucwords(escape($dir_label))."</option>\n";
					}
				}
			}
			closedir($handle);
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "language" && $name == "code" ) {
		echo "		<select class='formfld' id='theme_setting_value' name='theme_setting_value' style=''>\n";
		foreach ($_SESSION['app']['languages'] as $key => $value) {
			if ($theme_setting_value == $value) {
				echo "		<option value='".escape($value)."' selected='selected'>".escape($value)."</option>\n";
			}
			else {
				echo "		<option value='".escape($value)."'>".escape($value)."</option>\n";
			}
		}
		echo "		</select>\n";
	}
	elseif ($category == "email" && $subcategory == "smtp_auth" && $name == "var" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    <option value=''></option>\n";
		if ($theme_setting_value == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($theme_setting_value == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
	}
	elseif ($category == "email" && $subcategory == "smtp_secure" && $name == "var" ) {
		echo "    <select class='formfld' name='theme_setting_value'>\n";
		if ($theme_setting_value == "none") {
		echo "    <option value='none' selected='selected'>".$text['label-none']."</option>\n";
		}
		else {
		echo "    <option value='none'>".$text['label-none']."</option>\n";
		}
		if ($theme_setting_value == "tls") {
		echo "    <option value='tls' selected='selected'>TLS</option>\n";
		}
		else {
		echo "    <option value='tls'>TLS</option>\n";
		}
		if ($theme_setting_value == "ssl") {
		echo "    <option value='ssl' selected='selected'>SSL</option>\n";
		}
		else {
		echo "    <option value='ssl'>SSL</option>\n";
		}
		echo "    </select>\n";
	}
	elseif ($category == "domain" && $subcategory == "time_zone") {
		echo "		<select class='formfld searchable_select' id='theme_setting_value' name='theme_setting_value' style=''>\n";
		//$list = DateTimeZone::listAbbreviations();
		$time_zone_identifiers = DateTimeZone::listIdentifiers();
		$previous_category = '';
		$x = 0;
		foreach ($time_zone_identifiers as $key => $val) {
			$time_zone = explode("/", $val);
			$category = $time_zone[0];
			if ($category != $previous_category) {
				if ($x > 0) {
					echo "		</optgroup>\n";
				}
				echo "		<optgroup label='".$category."'>\n";
			}
			if (!empty($val)) {
				$time_zone_offset = get_time_zone_offset($val)/3600;
				$time_zone_offset_hours = floor($time_zone_offset);
				$time_zone_offset_minutes = ($time_zone_offset - $time_zone_offset_hours) * 60;
				$time_zone_offset_minutes = number_pad($time_zone_offset_minutes, 2);
				if ($time_zone_offset > 0) {
					$time_zone_offset_hours = number_pad($time_zone_offset_hours, 2);
					$time_zone_offset_hours = "+".$time_zone_offset_hours;
				}
				else {
					$time_zone_offset_hours = str_replace("-", "", $time_zone_offset_hours);
					$time_zone_offset_hours = "-".number_pad($time_zone_offset_hours, 2);
				}
			}
			if ($val == $theme_setting_value) {
				echo "			<option value='".escape($val)."' selected='selected'>(UTC ".$time_zone_offset_hours.":".$time_zone_offset_minutes.") ".escape($val)."</option>\n";
			}
			else {
				echo "			<option value='".escape($val)."'>(UTC ".$time_zone_offset_hours.":".$time_zone_offset_minutes.") ".escape($val)."</option>\n";
			}
			$previous_category = $category;
			$x++;
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "time_format" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='24h' ".(($theme_setting_value == "24h") ? "selected='selected'" : null).">".$text['label-24-hour']."</option>\n";
		echo "    	<option value='12h' ".(($theme_setting_value == "12h") ? "selected='selected'" : null).">".$text['label-12-hour']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($subcategory == 'password' || (substr_count($subcategory, '_password') > 0 && $subcategory != 'input_text_font_password') || $category == "login" && $subcategory == "password_reset_key" && $name == "text") {
		echo "	<input class='formfld password' type='password' id='theme_setting_value' name='theme_setting_value' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value=\"".escape($theme_setting_value)."\">\n";
	}
	elseif (substr_count($subcategory, "_color") > 0 && ($name == "text" || $name == 'array')) {
		echo "	<input type='text' class='formfld colorpicker' id='theme_setting_value' name='theme_setting_value' value=\"".escape($theme_setting_value)."\">\n";
	}
	elseif ($category == "theme" && substr_count($subcategory, "_font") > 0 && $name == "text") {
		$theme_setting_value = str_replace('"', "'", $theme_setting_value);
		if ($fonts = get_available_fonts('alpha')) {
			echo "	<select class='formfld' id='sel_theme_setting_value' onchange=\"if (this.selectedIndex == $('select#sel_theme_setting_value option').length - 1) { $('#txt_theme_setting_value').val('').fadeIn('fast'); $('#txt_theme_setting_value').trigger('focus'); } else { $('#txt_theme_setting_value').fadeOut('fast', function(){ $('#txt_theme_setting_value').val($('#sel_theme_setting_value').val()) }); } \">\n";
			echo "		<option value=''></option>\n";
			echo "		<optgroup label='".$text['label-web_fonts']."'>\n";
			$option_found = false;
			foreach ($fonts as $n => $font) {
				if ($theme_setting_value == $font) {
					$selected = 'selected';
					$option_found = true;
				}
				else {
					unset($selected);
				}
				echo "		<option value='".$font."' ".$selected.">".$font."</option>\n";
			}
			echo "		</optgroup>\n";
			echo "		<option value='' disabled='disabled'></option>\n";
			echo "		<option value='' ".(($theme_setting_value != '' && $option_found == false) ? 'selected' : null).">".$text['label-other']."...</option>\n";
			echo "	</select>";
			echo "	<input type='text' class='formfld' ".(($theme_setting_value == '' || $option_found) ? "style='display: none;'" : null)." id='txt_theme_setting_value' name='theme_setting_value' value=\"".escape($theme_setting_value)."\">\n";
		}
		else {
			echo "	<input type='text' class='formfld' id='theme_setting_value' name='theme_setting_value' value=\"".$theme_setting_value."\">\n";
		}
	}
	elseif ($category == "fax" && $subcategory == "page_size" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value' style=''>\n";
		echo "		<option value='letter' ".(($theme_setting_value == 'letter') ? 'selected' : null).">Letter</option>";
		echo "		<option value='legal' ".(($theme_setting_value == 'legal') ? 'selected' : null).">Legal</option>";
		echo "		<option value='a4' ".(($theme_setting_value == 'a4') ? 'selected' : null).">A4</option>";
		echo "	</select>";
	}
	elseif ($category == "fax" && $subcategory == "resolution" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value' style=''>\n";
		echo "		<option value='normal' ".(($theme_setting_value == 'normal') ? 'selected' : null).">".$text['label-normal']."</option>";
		echo "		<option value='fine' ".(($theme_setting_value == 'fine') ? 'selected' : null).">".$text['label-fine']."</option>";
		echo "		<option value='superfine' ".(($theme_setting_value == 'superfine') ? 'selected' : null).">".$text['label-superfine']."</option>";
		echo "	</select>";
	}
	elseif ($category == "provision" && $subcategory == "aastra_time_format" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "		<option value='1' ".(($theme_setting_value == "1") ? "selected='selected'" : null).">".$text['label-24-hour']."</option>\n";
		echo "		<option value='0' ".(($theme_setting_value == "0") ? "selected='selected'" : null).">".$text['label-12-hour']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "provision" && $subcategory == "aastra_date_format" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "		<option value='0' ".(($theme_setting_value == "0") ? "selected='selected'" : null).">WWW MMM DD</option>\n";
		echo "		<option value='1' ".(($theme_setting_value == "1") ? "selected='selected'" : null).">DD-MMM-YY</option>\n";
		echo "		<option value='2' ".(($theme_setting_value == "2") ? "selected='selected'" : null).">YYYY-MM-DD</option>\n";
		echo "		<option value='3' ".(($theme_setting_value == "3") ? "selected='selected'" : null).">DD/MM/YYYY</option>\n";
		echo "		<option value='4' ".(($theme_setting_value == "4") ? "selected='selected'" : null).">DD/MM/YY</option>\n";
		echo "		<option value='5' ".(($theme_setting_value == "5") ? "selected='selected'" : null).">DD-MM-YY</option>\n";
		echo "		<option value='6' ".(($theme_setting_value == "6") ? "selected='selected'" : null).">MM/DD/YY</option>\n";
		echo "		<option value='7' ".(($theme_setting_value == "7") ? "selected='selected'" : null).">MMM DD</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "message" && $subcategory == "display_last" && $name == "text") {
		$array = explode(' ',$theme_setting_value);
		if (!is_numeric($array[0])) { $array[1] = $array[0]; $array[0] = ''; }
		echo "	<input type='text' class='formfld' id='theme_setting_value_1' value=\"".$array[0]."\" onchange=\"$('#theme_setting_value').val($('#theme_setting_value_1').val() + ' ' + $('#theme_setting_value_2 option:selected').val());\">\n";
		echo "	<select class='formfld' id='theme_setting_value_2' onchange=\"$('#theme_setting_value').val($('#theme_setting_value_1').val() + ' ' + $('#theme_setting_value_2 option:selected').val());\">\n";
		echo "		<option value='hours' ".($array[1] == "hours" ? "selected='selected'" : null).">".$text['label-hours']."</option>\n";
		echo "		<option value='days' ".($array[1] == "days" ? "selected='selected'" : null).">".$text['label-days']."</option>\n";
		echo "		<option value='messages' ".($array[1] == "messages" ? "selected='selected'" : null).">".$text['label-messages']."</option>\n";
		echo "	</select>\n";
		echo "	<input type='hidden' id='theme_setting_value' name='theme_setting_value' value=\"".$theme_setting_value."\">\n";
		unset($array);
	}
	elseif ($category == "theme" && $subcategory == "domain_visible" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='false' ".(($theme_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    	<option value='true' ".(($theme_setting_value == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_brand_type" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='image' ".(($theme_setting_value == "image") ? "selected='selected'" : null).">".$text['label-image']."</option>\n";
		echo "    	<option value='text' ".(($theme_setting_value == "text") ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		echo "    	<option value='image_text' ".(($theme_setting_value == "image_text") ? "selected='selected'" : null).">".$text['label-image_text']."</option>\n";
		echo "    	<option value='none' ".(($theme_setting_value == "none") ? "selected='selected'" : null).">".$text['label-none']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_style" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='fixed' ".(($theme_setting_value == "fixed") ? "selected='selected'" : null).">".$text['label-fixed']."</option>\n";
		echo "    	<option value='static' ".(($theme_setting_value == "static") ? "selected='selected'" : null).">".$text['label-static']."</option>\n";
		echo "    	<option value='inline' ".(($theme_setting_value == "inline") ? "selected='selected'" : null).">".$text['label-inline']."</option>\n";
		echo "    	<option value='side' ".(($theme_setting_value == "side") ? "selected='selected'" : null).">".$text['label-side']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "logo_align" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='left' ".(($theme_setting_value == "left") ? "selected='selected'" : null).">".$text['label-left']."</option>\n";
		echo "    	<option value='center' ".(($theme_setting_value == "center") ? "selected='selected'" : null).">".$text['label-center']."</option>\n";
		echo "    	<option value='right' ".(($theme_setting_value == "right") ? "selected='selected'" : null).">".$text['label-right']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "custom_css_code" && $name == "text" ) {
		echo "	<textarea class='formfld' style='min-width: 100%; height: 300px; font-family: courier, monospace; overflow: auto; resize: vertical' id='theme_setting_value' name='theme_setting_value' wrap='off'>".escape($theme_setting_value)."</textarea>\n";
	}
	elseif ($category == "theme" && $subcategory == "button_icons" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='auto'>".$text['option-button_icons_auto']."</option>\n";
		echo "    	<option value='only' ".($theme_setting_value == "only" ? "selected='selected'" : null).">".$text['option-button_icons_only']."</option>\n";
		echo "    	<option value='always' ".($theme_setting_value == "always" ? "selected='selected'" : null).">".$text['option-button_icons_always']."</option>\n";
		echo "    	<option value='never' ".($theme_setting_value == "never" ? "selected='selected'" : null).">".$text['option-button_icons_never']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_side_state" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='expanded'>".$text['option-expanded']."</option>\n";
		echo "    	<option value='contracted' ".($theme_setting_value == "contracted" ? "selected='selected'" : null).">".$text['option-contracted']."</option>\n";
		echo "    	<option value='hidden' ".($theme_setting_value == "hidden" ? "selected='selected'" : null).">".$text['option-hidden']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_side_toggle" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='hover'>".$text['option-hover']."</option>\n";
		echo "    	<option value='click' ".($theme_setting_value == "click" ? "selected='selected'" : null).">".$text['option-click']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_side_toggle_body_width" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='shrink'>".$text['option-shrink']."</option>\n";
		echo "    	<option value='fixed' ".($theme_setting_value == "fixed" ? "selected='selected'" : null).">".$text['option-fixed']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_side_item_main_sub_close" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='automatic'>".$text['option-automatic']."</option>\n";
		echo "    	<option value='manual' ".($theme_setting_value == "manual" ? "selected='selected'" : null).">".$text['option-manual']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "body_header_brand_type" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='image' ".(($theme_setting_value == "image") ? "selected='selected'" : null).">".$text['label-image']."</option>\n";
		echo "    	<option value='text' ".(($theme_setting_value == "text") ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		echo "    	<option value='image_text' ".(($theme_setting_value == "image_text") ? "selected='selected'" : null).">".$text['label-image_text']."</option>\n";
		echo "    	<option value='none' ".(($theme_setting_value == "none") ? "selected='selected'" : null).">".$text['label-none']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "input_toggle_style" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='select'>".$text['option-select_box']."</option>\n";
		echo "    	<option value='switch_round' ".(($theme_setting_value == "switch_round") ? "selected='selected'" : null).">".$text['option-switch_round']."</option>\n";
		echo "    	<option value='switch_square' ".(($theme_setting_value == "switch_square") ? "selected='selected'" : null).">".$text['option-switch_square']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "users" && $subcategory == "username_format" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='any' ".($theme_setting_value == 'any' ? "selected='selected'" : null).">".$text['option-username_format_any']."</option>\n";
		echo "    	<option value='email' ".($theme_setting_value == 'email' ? "selected='selected'" : null).">".$text['option-username_format_email']."</option>\n";
		echo "    	<option value='no_email' ".($theme_setting_value == 'no_email' ? "selected='selected'" : null).">".$text['option-username_format_no_email']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "voicemail" && $subcategory == "voicemail_file" && $name == "text" ) {
		echo "    <select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='listen' ".(($theme_setting_value == "listen") ? "selected='selected'" : null).">".$text['option-voicemail_file_listen']."</option>\n";
		echo "    	<option value='link' ".(($theme_setting_value == "link") ? "selected='selected'" : null).">".$text['option-voicemail_file_link']."</option>\n";
		echo "    	<option value='attach' ".(($theme_setting_value == "attach") ? "selected='selected'" : null).">".$text['option-voicemail_file_attach']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "voicemail" && ($subcategory == "message_caller_id_number" || $subcategory == "message_date_time") && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='before'>".$text['label-before']."</option>\n";
		echo "    	<option value='after' ".(($theme_setting_value == "after") ? "selected='selected'" : null).">".$text['label-after']."</option>\n";
		echo "    	<option value='false' ".(($theme_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "recordings" && $subcategory == "storage_type" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='file'>".$text['label-file']."</option>\n";
		echo "    	<option value='base64' ".(($theme_setting_value == "base64") ? "selected='selected'" : null).">".$text['label-base64']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "destinations" && $subcategory == "dialplan_mode" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='multiple'>".$text['label-multiple']."</option>\n";
		echo "    	<option value='single' ".(($theme_setting_value == "single") ? "selected='selected'" : null).">".$text['label-single']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "destinations" && $subcategory == "select_mode" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='default'>".$text['label-default']."</option>\n";
		echo "    	<option value='dynamic' ".(($theme_setting_value == "dynamic") ? "selected='selected'" : null).">".$text['label-dynamic']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "cdr" && $subcategory == "column_overflow" && $name == "text" ) {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		echo "    	<option value='hidden' ".(($theme_setting_value == "hidden") ? "selected='selected'" : null).">".$text['label-hidden']."</option>\n";
		echo "    	<option value='scroll' ".(($theme_setting_value == "scroll") ? "selected='selected'" : null).">".$text['label-scroll']."</option>\n";
		echo "	</select>\n";
	}
	elseif (is_json($theme_setting_value)) {
		echo "	<textarea class='formfld' style='width: 100%; height: 80px; font-family: courier, monospace; overflow: auto;' id='theme_setting_value' name='theme_setting_value' wrap='off'>".escape($theme_setting_value)."</textarea>\n";
	}
	elseif ($name == "boolean") {
		echo "	<select class='formfld' id='theme_setting_value' name='theme_setting_value'>\n";
		if ($category == "provision" && is_numeric($theme_setting_value)) {
			echo "	<option value='0'>".$text['label-false']."</option>\n";
			echo "	<option value='1' ".(($theme_setting_value == 1) ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
			echo "	<option value='true' ".((strtolower($theme_setting_value) == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		}
		echo "	</select>\n";
	}
	else {
		if (strlen($theme_setting_value) > 25) {
			echo "	<textarea class='formfld' style='width: 185px; height: auto; max-height: 300px;' id='theme_setting_value' name='theme_setting_value'>".($theme_setting_value ?? '')."</textarea>\n";

			echo "	<script>\n";
			echo "	document.addEventListener('DOMContentLoaded', () => {\n";
			echo "		let textarea = document.getElementById('theme_setting_value');\n";
			echo "		textarea.style.height = 'auto';\n";
			echo "		textarea.style.height = textarea.scrollHeight + 'px';\n";
			echo "	});\n";
			echo "	</script>\n";
		}
		else {
			echo "	<input class='formfld' type='text' id='theme_setting_value' name='theme_setting_value' value=\"".escape($theme_setting_value ?? '')."\">\n";
		}
	}
	echo "<br />\n";
	echo $text['description-value']."\n";
	if ($category == "theme" && substr_count($subcategory, "_font") > 0 && $name == "text") {
		echo "&nbsp;&nbsp;".$text['label-reference'].": <a href='https://fonts.google.com' target='_blank'>".$text['label-web_fonts']."</a>\n";
	}
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
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_setting_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_setting_description' maxlength='255' value='".escape($theme_setting_description)."'>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
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
