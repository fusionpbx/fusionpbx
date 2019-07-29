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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('database_transaction_view')) {
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
	$order_by = $_GET["order_by"] != '' ? $_GET['order_by'] : 'transaction_date';
	$order = $_GET["order"] != '' ? $_GET['order'] : 'desc';

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "	lower(transaction_code) like :search ";
		$sql_search .= "	or lower(transaction_address) like :search ";
		$sql_search .= "	or lower(transaction_type) like :search ";
		$sql_search .= "	or lower(app_name) like :search ";
		$sql_search .= ") ";
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(database_transaction_uuid) from v_database_transactions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	if (strlen($search) > 0) {
		$parameters['search'] = '%'.$search.'%';
	}
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
	$sql = "select ";
	$sql .= "t.database_transaction_uuid, d.domain_name, u.username, t.user_uuid, t.app_name, t.app_uuid, ";
	$sql .= "t.transaction_code, t.transaction_address, t.transaction_type, t.transaction_date ";
	$sql .= "from v_database_transactions as t ";
	$sql .= "left outer join v_domains as d using (domain_uuid) ";
	$sql .= "left outer join v_users as u using (user_uuid) ";
	$sql .= "where t.domain_uuid = :domain_uuid ";
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
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-database_transactions']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	echo th_order_by('username', $text['label-user_uuid'], $order_by, $order);
	echo th_order_by('app_name', $text['label-app_name'], $order_by, $order);
	echo th_order_by('transaction_code', $text['label-transaction_code'], $order_by, $order);
	echo th_order_by('transaction_address', $text['label-transaction_address'], $order_by, $order);
	echo th_order_by('transaction_type', $text['label-transaction_type'], $order_by, $order);
	echo th_order_by('transaction_date', $text['label-transaction_date'], $order_by, $order);
	//echo th_order_by('transaction_old', $text['label-transaction_old'], $order_by, $order);
	//echo th_order_by('transaction_new', $text['label-transaction_new'], $order_by, $order);
	//echo th_order_by('transaction_result', $text['label-transaction_result'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	echo "	&nbsp;\n";
	echo "</td>\n";
	echo "<tr>\n";

	if (is_array($result)) {
		foreach($result as $row) {
			if (permission_exists('database_transaction_edit')) {
				$tr_link = "href='database_transaction_edit.php?id=".escape($row['database_transaction_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['domain_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['username'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['app_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['transaction_code'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['transaction_address'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['transaction_type'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['transaction_date'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['transaction_old']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['transaction_new']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['transaction_result']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('database_transaction_edit')) {
				echo "<a href='database_transaction_edit.php?id=".escape($row['database_transaction_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			//if (permission_exists('database_transaction_delete')) {
			//	echo "<a href='database_transaction_delete.php?id=".escape($row['database_transaction_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			//}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	echo 			"&nbsp;";
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
