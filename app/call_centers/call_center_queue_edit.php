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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_center_queue_add') || permission_exists('call_center_queue_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$call_center_queue_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get total call center queues count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['call_center_queues']['numeric'] != '') {
			$sql = "select count(*) as num_rows from v_call_center_queues ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				$total_call_center_queues = $row['num_rows'];
			}
			unset($prep_statement, $row);
			if ($total_call_center_queues >= $_SESSION['limit']['call_center_queues']['numeric']) {
				messages::add($text['message-maximum_queues'].' '.$_SESSION['limit']['call_center_queues']['numeric'], 'negative');
				header('Location: call_center_queues.php');
				return;
			}
		}
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		//get the post variables a run a security chack on them
			//$domain_uuid = check_str($_POST["domain_uuid"]);
			$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
			$queue_name = check_str($_POST["queue_name"]);
			$queue_extension = check_str($_POST["queue_extension"]);
			$queue_strategy = check_str($_POST["queue_strategy"]);
			$queue_moh_sound = check_str($_POST["queue_moh_sound"]);
			$queue_record_template = check_str($_POST["queue_record_template"]);
			$queue_time_base_score = check_str($_POST["queue_time_base_score"]);
			$queue_max_wait_time = check_str($_POST["queue_max_wait_time"]);
			$queue_max_wait_time_with_no_agent = check_str($_POST["queue_max_wait_time_with_no_agent"]);
			$queue_max_wait_time_with_no_agent_time_reached = check_str($_POST["queue_max_wait_time_with_no_agent_time_reached"]);
			$queue_tier_rules_apply = check_str($_POST["queue_tier_rules_apply"]);
			$queue_tier_rule_wait_second = check_str($_POST["queue_tier_rule_wait_second"]);
			$queue_tier_rule_wait_multiply_level = check_str($_POST["queue_tier_rule_wait_multiply_level"]);
			$queue_tier_rule_no_agent_no_wait = check_str($_POST["queue_tier_rule_no_agent_no_wait"]);
			$queue_timeout_action = check_str($_POST["queue_timeout_action"]);
			$queue_discard_abandoned_after = check_str($_POST["queue_discard_abandoned_after"]);
			$queue_abandoned_resume_allowed = check_str($_POST["queue_abandoned_resume_allowed"]);
			$queue_cid_prefix = check_str($_POST["queue_cid_prefix"]);
			$queue_announce_sound = check_str($_POST["queue_announce_sound"]);
			$queue_announce_frequency = check_str($_POST["queue_announce_frequency"]);
			$queue_cc_exit_keys = check_str($_POST["queue_cc_exit_keys"]);
			$queue_description = check_str($_POST["queue_description"]);

		//remove invalid characters
			$queue_cid_prefix = str_replace(":", "-", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("\"", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("@", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("\\", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("/", "", $queue_cid_prefix);
	}

//delete the tier (agent from the queue)
	if ($_REQUEST["a"] == "delete" && strlen($_REQUEST["id"]) > 0 && permission_exists("call_center_tier_delete")) {
		//set the variables
			$call_center_queue_uuid = check_str($_REQUEST["id"]);
			$call_center_tier_uuid = check_str($_REQUEST["call_center_tier_uuid"]);
		//get the agent details
			$sql = "select agent_name, queue_name, call_center_agent_uuid, call_center_queue_uuid ";
			$sql .= "from v_call_center_tiers ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and call_center_tier_uuid = '".$call_center_tier_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$tiers = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			if (is_array($tiers)) {
				foreach ($tiers as &$row) {
					$call_center_agent_uuid = $row["call_center_agent_uuid"];
					$call_center_queue_uuid = $row["call_center_queue_uuid"];
				}
			}
			unset ($prep_statement);
		//delete the agent from freeswitch
			//setup the event socket connection
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			//delete the agent over event socket
			if ($fp) {
				//callcenter_config tier del [queue_name] [agent_name]
				if (is_uuid($call_center_queue_uuid) && is_uuid($call_center_agent_uuid)) {
					$cmd = "api callcenter_config tier del ".$call_center_queue_uuid." ".$call_center_agent_uuid;
					$response = event_socket_request($fp, $cmd);
				}
			}
		//delete the tier from the database
			if (strlen($call_center_tier_uuid) > 0) {
				$sql = "delete from v_call_center_tiers where domain_uuid = '".$_SESSION['domain_uuid']."' and call_center_tier_uuid = '".$call_center_tier_uuid."'";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql);
			}
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$call_center_queue_uuid = check_str($_POST["call_center_queue_uuid"]);
			}
	
		//check for all required data
			$msg = '';
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
			if (strlen($queue_name) == 0) { $msg .= $text['message-required'].$text['label-queue_name']."<br>\n"; }
			if (strlen($queue_extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			if (strlen($queue_strategy) == 0) { $msg .= $text['message-required'].$text['label-strategy']."<br>\n"; }
			//if (strlen($queue_moh_sound) == 0) { $msg .= $text['message-required'].$text['label-music_on_hold']."<br>\n"; }
			//if (strlen($queue_record_template) == 0) { $msg .= $text['message-required'].$text['label-record_template']."<br>\n"; }
			//if (strlen($queue_time_base_score) == 0) { $msg .= $text['message-required'].$text['label-time_base_score']."<br>\n"; }
			//if (strlen($queue_max_wait_time) == 0) { $msg .= $text['message-required'].$text['label-max_wait_time']."<br>\n"; }
			//if (strlen($queue_max_wait_time_with_no_agent) == 0) { $msg .= $text['message-required'].$text['label-max_wait_time_with_no_agent']."<br>\n"; }
			//if (strlen($queue_max_wait_time_with_no_agent_time_reached) == 0) { $msg .= $text['message-required'].$text['label-max_wait_time_with_no_agent_time_reached']."<br>\n"; }
			//if (strlen($queue_tier_rules_apply) == 0) { $msg .= $text['message-required'].$text['label-tier_rules_apply']."<br>\n"; }
			//if (strlen($queue_tier_rule_wait_second) == 0) { $msg .= $text['message-required'].$text['label-tier_rule_wait_second']."<br>\n"; }
			//if (strlen($queue_tier_rule_wait_multiply_level) == 0) { $msg .= $text['message-required'].$text['label-tier_rule_wait_multiply_level']."<br>\n"; }
			//if (strlen($queue_tier_rule_no_agent_no_wait) == 0) { $msg .= $text['message-required'].$text['label-tier_rule_no_agent_no_wait']."<br>\n"; }
			//if (strlen($queue_timeout_action) == 0) { $msg .= $text['message-required'].$text['label-timeout_action']."<br>\n"; }
			//if (strlen($queue_discard_abandoned_after) == 0) { $msg .= $text['message-required'].$text['label-discard_abandoned_after']."<br>\n"; }
			//if (strlen($queue_abandoned_resume_allowed) == 0) { $msg .= $text['message-required'].$text['label-abandoned_resume_allowed']."<br>\n"; }
			//if (strlen($queue_cid_prefix) == 0) { $msg .= $text['message-required'].$text['label-caller_id_name_prefix']."<br>\n"; }
			//if (strlen($queue_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//set the domain_uuid
			$_POST["domain_uuid"] = $_SESSION["domain_uuid"];

		//add the call_center_queue_uuid
			if (strlen($_POST["call_center_queue_uuid"]) == 0) {
				$call_center_queue_uuid = uuid();
				$_POST["call_center_queue_uuid"] = $call_center_queue_uuid;
			}

		//add the dialplan_uuid
			if (strlen($_POST["dialplan_uuid"]) == 0) {
				$dialplan_uuid = uuid();
				$_POST["dialplan_uuid"] = $dialplan_uuid;
			}

		//update the call centier tiers array
			$x = 0;
			foreach ($_POST["call_center_tiers"] as $row) {
				//add the domain_uuid
					if (strlen($row["domain_uuid"]) == 0) {
						$_POST["call_center_tiers"][$x]["domain_uuid"] = $_SESSION['domain_uuid'];
					}
				//unset ring_group_destination_uuid if the field has no value
					if (strlen($row["call_center_agent_uuid"]) == 0) {
						unset($_POST["call_center_tiers"][$x]);
					}
				//increment the row
					$x++;
			}

		//get the application and data
			$action_array = explode(":",$queue_timeout_action);
			$queue_timeout_app = $action_array[0];
			unset($action_array[0]);
			$queue_timeout_data = implode($action_array);

		//build the xml dialplan
			$dialplan_xml = "<extension name=\"".$queue_name."\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^([^#]+#)(.*)\$\" break=\"never\">\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_name=\$2\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$queue_extension."$\">\n";
			$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
			if (strlen($queue_cid_prefix) > 0) {
				$dialplan_xml .= "		<action application=\"set\" data=\"effective_caller_id_name=".$queue_cid_prefix."#\${caller_id_name}\"/>\n";
			}
			if (strlen($queue_cc_exit_keys) > 0) {
				$dialplan_xml .= "		<action application=\"set\" data=\"cc_exit_keys=".$queue_cc_exit_keys."\"/>\n";
			}
			$dialplan_xml .= "		<action application=\"callcenter\" data=\"".$call_center_queue_uuid."\"/>\n";
			$dialplan_xml .= "		<action application=\"".$queue_timeout_app."\" data=\"".$queue_timeout_data."\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//build the dialplan array
			$dialplan["domain_uuid"] = $_SESSION['domain_uuid'];
			$dialplan["dialplan_uuid"] = $dialplan_uuid;
			$dialplan["dialplan_name"] = $queue_name;
			$dialplan["dialplan_number"] = $queue_extension;
			$dialplan["dialplan_context"] = $_SESSION['context'];
			$dialplan["dialplan_continue"] = "false";
			$dialplan["dialplan_xml"] = $dialplan_xml;
			$dialplan["dialplan_order"] = "230";
			$dialplan["dialplan_enabled"] = "true";
			$dialplan["dialplan_description"] = $queue_description;
			$dialplan["app_uuid"] = "95788e50-9500-079e-2807-fd530b0ea370";

		//prepare the array
			$array['call_center_queues'][] = $_POST;
			$array['dialplans'][] = $dialplan;

		//add the dialplan permission
			$p = new permissions;
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_edit", "temp");

		//save to the data
			$database = new database;
			$database->app_name = 'call_centers';
			$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
			$database->save($array);
			$message = $database->message;

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");

		//debug info
			//echo "<pre>". print_r($message, true) ."</pre>"; exit;

		//save the xml
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					messages::add($text['message-add']);
				}
				if ($action == "update") {
					messages::add($text['message-update']);
				}
			}

		//synchronize the configuration
			save_call_center_xml();
			remove_config_from_cache('configuration:callcenter.conf');

		//add agent/tier to queue
			$agent_name = check_str($_POST["agent_name"]);
			$tier_level = check_str($_POST["tier_level"]);
			$tier_position = check_str($_POST["tier_position"]);

			if ($agent_name != '') {
				//setup the event socket connection
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				//add the agent using event socket
					if ($fp) {
						/* syntax:
							callcenter_config tier add [queue_name] [agent_name] [level] [position]
							callcenter_config tier set state [queue_name] [agent_name] [state]
							callcenter_config tier set level [queue_name] [agent_name] [level]
							callcenter_config tier set position [queue_name] [agent_name] [position]
						*/
						//add the agent
						if (is_uuid($call_center_queue_uuid) && is_uuid($call_center_agent_uuid) && is_numeric($tier_level) && is_numeric($tier_position)) {
							$cmd = "api callcenter_config tier add ".$call_center_queue_uuid." ".$call_center_agent_uuid." ".$tier_level." ".$tier_position;
							$response = event_socket_request($fp, $cmd);
						}
						usleep(200);
						//agent set level
						if (is_uuid($call_center_queue_uuid) && is_uuid($call_center_agent_uuid) && is_numeric($tier_level)) {
							$cmd = "api callcenter_config tier set level ".$call_center_queue_uuid." ".$call_center_agent_uuid." ".$tier_level;
							$response = event_socket_request($fp, $cmd);
						}
						usleep(200);
						//agent set position
						if (is_uuid($call_center_queue_uuid) && is_uuid($call_center_agent_uuid) && is_numeric($tier_position)) {
							$cmd = "api callcenter_config tier set position ".$call_center_queue_uuid." ".$call_center_agent_uuid." ".$tier_position;
							$response = event_socket_request($fp, $cmd);
						}
						usleep(200);
					}
			}

		//syncrhonize configuration
			save_call_center_xml();

		//remove the cache
			remove_config_from_cache('configuration:callcenter.conf');

		//redirect the user
			header("Location: call_center_queue_edit.php?id=".urlencode($call_center_queue_uuid));
			return;

	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (is_array($_GET) && is_uuid($_GET["id"]) && $_POST["persistformvar"] != "true") {
		$call_center_queue_uuid = $_GET["id"];
		$sql = "select * from v_call_center_queues ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and call_center_queue_uuid = '$call_center_queue_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$call_center_queues = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (is_array($call_center_queues)) {
			foreach ($call_center_queues as &$row) {
				$queue_name = $row["queue_name"];
				$dialplan_uuid = $row["dialplan_uuid"];
				$database_queue_name = $row["queue_name"];
				$queue_extension = $row["queue_extension"];
				$queue_strategy = $row["queue_strategy"];
				$queue_moh_sound = $row["queue_moh_sound"];
				$queue_record_template = $row["queue_record_template"];
				$queue_time_base_score = $row["queue_time_base_score"];
				$queue_max_wait_time = $row["queue_max_wait_time"];
				$queue_max_wait_time_with_no_agent = $row["queue_max_wait_time_with_no_agent"];
				$queue_max_wait_time_with_no_agent_time_reached = $row["queue_max_wait_time_with_no_agent_time_reached"];
				$queue_timeout_action = $row["queue_timeout_action"];
				$queue_tier_rules_apply = $row["queue_tier_rules_apply"];
				$queue_tier_rule_wait_second = $row["queue_tier_rule_wait_second"];
				$queue_tier_rule_wait_multiply_level = $row["queue_tier_rule_wait_multiply_level"];
				$queue_tier_rule_no_agent_no_wait = $row["queue_tier_rule_no_agent_no_wait"];
				$queue_discard_abandoned_after = $row["queue_discard_abandoned_after"];
				$queue_abandoned_resume_allowed = $row["queue_abandoned_resume_allowed"];
				$queue_cid_prefix = $row["queue_cid_prefix"];
				$queue_announce_sound = $row["queue_announce_sound"];
				$queue_announce_frequency = $row["queue_announce_frequency"];
				$queue_cc_exit_keys = $row["queue_cc_exit_keys"];
				$queue_description = $row["queue_description"];
			}
		}
		unset ($prep_statement);
	}

//get the tiers
	$sql = "select t.call_center_tier_uuid, t.call_center_agent_uuid, t.call_center_queue_uuid, t.tier_level, t.tier_position, a.agent_name ";
	$sql .= "from v_call_center_tiers as t, v_call_center_agents as a ";
	$sql .= "where t.call_center_queue_uuid = '".$call_center_queue_uuid."' ";
	$sql .= "and t.call_center_agent_uuid = a.call_center_agent_uuid ";
	$sql .= "and t.domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by tier_level asc, tier_position asc, agent_name asc";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$tiers = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//add an empty row to the tiers array
	if (count($tiers) == 0) {
		$rows = $_SESSION['call_center']['agent_add_rows']['numeric'];
		$id = 0;
	}
	if (count($tiers) > 0) {
		$rows = $_SESSION['call_center']['agent_edit_rows']['numeric'];
		$id = count($tiers)+1;
	}
	for ($x = 0; $x < $rows; $x++) {
		$tiers[$id]['call_center_tier_uuid'] = uuid();
		$tiers[$id]['call_center_agent_uuid'] = '';
		$tiers[$id]['call_center_queue_uuid'] = $call_center_queue_uuid;
		$tiers[$id]['tier_level'] = '';
		$tiers[$id]['tier_position'] = '';
		$tiers[$id]['agent_name'] = '';
		$id++;
	}

//get the agents
	$sql = "select call_center_agent_uuid, agent_name from v_call_center_agents ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by agent_name asc";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$agents = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//set default values
	if (strlen($queue_strategy) == 0) { $queue_strategy = "longest-idle-agent"; }
	if (strlen($queue_moh_sound) == 0) { $queue_moh_sound = "\$\${hold_music}"; }
	if (strlen($queue_time_base_score) == 0) { $queue_time_base_score = "system"; }
	if (strlen($queue_max_wait_time) == 0) { $queue_max_wait_time = "0"; }
	if (strlen($queue_max_wait_time_with_no_agent) == 0) { $queue_max_wait_time_with_no_agent = "90"; }
	if (strlen($queue_max_wait_time_with_no_agent_time_reached) == 0) { $queue_max_wait_time_with_no_agent_time_reached = "30"; }
	if (strlen($queue_tier_rules_apply) == 0) { $queue_tier_rules_apply = "false"; }
	if (strlen($queue_tier_rule_wait_second) == 0) { $queue_tier_rule_wait_second = "30"; }
	if (strlen($queue_tier_rule_wait_multiply_level) == 0) { $queue_tier_rule_wait_multiply_level = "true"; }
	if (strlen($queue_tier_rule_no_agent_no_wait) == 0) { $queue_tier_rule_no_agent_no_wait = "true"; }
	if (strlen($queue_discard_abandoned_after) == 0) { $queue_discard_abandoned_after = "900"; }
	if (strlen($queue_abandoned_resume_allowed) == 0) { $queue_abandoned_resume_allowed = "false"; }

//show the header
	require_once "resources/header.php";
	if ($action == "add") {
		$document['title'] = $text['title-call_center_queue_add'];
	}
	if ($action == "update") {
		$document['title'] = $text['title-call_center_queue_edit'];
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' nowrap='nowrap'><b>".$text['header-call_center_queue_add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' nowrap='nowrap'><b>".$text['header-call_center_queue_edit']."</b></td>\n";
	}
	echo "<td align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_center_queues.php'\" value='".$text['button-back']."'>\n";
	if ($action == "update") {
		echo "	&nbsp;&nbsp;&nbsp;";
		if (permission_exists('call_center_wallboard')) {
			echo "  <input type='button' class='btn' value='".$text['button-wallboard']."' onclick=\"document.location.href='".PROJECT_PATH."/app/call_center_wallboard/call_center_wallboard.php?queue_name=".urlencode($call_center_queue_uuid)."';\" />\n";
		}
		echo "  <input type='button' class='btn' value='".$text['button-stop']."' onclick=\"document.location.href='cmd.php?cmd=unload&queue=".urlencode($call_center_queue_uuid)."';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-start']."' onclick=\"document.location.href='cmd.php?cmd=load&queue=".urlencode($call_center_queue_uuid)."';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-restart']."' onclick=\"document.location.href='cmd.php?cmd=reload&queue=".urlencode($call_center_queue_uuid)."';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-view']."' onclick=\"document.location.href='".PROJECT_PATH."/app/call_center_active/call_center_active.php?queue_name=".urlencode($call_center_queue_uuid)."';\" />\n";
		echo "	&nbsp;&nbsp;&nbsp;";
	}
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-queue_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='queue_name' maxlength='255' value=\"".escape($queue_name)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-queue_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='queue_extension' maxlength='255' min='0' step='1' value=\"".escape($queue_extension)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-strategy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_strategy'>\n";
	if ($queue_strategy == "ring-all") {
		echo "	<option value='ring-all' selected='selected' >".$text['option-ring_all']."</option>\n";
	}
	else {
		echo "	<option value='ring-all'>".$text['option-ring_all']."</option>\n";
	}
	if ($queue_strategy == "longest-idle-agent") {
		echo "	<option value='longest-idle-agent' selected='selected' >".$text['option-longest_idle_agent']."</option>\n";
	}
	else {
		echo "	<option value='longest-idle-agent'>".$text['option-longest_idle_agent']."</option>\n";
	}
	if ($queue_strategy == "round-robin") {
		echo "	<option value='round-robin' selected='selected'>".$text['option-round_robin']."</option>\n";
	}
	else {
		echo "	<option value='round-robin'>".$text['option-round_robin']."</option>\n";
	}
	if ($queue_strategy == "top-down") {
		echo "	<option value='top-down' selected='selected'>".$text['option-top_down']."</option>\n";
	}
	else {
		echo "	<option value='top-down'>".$text['option-top_down']."</option>\n";
	}
	if ($queue_strategy == "agent-with-least-talk-time") {
		echo "	<option value='agent-with-least-talk-time' selected='selected'>".$text['option-agent_with_least_talk_time']."</option>\n";
	}
	else {
		echo "	<option value='agent-with-least-talk-time'>".$text['option-agent_with_least_talk_time']."</option>\n";
	}

	if ($queue_strategy == "agent-with-fewest-calls") {
		echo "	<option value='agent-with-fewest-calls' selected='selected'>".$text['option-agent_with_fewest_calls']."</option>\n";
	}
	else {
		echo "	<option value='agent-with-fewest-calls'>".$text['option-agent_with_fewest_calls']."</option>\n";
	}
	if ($queue_strategy == "sequentially-by-agent-order") {
		echo "	<option value='sequentially-by-agent-order' selected='selected'>".$text['option-sequentially_by_agent_order']."</option>\n";
	}
	else {
		echo "	<option value='sequentially-by-agent-order'>".$text['option-sequentially_by_agent_order']."</option>\n";
	}
	if ($queue_strategy == "sequentially-by-next-agent-order") {
		echo "	<option value='sequentially-by-next-agent-order' selected='selected'>".$text['option-sequentially_by_next_agent_order']."</option>\n";
	}
	else {
		echo "	<option value='sequentially-by-next-agent-order'>".$text['option-sequentially_by_next_agent_order']."</option>\n";
	}
	if ($queue_strategy == "random") {
		echo "	<option value='random' selected='selected'>".$text['option-random']."</option>\n";
	}
	else {
		echo "	<option value='random'>".$text['option-random']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-strategy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('call_center_tier_view') && is_array($agents) && count($agents) > 0) {
		echo "<tr>";
		echo "	<td class='vncell' valign='top'>".$text['label-agents']."</td>";
		echo "	<td class='vtable' align='left'>";
		echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-agent_name']."</td>\n";
		echo "				<td class='vtable' style='text-align: center;'>".$text['label-tier_level']."</td>\n";
		echo "				<td class='vtable' style='text-align: center;'>".$text['label-tier_position']."</td>\n";
		echo "				<td></td>\n";
		echo "			</tr>\n";
		$x = 0;
		foreach($tiers as $field) {
			echo "	<tr>\n";
			echo "		<td class=''>";
			if (strlen($field['call_center_tier_uuid']) > 0) {
				echo "		<input name='call_center_tiers[".$x."][call_center_tier_uuid]' type='hidden' value=\"".escape($field['call_center_tier_uuid'])."\">\n";
			}
			echo "				<select name=\"call_center_tiers[$x][call_center_agent_uuid]\" class=\"formfld\">\n";
			echo "					<option value=\"\"></option>\n";
			foreach($agents as $row) {
				$selected = '';
				if ($row['call_center_agent_uuid'] == $field['call_center_agent_uuid']) {
					$selected = "selected=\"selected\"";
				}
				echo "				<option value=\"".escape($row['call_center_agent_uuid'])."\" $selected>".escape($row['agent_name'])."</option>\n";
			}
			echo "				</select>";
			echo "		</td>\n";
			echo "		<td class='' style='text-align: center;'>";
			echo "				 <select name=\"call_center_tiers[$x][tier_level]\" class=\"formfld\">\n";
			$i=0;
			while($i<=9) {
				$selected = ($i == $field['tier_level']) ? "selected" : null;
				echo "				<option value=\"$i\" ".escape($selected).">$i</option>\n";
				$i++;
			}
			echo "				</select>\n";
			echo "		</td>\n";

			echo "		<td class='' style='text-align: center;'>\n";
			echo "				<select name=\"call_center_tiers[$x][tier_position]\" class=\"formfld\">\n";
			$i=0;
			while($i<=9) {
				$selected = ($i == $field['tier_position']) ? "selected" : null;
				echo "				<option value=\"$i\" ".escape($selected).">$i</option>\n";
				$i++;
			}
			echo "				</select>\n";
			echo "		</td>\n";
			echo "		<td class=''>";
			if (permission_exists('call_center_tier_delete')) {
				echo "			<a href=\"call_center_queue_edit.php?id=".escape($call_center_queue_uuid)."&call_center_tier_uuid=".escape($field['call_center_tier_uuid'])."&a=delete\" alt=\"".$text['button-delete']."\" onclick=\"return confirm('".$text['confirm-delete']."');\">$v_link_label_delete</a>";
			}
			echo "		</td>\n";
			echo "	</tr>\n";
			$assigned_agents[] = $field['agent_name'];
			$x++;
		}
		unset ($prep_statement, $sql, $tiers);
		echo "		</table>\n";
		echo "		<br>\n";
		echo "		".$text['description-tiers']."\n";
		echo "		<br />\n";
		echo "	</td>";
		echo "</tr>";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-music_on_hold']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$ringbacks = new ringbacks;
	echo $ringbacks->select('queue_moh_sound', $queue_moh_sound);

	echo "<br />\n";
	echo $text['description-music_on_hold']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-record_template']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$record_template = $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}/\${uuid}.\${record_ext}";
	echo "	<select class='formfld' name='queue_record_template'>\n";
	if (strlen($queue_record_template) > 0) {
		echo "	<option value='".escape($record_template)."' selected='selected' >".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='".escape($record_template)."'>".$text['option-true']."</option>\n";
	}
	if (strlen($queue_record_template) == 0) {
		echo "	<option value='' selected='selected' >".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value=''>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-record_template']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-time_base_score']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_time_base_score'>\n";
	if ($queue_time_base_score == "system") {
		echo "	<option value='system' selected='selected' >".$text['option-system']."</option>\n";
	}
	else {
		echo "	<option value='system'>".$text['option-system']."</option>\n";
	}
	if ($queue_time_base_score == "queue") {
		echo "	<option value='queue' selected='selected' >".$text['option-queue']."</option>\n";
	}
	else {
		echo "	<option value='queue'>".$text['option-queue']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-time_base_score']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-max_wait_time']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_max_wait_time' maxlength='255' min='0' step='1' value='".escape($queue_max_wait_time)."'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-max_wait_time_with_no_agent']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_max_wait_time_with_no_agent' maxlength='255' min='0' step='1' value='".escape($queue_max_wait_time_with_no_agent)."'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time_with_no_agent']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-max_wait_time_with_no_agent_time_reached']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_max_wait_time_with_no_agent_time_reached' maxlength='255' min='0' step='1' value='".escape($queue_max_wait_time_with_no_agent_time_reached)."'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time_with_no_agent_time_reached']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-timeout_action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'queue_timeout_action', escape($queue_timeout_action));
	echo "<br />\n";
	echo $text['description-timeout_action']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-tier_rules_apply']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_tier_rules_apply'>\n";
	if ($queue_tier_rules_apply == "true") {
		echo "	<option value='true' selected='selected' >".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($queue_tier_rules_apply == "false") {
		echo "	<option value='false' selected='selected' >".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-tier_rules_apply']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-tier_rule_wait_second']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_tier_rule_wait_second' maxlength='255' min='0' step='1' value='".escape($queue_tier_rule_wait_second)."'>\n";
	echo "<br />\n";
	echo $text['description-tier_rule_wait_second']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-tier_rule_wait_multiply_level']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_tier_rule_wait_multiply_level'>\n";
	if ($queue_tier_rule_wait_multiply_level == "true") {
		echo "	<option value='true' selected='selected' >".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($queue_tier_rule_wait_multiply_level == "false") {
		echo "	<option value='false' selected='selected' >".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-tier_rule_wait_multiply_level']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-tier_rule_no_agent_no_wait']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_tier_rule_no_agent_no_wait'>\n";
	if ($queue_tier_rule_no_agent_no_wait == "true") {
		echo "	<option value='true' selected='selected' >".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($queue_tier_rule_no_agent_no_wait == "false") {
		echo "	<option value='false' selected='selected' >".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-tier_rule_no_agent_no_wait']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-discard_abandoned_after']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_discard_abandoned_after' maxlength='255' min='0' step='1' value='".escape($queue_discard_abandoned_after)."'>\n";
	echo "<br />\n";
	echo $text['description-discard_abandoned_after']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-abandoned_resume_allowed']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_abandoned_resume_allowed'>\n";
	if ($queue_abandoned_resume_allowed == "false") {
		echo "	<option value='false' selected='selected' >".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	if ($queue_abandoned_resume_allowed == "true") {
		echo "	<option value='true' selected='selected' >".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-abandoned_resume_allowed']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_cid_prefix' maxlength='255' value='".escape($queue_cid_prefix)."'>\n";
	echo "<br />\n";
	echo $text['description-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "  ".$text['label-caller_announce_sound']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_announce_sound' maxlength='255' value='".escape($queue_announce_sound)."'>\n";
	echo "<br />\n";
	echo $text['description-caller_announce_sound']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "  ".$text['label-caller_announce_frequency']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_announce_frequency' maxlength='255' min='0' step='1' value='".escape($queue_announce_frequency)."'>\n";
	echo "<br />\n";
	echo $text['description-caller_announce_frequency']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "  ".$text['label-exit_keys']."\n";
   	echo "</td>\n";
   	echo "<td class='vtable' align='left'>\n";
   	echo "  <input class='formfld' type='text' name='queue_cc_exit_keys' value='".escape($queue_cc_exit_keys)."'>\n";
   	echo "<br />\n";
   	echo $text['description-exit_keys']."\n";
   	echo "</td>\n";
   	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='queue_description' maxlength='255' value=\"".escape($queue_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='call_center_queue_uuid' value='".escape($call_center_queue_uuid)."'>\n";
		echo "		<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "			<br />";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
