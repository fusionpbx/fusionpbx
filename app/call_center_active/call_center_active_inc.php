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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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

//get the queue uuid and set it as a variable
	$queue_uuid = $_GET['queue_name'];

//get the queues from the database
	if (!is_array($_SESSION['queues'])) {
		$sql = "select * from v_call_center_queues ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "order by queue_name asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$database = new database;
		$_SESSION['queues'] = $database->select($sql, $parameters, 'all');
	}

//get the queue name
	foreach ($_SESSION['queues'] as $row) {
		if ($row['call_center_queue_uuid'] == $queue_uuid) {
			$queue_name = $row['queue_name'];
			$queue_extension = $row['queue_extension'];
		}
	}

//convert the string to a named array
	function str_to_named_array($tmp_str, $tmp_delimiter) {
		$tmp_array = explode ("\n", $tmp_str);
		$result = array();
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
						}
						$y++;
					}
				}
				$x++;
			}
			unset($row);
		}
		return $result;
	}

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
				$switch_command = 'callcenter_config queue list tiers '.$queue_extension."@".$_SESSION["domain_name"];
				$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_command));
				$result = str_to_named_array($event_socket_str, '|');

			//prepare the result for array_multisort
				$x = 0;
				if (is_array($result)) {
					foreach ($result as $row) {
						$tier_result[$x]['level'] = $row['level'];
						$tier_result[$x]['position'] = $row['position'];
						$tier_result[$x]['agent'] = $row['agent'];
						$tier_result[$x]['state'] = trim($row['state']);
						$tier_result[$x]['queue'] = $row['queue'];
						$x++;
					}
				}

			//sort the array //SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING
				if (isset($tier_result)) { array_multisort($tier_result, SORT_ASC); }

			//send the event socket command and get the response
				//callcenter_config queue list agents [queue_name] [status] |
				$switch_command = 'callcenter_config queue list agents '.$queue_extension."@".$_SESSION["domain_name"];
				$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_command));
				$agent_result = str_to_named_array($event_socket_str, '|');

			//get the agents from the database
				if (!is_array($_SESSION['agents'])) {
					$sql = "select * from v_call_center_agents ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "order by agent_name asc ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$database = new database;
					$_SESSION['agents'] = $database->select($sql, $parameters, 'all');
				}

			//list the agents
				echo "<table class='list'>\n";
				echo "<tr class='list-header'>\n";
				echo "<th>".$text['label-name']."</th>\n";
				echo "<th>".$text['label-extension']."</th>\n";
				echo "<th>".$text['label-status']."</th>\n";
				echo "<th>".$text['label-state']."</th>\n";
				echo "<th>".$text['label-status_change']."</th>\n";
				echo "<th class='center'>".$text['label-missed']."</th>\n";
				echo "<th class='center'>".$text['label-answered']."</th>\n";
				echo "<th>".$text['label-tier_state']."</th>\n";
				echo "<th class='center'>".$text['label-tier_level']."</th>\n";
				echo "<th class='center'>".$text['label-tier_position']."</th>\n";
				if (permission_exists('call_center_active_options')) {
					echo "<th class='center'>".$text['label-options']."</th>\n";
				}
				echo "</tr>\n";
				if (isset($tier_result)) {
					foreach ($tier_result as $tier_row) {
						//$queue = $tier_row['queue'];
						//$queue = str_replace('@'.$_SESSION['domain_name'], '', $queue);
						$agent = $tier_row['agent'];
						//$agent = str_replace('@'.$_SESSION['domain_name'], '', $agent);
						$tier_state = $tier_row['state'];
						$tier_level = $tier_row['level'];
						$tier_position = $tier_row['position'];

						if (isset($agent_result)) {
							foreach ($agent_result as $agent_row) {
								if ($tier_row['agent'] == $agent_row['name']) {
									$agent_uuid = $agent_row['name'];

									//get the agent name
									$agent_name = '';
									if (is_array($_SESSION['agents'])) {
										foreach ($_SESSION['agents'] as $agent) {
											if ($agent['call_center_agent_uuid'] == $agent_uuid) {
												$agent_name = $agent['agent_name'];
											}
										}
									}

									//$system = $agent_row['system'];
									if (is_uuid($agent_row['uuid'])) {
										$agent_uuid = $agent_row['uuid'];
									}
									//$type = $agent_row['type'];
									$contact = $agent_row['contact'];
									$agent_extension = preg_replace("/user\//", "", $contact);
									$agent_extension = preg_replace("/@.*/", "", $agent_extension);
									$agent_extension = preg_replace("/{.*}/", "", $agent_extension);
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

									$last_status_change_seconds = time() - $last_status_change;
									$last_status_change_length_hour = floor($last_status_change_seconds/3600);
									$last_status_change_length_min = floor($last_status_change_seconds/60 - ($last_status_change_length_hour * 60));
									$last_status_change_length_sec = $last_status_change_seconds - (($last_status_change_length_hour * 3600) + ($last_status_change_length_min * 60));
									$last_status_change_length_min = sprintf("%02d", $last_status_change_length_min);
									$last_status_change_length_sec = sprintf("%02d", $last_status_change_length_sec);
									$last_status_change_length = $last_status_change_length_hour.':'.$last_status_change_length_min.':'.$last_status_change_length_sec;

									if (permission_exists('call_center_agent_edit')) {
										$list_row_url = "../call_centers/call_center_agent_edit.php?id=".$agent_uuid;
									}

									echo "<tr class='list-row' href='".$list_row_url."'>\n";
									echo "<td>";
									if (permission_exists('call_center_agent_edit')) {
										echo "<a href='".$list_row_url."'>".escape($agent_name)."</a>";
									}
									else {
										echo escape($agent_name);
									}
									echo "</td>\n";
									echo "<td>".escape($agent_extension)."</td>\n";
									echo "<td>".escape($status)."</td>\n";
									echo "<td>".escape($state)."</td>\n";
									echo "<td>".escape($last_status_change_length)."</td>\n";
									echo "<td class='center'>".escape($no_answer_count)."</td>\n";
									echo "<td class='center'>".escape($calls_answered)."</td>\n";
									echo "<td>".escape($tier_state)."</td>\n";
									echo "<td class='center'>".escape($tier_level)."</td>\n";
									echo "<td class='center'>".escape($tier_position)."</td>\n";
									if (permission_exists('call_center_active_options')) {
										echo "<td class='no-link center'>\n";
										//need to check state to so only waiting gets call, and trying/answer gets eavesdrop
										if ($tier_state == "Offering" || $tier_state == "Active Inbound") {
											//$orig_command="{origination_caller_id_name=eavesdrop,origination_caller_id_number=".escape($agent_extension)."}user/".$_SESSION['user']['extension'][0]['user']."@".$_SESSION['domain_name']." %26eavesdrop(".escape($agent_uuid).")";
											echo button::create(['type'=>'button','class'=>'link','label'=>$text['label-eavesdrop'],'onclick'=>"if (confirm('".$text['message-confirm']."')) { send_command('call_center_exec.php?command=eavesdrop&uuid=".urlencode($agent_uuid)."&extension=".urlencode($agent_extension)."'); } else { this.blur(); return false; }"]);

											//$xfer_command = escape($agent_uuid)." -bleg ".escape($_SESSION['user']['extension'][0]['user'])." XML ".escape($_SESSION['domain_name']);
											echo button::create(['type'=>'button','class'=>'link','label'=>$text['label-transfer'],'style'=>'margin-left: 15px;','onclick'=>"if (confirm('".$text['message-confirm']."')) { send_command('call_center_exec.php?command=uuid_transfer&uuid=".urlencode($agent_uuid)."'); } else { this.blur(); return false; }"]);
										}
										else {
											//$orig_call="{origination_caller_id_name=c2c-".urlencode(escape($name)).",origination_caller_id_number=".escape($agent_extension)."}user/".$_SESSION['user']['extension'][0]['user']."@".$_SESSION['domain_name']." %26bridge(user/".escape($agent_extension)."@".$_SESSION['domain_name'].")";
											echo button::create(['type'=>'button','class'=>'link','label'=>$text['label-call'],'onclick'=>"if (confirm('".$text['message-confirm']."')) { send_command('call_center_exec.php?command=bridge&extension=".urlencode($agent_extension)."&caller_id_name=".urlencode($name)."'); } else { this.blur(); return false; }"]);
										}
										echo "</td>";
									}
									echo "</tr>\n";
								} //if
							} //foreach
						} //if
					} //foreach
				} //if
				echo "</table>\n\n";

		//add vertical spacing
			echo "<br /><br /><br />";

		//get the queue list
			//send the event socket command and get the response
				//callcenter_config queue list members [queue_name]
				if (is_uuid($queue_uuid)) {
					$switch_command = 'callcenter_config queue list members '.$queue_extension."@".$_SESSION["domain_name"];
					$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_command));
					$result = str_to_named_array($event_socket_str, '|');
					if (!is_array($result)) { unset($result); }
				}

			//show the title
				$q_waiting=0;
				$q_trying=0;
				$q_answered=0;

				echo "<div class='action_bar sub'>\n";
				echo "	<div class='heading'><b>".$text['label-queue'].": ".ucfirst(escape($queue_name))."</b></div>\n";
				echo "	<div class='actions'>\n";
				if (isset($result)) {
					foreach ($result as $row) {
						$state = $row['state'];
						$q_trying += ($state == "Trying") ? 1 : 0;
						$q_waiting += ($state == "Waiting") ? 1 : 0;
						$q_answered += ($state == "Answered") ? 1 : 0;
					}
				}
				echo "		<strong>".$text['label-waiting'].":</strong> <b>".$q_waiting."</b>&nbsp;&nbsp;&nbsp;";
				echo "		<strong>".$text['label-trying'].":</strong> <b>".$q_trying."</b>&nbsp;&nbsp;&nbsp; ";
				echo "		<strong>".$text['label-answered'].":</strong> <b>".$q_answered."</b>";
				echo "	</div>\n";
				echo "	<div style='clear: both;'></div>\n";
				echo "</div>\n";

				echo $text['description-queue']."\n";
				echo "<br /><br />\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
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

			if (is_array($result)) {
				foreach ($result as $row) {
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

					//get the serving agent name
					$serving_agent_name = '';
					if (is_array($_SESSION['agents'])) {
						foreach ($_SESSION['agents'] as $agent) {
							if ($agent['call_center_agent_uuid'] == $serving_agent) {
								$serving_agent_name = $agent['agent_name'];
							}
						}
					}

					echo "<tr class='list-row'>\n";
					echo "<td>".escape($joined_length)."</td>\n";
					//echo "<td>".escape($system_length)."</td>\n";
					echo "<td>".escape($caller_name)."&nbsp;</td>\n";
					echo "<td>".escape($caller_number)."&nbsp;</td>\n";
					echo "<td>".escape($state)."</td>\n";
					if (if_group("admin") || if_group("superadmin")) {
						echo "<td>";
						if ($state != "Abandoned") {
							$orig_command="{origination_caller_id_name=eavesdrop,origination_caller_id_number=".escape($q_caller_number)."}user/".escape($_SESSION['user']['extension'][0]['user'])."@".escape($_SESSION['domain_name'])." %26eavesdrop(".escape($session_uuid).")";
							echo button::create(['type'=>'button','class'=>'link','label'=>$text['label-eavesdrop'],'onclick'=>"if (confirm('".$text['message-confirm']."')) { send_command('call_center_exec.php?command=eavesdrop&caller_id_number=".urlencode($caller_number)."&uuid=".urlencode($session_uuid)."'); } else { this.blur(); return false; }"]);
						}
						else {
							echo "&nbsp;";
						}
						echo "</td>";
					}
					echo "<td>".escape($serving_agent_name)."&nbsp;</td>\n";
					echo "</tr>\n";
				}
			}
			echo "</table>\n";

	}

?>
