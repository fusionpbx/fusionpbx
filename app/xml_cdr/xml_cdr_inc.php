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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
		$caller_destination = check_str($_REQUEST["caller_destination"]);
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
		$call_result = check_str($_REQUEST["call_result"]);
		$xml_cdr_uuid = check_str($_REQUEST["xml_cdr_uuid"]);
		$bleg_uuid = check_str($_REQUEST["bleg_uuid"]);
		$accountcode = check_str($_REQUEST["accountcode"]);
		$read_codec = check_str($_REQUEST["read_codec"]);
		$write_codec = check_str($_REQUEST["write_codec"]);
		$remote_media_ip = check_str($_REQUEST["remote_media_ip"]);
		$network_addr = check_str($_REQUEST["network_addr"]);
		$bridge_uuid = check_str($_REQUEST["network_addr"]);
		$order_by = check_str($_REQUEST["order_by"]);
		$order = check_str($_REQUEST["order"]);
		if (is_array($_SESSION['cdr']['field'])) {
			foreach ($_SESSION['cdr']['field'] as $field) {
				$array = explode(",", $field);
				$field_name = end($array);
				$pattern = '/\s+as\s+/i';
				if (preg_match($pattern, $field_name)){
					$field_pattern = preg_split ($pattern, $field_name);
					$field_name = $field_pattern[1];
				}
				if (isset($_REQUEST[$field_name])) {
					$$field_name = check_str($_REQUEST[$field_name]);
				}
			}
		}
		if (strlen(check_str($_REQUEST["mos_comparison"])) > 0) {
			switch(check_str($_REQUEST["mos_comparison"])) {
				case 'less': $mos_comparison = "<"; break;
				case 'greater': $mos_comparison = ">"; break;
				case 'lessorequal': $mos_comparison = "<="; break;
				case 'greaterorequal': $mos_comparison = ">="; break;
				case 'equal': $mos_comparison = "<"; break;
				case 'notequal': $mos_comparison = "<>"; break;
			}
		} else {
			$mos_comparison = '';
		}
		//$mos_comparison = check_str($_REQUEST["mos_comparison"]);
		$mos_score = check_str($_REQUEST["mos_score"]);
		$leg = check_str($_REQUEST["leg"]);
	}

	if(!permission_exists('xml_cdr_b_leg')){
		$leg = 'a';
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
	if (strlen($caller_extension_uuid) > 0 && is_uuid($caller_extension_uuid)) {
		$sql_where_ands[] = "e.extension_uuid = '".$caller_extension_uuid."'";
	}
	if (strlen($caller_id_number) > 0) {
                $mod_caller_id_number = str_replace("*", "%", $caller_id_number);
                $sql_where_ands[] = "caller_id_number like '".$mod_caller_id_number."'";
        }
	if (strlen($caller_destination) > 0) {
		$mod_caller_destination = str_replace("*", "%", $caller_destination);
		$sql_where_ands[] = "caller_destination like '".$mod_caller_destination."'";
	}
	if (strlen($destination_number) > 0) {
		$mod_destination_number = str_replace("*", "%", $destination_number);
		$sql_where_ands[] = "destination_number like '".$mod_destination_number."'";
	}
	if (strlen($context) > 0) { $sql_where_ands[] = "context like '%".$context."%'"; }

	if (is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			$pattern = '/\s+as\s+/i';
			if (preg_match($pattern, $field_name)){
				$field_pattern = preg_split ($pattern, $field_name);
				$field_name = $field_pattern[0];
			}
			if (isset($$field_name)) {
				$$field_name = check_str($_REQUEST[$field_name]);
				if (strlen($$field_name) > 0) {
					$sql_where_ands[] = "$field_name like '%".$$field_name."%'";
				}
			}
		}
	}

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
	if (strlen($call_result) > 0) {
		switch ($call_result) {
			case 'answered':
				$sql_where_ands[] = "(answer_stamp is not null and bridge_uuid is not null)";
				break;
			case 'voicemail':
				$sql_where_ands[] = "(answer_stamp is not null and bridge_uuid is null)";
				break;
			case 'missed':
				$sql_missed_1 = "( (answer_stamp is not null and bridge_uuid is null) or (";
				$sql_missed_2 = ") )";
			case 'cancelled':
				if ($direction == 'inbound' || $direction == 'local' || $call_result == 'missed') {
					$sql_where_ands_cancelled = "(answer_stamp is null and bridge_uuid is null and sip_hangup_disposition <> 'send_refuse')";
				}
				else if ($direction == 'outbound') {
					$sql_where_ands_cancelled = "(answer_stamp is null and bridge_uuid is not null)";
				}
				else {
					$sql_where_ands_cancelled = "
						((
							(direction = 'inbound' or direction = 'local')
							and answer_stamp is null
							and bridge_uuid is null
							and sip_hangup_disposition <> 'send_refuse'
						)
						or (
							direction = 'outbound'
							and answer_stamp is null
							and bridge_uuid is not null
						))";
				}
				$sql_where_ands[] = ($call_result == 'missed') ? $sql_missed_1.' '.$sql_where_ands_cancelled.' '.$sql_missed_2 : $sql_where_ands_cancelled;
				break;
			default: //failed
				$sql_where_ands[] = "(answer_stamp is null and bridge_uuid is null and billsec = 0 and sip_hangup_disposition = 'send_refuse')";
		}
	}
	if (strlen($xml_cdr_uuid) > 0) { $sql_where_ands[] = "xml_cdr_uuid = '".$xml_cdr_uuid."'"; }
	if (strlen($bleg_uuid) > 0) { $sql_where_ands[] = "bleg_uuid = '".$bleg_uuid."'"; }
	if (strlen($accountcode) > 0) { $sql_where_ands[] = "accountcode = '".$accountcode."'"; }
	if (strlen($read_codec) > 0) { $sql_where_ands[] = "read_codec like '%".$read_codec."%'"; }
	if (strlen($write_codec) > 0) { $sql_where_ands[] = "write_codec like '%".$write_codec."%'"; }
	if (strlen($remote_media_ip) > 0) { $sql_where_ands[] = "remote_media_ip like '%".$remote_media_ip."%'"; }
	if (strlen($network_addr) > 0) { $sql_where_ands[] = "network_addr like '%".$network_addr."%'"; }
	if (strlen($mos_comparison) > 0 && strlen($mos_score) > 0 ) { $sql_where_ands[] = "rtp_audio_in_mos " . $mos_comparison . " ".$mos_score.""; }
	if (strlen($leg) > 0) { $sql_where_ands[] = "leg='$leg'"; }

	//if not admin or superadmin, only show own calls
	if (!permission_exists('xml_cdr_domain')) {
		if (count($_SESSION['user']['extension']) > 0) { // extensions are assigned to this user
			foreach ($_SESSION['user']['extension'] as $row) {
				$sql_where_ors[] = "c.extension_uuid = '".$row['extension_uuid']."'";
			}

			// create simple user extension array
			foreach ($_SESSION['user']['extension'] as $row) {
				$user_extensions[] = $row['user'];
			}

			// if both a source and destination are submitted, but neither are an assigned extension, restrict results
			if (
				$caller_id_number != '' &&
				$destination_number != '' &&
				array_search($caller_id_number, $user_extensions) === false &&
				array_search($destination_number, $user_extensions) === false
				) {
				$sql_where_ors[] = "caller_id_number = '".$user_extension."'";
				$sql_where_ors[] = "destination_number = '".$user_extension."'";
				$sql_where_ors[] = "destination_number = '*99".$user_extension."'";
			}
			// if source submitted is blank, implement restriction for assigned extension(s)
			if ($caller_id_number == '') { // if source criteria is blank, then restrict to assigned ext
				foreach ($user_extensions as $user_extension) {
					if (strlen($user_extension) > 0) {	$sql_where_ors[] = "caller_id_number = '".$user_extension."'"; }
				}
			}

			// if destination submitted is blank, implement restriction for assigned extension(s)
			if ($destination_number == '') {
				foreach ($user_extensions as $user_extension) {
					if (strlen($user_extension) > 0) {
						$sql_where_ors[] = "destination_number = '".$user_extension."'";
						$sql_where_ors[] = "destination_number = '*99".$user_extension."'";
					}
				}
			}

			// concatenate the 'or's array, then add to the 'and's array
			if ((is_array($sql_where_ors)) && (sizeof($sql_where_ors) > 0)) {
				$sql_where_ands[] = "( ".implode(" or ", $sql_where_ors)." )";
			}
		}
		else {
			$sql_where_ands[] = "1 <> 1"; //disable viewing of cdr records by users with no assigned extensions
		}
	}

	// concatenate the 'ands's array, add to where clause
	if ((is_array($sql_where_ands)) && (sizeof($sql_where_ands) > 0)) {
		$sql_where = " and ".implode(" and ", $sql_where_ands);
	}

