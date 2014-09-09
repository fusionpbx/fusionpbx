<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_log_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-fax_logs']."</b></td>\n";
	echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-fax_log']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_fax_logs ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
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
		$rows_per_page = 100;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $page; 

	//get the list
		$sql = "select * from v_fax_logs ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('fax_success', $text['label-fax_success'], $order_by, $order);
	echo th_order_by('fax_result_code', $text['label-fax_result_code'], $order_by, $order);
	echo th_order_by('fax_result_text', $text['label-fax_result_text'], $order_by, $order);
	echo th_order_by('fax_file', $text['label-fax_file'], $order_by, $order);
	echo th_order_by('fax_ecm_used', $text['label-fax_ecm_used'], $order_by, $order);
	echo th_order_by('fax_local_station_id', $text['label-fax_local_station_id'], $order_by, $order);
	//echo th_order_by('fax_document_transferred_pages', $text['label-fax_document_transferred_pages'], $order_by, $order);
	//echo th_order_by('fax_document_total_pages', $text['label-fax_document_total_pages'], $order_by, $order);
	//echo th_order_by('fax_image_resolution', $text['label-fax_image_resolution'], $order_by, $order);
	//echo th_order_by('fax_image_size', $text['label-fax_image_size'], $order_by, $order);
	echo th_order_by('fax_bad_rows', $text['label-fax_bad_rows'], $order_by, $order);
	echo th_order_by('fax_transfer_rate', $text['label-fax_transfer_rate'], $order_by, $order);
	echo th_order_by('fax_retry_attempts', $text['label-fax_retry_attempts'], $order_by, $order);
	//echo th_order_by('fax_retry_limit', $text['label-fax_retry_limit'], $order_by, $order);
	//echo th_order_by('fax_retry_sleep', $text['label-fax_retry_sleep'], $order_by, $order);
	echo th_order_by('fax_uri', $text['label-fax_destination'], $order_by, $order);
	echo th_order_by('fax_date', $text['label-fax_date'], $order_by, $order);
	//echo th_order_by('fax_epoch', $text['label-fax_epoch'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('fax_log_add')) {
		echo "<a href='fax_log_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			if (permission_exists('fax_log_edit')) {
				$tr_link = "href='fax_log_edit.php?id=".$row['fax_log_uuid']."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_success']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_result_code']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_result_text']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".basename($row['fax_file'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_ecm_used']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_local_station_id']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_document_transferred_pages']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_document_total_pages']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_image_resolution']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_image_size']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_bad_rows']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_transfer_rate']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_retry_attempts']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_retry_limit']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_retry_sleep']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".basename($row['fax_uri'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_date']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_epoch']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('fax_log_edit')) {
				echo "<a href='fax_log_edit.php?id=".$row['fax_log_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('fax_log_delete')) {
				echo "<a href='fax_log_delete.php?id=".$row['fax_log_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='21' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('fax_log_add')) {
		echo 		"<a href='fax_log_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
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
	echo "</div>";
	echo "<br /><br />";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>