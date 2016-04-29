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
if (permission_exists('xml_cdr_view')) {
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
/*	if (strlen($start_stamp_begin) > 0 && strlen($start_stamp_end) > 0) { $sql_where_ands[] = "start_stamp BETWEEN '".$start_stamp_begin.":00.000' AND '".$start_stamp_end.":59.999'"; }
	else {
		if (strlen($start_stamp_begin) > 0) { $sql_where_ands[] = "start_stamp >= '".$start_stamp_begin.":00.000'"; }
		if (strlen($start_stamp_end) > 0) { $sql_where_ands[] = "start_stamp <= '".$start_stamp_end.":59.999'"; }
	}
*/	if (strlen($answer_stamp_begin) > 0 && strlen($answer_stamp_end) > 0) { $sql_where_ands[] = "answer_stamp BETWEEN '".$answer_stamp_begin.":00.000' AND '".$answer_stamp_end.":59.999'"; }
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
                $sql_where = " where ".implode(" and ", $sql_where_ands)." and ";
	}

//calculate the seconds in different time frames
	$seconds_hour = 3600;
	$seconds_day = $seconds_hour * 24;
	$seconds_week = $seconds_day * 7;
	$seconds_month = $seconds_day * 30;

//get the call volume between a start end end time in seconds
	function get_call_volume_between($start, $end, $where) {
		global $db;
		if (strlen($where) == 0) {
			if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
				$where = "where ";
			}
			else {
				$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
			}
		}
		$sql = "select count(*) as count from v_xml_cdr ";
		$sql .= $where;
		$sql .= " start_epoch BETWEEN ".$start." AND ".$end." ";

		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		unset ($prep_statement, $sql);
		if (count($result) > 0) {
			foreach($result as $row) {
				return $row['count'];
			}
		}
		else {
			return false;
		}
		unset($prep_statement, $result, $sql);
	}

//get the call time in seconds between the start and end time in seconds
	function get_call_seconds_between($start, $end, $where) {
		global $db;
		if (strlen($where) == 0) {
			if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
				$where = "where ";
			}
			else {
				$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
			}
		}
		$sql = "select sum(billsec) as seconds from v_xml_cdr ";
		$sql .= $where;
		$sql .= " start_epoch BETWEEN ".$start." AND ".$end." ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		unset ($prep_statement, $sql);
		if (count($result) > 0) {
			foreach($result as $row) {
				$result = $row['seconds'];
				if (strlen($result) == 0) {
					return 0;
				}
				else {
					return $row['seconds'];
				}
			}
		}
		else {
			return false;
		}
		unset($prep_statement, $result, $sql);
	}
	//$call_seconds_1st_hour = get_call_seconds_between(3600, 0);
	//if (strlen($call_seconds_1st_hour) == 0) { $call_seconds_1st_hour = 0; }

	if (strlen(check_str($_GET['start_stamp_begin'])) > 0 && strlen(check_str($_GET['start_stamp_end'])) > 0 ) {
		$start_date = new DateTime(check_str($_GET['start_stamp_begin']));
		$end_date = new DateTime(check_str($_GET['start_stamp_end']));
		$time = $end_date->getTimestamp();
		$time = $time - $time % 3600;
		$hours = ($end_date->getTimestamp() - $start_date->getTimestamp()) / 3600;
	} else {
		//round down to the nearest hour
		$time = time() - time() % 3600;
		$hours = 23;
	}

//call info hour by hour
	for ($i = 0; $i <= $hours; $i++) {
		$start_epoch = $time - 3600*$i;
		$stop_epoch = $start_epoch + 3600;
		$stats[$i]['hours'] = $i + 1;
		$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
		$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
		$stats[$i]['start_epoch'] = $start_epoch;
		$stats[$i]['stop_epoch'] = $stop_epoch;
		$stats[$i]['volume'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $sql_where);
		$stats[$i]['seconds'] = get_call_seconds_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
		$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;
		$stats[$i]['avg_sec'] = ($stats[$i]['volume']==0) ? 0 : $stats[$i]['seconds'] / $stats[$i]['volume'];
		$stats[$i]['avg_min'] = (($stats[$i]['volume']==0) ? 0 : $stats[$i]['volume'] - $stats[$i]['missed']) / 60;

		//answer / seizure ratio
		if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
			$where = "where ";
		} else {
			$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
		}
		$where .= " billsec = '0' and ";
		$where .= " direction = 'inbound' and ";
		$stats[$i]['missed'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where);
		$stats[$i]['asr'] = ($stats[$i]['volume']==0) ? 0 : (($stats[$i]['volume'] - $stats[$i]['missed']) / ($stats[$i]['volume']) * 100);

		//average length of call
		$stats[$i]['aloc'] = ($stats[$i]['volume']==0) ? 0 : $stats[$i]['minutes'] / ($stats[$i]['volume'] - $stats[$i]['missed']);
	}

