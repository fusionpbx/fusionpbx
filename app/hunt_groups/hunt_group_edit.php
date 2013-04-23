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
require_once "includes/require.php";
require_once "includes/checkauth.php";
require_once "includes/paging.php";

//check permissions
	if (permission_exists('hunt_group_add') || permission_exists('hunt_group_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
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
		//$hunt_group_context = check_str($_POST["hunt_group_context"]);
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
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($hunt_group_extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		if (strlen($hunt_group_name) == 0) { $msg .= "Please provide: Hunt Group Name<br>\n"; }
		if (strlen($hunt_group_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($hunt_group_context) == 0) { $msg .= "Please provide: Context<br>\n"; }
		if (strlen($hunt_group_timeout) == 0) { $msg .= "Please provide: Timeout<br>\n"; }
		if (strlen($hunt_group_timeout_destination) == 0) { $msg .= "Please provide: Timeout Destination<br>\n"; }
		if (strlen($hunt_group_timeout_type) == 0) { $msg .= "Please provide: Timeout Type<br>\n"; }
		//if (strlen($hunt_group_ringback) == 0) { $msg .= "Please provide: Ring Back<br>\n"; }
		//if (strlen($hunt_group_cid_name_prefix) == 0) { $msg .= "Please provide: CID Prefix<br>\n"; }
		//if (strlen($hunt_group_pin) == 0) { $msg .= "Please provide: PIN<br>\n"; }
		if (strlen($hunt_group_caller_announce) == 0) { $msg .= "Please provide: Caller Announce<br>\n"; }
		//if (strlen($hunt_group_user_list) == 0) { $msg .= "Please provide: User List<br>\n"; }
		//if (strlen($hunt_group_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($hunt_group_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
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
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=hunt_groups.php\">\n";
					echo "<div align='center'>\n";
					echo "Add Complete\n";
					echo "</div>\n";
					require_once "includes/footer.php";
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
					require_once "includes/classes/switch_dialplan.php";
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
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=hunt_groups.php\">\n";
					echo "<div align='center'>\n";
					echo "Update Complete\n";
					echo "</div>\n";
					require_once "includes/footer.php";
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
			//$hunt_group_context = $row["hunt_group_context"];
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
	require_once "includes/header.php";

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
	echo "		<strong>Hunt Group</strong><br>\n";
	echo "	</span>\n";
	echo "</td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='copy' onclick=\"if (confirm('Do you really want to copy this?')){window.location='hunt_group_copy.php?id=".$hunt_group_uuid."';}\" value='Copy'>\n";
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='hunt_groups.php'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "		  A Hunt Group is a list of destinations that can be called in sequence or simultaneously. \n";
	echo "		  </span><br />\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 Hunt Group Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_name' maxlength='255' value=\"$hunt_group_name\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_extension' maxlength='255' value=\"$hunt_group_extension\">\n";
	echo "<br />\n";
	echo "example: 7002\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <select class='formfld' name='hunt_group_type'>\n";
	echo "	 <option value=''></option>\n";
	if ($hunt_group_type == "simultaneous") { 
		echo "	 <option value='simultaneous' selected='selected'>simultaneous</option>\n";
	}
	else {
		echo "	 <option value='simultaneous'>simultaneous</option>\n";
	}
	if ($hunt_group_type == "sequentially") { 
		echo "	 <option value='sequentially' selected='selected'>sequentially</option>\n";
	}
	else {
		echo "	 <option value='sequentially'>sequentially</option>\n";
	}
	//if ($hunt_group_type == "call_forward") { 
	//	echo "	 <option value='call_forward' selected='selected'>call_forward</option>\n";
	//}
	//else {
	//	echo "	 <option value='call_forward'>call_forward</option>\n";
	//}
	//if ($hunt_group_type == "dnd") { 
	//	echo "	 <option value='dnd' selected='selected'>dnd</option>\n";
	//}
	//else {
	//	echo "	 <option value='dnd'>dnd</option>\n";
	//}
	//if ($hunt_group_type == "follow_me_sequence") { 
	//	echo "	 <option value='follow_me_sequence' selected='selected'>follow_me_sequence</option>\n";
	//}
	//else {
	//	echo "	 <option value='follow_me_sequence'>follow_me_sequence</option>\n";
	//}
	//if ($hunt_group_type == "follow_me_simultaneous") { 
	//	echo "	 <option value='follow_me_simultaneous' selected='selected'>follow_me_simultaneous</option>\n";
	//}
	//else {
	//	echo "	 <option value='follow_me_simultaneous'>follow_me_simultaneous</option>\n";
	//}
	echo "	 </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 Timeout:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_timeout' maxlength='255' value=\"$hunt_group_timeout\">\n";
	echo "<br />\n";
	echo "The timeout sets the time in seconds to continue to call before timing out. \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 Timeout Destination:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_timeout_destination' maxlength='255' value=\"$hunt_group_timeout_destination\">\n";
	echo "<br />\n";
	echo "Destination. example: 1001\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 Timeout Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <select class='formfld' name='hunt_group_timeout_type'>\n";
	echo "	 <option value=''></option>\n";
	if ($hunt_group_timeout_type == "extension") { 
		echo "	 <option value='extension' SELECTED >extension</option>\n";
	}
	else {
		echo "	 <option value='extension'>extension</option>\n";
	}
	if ($hunt_group_timeout_type == "voicemail") { 
		echo "	 <option value='voicemail' SELECTED >voicemail</option>\n";
	}
	else {
		echo "	 <option value='voicemail'>voicemail</option>\n";
	}
	if ($hunt_group_timeout_type == "sip uri") { 
		echo "	 <option value='sip uri' SELECTED >sip uri</option>\n";
	}
	else {
		echo "	 <option value='sip uri'>sip uri</option>\n";
	}
	echo "	 </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 Ring Back:\n";
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
	echo "Defines what the caller will hear while the destination is being called. The choices are music (music on hold) ring (ring tone.) default: music \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	 CID Prefix:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_cid_name_prefix' maxlength='255' value=\"$hunt_group_cid_name_prefix\">\n";
	echo "<br />\n";
	echo "Set a prefix on the caller ID name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	 PIN:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_pin' maxlength='255' value=\"$hunt_group_pin\">\n";
	echo "<br />\n";
	echo "If this is provided then the caller will be required to enter the PIN number.\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (!$fp) {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	}
	if (switch_module_is_running($fp, 'mod_spidermonkey')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	 Caller Announce:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	 <select class='formfld' name='hunt_group_caller_announce'>\n";
		echo "	 <option value=''></option>\n";
		if ($hunt_group_caller_announce == "true") { 
			echo "	 <option value='true' selected='selected'>true</option>\n";
		}
		else {
			echo "	 <option value='true'>true</option>\n";
		}
		if ($hunt_group_caller_announce == "false") { 
			echo "	 <option value='false' selected='selected'>false</option>\n";
		}
		else {
			echo "	 <option value='false'>false</option>\n";
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
		echo "		User List:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		$onchange = "document.getElementById('hunt_group_user_list').value += document.getElementById('username').value + '\\n';";
		$table_name = 'v_users'; $field_name = 'username'; $field_current_value = ''; $sql_where_optional = "where domain_uuid = '$domain_uuid' and user_enabled = 'true' ";
		echo html_select_on_change($db, $table_name, $field_name, $sql_where_optional, $field_current_value, $onchange);
		echo "<br />\n";
		echo "Use the select list to add users to the user list. This will assign users to this extension.\n";
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
		echo "Assign the users that are can manage this hunt group extension.\n";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	 Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <select class='formfld' name='hunt_group_enabled'>\n";
	echo "	 <option value=''></option>\n";
	if ($hunt_group_enabled == "true" || strlen($hunt_group_enabled) == 0) { 
		echo "	 <option value='true' selected >true</option>\n";
	}
	else {
		echo "	 <option value='true'>true</option>\n";
	}
	if ($hunt_group_enabled == "false") { 
		echo "	 <option value='false' selected >false</option>\n";
	}
	else {
		echo "	 <option value='false'>false</option>\n";
	}
	echo "	 </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	 Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	 <input class='formfld' type='text' name='hunt_group_description' maxlength='255' value=\"$hunt_group_description\">\n";
	echo "<br />\n";
	echo "You may enter a description here for your reference (not parsed). \n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
		echo "				<input type='hidden' name='hunt_group_uuid' value='$hunt_group_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
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
		echo "		<span class='red'><strong>\n";
		echo "			Destinations<br />\n";
		echo "		</strong></span>\n";
		echo "			The following destinations will be called.\n";
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
		echo "<th align='center'>Destination</th>\n";
		echo "<th align='center'>Type</th>\n";
		echo "<th align='center'>Profile</th>\n";
		echo "<th align='center'>Order</th>\n";
		echo "<th align='center'>Description</th>\n";
		echo "<td align='right' width='42'>\n";
		if (permission_exists('hunt_group_add')) {
			echo "	<a href='hunt_group_destination_edit.php?id2=".$hunt_group_uuid."' alt='add'>$v_link_label_add</a>\n";
		}
		echo "</td>\n";
		echo "<tr>\n";

		if ($result_count > 0) {
			foreach($result as $row) {
				echo "<tr >\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['destination_data']."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['destination_type']."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['destination_profile']."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['destination_order']."</td>\n";
				echo "	<td valign='top' class='row_stylebg' width='30%'>".$row['destination_description']."&nbsp;</td>\n";
				echo "	<td valign='top' align='right'>\n";
				if (permission_exists('hunt_group_edit')) {
					echo "		<a href='hunt_group_destination_edit.php?id=".$row['hunt_group_destination_uuid']."&id2=".$hunt_group_uuid."' alt='edit'>$v_link_label_edit</a>\n";
				}
				if (permission_exists('hunt_group_delete')) {
					echo "		<a href='hunt_group_destination_delete.php?id=".$row['hunt_group_destination_uuid']."&id2=".$hunt_group_uuid."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
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
			echo "			<a href='hunt_group_destination_edit.php?id2=".$hunt_group_uuid."' alt='add'>$v_link_label_add</a>\n";
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
	require_once "includes/footer.php";
?>
