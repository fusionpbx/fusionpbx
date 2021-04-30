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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

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

//get the agents from the database
	$sql = "select * from v_call_center_tiers ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$tiers = $database->select($sql, $parameters, 'all');
	if (is_array($tiers) && count($tiers) == 0) {
		$per_queue_login = true;
	}
	else {
		$per_queue_login = false;
	}
	unset($sql, $parameters);

//setup the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

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
	$sql = "select q.*, d.domain_name ";
	$sql .= "from v_call_center_queues as q, v_domains as d ";
	$sql .= "where q.domain_uuid = :domain_uuid ";
	$sql .= "and d.domain_uuid = :domain_uuid ";
	$sql .= "and q.domain_uuid = d.domain_uuid ";
	$sql .= "order by queue_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$call_center_queues = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);
	//view_array($call_center_queues, false);

//add the status to the call_center_queues array
	$x = 0;
	if (is_array($call_center_queues)) {
		foreach ($call_center_queues as $queue) {
			//set the queue id
			$queue_id = $queue['queue_extension'].'@'.$queue['domain_name'];

			//get the queue list from event socket
			$switch_cmd = "callcenter_config queue list agents ".$queue_id;
			$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
			$queue_list = csv_to_named_array($event_socket_str, '|');
			$call_center_queues[$x]['queue_list'] = $queue_list;
			$x++;
		}
	}

//get the agent status from mod_callcenter and update the agent status in the agents array
	$x = 0;
	if (is_array($agents)) {
		foreach ($agents as $row) {
			//add the domain name
				$domain_name = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				$agents[$x]['domain_name'] = $domain_name;

			//update the queue status
				$i = 0;
				if (is_array($call_center_queues)) {
					foreach ($call_center_queues as $queue) {
						$agents[$x]['queues'][$i]['agent_name'] = $row['agent_name'];
						$agents[$x]['queues'][$i]['queue_name'] = $queue['queue_name'];
						$agents[$x]['queues'][$i]['call_center_agent_uuid'] = $row['call_center_agent_uuid'];
						$agents[$x]['queues'][$i]['call_center_queue_uuid'] = $queue['call_center_queue_uuid'];
						$agents[$x]['queues'][$i]['queue_status'] = 'Logged Out';
						if (is_array($queue['queue_list'])) {
							foreach ($queue['queue_list'] as $queue_list) {
								if ($row['call_center_agent_uuid'] == $queue_list['name']) {
									$agents[$x]['queues'][$i]['queue_status'] = 'Available';
								}
							}
						}
						$i++;
					}
				}

			//update the agent status
				if (is_array($agent_list)) {
					foreach ($agent_list as $r) {
						if ($r['name'] == $row['call_center_agent_uuid']) {
							$agents[$x]['agent_status'] = $r['status'];
						}
					}
				}

			//increment x
				$x++;
		}
	}

//remove rows from the http post array where the status has not changed
	if (is_array($_POST['agents']) && !$per_queue_login) {
		foreach($_POST['agents'] as $key => $row) {
			foreach($agents as $k => $field) {
				if ($field['agent_name'] === $row['agent_name'] && $field['agent_status'] === $row['agent_status']) {
					unset($_POST['agents'][$key]);
				}
			}
		}
	}

