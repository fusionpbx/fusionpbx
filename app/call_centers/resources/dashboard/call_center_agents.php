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
	Portions created by the Initial Developer are Copyright (C) 2017-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
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

//get http variables and set as php variables
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//connect to the database
	if (!isset($database)) {
		$database = new database;
	}

//setup the event socket connection
	$esl = event_socket::create();

//get the http post values and set them as php variables
	if (!empty($_POST['agents'])) {

		//get the agent id
		if (is_uuid($_POST['agents'][0]['id'])) {
			$agent_uuid = $_POST['agents'][0]['id'];
		}

		//get the agent details from event socket
		 if (is_uuid($agent_uuid)) {
			$switch_cmd = 'callcenter_config agent list '.$agent['call_center_agent_uuid'];
			$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
			$call_center_agent = csv_to_named_array($event_socket_str, '|');
			$agent['agent_status'] = $call_center_agent[1]['status'];
		 }

		//find the agent_status - used for mod_call_center as there is only one agent status not one per queue
		$agent_status = 'Logged Out';
		foreach ($_POST['agents'] as $row) {
			if ($row['agent_status'] == 'Available') {
				$agent_status = 'Available';
				break;
			}
			if ($row['agent_status'] == 'On Break') {
				$agent_status = 'On Break';
				break;
			}
		}

		//save the agent_stat change to the database
		$array['call_center_agents'][0]['call_center_agent_uuid'] = $agent_uuid;
		$array['call_center_agents'][0]['domain_uuid'] = $_SESSION['user']['domain_uuid'];
		$array['call_center_agents'][0]['agent_status'] = $agent_status;
		$database->app_name = 'call_centers_dashboard';
		$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
		$result = $database->save($array);

		//send the agent status status to mod_call_center
		$cmd = "callcenter_config agent set status ".$agent_uuid." '".$agent_status."'";
		$response = event_socket::api($cmd);

		//add or delete agents from the queue assigned by the tier
		foreach ($_POST['agents'] as $row) {

			//agent set status
			if ($fp && is_numeric($row['queue_extension']) && is_uuid($row['id'])) {

				//set the agent status to available and assign the agent to the queue with the tier
				if ($row['agent_status'] == 'Available') {
					//assign the agent to the queue
					$cmd = "callcenter_config tier add ".$row['queue_extension']."@".$_SESSION['domain_name']." ".$row['id']." 1 1";
					$response = event_socket::api($cmd);
				}

				//set the agent status to available and assign the agent to the queue with the tier
				if ($row['agent_status'] == 'On Break') {
					//assign the agent to the queue
					$cmd = "callcenter_config tier add ".$row['queue_extension']."@".$_SESSION['domain_name']." ".$row['id']." 1 1";
					$response = event_socket::api($cmd);
				}

				//un-assign the agent from the queue
				if ($row['agent_status'] == 'Logged Out') {
					$cmd = "callcenter_config tier del ".$row['queue_extension']."@".$_SESSION['domain_name']." ".$row['id'];
					$response = event_socket::api($cmd);
				}

				//small sleep
				usleep(200);
			}
		}

		//redirect
		header('Location: '.PROJECT_PATH.'/core/dashboard/');
		exit;
	}

//get the agent list from event socket
	$switch_cmd = 'callcenter_config tier list';
	$event_socket_str = trim(event_socket::api($switch_cmd));
	$call_center_tiers = csv_to_named_array($event_socket_str, '|');

//get the agents from the database
	$sql = "select * from v_call_center_agents ";
	$sql .= "where user_uuid = :user_uuid ";
	$sql .= "and domain_uuid = :domain_uuid";
	$parameters['user_uuid'] = $_SESSION['user_uuid'];
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$agents = $database->select($sql, $parameters, 'all');
	if (!empty($agents)) {
		$agent = $agents[0];
	}
	unset($sql, $parameters);

