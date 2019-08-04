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
if (permission_exists('device_add') || permission_exists('device_edit')) {
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
		$device_line_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (is_uuid($_GET["device_uuid"])) {
		$device_uuid = $_GET["device_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$line_number = $_POST["line_number"];
		$server_address = $_POST["server_address"];
		$outbound_proxy = $_POST["outbound_proxy"];
		$sip_port = $_POST["sip_port"];
		$sip_transport = $_POST["sip_transport"];
		$register_expires = $_POST["register_expires"];
		$display_name = $_POST["display_name"];
		$user_id = $_POST["user_id"];
		$auth_id = $_POST["auth_id"];
		$password = $_POST["password"];
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$device_line_uuid = $_POST["device_line_uuid"];
	}

	//check for all required data
		//if (strlen($line_number) == 0) { $msg .= $text['message-required']." ".$text['label-line_number']."<br>\n"; }
		//if (strlen($server_address) == 0) { $msg .= $text['message-required']." ".$text['label-server_address']."<br>\n"; }
		//if (strlen($outbound_proxy) == 0) { $msg .= $text['message-required']." ".$text['label-outbound_proxy']."<br>\n"; }
		//if (strlen($display_name) == 0) { $msg .= $text['message-required']." ".$text['label-display_name']."<br>\n"; }
		//if (strlen($user_id) == 0) { $msg .= $text['message-required']." ".$text['label-user_id']."<br>\n"; }
		//if (strlen($auth_id) == 0) { $msg .= $text['message-required']." ".$text['label-auth_id']."<br>\n"; }
		//if (strlen($password) == 0) { $msg .= $text['message-required']." ".$text['label-password']."<br>\n"; }
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
			//add the line
				if ($action == "add" && permission_exists('device_add')) {
					$array['device_lines'][0]['device_line_uuid'] = uuid();
					$array['device_lines'][0]['sip_port'] = $sip_port;
					$array['device_lines'][0]['register_expires'] = $register_expires;

					message::add($text['message-add']);
				}

			//update the line
				if ($action == "update" && permission_exists('device_edit')) {
					$array['device_lines'][0]['device_line_uuid'] = $device_line_uuid;
					$array['device_lines'][0]['sip_port'] = $sip_port != '' ? $sip_port : null;
					$array['device_lines'][0]['register_expires'] = $register_expires != '' ? $register_expires : null;

					message::add($text['message-update']);
				}

			//execute
				if (is_array($array) && @sizeof($array) != 0) {
					$array['device_lines'][0]['domain_uuid'] = $domain_uuid;
					$array['device_lines'][0]['device_uuid'] = $device_uuid;
					$array['device_lines'][0]['line_number'] = $line_number;
					$array['device_lines'][0]['server_address'] = $server_address;
					$array['device_lines'][0]['outbound_proxy'] = $outbound_proxy;
					$array['device_lines'][0]['sip_transport'] = $sip_transport;
					$array['device_lines'][0]['display_name'] = $display_name;
					$array['device_lines'][0]['user_id'] = $user_id;
					$array['device_lines'][0]['auth_id'] = $auth_id;
					$array['device_lines'][0]['password'] = $password;

					$database = new database;
					$database->app_name = 'devices';
					$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
					$database->save($array);
					unset($array);
				}

			header("Location: device_edit.php?id=".$device_uuid);
			exit;
		}
}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$device_line_uuid = $_GET["id"];
		$sql = "select * from v_device_lines ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and device_line_uuid = :device_line_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['device_line_uuid'] = $device_line_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$line_number = $row["line_number"];
			$server_address = $row["server_address"];
			$outbound_proxy = $row["outbound_proxy"];
			$sip_port = $row["sip_port"];
			$sip_transport = $row["sip_transport"];
			$register_expires = $row["register_expires"];
			$display_name = $row["display_name"];
			$user_id = $row["user_id"];
			$auth_id = $row["auth_id"];
			$password = $row["password"];
		}
		unset($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-device_line']."</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='device_edit.php?id=$device_uuid'\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-line_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' style='width: 45px;' name='line_number'>\n";
	if (is_numeric($line_number)) {
		echo "	<option value='".escape($line_number)."' selected='selected'>".escape($line_number)."</option>\n";
	}
	echo "		<option value=''></option>\n";
	for ($n = 1; $n <= 32; $n++) {
		echo "	<option value='".$n."'>".$n."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-line_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-server_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='server_address' maxlength='255' value=\"".escape($server_address)."\">\n";
	echo "<br />\n";
	echo $text['description-server_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-outbound_proxy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='outbound_proxy' maxlength='255' value=\"".escape($outbound_proxy)."\">\n";
	echo "<br />\n";
	echo $text['description-outbound_proxy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-display_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='display_name' maxlength='255' value=\"".escape($display_name)."\">\n";
	echo "<br />\n";
	echo $text['description-display_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-user_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='user_id' maxlength='255' value=\"".escape($user_id)."\">\n";
	echo "<br />\n";
	echo $text['description-user_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-auth_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='auth_id' maxlength='255' value=\"".escape($auth_id)."\">\n";
	echo "<br />\n";
	echo $text['description-auth_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='password' name='password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value=\"".escape($password),"\">\n";
	echo "<br />\n";
	echo $text['description-password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_port' maxlength='255' value=\"".escape($sip_port)."\">\n";
	echo "<br />\n";
	echo $text['description-sip_port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_transport']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_transport' maxlength='255' value=\"".escape($sip_transport)."\">\n";
	echo "<br />\n";
	echo $text['description-sip_transport']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-register_expires']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='register_expires' maxlength='255' value=\"".escape($register_expires)."\">\n";
	echo "<br />\n";
	echo $text['description-register_expires']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='device_uuid' value='$device_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='device_line_uuid' value='$device_line_uuid'>\n";
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
