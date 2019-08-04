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
	Copyright (C) 2013 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('device_key_add') || permission_exists('device_key_edit')) {
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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$device_key_uuid = $_REQUEST["id"];
		$device_uuid = $_REQUEST["device_uuid"];
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (is_uuid($_GET["device_key_uuid"])) {
		$device_key_uuid = $_GET["device_key_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$device_key_id = $_POST["device_key_id"];
		$device_key_category = $_POST["device_key_category"];
		$device_key_type = $_POST["device_key_type"];
		$device_key_line = $_POST["device_key_line"];
		$device_key_value = $_POST["device_key_value"];
		$device_key_extension = $_POST["device_key_extension"];
		$device_key_label = $_POST["device_key_label"];
		$device_key_icon = $_POST["device_key_icon"];
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$device_key_uuid = $_POST["device_key_uuid"];
	}

	//check for all required data
		//if (strlen($device_key_id) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_id']."<br>\n"; }
		//if (strlen($device_key_category) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_category']."<br>\n"; }
		//if (strlen($device_key_type) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_type']."<br>\n"; }
		//if (strlen($device_key_line) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_line']."<br>\n"; }
		//if (strlen($device_key_value) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_value']."<br>\n"; }
		//if (strlen($device_key_extension) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_extension']."<br>\n"; }
		//if (strlen($device_key_label) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_label']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persistformvar.php";
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
			if ($action == "add" && permission_exists('device_key_add')) {
				$array['device_keys'][0]['device_key_uuid'] = uuid();

				message::add($text['message-add']);
			}

			if ($action == "update" && permission_exists('device_key_edit')) {
				$array['device_keys'][0]['device_key_uuid'] = $device_key_uuid;

				message::add($text['message-update']);
			}

			if (is_array($array) && @sizeof($array) != 0) {
				$array['device_keys'][0]['domain_uuid'] = $domain_uuid;
				$array['device_keys'][0]['device_uuid'] = $device_uuid;
				$array['device_keys'][0]['device_key_id'] = $device_key_id;
				$array['device_keys'][0]['device_key_category'] = $device_key_category;
				$array['device_keys'][0]['device_key_type'] = $device_key_type;
				$array['device_keys'][0]['device_key_line'] = $device_key_line;
				$array['device_keys'][0]['device_key_value'] = $device_key_value;
				$array['device_keys'][0]['device_key_extension'] = $device_key_extension;
				$array['device_keys'][0]['device_key_label'] = $device_key_label;
				$array['device_keys'][0]['device_key_icon'] = $device_key_icon;

				$database = new database;
				$database->app_name = 'devices';
				$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
				$database->save($array);
				unset($array);

				header("Location: device_edit.php?id=".$device_uuid);
				return;
			}
		}
}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$device_key_uuid = $_GET["id"];
		$sql = "select * from v_device_keys ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and device_key_uuid = :device_key_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['device_key_uuid'] = $device_key_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$device_uuid = $row["device_uuid"];
			$device_key_id = $row["device_key_id"];
			$device_key_category = $row["device_key_category"];
			$device_key_type = $row["device_key_type"];
			$device_key_line = $row["device_key_line"];
			$device_key_value = $row["device_key_value"];
			$device_key_extension = $row["device_key_extension"];
			$device_key_label = $row["device_key_label"];
			$device_key_icon = $row["device_key_icon"];
		}
		unset($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-device_key']."</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='device_edit.php?id=$device_uuid'\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_key_category'>\n";
	echo "		<option value=''></option>\n";
	if ($device_key_category != '') {
		$selected[$device_key_category] = "selected='selected'";
	}
	echo "		<option value='line' ".$selected['line'].">".$text['label-line']."</option>\n";
	echo "		<option value='memory' ".$selected['memory'].">".$text['label-memory']."</option>\n";
	echo "		<option value='programmable' ".$selected['programmable'].">".$text['label-programmable']."</option>\n";
	echo "		<option value='expansion' ".$selected['expansion'].">".$text['label-expansion']."</option>\n";
	unset($selected);
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_key_category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_key_id'>\n";
	echo "		<option value=''></option>\n";
	if (is_numeric($device_key_id)) {
		$selected[$device_key_id] = "selected='selected'";
	}
	for ($i = 1; $i <= 18; $i++) {
		echo "	<option value='".$i."' ".$selected[$i].">".$i."</option>\n";
	}
	unset($selected);
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_key_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_line']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_key_line'>\n";
	echo "		<option value=''></option>\n";
	if (is_numeric($device_key_line)) {
		$selected[$device_key_line] = "selected='selected'";
	}
	for ($i = 0; $i <= 12; $i++) {
		echo "	<option value='".$i."' ".$selected[$i].">".$i."</option>\n";
	}
	unset($selected);
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_key_line']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$device_key_types['Cisco'] = array(
		'line' => 'line',
		'disabled' => 'disabled'
		);
	$device_key_types['Yealink'] = array(
		0 => 'N/A (Memory Key Default)',
		1 => 'Conference',
		2 => 'Forward',
		3 => 'Transfer',
		4 => 'Hold',
		5 => 'DND',
		6 => 'Redial',
		7 => 'Call Return',
		8 => 'SMS',
		9 => 'Call Pickup',
		10 => 'Call Park',
		11 => 'DTMF',
		12 => 'Voicemail',
		13 => 'SpeedDial',
		14 => 'Intercom',
		15 => 'Line (Line Key Default)',
		16 => 'BLF',
		17 => 'URL',
		19 => 'Public Hold',
		20 => 'Private',
		21 => 'Shared Line',
		22 => 'XML Group',
		23 => 'Group Pickup',
		24 => 'Paging',
		25 => 'Record',
		27 => 'XML Browser',
		28 => 'History',
		29 => 'Directory',
		30 => 'Menu',
		32 => 'New SMS',
		33 => 'Status',
		34 => 'Hot Desking',
		35 => 'URL Record',
		38 => 'LDAP',
		39 => 'BLF List',
		40 => 'Prefix',
		41 => 'Zero-Sp-Touch',
		42 => 'ACD',
		43 => 'Local Phonebook',
		44 => 'Broadsoft Phonebook',
		45 => 'Local Group',
		46 => 'Broadsoft Group',
		47 => 'XML Phonebook',
		48 => 'Switch Account Up',
		49 => 'Switch Account Down',
		50 => 'Keypad Lock'
		);
	$device_key_types['Other'] = array(
		'line' => 'line',
		'other' => 'other'
		);
	if ($device_key_type != '') {
		$selected[$device_key_type] = "selected='selected'";
		$found = in_array($device_key_type, $device_key_types_yealink) || $device_key_type == 'disabled' || $device_key_type == 'line' ? true : false;
	}
	echo "<select class='formfld' name='device_key_type'>\n";
	echo "	<option value=''></option>\n";
	foreach ($device_key_types as $vendor => $types) {
		echo "<optgroup label='".$vendor."'>\n";
		foreach ($types as $value => $label) {
			echo "<option value='".$value."' ".$selected[$value].">".$label."</option>\n";
		}
		if ($vendor == 'Other' && $device_key_type != '' && !$found) {
			echo "<option value='".$device_key_type."'>".$device_key_type."</option>\n";
		}
		echo "</optgroup>\n";
	}
	echo "</select>\n";
	unset($selected);

	echo "<br />\n";
	echo $text['description-device_key_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_value' maxlength='255' value=\"$device_key_value\">\n";
	echo "<br />\n";
	echo $text['description-device_key_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_extension' maxlength='255' value=\"$device_key_extension\">\n";
	echo "<br />\n";
	echo $text['description-device_key_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_label' maxlength='255' value=\"$device_key_label\">\n";
	echo "<br />\n";
	echo $text['description-device_key_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

        echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_icon']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_icon' maxlength='255' value=\"$device_key_icon\">\n";
	echo "<br />\n";
	echo $text['description-device_key_icon']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='device_uuid' value='$device_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='device_key_uuid' value='$device_key_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>