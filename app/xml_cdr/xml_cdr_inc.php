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
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('xml_cdr_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//additional includes
	require_once "resources/paging.php";

//set 24hr or 12hr clock
	define('TIME_24HR', 1);

//get post or get variables from http
	if (count($_REQUEST) > 0) {
		$cdr_id = check_str($_REQUEST["cdr_id"]);
		$missed = check_str($_REQUEST["missed"]);
		$direction = check_str($_REQUEST["direction"]);
		$caller_id_name = check_str($_REQUEST["caller_id_name"]);
		$caller_id_number = check_str($_REQUEST["caller_id_number"]);
		$caller_extension_uuid = check_str($_REQUEST["caller_extension_uuid"]);
		$destination_number = check_str($_REQUEST["destination_number"]);
		$context = check_str($_REQUEST["context"]);
		$start_stamp_begin = check_str($_REQUEST["start_stamp_begin"]);
		$start_stamp_end = check_str($_REQUEST["start_stamp_end"]);
		$answer_stamp_begin = check_str($_REQUEST["answer_stamp_begin"]);
		$answer_stamp_end = check_str($_REQUEST["answer_stamp_end"]);
		$end_stamp_begin = check_str($_REQUEST["end_stamp_begin"]);
		$end_stamp_end = check_str($_REQUEST["end_stamp_end"]);
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
		$bridge_uuid = check_str($_REQUEST["network_addr"]);
		$order_by = check_str($_REQUEST["order_by"]);
		$order = check_str($_REQUEST["order"]);
		if (strlen(check_str($_REQUEST["mos_comparison"])) > 0) {
			switch(check_str($_REQUEST["mos_comparison"])) {
				case 'less':
					$mos_comparison = "<";
					break;
				case 'greater':
					$mos_comparison = ">";
					break;
				case 'lessorequal':
					$mos_comparison = "<=";
					break;
				case 'greaterorequal':
					$mos_comparison = ">=";
					break;
				case 'equal':
					$mos_comparison = "<";
					break;
				case 'notequal':
					$mos_comparison = "<>";
					break;
			}
         } else {
             $mos_comparison = '';
        }
		//$mos_comparison = check_str($_REQUEST["mos_comparison"]);
		$mos_score = check_str($_REQUEST["mos_score"]);
	}



//build the sql where string
	if ($missed == true) {
		$sql_where_ands[] = "billsec = '0'";
	}
	if (strlen($start_epoch) > 0 && strlen($stop_epoch) > 0) {
		$sql_where_ands[] = "start_epoch BETWEEN ".$start_epoch." AND ".$stop_epoch." ";
	}
	if (strlen($cdr_id) > 0) { $sql_where_ands[] = "cdr_id like '%".$cdr_id."%'"; }
	if (strlen($direction) > 0) { $sql_where_ands[] = "direction = '".$direction."'"; }
	if (strlen($caller_id_name) > 0) {
		$mod_caller_id_name = str_replace("*", "%", $caller_id_name);
		$sql_where_ands[] = "caller_id_name like '".$mod_caller_id_name."'";
	}
	if (strlen($caller_extension_uuid) > 0) {
		$sql_where_ands[] = "extension_uuid = '".$caller_extension_uuid."'";
	}
	if (strlen($caller_id_number) > 0) {
		$mod_caller_id_number = str_replace("*", "%", $caller_id_number);
		$sql_where_ands[] = "caller_id_number like '".$mod_caller_id_number."'";
	}
	if (strlen($destination_number) > 0) {
		$mod_destination_number = str_replace("*", "%", $destination_number);
		$sql_where_ands[] = "destination_number like '".$mod_destination_number."'";
	}
	if (strlen($context) > 0) { $sql_where_ands[] = "context like '%".$context."%'"; }
	if (strlen($start_stamp_begin) > 0 && strlen($start_stamp_end) > 0) { $sql_where_ands[] = "start_stamp BETWEEN '".$start_stamp_begin.":00.000' AND '".$start_stamp_end.":59.999'"; }
	else {
		if (strlen($start_stamp_begin) > 0) { $sql_where_ands[] = "start_stamp >= '".$start_stamp_begin.":00.000'"; }
		if (strlen($start_stamp_end) > 0) { $sql_where_ands[] = "start_stamp <= '".$start_stamp_end.":59.999'"; }
	}
	if (strlen($answer_stamp_begin) > 0 && strlen($answer_stamp_end) > 0) { $sql_where_ands[] = "answer_stamp BETWEEN '".$answer_stamp_begin.":00.000' AND '".$answer_stamp_end.":59.999'"; }
	else {
		if (strlen($answer_stamp_begin) > 0) { $sql_where_ands[] = "answer_stamp >= '".$answer_stamp_begin.":00.000'"; }
		if (strlen($answer_stamp_end) > 0) { $sql_where_ands[] = "answer_stamp <= '".$answer_stamp_end.":59.999'"; }
	}
	if (strlen($end_stamp_begin) > 0 && strlen($end_stamp_end) > 0) { $sql_where_ands[] = "end_stamp BETWEEN '".$end_stamp_begin.":00.000' AND '".$end_stamp_end.":59.999'"; }
	else {
		if (strlen($end_stamp_begin) > 0) { $sql_where_ands[] = "end_stamp >= '".$end_stamp_begin.":00.000'"; }
		if (strlen($end_stamp_end) > 0) { $sql_where_ands[] = "end_stamp <= '".$end_stamp_end.":59.999'"; }
	}
	if (strlen($duration) > 0) { $sql_where_ands[] = "duration like '%".$duration."%'"; }
	if (strlen($billsec) > 0) { $sql_where_ands[] = "billsec like '%".$billsec."%'"; }
	if (strlen($hangup_cause) > 0) { $sql_where_ands[] = "hangup_cause like '%".$hangup_cause."%'"; }
	if (strlen($uuid) > 0) { $sql_where_ands[] = "uuid = '".$uuid."'"; }
	if (strlen($bleg_uuid) > 0) { $sql_where_ands[] = "bleg_uuid = '".$bleg_uuid."'"; }
	if (strlen($accountcode) > 0) { $sql_where_ands[] = "accountcode = '".$accountcode."'"; }
	if (strlen($read_codec) > 0) { $sql_where_ands[] = "read_codec like '%".$read_codec."%'"; }
	if (strlen($write_codec) > 0) { $sql_where_ands[] = "write_codec like '%".$write_codec."%'"; }
	if (strlen($remote_media_ip) > 0) { $sql_where_ands[] = "remote_media_ip like '%".$remote_media_ip."%'"; }
	if (strlen($network_addr) > 0) { $sql_where_ands[] = "network_addr like '%".$network_addr."%'"; }
	if (strlen($mos_comparison) > 0 && strlen($mos_score) > 0 ) { $sql_where_ands[] = "rtp_audio_in_mos " . $mos_comparison . " ".$mos_score.""; }

	//if not admin or superadmin, only show own calls
	if (!permission_exists('xml_cdr_domain')) {
		if (count($_SESSION['user']['extension']) > 0) { // extensions are assigned to this user
			// create simple user extension array
			foreach ($_SESSION['user']['extension'] as $row) { $user_extensions[] = $row['user']; }
			// if both a source and destination are submitted, but neither are an assigned extension, restrict results
			if (
				$caller_id_number != '' &&
				$destination_number != '' &&
				array_search($caller_id_number, $user_extensions) === false &&
				array_search($destination_number, $user_extensions) === false
				) {
				$sql_where_ors[] = "caller_id_number like '".$user_extension."'";
				$sql_where_ors[] = "destination_number like '".$user_extension."'";
				$sql_where_ors[] = "destination_number like '*99".$user_extension."'";
			}
			// if source submitted is blank, implement restriction for assigned extension(s)
			if ($caller_id_number == '') { // if source criteria is blank, then restrict to assigned ext
				foreach ($user_extensions as $user_extension) {
					if (strlen($user_extension) > 0) {	$sql_where_ors[] = "caller_id_number like '".$user_extension."'"; }
				}
			}
			// if destination submitted is blank, implement restriction for assigned extension(s)
			if ($destination_number == '') {
				foreach ($user_extensions as $user_extension) {
					if (strlen($user_extension) > 0) {
						$sql_where_ors[] = "destination_number like '".$user_extension."'";
						$sql_where_ors[] = "destination_number like '*99".$user_extension."'";
					}
				}
			}
			// concatenate the 'or's array, then add to the 'and's array
			if (sizeof($sql_where_ors) > 0) {
				$sql_where_ands[] = "( ".implode(" or ", $sql_where_ors)." )";
			}
		}
		else {
			$sql_where_ands[] = "1 <> 1"; //disable viewing of cdr records by users with no assigned extensions
		}
	}

	// concatenate the 'ands's array, add to where clause
	if (sizeof($sql_where_ands) > 0) {
		$sql_where = " and ".implode(" and ", $sql_where_ands);
	}

//set the param variable which is used with paging
	$param = "&cdr_id=".$cdr_id;
	$param .= "&missed=".$missed;
	$param .= "&direction=".$direction;
	$param .= "&caller_id_name=".$caller_id_name;
	$param .= "&caller_id_number=".$caller_id_number;
	$param .= "&caller_extension_uuid=".$caller_extension_uuid;
	$param .= "&destination_number=".$destination_number;
	$param .= "&context=".$context;
	$param .= "&start_stamp_begin=".$start_stamp_begin;
	$param .= "&start_stamp_end=".$start_stamp_end;
	$param .= "&answer_stamp_begin=".$answer_stamp_begin;
	$param .= "&answer_stamp_end=".$answer_stamp_end;
	$param .= "&end_stamp_begin=".$end_stamp_begin;
	$param .= "&end_stamp_end=".$end_stamp_end;
	$param .= "&start_epoch=".$start_epoch;
	$param .= "&stop_epoch=".$stop_epoch;
	$param .= "&duration=".$duration;
	$param .= "&billsec=".$billsec;
	$param .= "&hangup_cause=".$hangup_cause;
	$param .= "&uuid=".$uuid;
	$param .= "&bleg_uuid=".$bleg_uuid;
	$param .= "&accountcode=".$accountcode;
	$param .= "&read_codec=".$read_codec;
	$param .= "&write_codec=".$write_codec;
	$param .= "&remote_media_ip=".$remote_media_ip;
	$param .= "&network_addr=".$network_addr;
	$param .= "&bridge_uuid=".$bridge_uuid;
	$param .= "&mos_comparison=".$mos_comparison;
	$param .= "&mos_score=".$mos_score;
	if ($_GET['showall'] == 'true' && permission_exists('xml_cdr_all')) {
		$param .= "&showall=true";
	}
	if (isset($order_by)) {
		$param .= "&order_by=".$order_by."&order=".$order;
	}

//create the sql query to get the xml cdr records
	if (strlen($order_by) == 0)  { $order_by  = "start_epoch"; }
	if (strlen($order) == 0)  { $order  = "desc"; }

//set a default number of rows to show
	$num_rows = '0';

//set a default CDR limit
	if (!isset($_SESSION['cdr']['limit']['numeric'])) {
		$_SESSION['cdr']['limit']['numeric'] = 800;
	}

//page results if rows_per_page is greater than zero
	if ($rows_per_page > 0) {
		//get the number of rows in the v_xml_cdr
			$sql = "select count(*) as num_rows from v_xml_cdr where domain_uuid = '".$domain_uuid."' ".$sql_where;
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

		//limit the number of results
			if ($num_rows > $_SESSION['cdr']['limit']['numeric']) {
				$num_rows = $_SESSION['cdr']['limit']['numeric'];
			}
			if ($rows_per_page > $_SESSION['cdr']['limit']['numeric']) {
				$rows_per_page = $_SESSION['cdr']['limit']['numeric'];
			}

		//prepare to page the results
			//$rows_per_page = 150; //set on the page that includes this page
			$page = $_GET['page'];
			if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
			list($paging_controls_mini, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page, true); //top
			list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); //bottom
			$offset = $rows_per_page * $page;
	}

