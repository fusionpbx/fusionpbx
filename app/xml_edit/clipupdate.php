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
if (permission_exists('xml_editor_save')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "config.php";

if (count($_POST)>0) {
    $id = check_str($_POST["id"]);
    $clip_name = check_str($_POST["clip_name"]);
    $clip_folder = check_str($_POST["clip_folder"]);
    $clip_text_start = check_str($_POST["clip_text_start"]);
    $clip_text_end = check_str($_POST["clip_text_end"]);
    $clip_desc = check_str($_POST["clip_desc"]);
    $clip_order = check_str($_POST["clip_order"]);

    //sql update
    $sql  = "update v_clip_library set ";
    $sql .= "clip_name = '$clip_name', ";
    $sql .= "clip_folder = '$clip_folder', ";
    $sql .= "clip_text_start = '$clip_text_start', ";
    $sql .= "clip_text_end = '$clip_text_end', ";
    $sql .= "clip_desc = '$clip_desc', ";
    $sql .= "clip_order = '$clip_order' ";
    $sql .= "where id = '$id' ";
    $count = $db->exec(check_sql($sql));
    //echo "Affected Rows: ".$count;


    //edit: make sure the meta redirect url is correct 
    require_once "header.php";
    echo "<meta http-equiv=\"refresh\" content=\"1;url=clipoptions.php\">\n";
    echo "Update Complete";
    require_once "footer.php";
    return;
}
else {
	//get data from the db
		$id = $_GET["id"];

		$sql = "";
		$sql .= "select * from v_clip_library ";
		$sql .= "where id = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();

		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$clip_name = $row["clip_name"];
			$clip_folder = $row["clip_folder"];
			$clip_text_start = $row["clip_text_start"];
			$clip_text_end = $row["clip_text_end"];
			$clip_desc = $row["clip_desc"];
			$clip_order = $row["clip_order"];
			break; //limit to 1 row
		}
		echo "</table>";
		echo "<div>";}

	require_once "header.php";
	echo "<div align='left'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";

	echo "<form method='post' action=''>";
	echo "<table border='0' width='100%'>";
	echo "	<tr>";
	echo "		<td>Name:</td>";
	echo "		<td><input type='text' class='txt' name='clip_name' value='$clip_name'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td>Folder:</td>";
	echo "		<td><input type='text' class='txt'  name='clip_folder' value='$clip_folder'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='2'>Before Selection:<br>";
	echo "		  <textarea  class='txt' name='clip_text_start'>$clip_text_start</textarea>";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='2'>After Selection:<br>";
	echo "		  <textarea  class='txt' name='clip_text_end'>$clip_text_end</textarea>";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='2'>Notes:<br>";
	echo "		  <textarea  class='txt' name='clip_desc'>$clip_desc</textarea>";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='2' align='right'>";
	echo "     <input type='hidden' name='id' value='$id'>";
	echo "     <input type='submit' name='submit' value='Update'>";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

	require_once "footer.php";
?>
