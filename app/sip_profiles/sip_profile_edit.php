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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
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

//toggle enabled state
	if ($_REQUEST['spid'] != '' && $_REQUEST['spsid'] != '' && $_REQUEST['enabled'] != '') {
		$sql = "update v_sip_profile_settings set ";
		$sql .= "sip_profile_setting_enabled = '".check_str($_REQUEST['enabled'])."' ";
		$sql .= "where sip_profile_setting_uuid = '".check_str($_REQUEST['spsid'])."' ";
		$sql .= "and sip_profile_uuid = '".check_str($_REQUEST['spid'])."' ";
		$db->exec(check_sql($sql));
		unset($sql);

		//save the sip profile xml
		save_sip_profile_xml();

		//apply settings reminder
		$_SESSION["reload_xml"] = true;

		$_SESSION["message"] = $text['message-update'];
		header("Location: sip_profile_edit.php?id=".$_REQUEST['spid']);
		exit;
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$sip_profile_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$sip_profile_name = check_str($_POST["sip_profile_name"]);
		$sip_profile_hostname = check_str($_POST["sip_profile_hostname"]);
		$sip_profile_description = check_str($_POST["sip_profile_description"]);
		$sip_profile_enabled = check_str($_POST["sip_profile_enabled"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$sip_profile_uuid = check_str($_POST["sip_profile_uuid"]);
	}

	//check for all required data
		//if (strlen($sip_profile_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		//if (strlen($sip_profile_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
			//add the sip profile
				if ($action == "add") {
					$sql = "insert into v_sip_profiles ";
					$sql .= "(";
					$sql .= "sip_profile_uuid, ";
					$sql .= "sip_profile_name, ";
					$sql .= "sip_profile_hostname, ";
					$sql .= "sip_profile_description, ";
					$sql .= "sip_profile_enabled ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'$sip_profile_name', ";
					if (strlen($sip_profile_hostname) > 0) {
						$sql .= "'$sip_profile_hostname', ";
					}
					else {
						$sql .= "null, ";
					}
					$sql .= "'$sip_profile_description', ";
					$sql .= "'$sip_profile_enabled' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "add")

			//update the sip profile
				if ($action == "update") {
					$sql = "update v_sip_profiles set ";
					$sql .= "sip_profile_name = '$sip_profile_name', ";
					if (strlen($sip_profile_hostname) > 0) {
						$sql .= "sip_profile_hostname = '$sip_profile_hostname', ";
					}
					else {
						$sql .= "sip_profile_hostname = null, ";
					}
					$sql .= "sip_profile_description = '$sip_profile_description', ";
					$sql .= "sip_profile_enabled = '$sip_profile_enabled' ";
					$sql .= "where sip_profile_uuid = '$sip_profile_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "update")

			//get the hostname
				if ($sip_profile_name == nul) {
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_cmd = "hostname";
						$sip_profile_hostname = event_socket_request($fp, 'api '.$switch_cmd);
					}
				}

			//clear the cache
				$cache = new cache;
				$cache->delete("configuration:sofia.conf:".$sip_profile_hostname);

			//redirect the browser
				$_SESSION["message"] = $text['message-update'];
				header("Location: sip_profiles.php");
				return;
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$sip_profile_uuid = $_GET["id"];
		$sql = "select * from v_sip_profiles ";
		$sql .= "where sip_profile_uuid = '$sip_profile_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$sip_profile_name = $row["sip_profile_name"];
			$sip_profile_hostname = $row["sip_profile_hostname"];
			$sip_profile_description = $row["sip_profile_description"];
			$sip_profile_enabled = $row["sip_profile_enabled"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-sip_profile'];

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-sip_profile']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='sip_profiles.php'\" value='".$text['button-back']."'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"var name = prompt('".$text['confirm-copy']."'); if (name != null) { window.location='sip_profile_copy.php?id=".$sip_profile_uuid."&name=' + name; }\" value='".$text['button-copy']."'>\n";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo $text['description-sip_profiles']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_name' maxlength='255' value=\"$sip_profile_name\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-hostname']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_hostname' maxlength='255' value=\"$sip_profile_hostname\">\n";
	echo "<br />\n";
	echo $text['description-hostname']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='sip_profile_enabled'>\n";
	echo "    	<option value='true' ".(($sip_profile_enabled == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
	echo "    	<option value='false' ".(($sip_profile_enabled == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
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
	echo "	<textarea class='formfld' style='width: 300px;' name='sip_profile_description' rows='4'>$sip_profile_description</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='sip_profile_uuid' value='$sip_profile_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	if ($action == "update") {
		require "sip_profile_settings.php";
	}

//include the footer
	require_once "resources/footer.php";
?>
