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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

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

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-group_members'];

//show the content
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";

	echo "<table width='100%' cellpadding='6' cellspacing='1'>\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<span  class=\"\" height='50'><b>".$text['header-group_members'].$group_name."</b></span>";
	echo "		</td>\n";
	echo "		<td align='right' nowrap='nowrap'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='groups.php'\" value='".$text['button-back']."'>";
	echo "			&nbsp;&nbsp;&nbsp;\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	$sql = "SELECT u.user_uuid, u.username, g.group_user_uuid FROM v_group_users as g, v_users as u ";
	$sql .= "where g.user_uuid = u.user_uuid ";
	$sql .= "and g.domain_uuid = '$domain_uuid' ";
	$sql .= "and g.group_name = '$group_name' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();

	$strlist = "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	$strlist .= "<tr>\n";
	$strlist .= "	<th align=\"left\" nowrap> &nbsp; ".$text['label-username']." &nbsp; </th>\n";
	$strlist .= "	<th align=\"left\" nowrap> &nbsp; &nbsp; </th>\n";
	$strlist .= "	<td width='22' align=\"right\" nowrap>\n";
	$strlist .= "		&nbsp;\n";
	$strlist .= "	</td>\n";
	$strlist .= "</tr>\n";

	$count = 0;
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$group_user_uuid = $row["group_user_uuid"];
		$username = $row["username"];
		$user_uuid = $row["user_uuid"];
		$strlist .= "<tr'>";
		$strlist .= "<td align=\"left\"  class='".$row_style[$c]."' nowrap> &nbsp; $username &nbsp; </td>\n";
		$strlist .= "<td align=\"left\"  class='".$row_style[$c]."' nowrap> &nbsp; </td>\n";
		$strlist .= "<td class='list_control_icons' style='width: 25px;'>";
		if (permission_exists('group_member_delete')) {
			$strlist .= "<a href='groupmemberdelete.php?user_uuid=$user_uuid&group_name=$group_name' onclick=\"return confirm('".$text['confirm-delete']."')\" alt='".$text['button-delete']."'>$v_link_label_delete</a>";
		}
		$strlist .= "</td>\n";
		$strlist .= "</tr>\n";

		if ($c==0) { $c=1; } else { $c=0; }

		$group_users[] = $row["user_uuid"];
		$count++;
	}

	$strlist .= "</table>\n";
	echo $strlist;

	echo "<br>";

	echo "  <div align='center'>";
	echo "  <form method='post' action='groupmemberadd.php'>";
	echo "  <table width='250'>";
	echo "	<tr>";
	echo "		<td width='60%' align='right'>";

	$sql = "SELECT * FROM v_users ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "order by username ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();

	echo "<select name=\"user_uuid\" style='width: 200px;' class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
		$username = $field['username'];
		if (if_group_members($db, $group_name, $field['user_uuid']) && !in_array($field['user_uuid'], $group_users)) {
			echo "<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
		}
	}
	echo "</select>";
	unset($sql, $result);

	echo "		</td>";
	echo "		<td align='right'>";
	if (permission_exists('group_member_add')) {
		echo "          <input type='hidden' name='group_name' value='$group_name'>";
		echo "          <input type='submit' class='btn' value='".$text['button-add_member']."'>";
	}
	echo "      </td>";
	echo "	</tr>";
	echo "  </table>";
	echo "  </form>";
	echo "  </div>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";
?>