//use the http post array to change the status
	if (is_array($_POST['agents'])) {
		foreach($_POST['agents'] as $row) {
			if (isset($row['agent_status'])) {
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

						//get the queue_id
							if (isset($row['queue_uuid']) && is_uuid($row['queue_uuid'])) {
								if (is_array($call_center_queues)) {
									foreach ($call_center_queues as $queue) {
										if ($queue['call_center_queue_uuid'] == $row['queue_uuid']) {
											$queue_id = $queue['queue_extension'].'@'.$queue['domain_name'];
										}
									}
								}
							}

						//set the agent status to available and assign the agent to the queue with the tier
							if (isset($row['queue_uuid']) && $row['agent_status'] == 'Available') {
								//set the call center status
								//$command = "api callcenter_config agent set status ".$row['agent_name']."@".$_SESSION['domain_name']." '".$row['agent_status']."'";
								//$response = event_socket_request($fp, $command);

								//assign the agent to the queue
								if (is_uuid($row['queue_uuid']) && is_uuid($row['agent_uuid'])) {
									$command = "api callcenter_config tier add ".$queue_id." ".$row['agent_uuid']." 1 1";
									//echo $command."<br />\n";
									$response = event_socket_request($fp, $command);
								}
							}

						//un-assign the agent from the queue
							if (isset($row['queue_uuid']) && $row['agent_status'] == 'Logged Out') {
								if (is_uuid($row['queue_uuid']) && is_uuid($row['agent_uuid'])) {
									$command = "api callcenter_config tier del ".$queue_id." ".$row['agent_uuid'];
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

//includes the header
	$document['title'] = $text['title-call_center_agent_status'];
	require_once "resources/header.php";

//radio button cycle script
	echo "<script>\n";
	echo "	function get_selected(radio_group) {\n";
	echo "		for (var i = 0; i < radio_group.length; i++) {\n";
	echo "			if (radio_group[i].checked) { return i; }\n";
	echo "		}\n";
	echo "		return 0;\n";
	echo "	}\n";

	echo "	function cycle(radio_group_name) {\n";
	echo "		var radios = document.getElementsByName(radio_group_name);\n";
	echo "		var i = get_selected(radios);\n";
	echo "		if (i+1 == radios.length) {\n";
	echo "			radios[0].checked = true;\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			radios[i+1].checked = true;\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-call_center_agent_status']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'call_center_queues.php']);
	echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>$_SESSION['theme']['button_icon_refresh'],'collapse'=>'hide-xs','link'=>'call_center_agent_status.php']);
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs','style'=>'margin-left: 15px;','onclick'=>"list_form_submit('form_list');"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (is_array($_POST['agents']) && !$per_queue_login) {
		echo $text['description-call_center_agent_status']."\n";
		echo "<br /><br />\n";
	}

	echo "<form id='form_list' method='post'>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th class='pct-20'>".$text['label-agent']."</th>\n";
	if (!$per_queue_login) {
		echo "	<th class='shrink'>".$text['label-status']."</th>\n";
	}
	echo "	<th class='pct-20 hide-sm-dn'>&nbsp;</th>\n";
	if ($per_queue_login) {
		echo "	<th class='pct-40'>".$text['label-options']."</th>\n";
	}
	echo "</tr>\n";

	if (is_array($agents) && @sizeof($agents) != 0) {
		$x = 0;
		foreach ($agents as $row) {
			$onclick = "onclick=\"cycle('agents[".$x."][agent_status]');\"";
			$html = "<tr class='list-row'>\n";
			$html .= "	<td ".$onclick.">".escape($row['agent_name'])."&nbsp;</td>\n";

			if (!$per_queue_login) {
				$html .= "	<td class='no-wrap'>";
				$html .= "		<input type='hidden' name='agents[".$x."][agent_name]' value='".escape($row['agent_name'])."'>\n";
				$html .= "		<input type='hidden' name='agents[".$x."][agent_uuid]' value='".escape($row['call_center_agent_uuid'])."'>\n";
				$html .= "		<label style='margin: 0; cursor: pointer; margin-right: 10px;'><input type='radio' name='agents[".$x."][agent_status]' value='Available' ".($row['agent_status'] == 'Available' ? "checked='checked'" : null).">&nbsp;".$text['option-available']."</label>\n";
				$html .= "		<label style='margin: 0; cursor: pointer; margin-right: 10px;'><input type='radio' name='agents[".$x."][agent_status]' value='Logged Out' ".($row['agent_status'] == 'Logged Out' ? "checked='checked'" : null).">&nbsp;".$text['option-logged_out']."</label>\n";
				$html .= "		<label style='margin: 0; cursor: pointer;'><input type='radio' name='agents[".$x."][agent_status]' value='On Break' ".($row['agent_status'] == 'On Break' ? "checked='checked'" : null).">&nbsp;".$text['option-on_break']."</label>\n";
				//$html .= "		<label><input type='radio' name='agents[".$x."][agent_status]' value='Do Not Disturb' ".($row['agent_status'] == 'Do Not Disturb' ? "checked='checked'" : null).">&nbsp;".$text['option-do_not_disturb']."</label>\n";
				$html .= "	</td>\n";
			}
			$html .= "	<td ".$onclick." class='hide-sm-dn'>&nbsp;</td>\n";

			if ($per_queue_login) {
				$html .= "	<td class='description'>";
				if (is_array($row['queues'])) {
					$html .= "	<table class='list' >\n";
					$html .= "		<tr>\n";
					$html .= "			<th>".$text['label-queue']."</th>\n";
					$html .= "			<th>".$text['label-status']."</th>\n";
					$html .= "			<th>".$text['label-options']."</th>\n";
					$html .= "		</tr>\n";
					if (is_array($row['queues'])) {
						foreach ($row['queues'] as $queue) {
							$x++;
							$onclick = "onclick=\"cycle('agents[".$x."][agent_status]');\"";
							$html .= "	<tr class='list-row'>\n";
							$html .= "		<td ".$onclick." class='pct-80 no-wrap'>".$queue['queue_name']."</td>\n";
							$html .= "		<td>\n";
							if ($queue['queue_status'] == "Available") {
								$html .= "		".$text['option-available']."\n";
							}
							if ($queue['queue_status'] == "Logged Out") {
								$html .= "		".$text['option-logged_out']."\n";
							}
							$html .= "		</td>\n";
							$html .= "		<td class='no-wrap right'>";
							$html .= "			<input type='hidden' name='agents[".$x."][queue_name]' value='".escape($queue['queue_name'])."'>\n";
							$html .= "			<input type='hidden' name='agents[".$x."][agent_name]' value='".escape($row['agent_name'])."'>\n";
							$html .= "			<input type='hidden' name='agents[".$x."][user_uuid]' value='".escape($row['user_uuid'])."'>\n";
							$html .= "			<input type='hidden' name='agents[".$x."][queue_uuid]' value='".escape($queue['call_center_queue_uuid'])."'>\n";
							$html .= "			<input type='hidden' name='agents[".$x."][agent_uuid]' value='".escape($row['call_center_agent_uuid'])."'>\n";
							$html .= "			<label style='margin: 0; cursor: pointer; margin-right: 10px;'><input type='radio' name='agents[".$x."][agent_status]' value='Available' ".($queue['queue_status'] == 'Available' ? "checked='checked'" : null).">&nbsp;".$text['option-available']."</label>&nbsp;\n";
							$html .= "			<label style='margin: 0; cursor: pointer;'><input type='radio' name='agents[".$x."][agent_status]' value='Logged Out' ".($queue['queue_status'] == 'Logged Out' ? "checked='checked'" : null).">&nbsp;".$text['option-logged_out']."</label>\n";
							$html .= "		</td>\n";
							$html .= "	</tr>\n";
						}
					}
					$html .= "	</table>\n";
				}
				$html .= "	</td>\n";
			}
			$html .= "</tr>\n";
			if (count($_SESSION['domains']) > 1) {
				if ($row['domain_name'] == $_SESSION['domain_name']) {
					echo $html;
				}
			}
			else {
				echo $html;
			}
			$x++;
		}
		unset($agents);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//show the footer
	require_once "resources/footer.php";

?>
