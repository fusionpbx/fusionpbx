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
	Portions created by the Initial Developer are Copyright (C) 2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_center_queue_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/call_centers');

//includes and title
	require_once "resources/header.php";
	$document['title'] = $text['title-call_center_queues'];
	require_once "resources/paging.php";

//get http variables and set as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//setup the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get the http post values and set them as php variables
	if (count($_POST) > 0) {
		foreach($_POST['agents'] as $row) {
			if (strlen($row['agent_status']) > 0) {
				//echo "<pre>\n";
				//print_r($row);
				//echo "</pre>\n";

				//agent set status
					if ($fp) {
						//set the user_status
							$sql  = "update v_users set ";
							$sql .= "user_status = :user_status ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and username = :username ";
							$parameters['user_status'] = $row['agent_status'];
							$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
							$parameters['username'] = $row['agent_name'];
							//$database = new database;
							//$database->execute($sql, $parameters);
							//unset($sql, $parameters);

						//set the agent status to available and assign the agent to the queue with the tier
							if ($row['agent_status'] == 'Available') {
								//set the call center status
								//$cmd = "api callcenter_config agent set status ".$row['agent_name']."@".$_SESSION['domain_name']." '".$row['agent_status']."'";
								//$response = event_socket_request($fp, $cmd);

								//assign the agent to the queue
								$cmd = "api callcenter_config tier add ".$row['queue_name']."@".$_SESSION['domain_name']." ".$row['agent_name']."@".$_SESSION['domain_name']." 1 1";
								$response = event_socket_request($fp, $cmd);
							}

						//un-assign the agent from the queue
							if ($row['agent_status'] == 'Logged Out') {
								$cmd = "api callcenter_config tier del ".$row['queue_name']."@".$_SESSION['domain_name']." ".$row['agent_name']."@".$_SESSION['domain_name'];
								$response = event_socket_request($fp, $cmd);
							}
							
							//echo $cmd."\n";
							usleep(200);
					}
			}
		}
	}

//get the agent list from event socket
	$switch_cmd = 'callcenter_config tier list';
	$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
	$call_center_tiers = csv_to_named_array($event_socket_str, '|');
	//echo "<pre>\n";
	//print_r($call_center_tiers);
	//echo "</pre>\n";

//get the call center queues from the database
	$sql = "select * from v_call_center_queues ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by queue_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$call_center_queues = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the agents from the database
	$sql = "select * from v_call_center_agents ";
	$sql .= "where user_uuid = :user_uuid ";
	$sql .= "and domain_uuid = :domain_uuid ";
	//$sql .= "ORDER BY agent_name ASC ";
	$parameters['user_uuid'] = $_SESSION['user_uuid'];
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$agent = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);
	//echo "<pre>\n";
	//print_r($agent);
	//echo "</pre>\n";

//update the queue status
	$x = 0;
	foreach ($call_center_queues as $queue) {
		$call_center_queues[$x]['queue_status'] = 'Logged Out';
		foreach ($call_center_tiers as $tier) {
			if ($queue['queue_name'] .'@'. $_SESSION['domain_name'] == $tier['queue'] 
				&& $agent['agent_name'] .'@'. $_SESSION['domain_name'] == $tier['agent']) {
				$call_center_queues[$x]['queue_status'] = 'Available';
			}
		}
		$x++;
	}
	//echo "<pre>\n";
	//print_r($call_center_queues);
	//echo "</pre>\n";

//set the row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "	<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-call_center_queues']."</b></td>\n";
	echo "	<td width='50%' align='right'>\n";
	echo "		<h3>".$text['label-agent']."</h3> <b>".$agent['agent_name']."</b>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	//echo "<tr>\n";
	//echo "	<td align='left' colspan='2'>\n";
	//echo $text['description-call_center_queues']."<br /><br />\n";
	//echo "	</td>\n";
	//echo "</tr>\n";
	echo "</table>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th>".$text['label-queue_name']."</th>\n";
	echo "	<th>".$text['label-status']."</th>\n";
	echo "	<th>".$text['label-options']."</th>\n";
	echo "</tr>\n";

	if (count($call_center_queues) > 0) {
		$x = 0;
		foreach($call_center_queues as $row) {
			echo "<tr>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			echo "		".escape($row['queue_name'])."\n";
			echo "	</td>\n";

			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			if ($row['queue_status'] == "Available") {
				echo $text['option-available'];
			}
			if ($row['queue_status'] == "Logged Out") {
				echo $text['option-logged_out'];
			}
			echo "	</td>\n";

			echo "	<td valign='middle' class='".$row_style[$c]."' nowrap='nowrap'>";
			echo "		<input type='hidden' name='agents[".$x."][queue_name]' id='agent_".$x."_name' value='".escape($row['queue_name'])."'>\n";
			echo "		<input type='hidden' name='agents[".$x."][agent_name]' id='agent_".$x."_name' value='".$agent['agent_name']."'>\n";
			echo "		<input type='hidden' name='agents[".$x."][id]' id='agent_".$x."_name' value='".$agent['call_center_agent_uuid']."'>\n";
			//echo "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_no_change' value='' checked='checked'>&nbsp;<label for='agent_".$x."_status_no_change'>".$text['option-no_change']."</label>&nbsp;\n";
			echo "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_available' value='Available'>&nbsp;<label for='agent_".$x."_status_available'>".$text['option-available']."</label>&nbsp;\n";
			echo "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_logged_out' value='Logged Out'>&nbsp;<label for='agent_".$x."_status_logged_out'>".$text['option-logged_out']."</label>&nbsp;\n";
			//echo "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_on_break' value='On Break'>&nbsp;<label for='agent_".$x."_status_on_break'>".$text['option-on_break']."</label>&nbsp;\n";
			//echo "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_dnd' value='Do Not Disturb'><label for='agent_".$x."_status_dnd'>&nbsp;".$text['option-do_not_disturb']."</label>\n";
			echo "	</td>\n";

			//echo "	<td valign='top' class='row_stylebg'>".$row[queue_description]."&nbsp;</td>\n";
			echo "</tr>\n";
			$x++;
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap></td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "			<br />\n";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "</table>";
	echo "<br><br>";
	echo "</form>\n";

//include footer
	require_once "resources/footer.php";
?>
