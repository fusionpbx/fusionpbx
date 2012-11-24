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
if (permission_exists('call_center_tiers_add') || permission_exists('call_center_tiers_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$call_center_tier_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$agent_name = check_str($_POST["agent_name"]);
		$queue_name = check_str($_POST["queue_name"]);
		$tier_level = check_str($_POST["tier_level"]);
		$tier_position = check_str($_POST["tier_position"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$call_center_tier_uuid = check_str($_POST["call_center_tier_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($agent_name) == 0) { $msg .= "Please provide: Agent Name<br>\n"; }
		//if (strlen($queue_name) == 0) { $msg .= "Please provide: Queue Name<br>\n"; }
		//if (strlen($tier_level) == 0) { $msg .= "Please provide: Tier Level<br>\n"; }
		//if (strlen($tier_position) == 0) { $msg .= "Please provide: Tier Position<br>\n"; }
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

	//add the agent
		//setup the event socket connection
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		//add the agent using event socket
			if ($fp) {
				//get the domain using the $domain_uuid
					$tmp_domain = $_SESSION['domains'][$domain_uuid]['domain_name'];
				//syntax
					//callcenter_config tier add [queue_name] [agent_name] [level] [position]
					//callcenter_config tier set state [queue_name] [agent_name] [state]
					//callcenter_config tier set level [queue_name] [agent_name] [level]
					//callcenter_config tier set position [queue_name] [agent_name] [position]
				//add the agent
					$cmd = "api callcenter_config tier add ".$queue_name."@".$tmp_domain." ".$agent_name."@".$tmp_domain." ".$tier_level." ".$tier_position;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set level
					$cmd = "api callcenter_config tier set level ".$queue_name."@".$tmp_domain." ".$agent_name."@".$tmp_domain." ".$tier_level;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set position
					$cmd = "api callcenter_config tier set position ".$queue_name."@".$tmp_domain." ".$agent_name."@".$tmp_domain." ".$tier_position;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
			}

	//add or update the database
	if ($_POST["persistformvar"] != "true") {
		if ($action == "add") {
			$call_center_tier_uuid = uuid();
			$sql = "insert into v_call_center_tiers ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "call_center_tier_uuid, ";
			$sql .= "agent_name, ";
			$sql .= "queue_name, ";
			$sql .= "tier_level, ";
			$sql .= "tier_position ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$call_center_tier_uuid', ";
			$sql .= "'$agent_name', ";
			$sql .= "'$queue_name', ";
			$sql .= "'$tier_level', ";
			$sql .= "'$tier_position' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			//syncrhonize configuration
			save_call_center_xml();

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=call_center_tiers.php\">\n";
			echo "<div align='center'>\n";
			echo "Add Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_call_center_tiers set ";
			$sql .= "domain_uuid = '$domain_uuid', ";
			$sql .= "agent_name = '$agent_name', ";
			$sql .= "queue_name = '$queue_name', ";
			$sql .= "tier_level = '$tier_level', ";
			$sql .= "tier_position = '$tier_position' ";
			$sql .= "where call_center_tier_uuid = '$call_center_tier_uuid'";
			$db->exec(check_sql($sql));
			unset($sql);

			//syncrhonize configuration
			save_call_center_xml();

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=call_center_tiers.php\">\n";
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
		$call_center_tier_uuid = $_GET["id"];
		$sql = "select * from v_call_center_tiers ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and call_center_tier_uuid = '$call_center_tier_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$agent_name = $row["agent_name"];
			$queue_name = $row["queue_name"];
			$tier_level = $row["tier_level"];
			$tier_position = $row["tier_position"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}


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
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Call Center Tier Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Call Center Tier Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='call_center_tiers.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "List all tiers. Tiers assign agents to queues.<br /><br />\n";
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
			echo "<option value='".$field['username']."' selected='selected'>".$field['username']."</option>\n";
		}
		else {
			echo "<option value='".$field['username']."'>".$field['username']."</option>\n";
		}
	}
	echo "</select>";
	unset($sql, $result);
	//---- End Select List --------------------

	echo "<br />\n";
	echo "Select the agent name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Queue Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	//---- Begin Select List --------------------
	$sql = "SELECT * FROM v_call_center_queues ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "order by queue_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();

	echo "<select id=\"queue_name\" name=\"queue_name\" class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//$catcount = count($result);
	foreach($result as $field) {
		if ($field[queue_name] == $queue_name) {
			echo "<option value='".$field['queue_name']."' selected='selected'>".$field['queue_name']."</option>\n";
		}
		else {
			echo "<option value='".$field['queue_name']."'>".$field['queue_name']."</option>\n";
		}
	}
	echo "</select>";
	unset($sql, $result);
	//---- End Select List --------------------

	echo "<br />\n";
	echo "Select the queue name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Tier Level:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='tier_level'>\n";
	//echo "	<option value=''></option>\n";
	if ($tier_level == "1") { 
		echo "	<option value='1' selected='selected' >1</option>\n";
	}
	else {
		echo "	<option value='1'>1</option>\n";
	}
	if ($tier_level == "2") { 
		echo "	<option value='2' selected='selected' >2</option>\n";
	}
	else {
		echo "	<option value='2'>2</option>\n";
	}
	if ($tier_level == "3") { 
		echo "	<option value='3' selected='selected' >3</option>\n";
	}
	else {
		echo "	<option value='3'>3</option>\n";
	}
	if ($tier_level == "4") { 
		echo "	<option value='4' selected='selected' >4</option>\n";
	}
	else {
		echo "	<option value='4'>4</option>\n";
	}
	if ($tier_level == "5") { 
		echo "	<option value='5' selected='selected' >5</option>\n";
	}
	else {
		echo "	<option value='5'>5</option>\n";
	}
	if ($tier_level == "6") { 
		echo "	<option value='6' selected='selected' >6</option>\n";
	}
	else {
		echo "	<option value='6'>6</option>\n";
	}
	if ($tier_level == "7") { 
		echo "	<option value='7' selected='selected' >7</option>\n";
	}
	else {
		echo "	<option value='7'>7</option>\n";
	}
	if ($tier_level == "8") { 
		echo "	<option value='8' selected='selected' >8</option>\n";
	}
	else {
		echo "	<option value='8'>8</option>\n";
	}
	if ($tier_level == "9") { 
		echo "	<option value='9' selected='selected' >9</option>\n";
	}
	else {
		echo "	<option value='9'>9</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the tier level.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Tier Position:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='tier_position'>\n";
	//echo "	<option value=''></option>\n";
	if ($tier_position == "1") { 
		echo "	<option value='1' selected='selected' >1</option>\n";
	}
	else {
		echo "	<option value='1'>1</option>\n";
	}
	if ($tier_position == "2") { 
		echo "	<option value='2' selected='selected' >2</option>\n";
	}
	else {
		echo "	<option value='2'>2</option>\n";
	}
	if ($tier_position == "3") { 
		echo "	<option value='3' selected='selected' >3</option>\n";
	}
	else {
		echo "	<option value='3'>3</option>\n";
	}
	if ($tier_position == "4") { 
		echo "	<option value='4' selected='selected' >4</option>\n";
	}
	else {
		echo "	<option value='4'>4</option>\n";
	}
	if ($tier_position == "5") { 
		echo "	<option value='5' selected='selected' >5</option>\n";
	}
	else {
		echo "	<option value='5'>5</option>\n";
	}
	if ($tier_position == "6") { 
		echo "	<option value='6' selected='selected' >6</option>\n";
	}
	else {
		echo "	<option value='6'>6</option>\n";
	}
	if ($tier_position == "7") { 
		echo "	<option value='7' selected='selected' >7</option>\n";
	}
	else {
		echo "	<option value='7'>7</option>\n";
	}
	if ($tier_position == "8") { 
		echo "	<option value='8' selected='selected' >8</option>\n";
	}
	else {
		echo "	<option value='8'>8</option>\n";
	}
	if ($tier_position == "9") { 
		echo "	<option value='9' selected='selected' >9</option>\n";
	}
	else {
		echo "	<option value='9'>9</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the tier position.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='call_center_tier_uuid' value='$call_center_tier_uuid'>\n";
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

require_once "includes/footer.php";
?>