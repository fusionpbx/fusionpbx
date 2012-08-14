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
if (if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$database_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//clear the values
	$database_driver = '';
	$database_type = '';
	$database_host = '';
	$database_port = '';
	$database_name = '';
	$database_username = '';
	$database_password = '';
	$database_path = '';
	$database_description = '';

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$database_driver = check_str($_POST["database_driver"]);
		$database_type = check_str($_POST["database_type"]);
		$database_host = check_str($_POST["database_host"]);
		$database_port = check_str($_POST["database_port"]);
		$database_name = check_str($_POST["database_name"]);
		$database_username = check_str($_POST["database_username"]);
		$database_password = check_str($_POST["database_password"]);
		$database_path = check_str($_POST["database_path"]);
		$database_description = check_str($_POST["database_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$database_uuid = check_str($_POST["database_uuid"]);
	}

	//check for all required data
		//if (strlen($database_driver) == 0) { $msg .= "Please provide: Driver<br>\n"; }
		//if (strlen($database_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($database_host) == 0) { $msg .= "Please provide: Host<br>\n"; }
		//if (strlen($database_port) == 0) { $msg .= "Please provide: Port<br>\n"; }
		//if (strlen($database_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		//if (strlen($database_username) == 0) { $msg .= "Please provide: Username<br>\n"; }
		//if (strlen($database_password) == 0) { $msg .= "Please provide: Password<br>\n"; }
		//if (strlen($database_path) == 0) { $msg .= "Please provide: Path<br>\n"; }
		//if (strlen($database_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
			//add the data
				$database_uuid = uuid();
				$sql = "insert into v_databases ";
				$sql .= "(";
				//$sql .= "domain_uuid, ";
				$sql .= "database_uuid, ";
				$sql .= "database_driver, ";
				$sql .= "database_type, ";
				$sql .= "database_host, ";
				$sql .= "database_port, ";
				$sql .= "database_name, ";
				$sql .= "database_username, ";
				$sql .= "database_password, ";
				$sql .= "database_path, ";
				$sql .= "database_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				//$sql .= "'$domain_uuid', ";
				$sql .= "'$database_uuid', ";
				$sql .= "'$database_driver', ";
				$sql .= "'$database_type', ";
				$sql .= "'$database_host', ";
				$sql .= "'$database_port', ";
				$sql .= "'$database_name', ";
				$sql .= "'$database_username', ";
				$sql .= "'$database_password', ";
				$sql .= "'$database_path', ";
				$sql .= "'$database_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//set the defaults
				require_once "app_defaults.php";

			//redirect the browser
				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=databases.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
		} //if ($action == "add")

		if ($action == "update") {
			//udpate the database
				$sql = "update v_databases set ";
				$sql .= "database_type = '$database_type', ";
				$sql .= "database_driver = '$database_driver', ";
				$sql .= "database_host = '$database_host', ";
				$sql .= "database_port = '$database_port', ";
				$sql .= "database_name = '$database_name', ";
				$sql .= "database_username = '$database_username', ";
				$sql .= "database_password = '$database_password', ";
				$sql .= "database_path = '$database_path', ";
				$sql .= "database_description = '$database_description' ";
				$sql .= "where database_uuid = '$database_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);

			//set the defaults
				$domains_processed = 1;
				require_once "app_defaults.php";

			//redirect the browser
				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=databases.php\">\n";
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
		$database_uuid = $_GET["id"];
		$sql = "select * from v_databases ";
		$sql .= "where database_uuid = '$database_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$database_driver = $row["database_driver"];
			$database_type = $row["database_type"];
			$database_host = $row["database_host"];
			$database_port = $row["database_port"];
			$database_name = $row["database_name"];
			$database_username = $row["database_username"];
			$database_password = $row["database_password"];
			$database_path = $row["database_path"];
			$database_description = $row["database_description"];
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
	echo "<table width='100%'  border='0' cellpadding='3' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align=\"left\" width='30%' nowrap=\"nowrap\"><b>Database Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align=\"left\" width='30%' nowrap=\"nowrap\"><b>Database Edit</b></td>\n";
	}
	echo "<td width='70%' align=\"right\"><input type='button' class='btn' name='' alt='back' onclick=\"window.location='databases.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo "Database connection information.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Driver:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='database_driver'>\n";
	echo "	<option value=''></option>\n";
	if ($database_driver == "sqlite") {
		echo "	&nbsp; &nbsp;<option value='sqlite' selected='selected'>sqlite</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='sqlite'>sqlite</option>\n";
	}
	if ($database_driver == "pgsql") {
		echo "	&nbsp; &nbsp;<option value='pgsql' selected='selected'>pgsql</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='pgsql'>pgsql</option>\n";
	}
	if ($database_driver == "mysql") {
		echo "	&nbsp; &nbsp;<option value='mysql' selected='selected'>mysql</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mysql'>mysql</option>\n";
	}
	if ($database_driver == "odbc") {
		echo "	&nbsp; &nbsp;<option value='odbc' selected='selected'>odbc</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='odbc'>odbc</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the database driver.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='database_type'>\n";
	echo "	<option value=''></option>\n";
	if ($database_type == "sqlite") {
		echo "	&nbsp; &nbsp;<option value='sqlite' selected='selected'>sqlite</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='sqlite'>sqlite</option>\n";
	}
	if ($database_type == "pgsql") {
		echo "	&nbsp; &nbsp;<option value='pgsql' selected='selected'>pgsql</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='pgsql'>pgsql</option>\n";
	}
	if ($database_type == "mysql") {
		echo "	&nbsp; &nbsp;<option value='mysql' selected='selected'>mysql</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mysql'>mysql</option>\n";
	}
	if ($database_type == "mssql") {
		echo "	&nbsp; &nbsp;<option value='mssql' selected='selected'>mssql</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mssql'>mssql</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the database type.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Host:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_host' maxlength='255' value=\"$database_host\">\n";
	echo "<br />\n";
	echo "Enter the host name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Port:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_port' maxlength='255' value=\"$database_port\">\n";
	echo "<br />\n";
	echo "Enter the port number.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_name' maxlength='255' value=\"$database_name\">\n";
	echo "<br />\n";
	echo "Enter the database name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Username:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_username' maxlength='255' value=\"$database_username\">\n";
	echo "<br />\n";
	echo "Enter the database username.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Password:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_password' maxlength='255' value=\"$database_password\">\n";
	echo "<br />\n";
	echo "Enter the database password.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Path:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_path' maxlength='255' value=\"$database_path\">\n";
	echo "<br />\n";
	echo "Enter the database file path.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_description' maxlength='255' value=\"$database_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='database_uuid' value='$database_uuid'>\n";
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