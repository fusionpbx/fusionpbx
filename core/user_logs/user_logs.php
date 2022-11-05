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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2022
	the Initial Developer. All Rights Reserved.
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('user_log_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (is_array($_POST['user_logs'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$user_logs = $_POST['user_logs'];
	}

//process the http post data by action
	if ($action != '' && is_array($user_logs) && @sizeof($user_logs) != 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: user_logs.php');
			exit;
		}

		//prepare the array
		foreach($user_logs as $row) {
			$array['user_logs'][$x]['checked'] = $row['checked'];
			$array['user_logs'][$x]['user_log_uuid'] = $row['user_log_uuid'];
			$x++;
		}

		//prepare the database object
		$database = new database;
		$database->app_name = 'user_logs';
		$database->app_uuid = '582a13cf-7d75-4ea3-b2d9-60914352d76e';

		//send the array to the database class
		if ($action == 'delete') {
			if (permission_exists('user_log_delete')) {
				$database->delete($array);
			}
		}

		//redirect the user
		header('Location: user_logs.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
		$search = htmlspecialchars($search);
	}

//get the count
	$sql = "select count(user_log_uuid) ";
	$sql .= "from v_user_logs ";
	if (permission_exists('user_log_all') && $_GET['show'] == 'all') {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(username) like :search ";
		$sql .= "	or lower(type) like :search ";
		$sql .= "	or lower(result) like :search ";
		$sql .= "	or lower(remote_address) like :search ";
		$sql .= "	or lower(user_agent) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "search=".$search : null;
	$param .= ($_GET['show'] == 'all' && permission_exists('user_log_all')) ? "&show=all" : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}
	$parameters['time_zone'] = $time_zone;

//get the list
	$sql = "select ";
	$sql .= "domain_uuid, ";
	$sql .= "user_log_uuid, ";
	$sql .= "timestamp, ";
	$sql .= "to_char(timezone(:time_zone, timestamp), 'DD Mon YYYY') as date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, timestamp), 'HH12:MI:SS am') as time_formatted, \n";
	$sql .= "username, ";
	$sql .= "type, ";
	$sql .= "result, ";
	$sql .= "remote_address, ";
	$sql .= "user_agent ";
	$sql .= "from v_user_logs ";
	if (permission_exists('user_log_all') && $_GET['show'] == 'all') {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (isset($search)) {
		$sql .= "and ( ";
		$sql .= "	lower(username) like :search ";
		$sql .= "	or lower(type) like :search ";
		$sql .= "	or lower(result) like :search ";
		$sql .= "	or lower(remote_address) like :search ";
		$sql .= "	or lower(user_agent) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'timestamp', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$user_logs = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-user_logs'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-user_logs']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('user_log_delete') && $user_logs) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('user_log_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all&search='.$search]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'user_logs.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('user_log_delete') && $user_logs) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-user_logs']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('user_log_add') || permission_exists('user_log_edit') || permission_exists('user_log_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($user_logs ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == 'all' && permission_exists('user_log_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo "<th class='left'>".$text['label-date']."</th>\n";
	echo "<th class='left hide-md-dn'>".$text['label-time']."</th>\n";
	echo th_order_by('username', $text['label-username'], $order_by, $order);
	echo th_order_by('type', $text['label-type'], $order_by, $order);
	echo th_order_by('result', $text['label-result'], $order_by, $order);
	echo th_order_by('remote_address', $text['label-remote_address'], $order_by, $order);
	echo th_order_by('user_agent', $text['label-user_agent'], $order_by, $order);
	if (permission_exists('user_log_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($user_logs) && @sizeof($user_logs) != 0) {
		$x = 0;
		foreach ($user_logs as $row) {
			if (permission_exists('user_log_edit')) {
				$list_row_url = "user_log_edit.php?id=".urlencode($row['user_log_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('user_log_add') || permission_exists('user_log_edit') || permission_exists('user_log_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='user_logs[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='user_logs[$x][user_log_uuid]' value='".escape($row['user_log_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == 'all' && permission_exists('user_log_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td>".escape($row['date_formatted'])."</td>\n";
			echo "	<td class='left hide-md-dn'>".escape($row['time_formatted'])."</td>\n";
			echo "	<td>".escape($row['username'])."</td>\n";
			echo "	<td>".escape($row['type'])."</td>\n";
			echo "	<td>".escape($row['result'])."</td>\n";
			echo "	<td>".escape($row['remote_address'])."</td>\n";
			echo "	<td>".escape($row['user_agent'])."</td>\n";
			if (permission_exists('user_log_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($user_logs);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
