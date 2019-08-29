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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('time_condition_view')) {
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
		//build array
			$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][0]['dialplan_enabled'] = $dialplan_enabled;
			$array['dialplans'][0]['app_uuid'] = '4b821450-926b-175a-af93-a03c441818b1';
		//grant temporary permissions
			$p = new permissions;
			$p->add('dialplan_edit', 'temp');
		//execute update
			$database = new database;
			$database->app_name = 'time_conditions';
			$database->app_uuid = '4b821450-926b-175a-af93-a03c441818b1';
			$database->save($array);
			unset($array);
		//revoke temporary permissions
			$p->delete('dialplan_edit', 'temp');
		//set message
			message::add($text['message-update']);
		//redirect
			header('Location: time_conditions.php');
			exit;
	}

//set the http values as php variables
	$search = $_REQUEST["search"];
	$order_by = $_REQUEST["order_by"];
	$order = $_REQUEST["order"];
	$dialplan_context = $_REQUEST["dialplan_context"];
	$app_uuid = $_REQUEST["app_uuid"];

//includes
	require_once "resources/header.php";
	require_once "resources/paging.php";
	$document['title'] = $text['title-time_conditions'];

//set the alternating styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//get the number of rows in the dialplan
	$sql = "select count(*) from v_dialplans ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$sql .= "and app_uuid = '4b821450-926b-175a-af93-a03c441818b1' ";
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= " 	lower(dialplan_context) like :search ";
		$sql .= " 	or lower(dialplan_name) like :search ";
		$sql .= " 	or lower(dialplan_number) like :search ";
		$sql .= " 	or lower(dialplan_continue) like :search ";
		if (is_numeric($search)) {
			$sql .= " 	or dialplan_order = :search ";
		}
		$sql .= " 	or lower(dialplan_enabled) like :search ";
		$sql .= " 	or lower(dialplan_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.strtolower($search).'%';
	}
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page data
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	if (strlen($app_uuid) > 0) { $param = "&app_uuid=".$app_uuid; }
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the data
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= $order_by != '' ? order_by($order_by, $order) : " order by dialplan_order asc, dialplan_name asc ";
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$dialplans = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left' valign='top'>\n";
	echo "		<span class='title'>\n";
	echo "			".$text['header-time_conditions']."\n";
	echo "		</span>\n";
	echo "		<br><br>\n";
	echo "	</td>\n";
	echo "	<td align='right' valign='top' nowrap='nowrap' style='padding-left: 50px;'>\n";
	echo "		<form name='frm_search' method='get' action=''>\n";
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
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='2'>\n";
	echo "		<span class='vexpl'>\n";
	echo $text['description-time_conditions'];
	echo "		</span>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "<br />";

//show the content
	echo "<form name='frm_delete' method='post' action='time_condition_delete.php'>\n";
	echo "<input type='hidden' name='app_uuid' value='".escape($app_uuid)."'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (permission_exists('time_condition_delete') && is_array($dialplans)) {
		echo "<th style='text-align: center;' style='text-align: center; padding: 3px 0px 0px 0px;' width='1'><input type='checkbox' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	echo th_order_by('dialplan_name', $text['label-name'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
	echo th_order_by('dialplan_number', $text['label-number'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
	if (permission_exists('time_condition_context')) {
		echo th_order_by('dialplan_context', $text['label-context'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
	}
	echo th_order_by('dialplan_order', $text['label-order'], $order_by, $order, $app_uuid, "style='text-align: center;'", (($search != '') ? "search=".$search : null));
	echo th_order_by('dialplan_enabled', $text['label-enabled'], $order_by, $order, $app_uuid, "style='text-align: center;'", (($search != '') ? "search=".$search : null));
	echo th_order_by('dialplan_description', $text['label-description'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
	echo "<td class='list_control_icons'>";
	if (permission_exists('time_condition_add')) {
		echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	if (permission_exists('time_condition_delete') && $result_count > 0) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm_delete.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($dialplans)) {
		foreach($dialplans as $row) {
			$app_uuid = $row['app_uuid'];

			$tr_link = "href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."'";

			echo "<tr ".$tr_link.">\n";
			if (permission_exists("time_condition_delete")) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; padding: 3px 0px 0px 0px;'><input type='checkbox' name='id[]' id='checkbox_".escape($row['dialplan_uuid'])."' value='".$row['dialplan_uuid']."'></td>\n";
				$dialplan_ids[] = 'checkbox_'.escape($row['dialplan_uuid']);
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('time_condition_edit')) {
				echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."'>".escape($row['dialplan_name'])."</a>";
			}
			else {
				echo escape($row['dialplan_name']);
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".((strlen($row['dialplan_number']) > 0) ? $row['dialplan_number'] : "&nbsp;")."</td>\n";
			if (permission_exists('time_condition_context')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['dialplan_context'])."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center;'>".escape($row['dialplan_order'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center;'>";
			echo "		<a href='?id=".$row['dialplan_uuid']."&enabled=".(($row['dialplan_enabled'] == 'true') ? 'false' : 'true').(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null).(($search != '') ? "&search=".$search : null).(($order_by != '') ? "&order_by=".escape($order_by)."&order=".escape($order) : null)."'>".ucwords(escape($row['dialplan_enabled']))."</a>\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".((strlen($row['dialplan_description']) > 0) ? $row['dialplan_description'] : "&nbsp;")."</td>\n";
			echo "	<td class='list_control_icons'>\n";
 			if (permission_exists('time_condition_edit')) {
 				echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
 			}
			if (permission_exists('time_condition_delete')) {
				echo "<a href=\"time_condition_delete.php?id[]=".escape($row['dialplan_uuid']).(($app_uuid != '') ? "&app_uuid=".escape($app_uuid) : null)."\" alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = $c ? 0 : 1;
		}
	}
	unset($dialplans, $row);

	echo "<tr>\n";
	echo "<td colspan='8'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>".$paging_controls."</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('time_condition_add')) {
		echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	if (permission_exists('time_condition_delete') && $result_count > 0) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm_delete.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	if (sizeof($dialplan_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		foreach ($dialplan_ids as $checkbox_id) {
			echo "document.getElementById('".$checkbox_id."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>