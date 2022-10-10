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
	Portions created by the Initial Developer are Copyright (C) 2016-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//delete the group from the menu item
	if ($_REQUEST["a"] == "delete" && permission_exists("device_vendor_function_delete") && $_REQUEST["id"] != '') {
		//get the id
			$device_vendor_function_group_uuid = $_REQUEST["id"];
			$device_vendor_function_uuid = $_REQUEST["device_vendor_function_uuid"];
			$device_vendor_uuid = $_REQUEST["device_vendor_uuid"];

		//delete the device vendor function group
			$array['device_vendor_function_groups'][0]['device_vendor_function_group_uuid'] = $device_vendor_function_group_uuid;

			$p = new permissions;
			$p->add('device_vendor_function_group_delete', 'temp');

			$database = new database;
			$database->app_name = 'devices';
			$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
			$database->delete($array);
			unset($array);

			$p->delete('device_vendor_function_group_delete', 'temp');

		//redirect the browser
			message::add($text['message-delete']);
			header("Location: device_vendor_function_edit.php?id=".escape($device_vendor_function_uuid) ."&device_vendor_uuid=".escape($device_vendor_uuid));
			exit;
	}

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_vendor_function_add') || permission_exists('device_vendor_function_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$device_vendor_function_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the parent uuid
	if (is_uuid($_GET["device_vendor_uuid"])) {
		$device_vendor_uuid = $_GET["device_vendor_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		//$label = $_POST["label"];
		$name = $_POST["name"];
		$value = $_POST["value"];
		$enabled = $_POST["enabled"];
		$description = $_POST["description"];
	}

//process the http variables
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid
			if ($action == "update") {
				$device_vendor_function_uuid = $_POST["device_vendor_function_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: devices.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (strlen($label) == 0) { $msg .= $text['message-required']." ".$text['label-label']."<br>\n"; }
			if (strlen($name) == 0) { $msg .= $text['message-required']." ".$text['label-name']."<br>\n"; }
			if (strlen($value) == 0) { $msg .= $text['message-required']." ".$text['label-value']."<br>\n"; }
			if (strlen($enabled) == 0) { $msg .= $text['message-required']." ".$text['label-enabled']."<br>\n"; }
			//if (strlen($description) == 0) { $msg .= $text['message-required']." ".$text['label-description']."<br>\n"; }
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

				//add vendor functions
					if ($action == "add" && permission_exists('device_vendor_function_add')) {
						$device_vendor_function_uuid = uuid();
						$array['device_vendor_functions'][0]['device_vendor_function_uuid'] = $device_vendor_function_uuid;
					}

				//update vendor functions
					if ($action == "update" && permission_exists('device_vendor_function_edit')) {
						$array['device_vendor_functions'][0]['device_vendor_function_uuid'] = $device_vendor_function_uuid;
					}

				//execute
					if (is_array($array) && @sizeof($array) != 0) {
						$array['device_vendor_functions'][0]['device_vendor_uuid'] = $device_vendor_uuid;
						//$array['device_vendor_functions'][0]['label'] = $label;
						$array['device_vendor_functions'][0]['name'] = $name;
						$array['device_vendor_functions'][0]['value'] = $value;
						$array['device_vendor_functions'][0]['enabled'] = $enabled;
						$array['device_vendor_functions'][0]['description'] = $description;

						$database = new database;
						$database->app_name = 'devices';
						$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
						$database->save($array);
						unset($array);
					}

				//add a group to the menu
					if (permission_exists('device_vendor_function_add') && $_REQUEST["group_uuid_name"] != '') {

						//get the group uuid and group_name
							$group_data = explode('|', $_REQUEST["group_uuid_name"]);
							$group_uuid = $group_data[0];
							$group_name = $group_data[1];

						//add the group to the menu
							if (is_uuid($device_vendor_function_uuid)) {
								$device_vendor_function_group_uuid = uuid();
								$array['device_vendor_function_groups'][0]['device_vendor_function_group_uuid'] = $device_vendor_function_group_uuid;
								$array['device_vendor_function_groups'][0]['device_vendor_function_uuid'] = $device_vendor_function_uuid;
								$array['device_vendor_function_groups'][0]['device_vendor_uuid'] = $device_vendor_uuid;
								$array['device_vendor_function_groups'][0]['group_name'] = $group_name;
								$array['device_vendor_function_groups'][0]['group_uuid'] = $group_uuid;

								$p = new permissions;
								$p->add('device_vendor_function_group_add', 'temp');

								$database = new database;
								$database->app_name = 'devices';
								$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
								$database->save($array);
								unset($array);

								$p->delete('device_vendor_function_group_add', 'temp');
							}
					}

				//redirect the user
					$_SESSION["message"] = $text['message-'.$action];
					header("Location: device_vendor_function_edit.php?id=".escape($device_vendor_function_uuid) ."&device_vendor_uuid=".escape($device_vendor_uuid));
					exit;
			}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$device_vendor_function_uuid = $_GET["id"];
		$sql = "select * from v_device_vendor_functions ";
		$sql .= "where device_vendor_function_uuid = :device_vendor_function_uuid ";
		$parameters['device_vendor_function_uuid'] = $device_vendor_function_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			//$label = $row["label"];
			$name = $row["name"];
			$value = $row["value"];
			$enabled = $row["enabled"];
			$description = $row["description"];
		}
		unset($sql, $parameters, $row);
	}

