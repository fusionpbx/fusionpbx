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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_members_view') || if_group("superadmin")) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//requires a superadmin to view members of the superadmin group
	if (!if_group("superadmin") && $_GET["group_name"] == "superadmin") {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the group uuid, lookup domain uuid (if any) and name
	$group_uuid = $_REQUEST['group_uuid'];
	$sql = "select domain_uuid, group_name from v_groups ";
	$sql .= "where group_uuid = :group_uuid ";
	$parameters['group_uuid'] = $group_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && sizeof($row) != 0) {
		$domain_uuid = $row["domain_uuid"];
		$group_name = $row["group_name"];
	}
	unset($sql, $parameters, $row);

//define the if group members function
	function is_group_member($group_uuid, $user_uuid) {
		global $domain_uuid;
		$sql = "select count(*) from v_user_groups ";
		$sql .= "where user_uuid = :user_uuid ";
		$sql .= "and group_uuid = :group_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['user_uuid'] = $user_uuid;
		$parameters['group_uuid'] = $group_uuid;
		$parameters['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : $_SESSION['domain_uuid'];
		$database = new database;
		$num_rows = $database->select($sql, $parameters, 'column');
		return $num_rows == 0 ? true : false;
		unset($sql, $parameters, $num_rows);
	}
	//$exampledatareturned = example("apples", 1);

//get the the users array
	if (permission_exists('group_member_add')) {
		$sql = "select * from v_users where ";
		$sql .= "domain_uuid = :domain_uuid ";
		$sql .= "order by username ";
		$parameters['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : $_SESSION['domain_uuid'];
		$database = new database;
		$users = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the groups users
	$sql = "select u.user_uuid, u.username, ug.user_group_uuid, ug.domain_uuid, ug.group_uuid ";
	$sql .= "from v_user_groups as ug, v_users as u, v_domains as d ";
	$sql .= "where ug.user_uuid = u.user_uuid ";
	$sql .= "and ug.domain_uuid = d.domain_uuid ";
	if (is_uuid($domain_uuid)) {
		$sql .= "and ug.domain_uuid = :domain_uuid_ug ";
		$parameters['domain_uuid_ug'] = $domain_uuid;
	}
	if (!permission_exists('user_all')) {
		$sql .= "and u.domain_uuid = :domain_uuid_u ";
		$parameters['domain_uuid_u'] = $_SESSION['domain_uuid'];
	}
	$sql .= "and ug.group_uuid = :group_uuid ";
	$sql .= "order by d.domain_name asc, u.username asc ";
	$parameters['group_uuid'] = $group_uuid;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-group_members'];

//show the content
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='100%' align='left' valign='top'>\n";
	echo "			<b>".$text['header-group_members'].$group_name."</b>\n";
	echo "		</td>\n";
	echo "		<td align='right' nowrap='nowrap' valign='middle'>\n";
	echo "			<input type='button' class='btn' style='margin-right: 15px;' alt='".$text['button-back']."' onclick=\"window.location='groups.php'\" value='".$text['button-back']."'>";
	echo "		</td>";
	if (permission_exists('group_member_add')) {
		echo "		<td align='right' nowrap='nowrap' valign='top'>\n";
		echo "			<form method='post' action='groupmemberadd.php'>";
		echo "			<select name='user_uuid' style='width: 200px;' class='formfld'>\n";
		echo "				<option value=''></option>\n";
		foreach($users as $field) {
			if (is_group_member($group_uuid, $field['user_uuid'])) {
				echo "		<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
			}
		}
		unset($sql, $users);
		echo "			</select>";
		echo "			<input type='hidden' name='domain_uuid' value='".(($domain_uuid != '') ? $domain_uuid : $_SESSION['domain_uuid'])."'>";
		echo "			<input type='hidden' name='group_uuid' value='".$group_uuid."'>";
		echo "			<input type='hidden' name='group_name' value='".$group_name."'>";
		echo "			<input type='submit' class='btn' value='".$text['button-add_member']."'>";
		echo "			</form>";
		echo "		</td>\n";
	}
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>";

	$echo = "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	$echo .= "<tr>\n";
	if (permission_exists('user_all')) {
		$echo .= "<th width='30%' align='left' nowrap>".$text['label-domain']."</th>\n";
	}
	$echo .= "	<th align='left' nowrap>".$text['label-username']."</th>\n";
	$echo .= "	<td width='25' align='right' nowrap>&nbsp;</td>\n";
	$echo .= "</tr>\n";

	$count = 0;
	if (is_array($result) && sizeof($result) != 0) {
		foreach ($result as &$row) {
			$username = $row["username"];
			$user_uuid = $row["user_uuid"];
			$domain_uuid = $row["domain_uuid"];
			$group_uuid = $row["group_uuid"];
			$echo .= "<tr>";
			if (permission_exists('user_all')) {
				$echo .= "<td align='left' class='".$row_style[$c]."' nowrap='nowrap'>".$_SESSION['domains'][$domain_uuid]['domain_name']."</td>\n";
			}
			$echo .= "<td align='left' class='".$row_style[$c]."' nowrap='nowrap'>".$username."</td>\n";
			$echo .= "<td class='list_control_icons' style='width: 25px;'>";
			if (permission_exists('group_member_delete')) {
				$echo .= "<a href='groupmemberdelete.php?user_uuid=".$user_uuid."&group_name=".$group_name."&group_uuid=".$group_uuid."' onclick=\"return confirm('".$text['confirm-delete']."')\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
			}
			$echo .= "</td>\n";
			$echo .= "</tr>\n";

			$c = ($c) ? 0 : 1;

			$user_groups[] = $row["user_uuid"];
			$count++;
		}
	}

	$echo .= "</table>\n";
	$echo .= "<br /><br />";
	echo $echo;

//include the footer
	require_once "resources/footer.php";

?>
