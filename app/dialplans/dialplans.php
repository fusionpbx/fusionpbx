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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dialplan_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//handle enable toggle
	$dialplan_uuid = $_REQUEST['id'];
	$dialplan_enabled = $_REQUEST['enabled'];
	if (is_uuid($dialplan_uuid) && $dialplan_enabled != '') {
		//make sure enabled is only true or false
		if ($dialplan_enabled == "true") {
			$dialplan_enabled = 'true';	
		}
		else {
			$dialplan_enabled == 'false';
		}

		//get the dialplan context
		$sql = "select dialplan_context from v_dialplans ";
		$sql .= "where dialplan_uuid = :dialplan_uuid ";
		$parameters['dialplan_uuid'] = $dialplan_uuid;
		$database = new database;
		$dialplan_context = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);

		//change the status
		$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
		$array['dialplans'][0]['dialplan_enabled'] = $dialplan_enabled;

		$p = new permissions;
		$p->add('dialplan_edit', 'temp');

		$database = new database;
		$database->app_name = 'dialplans';
		$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
		$database->save($array);
		unset($array);

		$p->delete('dialplan_edit', 'temp');

		//clear the cache
		$cache = new cache;
		$cache->delete("dialplan:".$dialplan_context);

		//set the message
		message::add($text['message-update']);
	}

//set the http values as php variables
	$search = $_REQUEST["search"];
	$order_by = $_REQUEST["order_by"];
	$order = $_REQUEST["order"];
	$dialplan_context = $_REQUEST["dialplan_context"];
	$app_uuid = $_REQUEST["app_uuid"];

//make sure all dialplans with context of public have the inbound route app_uuid
	if ($app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
		$sql = "update v_dialplans set ";
		$sql .= "app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
		$sql .= "where dialplan_context = 'public' ";
		$sql .= "and app_uuid is null; ";
		$database = new database;
		$database->execute($sql);
		unset($sql);
	}

//includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//common sql where
	if ($_GET['show'] == "all" && permission_exists('dialplan_all')) {
		$sql_where = "where true ";
	}
	else {
		$sql_where .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!is_uuid($app_uuid)) {
		//hide inbound routes
			$sql_where .= "and app_uuid <> 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
			$sql_where .= "and dialplan_context <> 'public' ";
		//hide outbound routes
			$sql_where .= "and app_uuid <> '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	}
	else {
		if ($app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
			$sql_where .= "and (app_uuid = :app_uuid or dialplan_context = 'public') ";
		}
		else {
			$sql_where .= "and app_uuid = :app_uuid ";
		}
		$parameters['app_uuid'] = $app_uuid;
	}
	if (strlen($search) > 0) {
		$sql_where .= "and (";
		$sql_where .= " 	dialplan_context like :search ";
		$sql_where .= " 	or dialplan_name like :search ";
		$sql_where .= " 	or dialplan_number like :search ";
		$sql_where .= " 	or dialplan_continue like :search ";
		if (is_numeric($search)) {
			$sql_where .= " 	or dialplan_order = :search ";
		}
		$sql_where .= " 	or dialplan_enabled like :search ";
		$sql_where .= " 	or dialplan_description like :search ";
		$sql_where .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the number of rows in the dialplan
	$sql = "select count(*) from v_dialplans ";
	$sql .= $sql_where;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".escape($search);
	if ($_GET['show'] == "all" && permission_exists('destination_all')) {
		$param .= "&show=all";
	}
	if (strlen($app_uuid) > 0 && is_uuid($app_uuid)) { $param = "&app_uuid=".$app_uuid; }
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list of dialplans
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= ($order_by != '' ? order_by($order_by, $order) : 'order by dialplan_order asc, dialplan_name asc ');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$dialplans = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the alternating row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//set the title
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4") {
		$document['title'] = $text['title-inbound_routes'];
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
		$document['title'] = $text['title-outbound_routes'];
	}
	elseif ($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7") {
		$document['title'] = $text['title-queues'];
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
		$document['title'] = $text['title-time_conditions'];
	}
	else {
		$document['title'] = $text['title-dialplan_manager'];
	}

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left' valign='top'>\n";
	echo "		<span class='title'>\n";
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4") {
		echo "			".$text['header-inbound_routes']."\n";
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
		echo "			".$text['header-outbound_routes']."\n";
	}
	elseif ($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7") {
		echo "			".$text['header-queues']."\n";
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
		echo "			".$text['header-time_conditions']."\n";
	}
	else {
		echo "			".$text['header-dialplan_manager']."\n";
	}
	echo "		</span>\n";
	echo "		<br><br>\n";
	echo "	</td>\n";
	echo "	<td align='right' valign='top' nowrap='nowrap' style='padding-left: 50px;'>\n";
	echo "		<form name='frm_search' method='get' action=''>\n";
	if (permission_exists('dialplan_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='dialplans.php?show=all&search=".escape($search)."&app_uuid=".escape($app_uuid)."';\">\n";
		}
	}
	echo "		<input type='text' class='txt' style='width: 150px' name='search' value='".escape($search)."'>";
	if (is_uuid($app_uuid)) {
		echo "		<input type='hidden' class='txt' name='app_uuid' value='".escape($app_uuid)."'>";
	}
	if (strlen($order_by) > 0) {
		echo "		<input type='hidden' class='txt' name='order_by' value='".escape($order_by)."'>";
		echo "		<input type='hidden' class='txt' name='order' value='".escape($order)."'>";
	}
	echo "		<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "		</form>\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td colspan='2'>\n";
	echo "		<span class='vexpl'>\n";
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4") {
		echo $text['description-inbound_routes'];
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
		echo $text['description-outbound_routes'];
	}
	elseif ($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7") {
		echo $text['description-queues'];
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
		echo $text['description-time_conditions'];
	}
	else {
		if (if_group("superadmin")) {
			echo $text['description-dialplan_manager-superadmin'];
		}
		else {
			echo $text['description-dialplan_manager'];
		}
	}
	echo "		</span>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "<br />";

	echo "<form name='frm_delete' method='post' action='dialplan_delete.php'>\n";
	echo "<input type='hidden' name='app_uuid' value='".escape($app_uuid)."'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (permission_exists('dialplan_delete') && @sizeof($dialplans) != 0) {
		echo "<th style='width: 30px; text-align: center; padding: 3px 0px 0px 0px;' width='1'><input type='checkbox' style='margin: 0px 0px 0px 2px;' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	if ($_GET['show'] == "all" && permission_exists('destination_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('dialplan_name', $text['label-name'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".escape($search) : null));
	echo th_order_by('dialplan_number', $text['label-number'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".escape($search) : null));
	if (permission_exists('dialplan_context')) {
		echo th_order_by('dialplan_context', $text['label-context'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".escape($search) : null));
	}
	echo th_order_by('dialplan_order', $text['label-order'], $order_by, $order, $app_uuid, "style='text-align: center;'", (($search != '') ? "search=".escape($search) : null));
	echo th_order_by('dialplan_enabled', $text['label-enabled'], $order_by, $order, $app_uuid, "style='text-align: center;'", (($search != '') ? "search=".escape($search) : null));
	echo th_order_by('dialplan_description', $text['label-description'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".escape($search) : null));
	echo "<td class='list_control_icons'>";
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_add')) {
		echo "<a href='".PROJECT_PATH."/app/dialplan_inbound/dialplan_inbound_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_add')) {
		echo "<a href='".PROJECT_PATH."/app/dialplan_outbound/dialplan_outbound_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	elseif ($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_add')) {
		echo "<a href='".PROJECT_PATH."/app/fifo/fifo_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_add')) {
		echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	elseif (permission_exists('dialplan_add')) {
		echo "<a href='".PROJECT_PATH."/app/dialplans/dialplan_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	if (permission_exists('dialplan_delete') && @sizeof($dialplans) != 0) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm_delete.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($dialplans) && @sizeof($dialplans) != 0) {
		foreach($dialplans as $row) {

			//get the application id
			$app_uuid = $row['app_uuid'];

			// blank app id if doesn't match others, so will return to dialplan manager
			switch ($app_uuid) {
				case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" : // inbound route
				case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" : // outbound route
				case "16589224-c876-aeb3-f59f-523a1c0801f7" : // fifo
				case "4b821450-926b-175a-af93-a03c441818b1" : // time condition
					break;
				default :
					unset($app_uuid);
			}

			if ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) {
				$tr_link = "href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."'";
			}
			elseif (
				($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				permission_exists('dialplan_edit')
				) {
				$tr_link = "href='dialplan_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."'";
			}
			echo "<tr ".$tr_link.">\n";
			if (permission_exists("dialplan_delete")) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; padding: 3px 0px 0px 0px;'><input type='checkbox' name='id[]' id='checkbox_".escape($row['dialplan_uuid'])."' value='".escape($row['dialplan_uuid'])."'></td>\n";
				$dialplan_ids[] = 'checkbox_'.escape($row['dialplan_uuid']);
			}
			if ($_GET['show'] == "all" && permission_exists('dialplan_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = escape($_SESSION['domains'][$row['domain_uuid']]['domain_name']);
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($domain)."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) {
				echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."'>".escape($row['dialplan_name'])."</a>";
			}
			elseif (
				($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				permission_exists('dialplan_edit')
				) {
				echo "<a href='dialplan_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."'>".escape($row['dialplan_name'])."</a>";
			}
			else {
				echo escape($row['dialplan_name']);
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".((strlen($row['dialplan_number']) > 0) ? escape(format_phone($row['dialplan_number'])) : "&nbsp;")."</td>\n";
			if (permission_exists('dialplan_context')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['dialplan_context'])."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center;'>".escape($row['dialplan_order'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center;'>";
			echo "		<a href='?id=".escape($row['dialplan_uuid'])."&enabled=".(($row['dialplan_enabled'] == 'true') ? 'false' : 'true').(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null).(($search != '') ? "&search=".escape($search) : null).(($order_by != '') ? "&order_by=".escape($order_by)."&order=".escape($order) : null)."'>".$text['label-'.$row['dialplan_enabled']]."</a>\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".((strlen($row['dialplan_description']) > 0) ? escape($row['dialplan_description']) : "&nbsp;")."</td>\n";
			echo "	<td class='list_control_icons'>\n";
 			if ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) {
 				echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
 			}
			elseif (
				($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				permission_exists('dialplan_edit')
				) {
					echo "<a href='dialplan_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (
				($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_delete')) ||
				($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_delete')) ||
				($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_delete')) ||
				($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_delete')) ||
				permission_exists('dialplan_delete')
				) {
					echo "<a href=\"dialplan_delete.php?id[]=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."\" alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = $c == 0 ? 1 : 0;
		}
	}
	unset($dialplans, $row);

	echo "<tr>\n";
	echo "<td colspan='9'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%'>&nbsp;</td>\n";
	echo "		<td width='33.3%'>&nbsp;</td>\n";
	echo "		<td class='list_control_icons'>";
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_add')) {
		echo "<a href='".PROJECT_PATH."/app/dialplan_inbound/dialplan_inbound_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_add')) {
		echo "<a href='".PROJECT_PATH."/app/dialplan_outbound/dialplan_outbound_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	elseif ($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_add')) {
		echo "<a href='".PROJECT_PATH."/app/fifo/fifo_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_add')) {
		echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	elseif (permission_exists('dialplan_add')) {
		echo "<a href='".PROJECT_PATH."/app/dialplans/dialplan_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	if (permission_exists('dialplan_delete') && @sizeof($dialplans) != 0) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm_delete.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "</form>";

	if (strlen($paging_controls) > 0) {
		echo "<br />";
		echo $paging_controls."\n";
	}
	echo "<br><br>";

	if (sizeof($dialplan_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		foreach ($dialplan_ids as $checkbox_id) {
			echo "document.getElementById('".escape($checkbox_id)."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>
