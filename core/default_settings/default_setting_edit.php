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
 Portions created by the Initial Developer are Copyright (C) 2008-2015
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('default_setting_add') || permission_exists('default_setting_edit')) {
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
		$default_setting_uuid = check_str($_REQUEST["id"]);
		$search = check_str($_REQUEST['search']);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_REQUEST) > 0) {
		$default_setting_category = strtolower(check_str($_REQUEST["default_setting_category"]));
		$default_setting_subcategory = strtolower(check_str($_POST["default_setting_subcategory"]));
		$default_setting_name = strtolower(check_str($_POST["default_setting_name"]));
		$default_setting_value = check_str($_POST["default_setting_value"]);
		$default_setting_order = check_str($_POST["default_setting_order"]);
		$default_setting_enabled = check_str($_POST["default_setting_enabled"]);
		$default_setting_description = check_str($_POST["default_setting_description"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$default_setting_uuid = check_str($_POST["default_setting_uuid"]);
	}

	//check for all required data
		if (strlen($default_setting_category) == 0) { $msg .= $text['message-required'].$text['label-category']."<br>\n"; }
		if (strlen($default_setting_subcategory) == 0) { $msg .= $text['message-required'].$text['label-subcategory']."<br>\n"; }
		if (strlen($default_setting_name) == 0) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
		//if (strlen($default_setting_value) == 0) { $msg .= $text['message-required'].$text['label-value']."<br>\n"; }
		if (strlen($default_setting_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
		if (strlen($default_setting_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
		//if (strlen($default_setting_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
			// fix null
			$default_setting_order = ($default_setting_order != '') ? $default_setting_order : 'null';

			//update switch timezone variables
			if ($default_setting_category == "domain" && $default_setting_subcategory == "time_zone" && $default_setting_name == "name" ) {
				//get the action
					$sql = "select * from v_vars ";
					$sql .= "where var_name = 'timezone' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					$var_action = "add";
					foreach ($result as $row) {
						$var_action = "update";
					}
					unset ($prep_statement);

				//update the timezone
					if ($var_action == "update") {
						$sql = "update v_vars ";
						$sql .= "set var_value = '".$default_setting_value."' ";
						$sql .= "where var_name = 'timezone' ";
					}
					else {
						$sql = "insert into v_vars ";
						$sql .= "(var_uuid, var_name, var_value, var_cat, var_enabled) ";
						$sql .= "values ('".uuid()."', 'timezone', '$default_setting_value', 'Defaults', 'true'); ";
					}
					$db->query($sql);
					unset($sql);

				//synchronize the configuration
					save_var_xml();
			}

			if ($action == "add" && permission_exists('default_setting_add')) {
				$sql = "insert into v_default_settings ";
				$sql .= "(";
				$sql .= "default_setting_uuid, ";
				$sql .= "default_setting_category, ";
				$sql .= "default_setting_subcategory, ";
				$sql .= "default_setting_name, ";
				$sql .= "default_setting_value, ";
				$sql .= "default_setting_order, ";
				$sql .= "default_setting_enabled, ";
				$sql .= "default_setting_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".uuid()."', ";
				$sql .= "'$default_setting_category', ";
				$sql .= "'$default_setting_subcategory', ";
				$sql .= "'$default_setting_name', ";
				$sql .= "'$default_setting_value', ";
				$sql .= "$default_setting_order, ";
				$sql .= "'$default_setting_enabled', ";
				$sql .= "'$default_setting_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-add'];
				header("Location: default_settings.php#".$default_setting_category);
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('default_setting_edit')) {
				$sql = "update v_default_settings set ";
				$sql .= "default_setting_category = '$default_setting_category', ";
				$sql .= "default_setting_subcategory = '$default_setting_subcategory', ";
				$sql .= "default_setting_name = '$default_setting_name', ";
				$sql .= "default_setting_value = '$default_setting_value', ";
				$sql .= "default_setting_order = $default_setting_order, ";
				$sql .= "default_setting_enabled = '$default_setting_enabled', ";
				$sql .= "default_setting_description = '$default_setting_description' ";
				$sql .= "where default_setting_uuid = '$default_setting_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-update'];
				header("Location: default_settings.php".(($search != '') ? "?search=".$search : null)."#".$default_setting_category);
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$default_setting_uuid = check_str($_GET["id"]);
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_uuid = '$default_setting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$default_setting_category = $row["default_setting_category"];
			$default_setting_subcategory = $row["default_setting_subcategory"];
			$default_setting_name = $row["default_setting_name"];
			$default_setting_value = $row["default_setting_value"];
			$default_setting_order = $row["default_setting_order"];
			$default_setting_enabled = $row["default_setting_enabled"];
			$default_setting_description = $row["default_setting_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-default_setting-edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-default_setting-add'];
	}

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-default_setting-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-default_setting-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='default_settings.php".(($search != '') ? "?search=".$search : null)."'\" value='".$text['button-back']."'>";
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "add") {
		echo $text['description-default_setting-add'];
	}
	if ($action == "update") {
		echo $text['description-default_setting-edit'];
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='default_setting_category' maxlength='255' value=\"$default_setting_category\">\n";
	echo "<br />\n";
	echo $text['description-category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-subcategory']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld lowercase' type='text' name='default_setting_subcategory' id='default_setting_subcategory' maxlength='255' value=\"$default_setting_subcategory\">\n";
	echo "<br />\n";
	echo $text['description-subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld lowercase' type='text' name='default_setting_name' id='default_setting_name' maxlength='255' value=\"$default_setting_name\">\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$category = $row['default_setting_category'];
	$subcategory = $row['default_setting_subcategory'];
	$name = $row['default_setting_name'];
	if ($category == "cdr" && $subcategory == "format" && $name == "text" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		if ($default_setting_value == "json") {
			echo "		<option value='json' selected='selected'>json</option>\n";
		}
		else {
			echo "		<option value='json'>json</option>\n";
		}
		if ($default_setting_value == "xml") {
			echo "		<option value='xml' selected='selected'>xml</option>\n";
		}
		else {
			echo "		<option value='xml'>xml</option>\n";
		}
		echo "		</select>\n";
	}
	elseif ($category == "cdr" && $subcategory == "storage" && $name == "text" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		if ($default_setting_value == "db") {
			echo "		<option value='db' selected='selected'>db</option>\n";
		}
		else {
			echo "		<option value='db'>db</option>\n";
		}
		if ($default_setting_value == "dir") {
			echo "		<option value='dir' selected='selected'>dir</option>\n";
		}
		else {
			echo "		<option value='dir'>dir</option>\n";
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		$sql = "";
		$sql .= "select * from v_menus ";
		$sql .= "order by menu_language, menu_name asc ";
		$sub_prep_statement = $db->prepare(check_sql($sql));
		$sub_prep_statement->execute();
		$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($sub_result as $sub_row) {
			if (strtolower($default_setting_value) == strtolower($sub_row["menu_uuid"])) {
				echo "		<option value='".strtolower($sub_row["menu_uuid"])."' selected='selected'>".$sub_row["menu_language"]." - ".$sub_row["menu_name"]."\n";
			}
			else {
				echo "		<option value='".strtolower($sub_row["menu_uuid"])."'>".$sub_row["menu_language"]." - ".$sub_row["menu_name"]."</option>\n";
			}
		}
		unset ($sub_prep_statement);
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "template" && $name == "name" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		//add all the themes to the list
		$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
		if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
			while (false !== ($dir_name = readdir($handle))) {
				if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && $dir_name != ".git" && is_dir($theme_dir.'/'.$dir_name)) {
					$dir_label = str_replace('_', ' ', $dir_name);
					$dir_label = str_replace('-', ' ', $dir_label);
					if ($dir_name == $default_setting_value) {
						echo "		<option value='$dir_name' selected='selected'>".ucwords($dir_label)."</option>\n";
					}
					else {
						echo "		<option value='$dir_name'>".ucwords($dir_label)."</option>\n";
					}
				}
			}
			closedir($handle);
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "language" && $name == "code" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		foreach ($_SESSION['app']['languages'] as $key => $value) {
			if ($default_setting_value == $value) {
				echo "		<option value='$value' selected='selected'>$value</option>\n";
			}
			else {
				echo "		<option value='$value'>$value</option>\n";
			}
		}
		echo "		</select>\n";
	}
	elseif ($category == "email" && $subcategory == "smtp_auth" && $name == "var" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		echo "    <option value=''></option>\n";
		if ($default_setting_value == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($default_setting_value == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
	}
	elseif ($category == "email" && $subcategory == "smtp_secure" && $name == "var" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		if ($default_setting_value == "none") {
		echo "    <option value='none' selected='selected'>".$text['label-none']."</option>\n";
		}
		else {
		echo "    <option value='none'>".$text['label-none']."</option>\n";
		}
		if ($default_setting_value == "tls") {
		echo "    <option value='tls' selected='selected'>TLS</option>\n";
		}
		else {
		echo "    <option value='tls'>TLS</option>\n";
		}
		if ($default_setting_value == "ssl") {
		echo "    <option value='ssl' selected='selected'>SSL</option>\n";
		}
		else {
		echo "    <option value='ssl'>SSL</option>\n";
		}
		echo "    </select>\n";
	}
	elseif ($category == "domain" && $subcategory == "time_zone" && $name == "name" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
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
			if (strlen($val) > 0) {
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
			if ($val == $default_setting_value) {
				echo "			<option value='".$val."' selected='selected'>(UTC ".$time_zone_offset_hours.":".$time_zone_offset_minutes.") ".$val."</option>\n";
			}
			else {
				echo "			<option value='".$val."'>(UTC ".$time_zone_offset_hours.":".$time_zone_offset_minutes.") ".$val."</option>\n";
			}
			$previous_category = $category;
			$x++;
		}
		echo "		</select>\n";
	}
	elseif ($subcategory == 'password' || substr_count($subcategory, '_password') > 0 || $category == "login" && $subcategory == "password_reset_key" && $name == "text") {
		echo "	<input class='formfld' type='password' name='default_setting_value' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value=\"".$default_setting_value."\">\n";
	}
	elseif (
		$category == "theme" && $subcategory == "background_color" && $name == "array" ||
		$category == "theme" && $subcategory == "login_shadow_color" && $name == "text" ||
		$category == "theme" && $subcategory == "login_background_color" && $name == "text" ||
		$category == "theme" && $subcategory == "domain_color" && $name == "text" ||
		$category == "theme" && $subcategory == "domain_shadow_color" && $name == "text" ||
		$category == "theme" && $subcategory == "domain_background_color" && $name == "text" ||
		$category == "theme" && $subcategory == "footer_color" && $name == "text" ||
		$category == "theme" && $subcategory == "footer_background_color" && $name == "text" ||
		$category == "theme" && $subcategory == "message_default_background_color" && $name == "text" ||
		$category == "theme" && $subcategory == "message_default_color" && $name == "text" ||
		$category == "theme" && $subcategory == "message_negative_background_color" && $name == "text" ||
		$category == "theme" && $subcategory == "message_negative_color" && $name == "text" ||
		$category == "theme" && $subcategory == "message_alert_background_color" && $name == "text" ||
		$category == "theme" && $subcategory == "message_alert_color" && $name == "text"
		) {
		echo "	<style>";
		echo "		DIV.rui-colorpicker  { width: 253px; }";
		echo "		DIV.rui-colorpicker DIV.controls { width: 61px; }";
		echo "		DIV.rui-colorpicker DIV.controls DIV.preview { width: 55px; }";
		echo "		DIV.rui-colorpicker DIV.controls INPUT.display { width: 61px; text-align: center; font-family: courier; }";
		echo "		DIV.rui-colorpicker DIV.controls DIV.rgb-display { width: 50px; }";
		echo "		DIV.rui-colorpicker DIV.controls DIV.rgb-display DIV INPUT { width: 30px; }";
		echo "	</style>";
		echo "	<input class='formfld' id='default_setting_value' name='default_setting_value' data-colorpcker=\"{format: 'hex'}\" value=\"".$default_setting_value."\">\n";
		echo "	<script type='text/javascript'>new Colorpicker().assignTo('default_setting_value');</script>";
	}
	elseif ($category == "fax" && $subcategory == "page_size" && $name == "text" ) {
		echo "	<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		echo "		<option value='letter' ".(($default_setting_value == 'letter') ? 'selected' : null).">Letter</option>";
		echo "		<option value='legal' ".(($default_setting_value == 'legal') ? 'selected' : null).">Legal</option>";
		echo "		<option value='a4' ".(($default_setting_value == 'a4') ? 'selected' : null).">A4</option>";
		echo "	</select>";
	}
	elseif ($category == "fax" && $subcategory == "resolution" && $name == "text" ) {
		echo "	<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		echo "		<option value='normal' ".(($default_setting_value == 'normal') ? 'selected' : null).">".$text['label-normal']."</option>";
		echo "		<option value='fine' ".(($default_setting_value == 'fine') ? 'selected' : null).">".$text['label-fine']."</option>";
		echo "		<option value='superfine' ".(($default_setting_value == 'superfine') ? 'selected' : null).">".$text['label-superfine']."</option>";
		echo "	</select>";
	}
	elseif ($category == "theme" && $subcategory == "domain_visible" && $name == "text" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		echo "    	<option value='false' ".(($default_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    	<option value='true' ".(($default_setting_value == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "cache" && $name == "boolean" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		echo "    	<option value='true' ".(($default_setting_value == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($default_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "voicemail" && $subcategory == "voicemail_file" && $name == "text" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		echo "    	<option value='listen' ".(($default_setting_value == "listen") ? "selected='selected'" : null).">".$text['option-voicemail_file_listen']."</option>\n";
		echo "    	<option value='link' ".(($default_setting_value == "link") ? "selected='selected'" : null).">".$text['option-voicemail_file_link']."</option>\n";
		echo "    	<option value='attach' ".(($default_setting_value == "attach") ? "selected='selected'" : null).">".$text['option-voicemail_file_attach']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "voicemail" && $subcategory == "keep_local" && $name == "boolean" ) {
		echo "	<select class='formfld' name='default_setting_value'>\n";
		echo "    	<option value='true' ".(($default_setting_value == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($default_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
	}
	elseif (is_json($default_setting_value)) {
		echo "	<textarea class='formfld' style='width: 100%; height: 80px; font-family: courier; white-space: nowrap; overflow: auto;' name='default_setting_value' wrap='off'>".$default_setting_value."</textarea>\n";
	}
	else {
		echo "	<input class='formfld' type='text' name='default_setting_value' value=\"".htmlspecialchars($default_setting_value)."\">\n";
	}
	echo "<br />\n";
	echo $text['description-value']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<div id='tr_order' ".(($default_setting_name != 'array') ? "style='display: none;'" : null).">\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<select name='default_setting_order' class='formfld'>\n";
	$i=0;
	while($i<=999) {
		$selected = ($i == $default_setting_order) ? "selected" : null;
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
	echo "</table>\n";
	echo "</div>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='default_setting_enabled'>\n";
	if ($default_setting_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($default_setting_enabled == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='default_setting_description' maxlength='255' value=\"".$default_setting_description."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='default_setting_uuid' value='".$default_setting_uuid."'>\n";
		echo "		<input type='hidden' name='search' value='".$search."'>\n";
	}
	echo "			<br>";
	echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	if ($_REQUEST["id"] == '' && $_REQUEST["default_setting_category"] != '') {
		echo "<script>document.getElementById('default_setting_subcategory').focus();</script>";
	}

	echo "<script>\n";
//capture enter key to submit form
	echo "	$(window).keypress(function(event){\n";
	echo "		if (event.which == 13) { submit_form(); }\n";
	echo "	});\n";
//hide/convert password fields then submit form
	echo "	function submit_form() {\n";
	echo "		$('input:password').css('visibility','hidden');\n";
	echo "		$('input:password').attr({type:'text'});\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
//define lowercase class
	echo "	$('.lowercase').blur(function(){ this.value = this.value.toLowerCase(); });";
//show order if array
	echo "	$('#default_setting_name').keyup(function(){ \n";
	echo "		(this.value.toLowerCase() == 'array') ? $('#tr_order').slideDown('fast') : $('#tr_order').slideUp('fast');\n";
	echo "	});\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";
?>