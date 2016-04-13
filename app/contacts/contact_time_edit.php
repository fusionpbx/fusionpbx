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
if (permission_exists('contact_time_edit') || permission_exists('contact_time_add')) {
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
		$contact_time_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the contact uuid
	if (strlen($_GET["contact_uuid"]) > 0) {
		$contact_uuid = check_str($_GET["contact_uuid"]);
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$time_start = check_str($_POST["time_start"]);
		$time_stop = check_str($_POST["time_stop"]);
		$time_description = check_str($_POST["time_description"]);
	}

//process the form data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the uuid
			if ($action == "update") {
				$contact_time_uuid = check_str($_POST["contact_time_uuid"]);
			}

		//check for all required data
			$msg = '';
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

				//update last modified
				$sql = "update v_contacts set ";
				$sql .= "last_mod_date = now(), ";
				$sql .= "last_mod_user = '".$_SESSION['username']."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and contact_uuid = '".$contact_uuid."' ";
				$db->exec(check_sql($sql));
				unset($sql);

				if ($action == "add") {
					$contact_time_uuid = uuid();
					$sql = "insert into v_contact_times ";
					$sql .= "( ";
					$sql .= "domain_uuid, ";
					$sql .= "contact_time_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "user_uuid, ";
					$sql .= "time_start, ";
					$sql .= "time_stop, ";
					$sql .= "time_description ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "( ";
					$sql .= "'".$domain_uuid."', ";
					$sql .= "'".$contact_time_uuid."', ";
					$sql .= "'".$contact_uuid."', ";
					$sql .= "'".$_SESSION["user"]["user_uuid"]."', ";
					$sql .= "'".$time_start."', ";
					$sql .= "'".$time_stop."', ";
					$sql .= "'".$time_description."' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

					$_SESSION["message"] = $text['message-add'];
					header("Location: contact_edit.php?id=".$contact_uuid);
					return;
				} //if ($action == "add")

				if ($action == "update") {
					$sql = "update v_contact_times ";
					$sql .= "set ";
					$sql .= "time_start = '".$time_start."', ";
					$sql .= "time_stop = '".$time_stop."', ";
					$sql .= "time_description = '".$time_description."' ";
					$sql .= "where ";
					$sql .= "contact_time_uuid = '".$contact_time_uuid."' ";
					$sql .= "and domain_uuid = '".$domain_uuid."' ";
					$sql .= "and contact_uuid = '".$contact_uuid."' ";
					$sql .= "and user_uuid = '".$_SESSION["user"]["user_uuid"]."' ";
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
		$contact_time_uuid = $_GET["id"];
		$sql = "select ct.*, u.username ";
		$sql .= "from v_contact_times as ct, v_users as u ";
		$sql .= "where ct.user_uuid = u.user_uuid ";
		$sql .= "and ct.domain_uuid = '".$domain_uuid."' ";
		$sql .= "and ct.contact_uuid = '".$contact_uuid."' ";
		$sql .= "and ct.user_uuid = '".$_SESSION["user"]["user_uuid"]."' ";
		$sql .= "and contact_time_uuid = '".$contact_time_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetch(PDO::FETCH_NAMED);
		$time_start = $result["time_start"];
		$time_stop = $result["time_stop"];
		$time_description = $result["time_description"];
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-contact_time_edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_time_add'];
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' valign='top' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-contact_time_edit'];
	}
	else if ($action == "add") {
		echo $text['header-contact_time_add'];
	}
	echo "</b></td>\n";
	echo "<td align='right' valign='top'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_edit.php?id=$contact_uuid'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-time_start']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld datetimepicker' type='text' name='time_start' id='time_start' style='min-width: 135px; width: 135px;' value='".$time_start."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-time_stop']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld datetimepicker' type='text' name='time_stop' id='time_stop' style='min-width: 135px; width: 135px;' value='".$time_stop."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-time_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <textarea class='formfld' type='text' name='time_description' id='time_description' style='width: 400px; height: 100px;'>".$time_description."</textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>\n";
	echo "			<input type='hidden' name='contact_uuid' value='".$contact_uuid."'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='contact_time_uuid' value='".$contact_time_uuid."'>\n";
	}
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	//apply bootstrap-datetimepicker WITH seconds
		echo "<script language='JavaScript' type='text/javascript'>";
		echo "	$(document).ready(function() {\n";
		echo "		$(function() {\n";
		echo "			$('.datetimepicker').datetimepicker({\n";
		echo "				format: 'YYYY-MM-DD HH:mm:ss',\n";
		echo "			});\n";
		echo "		});\n";
		echo "	});\n";
		echo "</script>\n";

//include the footer
	require_once "resources/footer.php";
?>
