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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J. Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('ivr_menu_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(ivr_menu_name) like :search ";
		$sql_search .= "or lower(ivr_menu_extension) like :search ";
		//$sql_search .= "or lower(ivr_menu_greet_long) like :search ";
		//$sql_search .= "or lower(ivr_menu_greet_short) like :search ";
		//$sql_search .= "or lower(ivr_menu_invalid_sound) like :search ";
		//$sql_search .= "or lower(ivr_menu_exit_sound) like :search ";
		//$sql_search .= "or lower(ivr_menu_confirm_macro) like :search ";
		//$sql_search .= "or lower(ivr_menu_confirm_key) like :search ";
		//$sql_search .= "or lower(ivr_menu_tts_engine) like :search ";
		//$sql_search .= "or lower(ivr_menu_tts_voice) like :search ";
		//$sql_search .= "or lower(ivr_menu_confirm_attempts) like '%".$search."%'" ;
		//$sql_search .= "or lower(ivr_menu_timeout) like :search ";
		//$sql_search .= "or lower(ivr_menu_exit_app) like :search ";
		//$sql_search .= "or lower(ivr_menu_exit_data) like :search ";
		//$sql_search .= "or lower(ivr_menu_inter_digit_timeout) like :search ";
		//$sql_search .= "or lower(ivr_menu_max_failures) like :search ";
		//$sql_search .= "or lower(ivr_menu_max_timeouts) like :search ";
		//$sql_search .= "or lower(ivr_menu_digit_len) like :search ";
		//$sql_search .= "or lower(ivr_menu_direct_dial) like :search ";
		//$sql_search .= "or lower(ivr_menu_ringback) like :search ";
		//$sql_search .= "or lower(ivr_menu_cid_prefix) like :search ";
		$sql_search .= "or lower(ivr_menu_enabled) like :search ";
		$sql_search .= "or lower(ivr_menu_description) like :search ";
		$sql_search .= ")";
		$parameters['search'] = '%'.$search.'%';
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(*) from v_ivr_menus ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = escape($_GET['page']);
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-ivr_menus']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-ivr_menu']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo th_order_by('ivr_menu_uuid', $text['label-ivr_menu_uuid'], $order_by, $order);
	//echo th_order_by('dialplan_uuid', $text['label-dialplan_uuid'], $order_by, $order);
	echo th_order_by('ivr_menu_name', $text['label-name'], $order_by, $order);
	echo th_order_by('ivr_menu_extension', $text['label-extension'], $order_by, $order);
	//echo th_order_by('ivr_menu_greet_long', $text['label-ivr_menu_greet_long'], $order_by, $order);
	//echo th_order_by('ivr_menu_greet_short', $text['label-ivr_menu_greet_short'], $order_by, $order);
	//echo th_order_by('ivr_menu_invalid_sound', $text['label-ivr_menu_invalid_sound'], $order_by, $order);
	//echo th_order_by('ivr_menu_exit_sound', $text['label-ivr_menu_exit_sound'], $order_by, $order);
	//echo th_order_by('ivr_menu_confirm_macro', $text['label-ivr_menu_confirm_macro'], $order_by, $order);
	//echo th_order_by('ivr_menu_confirm_key', $text['label-ivr_menu_confirm_key'], $order_by, $order);
	//echo th_order_by('ivr_menu_tts_engine', $text['label-ivr_menu_tts_engine'], $order_by, $order);
	//echo th_order_by('ivr_menu_tts_voice', $text['label-ivr_menu_tts_voice'], $order_by, $order);
	//echo th_order_by('ivr_menu_confirm_attempts', $text['label-ivr_menu_confirm_attempts'], $order_by, $order);
	//echo th_order_by('ivr_menu_timeout', $text['label-ivr_menu_timeout'], $order_by, $order);
	//echo th_order_by('ivr_menu_exit_app', $text['label-ivr_menu_exit_app'], $order_by, $order);
	//echo th_order_by('ivr_menu_exit_data', $text['label-ivr_menu_exit_data'], $order_by, $order);
	//echo th_order_by('ivr_menu_inter_digit_timeout', $text['label-ivr_menu_inter_digit_timeout'], $order_by, $order);
	//echo th_order_by('ivr_menu_max_failures', $text['label-ivr_menu_max_failures'], $order_by, $order);
	//echo th_order_by('ivr_menu_max_timeouts', $text['label-ivr_menu_max_timeouts'], $order_by, $order);
	//echo th_order_by('ivr_menu_digit_len', $text['label-ivr_menu_digit_len'], $order_by, $order);
	//echo th_order_by('ivr_menu_direct_dial', $text['label-ivr_menu_direct_dial'], $order_by, $order);
	//echo th_order_by('ivr_menu_ringback', $text['label-ivr_menu_ringback'], $order_by, $order);
	//echo th_order_by('ivr_menu_cid_prefix', $text['label-ivr_menu_cid_prefix'], $order_by, $order);
	echo th_order_by('ivr_menu_enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('ivr_menu_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('ivr_menu_add')) {
		if ($_SESSION['limit']['ivr_menus']['numeric'] == '' || ($_SESSION['limit']['ivr_menus']['numeric'] != '' && $total_ivr_menus < $_SESSION['limit']['ivr_menus']['numeric'])) {
			echo "<a href='ivr_menu_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
		}
	}
	else {
		echo "&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if (is_array($result)) {
		foreach($result as $row) {
			if (permission_exists('ivr_menu_edit')) {
				$tr_link = "href='ivr_menu_edit.php?id=".escape($row['ivr_menu_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_uuid'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['dialplan_uuid'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_extension'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_greet_long'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_greet_short'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_invalid_sound'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_exit_sound'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_confirm_macro'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_confirm_key'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_tts_engine'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_tts_voice'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_confirm_attempts'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_timeout'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_exit_app'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_exit_data'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_inter_digit_timeout'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_max_failures'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_max_timeouts'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_digit_len'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_direct_dial'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_ringback'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_cid_prefix'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_enabled'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ivr_menu_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('ivr_menu_edit')) {
				echo "<a href='ivr_menu_edit.php?id=".escape($row['ivr_menu_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('ivr_menu_delete')) {
				echo "<a href='ivr_menu_delete.php?id=".escape($row['ivr_menu_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
	}
	unset($result, $row);

	echo "<tr>\n";
	echo "<td colspan='27' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('ivr_menu_add')) {
		if ($_SESSION['limit']['ivr_menus']['numeric'] == '' || ($_SESSION['limit']['ivr_menus']['numeric'] != '' && $total_ivr_menus < $_SESSION['limit']['ivr_menus']['numeric'])) {
			echo 		"<a href='ivr_menu_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
		}
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
