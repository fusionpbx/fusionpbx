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
if (permission_exists('domain_setting_add') || permission_exists('domain_setting_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//retrieve allowed setting categories
	if (!permission_exists('domain_setting_category_edit')) {
		if (is_array($_SESSION['settings']) && sizeof($_SESSION['settings']) > 0) {
			foreach ($_SESSION['groups'] as $index => $group) {
				$group_name = $group['group_name'];
				if (is_array($_SESSION['settings'][$group_name]) && sizeof($_SESSION['settings'][$group_name]) > 0) {
					foreach ($_SESSION['settings'][$group_name] as $category) {
						$categories[] = strtolower($category);
					}
				}
			}
		}
		if (is_array($categories) && sizeof($categories) > 0) {
			$allowed_categories = array_unique($categories);
			sort($allowed_categories, SORT_NATURAL);
		}
		unset($group, $group_name, $index, $category, $categories);
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$domain_setting_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//set the domain_uuid
	if (strlen($_GET["domain_uuid"]) > 0) {
		$domain_uuid = check_str($_GET["domain_uuid"]);
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$domain_setting_category = strtolower(check_str($_POST["domain_setting_category"]));
		$domain_setting_subcategory = strtolower(check_str($_POST["domain_setting_subcategory"]));
		$domain_setting_name = strtolower(check_str($_POST["domain_setting_name"]));
		$domain_setting_value = check_str($_POST["domain_setting_value"]);
		$domain_setting_order = check_str($_POST["domain_setting_order"]);
		$domain_setting_enabled = strtolower(check_str($_POST["domain_setting_enabled"]));
		$domain_setting_description = check_str($_POST["domain_setting_description"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$domain_setting_uuid = check_str($_POST["domain_setting_uuid"]);
	}

	//check for all required/authorized data
		if (strlen($domain_setting_category) == 0 || (is_array($allowed_categories) && sizeof($allowed_categories) > 0 && !in_array(strtolower($domain_setting_category), $allowed_categories))) { $msg .= $text['message-required'].$text['label-category']."<br>\n"; }
		if (strlen($domain_setting_subcategory) == 0) { $msg .= $text['message-required'].$text['label-subcategory']."<br>\n"; }
		if (strlen($domain_setting_name) == 0) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
		//if (strlen($domain_setting_value) == 0) { $msg .= $text['message-required'].$text['label-value']."<br>\n"; }
		if (strlen($domain_setting_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
		if (strlen($domain_setting_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
		//if (strlen($domain_setting_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
				$domain_setting_order = ($domain_setting_order != '') ? $domain_setting_order : 'null';

			//update switch timezone variables
				if ($domain_setting_category == "domain" && $domain_setting_subcategory == "time_zone" && $domain_setting_name == "name" ) {
					//get the dialplan_uuid
						$sql = "select * from v_dialplans ";
						$sql .= "where domain_uuid = '".$domain_uuid."' ";
						$sql .= "and app_uuid = '9f356fe7-8cf8-4c14-8fe2-6daf89304458' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						foreach ($result as $row) {
							$dialplan_uuid = $row["dialplan_uuid"];
						}
						unset ($prep_statement);

					//get the action
						$sql = "select * from v_dialplan_details ";
						$sql .= "where domain_uuid = '".$domain_uuid."' ";
						$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
						$sql .= "and dialplan_detail_tag = 'action' ";
						$sql .= "and dialplan_detail_type = 'set' ";
						$sql .= "and dialplan_detail_data like 'timezone=%' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						$detail_action = "add";
						foreach ($result as $row) {
							$dialplan_detail_uuid = $row["dialplan_detail_uuid"];
							$detail_action = "update";
						}
						unset ($prep_statement);

					//update the timezone
						if ($detail_action == "update") {
							$sql = "update v_dialplan_details ";
							$sql .= "set dialplan_detail_data = 'timezone=".$domain_setting_value."' ";
							$sql .= "where dialplan_detail_uuid = '".$dialplan_detail_uuid."' ";
						}
						else {
							$dialplan_detail_uuid = uuid();
							$dialplan_detail_group = 0;
							$sql = "insert into v_dialplan_details ";
							$sql .= "(";
							$sql .= "domain_uuid, ";
							$sql .= "dialplan_detail_uuid, ";
							$sql .= "dialplan_uuid, ";
							$sql .= "dialplan_detail_tag, ";
							$sql .= "dialplan_detail_type, ";
							$sql .= "dialplan_detail_data, ";
							$sql .= "dialplan_detail_inline, ";
							$sql .= "dialplan_detail_group ";
							$sql .= ") ";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".$domain_uuid."', ";
							$sql .= "'".$dialplan_detail_uuid."', ";
							$sql .= "'".$dialplan_uuid."', ";
							$sql .= "'action', ";
							$sql .= "'set', ";
							$sql .= "'timezone=".$domain_setting_value."', ";
							$sql .= "'true', ";
							$sql .= "'".$dialplan_detail_group."' ";
							$sql .= "); ";
						}
						$db->query($sql);
						unset($sql);
				}

			//add the domain
				if ($action == "add" && permission_exists('domain_setting_add')) {
					$sql = "insert into v_domain_settings ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "domain_setting_uuid, ";
					$sql .= "domain_setting_category, ";
					$sql .= "domain_setting_subcategory, ";
					$sql .= "domain_setting_name, ";
					$sql .= "domain_setting_value, ";
					$sql .= "domain_setting_order, ";
					$sql .= "domain_setting_enabled, ";
					$sql .= "domain_setting_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'$domain_setting_category', ";
					$sql .= "'$domain_setting_subcategory', ";
					$sql .= "'$domain_setting_name', ";
					$sql .= "'$domain_setting_value', ";
					$sql .= "$domain_setting_order, ";
					$sql .= "'$domain_setting_enabled', ";
					$sql .= "'$domain_setting_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "add")

			//update the domain
				if ($action == "update" && permission_exists('domain_setting_edit')) {
					$sql = "update v_domain_settings set ";
					$sql .= "domain_setting_category = '$domain_setting_category', ";
					$sql .= "domain_setting_subcategory = '$domain_setting_subcategory', ";
					$sql .= "domain_setting_name = '$domain_setting_name', ";
					$sql .= "domain_setting_value = '$domain_setting_value', ";
					$sql .= "domain_setting_order = $domain_setting_order, ";
					$sql .= "domain_setting_enabled = '$domain_setting_enabled', ";
					$sql .= "domain_setting_description = '$domain_setting_description' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and domain_setting_uuid = '$domain_setting_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "update")

			//update time zone
				if ($domain_setting_category == "domain" && $domain_setting_subcategory == "time_zone" && $domain_setting_name == "name" && strlen($domain_setting_value) > 0 ) {
					$sql = "select * from v_dialplans ";
					$sql .= "where app_uuid = '34dd307b-fffe-4ead-990c-3d070e288126' ";
					$sql .= "and domain_uuid = '".$_SESSION["domain_uuid"]."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					$time_zone_found = false;
					foreach ($result as &$row) {
						//get the dialplan_uuid
							$dialplan_uuid = $row["dialplan_uuid"];

						//get the dialplan details
							$sql = "select * from v_dialplan_details ";
							$sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
							$sql .= "and domain_uuid = '".$_SESSION["domain_uuid"]."' ";
							$sub_prep_statement = $db->prepare(check_sql($sql));
							$sub_prep_statement->execute();
							$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($sub_result as $field) {
								$dialplan_detail_uuid = $field["dialplan_detail_uuid"];
								$dialplan_detail_tag = $field["dialplan_detail_tag"]; //action //condition
								$dialplan_detail_type = $field["dialplan_detail_type"]; //set
								$dialplan_detail_data = $field["dialplan_detail_data"];
								$dialplan_detail_group = $field["dialplan_detail_group"];
								if ($dialplan_detail_tag == "action" && $dialplan_detail_type == "set") {
									$data_array = explode("=", $dialplan_detail_data);
									if ($data_array[0] == "timezone") {
										$time_zone_found = true;
										break;
									}
								}
							}

						//add the time zone
							if (!$time_zone_found) {
								//$dialplan_detail_uuid = uuid();
								$dialplan_detail_uuid = "eb3b3a4e-88ea-4306-b2a8-9f52d3c95f2f";
								$sql = "insert into v_dialplan_details ";
								$sql .= "(";
								$sql .= "domain_uuid, ";
								$sql .= "dialplan_uuid, ";
								$sql .= "dialplan_detail_uuid, ";
								$sql .= "dialplan_detail_tag, ";
								$sql .= "dialplan_detail_type, ";
								$sql .= "dialplan_detail_data, ";
								$sql .= "dialplan_detail_group, ";
								$sql .= "dialplan_detail_order ";
								$sql .= ") ";
								$sql .= "values ";
								$sql .= "(";
								$sql .= "'".$_SESSION["domain_uuid"]."', "; //8cfd9525-6ccf-4c2c-813a-bca5809067cd
								$sql .= "'$dialplan_uuid', "; //807b4aa6-4478-4663-a661-779397c1d542
								$sql .= "'$dialplan_detail_uuid', ";
								$sql .= "'action', ";
								$sql .= "'set', ";
								$sql .= "'timezone=$domain_setting_value', ";
								if (strlen($dialplan_detail_group) > 0) {
									$sql .= "'$dialplan_detail_group', ";
								}
								else {
									$sql .= "null, ";
								}
								$sql .= "'15' ";
								$sql .= ")";
								$db->exec(check_sql($sql));
								unset($sql);
							}

						//update the time zone
							if ($time_zone_found) {
								$sql = "update v_dialplan_details set ";
								$sql .= "dialplan_detail_data = 'timezone=".$domain_setting_value."' ";
								$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
								$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
								$sql .= "and dialplan_detail_uuid = '$dialplan_detail_uuid' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
					}
				}

			//redirect the browser
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				header("Location: domain_edit.php?id=".$domain_uuid);
				return;
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$domain_setting_uuid = check_str($_GET["id"]);
		$sql = "select * from v_domain_settings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and domain_setting_uuid = '$domain_setting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_setting_category = $row["domain_setting_category"];
			$domain_setting_subcategory = $row["domain_setting_subcategory"];
			$domain_setting_name = $row["domain_setting_name"];
			$domain_setting_value = $row["domain_setting_value"];
			$domain_setting_order = $row["domain_setting_order"];
			$domain_setting_enabled = $row["domain_setting_enabled"];
			$domain_setting_description = $row["domain_setting_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-domain_setting-edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-domain_setting-add'];
	}

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' valign='top' width='30%' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-domain_setting-edit'];
	}
	if ($action == "add") {
		echo $text['header-domain_setting-add'];
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right' valign='top'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='domain_edit.php?id=$domain_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "update") {
		echo $text['description-domain_setting-edit'];
	}
	if ($action == "add") {
		echo $text['description-domain_setting-add'];
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('domain_setting_category_edit')) {
		echo "	<input type='text' class='formfld' name='domain_setting_category' id='domain_setting_category' maxlength='255' value=\"".$domain_setting_category."\">\n";
	}
	else {
		echo "	<select class='formfld' name='domain_setting_category' id='domain_setting_category' onchange=\"$('#domain_setting_subcategory').focus();\">\n";
		echo "		<option value=''></option>\n";
		if (is_array($allowed_categories) && sizeof($allowed_categories) > 0) {
			foreach ($allowed_categories as $category) {
				$selected = ($domain_setting_category == $category) ? 'selected' : null;
				echo "		<option value='".$category."' ".$selected.">".ucwords(str_replace('_',' ',$category))."</option>\n";
			}
		}
		echo "	</select>";
	}
	echo "<br />\n";
	echo $text['description-category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-subcategory']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld lowercase' type='text' name='domain_setting_subcategory' id='domain_setting_subcategory' maxlength='255' value=\"$domain_setting_subcategory\">\n";
	echo "<br />\n";
	echo $text['description-subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld lowercase' type='text' name='domain_setting_name' id='domain_setting_name' maxlength='255' value=\"$domain_setting_name\">\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$category = $row['domain_setting_category'];
	$subcategory = $row['domain_setting_subcategory'];
	$name = $row['domain_setting_name'];
	if ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
		echo "		<select id='domain_setting_value' name='domain_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		$sql = "";
		$sql .= "select * from v_menus ";
		$sql .= "order by menu_language, menu_name asc ";
		$sub_prep_statement = $db->prepare(check_sql($sql));
		$sub_prep_statement->execute();
		$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($sub_result as $sub_row) {
			if (strtolower($row['domain_setting_value']) == strtolower($sub_row["menu_uuid"])) {
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
		echo "		<select id='domain_setting_value' name='domain_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		//add all the themes to the list
		$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
		if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
			while (false !== ($dir_name = readdir($handle))) {
				if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && $dir_name != ".git" && is_dir($theme_dir.'/'.$dir_name)) {
					$dir_label = str_replace('_', ' ', $dir_name);
					$dir_label = str_replace('-', ' ', $dir_label);
					if ($dir_name == $row['domain_setting_value']) {
						echo "		<option value='$dir_name' selected='selected'>$dir_label</option>\n";
					}
					else {
						echo "		<option value='$dir_name'>$dir_label</option>\n";
					}
				}
			}
			closedir($handle);
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "language" && $name == "code" ) {
		echo "		<select id='domain_setting_value' name='domain_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		foreach ($_SESSION['app']['languages'] as $key => $value) {
			if ($row['default_setting_value'] == $key) {
				echo "		<option value='$value' selected='selected'>$value</option>\n";
			}
			else {
				echo "		<option value='$value'>$value</option>\n";
			}
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "time_zone" && $name == "name" ) {
		echo "		<select id='domain_setting_value' name='domain_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
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
			if ($val == $row['domain_setting_value']) {
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
		echo "	<input class='formfld' type='password' name='domain_setting_value' maxlength='255' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" value=\"".$row['domain_setting_value']."\">\n";
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
		echo "	<input class='formfld' id='domain_setting_value' name='domain_setting_value' data-colorpcker=\"{format: 'hex'}\" value=\"".$row['domain_setting_value']."\">\n";
		echo "	<script type='text/javascript'>new Colorpicker().assignTo('domain_setting_value');</script>";
	}
	elseif ($category == "fax" && $subcategory == "page_size" && $name == "text" ) {
		echo "	<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		echo "		<option value='letter' ".(($row['default_setting_value'] == 'letter') ? 'selected' : null).">Letter</option>";
		echo "		<option value='legal' ".(($row['default_setting_value'] == 'legal') ? 'selected' : null).">Legal</option>";
		echo "		<option value='a4' ".(($row['default_setting_value'] == 'a4') ? 'selected' : null).">A4</option>";
		echo "	</select>";
	}
	elseif ($category == "fax" && $subcategory == "resolution" && $name == "text" ) {
		echo "	<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		echo "		<option value='normal' ".(($row['default_setting_value'] == 'normal') ? 'selected' : null).">".$text['label-normal']."</option>";
		echo "		<option value='fine' ".(($row['default_setting_value'] == 'fine') ? 'selected' : null).">".$text['label-fine']."</option>";
		echo "		<option value='superfine' ".(($row['default_setting_value'] == 'superfine') ? 'selected' : null).">".$text['label-superfine']."</option>";
		echo "	</select>";
	}
	elseif ($category == "theme" && $subcategory == "domain_visible" && $name == "text" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		echo "    	<option value='false' ".(($row['default_setting_value'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    	<option value='true' ".(($row['default_setting_value'] == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "cache" && $name == "boolean" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		echo "    	<option value='true' ".(($row['default_setting_value'] == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($row['default_setting_value'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    </select>\n";
	}
	else {
		echo "	<input class='formfld' type='text' name='domain_setting_value' maxlength='255' value=\"".$row['domain_setting_value']."\">\n";
	}
	echo "<br />\n";
	echo $text['description-value']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<div id='tr_order' ".(($domain_setting_name != 'array') ? "style='display: none;'" : null).">\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<select name='domain_setting_order' class='formfld'>\n";
	$i=0;
	while($i<=999) {
		$selected = ($i == $domain_setting_order) ? "selected" : null;
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
	echo "    <select class='formfld' name='domain_setting_enabled'>\n";
	if ($domain_setting_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($domain_setting_enabled == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-setting_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='domain_setting_description' maxlength='255' value=\"".$domain_setting_description."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='domain_uuid' value='$domain_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='domain_setting_uuid' value='$domain_setting_uuid'>\n";
	}
	echo "			<br />";
	echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br />";
	echo "</form>";

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
	echo "	$('#domain_setting_name').keyup(function(){ \n";
	echo "		(this.value.toLowerCase() == 'array') ? $('#tr_order').slideDown('fast') : $('#tr_order').slideUp('fast');\n";
	echo "	});\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";
?>