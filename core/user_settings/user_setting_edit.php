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
 Portions created by the Initial Developer are Copyright (C) 2008-2020
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('user_setting_add') || permission_exists('user_setting_edit')) {
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
	if (!permission_exists('user_setting_category_edit')) {
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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$user_setting_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the user_uuid
	if (is_uuid($_GET["user_uuid"])) {
		$user_uuid = $_GET["user_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_REQUEST) > 0) {
		$user_setting_category = strtolower($_REQUEST["user_setting_category"]);
		$user_setting_subcategory = strtolower($_POST["user_setting_subcategory"]);
		$user_setting_name = strtolower($_POST["user_setting_name"]);
		$user_setting_value = $_POST["user_setting_value"];
		$user_setting_order = $_POST["user_setting_order"];
		$user_setting_enabled = strtolower($_POST["user_setting_enabled"]);
		$user_setting_description = $_POST["user_setting_description"];
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$user_setting_uuid = $_POST["user_setting_uuid"];
	}

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: ../users/user_edit.php?id='.$user_uuid);
			exit;
		}

	//check for all required/authorized data
		if (strlen($user_setting_category) == 0 || (is_array($allowed_categories) && sizeof($allowed_categories) > 0 && !in_array(strtolower($user_setting_category), $allowed_categories))) { $msg .= $text['message-required'].$text['label-category']."<br>\n"; }
		if (strlen($user_setting_subcategory) == 0) { $msg .= $text['message-required'].$text['label-subcategory']."<br>\n"; }
		if (strlen($user_setting_name) == 0) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
		//if (strlen($user_setting_value) == 0) { $msg .= $text['message-required'].$text['label-value']."<br>\n"; }
		if (strlen($user_setting_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
		if (strlen($user_setting_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
		//if (strlen($user_setting_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
				$user_setting_order = ($user_setting_order != '') ? $user_setting_order : 'null';

			//update switch timezone variables
				if ($user_setting_category == "domain" && $user_setting_subcategory == "time_zone" && $user_setting_name == "name" ) {
					//get the dialplan_uuid
						$sql = "select dialplan_uuid from v_dialplans ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and app_uuid = '9f356fe7-8cf8-4c14-8fe2-6daf89304458' ";
						$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$dialplan_uuid = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

					//get the action
						$sql = "select dialplan_detail_uuid from v_dialplan_details ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and dialplan_uuid = :dialplan_uuid ";
						$sql .= "and dialplan_detail_tag = 'action' ";
						$sql .= "and dialplan_detail_type = 'set' ";
						$sql .= "and dialplan_detail_data like 'timezone=%' ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['dialplan_uuid'] = $dialplan_uuid;
						$database = new database;
						$dialplan_detail_uuid = $database->select($sql, $parameters, 'column');
						if (is_uuid($dialplan_detail_uuid)) {
							$detail_action = "update";
						}
						unset($sql, $parameters);

					//update the timezone
						if ($detail_action == "update") {
							$p = new permissions;
							$p->add('dialplan_detail_edit', 'temp');

							$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
							$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$user_setting_value;
						}
						else {
							$p = new permissions;
							$p->add('dialplan_detail_add', 'temp');

							$array['dialplan_details'][0]['domain_uuid'] = $domain_uuid;
							$array['dialplan_details'][0]['dialplan_detail_uuid'] = uuid();
							$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplan_details'][0]['dialplan_detail_tag'] = 'action';
							$array['dialplan_details'][0]['dialplan_detail_type'] = 'set';
							$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$user_setting_value;
							$array['dialplan_details'][0]['dialplan_detail_inline'] = 'true';
							$array['dialplan_details'][0]['dialplan_detail_group'] = 0;
						}
						if (is_array($array) && sizeof($array) != 0) {
							$database = new database;
							$database->app_name = 'user_settings';
							$database->app_uuid = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
							$database->save($array);
							unset($array);

							$p->delete('dialplan_detail_edit', 'temp');
							$p->delete('dialplan_detail_add', 'temp');
						}
				}

			//add the user setting
				if ($action == "add" && permission_exists('user_setting_add')) {
					$array['user_settings'][0]['user_setting_uuid'] = uuid();
				}

			//update the user setting
				if ($action == "update" && permission_exists('user_setting_edit')) {
					$array['user_settings'][0]['user_setting_uuid'] = $user_setting_uuid;
				}

			//execute add or update
				if (is_array($array) && sizeof($array) != 0) {
					$array['user_settings'][0]['user_uuid'] = $user_uuid;
					$array['user_settings'][0]['domain_uuid'] = $domain_uuid;
					$array['user_settings'][0]['user_setting_category'] = $user_setting_category;
					$array['user_settings'][0]['user_setting_subcategory'] = $user_setting_subcategory;
					$array['user_settings'][0]['user_setting_name'] = $user_setting_name;
					$array['user_settings'][0]['user_setting_value'] = $user_setting_value;
					$array['user_settings'][0]['user_setting_order'] = $user_setting_order;
					$array['user_settings'][0]['user_setting_enabled'] = $user_setting_enabled;
					$array['user_settings'][0]['user_setting_description'] = $user_setting_description;

					$database = new database;
					$database->app_name = 'user_settings';
					$database->app_uuid = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
					$database->save($array);
					unset($array);
				}

			//update time zone
				if ($user_setting_category == "domain" && $user_setting_subcategory == "time_zone" && $user_setting_name == "name" && strlen($user_setting_value) > 0 ) {
					$sql = "select * from v_dialplans ";
					$sql .= "where app_uuid = '34dd307b-fffe-4ead-990c-3d070e288126' ";
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
					$database = new database;
					$result = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);

					$time_zone_found = false;
					if (is_array($result) && sizeof($result) != 0) {
						foreach ($result as &$row) {
							//get the dialplan_uuid
								$dialplan_uuid = $row["dialplan_uuid"];

							//get the dialplan details
								$sql = "select * from v_dialplan_details ";
								$sql .= "where dialplan_uuid = :dialplan_uuid ";
								$sql .= "and domain_uuid = :domain_uuid ";
								$parameters['dialplan_uuid'] = $dialplan_uuid;
								$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
								$database = new database;
								$sub_result = $database->select($sql, $parameters, 'all');
								if (is_array($sub_result) && sizeof($sub_result) != 0) {
									foreach ($sub_result as $sub_row) {
										$dialplan_detail_uuid = $sub_row["dialplan_detail_uuid"];
										$dialplan_detail_tag = $sub_row["dialplan_detail_tag"]; //action //condition
										$dialplan_detail_type = $sub_row["dialplan_detail_type"]; //set
										$dialplan_detail_data = $sub_row["dialplan_detail_data"];
										$dialplan_detail_group = $sub_row["dialplan_detail_group"];
										if ($dialplan_detail_tag == "action" && $dialplan_detail_type == "set") {
											$data_array = explode("=", $dialplan_detail_data);
											if ($data_array[0] == "timezone") {
												$time_zone_found = true;
												break;
											}
										}
									}
								}
								unset($sql, $parameters, $sub_result, $sub_row);

							//add the time zone
								if (!$time_zone_found) {
									$dialplan_detail_uuid = "eb3b3a4e-88ea-4306-b2a8-9f52d3c95f2f";
									$array['dialplan_details'][0]['domain_uuid'] = $_SESSION["domain_uuid"];
									$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
									$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
									$array['dialplan_details'][0]['dialplan_detail_tag'] = 'action';
									$array['dialplan_details'][0]['dialplan_detail_type'] = 'set';
									$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$user_setting_value;
									$array['dialplan_details'][0]['dialplan_detail_group'] = strlen($dialplan_detail_group) > 0 ? $dialplan_detail_group : 'null';
									$array['dialplan_details'][0]['dialplan_detail_order'] = '15';

									$p = new permissions;
									$p->add('dialplan_detail_add', 'temp');

									$database = new database;
									$database->app_name = 'user_settings';
									$database->app_uuid = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
									$database->save($array);
									unset($array);

									$p->delete('dialplan_detail_add', 'temp');
								}

							//update the time zone
								if ($time_zone_found) {
									$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
									$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$user_setting_value;
									$array['dialplan_details'][0]['domain_uuid'] = $_SESSION["domain_uuid"];
									$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;

									$p = new permissions;
									$p->add('dialplan_detail_edit', 'temp');

									$database = new database;
									$database->app_name = 'user_settings';
									$database->app_uuid = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
									$database->save($array);
									unset($array);

									$p->delete('dialplan_detail_edit', 'temp');
								}
						}
					}
				}

			//redirect the browser
				if ($action == "update") {
					message::add($text['message-update']);
				}
				if ($action == "add") {
					message::add($text['message-add']);
				}
				header("Location: /core/users/user_edit.php?id=".$user_uuid);
				return;
		}
}

//pre-populate the form
	if (is_uuid($_GET["id"]) && count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$user_setting_uuid = $_GET["id"];
		$sql = "select user_setting_category, user_setting_subcategory, user_setting_name, user_setting_value, user_setting_order, cast(user_setting_enabled as text), user_setting_description ";
		$sql .= "from v_user_settings ";
		$sql .= "where user_setting_uuid = :user_setting_uuid ";
		$sql .= "and user_uuid = :user_uuid ";
		$parameters['user_setting_uuid'] = $user_setting_uuid;
		$parameters['user_uuid'] = $user_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$user_setting_category = $row["user_setting_category"];
			$user_setting_subcategory = $row["user_setting_subcategory"];
			$user_setting_name = $row["user_setting_name"];
			$user_setting_value = $row["user_setting_value"];
			$user_setting_order = $row["user_setting_order"];
			$user_setting_enabled = $row["user_setting_enabled"];
			$user_setting_description = $row["user_setting_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-user_setting-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-user_setting-add'];
	}

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-user_setting-edit']."</b>";
	}
	if ($action == "add") {
		echo "<b>".$text['header-user_setting-add']."</b>";
	}
	echo	"</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'style'=>'margin-right: 15px;','link'=>'/core/users/user_edit.php?id='.urlencode($user_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update") {
		echo $text['description-user_setting-edit']."\n";
	}
	if ($action == "add") {
		echo $text['description-user_setting-add']."\n";
	}
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-category']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if (permission_exists('user_setting_category_edit')) {
		echo "	<input type='text' class='formfld' name='user_setting_category' id='user_setting_category' maxlength='255' value=\"".escape($user_setting_category)."\">\n";
	}
	else {
		echo "	<select class='formfld' name='user_setting_category' id='user_setting_category' onchange=\"$('#user_setting_subcategory').trigger('focus');\">\n";
		echo "		<option value=''></option>\n";
		if (is_array($allowed_categories) && sizeof($allowed_categories) > 0) {
			foreach ($allowed_categories as $category) {
				$selected = ($user_setting_category == $category) ? 'selected' : null;
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
	echo "	<input class='formfld lowercase' type='text' name='user_setting_subcategory' id='user_setting_subcategory' maxlength='255' value=\"".escape($user_setting_subcategory)."\">\n";
	echo "<br />\n";
	echo $text['description-subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld lowercase' type='text' name='user_setting_name' id='user_setting_name' maxlength='255' value=\"".escape($user_setting_name)."\">\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($user_setting_category == "domain" && $user_setting_subcategory == "menu" && $user_setting_name == "uuid" ) {
		echo "		<select class='formfld' id='user_setting_value' name='user_setting_value' style=''>\n";
		echo "		<option value=''></option>\n";
		$sql = "select * from v_menus ";
		$sql .= "order by menu_language, menu_name asc ";
		$database = new database;
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			foreach ($result as $row) {
				if (strtolower($user_setting_value) == strtolower($row["menu_uuid"])) {
					echo "		<option value='".strtolower($row["menu_uuid"])."' selected='selected'>".escape($row["menu_language"])." - ".escape($row["menu_name"])."\n";
				}
				else {
					echo "		<option value='".strtolower($row["menu_uuid"])."'>".escape($row["menu_language"])." - ".escape($row["menu_name"])."</option>\n";
				}
			}
		}
		unset($sql, $result, $row);
		echo "		</select>\n";
	}
	else if ($user_setting_category == "domain" && $user_setting_subcategory == "template" && $user_setting_name == "name" ) {
		echo "		<select class='formfld' id='user_setting_value' name='user_setting_value' style=''>\n";
		echo "		<option value=''></option>\n";
		//add all the themes to the list
		$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
		if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
			while (false !== ($dir_name = readdir($handle))) {
				if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && $dir_name != ".git" && is_dir($theme_dir.'/'.$dir_name)) {
					$dir_label = str_replace('_', ' ', $dir_name);
					$dir_label = str_replace('-', ' ', $dir_label);
					if ($dir_name == $user_setting_value) {
						echo "		<option value='".escape($dir_name)."' selected='selected'>".ucwords($dir_label)."</option>\n";
					}
					else {
						echo "		<option value='".escape($dir_name)."'>".ucwords($dir_label)."</option>\n";
					}
				}
			}
			closedir($handle);
		}
		echo "		</select>\n";
	}
	else if ($user_setting_category == "domain" && $user_setting_subcategory == "language" && $user_setting_name == "code" ) {
		echo "		<select class='formfld' id='user_setting_value' name='user_setting_value' style=''>\n";
		echo "		<option value=''></option>\n";
		foreach ($_SESSION['app']['languages'] as $key => $value) {
			if ($user_setting_value == $key) {
				echo "		<option value='$value' selected='selected'>$value</option>\n";
			}
			else {
				echo "		<option value='$value'>$value</option>\n";
			}
		}
		echo "		</select>\n";
	}
	else if ($user_setting_category == "domain" && $user_setting_subcategory == "time_zone" && $user_setting_name == "name" ) {
		echo "		<select class='formfld' id='user_setting_value' name='user_setting_value' style=''>\n";
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
			if ($val == $user_setting_value) {
				echo "			<option value='".$val."' selected='selected'>(UTC ".$time_zone_offset_hours.":".$time_zone_offset_minutes.") ".$val."</option>\n";
			}
			else {
				echo "			<option value='".$val."'>(UTC ".escape($time_zone_offset_hours).":".escape($time_zone_offset_minutes).") ".$val."</option>\n";
			}
			$previous_category = $category;
			$x++;
		}
		echo "		</select>\n";
	}
	else if ($user_setting_category == "domain" && $user_setting_subcategory == "time_format" && $user_setting_name == "text" ) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='24h' ".(($user_setting_value == "24h") ? "selected='selected'" : null).">".$text['label-24-hour']."</option>\n";
		echo "    	<option value='12h' ".(($user_setting_value == "12h") ? "selected='selected'" : null).">".$text['label-12-hour']."</option>\n";
		echo "	</select>\n";
	}
	else if ($user_setting_subcategory == 'password' || substr_count($user_setting_subcategory, '_password') > 0 || $user_setting_category == "login" && $user_setting_subcategory == "password_reset_key" && $user_setting_name == "text") {
		echo "	<input class='formfld' type='password' id='user_setting_value' name='user_setting_value' maxlength='255' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" value=\"".escape($user_setting_value)."\">\n";
	}
	else if ($user_setting_category == "theme" && substr_count($user_setting_subcategory, "_color") > 0 && ($user_setting_name == "text" || $user_setting_name == 'array')) {
		echo "	<input type='text' class='formfld colorpicker' id='user_setting_value' name='user_setting_value' value=\"".$user_setting_value."\">\n";
	}
	else if ($user_setting_category == "theme" && substr_count($user_setting_subcategory, "_font") > 0 && $user_setting_name == "text") {
		$user_setting_value = str_replace('"', "'", $user_setting_value);
		if ($fonts = get_available_fonts('alpha')) {
			echo "	<select class='formfld' id='sel_user_setting_value' onchange=\"if (this.selectedIndex == $('select#sel_user_setting_value option').length - 1) { $('#txt_user_setting_value').val('').fadeIn('fast'); $('#txt_user_setting_value').trigger('focus'); } else { $('#txt_user_setting_value').fadeOut('fast', function(){ $('#txt_user_setting_value').val($('#sel_user_setting_value').val()) }); } \">\n";
			echo "		<option value=''></option>\n";
			echo "		<optgroup label='".$text['label-web_fonts']."'>\n";
			$option_found = false;
			foreach ($fonts as $n => $font) {
				if ($user_setting_value == $font) {
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
			echo "		<option value='' ".(($user_setting_value != '' && $option_found == false) ? 'selected' : null).">".$text['label-other']."...</option>\n";
			echo "	</select>";
			echo "	<input type='text' class='formfld' ".(($user_setting_value == '' || $option_found) ? "style='display: none;'" : null)." id='txt_user_setting_value' name='user_setting_value' value=\"".escape($user_setting_value)."\">\n";
		}
		else {
			echo "	<input type='text' class='formfld' id='user_setting_value' name='user_setting_value' value=\"".escape($user_setting_value)."\">\n";
		}
	}
	else if ($user_setting_category == "fax" && $user_setting_subcategory == "page_size" && $user_setting_name == "text" ) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value' style=''>\n";
		echo "		<option value='letter' ".(($user_setting_value == 'letter') ? 'selected' : null).">Letter</option>";
		echo "		<option value='legal' ".(($user_setting_value == 'legal') ? 'selected' : null).">Legal</option>";
		echo "		<option value='a4' ".(($user_setting_value == 'a4') ? 'selected' : null).">A4</option>";
		echo "	</select>";
	}
	else if ($user_setting_category == "fax" && $user_setting_subcategory == "resolution" && $user_setting_name == "text" ) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value' style=''>\n";
		echo "		<option value='normal' ".(($user_setting_value == 'normal') ? 'selected' : null).">".$text['label-normal']."</option>";
		echo "		<option value='fine' ".(($user_setting_value == 'fine') ? 'selected' : null).">".$text['label-fine']."</option>";
		echo "		<option value='superfine' ".(($user_setting_value == 'superfine') ? 'selected' : null).">".$text['label-superfine']."</option>";
		echo "	</select>";
	}
	else if ($user_setting_category == "theme" && $user_setting_subcategory == "domain_visible" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='false' ".(($user_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    	<option value='true' ".(($user_setting_value == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    </select>\n";
	}
	else if ($user_setting_category == "theme" && $user_setting_subcategory == "cache" && $user_setting_name == "boolean" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='true' ".(($user_setting_value == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($user_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    </select>\n";
	}
	else if (
		($user_setting_category == "theme" && $user_setting_subcategory == "menu_main_icons" && $user_setting_name == "boolean") ||
		($user_setting_category == "theme" && $user_setting_subcategory == "menu_sub_icons" && $user_setting_name == "boolean")
		) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='true' ".(($user_setting_value == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($user_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
	}
	else if ($user_setting_category == "theme" && $user_setting_subcategory == "menu_brand_type" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='image' ".(($user_setting_value == "image") ? "selected='selected'" : null).">".$text['label-image']."</option>\n";
		echo "    	<option value='text' ".(($user_setting_value == "text") ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		echo "    	<option value='image_text' ".(($user_setting_value == "image_text") ? "selected='selected'" : null).">".$text['label-image_text']."</option>\n";
		echo "    	<option value='none' ".(($user_setting_value == "none") ? "selected='selected'" : null).">".$text['label-none']."</option>\n";
		echo "    </select>\n";
	}
	else if ($user_setting_category == "theme" && $user_setting_subcategory == "menu_style" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='fixed' ".(($user_setting_value == "fixed") ? "selected='selected'" : null).">".$text['label-fixed']."</option>\n";
		echo "    	<option value='static' ".(($user_setting_value == "static") ? "selected='selected'" : null).">".$text['label-static']."</option>\n";
		echo "    	<option value='inline' ".(($user_setting_value == "inline") ? "selected='selected'" : null).">".$text['label-inline']."</option>\n";
		echo "    </select>\n";
	}
	else if ($user_setting_category == "theme" && $user_setting_subcategory == "menu_position" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='top' ".(($user_setting_value == "top") ? "selected='selected'" : null).">".$text['label-top']."</option>\n";
		echo "    	<option value='bottom' ".(($user_setting_value == "bottom") ? "selected='selected'" : null).">".$text['label-bottom']."</option>\n";
		echo "    </select>\n";
	}
	else if ($user_setting_category == "theme" && $user_setting_subcategory == "logo_align" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='left' ".(($user_setting_value == "left") ? "selected='selected'" : null).">".$text['label-left']."</option>\n";
		echo "    	<option value='center' ".(($user_setting_value == "center") ? "selected='selected'" : null).">".$text['label-center']."</option>\n";
		echo "    	<option value='right' ".(($user_setting_value == "right") ? "selected='selected'" : null).">".$text['label-right']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($user_setting_category == "theme" && $user_setting_subcategory == "menu_side_state" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='expanded'>".$text['option-expanded']."</option>\n";
		echo "    	<option value='contracted' ".($user_setting_value == "contracted" ? "selected='selected'" : null).">".$text['option-contracted']."</option>\n";
		echo "    	<option value='hidden' ".($user_setting_value == "hidden" ? "selected='selected'" : null).">".$text['option-hidden']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($user_setting_category == "theme" && $user_setting_subcategory == "menu_side_toggle" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='hover'>".$text['option-hover']."</option>\n";
		echo "    	<option value='click' ".($user_setting_value == "click" ? "selected='selected'" : null).">".$text['option-click']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($user_setting_category == "theme" && $user_setting_subcategory == "menu_side_toggle_body_width" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='shrink'>".$text['option-shrink']."</option>\n";
		echo "    	<option value='fixed' ".($user_setting_value == "fixed" ? "selected='selected'" : null).">".$text['option-fixed']."</option>\n";
		echo "    </select>\n";
	}
	else if ($user_setting_category == "theme" && $user_setting_subcategory == "body_header_brand_type" && $user_setting_name == "text" ) {
		echo "    <select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='image' ".(($user_setting_value == "image") ? "selected='selected'" : null).">".$text['label-image']."</option>\n";
		echo "    	<option value='text' ".(($user_setting_value == "text") ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		echo "    	<option value='image_text' ".(($user_setting_value == "image_text") ? "selected='selected'" : null).">".$text['label-image_text']."</option>\n";
		echo "    	<option value='none' ".(($user_setting_value == "none") ? "selected='selected'" : null).">".$text['label-none']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($user_setting_category == "users" && $user_setting_subcategory == "username_format" && $user_setting_name == "text" ) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='any' ".($user_setting_value == 'any' ? "selected='selected'" : null).">".$text['option-username_format_any']."</option>\n";
		echo "    	<option value='email' ".($user_setting_value == 'email' ? "selected='selected'" : null).">".$text['option-username_format_email']."</option>\n";
		echo "    	<option value='no_email' ".($user_setting_value == 'no_email' ? "selected='selected'" : null).">".$text['option-username_format_no_email']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($user_setting_category == "destinations" && $user_setting_subcategory == "dialplan_details" && $user_setting_name == "boolean" ) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='true'>".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($user_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($user_setting_category == "destinations" && $user_setting_subcategory == "dialplan_mode" && $user_setting_name == "text" ) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='multiple'>".$text['label-multiple']."</option>\n";
		echo "    	<option value='single' ".(($user_setting_value == "single") ? "selected='selected'" : null).">".$text['label-single']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($user_setting_category == "destinations" && $user_setting_subcategory == "select_mode" && $user_setting_name == "text" ) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='default'>".$text['label-default']."</option>\n";
		echo "    	<option value='dynamic' ".(($user_setting_value == "dynamic") ? "selected='selected'" : null).">".$text['label-dynamic']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($user_setting_category == "destinations" && $user_setting_subcategory == "unique" && $user_setting_name == "boolean" ) {
		echo "	<select class='formfld' id='user_setting_value' name='user_setting_value'>\n";
		echo "    	<option value='true'>".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($user_setting_value == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
	}
	else {
		echo "	<input class='formfld' type='text' id='user_setting_value' name='user_setting_value' maxlength='255' value=\"".escape($user_setting_value)."\">\n";
	}
	echo "<br />\n";
	echo $text['description-value']."\n";
	if ($user_setting_category == "theme" && substr_count($user_setting_subcategory, "_font") > 0 && $user_setting_name == "text") {
		echo "&nbsp;&nbsp;".$text['label-reference'].": <a href='https://www.google.com/fonts' target='_blank'>".$text['label-web_fonts']."</a>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<div id='tr_order' ".(($user_setting_name != 'array') ? "style='display: none;'" : null).">\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<select name='user_setting_order' class='formfld'>\n";
	$i=0;
	while($i<=999) {
		$selected = ($i == $user_setting_order) ? "selected" : null;
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
	echo "    <select class='formfld' name='user_setting_enabled'>\n";
	if ($user_setting_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($user_setting_enabled == "false") {
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
	echo "	<textarea class='formfld' style='width: 185px; height: 80px;' name='user_setting_description'>".escape($user_setting_description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='user_uuid' value='".escape($user_uuid)."'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='user_setting_uuid' value='".escape($user_setting_uuid)."'>\n";
	}
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br />";
	echo "</form>";

	echo "<script>\n";
//hide/convert password fields then submit form
	echo "	function submit_form() {\n";
	echo "		hide_password_fields();\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
//define lowercase class
	echo "	$('.lowercase').on('blur',function(){ this.value = this.value.toLowerCase(); });";
//show order if array
	echo "	$('#user_setting_name').on('keyup',function(){ \n";
	echo "		(this.value.toLowerCase() == 'array') ? $('#tr_order').slideDown('fast') : $('#tr_order').slideUp('fast');\n";
	echo "	});\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
