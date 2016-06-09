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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http get values and set them as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//check the permission
	if (!permission_exists('phrase_view')) {
		echo "access denied";
		exit;
	}

//add paging
	require_once "resources/paging.php";

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-phrases'];

//begin the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<b>".$text['header_phrases']."</b>\n";
	echo "			<br /><br />\n";
	echo "			".$text['description-phrases']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";
	echo "<br />\n";

	$sql = "select * from v_phrases ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$num_rows = count($result);
	unset ($prep_statement, $result, $sql);

	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$sql = "select * from v_phrases ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "order by ".((strlen($order_by) > 0) ? $order_by." ".$order." " : "phrase_name asc ");
	$sql .= "limit ".$rows_per_page." offset ".$offset." ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('phrase_name', $text['label-name'], $order_by, $order);
	echo th_order_by('phrase_language', $text['label-language'], $order_by, $order);
	echo th_order_by('phrase_enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('phrase_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('phrase_add')) {
		echo "<a href='phrase_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('phrase_edit')) ? "href='phrase_edit.php?id=".$row['phrase_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['phrase_name']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['phrase_language']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.$row['phrase_enabled']]."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['phrase_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('phrase_edit')) {
				echo "<a href='phrase_edit.php?id=".$row['phrase_uuid']."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
			}
			if (permission_exists('phrase_delete')) {
				echo "<a href='phrase_delete.php?id=".$row['phrase_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";

			$c = ($c==0) ? 1 : 0;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='4'>&nbsp;</td>\n";
	echo "<td class='list_control_icons'>";
	if (permission_exists('phrase_add')) {
		echo "<a href='phrase_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";

	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>".$paging_controls."</td>\n";
	echo "		<td class='list_control_icons'>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<br />\n";

//include the footer
	require_once "resources/footer.php";

?>