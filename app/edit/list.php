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

//add multi-lingual support
	$language = new text;
	$text = $language->get();

require_once "admin/edit/header.php";
echo "<div align='left'>";
echo "<table width='175'  border='0' cellpadding='0' cellspacing='2'>\n";
echo "<tr class='border'>\n";
echo "	<td align=\"left\">\n";
echo "      <br>";

$sql = "select * from v_clips ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
$result_count = count($result);

$c = 0;
$row_style["0"] = "background-color: #F5F5DC;";
$row_style["1"] = "background-color: #FFFFFF;";

echo "<div align='left'>\n";
echo "<table width='100%' border='0' cellpadding='1' cellspacing='1'>\n";
echo "<tr><td colspan='1'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>";

if ($result_count == 0) { //no results
	echo "<tr><td>&nbsp;</td></tr>";
}
else { //received results
	echo "<tr>";
	echo "<th nowrap>&nbsp; &nbsp; clip name &nbsp;</th>";
	//echo "<th nowrap>&nbsp; &nbsp; clip_folder&nbsp; &nbsp; </th>";
	//echo "<th nowrap>&nbsp; &nbsp; clip_text_start&nbsp; &nbsp; </th>";
	//echo "<th nowrap>&nbsp; &nbsp; clip_text_end&nbsp; &nbsp; </th>";
	//echo "<th nowrap>&nbsp; &nbsp; clip_desc&nbsp; &nbsp; </th>";
	//echo "<th nowrap>&nbsp; &nbsp; clip_order&nbsp; &nbsp; </th>";
	echo "</tr>";
	echo "<tr><td colspan='1'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";

	foreach($result as $row) {
		echo "<tr style='".$row_style[$c]."'>\n";
			//echo "<td valign='top'><a href='update.php?id=".$row[id]."'>".$row['clip_uuid']."</a></td>";
			echo "<td valign='top'><a href='/edit/update.php?id=".$row['clip_uuid']."'>".$row['clip_name']."</a></td>";
			//echo "<td valign='top'>".$row[clip_folder]."</td>";
			//echo "<td valign='top'>".$row[clip_text_start]."</td>";
			//echo "<td valign='top'>".$row[clip_text_end]."</td>";
			//echo "<td valign='top'>".$row[clip_desc]."</td>";
			//echo "<td valign='top'>".$row[clip_order]."</td>";
		echo "</tr>";
		echo "<tr><td colspan='1'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";
		if ($c==0) { $c=1; } else { $c=0; }
	} //end foreach
	unset($sql, $result, $row_count);
	echo "</table>\n";
	echo "</div>\n";

	echo "  </td>\n";
	echo "</tr>\n";
} //end if results
echo "</table>\n";

echo "<table width='175'><tr><td align='right'>\n"; 
echo "<input type='button' class='btn' name='' onclick=\"window.location='clipsearch.php'\" value='".$text['button-search']."'>&nbsp; &nbsp;\n";
echo "<input type='button' class='btn' name='' onclick=\"window.location='clipadd.php'\" value='".$text['button-add']."'>&nbsp; &nbsp;\n";
echo "</td></tr><table>\n";
echo "</div>";

echo "<br><br>";
require_once "admin/edit/footer.php";

unset ($result_count);
unset ($result);
unset ($key);
unset ($val);
unset ($c);

?>