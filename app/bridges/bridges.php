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

//get the action
	if (is_array($_POST["bridges"])) {
		$bridges = $_POST["bridges"];
		foreach($bridges as $row) {
			if ($row['action'] == 'delete') {
				$action = 'delete';
				break;
			}
		}
	}

//delete the bridges
	if (permission_exists('bridge_delete')) {
		if ($action == "delete") {
			//download
				$obj = new bridges;
				$obj->delete($bridges);
			//delete message
				message::add($text['message-delete']);
		}
	}

//get order and order by and sanatize the values
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//validate order by
	if (strlen($order_by) > 0) {
		$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', $order_by);
	}

//validate the order
	switch ($order) {
		case 'asc':
			break;
		case 'desc':
			break;
		default:
			$order = '';
	}

//add the parameters
	$parameters['domain_uuid'] = $domain_uuid;

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
	$sql = "select count(bridge_uuid) from v_bridges ";
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
	$sql = "select * from v_bridges ";
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
	}
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$bridges = $database->select($sql, $parameters, 'all');
	//$message = $database->message;
	//print_r($message);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//include the header
	require_once "resources/header.php";

//define the checkbox_toggle function
	echo "<script type=\"text/javascript\">\n";
	echo "	function checkbox_toggle(item) {\n";
	echo "		var inputs = document.getElementsByTagName(\"input\");\n";
	echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	echo "		    if (inputs[i].type === 'checkbox') {\n";
	echo "		       	if (document.getElementById('checkbox_all').checked == true) {\n";
	echo "				inputs[i].checked = true;\n";
	echo "			}\n";
	echo "				else {\n";
	echo "					inputs[i].checked = false;\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-bridges']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";

	if (permission_exists('bridge_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='bridges.php?show=all';\">\n";
		}
	}

	echo "				<input type='text' class='txt' style='width: 150px; margin-left: 15px;' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['title_description-bridge']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<form method='post' action=''>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th style='width:30px;'>\n";
	echo "		<input type='checkbox' name='checkbox_all' id='checkbox_all' value='' onclick=\"checkbox_toggle();\">\n";
	echo "	</th>\n";
	echo th_order_by('bridge_name', $text['label-bridge_name'], $order_by, $order);
	echo th_order_by('bridge_destination', $text['label-bridge_destination'], $order_by, $order);
	echo th_order_by('bridge_enabled', $text['label-bridge_enabled'], $order_by, $order);
	echo "	<td class='list_control_icons'>";
	if (permission_exists('bridge_add')) {
		echo "		<a href='bridge_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "	</td>\n";
	echo "<tr>\n";

	if (is_array($bridges)) {
		$x = 0;
		foreach($bridges as $row) {
			if (permission_exists('bridge_edit')) {
				$tr_link = "href='bridge_edit.php?id=".escape($row['bridge_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='align: center; padding: 3px 3px 0px 8px;'>\n";
			echo "		<input type='checkbox' name=\"bridges[$x][checked]\" id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$x."').checked = false; }\">\n";
			echo "		<input type='hidden' name=\"bridges[$x][bridge_uuid]\" value='".escape($row['bridge_uuid'])."' />\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['bridge_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['bridge_destination'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['bridge_enabled'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('bridge_edit')) {
				echo "<a href='bridge_edit.php?id=".escape($row['bridge_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('bridge_delete')) {
				echo "<button type='submit' class='btn btn-default list_control_icon' name=\"bridges[$x][action]\" alt='".$text['button-delete']."' value='delete'><span class='glyphicon glyphicon-remove'></span></button>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
			$c = $c == 1 ? 0 : 1;
		} //end foreach
		unset($sql, $bridges);
	} //end if results

	echo "<tr>\n";
	echo "</table>\n";
	if (permission_exists('bridge_add')) {
		echo "<div style='float: right;'>\n";
		echo "	<a href='bridge_edit.php' alt=\"".$text['button-add']."\">".$v_link_label_add."</a>";
		echo "</div>\n";
	}
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
