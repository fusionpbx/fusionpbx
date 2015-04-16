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
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the fax_uuid
	if (count($_GET) > 0) {
		$fax_uuid = check_str($_GET["id"]);
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' valign='top' nowrap='nowrap'><b>".$text['title-fax_logs']."</b></td>\n";
	echo "		<td width='50%' align='right'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='fax.php'\" value='".$text['button-back']."'>";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-refresh']."' onclick=\"document.location.reload();\" value='".$text['button-refresh']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top' colspan='2'>\n";
	echo "			<br>";
	echo "			".$text['description-fax_log']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_fax_logs ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and fax_uuid = '$fax_uuid' ";
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
		$rows_per_page = 50;
		$param = "&id=".$fax_uuid."&order_by=".$order_by."&order=".$order;
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the list
		$sql = "select * from v_fax_logs ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and fax_uuid = '$fax_uuid' ";
		$sql .= (strlen($order_by) > 0) ? "order by ".$order_by." ".$order." " : "order by fax_epoch desc ";
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('fax_success', $text['label-fax_success'], $order_by, $order, null, null, "&id=".$fax_uuid);
	echo th_order_by('fax_result_code', $text['label-fax_result_code'], $order_by, $order, null, null, "&id=".$fax_uuid);
	echo th_order_by('fax_result_text', $text['label-fax_result_text'], $order_by, $order, null, null, "&id=".$fax_uuid);
	echo th_order_by('fax_file', $text['label-fax_file'], $order_by, $order, null, null, "&id=".$fax_uuid);
	echo th_order_by('fax_ecm_used', $text['label-fax_ecm_used'], $order_by, $order, null, null, "&id=".$fax_uuid);
	echo th_order_by('fax_local_station_id', $text['label-fax_local_station_id'], $order_by, $order, null, null, "&id=".$fax_uuid);
	//echo th_order_by('fax_document_transferred_pages', $text['label-fax_document_transferred_pages'], $order_by, $order);
	//echo th_order_by('fax_document_total_pages', $text['label-fax_document_total_pages'], $order_by, $order);
	//echo th_order_by('fax_image_resolution', $text['label-fax_image_resolution'], $order_by, $order);
	//echo th_order_by('fax_image_size', $text['label-fax_image_size'], $order_by, $order);
	echo th_order_by('fax_bad_rows', $text['label-fax_bad_rows'], $order_by, $order, null, null, "&id=".$fax_uuid);
	echo th_order_by('fax_transfer_rate', $text['label-fax_transfer_rate'], $order_by, $order, null, null, "&id=".$fax_uuid);
	echo th_order_by('fax_retry_attempts', $text['label-fax_retry_attempts'], $order_by, $order, null, null, "&id=".$fax_uuid);
	//echo th_order_by('fax_retry_limit', $text['label-fax_retry_limit'], $order_by, $order);
	//echo th_order_by('fax_retry_sleep', $text['label-fax_retry_sleep'], $order_by, $order);
	echo th_order_by('fax_uri', $text['label-fax_destination'], $order_by, $order, null, null, "&id=".$fax_uuid);
	echo th_order_by('fax_epoch', $text['label-fax_date'], $order_by, $order, null, null, "&id=".$fax_uuid);
	//echo th_order_by('fax_epoch', $text['label-fax_epoch'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	echo "&nbsp;\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = "href='fax_log_view.php?id=".$row['fax_log_uuid']."&fax_uuid=".$fax_uuid."'";
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
			echo 		"<a href='fax_log_view.php?id=".$row['fax_log_uuid']."&fax_uuid=$fax_uuid' alt='".$text['button-view']."'>".$v_link_label_view."</a>";
			if (permission_exists('fax_log_delete')) {
				echo 	"<a href='fax_log_delete.php?id=".$row['fax_log_uuid']."&fax_uuid=".$fax_uuid."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
			}
			echo 	"</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "</table>";
	echo "<br /><br />";
	echo "<div style='text-align: center;'>".$paging_controls."</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>