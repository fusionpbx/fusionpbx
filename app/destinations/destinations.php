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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('destination_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$search = check_str($_GET["search"]);
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//includes and title
	require_once "resources/header.php";
	$document['title'] = $text['title-destinations'];
	require_once "resources/paging.php";

//get total destination count from the database
	$sql = "select count(*) as num_rows from v_destinations ";
	if ($_GET['showall'] && permission_exists('destination_all')) {
		if (strlen($search) > 0) {
			$sql .= "where ";
		}
	} else {
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		if (strlen($search) > 0) {
			$sql .= "and ";
		}
	}
	if (strlen($search) > 0) {
		$sql .= "(";
		$sql .= "	destination_type like '%".$search."%' ";
		$sql .= " 	or destination_number like '%".$search."%' ";
		$sql .= " 	or destination_context like '%".$search."%' ";
		$sql .= " 	or destination_enabled like '%".$search."%' ";
		$sql .= " 	or destination_description like '%".$search."%' ";
		$sql .= ") ";
	}
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$num_rows = $row['num_rows'];
	}
	else {
		$num_rows = 0;
	}

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if ($_GET['showall'] && permission_exists('destination_all')) {
		$param .= "&showall=true";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_destinations ";
	if ($_GET['showall'] && permission_exists('destination_all')) {
		if (strlen($search) > 0) {
			$sql .= " where ";
		}
	} else {
		$sql .= "where domain_uuid = '$domain_uuid' ";
		if (strlen($search) > 0) {
			$sql .= " and ";
		}
	}
	if (strlen($search) > 0) {
		$sql .= " (";
		$sql .= "	destination_type like '%".$search."%' ";
		$sql .= " 	or destination_number like '%".$search."%' ";
		$sql .= " 	or destination_context like '%".$search."%' ";
		$sql .= " 	or destination_enabled like '%".$search."%' ";
		$sql .= " 	or destination_description like '%".$search."%' ";
		$sql .= ") ";
	}
	if (strlen($order_by) > 0) {
		if ($order_by == 'destination_type') {
			$sql .= "order by destination_type ".$order.", destination_number asc ";
		}
		else {
			$sql .= "order by ".$order_by." ".$order." ";
		}
	}
	else {
		$sql .= "order by destination_type asc, destination_number asc ";
	}
	$sql .= "limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$destinations = $prep_statement->fetchAll();
	unset ($prep_statement, $sql);

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap' valign='top'><b>".$text['header-destinations']." (".$num_rows.")</b></td>\n";
	echo "			<form method='get' action=''>\n";
	echo "			<td width='50%' align='right'>\n";
	if (permission_exists('destination_all')) {
		if ($_GET['showall'] == 'true') {
			echo "		<input type='hidden' name='showall' value='true'>";
		}
		else {
			echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='destinations.php?showall=true';\">\n";
		}
	}
	echo "				<input type='text' class='txt' style='width: 150px' name='search' value='".$search."'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "			</td>\n";
	echo "			</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2' valign='top'>\n";
	echo "			".$text['description-destinations']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($_GET['showall'] && permission_exists('destination_all')) {
		echo th_order_by('domain_name', $text['label-domain-name'], $order_by, $order, '', '', $param);
	}
	echo th_order_by('destination_type', $text['label-destination_type'], $order_by, $order, '', '', $param);
	echo th_order_by('destination_number', $text['label-destination_number'], $order_by, $order, '', '', $param);
	echo th_order_by('destination_context', $text['label-destination_context'], $order_by, $order, '', '', $param);
	echo th_order_by('destination_enabled', $text['label-destination_enabled'], $order_by, $order, '', '', $param);
	echo th_order_by('destination_description', $text['label-destination_description'], $order_by, $order, '', '', $param);
	echo "<td class='list_control_icons'>";
	if (permission_exists('destination_add')) {
		if ($_SESSION['limit']['destinations']['numeric'] == '' || ($_SESSION['limit']['destinations']['numeric'] != '' && $num_rows < $_SESSION['limit']['destinations']['numeric'])) {
			echo "<a href='destination_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($num_rows > 0) {
		foreach($destinations as $row) {
			$tr_link = "href='destination_edit.php?id=".$row['destination_uuid']."'";
			echo "<tr ".$tr_link.">\n";
			if ($_GET['showall'] && permission_exists('destination_all')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['destination_type'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'><a href='destination_edit.php?id=".$row['destination_uuid']."'>".format_phone($row['destination_number'])."</a></td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['destination_context']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.$row['destination_enabled']]."</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['destination_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('destination_edit')) {
				echo 	"<a href='destination_edit.php?id=".$row['destination_uuid']."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
			}
			if (permission_exists('destination_delete')) {
				echo 	"<a href='destination_delete.php?id=".$row['destination_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $destinations, $row_count);
	} //end if results

	echo "<tr>\n";
	if ($_GET['showall'] && permission_exists('destination_all')) {
		echo "<td colspan='7' align='right'>\n";
	}
	else {
		echo "<td colspan='6' align='right'>\n";
	}
	if (permission_exists('destination_add')) {
		if ($_SESSION['limit']['destinations']['numeric'] == '' || ($_SESSION['limit']['destinations']['numeric'] != '' && $num_rows < $_SESSION['limit']['destinations']['numeric'])) {
			echo "<a href='destination_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";

	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>