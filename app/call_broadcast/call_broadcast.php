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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('call_broadcast_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
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
	if (!empty($_POST['call_broadcasts'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_broadcasts = $_POST['call_broadcasts'];
	}

//process the http post data by action
	if (!empty($action) && is_array($call_broadcasts)) {
		switch ($action) {
			case 'copy':
				if (permission_exists('call_broadcast_add')) {
					$obj = new call_broadcast;
					$obj->copy($call_broadcasts);
				}
				break;
			case 'delete':
				if (permission_exists('call_broadcast_delete')) {
					$obj = new call_broadcast;
					$obj->delete($call_broadcasts);
				}
				break;
		}

		header('Location: call_broadcast.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get the http get variables and set them to php variables
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//add the search term
	if (!empty($search)) {
		$search = strtolower($_GET["search"]);
	}

//get the count
	$sql = "select count(*) from v_call_broadcasts ";
	$sql .= "where true ";
	if ($show != "all" || !permission_exists('call_broadcast_all')) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "	lower(broadcast_name) like :search ";
		$sql .= "	or lower(broadcast_description) like :search ";
		$sql .= "	or lower(broadcast_caller_id_name) like :search ";
		$sql .= "	or lower(broadcast_caller_id_number) like :search ";
		$sql .= "	or lower(broadcast_phone_numbers) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');

//prepare the paging
	$param = '';
	$rows_per_page = (!empty($_SESSION['domain']['paging']['numeric'])) ? $_SESSION['domain']['paging']['numeric'] : 50;
	if (!empty($search)) {
		$param .= "&search=".urlencode($search);
	}
	if ($show == "all" && permission_exists('call_broadcast_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'] ?? '';
	if (empty($page)) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param ?? null, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param ?? null, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the call broadcasts
	$sql = "select call_broadcast_uuid, domain_uuid, broadcast_name, ";
	$sql .= "broadcast_description, broadcast_start_time, broadcast_timeout, ";
	$sql .= "broadcast_concurrent_limit, recording_uuid, broadcast_caller_id_name, ";
	$sql .= "broadcast_caller_id_number, broadcast_destination_type, broadcast_phone_numbers, ";
	$sql .= "broadcast_avmd, broadcast_destination_data, broadcast_accountcode, broadcast_toll_allow ";
	$sql .= "from v_call_broadcasts ";
	$sql .= "where true ";
	if ($show != "all" || !permission_exists('call_broadcast_all')) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "	lower(broadcast_name) like :search ";
		$sql .= "	or lower(broadcast_description) like :search ";
		$sql .= "	or lower(broadcast_caller_id_name) like :search ";
		$sql .= "	or lower(broadcast_caller_id_number) like :search ";
		$sql .= "	or lower(broadcast_phone_numbers) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'broadcast_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-call_broadcast'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_broadcast']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('call_broadcast_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'call_broadcast_edit.php']);
	}
	if (permission_exists('call_broadcast_add') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('call_broadcast_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('call_broadcast_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?type='.urlencode($destination_type ?? '').'&show=all'.(!empty($search) ? "&search=".urlencode($search ?? '') : null)]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_broadcast.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('call_broadcast_add') && $result) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('call_broadcast_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-call_broadcast']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('call_broadcast_add') || permission_exists('call_broadcast_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($result) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == "all" && permission_exists('call_broadcast_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('broadcast_name', $text['label-name'], $order_by, $order);
	echo th_order_by('broadcast_concurrent_limit', $text['label-concurrent-limit'], $order_by, $order);
	echo th_order_by('broadcast_start_time', $text['label-start_time'], $order_by, $order);
	echo th_order_by('broadcast_description', $text['label-description'], $order_by, $order);
	if (permission_exists('call_broadcast_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($result)) {
		$x = 0;
		foreach($result as $row) {
			if (permission_exists('call_broadcast_edit')) {
				$list_row_url = "call_broadcast_edit.php?id=".urlencode($row['call_broadcast_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('call_broadcast_add') || permission_exists('call_broadcast_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='call_broadcasts[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='call_broadcasts[$x][uuid]' value='".escape($row['call_broadcast_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == "all" && permission_exists('call_broadcast_all')) {
				if (!empty($_SESSION['domains'][$row['domain_uuid']]['domain_name'])) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td>";
			if (permission_exists('call_broadcast_edit')) {
				echo "<a href='".$list_row_url."'>".escape($row['broadcast_name'] ?? '')."</a>";
			}
			else {
				echo escape($row['broadcast_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['broadcast_concurrent_limit'])."</td>\n";
			//determine start date and time
			$broadcast_start_reference = !empty($row['update_date']) ?: !empty($row['insert_date']);
			if ($row['broadcast_start_time'] && $broadcast_start_reference) {
				$broadcast_start_time = date('Y-m-d H:i', strtotime($broadcast_start_reference) + $row['broadcast_start_time']);
			}
			echo "	<td>".escape($broadcast_start_time ?? '')."</td>\n";
			echo "	<td class='description overflow hide-xs'>".escape($row['broadcast_description'])."</td>\n";
			if (permission_exists('call_broadcast_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($result);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
