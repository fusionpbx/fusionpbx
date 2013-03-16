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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('xml_cdr_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//import xml_cdr files
	require_once "v_xml_cdr_import.php";

//additional includes
	require_once "includes/paging.php";

//set 24hr or 12hr clock
	define('TIME_24HR', 1);

//get post or get variables from http
	if (count($_REQUEST)>0) {
		$order_by = check_str($_REQUEST["order_by"]);
		$order = check_str($_REQUEST["order"]);
		$cdr_id = check_str($_REQUEST["cdr_id"]);
		$missed = check_str($_REQUEST["missed"]);
		$direction = check_str($_REQUEST["direction"]);
		$caller_id_name = check_str($_REQUEST["caller_id_name"]);
		$caller_id_number = check_str($_REQUEST["caller_id_number"]);
		$destination_number = check_str($_REQUEST["destination_number"]);
		$context = check_str($_REQUEST["context"]);
		$start_stamp = check_str($_REQUEST["start_stamp"]);
		$answer_stamp = check_str($_REQUEST["answer_stamp"]);
		$end_stamp = check_str($_REQUEST["end_stamp"]);
		$start_epoch = check_str($_REQUEST["start_epoch"]);
		$stop_epoch = check_str($_REQUEST["stop_epoch"]);
		$duration = check_str($_REQUEST["duration"]);
		$billsec = check_str($_REQUEST["billsec"]);
		$hangup_cause = check_str($_REQUEST["hangup_cause"]);
		$uuid = check_str($_REQUEST["uuid"]);
		$bleg_uuid = check_str($_REQUEST["bleg_uuid"]);
		$accountcode = check_str($_REQUEST["accountcode"]);
		$read_codec = check_str($_REQUEST["read_codec"]);
		$write_codec = check_str($_REQUEST["write_codec"]);
		$remote_media_ip = check_str($_REQUEST["remote_media_ip"]);
		$network_addr = check_str($_REQUEST["network_addr"]);
	}

//build the sql where string
	if ($missed == true) {
		$sql_where .= "and billsec = '0' ";
	}
	if (strlen($start_epoch) > 0 && strlen($stop_epoch) > 0) { 
		$sql_where .= "and start_epoch BETWEEN ".$start_epoch." AND ".$stop_epoch." ";
	}
	if (strlen($cdr_id) > 0) { $sql_where .= "and cdr_id like '%$cdr_id%' "; }
	if (strlen($direction) > 0) { $sql_where .= "and direction = '$direction' "; }
	if (strlen($caller_id_name) > 0) { $sql_where .= "and caller_id_name like '$caller_id_name' "; }
	if (strlen($caller_id_number) > 0 && strlen($destination_number) > 0) {
			$sql_where .= "and (";
			$sql_where .= "caller_id_number = '$caller_id_number' ";
			$sql_where .= "or destination_number = '$destination_number'";
			$sql_where .= ") ";
	}
	else {
		if (strlen($caller_id_number) > 0) { $sql_where .= "and caller_id_number like '$caller_id_number' "; }
		if (strlen($destination_number) > 0) { $sql_where .= "and destination_number like '$destination_number' "; }
	}
	if (strlen($context) > 0) { $sql_where .= "and context like '%$context%' "; }
	if ($db_type == "sqlite") {
		if (strlen($start_stamp) > 0) { $sql_where .= "and start_stamp like '%$start_stamp%' "; }
		if (strlen($end_stamp) > 0) { $sql_where .= "and end_stamp like '%$end_stamp%' "; }
	}
	if ($db_type == "pgsql" || $db_type == "mysql") {
		if (strlen($start_stamp) > 0 && strlen($end_stamp) == 0) { $sql_where .= "and start_stamp between '$start_stamp 00:00:00' and '$start_stamp 23:59:59' "; }
		if (strlen($start_stamp) > 0 && strlen($end_stamp) > 0) { $sql_where .= "and start_stamp between '$start_stamp 00:00:00' and '$end_stamp 23:59:59' "; }
	}
	if (strlen($answer_stamp) > 0) { $sql_where .= "and answer_stamp like '%$answer_stamp%' "; }
	if (strlen($duration) > 0) { $sql_where .= "and duration like '%$duration%' "; }
	if (strlen($billsec) > 0) { $sql_where .= "and billsec like '%$billsec%' "; }
	if (strlen($hangup_cause) > 0) { $sql_where .= "and hangup_cause like '%$hangup_cause%' "; }
	if (strlen($uuid) > 0) { $sql_where .= "and uuid = '$uuid' "; }
	if (strlen($bleg_uuid) > 0) { $sql_where .= "and bleg_uuid = '$bleg_uuid' "; }
	if (strlen($accountcode) > 0) { $sql_where .= "and accountcode = '$accountcode' "; }
	if (strlen($read_codec) > 0) { $sql_where .= "and read_codec like '%$read_codec%' "; }
	if (strlen($write_codec) > 0) { $sql_where .= "and write_codec like '%$write_codec%' "; }
	if (strlen($remote_media_ip) > 0) { $sql_where .= "and remote_media_ip like '%$remote_media_ip%' "; }
	if (strlen($network_addr) > 0) { $sql_where .= "and network_addr like '%$network_addr%' "; }

	//example sql
		// select caller_id_number, destination_number from v_xml_cdr where domain_uuid = '' 
		// and (caller_id_number = '1001' or destination_number = '1001' or destination_number = '*991001')
	if (!if_group("admin") && !if_group("superadmin") && !permission_exists('xml_cdr_domain')) {
		$sql_where = "where domain_uuid = '$domain_uuid' ";
		$sql_where .= "and ( ";
		if (count($_SESSION['user']['extension']) > 0) {
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x==0) {
					if ($row['user'] > 0) { $sql_where .= "caller_id_number = '".$row['user']."' \n"; } //source
				}
				else {
					if ($row['user'] > 0) { $sql_where .= "or caller_id_number = '".$row['user']."' \n"; } //source
				}
				if ($row['user'] > 0) { $sql_where .= "or destination_number = '".$row['user']."' \n"; } //destination
				if ($row['user'] > 0) { $sql_where .= "or destination_number = '*99".$row['user']."' \n"; } //destination
				$x++;
			}
		}
		else {
			$sql_where .= "destination_number = 'no extension assigned' \n"; //destination
		}
		$sql_where .= ") ";
	}
	else {
		//superadmin or admin or permission_exists('xml_cdr_domain')
		$sql_where = "where domain_uuid = '$domain_uuid' ".$sql_where;
	}
	//$sql_where = str_replace ("where or", "where", $sql_where);
	//$sql_where = str_replace ("where and", " and", $sql_where);

