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
if (if_group("agent") || if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//Action add or update
if (isset($_REQUEST["id"])) {
	$action = "update";
	$fifo_agent_id = check_str($_REQUEST["id"]);
}
else {
	$action = "add";
}

//POST to PHP variables
if (count($_POST)>0) {
	//$fifo_name = check_str($_POST["fifo_name"]);
	$fifo_agent_profile_id = check_str($_POST["fifo_agent_profile_id"]);
	$agent_username = $_SESSION["username"];
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
		if (strlen($fifo_agent_profile_id) == 0) { $msg .= "Please provide: profile<br>\n"; }
		//if (strlen($fifo_name) == 0) { $msg .= "Please provide: Queue Name<br>\n"; }
		//if (strlen($agent_username) == 0) { $msg .= "Please provide: Username<br>\n"; }
		//if (strlen($agent_priority) == 0) { $msg .= "Please provide: Agent Priority<br>\n"; }
		if (strlen($agent_contact_number) == 0) { $msg .= "Please provide: Contact Number<br>\n"; }
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

				$fifo_agent_profile_member_id = $_GET["id"];
				$sql = "";
				$sql .= "select * from v_fifo_agent_profile_members ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and fifo_agent_profile_id = '$fifo_agent_profile_id' ";
				$sql .= "and agent_username = '$agent_username' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$fifo_agent_profile_member_id = $row["fifo_agent_profile_member_id"];
					$fifo_agent_profile_id = $row["fifo_agent_profile_id"];
					$fifo_name = $row["fifo_name"];
					$agent_priority = $row["agent_priority"];
					$agent_status = '2'; //available
					$agent_last_call = 0;

					$sql = "insert into v_fifo_agents ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "fifo_name, ";
					$sql .= "agent_username, ";
					$sql .= "agent_priority, ";
					$sql .= "agent_status, ";
					$sql .= "agent_status_epoch, ";
					$sql .= "agent_last_call, ";
					$sql .= "agent_contact_number ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$fifo_name', ";
					$sql .= "'$agent_username', ";
					$sql .= "'$agent_priority', ";
					$sql .= "'$agent_status', ";
					$sql .= "'".time()."', ";
					$sql .= "'$agent_last_call', ";
					$sql .= "'$agent_contact_number' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

					//agent status log login 
						$agent_status = '1'; //login
						$sql = "insert into v_fifo_agent_status_logs ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "username, ";
						$sql .= "agent_status, ";
						$sql .= "add_date ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'$domain_uuid', ";
						$sql .= "'".$_SESSION["username"]."', ";
						$sql .= "'$agent_status', ";
						$sql .= "now() ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);

				}
				unset ($prep_statement);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_edit.php\">\n";
				echo "<div align='center'>\n";
				echo "Login Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")
		/*
			if ($action == "update") {
				$sql = "update v_fifo_agents set ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "fifo_name = '$fifo_name', ";
				$sql .= "agent_username = '$agent_username', ";
				$sql .= "agent_priority = '$agent_priority', ";
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
		*/
		} //if ($_POST["persistformvar"] != "true")

} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	/*
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$fifo_agent_id = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_fifo_agents ";
		$sql .= "where fifo_agent_id = '$fifo_agent_id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$fifo_name = $row["fifo_name"];
			$agent_username = $row["agent_username"];
			$agent_priority = $row["agent_priority"];
			$agent_contact_number = $row["agent_contact_number"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}
	*/

//begin the content
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
		echo "<td align='left' width='30%' nowrap><b>Agent Login</b></td>\n";
	}
	//if ($action == "update") {
	//	echo "<td align='left' width='30%' nowrap><b>Agent Login</b></td>\n";
	//}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_fifo_agents.php'\" value='Back'></td>\n";
	echo "</tr>\n";


	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Profile Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$sql = "";
	$sql .= "select * from v_fifo_agent_profiles ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	//$sql .= "and fifo_agent_profile_id = '$fifo_agent_profile_id' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$x = 0;
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	echo "<select name=\"fifo_agent_profile_id\" class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	foreach ($result as &$row) {
		$domain_uuid = $row["domain_uuid"];
		$profile_name = $row["profile_name"];
		$profile_desc = $row["profile_desc"];
		if ($row["fifo_agent_profile_id"] == $fifo_agent_profile_id) {
			echo "	<option value='".$row["fifo_agent_profile_id"]."' selected='selected'>".$row["profile_name"]."</option>\n";
		}
		else {
			echo "	<option value='".$row["fifo_agent_profile_id"]."'>".$row["profile_name"]."</option>\n";
		}
	}
	echo "</select>\n";
	unset ($prep_statement);


	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Contact Number:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='agent_contact_number' maxlength='255' value=\"$agent_contact_number\">\n";
	echo "<br />\n";
	echo "Enter the agent contact number.\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	//if ($action == "update") {
	//	echo "				<input type='hidden' name='fifo_agent_id' value='$fifo_agent_id'>\n";
	//}
	echo "				<input type='submit' name='submit' class='btn' value='Login'>\n";
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
