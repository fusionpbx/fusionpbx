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
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "config.php";
if (permission_exists('content_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (count($_POST)>0) {
	$rss_sub_uuid = check_str($_POST["rss_sub_uuid"]);
	$rss_uuid = check_str($_POST["rss_uuid"]);
	$rss_sub_title = check_str($_POST["rss_sub_title"]);
	$rss_sub_link = check_str($_POST["rss_sub_link"]);
	$rss_sub_description = check_str($_POST["rss_sub_description"]);
	$rss_sub_optional_1 = check_str($_POST["rss_sub_optional_1"]);
	$rss_sub_optional_2 = check_str($_POST["rss_sub_optional_2"]);
	$rss_sub_optional_3 = check_str($_POST["rss_sub_optional_3"]);
	$rss_sub_optional_4 = check_str($_POST["rss_sub_optional_4"]);
	$rss_sub_optional_5 = check_str($_POST["rss_sub_optional_5"]);
	$rss_sub_add_date = check_str($_POST["rss_sub_add_date"]);
	$rss_sub_add_user = check_str($_POST["rss_sub_add_user"]);


	require_once "resources/header.php";

	$sql = "";
	$sql .= "select * from v_rss_sub ";
	$sql .= "where ";
	if (strlen($domain_uuid) > 0) { $sql .= "and rss_sub_uuid = '$domain_uuid' "; }
	if (strlen($rss_sub_uuid) > 0) { $sql .= "and rss_sub_uuid like '%$rss_sub_uuid%' "; }
	if (strlen($rss_uuid) > 0) { $sql .= "and rss_uuid like '%$rss_uuid%' "; }
	if (strlen($rss_sub_title) > 0) { $sql .= "and rss_sub_title like '%$rss_sub_title%' "; }
	if (strlen($rss_sub_link) > 0) { $sql .= "and rss_sub_link like '%$rss_sub_link%' "; }
	if (strlen($rss_sub_description) > 0) { $sql .= "and rss_sub_description like '%$rss_sub_description%' "; }
	if (strlen($rss_sub_optional_1) > 0) { $sql .= "and rss_sub_optional_1 like '%$rss_sub_optional_1%' "; }
	if (strlen($rss_sub_optional_2) > 0) { $sql .= "and rss_sub_optional_2 like '%$rss_sub_optional_2%' "; }
	if (strlen($rss_sub_optional_3) > 0) { $sql .= "and rss_sub_optional_3 like '%$rss_sub_optional_3%' "; }
	if (strlen($rss_sub_optional_4) > 0) { $sql .= "and rss_sub_optional_4 like '%$rss_sub_optional_4%' "; }
	if (strlen($rss_sub_optional_5) > 0) { $sql .= "and rss_sub_optional_5 like '%$rss_sub_optional_5%' "; }
	if (strlen($rss_sub_add_date) > 0) { $sql .= "and rss_sub_add_date like '%$rss_sub_add_date%' "; }
	if (strlen($rss_sub_add_user) > 0) { $sql .= "and rss_sub_add_user like '%$rss_sub_add_user%' "; }
	$sql .= "and length(rss_sub_del_date) = 0 ";
	$sql .= "or ";
	if (strlen($domain_uuid) > 0) { $sql .= "and rss_sub_uuid = '$domain_uuid' "; }
	if (strlen($rss_sub_uuid) > 0) { $sql .= "and rss_sub_uuid like '%$rss_sub_uuid%' "; }
	if (strlen($rss_uuid) > 0) { $sql .= "and rss_uuid like '%$rss_uuid%' "; }
	if (strlen($rss_sub_title) > 0) { $sql .= "and rss_sub_title like '%$rss_sub_title%' "; }
	if (strlen($rss_sub_link) > 0) { $sql .= "and rss_sub_link like '%$rss_sub_link%' "; }
	if (strlen($rss_sub_description) > 0) { $sql .= "and rss_sub_description like '%$rss_sub_description%' "; }
	if (strlen($rss_sub_optional_1) > 0) { $sql .= "and rss_sub_optional_1 like '%$rss_sub_optional_1%' "; }
	if (strlen($rss_sub_optional_2) > 0) { $sql .= "and rss_sub_optional_2 like '%$rss_sub_optional_2%' "; }
	if (strlen($rss_sub_optional_3) > 0) { $sql .= "and rss_sub_optional_3 like '%$rss_sub_optional_3%' "; }
	if (strlen($rss_sub_optional_4) > 0) { $sql .= "and rss_sub_optional_4 like '%$rss_sub_optional_4%' "; }
	if (strlen($rss_sub_optional_5) > 0) { $sql .= "and rss_sub_optional_5 like '%$rss_sub_optional_5%' "; }
	if (strlen($rss_sub_add_date) > 0) { $sql .= "and rss_sub_add_date like '%$rss_sub_add_date%' "; }
	if (strlen($rss_sub_add_user) > 0) { $sql .= "and rss_sub_add_user like '%$rss_sub_add_user%' "; }
	$sql .= "and rss_sub_del_date is null ";

	$sql = trim($sql);
	if (substr($sql, -5) == "where"){ $sql = substr($sql, 0, (strlen($sql)-5)); }
	if (substr($sql, -3) == " or"){ $sql = substr($sql, 0, (strlen($sql)-5)); }
	$sql = str_replace ("where and", "where", $sql);
	$sql = str_replace ("or and", "or", $sql);

	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);

	$c = 0;
	$row_style["0"] = "background-color: #F5F5DC;";
	$row_style["1"] = "background-color: #FFFFFF;";

	echo "<b>".$text['label-search']."</b><br>";

	echo "<table border='0' cellpadding='1' cellspacing='1'>\n";
	echo "	<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>";

	if ($result_count == 0) { //no results
		echo "<tr><td>&nbsp;</td></tr>";
	}
	else { //received results

		echo "<tr>";
			echo "<th nowrap>&nbsp; &nbsp; ".$text['label-sub-id']."&nbsp; &nbsp; </th>";
			echo "<th nowrap>&nbsp; &nbsp; ".$text['label-id']."&nbsp; &nbsp; </th>";
			echo "<th nowrap>&nbsp; &nbsp; ".$text['label-title']."&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; Link&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; rss_sub_description&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; rss_sub_optional_1&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; rss_sub_optional_2&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; rss_sub_optional_3&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; rss_sub_optional_4&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; rss_sub_optional_5&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; rss_sub_add_date&nbsp; &nbsp; </th>";
			//echo "<th nowrap>&nbsp; &nbsp; rss_sub_add_user&nbsp; &nbsp; </th>";
		echo "</tr>";
		echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";

		foreach($result as $row) {
		//print_r( $row );
			echo "<tr style='".$row_style[$c]."'>\n";
				echo "<td valign='top'><a href='rsssubupdate.php?rss_sub_uuid=".$row[rss_sub_uuid]."'>".$row[rss_sub_uuid]."</a></td>";
				echo "<td valign='top'>".$row[rss_uuid]."</td>";
				echo "<td valign='top'>".$row[rss_sub_title]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_link]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_description]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_optional_1]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_optional_2]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_optional_3]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_optional_4]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_optional_5]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_add_date]."</td>";
				//echo "<td valign='top'>".$row[rss_sub_add_user]."</td>";
			echo "</tr>";
			echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);

	} //end if results

	echo "</table>\n";
	echo "<br><br>";

	require_once "resources/footer.php";

	unset ($result_count);
	unset ($result);
	unset ($key);
	unset ($val);
	unset ($c);

}
else {

	require_once "resources/header.php";

	echo "<form method='post' action=''>";
	echo "<table>";
	echo "	<tr>";
	echo "		<td>".$text['label-sub-id']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_sub_uuid'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-id']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_uuid'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-sub-title']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_sub_title'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-sub-link']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_sub_link'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-sub-desc']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_sub_description'></td>";
	echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_1</td>";
	//echo "		<td><input type='text' class='txt' name='rss_sub_optional_1'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_2</td>";
	//echo "		<td><input type='text' class='txt' name='rss_sub_optional_2'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_3</td>";
	//echo "		<td><input type='text' class='txt' name='rss_sub_optional_3'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_4</td>";
	//echo "		<td><input type='text' class='txt' name='rss_sub_optional_4'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_5</td>";
	//echo "		<td><input type='text' class='txt' name='rss_sub_optional_5'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_add_date</td>";
	//echo "		<td><input type='text' class='txt' name='rss_sub_add_date'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_add_user</td>";
	//echo "		<td><input type='text' class='txt' name='rss_sub_add_user'></td>";
	//echo "	</tr>";
	echo "	<tr>";
	echo "		<td colspan='2' align='right'><input type='submit' name='submit' class='btn' value='".$text['button-search']."'></td>";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	require_once "resources/footer.php";

} //end if not post
?>
