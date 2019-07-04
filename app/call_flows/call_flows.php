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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('call_flow_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the page title
	$document['title'] = $text['title-call_flows'];

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(call_flow_name) like :search ";
		$sql_search .= "or lower(call_flow_extension) like :search ";
		$sql_search .= "or lower(call_flow_feature_code) like :search ";
		$sql_search .= "or lower(call_flow_context) like :search ";
		//$sql_search .= "or lower(call_flow_status) like :search ";
		$sql_search .= "or lower(call_flow_pin_number) like :search ";
		$sql_search .= "or lower(call_flow_label) like :search ";
		//$sql_search .= "or lower(call_flow_sound) like :search ";
		//$sql_search .= "or lower(call_flow_app) like :search ";
		//$sql_search .= "or lower(call_flow_data) like :search ";
		$sql_search .= "or lower(call_flow_alternate_label) like :search ";
		//$sql_search .= "or lower(call_flow_alternate_sound) like :search ";
		//$sql_search .= "or lower(call_flow_alternate_app) like :search ";
		//$sql_search .= "or lower(call_flow_alternate_data) like :search ";
		$sql_search .= "or lower(call_flow_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(call_flow_uuid) from v_call_flows ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_call_flows ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$call_flows = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-call_flows']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-call_flows']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('call_flow_status', $text['label-call_flow_status'], $order_by, $order);
	//echo th_order_by('call_flow_name', $text['label-call_flow_name'], $order_by, $order);
	echo th_order_by('call_flow_extension', $text['label-call_flow_extension'], $order_by, $order);
	echo th_order_by('call_flow_feature_code', $text['label-call_flow_feature_code'], $order_by, $order);
	//echo th_order_by('call_flow_context', $text['label-call_flow_context'], $order_by, $order);
	//echo th_order_by('call_flow_pin_number', $text['label-call_flow_pin_number'], $order_by, $order);
	//echo th_order_by('call_flow_label', $text['label-call_flow_label'], $order_by, $order);
	//echo th_order_by('call_flow_sound', $text['label-call_flow_sound'], $order_by, $order);
	//echo th_order_by('call_flow_app', $text['label-call_flow_app'], $order_by, $order);
	//echo th_order_by('call_flow_data', $text['label-call_flow_data'], $order_by, $order);
	//echo th_order_by('call_flow_alternate_label', $text['label-call_flow_alternate_label'], $order_by, $order);
	//echo th_order_by('call_flow_alternate_sound', $text['label-call_flow_alternate_sound'], $order_by, $order);
	//echo th_order_by('call_flow_alternate_app', $text['label-call_flow_alternate_app'], $order_by, $order);
	//echo th_order_by('call_flow_alternate_data', $text['label-call_flow_alternate_data'], $order_by, $order);
	if (permission_exists('call_flow_context')) {
		echo th_order_by('call_flow_context', $text['label-call_flow_context'], $order_by, $order);
	}
	echo th_order_by('call_flow_description', $text['label-call_flow_description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('call_flow_add')) {
		echo "<a href='call_flow_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if (is_array($call_flows)) {
		foreach($call_flows as $row) {
			if (permission_exists('call_flow_edit')) {
				$tr_link = "href='call_flow_edit.php?id=".$row['call_flow_uuid']."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if ($row['call_flow_status'] != "false") {
				echo escape($row['call_flow_label']);
			}
			else {
				echo escape($row['call_flow_alternate_label']);
			}
			echo 		"&nbsp;\n";
			echo "	</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_extension'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_feature_code'])."&nbsp;</td>\n";
			if (permission_exists('call_flow_context')) {
				echo "	<td valign='top' class='row_stylebg'>".escape($row['call_flow_context'])."&nbsp;</td>\n";
			}
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_pin_number'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_label'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_sound'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_app'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_data'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_alternate_label'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_alternate_sound'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_alternate_app'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_flow_alternate_data'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['call_flow_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('call_flow_edit')) {
				echo "<a href='call_flow_edit.php?id=".escape($row['call_flow_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('call_flow_delete')) {
				echo "<a href='call_flow_delete.php?id=".escape($row['call_flow_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($call_flows);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='19' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('call_flow_add')) {
		echo 		"<a href='call_flow_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo 		"&nbsp;";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
