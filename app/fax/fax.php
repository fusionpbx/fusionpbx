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
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('fax_extension_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http get values and set them as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get record counts
	if (if_group("superadmin") || if_group("admin")) {
		//show all fax extensions
		$sql = "select count(*) from v_fax as f ";
		$sql .= "where f.domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	else {
		//show only assigned fax extensions
		$sql = "select count(*) from v_fax as f, v_fax_users as u ";
		$sql .= "where f.fax_uuid = u.fax_uuid ";
		$sql .= "and f.domain_uuid = :domain_uuid ";
		$sql .= "and u.user_uuid = :user_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['user_uuid'] = $_SESSION['user_uuid'];
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare paging
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = check_str($_GET['page']);
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get records
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'f.fax_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<span class=\"title\">".$text['title-fax']." (".$num_rows.")</span>";
	echo "			<br /><br />\n";
	echo "			".$text['description']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('fax_name', $text['label-name'], $order_by, $order);
	echo th_order_by('fax_extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('fax_email', $text['label-email'], $order_by, $order);
	echo "<th>".$text['label-tools']."</th>";
	echo th_order_by('fax_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>\n";
	if (permission_exists('fax_extension_add')) {
		echo "	<a href='fax_edit.php' alt='add'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		foreach($result as $row) {
			//remove the backslash
				$fax_email = str_replace("\\", "", $row['fax_email']);
				$fax_email = substr($fax_email, 0, 50);
			//show the fax extensions
				$tr_link = (permission_exists('fax_extension_edit')) ? "href='fax_edit.php?id=".escape($row['fax_uuid'])."'" : null;
				echo "<tr ".$tr_link.">\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				if (permission_exists('fax_extension_edit')) {
					echo "<a href='fax_edit.php?id=".escape($row['fax_uuid'])."'>".escape($row['fax_name'])."</a>";
				}
				else {
					echo escape($row['fax_name']);
				}
				echo "	</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['fax_extension'])."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($fax_email)."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'>";
				if (permission_exists('fax_send')) {
					echo "		<a href='fax_send.php?id=".escape($row['fax_uuid'])."'>".$text['label-new']."</a>&nbsp;&nbsp;";
				}
				if (permission_exists('fax_inbox_view')) {
					if ($row['fax_email_inbound_subject_tag'] != '') {
						$file = "fax_files_remote.php";
						$box = escape($row['fax_email_connection_mailbox']);
					}
					else {
						$file = "fax_files.php";
						$box = 'inbox';
					}
					echo "		<a href='".$file."?id=".escape($row['fax_uuid'])."&box=".$box."'>".$text['label-inbox']."</a>&nbsp;&nbsp;";
				}
				if (permission_exists('fax_sent_view')) {
					echo "		<a href='fax_files.php?id=".escape($row['fax_uuid'])."&box=sent'>".$text['label-sent']."</a>&nbsp;&nbsp;";
				}
				if (permission_exists('fax_log_view')) {
					echo "		<a href='fax_logs.php?id=".escape($row['fax_uuid'])."'>".$text['label-log']."</a>";
				}
				if (permission_exists('fax_active_view') && isset($_SESSION['fax']['send_mode']['text']) && $_SESSION['fax']['send_mode']['text'] == 'queue') {
					echo "		<a href='fax_active.php?id=".escape($row['fax_uuid'])."'>".$text['label-active']."</a>";
				}
				echo "	</td>\n";
				echo "	<td valign='top' class='row_stylebg' width='35%'>".escape($row['fax_description'])."&nbsp;</td>\n";
				echo "	<td class='list_control_icons'>";
				if (permission_exists('fax_extension_edit')) {
					echo "<a href='fax_edit.php?id=".escape($row['fax_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
				}
				if (permission_exists('fax_extension_delete')) {
					echo "<a href='fax_delete.php?id=".escape($row['fax_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
				}
				echo "	</td>\n";
				echo "</tr>\n";
			//alternate the CSS class
				if ($c==0) { $c=1; } else { $c=0; }
		}
	}
	unset($result, $row);

	echo "<tr>\n";
	echo "<td colspan='6'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('fax_extension_add')) {
		echo 		"<a href='fax_edit.php' alt='add'>$v_link_label_add</a>";
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
