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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('call_broadcast_edit')) {
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

//set the action with add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$call_broadcast_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http post variables and set them to php variables
	if (count($_POST)>0) {
		$broadcast_name = check_str($_POST["broadcast_name"]);
		$broadcast_description = check_str($_POST["broadcast_description"]);
		$broadcast_timeout = check_str($_POST["broadcast_timeout"]);
		$broadcast_concurrent_limit = check_str($_POST["broadcast_concurrent_limit"]);
		//$recording_uuid = check_str($_POST["recording_uuid"]);
		$broadcast_caller_id_name = check_str($_POST["broadcast_caller_id_name"]);
		$broadcast_caller_id_number = check_str($_POST["broadcast_caller_id_number"]);
		$broadcast_destination_type = check_str($_POST["broadcast_destination_type"]);
		$broadcast_phone_numbers = check_str($_POST["broadcast_phone_numbers"]);
		$broadcast_avmd = check_str($_POST["broadcast_avmd"]);
		$broadcast_destination_data = check_str($_POST["broadcast_destination_data"]);
                        
		if (if_group("superadmin")){
			$broadcast_accountcode = check_str($_POST["broadcast_accountcode"]);
		}
		elseif (if_group("admin") && file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billing/app_config.php")){
			$sql_accountcode = "SELECT COUNT(*) as count FROM v_billings WHERE domain_uuid = '".$_SESSION['domain_uuid']."' AND type_value='".$_POST["accountcode"]."'";
			$prep_statement_accountcode = $db->prepare(check_sql($sql_accountcode));
			$prep_statement_accountcode->execute();
			$row_accountcode = $prep_statement_accountcode->fetch(PDO::FETCH_ASSOC);
			if ($row_accountcode['count'] > 0) {
				$broadcast_accountcode = check_str($_POST["broadcast_accountcode"]);
			}
			else {
				$broadcast_accountcode = $_SESSION['domain_name'];
			}
			unset($sql_accountcode, $prep_statement_accountcode, $row_accountcode);
		}
		else{
			$broadcast_accountcode = $_SESSION['domain_name'];
		}
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$call_broadcast_uuid = check_str($_POST["call_broadcast_uuid"]);
	}

	//check for all required data
		if (strlen($broadcast_name) == 0) { $msg .= "".$text['confirm-name']."<br>\n"; }
		//if (strlen($broadcast_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		//if (strlen($broadcast_timeout) == 0) { $msg .= "Please provide: Timeout<br>\n"; }
		//if (strlen($broadcast_concurrent_limit) == 0) { $msg .= "Please provide: Concurrent Limit<br>\n"; }
		//if (strlen($recording_uuid) == 0) { $msg .= "Please provide: Recording<br>\n"; }
		//if (strlen($broadcast_caller_id_name) == 0) { $msg .= "Please provide: Caller ID Name<br>\n"; }
		//if (strlen($broadcast_caller_id_number) == 0) { $msg .= "Please provide: Caller ID Number<br>\n"; }
		//if (strlen($broadcast_destination_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($broadcast_phone_numbers) == 0) { $msg .= "Please provide: Phone Number List<br>\n"; }
		//if (strlen($broadcast_avmd) == 0) { $msg .= "Please provide: Voicemail Detection<br>\n"; }
		//if (strlen($broadcast_destination_data) == 0) { $msg .= "Please provide: Destination<br>\n"; }
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
		if ($action == "add" && permission_exists('call_broadcast_add')) {
			$call_broadcast_uuid = uuid();
			$sql = "insert into v_call_broadcasts ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "call_broadcast_uuid, ";
			$sql .= "broadcast_name, ";
			$sql .= "broadcast_description, ";
			$sql .= "broadcast_timeout, ";
			$sql .= "broadcast_concurrent_limit, ";
			//$sql .= "recording_uuid, ";
			$sql .= "broadcast_caller_id_name, ";
			$sql .= "broadcast_caller_id_number, ";
			$sql .= "broadcast_destination_type, ";
			$sql .= "broadcast_phone_numbers, ";
			$sql .= "broadcast_avmd, ";
			$sql .= "broadcast_destination_data, ";
			$sql .= "broadcast_accountcode ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$call_broadcast_uuid', ";
			$sql .= "'$broadcast_name', ";
			$sql .= "'$broadcast_description', ";
			if (strlen($broadcast_timeout) == 0) {
				$sql .= "null, ";
			}
			else {
				$sql .= "'$broadcast_timeout', ";
			}
			if (strlen($broadcast_concurrent_limit) == 0) {
				$sql .= "null, ";
			}
			else {
				$sql .= "'$broadcast_concurrent_limit', ";
			}
			//$sql .= "'$recording_uuid', ";
			$sql .= "'$broadcast_caller_id_name', ";
			$sql .= "'$broadcast_caller_id_number', ";
			$sql .= "'$broadcast_destination_type', ";
			$sql .= "'$broadcast_phone_numbers', ";
			$sql .= "'$broadcast_avmd', ";
			$sql .= "'$broadcast_destination_data', ";
			$sql .= "'$broadcast_accountcode' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['confirm-add'];
			header("Location: call_broadcast.php");
			return;
		} //if ($action == "add")

		if ($action == "update" && permission_exists('call_broadcast_edit')) {
			$sql = "update v_call_broadcasts set ";
			$sql .= "broadcast_name = '$broadcast_name', ";
			$sql .= "broadcast_description = '$broadcast_description', ";
			if (strlen($broadcast_timeout) == 0) {
				$sql .= "broadcast_timeout = null, ";
			}
			else {
				$sql .= "broadcast_timeout = '$broadcast_timeout', ";
			}
			if (strlen($broadcast_concurrent_limit) == 0) {
				$sql .= "broadcast_concurrent_limit = null, ";
			}
			else {
				$sql .= "broadcast_concurrent_limit = '$broadcast_concurrent_limit', ";
			}
			//$sql .= "recording_uuid = '$recording_uuid', ";
			$sql .= "broadcast_caller_id_name = '$broadcast_caller_id_name', ";
			$sql .= "broadcast_caller_id_number = '$broadcast_caller_id_number', ";
			$sql .= "broadcast_destination_type = '$broadcast_destination_type', ";
			$sql .= "broadcast_phone_numbers = '$broadcast_phone_numbers', ";
			$sql .= "broadcast_avmd = '$broadcast_avmd', ";
			$sql .= "broadcast_destination_data = '$broadcast_destination_data', ";
			$sql .= "broadcast_accountcode = '$broadcast_accountcode' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and call_broadcast_uuid = '$call_broadcast_uuid'";
			echo $sql."<br><br>";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['confirm-update'];
			header("Location: call_broadcast.php");
			return;
		} //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$call_broadcast_uuid = $_GET["id"];
		$sql = "select * from v_call_broadcasts ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and call_broadcast_uuid = '$call_broadcast_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		while($row = $prep_statement->fetch()) {
			$broadcast_name = $row["broadcast_name"];
			$broadcast_description = $row["broadcast_description"];
			$broadcast_timeout = $row["broadcast_timeout"];
			$broadcast_concurrent_limit = $row["broadcast_concurrent_limit"];
			//$recording_uuid = $row["recording_uuid"];
			$broadcast_caller_id_name = $row["broadcast_caller_id_name"];
			$broadcast_caller_id_number = $row["broadcast_caller_id_number"];
			$broadcast_destination_type = $row["broadcast_destination_type"];
			$broadcast_phone_numbers = $row["broadcast_phone_numbers"];
			$broadcast_avmd = $row["broadcast_avmd"];
			$broadcast_destination_data = $row["broadcast_destination_data"];
			$broadcast_accountcode = $row["broadcast_accountcode"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//begin header
	require_once "resources/header.php";

//begin content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";

	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' align='left' nowrap='nowrap'><b>".$text['label-call-broadcast']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='back' alt='".$text['button-back']."' onclick=\"window.location='call_broadcast.php'\" value='".$text['button-back']."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='call_broadcast_uuid' value='$call_broadcast_uuid'>\n";
		echo "<input type='button' class='btn' name='' alt='".$text['button-send']."' onclick=\"window.location='call_broadcast_send.php?id=$call_broadcast_uuid'\" value='".$text['button-send']."'>\n";
		echo "<input type='button' class='btn' name='' alt='".$text['button-stop']."' onclick=\"window.location='call_broadcast_stop.php?id=".$call_broadcast_uuid."'\" value='".$text['button-stop']."'>\n";
	}
	echo "	<input type='submit' class='btn' name='submit' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_name' maxlength='255' value=\"$broadcast_name\">\n";
	echo "<br />\n";
	echo "".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

        
	if (if_group("superadmin")){
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-accountcode'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if ($action == "add"){ $accountcode=$_SESSION['domain_name']; }
		echo "    <input class='formfld' type='text' name='broadcast_accountcode' maxlength='255' value=\"$broadcast_accountcode\">\n";
		echo "<br />\n";
		echo $text['description-accountcode']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}elseif (if_group("admin") &&  file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billing/app_config.php")){
		$sql_accountcode = "SELECT type_value FROM v_billings WHERE domain_uuid = '".$_SESSION['domain_uuid']."'";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-accountcode'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <select name='broadcast_accountcode' id='broadcast_accountcode' class='formfld'>\n";
		$prep_statement_accountcode = $db->prepare(check_sql($sql_accountcode));
		$prep_statement_accountcode->execute();
		$result_accountcode = $prep_statement_accountcode->fetchAll(PDO::FETCH_NAMED);
		foreach ($result_accountcode as &$row_accountcode) {
			$selected = '';
			if (($action == "add") && ($row_accountcode['type_value'] == $_SESSION['domain_name'])){
				$selected='selected="selected"';
			}
			elseif ($row_accountcode['type_value'] == $accountcode){
				$selected='selected="selected"';
			}
			echo "    <option value=\"".$row_accountcode['type_value']."\" $selected>".$row_accountcode['type_value']."</option>\n";
		}
                
		unset($sql_accountcode, $prep_statement_accountcode, $result_accountcode);
		echo "</select>";
		echo "<br />\n";
		echo $text['description-accountcode']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-timeout'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_timeout' maxlength='255' value=\"$broadcast_timeout\">\n";
	echo "<br />\n";
	echo "".$text['description-timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-concurrent-limit'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_concurrent_limit' maxlength='255' value=\"$broadcast_concurrent_limit\">\n";
	echo "<br />\n";
	echo "".$text['description-concurrent-limit']."\n";
	echo "</td>\n";
	echo "</tr>\n";


	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	//echo "	Recording:\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "		<select name='recording_uuid' class='formfld'>\n";
	//echo "		<option></option>\n";
	//$sql = "";
	//$sql .= "select * from v_recordings ";
	//$sql .= "where domain_uuid = '$domain_uuid' ";
	//$prep_statement = $db->prepare(check_sql($sql));
	//$prep_statement->execute();
	//while($row = $prep_statement->fetch()) {
	//	if ($recording_uuid == $row['recording_uuid']) {
	//		echo "		<option value='".$row['recording_uuid']."' selected='yes'>".$row['recordingname']."</option>\n";
	//	}
	//	else {
	//		echo "		<option value='".$row['recording_uuid']."'>".$row['recordingname']."</option>\n";
	//	}
	//}
	//unset ($prep_statement);
	//echo "		</select>\n";
	//echo "<br />\n";
	//echo "Recording to play when the call is answered.<br />\n";
	//echo "\n";
	//echo "</td>\n";
	//echo "</tr>\n";


	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-caller-id-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_caller_id_name' maxlength='255' value=\"$broadcast_caller_id_name\">\n";
	echo "<br />\n";
	echo "".$text['description-caller-id-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-callerid-number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_caller_id_number' maxlength='255' value=\"$broadcast_caller_id_number\">\n";
	echo "<br />\n";
	echo "".$text['description-caller-id-number']."\n";
	echo "</td>\n";
	echo "</tr>\n";
/*
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_destination_type' maxlength='255' value=\"$broadcast_destination_type\">\n";
	echo "<br />\n";
	echo "Optional, Destination Type: bridge, transfer, voicemail, conference, fifo, etc.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Destination:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_destination_data' maxlength='255' value=\"$broadcast_destination_data\">\n";
	echo "<br />\n";
	echo "Optional, send the call to an auto attendant, conference room, or any other destination. <br /><br />\n";
	echo "conference (8khz): 01-\${domain}@default <br />\n";
	echo "bridge (external number): sofia/gateway/gatewayname/12081231234 <br />\n";
	echo "bridge (auto attendant): sofia/internal/5002@\${domain} <br />\n";
	echo "transfer (external number): 12081231234 XML default <br />\n";
	echo "</td>\n";
	echo "</tr>\n";
*/

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-destination'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_destination_data' maxlength='255' value=\"$broadcast_destination_data\">\n";
	echo "<br />\n";
	echo "".$text['description-destination']." <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-phone'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' type='text' name='broadcast_phone_numbers' rows='10'>$broadcast_phone_numbers</textarea>\n";
	echo "<br />\n";
	echo "".$text['description-phone']." <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-avmd'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='broadcast_avmd'>\n";
	echo "    	<option value='false' ".(($broadcast_avmd == "false") ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "    	<option value='true' ".(($broadcast_avmd == "true") ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo "<br />\n";
	echo $text['description-avmd']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_description' maxlength='255' value=\"$broadcast_description\">\n";
	echo "<br />\n";
	echo "".$text['description-info']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "<br />\n";
	echo "<br />\n";
	echo "<br />\n";

	/*
	if ($action == "update") {

		echo "<table width='100%' border='0'>\n";
		echo "<tr>\n";
		echo "<td width='50%' nowrap><b>Call Broadcast</b></td>\n";
		echo "<td width='50%' align='right'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<form method='get' name='frm' action='call_broadcast_send.php'>\n";

		echo "<div align='center'>\n";
		echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Category:\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "		<select name='user_category' class='formfld'>\n";
		echo "		<option></option>\n";
		$sql = "";
		$sql .= "select distinct(user_category) as user_category from v_users ";
		//$sql .= "where domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		while($row = $prep_statement->fetch()) {
			if ($user_category   == $row['user_category']) {
				echo "		<option value='".$row['user_category']."' selected='yes'>".$row['user_category']."</option>\n";
			}
			else {
				echo "		<option value='".$row['user_category']."'>".$row['user_category']."</option>\n";
			}
		}
		unset ($prep_statement);
		echo "		</select>\n";
		echo "<br />\n";
		//echo "zzz.<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Group:\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "		<select name='group_name' class='formfld'>\n";
		echo "		<option></option>\n";
		$sql = "";
		$sql .= "select * from v_groups ";
		//$sql .= "where domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		while($row = $prep_statement->fetch()) {
			if ($recording_uuid == $row['group_name']) {
				echo "		<option value='".$row['group_name']."' selected='yes'>".$row['group_name']."</option>\n";
			}
			else {
				echo "		<option value='".$row['group_name']."'>".$row['group_name']."</option>\n";
			}
		}
		unset ($prep_statement);
		echo "		</select>\n";
		echo "<br />\n";
		//echo "zzz.<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";


		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Gateway:\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "		<select name='gateway' class='formfld'>\n";
		echo "		<option></option>\n";
		$sql = "";
		$sql .= "select * from v_gateways ";
		//$sql .= "where domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		while($row = $prep_statement->fetch()) {
			if ($gateway == $row['gateway']) {
				echo "		<option value='".$row['gateway']."' selected='yes'>".$row['gateway']."</option>\n";
			}
			else {
				echo "		<option value='".$row['gateway']."'>".$row['gateway']."</option>\n";
			}
		}
		unset ($prep_statement);
		echo "		<option value='loopback'>loopback</option>\n";
		echo "		</select>\n";
		echo "<br />\n";
		//echo "zzz.<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";


		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Phone Type:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "		<select name='phonetype1' class='formfld'>\n";
		echo "		<option></option>\n";
		echo "		<option value='phone1'>phone1</option>\n";
		echo "		<option value='phone2'>phone2</option>\n";
		echo "		<option value='cell'>cell</option>\n";
		//echo "		<option value='zzz'>cell</option>\n";
		echo "		</select>\n";
		echo "<br />\n";
		//echo "zzz.<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";


		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Phone Type:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "		<select name='phonetype2' class='formfld'>\n";
		echo "		<option></option>\n";
		echo "		<option value='phone1'>phone1</option>\n";
		echo "		<option value='phone2'>phone2</option>\n";
		echo "		<option value='cell'>cell</option>\n";
		//echo "		<option value='zzz'>cell</option>\n";
		echo "		</select>\n";
		echo "<br />\n";
		//echo "zzz.<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";


		echo "	<tr>\n";
		echo "		<td colspan='2' align='right'>\n";
		echo "				<input type='hidden' name='call_broadcast_uuid' value='$call_broadcast_uuid'>\n";
		echo "				<input type='submit' name='submit' class='btn' value='Send Broadcast'>\n";
		echo "		</td>\n";
		echo "	</tr>";

		echo "</table>";
		echo "</form>";
	}
	*/

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

require_once "resources/footer.php";
?>
