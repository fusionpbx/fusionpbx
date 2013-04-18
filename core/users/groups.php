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
require_once "includes/require.php";

//check the permissions
	require_once "includes/checkauth.php";
	if (if_group("admin") || if_group("superadmin")) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div class='' style='padding:0px;'>\n";
	echo "<table width='100%'>";
	echo "<td>";

	echo "<table width='100%' border='0'><tr>";
	echo "<td width='50%'><b>Groups</b></td>";
	echo "<td width='50%' align='right'>";
	if (permission_exists('user_view')) {
		echo "  <input type='button' class='btn' onclick=\"window.location='index.php'\" value='User Manager'>";
	}
	echo "</td>\n";
	echo "</tr></table>";

	$sql = "SELECT * FROM v_groups ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	$strlist = "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	$strlist .= "<tr class='border'>\n";
	$strlist .= "	<th align=\"left\" nowrap> &nbsp; Group Name &nbsp; </th>\n";
	$strlist .= "	<th align=\"left\" nowrap> &nbsp; Group Description &nbsp; </th>\n";
	$strlist .= "	<th align=\"center\" nowrap>&nbsp;</th>\n";

	$strlist .= "	<td width='22px' align=\"right\" nowrap>\n";
	if (permission_exists('group_add')) {
		$strlist .= "	<a href='groupadd.php' alt='add'>$v_link_label_add</a>\n";
	}
	$strlist .= "	</td>\n";
	$strlist .= "</tr>\n";

	$count = 0;
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$group_name = $row["group_name"];
		$group_uuid = $row["group_uuid"];
		$group_description = $row["group_description"];
		if (strlen($group_name) == 0) { $group_name = "&nbsp;"; }
		if (strlen($group_description) == 0) { $group_description = "&nbsp;"; }
		$group_description = wordwrap($group_description, 50, "<br />\n");

		if (!if_group("superadmin") && $group_name == "superadmin") {
			//hide the superadmin group from non superadmin's
		}
		else {
			$strlist .= "<tr>";
			$strlist .= "<td class='".$row_style[$c]."' align=\"left\" class='' nowrap> &nbsp; $group_name &nbsp; </td>\n";
			$strlist .= "<td class='".$row_style[$c]."' align=\"left\" class='' nowrap> &nbsp;  $group_description &nbsp; </td>\n";

			$strlist .= "<td class='".$row_style[$c]."' align=\"center\" nowrap>\n";
			if (permission_exists('group_add') || if_group("superadmin")) {
				$strlist .= "&nbsp;<a class='' href='group_permissions.php?group_name=$group_name' title='Group Permissions'>Permissions</a>&nbsp;&nbsp;";
			}
			if (permission_exists('group_member_view') || if_group("superadmin")) {
				$strlist .= "&nbsp;<a class='' href='groupmembers.php?group_name=$group_name' title='Group Members'>Members</a>&nbsp;";
			}
			$strlist .= "</td>\n";

			$strlist .= "<td align=\"right\" nowrap>\n";
			$strlist .= "<a href='groupdelete.php?id=$group_uuid' onclick=\"return confirm('Do you really want to delete this?')\" alt='delete'>$v_link_label_delete</a>\n";

			$strlist .= "</td>\n";
			$strlist .= "</tr>\n";
		}
		if ($c==0) { $c=1; } else { $c=0; }
		$count++;
	}

	$strlist .= "<tr>\n";
	$strlist .= "<td colspan='4' align='right' height='20'>\n";
	if (permission_exists('group_add')) {
		$strlist .= "	<a href='groupadd.php' alt='add'>$v_link_label_add</a>\n";
	}
	$strlist .= "</td>\n";
	$strlist .= "</tr>\n";

	$strlist .= "</table>\n";
	if ($count > 0) {
		echo $strlist;
	}

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";
	echo "</div>";

//show the footer
	require_once "includes/footer.php";

?>