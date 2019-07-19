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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists("user_view") || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_manager'];

//get variables used to control the order
	$order_by = $_GET["order_by"] != '' ? $_GET["order_by"] : 'u.username';
	$order = $_GET["order"];

//set the variables
	$search = $_REQUEST["search"];
	if (strlen($search) > 0) {
		$search = strtolower($search);
	}

//get the list of superadmins
	$superadmins = superadmin_list($db);

//common where clause
	$sql_where = "where true ";
	if (!(permission_exists('user_all') && $_GET['show'] == 'all')) {
		$sql_where .= "and u.domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (strlen($search) > 0) {
		$sql_where .= "and ( ";
		$sql_where .= "lower(username) like :search ";
		$sql_where .= "or lower(groups) like :search ";
		$sql_where .= "or lower(contact_organization) like :search ";
		$sql_where .= "or lower(contact_name_given) like :search ";
		$sql_where .= "or lower(contact_name_family) like :search ";
		$sql_where .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the user count from the database
	$sql = "select count(*) from view_users as u ";
	$sql .= $sql_where;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql);

//prepare for paging
	$rows_per_page = is_numeric($_SESSION['domain']['paging']['numeric']) ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "search=".escape($search);
	if (permission_exists('user_all') && $_GET['show'] == 'all') {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the users from the database
	$sql = "select u.domain_uuid, u.user_uuid, u.contact_uuid, u.domain_name, u.username, u.user_enabled, u.contact_organization, u.contact_name_given, u.contact_name_family, u.groups ";
	$sql .= "from view_users as u ";
	$sql .= $sql_where;
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $sql_where, $parameters);

//page title and description
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<form method='post' action=''>";
	echo "<tr>\n";
	echo "<td align='left' width='90%' nowrap='nowrap' valign='top'><b>".$text['header-user_manager']." (".$num_rows.")</b></td>\n";
	echo "<td align='right' nowrap='nowrap'>";
	if (permission_exists('user_all')) {
		if ($_GET['show'] == 'all') {
			echo "<input type='button' class='btn' value='".$text['button-back']."' onclick=\"window.location='users.php';\">\n";
			echo "<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='users.php?show=all';\">\n";
		}
	}
	if (permission_exists('user_import')) {
		echo 				"<input type='button' class='btn' alt='".$text['button-import']."' onclick=\"window.location='user_imports.php'\" value='".$text['button-import']."'>\n";
	}
	echo 	"<input type='text' class='txt' style='width: 150px; margin-left: 15px; margin-right: 3px;' name='search' value=\"".escape($search)."\">";
	echo 	"<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "</td>";
	echo "</tr>\n";
	echo "</form>";

	echo "<tr>\n";
	echo "<td align='left' colspan='4'>\n";
	echo $text['description-user_manager']."\n";
	echo "<br />\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the users
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	if (permission_exists('user_all') && $_GET['show'] == 'all') {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, '', '', $param);
	}
	echo th_order_by('username', $text['label-username'], $order_by, $order);
	echo th_order_by('groups', $text['label-groups'], $order_by, $order, '', '', $param);
	echo th_order_by('contact_organization', $text['label-organization'], $order_by, $order, '', '', $param);
	echo th_order_by('contact_name_given', $text['label-name'], $order_by, $order, '', '', $param);
	if (permission_exists('ticket_edit')) {
		echo "<th>".$text['label-tools']."</th>\n";
	}
	else {
		echo "<th>&nbsp;</th>\n";
	}
	echo th_order_by('user_enabled', $text['label-enabled'], $order_by, $order, '', '', $param);
	echo "<td class='list_control_icons'>";
	if (permission_exists('user_add')) {
		if ($_SESSION['limit']['users']['numeric'] == '' || ($_SESSION['limit']['users']['numeric'] != '' && $total_users < $_SESSION['limit']['users']['numeric'])) {
			echo "<a href='user_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($users) && sizeof($users) != 0) {
		foreach($users as $row) {
			if (if_superadmin($superadmins, $row['user_uuid']) && !if_group("superadmin")) {
				//hide
			} else {
				$tr_link = (permission_exists('user_edit')) ? "href='user_edit.php?id=".escape($row['user_uuid'])."'" : null;
				echo "<tr ".$tr_link.">\n";
				if (permission_exists('user_all') && $_GET['show'] == 'all') {
					echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['domain_name'])."</td>\n";
				}
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				if (permission_exists('user_edit')) {
					echo "<a href='user_edit.php?id=".escape($row['user_uuid'])."'>".escape($row['username'])."</a>";
				}
				else {
					echo escape($row['username']);
				}
				echo "	</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>\n";
				echo "		".$row['groups']."&nbsp;\n";
				echo "	</td>\n";

				echo "	<td class='".$row_style[$c]."'><a href='/app/contacts/contact_edit.php?id=".$row['contact_uuid']."'>".$row['contact_organization']."</a> &nbsp;</td>\n";
				echo "	<td class='".$row_style[$c]."'><a href='/app/contacts/contact_edit.php?id=".$row['contact_uuid']."'>".$row['contact_name_given']." ".$row['contact_name_family']."</a> &nbsp;</td>\n";

				echo "	<td class='".$row_style[$c]."'>\n";
				if (permission_exists('ticket_edit')) {
					echo "		<a href='/app/tickets/tickets.php?user_uuid=".$row['user_uuid']."'><span class='glyphicon glyphicon-tags' title='".$text['label-tickets']."'></span></a>\n";
				}
				echo "	</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				if ($row['user_enabled'] == 'true') {
					echo $text['option-true'];
				}
				else {
					echo $text['option-false'];
				}
				echo "&nbsp;</td>\n";
				echo "	<td valign='top' align='right' class='tr_link_void'>";
				if (permission_exists('user_edit')) {
					echo "<a href='user_edit.php?id=".$row['user_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
				}
				if (permission_exists('user_delete')) {
					if ($_SESSION["user"]["user_uuid"] != $row['user_uuid']) {
						echo "<a href='user_delete.php?id=".$row['user_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
					}
					else {
						echo "<span onclick=\"alert('".$text['message-cannot_delete_own_account']."');\">".str_replace("list_control_icon", "list_control_icon_disabled", $v_link_label_delete)."</span>";
					}
				}
				echo "	</td>\n";
				echo "</tr>\n";
				$c = $c == 0 ? 1 : 0;
			}
		}
		unset($users, $row);
	}

	echo "<tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	echo $paging_controls."\n";
	echo "<br /><br />\n";

//include the footer
	include "resources/footer.php";

?>
