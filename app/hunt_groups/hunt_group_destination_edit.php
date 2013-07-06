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

//check permissions
	if (permission_exists('hunt_group_add') || permission_exists('hunt_group_edit')) {
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
		$hunt_group_destination_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

	if (isset($_REQUEST["id2"])) {
		$hunt_group_uuid = check_str($_REQUEST["id2"]);
	}

//get the http values and set them as variables
	if (count($_POST)>0) {
		if (isset($_POST["hunt_group_uuid"])) {
			$hunt_group_uuid = check_str($_POST["hunt_group_uuid"]);
		}
		$destination_data = check_str($_POST["destination_data"]);
		$destination_type = check_str($_POST["destination_type"]);
		$destination_timeout = check_str($_POST["destination_timeout"]);
		$destination_order = check_str($_POST["destination_order"]);
		$destination_enabled = check_str($_POST["destination_enabled"]);
		$destination_description = check_str($_POST["destination_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$hunt_group_destination_uuid = check_str($_POST["hunt_group_destination_uuid"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
		if (strlen($destination_data) == 0) { $msg .= $text['message-required'].$text['label-destination']."<br>\n"; }
		if (strlen($destination_type) == 0) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
		//if (strlen($destination_timeout) == 0) { $msg .= $text['message-required'].$text['label-timeout']."<br>\n"; }
		//if (strlen($destination_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
		//if (strlen($destination_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
		//if (strlen($destination_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
			if ($action == "add" && permission_exists('hunt_group_add')) {
				$hunt_group_destination_uuid = uuid();
				$sql = "insert into v_hunt_group_destinations ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "hunt_group_uuid, ";
				$sql .= "hunt_group_destination_uuid, ";
				$sql .= "destination_data, ";
				$sql .= "destination_type, ";
				$sql .= "destination_timeout, ";
				$sql .= "destination_order, ";
				$sql .= "destination_enabled, ";
				$sql .= "destination_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$hunt_group_uuid', ";
				$sql .= "'$hunt_group_destination_uuid', ";
				$sql .= "'$destination_data', ";
				$sql .= "'$destination_type', ";
				$sql .= "'$destination_timeout', ";
				$sql .= "'$destination_order', ";
				$sql .= "'$destination_enabled', ";
				$sql .= "'$destination_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				//synchronize the xml config
				save_hunt_group_xml();

				require_once "resources/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=hunt_group_edit.php?id=".$hunt_group_uuid."\">\n";
				echo "<div align='center'>\n";
				echo $text['message-add']."\n";
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('hunt_group_edit')) {
				$sql = "update v_hunt_group_destinations set ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "hunt_group_uuid = '$hunt_group_uuid', ";
				$sql .= "destination_data = '$destination_data', ";
				$sql .= "destination_type = '$destination_type', ";
				$sql .= "destination_timeout = '$destination_timeout', ";
				$sql .= "destination_order = '$destination_order', ";
				$sql .= "destination_enabled = '$destination_enabled', ";
				$sql .= "destination_description = '$destination_description' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and hunt_group_destination_uuid = '$hunt_group_destination_uuid'";
				$db->exec(check_sql($sql));

				//synchronize the xml config
				save_hunt_group_xml();

				require_once "resources/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=hunt_group_edit.php?id=".$hunt_group_uuid."\">\n";
				echo "<div align='center'>\n";
				echo $text['message-update']."\n";
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$hunt_group_destination_uuid = $_GET["id"];
		$sql = "select * from v_hunt_group_destinations ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and hunt_group_destination_uuid = '$hunt_group_destination_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$hunt_group_uuid = $row["hunt_group_uuid"];
			$destination_data = $row["destination_data"];
			$destination_type = $row["destination_type"];
			$destination_timeout = $row["destination_timeout"];
			$destination_order = $row["destination_order"];
			$destination_enabled = $row["destination_enabled"];
			$destination_description = $row["destination_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "add") {
		$page["title"] = $text['title-hunt_group_destination_add'];
	}
	if ($action == "update") {
		$page["title"] = $text['title-hunt_group_destination_edit'];
	}

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
		echo "<td align='left' width='30%' nowrap><b>".$text['header-hunt_group_destination_add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap><b>".$text['header-hunt_group_destination_edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='hunt_group_edit.php?id=".$hunt_group_uuid."'\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-destination'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='destination_data' maxlength='255' value=\"$destination_data\">\n";
	echo "<br />\n";
	echo "<b>".$text['description-destination_examples']."</b>...<br>\n";
	echo $text['description-destination_example_extension'].": 1001<br />\n";
	echo $text['description-destination_example_voicemail'].": 1001<br />\n";
	echo $text['description-destination_example_sip_uri_voicemail'].": sofia/internal/*98@\${domain}<br />\n";
	echo $text['description-destination_example_sip_uri_external_number'].": sofia/gateway/gatewayname/12081231234<br />\n";
	echo $text['description-destination_example_sip_uri_auto_attendant'].": sofia/internal/5002@\${domain}<br />\n";
	echo $text['description-destination_example_sip_uri_user'].": /user/1001@\${domain}\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "                <select name='destination_type' class='formfld'>\n";
	echo "                <option></option>\n";
	if ($destination_type == "extension") {
		echo "                <option selected='yes' value='extension'>".$text['option-extension']."</option>\n";
	}
	else {
		echo "                <option value='extension'>".$text['option-extension']."</option>\n";
	}
	if ($destination_type == "voicemail") {
		echo "                <option selected='yes' value='voicemail'>".$text['option-voicemail']."</option>\n";
	}
	else {
		echo "                <option value='voicemail'>".$text['option-voicemail']."</option>\n";
	}
	if ($destination_type == "sip uri") {
		echo "                <option selected='yes' value='sip uri'>".$text['option-sip_uri']."</option>\n";
	}
	else {
		echo "                <option value='sip uri'>".$text['option-sip_uri']."</option>\n";
	}
	echo "                </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-timeout'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "              <select name='destination_timeout' class='formfld'>\n";
	echo "              <option></option>\n";
	if (strlen($destination_timeout)> 0) {
		echo "              <option selected='yes' value='".htmlspecialchars($destination_timeout)."'>".htmlspecialchars($destination_timeout)."</option>\n";
	}
	$i=0;
	while($i<=301) {
		echo "              <option value='$i'>$i</option>\n";
		$i++;
	}
	echo "              </select>\n";
	echo "<br />\n";
	echo $text['description-destination_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-order'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "              <select name='destination_order' class='formfld'>\n";
	if (strlen($destination_order)> 0) {
		echo "              <option selected='yes' value='".htmlspecialchars($destination_order)."'>".htmlspecialchars($destination_order)."</option>\n";
	}
	$i=0;
	while($i<=301) {
		if (strlen($i) == 1) {
			echo "              <option value='00$i'>00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "              <option value='0$i'>0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "              <option value='$i'>$i</option>\n";
		}
		$i++;
	}
	echo "              </select>\n";
	echo "<br />\n";
	echo $text['description-destination_order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='destination_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($destination_enabled == "true" || strlen($destination_enabled) == 0) {
		echo "    <option value='true' selected >".$text['option-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['option-true']."</option>\n";
	}
	if ($destination_enabled == "false") {
		echo "    <option value='false' selected >".$text['option-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['option-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='destination_description' maxlength='255' value=\"$destination_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='hunt_group_uuid' value='$hunt_group_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='hunt_group_destination_uuid' value='$hunt_group_destination_uuid'>\n";
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

//show the footer
	require_once "resources/footer.php";
?>