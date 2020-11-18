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
	Portions created by the Initial Developer are Copyright (C) 2016 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
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
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the user filter and search term
	$user_uuid = $_GET['user_uuid'];
	$search = strtolower($_GET["search"]);
	if ($search != '') {
		$sql_search = "and (";
		$sql_search .= "	lower(t.app_name) like :search ";
		$sql_search .= "	or lower(t.transaction_code) like :search ";
		$sql_search .= "	or lower(t.transaction_address) like :search ";
		$sql_search .= "	or lower(t.transaction_type) like :search ";
		$sql_search .= "	or cast(t.transaction_date as text) like :search ";
		$sql_search .= "	or lower(u.username) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(t.database_transaction_uuid) from v_database_transactions as t ";
	$sql .= "left outer join v_domains as d using (domain_uuid) ";
	$sql .= "left outer join v_users as u using (user_uuid) ";
	$sql .= "where t.domain_uuid = :domain_uuid ";
	if (is_uuid($user_uuid)) {
		$sql .= "and t.user_uuid = :user_uuid ";
		$parameters['user_uuid'] = $user_uuid;
	}
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(t.database_transaction_uuid)','t.database_transaction_uuid, d.domain_name, u.username, t.user_uuid, t.app_name, t.app_uuid, t.transaction_code, t.transaction_address, t.transaction_type, t.transaction_date', $sql);
	$sql .= order_by($order_by, $order, 't.transaction_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get users
	$sql = "select user_uuid, username from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by username ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$rows = $database->select($sql, $parameters, 'all');
	if (is_array($rows) && @sizeof($rows) != 0) {
		foreach ($rows as $row) {
			$users[$row['user_uuid']] = $row['username'];
		}
	}
	unset($sql, $parameters, $rows, $row);

//additional includes
	$document['title'] = $text['title-database_transactions'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-database_transactions']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (is_array($users) && @sizeof($users) != 0) {
		echo 	"<select class='formfld' name='user_uuid' onchange=\"document.getElementById('form_search').submit();\">\n";
		echo "		<option value=''>".$text['label-user']."...</option>\n";
		echo "		<option value=''>".$text['label-all']."</option>\n";
		foreach ($users as $uuid => $username) {
			$selected = $user_uuid == $uuid ? "selected='selected'" : null;
			echo "	<option value='".escape($uuid)."' ".$selected.">".escape($username)."</option>\n";
		}
		echo "	</select>";
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','onclick'=>"document.getElementById('search').value = ''; document.getElementById('form_search').submit();",'style'=>(!$search ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-database_transactions']."\n";
	echo "<br /><br />\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
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
	if (permission_exists('database_transaction_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($result)) {
		$x = 0;
		foreach($result as $row) {
			if (permission_exists('database_transaction_edit')) {
				$list_row_url = "database_transaction_edit.php?id=".urlencode($row['database_transaction_uuid']).($page != '' ? "&page=".urlencode($page) : null).($search != '' ? "&search=".urlencode($search) : null);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			echo "	<td>".escape($row['domain_name'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['username'])."&nbsp;</td>\n";
			echo "	<td><a href='".$list_row_url."'>".escape($row['app_name'])."</a>&nbsp;</td>\n";
			echo "	<td>".escape($row['transaction_code'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['transaction_address'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['transaction_type'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['transaction_date'])."&nbsp;</td>\n";
			//echo "	<td>".escape($row['transaction_old']."&nbsp;</td>\n";
			//echo "	<td>".escape($row['transaction_new']."&nbsp;</td>\n";
			//echo "	<td>".escape($row['transaction_result']."&nbsp;</td>\n";
			if (permission_exists('database_transaction_edit')) {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($result);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

//include the footer
	require_once "resources/footer.php";

?>
