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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>

	The original Call Block was written by Gerrit Visser <gerrit308@gmail.com>
	All of it has been rewritten over years.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (!permission_exists('call_block_view')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set additional variables
	$search = $_GET["search"] ?? '';
	$show = $_GET["show"] ?? '';

//set from session variables
	$list_row_edit_button = !empty($_SESSION['theme']['list_row_edit_button']['boolean']) ? $_SESSION['theme']['list_row_edit_button']['boolean'] : 'false';

//get posted data
	if (!empty($_POST['call_blocks'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_blocks = $_POST['call_blocks'];
	}

//process the http post data by action
	if (!empty($action) && !empty($call_blocks)) {
		switch ($action) {
			case 'copy':
				if (permission_exists('call_block_add')) {
					$obj = new call_block;
					$obj->copy($call_blocks);
				}
				break;
			case 'toggle':
				if (permission_exists('call_block_edit')) {
					$obj = new call_block;
					$obj->toggle($call_blocks);
				}
				break;
			case 'delete':
				if (permission_exists('call_block_delete')) {
					$obj = new call_block;
					$obj->delete($call_blocks);
				}
				break;
		}

		header('Location: call_block.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get variables used to control the order
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//add the search term
	if (!empty($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}

//prepare to page the results
	$sql = "select count(*) from view_call_block ";
	$sql .= "where true ";
	if ($show == "all" && permission_exists('call_block_all')) {
		//show all records across all domains
	}
	else {
		$sql .= "and ( ";
		$sql .= "	domain_uuid = :domain_uuid ";
		if (permission_exists('call_block_domain')) {
			$sql .= "	or domain_uuid is null ";
		}
		$sql .= ") ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!permission_exists('call_block_extension') && !empty($_SESSION['user']['extension'])) {
		$sql .= "and extension_uuid in (";
		$x = 0;
		foreach ($_SESSION['user']['extension'] as $field) {
			if (is_uuid($field['extension_uuid'])) {
				$sql .= ($x == 0) ? "'".$field['extension_uuid']."'" : ",'".$field['extension_uuid']."'";
			}
			$x++;
		}
		$sql .= ") ";
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= " lower(call_block_name) like :search ";
		$sql .= " or lower(call_block_direction) like :search ";
		$sql .= " or lower(call_block_number) like :search ";
		$sql .= " or lower(call_block_app) like :search ";
		$sql .= " or lower(call_block_data) like :search ";
		$sql .= " or lower(call_block_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($parameters);

//prepare to page the results
	$rows_per_page = (!empty($_SESSION['domain']['paging']['numeric'])) ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if ($show == "all" && permission_exists('call_block_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'] ?? '';
	if (empty($page)) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select domain_uuid, call_block_uuid, call_block_direction, extension_uuid, call_block_name, ";
	$sql .= " call_block_country_code, call_block_number, extension, number_alias, call_block_count, ";
	$sql .= " call_block_app, call_block_data, ";
	$sql .= " to_char(timezone(:time_zone, insert_date), 'DD Mon YYYY') as date_formatted, \n";
	if (date(!empty($_SESSION['domain']['time_format']['text']) == '12h')) {
		$sql .= " to_char(timezone(:time_zone, insert_date), 'HH12:MI:SS am') as time_formatted, \n";
	}
	else {
		$sql .= " to_char(timezone(:time_zone, insert_date), 'HH24:MI:SS am') as time_formatted, \n";
	}
	$sql .= " call_block_enabled, call_block_description, insert_date, insert_user, update_date, update_user ";
	$sql .= "from view_call_block ";
	$sql .= "where true ";
	$parameters['time_zone'] = $time_zone;
	if ($show == "all" && permission_exists('call_block_all')) {
		//$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	else {
		$sql .= "and ( ";
		$sql .= "	domain_uuid = :domain_uuid ";
		if (permission_exists('call_block_domain')) {
			$sql .= "	or domain_uuid is null ";
		}
		$sql .= ") ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!permission_exists('call_block_extension') && !empty($_SESSION['user']['extension']) && count($_SESSION['user']['extension']) > 0) {
		$sql .= "and extension_uuid in (";
		$x = 0;
		foreach ($_SESSION['user']['extension'] as $field) {
			if (is_uuid($field['extension_uuid'])) {
				$sql .= ($x == 0) ? "'".$field['extension_uuid']."'" : ",'".$field['extension_uuid']."'";
			}
			$x++;
		}
		$sql .= ") ";
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= " lower(call_block_name) like :search ";
		$sql .= " or lower(call_block_direction) like :search ";
		$sql .= " or lower(call_block_number) like :search ";
		$sql .= " or lower(call_block_app) like :search ";
		$sql .= " or lower(call_block_data) like :search ";
		$sql .= " or lower(call_block_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, ['domain_uuid','call_block_country_code','call_block_number']);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//determine if any global
	$global_call_blocks = false;
	if (permission_exists('call_block_domain') && !empty($result) && is_array($result) && @sizeof($result) != 0) {
		foreach ($result as $row) {
			if (!is_uuid($row['domain_uuid'])) { $global_call_blocks = true; break; }
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-call_block'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_block']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('call_block_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'call_block_edit.php']);
	}
	if (permission_exists('call_block_add') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('call_block_edit') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('call_block_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('call_block_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?type='.urlencode($destination_type ?? '').'&show=all'.($search != '' ? "&search=".urlencode($search ?? '') : null)]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_block.php','style'=>($search == '' ? 'display: none;' : null)]);
	if (!empty($paging_controls_mini)) {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('call_block_add') && $result) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('call_block_edit') && $result) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('call_block_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-call-block']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('call_block_add') || permission_exists('call_block_edit') || permission_exists('call_block_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($result) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == 'all' && permission_exists('domain_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	else if (permission_exists('call_block_domain') && $global_call_blocks) {
		echo th_order_by('domain_uuid', $text['label-domain'], $order_by, $order, null, "style='width: 1%;' class='center'");
	}
	echo th_order_by('call_block_direction', $text['label-direction'], $order_by, $order, null, "style='width: 1%;' class='center'");
	echo th_order_by('extension', $text['label-extension'], $order_by, $order, null, "class='center'");
	echo th_order_by('call_block_name', $text['label-name'], $order_by, $order);
	echo th_order_by('call_block_country_code', $text['label-country_code'], $order_by, $order);
	echo th_order_by('call_block_number', $text['label-number'], $order_by, $order);
	echo th_order_by('call_block_count', $text['label-count'], $order_by, $order, '', "class='center hide-sm-dn'");
	echo th_order_by('call_block_action', $text['label-action'], $order_by, $order);
	echo th_order_by('call_block_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('insert_date', $text['label-date-added'], $order_by, $order, null, "class='shrink no-wrap'");
	echo "<th class='hide-md-dn pct-20'>".$text['label-description']."</th>\n";
	if (permission_exists('call_block_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($result)) {
		$x = 0;
		foreach ($result as $row) {
			if (permission_exists('call_block_edit')) {
				$list_row_url = "call_block_edit.php?id=".urlencode($row['call_block_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('call_block_add') || permission_exists('call_block_edit') || permission_exists('call_block_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='call_blocks[".$x."][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='call_blocks[".$x."][uuid]' value='".escape($row['call_block_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if (!empty($show) && $show == 'all' && permission_exists('domain_all')) {
				if (!empty($row['domain_uuid']) && is_uuid($row['domain_uuid'])) {
					echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
				}
				else {
					echo "	<td>".$text['label-global']."</td>\n";
				}
			}
			else if ($global_call_blocks) {
				if (permission_exists('call_block_domain') && !is_uuid($row['domain_uuid'])) {
					echo "	<td>".$text['label-global'];
				}
				else {
					echo "	<td class='overflow'>";
					echo escape($_SESSION['domains'][$row['domain_uuid']]['domain_name']);
				}
				echo "</td>\n";
			}
			echo "	<td class='center'>";
			switch ($row['call_block_direction']) {
				case "inbound": echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_answered.png' style='border: none;' title='".$text['label-inbound']."'>\n"; break;
				case "outbound": echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_outbound_answered.png' style='border: none;' title='".$text['label-outbound']."'>\n"; break;
			}
			echo "	</td>\n";
			echo "	<td class='center'>";
			if (empty($row['extension'])) {
				echo $text['label-all'];
			}
			else {
				echo escape($row['extension']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['call_block_name'])."</td>\n";
			echo "	<td>";
			if (permission_exists('call_block_edit')) {
				echo "<a href='".$list_row_url."'>".escape($row['call_block_country_code'])."</a>";
			}
			else {
				echo escape($row['call_block_country_code']);
			}
			echo "	</td>\n";
			echo "	<td>";
			if (permission_exists('call_block_edit')) {
				echo "<a href='".$list_row_url."'>".escape(format_phone($row['call_block_number']))."</a>";
			}
			else {
				echo escape(format_phone($row['call_block_number']));
			}
			echo "	</td>\n";
			echo "	<td class='center hide-sm-dn'>".escape($row['call_block_count'])."</td>\n";
			echo "	<td>".$text['label-'.$row['call_block_app']]." ".escape($row['call_block_data'])."</td>\n";
			if (permission_exists('call_block_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['call_block_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['call_block_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='no-wrap'>".$row['date_formatted']." <span class='hide-sm-dn'>".$row['time_formatted']."</span></td>\n";
			echo "	<td class='description overflow hide-md-dn'>".escape($row['call_block_description'])."</td>\n";
			if (permission_exists('call_block_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($result);
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
