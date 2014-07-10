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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";

//check the permissions
require_once "resources/check_auth.php";
if (if_group("admin") || if_group("superadmin")) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-group_manager'];
	if (isset($_REQUEST["change"])) {
		//get the values from the HTTP POST and save them as PHP variables
		$change = check_str($_REQUEST["change"]);
		$group_name = check_str($_REQUEST["group_name"]);

		$sql = "update v_groups set ";
		$sql .= "group_protected = '$change' ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and group_name = '$group_name' ";
		$db->exec(check_sql($sql));
		unset($sql);
	}

//show the content
	echo "<div class='' style='padding:0px;'>\n";
	echo "<table width='100%'>";
	echo "<td>";

	echo "<table width='100%' border='0'><tr>";
	echo "<td width='50%'><b>".$text['header-group_manager']."</b><br><br></td>";
	echo "<td width='50%' align='right'>";
	if (permission_exists('user_view')) {
		echo "  <input type='button' class='btn' onclick=\"window.location='index.php'\" value='".$text['header-user_manager']."'>";
	}
	if (permission_exists('group_edit')) {
		echo "	<input type='button' class='btn' alt='".$text['button-restore']."' onclick=\"window.location='permissions_default.php'\" value='".$text['button-restore']."'>";
	}
	echo "</td>\n";
	echo "</tr></table>";

	$sql = "SELECT * FROM v_groups ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "order by group_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	$strlist = "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	$strlist .= "<tr class='border'>\n";
	$strlist .= "	<th nowrap>".$text['label-group_name']."</th>\n";
	$strlist .= "	<th nowrap>".$text['label-group_tools']."</th>\n";
	$strlist .= "	<th style='text-align: center;' nowrap>".$text['label-group_protected']."</th>\n";
	$strlist .= "	<th nowrap>".$text['label-group_description']."</th>\n";
	$strlist .= "	<td class='list_control_icons' style='width: 25px;'>";
	if (permission_exists('group_add')) {
		$strlist .= "<a href='groupadd.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	$strlist .= "	</td>\n";
	$strlist .= "</tr>\n";

	$count = 0;
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$group_name = $row["group_name"];
		$group_protected= $row["group_protected"];
		$group_uuid = $row["group_uuid"];
		$group_description = $row["group_description"];
		if (strlen($group_name) == 0) { $group_name = "&nbsp;"; }
		if (strlen($group_description) == 0) { $group_description = "&nbsp;"; }
		$group_description = wordwrap($group_description, 50, "<br />\n");

		if (!if_group("superadmin") && $group_name == "superadmin") {
			//hide the superadmin group from non superadmin's
		}
		else {
			/*
			$tr_link = (permission_exists('group_edit')) ? "href='groupedit.php?id=".$group_uuid."'" : null;
			*/
			$strlist .= "<tr ".$tr_link.">\n";
			$strlist .= "<td class='".$row_style[$c]."' nowrap>";
			/*
			if (permission_exists('group_edit')) {
				$strlist .= "<a href='groupedit.php?id=".$group_uuid."'>".$group_name."</a>";
			}
			else {
			*/
				$strlist .= $group_name;
			/*
			}
			*/
			$strlist .= "</td>\n";
			$strlist .= "<td class='".$row_style[$c]."' nowrap>\n";
			if (permission_exists('group_add') || if_group("superadmin")) {
				$strlist .= "<a class='' href='group_permissions.php?group_name=".$group_name."' title='".$text['label-group_permissions']."'>".$text['label-group_permissions']."</a>&nbsp;&nbsp;";
			}
			if (permission_exists('group_member_view') || if_group("superadmin")) {
				$strlist .= "<a class='' href='groupmembers.php?group_name=".$group_name."' title='".$text['label-group_members']."'>".$text['label-group_members']."</a>";
			}
			$strlist .= "</td>\n";
			$strlist .= "<td class='".$row_style[$c]."' style=\"padding: 0px; text-align: center;\" align=\"center\" nowrap>\n";
			if ($group_protected == "true") {
				$strlist .= "		<input type='checkbox' name='group_protected' checked='checked' value='true' onchange=\"window.location='".PROJECT_PATH."/core/users/groups.php?change=false&group_name=".$group_name."';\">\n";
			}
			else {
				$strlist .= "		<input type='checkbox' name='group_protected' value='false' onchange=\"window.location='".PROJECT_PATH."/core/users/groups.php?change=true&group_name=".$group_name."';\">\n";
			}
			$strlist .= "</td>\n";
			$strlist .= "<td class='row_stylebg' nowrap>".$group_description."</td>\n";
			$strlist .= "<td class='list_control_icons' style='width: 25px;'>";
			/*
			if (permission_exists('group_edit')) {
				$strlist .= "<a href='groupedit.php?id=$group_uuid' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			*/
			if (permission_exists('group_delete')) {
				$strlist .= "<a href='groupdelete.php?id=$group_uuid' onclick=\"return confirm('".$text['confirm-delete']."')\" alt='".$text['button-delete']."'>$v_link_label_delete</a>";
			}
			$strlist .= "</td>\n";
			$strlist .= "</tr>\n";
		}
		if ($c==0) { $c=1; } else { $c=0; }
		$count++;
	}

	$strlist .= "<tr>\n";
	$strlist .= "<td colspan='4'>&nbsp;</td>";
	$strlist .= "<td class='list_control_icons' style='width: 25px;'>";
	if (permission_exists('group_add')) {
		$strlist .= "<a href='groupadd.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
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
	require_once "resources/footer.php";

?>