//set the param variable which is used with paging
	$param = "&cdr_id=".escape($cdr_id);
	$param .= "&missed=".escape($missed);
	$param .= "&direction=".escape($direction);
	$param .= "&caller_id_name=".escape($caller_id_name);
	$param .= "&caller_id_number=".escape($caller_id_number);
	$param .= "&caller_destination=".escape($caller_destination);
	$param .= "&caller_extension_uuid=".escape($caller_extension_uuid);
	$param .= "&destination_number=".escape($destination_number);
	$param .= "&context=".escape($context);
	$param .= "&start_stamp_begin=".escape($start_stamp_begin);
	$param .= "&start_stamp_end=".escape($start_stamp_end);
	$param .= "&answer_stamp_begin=".escape($answer_stamp_begin);
	$param .= "&answer_stamp_end=".escape($answer_stamp_end);
	$param .= "&end_stamp_begin=".escape($end_stamp_begin);
	$param .= "&end_stamp_end=".escape($end_stamp_end);
	$param .= "&start_epoch=".escape($start_epoch);
	$param .= "&stop_epoch=".escape($stop_epoch);
	$param .= "&duration=".escape($duration);
	$param .= "&billsec=".escape($billsec);
	$param .= "&hangup_cause=".escape($hangup_cause);
	$param .= "&call_result=".escape($call_result);
	$param .= "&xml_cdr_uuid=".escape($xml_cdr_uuid);
	$param .= "&bleg_uuid=".escape($bleg_uuid);
	$param .= "&accountcode=".escape($accountcode);
	$param .= "&read_codec=".escape($read_codec);
	$param .= "&write_codec=".escape($write_codec);
	$param .= "&remote_media_ip=".escape($remote_media_ip);
	$param .= "&network_addr=".escape($network_addr);
	$param .= "&bridge_uuid=".escape($bridge_uuid);
	$param .= "&mos_comparison=".escape($mos_comparison);
	$param .= "&mos_score=".escape($mos_score);
	if (is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			$pattern = '/\s+as\s+/i';
			if (preg_match($pattern, $field_name)){
				$field_pattern = preg_split ($pattern, $field_name);
				$field_name = $field_pattern[1];
			}
			if (isset($$field_name)) {
				$param .= "&".$field_name."=".escape($$field_name);
			}
		}
	}
	if ($_GET['show'] == 'all' && permission_exists('xml_cdr_all')) {
		$param .= "&show=all";
	}
	if (isset($order_by)) {
		$param .= "&order_by=".escape($order_by)."&order=".escape($order);
	}

