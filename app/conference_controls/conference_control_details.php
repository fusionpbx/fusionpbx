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
	Portions created by the Initial Developer are Copyright (C) 2018-2019
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
	if (permission_exists('conference_control_detail_view')) {
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
	if (is_array($_POST['conference_control_details'])) {
		$action = $_POST['action'];
		$conference_control_uuid = $_POST['conference_control_uuid'];
		$conference_control_details = $_POST['conference_control_details'];
	}

//process the http post data by action
	if ($action != '' && is_array($conference_control_details) && @sizeof($conference_control_details) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('conference_control_detail_edit')) {
					$obj = new conference_controls;
					$obj->conference_control_uuid = $conference_control_uuid;
					$obj->toggle_details($conference_control_details);
				}
				break;
			case 'delete':
				if (permission_exists('conference_control_detail_delete')) {
					$obj = new conference_controls;
					$obj->conference_control_uuid = $conference_control_uuid;
					$obj->delete_details($conference_control_details);
				}
				break;
		}

		header('Location: conference_control_edit.php?id='.urlencode($conference_control_uuid));
		exit;
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = $_GET["search"];
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "control_digits like :search";
		$sql_search .= "or control_action like :search";
		$sql_search .= "or control_data like :search";
		$sql_search .= "or control_enabled like :search";
		$sql_search .= ")";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(conference_control_detail_uuid) ";
	$sql .= "from v_conference_control_details ";
	$sql .= "where conference_control_uuid = :conference_control_uuid ";
	$sql .= $sql_search;
	$parameters['conference_control_uuid'] = $conference_control_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&id=".$conference_control_uuid;
	if (isset($_GET['page'])) {	
		$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;
	}

//get the list
	$sql = str_replace('count(conference_control_detail_uuid)', '*', $sql);
	$sql .= $sql_search;
	$sql .= order_by($order_by, $order, 'control_digits', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/app/conference_controls/conference_control_details.php');

//show the content
	echo "<div class='action_bar' id='action_bar_sub'>\n";
	echo "	<div class='heading'><b id='heading_sub'>".$text['title-conference_control_details']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','id'=>'action_bar_sub_button_back','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'collapse'=>'hide-xs','style'=>'margin-right: 15px; display: none;','link'=>'conference_controls.php']);
	if (permission_exists('conference_control_detail_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'name'=>'btn_add','collapse'=>'hide-xs','link'=>'conference_control_detail_edit.php?conference_control_uuid='.urlencode($_GET['id'])]);
	}
	if (permission_exists('conference_control_detail_edit') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','collapse'=>'hide-xs','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('conference_control_detail_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 	"</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('conference_control_detail_edit') && $result) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('conference_control_detail_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<form id='form_list' method='post' action='conference_control_details.php'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='conference_control_uuid' value=\"".escape($conference_control_uuid)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('conference_control_detail_edit') || permission_exists('conference_control_detail_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('control_digits', $text['label-control_digits'], $order_by, $order, null, "class='pct-5 center'", $param);
	echo th_order_by('control_action', $text['label-control_action'], $order_by, $order, null, null, $param);
	echo th_order_by('control_data', $text['label-control_data'], $order_by, $order, null, "class='pct-50 hide-xs'", $param);
	echo th_order_by('control_enabled', $text['label-control_enabled'], $order_by, $order, null, "class='center'", $param);
	if (permission_exists('conference_control_detail_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$x = 0;
		foreach ($result as $row) {
			if (permission_exists('conference_control_detail_edit')) {
				$list_row_url = 'conference_control_detail_edit.php?conference_control_uuid='.urlencode($row['conference_control_uuid']).'&id='.urlencode($row['conference_control_detail_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('conference_control_detail_edit') || permission_exists('conference_control_detail_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='conference_control_details[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='conference_control_details[$x][uuid]' value='".escape($row['conference_control_detail_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td class='center'>".escape($row['control_digits'])."&nbsp;</td>\n";
			echo "	<td>\n";
			if (permission_exists('conference_control_detail_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['control_action'])."</a>\n";
			}
			else {
				echo "	".escape($row['control_action']);
			}
			echo "	</td>\n";
			echo "	<td class='overflow hide-xs'>".escape($row['control_data'])."&nbsp;</td>\n";
			if (permission_exists('conference_control_detail_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['control_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['control_enabled']];
			}
			echo "	</td>\n";
			if (permission_exists('conference_control_detail_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
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
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//make sub action bar sticky
	echo "<script>\n";
	echo "	window.addEventListener('scroll', function(){\n";
	echo "		action_bar_scroll('action_bar_sub', 255, heading_modify, heading_restore);\n";
	echo "	}, false);\n";

	echo "	function heading_modify() {\n";
	echo "		document.getElementById('heading_sub').innerHTML = \"".$text['title-conference_controls']."\";\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'inline-block';\n";
	echo "	}\n";

	echo "	function heading_restore() {\n";
	echo "		document.getElementById('heading_sub').innerHTML = \"".$text['title-conference_control_details']."\";\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'none';\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>