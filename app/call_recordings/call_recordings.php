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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_recording_view')) {
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
	if (is_array($_POST['call_recordings'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_recordings = $_POST['call_recordings'];
	}

//process the http post data by action
	if ($action != '' && is_array($call_recordings) && @sizeof($call_recordings) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('call_recording_add')) {
					$obj = new call_recordings;
					$obj->copy($call_recordings);
				}
				break;
			case 'toggle':
				if (permission_exists('call_recording_edit')) {
					$obj = new call_recordings;
					$obj->toggle($call_recordings);
				}
				break;
			case 'delete':
				if (permission_exists('call_recording_delete')) {
					$obj = new call_recordings;
					$obj->delete($call_recordings);
				}
				break;
		}

		header('Location: call_recordings.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search string
	if (isset($_GET["search"])) {
		$search =  strtolower($_GET["search"]);
		$sql_search = " (";
		$sql_search .= "	lower(call_recording_name) like :search ";
		$sql_search .= "	or lower(call_recording_path) like :search ";
		$sql_search .= "	or lower(call_recording_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(call_recording_uuid) from v_call_recordings ";
	if (isset($sql_search)) {
		$sql .= "where ".$sql_search;
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//get the list
	$sql = str_replace('count(call_recording_uuid)', '*', $sql);
	$sql .= order_by($order_by, $order, 'call_recording_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$call_recordings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_recordings']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('call_recording_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'link'=>'call_recording_edit.php']);
	}
	if (permission_exists('call_recording_delete') && $call_recordings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'onclick'=>"if (confirm('".$text['confirm-delete']."')) { list_action_set('delete'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_recordings.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-call_recordings']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('call_recording_add') || permission_exists('call_recording_edit') || permission_exists('call_recording_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($call_recordings ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('call_recording_name', $text['label-call_recording_name'], $order_by, $order);
	echo th_order_by('call_recording_length', $text['label-call_recording_length'], $order_by, $order);
	echo th_order_by('call_recording_date', $text['label-call_recording_date'], $order_by, $order);
	echo th_order_by('call_direction', $text['label-call_direction'], $order_by, $order);
	echo "	<th class='hide-sm-dn'>".$text['label-call_recording_description']."</th>\n";
	if (permission_exists('call_recording_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($call_recordings) && @sizeof($call_recordings) != 0) {
		$x = 0;
		foreach ($call_recordings as $row) {
			if (permission_exists('call_recording_edit')) {
				$list_row_url = "call_recording_edit.php?id=".urlencode($row['call_recording_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('call_recording_add') || permission_exists('call_recording_edit') || permission_exists('call_recording_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='call_recordings[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='call_recordings[$x][uuid]' value='".escape($row['call_recording_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('call_recording_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['call_recording_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['call_recording_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['call_recording_length'])."</td>\n";
			echo "	<td>".escape($row['call_recording_date'])."</td>\n";
			echo "	<td>".escape($row['call_direction'])."</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['call_recording_description'])."</td>\n";
			if (permission_exists('call_recording_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($call_recordings);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
