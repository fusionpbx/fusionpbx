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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('call_center_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the queue_name and set it as a variable
	$queue_name = $_GET[queue_name].'@'. $_SESSION['domains'][$domain_uuid]['domain_name'];

//convert the string to a named array
	function str_to_named_array($tmp_str, $tmp_delimiter) {
		$tmp_array = explode ("\n", $tmp_str);
		if (trim(strtoupper($tmp_array[0])) != "+OK") {
			$tmp_field_name_array = explode ($tmp_delimiter, $tmp_array[0]);
			$x = 0;
			if (isset($tmp_array)) foreach ($tmp_array as $row) {
				if ($x > 0) {
					$tmp_field_value_array = explode ($tmp_delimiter, $tmp_array[$x]);
					$y = 0;
					if (isset($tmp_field_value_array)) foreach ($tmp_field_value_array as $tmp_value) {
						$tmp_name = $tmp_field_name_array[$y];
						if (trim(strtoupper($tmp_value)) != "+OK") {
							$result[$x][$tmp_name] = $tmp_value;
							return $result;
						}
						else {
							return false;
						}
						$y++;
					}
				}
				$x++;
			}
			unset($row);
		}
	}

//alternate the color of the row
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//create an event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get the call center queue, agent and tiers list
	if (!$fp) {
		$msg = "<div align='center'>Connection to Event Socket failed.<br /></div>";
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>Message</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}
	else {

		//get the agent list

			//show the title
				echo "<b>".$text['header-agents']."</b><br />\n";
				echo $text['description-agents']."<br /><br />\n";

			//send the event socket command and get the response
				//callcenter_config queue list tiers [queue_name] |
				$switch_cmd = 'callcenter_config queue list tiers '.$queue_name;
				$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
				$result = str_to_named_array($event_socket_str, '|');

			//prepare the result for array_multisort
				$x = 0;
				if (isset($result)) foreach ($result as $row) {
					$tier_result[$x]['level'] = $row['level'];
					$tier_result[$x]['position'] = $row['position'];
					$tier_result[$x]['agent'] = $row['agent'];
					$tier_result[$x]['state'] = trim($row['state']);
					$tier_result[$x]['queue'] = $row['queue'];
					$x++;
				}

			//sort the array //SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING
				if (isset($tier_result)) { array_multisort($tier_result, SORT_ASC); }

			//send the event socket command and get the response
				//callcenter_config queue list agents [queue_name] [status] |
				$switch_cmd = 'callcenter_config queue list agents '.$queue_name;
				$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
				$agent_result = str_to_named_array($event_socket_str, '|');

			//list the agents
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "<tr>\n";
				echo "<th>".$text['label-name']."</th>\n";
				echo "<th>".$text['label-extension']."</th>\n";
				echo "<th>".$text['label-status']."</th>\n";
				echo "<th>".$text['label-state']."</th>\n";
				echo "<th>".$text['label-status_change']."</th>\n";
				echo "<th>".$text['label-missed']."</th>\n";
				echo "<th>".$text['label-answered']."</th>\n";
				echo "<th>".$text['label-tier_state']."</th>\n";
				echo "<th>".$text['label-tier_level']."</th>\n";
				echo "<th>".$text['label-tier_position']."</th>\n";
				if (permission_exists('call_center_active_options')) {
					echo "<th>".$text['label-options']."</th>\n";
				}
				echo "</tr>\n";
				if (isset($tier_result)) foreach ($tier_result as $tier_row) {
					//$queue = $tier_row['queue'];
					//$queue = str_replace('@'.$_SESSION['domain_name'], '', $queue);
					$agent = $tier_row['agent'];
					//$agent = str_replace('@'.$_SESSION['domain_name'], '', $agent);
					$tier_state = $tier_row['state'];
					$tier_level = $tier_row['level'];
					$tier_position = $tier_row['position'];

					if (isset($agent_result)) foreach ($agent_result as $agent_row) {
						if ($tier_row['agent'] == $agent_row['name']) {
							$name = $agent_row['name'];
							$name = str_replace('@'.$_SESSION['domain_name'], '', $name);
							//$system = $agent_row['system'];
							$a_uuid = $agent_row['uuid'];
							//$type = $agent_row['type'];
							$contact = $agent_row['contact'];
							$a_exten = preg_replace("/user\//", "", $contact);
							$a_exten = preg_replace("/@.*/", "", $a_exten);
							$a_exten = preg_replace("/{.*}/", "", $a_exten);
							$status = $agent_row['status'];
							$state = $agent_row['state'];
							$max_no_answer = $agent_row['max_no_answer'];
							$wrap_up_time = $agent_row['wrap_up_time'];
							$reject_delay_time = $agent_row['reject_delay_time'];
							$busy_delay_time = $agent_row['busy_delay_time'];
							$last_bridge_start = $agent_row['last_bridge_start'];
							$last_bridge_end = $agent_row['last_bridge_end'];
							//$last_offered_call = $agent_row['last_offered_call'];
							$last_status_change = $agent_row['last_status_change'];
							$no_answer_count = $agent_row['no_answer_count'];
							$calls_answered = $agent_row['calls_answered'];
							$talk_time = $agent_row['talk_time'];
							$ready_time = $agent_row['ready_time'];

							$last_offered_call_seconds = time() - $last_offered_call;
							$last_offered_call_length_hour = floor($last_offered_call_seconds/3600);
							$last_offered_call_length_min = floor($last_offered_call_seconds/60 - ($last_offered_call_length_hour * 60));
							$last_offered_call_length_sec = $last_offered_call_seconds - (($last_offered_call_length_hour * 3600) + ($last_offered_call_length_min * 60));
							$last_offered_call_length_min = sprintf("%02d", $last_offered_call_length_min);
							$last_offered_call_length_sec = sprintf("%02d", $last_offered_call_length_sec);
							$last_offered_call_length = $last_offered_call_length_hour.':'.$last_offered_call_length_min.':'.$last_offered_call_length_sec;

							$last_status_change_seconds = time() - $last_status_change;
							$last_status_change_length_hour = floor($last_status_change_seconds/3600);
							$last_status_change_length_min = floor($last_status_change_seconds/60 - ($last_status_change_length_hour * 60));
							$last_status_change_length_sec = $last_status_change_seconds - (($last_status_change_length_hour * 3600) + ($last_status_change_length_min * 60));
							$last_status_change_length_min = sprintf("%02d", $last_status_change_length_min);
							$last_status_change_length_sec = sprintf("%02d", $last_status_change_length_sec);
							$last_status_change_length = $last_status_change_length_hour.':'.$last_status_change_length_min.':'.$last_status_change_length_sec;

							echo "<tr>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$name."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$a_exten."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$status."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$state."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$last_status_change_length."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$no_answer_count."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$calls_answered."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$tier_state."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$tier_level."</td>\n";
							echo "<td valign='top' class='".$row_style[$c]."'>".$tier_position."</td>\n";

							if (permission_exists('call_center_active_options')) {

								echo "<td valign='top' class='".$row_style[$c]."'>";

								//need to check state to so only waiting gets call, and trying/answer gets eavesdrop
								if ($tier_state == "Offering" || $tier_state == "Active Inbound") {
									$orig_command="{origination_caller_id_name=eavesdrop,origination_caller_id_number=".$a_exten."}user/".$_SESSION['user']['extension'][0]['user']."@".$_SESSION['domain_name']." %26eavesdrop(".$a_uuid.")";

									//debug
									//echo $orig_command;
									//echo "  <a href='javascript:void(0);' style='color: #444444;' onclick=\"confirm_response = confirm('".$text['message-confirm']."');if (confirm_response){send_cmd('call_center_exec.php?cmd=log+".$orig_command.")');}\">log_cmd</a>&nbsp;\n";
									echo "  <a href='javascript:void(0);' style='color: #444444;' onclick=\"confirm_response = confirm('".$text['message-confirm']."');if (confirm_response){send_cmd('call_center_exec.php?cmd=originate+".$orig_command.")');}\">".$text['label-eavesdrop']."</a>&nbsp;\n";

									$xfer_command = $a_uuid." -bleg ".$_SESSION['user']['extension'][0]['user']." XML ".$_SESSION['domain_name'];
									//$xfer_command = $a_uuid." ".$_SESSION['user']['extension'][0]['user']." XML default";
									$xfer_command = urlencode($xfer_command);
									echo "  <a href='javascript:void(0);' style='color: #444444;' onclick=\"confirm_response = confirm('".$text['message-confirm']."');if (confirm_response){send_cmd('call_center_exec.php?cmd=uuid_transfer+".$xfer_command."');}\">".$text['label-transfer']."</a>&nbsp;\n";
								}
								else {
									$orig_call="{origination_caller_id_name=c2c-".urlencode($name).",origination_caller_id_number=".$a_exten."}user/".$_SESSION['user']['extension'][0]['user']."@".$_SESSION['domain_name']." %26bridge(user/".$a_exten."@".$_SESSION['domain_name'].")";
									echo "  <a href='javascript:void(0);' style='color: #444444;' onclick=\"confirm_response = confirm('".$text['message-confirm']."');if (confirm_response){send_cmd('call_center_exec.php?cmd=originate+".$orig_call.")');}\">".$text['label-call']."</a>&nbsp;\n";
								}
								echo "</td>";
							}
						}
					}
					echo "</tr>\n";

					if ($c==0) { $c=1; } else { $c=0; }
				}
				echo "</table>\n\n";

		//add vertical spacing
			echo "<br />";
			echo "<br />";
			echo "<br />";
			echo "<br />";


		//get the queue list
			//send the event socket command and get the response
				//callcenter_config queue list members [queue_name]
				$switch_cmd = 'callcenter_config queue list members '.$queue_name;
				$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
				$result = str_to_named_array($event_socket_str, '|');

			//show the title
				$q_waiting=0;
				$q_trying=0;
				$q_answered=0;

				echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
				echo "  <tr>\n";
				echo "	<td align='left'><b>".$text['label-queue'].": ".ucfirst($_GET[queue_name])."</b><br />\n";
				echo "		".$text['description-queue']."<br />\n";
				echo "	</td>\n";
				echo "	<td align='right' valign='top'>";
				if (isset($result)) foreach ($result as $row) {
					$state = $row['state'];
					$q_trying += ($state == "Trying") ? 1 : 0;
					$q_waiting += ($state == "Waiting") ? 1 : 0;
					$q_answered += ($state == "Answered") ? 1 : 0;
				}
				echo "		<strong>".$text['label-waiting'].":</strong> <b>".$q_waiting."</b>&nbsp;&nbsp;&nbsp;";
				echo "		<strong>".$text['label-trying'].":</strong> <b>".$q_trying."</b>&nbsp;&nbsp;&nbsp; ";
				echo "		<strong>".$text['label-answered'].":</strong> <b>".$q_answered."</b>";
				echo "	</td>";
				echo "  </tr>\n";
				echo "</table>\n";
				echo "<br />\n";

			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<th>".$text['label-time']."</th>\n";
			//echo "<th>".$text['label-system']."</th>\n";
			echo "<th>".$text['label-name']."</th>\n";
			echo "<th>".$text['label-number']."</th>\n";
			echo "<th>".$text['label-status']."</th>\n";
			if ((if_group("admin") || if_group("superadmin"))) {
				echo "<th>".$text['label-options']."</th>\n";
			}
			echo "<th>".$text['label-agent']."</th>\n";
			echo "</tr>\n";

			if (isset($result)) foreach ($result as $row) {
				$queue = $row['queue'];
				$system = $row['system'];
				$uuid = $row['uuid'];
				$session_uuid = $row['session_uuid'];
				$caller_number = $row['cid_number'];
				$caller_name = $row['cid_name'];
				$system_epoch = $row['system_epoch'];
				$joined_epoch = $row['joined_epoch'];
				$rejoined_epoch = $row['rejoined_epoch'];
				$bridge_epoch = $row['bridge_epoch'];
				$abandoned_epoch = $row['abandoned_epoch'];
				$base_score = $row['base_score'];
				$skill_score = $row['skill_score'];
				$serving_agent = $row['serving_agent'];
				$serving_system = $row['serving_system'];
				$state = $row['state'];
				$joined_seconds = time() - $joined_epoch;
				$joined_length_hour = floor($joined_seconds/3600);
				$joined_length_min = floor($joined_seconds/60 - ($joined_length_hour * 60));
				$joined_length_sec = $joined_seconds - (($joined_length_hour * 3600) + ($joined_length_min * 60));
				$joined_length_min = sprintf("%02d", $joined_length_min);
				$joined_length_sec = sprintf("%02d", $joined_length_sec);
				$joined_length = $joined_length_hour.':'.$joined_length_min.':'.$joined_length_sec;

				//$system_seconds = time() - $system_epoch;
				//$system_length_hour = floor($system_seconds/3600);
				//$system_length_min = floor($system_seconds/60 - ($system_length_hour * 60));
				//$system_length_sec = $system_seconds - (($system_length_hour * 3600) + ($system_length_min * 60));
				//$system_length_min = sprintf("%02d", $system_length_min);
				//$system_length_sec = sprintf("%02d", $system_length_sec);
				//$system_length = $system_length_hour.':'.$system_length_min.':'.$system_length_sec;

				echo "<tr>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$joined_length."</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>".$system_length."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$caller_name."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$caller_number."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$state."</td>\n";
				if (if_group("admin") || if_group("superadmin")) {
					echo "<td valign='top' class='".$row_style[$c]."'>";
					if ($state != "Abandoned") {
						$q_caller_number = urlencode($caller_number);
						$orig_command="{origination_caller_id_name=eavesdrop,origination_caller_id_number=".$q_caller_number."}user/".$_SESSION['user']['extension'][0]['user']."@".$_SESSION['domain_name']." %26eavesdrop(".$session_uuid.")";

						//debug
						//echo $orig_command;
						//echo "  <a href='javascript:void(0);' style='color: #444444;' onclick=\"confirm_response = confirm('".$text['message-confirm']."');if (confirm_response){send_cmd('call_center_exec.php?cmd=log+".$orig_command.")');}\">log_cmd</a>&nbsp;\n";

						echo "  <a href='javascript:void(0);' style='color: #444444;' onclick=\"confirm_response = confirm('".$text['message-confirm']."');if (confirm_response){send_cmd('call_center_exec.php?cmd=originate+".$orig_command.")');}\">".$text['label-eavesdrop']."</a>&nbsp;\n";
					}
					else {
						echo "&nbsp;";
					}
					echo "</td>";
				}
				echo "<td valign='top' class='".$row_style[$c]."'>".$serving_agent."&nbsp;</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			}
			echo "</table>\n";

		//add vertical spacing
			echo "<br />\n";
			echo "<br />\n";
			echo "<br />\n";
	}

?>