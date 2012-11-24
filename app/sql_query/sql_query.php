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
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('sql_query_execute')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//show the header
	require_once "includes/header.php";

//pdo voicemail database connection
	require_once "v_sql_query_pdo.php";

//show the content
	//edit area
		echo "    <script language=\"javascript\" type=\"text/javascript\" src=\"".PROJECT_PATH."/includes/edit_area/edit_area_full.js\"></script>\n";
		echo "	<script language=\"Javascript\" type=\"text/javascript\">\n";
		echo "\n";
		echo "		editAreaLoader.init({\n";
		echo "			id: \"sql_cmd\"	// id of the textarea to transform //, |, help\n";
		echo "			,start_highlight: true\n";
		//echo "			,display: \"later\"\n";
		echo "			,font_size: \"8\"\n";
		echo "			,allow_toggle: false\n";
		echo "			,language: \"en\"\n";
		echo "			,syntax: \"sql\"\n";
		echo "			,toolbar: \"search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, |, help\" //new_document,\n";
		echo "			,plugins: \"charmap\"\n";
		echo "			,charmap_default: \"arrows\"\n";
		echo "\n";
		echo "    });\n";
		echo "    </script>";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' target='frame' action='v_sql_query_result.php' >";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap><b>SQL Query</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	if (strlen($_REQUEST['id']) > 0) {
		echo "	<input type='button' class='btn' name='' alt='backup' onclick=\"window.location='v_sql_backup.php?id=".$_REQUEST['id']."'\" value='Backup'>\n";
	}
	else {
		echo "	<input type='button' class='btn' name='' alt='backup' onclick=\"window.location='v_sql_backup.php'\" value='Backup'>\n";
	}
	echo "	<input type='button' class='btn' name='' alt='backup' onclick=\"window.location='v_sql_query_db.php'\" value='Database'>\n";
	echo "	<input type='button' class='btn' name='' alt='back' onClick=\"history.back()\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2' class='vtable' align='left'>\n";
	echo "	<textarea name='sql_cmd' id='sql_cmd' rows='7' class='txt' wrap='off'>$sql_cmd</textarea\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	
	/*
	echo "			DB: <select name='sql_db'>\n";
	echo "				<option value=''></option>\n";
	$sql = "";
	$sql .= "select * from v_databases ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		//$database_type = $row["database_type"];
		//$database_host = $row["database_host"];
		//$database_port = $row["database_port"];
		//$database_name = $row["database_name"];
		//$database_username = $row["database_username"];
		//$database_password = $row["database_password"];
		//$database_path = $row["database_path"];
		//$database_description = $row["database_description"];
		echo "			<option value='".$row["database_uuid"]."'>".$row["database_host"]." - ".$row["database_name"]."</option>\n";
	}
	unset ($prep_statement);
	echo "			</select>\n";
	*/

	echo "			Type: <select name='sql_type'>\n";
	echo "			<option value='default'>default</option>\n";
	echo "			<option value='csv'>csv</option>\n";
	echo "			<option value='sql insert into'>sql insert into</option>\n";
	echo "			</select>\n";
	echo "			&nbsp;\n";
	echo "			&nbsp;\n";
	echo "			Table: \n";
	echo "			<select name='table_name'>\n";
	echo "			<option value=''></option>\n";
	if ($db_type == "sqlite") {
		$sql = "SELECT name FROM sqlite_master ";
		$sql .= "WHERE type='table' ";
		$sql .= "order by name;";
	}
	if ($db_type == "pgsql") {
		$sql = "select table_name as name ";
		$sql .= "from information_schema.tables ";
		$sql .= "where table_schema='public' ";
		$sql .= "and table_type='BASE TABLE' ";
		$sql .= "order by table_name ";
	}
	if ($db_type == "mysql") {
		$sql = "show tables";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		echo "			<option value='".$row['name']."'>".$row['name']."</option>\n";
	}
	echo "			</select>\n";
	echo "			<input type='hidden' name='id' value='".$_REQUEST['id']."'>\n";
	echo "			<input type='submit' name='submit' class='btn' value='Execute'>\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

	echo "<iframe id='frame' width='100%' height='400' FRAMEBORDER='0' name='frame' style='background-color : #FFFFFF;'></iframe>\n";

//show the footer
	include "includes/require.php";
	require_once "includes/footer.php";
?>