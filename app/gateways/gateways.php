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
if (permission_exists('gateway_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//connect to event socket
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		if (strlen($_GET["a"]) > 0) {
			$profile = check_str($_GET["profile"]);
			if (strlen($profile) == 0) {
				$profile = 'external';
			}
			if ($_GET["a"] == "stop") {
				$gateway_uuid = check_str($_GET["gateway"]);
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
	echo "<br />";
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-gateways']."</b></td>\n";
	echo "		<td align='right'>";
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

//prepare to page the results
	$sql = "select count(*) as num_rows from v_gateways ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
	$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = '0';
		}
	}

//get the list
	$sql = "select * from v_gateways ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($order_by) == 0) {
		$sql .= "order by gateway asc ";
	}
	else {
		$sql .= "order by $order_by $order ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

	$rows_per_page = 150;
	$param = "";
	$page = check_str($_GET['page']);
	if (strlen($page) == 0) { $page = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('gateway', $text['label-gateway'], $order_by, $order);
	echo th_order_by('context', $text['label-context'], $order_by, $order);
	if ($fp) {
		echo "<th>".$text['label-status']."</th>\n";
		echo "<th>".$text['label-action']."</th>\n";
		echo "<th>".$text['label-state']."</th>\n";
	}
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('gateway_add')) {
		echo "<a href='gateway_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($num_rows > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('gateway_edit')) ? "href='gateway_edit.php?id=".$row['gateway_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('gateway_edit')) {
				echo "<a href='gateway_edit.php?id=".$row['gateway_uuid']."'>".$row["gateway"]."</a>";
			}
			else {
				echo $row["gateway"];
			}
			echo "</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row["context"]."</td>\n";
			if ($fp) {
				if ($row["enabled"] == "true") {
					$response = switch_gateway_status($row["gateway_uuid"]);
					if ($response == "Invalid Gateway!") {
						//not running
						echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-status-stopped']."</td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'><a href='gateways.php?a=start&gateway=".$row["gateway_uuid"]."&profile=".$row["profile"]."' alt='".$text['label-action-start']."'>".$text['label-action-start']."</a></td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
					}
					else {
						//running
						try {
							$xml = new SimpleXMLElement($response);
							$state = $xml->state;
							echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-status-running']."</td>\n";
							echo "	<td valign='top' class='".$row_style[$c]."'><a href='gateways.php?a=stop&gateway=".$row["gateway_uuid"]."&profile=".$row["profile"]."' alt='".$text['label-action-stop']."'>".$text['label-action-stop']."</a></td>\n";
							echo "	<td valign='top' class='".$row_style[$c]."'>".$state."</td>\n"; //REGED, NOREG, UNREGED
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
				if ($row["enabled"] == "true") {
					echo "	<td valign='top' class='".$row_style[$c]."' style='align: center;'>".$text['label-true']."</td>\n";
				}
				else {
					echo "	<td valign='top' class='".$row_style[$c]."' style='align: center;'>".$text['label-false']."</td>\n";
				}
				echo "	<td valign='top' class='row_stylebg'>".$row["description"]."&nbsp;</td>\n";
				echo "	<td class='list_control_icons'>";
				if (permission_exists('gateway_edit')) {
					echo "<a href='gateway_edit.php?id=".$row['gateway_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
				}
				if (permission_exists('gateway_delete')) {
					echo "<a href='gateway_delete.php?id=".$row['gateway_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
				}
				echo "	</td>\n";
				echo "</tr>\n";
			}
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='8' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('gateway_add')) {
		echo "<a href='gateway_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>";
	echo "</div>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>