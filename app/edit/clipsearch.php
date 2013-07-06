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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/check_auth.php";
if (permission_exists('script_editor_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (count($_POST)>0) {
	$clip_uuid = $_POST["id"];
	$clip_name = $_POST["clip_name"];
	$clip_folder = $_POST["clip_folder"];
	$clip_text_start = $_POST["clip_text_start"];
	$clip_text_end = $_POST["clip_text_end"];
	$clip_desc = $_POST["clip_desc"];
	$clip_order = $_POST["clip_order"];

	require_once "header.php";
	echo "<div align='left'>";
	echo "<table width='175' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

    $sql .= "select * from v_clips ";
	$sql .= "where ";
	if (strlen($clip_uuid) > 0) { $sql .= "and id = '$clip_uuid' "; }
	if (strlen($clip_name) > 0) { $sql .= "and clip_name like '%$clip_name%' "; }
	if (strlen($clip_folder) > 0) { $sql .= "and clip_folder like '%$clip_folder%' "; }
	if (strlen($clip_text_start) > 0) { $sql .= "and clip_text_start like '%$clip_text_start%' "; }
	if (strlen($clip_text_end) > 0) { $sql .= "and clip_text_end like '%$clip_text_end%' "; }
	if (strlen($clip_desc) > 0) { $sql .= "and clip_desc like '%$clip_desc%' "; }
	if (strlen($clip_order) > 0) { $sql .= "and clip_order like '%$clip_order%' "; }

	$sql = trim($sql);
	if (substr($sql, -5) == "where"){ $sql = substr($sql, 0, (strlen($sql)-5)); }
	$sql = str_replace ("where and", "where", $sql);
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);

	$c = 0;
	$row_style["0"] = "background-color: #F5F5DC;";
	$row_style["1"] = "background-color: #FFFFFF;";

	echo "<div align='left'>\n";
	echo "<table border='0' cellpadding='1' cellspacing='1'>\n";
	echo "<tr><td colspan='1'><img src='/edit/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>";

	if ($result_count == 0) {
		//no results
		echo "<tr><td>&nbsp;</td></tr>";
	}
	else { //received results
		echo "<tr>";
		  //echo "<th nowrap>&nbsp; &nbsp; Id&nbsp; &nbsp; </th>";
		  echo "<th nowrap>&nbsp; &nbsp; clip_name Search &nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; clip_folder&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; clip_text_start&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; clip_text_end&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; clip_desc&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; clip_order&nbsp; &nbsp; </th>";
		echo "</tr>";
		echo "<tr><td colspan='1'><img src='images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";

		foreach($result as $row) {
		//print_r( $row );
			echo "<tr style='".$row_style[$c]."'>\n";
				//echo "<td valign='top'><a href='update.php?id=".$row[id]."'>".$row[id]."</a></td>";
				echo "<td valign='top'><a href='clipupdate.php?id=".$row[id]."'>".$row[clip_name]."</a></td>";
				//echo "<td valign='top'>".$row[clip_folder]."</td>";
				//echo "<td valign='top'>".$row[clip_text_start]."</td>";
				//echo "<td valign='top'>".$row[clip_text_end]."</td>";
				//echo "<td valign='top'>".$row[clip_desc]."</td>";
				//echo "<td valign='top'>".$row[clip_order]."</td>";
			echo "</tr>";

			echo "<tr><td colspan='1'><img src='images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach        unset($sql, $result, $row_count);
		echo "</table>\n";
		echo "</div>\n";

		echo "  <br><br>";
		echo "  </td>\n";
		echo "</tr>\n";

	} //end if results

	echo "</table>\n";
	echo "</div>";
	echo "<br><br>";
	require_once "footer.php";

	unset ($result_count);
	unset ($result);
	unset ($key);
	unset ($val);
	unset ($c);

}
else {

	//show the content
	require_once "header.php";
	echo "<div align='left'>";
	echo "<table with='175' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

	echo "<form method='post' action=''>";
	echo "<table>";
	echo "	<tr>";
	echo "		<td>Name:</td>";
	echo "		<td><input type='text' class='txt' name='clip_name'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>Folder:</td>";
	echo "		<td><input type='text' class='txt' name='clip_folder'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>Start:</td>";
	echo "		<td><input type='text' class='txt' name='clip_text_start'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>End:</td>";
	echo "		<td><input type='text' class='txt' name='clip_text_end'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>Desc:</td>";
	echo "		<td><input type='text' class='txt' name='clip_desc'></td>";
	echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>clip_order:</td>";
	//echo "		<td><input type='text' class='txt' name='clip_order'></td>";
	//echo "	</tr>";
	echo "	<tr>";
	echo "		<td colspan='2' align='right'><input type='submit' name='submit' value='Search'></td>";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

	require_once "footer.php";

} //end if not post
?>