//get the call center queues from the database
	//if ($settings->get('call_center, queue_login', '') == 'dynamic') {
		$sql = "select * from v_call_center_queues ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and call_center_queue_uuid in ( ";
		$sql .= "	select call_center_queue_uuid from v_call_center_tiers ";
		$sql .= "	where call_center_agent_uuid = :call_center_agent_uuid ";
		$sql .= ") ";
		$parameters['call_center_agent_uuid'] = $agent['call_center_agent_uuid'];
		$sql .= "order by queue_extension asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$call_center_queues = $database->select($sql, $parameters, 'all');
		$num_rows = !is_array($call_center_queues) ? 0 : @sizeof($call_center_queues);
		unset($sql, $parameters);
	//}

//get the agent details from event socket
	$switch_cmd = 'callcenter_config agent list '.($agent['call_center_agent_uuid'] ?? null);
	$event_socket_str = trim(event_socket_request($fp ?? null, 'api '.$switch_cmd));
	$call_center_agent = csv_to_named_array($event_socket_str, '|');

//set the agent status
	$agent['agent_status'] = $call_center_agent[1]['status'];

//update the queue status
	$x = 0;
	if (!empty($call_center_queues) && is_array($call_center_queues)) {
		foreach ($call_center_queues as $queue) {
			$call_center_queues[$x]['queue_status'] = 'Logged Out';
			foreach ($call_center_tiers as $tier) {
				if ($queue['queue_extension'] .'@'. $_SESSION['user']['domain_name'] == $tier['queue'] && $agent['call_center_agent_uuid'] == $tier['agent']) {
					$call_center_queues[$x]['queue_status'] = $agent['agent_status'];
				}
			}
			$x++;
		}
	}

//includes the header
	require_once "resources/header.php";

//radio button cycle script
	echo "<script>\n";
	echo "\n";

	echo "	function get_selected(radio_group) {\n";
	echo "		for (var i = 0; i < radio_group.length; i++) {\n";
	echo "			if (radio_group[i].checked) { return i; }\n";
	echo "		}\n";
	echo "		return 0;\n";
	echo "	}\n";
	echo "\n";

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
	echo "\n";

	echo "	function handle_status_change(agent_index) {\n";
	echo "		var agent_status = document.querySelector('input[name=\"agents[' + agent_index + '][agent_status]\"]:checked').value;\n";
	echo "		var radio_buttons = document.querySelectorAll('#form_list_call_center_agent_dashboard input[type=\"radio\"]');\n";
	//echo "console.log('status: '+agent_status);\n";
	//echo "		radio_checked_value = '';\n";
	echo "		radio_buttons.forEach(function(radio_button) {\n";
	//echo "			if (radio_button.checked == true && radio_button.value === 'Available') {\n";
	//echo "				radio_checked_value = 'Available';\n";
	//echo "			}\n";
	//echo "			if (radio_button.checked == true && radio_button.value === 'On Break') {\n";
	//echo "				radio_checked_value = 'On Break';\n";
	//echo "			}\n";
	//echo "			if (radio_button.value === 'Logged Out') {\n";
	//echo "				radio_checked_value = 'Logged Out';\n";
	//echo "			}\n";
	echo "			if (radio_button.checked) { console.log('checked: '+radio_button.value) }\n";
	echo "			if (radio_button.value === 'Available' && agent_status === 'Available') {\n"; // radio_checked_value == 'On Break' &&
	//echo "				radio_button.checked = true;\n";
	//echo "				radio_button.value = 'Available';\n";
	//echo "				radio_button[agent_status]\"]:checked').value == 'On Break';\n";
	//echo "				console.log('--'+radio_button.value+'--');\n";
	//echo "				console.log('need to change status On Break to Available');\n";
	//echo "				console.log('---');\n";
	echo "			}\n";
	echo "			if (radio_button.value === 'On Break' && agent_status === 'On Break') {\n"; // radio_checked_value == 'Available' &&
	//echo "				radio_button.checked = true;\n";
	//echo "				radio_button.value = 'On Break';\n";
	//echo "				radio_button[agent_status]\"]:checked').value == 'On Break';\n";
	//echo "				console.log('--'+radio_button.value+'--');\n";
	//echo "				console.log('need to change status Available to On Break');\n";
	//echo "				console.log('---');\n";
	echo "			}\n";

	//echo "			console.log('checked: '+radio_button.checked+' value:'+radio_button.value);\n";
	//echo "			console.log('radio checked value: '+ radio_checked_value +' checked: '+radio_button.checked+' value:'+radio_button.value);\n";
	//else if (agent_status === 'Available') {\n";
	//echo "				radio_button.checked = true;\n";
	//echo "			}\n";
	echo "		});\n";

	echo "	}\n";

	echo "\n";
	echo "</script>\n";