//get function groups assigned
	$sql = "select ";
	$sql .= "fg.*, g.domain_uuid as group_domain_uuid ";
	$sql .= "from ";
	$sql .= "v_device_vendor_function_groups as fg, ";
	$sql .= "v_groups as g ";
	$sql .= "where ";
	$sql .= "fg.group_uuid = g.group_uuid ";
	$sql .= "and fg.device_vendor_uuid = :device_vendor_uuid ";
	$sql .= "and fg.device_vendor_function_uuid = :device_vendor_function_uuid ";
	$sql .= "order by ";
	$sql .= "g.domain_uuid desc, ";
	$sql .= "g.group_name asc ";
	$parameters['device_vendor_uuid'] = $device_vendor_uuid;
	$parameters['device_vendor_function_uuid'] = $device_vendor_function_uuid;
	$database = new database;
	$function_groups = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the assigned_groups array
	if (is_array($function_groups) && @sizeof($function_groups) != 0) {
		foreach($function_groups as $field) {
			if (strlen($field['group_name']) > 0) {
				$assigned_groups[] = $field['group_uuid'];
			}
		}
	}

//get the groups
	$sql = "select * from v_groups ";
	if (is_array($assigned_groups) && @sizeof($assigned_groups) != 0) {
		$sql .= "where ";
		foreach ($assigned_groups as $index => $group_uuid) {
			$sql_where[] = 'group_uuid <> :group_uuid_'.$index;
			$parameters['group_uuid_'.$index] = $group_uuid;
		}
		if (is_array($sql_where) && @sizeof($sql_where) != 0) {
			$sql .= implode(' and ', $sql_where).' ';
		}
	}
	$sql .= "order by domain_uuid desc, group_name asc ";
	$database = new database;
	$groups = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $sql_where, $index);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-device_vendor_function'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-device_vendor_function']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','link'=>'device_vendor_edit.php?id='.urlencode($device_vendor_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='name' maxlength='255' value=\"".escape($name)."\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='value' maxlength='255' value=\"".escape($value)."\">\n";
	echo "<br />\n";
	echo $text['description-value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-groups']."</td>";
	echo "		<td class='vtable'>";
	if (is_array($function_groups) && @sizeof($function_groups) != 0) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		foreach ($function_groups as $field) {
			if (strlen($field['group_name']) > 0) {
				echo "<tr>\n";
				echo "	<td class='vtable' style='white-space: nowrap; padding-right: 30px;' nowrap='nowrap'>";
				echo $field['group_name'].(($field['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['group_domain_uuid']]['domain_name'] : null);
				echo "	</td>\n";
				if (permission_exists('group_member_delete') || if_group("superadmin")) {
					echo "	<td class='list_control_icons' style='width: 25px;'>";
					echo 		"<a href='device_vendor_function_edit.php?id=".$field['device_vendor_function_group_uuid']."&group_uuid=".$field['group_uuid']."&device_vendor_function_uuid=".$device_vendor_function_uuid."&device_vendor_uuid=".$device_vendor_uuid."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
					echo "	</td>";
				}
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
		echo "<br />\n";
	}
	if (is_array($groups) && @sizeof($groups) != 0) {
		echo "<select name='group_uuid_name' class='formfld' style='width: auto; margin-right: 3px;'>\n";
		echo "	<option value=''></option>\n";
		foreach ($groups as $field) {
			if ($field['group_name'] == "superadmin" && !if_group("superadmin")) { continue; }	//only show the superadmin group to other superadmins
			if ($field['group_name'] == "admin" && (!if_group("superadmin") && !if_group("admin") )) { continue; }	//only show the admin group to other admins
			if (!is_array($assigned_groups) || !in_array($field["group_uuid"], $assigned_groups)) {
				echo "	<option value='".escape($field['group_uuid'])."|".escape($field['group_name'])."'>".escape($field['group_name']).(($field['domain_uuid'] != '') ? "@".escape($_SESSION['domains'][$field['domain_uuid']]['domain_name']) : null)."</option>\n";
			}
		}
		echo "</select>";
		echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'collapse'=>'never']);
	}
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='enabled'>\n";
	echo "		<option value='true'>".$text['label-true']."</option>\n";
	echo "		<option value='false' ".($enabled == "false" || $enabled == '' ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='description' maxlength='255' value=\"".escape($description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='device_vendor_uuid' value='".escape($device_vendor_uuid)."'>\n";
	if ($action == "update") {
		echo "			<input type='hidden' name='device_vendor_function_uuid' value='".escape($device_vendor_function_uuid)."'>\n";
	}
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>