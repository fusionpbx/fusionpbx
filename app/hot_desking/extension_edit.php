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
	Copyright (C) 2008-2015 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('extension_add') || permission_exists('extension_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$extension_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http values and set them as php variables
	if (count($_POST) > 0) {
		//get the values from the HTTP POST and save them as PHP variables
		$extension_uuid = check_str($_POST["extension_uuid"]);
		$unique_id = check_str($_POST["unique_id"]);
		$vm_password = check_str($_POST["vm_password"]);
		$dial_string = check_str($_POST["dial_string"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	//check for all required data
		if (strlen($extension_uuid) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
		if (strlen($unique_id) == 0) { $msg .= $text['message-required'].$text['label-unique_id']."<br>\n"; }
	//get the number of rows in v_extensions
		$sql = "select count(*) as num_rows from v_extensions ";
		$sql .= "where unique_id = '".$unique_id."' and ";
		$sql .= "extension_uuid <> '".$extension_uuid."'";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] > 0) {
				$msg .= $text['message-unique']."<br>\n";
			}
		}
		unset($prep_statement, $result);
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

	//set the default user context
		if (if_group("superadmin")) {
			//allow a user assigned to super admin to change the user_context
		}
		else {
			//if the user_context was not set then set the default value
			if (strlen($user_context) == 0) {
				if (count($_SESSION["domains"]) > 1) {
					$user_context = $_SESSION['domain_name'];
				}
				else {
					$user_context = "default";
				}
			}
		}

	//add or update the database
	if ($_POST["persistformvar"] != "true") {

		//get the extension
			$sql = "select * from v_extensions ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and extension_uuid = '$extension_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$extension = $row["extension"];
				$number_alias = $row["number_alias"];
			}
			unset ($prep_statement);

		//update the extension and voicemail
			if (($action == "add" && permission_exists('extension_add')) || ($action == "update" && permission_exists('extension_edit'))) {
				//update the extension
					$sql = "update v_extensions set ";
					$sql .= "unique_id = '$unique_id' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and extension_uuid = '$extension_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);

				//update the voicemail
					if (strlen($vm_password) > 0) {
						$sql = "update v_voicemails set ";
						$sql .= "voicemail_password = '$vm_password' ";
						$sql .= "where domain_uuid = '$domain_uuid' ";
						if (is_numeric($extension)) {
							$sql .= "and voicemail_id = '$extension'";
						}
						else {
							$sql .= "and voicemail_id = '$number_alias'";
						}
						$db->exec(check_sql($sql));
						unset($sql);
					}
			}

		//clear the cache
			$cache = new cache;
			$cache->delete("directory:".$extension."@".$_SESSION['domain_name']);

		//set message and redirect user
			if ($action == "add") {
				$_SESSION["message"] = $text['message-add'];
			}
			if ($action == "update") {
				$_SESSION["message"] = $text['message-update'];
			}
			header("Location: index.php");
			return;

	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if ($_POST["persistformvar"] != "true") {
		//get the extension data
			$sql = "select * from v_extensions ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and extension_uuid = '$extension_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$extension = $row["extension"];
				$dial_string = $row["dial_string"];
				$unique_id = $row["unique_id"];
			}
			unset ($prep_statement);

		//get the voicemail data
			$sql = "select * from v_voicemails ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			if (is_numeric($extension)) {
				$sql .= "and voicemail_id = '$extension' ";
			}
			else {
				$sql .= "and voicemail_id = '$number_alias' ";
			}
			//$sql .= "and voicemail_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$vm_password = $row["voicemail_password"];
			}
			unset ($prep_statement);
	}

//set the defaults
	if (strlen($limit_max) == 0) { $limit_max = '5'; }

//begin the page content
	require_once "resources/header.php";

	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "\n";
	echo "function hide_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"none\";\n";
	echo "}\n";
	echo "</script>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' nowrap='nowrap' align='left' valign='top'>\n";
	echo "		<b>".$text['header-hot_desking']."</b>\n";
	echo "	</td>\n";
	echo "	<td width='70%' align='right' valign='top'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='index.php'\" value='".$text['button-back']."'>\n";
	echo "		<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($action == "add") {
		echo "<select id=\"extension_uuid\" name=\"extension_uuid\" class='formfld' \">\n";
		echo "<option value=''></option>\n";
		$sql = "select extension, extension_uuid, description FROM v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "order by extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		if ($result_count > 0) {
			foreach($result as $row) {
				if ($extension_uuid == $row['extension_uuid']) {
					echo "<option value=\"".$row['extension_uuid']."\" selected>".$row['extension']." ".$row['description']."</option>\n";
				}
				else {
					echo "<option value=\"".$row['extension_uuid']."\">".$row['extension']." ".$row['description']."</option>\n";
				}
			}
		}
		unset($sql, $result, $result_count);
		echo  "</select><br />\n";
		echo $text['description-extension-add']."\n";
	}
	if ($action == "update") {
		echo "    $extension<br />\n";
		echo $text['description-extension-edit']."\n";
	}
	echo "<br />\n";

	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-unique_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='number' name='unique_id' autocomplete='off' maxlength='255' min='0' step='1' required='required' value=\"$unique_id\">\n";
	echo "<br />\n";
	echo $text['description-unique_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_password']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='password' name='vm_password' id='vm_password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value='$vm_password'>\n";
		echo "    <br />\n";
		echo "    ".$text['description-voicemail_password']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-dial_string']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='dial_string' maxlength='255' value=\"$dial_string\">\n";
		echo "<br />\n";
		echo $text['description-dial_string']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<input type='hidden' name='extension_uuid' maxlength='255' value=\"$extension_uuid\">\n";
	}

	echo "<tr>\n";
	echo "<td colspan='2' align='right'>\n";
	echo "	<br>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";

require_once "resources/footer.php";
?>
