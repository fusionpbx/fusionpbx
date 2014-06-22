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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//require_once "resources/header.php";
require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//javascript function: send_cmd
	echo "<script type=\"text/javascript\">\n";
	echo "function send_cmd(url) {\n";
	echo "	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari\n";
	echo "		xmlhttp=new XMLHttpRequest();\n";
	echo "	}\n";
	echo "	else {// code for IE6, IE5\n";
	echo "		xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
	echo "	}\n";
	echo "	xmlhttp.open(\"GET\",url,true);\n";
	echo "	xmlhttp.send(null);\n";
	echo "	document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
	echo "}\n";
	echo "</script>\n";

//show the content

	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['label-phone_numbers']."</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_contact_phones ";
		$sql .= " where domain_uuid = '$domain_uuid' ";
		$sql .= " and contact_uuid = '$contact_uuid' ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
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

	//prepare to page the results
		$rows_per_page = 10;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the contact list
		$sql = "select * from v_contact_phones ";
		$sql .= " where domain_uuid = '$domain_uuid' ";
		$sql .= " and contact_uuid = '$contact_uuid' ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$sql .= " limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('phone_type', $text['label-phone_type'], $order_by, $order);
	echo th_order_by('phone_number', $text['label-phone_number'], $order_by, $order);
	echo "<th>".$text['label-phone_tools']."</th>\n";
	echo th_order_by('phone_description', $text['label-phone_description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	echo 	"<a href='contact_phone_edit.php?contact_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	echo "</td>\n";
	echo "</tr>\n";
	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = "href='contact_phone_edit.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_phone_uuid']."'";
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['phone_type'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			echo "		<a href=\"javascript:void(0)\" onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($row['phone_number'])."&src_cid_number=".urlencode($row['phone_number'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($row['phone_number'])."&rec=false&ringback=us-ring&auto_answer=true');\">\n";
			echo "		".format_phone($row['phone_number'])."</a>&nbsp;\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' nowrap='nowrap'>\n";
			echo "		<a href=\"javascript:void(0)\" onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($row['phone_number'])."&src_cid_number=".urlencode($row['phone_number'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($row['phone_number'])."&rec=false&ringback=us-ring&auto_answer=true');\">".$text['label-phone_call']."</a>\n";
			echo "		&nbsp;\n";
			echo "		<a href=\"".PROJECT_PATH."/app/xml_cdr/xml_cdr.php?caller_id_number=".$row['phone_number']."&destination_number=".$row['phone_number']."\">CDR</a>\n";
			echo "		&nbsp;\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['phone_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			echo 		"<a href='contact_phone_edit.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_phone_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			echo 		"<a href='contact_phone_delete.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_phone_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	echo 			"<a href='contact_phone_edit.php?contact_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";

?>