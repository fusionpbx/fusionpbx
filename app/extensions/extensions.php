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
if (permission_exists('extension_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$search = check_str($_GET["search"]);
	if (isset($_GET["order_by"])) {
		$order_by = check_str($_GET["order_by"]);
		$order = check_str($_GET["order"]);
	}

require_once "resources/header.php";
$document['title'] = $text['title-extensions'];

require_once "resources/paging.php";

//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left'><b>".$text['header-extensions']."</b><br>\n";
	echo "		".$text['description-extensions']."\n";
	echo "	</td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='30%' align='right'>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' value='".$search."'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "  </tr>\n";
	echo "</table>\n";
	echo "<br />";

//get total extension count from the database
	$sql = "select count(*) as num_rows from v_extensions where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_extensions = $row['num_rows'];
	}
	unset($prep_statement, $row);

//get the number of extensions (reuse $sql from above)
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= "	extension like '%".$search."%' ";
		$sql .= " 	or call_group like '%".$search."%' ";
		$sql .= " 	or user_context like '%".$search."%' ";
		$sql .= " 	or enabled like '%".$search."%' ";
		$sql .= " 	or description like '%".$search."%' ";
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

//prepare to page the results
	$rows_per_page = 150;
	$param = "";
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $_GET['page'];

//get the extensions
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= "	extension like '%".$search."%' ";
		$sql .= " 	or call_group like '%".$search."%' ";
		$sql .= " 	or user_context like '%".$search."%' ";
		$sql .= " 	or enabled like '%".$search."%' ";
		$sql .= " 	or description like '%".$search."%' ";
		$sql .= ") ";
	}
	if (isset($order_by)) {
		$sql .= "order by $order_by $order ";
	}
	else {
		$sql .= "order by extension asc ";
	}
	$sql .= " limit $rows_per_page offset $offset ";
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
	echo th_order_by('extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('call_group', $text['label-call_group'], $order_by, $order);
	//echo th_order_by('voicemail_mail_to', $text['label-voicemail_mail_to'], $order_by, $order);
	echo th_order_by('user_context', $text['label-user_context'], $order_by, $order);
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>\n";
	if (permission_exists('extension_add')) {
		if ($_SESSION['limit']['extensions']['numeric'] == '' || ($_SESSION['limit']['extensions']['numeric'] != '' && $total_extensions < $_SESSION['limit']['extensions']['numeric'])) {
			echo "	<a href='extension_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>\n";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('extension_edit')) ? " href='extension_edit.php?id=".$row['extension_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('extension_edit')) {
				echo "<a href='extension_edit.php?id=".$row['extension_uuid']."'>".$row['extension']."</a>";
			}
			else {
				echo $row['extension'];
			}
			echo "</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['call_group']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['voicemail_mail_to']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['user_context']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['enabled'])."</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".$row['description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('extension_edit')) {
				echo "<a href='extension_edit.php?id=".$row['extension_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('extension_delete')) {
				echo "<a href='extension_delete.php?id=".$row['extension_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='6' align='left'>\n";
	echo "	<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' class='list_control_icons'>\n";
	if (permission_exists('extension_add')) {
		if ($_SESSION['limit']['extensions']['numeric'] == '' || ($_SESSION['limit']['extensions']['numeric'] != '' && $total_extensions < $_SESSION['limit']['extensions']['numeric'])) {
			echo "			<a href='extension_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>\n";
		}
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";


//show the footer
	require_once "resources/footer.php";
?>