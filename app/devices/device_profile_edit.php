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
	Copyright (C) 2020 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_profile_add') || permission_exists('device_profile_edit')) {
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
		$device_profile_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($_POST['device_profile_uuid'])) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $_POST['device_profile_uuid'];

				switch ($_POST['action']) {
					case 'delete':
						if (permission_exists('device_profile_delete')) {
							$obj = new device;
							$obj->delete_profiles($array);
						}
						break;
				}

				header('Location: device_profiles.php');
				exit;
			}

		//get http post variables and set them to php variables
			$device_profile_uuid = $_POST["device_profile_uuid"];
			$device_profile_name = $_POST["device_profile_name"];
			$device_profile_keys = $_POST["device_profile_keys"];
			$device_profile_settings = $_POST["device_profile_settings"];
			$device_profile_enabled = $_POST["device_profile_enabled"] ?: 'false';
			$device_profile_description = $_POST["device_profile_description"];
			$device_profile_keys_delete = $_POST["device_profile_keys_delete"];
			$device_profile_settings_delete = $_POST["device_profile_settings_delete"];

		//set the domain_uuid for users that do not have the permission
			if (permission_exists('device_profile_domain')) {
				//allowed to updat the domain_uuid
				$domain_uuid = $_POST["domain_uuid"];
			}
			else {
				if ($action == 'add') {
					//use the current domain
					$domain_uuid = $_SESSION['domain_uuid'];
				}
				else {
					//keep the current domain_uuid
					$sql = "select domain_uuid from v_device_profiles ";
					$sql .= "where device_profile_uuid = :device_profile_uuid ";
					$parameters['device_profile_uuid'] = $device_profile_uuid;
					$database = new database;
					$domain_uuid = $database->execute($sql, $parameters, 'column');
				}
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
			if (strlen($device_profile_name) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_name']."<br>\n"; }
			//if (strlen($device_profile_keys) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_keys']."<br>\n"; }
			//if (strlen($device_profile_settings) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_settings']."<br>\n"; }
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			if (strlen($device_profile_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_enabled']."<br>\n"; }
			//if (strlen($device_profile_description) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_description']."<br>\n"; }
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

		//add the device_profile_uuid
			if (strlen($_POST["device_profile_uuid"]) == 0) {
				$device_profile_uuid = uuid();
			}

		//prepare the array
			$array['device_profiles'][0]["device_profile_uuid"] = $device_profile_uuid;
			$array['device_profiles'][0]["device_profile_name"] = $device_profile_name;
			$array['device_profiles'][0]["domain_uuid"] = $domain_uuid;
			$array['device_profiles'][0]["device_profile_enabled"] = $device_profile_enabled;
			$array['device_profiles'][0]["device_profile_description"] = $device_profile_description;
			$y = 0;
			foreach ($device_profile_keys as $row) {
				if (strlen($row['profile_key_vendor']) > 0 && strlen($row['profile_key_id']) > 0) {
					$array['device_profiles'][0]['device_profile_keys'][$y]["domain_uuid"] = $domain_uuid;
					$array['device_profiles'][0]['device_profile_keys'][$y]["device_profile_key_uuid"] = $row["device_profile_key_uuid"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_category"] = $row["profile_key_category"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_id"] = $row["profile_key_id"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_vendor"] = $row["profile_key_vendor"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_type"] = $row["profile_key_type"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_line"] = $row["profile_key_line"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_value"] = $row["profile_key_value"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_extension"] = $row["profile_key_extension"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_protected"] = $row["profile_key_protected"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_label"] = $row["profile_key_label"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_icon"] = $row["profile_key_icon"];
					$y++;
				}
			}
			$y = 0;
			if (permission_exists("device_profile_setting_edit")) {
				foreach ($device_profile_settings as $row) {
					if (strlen($row['profile_setting_name']) > 0 && strlen($row['profile_setting_enabled']) > 0) {
						$array['device_profiles'][0]['device_profile_settings'][$y]["domain_uuid"] = $domain_uuid;
						$array['device_profiles'][0]['device_profile_settings'][$y]["device_profile_setting_uuid"] = $row["device_profile_setting_uuid"];
						$array['device_profiles'][0]['device_profile_settings'][$y]["profile_setting_name"] = $row["profile_setting_name"];
						$array['device_profiles'][0]['device_profile_settings'][$y]["profile_setting_value"] = $row["profile_setting_value"];
						$array['device_profiles'][0]['device_profile_settings'][$y]["profile_setting_enabled"] = $row["profile_setting_enabled"];
						$array['device_profiles'][0]['device_profile_settings'][$y]["profile_setting_description"] = $row["profile_setting_description"];
						$y++;
					}
				}
			}

		//save to the data
			$database = new database;
			$database->app_name = 'Device Profiles';
			$database->app_uuid = 'bb2531c3-97e6-428f-9a19-cbac1b96f5b7';
			$database->save($array);

		//remove checked keys
			if (
				$action == 'update'
				&& permission_exists('device_profile_key_delete')
				&& is_array($device_profile_keys_delete)
				&& @sizeof($device_profile_keys_delete) != 0
				) {
				$obj = new device;
				$obj->device_profile_uuid = $device_profile_uuid;
				$obj->delete_profile_keys($device_profile_keys_delete);
			}

		//remove checked settings
			if (
				$action == 'update'
				&& permission_exists('device_profile_setting_delete')
				&& is_array($device_profile_settings_delete)
				&& @sizeof($device_profile_settings_delete) != 0
				) {
				$obj = new device;
				$obj->device_profile_uuid = $device_profile_uuid;
				$obj->delete_profile_settings($device_profile_settings_delete);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: device_profile_edit.php?id='.$device_profile_uuid);
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$device_profile_uuid = $_GET["id"];
		$sql = "select * from v_device_profiles ";
		$sql .= "where device_profile_uuid = :device_profile_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['device_profile_uuid'] = $device_profile_uuid;
		$database = new database;
		$result = $database->execute($sql, $parameters, 'all');
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$device_profile_name = $row["device_profile_name"];
			$device_profile_keys = $row["device_profile_keys"];
			$device_profile_settings = $row["device_profile_settings"];
			$device_profile_enabled = $row["device_profile_enabled"];
			$device_profile_description = $row["device_profile_description"];
		}
		unset ($sql, $parameters);
	}

//set the defaults
	if (strlen($device_profile_enabled) == 0) { $device_profile_enabled = 'true'; }

//get the child data
	if (strlen($device_profile_uuid) > 0) {
		$sql = "select * from v_device_profile_keys ";
		$sql .= "where device_profile_uuid = :device_profile_uuid ";
		//$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$sql .= "order by profile_key_vendor asc, ";
		$sql .= "case profile_key_category ";
		$sql .= "when 'line' then 1 ";
		$sql .= "when 'memory' then 2 ";
		$sql .= "when 'programmable' then 3 ";
		$sql .= "when 'expansion' then 4 ";
		$sql .= "when 'expansion-1' then 5 ";
		$sql .= "when 'expansion-2' then 6 ";
		$sql .= "when 'expansion-3' then 7 ";
		$sql .= "when 'expansion-4' then 8 ";
		$sql .= "when 'expansion-5' then 9 ";
		$sql .= "when 'expansion-6' then 10 ";
		$sql .= "else 100 end, ";
		$sql .= "profile_key_id asc ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['device_profile_uuid'] = $device_profile_uuid;
		$database = new database;
		$device_profile_keys = $database->execute($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//get the vendor count
	$vendor_count = 0;
	if (is_array($device_profile_keys) && @sizeof($device_profile_keys) != 0) {
		foreach($device_profile_keys as $row) {
			if ($previous_vendor != $row['profile_key_vendor']) {
				$previous_vendor = $row['profile_key_vendor'];
				$vendor_count++;
			}
		}
	}

//get the vendors
	$sql = "select * ";
	$sql .= "from v_device_vendors as v ";
	$sql .= "where enabled = 'true' ";
	$sql .= "order by name asc ";
	$database = new database;
	$vendors = $database->select($sql, null, 'all');
	unset($sql);

//get the vendor functions
	$sql = "select v.name as vendor_name, f.name, f.value ";
	$sql .= "from v_device_vendors as v, v_device_vendor_functions as f ";
	$sql .= "where v.device_vendor_uuid = f.device_vendor_uuid ";
	$sql .= "and v.enabled = 'true' ";
	$sql .= "and f.enabled = 'true' ";
	$sql .= "order by v.name asc, f.name asc ";
	$database = new database;
	$vendor_functions = $database->select($sql, null, 'all');
	unset($sql);

//add the $device_profile_key_uuid
	if (strlen($device_profile_key_uuid) == 0) {
		$device_profile_key_uuid = uuid();
	}

//add an empty row
	if (!is_array($device_profile_keys) || count($device_profile_keys) == 0) {
		$rows = $_SESSION['devices']['profile_key_add_rows']['numeric'];
		$id = 0;
	}
	if (is_array($device_profile_keys) && count($device_profile_keys) > 0) {
		$rows = $_SESSION['devices']['profile_key_edit_rows']['numeric'];
		$id = count($device_profile_keys)+1;
	}
	for ($x = 0; $x < $rows; $x++) {
		$device_profile_keys[$id]['domain_uuid'] = $domain_uuid;
		$device_profile_keys[$id]['device_profile_uuid'] = $device_profile_uuid;
		$device_profile_keys[$id]['device_profile_key_uuid'] = '';
		$device_profile_keys[$id]['profile_key_category'] = '';
		$device_profile_keys[$id]['profile_key_id'] = '';
		$device_profile_keys[$id]['profile_key_vendor'] = '';
		$device_profile_keys[$id]['profile_key_type'] = '';
		$device_profile_keys[$id]['profile_key_line'] = '';
		$device_profile_keys[$id]['profile_key_value'] = '';
		$device_profile_keys[$id]['profile_key_extension'] = '';
		$device_profile_keys[$id]['profile_key_protected'] = '';
		$device_profile_keys[$id]['profile_key_label'] = '';
		$device_profile_keys[$id]['profile_key_icon'] = '';
		$id++;
	}

//get the child data
	if (strlen($device_profile_uuid) > 0) {
		$sql = "select * from v_device_profile_settings ";
		$sql .= "where device_profile_uuid = :device_profile_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$sql .= "order by profile_setting_name asc";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['device_profile_uuid'] = $device_profile_uuid;
		$database = new database;
		$device_profile_settings = $database->execute($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $device_profile_setting_uuid
	if (strlen($device_profile_setting_uuid) == 0) {
		$device_profile_setting_uuid = uuid();
	}

//add an empty row
	$x = is_array($device_profile_settings) ? count($device_profile_settings) : 0;
	$device_profile_settings[$x]['domain_uuid'] = $domain_uuid;
	$device_profile_settings[$x]['device_profile_uuid'] = $device_profile_uuid;
	$device_profile_settings[$x]['device_profile_setting_uuid'] = '';
	$device_profile_settings[$x]['profile_setting_name'] = '';
	$device_profile_settings[$x]['profile_setting_value'] = '';
	$device_profile_settings[$x]['profile_setting_enabled'] = '';
	$device_profile_settings[$x]['profile_setting_description'] = '';

//filter the uuid
	if (!is_uuid($device_profile_uuid)) {
		$device_profile_uuid = null;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-device_profile'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-device_profile']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'device_profiles.php']);
	if ($action == 'update') {
		$button_margin = 'margin-left: 15px;';
		if (permission_exists('device_profile_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>$button_margin,'onclick'=>"modal_open('modal-copy','btn_copy');"]);
			unset($button_margin);
		}
		if (
			permission_exists('device_profile_delete') ||
			permission_exists('device_profile_key_delete') ||
			permission_exists('device_profile_setting_delete')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>$button_margin,'onclick'=>"modal_open('modal-delete','btn_delete');"]);
			unset($button_margin);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('device_profile_add')) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','link'=>'device_profile_copy.php?id='.urlencode($device_profile_uuid),'onclick'=>"modal_close();"])]);
	}
	if (
		$action == 'update' && (
		permission_exists('device_profile_delete') ||
		permission_exists('device_profile_key_delete') ||
		permission_exists('device_profile_setting_delete')
		)) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo $text['description-device_profiles']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_profile_name' maxlength='255' value='".escape($device_profile_name)."'>\n";
	echo "<br />\n";
	echo $text['description-device_profile_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_keys']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	if ($vendor_count == 0) {
		echo "		<tr>\n";
		echo "			<th class='vtablereq'>".$text['label-device_key_category']."</th>\n";
		echo "			<th class='vtablereq'>".$text['label-device_key_id']."</th>\n";
		echo "			<th class='vtablereq'>".$text['label-device_key_vendor']."</th>\n";
		echo "			<th class='vtablereq'>".$text['label-device_key_type']."</th>\n";
		echo "			<th class='vtablereq'>".$text['label-device_key_line']."</th>\n";
		echo "			<td class='vtable'>".$text['label-device_key_value']."</td>\n";
		if (permission_exists('device_key_extension')) {
			echo "			<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
		}
		if (permission_exists('device_key_protected')) {
			echo "			<td class='vtable'>".$text['label-device_key_protected']."</td>\n";
		}
		echo "			<td class='vtable'>".$text['label-device_key_label']."</td>\n";
		if (permission_exists('device_key_icon')) {
			echo "			<td class='vtable'>".$text['label-device_key_icon']."</td>\n";
		}
		if (is_array($device_profile_keys) && @sizeof($device_profile_keys) > 1 && permission_exists('device_profile_key_delete')) {
			echo "			<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_keys', 'delete_toggle_keys');\" onmouseout=\"swap_display('delete_label_keys', 'delete_toggle_keys');\">\n";
			echo "				<span id='delete_label_keys'>".$text['label-delete']."</span>\n";
			echo "				<span id='delete_toggle_keys'><input type='checkbox' id='checkbox_all_keys' name='checkbox_all' onclick=\"edit_all_toggle('keys');\"></span>\n";
			echo "			</td>\n";
		}
		echo "		</tr>\n";
	}

	$x = 0;
	foreach($device_profile_keys as $row) {

		//get the profile key vendor from the key type
		foreach ($vendor_functions as $function) {
			if ($row['profile_key_vendor'] == $function['vendor_name'] && $row['profile_key_type'] == $function['value']) {
				$profile_key_vendor = $function['vendor_name'];
			}
		}

		//prep vendor name for use in ids and classes
		$device_vendor = str_replace(' ', '_', $row['profile_key_vendor']);

		//set the column names
		if ($previous_profile_key_vendor != $row['profile_key_vendor']) {
			echo "			<tr>\n";
			echo "				<th class='vtablereq'>".$text['label-device_key_category']."</td>\n";
			echo "				<th class='vtablereq'>".$text['label-device_key_id']."</td>\n";
			echo "				<th class='vtablereq'>".$text['label-device_vendor']."</td>\n";
			echo "				<th class='vtablereq'>".$text['label-device_key_type']."</td>\n";
			echo "				<th class='vtablereq'>".$text['label-device_key_line']."</td>\n";
			echo "				<td class='vtable'>".$text['label-device_key_value']."</td>\n";
			if (permission_exists('device_key_extension')) {
				echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
			}
			if (permission_exists('device_key_protected')) {
				echo "				<td class='vtable'>".$text['label-device_key_protected']."</td>\n";
			}
			echo "				<td class='vtable'>".$text['label-device_key_label']."</td>\n";
			if (permission_exists('device_key_icon')) {
				echo "			<td class='vtable'>".$text['label-device_key_icon']."</td>\n";
			}
			if (is_array($device_profile_keys) && @sizeof($device_profile_keys) > 1 && is_uuid($row["device_profile_key_uuid"]) && permission_exists('device_profile_key_delete')) {
				echo "				<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_keys_".$device_vendor."', 'delete_toggle_keys_".$device_vendor."');\" onmouseout=\"swap_display('delete_label_keys_".$device_vendor."', 'delete_toggle_keys_".$device_vendor."');\">\n";
				echo "					<span id='delete_label_keys_".$device_vendor."'>".$text['label-delete']."</span>\n";
				echo "					<span id='delete_toggle_keys_".$device_vendor."'><input type='checkbox' id='checkbox_all_keys_".$device_vendor."' name='checkbox_all' onclick=\"edit_all_toggle('keys_".$device_vendor."');\"></span>\n";
				echo "				</td>\n";
			}
			echo "			</tr>\n";
		}

		//show all the rows in the array
		echo "		<tr>\n";
		echo "			<input type='hidden' name='device_profile_keys[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
		echo "			<input type='hidden' name='device_profile_keys[$x][device_profile_uuid]' value=\"".escape($row["device_profile_uuid"])."\">\n";
		echo "			<input type='hidden' name='device_profile_keys[$x][device_profile_key_uuid]' value=\"".(is_uuid($row["device_profile_key_uuid"]) ? $row["device_profile_key_uuid"] : uuid())."\">\n";
		echo "			<td class='formfld'>\n";
		echo "				<select class='formfld' name='device_profile_keys[$x][profile_key_category]'>\n";
		echo "					<option value='line' ".($row['profile_key_category'] == "line" ? "selected='selected'" : null).">".$text['label-line']."</option>\n";
		if ($row['profile_key_vendor'] !== "polycom") { 
			echo "					<option value='memory' ".($row['profile_key_category'] == "memory" ? "selected='selected'" : null).">".$text['label-memory']."</option>\n";
		}
		echo "					<option value='programmable' ".($row['profile_key_category'] == "programmable" ? "selected='selected'" : null).">".$text['label-programmable']."</option>\n";
		if ($row['profile_key_vendor'] !== "polycom") { 
			if (strlen($row['profile_key_vendor']) == 0) {
				echo "					<option value='expansion' ".($row['profile_key_category'] == "expansion" ? "selected='selected'" : null).">".$text['label-expansion']."</option>\n";
				echo "					<option value='expansion-1' ".($row['profile_key_category'] == "expansion-1" ? "selected='selected'" : null).">".$text['label-expansion']." 1</option>\n";
				echo "					<option value='expansion-2' ".($row['profile_key_category'] == "expansion-2" ? "selected='selected'" : null).">".$text['label-expansion']." 2</option>\n";
				echo "					<option value='expansion-3' ".($row['profile_key_category'] == "expansion-3" ? "selected='selected'" : null).">".$text['label-expansion']." 3</option>\n";
				echo "					<option value='expansion-4' ".($row['profile_key_category'] == "expansion-4" ? "selected='selected'" : null).">".$text['label-expansion']." 4</option>\n";
				echo "					<option value='expansion-5' ".($row['profile_key_category'] == "expansion-5" ? "selected='selected'" : null).">".$text['label-expansion']." 5</option>\n";
				echo "					<option value='expansion-6' ".($row['profile_key_category'] == "expansion-6" ? "selected='selected'" : null).">".$text['label-expansion']." 6</option>\n";
			}
			elseif (strtolower($row['profile_key_vendor']) == "grandstream") {
				echo "	<option value='expansion' ".($row['profile_key_category'] == "expansion" || $row['profile_key_category'] == "expansion" ? "selected='selected'" : null).">".$text['label-expansion']."</option>\n";
			}
			else {
				if (strtolower($row['profile_key_vendor']) == "cisco" || strtolower($row['profile_key_vendor']) == "yealink") {
					echo "	<option value='expansion-1' ".($row['profile_key_category'] == "expansion-1" || $row['profile_key_category'] == "expansion" ? "selected='selected'" : null).">".$text['label-expansion']." 1</option>\n";
					echo "	<option value='expansion-2' ".($row['profile_key_category'] == "expansion-2" ? "selected='selected'" : null).">".$text['label-expansion']." 2</option>\n";
					echo "	<option value='expansion-3' ".($row['profile_key_category'] == "expansion-3" ? "selected='selected'" : null).">".$text['label-expansion']." 3</option>\n";
					echo "	<option value='expansion-4' ".($row['profile_key_category'] == "expansion-4" ? "selected='selected'" : null).">".$text['label-expansion']." 4</option>\n";
					echo "	<option value='expansion-5' ".($row['profile_key_category'] == "expansion-5" ? "selected='selected'" : null).">".$text['label-expansion']." 5</option>\n";
					echo "	<option value='expansion-6' ".($row['profile_key_category'] == "expansion-6" ? "selected='selected'" : null).">".$text['label-expansion']." 6</option>\n";
				}
			}
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td class='formfld'>\n";
		echo "				<select class='formfld' name='device_profile_keys[$x][profile_key_id]'>\n";
		echo "					<option value=''></option>\n";
		for ($i = 1; $i <= 255; $i++) {
			echo "					<option value='$i' ".($row['profile_key_id'] == $i ? "selected":null).">$i</option>\n";
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td class='formfld'>\n";
		echo "				<select class='formfld' name='device_profile_keys[".$x."][profile_key_vendor]' id='key_vendor_".$x."'>\n";
		echo "					<option value=''></option>\n";
		foreach ($vendors as $vendor) {
			$selected = '';
			if ($row['profile_key_vendor'] == $vendor['name']) {
				$selected = "selected='selected'";
			}
			if (strlen($vendor['name']) > 0) {
				echo "					<option value='".escape($vendor['name'])."' $selected >".escape(ucwords($vendor['name']))."</option>\n";
			}
		}
		echo "				</select>\n";

		echo "			</td>\n";
		echo "			<td class='formfld'>\n";
		//echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_type]' maxlength='255' value=\"".escape($row["profile_key_type"])."\">\n";

		echo "				<select class='formfld' name='device_profile_keys[".$x."][profile_key_type]' id='key_type_".$x."'>\n";
		echo "					<option value=''></option>\n";
		$previous_vendor = '';
		$i = 0;
		foreach ($vendor_functions as $function) {
			if (strlen($row['profile_key_vendor']) == 0 && $function['vendor_name'] != $previous_vendor) {
				if ($i > 0) { echo "	</optgroup>\n"; }
				echo "					<optgroup label='".escape(ucwords($function['vendor_name']))."'>\n";
			}
			$selected = '';
			if ($row['profile_key_vendor'] == $function['vendor_name'] && $row['profile_key_type'] == $function['value']) {
				$selected = "selected='selected'";
			}
			if (strlen($row['profile_key_vendor']) == 0) {
				echo "					<option value='".escape($function['value'])."' vendor='".escape($function['vendor_name'])."' $selected >".$text['label-'.$function['name']]."</option>\n";
			}
			if (strlen($row['profile_key_vendor']) > 0 && $row['profile_key_vendor'] == $function['vendor_name']) {
				echo "					<option value='".escape($function['value'])."' vendor='".escape($function['vendor_name'])."' $selected >".$text['label-'.$function['name']]."</option>\n";
			}
			$previous_vendor = $function['vendor_name'];
			$i++;
		}
		if (strlen($row['profile_key_vendor']) == 0) {
			echo "					</optgroup>\n";
		}
		echo "				</select>\n";

		echo "			</td>\n";
		echo "			<td class='formfld'>\n";
		echo "				<select class='formfld' name='device_profile_keys[$x][profile_key_line]'>\n";
		echo "					<option value=''></option>\n";
		for ($l = 0; $l <= 12; $l++) {
			echo "					<option value='".$l."' ".(($row['profile_key_line'] == $l) ? "selected='selected'" : null).">".$l."</option>\n";
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td class='formfld'>\n";
		echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_value]' maxlength='255' value=\"".escape($row["profile_key_value"])."\">\n";
		echo "			</td>\n";
		if (permission_exists('device_key_extension')) {
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_extension]' maxlength='255' value=\"".escape($row["profile_key_extension"])."\">\n";
			echo "			</td>\n";
		}
		if (permission_exists('device_key_protected')) {
			echo "			<td>\n";
			echo "				<select class='formfld' name='device_profile_keys[$x][profile_key_protected]'>\n";
			echo "					<option value=''></option>\n";
			echo "					<option value='true' ".($row['profile_key_protected'] == "true" ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
			echo "					<option value='false' ".($row['profile_key_protected'] == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
			echo "				</select>\n";
			echo "			</td>\n";
		}
		echo "			<td class='formfld'>\n";
		echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_label]' maxlength='255' value=\"".escape($row["profile_key_label"])."\">\n";
		echo "			</td>\n";
		if (permission_exists('device_key_icon')) {
			echo "		<td class='formfld'>\n";
			echo "			<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_icon]' maxlength='255' value=\"".escape($row["profile_key_icon"])."\">\n";
			echo "		</td>\n";
		}
		if (is_array($device_profile_keys) && @sizeof($device_profile_keys) > 1 && permission_exists('device_profile_key_delete')) {
			if (is_uuid($row["device_profile_key_uuid"])) {
				echo "			<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
				echo "				<input type='checkbox' name='device_profile_keys_delete[".$x."][checked]' value='true' class='chk_delete checkbox_keys_".$device_vendor."' onclick=\"edit_delete_action('keys_".$device_vendor."');\">\n";
				echo "				<input type='hidden' name='device_profile_keys_delete[".$x."][uuid]' value='".escape($row['device_profile_key_uuid'])."' />\n";
			}
			else {
				echo "			<td>\n";
			}
			echo "			</td>\n";
		}
		echo "		</tr>\n";

		//set the previous vendor
		$previous_profile_key_vendor = $row['profile_key_vendor'];

		//increment the array key
		$x++;
	}
	echo "	</table>\n";
	echo "<br />\n";
	echo $text['description-profile_key_icon']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('device_profile_setting_edit')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_profile_settings']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<table>\n";
		echo "		<tr>\n";
		echo "			<th class='vtablereq'>".$text['label-device_setting_name']."</th>\n";
		echo "			<td class='vtable'>".$text['label-device_setting_value']."</td>\n";
		echo "			<td class='vtable'>".$text['label-enabled']."</td>\n";
		echo "			<td class='vtable'>".$text['label-device_setting_description']."</td>\n";
		if (is_array($device_profile_settings) && @sizeof($device_profile_settings) > 1 && permission_exists('device_profile_setting_delete')) {
			echo "			<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_settings', 'delete_toggle_settings');\" onmouseout=\"swap_display('delete_label_settings', 'delete_toggle_settings');\">\n";
			echo "				<span id='delete_label_settings'>".$text['label-delete']."</span>\n";
			echo "				<span id='delete_toggle_settings'><input type='checkbox' id='checkbox_all_settings' name='checkbox_all' onclick=\"edit_all_toggle('settings');\"></span>\n";
			echo "			</td>\n";
		}
		echo "		</tr>\n";
		$x = 0;
		foreach($device_profile_settings as $row) {
			echo "		<tr>\n";
			echo "			<input type='hidden' name='device_profile_settings[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
			echo "			<input type='hidden' name='device_profile_settings[$x][device_profile_uuid]' value=\"".escape($row["device_profile_uuid"])."\">\n";
			echo "			<input type='hidden' name='device_profile_settings[$x][device_profile_setting_uuid]' value=\"".(is_uuid($row["device_profile_setting_uuid"]) ? $row["device_profile_setting_uuid"] : uuid())."\">\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='device_profile_settings[$x][profile_setting_name]' maxlength='255' value=\"".escape($row["profile_setting_name"])."\">\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='device_profile_settings[$x][profile_setting_value]' maxlength='255' value=\"".escape($row["profile_setting_value"])."\">\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<select class='formfld' name='device_profile_settings[$x][profile_setting_enabled]'>\n";
			echo "					<option value='true'>".$text['label-true']."</option>\n";
			echo "					<option value='false' ".($row['profile_setting_enabled'] == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
			echo "				</select>\n";
			echo "			</td>\n";
			echo "			<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='device_profile_settings[$x][profile_setting_description]' maxlength='255' value=\"".escape($row["profile_setting_description"])."\">\n";
			echo "			</td>\n";
			if (is_array($device_profile_settings) && @sizeof($device_profile_settings) > 1 && permission_exists('device_profile_setting_delete')) {
				if (is_uuid($row["device_profile_setting_uuid"])) {
					echo "			<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
					echo "				<input type='checkbox' name='device_profile_settings_delete[".$x."][checked]' value='true' class='chk_delete checkbox_settings' onclick=\"edit_delete_action('settings');\">\n";
					echo "				<input type='hidden' name='device_profile_settings_delete[".$x."][uuid]' value='".escape($row['device_profile_setting_uuid'])."' />\n";
				}
				else {
					echo "			<td>\n";
				}
				echo "			</td>\n";
			}
			echo "		</tr>\n";
			$x++;
		}
		echo "	</table>\n";
		echo "<br />\n";
		echo $text['description-profile_setting_description']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_profile_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain_uuid']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<select class='formfld' name='domain_uuid'>\n";
		echo "		<option value='' ".(!is_uuid($domain_uuid) ? "selected='selected'" : null).">".$text['select-global']."</option>\n";
		foreach ($_SESSION['domains'] as $row) {
			echo "		<option value='".$row['domain_uuid']."' ".($row['domain_uuid'] == $domain_uuid ? "selected='selected'" : null).">".escape($row['domain_name'])."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-domain_uuid']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='device_profile_enabled' name='device_profile_enabled' value='true' ".($device_profile_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='device_profile_enabled' name='device_profile_enabled'>\n";
		echo "		<option value='true' ".($device_profile_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($device_profile_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-device_profile_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_profile_description' maxlength='255' value='".escape($device_profile_description)."'>\n";
	echo "<br />\n";
	echo $text['description-device_profile_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='device_profile_uuid' value='".escape($device_profile_uuid)."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
