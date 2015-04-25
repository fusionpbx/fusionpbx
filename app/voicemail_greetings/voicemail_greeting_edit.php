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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('voicemail_greeting_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get greeting id
	if (isset($_REQUEST["id"])) {
		$voicemail_greeting_uuid = check_str($_REQUEST["id"]);
	}

//get the form value and set to php variables
	$voicemail_id = check_str($_REQUEST["voicemail_id"]);
	if (count($_POST) > 0) {
		$greeting_name = check_str($_POST["greeting_name"]);
		$greeting_description = check_str($_POST["greeting_description"]);

		//clean the name
		$greeting_name = str_replace("'", "", $greeting_name);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	//get greeting uuid to edit
		$voicemail_greeting_uuid = check_str($_POST["voicemail_greeting_uuid"]);

	//check for all required data
		$msg = '';
		if (strlen($greeting_name) == 0) { $msg .= "".$text['confirm-name']."<br>\n"; }
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

	//update the database
	if ($_POST["persistformvar"] != "true") {
		if (permission_exists('voicemail_greeting_edit')) {
			//update the database with the new data
				$sql = "update v_voicemail_greetings set ";
				$sql .= "greeting_name = '".$greeting_name."', ";
				$sql .= "greeting_description = '".$greeting_description."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and voicemail_greeting_uuid = '".$voicemail_greeting_uuid."' ";
				$db->exec(check_sql($sql));
				unset($sql);

			//redirect the user
				$_SESSION["message"] = $text['message-update'];
				header("Location: voicemail_greetings.php?id=".$voicemail_id);
				return;
		} //if (permission_exists('voicemail_greeting_edit')) {
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$voicemail_greeting_uuid = check_str($_GET["id"]);
		$sql = "select * from v_voicemail_greetings ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and voicemail_greeting_uuid = '".$voicemail_greeting_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$greeting_name = $row["greeting_name"];
			$greeting_description = $row["greeting_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	$document['title'] = $text['label-edit'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";

	echo "<table cellpadding='0' cellspacing='0' border='0' align='right'>\n";
	echo "<tr>\n";
	echo "<td nowrap='nowrap'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='voicemail_greetings.php?id=".$voicemail_id."'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<b>".$text['label-edit']."</b>\n";
	echo "<br><br>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='greeting_name' maxlength='255' value=\"$greeting_name\">\n";
	echo "<br />\n";
	echo "".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='greeting_description' maxlength='255' value=\"$greeting_description\">\n";
	echo "<br />\n";
	echo "".$text['description-info']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='voicemail_greeting_uuid' value='".$voicemail_greeting_uuid."'>\n";
	echo "			<input type='hidden' name='voicemail_id' value='".$voicemail_id."'>\n";
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