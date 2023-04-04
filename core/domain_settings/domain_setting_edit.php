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
 Portions created by the Initial Developer are Copyright (C) 2008-2021
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$domain_setting_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the domain_uuid
	if (is_uuid($_GET["domain_uuid"])) {
		$domain_uuid = $_GET["domain_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$domain_setting_category = strtolower($_POST["domain_setting_category"]);
		$domain_setting_subcategory = strtolower($_POST["domain_setting_subcategory"]);
		$domain_setting_name = strtolower($_POST["domain_setting_name"]);
		$domain_setting_value = $_POST["domain_setting_value"];
		$domain_setting_order = $_POST["domain_setting_order"];
		$domain_setting_enabled = strtolower($_POST["domain_setting_enabled"] ?: 'false');
		$domain_setting_description = $_POST["domain_setting_description"];
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$domain_setting_uuid = $_POST["domain_setting_uuid"];
	}

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: ../domains/domain_edit.php?id='.$domain_uuid);
			exit;
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
				$domain_setting_order = $domain_setting_order != '' ? $domain_setting_order : 'null';

			//update switch timezone variables
				if ($domain_setting_category == "domain" && $domain_setting_subcategory == "time_zone" && $domain_setting_name == "name" ) {
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
						$detail_action = is_uuid($dialplan_detail_uuid) ? 'update' : 'add';
						unset($sql, $parameters);

					//update the timezone
						$p = new permissions;
						if ($detail_action == "update") {
							$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
							$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$domain_setting_value;
							$p->add('dialplan_detail_edit', 'temp');
						}
						else {
							$array['dialplan_details'][0]['dialplan_detail_uuid'] = uuid();
							$array['dialplan_details'][0]['domain_uuid'] = $domain_uuid;
							$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplan_details'][0]['dialplan_detail_tag'] = 'action';
							$array['dialplan_details'][0]['dialplan_detail_type'] = 'set';
							$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$domain_setting_value;
							$array['dialplan_details'][0]['dialplan_detail_inline'] = 'true';
							$array['dialplan_details'][0]['dialplan_detail_group'] = '0';
							$array['dialplan_details'][0]['dialplan_detail_order'] = '20';
							$p->add('dialplan_detail_add', 'temp');
						}
						if (is_array($array) && sizeof($array) != 0) {
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->save($array);
							unset($array);

							$p->delete('dialplan_detail_edit', 'temp');
							$p->delete('dialplan_detail_add', 'temp');
						}

					//get the dialplan uuid
						$sql = "select domain_name from v_domains ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$domain_name = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

					//update the dialplan xml
						$dialplans = new dialplan;
						$dialplans->source = "details";
						$dialplans->destination = "database";
						$dialplans->uuid = $dialplan_uuid;
						$dialplans->xml();

					//clear the cache
						$cache = new cache;
						$cache->delete("dialplan:".$domain_name);
				}

			//add
				if ($action == "add" && permission_exists('domain_setting_add')) {
					$array['domain_settings'][0]['domain_setting_uuid'] = uuid();
				}

			//update
				if ($action == "update" && permission_exists('domain_setting_edit')) {
					$array['domain_settings'][0]['domain_setting_uuid'] = $domain_setting_uuid;
				}
			//execute
				if (is_uuid($array['domain_settings'][0]['domain_setting_uuid'])) {
					$array['domain_settings'][0]['domain_uuid'] = $domain_uuid;
					$array['domain_settings'][0]['domain_setting_category'] = $domain_setting_category;
					$array['domain_settings'][0]['domain_setting_subcategory'] = $domain_setting_subcategory;
					$array['domain_settings'][0]['domain_setting_name'] = $domain_setting_name;
					$array['domain_settings'][0]['domain_setting_value'] = $domain_setting_value;
					$array['domain_settings'][0]['domain_setting_order'] = $domain_setting_order;
					$array['domain_settings'][0]['domain_setting_enabled'] = $domain_setting_enabled;
					$array['domain_settings'][0]['domain_setting_description'] = $domain_setting_description;
					$database = new database;
					$database->app_name = 'domain_settings';
					$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
					$database->save($array);
					unset($array);
				}

			//update time zone
				if ($domain_setting_category == "domain" && $domain_setting_subcategory == "time_zone" && $domain_setting_name == "name" && strlen($domain_setting_value) > 0 ) {
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
								}
								unset($sql, $parameters, $sub_result, $field);

							//add the time zone
								if (!$time_zone_found) {
									$dialplan_detail_uuid = "eb3b3a4e-88ea-4306-b2a8-9f52d3c95f2f";
									$array['dialplan_details'][0]['domain_uuid'] = $_SESSION["domain_uuid"]; //8cfd9525-6ccf-4c2c-813a-bca5809067cd
									$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid; //807b4aa6-4478-4663-a661-779397c1d542
									$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
									$array['dialplan_details'][0]['dialplan_detail_tag'] = 'action';
									$array['dialplan_details'][0]['dialplan_detail_type'] = 'set';
									$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$domain_setting_value;
									$array['dialplan_details'][0]['dialplan_detail_group'] = $dialplan_detail_group;
									$array['dialplan_details'][0]['dialplan_detail_order'] = '15';

									$p = new permissions;
									$p->add('dialplan_detail_add', 'temp');
								}

							//update the time zone
								if ($time_zone_found) {
									$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
									$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$domain_setting_value;

									$p = new permissions;
									$p->add('dialplan_detail_edit', 'temp');
								}

							//execute
								if (is_array($array) && sizeof($array) != 0) {
									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('dialplan_detail_add', 'temp');
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
				header("Location: ".PROJECT_PATH."/core/domain_settings/domain_settings.php?id=".$domain_uuid);
				exit;
		}
}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true" && is_uuid($_GET["id"])) {
		$domain_setting_uuid = $_GET["id"];
		$sql = "select domain_setting_uuid, domain_setting_category, domain_setting_subcategory, domain_setting_name, domain_setting_value, domain_setting_order, cast(domain_setting_enabled as text), domain_setting_description ";
		$sql .= "from v_domain_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and domain_setting_uuid = :domain_setting_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['domain_setting_uuid'] = $domain_setting_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$domain_setting_category = $row["domain_setting_category"];
			$domain_setting_subcategory = $row["domain_setting_subcategory"];
			$domain_setting_name = $row["domain_setting_name"];
			$domain_setting_value = $row["domain_setting_value"];
			$domain_setting_order = $row["domain_setting_order"];
			$domain_setting_enabled = $row["domain_setting_enabled"];
			$domain_setting_description = $row["domain_setting_description"];
		}
		unset($sql, $parameters);
	}

