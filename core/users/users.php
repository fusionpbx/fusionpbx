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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists("user_view") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//additional includes
	require_once "resources/paging.php";

//set the variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);
	$search_value = check_str($_REQUEST["search_value"]);

//get the list of superadmins
	$superadmins = superadmin_list($db);

//get the users' group(s) from the database
	$sql = "select ";
	$sql .= "	gu.*, g.domain_uuid as group_domain_uuid ";
	$sql .= "from ";
	$sql .= "	v_group_users as gu, ";
	$sql .= "	v_groups as g ";
	$sql .= "where ";
	$sql .= "	gu.group_uuid = g.group_uuid ";
	if (!(permission_exists('user_all') && $_GET['showall'] == 'true')) {
		$sql .= "	and (";
		$sql .= "		g.domain_uuid = '".$domain_uuid."' ";
		$sql .= "		or g.domain_uuid is null ";
		$sql .= "	) ";
		$sql .= "	and gu.domain_uuid = '".$domain_uuid."' ";
	}
	$sql .= "order by ";
	$sql .= "	g.domain_uuid desc, ";
	$sql .= "	g.group_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	if (count($result) > 0) {
		foreach($result as $row) {
			$user_groups[$row['user_uuid']][] = $row['group_name'].(($row['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$row['group_domain_uuid']]['domain_name'] : null);
		}
	}
	unset ($sql, $prep_statement);

//get total user count from the database
	$sql = "select count(*) as num_rows from v_users where 1 = 1 ";
	if (!(permission_exists('user_all') && $_GET['showall'] == 'true')) {
		$sql .= "and domain_uuid = '".$_SESSION['domain_uuid']."' ";
	}
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_users = $row['num_rows'];
	}
	unset($prep_statement, $row);

//get the users from the database (reuse $sql from above)
	if (strlen($search_value) > 0) {
		$sql .= "and username = '".$search_value."' ";
	}
	if (strlen($order_by) > 0) { $sql .= "order by ".$order_by." ".$order." "; }
	$prep_statement = $db->prepare($sql);
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
	unset ($prep_statement, $result, $sql);
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "search=".$search_value;
	if (permission_exists('user_all') && $_GET['showall'] == 'true') {
		$param .= "&showall=true";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$sql = "select * from v_users where 1 = 1 ";
	if (!(permission_exists('user_all') && $_GET['showall'] == 'true')) {
		$sql .= "and domain_uuid = '".$_SESSION['domain_uuid']."' ";
	}
	if (strlen($search_value) > 0) {
		$sql .= "and username like '%".$search_value."%' ";
	}
	if (strlen($order_by)> 0) {
		$sql .= "order by ".$order_by." ".$order." ";
	}
	else {
		$sql .= "order by username asc ";
	}
	$sql .= " limit ".$rows_per_page." offset ".$offset." ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$user_count = count($users);
	unset ($prep_statement, $sql);

//page title and description
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<form method='post' action=''>";
	echo "<tr>\n";
	echo "<td align='left' width='90%' nowrap='nowrap' valign='top'><b>".$text['header-user_manager']." (".$num_rows.")</b></td>\n";
	echo "<td align='right' nowrap='nowrap'>";
	if (permission_exists('user_all')) {
		if ($_GET['showall'] == 'true') {
			echo "<input type='button' class='btn' value='".$text['button-back']."' onclick=\"window.location='index.php';\">\n";
			echo "<input type='hidden' name='showall' value='true'>";
		}
		else {
			echo "<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='index.php?showall=true';\">\n";
		}
	}
	echo 	"<input type='text' class='txt' style='width: 150px; margin-right: 3px;' name='search_value' value=\"".$search_value."\">";
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

//show the data
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	if (permission_exists('user_all') && $_GET['showall'] == 'true') {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, '', '', $param);
	}
	echo th_order_by('username', $text['label-username'], $order_by, $order);
	echo "<th>".$text['label-groups']."</th>\n";
	echo th_order_by('user_enabled', $text['label-enabled'], $order_by, $order, '', '', $param);
	echo "<td class='list_control_icons'>";
	if (permission_exists('user_add')) {
		if ($_SESSION['limit']['users']['numeric'] == '' || ($_SESSION['limit']['users']['numeric'] != '' && $total_users < $_SESSION['limit']['users']['numeric'])) {
			echo "<a href='signup.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($user_count > 0) {
		foreach($users as $row) {
			if (if_superadmin($superadmins, $row['user_uuid']) && !if_group("superadmin")) {
				//hide
			} else {
				$tr_link = (permission_exists('user_edit')) ? "href='usersupdate.php?id=".$row['user_uuid']."'" : null;
				echo "<tr ".$tr_link.">\n";
				if (permission_exists('user_all') && $_GET['showall'] == 'true') {
					echo "	<td valign='top' class='".$row_style[$c]."'>".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."</td>\n";
				}
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				if (permission_exists('user_edit')) {
					echo "<a href='usersupdate.php?id=".$row['user_uuid']."'>".$row['username']."</a>";
				}
				else {
					echo $row['username'];
				}
				echo "	</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				if (sizeof($user_groups[$row['user_uuid']]) > 0) {
					echo implode(', ', $user_groups[$row['user_uuid']]);
				}
				echo "&nbsp;</td>\n";
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
					echo "<a href='usersupdate.php?id=".$row['user_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
				}
				if (permission_exists('user_delete')) {
					if ($_SESSION["user"]["user_uuid"] != $row['user_uuid']) {
						echo "<a href='userdelete.php?id=".$row['user_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
					}
					else {
						echo "<span onclick=\"alert('".$text['message-cannot_delete_own_account']."');\">".str_replace("list_control_icon", "list_control_icon_disabled", $v_link_label_delete)."</span>";
					}
				}
				echo "	</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			}
		} //end foreach
		unset($sql, $users, $user_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='49' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('user_add')) {
		if ($_SESSION['limit']['users']['numeric'] == '' || ($_SESSION['limit']['users']['numeric'] != '' && $total_users < $_SESSION['limit']['users']['numeric'])) {
			echo "<a href='signup.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

?>