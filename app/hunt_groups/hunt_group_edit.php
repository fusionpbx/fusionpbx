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
require_once "resources/paging.php";

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

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$hunt_group_uuid = check_str($_REQUEST["id"]);
		$dialplan_uuid = check_str($_REQUEST["dialplan_uuid"]);
	}
	else {
		$action = "add";
	}

//get the http values and set them as variables
	if (count($_POST)>0) {
		$hunt_group_extension = check_str($_POST["hunt_group_extension"]);
		$hunt_group_name = check_str($_POST["hunt_group_name"]);
		$hunt_group_type = check_str($_POST["hunt_group_type"]);
		$hunt_group_timeout = check_str($_POST["hunt_group_timeout"]);
		$hunt_group_timeout_destination = check_str($_POST["hunt_group_timeout_destination"]);
		$hunt_group_timeout_type = check_str($_POST["hunt_group_timeout_type"]);
		$hunt_group_ringback = check_str($_POST["hunt_group_ringback"]);
		$hunt_group_cid_name_prefix = check_str($_POST["hunt_group_cid_name_prefix"]);
		$hunt_group_pin = check_str($_POST["hunt_group_pin"]);
		$hunt_group_caller_announce = check_str($_POST["hunt_group_caller_announce"]);

		//prepare the user list for the database
		$hunt_group_user_list = $_POST["hunt_group_user_list"];
		if (strlen($hunt_group_user_list) > 0) {
			$hunt_group_user_list_array = explode("\n", $hunt_group_user_list);
			if (count($hunt_group_user_list_array) == 0) {
				$hunt_group_user_list = '';
			}
			else {
				$hunt_group_user_list = '|';
				foreach($hunt_group_user_list_array as $user){
					if(strlen(trim($user)) > 0) {
						$hunt_group_user_list .= check_str(trim($user))."|";
					}
				}
			}
		}

		$hunt_group_enabled = check_str($_POST["hunt_group_enabled"]);
		$hunt_group_description = check_str($_POST["hunt_group_description"]);

		//remove invalid characters
		$hunt_group_cid_name_prefix = str_replace(":", "-", $hunt_group_cid_name_prefix);
		$hunt_group_cid_name_prefix = str_replace("\"", "", $hunt_group_cid_name_prefix);
		$hunt_group_cid_name_prefix = str_replace("@", "", $hunt_group_cid_name_prefix);
		$hunt_group_cid_name_prefix = str_replace("\\", "", $hunt_group_cid_name_prefix);
		$hunt_group_cid_name_prefix = str_replace("/", "", $hunt_group_cid_name_prefix);

		//set default
		if (strlen($hunt_group_caller_announce) == 0) { $hunt_group_caller_announce = "false"; }
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$hunt_group_uuid = check_str($_POST["hunt_group_uuid"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
		if (strlen($hunt_group_extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
		if (strlen($hunt_group_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		if (strlen($hunt_group_type) == 0) { $msg .= $text['message-required'].$text['label-strategy']."<br>\n"; }
		if (strlen($hunt_group_timeout) == 0) { $msg .= $text['message-required'].$text['label-timeout']."<br>\n"; }
		if (strlen($hunt_group_timeout_destination) == 0) { $msg .= $text['message-required'].$text['label-timeout_destination']."<br>\n"; }
		if (strlen($hunt_group_timeout_type) == 0) { $msg .= $text['message-required'].$text['label-timeout_type']."<br>\n"; }
		//if (strlen($hunt_group_ringback) == 0) { $msg .= $text['message-required'].$text['label-ring_back']."<br>\n"; }
		//if (strlen($hunt_group_cid_name_prefix) == 0) { $msg .= $text['message-required'].$text['label-caller_id_name_prefix']."<br>\n"; }
		//if (strlen($hunt_group_pin) == 0) { $msg .= $text['message-required'].$text['label-pin_number']."<br>\n"; }
		if (strlen($hunt_group_caller_announce) == 0) { $msg .= $text['message-required'].$text['label-caller_announce']."<br>\n"; }
		//if (strlen($hunt_group_user_list) == 0) { $msg .= $text['message-required'].$text['label-user_list']."<br>\n"; }
		//if (strlen($hunt_group_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
		//if (strlen($hunt_group_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
				//add to the table
					$dialplan_uuid = uuid();
					$hunt_group_uuid = uuid();
					$sql = "insert into v_hunt_groups ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "dialplan_uuid, ";
					$sql .= "hunt_group_uuid, ";
					$sql .= "hunt_group_extension, ";
					$sql .= "hunt_group_name, ";
					$sql .= "hunt_group_type, ";
					$sql .= "hunt_group_context, ";
					$sql .= "hunt_group_timeout, ";
					$sql .= "hunt_group_timeout_destination, ";
					$sql .= "hunt_group_timeout_type, ";
					$sql .= "hunt_group_ringback, ";
					$sql .= "hunt_group_cid_name_prefix, ";
					$sql .= "hunt_group_pin, ";
					$sql .= "hunt_group_caller_announce, ";
					$sql .= "hunt_group_user_list, ";
					$sql .= "hunt_group_enabled, ";
					$sql .= "hunt_group_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$dialplan_uuid', ";
					$sql .= "'$hunt_group_uuid', ";
					$sql .= "'$hunt_group_extension', ";
					$sql .= "'$hunt_group_name', ";
					$sql .= "'$hunt_group_type', ";
					$sql .= "'".$_SESSION['context']."', ";
					$sql .= "'$hunt_group_timeout', ";
					$sql .= "'$hunt_group_timeout_destination', ";
					$sql .= "'$hunt_group_timeout_type', ";
					$sql .= "'$hunt_group_ringback', ";
					$sql .= "'$hunt_group_cid_name_prefix', ";
					$sql .= "'$hunt_group_pin', ";
					$sql .= "'$hunt_group_caller_announce', ";
					$sql .= "'$hunt_group_user_list', ";
					$sql .= "'$hunt_group_enabled', ";
					$sql .= "'$hunt_group_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				//synchronize the xml config
					save_hunt_group_xml();

				//redirect the user
					require_once "resources/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=hunt_groups.php\">\n";
					echo "<div align='center'>\n";
					echo $text['message-add']."\n";
					echo "</div>\n";
					require_once "resources/footer.php";
					return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('hunt_group_edit')) {
				//update the table
					$sql = "update v_hunt_groups set ";
					$sql .= "hunt_group_extension = '$hunt_group_extension', ";
					$sql .= "hunt_group_name = '$hunt_group_name', ";
					$sql .= "hunt_group_type = '$hunt_group_type', ";
					$sql .= "hunt_group_context = '".$_SESSION['context']."', ";
					$sql .= "hunt_group_timeout = '$hunt_group_timeout', ";
					$sql .= "hunt_group_timeout_destination = '$hunt_group_timeout_destination', ";
					$sql .= "hunt_group_timeout_type = '$hunt_group_timeout_type', ";
					$sql .= "hunt_group_ringback = '$hunt_group_ringback', ";
					$sql .= "hunt_group_cid_name_prefix = '$hunt_group_cid_name_prefix', ";
					$sql .= "hunt_group_pin = '$hunt_group_pin', ";
					$sql .= "hunt_group_caller_announce = '$hunt_group_caller_announce', ";
					if (if_group("admin") || if_group("superadmin")) {
						$sql .= "hunt_group_user_list = '$hunt_group_user_list', ";
					}
					$sql .= "hunt_group_enabled = '$hunt_group_enabled', ";
					$sql .= "hunt_group_description = '$hunt_group_description' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and hunt_group_uuid = '$hunt_group_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);

				//update the dialplan entry
					require_once "resources/classes/dialplan.php";
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $_SESSION['domain_uuid'];
					$dialplan->app_uuid = $app_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_name = $hunt_group_name;
					//$dialplan->dialplan_continue = $dialplan_continue;
					//$dialplan->dialplan_order = '330';
					$dialplan->dialplan_context = $_SESSION['context'];
					$dialplan->dialplan_enabled = $hunt_group_enabled;
					$dialplan->dialplan_description = $hunt_group_description;
					$dialplan->dialplan_update();
					unset($dialplan);

					//update the condition
					$sql = "update v_dialplan_details set ";
					$sql .= "dialplan_detail_data = '^".$hunt_group_extension."$' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_detail_tag = 'condition' ";
					$sql .= "and dialplan_detail_type = 'destination_number' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$sql .= "and dialplan_detail_group = '1' ";
					$db->query($sql);
					unset($sql);

					//update the action
					$sql = "update v_dialplan_details set ";
					$sql .= "dialplan_detail_data = 'v_huntgroup_".$_SESSION['domain_name']."_".$hunt_group_extension.".lua', ";
					$sql .= "dialplan_detail_type = 'lua' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_detail_tag = 'action' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$sql .= "and dialplan_detail_type = 'lua' ";
					$sql .= "and dialplan_detail_group = '1' ";
					$db->query($sql);

				//synchronize the xml config
					save_hunt_group_xml();

				//delete the dialplan context from memcache
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}

				//rediret the user
					require_once "resources/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=hunt_groups.php\">\n";
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
		$hunt_group_uuid = $_GET["id"];
		$sql = "select * from v_hunt_groups ";
		$sql .= "where hunt_group_uuid = '$hunt_group_uuid' ";
		$sql .= "and domain_uuid = '$domain_uuid' ";
		$sql .- "hunt_group_enabled = 'true' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$hunt_group_extension = $row["hunt_group_extension"];
			$hunt_group_name = $row["hunt_group_name"];
			$hunt_group_type = $row["hunt_group_type"];
			$hunt_group_timeout = $row["hunt_group_timeout"];
			$hunt_group_timeout_destination = $row["hunt_group_timeout_destination"];
			$hunt_group_timeout_type = $row["hunt_group_timeout_type"];
			$hunt_group_ringback = $row["hunt_group_ringback"];
			$hunt_group_cid_name_prefix = $row["hunt_group_cid_name_prefix"];
			$hunt_group_pin = $row["hunt_group_pin"];
			$hunt_group_caller_announce = $row["hunt_group_caller_announce"];
			$hunt_group_user_list = $row["hunt_group_user_list"];
			$hunt_group_enabled = $row["hunt_group_enabled"];
			$hunt_group_description = $row["hunt_group_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	$page["title"] = $text['title-hunt_group'];


//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "<td align=\"left\">\n";
	echo "<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";

	echo "<table width='100%'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap>\n";
	echo "	<span class='vexpl'>\n";
	echo "		<strong>".$text['header-hunt_group']."</strong><br>\n";
	echo "	</span>\n";
	echo "</td>\n";
	echo "<td width='70%' align='right'>\n";
	if ($action != "add") {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"var new_ext = prompt('".$text['message_extension']."'); if (new_ext != null) { window.location='hunt_group_copy.php?id=".$hunt_group_uuid."&ext=' + new_ext; }\" value='".$text['button-copy']."'>\n";
	}
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='hunt_groups.php'\" value='".$text['button-back']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "		  ".$text['description-hunt_group']."\n";
	echo "		  </span><br />\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_name' maxlength='255' value=\"$hunt_group_name\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-extension'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_extension' maxlength='255' value=\"$hunt_group_extension\">\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-strategy'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <select class='formfld' name='hunt_group_type'>\n";
	echo "	 <option value=''></option>\n";
	if ($hunt_group_type == "simultaneous") {
		echo "	 <option value='simultaneous' selected='selected'>".$text['option-simultaneous']."</option>\n";
	}
	else {
		echo "	 <option value='simultaneous'>".$text['option-simultaneous']."</option>\n";
	}
	if ($hunt_group_type == "sequentially") {
		echo "	 <option value='sequentially' selected='selected'>".$text['option-sequential']."</option>\n";
	}
	else {
		echo "	 <option value='sequentially'>".$text['option-sequential']."</option>\n";
	}
	echo "	 </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-timeout'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_timeout' maxlength='255' value=\"$hunt_group_timeout\">\n";
	echo "<br />\n";
	echo $text['description-timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-timeout_destination'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_timeout_destination' maxlength='255' value=\"$hunt_group_timeout_destination\">\n";
	echo "<br />\n";
	echo $text['description-timeout_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-timeout_type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <select class='formfld' name='hunt_group_timeout_type'>\n";
	echo "	 <option value=''></option>\n";
	if ($hunt_group_timeout_type == "extension") {
		echo "	 <option value='extension' SELECTED >".$text['option-extension']."</option>\n";
	}
	else {
		echo "	 <option value='extension'>".$text['option-extension']."</option>\n";
	}
	if ($hunt_group_timeout_type == "voicemail") {
		echo "	 <option value='voicemail' SELECTED >".$text['option-voicemail']."</option>\n";
	}
	else {
		echo "	 <option value='voicemail'>".$text['option-voicemail']."</option>\n";
	}
	if ($hunt_group_timeout_type == "sip uri") {
		echo "	 <option value='sip uri' SELECTED >".$text['option-sip_uri']."</option>\n";
	}
	else {
		echo "	 <option value='sip uri'>".$text['option-sip_uri']."</option>\n";
	}
	echo "	 </select>\n";
	echo "<br />\n";
	echo $text['description-timeout_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-ring_back'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$select_options = "";
	if ($hunt_group_ringback == "\${us-ring}" || $hunt_group_ringback == "us-ring") {
		$select_options .= "		<option value='\${us-ring}' selected='selected'>us-ring</option>\n";
	}
	else {
		$select_options .= "		<option value='\${us-ring}'>us-ring</option>\n";
	}
	if ($hunt_group_ringback == "\${fr-ring}" || $hunt_group_ringback == "fr-ring") {
		$select_options .= "		<option value='\${fr-ring}' selected='selected'>fr-ring</option>\n";
	}
	else {
		$select_options .= "		<option value='\${fr-ring}'>fr-ring</option>\n";
	}
	if ($hunt_group_ringback == "\${uk-ring}" || $hunt_group_ringback == "uk-ring") {
		$select_options .= "		<option value='\${uk-ring}' selected='selected'>uk-ring</option>\n";
	}
	else {
		$select_options .= "		<option value='\${uk-ring}'>uk-ring</option>\n";
	}
	if ($hunt_group_ringback == "\${rs-ring}" || $hunt_group_ringback == "rs-ring") {
		$select_options .= "		<option value='\${rs-ring}' selected='selected'>rs-ring</option>\n";
	}
	else {
		$select_options .= "		<option value='\${rs-ring}'>rs-ring</option>\n";
	}
	require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
	$moh = new switch_music_on_hold;
	$moh->select_name = "hunt_group_ringback";
	$moh->select_value = $hunt_group_ringback;
	$moh->select_options = $select_options;
	echo $moh->select();

	echo "<br />\n";
	echo $text['description-ring_back']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-caller_id_name_prefix'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_cid_name_prefix' maxlength='255' value=\"$hunt_group_cid_name_prefix\">\n";
	echo "<br />\n";
	echo $text['description-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-pin_number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_pin' maxlength='255' value=\"$hunt_group_pin\">\n";
	echo "<br />\n";
	echo $text['description-pin_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (!$fp) {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	}
	if (switch_module_is_running($fp, 'mod_spidermonkey')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	 ".$text['label-caller_announce'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	 <select class='formfld' name='hunt_group_caller_announce'>\n";
		echo "	 <option value=''></option>\n";
		if ($hunt_group_caller_announce == "true") {
			echo "	 <option value='true' selected='selected'>".$text['option-true']."</option>\n";
		}
		else {
			echo "	 <option value='true'>".$text['option-true']."</option>\n";
		}
		if ($hunt_group_caller_announce == "false") {
			echo "	 <option value='false' selected='selected'>".$text['option-false']."</option>\n";
		}
		else {
			echo "	 <option value='false'>".$text['option-false']."</option>\n";
		}
		echo "	 </select>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (if_group("admin") || if_group("superadmin")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "		".$text['label-user_list'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		$onchange = "document.getElementById('hunt_group_user_list').value += document.getElementById('username').value + '\\n';";
		$table_name = 'v_users'; $field_name = 'username'; $field_current_value = ''; $sql_where_optional = "where domain_uuid = '$domain_uuid' and user_enabled = 'true' ";
		echo html_select_on_change($db, $table_name, $field_name, $sql_where_optional, $field_current_value, $onchange);
		echo "<br />\n";
		echo $text['description-user_list_select']."\n";
		echo "<br />\n";
		echo "<br />\n";
		//replace the vertical bar with a line feed to display in the textarea
		$hunt_group_user_list = trim($hunt_group_user_list, "|");
		$hunt_group_user_list_array = explode("|", $hunt_group_user_list);
		$hunt_group_user_list = '';
		foreach($hunt_group_user_list_array as $user){
			$hunt_group_user_list .= trim($user)."\n";
		}
		echo "		<textarea name=\"hunt_group_user_list\" id=\"hunt_group_user_list\" class=\"formfld\" cols=\"30\" rows=\"3\" wrap=\"off\">$hunt_group_user_list</textarea>\n";
		echo "		<br>\n";
		echo $text['description-user_list_textarea']."\n";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <select class='formfld' name='hunt_group_enabled'>\n";
	echo "	 <option value=''></option>\n";
	if ($hunt_group_enabled == "true" || strlen($hunt_group_enabled) == 0) {
		echo "	 <option value='true' selected >".$text['option-true']."</option>\n";
	}
	else {
		echo "	 <option value='true'>".$text['option-true']."</option>\n";
	}
	if ($hunt_group_enabled == "false") {
		echo "	 <option value='false' selected >".$text['option-false']."</option>\n";
	}
	else {
		echo "	 <option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	 </select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	 ".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_description' maxlength='255' value=\"$hunt_group_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
		echo "				<input type='hidden' name='hunt_group_uuid' value='$hunt_group_uuid'>\n";
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

//list hunt group destinations
	if ($action == "update") {

		echo "<div align='center'>";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";

		echo "<tr class='border'>\n";
		echo "	<td align=\"center\">\n";
		echo "		<br>";

		echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "	<td align='left'><p><span class='vexpl'>\n";
		echo "		<span class='red'><strong>".$text['header-hunt_group_destinations']."</strong></span><br>\n";
		echo "			".$text['description-hunt_group_destinations']."\n";
		echo "		</span></p></td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "<br />\n";

		$sql = " select * from v_hunt_group_destinations ";
		$sql .= " where domain_uuid = '$domain_uuid' ";
		$sql .= " and hunt_group_uuid = '$hunt_group_uuid' ";
		$sql .= " order by destination_order, destination_data asc";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);


		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		echo "<div align='center'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

		echo "<tr>\n";
		echo "<th align='center'>".$text['label-destination']."</th>\n";
		echo "<th align='center'>".$text['label-type']."</th>\n";
		echo "<th align='center'>".$text['label-profile']."</th>\n";
		echo "<th align='center'>".$text['label-order']."</th>\n";
		echo "<th align='center'>".$text['label-description']."</th>\n";
		echo "<td align='right' width='42'>\n";
		if (permission_exists('hunt_group_add')) {
			echo "	<a href='hunt_group_destination_edit.php?id2=".$hunt_group_uuid."' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
		}
		echo "</td>\n";
		echo "<tr>\n";

		if ($result_count > 0) {
			foreach($result as $row) {
				echo "<tr >\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['destination_data']."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;";
				switch($row['destination_type']) {
					case "extension" : echo $text['option-extension']; break;
					case "voicemail" : echo $text['option-voicemail']; break;
					case "sip uri" : echo $text['option-sip_uri']; break;
				}
				echo "</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['destination_profile']."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['destination_order']."</td>\n";
				echo "	<td valign='top' class='row_stylebg' width='30%'>".$row['destination_description']."&nbsp;</td>\n";
				echo "	<td valign='top' align='right'>\n";
				if (permission_exists('hunt_group_edit')) {
					echo "		<a href='hunt_group_destination_edit.php?id=".$row['hunt_group_destination_uuid']."&id2=".$hunt_group_uuid."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
				}
				if (permission_exists('hunt_group_delete')) {
					echo "		<a href='hunt_group_destination_delete.php?id=".$row['hunt_group_destination_uuid']."&id2=".$hunt_group_uuid."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
				}
				echo "	</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			} //end foreach
			unset($sql, $result, $row_count);
		} //end if results

		echo "<tr>\n";
		echo "<td colspan='6'>\n";
		echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
		echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
		echo "		<td width='33.3%' align='right'>\n";
		if (permission_exists('hunt_group_add')) {
			echo "			<a href='hunt_group_destination_edit.php?id2=".$hunt_group_uuid."' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
		}
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "</table>";
		echo "</div>";
		echo "<br><br>";
		echo "<br><br>";

		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";
		echo "<br><br>";
	} //end if update

//show the footer
	require_once "resources/footer.php";
?>
