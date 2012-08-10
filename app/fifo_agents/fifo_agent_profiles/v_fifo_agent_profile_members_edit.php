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
if (if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//Action add or update
if (isset($_REQUEST["id"])) {
	$action = "update";
	$fifo_agent_profile_member_id = check_str($_REQUEST["id"]);
}
else {
	$action = "add";
}

if (strlen($_GET["fifo_agent_profile_id"]) > 0) {
	$fifo_agent_profile_id = check_str($_GET["fifo_agent_profile_id"]);
}

//POST to PHP variables
if (count($_POST)>0) {
	//$domain_uuid = check_str($_POST["domain_uuid"]);
	$fifo_agent_profile_id = check_str($_POST["fifo_agent_profile_id"]);
	$fifo_name = check_str($_POST["fifo_name"]);
	$agent_priority = check_str($_POST["agent_priority"]);
	$agent_username = check_str($_POST["agent_username"]);
}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';

	////recommend moving this to the config.php file
	$uploadtempdir = $_ENV["TEMP"]."\\";
	ini_set('upload_tmp_dir', $uploadtempdir);
	////$imagedir = $_ENV["TEMP"]."\\";
	////$filedir = $_ENV["TEMP"]."\\";

	if ($action == "update") {
		$fifo_agent_profile_member_id = check_str($_POST["fifo_agent_profile_member_id"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($fifo_agent_profile_id) == 0) { $msg .= "Please provide: fifo_agent_profile_id<br>\n"; }
		//if (strlen($fifo_name) == 0) { $msg .= "Please provide: FIFO Name<br>\n"; }
		//if (strlen($agent_priority) == 0) { $msg .= "Please provide: Agent Priority<br>\n"; }
		//if (strlen($agent_username) == 0) { $msg .= "Please provide: Agent<br>\n"; }
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
	if ($action == "add") {
		$sql = "insert into v_fifo_agent_profile_members ";
		$sql .= "(";
		$sql .= "domain_uuid, ";
		$sql .= "fifo_agent_profile_id, ";
		$sql .= "fifo_name, ";
		$sql .= "agent_priority, ";
		$sql .= "agent_username ";
		$sql .= ")";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$fifo_agent_profile_id', ";
		$sql .= "'$fifo_name', ";
		$sql .= "'$agent_priority', ";
		$sql .= "'$agent_username' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

		require_once "includes/header.php";
		echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_profiles_edit.php?id=$fifo_agent_profile_id\">\n";
		echo "<div align='center'>\n";
		echo "Add Complete\n";
		echo "</div>\n";
		require_once "includes/footer.php";
		return;
	} //if ($action == "add")

	if ($action == "update") {
		$sql = "update v_fifo_agent_profile_members set ";
		$sql .= "fifo_agent_profile_id = '$fifo_agent_profile_id', ";
		$sql .= "fifo_name = '$fifo_name', ";
		$sql .= "agent_priority = '$agent_priority', ";
		$sql .= "agent_username = '$agent_username' ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and fifo_agent_profile_member_id = '$fifo_agent_profile_member_id'";
		$db->exec(check_sql($sql));
		unset($sql);

		require_once "includes/header.php";
		echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_profiles_edit.php?id=$fifo_agent_profile_id\">\n";
		echo "<div align='center'>\n";
		echo "Update Complete\n";
		echo "</div>\n";
		require_once "includes/footer.php";
		return;
	} //if ($action == "update")
} //if ($_POST["persistformvar"] != "true") { 

} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
	$fifo_agent_profile_member_id = $_GET["id"];
	$sql = "";
	$sql .= "select * from v_fifo_agent_profile_members ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and fifo_agent_profile_member_id = '$fifo_agent_profile_member_id' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$fifo_agent_profile_id = $row["fifo_agent_profile_id"];
		$fifo_name = $row["fifo_name"];
		$agent_priority = $row["agent_priority"];
		$agent_username = $row["agent_username"];
		break; //limit to 1 row
	}
	unset ($prep_statement);
}


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
		echo "<td align='left' width='30%' nowrap><b>Agent Member List Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap><b>Agent Member List Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_fifo_agent_profiles_edit.php?id=$fifo_agent_profile_id'\" value='Back'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
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
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Agent:\n";
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
	echo "Select the agent.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Agent Priority:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='agent_priority'>\n";
	echo "	<option value=''></option>\n";
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
	if ($agent_priority == "10") { 
		echo "	<option value='10' SELECTED >10</option>\n";
	}
	else {
		echo "	<option value='10'>10</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the agent priority.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='fifo_agent_profile_id' value='$fifo_agent_profile_id'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='fifo_agent_profile_member_id' value='$fifo_agent_profile_member_id'>\n";
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
