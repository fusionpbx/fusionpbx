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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('xml_cdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set 24hr or 12hr clock
	define('TIME_24HR', 1);

//get post or get variables from http
	if (count($_REQUEST) > 0) {
		$cdr_id = $_REQUEST["cdr_id"];
		$missed = $_REQUEST["missed"];
		$direction = $_REQUEST["direction"];
		$caller_id_name = $_REQUEST["caller_id_name"];
		$caller_id_number = $_REQUEST["caller_id_number"];
		$caller_destination = $_REQUEST["caller_destination"];
		$extension_uuid = $_REQUEST["extension_uuid"];
		$destination_number = $_REQUEST["destination_number"];
		$context = $_REQUEST["context"];
		$start_stamp_begin = $_REQUEST["start_stamp_begin"];
		$start_stamp_end = $_REQUEST["start_stamp_end"];
		$answer_stamp_begin = $_REQUEST["answer_stamp_begin"];
		$answer_stamp_end = $_REQUEST["answer_stamp_end"];
		$end_stamp_begin = $_REQUEST["end_stamp_begin"];
		$end_stamp_end = $_REQUEST["end_stamp_end"];
		$start_epoch = $_REQUEST["start_epoch"];
		$stop_epoch = $_REQUEST["stop_epoch"];
		$duration_min = $_REQUEST["duration_min"];
		$duration_max = $_REQUEST["duration_max"];
		$billsec = $_REQUEST["billsec"];
		$hangup_cause = $_REQUEST["hangup_cause"];
		$call_result = $_REQUEST["call_result"];
		$xml_cdr_uuid = $_REQUEST["xml_cdr_uuid"];
		$bleg_uuid = $_REQUEST["bleg_uuid"];
		$accountcode = $_REQUEST["accountcode"];
		$read_codec = $_REQUEST["read_codec"];
		$write_codec = $_REQUEST["write_codec"];
		$remote_media_ip = $_REQUEST["remote_media_ip"];
		$network_addr = $_REQUEST["network_addr"];
		$bridge_uuid = $_REQUEST["network_addr"];
		$tta_min = $_REQUEST['tta_min'];
		$tta_max = $_REQUEST['tta_max'];
		$recording = $_REQUEST['recording'];
		$order_by = $_REQUEST["order_by"];
		$order = $_REQUEST["order"];
		if (is_array($_SESSION['cdr']['field'])) {
			foreach ($_SESSION['cdr']['field'] as $field) {
				$array = explode(",", $field);
				$field_name = end($array);
				if (isset($_REQUEST[$field_name])) {
					$$field_name = $_REQUEST[$field_name];
				}
			}
		}
		if (strlen($_REQUEST["mos_comparison"]) > 0) {
			switch($_REQUEST["mos_comparison"]) {
				case 'less': $mos_comparison = "<"; break;
				case 'greater': $mos_comparison = ">"; break;
				case 'lessorequal': $mos_comparison = "<="; break;
				case 'greaterorequal': $mos_comparison = ">="; break;
				case 'equal': $mos_comparison = "<"; break;
				case 'notequal': $mos_comparison = "<>"; break;
			}
		}
		else {
			$mos_comparison = '';
		}
		//$mos_comparison = $_REQUEST["mos_comparison"];
		$mos_score = $_REQUEST["mos_score"];
		$leg = $_REQUEST["leg"];
	}

//check to see if permission does not exist
	if (!permission_exists('xml_cdr_b_leg')) {
		$leg = 'a';
	}

//get variables used to control the order
	$order_by = $_REQUEST["order_by"];
	$order = $_REQUEST["order"];

//validate the order
	switch ($order) {
		case 'asc':
			break;
		case 'desc':
			break;
		default:
			$order = '';
	}

//set the assigned extensions
	if (!permission_exists('xml_cdr_domain') && is_array($_SESSION['user']['extension'])) {
		foreach ($_SESSION['user']['extension'] as $row) {
			if (is_uuid($row['extension_uuid'])) {
				$extension_uuids[] = $row['extension_uuid'];
			}
		}
	}

//set the param variable which is used with paging
	$param = "&cdr_id=".urlencode($cdr_id);
	$param .= "&missed=".urlencode($missed);
	$param .= "&direction=".urlencode($direction);
	$param .= "&caller_id_name=".urlencode($caller_id_name);
	$param .= "&caller_id_number=".urlencode($caller_id_number);
	$param .= "&caller_destination=".urlencode($caller_destination);
	$param .= "&extension_uuid=".urlencode($extension_uuid);
	$param .= "&destination_number=".urlencode($destination_number);
	$param .= "&context=".urlencode($context);
	$param .= "&start_stamp_begin=".urlencode($start_stamp_begin);
	$param .= "&start_stamp_end=".urlencode($start_stamp_end);
	$param .= "&answer_stamp_begin=".urlencode($answer_stamp_begin);
	$param .= "&answer_stamp_end=".urlencode($answer_stamp_end);
	$param .= "&end_stamp_begin=".urlencode($end_stamp_begin);
	$param .= "&end_stamp_end=".urlencode($end_stamp_end);
	$param .= "&start_epoch=".urlencode($start_epoch);
	$param .= "&stop_epoch=".urlencode($stop_epoch);
	$param .= "&duration_min=".urlencode($duration_min);
	$param .= "&duration_max=".urlencode($duration_max);
	$param .= "&billsec=".urlencode($billsec);
	$param .= "&hangup_cause=".urlencode($hangup_cause);
	$param .= "&call_result=".urlencode($call_result);
	$param .= "&xml_cdr_uuid=".urlencode($xml_cdr_uuid);
	$param .= "&bleg_uuid=".urlencode($bleg_uuid);
	$param .= "&accountcode=".urlencode($accountcode);
	$param .= "&read_codec=".urlencode($read_codec);
	$param .= "&write_codec=".urlencode($write_codec);
	$param .= "&remote_media_ip=".urlencode($remote_media_ip);
	$param .= "&network_addr=".urlencode($network_addr);
	$param .= "&bridge_uuid=".urlencode($bridge_uuid);
	$param .= "&mos_comparison=".urlencode($mos_comparison);
	$param .= "&mos_score=".urlencode($mos_score);
	$param .= "&tta_min=".urlencode($tta_min);
	$param .= "&tta_max=".urlencode($tta_max);
	$param .= "&recording=".urlencode($recording);
	if (is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			if (isset($$field_name)) {
				$param .= "&".$field_name."=".urlencode($$field_name);
			}
		}
	}
	if ($_GET['show'] == 'all' && permission_exists('xml_cdr_all')) {
		$param .= "&show=all";
	}
	if (isset($order_by)) {
		$param .= "&order_by=".urlencode($order_by)."&order=".urlencode($order);
	}

