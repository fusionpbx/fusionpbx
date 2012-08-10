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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$fifo_agent_id = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$fifo_name = check_str($_POST["fifo_name"]);
		$agent_username = check_str($_POST["agent_username"]);
		$agent_priority = check_str($_POST["agent_priority"]);
		$agent_status = check_str($_POST["agent_status"]);
		$agent_last_call = check_str($_POST["agent_last_call"]);
		$agent_last_uuid = check_str($_POST["agent_last_uuid"]);
		$agent_contact_number = check_str($_POST["agent_contact_number"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';

	////recommend moving this to the config.php file
	$uploadtempdir = $_ENV["TEMP"]."\\";
	ini_set('upload_tmp_dir', $uploadtempdir);
	////$imagedir = $_ENV["TEMP"]."\\";
	////$filedir = $_ENV["TEMP"]."\\";

	if ($action == "update") {
		$fifo_agent_id = check_str($_POST["fifo_agent_id"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($fifo_name) == 0) { $msg .= "Please provide: Queue Name<br>\n"; }
		if (strlen($agent_username) == 0) { $msg .= "Please provide: Username<br>\n"; }
		if (strlen($agent_priority) == 0) { $msg .= "Please provide: Agent Priority<br>\n"; }
		if (strlen($agent_status) == 0) { $msg .= "Please provide: Status<br>\n"; }
		//if (strlen($agent_last_call) == 0) { $msg .= "Please provide: Last Call<br>\n"; }
		//if (strlen($agent_last_uuid) == 0) { $msg .= "Please provide: Last UUID<br>\n"; }
		//if (strlen($agent_contact_number) == 0) { $msg .= "Please provide: Contact Number<br>\n"; }
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

	//Add or update the database
	if ($_POST["persistformvar"] != "true") {
		if ($action == "add") {
			$sql = "insert into v_fifo_agents ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "fifo_name, ";
			$sql .= "agent_username, ";
			$sql .= "agent_priority, ";
			$sql .= "agent_status, ";
			$sql .= "agent_last_call, ";
			$sql .= "agent_last_uuid, ";
			$sql .= "agent_contact_number ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$fifo_name', ";
			$sql .= "'$agent_username', ";
			$sql .= "'$agent_priority', ";
			$sql .= "'$agent_status', ";
			$sql .= "'$agent_last_call', ";
			$sql .= "'$agent_last_uuid', ";
			$sql .= "'$agent_contact_number' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agents.php\">\n";
			echo "<div align='center'>\n";
			echo "Add Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_fifo_agents set ";
			$sql .= "domain_uuid = '$domain_uuid', ";
			$sql .= "fifo_name = '$fifo_name', ";
			$sql .= "agent_username = '$agent_username', ";
			$sql .= "agent_priority = '$agent_priority', ";
			$sql .= "agent_status = '$agent_status', ";
			$sql .= "agent_status_epoch = ".time()." ";
			$sql .= "agent_last_call = '$agent_last_call', ";
			$sql .= "agent_last_uuid = '$agent_last_uuid', ";
			$sql .= "agent_contact_number = '$agent_contact_number' ";
			$sql .= "where fifo_agent_id = '$fifo_agent_id'";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agents.php\">\n";
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
		$fifo_agent_id = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_fifo_agents ";
		$sql .= "where fifo_agent_id = '$fifo_agent_id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$fifo_name = $row["fifo_name"];
			$agent_username = $row["agent_username"];
			$agent_priority = $row["agent_priority"];
			$agent_status = $row["agent_status"];
			$agent_last_call = $row["agent_last_call"];
			$agent_last_uuid = $row["agent_last_uuid"];
			$agent_contact_number = $row["agent_contact_number"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//send the content to the browser
	require_once "includes/header.php";


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
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Agent Login</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Fifo Agent Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_fifo_agents.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "List the agents assigned to a Queue.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Queue Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//echo "	<input class='formfld' type='text' name='fifo_name' maxlength='255' value=\"$fifo_name\">\n";

		//generate the fifo name select list
		$sql = "";
		$sql .= "select * from v_dialplan_details ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$x = 0;
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		echo "<select name=\"fifo_name\" class='formfld'>\n";
		echo "<option value=\"\"></option>\n";
		foreach ($result as &$row) {
			if ($row["dialplan_detail_type"] == "fifo") {
				//if (strpos($row["dialplan_detail_data"], '@${domain_name} in') !== false) {
					//echo "rtrim(".$row["dialplan_detail_data"].", ' in') == ".$fifo_name."<br />";
					if (rtrim($row["dialplan_detail_data"], " in") == $fifo_name) {
						echo "		<option value='".rtrim($row["dialplan_detail_data"], " in")."' selected='selected'>".rtrim($row["dialplan_detail_data"], " in")."</option>\n";
					}
					else {
						echo "		<option value='".rtrim($row["dialplan_detail_data"], " in")."'>".rtrim($row["dialplan_detail_data"], " in")."</option>\n";
					}
				//}
			}
		}
		echo "</select>\n";
		unset ($prep_statement);

	echo "<br />\n";
	echo "Select the queue name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Username:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//echo "	<input class='formfld' type='text' name='agent_username' maxlength='255' value=\"$agent_username\">\n";

	//generate the user list
		$sql = "SELECT * FROM v_users ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and user_enabled = 'true' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();

		echo "<select name=\"agent_username\" class='formfld'>\n";
		echo "<option value=\"\"></option>\n";
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $field) {
			if ($agent_username == $field[username]) {
				echo "<option value='".$field[username]."' selected='selected'>".$field[username]."</option>\n";
			}
			else {
				echo "<option value='".$field[username]."'>".$field[username]."</option>\n";
			}
		}
		echo "</select>";
		unset($sql, $result);

	echo "<br />\n";
	echo "Select the username.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Agent Priority:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='agent_priority'>\n";
	echo "	<option value=''></option>\n";
	if ($agent_priority == "0") { 
		echo "	<option value='0' SELECTED >0</option>\n";
	}
	else {
		echo "	<option value='0'>0</option>\n";
	}
	if ($agent_priority == "1") { 
		echo "	<option value='1' SELECTED >1</option>\n";
	}
	else {
		echo "	<option value='1'>1</option>\n";
	}
	if ($agent_priority == "2") { 
		echo "	<option value='2' SELECTED >2</option>\n";
	}
	else {
		echo "	<option value='2'>2</option>\n";
	}
	if ($agent_priority == "3") { 
		echo "	<option value='3' SELECTED >3</option>\n";
	}
	else {
		echo "	<option value='3'>3</option>\n";
	}
	if ($agent_priority == "4") { 
		echo "	<option value='4' SELECTED >4</option>\n";
	}
	else {
		echo "	<option value='4'>4</option>\n";
	}
	if ($agent_priority == "5") { 
		echo "	<option value='5' SELECTED >5</option>\n";
	}
	else {
		echo "	<option value='5'>5</option>\n";
	}
	if ($agent_priority == "6") { 
		echo "	<option value='6' SELECTED >6</option>\n";
	}
	else {
		echo "	<option value='6'>6</option>\n";
	}
	if ($agent_priority == "7") { 
		echo "	<option value='7' SELECTED >7</option>\n";
	}
	else {
		echo "	<option value='7'>7</option>\n";
	}
	if ($agent_priority == "8") { 
		echo "	<option value='8' SELECTED >8</option>\n";
	}
	else {
		echo "	<option value='8'>8</option>\n";
	}
	if ($agent_priority == "9") { 
		echo "	<option value='9' SELECTED >9</option>\n";
	}
	else {
		echo "	<option value='9'>9</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select a priority.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Status:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//generate the agent status select list
		$sql = "SELECT var_name, var_value FROM v_vars ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and var_cat = 'Queues Agent Status' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		echo "<select name=\"agent_status\" class='formfld'>\n";
		echo "<option value=\"\"></option>\n";
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $field) {
			if ($field[var_value] == $agent_status) {
				echo "<option value='".$field[var_value]."' selected='selected'>".$field[var_name]."</option>\n";
			}
			else {
				echo "<option value='".$field[var_value]."'>".$field[var_name]."</option>\n";
			}
		}
		echo "</select>";
		/*
				foreach($result as $field) {
					$_SESSION["array_agent_status"][$field[var_value]] = $field[var_name];
				}

				$x=1;
				foreach($_SESSION["array_agent_status"] as $value) {
					echo "$x $value<br />\n";
					$x++;
				}
		*/

		if (!is_array($_SESSION["array_agent_status"])) {
			echo "not an array";
			foreach($result as $field) {
				$_SESSION["array_agent_status"][$field[var_value]] = $field[var_name];
			}
		}
		else {
			//echo "is an array";
			//unset($_SESSION["array_agent_status"]);
		}
		unset($sql, $result);

	/*
	echo "	<select class='formfld' name='agent_status'>\n";
	echo "	<option value=''></option>\n";
	if ($agent_status == "busy") { 
		echo "	<option value='busy' SELECTED >busy</option>\n";
	}
	else {
		echo "	<option value='busy'>busy</option>\n";
	}
	if ($agent_status == "break") { 
		echo "	<option value='break' SELECTED >break</option>\n";
	}
	else {
		echo "	<option value='break'>break</option>\n";
	}
	if ($agent_status == "waiting") { 
		echo "	<option value='waiting' SELECTED >waiting</option>\n";
	}
	else {
		echo "	<option value='waiting'>waiting</option>\n";
	}
	echo "	</select>\n";
	*/
	echo "<br />\n";
	echo "Enter the status of the Agent.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Last Call:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_last_call' maxlength='255' value='$agent_last_call'>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Last UUID:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='agent_last_uuid' maxlength='255' value=\"$agent_last_uuid\">\n";
	echo "<br />\n";
	echo "Enter the UUID for the last call.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Contact Number:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_contact_number' maxlength='255' value='$agent_contact_number'>\n";
	echo "<br />\n";
	echo "Enter the agent contact number.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='fifo_agent_id' value='$fifo_agent_id'>\n";
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
