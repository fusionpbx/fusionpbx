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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
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

//show all call detail records to admin and superadmin. for everyone else show only the call details for extensions assigned to them
	if (!permission_exists('xml_cdr_domain')) {
		// select caller_id_number, destination_number from v_xml_cdr where domain_uuid = ''
		// and (caller_id_number = '1001' or destination_number = '1001' or destination_number = '*991001')

		$sql_where = "c.domain_uuid = '".$_SESSION["domain_uuid"]."' and ( ";
		if (count($_SESSION['user']['extension']) > 0) {
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x==0) {
					if ($row['user'] > 0) { $sql_where .= "c.caller_id_number = '".$row['user']."' \n"; } //source
				}
				else {
					if ($row['user'] > 0) { $sql_where .= "or c.caller_id_number = '".$row['user']."' \n"; } //source
				}
				if ($row['user'] > 0) { $sql_where .= "or c.destination_number = '".$row['user']."' \n"; } //destination
				if ($row['user'] > 0) { $sql_where .= "or c.destination_number = '*99".$row['user']."' \n"; } //destination
				$x++;
			}
		}
		$sql_where .= ") ";
	}
	else {
		//superadmin or admin
		if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
			$sql_where = '';
		} else {
			$sql_where = "c.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		}
	}
	if (isset($sql_where) && $sql_where != '') {
		$sql_where_ands[] = $sql_where;
		unset($sql_where);
	}

//create the sql query to get the xml cdr records
	if (strlen($order_by) == 0) { $order_by  = "start_epoch"; }
	if (strlen($order) == 0) { $order  = "desc"; }

//get post or get variables from http
	if (isset($_REQUEST)) {
		$cdr_id = $_REQUEST["cdr_id"];
		$missed = $_REQUEST["missed"];
		$direction = $_REQUEST["direction"];
		$caller_id_name = $_REQUEST["caller_id_name"];
		$caller_id_number = $_REQUEST["caller_id_number"];
		$caller_extension_uuid = $_REQUEST["caller_extension_uuid"];
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
		//$direction = 'inbound';
	}

//if we do not see b-leg then use only a-leg to generate statistics
	if (!permission_exists('xml_cdr_b_leg')) {
		$leg = 'a';
	}

