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
	Portions created by the Initial Developer are Copyright (C) 2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('ring_group_forward')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	unset($text);
	require_once "app/ring_groups/app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

require_once "resources/header.php";
require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

	//echo "<table width='100%' border='0'>\n";
	//echo "	<tr>\n";
	//echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title']."</b></td>\n";
	//echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	//echo "	</tr>\n";
	//echo "	<tr>\n";
	//echo "		<td align='left' colspan='2'>\n";
	//echo "			".$text['description']."<br /><br />\n";
	//echo "		</td>\n";
	//echo "	</tr>\n";
	//echo "</table>\n";

	//prepare to page the results
		if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
			//show all ring groups
			$sql = "select count(*) as num_rows from v_ring_groups ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
		}
		else {
			//show only assigned fax extensions
			$sql = "select count(*) as num_rows from v_ring_groups as r, v_ring_group_users as u ";
			$sql .= "where r.ring_group_uuid = u.ring_group_uuid ";
			$sql .= "and r.domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
		}
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
		$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] > 0) {
				$num_rows = $row['num_rows'];
			}
			else {
				$num_rows = '0';
			}
		}

	//prepare to page the results
		$rows_per_page = 10;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $page; 

	//get the  list
		if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
			//show all ring groups
			$sql = "select * from v_ring_groups ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
		}
		else {
			//show only assigned fax extensions
			$sql = "select r.ring_group_uuid, r.ring_group_extension, r.ring_group_description from v_ring_groups as r, v_ring_group_users as u ";
			$sql .= "where r.ring_group_uuid = u.ring_group_uuid ";
			$sql .= "and r.domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
		}
		if (strlen($order_by) == 0) {
			$sql .= "order by ring_group_extension asc ";
		}
		else {
			$sql .= "order by $order_by $order ";
		}
		$sql .= " limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo th_order_by('ring_group_name', $text['label-name'], $order_by, $order);
	echo th_order_by('ring_group_extension', $text['label-ring-group-extension'], $order_by, $order);
	//echo th_order_by('ring_group_context', 'Context', $order_by, $order);
	//echo th_order_by('ring_group_strategy', 'Strategy', $order_by, $order);
	//echo th_order_by('ring_group_timeout_app', 'Timeout App', $order_by, $order);
	//echo th_order_by('ring_group_timeout_data', 'Timeout Data', $order_by, $order);
	//echo th_order_by('ring_group_enabled', $text['label-enabled'], $order_by, $order);
	echo "<th>".$text['label-tools']."</th>";
	echo th_order_by('ring_group_description', $text['label-description'], $order_by, $order);
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			echo "<tr >\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ring_group_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ring_group_extension']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ring_group_context']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ring_group_strategy']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ring_group_timeout_app']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ring_group_timeout_data']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ring_group_enabled']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'><a href='".PROJECT_PATH."/app/ring_groups/ring_group_forward_edit.php?id=".$row['ring_group_uuid']."' alt='".$text['link-call-forward']."'>".$text['link-call-forward']."</a></td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['ring_group_description']."&nbsp;</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";
	echo "<br /><br />";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>