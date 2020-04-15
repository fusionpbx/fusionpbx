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
	require_once "resources/paging.php";

//check permissions	
	if (permission_exists('zoiper')) {
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

//get the SIP port if set. If not default will be 5060 in zoiper app
	if (isset($_SESSION['zoiper']['sip_port']['text'])) {
		$zoiper_sip_port = ":" . $_SESSION['zoiper']['sip_port']['text'];
	}

//handle search term
	$search = check_str($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_mod = "and ( ";
		$sql_mod .= "extension ILIKE '%".$search."%' ";
		$sql_mod .= "or description ILIKE '%".$search."%' ";
		$sql_mod .= ") ";
	}
	if (strlen($order_by) < 1) {
		$order_by = "extension";
		$order = "ASC";
	}

//get total extension count from the database
	$sql = "select count(*) as num_rows from v_extensions where domain_uuid = '".$_SESSION['domain_uuid']."' ".$sql_mod." ";
	//$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
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
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= $sql_mod; //add search mod from above	
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

//begin the content
	require_once "resources/header.php";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['title']."</b><br>\n";
	echo "	</td>\n";
	echo "		<td align='right' width='100%' style='vertical-align: top;'>";
	if ((if_group("admin") || if_group("superadmin"))) {
		echo "		<form method='get' action=''>\n";
		echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
		echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".escape($search)."'>";
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
	echo "			".$text['description-zoiper']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			<b>".$text['title-mobile']."</b><br>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-zoiper2']."\n";	
	echo "		</td>\n";
	echo "	</tr>\n";	
	echo "</table>\n";
	echo "<br>";
	
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('extension', $text['table-extension'], $order_by,$order);
	echo "<th>".$text['table-tools']."</th>\n";
	echo "<th>".$text['table-qr']."</th>\n";
//	echo "<th>".$text['table-password']."</th>\n";	
	echo th_order_by('description', $text['table-description'], $order_by, $order);
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_url = "https://www.zoiper.com/en/page/" . $_SESSION['zoiper']['page_id']['text'] . "?u=" . escape($row['extension']) . "&h=" . escape($row['user_context']) . rawurlencode($zoiper_sip_port) . "&p=" . escape($row['password']) . "&o=" . $_SESSION['zoiper']['outbound_proxy']['text'] . "&t=&x=&a=" . escape($row['extension']) . "&tr=";
			$qr_img = "https://oem.zoiper.com/qr.php?provider_id=" . $_SESSION['zoiper']['provider_id']['text'] . "&u=" . escape($row['extension']) . "&h=" . escape($row['user_context']) . rawurlencode($zoiper_sip_port) . "&p=" . escape($row['password']) . "&o=" . $_SESSION['zoiper']['outbound_proxy']['text'] . "&t=&x=&a=" . escape($row['extension']) . "&tr=";
			$tr_link = (permission_exists('zoiper')) ? "href='".$tr_url."'" : null;
			echo "<tr>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['extension'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			if (permission_exists('zoiper')) { 	echo "<a href='".$tr_url."' target='_blank'>" . $text['label-zoiper'] . "</a>&nbsp;&nbsp;&nbsp;"; }
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			echo "	<a href='".$qr_img."' target='_blank'>" . $text['label-qr'] . "</a>&nbsp;&nbsp;&nbsp;";
			echo "	</td>\n";
//			echo "	<td valign='top' class='".$row_style[$c]."'>";
//			echo "			<option data-toggle='tooltip' ";
//			echo 				"title='User: ".$row['extension']."\n";
//			echo 				"Password: ".$row['password']."' ";
//			echo "			</option>\n";			
//			echo "******";
//			echo "&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='40%'>".escape($row['description'])."&nbsp;</td>\n";
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

	if ($is_included != "true") {
		require_once "resources/footer.php";
	}

?>
