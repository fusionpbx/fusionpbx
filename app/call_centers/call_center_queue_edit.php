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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
			$sql = "select count(*) as num_rows from v_call_center_queues where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				$total_call_center_queues = $row['num_rows'];
			}
			unset($prep_statement, $row);
			if ($total_call_center_queues >= $_SESSION['limit']['call_center_queues']['numeric']) {
				$_SESSION['message_mood'] = 'negative';
				$_SESSION['message'] = $text['message-maximum_queues'].' '.$_SESSION['limit']['call_center_queues']['numeric'];
				header('Location: call_center_queues.php');
				return;
			}
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//get the post variables a run a security chack on them
			//$domain_uuid = check_str($_POST["domain_uuid"]);
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
			$queue_description = check_str($_POST["queue_description"]);

		//replace the space in the queue name with a dash
			$queue_name = str_replace(" ", "-", $queue_name);

		//remove invalid characters
			$queue_cid_prefix = str_replace(":", "-", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("\"", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("@", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("\\", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("/", "", $queue_cid_prefix);
	}

//delete the tier (agent from the queue)
	if ($_REQUEST["delete_type"] == "tier" && strlen($_REQUEST["delete_uuid"]) > 0 && permission_exists("call_center_tier_delete")) {
		//set the variables
			$call_center_queue_uuid = check_str($_REQUEST["id"]);
			$tier_uuid = check_str($_REQUEST["delete_uuid"]);
		//get the agent details
			$sql = "
				select
					agent_name,
					queue_name
				from
					v_call_center_tiers
				where
					domain_uuid = '".$_SESSION['domain_uuid']."' and
					call_center_tier_uuid = '".$tier_uuid."'
					";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$agent_name = $row["agent_name"];
				$queue_name = $row["queue_name"];
				break; //limit to 1 row
			}
			unset ($prep_statement);
		//delete the agent from freeswitch
			//get the domain using the $_SESSION['domain_uuid']
			$tmp_domain = $_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'];
			//setup the event socket connection
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			//delete the agent over event socket
			if ($fp) {
				//callcenter_config tier del [queue_name] [agent_name]
				$cmd = "api callcenter_config tier del ".$queue_name."@".$tmp_domain." ".$agent_name."@".$_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'];
				$response = event_socket_request($fp, $cmd);
			}
		//delete the tier from the database
			if (strlen($tier_uuid)>0) {
				$sql = "delete from v_call_center_tiers where domain_uuid = '".$_SESSION['domain_uuid']."' and call_center_tier_uuid = '".$tier_uuid."'";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql);
			}
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$call_center_queue_uuid = check_str($_POST["call_center_queue_uuid"]);
	}

	//check for all required data
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

	//add or update the database
	if ($_POST["persistformvar"] != "true") {
		if ($action == "add") {
			//add the call center queue
				$call_center_queue_uuid = uuid();
				$sql = "insert into v_call_center_queues ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "call_center_queue_uuid, ";
				$sql .= "queue_name, ";
				$sql .= "queue_extension, ";
				$sql .= "queue_strategy, ";
				$sql .= "queue_moh_sound, ";
				$sql .= "queue_record_template, ";
				$sql .= "queue_time_base_score, ";
				$sql .= "queue_max_wait_time, ";
				$sql .= "queue_max_wait_time_with_no_agent, ";
				$sql .= "queue_max_wait_time_with_no_agent_time_reached, ";
				$sql .= "queue_tier_rules_apply, ";
				$sql .= "queue_tier_rule_wait_second, ";
				$sql .= "queue_tier_rule_wait_multiply_level, ";
				$sql .= "queue_tier_rule_no_agent_no_wait, ";
				$sql .= "queue_timeout_action, ";
				$sql .= "queue_discard_abandoned_after, ";
				$sql .= "queue_abandoned_resume_allowed, ";
				$sql .= "queue_cid_prefix, ";
				if (strlen($queue_announce_sound) > 0) {
					$sql .= "queue_announce_sound, ";
				}
				if (strlen($queue_announce_frequency) > 0) {
					$sql .= "queue_announce_frequency, ";
				}
				$sql .= "queue_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$_SESSION['domain_uuid']."', ";
				$sql .= "'$call_center_queue_uuid', ";
				$sql .= "'$queue_name', ";
				$sql .= "'$queue_extension', ";
				$sql .= "'$queue_strategy', ";
				$sql .= "'$queue_moh_sound', ";
				$sql .= "'$queue_record_template', ";
				$sql .= "'$queue_time_base_score', ";
				$sql .= "'$queue_max_wait_time', ";
				$sql .= "'$queue_max_wait_time_with_no_agent', ";
				$sql .= "'$queue_max_wait_time_with_no_agent_time_reached', ";
				$sql .= "'$queue_tier_rules_apply', ";
				$sql .= "'$queue_tier_rule_wait_second', ";
				$sql .= "'$queue_tier_rule_wait_multiply_level', ";
				$sql .= "'$queue_tier_rule_no_agent_no_wait', ";
				$sql .= "'$queue_timeout_action', ";
				$sql .= "'$queue_discard_abandoned_after', ";
				$sql .= "'$queue_abandoned_resume_allowed', ";
				$sql .= "'$queue_cid_prefix', ";
				if (strlen($queue_announce_sound) > 0) {
					$sql .= "'$queue_announce_sound', ";
				}
				if (strlen($queue_announce_frequency) > 0) {
					$sql .= "'$queue_announce_frequency', ";
				}
				$sql .= "'$queue_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//syncrhonize the configuration
				save_call_center_xml();

			//delete the dialplan context from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

			$_SESSION["message"] = $text['message-add'];
		} //if ($action == "add")

		if ($action == "update") {
			//update the call center queue
				$sql = "update v_call_center_queues set ";
				$sql .= "queue_name = '$queue_name', ";
				$sql .= "queue_extension = '$queue_extension', ";
				$sql .= "queue_strategy = '$queue_strategy', ";
				$sql .= "queue_moh_sound = '$queue_moh_sound', ";
				$sql .= "queue_record_template = '$queue_record_template', ";
				$sql .= "queue_time_base_score = '$queue_time_base_score', ";
				$sql .= "queue_max_wait_time = '$queue_max_wait_time', ";
				$sql .= "queue_max_wait_time_with_no_agent = '$queue_max_wait_time_with_no_agent', ";
				$sql .= "queue_max_wait_time_with_no_agent_time_reached = '$queue_max_wait_time_with_no_agent_time_reached', ";
				$sql .= "queue_tier_rules_apply = '$queue_tier_rules_apply', ";
				$sql .= "queue_tier_rule_wait_second = '$queue_tier_rule_wait_second', ";
				$sql .= "queue_tier_rule_wait_multiply_level = '$queue_tier_rule_wait_multiply_level', ";
				$sql .= "queue_tier_rule_no_agent_no_wait = '$queue_tier_rule_no_agent_no_wait', ";
				$sql .= "queue_timeout_action = '$queue_timeout_action', ";
				$sql .= "queue_discard_abandoned_after = '$queue_discard_abandoned_after', ";
				$sql .= "queue_abandoned_resume_allowed = '$queue_abandoned_resume_allowed', ";
				$sql .= "queue_cid_prefix = '$queue_cid_prefix', ";
				$sql .= "queue_announce_sound = '$queue_announce_sound', ";
				if (strlen($queue_announce_frequency) > 0) {
					$sql .= "queue_announce_frequency = '$queue_announce_frequency', ";
				}
				$sql .= "queue_description = '$queue_description' ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and call_center_queue_uuid = '$call_center_queue_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

			//get the dialplan_uuid
				$sql = "select * from v_call_center_queues ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and call_center_queue_uuid = '$call_center_queue_uuid' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$dialplan_uuid = $row["dialplan_uuid"];
				}
				unset ($prep_statement);

			//dialplan add or update
				$c = new call_center;
				$c->db = $db;
				$c->domain_uuid = $_SESSION['domain_uuid'];
				$c->call_center_queue_uuid = $call_center_queue_uuid;
				$c->dialplan_uuid = $dialplan_uuid;
				$c->queue_name = $queue_name;
				$c->queue_name = $queue_name;
				$c->queue_cid_prefix = $queue_cid_prefix;
				$c->queue_timeout_action = $queue_timeout_action;
				$c->queue_description = $queue_description;
				$c->destination_number = $queue_extension;
				$a = $c->dialplan();

			//synchronize the configuration
				save_call_center_xml();

			//clear the cache
				$cache = new cache;
				$cache->delete("memcache delete dialplan:".$_SESSION["context"]);

			//set the update message
				$_SESSION["message"] = $text['message-update'];
		} //if ($action == "update")

	//add agent/tier to queue
		$agent_name = check_str($_POST["agent_name"]);
		$tier_level = check_str($_POST["tier_level"]);
		$tier_position = check_str($_POST["tier_position"]);

		if ($agent_name != '') {
			//add the agent
				//setup the event socket connection
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				//add the agent using event socket
					if ($fp) {
						//get the domain using the $domain_uuid
						$tmp_domain = $_SESSION['domains'][$domain_uuid]['domain_name'];
						/* syntax:
							callcenter_config tier add [queue_name] [agent_name] [level] [position]
							callcenter_config tier set state [queue_name] [agent_name] [state]
							callcenter_config tier set level [queue_name] [agent_name] [level]
							callcenter_config tier set position [queue_name] [agent_name] [position]
						*/
						//add the agent
						$cmd = "api callcenter_config tier add ".$queue_name."@".$tmp_domain." ".$agent_name."@".$tmp_domain." ".$tier_level." ".$tier_position;
						$response = event_socket_request($fp, $cmd);
						usleep(200);
						//agent set level
						$cmd = "api callcenter_config tier set level ".$queue_name."@".$tmp_domain." ".$agent_name."@".$tmp_domain." ".$tier_level;
						$response = event_socket_request($fp, $cmd);
						usleep(200);
						//agent set position
						$cmd = "api callcenter_config tier set position ".$queue_name."@".$tmp_domain." ".$agent_name."@".$tmp_domain." ".$tier_position;
						$response = event_socket_request($fp, $cmd);
						usleep(200);
					}

			//add tier to database
				$call_center_tier_uuid = uuid();
				$sql = "insert into v_call_center_tiers ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "call_center_tier_uuid, ";
				$sql .= "agent_name, ";
				$sql .= "queue_name, ";
				$sql .= "tier_level, ";
				$sql .= "tier_position ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$_SESSION['domain_uuid']."', ";
				$sql .= "'$call_center_tier_uuid', ";
				$sql .= "'$agent_name', ";
				$sql .= "'$queue_name', ";
				$sql .= "'$tier_level', ";
				$sql .= "'$tier_position' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//syncrhonize configuration
				save_call_center_xml();
		}

		//redirect
			header("Location: call_center_queue_edit.php?id=".$call_center_queue_uuid);
			return;

	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$call_center_queue_uuid = $_GET["id"];
		$sql = "select * from v_call_center_queues ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and call_center_queue_uuid = '$call_center_queue_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$queue_name = $row["queue_name"];
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
			$queue_description = $row["queue_description"];
		}
		unset ($prep_statement);
	}

