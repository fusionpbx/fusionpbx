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
        James Rose <james.o.rose@gmail.com>
        Portions created by the Initial Developer are Copyright (C) 2008-2012
        the Initial Developer. All Rights Reserved.

        Contributor(s):
        James Rose <james.o.rose@gmail.com>
        Mark J Crane <markjcrane@fusionpbx.com>
*/
//includes
//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
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
	require_once "resources/header.php";


$dom = date('m');
$year = date('Y');

for ($y = 0; $y < 4; $y++) {
	$fileheader = array('month', 'total calls', 'total seconds', 'total minutes', 'total hours', 'billing periods', 'rate', 'approx cost');

	if ($y == 0) {
		$table_name = "<b>Previous 12 Months Inbound Call Summary</b><br>";
		$sql_begin = "select billsec as seconds from v_xml_cdr where (domain_uuid = :domain_uuid and direction = 'inbound') ";
		$rate = 0.012;
		$bill_period = 60;
	}
	else if ($y == 1) {
		$table_name = "<b>Previous 12 Months Outbound Metered Call Summary</b><br>";
		$sql_begin = "select billsec as seconds from v_xml_cdr where (domain_uuid = :domain_uuid and direction = 'outbound') ";
		$sql_end = "and destination_number not like '%800_______' and destination_number not like '%888_______' and destination_number not like '%877_______' and destination_number not like '%866_______' and destination_number not like '%855_______' ";
		$rate = 0.0098;
		$rate = $rate / 10;
		$bill_period = 6;
	}
	else if ($y == 2) {
		$table_name = "<b>Previous 12 Months Toll Free Call Summary</b><br>";
		$sql_begin = "select billsec as seconds from v_xml_cdr where (domain_uuid = :domain_uuid and direction = 'outbound') ";
		$sql_end = "and (destination_number like '%800_______' or destination_number like '%888_______' or destination_number like '%877_______' or destination_number like '%866_______' or destination_number like '%855_______') ";
		$rate = 0;
		$bill_period = 6;
	}
	else if ($y == 3) {
		$table_name = "<b>Previous 12 Months Local Free Call Summary</b><br>";
		$sql_begin = "select billsec as seconds from v_xml_cdr where (domain_uuid = :domain_uuid and direction = 'local') ";
		$rate = 0;
		$bill_period = 6;
	}

	//set the style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table width='100%' cellpadding='0' cellspacing='0'>";

	$dom = date('m');
	$year = date('Y');
	$lyear = $year;
	$last_month = $year."-".$dolm."-01";
	$this_month = $year."-".$dom."-01";

	echo $table_name;

	foreach ($fileheader as $tr) {
		echo "<th>".$tr."</th>";
	}

	for ($x = 0; $x < 12; $x++) {
		if ($dom == 1) {
			$dom = 12;
			$year = $year - 1;
		}
		else if ($x == 0) {
			$dom = $dom;
		}
		else {
			$dom = $dom - 1;
		}
		if ($dom == 1) {
			$dolm = 12;
			$lyear = $lyear - 1;
		}
		else {
			$dolm = $dom - 1;
		}

		//convert to int
		$dom = $dom * 1;
		$dolm = $dolm * 1;

		//back to string, prepend 0)
		if ( $dolm < 10 ) {
			$dolm = "0".$dolm;
		}
		if ( $dom < 10 ) {
			$dom = "0".$dom;
		}
		$last_month = $lyear."-".$dolm."-01";
		$this_month = $year."-".$dom."-01";

		$sql = $sql_begin." and (start_stamp >= :last_month and start_stamp < :this_month) ".$sql_end;
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['last_month'] = $last_month;
		$parameters['this_month'] = $this_month;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		unset($sql);
		$max = sizeof($result) - 1;

		$i = 0;

		echo "<tr>";
		//echo "  <td valign='top' class='".$row_style[$c]."'>".($i+1)."</td>\n";
		if ($max > 0) {
			foreach ($result as $row) {
				$result = $row['seconds'];
				$billtime_1 = intval($result / $bill_period);
				$billtime_2 = $result % $bill_period;

				if (($result % $bill_period) != 0) {
					//need to round up for billing period
					$billtime = $billtime + intval($result / $bill_period) + 1;
					//echo " mod worked ";
				}
				else {
					//no need to round up for billing period
					$billtime = $billtime + intval($result / $bill_period);
				}
				$tottime = $tottime + $result;
			}

			echo "	<td valign='top' class='".$row_style[$c]."'>".$dolm."/".$lyear."</td>";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$max."</td>";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$tottime."</td>";
			$mintime = $tottime / $bill_period;
			echo "	<td valign='top' class='".$row_style[$c]."'>".round($mintime,1)."</td>";
			$hourtime = $tottime / 3600;
			echo "	<td valign='top' class='".$row_style[$c]."'>".round($hourtime,1)."</td>";
			$tot_cost = $rate * $billtime ;
			echo "	<td valign='top' class='".$row_style[$c]."'>".$billtime."</td>";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$rate."</td>";
			echo "	<td valign='top' class='".$row_style[$c]."'>$".round($tot_cost,2)."</td>";

			$c = $c ? 0 : 1;
		}
		echo "</tr>";

		$max = 0;
		$tottime = 0;
		$hourtime = 0;
		$billtime = 0;
		$tot_cost = 0;
		$mintime = 0;
	}
	echo "</table>";
	echo "<br>";

}
//show the footer
	require_once "resources/footer.php";

?>