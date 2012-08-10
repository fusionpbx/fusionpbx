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
	$fifo_agent_language_id = check_str($_REQUEST["id"]);
}
else {
	$action = "add";
}

//POST to PHP variables
	if (count($_POST)>0) {
		$username = check_str($_POST["username"]);
		$language = check_str($_POST["language"]);
		$proficiency = check_str($_POST["proficiency"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';

	if ($action == "update") {
		$fifo_agent_language_id = check_str($_POST["fifo_agent_language_id"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($username) == 0) { $msg .= "Please provide: Username<br>\n"; }
		//if (strlen($language) == 0) { $msg .= "Please provide: Language<br>\n"; }
		//if (strlen($proficiency) == 0) { $msg .= "Please provide: Proficiency<br>\n"; }
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
			$sql = "insert into v_fifo_agent_languages ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "username, ";
			$sql .= "language, ";
			$sql .= "proficiency ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$username', ";
			$sql .= "'$language', ";
			$sql .= "'$proficiency' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_languages.php\">\n";
			echo "<div align='center'>\n";
			echo "Add Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "add")

		if ($action == "update") {
			$sql = "update v_fifo_agent_languages set ";
			$sql .= "domain_uuid = '$domain_uuid', ";
			$sql .= "username = '$username', ";
			$sql .= "language = '$language', ";
			$sql .= "proficiency = '$proficiency' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and fifo_agent_language_id = '$fifo_agent_language_id'";
			$db->exec(check_sql($sql));
			unset($sql);

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_languages.php\">\n";
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
		$fifo_agent_language_id = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_fifo_agent_languages ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and fifo_agent_language_id = '$fifo_agent_language_id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$username = $row["username"];
			$language = $row["language"];
			$proficiency = $row["proficiency"];
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
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Fifo Agent Language Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Fifo Agent Language Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_fifo_agent_languages.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "Matches the Agent with languages they can speak with their proficiency level.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Username:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	//generate the user list
		$sql = "SELECT * FROM v_users ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and user_enabled = 'true' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();

		echo "<select name=\"username\" class='formfld'>\n";
		echo "<option value=\"\"></option>\n";
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $field) {
			if ($username == $field[username]) {
				echo "<option value='".$field[username]."' selected='selected'>".$field[username]."</option>\n";
			}
			else {
				echo "<option value='".$field[username]."'>".$field[username]."</option>\n";
			}
		}
		echo "</select>";
		unset($sql, $result);

	echo "<br />\n";
	echo "Select the Username from the list.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Language:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	//generate the language select list
		$sql = "SELECT var_name, var_value FROM v_vars ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and var_cat = 'Languages' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		echo "<select name=\"language\" class='formfld'>\n";
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

	echo "<br />\n";
	echo "Enter the two letter language code.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Proficiency:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "	<select class='formfld' name='proficiency'>\n";
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
	echo "Select the language proficiency level.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='fifo_agent_language_id' value='$fifo_agent_language_id'>\n";
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
