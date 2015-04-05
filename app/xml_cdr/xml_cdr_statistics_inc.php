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

//show all call detail records to admin and superadmin. for everyone else show only the call details for extensions assigned to them
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

//round down to the nearest hour
	$time = time() - time() % 3600;

//call info hour by hour
	for ($i = 0; $i <= 23; $i++) {
		$start_epoch = $time - 3600*$i;
		$stop_epoch = $start_epoch + 3600;
		$stats[$i]['hours'] = $i + 1;
		$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
		$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
		$stats[$i]['start_epoch'] = $start_epoch;
		$stats[$i]['stop_epoch'] = $stop_epoch;
		$stats[$i]['volume'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
		$stats[$i]['seconds'] = get_call_seconds_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
		$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;
		$stats[$i]['avg_sec'] = $stats[$i]['seconds'] / $stats[$i]['volume'];
		$stats[$i]['avg_min'] = ($stats[$i]['volume'] - $stats[$i]['missed']) / 60;

		//answer / seizure ratio
		if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
			$where = "where ";
		} else {
			$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
		}
		$where .= " billsec = '0' and ";
		$where .= " direction = 'inbound' and ";
		$stats[$i]['missed'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where);
		$stats[$i]['asr'] = (($stats[$i]['volume'] - $stats[$i]['missed']) / ($stats[$i]['volume']) * 100);

		//average length of call
		$stats[$i]['aloc'] = $stats[$i]['minutes'] / ($stats[$i]['volume'] - $stats[$i]['missed']);
	}

//call info for a day
	$i = 24;
	$start_epoch = time() - $seconds_day;
	$stop_epoch = time();
	$stats[$i]['hours'] = 24;
	$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
	$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
	$stats[$i]['start_epoch'] = $start_epoch;
	$stats[$i]['stop_epoch'] = $stop_epoch;
	$stats[$i]['volume'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['seconds'] = get_call_seconds_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;
	$stats[$i]['avg_sec'] = $stats[$i]['seconds'] / $stats[$i]['volume'];
	$stats[$i]['avg_min'] = ($stats[$i]['volume'] - $stats[$i]['missed']) / (60*24);
	if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
		$where = "where ";
	} else {
		$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
	}
	$where .= " billsec = '0' and ";
	$where .= " direction = 'inbound' and ";
	$stats[$i]['missed'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where);
	$stats[$i]['asr'] = (($stats[$i]['volume'] - $stats[$i]['missed']) / ($stats[$i]['volume']) * 100);
	$stats[$i]['aloc'] = $stats[$i]['minutes'] / ($stats[$i]['volume'] - $stats[$i]['missed']);
	$i++;

//call info for a week
	$start_epoch = time() - $seconds_week;
	$stop_epoch = time();
	$stats[$i]['hours'] = 24 * 7;
	$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
	$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
	$stats[$i]['start_epoch'] = $start_epoch;
	$stats[$i]['stop_epoch'] = $stop_epoch;
	$stats[$i]['volume'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['seconds'] = get_call_seconds_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;
	$stats[$i]['avg_sec'] = $stats[$i]['seconds'] / $stats[$i]['volume'];
	$stats[$i]['avg_min'] = ($stats[$i]['volume'] - $stats[$i]['missed']) / (60*24*7);
	if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
		$where = "where ";
	} else {
		$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
	}
	$where .= " billsec = '0' and ";
	$where .= " direction = 'inbound' and ";
	$stats[$i]['missed'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where);
	$stats[$i]['asr'] = (($stats[$i]['volume'] - $stats[$i]['missed']) / ($stats[$i]['volume']) * 100);
	$stats[$i]['aloc'] = $stats[$i]['minutes'] / ($stats[$i]['volume'] - $stats[$i]['missed']);
	$i++;

//call info for a month
	$start_epoch = time() - $seconds_month;
	$stop_epoch = time();
	$stats[$i]['hours'] = 24 * 30;
	$stats[$i]['start_stamp'] = date('Y-m-d h:n:s', $start_epoch);
	$stats[$i]['stop_stamp'] = date('Y-m-d h:n:s', $stop_epoch);
	$stats[$i]['start_epoch'] = $start_epoch;
	$stats[$i]['stop_epoch'] = $stop_epoch;
	$stats[$i]['volume'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['seconds'] = get_call_seconds_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], '');
	$stats[$i]['minutes'] = $stats[$i]['seconds'] / 60;
	$stats[$i]['avg_sec'] = $stats[$i]['seconds'] / $stats[$i]['volume'];
	$stats[$i]['avg_min'] = ($stats[$i]['volume'] - $stats[$i]['missed']) / (60*24*30);
	if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
		$where = "where ";
	} else {
		$where = "where domain_uuid = '".$_SESSION['domain_uuid']."' and ";
	}
	$where .= " billsec = '0' and ";
	$where .= " direction = 'inbound' and ";
	$stats[$i]['missed'] = get_call_volume_between($stats[$i]['start_epoch'], $stats[$i]['stop_epoch'], $where);
	$stats[$i]['asr'] = (($stats[$i]['volume'] - $stats[$i]['missed']) / ($stats[$i]['volume']) * 100);
	$stats[$i]['aloc'] = $stats[$i]['minutes'] / ($stats[$i]['volume'] - $stats[$i]['missed']);
	$i++;

//show the graph
	$x = 0;
	foreach ($stats as $row) {
		$graph['volume'][$x][] = date('H', $row['start_epoch']);
		$graph['volume'][$x][] = $row['volume']/1;
		if ($x == 23) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['minutes'][$x][] = date('H', $row['start_epoch']);
		$graph['minutes'][$x][] = round($row['minutes'],2);
		if ($x == 23) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['call_per_min'][$x][] = date('H', $row['start_epoch']);
		$graph['call_per_min'][$x][] = round($row['avg_min'],2);
		if ($x == 23) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['missed'][$x][] = date('H', $row['start_epoch']);
		$graph['missed'][$x][] = $row['missed']/1;
		if ($x == 23) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['asr'][$x][] = date('H', $row['start_epoch']);
		$graph['asr'][$x][] = round($row['asr'],2)/100;
		if ($x == 23) { break; }
		$x++;
	}
	$x = 0;
	foreach ($stats as $row) {
		$graph['aloc'][$x][] = date('H', $row['start_epoch']);
		$graph['aloc'][$x][] = round($row['aloc'],2);
		if ($x == 23) { break; }
		$x++;
	}

?>