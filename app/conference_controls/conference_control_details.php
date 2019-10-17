<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_control_detail_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = $_GET["search"];
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "control_digits like :search";
		$sql_search .= "or control_action like :search";
		$sql_search .= "or control_data like :search";
		$sql_search .= "or control_enabled like :search";
		$sql_search .= ")";
		$parameters['search'] = '%'.$search.'%';
	}
//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(*) ";
	$sql .= "from v_conference_control_details ";
	$sql .= "where conference_control_uuid = :conference_control_uuid ";
	//$sql .= "and domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$parameters['conference_control_uuid'] = $conference_control_uuid;
	//$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_conference_control_details ";
	$sql .= "where conference_control_uuid = :conference_control_uuid ";
	//$sql .= "and domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-conference_control_details']."</b></td>\n";
	//echo "		<form method='get' action=''>\n";
	//echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	//echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".escape($search)."'>\n";
	//echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	//echo "			</td>\n";
	//echo "		</form>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('control_digits', $text['label-control_digits'], $order_by, $order);
	echo th_order_by('control_action', $text['label-control_action'], $order_by, $order);
	echo th_order_by('control_data', $text['label-control_data'], $order_by, $order);
	echo th_order_by('control_enabled', $text['label-control_enabled'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('conference_control_detail_add')) {
		echo "<a href='conference_control_detail_edit.php?conference_control_uuid=".escape($_GET['id'])."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if (is_array($result) && sizeof($result) != 0) {
		foreach($result as $row) {
			if (permission_exists('conference_control_detail_edit')) {
				$tr_link = "href='conference_control_detail_edit.php?conference_control_uuid=".escape($row['conference_control_uuid'])."&id=".escape($row['conference_control_detail_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['control_digits'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['control_action'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['control_data'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['control_enabled'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('conference_control_detail_edit')) {
				echo "<a href='conference_control_detail_edit.php?conference_control_uuid=".escape($row['conference_control_uuid'])."&id=".escape($row['conference_control_detail_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('conference_control_detail_delete')) {
				echo "<a href='conference_control_detail_delete.php?conference_control_uuid=".escape($row['conference_control_uuid'])."&id=".escape($row['conference_control_detail_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($result);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('conference_control_detail_add')) {
		echo 		"<a href='conference_control_detail_edit.php?conference_control_uuid=".escape($_GET['id'])."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo 		"&nbsp;";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
