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

require_once "resources/header.php";
echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"\" href=\"rss.php\" />\n";

$order_by = $_GET["order_by"];
$order = $_GET["order"];


	echo "<table width='100%'>";
	echo "<tr>";
	echo "<td align='left'>";
	echo "      <b>$module_title ".$text['label-list']."</b>";
	echo "</td>";
	echo "<td align='right'>";
	//echo "      <input type='button' class='btn' name='' onclick=\"window.location='rssadd.php'\" value='Add $module_title'>&nbsp; &nbsp;\n";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	$sql = "";
	$sql .= "select * from v_rss ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and rss_category = '$rss_category' ";
	$sql .= "and length(rss_del_date) = 0 ";
	$sql .= "or domain_uuid = '$domain_uuid' ";
	$sql .= "and rss_category = '$rss_category' ";
	$sql .= "and rss_del_date is null ";
	if (strlen($order_by)> 0) {
		$sql .= "order by $order_by $order ";
	}
	else {
		$sql .= "order by rss_order asc ";
	}
	//echo $sql;
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='2' cellspacing='0'>\n";
	echo "<tr>";
	echo th_order_by('rss_title', $text['label-title'], $order_by, $order);
	echo th_order_by('rss_link', $text['label-link'], $order_by, $order);
	//echo th_order_by('rss_sub_category', 'Template', $order_by, $order);
	echo th_order_by('rss_group', $text['label-group'], $order_by, $order);
	echo th_order_by('rss_order', $text['label-order'], $order_by, $order, '', "style='text-align: center;'");
	if ($result_count == 0) { //no results
		echo "<td class='list_control_icons'>\n";
	}
	else {
		echo "<td class='list_control_icons'>\n";
	}
	echo "	<a href='rssadd.php' alt='add'>$v_link_label_add</a>\n";
	echo "</td>\n";
	echo "</tr>";

	if ($result_count > 0) {
		foreach($result as $row) {
		//print_r( $row );
			$tr_link = "href='rssupdate.php?rss_uuid=".$row[rss_uuid]."'";
			echo "<tr ".$tr_link.">\n";
				//echo "<td valign='top'><a href='rssupdate.php?rss_uuid=".$row[rss_uuid]."'>".$row[rss_uuid]."</a></td>";
				//echo "<td valign='top'>".$row[rss_category]."</td>";

				echo "<td valign='top' nowrap class='".$row_style[$c]."'><a href='rssupdate.php?rss_uuid=".$row[rss_uuid]."'>".$row[rss_title]."</a></td>";
				echo "<td valign='top' nowrap class='".$row_style[$c]."'><a href='/index.php?c=".$row[rss_link]."'>".$row[rss_link]."</a></td>";
				//echo "<td valign='top' class='".$row_style[$c]."'>".$row[rss_sub_category]."&nbsp;</td>";
				if (strlen($row[rss_group]) > 0) {
					echo "<td valign='top' class='".$row_style[$c]."'>".$row[rss_group]."</td>";
				}
				else {
					echo "<td valign='top' class='".$row_style[$c]."'>public</td>";
				}

				//echo "<td valign='top'>".$row[rss_description]."</td>";
				//echo "<td valign='top'>".$row[rss_img]."</td>";
				//echo "<td valign='top'>&nbsp;".$row[rss_optional_1]."&nbsp;</td>"; //priority

				//echo "<td valign='top' class='".$row_style[$c]."'>&nbsp;";
				//sif ($row[rss_optional_2]=="100") {
				//    echo "Complete";
				//}
				//else {
				//    echo $row[rss_optional_2]."%";
				//}
				//echo "&nbsp;</td>"; //completion status

				//echo "<td valign='top'>".$row[rss_optional_3]."</td>";
				//echo "<td valign='top'>".$row[rss_optional_4]."</td>";
				//echo "<td valign='top'>".$row[rss_optional_5]."</td>";
				echo "<td valign='top' class='".$row_style[$c]."' style='text-align: center;'>".$row[rss_order]."</td>";

				//echo "<td valign='top' align='center'>";
				//echo "  <input type='button' class='btn' name='' onclick=\"window.location='rssmoveup.php?menuparentid=".$row[menuparentid]."&rss_uuid=".$row[rss_uuid]."&rss_order=".$row[rss_order]."'\" value='<' title='".$row[rss_order].". Move Up'>";
				//echo "  <input type='button' class='btn' name='' onclick=\"window.location='rssmovedown.php?menuparentid=".$row[menuparentid]."&rss_uuid=".$row[rss_uuid]."&rss_order=".$row[rss_order]."'\" value='>' title='".$row[rss_order].". Move Down'>";
				//echo "</td>";

				echo "	<td class='list_control_icons'>";
				echo "<a href='rssupdate.php?rss_uuid=".$row[rss_uuid]."' alt='".$text['label-edit']."'>$v_link_label_edit</a>";
				echo "<a href='rssdelete.php?rss_uuid=".$row[rss_uuid]."' alt='delete' onclick=\"return confirm('".$text['message-confirm-delete']."')\">$v_link_label_delete</a>";
				echo "</td>\n";

				//echo "<td valign='top' align='right' class='".$row_style[$c]."'>";
				//echo "  <input type='button' class='btn' name='' onclick=\"if (confirm('Are you sure you wish to continue?')) { window.location='rssdelete.php?rss_uuid=".$row[rss_uuid]."' }\" value='Delete'>";
				//echo "</td>";

			echo "</tr>";

			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);

	} //end if results

	echo "<tr>\n";
	echo "<td colspan='6' align='left'>\n";

	echo "	<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>\n";
	echo "			<a href='rssadd.php' alt='add'>$v_link_label_add</a>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br><br>";

	//echo "<input type='button' class='btn' name='' onclick=\"window.location='rsssearch.php'\" value='Search'>&nbsp; &nbsp;\n";
	//echo "<input type='button' class='btn' name='' onclick=\"window.location='rssadd.php'\" value='Add $module_title'>&nbsp; &nbsp;\n";

	require_once "resources/footer.php";

	unset ($result_count);
	unset ($result);
	unset ($key);
	unset ($val);
	unset ($c);

?>
