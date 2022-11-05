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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
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

//get the fax_uuid
	$fax_uuid = $_REQUEST["id"];

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the http post data
	if (is_array($_POST['fax_logs'])) {
		$action = $_POST['action'];
		$fax_logs = $_POST['fax_logs'];
	}

//process the http post data by action
	if ($action != '' && is_array($fax_logs) && @sizeof($fax_logs) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('fax_log_delete')) {
					$obj = new fax;
					$obj->fax_uuid = $fax_uuid;
					$obj->delete_logs($fax_logs);
				}
				break;
		}

		header('Location: fax_logs.php?id='.urlencode($fax_uuid));
		exit;
	}

//add the search string
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = " and (";
		$sql_search .= "	lower(fax_result_text) like :search ";
		$sql_search .= "	or lower(fax_file) like :search ";
		$sql_search .= "	or lower(fax_local_station_id) like :search ";
		$sql_search .= "	or fax_date::text like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(fax_log_uuid) from v_fax_logs ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and fax_uuid = :fax_uuid ";
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['fax_uuid'] = $fax_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&id=".$fax_uuid."&order_by=".$order_by."&order=".$order."&search=".$search;
	if (isset($_GET['page'])) {
		$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $page;
	}

//get the list
	$sql = str_replace('count(fax_log_uuid)', '*', $sql);
	$sql .= order_by($order_by, $order, 'fax_epoch', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$fax_logs = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-fax_logs'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-fax_logs']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'fax.php']);
	if (permission_exists('fax_log_delete') && $fax_logs) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>$_SESSION['theme']['button_icon_refresh'],'style'=>'margin-left: 15px;','onclick'=>'document.location.reload(true);']);
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='hidden' name='id' value='".escape($fax_uuid)."'>";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'fax_logs.php?id='.$fax_uuid,'style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('fax_log_delete') && $fax_logs) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-fax_log']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('fax_log_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($fax_logs ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('fax_epoch', $text['label-fax_date'], $order_by, $order, null, null, "&id=".$fax_uuid);
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
	//echo th_order_by('fax_epoch', $text['label-fax_epoch'], $order_by, $order);
	if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($fax_logs) && @sizeof($fax_logs) != 0) {
		$x = 0;
		foreach ($fax_logs as $row) {
			$list_row_url = "fax_log_view.php?id=".urlencode($row['fax_log_uuid'])."&fax_uuid=".$fax_uuid;
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('fax_log_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='fax_logs[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='fax_logs[$x][uuid]' value='".escape($row['fax_log_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td><a href='".$list_row_url."'>".($_SESSION['domain']['time_format']['text'] == '12h' ? date("j M Y g:i:sa", $row['fax_epoch']) : date("j M Y H:i:s", $row['fax_epoch']))."</a>&nbsp;</td>\n";
			echo "	<td>".$row['fax_success']."&nbsp;</td>\n";
			echo "	<td>".$row['fax_result_code']."&nbsp;</td>\n";
			echo "	<td>".$row['fax_result_text']."&nbsp;</td>\n";
			echo "	<td>".basename($row['fax_file'])."&nbsp;</td>\n";
			echo "	<td>".$row['fax_ecm_used']."&nbsp;</td>\n";
			echo "	<td>".$row['fax_local_station_id']."&nbsp;</td>\n";
			//echo "	<td>".$row['fax_document_transferred_pages']."&nbsp;</td>\n";
			//echo "	<td>".$row['fax_document_total_pages']."&nbsp;</td>\n";
			//echo "	<td>".$row['fax_image_resolution']."&nbsp;</td>\n";
			//echo "	<td>".$row['fax_image_size']."&nbsp;</td>\n";
			echo "	<td>".$row['fax_bad_rows']."&nbsp;</td>\n";
			echo "	<td>".$row['fax_transfer_rate']."&nbsp;</td>\n";
			echo "	<td>".$row['fax_retry_attempts']."&nbsp;</td>\n";
			//echo "	<td>".$row['fax_retry_limit']."&nbsp;</td>\n";
			//echo "	<td>".$row['fax_retry_sleep']."&nbsp;</td>\n";
			echo "	<td>".basename($row['fax_uri'])."&nbsp;</td>\n";
			//echo "	<td>".$row['fax_epoch']."&nbsp;</td>\n";
			if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($fax_logs);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='id' value='".escape($fax_uuid)."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>