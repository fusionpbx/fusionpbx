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
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$device_line_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (strlen($_GET["device_uuid"]) > 0) {
		$device_uuid = check_str($_GET["device_uuid"]);
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$line_number = check_str($_POST["line_number"]);
		$server_address = check_str($_POST["server_address"]);
		$outbound_proxy = check_str($_POST["outbound_proxy"]);
		$sip_port = check_str($_POST["sip_port"]);
		$sip_transport = check_str($_POST["sip_transport"]);
		$register_expires = check_str($_POST["register_expires"]);
		$display_name = check_str($_POST["display_name"]);
		$user_id = check_str($_POST["user_id"]);
		$auth_id = check_str($_POST["auth_id"]);
		$password = check_str($_POST["password"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$device_line_uuid = check_str($_POST["device_line_uuid"]);
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
					$sql = "insert into v_device_lines ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "device_line_uuid, ";
					$sql .= "device_uuid, ";
					$sql .= "line_number, ";
					$sql .= "server_address, ";
					$sql .= "outbound_proxy, ";
					$sql .= "sip_port, ";
					$sql .= "sip_transport, ";
					$sql .= "register_expires, ";
					$sql .= "display_name, ";
					$sql .= "user_id, ";
					$sql .= "auth_id, ";
					$sql .= "password ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'$device_uuid', ";
					$sql .= "'$line_number', ";
					$sql .= "'$server_address', ";
					$sql .= "'$outbound_proxy', ";
					$sql .= "'$sip_port', ";
					$sql .= "'$sip_transport', ";
					$sql .= "'$register_expires', ";
					$sql .= "'$display_name', ";
					$sql .= "'$user_id', ";
					$sql .= "'$auth_id', ";
					$sql .= "'$password' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				} //if ($action == "add")

			//update the line
				if ($action == "update" && permission_exists('device_edit')) {
					$sql = "update v_device_lines set ";
					$sql .= "device_uuid = '$device_uuid', ";
					$sql .= "line_number = '$line_number', ";
					$sql .= "server_address = '$server_address', ";
					$sql .= "outbound_proxy = '$outbound_proxy', ";
					if (strlen($sip_port) > 0) {
						$sql .= "sip_port = '$sip_port', ";
					}
					else {
						$sql .= "sip_port = null, ";
					}
					$sql .= "sip_transport = '$sip_transport', ";
					if (strlen($register_expires) > 0) {
						$sql .= "register_expires = '$register_expires', ";
					}
					else {
						$sql .= "register_expires = null, ";
					}
					$sql .= "display_name = '$display_name', ";
					$sql .= "user_id = '$user_id', ";
					$sql .= "auth_id = '$auth_id', ";
					$sql .= "password = '$password' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and device_line_uuid = '$device_line_uuid' ";
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
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$device_line_uuid = check_str($_GET["id"]);
		$sql = "select * from v_device_lines ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and device_line_uuid = '$device_line_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
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
		unset ($prep_statement);
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
	echo "				<select class='formfld' style='width: 45px;' name='line_number'>\n";
	echo "				<option value='$line_number' SELECTED='SELECTED'>$line_number</option>\n";
	echo "				<option value=''></option>\n";
	echo "				<option value='1'>1</option>\n";
	echo "				<option value='2'>2</option>\n";
	echo "				<option value='3'>3</option>\n";
	echo "				<option value='4'>4</option>\n";
	echo "				<option value='5'>5</option>\n";
	echo "				<option value='6'>6</option>\n";
	echo "				<option value='7'>7</option>\n";
	echo "				<option value='8'>8</option>\n";
	echo "				<option value='9'>9</option>\n";
	echo "				<option value='10'>10</option>\n";
	echo "				<option value='11'>11</option>\n";
	echo "				<option value='12'>12</option>\n";
	echo "				</select>\n";
	echo "<br />\n";
	echo $text['description-line_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-server_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='server_address' maxlength='255' value=\"$server_address\">\n";
	echo "<br />\n";
	echo $text['description-server_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-outbound_proxy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='outbound_proxy' maxlength='255' value=\"$outbound_proxy\">\n";
	echo "<br />\n";
	echo $text['description-outbound_proxy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-display_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='display_name' maxlength='255' value=\"$display_name\">\n";
	echo "<br />\n";
	echo $text['description-display_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-user_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='user_id' maxlength='255' value=\"$user_id\">\n";
	echo "<br />\n";
	echo $text['description-user_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-auth_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='auth_id' maxlength='255' value=\"$auth_id\">\n";
	echo "<br />\n";
	echo $text['description-auth_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='password' name='password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value=\"$password\">\n";
	echo "<br />\n";
	echo $text['description-password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_port' maxlength='255' value=\"$sip_port\">\n";
	echo "<br />\n";
	echo $text['description-sip_port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_transport']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_transport' maxlength='255' value=\"$sip_transport\">\n";
	echo "<br />\n";
	echo $text['description-sip_transport']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-register_expires']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='register_expires' maxlength='255' value=\"$register_expires\">\n";
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