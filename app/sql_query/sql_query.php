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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('sql_query_execute')) {
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

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-sql_query'];

//pdo voicemail database connection
	require_once "sql_query_pdo.php";

//show the content
	//edit area
		echo "    <script language=\"javascript\" type=\"text/javascript\" src=\"".PROJECT_PATH."/resources/edit_area/edit_area_full.js\"></script>\n";
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

	echo "<form method='post' target='frame' action='sql_query_result.php' >";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap><b>".$text['header-sql_query']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onClick=\"history.back()\" value='".$text['button-back']."'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-select_database']."' onclick=\"window.location='sql_query_db.php'\" value='".$text['button-select_database']."'>\n";
	if (strlen($_REQUEST['id']) > 0) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-backup']."' onclick=\"window.location='sql_backup.php?id=".$_REQUEST['id']."'\" value='".$text['button-backup']."'>\n";
	}
	else {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-backup']."' onclick=\"window.location='sql_backup.php'\" value='".$text['button-backup']."'>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2' style='padding: none;' align='left'><br>\n";
	echo "	<textarea name='sql_cmd' id='sql_cmd' rows='10' class='formfld' style='width: 100%;' wrap='off'>$sql_cmd</textarea\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right' style='padding-top: 10px;'>\n";

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


	echo "			".$text['label-table'].": \n";
	echo "			<select name='table_name' class='formfld' style='width: auto;'>\n";
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
		$row = array_values($row);
		echo "			<option value='".$row[0]."'>".$row[0]."</option>\n";
	}
	echo "			</select>\n";
	echo "			&nbsp;\n";
	echo "			&nbsp;\n";
	echo "			".$text['label-result_type'].": <select name='sql_type' class='formfld' style='width: auto;'>\n";
	echo "			<option value='default'>".$text['option-result_type_view']."</option>\n";
	echo "			<option value='csv'>".$text['option-result_type_csv']."</option>\n";
	echo "			<option value='sql insert into'>".$text['option-result_type_insert']."</option>\n";
	echo "			</select>\n";
	echo "			<input type='hidden' name='id' value='".$_REQUEST['id']."'>\n";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-execute']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table><br>";
	echo "</form>";

	echo "</div>";

	echo "<iframe id='frame' height='400' FRAMEBORDER='0' name='frame' style='width: 100%; background-color : #FFFFFF; border: 1px solid #c0c0c0;'></iframe>\n";

//show the footer
	require_once "resources/footer.php";
?>