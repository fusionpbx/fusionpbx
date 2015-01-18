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

$rss_uuid = $_GET["rss_uuid"];
$order_by = $_GET["order_by"];
$order = $_GET["order"];

require_once "resources/header.php";


	echo "<div align='center'>";
	echo "<table width='500' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";

	echo "      <br>";
	echo "      <b>$module_title ".$text['label-details']."</b>";
	$sql = "";
	$sql .= "select * from v_rss ";
	$sql .= "where domain_uuid = '$domain_uuid'  ";
	$sql .= "and rss_uuid = '$rss_uuid'  ";
	$sql .= "and rss_category = '$rss_category' ";
	$sql .= "and length(rss_del_date) = 0 ";	
	$sql .= "or domain_uuid = '$domain_uuid'  ";
	$sql .= "and rss_uuid = '$rss_uuid'  ";
	$sql .= "and rss_category = '$rss_category' ";
	$sql .= "and rss_del_date is null  ";
	$sql .= "order by rss_uuid asc ";

	//echo $sql;
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	echo "<table border='0' width='100%'>";
	if ($result_count == 0) { //no results
		echo "<tr><td>&nbsp;</td></tr>";
	}
	else { //received results
		foreach($result as $row) {
		  //print_r( $row );
			  //echo "<tr style='".$row_style[$c]."'>\n";
			  //echo "<tr>";
			  //echo "    <td valign='top'>Title</td>";
			  //echo "    <td valign='top'><a href='rssupdate.php?rss_uuid=".$row[rss_uuid]."'>".$row[rss_uuid]."</a></td>";
			  //echo "</tr>";
			  //echo "<td valign='top'>".$row[rss_category]."</td>";
			  
			  echo "<tr>";
			  echo "    <td valign='top'>".$text['label-title'].": &nbsp;</td>";
			  echo "    <td valign='top'><b>".$row[rss_title]."</b></td>";
			  echo "    <td valign='top' align='right'>";
			  echo "        <input type='button' class='btn' name='' onclick=\"window.location='rssupdate.php?rss_uuid=".$row[rss_uuid]."'\" value='".$text['button-update']."'>";
			  echo "    </td>";
			  $rss_description = $row[rss_description];
			  //$rss_description = str_replace ("\r\n", "<br>", $rss_description);
			  //$rss_description = str_replace ("\n", "<br>", $rss_description);
			  echo "</tr>";              
			  
			  
			  echo "<tr>";
			  echo "    <td valign='top'>".$text['label-template'].": &nbsp;</td>";
			  echo "     <td valign='top'>".$row[rss_sub_category]."</td>";
			  echo "</tr>";

			  echo "<tr>";
			  echo "    <td valign='top'>".$text['label-group'].": &nbsp;</td>";
			  echo "     <td valign='top'>".$row[rss_group]."</td>";
			  echo "</tr>";
			  
			  if (strlen($row[rss_order]) > 0) {
				  echo "<tr>";
				  echo "    <td valign='top'>".$text['label-order'].": &nbsp;</td>";
				  echo "     <td valign='top'>".$row[rss_order]."</td>";
				  echo "</tr>";
			  }

			  //echo "<td valign='top'>".$row[rss_link]."</td>";
			  echo "    <td valign='top'>".$text['label-description'].": &nbsp;</td>";
			  echo "    <td valign='top' colspan='2'>".$rss_description."</td>";
			  //echo "<td valign='top'>".$row[rss_img]."</td>";

			  //echo "<tr>";
			  //echo "    <td valign='top'>Priority: &nbsp;</td>";
			  //echo "    <td valign='top' colspan='2'>".$row[rss_optional_1]."</td>"; //priority
			  //echo "</tr>";

			  //echo "<tr>";
			  //echo "    <td valign='top'>Status: &nbsp;</td>"; //completion status
			  //echo "    <td valign='top' colspan='2'>";
			  //echo      $row[rss_optional_2];
			  //if ($row[rss_optional_2]=="100") {
			  //    echo "Complete";
			  //}
			  //else {
			  //    echo $row[rss_optional_2]."%";
			  //}
			  //echo      "</td>"; //completion status
			  //echo "<td valign='top'>".$row[rss_optional_3]."</td>";
			  //echo "<td valign='top'>".$row[rss_optional_4]."</td>";
			  //echo "<td valign='top'>".$row[rss_optional_5]."</td>";
			  //echo "<td valign='top'>".$row[rss_add_date]."</td>";
			  //echo "<td valign='top'>".$row[rss_add_user]."</td>";
			  //echo "<tr>";
			  //echo "    <td valign='top'>";
			  //echo "      <a href='rsssublist.php?rss_uuid=".$row[rss_uuid]."'>Details</a>";
			  //echo "        <input type='button' class='btn' name='' onclick=\"window.location='rsssublist.php?rss_uuid=".$row[rss_uuid]."'\" value='Details'>";
			  //echo "    </td>";
			  //echo "</tr>";

			  echo "</tr>";

			  //echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";
			  if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
	}
	echo "</table>";
	unset($sql, $prep_statement, $result);


	if ($rss_sub_show == 1) {

		echo "<br><br><br>";
		echo "<b>$rss_sub_title</b><br>";

		$sql = "";
		$sql .= "select * from v_rss_sub ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and rss_uuid = '$rss_uuid' ";
		$sql .= "and length(rss_sub_del_date) = 0 ";
		$sql .= "or domain_uuid = '$domain_uuid' ";
		$sql .= "and rss_uuid = '$rss_uuid' ";
		$sql .= "and rss_sub_del_date is null ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		//echo $sql;

		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);

		$c = 0;
		$row_style["0"] = "background-color: #F5F5DC;";
		$row_style["1"] = "background-color: #FFFFFF;";

		echo "<div align='left'>\n";
		echo "<table width='100%' border='0' cellpadding='1' cellspacing='1'>\n";
		//echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>";

		if ($result_count == 0) { //no results
			echo "<tr><td>&nbsp;</td></tr>";
		}
		else { //received results

			echo "<tr>";
			/*
			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_uuid&order=desc' title='ascending'>rss_sub_uuid</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_uuid&order=desc' title='ascending'>rss_sub_uuid</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_uuid&order=asc' title='descending'>rss_sub_uuid</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_uuid&order=desc' title='ascending'>rss_uuid</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_uuid&order=desc' title='ascending'>rss_uuid</a>";
				}
				else {
					echo "<a href='?order_by=rss_uuid&order=asc' title='descending'>rss_uuid</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_title&order=desc' title='ascending'>rss_sub_title</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_title&order=desc' title='ascending'>rss_sub_title</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_title&order=asc' title='descending'>rss_sub_title</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_link&order=desc' title='ascending'>rss_sub_link</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_link&order=desc' title='ascending'>rss_sub_link</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_link&order=asc' title='descending'>rss_sub_link</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_description&order=desc' title='ascending'>rss_sub_description</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_description&order=desc' title='ascending'>rss_sub_description</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_description&order=asc' title='descending'>rss_sub_description</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_optional_1&order=desc' title='ascending'>rss_sub_optional_1</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_optional_1&order=desc' title='ascending'>rss_sub_optional_1</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_optional_1&order=asc' title='descending'>rss_sub_optional_1</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_optional_2&order=desc' title='ascending'>rss_sub_optional_2</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_optional_2&order=desc' title='ascending'>rss_sub_optional_2</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_optional_2&order=asc' title='descending'>rss_sub_optional_2</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_optional_3&order=desc' title='ascending'>rss_sub_optional_3</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_optional_3&order=desc' title='ascending'>rss_sub_optional_3</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_optional_3&order=asc' title='descending'>rss_sub_optional_3</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_optional_4&order=desc' title='ascending'>rss_sub_optional_4</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_optional_4&order=desc' title='ascending'>rss_sub_optional_4</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_optional_4&order=asc' title='descending'>rss_sub_optional_4</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_optional_5&order=desc' title='ascending'>rss_sub_optional_5</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_optional_5&order=desc' title='ascending'>rss_sub_optional_5</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_optional_5&order=asc' title='descending'>rss_sub_optional_5</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_add_date&order=desc' title='ascending'>rss_sub_add_date</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_add_date&order=desc' title='ascending'>rss_sub_add_date</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_add_date&order=asc' title='descending'>rss_sub_add_date</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";

			  echo "<th nowrap>&nbsp; &nbsp; ";
			  if (strlen($order_by)==0) {
				echo "<a href='?order_by=rss_sub_add_user&order=desc' title='ascending'>rss_sub_add_user</a>";
			  }
			  else {
				if ($order=="asc") {
					echo "<a href='?order_by=rss_sub_add_user&order=desc' title='ascending'>rss_sub_add_user</a>";
				}
				else {
					echo "<a href='?order_by=rss_sub_add_user&order=asc' title='descending'>rss_sub_add_user</a>";
				}
			  }
			  echo "&nbsp; &nbsp; </th>";
			  */

			echo "</tr>";
			echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";

			foreach($result as $row) {
			//print_r( $row );
				echo "<tr style='".$row_style[$c]."'>\n";
					//echo "<td valign='top'>".$rss_uuid."</td>";
					//echo "<td valign='top'>&nbsp;<b>".$row[rss_sub_title]."</b>&nbsp;</td>";
					//echo "<td valign='top'>&nbsp;".$row[rss_sub_link]."&nbsp;</td>";
					echo "<td valign='top' width='200'>";
					echo "  <b>".$row[rss_sub_title]."</b>";
					echo "</td>";

					echo "<td valign='top'>".$row[rss_sub_add_date]."</td>";

					//echo "<td valign='top'>".$row[rss_sub_optional_1]."</td>";
					//echo "<td valign='top'>".$row[rss_sub_optional_2]."</td>";
					//echo "<td valign='top'>".$row[rss_sub_optional_3]."</td>";
					//echo "<td valign='top'>".$row[rss_sub_optional_4]."</td>";
					//echo "<td valign='top'>".$row[rss_sub_optional_5]."</td>";
					//echo "<td valign='top'>".$row[rss_sub_add_user]."</td>";

					echo "<td valign='top'>";
					echo "  <input type='button' class='btn' name='' onclick=\"if (confirm('".$text['message-confirm-delete']."')) { window.location='rsssubdelete.php?rss_uuid=".$row[rss_uuid]."&rss_sub_uuid=".$row[rss_sub_uuid]."' }\" value='".$text['button-delete']."'>";
					echo "</td>";

					echo "<td valign='top' align='right'>";
					echo "  &nbsp;";
					echo "  <input type='button' class='btn' name='' onclick=\"window.location='rsssubupdate.php?rss_uuid=".$rss_uuid."&rss_sub_uuid=".$row[rss_sub_uuid]."'\" value='".$text['button-update']."'>";
					echo "  &nbsp; \n";
					//echo "  <a href='rsssubupdate.php?rss_uuid=".$rss_uuid."&rss_sub_uuid=".$row[rss_sub_uuid]."'>Update</a>&nbsp;";
					echo "</td>";


					$rss_sub_description = $row[rss_sub_description];
					$rss_sub_description = str_replace ("\r\n", "<br>", $rss_sub_description);
					$rss_sub_description = str_replace ("\n", "<br>", $rss_sub_description);

					echo "</tr>";
					echo "<tr style='".$row_style[$c]."'>\n";
					echo "<td valign='top' width='300' colspan='4'>";
					echo "".$rss_sub_description."&nbsp;";
					echo "</td>";

					echo "</tr>";



				echo "</tr>";

				echo "<tr><td colspan='100%'><img src='/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			} //end foreach        unset($sql, $result, $row_count);



		} //end if results

		echo "</table>\n";
		echo "</div>\n";


	} //if ($showrsssub == 1) {

	echo "  <br><br>";
	echo "  </td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//echo "<input type='button' class='btn' name='' onclick=\"window.location='rsssubsearch.php'\" value='Search'>&nbsp; &nbsp;\n";
	if ($rss_sub_show == 1) {
		echo "<input type='button' class='btn' name='' onclick=\"window.location='rsssubadd.php?rss_uuid=".$rss_uuid."'\" value='".$text['button-add-title']." $rss_sub_title'>&nbsp; &nbsp;\n";
	}
	echo "</div>";

	echo "<br><br>";
	require_once "resources/footer.php";

	unset ($result_count);
	unset ($result);
	unset ($key);
	unset ($val);
	unset ($c);

?>
