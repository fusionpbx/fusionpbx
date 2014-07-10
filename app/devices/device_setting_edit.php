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
 Portions created by the Initial Developer are Copyright (C) 2008-2012
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('device_setting_add') || permission_exists('device_setting_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$device_setting_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["device_uuid"]) > 0) {
	$device_uuid = check_str($_GET["device_uuid"]);
}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$device_setting_category = check_str($_POST["device_setting_category"]);
		$device_setting_subcategory = check_str($_POST["device_setting_subcategory"]);
		$device_setting_name = check_str($_POST["device_setting_name"]);
		$device_setting_value = check_str($_POST["device_setting_value"]);
		$device_setting_enabled = check_str($_POST["device_setting_enabled"]);
		$device_setting_description = check_str($_POST["device_setting_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update" && permission_exists('device_setting_edit')) {
		$device_setting_uuid = check_str($_POST["device_setting_uuid"]);
	}
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
			//add the device
				if ($action == "add" && permission_exists('device_setting_add')) {
					$sql = "insert into v_device_settings ";
					$sql .= "(";
					$sql .= "device_uuid, ";
					$sql .= "device_setting_uuid, ";
					$sql .= "device_setting_category, ";
					$sql .= "device_setting_subcategory, ";
					$sql .= "device_setting_name, ";
					$sql .= "device_setting_value, ";
					$sql .= "device_setting_enabled, ";
					$sql .= "device_setting_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$device_uuid', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'$device_setting_category', ";
					$sql .= "'$device_setting_subcategory', ";
					$sql .= "'$device_setting_name', ";
					$sql .= "'$device_setting_value', ";
					$sql .= "'$device_setting_enabled', ";
					$sql .= "'$device_setting_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "add")

			//update the device
				if ($action == "update" && permission_exists('device_setting_edit')) {
					$sql = "update v_device_settings set ";
					$sql .= "device_setting_category = '$device_setting_category', ";
					$sql .= "device_setting_subcategory = '$device_setting_subcategory', ";
					$sql .= "device_setting_name = '$device_setting_name', ";
					$sql .= "device_setting_value = '$device_setting_value', ";
					$sql .= "device_setting_enabled = '$device_setting_enabled', ";
					$sql .= "device_setting_description = '$device_setting_description' ";
					$sql .= "where device_uuid = '$device_uuid' ";
					$sql .= "and device_setting_uuid = '$device_setting_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "update")

			if ($action == "add") {
				$_SESSION["message"] = $text['message-add'];
			}
			if ($action == "update") {
				$_SESSION["message"] = $text['message-update'];
			}
			header("Location: device_edit.php?id=".$device_uuid);
			return;
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$device_setting_uuid = check_str($_GET["id"]);
		$sql = "select * from v_device_settings ";
		$sql .= "where device_uuid = '$device_uuid' ";
		$sql .= "and device_setting_uuid = '$device_setting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$device_setting_category = $row["device_setting_category"];
			$device_setting_subcategory = $row["device_setting_subcategory"];
			$device_setting_name = $row["device_setting_name"];
			$device_setting_value = $row["device_setting_value"];
			$device_setting_enabled = $row["device_setting_enabled"];
			$device_setting_description = $row["device_setting_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-device_setting-edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-device_setting-add'];
	}

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-device_setting-edit'];
	}
	if ($action == "add") {
		echo $text['header-device_setting-add'];
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='device_settings.php?id=$device_uuid'\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "update") {
		echo $text['description-device_setting-edit'];
	}
	if ($action == "add") {
		echo $text['header-device_setting-add'];
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-category'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_setting_category' maxlength='255' value=\"$device_setting_category\">\n";
	echo "<br />\n";
	echo $text['description-category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-subcategory'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_setting_subcategory' maxlength='255' value=\"$device_setting_subcategory\">\n";
	echo "<br />\n";
	echo $text['description-subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_setting_name' maxlength='255' value=\"$device_setting_name\">\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-value'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_setting_value' maxlength='255' value=\"$device_setting_value\">\n";
	echo "<br />\n";
	echo $text['description-value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='device_setting_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($device_setting_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($device_setting_enabled == "false") {
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
	echo "	".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_setting_description' maxlength='255' value=\"$device_setting_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='device_uuid' value='$device_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='device_setting_uuid' value='$device_setting_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";
+9*
//include the footer
	require_once "resources/footer.php";
?>