//call info for a day
	$i = $hours+1;
	$start_epoch = time() - $seconds_day;
	$stop_epoch = time();
	$stats[$i]['hours'] = 24;
	$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
	$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
	$stats[$i]['start_epoch'] = $start_epoch;
	$stats[$i]['stop_epoch'] = $stop_epoch;
	$stats[$i]['volume'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $sql_where);
	$stats[$i]['seconds'] = get_call_seconds_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;
	$stats[$i]['avg_sec'] = ($stats[$i]['volume']==0) ? 0 : $stats[$i]['seconds'] / $stats[$i]['volume'];
	$stats[$i]['avg_min'] = ($stats[$i]['volume'] - $stats[$i]['missed']) / (60*24);
	if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
		$where = "where ";
	} else {
		$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
	}
	$where .= " billsec = '0' and ";
	$where .= " direction = 'inbound' and ";
	$stats[$i]['missed'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where);
	$stats[$i]['asr'] = ($stats[$i]['volume']==0) ? 0 :(($stats[$i]['volume'] - $stats[$i]['missed']) / ($stats[$i]['volume']) * 100);
	$stats[$i]['aloc'] = ($stats[$i]['volume']==0) ? 0 :$stats[$i]['minutes'] / ($stats[$i]['volume'] - $stats[$i]['missed']);
	$i++;

//call info for a week
	$start_epoch = time() - $seconds_week;
	$stop_epoch = time();
	$stats[$i]['hours'] = 24 * 7;
	$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
	$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
	$stats[$i]['start_epoch'] = $start_epoch;
	$stats[$i]['stop_epoch'] = $stop_epoch;
	$stats[$i]['volume'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $sql_where);
	$stats[$i]['seconds'] = get_call_seconds_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;
	$stats[$i]['avg_sec'] = ($stats[$i]['volume']==0) ? 0 :$stats[$i]['seconds'] / $stats[$i]['volume'];
	$stats[$i]['avg_min'] = ($stats[$i]['volume']==0) ? 0 :($stats[$i]['volume'] - $stats[$i]['missed']) / (60*24*7);
	if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
		$where = "where ";
	} else {
		$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
	}
	$where .= " billsec = '0' and ";
	$where .= " direction = 'inbound' and ";
	$stats[$i]['missed'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where);
	$stats[$i]['asr'] = ($stats[$i]['volume']==0) ? 0 :(($stats[$i]['volume'] - $stats[$i]['missed']) / ($stats[$i]['volume']) * 100);
	$stats[$i]['aloc'] = ($stats[$i]['volume']==0) ? 0 :$stats[$i]['minutes'] / ($stats[$i]['volume'] - $stats[$i]['missed']);
	$i++;

//call info for a month
	$start_epoch = time() - $seconds_month;
	$stop_epoch = time();
	$stats[$i]['hours'] = 24 * 30;
	$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
	$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
	$stats[$i]['start_epoch'] = $start_epoch;
	$stats[$i]['stop_epoch'] = $stop_epoch;
	$stats[$i]['volume'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $sql_where);
	$stats[$i]['seconds'] = get_call_seconds_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;
	$stats[$i]['avg_sec'] = ($stats[$i]['volume']==0) ? 0 :$stats[$i]['seconds'] / $stats[$i]['volume'];
	$stats[$i]['avg_min'] = ($stats[$i]['volume'] - $stats[$i]['missed']) / (60*24*30);
	if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
		$where = "where ";
	} else {
		$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
	}
	$where .= " billsec = '0' and ";
	$where .= " direction = 'inbound' and ";
	$stats[$i]['missed'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where);
	$stats[$i]['asr'] = ($stats[$i]['volume']==0) ? 0 :(($stats[$i]['volume'] - $stats[$i]['missed']) / ($stats[$i]['volume']) * 100);
	$stats[$i]['aloc'] =($stats[$i]['volume']==0) ? 0 : $stats[$i]['minutes'] / ($stats[$i]['volume'] - $stats[$i]['missed']);
	$i++;

//show the graph

	$x = 0;
	foreach ($stats as $row) {
		$graph['volume'][$x][] = $row['start_epoch'] * 1000;
		$graph['volume'][$x][] = $row['volume']/1;
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
		$graph['missed'][$x][] = $row['missed']/1;
		if ($x == $hours) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['asr'][$x][] = $row['start_epoch'] * 1000;
		$graph['asr'][$x][] = round($row['asr'],2)/100;
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