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
	Portions created by the Initial Developer are Copyright (C) 2018-2020
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('stream_view')) {
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
	if (is_array($_POST['streams'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$streams = $_POST['streams'];
	}

//process the http post data by action
	if ($action != '' && is_array($streams) && @sizeof($streams) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('stream_add')) {
					$obj = new streams;
					$obj->copy($streams);
				}
				break;
			case 'toggle':
				if (permission_exists('stream_edit')) {
					$obj = new streams;
					$obj->toggle($streams);
				}
				break;
			case 'delete':
				if (permission_exists('stream_delete')) {
					$obj = new streams;
					$obj->delete($streams);
				}
				break;
		}

		header('Location: streams.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(stream_name) like :search ";
		$sql_search .= "or lower(stream_location) like :search ";
		$sql_search .= "or lower(stream_enabled) like :search ";
		$sql_search .= "or lower(stream_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(stream_uuid) from v_streams where true ";
	$sql .= $sql_search;
	if (!($_GET['show'] == "all" && permission_exists('stream_all'))) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$param = ($_GET['show'] == 'all' && permission_exists('stream_all')) ? "&show=all" : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(stream_uuid)', '*', $sql);
	$sql .= order_by($order_by, $order, 'stream_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$streams = $database->select($sql, (is_array($parameters) && @sizeof($parameters) != 0 ? $parameters : null), 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include header
	$document['title'] = $text['title-streams'];
	require_once "resources/header.php";

//audio control styles
	echo "<style>\n";
	echo "	audio {\n";
	echo "		margin-top: 0px;\n";
	echo "		margin-bottom: -6px;\n";
	echo "		width: 100%;\n";
	echo "		height: 35px;\n";
	echo "	}\n";
	echo "</style>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-streams']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('stream_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'stream_edit.php']);
	}
	if (permission_exists('stream_add') && $streams) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('stream_edit') && $streams) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('stream_delete') && $streams) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('stream_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'streams.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('stream_add') && $streams) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('stream_edit') && $streams) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('stream_delete') && $streams) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-stream']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('stream_add') || permission_exists('stream_edit') || permission_exists('stream_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($streams ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == 'all' && permission_exists('stream_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo th_order_by('stream_name', $text['label-stream_name'], $order_by, $order);
	echo "	<th class='pct-60'>".$text['label-play']."</th>\n";
	echo th_order_by('stream_enabled', $text['label-stream_enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('stream_description', $text['label-stream_description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('stream_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($streams) && @sizeof($streams) != 0) {
		$x = 0;
		foreach ($streams as $row) {
			if (permission_exists('stream_edit')) {
				$list_row_url = "stream_edit.php?id=".urlencode($row['stream_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('stream_add') || permission_exists('stream_edit') || permission_exists('stream_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='streams[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='streams[$x][uuid]' value='".escape($row['stream_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == 'all' && permission_exists('stream_all')) {
				echo "	<td>";
				if ($_SESSION['domains'][$row['domain_uuid']]['domain_name'] != '') {
					echo escape($_SESSION['domains'][$row['domain_uuid']]['domain_name']);
				}
				else {
					echo $text['label-global'];
				}
				echo "	</td>\n";
			}
			echo "	<td class='no-wrap'>\n";
			if (permission_exists('stream_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['stream_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['stream_name']);
			}
			echo "	</td>\n";
			echo "	<td class='no-wrap button'>\n";
			if (strlen($row['stream_location']) > 0) {
				$location_parts = explode('://',$row['stream_location']);
				if ($location_parts[0] == "shout") {
					echo "<audio src='http://".$location_parts[1]."' controls='controls' />\n";
				}
			}
			echo "	</td>\n";
			if (permission_exists('stream_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['stream_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['stream_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['stream_description'])."&nbsp;</td>\n";
			if (permission_exists('stream_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($streams);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>