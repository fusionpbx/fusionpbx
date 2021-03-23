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
	if (permission_exists('xml_cdr_statistics')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

/*//show all call detail records to admin and superadmin. for everyone else show only the call details for extensions assigned to them
	if (!if_group("admin") && !if_group("superadmin")) {
		// select caller_id_number, destination_number from v_xml_cdr where domain_uuid = ''
		// and (caller_id_number = '1001' or destination_number = '1001' or destination_number = '*991001')

		$sql_where = "where domain_uuid = '".$_SESSION["domain_uuid"]."' and ( ";
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
		$sql_where .= ") ";
	}
	else {
		//superadmin or admin
		if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
			$sql_where = "";
		} else {
			$sql_where = "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		}
	}

//create the sql query to get the xml cdr records
	if (strlen($order_by) == 0) { $order_by  = "start_epoch"; }
	if (strlen($order) == 0) { $order  = "desc"; }
*/

//get post or get variables from http
	if (count($_REQUEST) > 0) {
		$cdr_id = $_REQUEST["cdr_id"];
		$missed = $_REQUEST["missed"];
		$direction = $_REQUEST["direction"];
		$caller_id_name = $_REQUEST["caller_id_name"];
		$caller_id_number = $_REQUEST["caller_id_number"];
		$caller_extension_uuid = $_REQUEST["caller_extension_uuid"];
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
		$duration = $_REQUEST["duration"];
		$billsec = $_REQUEST["billsec"];
		$hangup_cause = $_REQUEST["hangup_cause"];
		$uuid = $_REQUEST["uuid"];
		$bleg_uuid = $_REQUEST["bleg_uuid"];
		$accountcode = $_REQUEST["accountcode"];
		$read_codec = $_REQUEST["read_codec"];
		$write_codec = $_REQUEST["write_codec"];
		$remote_media_ip = $_REQUEST["remote_media_ip"];
		$network_addr = $_REQUEST["network_addr"];
		$bridge_uuid = $_REQUEST["network_addr"];
		$order_by = $_REQUEST["order_by"];
		$order = $_REQUEST["order"];
		if (strlen($_REQUEST["mos_comparison"]) > 0) {
			switch($_REQUEST["mos_comparison"]) {
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
		}
		else {
			unset($mos_comparison);
		}
		//$mos_comparison = $_REQUEST["mos_comparison"];
		$mos_score = $_REQUEST["mos_score"];
		if (permission_exists('xml_cdr_b_leg')) {
			$leg = $_REQUEST["leg"];
		}
		$show_all = permission_exists('xml_cdr_all') && ($_REQUEST['showall'] == 'true');
	}
	else {
		$show_all = permission_exists('xml_cdr_all') && ($_GET['showall'] == 'true');
		$direction = 'inbound';
	}

//if we do not see b-leg then use only a-leg to generate statistics
	if (!permission_exists('xml_cdr_b_leg')) {
		$leg = 'a';
	}

//build the sql where string
	if (!$show_all) {
		$sql_where_ands[] = "domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if ($missed == true) {
		$sql_where_ands[] = "missed_call = true ";
	}
	if (strlen($start_epoch) > 0 && strlen($stop_epoch) > 0) {
		$sql_where_ands[] = "start_epoch between :start_epoch and :stop_epoch";
		$parameters['start_epoch'] = $start_epoch;
		$parameters['stop_epoch'] = $stop_epoch;
	}
	if (strlen($cdr_id) > 0) {
		$sql_where_ands[] = "cdr_id like :cdr_id";
		$parameters['cdr_id'] = '%'.$cdr_id.'%';
	}
	if (strlen($direction) > 0) {
		$sql_where_ands[] = "direction = :direction";
		$parameters['direction'] = $direction;
	}
	if (strlen($caller_id_name) > 0) {
		$mod_caller_id_name = str_replace("*", "%", $caller_id_name);
		$sql_where_ands[] = "caller_id_name like :mod_caller_id_name";
		$parameters['mod_caller_id_name'] = $mod_caller_id_name;
	}
	if (strlen($caller_extension_uuid) > 0) {
		$sql_where_ands[] = "extension_uuid = :caller_extension_uuid";
		$parameters['caller_extension_uuid'] = $caller_extension_uuid;
	}
	if (strlen($caller_id_number) > 0) {
		$mod_caller_id_number = str_replace("*", "%", $caller_id_number);
		$sql_where_ands[] = "caller_id_number like :mod_caller_id_number";
		$parameters['mod_caller_id_number'] = $mod_caller_id_number;
	}
	if (strlen($destination_number) > 0) {
		$mod_destination_number = str_replace("*", "%", $destination_number);
		$sql_where_ands[] = "destination_number like :mod_destination_number";
		$parameters['mod_destination_number'] = $mod_destination_number;
	}
	if (strlen($context) > 0) {
		$sql_where_ands[] = "context like :context";
		$parameters['context'] = '%'.$context.'%';
	}
	/*
	if (strlen($start_stamp_begin) > 0 && strlen($start_stamp_end) > 0) {
		$sql_where_ands[] = "start_stamp between :start_stamp_begin and :start_stamp_end";
		$parameters['start_stamp_begin'] = $start_stamp_begin.':00.000';
		$parameters['start_stamp_end'] = $start_stamp_end.':59.999';
	}
	else if (strlen($start_stamp_begin) > 0) {
		$sql_where_ands[] = "start_stamp >= :start_stamp_begin";
		$parameters['start_stamp_begin'] = $start_stamp_begin.':00.000';
	}
	else if (strlen($start_stamp_end) > 0) {
		$sql_where_ands[] = "start_stamp <= :start_stamp_end";
		$parameters['start_stamp_end'] = $start_stamp_end.':59.999';
	}
	*/
	if (strlen($answer_stamp_begin) > 0 && strlen($answer_stamp_end) > 0) {
		$sql_where_ands[] = "answer_stamp between :answer_stamp_begin and :answer_stamp_end";
		$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000';
		$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999';
	}
	else if (strlen($answer_stamp_begin) > 0) {
		$sql_where_ands[] = "answer_stamp >= :answer_stamp_begin";
		$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000';
	}
	else if (strlen($answer_stamp_end) > 0) {
		$sql_where_ands[] = "answer_stamp <= :answer_stamp_end";
		$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999';
	}
	if (strlen($end_stamp_begin) > 0 && strlen($end_stamp_end) > 0) {
		$sql_where_ands[] = "end_stamp between :end_stamp_begin and :end_stamp_end";
		$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000';
		$parameters['end_stamp_end'] = $end_stamp_end.':59.999';
	}
	else if (strlen($end_stamp_begin) > 0) {
		$sql_where_ands[] = "end_stamp >= :end_stamp_begin";
		$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000';
	}
	else if (strlen($end_stamp_end) > 0) {
		$sql_where_ands[] = "end_stamp <= :end_stamp_end";
		$parameters['end_stamp_end'] = $end_stamp_end.':59.999';
	}
	if (strlen($duration) > 0) {
		$sql_where_ands[] = "duration like :duration";
		$parameters['duration'] = '%'.$duration.'%';
	}
	if (strlen($billsec) > 0) {
		$sql_where_ands[] = "billsec like :billsec";
		$parameters['billsec'] = '%'.$billsec.'%';
	}
	if (strlen($hangup_cause) > 0) {
		$sql_where_ands[] = "hangup_cause like :hangup_cause";
		$parameters['hangup_cause'] = '%'.$hangup_cause.'%';
	}
	if (is_uuid($uuid)) {
		$sql_where_ands[] = "uuid = :uuid";
		$parameters['uuid'] = $uuid;
	}
	if (is_uuid($bleg_uuid)) {
		$sql_where_ands[] = "bleg_uuid = :bleg_uuid";
		$parameters['bleg_uuid'] = $bleg_uuid;
	}
	if (strlen($accountcode) > 0) {
		$sql_where_ands[] = "accountcode = :accountcode";
		$parameters['accountcode'] = $accountcode;
	}
	if (strlen($read_codec) > 0) {
		$sql_where_ands[] = "read_codec like :read_codec";
		$parameters['read_codec'] = '%'.$read_codec.'%';
	}
	if (strlen($write_codec) > 0) {
		$sql_where_ands[] = "write_codec like :write_codec";
		$parameters['write_codec'] = '%'.$write_codec.'%';
	}
	if (strlen($remote_media_ip) > 0) {
		$sql_where_ands[] = "remote_media_ip like :remote_media_ip";
		$parameters['remote_media_ip'] = '%'.$remote_media_ip.'%';
	}
	if (strlen($network_addr) > 0) {
		$sql_where_ands[] = "network_addr like :network_addr";
		$parameters['network_addr'] = '%'.$network_addr.'%';
	}
	if (strlen($mos_comparison) > 0 && strlen($mos_score) > 0 ) {
		$sql_where_ands[] = "rtp_audio_in_mos ".$mos_comparison." :mos_score";
		$parameters['mos_score'] = $mos_score;
	}
	if (strlen($leg) > 0) {
		$sql_where_ands[] = "leg = :leg";
		$parameters['leg'] = $leg;
	}
	//Exclude enterprise ring group legs
	if (!permission_exists('xml_cdr_enterprise_leg')) {
		$sql_where_ands[] .= "originating_leg_uuid IS NULL";
	}
	//If you can't see lose_race, don't run stats on it
	elseif (!permission_exists('xml_cdr_lose_race')) {
		$sql_where_ands[] = "hangup_cause != 'LOSE_RACE'";
	}
	

	//if not admin or superadmin, only show own calls
	if (!permission_exists('xml_cdr_domain')) {
		if (is_array($_SESSION['user']['extension']) && count($_SESSION['user']['extension']) > 0) { // extensions are assigned to this user
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
				$sql_where_ors[] = "caller_id_number like :user_extension";
				$sql_where_ors[] = "destination_number like :user_extension";
				$sql_where_ors[] = "destination_number like :star_99_user_extension";
				$parameters['user_extension'] = $user_extension;
				$parameters['star_99_user_extension'] = '*99'.$user_extension;
			}
			// if source submitted is blank, implement restriction for assigned extension(s)
			if ($caller_id_number == '') { // if source criteria is blank, then restrict to assigned ext
				foreach ($user_extensions as $user_extension) {
					if (strlen($user_extension) > 0) {
						$sql_where_ors[] = "caller_id_number like :user_extension";
						$parameters['user_extension'] = $user_extension;
					}
				}
			}
			// if destination submitted is blank, implement restriction for assigned extension(s)
			if ($destination_number == '') {
				foreach ($user_extensions as $user_extension) {
					if (strlen($user_extension) > 0) {
						$sql_where_ors[] = "destination_number like :user_extension";
						$sql_where_ors[] = "destination_number like :star_99_user_extension";
						$parameters['user_extension'] = $user_extension;
						$parameters['star_99_user_extension'] = '*99'.$user_extension;
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

	$sql_where = ' where ';
	// concatenate the 'ands's array, add to where clause
	if (is_array($sql_where_ands) && @sizeof($sql_where_ands) > 0) {
		$sql_where .= implode(" and ", $sql_where_ands)." and ";
	}

//calculate the seconds in different time frames
	$seconds_hour = 3600;
	$seconds_day = $seconds_hour * 24;
	$seconds_week = $seconds_day * 7;
	$seconds_month = $seconds_day * 30;

//get the call volume between a start end end time in seconds
	function get_call_volume_between($start, $end, $where, $parameters) {
		$sql = "select count(*) as count, sum(billsec) as seconds, sum(answer_stamp - start_stamp) as tta from v_xml_cdr ";
		$sql .= $where." ";
		$sql .= "start_epoch between :start and :end ";
		$parameters['start'] = $start;
		$parameters['end'] = $end;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			return array(
				'volume' => $row['count'],
				'seconds' => $row['seconds'],
				'tta' => $row['tta'],
			);
		}
		return false;
	}

	function append_stats(&$stats, $hours, $start_epoch, $stop_epoch) {
		global $sql_where, $parameters, $missed;

		$i = count($stats);

		$stats[$i]['hours'] = $hours;
		$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
		$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
		$stats[$i]['start_epoch'] = $start_epoch;
		$stats[$i]['stop_epoch'] = $stop_epoch;
		$stat_range = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $sql_where, $parameters);
		$stats[$i]['volume'] = $stat_range ? $stat_range['volume'] : 0;
		$stats[$i]['seconds'] = $stat_range ? $stat_range['seconds'] : 0;
		$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;

		if ($missed) {
			//we select only missed calls at first place - no reasons to select it again
			$stats[$i]['missed'] = $stats[$i]['volume'];
		}
		else {
			$where = $sql_where."missed_call = true and ";
			$stat_range = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where, $parameters);
			$stats[$i]['missed'] = $stat_range ? $stat_range['volume'] : 0;
		}

		$delta_min = ($stop_epoch - $start_epoch) / 60;
		$success_volume = $stats[$i]['volume'] == 0 ? 0 : ($stats[$i]['volume'] - $stats[$i]['missed']);

		//calls per minute (answered)
		$stats[$i]['cpm_ans'] = $success_volume / $delta_min;

		//calls per minute
		$stats[$i]['avg_min'] = $stats[$i]['volume'] / $delta_min;

		//answer / seizure ratio
		$stats[$i]['asr'] = $stats[$i]['volume'] == 0 ? 0 : ($success_volume / $stats[$i]['volume'] * 100);

		//average time to answer
		$stats[$i]['avg_tta'] = $stats[$i]['volume'] == 0 ? 0 : round($stat_range['tta'] / $success_volume);

		//average length of call
		$stats[$i]['aloc'] = $success_volume == 0 ? 0 : $stats[$i]['minutes'] / $success_volume;
	}

	if (strlen($_GET['start_stamp_begin']) > 0 && strlen($_GET['start_stamp_end']) > 0 ) {
		$start_date = new DateTime($_GET['start_stamp_begin']);
		$end_date = new DateTime($_GET['start_stamp_end']);
		$time = $end_date->getTimestamp();
		$time = $time - $time % 3600;
		$hours = floor(($end_date->getTimestamp() - $start_date->getTimestamp()) / 3600);
	}
	else {
		//round down to the nearest hour
		$time = time() - time() % 3600;
		$hours = 23;
	}

	if (isset($_SESSION['cdr']['stat_hours_limit']['numeric'])) {
		$limit = $_SESSION['cdr']['stat_hours_limit']['numeric'] - 1;
		if ($hours > $limit) {
			$hours = $limit;
		}
		unset($limit);
	}

	$stats = array();

//call info hour by hour for last n hours
	for ($i = $hours; $i >= 0 ; $i--) {
		$start_epoch = $time - 3600 * $i;
		$stop_epoch = $start_epoch + 3600;
		append_stats($stats, 1, $start_epoch, $stop_epoch);
	}

//call info for entire period
	if (strlen($_GET['start_stamp_begin']) > 0 && strlen($_GET['start_stamp_end']) > 0 ) {
		$start_epoch = new DateTime($_GET['start_stamp_begin']);
		$stop_epoch = new DateTime($_GET['start_stamp_end']);
		$days = $start_epoch->diff($stop_epoch)->d;
		append_stats($stats, 24 * $days, $start_epoch->getTimestamp(), $stop_epoch->getTimestamp() );
	}
	else {
		$stop_epoch = time();
		append_stats($stats, 24, $stop_epoch - $seconds_day, $stop_epoch );
		append_stats($stats, 24 * 7, $stop_epoch - $seconds_week, $stop_epoch );
		append_stats($stats, 24 * 30, $stop_epoch - $seconds_month, $stop_epoch );
	}

//show the graph
	$x = 0;
	foreach ($stats as $row) {
		$graph['volume'][$x][] = $row['start_epoch'] * 1000;
		$graph['volume'][$x][] = $row['volume'] / 1;
		if ($x == $hours) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['minutes'][$x][] = $row['start_epoch'] * 1000;
		$graph['minutes'][$x][] = round($row['minutes'],2);
		if ($x == $hours) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['call_per_min'][$x][] = $row['start_epoch'] * 1000;
		$graph['call_per_min'][$x][] = round($row['avg_min'],2);
		if ($x == $hours) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['missed'][$x][] = $row['start_epoch'] * 1000;
		$graph['missed'][$x][] = $row['missed'] / 1;
		if ($x == $hours) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['asr'][$x][] = $row['start_epoch'] * 1000;
		$graph['asr'][$x][] = round($row['asr'],2) / 100;
		if ($x == $hours) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['aloc'][$x][] = $row['start_epoch'] * 1000;
		$graph['aloc'][$x][] = round($row['aloc'],2);
		if ($x == $hours) { break; }
		$x++;
	}

?>