//get the results from the db
	$sql = "select ";
	$sql .= "domain_uuid, ";
	$sql .= "start_stamp, ";
	$sql .= "start_epoch, ";
	$sql .= "hangup_cause, ";
	$sql .= "duration, ";
	$sql .= "billmsec, ";
	$sql .= "recording_file, ";
	$sql .= "uuid, ";
	$sql .= "bridge_uuid, ";
	$sql .= "direction, ";
	$sql .= "billsec, ";
	$sql .= "caller_id_name, ";
	$sql .= "caller_id_number, ";
	$sql .= "destination_number, ";
	$sql .= "accountcode, ";
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/billing/app_config.php")){
		$sql .= "call_sell, ";
	}
	if (permission_exists("xml_cdr_pdd")) {
		$sql .= "pdd_ms, ";
	}
	if (permission_exists("xml_cdr_mos")) {
		$sql .= "rtp_audio_in_mos, ";
	}
	$sql .= "(answer_epoch - start_epoch) as tta ";
	if ($_REQUEST['showall'] == "true" && permission_exists('xml_cdr_all')) {
		$sql .= ", domain_name ";
	}
	$sql .= "from v_xml_cdr ";
	if ($_REQUEST['showall'] == "true" && permission_exists('xml_cdr_all')) {
		if ($sql_where) { $sql .= "where "; }
	} else {
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
	}
	$sql .= $sql_where;
	if (strlen($order_by)> 0) { $sql .= " order by ".$order_by." ".$order." "; }
	if ($rows_per_page == 0) {
		$sql .= " limit ".$_SESSION['cdr']['limit']['numeric']." offset 0 ";
	}
	else {
		$sql .= " limit ".$rows_per_page." offset ".$offset." ";
	}
	$sql= str_replace("  ", " ", $sql);
	$sql= str_replace("where and", "where", $sql);
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	$row_style["2"] = "row_style2";

?>