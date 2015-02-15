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
return; //disabled

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
	$rss_uuid = check_str($_POST["rss_uuid"]);
	//$rss_category = check_str($_POST["rss_category"]); //defined in local config.php
	$rss_sub_category = check_str($_POST["rss_sub_category"]);
	$rss_title = check_str($_POST["rss_title"]);
	$rss_link = check_str($_POST["rss_link"]);
	$rss_description = check_str($_POST["rss_description"]);
	$rss_img = check_str($_POST["rss_img"]);
	$rss_optional_1 = check_str($_POST["rss_optional_1"]);
	$rss_optional_2 = check_str($_POST["rss_optional_2"]);
	$rss_optional_3 = check_str($_POST["rss_optional_3"]);
	$rss_optional_4 = check_str($_POST["rss_optional_4"]);
	$rss_optional_5 = check_str($_POST["rss_optional_5"]);
	$rss_add_date = check_str($_POST["rss_add_date"]);
	$rss_add_user = check_str($_POST["rss_add_user"]);

	require_once "resources/header.php";



	echo "<div align='center'>";
	echo "<table border='0' cellpadding='0' cellspacing='2'>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";


	$sql = "";
	$sql .= "select * from v_rss ";
	$sql .= "where ";
	if (strlen($rss_uuid) > 0) { $sql .= "and rss_uuid like '%$rss_uuid%' "; }
	if (strlen($rss_category) > 0) { $sql .= "and rss_category like '%$rss_category%' "; }
	if (strlen($rss_sub_category) > 0) { $sql .= "and rss_sub_category like '%$rss_sub_category%' "; }
	if (strlen($rss_title) > 0) { $sql .= "and rss_title like '%$rss_title%' "; }
	if (strlen($rss_link) > 0) { $sql .= "and rss_link like '%$rss_link%' "; }
	if (strlen($rss_description) > 0) { $sql .= "and rss_description like '%$rss_description%' "; }
	if (strlen($rss_img) > 0) { $sql .= "and rss_img like '%$rss_img%' "; }
	if (strlen($rss_optional_1) > 0) { $sql .= "and rss_optional_1 like '%$rss_optional_1%' "; }
	if (strlen($rss_optional_2) > 0) { $sql .= "and rss_optional_2 like '%$rss_optional_2%' "; }
	if (strlen($rss_optional_3) > 0) { $sql .= "and rss_optional_3 like '%$rss_optional_3%' "; }
	if (strlen($rss_optional_4) > 0) { $sql .= "and rss_optional_4 like '%$rss_optional_4%' "; }
	if (strlen($rss_optional_5) > 0) { $sql .= "and rss_optional_5 like '%$rss_optional_5%' "; }
	if (strlen($rss_add_date) > 0) { $sql .= "and rss_add_date like '%$rss_add_date%' "; }
	if (strlen($rss_add_user) > 0) { $sql .= "and rss_add_user like '%$rss_add_user%' "; }
	$sql .= "and length(rss_del_date) = 0 ";
	$sql .= "or ";
	if (strlen($rss_uuid) > 0) { $sql .= "and rss_uuid like '%$rss_uuid%' "; }
	if (strlen($rss_category) > 0) { $sql .= "and rss_category like '%$rss_category%' "; }
	if (strlen($rss_sub_category) > 0) { $sql .= "and rss_sub_category like '%$rss_sub_category%' "; }
	if (strlen($rss_title) > 0) { $sql .= "and rss_title like '%$rss_title%' "; }
	if (strlen($rss_link) > 0) { $sql .= "and rss_link like '%$rss_link%' "; }
	if (strlen($rss_description) > 0) { $sql .= "and rss_description like '%$rss_description%' "; }
	if (strlen($rss_img) > 0) { $sql .= "and rss_img like '%$rss_img%' "; }
	if (strlen($rss_optional_1) > 0) { $sql .= "and rss_optional_1 like '%$rss_optional_1%' "; }
	if (strlen($rss_optional_2) > 0) { $sql .= "and rss_optional_2 like '%$rss_optional_2%' "; }
	if (strlen($rss_optional_3) > 0) { $sql .= "and rss_optional_3 like '%$rss_optional_3%' "; }
	if (strlen($rss_optional_4) > 0) { $sql .= "and rss_optional_4 like '%$rss_optional_4%' "; }
	if (strlen($rss_optional_5) > 0) { $sql .= "and rss_optional_5 like '%$rss_optional_5%' "; }
	if (strlen($rss_add_date) > 0) { $sql .= "and rss_add_date like '%$rss_add_date%' "; }
	if (strlen($rss_add_user) > 0) { $sql .= "and rss_add_user like '%$rss_add_user%' "; }
	$sql .= "and rss_del_date is null ";

	$sql = trim($sql);
	if (substr($sql, -5) == "where"){ $sql = substr($sql, 0, (strlen($sql)-5)); }
	if (substr($sql, -3) == " or"){ $sql = substr($sql, 0, (strlen($sql)-5)); }
	$sql = str_replace ("where and", "where", $sql);
	$sql = str_replace ("or and", "or", $sql);
	//echo $sql;
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);

	$c = 0;
	$row_style["0"] = "background-color: #F5F5DC;";
	$row_style["1"] = "background-color: #FFFFFF;";

	echo "<b>".$text['label-search']."</b><br>";
	echo "<div align='left'>\n";
	echo "<table border='0' cellpadding='1' cellspacing='1'>\n";
	echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>";

	if ($result_count == 0) { //no results
		echo "<tr><td>&nbsp;</td></tr>";
	}
	else { //received results

		echo "<tr>";
		  echo "<th nowrap>&nbsp; &nbsp; ".$text['label-id']."&nbsp; &nbsp; </th>";
		  echo "<th nowrap>&nbsp; &nbsp; ".$text['label-category']."&nbsp; &nbsp; </th>";
		  echo "<th nowrap>&nbsp; &nbsp; ".$text['label-sub-category']."&nbsp; &nbsp; </th>";
		  echo "<th nowrap>&nbsp; &nbsp; ".$text['label-title']."&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_link&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_description&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_img&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_optional_1&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_optional_2&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_optional_3&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_optional_4&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_optional_5&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_add_date&nbsp; &nbsp; </th>";
		  //echo "<th nowrap>&nbsp; &nbsp; rss_add_user&nbsp; &nbsp; </th>";
		echo "</tr>";
		echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";

		foreach($result as $row) {
		//print_r( $row );
			echo "<tr style='".$row_style[$c]."'>\n";
				echo "<td valign='top'><a href='rssupdate.php?rss_uuid=".$row[rss_uuid]."'>".$row[rss_uuid]."</a></td>";
				echo "<td valign='top'>".$row[rss_category]."</td>";
				echo "<td valign='top'>".$row[rss_sub_category]."</td>";
				echo "<td valign='top'>".$row[rss_title]."</td>";
				//echo "<td valign='top'>".$row[rss_link]."</td>";
				//echo "<td valign='top'>".$row[rss_description]."</td>";
				//echo "<td valign='top'>".$row[rss_img]."</td>";
				//echo "<td valign='top'>".$row[rss_optional_1]."</td>";
				//echo "<td valign='top'>".$row[rss_optional_2]."</td>";
				//echo "<td valign='top'>".$row[rss_optional_3]."</td>";
				//echo "<td valign='top'>".$row[rss_optional_4]."</td>";
				//echo "<td valign='top'>".$row[rss_optional_5]."</td>";
				//echo "<td valign='top'>".$row[rss_add_date]."</td>";
				//echo "<td valign='top'>".$row[rss_add_user]."</td>";
			echo "</tr>";

			echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";
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
	require_once "resources/footer.php";

	unset ($result_count);
	unset ($result);
	unset ($key);
	unset ($val);
	unset ($c);

	}
	else {

		echo "\n";    require_once "resources/header.php";
	echo "<div align='center'>";
	echo "<table border='0' cellpadding='0' cellspacing='2'>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";


	echo "<form method='post' action=''>";
	echo "<table>";
	echo "	<tr>";
	echo "		<td>Id</td>";
	echo "		<td><input type='text' class='txt' name='rss_uuid'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-category']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_category'></td>";
	echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_category</td>";
	//echo "		<td><input type='text' class='txt' name='rss_sub_category'></td>";
	//echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-title']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_title'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-link']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_link'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-description']."</td>";
	echo "		<td><input type='text' class='txt' name='rss_description'></td>";
	echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>Image</td>";
	//echo "		<td><input type='text' class='txt' name='rss_img'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_optional_1</td>";
	//echo "		<td><input type='text' class='txt' name='rss_optional_1'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_optional_2</td>";
	//echo "		<td><input type='text' class='txt' name='rss_optional_2'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_optional_3</td>";
	//echo "		<td><input type='text' class='txt' name='rss_optional_3'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_optional_4</td>";
	//echo "		<td><input type='text' class='txt' name='rss_optional_4'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_optional_5</td>";
	//echo "		<td><input type='text' class='txt' name='rss_optional_5'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_add_date</td>";
	//echo "		<td><input type='text' class='txt' name='rss_add_date'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_add_user</td>";
	//echo "		<td><input type='text' class='txt' name='rss_add_user'></td>";
	//echo "	</tr>";
	echo "	<tr>";
	echo "		<td colspan='2' align='right'><input type='submit' name='submit' class='btn' value='".$text['button-search']."'></td>";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";


	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";


require_once "resources/footer.php";

} //end if not post
?>
