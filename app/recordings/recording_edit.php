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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('recording_add') || permission_exists('recording_edit')) {
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

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$recording_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the form value and set to php variables
	if (count($_POST)>0) {
		$recording_filename = check_str($_POST["recording_filename"]);
		$recording_name = check_str($_POST["recording_name"]);
		//$recording_uuid = check_str($_POST["recording_uuid"]);
		$recording_description = check_str($_POST["recording_description"]);

		//clean the recording filename and name
		$recording_filename = str_replace(" ", "_", $recording_filename);
		$recording_filename = str_replace("'", "", $recording_filename);
		$recording_name = str_replace(" ", "_", $recording_name);
		$recording_name = str_replace("'", "", $recording_name);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$recording_uuid = check_str($_POST["recording_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($recording_filename) == 0) { $msg .= $text['label-edit-file']."<br>\n"; }
		if (strlen($recording_name) == 0) { $msg .= $text['label-edit-recording']."<br>\n"; }
		//if (strlen($recording_uuid) == 0) { $msg .= "Please provide: recording_uuid<br>\n"; }
		//if (strlen($recording_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
		if ($action == "add" && permission_exists('recording_add')) {
			$recording_uuid = uuid();
			$sql = "insert into v_recordings ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "recording_uuid, ";
			$sql .= "recording_filename, ";
			$sql .= "recording_name, ";
			$sql .= "recording_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$recording_uuid', ";
			$sql .= "'$recording_filename', ";
			$sql .= "'$recording_name', ";
			$sql .= "'$recording_description' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-add'];
			header("Location: recordings.php");
			return;
		} //if ($action == "add")

		if ($action == "update" && permission_exists('recording_edit')) {
			//get the original filename
				$sql = "select * from v_recordings ";
				$sql .= "where recording_uuid = '$recording_uuid' ";
				$sql .= "and domain_uuid = '$domain_uuid' ";
				//echo "sql: ".$sql."<br />\n";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$recording_filename_orig = $row["recording_filename"];
					break; //limit to 1 row
				}
				unset ($prep_statement);

			//if file name is not the same then rename the file
				if ($recording_filename != $recording_filename_orig) {
					//echo "orig: ".$_SESSION['switch']['recordings']['dir'].'/'.$recording_filename_orig."<br />\n";
					//echo "new: ".$_SESSION['switch']['recordings']['dir'].'/'.$recording_filename."<br />\n";
					rename($_SESSION['switch']['recordings']['dir'].'/'.$recording_filename_orig, $_SESSION['switch']['recordings']['dir'].'/'.$recording_filename);
				}

			//update the database with the new data
				$sql = "update v_recordings set ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "recording_filename = '$recording_filename', ";
				$sql .= "recording_name = '$recording_name', ";
				//$sql .= "recording_uuid = '$recording_uuid', ";
				$sql .= "recording_description = '$recording_description' ";
				$sql .= "where domain_uuid = '$domain_uuid'";
				$sql .= "and recording_uuid = '$recording_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

			$_SESSION["message"] = $text['message-update'];
			header("Location: recordings.php");
			return;
		} //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$recording_uuid = $_GET["id"];
		$sql = "select * from v_recordings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and recording_uuid = '$recording_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$recording_filename = $row["recording_filename"];
			$recording_name = $row["recording_name"];
			//$recording_uuid = $row["recording_uuid"];
			$recording_description = $row["recording_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

	echo "<form method='post' name='frm' action=''>\n";

	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap><b>".$text['title-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap><b>".$text['title-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='recordings.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-recording_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='recording_name' maxlength='255' value=\"$recording_name\">\n";
	echo "<br />\n";
	echo $text['description-recording']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-file_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='recording_filename' maxlength='255' value=\"$recording_filename\">\n";
	echo "<br />\n";
	echo $text['message-file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	//echo "    recording_uuid:\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "    <input class='formfld' type='text' name='recording_uuid' maxlength='255' value=\"$recording_uuid\">\n";
	//echo "<br />\n";
	//echo "\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='recording_description' maxlength='255' value=\"$recording_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='recording_uuid' value='$recording_uuid'>\n";
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