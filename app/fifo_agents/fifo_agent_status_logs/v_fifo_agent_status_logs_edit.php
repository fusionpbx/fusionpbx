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

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$fifo_agent_status_log_id = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//POST to PHP variables
	if (count($_POST)>0) {
		$username = check_str($_POST["username"]);
		$agent_status = check_str($_POST["agent_status"]);
		$uuid = check_str($_POST["uuid"]);
		$add_date = check_str($_POST["add_date"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$fifo_agent_status_log_id = check_str($_POST["fifo_agent_status_log_id"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($username) == 0) { $msg .= "Please provide: Username<br>\n"; }
		//if (strlen($agent_status) == 0) { $msg .= "Please provide: Status<br>\n"; }
		//if (strlen($uuid) == 0) { $msg .= "Please provide: UUID<br>\n"; }
		//if (strlen($add_date) == 0) { $msg .= "Please provide: Add Date<br>\n"; }
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
				$sql = "insert into v_fifo_agent_status_logs ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "username, ";
				$sql .= "agent_status, ";
				$sql .= "uuid, ";
				$sql .= "add_date ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$username', ";
				$sql .= "'$agent_status', ";
				$sql .= "'$uuid', ";
				$sql .= "now() ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_logs.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_fifo_agent_status_logs set ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "username = '$username', ";
				$sql .= "agent_status = '$agent_status', ";
				$sql .= "uuid = '$uuid', ";
				$sql .= "add_date = '$add_date' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and fifo_agent_status_log_id = '$fifo_agent_status_log_id'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_logs.php\">\n";
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
		$fifo_agent_status_log_id = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_fifo_agent_status_logs ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and fifo_agent_status_log_id = '$fifo_agent_status_log_id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$username = $row["username"];
			$agent_status = $row["agent_status"];
			$uuid = $row["uuid"];
			$add_date = $row["add_date"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}


//send the content
	require_once "includes/header.php";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";


	echo "<form method='post' name='frm' action=''>\n";

	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Fifo Agent Status Log Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Fifo Agent Status Log Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_fifo_agent_status_logs.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "Agent Status History<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	domain_uuid:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='domain_uuid' maxlength='255' value='$domain_uuid'>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Username:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='username' maxlength='255' value=\"$username\">\n";
	echo "<br />\n";
	echo "Enter the Username.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Status:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='agent_status' maxlength='255' value=\"$agent_status\">\n";
	echo "<br />\n";
	echo "Enter the agent status.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	UUID:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='uuid' maxlength='255' value=\"$uuid\">\n";
	echo "<br />\n";
	echo "Enter the UUID.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Add Date:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='add_date' maxlength='255' value=\"$add_date\">\n";
	echo "	<br />\n";
	echo "	Enter the date.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='fifo_agent_status_log_id' value='$fifo_agent_status_log_id'>\n";
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
