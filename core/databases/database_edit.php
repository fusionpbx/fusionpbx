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
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('database_add') || permission_exists('database_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
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
		//if (strlen($database_driver) == 0) { $msg .= $text['message-required'].$text['label-driver']."<br>\n"; }
		//if (strlen($database_type) == 0) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
		//if (strlen($database_host) == 0) { $msg .= $text['message-required'].$text['label-host']."<br>\n"; }
		//if (strlen($database_port) == 0) { $msg .= $text['message-required'].$text['label-port']."<br>\n"; }
		//if (strlen($database_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		//if (strlen($database_username) == 0) { $msg .= $text['message-required'].$text['label-username']."<br>\n"; }
		//if (strlen($database_password) == 0) { $msg .= $text['message-required'].$text['label-password']."<br>\n"; }
		//if (strlen($database_path) == 0) { $msg .= $text['message-required'].$text['label-path']."<br>\n"; }
		//if (strlen($database_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
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
				$_SESSION["message"] = $text['message-add'];
				header("Location: databases.php");
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
				$_SESSION["message"] = $text['message-update'];
				header("Location: databases.php");
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
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-database-edit'];
	}
	if ($action == "add") {
		$document['title'] = $text['title-database-add'];
	}

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
		echo "<td align=\"left\" width='30%' nowrap=\"nowrap\"><b>".$text['header-database-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align=\"left\" width='30%' nowrap=\"nowrap\"><b>".$text['header-database-edit']."</b></td>\n";
	}
	echo "<td width='70%' align=\"right\">";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='databases.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	if ($action == "add") {
		echo $text['description-database-add'];
	}
	if ($action == "update") {
		echo $text['description-database-edit'];
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-driver'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='database_driver'>\n";
	echo "	<option value=''></option>\n";
	if ($database_driver == "sqlite") {
		echo "	&nbsp; &nbsp;<option value='sqlite' selected='selected'>SQLite</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='sqlite'>SQLite</option>\n";
	}
	if ($database_driver == "pgsql") {
		echo "	&nbsp; &nbsp;<option value='pgsql' selected='selected'>PostgreSQL</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='pgsql'>PostgreSQL</option>\n";
	}
	if ($database_driver == "mysql") {
		echo "	&nbsp; &nbsp;<option value='mysql' selected='selected'>MySQL</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mysql'>MySQL</option>\n";
	}
	if ($database_driver == "odbc") {
		echo "	&nbsp; &nbsp;<option value='odbc' selected='selected'>ODBC</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='odbc'>ODBC</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-driver']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='database_type'>\n";
	echo "	<option value=''></option>\n";
	if ($database_type == "sqlite") {
		echo "	&nbsp; &nbsp;<option value='sqlite' selected='selected'>SQLite</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='sqlite'>SQLite</option>\n";
	}
	if ($database_type == "pgsql") {
		echo "	&nbsp; &nbsp;<option value='pgsql' selected='selected'>PostgreSQL</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='pgsql'>PostgreSQL</option>\n";
	}
	if ($database_type == "mysql") {
		echo "	&nbsp; &nbsp;<option value='mysql' selected='selected'>MySQL</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mysql'>MySQL</option>\n";
	}
	if ($database_type == "mssql") {
		echo "	&nbsp; &nbsp;<option value='mssql' selected='selected'>Microsoft SQL Server</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mssql'>Microsoft SQL Server</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-host'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_host' maxlength='255' value=\"$database_host\">\n";
	echo "<br />\n";
	echo $text['description-host']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-port'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_port' maxlength='255' value=\"$database_port\">\n";
	echo "<br />\n";
	echo $text['description-port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_name' maxlength='255' value=\"$database_name\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-username'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_username' maxlength='255' value=\"$database_username\">\n";
	echo "<br />\n";
	echo $text['description-username']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-password'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_password' maxlength='255' value=\"$database_password\">\n";
	echo "<br />\n";
	echo $text['description-password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-path'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_path' maxlength='255' value=\"$database_path\">\n";
	echo "<br />\n";
	echo $text['description-path']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_description' maxlength='255' value=\"$database_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='database_uuid' value='$database_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "resources/footer.php";
?>