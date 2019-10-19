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
	require_once "resources/paging.php";

//check permissions
	if (!permission_exists('bridge_view')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted data
	if (is_array($_POST['bridges'])) {
		$action = $_POST['action'];
		$bridges = $_POST['bridges'];
	}

//copy the bridges
	if (permission_exists('bridge_add')) {
		if ($action == 'copy' && is_array($bridges) && @sizeof($bridges) != 0) {
			//copy
				$obj = new bridges;
				$obj->copy($bridges);
			//redirect
				header('Location: bridges.php');
				exit;
		}
	}

//toggle the bridges
	if (permission_exists('bridge_edit')) {
		if ($action == 'toggle' && is_array($bridges) && @sizeof($bridges) != 0) {
			//toggle
				$obj = new bridges;
				$obj->toggle($bridges);
			//redirect
				header('Location: bridges.php');
				exit;
		}
	}

//delete the bridges
	if (permission_exists('bridge_delete')) {
		if ($action == 'delete' && is_array($bridges) && @sizeof($bridges) != 0) {
			//delete
				$obj = new bridges;
				$obj->delete($bridges);
			//redirect
				header('Location: bridges.php');
				exit;
		}
	}

//get order and order by and sanatize the values
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = " (";
		$sql_search .= "	lower(bridge_name) like :search ";
		$sql_search .= "	or lower(bridge_destination) like :search ";
		$sql_search .= "	or lower(bridge_enabled) like :search ";
		$sql_search .= ") ";

		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(*) from v_bridges ";
	if ($_GET['show'] == "all" && permission_exists('bridge_all')) {
		if (isset($sql_search)) {
			$sql .= "where ".$sql_search;
		}
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		if (isset($sql_search)) {
			$sql .= "and ".$sql_search;
		}
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if ($_GET['show'] == "all" && permission_exists('bridge_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'bridge_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$bridges = $database->select($sql, $parameters, 'all');

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//include the header
	require_once "resources/header.php";

//define the checkbox functions
	echo "<script type='text/javascript'>\n";
	echo "	function checkbox_toggle() {\n";
	echo "		var inputs = document.getElementsByTagName('input');\n";
	echo "		var box_checked = document.getElementById('checkbox_all').checked;\n";
	echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	echo "			if (inputs[i].type === 'checkbox') {\n";
	echo "				inputs[i].checked = box_checked;\n";
	echo "			}\n";
	echo "		}\n";
	echo "		if (box_checked) {\n";
	echo "			document.getElementById('btn_check_all').style.display = 'none';\n";
	echo "			document.getElementById('btn_check_none').style.display = '';\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			document.getElementById('btn_check_all').style.display = '';\n";
	echo "			document.getElementById('btn_check_none').style.display = 'none';\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<b style='float: left;'>".$text['title-bridges']." (".$num_rows.")</b>\n";
	if (permission_exists('bridge_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'link'=>'bridge_edit.php']);
	}
	if (permission_exists('bridge_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'onclick'=>"if (confirm('".$text['confirm-copy']."')) { set_action('copy'); submit_form('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('bridge_edit')) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'onclick'=>"if (confirm('".$text['confirm-toggle']."')) { set_action('toggle'); submit_form('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('bridge_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'onclick'=>"if (confirm('".$text['confirm-delete']."')) { set_action('delete'); submit_form('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('bridge_all')) {
		if ($_GET['show'] == 'all') {
			echo "	<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo "<form id='form_search' class='inline' method='get'>\n";
	echo "<input type='text' class='txt search' name='search' id='search' value='".escape($search)."' placeholder=\"".$text['label-search']."\" onkeydown='reset_search();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'bridges.php','style'=>($search == '' ? 'display: none;' : null)]);
	echo "</form>\n";
	echo "</div>\n";

	echo $text['title_description-bridge']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th style='width: 30px;'>\n";
	echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' value='' onclick='checkbox_toggle();'>\n";
	echo "	</th>\n";
	if ($_GET['show'] == "all" && permission_exists('bridge_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo th_order_by('bridge_name', $text['label-bridge_name'], $order_by, $order);
	echo th_order_by('bridge_destination', $text['label-bridge_destination'], $order_by, $order);
	echo th_order_by('bridge_enabled', $text['label-bridge_enabled'], $order_by, $order, null, "style='text-align: center;'");
	echo "	<th>".$text['label-description']."</th>\n";
	echo "	<td style='width: 1px;'>&nbsp;</td>\n";
	echo "</tr>\n";

	if (is_array($bridges)) {
		$x = 0;
		foreach($bridges as $row) {
			if (permission_exists('bridge_edit')) {
				$tr_link = "href='bridge_edit.php?id=".escape($row['bridge_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='align: center; padding: 3px 3px 0px 8px;'>\n";
			echo "		<input type='checkbox' name='bridges[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
			echo "		<input type='hidden' name='bridges[$x][bridge_uuid]' value='".escape($row['bridge_uuid'])."' />\n";
			echo "	</td>\n";
			if ($_GET['show'] == "all" && permission_exists('bridge_all')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('bridge_edit')) {
				echo "	<a ".$tr_link." title=\"".$text['button-edit']."\">".escape($row['bridge_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['bridge_name'])."\n";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['bridge_destination'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center;'>";
			echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['bridge_enabled']],'title'=>$text['button-toggle'],'onclick'=>"check_self('checkbox_".$x."'); set_action('toggle'); submit_form('form_list')"]);
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['bridge_description'])."</td>\n";
			echo "	<td style='width: 0; white-space: nowrap;' class='tr_link_void'>";

			if (permission_exists('bridge_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>"bridge_edit.php?id=".escape($row['bridge_uuid'])]);
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = $c ? 0 : 1;
			$x++;
		}
		unset($sql, $bridges);
	}

	echo "<tr>\n";
	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//handle form actions
	echo "<script type='text/javascript'>\n";
	echo "	function check_self(checkbox_id) {\n";
	echo "		document.getElementById(checkbox_id).checked = true;\n";
	echo "	}\n";

	echo "	function set_action(action) {\n";
	echo "		document.getElementById('action').value = action;\n";
	echo "	}\n";

	echo "	function submit_form(form_id) {\n";
	echo "		document.getElementById(form_id).submit();\n";
	echo "	}\n";

	echo "	function reset_search() {\n";
	echo "		document.getElementById('btn_reset').style.display = 'none';\n";
	echo "		document.getElementById('btn_search').style.display = '';\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