//replace the dash in the queue name with a space
	$queue_name = str_replace("-", " ", $queue_name);

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
		echo "  <input type='button' class='btn' value='".$text['button-stop']."' onclick=\"document.location.href='cmd.php?cmd=api+callcenter_config+queue+unload+".$database_queue_name."@".$_SESSION['domain_name']."';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-start']."' onclick=\"document.location.href='cmd.php?cmd=api+callcenter_config+queue+load+".$database_queue_name."@".$_SESSION['domain_name']."';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-restart']."' onclick=\"document.location.href='cmd.php?cmd=api+callcenter_config+queue+reload+".$database_queue_name."@".$_SESSION['domain_name']."';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-view']."' onclick=\"document.location.href='".PROJECT_PATH."/app/call_center_active/call_center_active.php?queue_name=".$database_queue_name."';\" />\n";
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
	echo "	<input class='formfld' type='text' name='queue_name' maxlength='255' value=\"$queue_name\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-queue_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='queue_extension' maxlength='255' min='0' step='1' value=\"$queue_extension\" required='required'>\n";
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

	if (permission_exists('call_center_tier_view')) {
		echo "<tr>";
		echo "	<td class='vncell' valign='top'>".$text['label-tiers']."</td>";
		echo "	<td class='vtable' align='left'>";
		echo "		<table width='45%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-agent_name']."</td>\n";
		echo "				<td class='vtable' style='text-align: center;'>".$text['label-tier_level']."</td>\n";
		echo "				<td class='vtable' style='text-align: center;'>".$text['label-tier_position']."</td>\n";
		echo "				<td></td>\n";
		echo "			</tr>\n";

		if ($call_center_queue_uuid != '') {
			//replace the space in the queue name with a dash
			$db_queue_name = str_replace(" ", "-", $queue_name);

			$sql = "select * from v_call_center_tiers ";
			$sql .= "where queue_name = '".$db_queue_name."' ";
			$sql .= "and domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "order by tier_level asc, tier_position asc, agent_name asc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $field) {
				echo "	<tr>\n";
				echo "		<td class='vtable'>".$field['agent_name']."</td>\n";
				echo "		<td class='vtable' style='text-align: center;'>".$field['tier_level']."&nbsp;</td>\n";
				echo "		<td class='vtable' style='text-align: center;'>".$field['tier_position']."&nbsp;</td>\n";
				echo "		<td class='list_control_icons'>";
				if (permission_exists('call_center_tier_edit')) {
					echo		"<a href='call_center_tier_edit.php?id=".$field['call_center_tier_uuid']."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
				}
				if (permission_exists('call_center_tier_delete')) {
					echo 		"<a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('delete_type').value = 'tier'; document.getElementById('delete_uuid').value = '".$field['call_center_tier_uuid']."'; document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
				}
				echo "		</td>\n";
				echo "	</tr>\n";
				$assigned_agents[] = $field['agent_name'];
			}
			unset ($prep_statement, $sql, $result);
		}

		if (permission_exists('call_center_tier_add')) {
			//get agents
			$sql = "select agent_name from v_call_center_agents where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			foreach($assigned_agents as $assigned_agent) {
				$sql .= "and agent_name <> '".$assigned_agent."' ";
			}
			$sql .= "order by agent_name asc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			if (sizeof($result) > 0) {
				echo "		<tr>\n";
				echo "			<td class='vtable'>\n";
				echo "<select id='agent_name' name='agent_name' class='formfld'>\n";
				echo "					<option value=''></option>\n";
				foreach($result as $field) {
					echo "				<option value='".$field['agent_name']."'>".$field['agent_name']."</option>\n";
				}
				unset($sql,$prep_statement);
				echo "				</select>";
				echo "			</td>\n";
				echo "			<td class='vtable' style='text-align: center;'>\n";
				echo "				<select class='formfld' name='tier_level'>\n";
				for ($t = 0; $t <= 9; $t++) {
					echo "				<option value='".$t."'>".$t."</option>\n";
				}
				echo "				</select>\n";
				echo "			</td>\n";
				echo "			<td class='vtable' style='text-align: center;'>\n";
				echo "				<select class='formfld' name='tier_position'>\n";
				for ($t = 1; $t <= 9; $t++) {
					echo "				<option value='".$t."'>".$t."</option>\n";
				}
				echo "				</select>\n";
				echo "			</td>\n";
				echo "			<td>";
				echo "				<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
				echo "			</td>\n";
				echo "		</tr>\n";
			}
		}

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

	$select_options = "";
	if ($queue_moh_sound == "tone_stream://\$\${us-ring};loops=-1" || $queue_moh_sound == "us-ring") {
		$select_options .= "            <option value='tone_stream://\$\${us-ring};loops=-1' selected='selected'>".$text['option-usring']."</option>\n";
	}
	else {
		$select_options .= "            <option value='tone_stream://\$\${us-ring};loops=-1'>".$text['option-usring']."</option>\n";
	}
	if ($queue_moh_sound == "tone_stream://\$\${pt-ring};loops=-1" || $queue_moh_sound == "pt-ring") {
		$select_options .= "            <option value='tone_stream://\$\${pt-ring};loops=-1' selected='selected'>".$text['option-ptring']."</option>\n";
	}
	else {
		$select_options .= "            <option value='tone_stream://\$\${pt-ring};loops=-1'>".$text['option-ptring']."</option>\n";
	}
	if ($queue_moh_sound == "tone_stream://\$\${fr-ring};loops=-1" || $queue_moh_sound == "fr-ring") {
		$select_options .= "            <option value='tone_stream://\$\${fr-ring};loops=-1' selected='selected'>".$text['option-frring']."</option>\n";
	}
	else {
		$select_options .= "            <option value='tone_stream://\$\${fr-ring};loops=-1'>".$text['option-frring']."</option>\n";
	}
	if ($queue_moh_sound == "tone_stream://\$\${uk-ring};loops=-1" || $queue_moh_sound == "uk-ring") {
		$select_options .= "            <option value='tone_stream://\$\${uk-ring};loops=-1' selected='selected'>".$text['option-ukring']."</option>\n";
	}
	else {
		$select_options .= "            <option value='tone_stream://\$\${uk-ring};loops=-1'>".$text['option-ukring']."</option>\n";
	}
	if ($queue_moh_sound == "tone_stream://\$\${rs-ring};loops=-1" || $queue_moh_sound == "rs-ring") {
		$select_options .= "            <option value='tone_stream://\$\${rs-ring};loops=-1' selected='selected'>".$text['option-rsring']."</option>\n";
	}
	else {
		$select_options .= "            <option value='tone_stream://\$\${rs-ring};loops=-1'>".$text['option-rsring']."</option>\n";
	}

	if ($queue_moh_sound == "tone_stream://\$\${it-ring};loops=-1" || $queue_moh_sound == "it-ring") {
		$select_options .= "            <option value='tone_stream://\$\${it-ring};loops=-1' selected='selected'>".$text['option-itring']."</option>\n";
	}
	else {
		$select_options .= "            <option value='tone_stream://\$\${it-ring};loops=-1'>".$text['option-itring']."</option>\n";
	}
	require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
	$moh= new switch_music_on_hold;
	$moh->select_name = "queue_moh_sound";
	$moh->select_value = $queue_moh_sound;
	$moh->select_options = $select_options;
	echo $moh->select();

	echo "<br />\n";
	echo $text['description-music_on_hold']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-record_template']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$record_ext=($_SESSION['record_ext']=='mp3'?'mp3':'wav');
	$record_template = $_SESSION['switch']['recordings']['dir']."/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}/\${uuid}.".$record_ext;
	echo "	<select class='formfld' name='queue_record_template'>\n";
	if (strlen($queue_record_template) > 0) {
		echo "	<option value='$record_template' selected='selected' >".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='$record_template'>".$text['option-true']."</option>\n";
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
	echo "  <input class='formfld' type='number' name='queue_max_wait_time' maxlength='255' min='0' step='1' value='$queue_max_wait_time'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-max_wait_time_with_no_agent']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_max_wait_time_with_no_agent' maxlength='255' min='0' step='1' value='$queue_max_wait_time_with_no_agent'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time_with_no_agent']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-max_wait_time_with_no_agent_time_reached']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_max_wait_time_with_no_agent_time_reached' maxlength='255' min='0' step='1' value='$queue_max_wait_time_with_no_agent_time_reached'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time_with_no_agent_time_reached']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-timeout_action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'queue_timeout_action', $queue_timeout_action);
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
	echo "  <input class='formfld' type='number' name='queue_tier_rule_wait_second' maxlength='255' min='0' step='1' value='$queue_tier_rule_wait_second'>\n";
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
	echo "  <input class='formfld' type='number' name='queue_discard_abandoned_after' maxlength='255' min='0' step='1' value='$queue_discard_abandoned_after'>\n";
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
	echo "  <input class='formfld' type='text' name='queue_cid_prefix' maxlength='255' value='$queue_cid_prefix'>\n";
	echo "<br />\n";
	echo $text['description-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "  ".$text['label-caller_announce_sound']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_announce_sound' maxlength='255' value='$queue_announce_sound'>\n";
	echo "<br />\n";
	echo $text['description-caller_announce_sound']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "  ".$text['label-caller_announce_frequency']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_announce_frequency' maxlength='255' min='0' step='1' value='$queue_announce_frequency'>\n";
	echo "<br />\n";
	echo $text['description-caller_announce_frequency']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='queue_description' maxlength='255' value=\"$queue_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='call_center_queue_uuid' value='".$call_center_queue_uuid."'>\n";
		echo "		<input type='hidden' name='id' id='id' value='".$call_center_queue_uuid."'>";
		echo "		<input type='hidden' name='delete_type' id='delete_type' value=''>";
		echo "		<input type='hidden' name='delete_uuid' id='delete_uuid' value=''>";
	}
	echo "			<br />";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

require_once "resources/footer.php";

?>