//build the sql where string
	//if (!$show_all) {
	//	$sql_where_ands[] = "c.domain_uuid = :domain_uuid ";
	//	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	//}
	if ($missed == true) {
		$sql_where_ands[] = "c.missed_call = true ";
		$sql_where_ands[] = "c.and hangup_cause <> 'LOSE_RACE' ";
	}
	if (strlen($start_epoch) > 0 && strlen($stop_epoch) > 0) {
		$sql_where_ands[] = "c.start_epoch between :start_epoch and :stop_epoch";
		$parameters['start_epoch'] = $start_epoch;
		$parameters['stop_epoch'] = $stop_epoch;
	}
	if (strlen($cdr_id) > 0) {
		$sql_where_ands[] = "c.cdr_id like :cdr_id";
		$parameters['cdr_id'] = '%'.$cdr_id.'%';
	}
	if (strlen($direction) > 0) {
		$sql_where_ands[] = "c.direction = :direction";
		$parameters['direction'] = $direction;
	}
	if (strlen($caller_id_name) > 0) {
		$mod_caller_id_name = str_replace("*", "%", $caller_id_name);
		$sql_where_ands[] = "c.caller_id_name like :mod_caller_id_name";
		$parameters['mod_caller_id_name'] = $mod_caller_id_name;
	}
	if (strlen($caller_extension_uuid) > 0) {
		$sql_where_ands[] = "c.extension_uuid = :caller_extension_uuid";
		$parameters['caller_extension_uuid'] = $caller_extension_uuid;
	}
	if (strlen($extension_uuid) > 0) {
		$sql_where_ands[] = "c.extension_uuid = :extension_uuid";
		$parameters['extension_uuid'] = $extension_uuid;
	}
	if (strlen($caller_id_number) > 0) {
		$mod_caller_id_number = str_replace("*", "%", $caller_id_number);
		$sql_where_ands[] = "c.caller_id_number like :mod_caller_id_number";
		$parameters['mod_caller_id_number'] = $mod_caller_id_number;
	}
	if (strlen($destination_number) > 0) {
		$mod_destination_number = str_replace("*", "%", $destination_number);
		$sql_where_ands[] = "c.destination_number like :mod_destination_number";
		$parameters['mod_destination_number'] = $mod_destination_number;
	}
	if (strlen($context) > 0) {
		$sql_where_ands[] = "c.context like :context";
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
		$sql_where_ands[] = "c.answer_stamp between :answer_stamp_begin and :answer_stamp_end";
		$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000';
		$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999';
	}
	else if (strlen($answer_stamp_begin) > 0) {
		$sql_where_ands[] = "c.answer_stamp >= :answer_stamp_begin";
		$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000';
	}
	else if (strlen($answer_stamp_end) > 0) {
		$sql_where_ands[] = "c.answer_stamp <= :answer_stamp_end";
		$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999';
	}
	if (strlen($end_stamp_begin) > 0 && strlen($end_stamp_end) > 0) {
		$sql_where_ands[] = "c.end_stamp between :end_stamp_begin and :end_stamp_end";
		$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000';
		$parameters['end_stamp_end'] = $end_stamp_end.':59.999';
	}
	else if (strlen($end_stamp_begin) > 0) {
		$sql_where_ands[] = "c.end_stamp >= :end_stamp_begin";
		$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000';
	}
	else if (strlen($end_stamp_end) > 0) {
		$sql_where_ands[] = "c.end_stamp <= :end_stamp_end";
		$parameters['end_stamp_end'] = $end_stamp_end.':59.999';
	}
	if (strlen($duration) > 0) {
		$sql_where_ands[] = "c.duration like :duration";
		$parameters['duration'] = '%'.$duration.'%';
	}
	if (strlen($billsec) > 0) {
		$sql_where_ands[] = "c.billsec like :billsec";
		$parameters['billsec'] = '%'.$billsec.'%';
	}
	if (strlen($hangup_cause) > 0) {
		$sql_where_ands[] = "c.hangup_cause like :hangup_cause";
		$parameters['hangup_cause'] = '%'.$hangup_cause.'%';
	}
	if (is_uuid($uuid)) {
		$sql_where_ands[] = "c.uuid = :uuid";
		$parameters['uuid'] = $uuid;
	}
	if (is_uuid($bleg_uuid)) {
		$sql_where_ands[] = "c.bleg_uuid = :bleg_uuid";
		$parameters['bleg_uuid'] = $bleg_uuid;
	}
	if (strlen($accountcode) > 0) {
		$sql_where_ands[] = "c.accountcode = :accountcode";
		$parameters['accountcode'] = $accountcode;
	}
	if (strlen($read_codec) > 0) {
		$sql_where_ands[] = "c.read_codec like :read_codec";
		$parameters['read_codec'] = '%'.$read_codec.'%';
	}
	if (strlen($write_codec) > 0) {
		$sql_where_ands[] = "c.write_codec like :write_codec";
		$parameters['write_codec'] = '%'.$write_codec.'%';
	}
	if (strlen($remote_media_ip) > 0) {
		$sql_where_ands[] = "c.remote_media_ip like :remote_media_ip";
		$parameters['remote_media_ip'] = '%'.$remote_media_ip.'%';
	}
	if (strlen($network_addr) > 0) {
		$sql_where_ands[] = "c.network_addr like :network_addr";
		$parameters['network_addr'] = '%'.$network_addr.'%';
	}
	if (strlen($mos_comparison) > 0 && strlen($mos_score) > 0 ) {
		$sql_where_ands[] = "c.rtp_audio_in_mos ".$mos_comparison." :mos_score";
		$parameters['mos_score'] = $mos_score;
	}
	if (strlen($leg) > 0) {
		$sql_where_ands[] = "c.leg = :leg";
		$parameters['leg'] = $leg;
	}
	//Exclude enterprise ring group legs
	if (!permission_exists('xml_cdr_enterprise_leg')) {
		$sql_where_ands[] .= "c.originating_leg_uuid IS NULL";
	}
	//If you can't see lose_race, don't run stats on it
	elseif (!permission_exists('xml_cdr_lose_race')) {
		$sql_where_ands[] = "c.hangup_cause != 'LOSE_RACE'";
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
				$sql_where_ors[] = "c.caller_id_number like :user_extension";
				$sql_where_ors[] = "c.destination_number like :user_extension";
				$sql_where_ors[] = "c.destination_number like :star_99_user_extension";
				$parameters['user_extension'] = $user_extension;
				$parameters['star_99_user_extension'] = '*99'.$user_extension;
			}
			// if source submitted is blank, implement restriction for assigned extension(s)
			if ($caller_id_number == '') { // if source criteria is blank, then restrict to assigned ext
				foreach ($user_extensions as $user_extension) {
					if (strlen($user_extension) > 0) {
						$sql_where_ors[] = "c.caller_id_number like :user_extension";
						$parameters['user_extension'] = $user_extension;
					}
				}
			}
			// if destination submitted is blank, implement restriction for assigned extension(s)
			if ($destination_number == '') {
				foreach ($user_extensions as $user_extension) {
					if (strlen($user_extension) > 0) {
						$sql_where_ors[] = "c.destination_number like :user_extension";
						$sql_where_ors[] = "c.destination_number like :star_99_user_extension";
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

//calculate the seconds in different time frames
	$seconds_hour = 3600;
	$seconds_day = $seconds_hour * 24;
	$seconds_week = $seconds_day * 7;
	$seconds_month = $seconds_day * 30;

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}
	$parameters['time_zone'] = $time_zone;

//build the sql query for xml cdr statistics
	$sql = "select ";
	$sql .= "row_number() over() as hours, ";
	$sql .= "to_char(start_date at time zone :time_zone, 'DD Mon') as date, \n";
	$sql .= "to_char(start_date at time zone :time_zone, 'HH12:MI am') || ' - ' || to_char(end_date at time zone :time_zone, 'HH12:MI am') as time, \n";
	$sql .= "extract(epoch from start_date) as start_epoch, ";
	$sql .= "extract(epoch from end_date) as end_epoch, ";
	$sql .= "s_hour, start_date, end_date, volume, answered, (round(d.seconds / 60, 1)) as minutes, \n";
	$sql .= "(volume / (s_hour * 60)) as calls_per_minute, \n";
	$sql .= "(volume / s_hour) as calls_per_hour,  missed, \n";
	$sql .= "(answered::numeric / (s_hour * 60)) as cpm_answered, \n"; //used in the graph
	$sql .= "(volume / (s_hour * 60)) as avg_min, \n"; //used in the graph
	$sql .= "(round(100 * (answered::numeric / NULLIF(volume, 0)),2)) as asr, \n";
	$sql .= "(round(seconds / NULLIF(answered, 0) / 60, 2)) as aloc, seconds \n";
	$sql .= "from \n";
	$sql .= "( \n";
	$sql .= "	select \n";
	$sql .= "	(count(*) filter ( \n";
	$sql .= "		where start_stamp between s.start_date and s.end_date \n";
	$sql .= "	)) as volume, \n";
	$sql .= "	(count(*) filter ( \n";
	$sql .= "		where start_stamp between s.start_date and s.end_date \n";
	$sql .= "		and c.originating_leg_uuid IS NULL \n";
	$sql .= "		and (c.answer_stamp IS NOT NULL and c.bridge_uuid IS NOT NULL) \n";
	$sql .= "		and (c.cc_side IS NULL or c.cc_side !='agent') \n";
	$sql .= "	)) as answered, \n";
	$sql .= "	(count(*) filter ( \n";
	$sql .= "		where start_stamp between s.start_date and s.end_date \n";
	$sql .= "		and missed_call = true \n";
	$sql .= "	)) as missed, \n";
	$sql .= "	(sum(c.billsec) filter ( \n";
	$sql .= "		where c.start_stamp between s.start_date and s.end_date \n";
	$sql .= "	)) as seconds, \n";
	$sql .= "	s.start_date, \n";
	$sql .= "	s.end_date, \n";
	$sql .= "	s.s_hour \n";
	$sql .= "	from v_xml_cdr as c, \n";
	$sql .= "	( \n";
	$sql .= "		select h.s_id, h.s_start, h.s_end, h.s_hour, \n";
	$sql .= "			(date_trunc('hour', now()) + (interval '1 hour') - (h.s_start * (interval '1 hour'))) as start_date, \n";
	$sql .= "			(date_trunc('hour', now()) + (interval '1 hour') - (h.s_end * (interval '1 hour'))) as end_date  \n";
	$sql .= "		from ( \n";
	$sql .= "				select generate_series(0, 23) as s_id, generate_series(1, 24) as s_start, generate_series(0, 23) as s_end, 1 s_hour \n";
	$sql .= "				union \n";
	$sql .= "				select 25 s_id, 24 as s_start, 0 as s_end, 24 s_hour \n";
	$sql .= "				union \n";
	$sql .= "				select 26 s_id, 168 as s_start, 0 as s_end, 168 s_hour \n";
	$sql .= "				union \n";
	$sql .= "				select 27 s_id, 720 as s_start, 0 as s_end, 720 s_hour \n";
	$sql .= "			) as h \n";
	$sql .= "		where true \n";
	$sql .= "		group by s_id, s_hour, s_start, s_end \n";
	$sql .= "		order by s_id asc \n";
	$sql .= "	) as s \n";
	$sql .= "where true \n";

//concatenate the 'ands's array, add to where clause
	if (is_array($sql_where_ands) && @sizeof($sql_where_ands) > 0) {
		$sql .= "and ".implode(" and ", $sql_where_ands)." ";
	}

	/*
	if (!$show_all) {
		$sql .= "and c.domain_uuid = :domain_uuid \n";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if ($missed == true) {
		$sql .= "and c.missed_call = true ";
	}
	if (strlen($start_epoch) > 0 && strlen($stop_epoch) > 0) {
		$sql .= "and c.start_epoch between :start_epoch and :stop_epoch \n";
		$parameters['start_epoch'] = $start_epoch;
		$parameters['stop_epoch'] = $stop_epoch;
	}
	if (strlen($start_date) > 0 && strlen($stop_date) > 0) {
		$sql .= "and c.start_stamp between :start_date and :stop_date \n";
		$parameters['start_date'] = $start_date;
		$parameters['stop_date'] = $stop_date;
	}
	//if (strlen($start_stamp) == 0 && strlen($end_stamp) == 0) {
	//	$sql .= "and c.start_stamp between NOW() - INTERVAL '24 HOURS' AND NOW() \n";
	//}
	if (strlen($cdr_id) > 0) {
		$sql .= "and c.cdr_id like :cdr_id \n";
		$parameters['cdr_id'] = '%'.$cdr_id.'%';
	}
	if (strlen($direction) > 0) {
		$sql .= "and c.direction = :direction \n";
		$parameters['direction'] = $direction;
	}
	if (strlen($caller_id_name) > 0) {
		$mod_caller_id_name = str_replace("*", "%", $caller_id_name);
		$sql .= "and c.caller_id_name like :mod_caller_id_name";
		$parameters['mod_caller_id_name'] = $mod_caller_id_name;
	}
	if (strlen($caller_extension_uuid) > 0) {
		$sql .= "and c.extension_uuid = :caller_extension_uuid \n";
		$parameters['caller_extension_uuid'] = $caller_extension_uuid;
	}
	if (strlen($extension_uuid) > 0) {
		$sql .= "and c.extension_uuid = :extension_uuid \n";
		$parameters['extension_uuid'] = $extension_uuid;
	}
	if (strlen($caller_id_number) > 0) {
		$mod_caller_id_number = str_replace("*", "%", $caller_id_number);
		$sql .= "and c.caller_id_number like :mod_caller_id_number \n";
		$parameters['mod_caller_id_number'] = $mod_caller_id_number;
	}
	if (strlen($destination_number) > 0) {
		$mod_destination_number = str_replace("*", "%", $destination_number);
		$sql .= "and c.destination_number like :mod_destination_number \n";
		$parameters['mod_destination_number'] = $mod_destination_number;
	}
	if (strlen($context) > 0) {
		$sql .= "and c.context like :context \n";
		$parameters['context'] = '%'.$context.'%';
	}
	if (strlen($start_stamp_begin) > 0 && strlen($start_stamp_end) > 0) {
		$sql .= "and c.start_stamp between :start_stamp_begin and :start_stamp_end \n";
		$parameters['start_stamp_begin'] = $start_stamp_begin.':00.000';
		$parameters['start_stamp_end'] = $start_stamp_end.':59.999';
	}
	else if (strlen($start_stamp_begin) > 0) {
		$sql .= "and c.start_stamp >= :start_stamp_begin \n";
		$parameters['start_stamp_begin'] = $start_stamp_begin.':00.000';
	}
	else if (strlen($start_stamp_end) > 0) {
		$sql .= "and c.start_stamp <= :start_stamp_end \n";
		$parameters['start_stamp_end'] = $start_stamp_end.':59.999';
	}
	if (strlen($answer_stamp_begin) > 0 && strlen($answer_stamp_end) > 0) {
		$sql .= "and c.answer_stamp between :answer_stamp_begin and :answer_stamp_end \n";
		$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000';
		$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999';
	}
	else if (strlen($answer_stamp_begin) > 0) {
		$sql .= "and c.answer_stamp >= :answer_stamp_begin \n";
		$parameters['answer_stamp_begin'] = $answer_stamp_begin.':00.000';
	}
	else if (strlen($answer_stamp_end) > 0) {
		$sql .= "and c.answer_stamp <= :answer_stamp_end \n";
		$parameters['answer_stamp_end'] = $answer_stamp_end.':59.999';
	}
	if (strlen($end_stamp_begin) > 0 && strlen($end_stamp_end) > 0) {
		$sql .= "and c.end_stamp between :end_stamp_begin and :end_stamp_end \n";
		$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000';
		$parameters['end_stamp_end'] = $end_stamp_end.':59.999';
	}
	else if (strlen($end_stamp_begin) > 0) {
		$sql .= "and c.end_stamp >= :end_stamp_begin \n";
		$parameters['end_stamp_begin'] = $end_stamp_begin.':00.000';
	}
	else if (strlen($end_stamp_end) > 0) {
		$sql .= "and c.end_stamp <= :end_stamp_end \n";
		$parameters['end_stamp_end'] = $end_stamp_end.':59.999';
	}
	if (strlen($duration) > 0) {
		$sql .= "and c.duration like :duration \n";
		$parameters['duration'] = '%'.$duration.'%';
	}
	if (strlen($billsec) > 0) {
		$sql .= "and c.billsec like :billsec \n";
		$parameters['billsec'] = '%'.$billsec.'%';
	}
	if (strlen($hangup_cause) > 0) {
		$sql .= "and c.hangup_cause like :hangup_cause \n";
		$parameters['hangup_cause'] = '%'.$hangup_cause.'%';
	}
	if (is_uuid($uuid)) {
		$sql .= "and c.uuid = :uuid \n";
		$parameters['uuid'] = $uuid;
	}
	if (is_uuid($bleg_uuid)) {
		$sql .= "and c.bleg_uuid = :bleg_uuid \n";
		$parameters['bleg_uuid'] = $bleg_uuid;
	}
	if (strlen($accountcode) > 0) {
		$sql .= "and c.accountcode = :accountcode \n";
		$parameters['accountcode'] = $accountcode;
	}
	if (strlen($read_codec) > 0) {
		$sql .= "and c.read_codec like :read_codec \n";
		$parameters['read_codec'] = '%'.$read_codec.'%';
	}
	if (strlen($write_codec) > 0) {
		$sql .= "and c.write_codec like :write_codec \n";
		$parameters['write_codec'] = '%'.$write_codec.'%';
	}
	if (strlen($remote_media_ip) > 0) {
		$sql .= "and c.remote_media_ip like :remote_media_ip \n";
		$parameters['remote_media_ip'] = '%'.$remote_media_ip.'%';
	}
	if (strlen($network_addr) > 0) {
		$sql .= "and c.network_addr like :network_addr \n";
		$parameters['network_addr'] = '%'.$network_addr.'%';
	}
	if (strlen($mos_comparison) > 0 && strlen($mos_score) > 0 ) {
		$sql .= "and c.rtp_audio_in_mos ".$mos_comparison." :mos_score \n";
		$parameters['mos_score'] = $mos_score;
	}
	if (strlen($leg) > 0) {
		$sql .= "and c.leg = :leg \n";
		$parameters['leg'] = $leg;
	}

	//exclude enterprise ring group and follow me originated legs
	if (!permission_exists('xml_cdr_enterprise_leg')) {
		$sql .= "and c.originating_leg_uuid IS NULL \n";
	}
	//if you can't see lose_race, don't run stats on it
	if (!permission_exists('xml_cdr_lose_race')) {
		$sql .= "and c.hangup_cause != 'LOSE_RACE' \n";
	}
	*/

	$sql .= "	group by s.s_id, s.start_date, s.end_date, s.s_hour \n";
	$sql .= "	order by s.s_id asc \n";
	$sql .= ") as d; \n";
	$database = new database;
	$stats = $database->select($sql, $parameters, 'all');

//set the hours
	$hours = 23;

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
