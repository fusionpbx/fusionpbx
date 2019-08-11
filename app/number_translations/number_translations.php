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
	Portions created by the Initial Developer are Copyright (C) 2017-2018
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('number_translation_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the action
	if (is_array($_POST["number_translations"])) {
		$number_translations = $_POST["number_translations"];
		foreach($number_translations as $row) {
			if ($row['action'] == 'delete') {
				$action = 'delete';
				break;
			}
		}
	}

//delete the number_translations
	if (permission_exists('number_translation_delete')) {
		if ($action == "delete") {
			//download
				$obj = new number_translations;
				$obj->delete($number_translations);
			//delete message
				message::add($text['message-delete']);
		}
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = " (";
		$sql_search .= "   lower(number_translation_name) like :search ";
		$sql_search .= "   or lower(number_translation_enabled) like :search ";
		$sql_search .= "   or lower(number_translation_description) like :search ";
		$sql_search .= " ) ";
		$parameters['search'] = '%'.$search.'%';
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(*) from v_number_translations ";
	if (isset($sql_search)) {
		$sql .= "where ".$sql_search;
	}
	$database = new database;
	$num_rows = $database->select($sql, (is_array($parameters) ? $parameters : null), 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, (is_array($parameters) ? $parameters : null), 'all');
	unset($sql, $parameters);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//define the checkbox_toggle function
	echo "<script type=\"text/javascript\">\n";
	echo "	function checkbox_toggle(item) {\n";
	echo "		var inputs = document.getElementsByTagName(\"input\");\n";
	echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	echo "			if (inputs[i].type === 'checkbox') {\n";
	echo "				if (document.getElementById('checkbox_all').checked == true) {\n";
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
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-number_translations']."</b></td>\n";
	echo "		<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "			<form method='get' action=''>\n";
	if (permission_exists('number_translation_delete')) {
		echo "			<input type='button' class='btn' value='".$text['button-delete']."' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('frm').action = 'number_translation_delete.php'; document.getElementById('frm').submit(); }\">\n";
	}
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</form>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['title_description-number_translation']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<form method='post' id='frm' action='number_translation_delete.php'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th style='width:30px;'>\n";
	echo "		<input type='checkbox' name='checkbox_all' id='checkbox_all' value='' onclick=\"checkbox_toggle();\">\n";
	echo "	</th>\n";
	echo th_order_by('number_translation_name', $text['label-number_translation_name'], $order_by, $order);
	echo th_order_by('number_translation_enabled', $text['label-number_translation_enabled'], $order_by, $order);
	echo th_order_by('number_translation_description', $text['label-number_translation_description'], $order_by, $order);
	echo "	<td class='list_control_icons'>";
	if (permission_exists('number_translation_add')) {
		echo "		<a href='number_translation_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "	</td>\n";
	echo "<tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$x = 0;
		foreach($result as $row) {
			if (permission_exists('number_translation_edit')) {
				$tr_link = "href='number_translation_edit.php?id=".escape($row['number_translation_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='align: center; padding: 3px 3px 0px 8px;'>\n";
			echo "		<input type='checkbox' name='number_translations[]' id='checkbox_".$x."' value='".escape($row['number_translation_uuid'])."' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$x."').checked = false; }\">\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['number_translation_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['number_translation_enabled'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['number_translation_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('number_translation_edit')) {
				echo "<a href='number_translation_edit.php?id=".escape($row['number_translation_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('number_translation_delete')) {
				echo "<a href='number_translation_delete.php?number_translations[]=".escape($row['number_translation_uuid'])."' alt='".$text['button-delete']."' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('frm').submit(); } else { return false; }\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
			if ($c==0) { $c=1; } else { $c=0; }
		}
	}
	unset($result, $row);

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('number_translation_add')) {
		echo 		"<a href='number_translation_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
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
	echo "</form>\n";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>