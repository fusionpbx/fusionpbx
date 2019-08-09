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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('gateway_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//connect to event socket
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		if (strlen($_GET["a"]) > 0 && is_uuid($_GET["gateway"])) {
			$profile = $_GET["profile"];
			if (strlen($profile) == 0) {
				$profile = 'external';
			}
			if ($_GET["a"] == "stop") {
				$gateway_uuid = $_GET["gateway"];
				$cmd = 'api sofia profile '.$profile.' killgw '.$gateway_uuid;
				$response = trim(event_socket_request($fp, $cmd));
				$msg = '<strong>Stop Gateway:</strong><pre>'.$response.'</pre>';
			}
			if ($_GET["a"] == "start") {
				$gateway_uuid = $_GET["gateway"];
				$cmd = 'api sofia profile '.$profile.' rescan';
				$response = trim(event_socket_request($fp, $cmd));
				$msg = '<strong>Start Gateway:</strong><pre>'.$response.'</pre>';
			}
		}

		if (!function_exists('switch_gateway_status')) {
			function switch_gateway_status($gateway_uuid, $result_type = 'xml') {
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				$cmd = 'api sofia xmlstatus gateway '.$gateway_uuid;
				$response = trim(event_socket_request($fp, $cmd));
				if ($response == "Invalid Gateway!") {
					$cmd = 'api sofia xmlstatus gateway '.strtoupper($gateway_uuid);
					$response = trim(event_socket_request($fp, $cmd));
				}
				return $response;
			}
		}
	}

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-gateways']."</b></td>\n";
	echo "		<td align='right'>";
	if (permission_exists('gateway_all')) {
		if ($_GET['show'] == 'all') {
			echo "	<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "	<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='gateways.php?show=all';\">\n";
		}
	}
	echo "			<input type='button' class='btn' name='refresh' alt='".$text['button-refresh']."' onclick=\"window.location='gateways.php'\" value='".$text['button-refresh']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			<span class=\"vexpl\">\n";
	echo "				".$text['description-gateway']."\n";
	echo "			</span>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />\n";

//get total gateway count from the database
	$sql = "select count(*) from v_gateways ";
	if (!($_GET['show'] == "all" && permission_exists('gateway_all'))) {
		$sql .= "where (domain_uuid = :domain_uuid ".(permission_exists('gateway_domain') ? " or domain_uuid is null " : null).") ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	$database = new database;
	$total_gateways = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&order_by=".escape($order_by)."&order=".escape($order);
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls, $rows_per_page, $var_3) = paging($total_gateways, $param, $rows_per_page);
	$offset = $rows_per_page * $_GET['page'];

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'gateway', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$gateways = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($_GET['show'] == "all" && permission_exists('gateway_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('gateway', $text['label-gateway'], $order_by, $order);
	echo th_order_by('context', $text['label-context'], $order_by, $order);
	if ($fp) {
		echo "<th>".$text['label-status']."</th>\n";
		echo "<th>".$text['label-action']."</th>\n";
		echo "<th>".$text['label-state']."</th>\n";
	}
	echo th_order_by('hostname', $text['label-hostname'], $order_by, $order);
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('gateway_add')) {
		if ($_SESSION['limit']['gateways']['numeric'] == '' || ($_SESSION['limit']['gateways']['numeric'] != '' && $total_gateways < $_SESSION['limit']['gateways']['numeric'])) {
			echo "<a href='gateway_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($gateways)) {
		foreach($gateways as $row) {
			$tr_link = (permission_exists('gateway_edit')) ? "href='gateway_edit.php?id=".escape($row['gateway_uuid'])."'" : null;
			echo "<tr ".$tr_link.">\n";
			if ($_GET['show'] == "all" && permission_exists('gateway_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = escape($_SESSION['domains'][$row['domain_uuid']]['domain_name']);
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($domain)."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('gateway_edit')) {
				echo "<a href='gateway_edit.php?id=".escape($row['gateway_uuid'])."'>".escape($row["gateway"])."</a>";
			}
			else {
				echo $row["gateway"];
			}
			echo "</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row["context"])."</td>\n";
			if ($fp) {
				if ($row["enabled"] == "true") {
					$response = switch_gateway_status($row["gateway_uuid"]);
					if ($response == "Invalid Gateway!") {
						//not running
						echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-status-stopped']."</td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'><a href='gateways.php?a=start&gateway=".escape($row["gateway_uuid"])."&profile=".escape($row["profile"])."' alt='".$text['label-action-start']."'>".$text['label-action-start']."</a></td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
					}
					else {
						//running
						try {
							$xml = new SimpleXMLElement($response);
							$state = $xml->state;
							echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-status-running']."</td>\n";
							echo "	<td valign='top' class='".$row_style[$c]."'><a href='gateways.php?a=stop&gateway=".escape($row["gateway_uuid"])."&profile=".escape($row["profile"])."' alt='".$text['label-action-stop']."'>".$text['label-action-stop']."</a></td>\n";
							echo "	<td valign='top' class='".$row_style[$c]."'>".escape($state)."</td>\n"; //REGED, NOREG, UNREGED
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
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row["hostname"])."</td>\n";
				if ($row["enabled"] == "true") {
					echo "	<td valign='top' class='".$row_style[$c]."' style='align: center;'>".$text['label-true']."</td>\n";
				}
				else {
					echo "	<td valign='top' class='".$row_style[$c]."' style='align: center;'>".$text['label-false']."</td>\n";
				}
				echo "	<td valign='top' class='row_stylebg'>".escape($row["description"])."&nbsp;</td>\n";
				echo "	<td class='list_control_icons'>";
				if (permission_exists('gateway_edit')) {
					echo "<a href='gateway_edit.php?id=".escape($row['gateway_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
				}
				if (permission_exists('gateway_delete')) {
					echo "<a href='gateway_delete.php?id=".escape($row['gateway_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
				}
				echo "	</td>\n";
				echo "</tr>\n";
			}
			$c = $c ? 0 : 1;
		}
	}
	unset($gateways, $row);

	echo "<tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	echo $paging_controls."\n";
	echo "<br /><br />\n";

//include the footer
	require_once "resources/footer.php";

?>
