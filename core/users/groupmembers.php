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

//get the http value and set as a variable
	$group_name = $_GET["group_name"];

//define the if group members function
	function if_group_members($db, $group_name, $user_uuid) {
		$sql = "select * from v_group_users ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and group_name = '$group_name' ";
		$sql .= "and user_uuid = '$user_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		if (count($prep_statement->fetchAll(PDO::FETCH_NAMED)) == 0) { return true; } else { return false; }
		unset ($sql, $prep_statement);
	}
	//$exampledatareturned = example("apples", 1);

//get the group from v_groups
	$sql = "select * from v_groups ";
	$sql .= "where group_uuid = '".$group_uuid."' ";
	$sql .= "and (domain_uuid = '".$_SESSION['domain_uuid']."' or domain_uuid is null) ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$groups = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($groups as &$row) {
		$group_name = $row["group_name"];
	}
	unset ($prep_statement);

//get the the users array
	if (permission_exists('group_member_add')) {
		$sql = "SELECT * FROM v_users ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "order by username ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	}

//get the groups users
	$sql = "SELECT u.user_uuid, u.username, g.group_user_uuid, g.group_uuid FROM v_group_users as g, v_users as u ";
	$sql .= "where g.user_uuid = u.user_uuid ";
	$sql .= "and g.domain_uuid = '$domain_uuid' ";
	$sql .= "and g.group_name = '$group_name' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

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
	echo "			<b>".$text['header-group_members'].$group_name."</b>";
	echo "		</td>\n";
	echo "		<td align='right' nowrap='nowrap' valign='middle'>\n";
	echo "			<input type='button' class='btn' style='margin-right: 15px;' alt='".$text['button-back']."' onclick=\"window.location='groups.php'\" value='".$text['button-back']."'>";
	echo "		</td>";
	if (permission_exists('group_member_add')) {
		echo "		<td align='right' nowrap='nowrap' valign='top'>\n";
		echo "			<form method='post' action='groupmemberadd.php'>";
		echo "			<select name=\"user_uuid\" style='width: 200px;' class='formfld'>\n";
		echo "				<option value=\"\"></option>\n";
		foreach($users as $field) {
			$username = $field['username'];
			if (if_group_members($db, $group_name, $field['user_uuid']) && !in_array($field['user_uuid'], $group_users)) {
				echo "		<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
			}
		}
		unset($sql, $users);
		echo "			</select>";
		echo "			<input type='hidden' name='group_uuid' value='$group_uuid'>";
		echo "			<input type='hidden' name='group_name' value='$group_name'>";
		echo "			<input type='submit' class='btn' value='".$text['button-add_member']."'>";
		echo "			</form>";
		echo "		</td>\n";
	}
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>";

	$strlist = "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	$strlist .= "<tr>\n";
	$strlist .= "	<th align=\"left\" nowrap> &nbsp; ".$text['label-username']." &nbsp; </th>\n";
	$strlist .= "	<th align=\"left\" nowrap> &nbsp; &nbsp; </th>\n";
	$strlist .= "	<td width='22' align=\"right\" nowrap>\n";
	$strlist .= "		&nbsp;\n";
	$strlist .= "	</td>\n";
	$strlist .= "</tr>\n";

	$count = 0;
	foreach ($result as &$row) {
		$group_user_uuid = $row["group_user_uuid"];
		$username = $row["username"];
		$user_uuid = $row["user_uuid"];
		$group_uuid = $row["group_uuid"];
		$strlist .= "<tr'>";
		$strlist .= "<td align=\"left\"  class='".$row_style[$c]."' nowrap> &nbsp; $username &nbsp; </td>\n";
		$strlist .= "<td align=\"left\"  class='".$row_style[$c]."' nowrap> &nbsp; </td>\n";
		$strlist .= "<td class='list_control_icons' style='width: 25px;'>";
		if (permission_exists('group_member_delete')) {
			$strlist .= "<a href='groupmemberdelete.php?user_uuid=$user_uuid&group_name=$group_name&group_uuid=$group_uuid' onclick=\"return confirm('".$text['confirm-delete']."')\" alt='".$text['button-delete']."'>$v_link_label_delete</a>";
		}
		$strlist .= "</td>\n";
		$strlist .= "</tr>\n";

		if ($c==0) { $c=1; } else { $c=0; }

		$group_users[] = $row["user_uuid"];
		$count++;
	}

	$strlist .= "</table>\n";
	echo $strlist;
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";
?>