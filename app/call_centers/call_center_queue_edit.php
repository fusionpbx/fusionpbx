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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!(permission_exists('call_center_queue_add') || permission_exists('call_center_queue_edit'))) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//initialize settings object
	$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid'] ?? '', 'user_uuid' => $_SESSION['user_uuid'] ?? '']);

//set the defaults
	$queue_name = '';
	$queue_extension = '';
	$queue_time_base_score_sec = '';
	$queue_cid_prefix = '';
	$queue_announce_frequency = '';
	$queue_cc_exit_keys = '';
	$queue_description = '';
	$queue_timeout_action = '';

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$call_center_queue_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the domain details
	$domain_uuid = $_SESSION['domain_uuid'];
	$domain_name = $_SESSION['domain_name'];

//initialize the destination object
	$destination = new destinations;

//get installed languages
	$language_paths = glob($settings->get('switch', 'sounds')."/*/*/*");
	foreach ($language_paths as $key => $path) {
		$path = str_replace($settings->get('switch', 'sounds').'/', "", $path);
		$path_array = explode('/', $path);
		if (count($path_array) <> 3 || strlen($path_array[0]) <> 2 || strlen($path_array[1]) <> 2) {
			unset($language_paths[$key]);
		}
		$language_paths[$key] = str_replace($settings->get('switch', 'sounds')."/","",$language_paths[$key] ?? '');
		if (empty($language_paths[$key])) {
			unset($language_paths[$key]);
		}
	}

