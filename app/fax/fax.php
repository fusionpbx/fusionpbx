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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//get the fax extensions
	if (if_group("superadmin") || if_group("admin")) {
		//show all fax extensions
		$sql = "select count(*) as num_rows from v_fax ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	}
	else {
		//show only assigned fax extensions
		$sql = "select count(*) as num_rows from v_fax as f, v_fax_users as u ";
		$sql .= "where f.fax_uuid = u.fax_uuid ";
		$sql .= "and f.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
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
	$page = check_str($_GET['page']);
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	if (if_group("superadmin") || if_group("admin")) {
		//show all fax extensions
		$sql = "select * from v_fax ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		if (strlen($order_by) == 0) { $sql .= "order by fax_name asc "; }
	}
	else {
		//show only assigned fax extensions
		$sql = "select * from v_fax as f, v_fax_users as u ";
		$sql .= "where f.fax_uuid = u.fax_uuid ";
		$sql .= "and f.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
		if (strlen($order_by) == 0) { $sql .= "order by f.fax_name asc "; }
	}
	if (strlen($order_by) > 0) {
		$sql .= "order by $order_by $order ";
	}
	$sql .= "limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	unset ($prep_statement, $sql);

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

	if ($num_rows > 0) {
		foreach($result as $row) {
			//remove the backslash
				$row['fax_email'] = str_replace("\\", "", $row['fax_email']);
			//show the fax extensions
				$tr_link = (permission_exists('fax_extension_edit')) ? "href='fax_edit.php?id=".$row['fax_uuid']."'" : null;
				echo "<tr ".$tr_link.">\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				if (permission_exists('fax_extension_edit')) {
					echo "<a href='fax_edit.php?id=".$row['fax_uuid']."'>".$row['fax_name']."</a>";
				}
				else {
					echo $row['fax_name'];
				}
				echo "	</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_extension']."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_email']."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'>";
				if (permission_exists('fax_send')) {
					echo "		<a href='fax_send.php?id=".$row['fax_uuid']."'>".$text['label-new']."</a>&nbsp;&nbsp;";
				}
				if (permission_exists('fax_inbox_view')) {
					if ($row['fax_email_inbound_subject_tag'] != '') {
						$file = "fax_files_remote.php";
						$box = $row['fax_email_connection_mailbox'];
					}
					else {
						$file = "fax_files.php";
						$box = 'inbox';
					}
					echo "		<a href='".$file."?id=".$row['fax_uuid']."&box=".$box."'>".$text['label-inbox']."</a>&nbsp;&nbsp;";
				}
				if (permission_exists('fax_sent_view')) {
					echo "		<a href='fax_files.php?id=".$row['fax_uuid']."&box=sent'>".$text['label-sent']."</a>&nbsp;&nbsp;";
				}
				if (permission_exists('fax_log_view')) {
					echo "		<a href='fax_logs.php?id=".$row['fax_uuid']."'>".$text['label-log']."</a>";
				}
				if (permission_exists('fax_active_view')) {
					echo "		<a href='fax_active.php?id=".$row['fax_uuid']."'>".$text['label-active']."</a>";
				}
				echo "	</td>\n";
				echo "	<td valign='top' class='row_stylebg' width='35%'>".$row['fax_description']."&nbsp;</td>\n";
				echo "	<td class='list_control_icons'>";
				if (permission_exists('fax_extension_edit')) {
					echo "<a href='fax_edit.php?id=".$row['fax_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
				}
				if (permission_exists('fax_extension_delete')) {
					echo "<a href='fax_delete.php?id=".$row['fax_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
				}
				echo "	</td>\n";
				echo "</tr>\n";
			//alternate the CSS class
				if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='6'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
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