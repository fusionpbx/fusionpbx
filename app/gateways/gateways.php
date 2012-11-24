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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('gateways_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

$order_by = $_GET["order_by"];
$order = $_GET["order"];

//connect to event socket
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if ($fp) {
	if (strlen($_GET["a"]) > 0) {
		$profile = $_GET["profile"];
		if (strlen($profile) == 0) {
			$profile = 'external';
		}
		if ($_GET["a"] == "stop") {
			$gateway_name = $_GET["gateway"];
			if (count($_SESSION["domains"]) > 1) {
				$cmd = 'api sofia profile '.$profile.' killgw '.$_SESSION['domain_name'].'-'.$gateway_name;
			}
			else {
				$cmd = 'api sofia profile '.$profile.' killgw '.$gateway_name;
			}
			$response = trim(event_socket_request($fp, $cmd));
			$msg = '<strong>Stop Gateway:</strong><pre>'.$response.'</pre>';
		}
		if ($_GET["a"] == "start") {
			$gateway_name = $_GET["gateway"];
			$cmd = 'api sofia profile '.$profile.' rescan';
			$response = trim(event_socket_request($fp, $cmd));
			$msg = '<strong>Start Gateway:</strong><pre>'.$response.'</pre>';
		}
	}

	if (!function_exists('switch_gateway_status')) {
		function switch_gateway_status($gateway_name, $result_type = 'xml') {
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if (count($_SESSION["domains"]) > 1) {
				$cmd = 'api sofia xmlstatus gateway '.$_SESSION['domain_name'].'-'.$gateway_name;
			}
			else {
				$cmd = 'api sofia xmlstatus gateway '.$gateway_name;
			}
			return trim(event_socket_request($fp, $cmd));
		}
	}
}

echo "<div align='center'>";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
echo "<tr class='border'>\n";
echo "	<td align=\"center\">\n";
echo "		<br>";

echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "  <tr>\n";
echo "    <td align='left'><span class=\"vexpl\">\n";
echo "        <strong>Gateways</strong></span>\n";
echo "    </td>\n";
echo "    <td align='right'>";
echo "        <input type='button' class='btn' name='' alt='back' onclick=\"window.location='gateways.php'\" value='Refresh'>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td align='left' colspan='2'>\n";
echo "      <span class=\"vexpl\">\n";
echo "          Gateways provide access into other voice networks. These can be voice providers or other systems that require SIP registration.\n";
echo "      </span>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "</table>";

echo "<br />\n";
echo "<br />\n";

$sql = "select * from v_gateways ";
$sql .= "where domain_uuid = '$domain_uuid' ";
if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
$num_rows = count($result);
unset ($prep_statement, $result, $sql);

$rows_per_page = 10;
$param = "";
$page = $_GET['page'];
if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
$offset = $rows_per_page * $page; 

$sql = "select * from v_gateways ";
$sql .= "where domain_uuid = '$domain_uuid' ";
if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
$sql .= " limit $rows_per_page offset $offset ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
$result_count = count($result);
unset ($prep_statement, $sql);

$c = 0;
$row_style["0"] = "row_style0";
$row_style["1"] = "row_style1";

echo "<div align='center'>\n";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";
echo th_order_by('gateway', 'Gateway', $order_by, $order);
echo th_order_by('context', 'Context', $order_by, $order);
if ($fp) {
	echo "<th>Status</th>\n";
	echo "<th>Action</th>\n";
	echo "<th>State</th>\n";
}
echo th_order_by('enabled', 'Enabled', $order_by, $order);
echo th_order_by('description', 'Gateway Description', $order_by, $order);
echo "<td align='right' width='42'>\n";
if (permission_exists('gateways_add')) {
	echo "	<a href='gateway_edit.php' alt='add'>$v_link_label_add</a>\n";
}
echo "</td>\n";
echo "<tr>\n";

if ($result_count == 0) {
	//no results
}
else { //received results
	foreach($result as $row) {
		echo "<tr >\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row["gateway"]."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row["context"]."</td>\n";

		if ($fp) {
			if ($row["enabled"] == "true") {
				$response = switch_gateway_status($row["gateway"]);
				if ($response == "Invalid Gateway!") {
					//not running
					echo "	<td valign='top' class='".$row_style[$c]."'>Stopped</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'><a href='gateways.php?a=start&gateway=".$row["gateway"]."&profile=".$row["profile"]."' alt='start'>Start</a></td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
				}
				else {
					//running
					try {
						$xml = new SimpleXMLElement($response);
						$state = $xml->state;
						echo "	<td valign='top' class='".$row_style[$c]."'>Running</td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'><a href='gateways.php?a=stop&gateway=".$row["gateway"]."&profile=".$row["profile"]."' alt='stop'>Stop</a></td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'>".$state."</td>\n";
					}
					catch(Exception $e) {
						//echo $e->getMessage();
					}
				}
			}
			else {
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
			}
		}

		echo "	<td valign='top' class='".$row_style[$c]."' style='align: center;'>".$row["enabled"]."</td>\n";
		echo "	<td valign='top' class='row_stylebg'>".$row["description"]."</td>\n";
		echo "	<td valign='top' align='right'>\n";
		if (permission_exists('gateways_edit')) {
			echo "		<a href='gateway_edit.php?id=".$row["gateway_uuid"]."' alt='edit'>$v_link_label_edit</a>\n";
		}
		if (permission_exists('gateways_delete')) {
			echo "		<a href='gateway_delete.php?id=".$row["gateway_uuid"]."' onclick=\"return confirm('Do you really want to delete this?')\" alt='delete'>$v_link_label_delete</a>\n";
		}
		echo "	</td>\n";
		echo "</tr>\n";
		if ($c==0) { $c=1; } else { $c=0; }
	} //end foreach
	unset($sql, $result, $row_count);
} //end if results

echo "<tr>\n";
echo "<td colspan='8'>\n";
echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
echo "		<tr>\n";
echo "			<td width='33.3%' nowrap>&nbsp;</td>\n";
echo "			<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
echo "			<td width='33.3%' align='right'>\n";
if (permission_exists('gateways_add')) {
	echo "				<a href='gateway_edit.php' alt='add'>$v_link_label_add</a>\n";
}
echo "			</td>\n";
echo "		</tr>\n";
echo "	</table>\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td colspan='8' align='left'>\n";
echo "<br />\n";
if ($v_path_show) {
	echo $_SESSION['switch']['gateways']['dir']."/sip_profiles\n";
}
echo "</td>\n";
echo "</tr>\n";

echo "</table>";
echo "</div>";
echo "<br><br>";
echo "<br><br>";

echo "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";
echo "<br><br>";

require_once "includes/footer.php";
?>