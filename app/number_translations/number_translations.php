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
	Matthew Vale <github@mafoo.org>
*/

require_once "root.php";
require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (!permission_exists('number_translation_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//handle enable toggle
	$number_translation_uuid = check_str($_REQUEST['id']);
	$number_translation_enabled = check_str($_REQUEST['enabled']);
	if ($number_translation_uuid != '' && $number_translation_enabled != '') {
		$array['number_translations'][0]['number_translation_uuid'] = $number_translation_uuid;
		$array['number_translations'][0]['number_translation_enabled'] = $number_translation_enabled;
		$database = new database;
		$database->app_name = 'number_translations';
		$database->save($array);
		$number_translation = new number_translation;
		$number_translation->xml();
		messages::add($text['message-update']);
		unset($array, $number_translation);
	}

//set the http values as php variables
	if (isset($_REQUEST["search"])) { $search = check_str($_REQUEST["search"]); } else { $search = null; }
	if (isset($_REQUEST["order_by"])) { $order_by = check_str($_REQUEST["order_by"]); } else { $order_by = null; }
	if (isset($_REQUEST["order"])) { $order = check_str($_REQUEST["order"]); } else { $order = null; }

//includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get the number of rows in the number_translation
	$sql = "select count(*) as num_rows from v_number_translations ";
	$sql .= "where true ";
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= " 	number_translation_name like '%".$search."%' ";
		$sql .= " 	or number_translation_description like '%".$search."%' ";
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

//get the list of number_translations
	$sql = "select * from v_number_translations ";
	$sql .= "where true ";
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= " 	number_translation_name like '%".$search."%' ";
		$sql .= " 	or number_translation_description like '%".$search."%' ";
		$sql .= ") ";
	}
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; } else { $sql .= "order by number_translation_name asc "; }
	$sql .= " limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$number_translations = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($number_translations);
	unset ($prep_statement, $sql);

//set the alternating row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//set the title
	$document['title'] = $text['title-number_translation'];

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left' valign='top'>\n";
	echo "		<span class='title'>\n";
	echo "			".$text['header-number_translation']."\n";
	echo "		</span>\n";
	echo "		<br><br>\n";
	echo "	</td>\n";
	echo "	<td align='right' valign='top' nowrap='nowrap' style='padding-left: 50px;'>\n";
	echo "		<form name='frm_search' method='get' action=''>\n";
	echo "		<input type='text' class='txt' style='width: 150px' name='search' value='".$search."'>";
	if (strlen($order_by) > 0) {
		echo "		<input type='hidden' class='txt' name='order_by' value='".$order_by."'>";
		echo "		<input type='hidden' class='txt' name='order' value='".$order."'>";
	}
	echo "		<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "  	<input type='button' class='btn' value='".$text['button-reload_xml']."' onclick=\"document.location.href='cmd.php?cmd=api+reloadxml';\" />\n";
	echo "  	<input type='button' class='btn' value='".$text['button-reload']." mod_translate' onclick=\"document.location.href='cmd.php?cmd=api+reload+mod_translate';\" />\n";
	echo "		</form>\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td colspan='2'>\n";
	echo "		<span class='vexpl'>\n";
	echo "			" . $text['description-number_translation'] . "\n";
	echo "		</span>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "<br />";

	echo "<form name='frm_delete' method='post' action='number_translation_delete.php'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (permission_exists('number_translation_delete') && $result_count > 0) {
		echo "<th style='text-align: center; padding: 3px 0px 0px 0px;' width='1'><input type='checkbox' style='margin: 0px 0px 0px 2px;' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	echo th_order_by('number_translation_name', $text['label-name'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
	echo th_order_by('number_translation_enabled', $text['label-enabled'], $order_by, $order, $app_uuid, "style='text-align: center;'", (($search != '') ? "search=".$search : null));
	echo th_order_by('number_translation_description', $text['label-description'], $order_by, $order, $app_uuid, null, (($search != '') ? "search=".$search : null));
	echo "<td class='list_control_icons'>";
	if (permission_exists('number_translation_delete') && $result_count > 0) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm_delete.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($number_translations as $row) {
			$tr_link = "href='number_translation_edit.php?id=".$row['number_translation_uuid']."'";
			echo "<tr ".$tr_link.">\n";
			if (permission_exists("number_translation_delete")) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; padding: 3px 0px 0px 0px;'><input type='checkbox' name='id[]' id='checkbox_".$row['number_translation_uuid']."' value='".$row['number_translation_uuid']."'></td>\n";
				$number_translation_ids[] = 'checkbox_'.$row['number_translation_uuid'];
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
				echo $row['number_translation_name'];
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center;'>";
			echo "		<a href='?id=".$row['number_translation_uuid']."&enabled=".(($row['number_translation_enabled'] == 'true') ? 'false' : 'true').(($app_uuid != '') ? "&app_uuid=".$app_uuid : null).(($search != '') ? "&search=".$search : null).(($order_by != '') ? "&order_by=".$order_by."&order=".$order : null)."'>".$text['label-'.$row['number_translation_enabled']]."</a>\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".((strlen($row['number_translation_description']) > 0) ? $row['number_translation_description'] : "&nbsp;")."</td>\n";
			echo "	<td class='list_control_icons'>\n";
			if (permission_exists('number_translation_edit')) {
				echo "		<a href='number_translation_edit.php?id=".$row['number_translation_uuid'].(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('number_translation_delete')) {
				echo "		<a href=\"number_translation_delete.php?id[]=".$row['number_translation_uuid'].(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."\" alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
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
	if (permission_exists('number_translation_edit')) {
		echo "			<a href='number_translation_add.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	if (permission_exists('number_translation_delete') && $result_count > 0) {
		echo "			<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm_delete.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	if (sizeof($number_translation_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		foreach ($number_translation_ids as $checkbox_id) {
			echo "document.getElementById('".$checkbox_id."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

//unset the variables
	unset ($result_count);
	unset ($result);
	unset ($key);
	unset ($val);
	unset ($c);

?>