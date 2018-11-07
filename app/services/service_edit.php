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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('service_add') || permission_exists('service_edit')) {
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
		$service_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post and set it to php variables
	if (count($_POST)>0) {
		$service_name = check_str($_POST["service_name"]);
		$service_type = check_str($_POST["service_type"]);
		$service_data = check_str($_POST["service_data"]);
		$service_cmd_start = check_str($_POST["service_cmd_start"]);
		$service_cmd_stop = check_str($_POST["service_cmd_stop"]);
		$service_description = check_str($_POST["service_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$service_uuid = check_str($_POST["service_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($service_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		//if (strlen($service_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($service_data) == 0) { $msg .= "Please provide: Data<br>\n"; }
		//if (strlen($service_cmd_start) == 0) { $msg .= "Please provide: Start Command<br>\n"; }
		//if (strlen($service_cmd_stop) == 0) { $msg .= "Please provide: Stop Command<br>\n"; }
		//if (strlen($service_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
			if ($action == "add" && permission_exists('service_add')) {
				$service_uuid = uuid();
				$sql = "insert into v_services ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "service_uuid, ";
				$sql .= "service_name, ";
				$sql .= "service_type, ";
				$sql .= "service_data, ";
				$sql .= "service_cmd_start, ";
				$sql .= "service_cmd_stop, ";
				$sql .= "service_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$service_uuid', ";
				$sql .= "'$service_name', ";
				$sql .= "'$service_type', ";
				$sql .= "'$service_data', ";
				$sql .= "'$service_cmd_start', ";
				$sql .= "'$service_cmd_stop', ";
				$sql .= "'$service_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				message::add($text['message-add']);
				header("Location: services.php");
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('service_edit')) {
				$sql = "update v_services set ";
				$sql .= "service_name = '$service_name', ";
				$sql .= "service_type = '$service_type', ";
				$sql .= "service_data = '$service_data', ";
				$sql .= "service_cmd_start = '$service_cmd_start', ";
				$sql .= "service_cmd_stop = '$service_cmd_stop', ";
				$sql .= "service_description = '$service_description' ";
				$sql .= "where service_uuid = '$service_uuid' ";
				// $sql .= "and domain_uuid = '$domain_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				message::add($text['message-update']);
				header("Location: services.php");
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$service_uuid = $_GET["id"];
		$sql = "select * from v_services ";
		$sql .= "where service_uuid = '$service_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$service_name = $row["service_name"];
			$service_type = $row["service_type"];
			$service_data = $row["service_data"];
			$service_cmd_start = $row["service_cmd_start"];
			$service_cmd_stop = $row["service_cmd_stop"];
			$service_description = $row["service_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//begin the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap><b>".$text['title']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap><b>Service Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='services.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo $text['description']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_name' maxlength='255' value=\"".escape($service_name)."\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='service_type'>\n";
	echo "	<option value=''></option>\n";
	if ($service_type == "pid" || $service_type == "pid_file") {
		echo "	<option value='pid' selected='selected'>pid</option>\n";
	}
	else {
		echo "	<option value='pid'>pid</option>\n";
	}
	//if ($service_type == "php") {
	//	echo "	<option value='php' selected='selected'>php</option>\n";
	//}
	//else {
	//	echo "	<option value='php'>php</option>\n";
	//}
	if ($service_type == "file") {
		echo "	<option value='file' selected='selected'>file</option>\n";
	}
	else {
		echo "	<option value='file'>file</option>\n";
	}
	if ($service_type == "svc") {
		echo "	<option value='svc' selected='selected'>svc</option>\n";
	}
	else {
		echo "	<option value='svc'>svc</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-data']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_data' maxlength='255' value=\"" .  htmlspecialchars($service_data) . "\">\n";
	//echo "	<textarea class='formfld' name='service_data' rows='4'>$service_data</textarea>\n";
	echo "<br />\n";
	echo $text['description-data']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-start_command']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_cmd_start' maxlength='255' value=\"" .  htmlspecialchars($service_cmd_start) . "\">\n";
	echo "<br />\n";
	echo $text['description-start_command']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-stop_command']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_cmd_stop' maxlength='255' value=\"" .  htmlspecialchars($service_cmd_stop) . "\">\n";
	echo "<br />\n";
	echo $text['description-stop_command']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' name='service_description' rows='4'>".escape($service_description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='service_uuid' value='".escape($service_uuid)."'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//show the footer
	require_once "resources/footer.php";
?>