//create the sql query to get the xml cdr records
	if (strlen($order_by) == 0)  { $order_by  = "start_epoch"; }
	if (strlen($order) == 0)  { $order  = "desc"; }

//set a default number of rows to show
	$num_rows = '0';

//disable the paging
	if ($_REQUEST['export_format'] == "csv") { $rows_per_page = 0; }
	if ($_REQUEST['export_format'] == "pdf") { $rows_per_page = 0; }

//count the records in the database
	/*
	if ($_SESSION['cdr']['limit']['numeric'] == 0) {
		$sql = "select count(xml_cdr_uuid) as num_rows from v_xml_cdr ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ".$sql_where;
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
	}
	*/

//limit the number of results
	if ($_SESSION['cdr']['limit']['numeric'] > 0) {
		$num_rows = $_SESSION['cdr']['limit']['numeric'];
	}

//set the default paging
	$rows_per_page = $_SESSION['domain']['paging']['numeric'];

//prepare to page the results
	//$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50; //set on the page that includes this page
	if (is_numeric($_GET['page'])) { $page = $_GET['page']; }
	if (!isset($_GET['page'])) { $page = 0; $_GET['page'] = 0; }
	$offset = $rows_per_page * $page;

//get the results from the db
	$sql = "select \n";
	$sql .= "c.domain_uuid, \n";
	$sql .= "e.extension, \n";
	$sql .= "c.start_stamp, \n";
	$sql .= "c.end_stamp, \n";
	$sql .= "c.start_epoch, \n";
	$sql .= "c.hangup_cause, \n";
	$sql .= "c.duration, \n";
	$sql .= "c.billmsec, \n";
	$sql .= "c.record_path, \n";
	$sql .= "c.record_name, \n";
	$sql .= "c.xml_cdr_uuid, \n";
	$sql .= "c.bridge_uuid, \n";
	$sql .= "c.direction, \n";
	$sql .= "c.billsec, \n";
	$sql .= "c.caller_id_name, \n";
	$sql .= "c.caller_id_number, \n";
	$sql .= "c.caller_destination, \n";
	$sql .= "c.source_number, \n";
	$sql .= "c.destination_number, \n";
	$sql .= "c.leg, \n";
	$sql .= "(c.xml IS NOT NULL OR c.json IS NOT NULL) AS raw_data_exists, \n";
	if (is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			$sql .= $field_name.", \n";
		}
	}
	if (is_array($_SESSION['cdr']['export'])) {
		foreach ($_SESSION['cdr']['export'] as $field) {
			$sql .= $field.", \n";
		}
	}
	$sql .= "c.accountcode, \n";
	$sql .= "c.answer_stamp, \n";
	$sql .= "c.sip_hangup_disposition, \n";
	if (permission_exists("xml_cdr_pdd")) {
		$sql .= "c.pdd_ms, \n";
	}
	if (permission_exists("xml_cdr_mos")) {
		$sql .= "c.rtp_audio_in_mos, \n";
	}
	$sql .= "(c.answer_epoch - c.start_epoch) as tta ";
	if ($_REQUEST['show'] == "all" && permission_exists('xml_cdr_all')) {
		$sql .= ", c.domain_name \n";
	}
	$sql .= "from v_xml_cdr as c \n";
	$sql .= "left join v_extensions as e on e.extension_uuid = c.extension_uuid ";
	$sql .= "inner join v_domains as d on d.domain_uuid = c.domain_uuid ";
	if ($_REQUEST['show'] == "all" && permission_exists('xml_cdr_all')) {
		if ($sql_where) { $sql .= "where "; }
	} else {
		$sql .= "where c.domain_uuid = '".$domain_uuid."' \n";
	}
	$sql .= $sql_where;
	if (strlen($order_by)> 0) { $sql .= " order by ".$order_by." ".$order." "; }
	if ($_REQUEST['export_format'] != "csv" && $_REQUEST['export_format'] != "pdf") {
		if ($rows_per_page == 0) {
			$sql .= " limit ".$_SESSION['cdr']['limit']['numeric']." offset 0 \n";
		}
		else {
			$sql .= " limit ".$rows_per_page." offset ".$offset." \n";
		}
	}
	$sql= str_replace("  ", " ", $sql);
	$sql= str_replace("where and", "where", $sql);
	$database = new database;
	if ($archive_request == 'true') {
		if ($_SESSION['cdr']['archive_database']['boolean'] == 'true') {
			$database->driver = $_SESSION['cdr']['archive_database_driver']['text'];
			$database->host = $_SESSION['cdr']['archive_database_host']['text'];
			$database->type = $_SESSION['cdr']['archive_database_type']['text'];
			$database->port = $_SESSION['cdr']['archive_database_port']['text'];
			$database->db_name = $_SESSION['cdr']['archive_database_name']['text'];
			$database->username = $_SESSION['cdr']['archive_database_username']['text'];
			$database->password = $_SESSION['cdr']['archive_database_password']['text'];
		}
	}
	$database->select($sql);
	$result = $database->result;
	$result_count = count($result);
	unset($database);

//return the paging
	list($paging_controls_mini, $rows_per_page, $offset) = paging($num_rows, $param, $rows_per_page, true, $result_count); //top
	list($paging_controls, $rows_per_page, $offset) = paging($num_rows, $param, $rows_per_page, false, $result_count); //bottom

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

?>
