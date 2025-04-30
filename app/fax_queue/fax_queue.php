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
	Portions created by the Initial Developer are Copyright (C) 2023
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('fax_queue_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set defaults
	$database = database::new();
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
	$user_uuid = $_SESSION['user_uuid'] ?? '';
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);

//set default permissions
	$permission = [];
	$permission['fax_queue_add'] = permission_exists('fax_queue_add');
	$permission['fax_queue_delete'] = permission_exists('fax_queue_delete');
	$permission['fax_queue_domain'] = permission_exists('fax_queue_domain');
	$permission['fax_queue_all'] = permission_exists('fax_queue_all');
	$permission['fax_queue_edit'] = permission_exists('fax_queue_edit');

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (isset($_REQUEST['action'])) {
		$action = $_REQUEST['action'];
	}

//add the search
	if (isset($_REQUEST["search"])) {
		$search = strtolower($_REQUEST["search"]);
	}

//get the fax_queue item checked
	if (isset($_REQUEST['fax_queue'])) {
		$fax_queue = $_REQUEST['fax_queue'];
	}

//process the http post data by action
	if (!empty($action) && !empty($fax_queue) && !empty($fax_queue)) {

		switch ($action) {
			case 'copy':
				if ($permission['fax_queue_add']) {
					$obj = new fax_queue;
					$obj->copy($fax_queue);
				}
				break;
			//case 'toggle':
			//	if ($permission['fax_queue_edit']) {
			//		$obj = new fax_queue;
			//		$obj->toggle($fax_queue);
			//	}
			//	break;
			case 'delete':
				if ($permission['fax_queue_delete']) {
					$obj = new fax_queue;
					$obj->delete($fax_queue);
				}
				break;
		}

		//redirect the user
		header('Location: fax_queue.php'.(!empty($search) ? '?search='.urlencode($search) : ''));
		exit;
	}

//set the time zone
	$time_zone = $settings->get('domain', 'time_zone', date_default_timezone_get());