//show the content
	echo "<div class='hud_box'>";

	echo "<div class='hud_content' style='display: block;'>\n";
	echo "	<div class='action_bar sub'>\n";
	echo "		<div class='heading' style='padding-left: 5px;'><b>".$text['header-call_center_queues'].(!empty($agent['agent_name']) ? "&nbsp;&nbsp;&nbsp;</b> Agent: <strong>".$agent['agent_name']."</strong>" : "</b>")."</div>\n";
	echo "		<div class='actions' style='padding-top: 2px;'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'collapse'=>false,'onclick'=>"document.getElementById('form_list_call_center_agent_dashboard').submit();"]);
	echo "		</div>\n";
	echo "		<div style='clear: both;'></div>\n";
	echo "	</div>\n";

	echo "	<form id='form_list_call_center_agent_dashboard' method='post'>\n";

	echo "	<table class='list' style='padding: 0 5px;'>\n";
	echo "	<tr class='list-header'>\n";
	echo "		<th>".$text['label-queue_name']."</th>\n";
	echo "		<th class='shrink'>".$text['label-status']."</th>\n";
	echo "	</tr>\n";

	if (!empty($call_center_queues) && is_array($call_center_queues) && @sizeof($call_center_queues) != 0) {
		$x = 0;
		foreach ($call_center_queues as $row) {
			$onclick = "onclick=\"cycle('agents[".$x."][agent_status]');\"";
			$onclick = '';
			echo "<tr class='list-row'>\n";
			echo "	<td ".$onclick.">".escape($row['queue_name'])."</td>\n";
			echo "	<td class='no-wrap right'>\n";
			echo "		<input type='hidden' name='agents[".$x."][queue_extension]' value='".escape($row['queue_extension'])."'>\n";
			echo "		<input type='hidden' name='agents[".$x."][agent_name]' value='".escape($agent['agent_name'])."'>\n";
			echo "		<input type='hidden' name='agents[".$x."][id]' value='".escape($agent['call_center_agent_uuid'])."'>\n";
			echo "		<label style='margin: 0; cursor: pointer; margin-right: 10px;'><input type='radio' name='agents[".$x."][agent_status]' value='Available' ".($row['queue_status'] == 'Available' ? "checked='checked'" : null)." onchange='handle_status_change(".$x.")'>&nbsp;".$text['option-available']."</label>\n";
			echo "		<label style='margin: 0; cursor: pointer; margin-right: 10px;'><input type='radio' name='agents[".$x."][agent_status]' value='Logged Out' ".($row['queue_status'] == 'Logged Out' ? "checked='checked'" : null)." onchange='handle_status_change(".$x.")'>&nbsp;".$text['option-logged_out']."</label>\n";
			echo "		<label style='margin: 0; cursor: pointer;'><input type='radio' name='agents[".$x."][agent_status]' value='On Break' ".($row['queue_status'] == 'On Break' ? "checked='checked'" : null)." onchange='handle_status_change(".$x.")'>&nbsp;".$text['option-on_break']."</label>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
		}
		unset($call_center_queues);
	}

	echo "	</table>\n";
	echo "	<br />\n";
	echo "	<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "	</form>\n";
	echo "</div>\n";

	echo "</div>\n";

?>
