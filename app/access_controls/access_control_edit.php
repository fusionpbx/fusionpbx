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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.
	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('access_control_add') && !permission_exists('access_control_edit')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$access_control_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$access_control_name = $_POST["access_control_name"];
		$access_control_default = $_POST["access_control_default"];
		$access_control_description = $_POST["access_control_description"];
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	//get the primary key
		if ($action == "update") {
			$access_control_uuid = $_POST["access_control_uuid"];
		}

	//check for all required data
		$msg = '';
		if (strlen($access_control_name) == 0) { $msg .= $text['message-required']." ".$text['label-access_control_name']."<br>\n"; }
		if (strlen($access_control_default) == 0) { $msg .= $text['message-required']." ".$text['label-access_control_default']."<br>\n"; }
		//if (strlen($access_control_description) == 0) { $msg .= $text['message-required']." ".$text['label-access_control_description']."<br>\n"; }
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
			$execute = false;

			if ($action == "add" && permission_exists('access_control_add')) {
				$execute = true;
				$access_control_uuid = uuid();

				//add the message
				message::add($text['message-add']);
			}

			if ($action == "update" && permission_exists('access_control_edit')) {
				$execute = true;
				//$access_control_uuid //already set

				//add the message
				message::add($text['message-update']);
			}

			if ($execute) {
				$array['access_controls'][0]['access_control_uuid'] = $access_control_uuid;
				$array['access_controls'][0]['access_control_name'] = $access_control_name;
				$array['access_controls'][0]['access_control_default'] = $access_control_default;
				$array['access_controls'][0]['access_control_description'] = $access_control_description;
				$database = new database;
				$database->app_name = 'access_control';
				$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
				$database->save($array);
				unset($array);

				//clear the cache
				$cache = new cache;
				$cache->delete("configuration:acl.conf");

				//create the event socket connection
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) { event_socket_request($fp, "api reloadacl"); }
			}

			//redirect the user
			header("Location: access_controls.php");
			return;

		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true" && is_uuid($_GET["id"])) {
		$access_control_uuid = $_GET["id"];
		$sql = "select * from v_access_controls ";
		$sql .= "where access_control_uuid = :access_control_uuid ";
		$parameters['access_control_uuid'] = $access_control_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row)) {
			$access_control_name = $row["access_control_name"];
			$access_control_default = $row["access_control_default"];
			$access_control_description = $row["access_control_description"];
		}
		unset ($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-access_control']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='access_controls.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-access_control_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='access_control_name' maxlength='255' value=\"".escape($access_control_name)."\">\n";
	echo "<br />\n";
	echo $text['description-access_control_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-access_control_default']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='access_control_default'>\n";
	echo "	<option value=''></option>\n";
	if ($access_control_default == "allow") {
		echo "	<option value='allow' selected='selected'>".$text['label-allow']."</option>\n";
	}
	else {
		echo "	<option value='allow'>".$text['label-allow']."</option>\n";
	}
	if ($access_control_default == "deny") {
		echo "	<option value='deny' selected='selected'>".$text['label-deny']."</option>\n";
	}
	else {
		echo "	<option value='deny'>".$text['label-deny']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-access_control_default']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-access_control_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='access_control_description' maxlength='255' value=\"".escape($access_control_description)."\">\n";
	echo "<br />\n";
	echo $text['description-access_control_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='access_control_uuid' value='".escape($access_control_uuid)."'>\n";
	}
	echo "				<br><input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

	if ($action == "update") {
		require "access_control_nodes.php";
		echo "<br><br>";
	}

//include the footer
	require_once "resources/footer.php";

?>
