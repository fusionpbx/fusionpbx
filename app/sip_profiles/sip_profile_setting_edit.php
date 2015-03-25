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
if (permission_exists('sip_profile_add') || permission_exists('sip_profile_edit')) {
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
		$sip_profile_setting_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["sip_profile_uuid"]) > 0) {
	$sip_profile_uuid = check_str($_GET["sip_profile_uuid"]);
}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$sip_profile_setting_name = check_str($_POST["sip_profile_setting_name"]);
		$sip_profile_setting_value = check_str($_POST["sip_profile_setting_value"]);
		$sip_profile_setting_enabled = check_str($_POST["sip_profile_setting_enabled"]);
		$sip_profile_setting_description = check_str($_POST["sip_profile_setting_description"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$sip_profile_setting_uuid = check_str($_POST["sip_profile_setting_uuid"]);
	}

	//check for all required data
		//if (strlen($sip_profile_setting_name) == 0) { $msg .= $text['message-required'].$text['label-setting_name']."<br>\n"; }
		//if (strlen($sip_profile_setting_value) == 0) { $msg .= $text['message-required'].$text['label-setting_value']."<br>\n"; }
		//if (strlen($sip_profile_setting_enabled) == 0) { $msg .= $text['message-required'].$text['label-setting_enabled']."<br>\n"; }
		//if (strlen($sip_profile_setting_description) == 0) { $msg .= $text['message-required'].$text['label-setting_description']."<br>\n"; }
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
			if ($action == "add") {
				//add the sip profile setting
					$sql = "insert into v_sip_profile_settings ";
					$sql .= "(";
					$sql .= "sip_profile_setting_uuid, ";
					$sql .= "sip_profile_uuid, ";
					$sql .= "sip_profile_setting_name, ";
					$sql .= "sip_profile_setting_value, ";
					$sql .= "sip_profile_setting_enabled, ";
					$sql .= "sip_profile_setting_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'$sip_profile_uuid', ";
					$sql .= "'$sip_profile_setting_name', ";
					$sql .= "'$sip_profile_setting_value', ";
					$sql .= "'$sip_profile_setting_enabled', ";
					$sql .= "'$sip_profile_setting_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				//save the sip profile xml
					save_sip_profile_xml();

				//apply settings reminder
					$_SESSION["reload_xml"] = true;

				//redirect the browser
					$_SESSION["message"] = $text['message-add'];
					header("Location: sip_profile_edit.php?id=".$sip_profile_uuid);
					return;
			} //if ($action == "add")

			if ($action == "update") {
				//update the sip profile setting
					$sql = "update v_sip_profile_settings set ";
					$sql .= "sip_profile_uuid = '$sip_profile_uuid', ";
					$sql .= "sip_profile_setting_name = '$sip_profile_setting_name', ";
					$sql .= "sip_profile_setting_value = '$sip_profile_setting_value', ";
					$sql .= "sip_profile_setting_enabled = '$sip_profile_setting_enabled', ";
					$sql .= "sip_profile_setting_description = '$sip_profile_setting_description' ";
					$sql .= "where sip_profile_setting_uuid = '$sip_profile_setting_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);

				//save the sip profile xml
					save_sip_profile_xml();

				//apply settings reminder
					$_SESSION["reload_xml"] = true;

				//redirect the browser
					$_SESSION["message"] = $text['message-update'];
					header("Location: sip_profile_edit.php?id=".$sip_profile_uuid);
					return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$sip_profile_setting_uuid = $_GET["id"];
		$sql = "select * from v_sip_profile_settings ";
		$sql .= "where sip_profile_setting_uuid = '$sip_profile_setting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$sip_profile_setting_name = $row["sip_profile_setting_name"];
			$sip_profile_setting_value = $row["sip_profile_setting_value"];
			$sip_profile_setting_enabled = $row["sip_profile_setting_enabled"];
			$sip_profile_setting_description = $row["sip_profile_setting_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-setting'];

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-setting']."</b></td>\n";
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='sip_profile_edit.php?id=$sip_profile_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	//echo "Settings.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-setting_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_setting_name' maxlength='255' value=\"$sip_profile_setting_name\">\n";
	echo "<br />\n";
	echo $text['description-setting_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-setting_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_setting_value' maxlength='255' value=\"$sip_profile_setting_value\">\n";
	echo "<br />\n";
	echo $text['description-setting_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-setting_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='sip_profile_setting_enabled'>\n";
	if ($sip_profile_setting_enabled == "true") {
		echo "    <option value='true' selected >".$text['option-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['option-true']."</option>\n";
	}
	if ($sip_profile_setting_enabled == "false") {
		echo "    <option value='false' selected >".$text['option-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['option-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-setting_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-setting_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_setting_description' maxlength='255' value=\"$sip_profile_setting_description\">\n";
	echo "<br />\n";
	echo $text['description-setting_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='sip_profile_uuid' value='$sip_profile_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='sip_profile_setting_uuid' value='$sip_profile_setting_uuid'>\n";
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