//set the defaults
	if (strlen($domain_setting_enabled) == 0) { $domain_setting_enabled = 'true'; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	if ($action == "update") {
		$document['title'] = $text['title-domain_setting-edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-domain_setting-add'];
	}
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-domain_setting-edit']."</b>";
	}
	if ($action == "add") {
		echo "<b>".$text['header-domain_setting-add']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>PROJECT_PATH.'/core/domains/domain_edit.php?id='.urlencode($domain_uuid)]);
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','onclick'=>'submit_form();']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update") {
		echo $text['description-domain_setting-edit']."\n";
	}
	if ($action == "add") {
		echo $text['description-domain_setting-add']."\n";
	}
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-category']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if (permission_exists('domain_setting_category_edit')) {
		if ($action == 'add') {
			$domain_setting_category = $_GET['domain_setting_category'];
		}
		echo "	<input type='text' class='formfld' name='domain_setting_category' id='domain_setting_category' maxlength='255' value=\"".escape($domain_setting_category)."\">\n";
	}
	else {
		echo "	<select class='formfld' name='domain_setting_category' id='domain_setting_category' onchange=\"$('#domain_setting_subcategory').trigger('focus');\">\n";
		echo "		<option value=''></option>\n";
		if (is_array($allowed_categories) && sizeof($allowed_categories) > 0) {
			foreach ($allowed_categories as $category) {
				$selected = ($domain_setting_category == $category) ? 'selected' : null;
				echo "		<option value='".escape($category)."' ".$selected.">".ucwords(str_replace('_',' ',escape($category)))."</option>\n";
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
	echo "	<input class='formfld lowercase' type='text' name='domain_setting_subcategory' id='domain_setting_subcategory' maxlength='255' value=\"".escape($domain_setting_subcategory)."\">\n";
	echo "<br />\n";
	echo $text['description-subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$setting_types = ['Array','Boolean','Code','Dir','Name','Numeric','Text','UUID'];
	echo "	<select class='formfld' id='domain_setting_name' name='domain_setting_name' required='required'>\n";
	echo "		<option value=''></option>\n";
	foreach ($setting_types as $setting_type) {
		echo "	<option value='".strtolower($setting_type)."' ".($domain_setting_name == strtolower($setting_type) ? "selected='selected'" : null).">".$setting_type."</option>\n";
	}
	echo "	</select>\n";
	unset($setting_types, $setting_type);
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
		echo "		<select class='formfld' id='domain_setting_value' name='domain_setting_value' style=''>\n";
		echo "		<option value=''></option>\n";
		$sql = "select * from v_menus ";
		$sql .= "order by menu_language, menu_name asc ";
		$database = new database;
		$sub_result = $database->select($sql, null, 'all');
		if (is_array($sub_result) && sizeof($sub_result) != 0) {
			foreach ($sub_result as $sub_row) {
				$selected = strtolower($row['domain_setting_value']) == strtolower($sub_row["menu_uuid"]) ? "selected='selected'" : null;
				echo "		<option value='".strtolower(escape($sub_row["menu_uuid"]))."' ".$selected.">".escape($sub_row["menu_language"])." - ".escape($sub_row["menu_name"])."</option>\n";
			}
		}
		unset($sql, $sub_result, $sub_row, $selected);
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "template" && $name == "name" ) {
		echo "		<select class='formfld' id='domain_setting_value' name='domain_setting_value' style=''>\n";
		echo "		<option value=''></option>\n";
		//add all the themes to the list
		$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
		if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
			while (false !== ($dir_name = readdir($handle))) {
				if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && $dir_name != ".git" && is_dir($theme_dir.'/'.$dir_name)) {
					$dir_label = str_replace('_', ' ', $dir_name);
					$dir_label = str_replace('-', ' ', $dir_label);
					if ($dir_name == $row['domain_setting_value']) {
						echo "		<option value='".escape($dir_name)."' selected='selected'>".escape($dir_label)."</option>\n";
					}
					else {
						echo "		<option value='".escape($dir_name)."'>".escape($dir_label)."</option>\n";
					}
				}
			}
			closedir($handle);
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "language" && $name == "code" ) {
		echo "		<select class='formfld' id='domain_setting_value' name='domain_setting_value' style=''>\n";
		echo "		<option value=''></option>\n";
		foreach ($_SESSION['app']['languages'] as $key => $value) {
			if ($row['domain_setting_value'] == $value) {
				echo "		<option value='".escape($value)."' selected='selected'>".escape($value)."</option>\n";
			}
			else {
				echo "		<option value='".escape($value)."'>".escape($value)."</option>\n";
			}
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "time_zone" && $name == "name" ) {
		echo "		<select class='formfld' id='domain_setting_value' name='domain_setting_value' style=''>\n";
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
				echo "		<optgroup label='".escape($category)."'>\n";
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
				echo "			<option value='".escape($val)."' selected='selected'>(UTC ".escape($time_zone_offset_hours).":".escape($time_zone_offset_minutes).") ".$val."</option>\n";
			}
			else {
				echo "			<option value='".escape($val)."'>(UTC ".escape($time_zone_offset_hours).":".escape($time_zone_offset_minutes).") ".escape($val)."</option>\n";
			}
			$previous_category = $category;
			$x++;
		}
		echo "		</select>\n";
	}
	elseif ($category == "domain" && $subcategory == "time_format" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='24h' ".(($domain_setting_value == "24h") ? "selected='selected'" : null).">".$text['label-24-hour']."</option>\n";
		echo "    	<option value='12h' ".(($domain_setting_value == "12h") ? "selected='selected'" : null).">".$text['label-12-hour']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($subcategory == 'password' || substr_count($subcategory, '_password') > 0 || $category == "login" && $subcategory == "password_reset_key" && $name == "text") {
		echo "	<input class='formfld' type='password' id='domain_setting_value' name='domain_setting_value' maxlength='255' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" value=\"".escape($row['domain_setting_value'])."\">\n";
	}
	elseif ($category == "theme" && substr_count($subcategory, "_color") > 0 && ($name == "text" || $name == 'array')) {
		echo "	<input type='text' class='formfld colorpicker' id='domain_setting_value' name='domain_setting_value' value=\"".escape($row['domain_setting_value'])."\">\n";
	}
	elseif ($category == "theme" && substr_count($subcategory, "_font") > 0 && $name == "text") {
		$row['domain_setting_value'] = str_replace('"', "'", $row['domain_setting_value']);
		if ($fonts = get_available_fonts('alpha')) {
			echo "	<select class='formfld' id='sel_domain_setting_value' onchange=\"if (this.selectedIndex == $('select#sel_domain_setting_value option').length - 1) { $('#txt_domain_setting_value').val('').fadeIn('fast'); $('#txt_domain_setting_value').trigger('focus'); } else { $('#txt_domain_setting_value').fadeOut('fast', function(){ $('#txt_domain_setting_value').val($('#sel_domain_setting_value').val()) }); } \">\n";
			echo "		<option value=''></option>\n";
			echo "		<optgroup label='".$text['label-web_fonts']."'>\n";
			$option_found = false;
			foreach ($fonts as $n => $font) {
				if ($row['domain_setting_value'] == $font) {
					$selected = 'selected';
					$option_found = true;
				}
				else {
					unset($selected);
				}
				echo "		<option value='".escape($font)."' ".$selected.">".escape($font)."</option>\n";
			}
			echo "		</optgroup>\n";
			echo "		<option value='' disabled='disabled'></option>\n";
			echo "		<option value='' ".(($row['domain_setting_value'] != '' && $option_found == false) ? 'selected' : null).">".$text['label-other']."...</option>\n";
			echo "	</select>";
			echo "	<input type='text' class='formfld' ".(($row['domain_setting_value'] == '' || $option_found) ? "style='display: none;'" : null)." id='txt_domain_setting_value' name='domain_setting_value' value=\"".escape($row['domain_setting_value'])."\">\n";
		}
		else {
			echo "	<input type='text' class='formfld' id='domain_setting_value' name='domain_setting_value' value=\"".escape($row['domain_setting_value'])."\">\n";
		}
	}
	elseif ($category == "fax" && $subcategory == "page_size" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value' style=''>\n";
		echo "		<option value='letter' ".(($row['domain_setting_value'] == 'letter') ? 'selected' : null).">Letter</option>";
		echo "		<option value='legal' ".(($row['domain_setting_value'] == 'legal') ? 'selected' : null).">Legal</option>";
		echo "		<option value='a4' ".(($row['domain_setting_value'] == 'a4') ? 'selected' : null).">A4</option>";
		echo "	</select>";
	}
	elseif ($category == "fax" && $subcategory == "resolution" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value' style=''>\n";
		echo "		<option value='normal' ".(($row['domain_setting_value'] == 'normal') ? 'selected' : null).">".$text['label-normal']."</option>";
		echo "		<option value='fine' ".(($row['domain_setting_value'] == 'fine') ? 'selected' : null).">".$text['label-fine']."</option>";
		echo "		<option value='superfine' ".(($row['domain_setting_value'] == 'superfine') ? 'selected' : null).">".$text['label-superfine']."</option>";
		echo "	</select>";
	}
	elseif ($category == "provision" && $subcategory == "aastra_time_format" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "		<option value='1' ".(($domain_setting_value == "1") ? "selected='selected'" : null).">".$text['label-24-hour']."</option>\n";
		echo "		<option value='0' ".(($domain_setting_value == "0") ? "selected='selected'" : null).">".$text['label-12-hour']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "provision" && $subcategory == "aastra_date_format" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "		<option value='0' ".(($domain_setting_value == "0") ? "selected='selected'" : null).">WWW MMM DD</option>\n";
		echo "		<option value='1' ".(($domain_setting_value == "1") ? "selected='selected'" : null).">DD-MMM-YY</option>\n";
		echo "		<option value='2' ".(($domain_setting_value == "2") ? "selected='selected'" : null).">YYYY-MM-DD</option>\n";
		echo "		<option value='3' ".(($domain_setting_value == "3") ? "selected='selected'" : null).">DD/MM/YYYY</option>\n";
		echo "		<option value='4' ".(($domain_setting_value == "4") ? "selected='selected'" : null).">DD/MM/YY</option>\n";
		echo "		<option value='5' ".(($domain_setting_value == "5") ? "selected='selected'" : null).">DD-MM-YY</option>\n";
		echo "		<option value='6' ".(($domain_setting_value == "6") ? "selected='selected'" : null).">MM/DD/YY</option>\n";
		echo "		<option value='7' ".(($domain_setting_value == "7") ? "selected='selected'" : null).">MMM DD</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "theme" && $subcategory == "domain_visible" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='false' ".(($row['domain_setting_value'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    	<option value='true' ".(($row['domain_setting_value'] == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_brand_type" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='image' ".(($row['domain_setting_value'] == "image") ? "selected='selected'" : null).">".$text['label-image']."</option>\n";
		echo "    	<option value='text' ".(($row['domain_setting_value'] == "text") ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		echo "    	<option value='image_text' ".(($row['domain_setting_value'] == "image_text") ? "selected='selected'" : null).">".$text['label-image_text']."</option>\n";
		echo "    	<option value='none' ".(($row['domain_setting_value'] == "none") ? "selected='selected'" : null).">".$text['label-none']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_style" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='fixed' ".(($row['domain_setting_value'] == "fixed") ? "selected='selected'" : null).">".$text['label-fixed']."</option>\n";
		echo "    	<option value='static' ".(($row['domain_setting_value'] == "static") ? "selected='selected'" : null).">".$text['label-static']."</option>\n";
		echo "    	<option value='inline' ".(($row['domain_setting_value'] == "inline") ? "selected='selected'" : null).">".$text['label-inline']."</option>\n";
		echo "    	<option value='side' ".(($row['domain_setting_value'] == "side") ? "selected='selected'" : null).">".$text['label-side']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_position" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='top' ".(($row['domain_setting_value'] == "top") ? "selected='selected'" : null).">".$text['label-top']."</option>\n";
		echo "    	<option value='bottom' ".(($row['domain_setting_value'] == "bottom") ? "selected='selected'" : null).">".$text['label-bottom']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "logo_align" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='left' ".(($row['domain_setting_value'] == "left") ? "selected='selected'" : null).">".$text['label-left']."</option>\n";
		echo "    	<option value='center' ".(($row['domain_setting_value'] == "center") ? "selected='selected'" : null).">".$text['label-center']."</option>\n";
		echo "    	<option value='right' ".(($row['domain_setting_value'] == "right") ? "selected='selected'" : null).">".$text['label-right']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "custom_css_code" && $name == "text" ) {
		echo "	<textarea class='formfld' style='min-width: 100%; height: 300px; font-family: courier, monospace; overflow: auto; resize: vertical' id='domain_setting_value' name='domain_setting_value' wrap='off'>".$row['domain_setting_value']."</textarea>\n";
	}
	elseif ($category == "theme" && $subcategory == "button_icons" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='auto'>".$text['option-button_icons_auto']."</option>\n";
		echo "    	<option value='only' ".($row['domain_setting_value'] == "only" ? "selected='selected'" : null).">".$text['option-button_icons_only']."</option>\n";
		echo "    	<option value='always' ".($row['domain_setting_value'] == "always" ? "selected='selected'" : null).">".$text['option-button_icons_always']."</option>\n";
		echo "    	<option value='never' ".($row['domain_setting_value'] == "never" ? "selected='selected'" : null).">".$text['option-button_icons_never']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_side_state" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='expanded'>".$text['option-expanded']."</option>\n";
		echo "    	<option value='contracted' ".($row['domain_setting_value'] == "contracted" ? "selected='selected'" : null).">".$text['option-contracted']."</option>\n";
		echo "    	<option value='hidden' ".($row['domain_setting_value'] == "hidden" ? "selected='selected'" : null).">".$text['option-hidden']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_side_toggle" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='hover'>".$text['option-hover']."</option>\n";
		echo "    	<option value='click' ".($row['domain_setting_value'] == "click" ? "selected='selected'" : null).">".$text['option-click']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_side_toggle_body_width" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='shrink'>".$text['option-shrink']."</option>\n";
		echo "    	<option value='fixed' ".($row['domain_setting_value'] == "fixed" ? "selected='selected'" : null).">".$text['option-fixed']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "menu_side_item_main_sub_close" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='automatic'>".$text['option-automatic']."</option>\n";
		echo "    	<option value='manual' ".($domain_setting_value == "manual" ? "selected='selected'" : null).">".$text['option-manual']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "body_header_brand_type" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='image' ".(($row['domain_setting_value'] == "image") ? "selected='selected'" : null).">".$text['label-image']."</option>\n";
		echo "    	<option value='text' ".(($row['domain_setting_value'] == "text") ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		echo "    	<option value='image_text' ".(($row['domain_setting_value'] == "image_text") ? "selected='selected'" : null).">".$text['label-image_text']."</option>\n";
		echo "    	<option value='none' ".(($row['domain_setting_value'] == "none") ? "selected='selected'" : null).">".$text['label-none']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "theme" && $subcategory == "input_toggle_style" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='select'>".$text['option-select']."</option>\n";
		echo "    	<option value='switch_round' ".(($row['domain_setting_value'] == "switch_round") ? "selected='selected'" : null).">".$text['option-switch_round']."</option>\n";
		echo "    	<option value='switch_square' ".(($row['domain_setting_value'] == "switch_square") ? "selected='selected'" : null).">".$text['option-switch_square']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "users" && $subcategory == "username_format" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='any' ".($row['domain_setting_value'] == 'any' ? "selected='selected'" : null).">".$text['option-username_format_any']."</option>\n";
		echo "    	<option value='email' ".($row['domain_setting_value'] == 'email' ? "selected='selected'" : null).">".$text['option-username_format_email']."</option>\n";
		echo "    	<option value='no_email' ".($row['domain_setting_value'] == 'no_email' ? "selected='selected'" : null).">".$text['option-username_format_no_email']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "voicemail" && $subcategory == "voicemail_file" && $name == "text" ) {
		echo "    <select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='listen' ".(($row['domain_setting_value'] == "listen") ? "selected='selected'" : null).">".$text['option-voicemail_file_listen']."</option>\n";
		echo "    	<option value='link' ".(($row['domain_setting_value'] == "link") ? "selected='selected'" : null).">".$text['option-voicemail_file_link']."</option>\n";
		echo "    	<option value='attach' ".(($row['domain_setting_value'] == "attach") ? "selected='selected'" : null).">".$text['option-voicemail_file_attach']."</option>\n";
		echo "    </select>\n";
	}
	elseif ($category == "voicemail" && ($subcategory == "message_caller_id_number" || $subcategory == "message_date_time") && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='before'>".$text['label-before']."</option>\n";
		echo "    	<option value='after' ".(($row['domain_setting_value'] == "after") ? "selected='selected'" : null).">".$text['label-after']."</option>\n";
		echo "    	<option value='false' ".(($row['domain_setting_value'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "recordings" && $subcategory == "storage_type" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='file'>".$text['label-file']."</option>\n";
		echo "    	<option value='base64' ".(($row['domain_setting_value'] == "base64") ? "selected='selected'" : null).">".$text['label-base64']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "destinations" && $subcategory == "dialplan_mode" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='multiple'>".$text['label-multiple']."</option>\n";
		echo "    	<option value='single' ".(($row['domain_setting_value'] == "single") ? "selected='selected'" : null).">".$text['label-single']."</option>\n";
		echo "	</select>\n";
	}
	elseif ($category == "destinations" && $subcategory == "select_mode" && $name == "text" ) {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		echo "    	<option value='default'>".$text['label-default']."</option>\n";
		echo "    	<option value='dynamic' ".(($row['domain_setting_value'] == "dynamic") ? "selected='selected'" : null).">".$text['label-dynamic']."</option>\n";
		echo "	</select>\n";
	}
	elseif (is_json($row['domain_setting_value'])) {
		echo "	<textarea class='formfld' style='width: 100%; height: 80px; font-family: courier, monospace; overflow: auto;' id='domain_setting_value' name='domain_setting_value' wrap='off'>".$row['domain_setting_value']."</textarea>\n";
	}
	elseif ($name == "boolean") {
		echo "	<select class='formfld' id='domain_setting_value' name='domain_setting_value'>\n";
		if ($category == "provision" && is_numeric($row['domain_setting_value'])) {
			echo "	<option value='0'>".$text['label-false']."</option>\n";
			echo "	<option value='1' ".(($row['domain_setting_value'] == 1) ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
			echo "	<option value='true' ".((strtolower($row['domain_setting_value']) == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		}
		echo "	</select>\n";
	}
	else {
		echo "	<input class='formfld' type='text' id='domain_setting_value' name='domain_setting_value' value=\"".escape($row['domain_setting_value'])."\">\n";
	}
	echo "<br />\n";
	echo $text['description-value']."\n";
	if ($category == "theme" && substr_count($subcategory, "_font") > 0 && $name == "text") {
		echo "&nbsp;&nbsp;".$text['label-reference'].": <a href='https://www.google.com/fonts' target='_blank'>".$text['label-web_fonts']."</a>\n";
	}
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
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='domain_setting_enabled' name='domain_setting_enabled' value='true' ".($domain_setting_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='domain_setting_enabled' name='domain_setting_enabled'>\n";
		echo "		<option value='true' ".($domain_setting_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($domain_setting_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-setting_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='width: 185px; height: 80px;' name='domain_setting_description'>".escape($domain_setting_description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='domain_setting_uuid' value='".escape($domain_setting_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

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
	echo "	$('#domain_setting_name').on('keyup',function(){ \n";
	echo "		(this.value.toLowerCase() == 'array') ? $('#tr_order').slideDown('fast') : $('#tr_order').slideUp('fast');\n";
	echo "	});\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