//get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//get the count
	$sql = "select count(fax_queue_uuid) ";
	$sql .= "from v_fax_queue as q ";
	$sql .= "LEFT JOIN v_users AS u ON q.insert_user = u.user_uuid ";
	if (!empty($_GET['show']) && $_GET['show'] == "all" && $permission['fax_queue_all']) {
		// show faxes for all domains
		$sql .= "WHERE true ";
	}
	elseif ($permission['fax_queue_domain']) {
		// show faxes for one domain
		$sql .= "WHERE q.domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	else {
		// show only assigned fax extensions
		$sql .= "WHERE q.domain_uuid = :domain_uuid ";
		$sql .= "AND u.user_uuid = :user_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['user_uuid'] = $user_uuid;
	}

	if (isset($search)) {
		$sql .= "AND (";
		$sql .= "	LOWER(q.hostname) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_caller_id_name) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_caller_id_number) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_number) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_email_address) LIKE :search ";
		$sql .= "	OR LOWER(u.username) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_file) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_status) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_accountcode) LIKE :search ";
		$sql .= ") ";
		$parameters['search'] = '%' . $search . '%';
	}

	if (isset($_GET["fax_status"]) && !empty($_GET["fax_status"])) {
			$sql .= "AND q.fax_status = :fax_status ";
			$parameters['fax_status'] = $_GET["fax_status"];
	}
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	$param = !empty($search) ? "&search=".$search : null;
	$param = (!empty($_GET['show']) && $_GET['show'] == 'all' && $permission['fax_queue_all']) ? "&show=all" : null;
	$page = !empty($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "SELECT ";
	$sql .= "d.domain_name, ";
	$sql .= "q.domain_uuid, ";
	$sql .= "q.fax_queue_uuid, ";
	$sql .= "q.fax_uuid, ";
	$sql .= "q.fax_date, ";
	$sql .= "to_char(timezone(:time_zone, q.fax_date), 'DD Mon YYYY') as fax_date_formatted, ";
	$sql .= "to_char(timezone(:time_zone, q.fax_date), 'HH12:MI:SS am') as fax_time_formatted, ";
	$sql .= "q.hostname, ";
	$sql .= "q.fax_caller_id_name, ";
	$sql .= "q.fax_caller_id_number, ";
	$sql .= "q.fax_number, ";
	$sql .= "q.fax_prefix, ";
	$sql .= "q.fax_email_address, ";
	$sql .= "u.username as insert_user, ";
	$sql .= "q.fax_file, ";
	$sql .= "q.fax_status, ";
	$sql .= "q.fax_retry_date, ";
	$sql .= "to_char(timezone(:time_zone, q.fax_retry_date), 'DD Mon YYYY') as fax_retry_date_formatted, ";
	$sql .= "to_char(timezone(:time_zone, q.fax_retry_date), 'HH12:MI:SS am') as fax_retry_time_formatted, ";
	$sql .= "q.fax_notify_date, ";
	$sql .= "to_char(timezone(:time_zone, q.fax_notify_date), 'DD Mon YYYY') as fax_notify_date_formatted, ";
	$sql .= "to_char(timezone(:time_zone, q.fax_notify_date), 'HH12:MI:SS am') as fax_notify_time_formatted, ";
	$sql .= "q.fax_retry_count, ";
	$sql .= "q.fax_accountcode, ";
	$sql .= "q.fax_command ";
	$sql .= "FROM v_fax_queue AS q ";
	$sql .= "LEFT JOIN v_users AS u ON q.insert_user = u.user_uuid ";
	$sql .= "JOIN v_domains AS d ON q.domain_uuid = d.domain_uuid ";

	if (!empty($_GET['show']) && $_GET['show'] == "all" && $permission['fax_queue_all']) {
		// show faxes for all domains
		$sql .= "WHERE true ";
	}
	elseif ($permission['fax_queue_domain']) {
		// show faxes for one domain
		$sql .= "WHERE q.domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	else {
		// show only assigned fax extensions
		$sql .= "WHERE q.domain_uuid = :domain_uuid ";
		$sql .= "AND u.user_uuid = :user_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['user_uuid'] = $user_uuid;
	}

	if (isset($search)) {
		$sql .= "AND (";
		$sql .= "	LOWER(q.hostname) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_caller_id_name) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_caller_id_number) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_number) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_email_address) LIKE :search ";
		$sql .= "	OR LOWER(u.username) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_file) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_status) LIKE :search ";
		$sql .= "	OR LOWER(q.fax_accountcode) LIKE :search ";
		$sql .= ") ";
		$parameters['search'] = '%' . $search . '%';
	}

	if (isset($_GET["fax_status"]) && !empty($_GET["fax_status"])) {
			$sql .= "AND q.fax_status = :fax_status ";
			$parameters['fax_status'] = $_GET["fax_status"];
	}

	$sql .= order_by($order_by, $order, 'fax_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['time_zone'] = $time_zone;
	$fax_queue = $database->select($sql, $parameters, 'all');
	unset ($sql, $parameters);

	//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

	//additional includes
	$document['title'] = $text['title-fax_queue'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-fax_queue']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'/app/fax/fax.php']);
	if ($permission['fax_queue_add']) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','name'=>'btn_add','link'=>'fax_queue_edit.php']);
	}
	if ($permission['fax_queue_add'] && $fax_queue) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	//if ($permission['fax_queue_edit'] && $fax_queue) {
	//	echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	//}
	if ($permission['fax_queue_delete'] && $fax_queue) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if ($permission['fax_queue_all']) {
		if (!empty($_GET['show']) && $_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$settings->get('theme', 'button_icon_all'),'link'=>'?show=all']);
		}
	}
	echo			"<form id='form_search' class='inline' method='get'>\n";
	echo "		<select class='formfld' name='fax_status' style='margin-left: 15px;'>\n";
	echo "			<option value='' selected='selected' disabled hidden>".$text['label-fax_status']."...</option>";
	echo "			<option value=''></option>\n";
	echo "			<option value='waiting' ".(!empty($_GET["fax_status"]) && $_GET["fax_status"] == "waiting" ? "selected='selected'" : null).">".ucwords($text['label-waiting'])."</option>\n";
	echo "			<option value='sending' ".(!empty($_GET["fax_status"]) && $_GET["fax_status"] == "sending" ? "selected='selected'" : null).">".ucwords($text['label-sending'])."</option>\n";
	echo "			<option value='trying' ".(!empty($_GET["fax_status"]) && $_GET["fax_status"] == "trying" ? "selected='selected'" : null).">".ucwords($text['label-trying'])."</option>\n";
	echo "			<option value='sent' ".(!empty($_GET["fax_status"]) && $_GET["fax_status"] == "sent" ? "selected='selected'" : null).">".ucwords($text['label-sent'])."</option>\n";
	echo "			<option value='busy' ".(!empty($_GET["fax_status"]) && $_GET["fax_status"] == "busy" ? "selected='selected'" : null).">".ucwords($text['label-busy'])."</option>\n";
	echo "			<option value='failed' ".(!empty($_GET["fax_status"]) && $_GET["fax_status"] == "failed" ? "selected='selected'" : null).">".ucwords($text['label-failed'])."</option>\n";
	echo "		</select>\n";
	echo			"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search ?? '')."\" placeholder=\"".$text['label-search']."\" />";
	echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
	if (!empty($paging_controls_mini)) {
		echo		"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($permission['fax_queue_add'] && $fax_queue) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	//if ($permission['fax_queue_edit'] && $fax_queue) {
	//	echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	//}
	if ($permission['fax_queue_delete'] && $fax_queue) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}


	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? '')."\">\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if ($permission['fax_queue_add'] || $permission['fax_queue_edit'] || $permission['fax_queue_delete']) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($fax_queue) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	if (!empty($_GET['show']) && $_GET['show'] == 'all' && $permission['fax_queue_all']) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	//echo th_order_by('fax_date', $text['label-fax_date'], $order_by, $order);
	echo "<th class='center shrink'>".$text['label-date']."</th>\n";
	echo "<th class='center shrink hide-md-dn'>".$text['label-time']."</th>\n";
	if ($permission['fax_queue_all']) {
		echo th_order_by('hostname', $text['label-hostname'], $order_by, $order, null, "class='hide-md-dn'");
	}
	echo th_order_by('fax_caller_id_name', $text['label-fax_caller_id_name'], $order_by, $order, null, "class='hide-md-dn'");
	echo th_order_by('fax_caller_id_number', $text['label-fax_caller_id_number'], $order_by, $order);
	echo th_order_by('fax_number', $text['label-fax_number'], $order_by, $order);
	echo th_order_by('fax_email_address', $text['label-fax_email_address'], $order_by, $order);
	echo th_order_by('insert_user', $text['label-insert_user'], $order_by, $order);
	//echo th_order_by('fax_file', $text['label-fax_file'], $order_by, $order);
	echo th_order_by('fax_status', $text['label-fax_status'], $order_by, $order);
	echo th_order_by('fax_retry_date', $text['label-fax_retry_date'], $order_by, $order);
	echo th_order_by('fax_notify_date', $text['label-fax_notify_date'], $order_by, $order);
	echo th_order_by('fax_retry_count', $text['label-fax_retry_count'], $order_by, $order);
	if ($permission['fax_queue_edit'] && $settings->get('theme', 'list_row_edit_button', false)) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($fax_queue)) {
		$x = 0;
		foreach ($fax_queue as $row) {
			$list_row_url = '';
			if ($permission['fax_queue_edit']) {
				$list_row_url = "fax_queue_edit.php?id=".urlencode($row['fax_queue_uuid']);
				if ($row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
					$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
				}
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if ($permission['fax_queue_add'] || $permission['fax_queue_edit'] || $permission['fax_queue_delete']) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='fax_queue[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='fax_queue[$x][fax_queue_uuid]' value='".escape($row['fax_queue_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if (!empty($_GET['show']) && $_GET['show'] == 'all' && $permission['fax_queue_all']) {
				echo "	<td>".escape($row['domain_name'])."</td>\n";
			}
			echo "	<td nowrap='nowrap'>".escape($row['fax_date_formatted'])."</td>\n";
			echo "	<td class='hide-md-dn' nowrap='nowrap'>".escape($row['fax_time_formatted'])."</td>\n";
			if ($permission['fax_queue_all']) {
				echo "	<td class='hide-md-dn'>".escape($row['hostname'])."</td>\n";
			}
			echo "	<td class='hide-md-dn'>".escape($row['fax_caller_id_name'])."</td>\n";
			echo "	<td>".escape($row['fax_caller_id_number'])."</td>\n";
			echo "	<td>".escape($row['fax_number'])."</td>\n";
			echo "	<td>".escape(str_replace(',', ' ', $row['fax_email_address'] ?? ''))."</td>\n";
			echo "	<td>".escape($row['insert_user']) ."</td>\n";
			//echo "	<td>".escape($row['fax_file'])."</td>\n";
			echo "	<td>".ucwords($text['label-'.$row['fax_status']])."</td>\n";
			echo "	<td>".escape($row['fax_retry_date_formatted'])." ".escape($row['fax_retry_time_formatted'])."</td>\n";
			echo "	<td>".escape($row['fax_notify_date_formatted'])." ".escape($row['fax_notify_time_formatted'])."</td>\n";
			echo "	<td>".escape($row['fax_retry_count'])."</td>\n";
			if ($permission['fax_queue_edit'] && $settings->get('theme', 'list_row_edit_button', false)) {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon' => $settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($fax_queue);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
