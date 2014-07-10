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
if (permission_exists('contact_edit')) {
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
		$contact_note_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["contact_uuid"]) > 0) {
	$contact_uuid = check_str($_GET["contact_uuid"]);
}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$contact_note = check_str($_POST["contact_note"]);
		$last_mod_date = check_str($_POST["last_mod_date"]);
		$last_mod_user = check_str($_POST["last_mod_user"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$contact_note_uuid = check_str($_POST["contact_note_uuid"]);
	}

	//check for all required data
		//if (strlen($contact_note) == 0) { $msg .= $text['message-required'].$text['label-contact_note']."<br>\n"; }
		//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
		//if (strlen($last_mod_date) == 0) { $msg .= $text['message-required']."Last Modified Date<br>\n"; }
		//if (strlen($last_mod_user) == 0) { $msg .= $text['message-required']."Last Modified By<br>\n"; }
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
			$contact_note_uuid = uuid();
			$sql = "insert into v_contact_notes ";
			$sql .= "(";
			$sql .= "contact_note_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "contact_note, ";
			$sql .= "domain_uuid, ";
			$sql .= "last_mod_date, ";
			$sql .= "last_mod_user ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$contact_note_uuid', ";
			$sql .= "'$contact_uuid', ";
			$sql .= "'$contact_note', ";
			$sql .= "'$domain_uuid', ";
			$sql .= "now(), ";
			$sql .= "'".$_SESSION['username']."' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-add'];
			header("Location: contact_edit.php?id=".$contact_uuid);
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_contact_notes set ";
			$sql .= "contact_uuid = '$contact_uuid', ";
			$sql .= "contact_note = '$contact_note', ";
			$sql .= "last_mod_date = now(), ";
			$sql .= "last_mod_user = '".$_SESSION['username']."' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and contact_note_uuid = '$contact_note_uuid'";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-update'];
			header("Location: contact_edit.php?id=".$contact_uuid);
			return;
		} //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$contact_note_uuid = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_contact_notes ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and contact_note_uuid = '$contact_note_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$contact_note = $row["contact_note"];
			$last_mod_date = $row["last_mod_date"];
			$last_mod_user = $row["last_mod_user"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-contact_notes-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_notes-add'];
	}

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
	if ($action == "add") {
		echo "<td align='left' width='15%' nowrap='nowrap'><b>".$text['header-contact_notes-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='15%' nowrap='nowrap'><b>".$text['header-contact_notes-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_edit.php?id=$contact_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_note'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <textarea class='formfld' type='text' rows=\"20\" style=\"width: 100%\" name='contact_note'>$contact_note</textarea>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='contact_uuid' value='$contact_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='contact_note_uuid' value='$contact_note_uuid'>\n";
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

//include the footer
	require_once "resources/footer.php";
?>