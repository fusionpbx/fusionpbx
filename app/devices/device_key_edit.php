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
if (permission_exists('device_key_add') || permission_exists('device_key_edit')) {
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
		$device_key_uuid = check_str($_REQUEST["id"]);
		$device_uuid = check_str($_REQUEST["device_uuid"]);
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (strlen($_GET["device_key_uuid"]) > 0) {
		$device_key_uuid = check_str($_GET["device_key_uuid"]);
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$device_key_id = check_str($_POST["device_key_id"]);
		$device_key_category = check_str($_POST["device_key_category"]);
		$device_key_type = check_str($_POST["device_key_type"]);
		$device_key_line = check_str($_POST["device_key_line"]);
		$device_key_value = check_str($_POST["device_key_value"]);
		$device_key_extension = check_str($_POST["device_key_extension"]);
		$device_key_label = check_str($_POST["device_key_label"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$device_key_uuid = check_str($_POST["device_key_uuid"]);
	}

	//check for all required data
		//if (strlen($device_key_id) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_id']."<br>\n"; }
		//if (strlen($device_key_category) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_category']."<br>\n"; }
		//if (strlen($device_key_type) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_type']."<br>\n"; }
		//if (strlen($device_key_line) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_line']."<br>\n"; }
		//if (strlen($device_key_value) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_value']."<br>\n"; }
		//if (strlen($device_key_extension) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_extension']."<br>\n"; }
		//if (strlen($device_key_label) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_label']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persistformvar.php";
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
			if ($action == "add" && permission_exists('device_key_add')) {
				$sql = "insert into v_device_keys ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "device_key_uuid, ";
				$sql .= "device_uuid, ";
				$sql .= "device_key_id, ";
				$sql .= "device_key_category, ";
				$sql .= "device_key_type, ";
				$sql .= "device_key_line, ";
				$sql .= "device_key_value, ";
				$sql .= "device_key_extension, ";
				$sql .= "device_key_label ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$device_uuid', ";
				$sql .= "'$device_key_id', ";
				$sql .= "'$device_key_category', ";
				$sql .= "'$device_key_type', ";
				$sql .= "'$device_key_line', ";
				$sql .= "'$device_key_value', ";
				$sql .= "'$device_key_extension', ";
				$sql .= "'$device_key_label' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-add'];
				header("Location: device_edit.php?id=".$device_uuid);
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('device_key_edit')) {
				$sql = "update v_device_keys set ";
				$sql .= "device_key_id = '$device_key_id', ";
				$sql .= "device_key_category = '$device_key_category', ";
				$sql .= "device_key_type = '$device_key_type', ";
				$sql .= "device_key_line = '$device_key_line', ";
				$sql .= "device_key_value = '$device_key_value', ";
				$sql .= "device_key_extension = '$device_key_extension', ";
				$sql .= "device_key_label = '$device_key_label' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and device_key_uuid = '$device_key_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-update'];
				header("Location: device_edit.php?id=".$device_uuid);
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$device_key_uuid = check_str($_GET["id"]);
		$sql = "select * from v_device_keys ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and device_key_uuid = '$device_key_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$device_uuid = $row["device_uuid"];
			$device_key_id = $row["device_key_id"];
			$device_key_category = $row["device_key_category"];
			$device_key_type = $row["device_key_type"];
			$device_key_line = $row["device_key_line"];
			$device_key_value = $row["device_key_value"];
			$device_key_extension = $row["device_key_extension"];
			$device_key_label = $row["device_key_label"];
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-device_key']."</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='device_edit.php?id=$device_uuid'\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_category'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_key_category'>\n";
	echo "	<option value=''></option>\n";
	if ($device_key_category == "line") {
		echo "	<option value='line' selected='selected'>".$text['label-line']."</option>\n";
	}
	else {
		echo "	<option value='line'>".$text['label-line']."</option>\n";
	}
	if ($device_key_category == "memory") {
		echo "	<option value='memory' selected='selected'>".$text['label-memory']."</option>\n";
	}
	else {
		echo "	<option value='memory'>".$text['label-memory']."</option>\n";
	}
	if ($device_key_category == "programmable") {
		echo "	<option value='programmable' selected='selected'>".$text['label-programmable']."</option>\n";
	}
	else {
		echo "	<option value='programmable'>".$text['label-programmable']."</option>\n";
	}
	if ($device_key_category == "expansion") {
		echo "	<option value='expansion' selected='selected'>".$text['label-expansion']."</option>\n";
	}
	else {
		echo "	<option value='expansion'>".$text['label-expansion']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_key_category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_id'].": \n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_key_id'>\n";
	echo "	<option value=''></option>\n";
	if ($device_key_id == "1") {
		echo "	<option value='1' selected='selected'>1</option>\n";
	}
	else {
		echo "	<option value='1'>1</option>\n";
	}
	if ($device_key_id == "2") {
		echo "	<option value='2' selected='selected'>2</option>\n";
	}
	else {
		echo "	<option value='2'>2</option>\n";
	}
	if ($device_key_id == "3") {
		echo "	<option value='3' selected='selected'>3</option>\n";
	}
	else {
		echo "	<option value='3'>3</option>\n";
	}
	if ($device_key_id == "4") {
		echo "	<option value='4' selected='selected'>4</option>\n";
	}
	else {
		echo "	<option value='4'>4</option>\n";
	}
	if ($device_key_id == "5") {
		echo "	<option value='5' selected='selected'>5</option>\n";
	}
	else {
		echo "	<option value='5'>5</option>\n";
	}
	if ($device_key_id == "6") {
		echo "	<option value='6' selected='selected'>6</option>\n";
	}
	else {
		echo "	<option value='6'>6</option>\n";
	}
	if ($device_key_id == "7") {
		echo "	<option value='7' selected='selected'>7</option>\n";
	}
	else {
		echo "	<option value='7'>7</option>\n";
	}
	if ($device_key_id == "8") {
		echo "	<option value='8' selected='selected'>8</option>\n";
	}
	else {
		echo "	<option value='8'>8</option>\n";
	}
	if ($device_key_id == "9") {
		echo "	<option value='9' selected='selected'>9</option>\n";
	}
	else {
		echo "	<option value='9'>9</option>\n";
	}
	if ($device_key_id == "10") {
		echo "	<option value='10' selected='selected'>10</option>\n";
	}
	else {
		echo "	<option value='10'>10</option>\n";
	}
	if ($device_key_id == "11") {
		echo "	<option value='11' selected='selected'>11</option>\n";
	}
	else {
		echo "	<option value='11'>11</option>\n";
	}
	if ($device_key_id == "12") {
		echo "	<option value='12' selected='selected'>12</option>\n";
	}
	else {
		echo "	<option value='12'>12</option>\n";
	}
	if ($device_key_id == "13") {
		echo "	<option value='13' selected='selected'>13</option>\n";
	}
	else {
		echo "	<option value='13'>13</option>\n";
	}
	if ($device_key_id == "14") {
		echo "	<option value='14' selected='selected'>14</option>\n";
	}
	else {
		echo "	<option value='14'>14</option>\n";
	}
	if ($device_key_id == "15") {
		echo "	<option value='15' selected='selected'>15</option>\n";
	}
	else {
		echo "	<option value='15'>15</option>\n";
	}
	if ($device_key_id == "16") {
		echo "	<option value='16' selected='selected'>16</option>\n";
	}
	else {
		echo "	<option value='16'>16</option>\n";
	}
	if ($device_key_id == "17") {
		echo "	<option value='17' selected='selected'>17</option>\n";
	}
	else {
		echo "	<option value='17'>17</option>\n";
	}
	if ($device_key_id == "18") {
		echo "	<option value='18' selected='selected'>18</option>\n";
	}
	else {
		echo "	<option value='18'>18</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_key_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_line'].": \n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_key_line'>\n";
	echo "	<option value=''></option>\n";
	if ($device_key_line == "0") {
		echo "	<option value='0' selected='selected'>0</option>\n";
	}
	else {
		echo "	<option value='0'>0</option>\n";
	}
	if ($device_key_line == "1") {
		echo "	<option value='1' selected='selected'>1</option>\n";
	}
	else {
		echo "	<option value='1'>1</option>\n";
	}
	if ($device_key_line == "2") {
		echo "	<option value='2' selected='selected'>2</option>\n";
	}
	else {
		echo "	<option value='2'>2</option>\n";
	}
	if ($device_key_line == "3") {
		echo "	<option value='3' selected='selected'>3</option>\n";
	}
	else {
		echo "	<option value='3'>3</option>\n";
	}
	if ($device_key_line == "4") {
		echo "	<option value='4' selected='selected'>4</option>\n";
	}
	else {
		echo "	<option value='4'>4</option>\n";
	}
	if ($device_key_line == "5") {
		echo "	<option value='5' selected='selected'>5</option>\n";
	}
	else {
		echo "	<option value='5'>5</option>\n";
	}
	if ($device_key_line == "6") {
		echo "	<option value='6' selected='selected'>6</option>\n";
	}
	else {
		echo "	<option value='6'>6</option>\n";
	}
	if ($device_key_line == "7") {
		echo "	<option value='7' selected='selected'>7</option>\n";
	}
	else {
		echo "	<option value='7'>7</option>\n";
	}
	if ($device_key_line == "8") {
		echo "	<option value='8' selected='selected'>8</option>\n";
	}
	else {
		echo "	<option value='8'>8</option>\n";
	}
	if ($device_key_line == "9") {
		echo "	<option value='9' selected='selected'>9</option>\n";
	}
	else {
		echo "	<option value='9'>9</option>\n";
	}
	if ($device_key_line == "10") {
		echo "	<option value='10' selected='selected'>10</option>\n";
	}
	else {
		echo "	<option value='10'>10</option>\n";
	}
	if ($device_key_line == "11") {
		echo "	<option value='11' selected='selected'>11</option>\n";
	}
	else {
		echo "	<option value='11'>11</option>\n";
	}
	if ($device_key_line == "12") {
		echo "	<option value='12' selected='selected'>12</option>\n";
	}
	else {
		echo "	<option value='12'>12</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_key_line']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
?>

	<?php $selected = "selected='selected'"; ?>
	<?php $found = false; ?>
	<select class='formfld' name='device_key_type'>
	<option value=''></option>
	<optgroup label='Cisco'>
		<option value='line' <?php if ($device_key_type == "0") { echo $selected;$found=true; } ?>>line</option>
		<option value='disabled' <?php if ($device_key_type == "disabled") { echo $selected;$found=true; } ?>>disabled</option>
	</optgroup>
	<optgroup label='Yealink'>
		<option value='0' <?php if ($device_key_type == "0") { echo $selected;$found=true; } ?>>0-N/A(default for memory key)</option>
		<option value='1' <?php if ($device_key_type == "1") { echo $selected;$found=true; } ?>>1-Conference</option>
		<option value='2' <?php if ($device_key_type == "2") { echo $selected;$found=true; } ?>>2-Forward</option>
		<option value='3' <?php if ($device_key_type == "3") { echo $selected;$found=true; } ?>>3-Transfer</option>
		<option value='4' <?php if ($device_key_type == "4") { echo $selected;$found=true; } ?>>4-Hold</option>
		<option value='5' <?php if ($device_key_type == "5") { echo $selected;$found=true; } ?>>5-DND</option>
		<option value='6' <?php if ($device_key_type == "6") { echo $selected;$found=true; } ?>>6-Redial</option>
		<option value='7' <?php if ($device_key_type == "7") { echo $selected;$found=true; } ?>>7-Call Return</option>
		<option value='8' <?php if ($device_key_type == "8") { echo $selected;$found=true; } ?>>8-SMS</option>
		<option value='9' <?php if ($device_key_type == "9") { echo $selected;$found=true; } ?>>9-Call Pickup</option>
		<option value='10' <?php if ($device_key_type == "10") { echo $selected;$found=true; } ?>>10-Call Park</option>
		<option value='11' <?php if ($device_key_type == "11") { echo $selected;$found=true; } ?>>11-DTMF</option>
		<option value='12' <?php if ($device_key_type == "12") { echo $selected;$found=true; } ?>>12-Voicemail</option>
		<option value='13' <?php if ($device_key_type == "13") { echo $selected;$found=true; } ?>>13-SpeedDial</option>
		<option value='14' <?php if ($device_key_type == "14") { echo $selected;$found=true; } ?>>14-Intercom</option>
		<option value='15' <?php if ($device_key_type == "15") { echo $selected;$found=true; } ?>>15-Line(default for line key)</option>
		<option value='16' <?php if ($device_key_type == "16") { echo $selected;$found=true; } ?>>16-BLF</option>
		<option value='17' <?php if ($device_key_type == "17") { echo $selected;$found=true; } ?>>17-URL</option>
		<option value='19' <?php if ($device_key_type == "19") { echo $selected;$found=true; } ?>>19-Public Hold</option>
		<option value='20' <?php if ($device_key_type == "20") { echo $selected;$found=true; } ?>>20-Private</option>
		<option value='21' <?php if ($device_key_type == "21") { echo $selected;$found=true; } ?>>21-Shared Line</option>
		<option value='22' <?php if ($device_key_type == "22") { echo $selected;$found=true; } ?>>22-XML Group</option>
		<option value='23' <?php if ($device_key_type == "23") { echo $selected;$found=true; } ?>>23-Group Pickup</option>
		<option value='24' <?php if ($device_key_type == "24") { echo $selected;$found=true; } ?>>24-Paging</option>
		<option value='25' <?php if ($device_key_type == "25") { echo $selected;$found=true; } ?>>25-Record</option>
		<option value='27' <?php if ($device_key_type == "27") { echo $selected;$found=true; } ?>>27-XML Browser</option>
		<option value='28' <?php if ($device_key_type == "28") { echo $selected;$found=true; } ?>>28-History</option>
		<option value='29' <?php if ($device_key_type == "29") { echo $selected;$found=true; } ?>>29-Directory</option>
		<option value='30' <?php if ($device_key_type == "30") { echo $selected;$found=true; } ?>>30-Menu</option>
		<option value='32' <?php if ($device_key_type == "32") { echo $selected;$found=true; } ?>>32-New SMS</option>
		<option value='33' <?php if ($device_key_type == "33") { echo $selected;$found=true; } ?>>33-Status</option>
		<option value='34' <?php if ($device_key_type == "34") { echo $selected;$found=true; } ?>>34-Hot Desking</option>
		<option value='35' <?php if ($device_key_type == "35") { echo $selected;$found=true; } ?>>35-URL Record</option>
		<option value='38' <?php if ($device_key_type == "38") { echo $selected;$found=true; } ?>>38-LDAP</option>
		<option value='39' <?php if ($device_key_type == "39") { echo $selected;$found=true; } ?>>39-BLF List</option>
		<option value='40' <?php if ($device_key_type == "40") { echo $selected;$found=true; } ?>>40-Prefix</option>
		<option value='41' <?php if ($device_key_type == "41") { echo $selected;$found=true; } ?>>41-Zero-Sp-Touch</option>
		<option value='42' <?php if ($device_key_type == "42") { echo $selected;$found=true; } ?>>42-ACD</option>
		<option value='43' <?php if ($device_key_type == "43") { echo $selected;$found=true; } ?>>43-Local Phonebook</option>
		<option value='44' <?php if ($device_key_type == "44") { echo $selected;$found=true; } ?>>44-Broadsoft Phonebook</option>
		<option value='45' <?php if ($device_key_type == "45") { echo $selected;$found=true; } ?>>45-Local Group</option>
		<option value='46' <?php if ($device_key_type == "46") { echo $selected;$found=true; } ?>>46-Broadsoft Group</option>
		<option value='47' <?php if ($device_key_type == "47") { echo $selected;$found=true; } ?>>47-XML Phonebook</option>
		<option value='48' <?php if ($device_key_type == "48") { echo $selected;$found=true; } ?>>48-Switch Account Up</option>
		<option value='49' <?php if ($device_key_type == "49") { echo $selected;$found=true; } ?>>49-Switch Account Down</option>
		<option value='50' <?php if ($device_key_type == "50") { echo $selected;$found=true; } ?>>50-Keypad Lock</option>
	</optgroup>
	<optgroup label='Other'>
		<option value='line' <?php if ($device_key_type == "line") { echo $selected;$found=true; } ?>>line</option>
		<option value='other'>other</option>
	<?php
		if (!$found) {
			echo "<option value='".$device_key_type."'>".$device_key_type."</option>\n";
		}
	?>
	</optgroup>
	</select>

<?php
	echo "<br />\n";
	echo $text['description-device_key_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_value'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_value' maxlength='255' value=\"$device_key_value\">\n";
	echo "<br />\n";
	echo $text['description-device_key_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_extension'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_extension' maxlength='255' value=\"$device_key_extension\">\n";
	echo "<br />\n";
	echo $text['description-device_key_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_label'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_label' maxlength='255' value=\"$device_key_label\">\n";
	echo "<br />\n";
	echo $text['description-device_key_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='device_uuid' value='$device_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='device_key_uuid' value='$device_key_uuid'>\n";
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