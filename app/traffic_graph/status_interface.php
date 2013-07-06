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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('traffic_graph_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//http get saved to a php variable
	$interface_name = $_GET['interface'];

//notes
	//links to get netstat working in a jail
		//http://ggeek.blogspot.com/2008/10/freebsd-jails-and-net-snmp.html
		//http://am-productions.biz/docs/devfs.rules.php
		//http://ggeek.blogspot.com/2008/10/freebsd-jails-and-net-snmp.html

	//freebsd
		//netstat -i -nWb -f link
		//Name      Mtu Network       Address              Ipkts Ierrs     Ibytes    Opkts Oerrs     Obytes  Coll
		//dc0      1500 <Link#1>      00:07:95:b3:e9:14 38081447     0 1267085007 10894865     0 1825820736     0
		//xl0      1500 <Link#2>      00:08:74:11:f8:44 10910693     0 1855752990 24502116  7466  459761069 4074482

	//linux get the info need to parse it
		//list a single interface doesn't work on all versions of linux
			//netstat -I=eth0
		//list all interfaces
			//netstat -i
			//Kernel Interface table
			//Iface       MTU Met    RX-OK RX-ERR RX-DRP RX-OVR    TX-OK TX-ERR TX-DRP TX-OVR Flg
			//eth0       1500   0 25878419      0      0      0 32196826      0      0      0 BMRU
			//eth1       1500   0    17537      0      0      0    27819      0      0      0 BMRU
			//lo        16436   0    99683      0      0      0    99683      0      0      0 LRU

	//osx
		//list all interfaces
			//netstat -i
		//Name  Mtu   Network       Address            Ipkts Ierrs    Opkts Oerrs  Coll
			//lo0   16384 <Link#1>                       2761841     0  2761840     0     0
			//lo0   16384 localhost   ::1                2761841     -  2761840     -     -
			//lo0   16384 localhost   fe80:1::1          2761841     -  2761840     -     -
			//lo0   16384 127           localhost        2761841     -  2761840     -     -
			//gif0* 1280  <Link#2>                             0     0        0     0     0
			//stf0* 1280  <Link#3>                             0     0        0     0     0
			//en0   1500  <Link#4>    c4:2c:03:0b:52:de  3242578     0  3286461     0     0
			//en0   1500  macolantern fe80:4::c62c:3ff:  3242578     -  3286461     -     -
			//en0   1500  192.168.2     744.ssenn.net    3242578     -  3286461     -     -

	//example code
		//exec("/usr/bin/netstat -i", $result_array);
		//echo "<pre>\n";
		//print_r($result_array);
		//echo "</pre>\n";

		//$interface_name_info = preg_split("/\s+/", $result_array[0]);
		//$interface_value_info = preg_split("/\s+/", $result_array[1]);
		//$netstat_array = array();
		//foreach ($interface_name_info as $key => $value) {
		//	$netstat_array[$value] = $interface_value_info[$key];
		//}
		//echo "<pre>\n";
		//print_r($netstat_array);
		//echo "</pre>\n";

// run netstat to determine interface info
	exec("netstat -i -nWb -f link", $result_array);
	if (count($result_array) == 0) {
		exec("netstat -i", $result_array);
	}

//parse the data into a named array
	$x = 0;
	foreach ($result_array as $key => $value) {
		if ($value != "Kernel Interface table") {
			if ($x == 0) {
				//get the names of the values
					$interface_name_info = preg_split("/\s+/", $result_array[$key]);
			}
			else {
				//get the values
					$interface_value_info = preg_split("/\s+/", $result_array[$key]);
				//find data for the selected interface
					if ($interface_name == $interface_value_info[0]) {
						$netstat_array = array();
						foreach ($interface_name_info as $sub_key => $sub_value) {
							$netstat_array[$sub_value] = $interface_value_info[$sub_key];
						}
					}
			}
			$x++;
		}
	}

//set the correct tx and rx values
	if (strlen($netstat_array['Iface']) > 0) {
		$in_bytes = ($netstat_array['RX-OK']*1024);
		$out_bytes = ($netstat_array['TX-OK']*1024);
	}
	if (strlen($netstat_array['Name']) > 0) {
		$in_bytes = $netstat_array['Ibytes'];
		$out_bytes = $netstat_array['Obytes'];
	}

//get the timing
	$tmp_time = gettimeofday();
	$timing = (double)$tmp_time["sec"] + (double)$tmp_time["usec"] / 1000000.0;

//provide the timing, input bytes and output bytes
	echo "$timing|" . $in_bytes . "|" . $out_bytes . "\n";

?>
