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

//check permissions
if (permission_exists('hunt_group_call_forward')) {

	require_once "resources/header.php";
	$page["title"] = $text['title-hunt-group_call_forward'];

	require_once "resources/paging.php";


	//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";

	if ($is_included != "true") {
		echo "	<br>";
		echo "	<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
		echo "	<tr>\n";
		echo "	<td align='left'><b>".$text['header-hunt-group_call_forward']."</b><br>\n";
		echo "		".$text['description-hunt_group_call_forward']."\n";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "	<br />";
	}

	$sql = "select * from v_hunt_groups ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and hunt_group_type <> 'dnd' ";
	$sql .= "and hunt_group_type <> 'call_forward' ";
	$sql .= "and hunt_group_type <> 'follow_me_simultaneous' ";
	$sql .= "and hunt_group_type <> 'follow_me_sequence' ";
	if (!(permission_exists('hunt_group_add') || permission_exists('hunt_group_edit'))) {
		$sql .= "and hunt_group_user_list like '%|".$_SESSION["username"]."|%' ";
	}
	if (strlen($order_by)> 0) {
		$sql .= "order by $order_by $order ";
	}
	else {
		$sql .= "order by hunt_group_extension asc ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	if ($is_included == "true" && $result_count == 0) {
		//hide this when there is no result
	}
	else {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th>".$text['label-extension']."</th>\n";
		echo "<th>".$text['label-tools']."</th>\n";
		echo "<th>".$text['label-description']."</th>\n";
		echo "</tr>\n";
	}

	if ($result_count > 0) {
		foreach($result as $row) {
			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['hunt_group_extension']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			echo "		<a href='".PROJECT_PATH."/app/hunt_group/hunt_group_call_forward_edit.php?id=".$row['hunt_group_uuid']."&a=call_forward' alt='".$text['label-call_forward']."'>".$text['label-call_forward']."</a> \n";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='40%'>".$row['hunt_group_description']."&nbsp;</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
		unset($sql, $result, $row_count);
	} //end if results

	if ($is_included == "true" && $result_count == 0) {
		//hide this when there is no result
	}
	else {
		echo "</table>";

		echo "<br>";
		echo "<br>";
		echo "<br>";
	}

	echo "</table>";
	echo "</div>";

	if ($is_included != "true") {
		require_once "resources/footer.php";
	}
}

?>
