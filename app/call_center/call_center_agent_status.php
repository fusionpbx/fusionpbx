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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('call_center_agents_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

//setup the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get the http post values and set them as php variables
	if (count($_POST)>0) {
		//include the dnd php class
		include "includes/classes/do_not_disturb.php";

		foreach($_POST['agents'] as $row) {
			if (strlen($row['status']) > 0) {
				//agent set status
					if ($fp) {
						//set the user_status
							$sql  = "update v_users set ";
							$sql .= "user_status = '".$row['status']."' ";
							$sql .= "where domain_uuid = '$domain_uuid' ";
							$sql .= "and username = '".$row['name']."' ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
						//set the call center status
							if ($row['status'] == "Do Not Disturb") {
								//set the default dnd action
									$dnd_action = "add";
								//set the call center status to Logged Out
									$cmd = "api callcenter_config agent set status ".$row['name']."@".$_SESSION['domains'][$domain_uuid]['domain_name']." 'Logged Out'";
							}
							else {
								$cmd = "api callcenter_config agent set status ".$row['name']."@".$_SESSION['domains'][$domain_uuid]['domain_name']." '".$row['status']."'";
							}
							//echo $cmd."<br />\n";
							$response = event_socket_request($fp, $cmd);
							usleep(200);
					}

				//loop through the list of assigned extensions
					foreach ($_SESSION['user']['extension'] as &$sub_row) {
						$extension = $sub_row["user"];
						//hunt_group information used to determine if this is an add or an update
							$sql  = "select * from v_hunt_groups ";
							$sql .= "where domain_uuid = '$domain_uuid' ";
							$sql .= "and hunt_group_extension = '$extension' ";
							$prep_statement_2 = $db->prepare(check_sql($sql));
							$prep_statement_2->execute();
							$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
							foreach ($result2 as &$row2) {
								if ($row2["hunt_group_type"] == 'dnd') {
									$dnd_action = "update";
									$dnd_uuid = $row2["hunt_group_uuid"];
								}
							}
							unset ($prep_statement_2, $result2, $row2);
						//add or update dnd
							$dnd = new do_not_disturb;
							//$dnd->debug = false;
							$dnd->domain_uuid = $domain_uuid;
							$dnd->dnd_uuid = $dnd_uuid;
							$dnd->domain_name = $_SESSION['domain_name'];
							$dnd->extension = $extension;
							if ($row['status'] == "Do Not Disturb") {
								$dnd->dnd_enabled = "true";
								if ($dnd_action == "add") {
									$dnd->dnd_add();
								}
								if ($dnd_action == "update") {
									$dnd->dnd_update();
								}
							}
							else {
								//for other status disable dnd
								if ($dnd_action == "update") {
									$dnd->dnd_enabled = "false";
									$dnd->dnd_update();
								}
							}
							unset($dnd);
					}
					unset ($prep_statement);
			}
		}
	}

//get the agent list from event socket
	$switch_cmd = 'callcenter_config agent list';
	$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
	$agent_array = csv_to_named_array($event_socket_str, '|');
//set the status on the user_array by using the extension as the key
	foreach ($agent_array as $row) {
		if (count($_SESSION['domains']) == 1) {
			//get the extension status from the call center agent list
			preg_match('/user\/(\d{2,7})/', $row['contact'], $matches);
			$extension = $matches[1];
			$user_array[$extension]['username'] = $tmp[0];
			if ($user_array[$extension]['user_status'] != "Do Not Disturb") {
				$user_array[$extension]['user_status'] = $row['status'];
			}
		} else {
			$tmp = explode('@',$row["name"]);
			if ($tmp[1] == $_SESSION['domain_name']) {
				//get the extension status from the call center agent list
				preg_match('/user\/(\d{2,7})/', $row['contact'], $matches);
				$extension = $matches[1];
				$user_array[$extension]['username'] = $tmp[0];
				if ($user_array[$extension]['user_status'] != "Do Not Disturb") {
					$user_array[$extension]['user_status'] = $row['status'];
				}
			}
		}
	}

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>Call Center Agent Status</b></td>\n";
	echo "<td width='50%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='add' onclick=\"window.location='call_center_agent_status.php'\" value='Refresh'>\n";
	echo "	<input type='button' class='btn' name='' alt='add' onclick=\"window.location='call_center_queues.php'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "List all the call center agents with the option to change the status of one or more agents.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>Agent</th>\n";
	echo "<th>Status</th>\n";
	echo "<th>Options</th>\n";
	echo "<tr>\n";

	$x = 0;
	foreach($agent_array as $row) {
		$tmp = explode('@',$row["name"]);
		$agent_name = $tmp[0];
		if ($tmp[1] == $_SESSION['domain_name']) {
			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['status']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo "		<input type='hidden' name='agents[".$x."][name]' id='agent_".$x."_name' value='".$agent_name."'>\n";
			echo "		<input type='radio' name='agents[".$x."][status]' id='agent_".$x."_status_no_change' value='' checked='checked'><label for='agent_".$x."_status_no_change'>No Change</label>\n";
			echo "		<input type='radio' name='agents[".$x."][status]' id='agent_".$x."_status_available' value='Available'><label for='agent_".$x."_status_available'>Available</label>\n";
			echo "		<input type='radio' name='agents[".$x."][status]' id='agent_".$x."_status_logged_out' value='Logged Out'><label for='agent_".$x."_status_logged_out'>Logged Out</label>\n"; 
			echo "		<input type='radio' name='agents[".$x."][status]' id='agent_".$x."_status_on_break' value='On Break'><label for='agent_".$x."_status_on_break'>On Break</label>\n";
			echo "		<input type='radio' name='agents[".$x."][status]' id='agent_".$x."_status_dnd' value='Do Not Disturb'><label for='agent_".$x."_status_dnd'>DND</label>\n";
			echo "	</td>\n";	
			echo "</tr>\n";
		}
		if ($c==0) { $c=1; } else { $c=0; }
		$x++;
	} //end foreach
	unset($sql, $result, $row_count);

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "			<br />\n";
	echo "			<input type='submit' name='submit' class='btn' value='Update Status'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
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
	echo "</form>\n";
	echo "<br><br>";

//show the footer
	require_once "includes/footer.php";
?>
