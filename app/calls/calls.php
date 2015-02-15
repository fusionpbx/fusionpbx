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
if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//get the https values and set as variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/calls');

//begin the content
	require_once "resources/header.php";
	require_once "resources/paging.php";

	if ($is_included != "true") {
		echo "		<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "		<tr>\n";
		echo "		<td align='left'><b>".$text['title']."</b><br>\n";
		echo "			".$text['description-2']."\n";
		echo "			".$text['description-3']." \n";
		echo "		</td>\n";
		echo "		</tr>\n";
		echo "		</table>\n";
		echo "		<br />";
	}

	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and enabled = 'true' ";
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//used to hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	if (strlen($order_by)> 0) {
		$sql .= "order by $order_by $order ";
	}
	else {
		$sql .= "order by extension asc ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$num_rows = count($result);
	unset ($prep_statement, $result, $sql);

	$rows_per_page = 150;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and enabled = 'true' ";
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	if (strlen($order_by)> 0) {
		$sql .= "order by $order_by $order ";
	}
	else {
		$sql .= "order by extension asc ";
	}
	$sql .= " limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>".$text['table-extension']."</th>\n";
	echo "<th>".$text['table-tools']."</th>\n";
	echo "<th>".$text['table-description']."</th>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_url = PROJECT_PATH."/app/calls/call_edit.php?id=".$row['extension_uuid'];
			$tr_link = (permission_exists('call_forward') || permission_exists('follow_me') || permission_exists('do_not_disturb')) ? "href='".$tr_url."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['extension']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			if (permission_exists('call_forward')) { 	echo "<a href='".$tr_url."'>".$text['label-call-forward']."</a>&nbsp;&nbsp;&nbsp;"; }
			if (permission_exists('follow_me')) {		echo "<a href='".$tr_url."'>".$text['label-follow-me']."</a>&nbsp;&nbsp;&nbsp;"; }
			if (permission_exists('do_not_disturb')) {	echo "<a href='".$tr_url."'>".$text['label-dnd']."</a>"; }
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='40%'>".$row['description']."&nbsp;</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	if (strlen($paging_controls) > 0) {
		echo "<tr>\n";
		echo "<td colspan='5' align='left'>\n";
		echo "	<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
		echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>";
	echo "<br><br>";

	if ($is_included != "true") {
		require_once "resources/footer.php";
	}

?>