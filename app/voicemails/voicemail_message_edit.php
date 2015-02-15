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
if (permission_exists('voicemail_message_add') || permission_exists('voicemail_message_edit')) {
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
		$voicemail_message_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (strlen($_GET["voicemail_uuid"]) > 0) {
		$voicemail_uuid = check_str($_GET["voicemail_uuid"]);
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$voicemail_uuid = check_str($_POST["voicemail_uuid"]);
		$created_epoch = check_str($_POST["created_epoch"]);
		$read_epoch = check_str($_POST["read_epoch"]);
		$caller_id_name = check_str($_POST["caller_id_name"]);
		$caller_id_number = check_str($_POST["caller_id_number"]);
		$message_length = check_str($_POST["message_length"]);
		$message_status = check_str($_POST["message_status"]);
		$message_priority = check_str($_POST["message_priority"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$voicemail_message_uuid = check_str($_POST["voicemail_message_uuid"]);
	}

	//check for all required data
		//if (strlen($voicemail_uuid) == 0) { $msg .= "Please provide: Voicemail UUID<br>\n"; }
		//if (strlen($created_epoch) == 0) { $msg .= "Please provide: Created Epoch<br>\n"; }
		//if (strlen($read_epoch) == 0) { $msg .= "Please provide: Read Epoch<br>\n"; }
		//if (strlen($caller_id_name) == 0) { $msg .= "Please provide: Caller ID Name<br>\n"; }
		//if (strlen($caller_id_number) == 0) { $msg .= "Please provide: Caller ID Number<br>\n"; }
		//if (strlen($message_length) == 0) { $msg .= "Please provide: Length<br>\n"; }
		//if (strlen($message_status) == 0) { $msg .= "Please provide: Status<br>\n"; }
		//if (strlen($message_priority) == 0) { $msg .= "Please provide: Priority<br>\n"; }
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
			if ($action == "add" && permission_exists('voicemail_message_add')) {
				$sql = "insert into v_voicemail_messages ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "voicemail_message_uuid, ";
				$sql .= "voicemail_uuid, ";
				$sql .= "created_epoch, ";
				$sql .= "read_epoch, ";
				$sql .= "caller_id_name, ";
				$sql .= "caller_id_number, ";
				$sql .= "message_length, ";
				$sql .= "message_status, ";
				$sql .= "message_priority ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$voicemail_uuid', ";
				$sql .= "'$created_epoch', ";
				$sql .= "'$read_epoch', ";
				$sql .= "'$caller_id_name', ";
				$sql .= "'$caller_id_number', ";
				$sql .= "'$message_length', ";
				$sql .= "'$message_status', ";
				$sql .= "'$message_priority' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-add'];
				header("Location: voicemail_edit.php?id=".$voicemail_uuid);
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('voicemail_message_edit')) {
				$sql = "update v_voicemail_messages set ";
				$sql .= "voicemail_uuid = '$voicemail_uuid', ";
				$sql .= "voicemail_uuid = '$voicemail_uuid', ";
				$sql .= "created_epoch = '$created_epoch', ";
				$sql .= "read_epoch = '$read_epoch', ";
				$sql .= "caller_id_name = '$caller_id_name', ";
				$sql .= "caller_id_number = '$caller_id_number', ";
				$sql .= "message_length = '$message_length', ";
				$sql .= "message_status = '$message_status', ";
				$sql .= "message_priority = '$message_priority' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and voicemail_message_uuid = '$voicemail_message_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-update'];
				header("Location: voicemail_edit.php?id=".$voicemail_uuid);
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$voicemail_message_uuid = check_str($_GET["id"]);
		$sql = "select * from v_voicemail_messages ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and voicemail_message_uuid = '$voicemail_message_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$voicemail_uuid = $row["voicemail_uuid"];
			$created_epoch = $row["created_epoch"];
			$read_epoch = $row["read_epoch"];
			$caller_id_name = $row["caller_id_name"];
			$caller_id_number = $row["caller_id_number"];
			$message_length = $row["message_length"];
			$message_status = $row["message_status"];
			$message_priority = $row["message_priority"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>Voicemail Messages</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='voicemail_edit.php?id=$voicemail_uuid'\" value='Back'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Created Epoch\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='created_epoch' maxlength='255' value='$created_epoch'>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Read Epoch\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='read_epoch' maxlength='255' value='$read_epoch'>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Caller ID Name\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='caller_id_name' maxlength='255' value=\"$caller_id_name\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Caller ID Number\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='caller_id_number' maxlength='255' value=\"$caller_id_number\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Length\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='message_length' maxlength='255' value='$message_length'>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Status\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='message_status' maxlength='255' value=\"$message_status\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Priority\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='message_priority' maxlength='255' value=\"$message_priority\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='voicemail_uuid' value='$voicemail_uuid'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='voicemail_message_uuid' value='$voicemail_message_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>