//create the sql query to get the xml cdr records
	if (strlen($order_by) == 0) { $order_by  = "start_stamp"; }
	if (strlen($order) == 0) { $order  = "desc"; }

//set a default number of rows to show
	$num_rows = '0';

//count the records in the database
	/*
	if ($_SESSION['cdr']['limit']['numeric'] == 0) {
		$sql = "select count(*) from v_xml_cdr ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= ".$sql_where;
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$num_rows = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);
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

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}
	$parameters['time_zone'] = $time_zone;

//get the results from the db
	$sql = "select \n";
	$sql .= "c.domain_uuid, \n";
	$sql .= "c.sip_call_id, \n";
	$sql .= "e.extension, \n";
	$sql .= "c.start_stamp, \n";
	$sql .= "c.end_stamp, \n";
	$sql .= "to_char(timezone(:time_zone, start_stamp), 'DD Mon YYYY') as start_date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, start_stamp), 'HH12:MI:SS am') as start_time_formatted, \n";
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
	$sql .= "c.cc_side, \n";
	//$sql .= "(c.xml is not null or c.json is not null) as raw_data_exists, \n";
	//$sql .= "c.json, \n";
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
	$sql .= "left join v_extensions as e on e.extension_uuid = c.extension_uuid \n";
	$sql .= "inner join v_domains as d on d.domain_uuid = c.domain_uuid \n";
	if ($_REQUEST['show'] == "all" && permission_exists('xml_cdr_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where c.domain_uuid = :domain_uuid \n";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!permission_exists('xml_cdr_domain')) { //only show the user their calls
		if (is_array($extension_uuids) && @sizeof($extension_uuids)) {
			$sql .= "and (c.extension_uuid = '".implode("' or c.extension_uuid = '", $extension_uuids)."') ";
		}
		else {
			$sql .= "and false ";
		}
	}
	if ($missed == true) {
		$sql .= "and missed_call = 1 \n";
	}
	if (strlen($start_epoch) > 0 && strlen($stop_epoch) > 0) {
		$sql .= "and start_epoch between :start_epoch and :stop_epoch \n";
		$parameters['start_epoch'] = $start_epoch;
		$parameters['stop_epoch'] = $stop_epoch;
	}
	if (strlen($cdr_id) > 0) { 
		$sql .= "and cdr_id like :cdr_id \n";
		$parameters['cdr_id'] = '%'.$cdr_id.'%';
	}
	if (strlen($direction) > 0) {
		$sql .= "and direction = :direction \n";
		$parameters['direction'] = $direction;
	}
	if (strlen($caller_id_name) > 0) {
		$mod_caller_id_name = str_replace("*", "%", $caller_id_name);
		if (strstr($mod_caller_id_name, '%')) {
			$sql .= "and caller_id_name like :caller_id_name \n";
			$parameters['caller_id_name'] = $mod_caller_id_name;
		}
		else {
			$sql .= "and caller_id_name = :caller_id_name \n";
			$parameters['caller_id_name'] = $mod_caller_id_name;
		}
	}
	if (strlen($caller_id_number) > 0) {
		$mod_caller_id_number = str_replace("*", "%", $caller_id_number);
		$mod_caller_id_number = preg_replace("#[^\+0-9.%/]#", "", $mod_caller_id_number);
		if (strstr($mod_caller_id_number, '%')) {
			$sql .= "and caller_id_number like :caller_id_number \n";
			$parameters['caller_id_number'] = $mod_caller_id_number;
		}
		else {
			$sql .= "and caller_id_number = :caller_id_number \n";
			$parameters['caller_id_number'] = $mod_caller_id_number;
		}
	}

	if (strlen($extension_uuid) > 0 && is_uuid($extension_uuid)) {
		$sql .= "and e.extension_uuid = :extension_uuid \n";
		$parameters['extension_uuid'] = $extension_uuid;
	}
	if (strlen($caller_destination) > 0) {
		$mod_caller_destination = str_replace("*", "%", $caller_destination);
		$mod_caller_destination = preg_replace("#[^\+0-9.%/]#", "", $mod_caller_destination);
		if (strstr($mod_caller_destination, '%')) {
			$sql .= "and caller_destination like :caller_destination \n";
			$parameters['caller_destination'] = $mod_caller_destination;
		}
		else {
			$sql .= "and caller_destination = :caller_destination \n";
			$parameters['caller_destination'] = $mod_caller_destination;
		}
	}
	if (strlen($destination_number) > 0) {
		$mod_destination_number = str_replace("*", "%", $destination_number);
		$mod_destination_number = preg_replace("#[^\+0-9.%/]#", "", $mod_destination_number);
		if (strstr($mod_destination_number, '%')) {
			$sql .= "and destination_number like :destination_number \n";
			$parameters['destination_number'] = $mod_destination_number;
		}
		else {
			$sql .= "and destination_number = :destination_number \n";
			$parameters['destination_number'] = $mod_destination_number;
		}
	}
	if (strlen($context) > 0) {
		$sql .= "and context like :context \n";
		$parameters['context'] = '%'.$context.'%';
	}
	if (is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			if (isset($$field_name)) {
				$$field_name = $_REQUEST[$field_name];
				if (strlen($$field_name) > 0) {
					if (strstr($$field_name, '%')) {
						$sql .= "and $field_name like :".$field_name." \n";
						$parameters[$field_name] = $$field_name;
					}
					else {
						$sql .= "and $field_name = :".$field_name." \n";
						$parameters[$field_name] = $$field_name;
					}
				}
			}
		}
	}

	if (strlen($start_stamp_begin) > 0 && strlen($start_stamp_end) > 0) {
		$sql .= "and start_stamp between :start_stamp_begin::timestamptz and :start_stamp_end::timestamptz ";
		$parameters['start_stamp_begin'] = $start_stamp_begin.':00.000 '.$time_zone;
		$parameters['start_stamp_end'] = $start_stamp_end.':59.999 '.$time_zone;
	}
	else {
		if (strlen($start_stamp_begin) > 0) {
			$sql .= "and start_stamp >= :start_stamp_begin ";
			$parameters['start_stamp_begin'] = $start_stamp_begin.':00.000 '.$time_zone;
		}
		if (strlen($start_stamp_end) > 0) {
			$sql .= "and start_stamp <= :start_stamp_end ";
			$parameters['start_stamp_end'] = $start_stamp_end.':59.999 '.$time_zone;
		}
	}
	if (strlen($answer_stamp_begin) > 0 && strlen($answer_stamp_end) > 0) {
		$sql .= "and answer_stamp between :answer_stamp_begin::timestamptz and :answer_stamp_end::timestamptz ";
		$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000 '.$time_zone;
		$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999 '.$time_zone;
	}
	else {
		if (strlen($answer_stamp_begin) > 0) {
			$sql .= "and answer_stamp >= :answer_stamp_begin ";
			$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000 '.$time_zone;;
		}
		if (strlen($answer_stamp_end) > 0) {
			$sql .= "and answer_stamp <= :answer_stamp_end "; 
			$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999 '.$time_zone;
		}
	}
	if (strlen($end_stamp_begin) > 0 && strlen($end_stamp_end) > 0) {
		$sql .= "and end_stamp between :end_stamp_begin::timestamptz and :end_stamp_end::timestamptz ";
		$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000 '.$time_zone;
		$parameters['end_stamp_end'] = $end_stamp_end.':59.999 '.$time_zone;
	}
	else {
		if (strlen($end_stamp_begin) > 0) {
			$sql .= "and end_stamp >= :end_stamp_begin ";
			$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000 '.$time_zone;
		}
		if (strlen($end_stamp_end) > 0) {
			$sql .= "and end_stamp <= :end_stamp_end ";
			$parameters['end_stamp'] = $end_stamp_end.':59.999 '.$time_zone;
		}
	}
	if (is_numeric($duration_min)) {
		$sql .= "and duration >= :duration_min ";
		$parameters['duration_min'] = $duration_min;
	}
	if (is_numeric($duration_max)) {
		$sql .= "and duration <= :duration_max ";
		$parameters['duration_max'] = $duration_max;
	}
	if (strlen($billsec) > 0) {
		$sql .= "and billsec like :billsec ";
		$parameters['billsec'] = '%'.$billsec.'%';
	}
	if (strlen($hangup_cause) > 0) {
		$sql .= "and hangup_cause like :hangup_cause ";
		$parameters['hangup_cause'] = '%'.$hangup_cause.'%';
	}
	elseif (!permission_exists('xml_cdr_lose_race') && !permission_exists('xml_cdr_enterprise_leg')) {
		$sql .= "and hangup_cause != 'LOSE_RACE' ";
	}
	//exclude enterprise ring group legs
	if (!permission_exists('xml_cdr_enterprise_leg')) {
		$sql .= "and originating_leg_uuid IS NULL ";
	}
	if (strlen($call_result) > 0) {
		switch ($call_result) {
			case 'answered':
				$sql .= "and (answer_stamp is not null and bridge_uuid is not null) ";
				break;
			case 'voicemail':
				$sql .= "and (answer_stamp is not null and bridge_uuid is null) ";
				break;
			case 'missed':
				$sql .= "and missed_call = '1' ";
				break;
			case 'cancelled':
				if ($direction == 'inbound' || $direction == 'local' || $call_result == 'missed') {
					$sql .= "
						and ((
							answer_stamp is null 
							and bridge_uuid is null 
							and sip_hangup_disposition <> 'send_refuse'
						)
						or (
							answer_stamp is not null 
							and bridge_uuid is null 
							and voicemail_message = false
						))";
				}
				else if ($direction == 'outbound') {
					$sql .= "and (answer_stamp is null and bridge_uuid is not null) ";
				}
				else {
					$sql .= "
						and ((
							(direction = 'inbound' or direction = 'local')
							and answer_stamp is null
							and bridge_uuid is null
							and sip_hangup_disposition <> 'send_refuse'
						)
						or (
							direction = 'outbound'
							and answer_stamp is null
							and bridge_uuid is not null
						)
						or (
							(direction = 'inbound' or direction = 'local')
							and answer_stamp is not null
							and bridge_uuid is null
							and voicemail_message = false
						))";
				}
				break;
			default: 
				$sql .= "and (answer_stamp is null and bridge_uuid is null and duration = 0) ";
				//$sql .= "and (answer_stamp is null and bridge_uuid is null and billsec = 0 and sip_hangup_disposition = 'send_refuse') ";
		}
	}
	if (strlen($xml_cdr_uuid) > 0) {
		$sql .= "and xml_cdr_uuid = :xml_cdr_uuid ";
		$parameters['xml_cdr_uuid'] = $xml_cdr_uuid;
	}
	if (strlen($bleg_uuid) > 0) {
		$sql .= "and bleg_uuid = :bleg_uuid ";
		$parameters['bleg_uuid'] = $bleg_uuid;
	}
	if (strlen($accountcode) > 0) {
		$sql .= "and c.accountcode = :accountcode ";
		$parameters['accountcode'] = $accountcode;
	}
	if (strlen($read_codec) > 0) {
		$sql .= "and read_codec like :read_codec ";
		$parameters['read_codec'] = '%'.$read_codec.'%';
	}
	if (strlen($write_codec) > 0) {
		$sql .= "and write_codec like :write_codec ";
		$parameters['write_codec'] = '%'.$write_codec.'%';
	}
	if (strlen($remote_media_ip) > 0) {
		$sql .= "and remote_media_ip like :remote_media_ip ";
		$parameters['remote_media_ip'] = $remote_media_ip;
	}
	if (strlen($network_addr) > 0) {
		$sql .= "and network_addr like :network_addr ";
		$parameters['network_addr'] = '%'.$network_addr.'%';
	}
	//if (strlen($mos_comparison) > 0 && strlen($mos_score) > 0 ) {
	//	$sql .= "and rtp_audio_in_mos = :mos_comparison :mos_score ";
	//	$parameters['mos_comparison'] = $mos_comparison;
	//	$parameters['mos_score'] = $mos_score;
	//}
	if (strlen($leg) > 0) {
		$sql .= "and leg = :leg ";
		$parameters['leg'] = $leg;
	}
	if (is_numeric($tta_min)) {
		$sql .= "and (c.answer_epoch - c.start_epoch) >= :tta_min ";
		$parameters['tta_min'] = $tta_min;
	}
	if (is_numeric($tta_max)) {
		$sql .= "and (c.answer_epoch - c.start_epoch) <= :tta_max ";
		$parameters['tta_max'] = $tta_max;
	}
	if ($recording == 'true' || $recording == 'false') {
		if ($recording == 'true') {
			$sql .= "and c.record_path is not null and c.record_name is not null ";
		}
		if ($recording == 'false') {
			$sql .= "and (c.record_path is null or c.record_name is null) ";
		}
	}
	//show agent originated legs only to those with the permission
	if (!permission_exists('xml_cdr_cc_agent_leg')) {
		$sql .= "and (cc_side is null or cc_side != 'agent') ";
	}
	//end where
	if (strlen($order_by) > 0) {
		$sql .= order_by($order_by, $order);
	}
	if ($_REQUEST['export_format'] !== "csv" && $_REQUEST['export_format'] !== "pdf") {
		if ($rows_per_page == 0) {
			$sql .= " limit :limit offset 0 \n";
			$parameters['limit'] = $_SESSION['cdr']['limit']['numeric'];
		}
		else {
			$sql .= " limit :limit offset :offset \n";
			$parameters['limit'] = $rows_per_page;
			$parameters['offset'] = $offset;
		}
	}
	$sql = str_replace("  ", " ", $sql);
	$database = new database;
	if ($archive_request && $_SESSION['cdr']['archive_database']['boolean'] == 'true') {
		$database->driver = $_SESSION['cdr']['archive_database_driver']['text'];
		$database->host = $_SESSION['cdr']['archive_database_host']['text'];
		$database->type = $_SESSION['cdr']['archive_database_type']['text'];
		$database->port = $_SESSION['cdr']['archive_database_port']['text'];
		$database->db_name = $_SESSION['cdr']['archive_database_name']['text'];
		$database->username = $_SESSION['cdr']['archive_database_username']['text'];
		$database->password = $_SESSION['cdr']['archive_database_password']['text'];
	}
	$result = $database->select($sql, $parameters, 'all');
	$result_count = is_array($result) ? sizeof($result) : 0;
	unset($database, $sql, $parameters);

//return the paging
	if ($_REQUEST['export_format'] !== "csv" && $_REQUEST['export_format'] !== "pdf") {
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true, $result_count); //top
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page, false, $result_count); //bottom
	}

?>