//get total call center queues count from the database, check limit, if defined
	if ($action == 'add' && $settings->get('limit','call_center_queues') != '') {
		$sql = "select count(*) from v_call_center_queues ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$total_call_center_queues = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);

		if ($total_call_center_queues >= $settings->get('limit','call_center_queues', 0)) {
			message::add($text['message-maximum_queues'].' '.$settings->get('limit','call_center_queues', ''), 'negative');
			header('Location: call_center_queues.php');
			return;
		}
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		//get the post variables a run a security chack on them
			//$domain_uuid = $_POST["domain_uuid"];
			$dialplan_uuid = $_POST["dialplan_uuid"] ?? null;
			$queue_name = $_POST["queue_name"];
			$queue_extension = $_POST["queue_extension"];
			$queue_greeting = $_POST["queue_greeting"];
			$queue_language = $_POST["queue_language"];
			$queue_strategy = $_POST["queue_strategy"];
			$call_center_tiers = $_POST["call_center_tiers"];
			$queue_moh_sound = $_POST["queue_moh_sound"];
			$queue_record_enabled = $_POST["queue_record_enabled"];
			$queue_limit = $_POST["queue_limit"];
			$queue_time_base_score = $_POST["queue_time_base_score"];
			$queue_time_base_score_sec = $_POST["queue_time_base_score_sec"];
			$queue_max_wait_time = $_POST["queue_max_wait_time"];
			$queue_max_wait_time_with_no_agent = $_POST["queue_max_wait_time_with_no_agent"];
			$queue_max_wait_time_with_no_agent_time_reached = $_POST["queue_max_wait_time_with_no_agent_time_reached"];
			$queue_tier_rules_apply = $_POST["queue_tier_rules_apply"];
			$queue_tier_rule_wait_second = $_POST["queue_tier_rule_wait_second"];
			$queue_tier_rule_wait_multiply_level = $_POST["queue_tier_rule_wait_multiply_level"];
			$queue_tier_rule_no_agent_no_wait = $_POST["queue_tier_rule_no_agent_no_wait"];
			$queue_timeout_action = $_POST["queue_timeout_action"] ?? null;
			$queue_discard_abandoned_after = $_POST["queue_discard_abandoned_after"];
			$queue_abandoned_resume_allowed = $_POST["queue_abandoned_resume_allowed"];
			$queue_cid_prefix = $_POST["queue_cid_prefix"];
			$queue_outbound_caller_id_name = $_POST["queue_outbound_caller_id_name"] ?? null;
			$queue_outbound_caller_id_number = $_POST["queue_outbound_caller_id_number"] ?? null;
			$queue_announce_position = $_POST["queue_announce_position"] ?? null;
			$queue_announce_sound = $_POST["queue_announce_sound"];
			$queue_announce_frequency = $_POST["queue_announce_frequency"];
			$queue_cc_exit_keys = $_POST["queue_cc_exit_keys"] ?? null;
			$queue_email_address = $_POST["queue_email_address"] ?? null;
			$queue_description = $_POST["queue_description"];
			$call_center_tier_delete = $_POST["call_center_tier_delete"] ?? null;

		//set the context for users that do not have the permission
			if (permission_exists('call_center_queue_context')) {
				$queue_context = $_POST["queue_context"];
			}
			else if ($action == 'add') {
				$queue_context = $domain_name;
			}

		//seperate the language components into language, dialect and voice
			$language_array = explode("/",$queue_language);
			$queue_language = $language_array[0] ?? '';
			$queue_dialect = $language_array[1] ?? '';
			$queue_voice = $language_array[2] ?? '';

		//remove invalid characters
			$queue_cid_prefix = str_replace(":", "-", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("\"", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("@", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("\\", "", $queue_cid_prefix);
			$queue_cid_prefix = str_replace("/", "", $queue_cid_prefix);
	}

//delete the tier (agent from the queue)
	if (!empty($_REQUEST["a"]) && $_REQUEST["a"] == "delete" && is_uuid($_REQUEST["id"]) && permission_exists("call_center_tier_delete")) {
		//set the variables
			$call_center_queue_uuid = $_REQUEST["id"];
			$call_center_tier_uuid = $_REQUEST["call_center_tier_uuid"];

		//get the agent details
			$sql = "select t.call_center_agent_uuid, t.call_center_queue_uuid, q.queue_extension  ";
			$sql .= "from v_call_center_tiers as t, v_call_center_queues as q ";
			$sql .= "where t.domain_uuid = :domain_uuid  ";
			$sql .= "and t.call_center_tier_uuid = :call_center_tier_uuid ";
			$sql .= "and t.call_center_queue_uuid = q.call_center_queue_uuid; ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['call_center_tier_uuid'] = $call_center_tier_uuid;
			$tiers = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters);

			if (!empty($tiers)) {
				foreach ($tiers as $row) {
					$call_center_agent_uuid = $row["call_center_agent_uuid"];
					$call_center_queue_uuid = $row["call_center_queue_uuid"];
					$queue_extension = $row["queue_extension"];
				}
			}

		//delete the agent from freeswitch
			//setup the event socket connection
			$esl = event_socket::create();
			//delete the agent over event socket
			if ($esl->is_connected()) {
				//callcenter_config tier del [queue_name] [agent_name]
				if (is_numeric($queue_extension) && is_uuid($call_center_agent_uuid)) {
					$cmd = "callcenter_config tier del ".$queue_extension."@".$domain_name." ".$call_center_agent_uuid;
					$response = event_socket::api($cmd);
				}
			}

		//delete the tier from the database
			if (!empty($call_center_tier_uuid)) {
				$array['call_center_tiers'][0]['call_center_tier_uuid'] = $call_center_tier_uuid;
				$array['call_center_tiers'][0]['domain_uuid'] = $domain_uuid;

				$p = permissions::new();
				$p->add('call_center_tier_delete', 'temp');

				$database->delete($array);
				unset($array);

				$p->delete('call_center_tier_delete', 'temp');
			}
	}

//process the user data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//get the uuid from the POST
			if ($action == "update") {
				$call_center_queue_uuid = $_POST["call_center_queue_uuid"];
			}

		//if the user doesn't have the correct permission then
			//override domain_uuid and queue_context values
			if ($action == 'update' && is_uuid($call_center_queue_uuid)) {
				$sql = "select * from v_call_center_queues ";
				$sql .= "where call_center_queue_uuid = :call_center_queue_uuid ";
				$parameters['call_center_queue_uuid'] = $call_center_queue_uuid;
				$row = $database->select($sql, $parameters, 'row');
				if (!empty($row)) {
					//if (!permission_exists('call_center_queue_domain')) {
					//	$domain_uuid = $row["domain_uuid"];
					//}
					if (!permission_exists('call_center_queue_context')) {
						$queue_context = $row["queue_context"];
					}
				}
				unset($sql, $parameters, $row);
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: call_center_queues.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (empty($domain_uuid)) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
			if (empty($queue_name)) { $msg .= $text['message-required'].$text['label-queue_name']."<br>\n"; }
			if (empty($queue_extension)) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			if (empty($queue_strategy)) { $msg .= $text['message-required'].$text['label-strategy']."<br>\n"; }
			//if (empty($queue_moh_sound)) { $msg .= $text['message-required'].$text['label-music_on_hold']."<br>\n"; }
			//if (empty($queue_record_enabled)) { $msg .= $text['message-required'].$text['label-record_template']."<br>\n"; }
			//if (empty($queue_time_base_score)) { $msg .= $text['message-required'].$text['label-time_base_score']."<br>\n"; }
			//if (empty($queue_time_base_score_sec)) { $msg .= $text['message-required'].$text['label-time_base_score_sec']."<br>\n"; }
			//if (empty($queue_max_wait_time)) { $msg .= $text['message-required'].$text['label-max_wait_time']."<br>\n"; }
			//if (empty($queue_max_wait_time_with_no_agent)) { $msg .= $text['message-required'].$text['label-max_wait_time_with_no_agent']."<br>\n"; }
			//if (empty($queue_max_wait_time_with_no_agent_time_reached)) { $msg .= $text['message-required'].$text['label-max_wait_time_with_no_agent_time_reached']."<br>\n"; }
			//if (empty($queue_tier_rules_apply)) { $msg .= $text['message-required'].$text['label-tier_rules_apply']."<br>\n"; }
			//if (empty($queue_tier_rule_wait_second)) { $msg .= $text['message-required'].$text['label-tier_rule_wait_second']."<br>\n"; }
			//if (empty($queue_tier_rule_wait_multiply_level)) { $msg .= $text['message-required'].$text['label-tier_rule_wait_multiply_level']."<br>\n"; }
			//if (empty($queue_tier_rule_no_agent_no_wait)) { $msg .= $text['message-required'].$text['label-tier_rule_no_agent_no_wait']."<br>\n"; }
			//if (empty($queue_timeout_action)) { $msg .= $text['message-required'].$text['label-timeout_action']."<br>\n"; }
			//if (empty($queue_discard_abandoned_after)) { $msg .= $text['message-required'].$text['label-discard_abandoned_after']."<br>\n"; }
			//if (empty($queue_abandoned_resume_allowed)) { $msg .= $text['message-required'].$text['label-abandoned_resume_allowed']."<br>\n"; }
			//if (empty($queue_cid_prefix)) { $msg .= $text['message-required'].$text['label-caller_id_name_prefix']."<br>\n"; }
			//if (empty($queue_description)) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//add the call_center_queue_uuid
			if (empty($_POST["call_center_queue_uuid"])) {
				$call_center_queue_uuid = uuid();
				$_POST["call_center_queue_uuid"] = $call_center_queue_uuid;
			}

		//add the dialplan_uuid
			if (empty($_POST["dialplan_uuid"])) {
				$dialplan_uuid = uuid();
				$_POST["dialplan_uuid"] = $dialplan_uuid;
			}

		//update the call centier tiers array
			$x = 0;
			if (!empty($_POST["call_center_tiers"])) {
				foreach ($_POST["call_center_tiers"] as $row) {
					//add the domain_uuid
						if (empty($row["domain_uuid"])) {
							$_POST["call_center_tiers"][$x]["domain_uuid"] = $domain_uuid;
						}
					//unset ring_group_destination_uuid if the field has no value
						if (empty($row["call_center_agent_uuid"])) {
							unset($_POST["call_center_tiers"][$x]);
						}
					//increment the row
						$x++;
				}
			}

		//get the application and data
			$action_array = explode(":",$queue_timeout_action);
			$queue_timeout_app = $action_array[0];
			unset($action_array[0]);
			$queue_timeout_data = implode($action_array);

		//add the recording path if needed
			if (!empty($queue_greeting)) {
				if (file_exists($settings->get('switch','recordings', '').'/'.$domain_name.'/'.$queue_greeting)) {
					$queue_greeting_path = $settings->get('switch','recordings', '').'/'.$domain_name.'/'.$queue_greeting;
				}
				else {
					$queue_greeting_path = trim($queue_greeting);
				}
			}

		//set the the record_template for mod call center
			if ($queue_record_enabled == 'true') {
				$record_template = $settings->get('switch','recordings', '')."/".$domain_name."/archive/";
				$record_template .= $settings->get('call_center','record_name', "\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}/\${uuid}.\${record_ext}");
			}
			else {
				$record_template = '';
			}

		//prepare the array
			$array['call_center_queues'][0]['queue_name'] = $queue_name;
			$array['call_center_queues'][0]['queue_extension'] = $queue_extension;
			$array['call_center_queues'][0]['queue_greeting'] = $queue_greeting;
			$array['call_center_queues'][0]['queue_language'] = $queue_language;
			$array['call_center_queues'][0]['queue_strategy'] = $queue_strategy;
			$array['call_center_queues'][0]['queue_moh_sound'] = $queue_moh_sound;
			$array['call_center_queues'][0]['queue_record_template'] = $record_template;
			$array['call_center_queues'][0]['queue_dialect'] = $queue_dialect;
			$array['call_center_queues'][0]['queue_voice'] = $queue_voice;
			$array['call_center_queues'][0]['queue_limit'] = $queue_limit;
			$array['call_center_queues'][0]['queue_time_base_score'] = $queue_time_base_score;
			$array['call_center_queues'][0]['queue_time_base_score_sec'] = $queue_time_base_score_sec;
			$array['call_center_queues'][0]['queue_max_wait_time'] = $queue_max_wait_time;
			$array['call_center_queues'][0]['queue_max_wait_time_with_no_agent'] = $queue_max_wait_time_with_no_agent;
			$array['call_center_queues'][0]['queue_max_wait_time_with_no_agent_time_reached'] = $queue_max_wait_time_with_no_agent_time_reached;
			if ($destination->valid($queue_timeout_action)) {
				$array['call_center_queues'][0]['queue_timeout_action'] = $queue_timeout_action;
			}
			$array['call_center_queues'][0]['queue_tier_rules_apply'] = $queue_tier_rules_apply;
			$array['call_center_queues'][0]['queue_tier_rule_wait_second'] = $queue_tier_rule_wait_second;
			$array['call_center_queues'][0]['queue_tier_rule_wait_multiply_level'] = $queue_tier_rule_wait_multiply_level;
			$array['call_center_queues'][0]['queue_tier_rule_no_agent_no_wait'] = $queue_tier_rule_no_agent_no_wait;
			$array['call_center_queues'][0]['queue_discard_abandoned_after'] = $queue_discard_abandoned_after;
			$array['call_center_queues'][0]['queue_abandoned_resume_allowed'] = $queue_abandoned_resume_allowed;
			$array['call_center_queues'][0]['queue_cid_prefix'] = $queue_cid_prefix;
			if (permission_exists('call_center_outbound_caller_id_name')) {
				$array['call_center_queues'][0]['queue_outbound_caller_id_name'] = $queue_outbound_caller_id_name;
			}
			if (permission_exists('call_center_outbound_caller_id_number')) {
				$array['call_center_queues'][0]['queue_outbound_caller_id_number'] = $queue_outbound_caller_id_number;
			}
			$array['call_center_queues'][0]['queue_announce_position'] = $queue_announce_position;
			if (permission_exists('call_center_announce_sound')) {
				$array['call_center_queues'][0]['queue_announce_sound'] = $queue_announce_sound;
			}
			if (permission_exists('call_center_announce_frequency')) {
				$array['call_center_queues'][0]['queue_announce_frequency'] = $queue_announce_frequency;
			}
			$array['call_center_queues'][0]['queue_cc_exit_keys'] = $queue_cc_exit_keys;
			if (permission_exists('call_center_email_address')) {
				$array['call_center_queues'][0]['queue_email_address'] = $queue_email_address;
			}
			$array['call_center_queues'][0]['queue_context'] = $queue_context;
			$array['call_center_queues'][0]['queue_description'] = $queue_description;
			$array['call_center_queues'][0]['call_center_queue_uuid'] = $call_center_queue_uuid;
			$array['call_center_queues'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['call_center_queues'][0]['domain_uuid'] = $domain_uuid;

			$y = 0;
			if (!empty($_POST["call_center_tiers"])) {
				foreach ($_POST["call_center_tiers"] as $row) {
					if (is_uuid($row['call_center_tier_uuid'])) {
						$call_center_tier_uuid = $row['call_center_tier_uuid'];
					}
					else {
						$call_center_tier_uuid = uuid();
					}
					if (!empty($row['call_center_agent_uuid'])) {
						$array["call_center_queues"][0]["call_center_tiers"][$y]["call_center_tier_uuid"] = $call_center_tier_uuid;
						$array['call_center_queues'][0]["call_center_tiers"][$y]["call_center_agent_uuid"] = $row['call_center_agent_uuid'];
						$array['call_center_queues'][0]["call_center_tiers"][$y]["tier_level"] = $row['tier_level'];
						$array['call_center_queues'][0]["call_center_tiers"][$y]["tier_position"] = $row['tier_position'];
						$array['call_center_queues'][0]["call_center_tiers"][$y]["domain_uuid"] = $domain_uuid;
					}
					$y++;
				}
			}

		//add definable export variables can be set in default settings
			$export_variables = 'call_center_queue_uuid,sip_h_Alert-Info';
			if (!empty($settings->get('call_center','export_vars', []))) {
				foreach ($settings->get('call_center','export_vars', []) as $export_variable) {
					$export_variables .= ','.$export_variable;
				}
			}

		//build the xml dialplan
			$dialplan_xml = "<extension name=\"".xml::sanitize($queue_name)."\" continue=\"\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
			if (!empty($queue_limit)) {
				$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^(callcenter\+)?".xml::sanitize($queue_extension)."$\" break=\"on-false\">\n";
				$dialplan_xml .= "		<action application=\"limit\" data=\"hash inbound \${destination_number} ".xml::sanitize($queue_limit)." !NORMAL_CIRCUIT_CONGESTION\"/>\n";
				$dialplan_xml .= "	</condition>\n";
			}
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^([^#]+#)(.*)\$\" break=\"never\">\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_name=\$2\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^(callcenter\+)?".xml::sanitize($queue_extension)."$\">\n";
			$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
			if (!empty($call_center_queue_uuid) && is_uuid($call_center_queue_uuid)) {
				$dialplan_xml .= "		<action application=\"set\" data=\"call_center_queue_uuid=".xml::sanitize($call_center_queue_uuid)."\"/>\n";
			}
			if (!empty($queue_extension) && is_numeric($queue_extension)) {
				$dialplan_xml .= "		<action application=\"set\" data=\"queue_extension=".xml::sanitize($queue_extension)."\"/>\n";
			}
			$dialplan_xml .= "		<action application=\"set\" data=\"cc_export_vars=\${cc_export_vars},".$export_variables."\"/>\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
			if (!empty($queue_time_base_score_sec)) {
				$dialplan_xml .= "		<action application=\"set\" data=\"cc_base_score=".xml::sanitize($queue_time_base_score_sec)."\"/>\n";
			}
			if (!empty($queue_greeting_path)) {
				$dialplan_xml .= "		<action application=\"sleep\" data=\"1000\"/>\n";
				$greeting_array = explode(':', $queue_greeting_path);
				if (count($greeting_array) == 1) {
					$dialplan_xml .= "		<action application=\"playback\" data=\"".xml::sanitize($queue_greeting_path)."\"/>\n";
				}
				else {
					if ($greeting_array[0] == 'say' || $greeting_array[0] == 'tone_stream' || $greeting_array[0] == 'phrase') {
						$dialplan_xml .= "		<action application=\"".xml::sanitize($greeting_array[0])."\" data=\"".xml::sanitize($greeting_array[1])."\"/>\n";
					}
				}
			}
			if (!empty($queue_cid_prefix)) {
				$dialplan_xml .= "		<action application=\"set\" data=\"effective_caller_id_name=".xml::sanitize($queue_cid_prefix)."#\${caller_id_name}\"/>\n";
			}
			if ($queue_cc_exit_keys !== null) {
				$dialplan_xml .= "		<action application=\"set\" data=\"cc_exit_keys=".xml::sanitize($queue_cc_exit_keys)."\"/>\n";
			}
			$dialplan_xml .= "		<action application=\"callcenter\" data=\"".xml::sanitize($queue_extension)."@".$domain_name."\"/>\n";
			if ($destination->valid($queue_timeout_app.':'.$queue_timeout_data)) {
				$dialplan_xml .= "		<action application=\"".xml::sanitize($queue_timeout_app)."\" data=\"".xml::sanitize($queue_timeout_data)."\"/>\n";
			}
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//build the dialplan array
			$array['dialplans'][0]["domain_uuid"] = $domain_uuid;
			$array['dialplans'][0]["dialplan_uuid"] = $dialplan_uuid;
			$array['dialplans'][0]["dialplan_name"] = $queue_name;
			$array['dialplans'][0]["dialplan_number"] = $queue_extension;
			$array['dialplans'][0]["dialplan_context"] = $queue_context;
			$array['dialplans'][0]["dialplan_continue"] = "false";
			$array['dialplans'][0]["dialplan_xml"] = $dialplan_xml;
			$array['dialplans'][0]["dialplan_order"] = "230";
			$array['dialplans'][0]["dialplan_enabled"] = true;
			$array['dialplans'][0]["dialplan_description"] = $queue_description;
			$array['dialplans'][0]["app_uuid"] = "95788e50-9500-079e-2807-fd530b0ea370";

		//add the dialplan permission
			$p = permissions::new();
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_edit", "temp");

		//save to the data
			$database->save($array);
			$message = $database->message;

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");

		//remove checked options
			if ($action == 'update' && permission_exists('call_center_tier_delete') && !empty($call_center_tier_delete)) {
				$obj = new call_center;
				$obj->delete_tiers($call_center_tier_delete);
			}

		//debug info
			//echo "<pre>". print_r($message, true) ."</pre>"; exit;

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$domain_name);

		//clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}
			}

		//synchronize the configuration
			save_call_center_xml();
			remove_config_from_cache('configuration:callcenter.conf');

		//add agent/tier to queue
			$agent_name = $_POST["agent_name"] ?? null;
			$tier_level = $_POST["tier_level"] ?? null;
			$tier_position = $_POST["tier_position"] ?? null;

			if (!empty($agent_name)) {
				//setup the event socket connection
					$esl = event_socket::create();
				//add the agent using event socket
					if ($esl->is_connected()) {
						/* syntax:
							callcenter_config tier add [queue_name] [agent_name] [level] [position]
							callcenter_config tier set state [queue_name] [agent_name] [state]
							callcenter_config tier set level [queue_name] [agent_name] [level]
							callcenter_config tier set position [queue_name] [agent_name] [position]
						*/
						//add the agent
						if (is_numeric($queue_extension) && is_uuid($call_center_agent_uuid) && is_numeric($tier_level) && is_numeric($tier_position)) {
							$cmd = "callcenter_config tier add ".$queue_extension."@".$domain_name." ".$call_center_agent_uuid." ".$tier_level." ".$tier_position;
							$response = event_socket::api($cmd);
						}
						usleep(200);
						//agent set level
						if (is_numeric($queue_extension) && is_numeric($tier_level)) {
							$cmd = "callcenter_config tier set level ".$queue_extension."@".$domain_name." ".$call_center_agent_uuid." ".$tier_level;
							$response = event_socket::api($cmd);
						}
						usleep(200);
						//agent set position
						if (is_numeric($queue_extension) && is_numeric($tier_position)) {
							$cmd = "callcenter_config tier set position ".$queue_extension."@".$domain_name." ".$tier_position;
							$response = event_socket::api($cmd);
						}
						usleep(200);
					}
			}

		//syncrhonize configuration
			save_call_center_xml();

		//clear the cache
			$cache = new cache;
			$cache->delete('configuration:callcenter.conf');

		//redirect the user
			if (is_uuid($call_center_queue_uuid)) {
				header("Location: call_center_queue_edit.php?id=".urlencode($call_center_queue_uuid));
			}
			return;

	} //(count($_POST)>0 && empty($_POST["persistformvar"]))

//pre-populate the form
	if (!empty($_GET) && is_uuid($_GET["id"]) && empty($_POST["persistformvar"])) {
		$call_center_queue_uuid = $_GET["id"];
		$sql = "select ";
		$sql .= "queue_name, ";
		$sql .= "dialplan_uuid, ";
		$sql .= "queue_extension, ";
		$sql .= "queue_greeting, ";
		$sql .= "queue_language, ";
		$sql .= "queue_dialect, ";
		$sql .= "queue_voice , ";
		$sql .= "queue_strategy, ";
		$sql .= "queue_moh_sound, ";
		$sql .= "queue_record_template, ";
		$sql .= "queue_limit, ";
		$sql .= "queue_time_base_score, ";
		$sql .= "queue_time_base_score_sec, ";
		$sql .= "queue_max_wait_time, ";
		$sql .= "queue_max_wait_time_with_no_agent, ";
		$sql .= "queue_max_wait_time_with_no_agent_time_reached, ";
		$sql .= "queue_timeout_action, ";
		$sql .= "queue_tier_rules_apply, ";
		$sql .= "queue_tier_rule_wait_second, ";
		$sql .= "queue_tier_rule_wait_multiply_level, ";
		$sql .= "queue_tier_rule_no_agent_no_wait, ";
		$sql .= "queue_discard_abandoned_after, ";
		$sql .= "queue_abandoned_resume_allowed, ";
		$sql .= "queue_cid_prefix, ";
		$sql .= "queue_outbound_caller_id_name, ";
		$sql .= "queue_outbound_caller_id_number, ";
		$sql .= "queue_announce_position, ";
		$sql .= "queue_announce_sound, ";
		$sql .= "queue_announce_frequency, ";
		$sql .= "queue_cc_exit_keys, ";
		$sql .= "queue_email_address, ";
		$sql .= "queue_context, ";
		$sql .= "queue_description ";
		$sql .= "from v_call_center_queues ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and call_center_queue_uuid = :call_center_queue_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['call_center_queue_uuid'] = $call_center_queue_uuid;
		$call_center_queues = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);

		if (!empty($call_center_queues)) {
			foreach ($call_center_queues as $row) {
				$queue_name = $row["queue_name"];
				$dialplan_uuid = $row["dialplan_uuid"];
				$database_queue_name = $row["queue_name"];
				$queue_extension = $row["queue_extension"];
				$queue_greeting = $row["queue_greeting"];
				$queue_language = $row["queue_language"];
				$queue_dialect = $row["queue_dialect"];
				$queue_voice = $row["queue_voice"];
				$queue_strategy = $row["queue_strategy"];
				$queue_moh_sound = $row["queue_moh_sound"];
				$queue_record_template = $row["queue_record_template"];
				$queue_limit = $row["queue_limit"];
				$queue_time_base_score = $row["queue_time_base_score"];
				$queue_time_base_score_sec = $row["queue_time_base_score_sec"];
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
				$queue_outbound_caller_id_name = $row["queue_outbound_caller_id_name"];
				$queue_outbound_caller_id_number = $row["queue_outbound_caller_id_number"];
				$queue_announce_position = $row["queue_announce_position"];
				$queue_announce_sound = $row["queue_announce_sound"];
				$queue_announce_frequency = $row["queue_announce_frequency"];
				$queue_cc_exit_keys = $row["queue_cc_exit_keys"];
				$queue_email_address = $row["queue_email_address"];
				$queue_context = $row["queue_context"];
				$queue_description = $row["queue_description"];
			}
		}
	}

//get the tiers
	$sql = "select t.call_center_tier_uuid, t.call_center_agent_uuid, t.call_center_queue_uuid, t.tier_level, t.tier_position, a.agent_name ";
	$sql .= "from v_call_center_tiers as t, v_call_center_agents as a ";
	$sql .= "where t.call_center_queue_uuid = :call_center_queue_uuid ";
	$sql .= "and t.call_center_agent_uuid = a.call_center_agent_uuid ";
	$sql .= "and t.domain_uuid = :domain_uuid ";
	$sql .= "order by tier_level asc, tier_position asc, a.agent_name asc";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['call_center_queue_uuid'] = $call_center_queue_uuid ?? null;
	$tiers = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row to the tiers array
	if (count($tiers) == 0) {
		$rows = $settings->get('call_center','agent_add_rows', null);
		$id = 0;
	}
	if (count($tiers) > 0) {
		$rows = $settings->get('call_center','agent_edit_rows', null);
		$id = count($tiers)+1;
	}
	for ($x = 0; $x < $rows; $x++) {
		$tiers[$id]['call_center_tier_uuid'] = uuid();
		$tiers[$id]['call_center_agent_uuid'] = '';
		$tiers[$id]['call_center_queue_uuid'] = $call_center_queue_uuid ?? null;
		$tiers[$id]['tier_level'] = '';
		$tiers[$id]['tier_position'] = '';
		$tiers[$id]['agent_name'] = '';
		$id++;
	}

//get the agents
	$sql = "select call_center_agent_uuid, agent_name from v_call_center_agents ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by agent_name asc";
	$parameters['domain_uuid'] = $domain_uuid;
	$agents = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the sounds
	$sounds = new sounds;
	$audio_files[0] = $sounds->get();
	unset($sounds);

//get the list of sounds
	if (permission_exists('call_center_announce_sound')) {
		$sounds = new sounds;
		$sounds->sound_types = ['recordings'];
		$sounds->full_path = ['recordings'];
		$audio_files[1] = $sounds->get();
		unset($sounds);
	}

//set default values
	$queue_greeting = $queue_greeting ?? '';
	$queue_strategy = $queue_strategy ?? "longest-idle-agent";
	$queue_moh_sound = $queue_moh_sound ?? "\$\${hold_music}";
	$queue_time_base_score = $queue_time_base_score ?? "system";
	$queue_max_wait_time = $queue_max_wait_time ?? "0";
	$queue_max_wait_time_with_no_agent = $queue_max_wait_time_with_no_agent ?? "90";
	$queue_max_wait_time_with_no_agent_time_reached = $queue_max_wait_time_with_no_agent_time_reached ?? "30";
	$queue_tier_rule_wait_second = $queue_tier_rule_wait_second ?? "30";
	$queue_discard_abandoned_after = $queue_discard_abandoned_after ?? "900";
	$queue_record_enabled = !empty($queue_record_template) ? true : false;
	$queue_tier_rules_apply = $queue_tier_rules_apply ?? false;
	$queue_tier_rule_wait_multiply_level = $queue_tier_rule_wait_multiply_level ?? true;
	$queue_tier_rule_no_agent_no_wait = $queue_tier_rule_no_agent_no_wait ?? true;
	$queue_abandoned_resume_allowed = $queue_abandoned_resume_allowed ?? false;
	$queue_announce_sound = $queue_announce_sound ?? '';
	$queue_context = $queue_context ?? $domain_name;

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "add") {
		$document['title'] = $text['title-call_center_queue_add'];
	}
	if ($action == "update") {
		$document['title'] = $text['title-call_center_queue_edit'];
	}
	require_once "resources/header.php";

//only allow a uuid
	if (empty($call_center_queue_uuid)) {
		$call_center_queue_uuid = null;
	}

//show the content
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	function set_playable(id, audio_selected, audio_type) {\n";
		echo "		file_ext = audio_selected.split('.').pop();\n";
		echo "		var mime_type = '';\n";
		echo "		switch (file_ext) {\n";
		echo "			case 'wav': mime_type = 'audio/wav'; break;\n";
		echo "			case 'mp3': mime_type = 'audio/mpeg'; break;\n";
		echo "			case 'ogg': mime_type = 'audio/ogg'; break;\n";
		echo "		}\n";
		echo "		if (mime_type != '' && (audio_type == 'recordings' || audio_type == 'sounds')) {\n";
		echo "			if (audio_type == 'recordings') {\n";
		echo "				if (audio_selected.includes('/')) {\n";
		echo "					audio_selected = audio_selected.split('/').pop()\n";
		echo "				}\n";
		echo "				$('#recording_audio_' + id).attr('src', '../recordings/recordings.php?action=download&type=rec&filename=' + audio_selected);\n";
		echo "			}\n";
		echo "			else if (audio_type == 'sounds') {\n";
		echo "				$('#recording_audio_' + id).attr('src', '../switch/sounds.php?action=download&filename=' + audio_selected);\n";
		echo "			}\n";
		echo "			$('#recording_audio_' + id).attr('type', mime_type);\n";
		echo "			$('#recording_button_' + id).show();\n";
		echo "		}\n";
		echo "		else {\n";
		echo "			$('#recording_button_' + id).hide();\n";
		echo "			$('#recording_audio_' + id).attr('src','').attr('type','');\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";
	}
	if (if_group("superadmin")) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	var objs;\n";
		echo "	function toggle_select_input(obj, instance_id){\n";
		echo "		tb=document.createElement('INPUT');\n";
		echo "		tb.type='text';\n";
		echo "		tb.name=obj.name;\n";
		echo "		tb.className='formfld';\n";
		echo "		tb.setAttribute('id', instance_id);\n";
		echo "		tb.setAttribute('style', 'width: ' + obj.offsetWidth + 'px;');\n";
		if (!empty($on_change)) {
			echo "	tb.setAttribute('onchange', \"".$on_change."\");\n";
			echo "	tb.setAttribute('onkeyup', \"".$on_change."\");\n";
		}
		echo "		tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "		document.getElementById('btn_select_to_input_' + instance_id).style.display = 'none';\n";
		echo "		tbb=document.createElement('INPUT');\n";
		echo "		tbb.setAttribute('class', 'btn');\n";
		echo "		tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "		tbb.type='button';\n";
		echo "		tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "		tbb.objs=[obj,tb,tbb];\n";
		echo "		tbb.onclick=function(){ replace_element(this.objs, instance_id); }\n";
		echo "		obj.parentNode.insertBefore(tb,obj);\n";
		echo "		obj.parentNode.insertBefore(tbb,obj);\n";
		echo "		obj.parentNode.removeChild(obj);\n";
		echo "		replace_element(this.objs, instance_id);\n";
		echo "	}\n";
		echo "	function replace_element(obj, instance_id){\n";
		echo "		obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "		obj[0].parentNode.removeChild(obj[1]);\n";
		echo "		obj[0].parentNode.removeChild(obj[2]);\n";
		echo "		document.getElementById('btn_select_to_input_' + instance_id).style.display = 'inline';\n";
		if (!empty($on_change)) {
			echo "	".$on_change.";\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['header-call_center_queue_add']."</b>";
	}
	if ($action == "update") {
		echo "<b>".$text['header-call_center_queue_edit']."</b>";
	}
	echo 	"</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme','button_icon_back', ''),'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'call_center_queues.php']);

	if ($action == "update") {
		if (permission_exists('call_center_wallboard')) {
			echo button::create(['type'=>'button','label'=>$text['button-wallboard'],'icon'=>'th','link'=>PROJECT_PATH.'/app/call_center_wallboard/call_center_wallboard.php?queue_name='.urlencode($call_center_queue_uuid)]);
		}
		//echo button::create(['type'=>'button','label'=>$text['button-stop'],'icon'=>$settings->get('theme', 'button_icon_stop'),'link'=>'cmd.php?cmd=unload&id='.urlencode($call_center_queue_uuid)]);
		//echo button::create(['type'=>'button','label'=>$text['button-start'],'icon'=>$settings->get('theme', 'button_icon_start'),'link'=>'cmd.php?cmd=load&id='.urlencode($call_center_queue_uuid)]);
		echo button::create(['type'=>'button','label'=>$text['button-reload'],'icon'=>$settings->get('theme','button_icon_reload', ''),'link'=>'cmd.php?cmd=reload&id='.urlencode($call_center_queue_uuid)]);
		echo button::create(['type'=>'button','label'=>$text['button-view'],'icon'=>$settings->get('theme','button_icon_view', ''),'style'=>'margin-right: 15px;','link'=>PROJECT_PATH.'/app/call_center_active/call_center_active.php?queue_name='.urlencode($call_center_queue_uuid)]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme','button_icon_save', ''),'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<div class='card'>\n";
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
	echo "	<input class='formfld' type='number' name='queue_extension' maxlength='255' min='0' step='1' value=\"".escape($queue_extension)."\" required='required' placeholder=\"".$settings->get('call_center','extension_range', '')."\">\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	$instance_id = 'queue_greeting';
	$instance_label = 'greeting';
	$instance_value = $queue_greeting;
	echo "<tr>\n";
	echo "<td class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-'.$instance_label]."\n";
	echo "</td>\n";
	echo "<td class='vtable playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' onclick=\"recording_play('".$instance_id."', document.getElementById('".$instance_id."').value, document.getElementById('".$instance_id."').options[document.getElementById('".$instance_id."').selectedIndex].parentNode.getAttribute('data-type'));\" style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='".$instance_id."' id='".$instance_id."' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
	echo "	<option value=''></option>\n";
	$found = $playable = false;
	if (!empty($audio_files[0]) && is_array($audio_files[0]) && @sizeof($audio_files[0]) != 0) {
		foreach ($audio_files[0] as $key => $value) {
			echo "<optgroup label=".$text['label-'.$key]." data-type='".$key."'>\n";
			foreach ($value as $row) {
				if ($key == 'recordings') {
					if (
						!empty($instance_value) &&
						($instance_value == $row["value"] || $instance_value == $settings->get('switch','recordings', '')."/".$domain_name.'/'.$row["value"]) &&
						file_exists($settings->get('switch','recordings', '')."/".$domain_name.'/'.pathinfo($row["value"], PATHINFO_BASENAME))
						) {
						$selected = "selected='selected'";
						$playable = '../recordings/recordings.php?action=download&type=rec&filename='.pathinfo($row["value"], PATHINFO_BASENAME);
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else if ($key == 'sounds') {
					if (!empty($instance_value) && $instance_value == $row["value"]) {
						$selected = "selected='selected'";
						$playable = '../switch/sounds.php?action=download&filename='.$row["value"];
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else {
					unset($selected);
				}
				echo "	<option value='".escape($row["value"])."' ".($selected ?? '').">".escape($row["name"])."</option>\n";
			}
			echo "</optgroup>\n";
		}
	}
	if (if_group("superadmin") && !empty($instance_value) && !$found) {
		echo "	<option value='".escape($instance_value)."' selected='selected'>".escape($instance_value)."</option>\n";
	}
	unset($selected);
	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".$instance_id."' class='btn' name='' alt='back' onclick='toggle_select_input(document.getElementById(\"".$instance_id."\"), \"".$instance_id."\"); this.style.visibility=\"hidden\";' value='&#9665;'>";
	}
	if ((permission_exists('recording_play') || permission_exists('recording_download')) && (!empty($playable) || empty($instance_value))) {
		switch (pathinfo($playable, PATHINFO_EXTENSION)) {
			case 'wav' : $mime_type = 'audio/wav'; break;
			case 'mp3' : $mime_type = 'audio/mpeg'; break;
			case 'ogg' : $mime_type = 'audio/ogg'; break;
		}
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$settings->get('theme','button_icon_play', ''),'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."', document.getElementById('".$instance_id."').value, document.getElementById('".$instance_id."').options[document.getElementById('".$instance_id."').selectedIndex].parentNode.getAttribute('data-type'))"]);
		unset($playable, $mime_type);
	}
	echo "<br />\n";
	echo $text['description-'.$instance_label]."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <select class='formfld' type='text' name='queue_language'>\n";
	echo "		<option></option>\n";
	if (!empty($queue_language) && !empty($queue_dialect) && !empty($queue_voice)) {
		$language_formatted = $queue_language."-".$queue_dialect." ".$queue_voice;
		echo "		<option value='".escape($queue_language.'/'.$queue_dialect.'/'.$queue_voice)."' selected='selected'>".escape($language_formatted)."</option>\n";
	}
	if (!empty($language_paths)) {
		foreach ($language_paths as $key => $language_variables) {
			$language_variables = explode('/',$language_paths[$key]);
			$language = $language_variables[0];
			$dialect = $language_variables[1];
			$voice = $language_variables[2];
			if (empty($language_formatted) || $language_formatted != $language.'-'.$dialect.' '.$voice) {
				echo "		<option value='".$language."/".$dialect."/".$voice."'>".$language."-".$dialect." ".$voice."</option>\n";
			}
		}
	}
	echo "  </select>\n";
	echo "<br />\n";
	echo $text['description-language']."\n";
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

	if (permission_exists('call_center_tier_view') && !empty($agents) && is_array($agents)) {
		echo "<tr>";
		echo "	<td class='vncell' valign='top'>".$text['label-agents']."</td>";
		echo "	<td class='vtable' align='left'>";
		echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-agent_name']."</td>\n";
		echo "				<td class='vtable' style='text-align: center;'>".$text['label-tier_level']."</td>\n";
		echo "				<td class='vtable' style='text-align: center;'>".$text['label-tier_position']."</td>\n";
		if (permission_exists('call_center_tier_delete')) {
			echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_options', 'delete_toggle_options');\" onmouseout=\"swap_display('delete_label_options', 'delete_toggle_options');\">\n";
			echo "						<span id='delete_label_options'>".$text['label-delete']."</span>\n";
			echo "						<span id='delete_toggle_options'><input type='checkbox' id='checkbox_all_options' name='checkbox_all' onclick=\"edit_all_toggle('options');\"></span>\n";
			echo "					</td>\n";
		}
		echo "			</tr>\n";
		$x = 0;
		if (is_array($tiers)) {
			foreach($tiers as $field) {
				echo "	<tr>\n";
				echo "		<td class=''>";
				if (!empty($field['call_center_tier_uuid'])) {
					echo "				<input name='call_center_tiers[".$x."][call_center_tier_uuid]' type='hidden' value=\"".escape($field['call_center_tier_uuid'])."\">\n";
				}
				echo "				<select name=\"call_center_tiers[$x][call_center_agent_uuid]\" class=\"formfld\" style=\"width: 200px\">\n";
				if (is_uuid($field['call_center_agent_uuid'])) {
					echo "				<option value=\"".escape($field['call_center_agent_uuid'])."\">".escape($field['agent_name'])."</option>\n";
				}
				else {
					echo "					<option value=\"\"></option>\n";
					foreach($agents as $row) {
						echo "				<option value=\"".escape($row['call_center_agent_uuid'])."\">".escape($row['agent_name'])."</option>\n";
					}
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
				if (permission_exists('call_center_tier_delete')) {
					if (!empty($field['call_center_agent_uuid']) && is_uuid($field['call_center_agent_uuid'])) {
						echo "<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
						echo "	<input type='checkbox' name='call_center_tier_delete[".$x."][checked]' value='true' class='chk_delete checkbox_options' onclick=\"edit_delete_action('options');\">\n";
						echo "	<input type='hidden' name='call_center_tier_delete[".$x."][uuid]' value='".escape($field['call_center_tier_uuid'])."' />\n";
					}
					else {
						echo "<td>";
					}
					echo "</td>\n";
				}
				echo "	</tr>\n";
				$assigned_agents[] = $field['agent_name'];
				$x++;
			}
			unset ($tiers);
			echo "		</table>\n";
			echo "		<br>\n";
			echo "		".$text['description-tiers']."\n";
			echo "		<br />\n";
			echo "	</td>";
			echo "</tr>";
		}
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
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
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='queue_record_enabled' name='queue_record_enabled'>\n";
	echo "			<option value='true' ".($queue_record_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($queue_record_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-record_template']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-queue_limit']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_limit' maxlength='5' min='0' step='1' value='".escape($queue_limit)."'>\n";
	echo "<br />\n";
	echo $text['description-queue_limit']."\n";
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
	echo "	".$text['label-time_base_score_sec']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='queue_time_base_score_sec' maxlength='255' min='0' step='1' value='".escape($queue_time_base_score_sec)."'>\n";
	echo "<br />\n";
	echo $text['description-time_base_score_sec']."\n";
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
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='queue_tier_rules_apply' name='queue_tier_rules_apply'>\n";
	echo "			<option value='true' ".($queue_tier_rules_apply == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($queue_tier_rules_apply == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
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
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='queue_tier_rule_wait_multiply_level' name='queue_tier_rule_wait_multiply_level'>\n";
	echo "			<option value='true' ".($queue_tier_rule_wait_multiply_level == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($queue_tier_rule_wait_multiply_level == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-tier_rule_wait_multiply_level']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-tier_rule_no_agent_no_wait']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='queue_tier_rule_no_agent_no_wait' name='queue_tier_rule_no_agent_no_wait'>\n";
	echo "			<option value='true' ".($queue_tier_rule_no_agent_no_wait == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($queue_tier_rule_no_agent_no_wait == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
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
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='queue_abandoned_resume_allowed' name='queue_abandoned_resume_allowed'>\n";
	echo "			<option value='true' ".($queue_abandoned_resume_allowed == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($queue_abandoned_resume_allowed == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
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

	if (permission_exists('call_center_outbound_caller_id_name')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-outbound_caller_id_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='queue_outbound_caller_id_name' maxlength='255' value='".escape($queue_outbound_caller_id_name ?? '')."'>\n";
		echo "<br />\n";
		echo $text['description-outbound_caller_id_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('call_center_outbound_caller_id_number')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-outbound_caller_id_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='queue_outbound_caller_id_number' maxlength='255' value='".escape($queue_outbound_caller_id_number ?? '')."'>\n";
		echo "<br />\n";
		echo $text['description-outbound_caller_id_number']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('call_center_announce_position')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "  ".$text['label-queue_announce_position']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='queue_announce_position'>\n";
		echo "		<option value='false'>".$text['option-false']."</option>\n";
		echo "		<option value='true' ".(!empty($queue_announce_position) && $queue_announce_position == "true" ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo ($text['description-queue_announce_position'] ?? '')."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('call_center_announce_sound')) {
		$instance_id = 'queue_announce_sound';
		$instance_label = 'caller_announce_sound';
		$instance_value = $queue_announce_sound;
		echo "<tr>\n";
		echo "<td class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-'.$instance_label]."\n";
		echo "</td>\n";
		echo "<td class='vtable playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' onclick=\"recording_play('".$instance_id."', document.getElementById('".$instance_id."').value, document.getElementById('".$instance_id."').options[document.getElementById('".$instance_id."').selectedIndex].parentNode.getAttribute('data-type'));\" style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "<select name='".$instance_id."' id='".$instance_id."' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
		echo "	<option value=''></option>\n";
		$found = $playable = false;
		if (!empty($audio_files[1]) && is_array($audio_files[1]) && @sizeof($audio_files[1]) != 0) {
			foreach ($audio_files[1] as $key => $value) {
				echo "<optgroup label=".$text['label-'.$key]." data-type='".$key."'>\n";
				foreach ($value as $row) {
					if ($key == 'recordings') {
						if (
							!empty($instance_value) &&
							($instance_value == $row["value"] || $instance_value == $settings->get('switch','recordings', '')."/".$domain_name.'/'.$row["value"]) &&
							file_exists($settings->get('switch','recordings', '')."/".$domain_name.'/'.pathinfo($row["value"], PATHINFO_BASENAME))
							) {
							$selected = "selected='selected'";
							$playable = '../recordings/recordings.php?action=download&type=rec&filename='.pathinfo($row["value"], PATHINFO_BASENAME);
							$found = true;
						}
						else {
							unset($selected);
						}
					}
					else if ($key == 'sounds') {
						if (!empty($instance_value) && $instance_value == $row["value"]) {
							$selected = "selected='selected'";
							$playable = '../switch/sounds.php?action=download&filename='.$row["value"];
							$found = true;
						}
						else {
							unset($selected);
						}
					}
					else {
						unset($selected);
					}
					echo "	<option value='".escape($row["value"])."' ".($selected ?? '').">".escape($row["name"])."</option>\n";
				}
				echo "</optgroup>\n";
			}
		}
		if (if_group("superadmin") && !empty($instance_value) && !$found) {
			echo "	<option value='".escape($instance_value)."' selected='selected'>".escape($instance_value)."</option>\n";
		}
		unset($selected);
		echo "	</select>\n";
		if (if_group("superadmin")) {
			echo "<input type='button' id='btn_select_to_input_".$instance_id."' class='btn' name='' alt='back' onclick='toggle_select_input(document.getElementById(\"".$instance_id."\"), \"".$instance_id."\"); this.style.visibility=\"hidden\";' value='&#9665;'>";
		}
		if ((permission_exists('recording_play') || permission_exists('recording_download')) && (!empty($playable) || empty($instance_value))) {
			switch (pathinfo($playable, PATHINFO_EXTENSION)) {
				case 'wav' : $mime_type = 'audio/wav'; break;
				case 'mp3' : $mime_type = 'audio/mpeg'; break;
				case 'ogg' : $mime_type = 'audio/ogg'; break;
			}
			echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
			echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$settings->get('theme','button_icon_play', ''),'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."', document.getElementById('".$instance_id."').value, document.getElementById('".$instance_id."').options[document.getElementById('".$instance_id."').selectedIndex].parentNode.getAttribute('data-type'))"]);
			unset($playable, $mime_type);
		}
		echo "<br />\n";
		echo $text['description-'.$instance_label]."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('call_center_announce_frequency')) {
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
	}

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

	if (permission_exists('call_center_email_address')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-queue_email_address']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='queue_email_address' maxlength='255' value='".escape($queue_email_address ?? '')."'>\n";
		echo "<br />\n";
		echo $text['description-queue_email_address']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('call_center_queue_context')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='queue_context' maxlength='255' value=\"".escape($queue_context)."\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-enter-context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

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

	echo "</table>";
	echo "</div>\n";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='call_center_queue_uuid' value='".escape($call_center_queue_uuid)."'>\n";
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
