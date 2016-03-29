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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
	$dialplan_uuid = check_str($_REQUEST['id']);
	$dialplan_enabled = check_str($_REQUEST['enabled']);
	if ($dialplan_uuid != '' && $dialplan_enabled != '') {
		$sql = "update v_dialplans set ";
		$sql .= "dialplan_enabled = '".$dialplan_enabled."' ";
		$sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
		$sql .= "and app_uuid = '4b821450-926b-175a-af93-a03c441818b1' ";
		$db->exec(check_sql($sql));
		unset($sql);
		$_SESSION["message"] = $text['message-update'];
	}

//set the http values as php variables
	$search = check_str($_REQUEST["search"]);
	$order_by = check_str($_REQUEST["order_by"]);
	$order = check_str($_REQUEST["order"]);
	$dialplan_context = check_str($_REQUEST["dialplan_context"]);
	$app_uuid = check_str($_REQUEST["app_uuid"]);

//includes
	require_once "resources/header.php";
	require_once "resources/paging.php";
	$document['title'] = $text['title-time_conditions'];

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left' valign='top'>\n";
	echo "		<span class='title'>\n";
	echo "			".$text['header-time_conditions']."\n";
	echo "		</span>\n";
	echo "		<br><br>\n";
	echo "		<span class='vexpl'>\n";
	echo $text['description-time_conditions'];
	echo "		</span>\n";
	echo "	</td>\n";

	echo "	<td align='right' valign='top' nowrap='nowrap' style='padding-left: 50px;'>\n";
	echo "		<form name='frm_search' method='get' action=''>\n";
	echo "		<input type='text' class='txt' style='width: 150px' name='search' value='".$search."'>";
	if (strlen($app_uuid) > 0) {
		echo "		<input type='hidden' class='txt' name='app_uuid' value='".$app_uuid."'>";
	}
	if (strlen($order_by) > 0) {
		echo "		<input type='hidden' class='txt' name='order_by' value='".$order_by."'>";
		echo "		<input type='hidden' class='txt' name='order' value='".$order."'>";
	}
	echo "		<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "		</form>\n";
	echo "	</td>\n";

	echo "</tr>\n";
	echo "</table>";
	echo "<br />";

	//get the number of rows in the dialplan
	$sql = "select count(*) as num_rows from v_dialplans ";
	$sql .= "where (domain_uuid = '$domain_uuid' or domain_uuid is null) ";
	$sql .= "and app_uuid = '4b821450-926b-175a-af93-a03c441818b1' ";
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= " 	dialplan_context like '%".$search."%' ";
		$sql .= " 	or dialplan_name like '%".$search."%' ";
		$sql .= " 	or dialplan_number like '%".$search."%' ";
		$sql .= " 	or dialplan_continue like '%".$search."%' ";
		if (is_numeric($search)) {
			$sql .= " 	or dialplan_order = '".$search."' ";
		}
		$sql .= " 	or dialplan_enabled like '%".$search."%' ";
		$sql .= " 	or dialplan_description like '%".$search."%' ";
		$sql .= ") ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = '0';
		}
	}
	unset($prep_statement, $result);

	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	if (strlen($app_uuid) > 0) { $param = "&app_uuid=".$app_uuid; }
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$sql = "select * from v_dialplans ";
	$sql .= "where (domain_uuid = '$domain_uuid' or domain_uuid is null) ";
	$sql .= "and app_uuid = '4b821450-926b-175a-af93-a03c441818b1' ";
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= " 	dialplan_context like '%".$search."%' ";
		$sql .= " 	or dialplan_name like '%".$search."%' ";
		$sql .= " 	or dialplan_number like '%".$search."%' ";
		$sql .= " 	or dialplan_continue like '%".$search."%' ";
		if (is_numeric($search)) {
			$sql .= " 	or dialplan_order = '".$search."' ";
		}
		$sql .= " 	or dialplan_enabled like '%".$search."%' ";
		$sql .= " 	or dialplan_description like '%".$search."%' ";
		$sql .= ") ";
	}
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; } else { $sql .= "order by dialplan_order asc, dialplan_name asc "; }
	$sql .= " limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<form name='frm_delete' method='post' action='time_condition_delete.php'>\n";
	echo "<input type='hidden' name='app_uuid' value='".$app_uuid."'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (permission_exists('dialplan_delete') && $result_count > 0) {
		echo "<th style='text-align: center;' style='text-align: center; padding: 3px 0px 0px 0px;' width='1'><input type='checkbox' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	echo th_order_by('dialplan_name', $text['label-name'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
	echo th_order_by('dialplan_number', $text['label-number'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
	echo th_order_by('dialplan_context', $text['label-context'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
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

	if ($result_count > 0) {
		foreach($result as $row) {
			$app_uuid = $row['app_uuid'];

			$tr_link = "href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".$row['dialplan_uuid'].(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."'";

			echo "<tr ".$tr_link.">\n";
			if (permission_exists("time_condition_delete")) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; padding: 3px 0px 0px 0px;'><input type='checkbox' name='id[]' id='checkbox_".$row['dialplan_uuid']."' value='".$row['dialplan_uuid']."'></td>\n";
				$dialplan_ids[] = 'checkbox_'.$row['dialplan_uuid'];
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('time_condition_edit')) {
				echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".$row['dialplan_uuid'].(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."'>".$row['dialplan_name']."</a>";
			}
			else {
				echo $row['dialplan_name'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".((strlen($row['dialplan_number']) > 0) ? $row['dialplan_number'] : "&nbsp;")."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['dialplan_context']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center;'>".$row['dialplan_order']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center;'>";
			echo "		<a href='?id=".$row['dialplan_uuid']."&enabled=".(($row['dialplan_enabled'] == 'true') ? 'false' : 'true').(($app_uuid != '') ? "&app_uuid=".$app_uuid : null).(($search != '') ? "&search=".$search : null).(($order_by != '') ? "&order_by=".$order_by."&order=".$order : null)."'>".ucwords($row['dialplan_enabled'])."</a>\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".((strlen($row['dialplan_description']) > 0) ? $row['dialplan_description'] : "&nbsp;")."</td>\n";
			echo "	<td class='list_control_icons'>\n";
 			if (permission_exists('time_condition_edit')) {
 				echo "<a href='".PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".$row['dialplan_uuid'].(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
 			}
			if (permission_exists('time_condition_delete')) {
				echo "<a href=\"time_condition_delete.php?id[]=".$row['dialplan_uuid'].(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."\" alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

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