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
		$server_setting_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["server_uuid"]) > 0) {
	$server_uuid = check_str($_GET["server_uuid"]);
}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$server_uuid = check_str($_POST["server_uuid"]);
		$server_setting_category = check_str($_POST["server_setting_category"]);
		$server_setting_value = check_str($_POST["server_setting_value"]);
		$server_setting_name = check_str($_POST["server_setting_name"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$server_setting_uuid = check_str($_POST["server_setting_uuid"]);
	}

	//check for all required data
		//if (strlen($server_uuid) == 0) { $msg .= "Please provide: server_uuid<br>\n"; }
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($server_setting_category) == 0) { $msg .= "Please provide: Category<br>\n"; }
		//if (strlen($server_setting_value) == 0) { $msg .= "Please provide: Value<br>\n"; }
		//if (strlen($server_setting_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
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
				$sql = "insert into v_server_settings ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "server_uuid, ";
				$sql .= "server_uuid, ";
				$sql .= "domain_uuid, ";
				$sql .= "server_setting_category, ";
				$sql .= "server_setting_value, ";
				$sql .= "server_setting_name ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$server_uuid', ";
				$sql .= "'$server_uuid', ";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$server_setting_category', ";
				$sql .= "'$server_setting_value', ";
				$sql .= "'$server_setting_name' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=servers_edit.php?id=$server_uuid\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_server_settings set ";
				$sql .= "server_uuid = '$server_uuid', ";
				$sql .= "server_uuid = '$server_uuid', ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "server_setting_category = '$server_setting_category', ";
				$sql .= "server_setting_value = '$server_setting_value', ";
				$sql .= "server_setting_name = '$server_setting_name' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and server_setting_uuid = '$server_setting_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=servers_edit.php?id=$server_uuid\">\n";
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
		$server_setting_uuid = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_server_settings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and server_setting_uuid = '$server_setting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$server_uuid = $row["server_uuid"];
			$server_setting_category = $row["server_setting_category"];
			$server_setting_value = $row["server_setting_value"];
			$server_setting_name = $row["server_setting_name"];
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
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Server Setting Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Server Setting Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='servers_edit.php?id=$server_uuid'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "Server settings are assigned to Domains.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Category:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='server_setting_category' maxlength='255' value=\"$server_setting_category\">\n";
	echo "<br />\n";
	echo "Enter the category.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='server_setting_name' maxlength='255' value=\"$server_setting_name\">\n";
	echo "<br />\n";
	echo "Enter the name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Value:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='server_setting_value' maxlength='255' value=\"$server_setting_value\">\n";
	echo "<br />\n";
	echo "Enter the value.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='server_uuid' value='$server_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='server_setting_uuid' value='$server_setting_uuid'>\n";
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

//include the footer
	require_once "includes/footer.php";
?>