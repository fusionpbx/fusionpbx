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
	Portions created by the Initial Developer are Copyright (C) 2017-2019
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

//get http variables and set as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//setup the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get the http post values and set them as php variables
	if (count($_POST) > 0) {
		foreach ($_POST['agents'] as $row) {
			if (strlen($row['agent_status']) > 0) {
				//agent set status
					if ($fp) {
						// update the database
							$array['call_center_agents'][0]['call_center_agent_uuid'] = $row['id'];
							$array['call_center_agents'][0]['domain_uuid'] = $_SESSION['user']['domain_uuid'];
							$array['call_center_agents'][0]['agent_status'] = $row['agent_status'];
							$database = new database;
							$database->app_name = 'call_centers_dashboard';
							$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
							$database->save($array);

						//set the call center status
							$cmd = "api callcenter_config agent set status ".$row['id']." '".$row['agent_status']."'";
							$response = event_socket_request($fp, $cmd);
						//set the agent status to available and assign the agent to the queue with the tier
							if ($row['agent_status'] == 'Available') {
								//assign the agent to the queue
								$cmd = "api callcenter_config tier add ".$row['queue_extension']."@".$_SESSION['domain_name']." ".$row['id']." 1 1";
								$response = event_socket_request($fp, $cmd);
							}

						//un-assign the agent from the queue
							if ($row['agent_status'] == 'Logged Out') {
								$cmd = "api callcenter_config tier del ".$row['queue_extension']."@".$_SESSION['domain_name']." ".$row['id'];
								$response = event_socket_request($fp, $cmd);
							}
							usleep(200);
							unset($parameters);
					}
			}
		}

		//set message
			//...

		//redirect
			header('Location: '.PROJECT_PATH.'/core/user_settings/user_dashboard.php');
			exit;
	}

//get the agent list from event socket
	$switch_cmd = 'callcenter_config tier list';
	$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
	$call_center_tiers = csv_to_named_array($event_socket_str, '|');

//get the call center queues from the database
	$sql = "select * from v_call_center_queues ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by queue_extension asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$call_center_queues = $database->select($sql, $parameters, 'all');
	$num_rows = !is_array($call_center_queues) ? 0 : @sizeof($call_center_queues);
	unset($sql, $parameters);

//get the agents from the database
	$sql = "select * from v_call_center_agents ";
	$sql .= "where user_uuid = :user_uuid ";
	$sql .= "and domain_uuid = :domain_uuid";
	$parameters['user_uuid'] = $_SESSION['user_uuid'];
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$agents = $database->select($sql, $parameters, 'all');
	if (count($agents) > 0) {
		$agent = $agents[0];
	}
	unset($sql, $parameters);

//update the queue status
	$x = 0;
	foreach ($call_center_queues as $queue) {
		$call_center_queues[$x]['queue_status'] = 'Logged Out';
		foreach ($call_center_tiers as $tier) {
			if ($queue['queue_extension'] .'@'. $_SESSION['user']['domain_name'] == $tier['queue'] && $agent['call_center_agent_uuid'] == $tier['agent']) {
				$call_center_queues[$x]['queue_status'] = $agent['agent_status'];
			}
		}
		$x++;
	}

//includes the header
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
	echo "<div class='action_bar sub'>\n";
	echo "	<div class='heading'><b>".$text['header-call_center_queues'].($agent['agent_name'] != '' ? "&nbsp;&nbsp;&nbsp;</b> Agent: <strong>".$agent['agent_name']."</strong>" : "</b>")."</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>false,'onclick'=>"list_form_submit('form_list_call_center_agent_dashboard');"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<form id='form_list_call_center_agent_dashboard' method='post'>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>".$text['label-queue_name']."</th>\n";
	echo "	<th class='shrink'>".$text['label-status']."</th>\n";
// 	echo "	<th>".$text['label-options']."</th>\n";
	echo "</tr>\n";

	if (is_array($call_center_queues) && @sizeof($call_center_queues) != 0) {
		$x = 0;
		foreach ($call_center_queues as $row) {
			$onclick = "onclick=\"cycle('agents[".$x."][agent_status]');\"";
			echo "<tr class='list-row'>\n";
			echo "	<td ".$onclick.">".escape($row['queue_name'])."</td>\n";
// 			echo "	<td>";
// 			if ($row['queue_status'] == "Available") {
// 				echo $text['option-available'];
// 			}
// 			if ($row['queue_status'] == "Logged Out") {
// 				echo $text['option-logged_out'];
// 			}
// 			echo "	</td>\n";
			echo "	<td class='no-wrap right'>\n";
			echo "		<input type='hidden' name='agents[".$x."][queue_extension]' value='".escape($row['queue_extension'])."'>\n";
			echo "		<input type='hidden' name='agents[".$x."][agent_name]' value='".escape($agent['agent_name'])."'>\n";
			echo "		<input type='hidden' name='agents[".$x."][id]' value='".escape($agent['call_center_agent_uuid'])."'>\n";
			echo "		<label style='margin: 0; cursor: pointer; margin-right: 10px;'><input type='radio' name='agents[".$x."][agent_status]' value='Available' ".($row['queue_status'] == 'Available' ? "checked='checked'" : null).">&nbsp;".$text['option-available']."</label>\n";
			echo "		<label style='margin: 0; cursor: pointer; margin-right: 10px;'><input type='radio' name='agents[".$x."][agent_status]' value='Logged Out' ".($row['queue_status'] == 'Logged Out' ? "checked='checked'" : null).">&nbsp;".$text['option-logged_out']."</label>\n";
			echo "		<label style='margin: 0; cursor: pointer;'><input type='radio' name='agents[".$x."][agent_status]' value='On Break' ".($row['queue_status'] == 'On Break' ? "checked='checked'" : null).">&nbsp;".$text['option-on_break']."</label>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;

		}
		unset($call_center_queues);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

?>
