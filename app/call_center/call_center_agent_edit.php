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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('call_center_agents_add') || permission_exists('call_center_agents_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$call_center_agent_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$agent_name = check_str($_POST["agent_name"]);
		$agent_type = check_str($_POST["agent_type"]);
		$agent_call_timeout = check_str($_POST["agent_call_timeout"]);
		$agent_contact = check_str($_POST["agent_contact"]);
		$agent_status = check_str($_POST["agent_status"]);
		$agent_logout = check_str($_POST["agent_logout"]);
		$agent_no_answer_delay_time = check_str($_POST["agent_no_answer_delay_time"]);
		$agent_max_no_answer = check_str($_POST["agent_max_no_answer"]);
		$agent_wrap_up_time = check_str($_POST["agent_wrap_up_time"]);
		$agent_reject_delay_time = check_str($_POST["agent_reject_delay_time"]);
		$agent_busy_delay_time = check_str($_POST["agent_busy_delay_time"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';

	if ($action == "update") {
		$call_center_agent_uuid = check_str($_POST["call_center_agent_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($agent_name) == 0) { $msg .= "Please provide: Agent Name<br>\n"; }
		//if (strlen($agent_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($agent_call_timeout) == 0) { $msg .= "Please provide: Call Timeout<br>\n"; }
		//if (strlen($agent_contact) == 0) { $msg .= "Please provide: Contact<br>\n"; }
		//if (strlen($agent_status) == 0) { $msg .= "Please provide: Status<br>\n"; }
		//if (strlen($agent_logout) == 0) { $msg .= "Please provide: Logout<br>\n"; }
		//if (strlen($agent_no_answer_delay_time) == 0) { $msg .= "Please provide: No Answer Delay Time<br>\n"; }
		//if (strlen($agent_max_no_answer) == 0) { $msg .= "Please provide: Max No Answer<br>\n"; }
		//if (strlen($agent_wrap_up_time) == 0) { $msg .= "Please provide: Wrap Up Time<br>\n"; }
		//if (strlen($agent_reject_delay_time) == 0) { $msg .= "Please provide: Reject Delay Time<br>\n"; }
		//if (strlen($agent_busy_delay_time) == 0) { $msg .= "Please provide: Busy Delay Time<br>\n"; }
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

	//get and then set the complete agent_contact with the call_timeout and when necessary confirm
		$tmp_confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1";
		if(strstr($agent_contact, '}') === FALSE) {
			//not found
			if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
				//add the call_timeout
				$tmp_agent_contact = "{call_timeout=".$agent_call_timeout."}".$agent_contact;
			}
			else {
				//add the call_timeout and confirm
				$tmp_agent_contact = $tmp_first.',call_timeout='.$agent_call_timeout.$tmp_last;
				$tmp_agent_contact = "{".$tmp_confirm.",call_timeout=".$agent_call_timeout."}".$agent_contact;
			}
		}
		else {
			//found
			if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
				//not found
				if(stristr($agent_contact, 'call_timeout') === FALSE) {
					//add the call_timeout
					$tmp_pos = strrpos($agent_contact, "}");
					$tmp_first = substr($agent_contact, 0, $tmp_pos);
					$tmp_last = substr($agent_contact, $tmp_pos); 
					$tmp_agent_contact = $tmp_first.',call_timeout='.$agent_call_timeout.$tmp_last;
				}
				else {
					//the string has the call timeout
					$tmp_agent_contact = $agent_contact;
				}
			}
			else {
				//found
				$tmp_pos = strrpos($agent_contact, "}");
				$tmp_first = substr($agent_contact, 0, $tmp_pos);
				$tmp_last = substr($agent_contact, $tmp_pos);
				if(stristr($agent_contact, 'call_timeout') === FALSE) {
					//add the call_timeout and confirm
					$tmp_agent_contact = $tmp_first.','.$tmp_confirm.',call_timeout='.$agent_call_timeout.$tmp_last;
				}
				else {
					//add confirm
					$tmp_agent_contact = $tmp_first.','.$tmp_confirm.$tmp_last;
				}
			}
		}

	//set the user_status
		$sql  = "update v_users set ";
		$sql .= "user_status = '".$agent_status."' ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and username = '".$agent_name."' ";
 		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();

	//add the agent
		//setup the event socket connection
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		//add the agent using event socket
			if ($fp) {
				//add the agent
					$cmd = "api callcenter_config agent add ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']." ".$agent_type;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set contact
					$cmd = "api callcenter_config agent set contact ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']." ".$tmp_agent_contact;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set status
					$cmd = "api callcenter_config agent set status ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']." '".$agent_status."'";
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set reject_delay_time
					$cmd = "api callcenter_config agent set reject_delay_time ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']." ".$agent_reject_delay_time;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set busy_delay_time
					$cmd = "api callcenter_config agent set busy_delay_time ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']." ".$agent_busy_delay_time;
					$response = event_socket_request($fp, $cmd);
				//agent set no_answer_delay_time
					$cmd = "api callcenter_config agent set no_answer_delay_time ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']." ".$agent_no_answer_delay_time;
					$response = event_socket_request($fp, $cmd);
				//agent set max_no_answer
					$cmd = "api callcenter_config agent set max_no_answer ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']." ".$agent_max_no_answer;
					$response = event_socket_request($fp, $cmd);
				//agent set wrap_up_time
					$cmd = "api callcenter_config agent set wrap_up_time ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']." ".$agent_wrap_up_time;
					$response = event_socket_request($fp, $cmd);
			}

	//add or update the database
	if ($_POST["persistformvar"] != "true") {
		if ($action == "add") {
			//add the agent to the database
				$call_center_agent_uuid = uuid();
				$sql = "insert into v_call_center_agents ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "call_center_agent_uuid, ";
				$sql .= "agent_name, ";
				$sql .= "agent_type, ";
				$sql .= "agent_call_timeout, ";
				$sql .= "agent_contact, ";
				$sql .= "agent_status, ";
				$sql .= "agent_logout, ";
				$sql .= "agent_no_answer_delay_time, ";
				$sql .= "agent_max_no_answer, ";
				$sql .= "agent_wrap_up_time, ";
				$sql .= "agent_reject_delay_time, ";
				$sql .= "agent_busy_delay_time ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$call_center_agent_uuid', ";
				$sql .= "'$agent_name', ";
				$sql .= "'$agent_type', ";
				$sql .= "'$agent_call_timeout', ";
				$sql .= "'$agent_contact', ";
				$sql .= "'$agent_status', ";
				$sql .= "'$agent_logout', ";
				$sql .= "'$agent_no_answer_delay_time', ";
				$sql .= "'$agent_max_no_answer', ";
				$sql .= "'$agent_wrap_up_time', ";
				$sql .= "'$agent_reject_delay_time', ";
				$sql .= "'$agent_busy_delay_time' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//syncrhonize configuration
				save_call_center_xml();

			//redirect the user
				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=call_center_agents.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_call_center_agents set ";
			$sql .= "agent_name = '$agent_name', ";
			$sql .= "agent_type = '$agent_type', ";
			$sql .= "agent_call_timeout = '$agent_call_timeout', ";
			$sql .= "agent_contact = '$agent_contact', ";
			$sql .= "agent_status = '$agent_status', ";
			$sql .= "agent_logout = '$agent_logout', ";
			$sql .= "agent_no_answer_delay_time = '$agent_no_answer_delay_time', ";
			$sql .= "agent_max_no_answer = '$agent_max_no_answer', ";
			$sql .= "agent_wrap_up_time = '$agent_wrap_up_time', ";
			$sql .= "agent_reject_delay_time = '$agent_reject_delay_time', ";
			$sql .= "agent_busy_delay_time = '$agent_busy_delay_time' ";
			$sql .= "where domain_uuid = '$domain_uuid'";
			$sql .= "and call_center_agent_uuid = '$call_center_agent_uuid'";
			$db->exec(check_sql($sql));
			unset($sql);

			//syncrhonize configuration
			save_call_center_xml();

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=call_center_agents.php\">\n";
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
		$call_center_agent_uuid = $_GET["id"];
		$sql = "select * from v_call_center_agents ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and call_center_agent_uuid = '$call_center_agent_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$agent_name = $row["agent_name"];
			$agent_type = $row["agent_type"];
			$agent_call_timeout = $row["agent_call_timeout"];
			$agent_contact = $row["agent_contact"];
			$agent_status = $row["agent_status"];
			$agent_logout = $row["agent_logout"];
			$agent_no_answer_delay_time = $row["agent_no_answer_delay_time"];
			$agent_max_no_answer = $row["agent_max_no_answer"];
			$agent_wrap_up_time = $row["agent_wrap_up_time"];
			$agent_reject_delay_time = $row["agent_reject_delay_time"];
			$agent_busy_delay_time = $row["agent_busy_delay_time"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//set default values
	if (strlen($agent_type) == 0) { $agent_type = "callback"; }
	if (strlen($agent_call_timeout) == 0) { $agent_call_timeout = 10; }
	if (strlen($agent_max_no_answer) == 0) { $agent_max_no_answer = "3"; }
	if (strlen($agent_wrap_up_time) == 0) { $agent_wrap_up_time = "10"; }
	if (strlen($agent_no_answer_delay_time) == 0) { $agent_no_answer_delay_time = "10"; }
	if (strlen($agent_reject_delay_time) == 0) { $agent_reject_delay_time = "10"; }
	if (strlen($agent_busy_delay_time) == 0) { $agent_busy_delay_time = "60"; }

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Call Center Agent Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Call Center Agent Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='call_center_agents.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	//echo "Call Center agents settings.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Agent Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//---- Begin Select List --------------------
	$sql = "SELECT * FROM v_users ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();

	echo "<select id=\"agent_name\" name=\"agent_name\" class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//$catcount = count($result);
	foreach($result as $field) {
		if ($field[username] == $agent_name) {
			echo "<option value='".$field[username]."' selected='selected'>".$field[username]."</option>\n";
		}
		else {
			echo "<option value='".$field[username]."'>".$field[username]."</option>\n";
		}
	}
	echo "</select>";
	unset($sql, $result);
	//---- End Select List --------------------
	echo "<br />\n";
	echo "Select the agents name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='agent_type' maxlength='255' value=\"$agent_type\">\n";
	echo "<br />\n";
	echo "Enter the agent type.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Call Timeout:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_call_timeout' maxlength='255' value='$agent_call_timeout'>\n";
	echo "<br />\n";
	echo "Enter the call timeout.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Contact:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
	switch_select_destination("call_center_contact", "", "agent_contact", $agent_contact, "", "");

	echo "<br />\n";
	echo "Select the contact number.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Status:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='agent_status'>\n";
	echo "	<option value=''></option>\n";
	if ($agent_status == "Logged Out") { 
		echo "	<option value='Logged Out' SELECTED >Logged Out</option>\n";
	}
	else {
		echo "	<option value='Logged Out'>Logged Out</option>\n";
	}
	if ($agent_status == "Available") { 
		echo "	<option value='Available' SELECTED >Available</option>\n";
	}
	else {
		echo "	<option value='Available'>Available</option>\n";
	}
	if ($agent_status == "Available (On Demand)") { 
		echo "	<option value='Available (On Demand)' SELECTED >Available (On Demand)</option>\n";
	}
	else {
		echo "	<option value='Available (On Demand)'>Available (On Demand)</option>\n";
	}
	if ($agent_status == "On Break") { 
		echo "	<option value='On Break' SELECTED >On Break</option>\n";
	}
	else {
		echo "	<option value='On Break'>On Break</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the agent status.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	No Answer Delay Time:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_no_answer_delay_time' maxlength='255' value='$agent_no_answer_delay_time'>\n";
	echo "<br />\n";
	echo "Enter the agent no answer delay time in seconds.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Max No Answer:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_max_no_answer' maxlength='255' value='$agent_max_no_answer'>\n";
	echo "<br />\n";
	echo "Enter max no answer.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Wrap Up Time:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_wrap_up_time' maxlength='255' value='$agent_wrap_up_time'>\n";
	echo "<br />\n";
	echo "Enter the wrap up time.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Reject Delay Time:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_reject_delay_time' maxlength='255' value='$agent_reject_delay_time'>\n";
	echo "<br />\n";
	echo "Enter the reject delay time.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Busy Delay Time:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_busy_delay_time' maxlength='255' value='$agent_busy_delay_time'>\n";
	echo "<br />\n";
	echo "Enter the agent busy delay time.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Agent Logout:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_logout' maxlength='255' value='$agent_logout'>\n";
	echo "<br />\n";
	echo "Enter the auto agent logout time in 00:00 format. Requires a service to enforce the logout time.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='call_center_agent_uuid' value='$call_center_agent_uuid'>\n";
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

//footer
	require_once "includes/footer.php";
?>
