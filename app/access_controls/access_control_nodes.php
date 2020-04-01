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
	Portions created by the Initial Developer are Copyright (C) 2019
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
	if (!permission_exists('access_control_node_view')) {
		echo "access denied";
		exit;
	}

//get the http post data
	if ($_POST['action'] != '') {
		$action = $_POST['action'];
		$access_control_uuid = $_POST['access_control_uuid'];
		$access_control_nodes = $_POST['access_control_nodes'];

		//process the http post data by action
			if (is_array($access_control_nodes) && @sizeof($access_control_nodes) != 0) {
				switch ($action) {
					case 'delete':
						if (permission_exists('access_control_node_delete')) {
							$obj = new access_controls;
							$obj->delete_nodes($access_control_nodes);
						}
						break;
				}
			}

		//redirect
			header('Location: access_control_edit.php?id='.urlencode($access_control_uuid));
			exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//prepare to page the results
	$sql = "select count(*) from v_access_control_nodes ";
	$sql .= "where access_control_uuid = :access_control_uuid ";
	$parameters['access_control_uuid'] = $access_control_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&id=".escape($access_control_uuid);
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;
	}

//get the list
	$sql = "select * from v_access_control_nodes ";
	$sql .= "where access_control_uuid = :access_control_uuid ";
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['access_control_uuid'] = $access_control_uuid;
	$database = new database;
	$access_control_nodes = $database->select($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/app/access_controls/access_control_nodes.php');

//show the content
	echo "<form id='form_list' method='post' action='access_control_nodes.php'>\n";
	echo "<input type='hidden' name='action' id='action' value=''>\n";
	echo "<input type='hidden' name='access_control_uuid' value='".escape($access_control_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar_sub'>\n";
	echo "	<div class='heading'><b id='heading_sub'>".$text['title-access_control_nodes']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','id'=>'action_bar_sub_button_back','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'collapse'=>'hide-xs','style'=>'margin-right: 15px; display: none;','link'=>'access_controls.php']);
	if (permission_exists('access_control_node_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','collapse'=>'hide-xs','link'=>'access_control_node_edit.php?access_control_uuid='.urlencode($_GET['id'])]);
	}
	if (permission_exists('access_control_node_delete') && $access_control_nodes) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete-access-control-node','btn_delete_access_control_node');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('access_control_node_delete') && $access_control_nodes) {
		echo modal::create(['id'=>'modal-delete-access-control-node','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete_access_control_node','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('access_control_node_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($access_control_nodes ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('node_type', $text['label-node_type'], $order_by, $order);
	echo th_order_by('node_cidr', $text['label-node_cidr'], $order_by, $order);
	echo th_order_by('node_domain', $text['label-node_domain'], $order_by, $order);
	echo th_order_by('node_description', $text['label-node_description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('access_control_node_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($access_control_nodes) && @sizeof($access_control_nodes) != 0) {
		$x = 0;
		foreach ($access_control_nodes as $row) {
			if (permission_exists('access_control_node_edit')) {
				$list_row_url = 'access_control_node_edit.php?access_control_uuid='.urlencode($row['access_control_uuid'])."&id=".urlencode($row['access_control_node_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('access_control_node_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='access_control_nodes[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='access_control_nodes[$x][uuid]' value='".escape($row['access_control_node_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>".escape($row['node_type'])."&nbsp;</td>\n";
			echo "	<td>\n";
			if (permission_exists('access_control_node_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['node_cidr'])."</a>\n";
			}
			else {
				echo "	".escape($row['node_cidr']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['node_domain'])."&nbsp;</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['node_description'])."&nbsp;</td>\n";
			if (permission_exists('access_control_node_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($access_control_nodes);
	}

	echo "</table>\n";
	echo "<br />\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//make sub action bar sticky
	echo "<script>\n";

	echo "	window.addEventListener('scroll', function(){\n";
	echo "		action_bar_scroll('action_bar_sub', 270, heading_modify, heading_restore);\n";
	echo "	}, false);\n";

	echo "	function heading_modify() {\n";
	echo "		document.getElementById('heading_sub').innerHTML = \"".$text['title-access_control'].' '.$text['title-access_control_nodes']." (".$num_rows.")\";\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'inline-block';\n";
	echo "	}\n";

	echo "	function heading_restore() {\n";
	echo "		document.getElementById('heading_sub').innerHTML = \"".$text['title-access_control_nodes']." (".$num_rows.")\";\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'none';\n";
	echo "	}\n";

	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>