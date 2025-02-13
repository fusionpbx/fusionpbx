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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Tony Fernandez <tfernandez@smartip.ca>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
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

//set permissions
	$permission = array();
	$permission['xml_cdr_view'] = permission_exists('xml_cdr_view');
	$permission['xml_cdr_search_extension'] = permission_exists('xml_cdr_search_extension');
	$permission['xml_cdr_delete'] = permission_exists('xml_cdr_delete');
	$permission['xml_cdr_domain'] = permission_exists('xml_cdr_domain');
	$permission['xml_cdr_search_call_center_queues'] = permission_exists('xml_cdr_search_call_center_queues');
	$permission['xml_cdr_search_ring_groups'] = permission_exists('xml_cdr_search_ring_groups');
	$permission['xml_cdr_statistics'] = permission_exists('xml_cdr_statistics');
	$permission['xml_cdr_archive'] = permission_exists('xml_cdr_archive');
	$permission['xml_cdr_all'] = permission_exists('xml_cdr_all');
	$permission['xml_cdr_export'] = permission_exists('xml_cdr_export');
	$permission['xml_cdr_export_csv'] = permission_exists('xml_cdr_export_csv');
	$permission['xml_cdr_export_pdf'] = permission_exists('xml_cdr_export_pdf');
	$permission['xml_cdr_search'] = permission_exists('xml_cdr_search');
	$permission['xml_cdr_search_direction'] = permission_exists('xml_cdr_search_direction');
	$permission['xml_cdr_b_leg'] = permission_exists('xml_cdr_b_leg');
	$permission['xml_cdr_search_status'] = permission_exists('xml_cdr_search_status');
	$permission['xml_cdr_search_caller_id'] = permission_exists('xml_cdr_search_caller_id');
	$permission['xml_cdr_search_start_range'] = permission_exists('xml_cdr_search_start_range');
	$permission['xml_cdr_search_duration'] = permission_exists('xml_cdr_search_duration');
	$permission['xml_cdr_search_caller_destination'] = permission_exists('xml_cdr_search_caller_destination');
	$permission['xml_cdr_search_destination'] = permission_exists('xml_cdr_search_destination');
	$permission['xml_cdr_codecs'] = permission_exists('xml_cdr_codecs');
	$permission['xml_cdr_search_tta'] = permission_exists('xml_cdr_search_tta');
	$permission['xml_cdr_search_hangup_cause'] = permission_exists('xml_cdr_search_hangup_cause');
	$permission['xml_cdr_search_recording'] = permission_exists('xml_cdr_search_recording');
	$permission['xml_cdr_search_order'] = permission_exists('xml_cdr_search_order');
	$permission['xml_cdr_extension'] = permission_exists('xml_cdr_extension');
	$permission['xml_cdr_caller_id_name'] = permission_exists('xml_cdr_caller_id_name');
	$permission['xml_cdr_caller_id_number'] = permission_exists('xml_cdr_caller_id_number');
	$permission['xml_cdr_caller_destination'] = permission_exists('xml_cdr_caller_destination');
	$permission['xml_cdr_destination'] = permission_exists('xml_cdr_destination');
	$permission['xml_cdr_start'] = permission_exists('xml_cdr_start');
	$permission['xml_cdr_tta'] = permission_exists('xml_cdr_tta');
	$permission['xml_cdr_duration'] = permission_exists('xml_cdr_duration');
	$permission['xml_cdr_pdd'] = permission_exists('xml_cdr_pdd');
	$permission['xml_cdr_mos'] = permission_exists('xml_cdr_mos');
	$permission['xml_cdr_hangup_cause'] = permission_exists('xml_cdr_hangup_cause');
	$permission['xml_cdr_custom_fields'] = permission_exists('xml_cdr_custom_fields');
	$permission['xml_cdr_search_advanced'] = permission_exists('xml_cdr_search_advanced');
	$permission['xml_cdr_direction'] = permission_exists('xml_cdr_direction');
	$permission['xml_cdr_recording'] = permission_exists('xml_cdr_recording');
	$permission['xml_cdr_recording_play'] = permission_exists('xml_cdr_recording_play');
	$permission['xml_cdr_recording_download'] = permission_exists('xml_cdr_recording_download');
	$permission['xml_cdr_account_code'] = permission_exists('xml_cdr_account_code');
	$permission['xml_cdr_status'] = permission_exists('xml_cdr_status');
	$permission['xml_cdr_details'] = permission_exists('xml_cdr_details');
	$permission['xml_cdr_lose_race'] = permission_exists('xml_cdr_lose_race');
	$permission['xml_cdr_cc_agent_leg'] = permission_exists('xml_cdr_cc_agent_leg');
	$permission['xml_cdr_cc_side'] = permission_exists('xml_cdr_cc_side');
	$permission['xml_cdr_call_center_queues'] = permission_exists('xml_cdr_call_center_queues');

