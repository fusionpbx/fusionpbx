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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_center_agent_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//includes and title
	require_once "resources/header.php";
	$document['title'] = $text['title-call_center_agent_status'];
	require_once "resources/paging.php";

//get the agents from the database
	$sql = "select * from v_call_center_tiers ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$tiers = $database->select($sql, $parameters, 'all');
	if (count($tiers) == 0) {
		$per_queue_login = true;
	}
	else {
		$per_queue_login = false;
	}
	unset($sql, $parameters);

//setup the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get the http post values and set them as php variables
	if (count($_POST) > 0) {

		//debug info
		//echo "<pre>\n";
		//print_r($_POST);
		//echo "</pre>\n";

		foreach($_POST['agents'] as $row) {
			if (strlen($row['agent_status']) > 0) {
				//agent set status
					if ($fp) {
						//set the user_status
							if (!isset($row['queue_name'])) {
								$array['users'][0]['user_uuid'] = $row['user_uuid'];
								$array['users'][0]['user_status'] = $row['agent_status'];
								$array['users'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

								$p = new permissions;
								$p->add('user_edit', 'temp');

								$database = new database;
								$database->app_name = 'call_centers';
								$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
								$database->save($array);
								$response = $database->message;
								unset($array);

								$p->delete('user_edit', 'temp');
							}

						//validate the agent status
							$agent_status = $row['agent_status'];
							switch ($agent_status) {
								case "Available" :
									break;
								case "Available (On Demand)" :
									break;
								case "On Break" :
									break;
								case "Do Not Disturb" :
									break;
								case "Logged Out" :
									break;
								default :
									$agent_status = null;
							}

						//set the call center status
							$command = '';
							if (!isset($row['queue_name'])) {
								if ($agent_status == "Do Not Disturb") {
									//set the default dnd action
										$dnd_action = "add";
									//set the call center status to Logged Out
										if (is_uuid($row['agent_uuid'])) {
											$command = "api callcenter_config agent set status ".$row['agent_uuid']." 'Logged Out' ";
										}
								}
								else {
									if (is_uuid($row['agent_uuid'])) {
										$command = "api callcenter_config agent set status ".$row['agent_uuid']." '".$agent_status."'";
									}
								}
								$response = event_socket_request($fp, $command);
							}
							//echo $command."\n";

						//set the agent status to available and assign the agent to the queue with the tier
							if (isset($row['queue_uuid']) && $row['agent_status'] == 'Available') {
								//set the call center status
								//$command = "api callcenter_config agent set status ".$row['agent_name']."@".$_SESSION['domain_name']." '".$row['agent_status']."'";
								//$response = event_socket_request($fp, $command);

								//assign the agent to the queue
								if (is_uuid($row['queue_uuid']) && is_uuid($row['agent_uuid'])) {
									$command = "api callcenter_config tier add ".$row['queue_uuid']." ".$row['agent_uuid']." 1 1";
									//echo $command."<br />\n";
									$response = event_socket_request($fp, $command);
								}
							}

						//un-assign the agent from the queue
							if (isset($row['queue_uuid']) && $row['agent_status'] == 'Logged Out') {
								if (is_uuid($row['queue_uuid']) && is_uuid($row['agent_uuid'])) {
									$command = "api callcenter_config tier del ".$row['queue_uuid']." ".$row['agent_uuid'];
									//echo $command."<br />\n";
									$response = event_socket_request($fp, $command);
								}
							}
							usleep(200);

						//set the blf status
						//get the agents from the database
							$sql = "select agent_name from v_call_center_agents ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and call_center_agent_uuid = :call_center_agent_uuid ";
							$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
							$parameters['call_center_agent_uuid'] = $row['agent_uuid'];
							$database = new database;
							$agent_name = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if ($row['agent_status'] == 'Available') {
								$answer_state = 'confirmed';
							}
							else {
								$answer_state = 'terminated';
							}
							$call_center_notify = new call_center_notify;
							$call_center_notify->domain_name = $_SESSION['domain_name'];
							$call_center_notify->agent_name = $agent_name[0]['agent_name'];
							$call_center_notify->answer_state = $answer_state;
							$call_center_notify->agent_uuid = $row['agent_uuid'];
							$call_center_notify->send_call_center_notify();
							unset($call_center_notify);

					} //end fp
			} //strlen
		} //foreach

		//send a message
		message::add($text['confirm-add']);
		header("Location: call_center_agent_status.php");
		return;
	} //post

//get the agents from the database
	$sql = "select * from v_call_center_agents ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by agent_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$agents = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the agent list from event socket
	$switch_cmd = 'callcenter_config agent list';
	$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
	$agent_list = csv_to_named_array($event_socket_str, '|');

//get the agent list from event socket
	$switch_cmd = 'callcenter_config tier list';
	$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
	$call_center_tiers = csv_to_named_array($event_socket_str, '|');

//get the call center queues from the database
	$sql = "select * from v_call_center_queues ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by queue_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$call_center_queues = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add the status to the call_center_queues array
	$x = 0;
	foreach ($call_center_queues as $queue) {
		//get the queue list from event socket
		$switch_cmd = "callcenter_config queue list agents ".$queue['call_center_queue_uuid'];
		$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
		$queue_list = csv_to_named_array($event_socket_str, '|');
		$call_center_queues[$x]['queue_list'] = $queue_list;
		$x++;
	}

//get the agent status from mod_callcenter and update the agent status in the agents array
	$x = 0;
	foreach ($agents as $row) {
		//add the domain name
			$domain_name = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
			$agents[$x]['domain_name'] = $domain_name;

		//update the queue status
			$i = 0;
			foreach ($call_center_queues as $queue) {
				$agents[$x]['queues'][$i]['agent_name'] = $row['agent_name'];
				$agents[$x]['queues'][$i]['queue_name'] = $queue['queue_name'];
				$agents[$x]['queues'][$i]['call_center_agent_uuid'] = $row['call_center_agent_uuid'];
				$agents[$x]['queues'][$i]['call_center_queue_uuid'] = $queue['call_center_queue_uuid'];
				$agents[$x]['queues'][$i]['queue_status'] = 'Logged Out';
				foreach ($queue['queue_list'] as $queue_list) {
					if ($row['call_center_agent_uuid'] == $queue_list['name']) {
						$agents[$x]['queues'][$i]['queue_status'] = 'Available';
					}
				}
				$i++;
			}

		//update the agent status
			foreach ($agent_list as $r) {
				if ($r['name'] == $row['call_center_agent_uuid']) {
					$agents[$x]['agent_status'] = $r['status'];
				}
			}
		//increment x
			$x++;
	}

//debug info
	//echo "<pre>\n";
	//print_r($agents);
	//echo "</pre>\n";

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-call_center_agent_status']."</b></td>\n";
	echo "<td width='50%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_center_queues.php'\" value='".$text['button-back']."'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-refresh']."' onclick=\"window.location='call_center_agent_status.php'\" value='".$text['button-refresh']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo $text['description-call_center_agent_status']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='2' cellspacing='2'>\n";
	echo "<tr>\n";
	echo "	<th>".$text['label-agent']."</th>\n";
	echo "	<th>".$text['label-status']."</th>\n";
	echo "	<th>".$text['label-options']."</th>\n";
	echo "	<th>".$text['label-queues']."</th>\n";
	echo "</tr>\n";
	$x = 0;
	foreach($agents as $row) {
		$html = "<tr>\n";
		$html .= "		<td valign='top' class='".$row_style[$c]."'>".escape($row['agent_name'])."&nbsp;</td>\n";

		//$html .= "	<td valign='top' class='".$row_style[$c]."'>".$row['agent_name']."&nbsp;</td>\n";
		$html .= "	<td valign='top' class='".$row_style[$c]."'>".escape($row['agent_status'])."&nbsp;</td>\n";
		$html .= "	<td valign='top' class='".$row_style[$c]."' nowrap='nowrap'>";
		$html .= "		<input type='hidden' name='agents[".$x."][agent_name]' id='agent_".$x."_name' value='".escape($row['agent_name'])."'>\n";
		$html .= "		<input type='hidden' name='agents[".$x."][agent_uuid]' id='agent_".$x."_uuid' value='".escape($row['call_center_agent_uuid'])."'>\n";
		//$html .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_no_change' value='' checked='checked'>&nbsp;<label for='agent_".$x."_status_no_change'>".$text['option-no_change']."</label>&nbsp;\n";
		$html .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_available' value='Available'>&nbsp;<label for='agent_".$x."_status_available'>".$text['option-available']."</label>&nbsp;\n";
		$html .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_logged_out' value='Logged Out'>&nbsp;<label for='agent_".$x."_status_logged_out'>".$text['option-logged_out']."</label>&nbsp;\n";
		$html .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_on_break' value='On Break'>&nbsp;<label for='agent_".$x."_status_on_break'>".$text['option-on_break']."</label>&nbsp;\n";
		//$html .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_dnd' value='Do Not Disturb'><label for='agent_".$x."_status_dnd'>&nbsp;".$text['option-do_not_disturb']."</label>\n";
		$html .= "	</td>\n";

		$html .= "	<td valign='top' class='".$row_style[$c]."' nowrap='nowrap'>";
		if (is_array($row['queues']) && $per_queue_login) {
			$html .= "		<table width='100%' border='0' cellpadding='2' cellspacing='2'>\n";
			$html .= "		<tr>\n";
			$html .= "			<th>".$text['label-agent']."</th>\n";
			$html .= "			<th width='100'>".$text['label-status']."</th>\n";
			$html .= "			<th>".$text['label-options']."</th>\n";
			$html .= "		</tr>\n";
			foreach ($row['queues'] as $queue) {
				$x++;
				$html .= "	<tr>\n";
				$html .= "		<td valign='top' class='".$row_style[$c]."'>\n";
				$html .= "			".$queue['queue_name']."\n";
				$html .= "		</td>\n";

				$html .= "		<td valign='top' class='".$row_style[$c]."'>\n";
				//.$row[queue_status]."&nbsp;
				if ($queue['queue_status'] == "Available") {
					$html .= "		".$text['option-available']."\n";
				}
				if ($queue['queue_status'] == "Logged Out") {
					$html .= "		".$text['option-logged_out']."\n";
				}
				$html .= "		</td>\n";

				$html .= "		<td valign='middle' class='".$row_style[$c]."' nowrap='nowrap'>";
				//$html .= "			<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_no_change' value='' checked='checked'>&nbsp;<label for='agent_".$x."_status_no_change'>".$text['option-no_change']."</label>&nbsp;\n";
				$html .= "			<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_available' value='Available'>&nbsp;<label for='agent_".$x."_status_available'>".$text['option-available']."</label>&nbsp;\n";
				$html .= "			<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_logged_out' value='Logged Out'>&nbsp;<label for='agent_".$x."_status_logged_out'>".$text['option-logged_out']."</label>&nbsp;\n";
				$html .= "			<input type='hidden' name='agents[".$x."][queue_name]' id='queue_".$x."_name' value='".escape($queue['queue_name'])."'>\n";
				$html .= "			<input type='hidden' name='agents[".$x."][agent_name]' id='agent_".$x."_name' value='".escape($row['agent_name'])."'>\n";
				$html .= "			<input type='hidden' name='agents[".$x."][user_uuid]' id='agent_".$x."_name' value='".escape($row['user_uuid'])."'>\n";
				$html .= "			<input type='hidden' name='agents[".$x."][queue_uuid]' id='queue_".$x."_uuid' value='".escape($queue['call_center_queue_uuid'])."'>\n";
				$html .= "			<input type='hidden' name='agents[".$x."][agent_uuid]' id='agent_".$x."_uuid' value='".escape($row['call_center_agent_uuid'])."'>\n";
				$html .= "		</td>\n";
				$html .= "	</tr>\n";
			}
			$html .= "		</table>\n";
		}
		$html .= "	</td>\n";
		$html .= "</tr>\n";
		if (count($_SESSION['domains']) > 1) {
			if ($row['domain_name'] == $_SESSION['domain_name']) {
				echo $html;
				if ($c==0) { $c=1; } else { $c=0; }
			}
		}
		else {
			echo $html;
			if ($c==0) { $c=1; } else { $c=0; }
		}
		$x++;
	} //end foreach
	unset($sql, $agents);

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
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

//show the footer
	require_once "resources/footer.php";

?>
