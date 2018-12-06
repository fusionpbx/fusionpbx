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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	KonradSC <konrd@yahoo.com>
  Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions	
	if (permission_exists('mobile_twinning_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();
	
//get the https values and set as variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//handle search term
	$search = check_str($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_mod = "and ( ";
		$sql_mod .= "e.extension ILIKE '%".$search."%' ";
		$sql_mod .= "or m.mobile_twinning_description ILIKE '%".$search."%' ";
		$sql_mod .= "or m.mobile_twinning_number ILIKE '%".$search."%' ";
		$sql_mod .= ") ";
	}
	if (strlen($order_by) < 1) {
		$order_by = "e.extension";
		$order = "ASC";
	}

//get total extension count from the database
	$sql = "select count(*) as num_rows from v_extensions where domain_uuid = '".$_SESSION['domain_uuid']."' ".$sql_mod." ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_extensions = $row['num_rows'];
		if (($db_type == "pgsql") or ($db_type == "mysql")) {
			$numeric_extensions = $row['num_rows'];
		}
	}
	unset($prep_statement, $row);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search."&order_by=".$order_by."&order=".$order;
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($total_extensions, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($total_extensions, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $_GET['page'];

//get all the extensions from the database
	$sql = "select e.extension, m.mobile_twinning_number, e.description, m.mobile_twinning_uuid, e.extension_uuid \n";
	$sql .= "FROM  v_extensions AS e \n ";
	$sql .= "LEFT OUTER JOIN v_mobile_twinnings AS m ON m.extension_uuid = e.extension_uuid ";
	$sql .= "where e.domain_uuid = '$domain_uuid' ";
	$sql .= $sql_mod; //add search mod from above	
	$sql .= "and e.enabled = 'true' ";
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

//set the alternating styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//begin the content
	require_once "resources/header.php";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-mobile_twinning']." (".$numeric_extensions.")</b><br>\n";
	echo "	</td>\n";
	echo "		<td align='right' width='100%' style='vertical-align: top;'>";
	if ((if_group("admin") || if_group("superadmin"))) {
		echo "		<form method='get' action=''>\n";
		echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
		echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>";
		echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
		if ($paging_controls_mini != '') {
			echo 			"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
		}
		echo "			</td>\n";
		echo "			</td>\n";
		echo "		</form>\n";	
	}
	echo "  </tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-mobile_twinning']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>";
	
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('extension', $text['table-extension'], $order_by,$order);
	echo th_order_by('m.mobile_twinning_number', $text['table-twinning_number'], $order_by, $order);
	echo th_order_by('e.description', $text['table-description'], $order_by, $order);
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('mobile_twinning_edit')) ? " href='mobile_twinning_edit.php?id=".$row['mobile_twinning_uuid']."&extid=".$row['extension_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['extension']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".format_phone(substr($row['mobile_twinning_number'],-10))."</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='40%'>".$row['description']."&nbsp;</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "</table>";
	if (strlen($paging_controls) > 0) {
		echo "<br />";
		echo $paging_controls."\n";
	}
	echo "<br><br>";
	
//show the footer
	require_once "resources/footer.php";
?>