//set 24hr or 12hr clock
	define('TIME_24HR', 1);

//set defaults
	if(empty($_GET['show'])) {
		$_GET['show'] = 'false';
	}

//connect to database
	$database = database::new();

//get post or get variables from http
	if (!empty($_REQUEST)) {
		$cdr_id = $_REQUEST["cdr_id"] ?? '';
		$direction = $_REQUEST["direction"] ?? '';
		$caller_id_name = $_REQUEST["caller_id_name"] ?? '';
		$caller_id_number = $_REQUEST["caller_id_number"] ?? '';
		$caller_destination = $_REQUEST["caller_destination"] ?? '';
		$extension_uuid = $_REQUEST["extension_uuid"] ?? '';
		$destination_number = $_REQUEST["destination_number"] ?? '';
		$context = $_REQUEST["context"] ?? '';
		$start_stamp_begin = $_REQUEST["start_stamp_begin"] ?? '';
		$start_stamp_end = $_REQUEST["start_stamp_end"] ?? '';
		$answer_stamp_begin = $_REQUEST["answer_stamp_begin"] ?? '';
		$answer_stamp_end = $_REQUEST["answer_stamp_end"] ?? '';
		$end_stamp_begin = $_REQUEST["end_stamp_begin"] ?? '';
		$end_stamp_end = $_REQUEST["end_stamp_end"] ?? '';
		$start_epoch = $_REQUEST["start_epoch"] ?? '';
		$stop_epoch = $_REQUEST["stop_epoch"] ?? '';
		$duration_min = $_REQUEST["duration_min"] ?? '';
		$duration_max = $_REQUEST["duration_max"] ?? '';
		$billsec = $_REQUEST["billsec"] ?? '';
		$hangup_cause = $_REQUEST["hangup_cause"] ?? '';
		$status = $_REQUEST["status"] ?? '';
		$xml_cdr_uuid = $_REQUEST["xml_cdr_uuid"] ?? '';
		$bleg_uuid = $_REQUEST["bleg_uuid"] ?? '';
		$accountcode = $_REQUEST["accountcode"] ?? '';
		$read_codec = $_REQUEST["read_codec"] ?? '';
		$write_codec = $_REQUEST["write_codec"] ?? '';
		$remote_media_ip = $_REQUEST["remote_media_ip"] ?? '';
		$network_addr = $_REQUEST["network_addr"] ?? '';
		$bridge_uuid = $_REQUEST["network_addr"] ?? '';
		$tta_min = $_REQUEST['tta_min'] ?? '';
		$tta_max = $_REQUEST['tta_max'] ?? '';
		$recording = $_REQUEST['recording'] ?? '';
		$order_by = $_REQUEST["order_by"] ?? '';
		$order = $_REQUEST["order"] ?? '';
		$cc_side = $_REQUEST["cc_side"] ?? '';
		$call_center_queue_uuid = $_REQUEST["call_center_queue_uuid"] ?? '';
		$ring_group_uuid = $_REQUEST["ring_group_uuid"] ?? '';
		if (isset($_SESSION['cdr']['field']) && is_array($_SESSION['cdr']['field'])) {
			foreach ($_SESSION['cdr']['field'] as $field) {
				$array = explode(",", $field);
				$field_name = end($array);
				if (isset($_REQUEST[$field_name])) {
					$$field_name = $_REQUEST[$field_name];
				}
			}
		}
		if (!empty($_REQUEST["mos_comparison"])) {
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
		$mos_score = $_REQUEST["mos_score"] ?? '';
		$leg = $_REQUEST["leg"] ?? 'a';
	}

//check to see if permission does not exist
	if (!$permission['xml_cdr_b_leg']) {
		$leg = 'a';
	}

//set the export_format
	if (isset($_REQUEST['export_format'])) {
		$export_format = $_REQUEST['export_format'];
	}
	else {
		$export_format = '';
	}

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
	if (!$permission['xml_cdr_domain'] && isset($_SESSION['user']['extension']) && is_array($_SESSION['user']['extension'])) {
		foreach ($_SESSION['user']['extension'] as $row) {
			if (is_uuid($row['extension_uuid'])) {
				$extension_uuids[] = $row['extension_uuid'];
			}
		}
	}

//set the param variable which is used with paging
	$param = "&cdr_id=".urlencode($cdr_id ?? '');
	$param .= "&missed=".urlencode($missed ?? '');
	$param .= "&direction=".urlencode($direction ?? '');
	$param .= "&caller_id_name=".urlencode($caller_id_name ?? '');
	$param .= "&caller_id_number=".urlencode($caller_id_number ?? '');
	$param .= "&caller_destination=".urlencode($caller_destination ?? '');
	$param .= "&extension_uuid=".urlencode($extension_uuid ?? '');
	$param .= "&destination_number=".urlencode($destination_number ?? '');
	$param .= "&context=".urlencode($context ?? '');
	$param .= "&start_stamp_begin=".urlencode($start_stamp_begin ?? '');
	$param .= "&start_stamp_end=".urlencode($start_stamp_end ?? '');
	$param .= "&answer_stamp_begin=".urlencode($answer_stamp_begin ?? '');
	$param .= "&answer_stamp_end=".urlencode($answer_stamp_end ?? '');
	$param .= "&end_stamp_begin=".urlencode($end_stamp_begin ?? '');
	$param .= "&end_stamp_end=".urlencode($end_stamp_end ?? '');
	$param .= "&start_epoch=".urlencode($start_epoch ?? '');
	$param .= "&stop_epoch=".urlencode($stop_epoch ?? '');
	$param .= "&duration_min=".urlencode($duration_min ?? '');
	$param .= "&duration_max=".urlencode($duration_max ?? '');
	$param .= "&billsec=".urlencode($billsec ?? '');
	$param .= "&hangup_cause=".urlencode($hangup_cause ?? '');
	$param .= "&status=".urlencode($status ?? '');
	$param .= "&xml_cdr_uuid=".urlencode($xml_cdr_uuid ?? '');
	$param .= "&bleg_uuid=".urlencode($bleg_uuid ?? '');
	$param .= "&accountcode=".urlencode($accountcode ?? '');
	$param .= "&read_codec=".urlencode($read_codec ?? '');
	$param .= "&write_codec=".urlencode($write_codec ?? '');
	$param .= "&remote_media_ip=".urlencode($remote_media_ip ?? '');
	$param .= "&network_addr=".urlencode($network_addr ?? '');
	$param .= "&bridge_uuid=".urlencode($bridge_uuid ?? '');
	$param .= "&mos_comparison=".urlencode($mos_comparison ?? '');
	$param .= "&mos_score=".urlencode($mos_score ?? '');
	$param .= "&tta_min=".urlencode($tta_min ?? '');
	$param .= "&tta_max=".urlencode($tta_max ?? '');
	$param .= "&recording=".urlencode($recording ?? '');
	$param .= "&cc_side=".urlencode($cc_side ?? '');
	$param .= "&call_center_queue_uuid=".urlencode($call_center_queue_uuid ?? '');

	if (isset($_SESSION['cdr']['field']) && is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			if (isset($$field_name)) {
				$param .= "&".$field_name."=".urlencode($$field_name);
			}
		}
	}
	if ($_GET['show'] == 'all' && $permission['xml_cdr_all']) {
		$param .= "&show=all";
	}
	if (!empty($order_by)) {
		$param .= "&order_by=".urlencode($order_by)."&order=".urlencode($order);
	}

