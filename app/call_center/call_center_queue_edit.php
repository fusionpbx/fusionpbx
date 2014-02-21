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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$call_center_queue_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
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

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$call_center_queue_uuid = check_str($_POST["call_center_queue_uuid"]);
	}

	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
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
				$sql .= "queue_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
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
			header("Location: call_center_queues.php");
			return;
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
				$sql .= "queue_description = '$queue_description' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and call_center_queue_uuid = '$call_center_queue_uuid'";
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

			$_SESSION["message"] = $text['message-update'];
			header("Location: call_center_queues.php");
			return;
		} //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$call_center_queue_uuid = $_GET["id"];
		$sql = "select * from v_call_center_queues ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and call_center_queue_uuid = '$call_center_queue_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$queue_name = $row["queue_name"];
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
	if (strlen($queue_max_wait_time_with_no_agent) == 0) { $queue_max_wait_time_with_no_agent = "30"; }
	if (strlen($queue_max_wait_time_with_no_agent_time_reached) == 0) { $queue_max_wait_time_with_no_agent_time_reached = "60"; }
	if (strlen($queue_tier_rules_apply) == 0) { $queue_tier_rules_apply = "false"; }
	if (strlen($queue_tier_rule_wait_second) == 0) { $queue_tier_rule_wait_second = "3"; }
	if (strlen($queue_tier_rule_wait_multiply_level) == 0) { $queue_tier_rule_wait_multiply_level = "true"; }
	if (strlen($queue_tier_rule_no_agent_no_wait) == 0) { $queue_tier_rule_no_agent_no_wait = "false"; }
	if (strlen($queue_discard_abandoned_after) == 0) { $queue_discard_abandoned_after = "60"; }
	if (strlen($queue_abandoned_resume_allowed) == 0) { $queue_abandoned_resume_allowed = "false"; }

//show the header
	require_once "resources/header.php";
	if ($action == "add") {
		$page["title"] = $text['title-call_center_queue_add'];
	}
	if ($action == "update") {
		$page["title"] = $text['title-call_center_queue_edit'];
	}

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-call_center_queue_add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-call_center_queue_edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>\n";
	if ($action == "update") {
		echo "  <input type='button' class='btn' value='".$text['button-view']."' onclick=\"document.location.href='".PROJECT_PATH."/app/call_center_active/call_center_active.php?queue_name=$queue_name';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-load']."' onclick=\"document.location.href='cmd.php?cmd=api+callcenter_config+queue+load+$queue_name@".$_SESSION['domain_name']."';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-unload']."' onclick=\"document.location.href='cmd.php?cmd=api+callcenter_config+queue+unload+$queue_name@".$_SESSION['domain_name']."';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-reload']."' onclick=\"document.location.href='cmd.php?cmd=api+callcenter_config+queue+reload+$queue_name@".$_SESSION['domain_name']."';\" />\n";
	}
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_center_queues.php'\" value='".$text['button-back']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	//echo "Call Center queue settings.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-queue_name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='queue_name' maxlength='255' value=\"$queue_name\">\n";
	echo "<br />\n";
	echo $text['description-queue_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-extension'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='queue_extension' maxlength='255' value=\"$queue_extension\">\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-strategy'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_strategy'>\n";
	echo "	<option value=''></option>\n";
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

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-music_on_hold'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$select_options = "";
	if ($queue_moh_sound == "\${us-ring}" || $queue_moh_sound == "us-ring") {
		$select_options .= "		<option value='\${us-ring}' selected='selected'>".$text['option-usring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${us-ring}'>".$text['option-usring']."</option>\n";
	}
	if ($queue_moh_sound == "\${pt-ring}" || $queue_moh_sound == "pt-ring") {
		$select_options .= "		<option value='\${pt-ring}' selected='selected'>".$text['option-ptring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${pt-ring}'>".$text['option-ptring']."</option>\n";
	}
	if ($queue_moh_sound == "\${fr-ring}" || $queue_moh_sound == "fr-ring") {
		$select_options .= "		<option value='\${fr-ring}' selected='selected'>".$text['option-frring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${fr-ring}'>".$text['option-frring']."</option>\n";
	}
	if ($queue_moh_sound == "\${uk-ring}" || $queue_moh_sound == "uk-ring") {
		$select_options .= "		<option value='\${uk-ring}' selected='selected'>".$text['option-ukring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${uk-ring}'>".$text['option-ukring']."</option>\n";
	}
	if ($queue_moh_sound == "\${rs-ring}" || $queue_moh_sound == "rs-ring") {
		$select_options .= "		<option value='\${rs-ring}' selected='selected'>".$text['option-rsring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${rs-ring}'>".$text['option-rsring']."</option>\n";
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
	echo "	".$text['label-record_template'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='queue_record_template' maxlength='255' value=\"$queue_record_template\">\n";
	echo "<br />\n";
	echo $text['description-record_template']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-time_base_score'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_time_base_score'>\n";
	echo "	<option value=''></option>\n";
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
	echo "	".$text['label-max_wait_time'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_max_wait_time' maxlength='255' value='$queue_max_wait_time'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-max_wait_time_with_no_agent'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_max_wait_time_with_no_agent' maxlength='255' value='$queue_max_wait_time_with_no_agent'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time_with_no_agent']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-max_wait_time_with_no_agent_time_reached'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_max_wait_time_with_no_agent_time_reached' maxlength='255' value='$queue_max_wait_time_with_no_agent_time_reached'>\n";
	echo "<br />\n";
	echo $text['description-max_wait_time_with_no_agent_time_reached']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-timeout_action'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
	switch_select_destination("dialplan", "", "queue_timeout_action", $queue_timeout_action, "", "");
	echo "<br />\n";
	echo $text['description-timeout_action']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-tier_rules_apply'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_tier_rules_apply'>\n";
	echo "	<option value=''></option>\n";
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
	echo "	".$text['label-tier_rule_wait_second'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_tier_rule_wait_second' maxlength='255' value='$queue_tier_rule_wait_second'>\n";
	echo "<br />\n";
	echo $text['description-tier_rule_wait_second']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-tier_rule_wait_multiply_level'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_tier_rule_wait_multiply_level'>\n";
	echo "	<option value=''></option>\n";
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
	echo "	".$text['label-tier_rule_no_agent_no_wait'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_tier_rule_no_agent_no_wait'>\n";
	echo "	<option value=''></option>\n";
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
	echo "	".$text['label-discard_abandoned_after'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_discard_abandoned_after' maxlength='255' value='$queue_discard_abandoned_after'>\n";
	echo "<br />\n";
	echo $text['description-discard_abandoned_after']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-abandoned_resume_allowed'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='queue_abandoned_resume_allowed'>\n";
	echo "	<option value=''></option>\n";
	if ($queue_abandoned_resume_allowed == "true") {
		echo "	<option value='true' selected='selected' >".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($queue_abandoned_resume_allowed == "false") {
		echo "	<option value='false' selected='selected' >".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-abandoned_resume_allowed']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-caller_id_name_prefix'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='queue_cid_prefix' maxlength='255' value='$queue_cid_prefix'>\n";
	echo "<br />\n";
	echo $text['description-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description'].":\n";
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
		echo "				<input type='hidden' name='call_center_queue_uuid' value='$call_center_queue_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

require_once "resources/footer.php";
?>