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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('sip_profile_add') || permission_exists('sip_profile_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

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
	if (count($_POST)>0) {
		$sip_profile_setting_name = check_str($_POST["sip_profile_setting_name"]);
		$sip_profile_setting_value = check_str($_POST["sip_profile_setting_value"]);
		$sip_profile_setting_enabled = check_str($_POST["sip_profile_setting_enabled"]);
		$sip_profile_setting_description = check_str($_POST["sip_profile_setting_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$sip_profile_setting_uuid = check_str($_POST["sip_profile_setting_uuid"]);
	}

	//check for all required data
		//if (strlen($sip_profile_setting_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		//if (strlen($sip_profile_setting_value) == 0) { $msg .= "Please provide: Value<br>\n"; }
		//if (strlen($sip_profile_setting_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($sip_profile_setting_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
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
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=sip_profiles_edit.php?id=$sip_profile_uuid\">\n";
					echo "<div align='center'>\n";
					echo "Add Complete\n";
					echo "</div>\n";
					require_once "includes/footer.php";
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
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=sip_profiles_edit.php?id=$sip_profile_uuid\">\n";
					echo "<div align='center'>\n";
					echo "Update Complete\n";
					echo "</div>\n";
					require_once "includes/footer.php";
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
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>Setting</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='sip_profiles_edit.php?id=$sip_profile_uuid'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	//echo "Settings.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_setting_name' maxlength='255' value=\"$sip_profile_setting_name\">\n";
	echo "<br />\n";
	echo "Enter the name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Value:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_setting_value' maxlength='255' value=\"$sip_profile_setting_value\">\n";
	echo "<br />\n";
	echo "Enter the value.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='sip_profile_setting_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($sip_profile_setting_enabled == "true" || strlen($sip_profile_setting_enabled) == 0) { 
		echo "    <option value='true' selected >true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($sip_profile_setting_enabled == "false") { 
		echo "    <option value='false' selected >false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Choose to enable or disable this.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_setting_description' maxlength='255' value=\"$sip_profile_setting_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='sip_profile_uuid' value='$sip_profile_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='sip_profile_setting_uuid' value='$sip_profile_setting_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>