//create the sql query to get the xml cdr records
	if (empty($order_by)) { $order_by  = "start_stamp"; }
	if (empty($order)) { $order  = "desc"; }

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
	if (!empty($_SESSION['cdr']['limit']['numeric']) && $_SESSION['cdr']['limit']['numeric'] > 0) {
		$num_rows = $_SESSION['cdr']['limit']['numeric'];
	}

//set the default paging
	//$rows_per_page = $_SESSION['domain']['paging']['numeric'];

//prepare to page the results
	//$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50; //set on the page that includes this page
	if (empty($_GET['page']) || (!empty($_GET['page']) && !is_numeric($_GET['page']))) {
		$_GET['page'] = 0;
	}
	//ensure page is within bounds of integer
	$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
	$offset = $rows_per_page * $page;

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}
	$parameters['time_zone'] = $time_zone;

//set the sql time format
	$sql_time_format = 'HH12:MI am';
	if (!empty($_SESSION['domain']['time_format']['text'])) {
		$sql_time_format = $_SESSION['domain']['time_format']['text'] == '12h' ? "HH12:MI am" : "HH24:MI";
	}

//get the results from the db
	$sql = "select \n";
	$sql .= "c.domain_uuid, \n";
	$sql .= "c.sip_call_id, \n";
	$sql .= "e.extension, \n";
	$sql .= "e.effective_caller_id_name as extension_name, \n";
	$sql .= "c.start_stamp, \n";
	$sql .= "c.end_stamp, \n";
	$sql .= "to_char(timezone(:time_zone, start_stamp), 'DD Mon YYYY') as start_date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, start_stamp), '".$sql_time_format."') as start_time_formatted, \n";
	$sql .= "c.start_epoch, \n";
	$sql .= "c.hangup_cause, \n";
	$sql .= "c.billsec as duration, \n";
	$sql .= "c.billmsec, \n";
	$sql .= "c.missed_call, \n";
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
	$sql .= "c.read_codec, \n";
	$sql .= "c.write_codec, \n";
	$sql .= "c.cc_side, \n";
	//$sql .= "(c.xml is not null or c.json is not null) as raw_data_exists, \n";
	//$sql .= "c.json, \n";
	if (isset($_SESSION['cdr']['field']) && is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			$sql .= $field_name.", \n";
		}
	}
	if (isset($_SESSION['cdr']['export']) && is_array($_SESSION['cdr']['export'])) {
		foreach ($_SESSION['cdr']['export'] as $field) {
			$sql .= $field.", \n";
		}
	}
	if ($permission['xml_cdr_account_code']) {
		$sql .= "c.accountcode, \n";
	}
	$sql .= "c.answer_stamp, \n";
	$sql .= "c.status, \n";
	$sql .= "c.sip_hangup_disposition, \n";
	if ($permission['xml_cdr_pdd']) {
		$sql .= "c.pdd_ms, \n";
	}
	if ($permission['xml_cdr_mos']) {
		$sql .= "c.rtp_audio_in_mos, \n";
	}
	$sql .= "(c.answer_epoch - c.start_epoch) as tta ";
	if (!empty($_REQUEST['show']) && $_REQUEST['show'] == "all" && $permission['xml_cdr_all']) {
		$sql .= ", c.domain_name \n";
	}
	$sql .= "from v_xml_cdr as c \n";
	$sql .= "left join v_extensions as e on e.extension_uuid = c.extension_uuid \n";
	$sql .= "inner join v_domains as d on d.domain_uuid = c.domain_uuid \n";
	if (!empty($_REQUEST['show']) && $_REQUEST['show'] == "all" && $permission['xml_cdr_all']) {
		$sql .= "where true \n";
	}
	else {
		$sql .= "where c.domain_uuid = :domain_uuid \n";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!$permission['xml_cdr_domain']) { //only show the user their calls
		if (isset($extension_uuids) && is_array($extension_uuids) && @sizeof($extension_uuids)) {
			$sql .= "and (c.extension_uuid = '".implode("' or c.extension_uuid = '", $extension_uuids)."') \n";
		}
		else {
			$sql .= "and false \n";
		}
	}
	if (!empty($start_epoch) && !empty($stop_epoch)) {
		$sql .= "and start_epoch between :start_epoch and :stop_epoch \n";
		$parameters['start_epoch'] = $start_epoch;
		$parameters['stop_epoch'] = $stop_epoch;
	}
	if (!empty($cdr_id)) {
		$sql .= "and cdr_id like :cdr_id \n";
		$parameters['cdr_id'] = '%'.$cdr_id.'%';
	}
	if (!empty($direction)) {
		$sql .= "and direction = :direction \n";
		$parameters['direction'] = $direction;
	}
	if (!empty($caller_id_name)) {
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
	if (!empty($caller_id_number)) {
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

	if (!empty($extension_uuid) && is_uuid($extension_uuid)) {
		$sql .= "and e.extension_uuid = :extension_uuid \n";
		$parameters['extension_uuid'] = $extension_uuid;
	}
	if (!empty($caller_destination)) {
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
	if (!empty($destination_number)) {
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
	if (!empty($context)) {
		$sql .= "and context like :context \n";
		$parameters['context'] = '%'.$context.'%';
	}
	if (!empty($_SESSION['cdr']['field']) && is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			if (isset($$field_name)) {
				$$field_name = $_REQUEST[$field_name];
				if (!empty($$field_name)) {
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

	if (!empty($start_stamp_begin) && !empty($start_stamp_end)) {
		$sql .= "and start_stamp between :start_stamp_begin::timestamptz and :start_stamp_end::timestamptz \n";
		$parameters['start_stamp_begin'] = $start_stamp_begin.':00.000 '.$time_zone;
		$parameters['start_stamp_end'] = $start_stamp_end.':59.999 '.$time_zone;
	}
	else {
		if (!empty($start_stamp_begin)) {
			$sql .= "and start_stamp >= :start_stamp_begin \n";
			$parameters['start_stamp_begin'] = $start_stamp_begin.':00.000 '.$time_zone;
		}
		if (!empty($start_stamp_end)) {
			$sql .= "and start_stamp <= :start_stamp_end \n";
			$parameters['start_stamp_end'] = $start_stamp_end.':59.999 '.$time_zone;
		}
	}
	if (!empty($answer_stamp_begin) && !empty($answer_stamp_end)) {
		$sql .= "and answer_stamp between :answer_stamp_begin::timestamptz and :answer_stamp_end::timestamptz \n";
		$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000 '.$time_zone;
		$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999 '.$time_zone;
	}
	else {
		if (!empty($answer_stamp_begin)) {
			$sql .= "and answer_stamp >= :answer_stamp_begin \n";
			$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000 '.$time_zone;;
		}
		if (!empty($answer_stamp_end)) {
			$sql .= "and answer_stamp <= :answer_stamp_end \n";
			$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999 '.$time_zone;
		}
	}
	if (!empty($end_stamp_begin) && !empty($end_stamp_end)) {
		$sql .= "and end_stamp between :end_stamp_begin::timestamptz and :end_stamp_end::timestamptz \n";
		$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000 '.$time_zone;
		$parameters['end_stamp_end'] = $end_stamp_end.':59.999 '.$time_zone;
	}
	else {
		if (!empty($end_stamp_begin)) {
			$sql .= "and end_stamp >= :end_stamp_begin \n";
			$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000 '.$time_zone;
		}
		if (!empty($end_stamp_end)) {
			$sql .= "and end_stamp <= :end_stamp_end \n";
			$parameters['end_stamp'] = $end_stamp_end.':59.999 '.$time_zone;
		}
	}
	if (is_numeric($duration_min)) {
		$sql .= "and billsec >= :duration_min \n";
		$parameters['duration_min'] = $duration_min;
	}
	if (is_numeric($duration_max)) {
		$sql .= "and billsec <= :duration_max \n";
		$parameters['duration_max'] = $duration_max;
	}
	if (!empty($billsec)) {
		$sql .= "and billsec like :billsec \n";
		$parameters['billsec'] = '%'.$billsec.'%';
	}
	if (!empty($hangup_cause)) {
		$sql .= "and hangup_cause like :hangup_cause \n";
		$parameters['hangup_cause'] = '%'.$hangup_cause.'%';
	}

	//exclude ring group legs that were not answered
	if (!$permission['xml_cdr_lose_race']) {
		$sql .= "and hangup_cause != 'LOSE_RACE' \n";
	}
	if (!empty($status)) {
		$sql .= "and status = :status \n";
		$parameters['status'] = $status;
	}
	if (!empty($xml_cdr_uuid)) {
		$sql .= "and xml_cdr_uuid = :xml_cdr_uuid \n";
		$parameters['xml_cdr_uuid'] = $xml_cdr_uuid;
	}
	if (!empty($bleg_uuid)) {
		$sql .= "and bleg_uuid = :bleg_uuid \n";
		$parameters['bleg_uuid'] = $bleg_uuid;
	}
	if ($permission['xml_cdr_account_code'] && !empty($accountcode)) {
		$sql .= "and c.accountcode = :accountcode \n";
		$parameters['accountcode'] = $accountcode;
	}
	if (!empty($read_codec)) {
		$sql .= "and read_codec like :read_codec \n";
		$parameters['read_codec'] = '%'.$read_codec.'%';
	}
	if (!empty($write_codec)) {
		$sql .= "and write_codec like :write_codec \n";
		$parameters['write_codec'] = '%'.$write_codec.'%';
	}
	if (!empty($remote_media_ip)) {
		$sql .= "and remote_media_ip like :remote_media_ip \n";
		$parameters['remote_media_ip'] = $remote_media_ip;
	}
	if (!empty($network_addr)) {
		$sql .= "and network_addr like :network_addr \n";
		$parameters['network_addr'] = '%'.$network_addr.'%';
	}
	//if (strlen($mos_comparison) > 0 && !empty($mos_score) ) {
	//	$sql .= "and rtp_audio_in_mos = :mos_comparison :mos_score ";
	//	$parameters['mos_comparison'] = $mos_comparison;
	//	$parameters['mos_score'] = $mos_score;
	//}
	if (!empty($leg)) {
		$sql .= "and leg = :leg \n";
		$parameters['leg'] = $leg;
	}
	if (is_numeric($tta_min)) {
		$sql .= "and (c.answer_epoch - c.start_epoch) >= :tta_min \n";
		$parameters['tta_min'] = $tta_min;
	}
	if (is_numeric($tta_max)) {
		$sql .= "and (c.answer_epoch - c.start_epoch) <= :tta_max \n";
		$parameters['tta_max'] = $tta_max;
	}
	if ($recording == 'true' || $recording == 'false') {
		if ($recording == 'true') {
			$sql .= "and c.record_path is not null and c.record_name is not null \n";
		}
		if ($recording == 'false') {
			$sql .= "and (c.record_path is null or c.record_name is null) \n";
		}
	}
	//show agent originated legs only to those with the permission
	if (!$permission['xml_cdr_cc_agent_leg']) {
		$sql .= "and (cc_side is null or cc_side != 'agent') \n";
	}
	//call center queue search for member or agent
	if (!empty($cc_side) && $permission['xml_cdr_cc_side']) {
		$sql .= "and cc_side = :cc_side \n";
		$parameters['cc_side'] = $cc_side;
	}
	//show specific call center queue
	if (!empty($call_center_queue_uuid) && $permission['xml_cdr_call_center_queues']) {
		$sql .= "and call_center_queue_uuid = :call_center_queue_uuid \n";
		$parameters['call_center_queue_uuid'] = $call_center_queue_uuid;
	}
	//show specific ring groups
	if (!empty($ring_group_uuid)) {
		$sql .= "and ring_group_uuid = :ring_group_uuid \n";
		$parameters['ring_group_uuid'] = $ring_group_uuid;
	}
	//end where
	if (!empty($order_by)) {
		$sql .= order_by($order_by, $order);
	}
	if ($export_format !== "csv" && $export_format !== "pdf") {
		if ($rows_per_page == 0) {
			$sql .= " limit :limit offset 0 \n";
			$parameters['limit'] = $_SESSION['cdr']['limit']['numeric'];
		}
		else {
			$sql .= " limit :limit offset :offset \n";
			$parameters['limit'] = intval($rows_per_page);
			$parameters['offset'] = intval($offset);
		}
	}
	$sql = str_replace("  ", " ", $sql);
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
	unset($sql, $parameters);

//return the paging
	if (empty($_REQUEST['export_format'])) {
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true, $result_count); //top
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page, false, $result_count); //bottom
	}

?>