//set the param variable which is used with paging
	$param = "";
	$param .= "&missed=$missed";
	$param .= "&caller_id_name=$caller_id_name";
	$param .= "&start_stamp=$start_stamp";
	$param .= "&hangup_cause=$hangup_cause";
	$param .= "&caller_id_number=$caller_id_number";
	$param .= "&destination_number=$destination_number";
	$param .= "&context=$context";
	$param .= "&answer_stamp=$answer_stamp";
	$param .= "&end_stamp=$end_stamp";
	$param .= "&start_epoch=$start_epoch";
	$param .= "&stop_epoch=$stop_epoch";
	$param .= "&duration=$duration";
	$param .= "&billsec=$billsec";
	$param .= "&uuid=$uuid";
	$param .= "&bridge_uuid=$bridge_uuid";
	$param .= "&accountcode=$accountcode";
	$param .= "&read_codec=$read_codec";
	$param .= "&write_codec=$write_codec";
	$param .= "&remote_media_ip=$remote_media_ip";
	$param .= "&network_addr=$network_addr";
	if (isset($order_by)) {
		$param .= "&order_by=".$order_by;
	}
	if (isset($order)) {
		$param .= "&order=".$order;
	}

//create the sql query to get the xml cdr records
	if (strlen($order_by) == 0)  { $order_by  = "start_epoch"; }
	if (strlen($order) == 0)  { $order  = "desc"; }

//set the default
	$num_rows = '0';

//page results if rows_per_page is greater than zero
	if ($rows_per_page > 0) {
		//get the number of rows in the v_xml_cdr 
			$sql = "select count(*) as num_rows from v_xml_cdr ";
			$sql .= $sql_where;
			$prep_statement = $db->prepare(check_sql($sql));
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
			unset($prep_statement, $result);

		//prepare to page the results
			//$rows_per_page = 150; //set on the page that includes this page
			$page = $_GET['page'];
			if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
			list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
			$offset = $rows_per_page * $page;
	}

//get the results from the db
	$sql = "select * from v_xml_cdr ";
	$sql .= $sql_where;
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	if ($rows_per_page > 0) {
		$sql .= " limit $rows_per_page offset $offset ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

?>