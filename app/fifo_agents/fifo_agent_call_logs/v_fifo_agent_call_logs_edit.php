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
	$fifo_agent_call_log_id = check_str($_REQUEST["id"]);
}
else {
	$action = "add";
}

//POST to PHP variables
if (count($_POST)>0) {
	$resolution_code = check_str($_POST["resolution_code"]);
	$transaction_id = check_str($_POST["transaction_id"]);
	$action_item = check_str($_POST["action_item"]);
	$uuid = check_str($_POST["uuid"]);
	$notes = check_str($_POST["notes"]);
	$add_user = check_str($_POST["add_user"]);
	$add_date = check_str($_POST["add_date"]);
}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';

	if ($action == "update") {
		$fifo_agent_call_log_id = check_str($_POST["fifo_agent_call_log_id"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($resolution_code) == 0) { $msg .= "Please provide: Resolution Code<br>\n"; }
		//if (strlen($transaction_id) == 0) { $msg .= "Please provide: Transaction ID<br>\n"; }
		//if (strlen($action_item) == 0) { $msg .= "Please provide: Action Item<br>\n"; }
		//if (strlen($uuid) == 0) { $msg .= "Please provide: UUID<br>\n"; }
		//if (strlen($notes) == 0) { $msg .= "Please provide: Notes<br>\n"; }
		//if (strlen($add_user) == 0) { $msg .= "Please provide: Add User<br>\n"; }
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
			$sql = "insert into v_fifo_agent_call_logs ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "resolution_code, ";
			$sql .= "transaction_id, ";
			$sql .= "action_item, ";
			$sql .= "uuid, ";
			$sql .= "notes, ";
			$sql .= "add_user, ";
			$sql .= "add_date ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$resolution_code', ";
			$sql .= "'$transaction_id', ";
			$sql .= "'$action_item', ";
			$sql .= "'$uuid', ";
			$sql .= "'$notes', ";
			$sql .= "'$add_user', ";
			$sql .= "'$add_date' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_call_logs.php\">\n";
			echo "<div align='center'>\n";
			echo "Add Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_fifo_agent_call_logs set ";
			$sql .= "resolution_code = '$resolution_code', ";
			$sql .= "transaction_id = '$transaction_id', ";
			$sql .= "action_item = '$action_item', ";
			$sql .= "uuid = '$uuid', ";
			$sql .= "notes = '$notes', ";
			$sql .= "add_user = '$add_user', ";
			$sql .= "add_date = '$add_date' ";
			$sql .= "where domain_uuid = '$domain_uuid'";
			$sql .= "and fifo_agent_call_log_id = '$fifo_agent_call_log_id'";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_call_logs.php\">\n";
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
	$fifo_agent_call_log_id = $_GET["id"];
	$sql = "";
	$sql .= "select * from v_fifo_agent_call_logs ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and fifo_agent_call_log_id = '$fifo_agent_call_log_id' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$resolution_code = $row["resolution_code"];
		$transaction_id = $row["transaction_id"];
		$action_item = $row["action_item"];
		$uuid = $row["uuid"];
		$notes = $row["notes"];
		$add_user = $row["add_user"];
		$add_date = $row["add_date"];
		break; //limit to 1 row
	}
	unset ($prep_statement);
}

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
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Fifo Agent Call Log Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Fifo Agent Call Log Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_fifo_agent_call_logs.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "The agent call logs show a list of call calls agents have received.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Resolution Code:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='resolution_code' maxlength='255' value=\"$resolution_code\">\n";
	echo "<br />\n";
	echo "Enter the resolution code.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Transaction ID:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='transaction_id' maxlength='255' value=\"$transaction_id\">\n";
	echo "<br />\n";
	echo "Enter the Transaction ID.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Action Item:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='action_item' maxlength='255' value=\"$action_item\">\n";
	echo "<br />\n";
	echo "Enter the Action Item.\n";
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
	echo "	Notes:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' name='notes' rows='4'>$notes</textarea>\n";
	echo "<br />\n";
	echo "Enter the notes.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Add User:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='add_user' maxlength='255' value=\"$add_user\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Add Date:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='add_user' maxlength='255' value=\"$add_date\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='fifo_agent_call_log_id' value='$fifo_agent_call_log_id'>\n";
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
