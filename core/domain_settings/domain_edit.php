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
if (permission_exists('domain_add') || permission_exists('domain_edit')) {
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
	if (!permission_exists('domain_add') || (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/") && !permission_exists('domain_parent') && permission_exists('domain_descendants'))) {
		//admin editing own domain/settings
		$domain_uuid = $_SESSION['domain_uuid'];
		$action = "update";
	}
	else {
		if (isset($_REQUEST["id"])) {
			$action = "update";
			$domain_uuid = check_str($_REQUEST["id"]);
		}
		else {
			$action = "add";
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$domain_name = check_str($_POST["domain_name"]);
		$domain_enabled = check_str($_POST["domain_enabled"]);
		$domain_description = check_str($_POST["domain_description"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$domain_uuid = check_str($_POST["domain_uuid"]);
	}

	//check for all required data
		if (strlen($domain_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		//if (strlen($domain_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
			if ($action == "add" && permission_exists('domain_add')) {
				$sql = "select count(*) as num_rows from v_domains ";
				$sql .= "where domain_name = '".$domain_name."' ";
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
				$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['num_rows'] == 0) {
						$sql = "insert into v_domains ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "domain_name, ";
						$sql .= "domain_enabled, ";
						$sql .= "domain_description ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".uuid()."', ";
						$sql .= "'".$domain_name."', ";
						$sql .= "'".$domain_enabled."', ";
						$sql .= "'".$domain_description."' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}
				}
			}

			if ($action == "update" && permission_exists('domain_edit')) {
				// get original domain name
				$sql = "select domain_name from v_domains ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$original_domain_name = $row["domain_name"];
					break;
				}
				unset($sql, $prep_statement);

				// update domain name, description
				$sql = "update v_domains set ";
				$sql .= "domain_name = '".$domain_name."', ";
				$sql .= "domain_enabled = '".$domain_enabled."', ";
				$sql .= "domain_description = '".$domain_description."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$db->exec(check_sql($sql));
				unset($sql);

				if ($original_domain_name != $domain_name) {

					// update dialplans
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/dialplan/app_config.php")){
							$sql = "update v_dialplans set ";
							$sql .= "dialplan_context = '".$domain_name."' ";
							$sql .= "where dialplan_context = '".$original_domain_name."' ";
							$sql .= "and domain_uuid = '".$domain_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);
						}

					// update extensions (accountcode, user_context, dial_domain)
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/extensions/app_config.php")){
							$sql = "update v_extensions set ";
							$sql .= "accountcode = '".$domain_name."' ";
							$sql .= "where accountcode = '".$original_domain_name."' ";
							$sql .= "and domain_uuid = '".$domain_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);

							$sql = "update v_extensions set ";
							$sql .= "user_context = '".$domain_name."' ";
							$sql .= "where user_context = '".$original_domain_name."' ";
							$sql .= "and domain_uuid = '".$domain_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);

							$sql = "update v_extensions set ";
							$sql .= "dial_domain = '".$domain_name."' ";
							$sql .= "where dial_domain = '".$original_domain_name."' ";
							$sql .= "and domain_uuid = '".$domain_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);
						}

					// update cdr records (domain_name, context)
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/xml_cdr/app_config.php")){
							$sql = "update v_xml_cdr set ";
							$sql .= "domain_name = '".$domain_name."' ";
							$sql .= "where domain_name = '".$original_domain_name."' ";
							$sql .= "and domain_uuid = '".$domain_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);

							$sql = "update v_xml_cdr set ";
							$sql .= "context = '".$domain_name."' ";
							$sql .= "where context = '".$original_domain_name."' ";
							$sql .= "and domain_uuid = '".$domain_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);
						}

					// update billing, if installed
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/billing/app_config.php")){
							$sql = "update v_billings set ";
							$sql .= "type_value = '".$domain_name."' ";
							$sql .= "where type_value = '".$original_domain_name."' ";
							$sql .= "and domain_uuid = '".$domain_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);
						}

					// rename switch/storage/voicemail/default/[domain] (folder)
						if ( isset($_SESSION['switch']['voicemail']['dir']) && file_exists($_SESSION['switch']['voicemail']['dir']."/default/".$original_domain_name) ) {
							@rename($_SESSION['switch']['voicemail']['dir']."/default/".$original_domain_name, $_SESSION['switch']['voicemail']['dir']."/default/".$domain_name); // folder
						}

					// rename switch/storage/fax/[domain] (folder)
						if ( isset($_SESSION['switch']['storage']['dir']) && file_exists($_SESSION['switch']['storage']['dir']."/fax/".$original_domain_name) ) {
							@rename($_SESSION['switch']['storage']['dir']."/fax/".$original_domain_name, $_SESSION['switch']['storage']['dir']."/fax/".$domain_name); // folder
						}

					// rename switch/conf/dialplan/[domain] (folder/file)
						if ( isset($_SESSION['switch']['dialplan']['dir']) ) {
							if ( file_exists($_SESSION['switch']['dialplan']['dir']."/".$original_domain_name) ) {
								@rename($_SESSION['switch']['dialplan']['dir']."/".$original_domain_name, $_SESSION['switch']['dialplan']['dir']."/".$domain_name); // folder
							}
							if ( file_exists($_SESSION['switch']['dialplan']['dir']."/".$original_domain_name.".xml") ) {
								@rename($_SESSION['switch']['dialplan']['dir']."/".$original_domain_name.".xml", $_SESSION['switch']['dialplan']['dir']."/".$domain_name.".xml"); // file
							}
						}

					// rename switch/conf/dialplan/public/[domain] (folder/file)
						if ( isset($_SESSION['switch']['dialplan']['dir']) ) {
							if ( file_exists($_SESSION['switch']['dialplan']['dir']."/public/".$original_domain_name) ) {
								@rename($_SESSION['switch']['dialplan']['dir']."/public/".$original_domain_name, $_SESSION['switch']['dialplan']['dir']."/public/".$domain_name); // folder
							}
							if ( file_exists($_SESSION['switch']['dialplan']['dir']."/public/".$original_domain_name.".xml") ) {
								@rename($_SESSION['switch']['dialplan']['dir']."/public/".$original_domain_name.".xml", $_SESSION['switch']['dialplan']['dir']."/public/".$domain_name.".xml"); // file
							}
						}

					// rename switch/conf/directory/[domain] (folder/file)
						if ( isset($_SESSION['switch']['extensions']['dir']) ) {
							if ( file_exists($_SESSION['switch']['extensions']['dir']."/".$original_domain_name) ) {
								@rename($_SESSION['switch']['extensions']['dir']."/".$original_domain_name, $_SESSION['switch']['extensions']['dir']."/".$domain_name); // folder
							}
							if ( file_exists($_SESSION['switch']['extensions']['dir']."/".$original_domain_name.".xml") ) {
								@rename($_SESSION['switch']['extensions']['dir']."/".$original_domain_name.".xml", $_SESSION['switch']['extensions']['dir']."/".$domain_name.".xml"); // file
							}
						}

					// rename switch/recordings/[domain] (folder)
						if ( file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']) ) {
							$switch_recordings_dir = str_replace("/".$_SESSION["domain_name"], "", $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']);
							if ( file_exists($switch_recordings_dir."/".$original_domain_name) ) {
								@rename($switch_recordings_dir."/".$original_domain_name, $switch_recordings_dir."/".$domain_name); // folder
							}
						}

					// update conference session recording paths
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/conference_centers/app_config.php")){
							$sql = "select conference_session_uuid, recording from v_conference_sessions ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and recording like '%".$original_domain_name."%' ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$conference_session_uuid = $row["conference_session_uuid"];
								$recording = $row["recording"];
								// replace old domain name with new domain
								$recording = str_replace($original_domain_name, $domain_name, $recording);
								// update db record
								$sql = "update v_conference_sessions set ";
								$sql .= "recording = '".$recording."' ";
								$sql .= "where conference_session_uuid = '".$conference_session_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update conference center greetings
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/conference_centers/app_config.php")){
							$sql = "select conference_center_uuid, conference_center_greeting from v_conference_centers ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and conference_center_greeting like '%".$original_domain_name."%' ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$conference_center_uuid = $row["conference_center_uuid"];
								$conference_center_greeting = $row["conference_center_greeting"];
								// replace old domain name with new domain
								$conference_center_greeting = str_replace($original_domain_name, $domain_name, $conference_center_greeting);
								// update db record
								$sql = "update v_conference_centers set ";
								$sql .= "conference_center_greeting = '".$conference_center_greeting."' ";
								$sql .= "where conference_center_uuid = '".$conference_center_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update ivr menu greetings
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ivr_menu/app_config.php")){
							$sql = "select ivr_menu_uuid, ivr_menu_greet_long, ivr_menu_greet_short from v_ivr_menus ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and ( ";
							$sql .= "ivr_menu_greet_long like '%".$original_domain_name."%' or ";
							$sql .= "ivr_menu_greet_short like '%".$original_domain_name."%' ";
							$sql .= ") ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$ivr_menu_uuid = $row["ivr_menu_uuid"];
								$ivr_menu_greet_long = $row["ivr_menu_greet_long"];
								$ivr_menu_greet_short = $row["ivr_menu_greet_short"];
								// replace old domain name with new domain
								$ivr_menu_greet_long = str_replace($original_domain_name, $domain_name, $ivr_menu_greet_long);
								$ivr_menu_greet_short = str_replace($original_domain_name, $domain_name, $ivr_menu_greet_short);
								// update db record
								$sql = "update v_ivr_menus set ";
								$sql .= "ivr_menu_greet_long = '".$ivr_menu_greet_long."', ";
								$sql .= "ivr_menu_greet_short = '".$ivr_menu_greet_short."' ";
								$sql .= "where ivr_menu_uuid = '".$ivr_menu_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update ivr menu option parameters
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ivr_menu/app_config.php")){
							$sql = "select ivr_menu_option_uuid, ivr_menu_option_param from v_ivr_menu_options ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and ivr_menu_option_param like '%".$original_domain_name."%' ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$ivr_menu_option_uuid = $row["ivr_menu_option_uuid"];
								$ivr_menu_option_param = $row["ivr_menu_option_param"];
								// replace old domain name with new domain
								$ivr_menu_option_param = str_replace($original_domain_name, $domain_name, $ivr_menu_option_param);
								// update db record
								$sql = "update v_ivr_menu_options set ";
								$sql .= "ivr_menu_option_param = '".$ivr_menu_option_param."' ";
								$sql .= "where ivr_menu_option_uuid = '".$ivr_menu_option_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update call center queue record templates
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_center/app_config.php")){
							$sql = "select call_center_queue_uuid, queue_record_template from v_call_center_queues ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and queue_record_template like '%".$original_domain_name."%' ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$call_center_queue_uuid = $row["call_center_queue_uuid"];
								$queue_record_template = $row["queue_record_template"];
								// replace old domain name with new domain
								$queue_record_template = str_replace($original_domain_name, $domain_name, $queue_record_template);
								// update db record
								$sql = "update v_call_center_queues set ";
								$sql .= "queue_record_template = '".$queue_record_template."' ";
								$sql .= "where call_center_queue_uuid = '".$call_center_queue_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update call center agent contacts
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_center/app_config.php")){
							$sql = "select call_center_agent_uuid, agent_contact from v_call_center_agents ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and agent_contact like '%".$original_domain_name."%' ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$call_center_agent_uuid = $row["call_center_agent_uuid"];
								$agent_contact = $row["agent_contact"];
								// replace old domain name with new domain
								$agent_contact = str_replace($original_domain_name, $domain_name, $agent_contact);
								// update db record
								$sql = "update v_call_center_agents set ";
								$sql .= "agent_contact = '".$agent_contact."' ";
								$sql .= "where call_center_agent_uuid = '".$call_center_agent_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update call flows data, anti-data and contexts
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_flows/app_config.php")){
							$sql = "select call_flow_uuid, call_flow_data, call_flow_anti_data, call_flow_context from v_call_flows ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and ( ";
							$sql .= "call_flow_data like '%".$original_domain_name."%' or ";
							$sql .= "call_flow_anti_data like '%".$original_domain_name."%' or ";
							$sql .= "call_flow_context like '%".$original_domain_name."%' ";
							$sql .= ") ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$call_flow_uuid = $row["call_flow_uuid"];
								$call_flow_data = $row["call_flow_data"];
								$call_flow_anti_data = $row["call_flow_anti_data"];
								$call_flow_context = $row["call_flow_context"];
								// replace old domain name with new domain
								$call_flow_data = str_replace($original_domain_name, $domain_name, $call_flow_data);
								$call_flow_anti_data = str_replace($original_domain_name, $domain_name, $call_flow_anti_data);
								$call_flow_context = str_replace($original_domain_name, $domain_name, $call_flow_context);
								// update db record
								$sql = "update v_call_flows set ";
								$sql .= "call_flow_data = '".$call_flow_data."', ";
								$sql .= "call_flow_anti_data = '".$call_flow_anti_data."', ";
								$sql .= "call_flow_context = '".$call_flow_context."' ";
								$sql .= "where call_flow_uuid = '".$call_flow_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update ring group context, forward destination, timeout data
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ring_groups/app_config.php")){
							$sql = "select ring_group_uuid, ring_group_context, ring_group_forward_destination, ring_group_timeout_data from v_ring_groups ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and ( ";
							$sql .= "ring_group_context like '%".$original_domain_name."%' or ";
							$sql .= "ring_group_forward_destination like '%".$original_domain_name."%' or ";
							$sql .= "ring_group_timeout_data like '%".$original_domain_name."%' ";
							$sql .= ") ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$ring_group_uuid = $row["ring_group_uuid"];
								$ring_group_context = $row["ring_group_context"];
								$ring_group_forward_destination = $row["ring_group_forward_destination"];
								$ring_group_timeout_data = $row["ring_group_timeout_data"];
								// replace old domain name with new domain
								$ring_group_context = str_replace($original_domain_name, $domain_name, $ring_group_context);
								$ring_group_forward_destination = str_replace($original_domain_name, $domain_name, $ring_group_forward_destination);
								$ring_group_timeout_data = str_replace($original_domain_name, $domain_name, $ring_group_timeout_data);
								// update db record
								$sql = "update v_ring_groups set ";
								$sql .= "ring_group_context = '".$ring_group_context."', ";
								$sql .= "ring_group_forward_destination = '".$ring_group_forward_destination."', ";
								$sql .= "ring_group_timeout_data = '".$ring_group_timeout_data."' ";
								$sql .= "where ring_group_uuid = '".$ring_group_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update device lines server address, outbound proxy
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/devices/app_config.php")){
							$sql = "select device_line_uuid, server_address, outbound_proxy from v_device_lines ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and ( ";
							$sql .= "server_address like '%".$original_domain_name."%' or ";
							$sql .= "outbound_proxy like '%".$original_domain_name."%' ";
							$sql .= ") ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$device_line_uuid = $row["device_line_uuid"];
								$server_address = $row["server_address"];
								$outbound_proxy = $row["outbound_proxy"];
								// replace old domain name with new domain
								$server_address = str_replace($original_domain_name, $domain_name, $server_address);
								$outbound_proxy = str_replace($original_domain_name, $domain_name, $outbound_proxy);
								// update db record
								$sql = "update v_device_lines set ";
								$sql .= "server_address = '".$server_address."', ";
								$sql .= "outbound_proxy = '".$outbound_proxy."' ";
								$sql .= "where device_line_uuid = '".$device_line_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update dialplan, dialplan/public xml files
						$dialplan_xml = file_get_contents($_SESSION['switch']['dialplan']['dir']."/".$domain_name.".xml");
						$dialplan_xml = str_replace($original_domain_name, $domain_name, $dialplan_xml);
						file_put_contents($_SESSION['switch']['dialplan']['dir']."/".$domain_name.".xml", $dialplan_xml);
						unset($dialplan_xml);

						$dialplan_public_xml = file_get_contents($_SESSION['switch']['dialplan']['dir']."/public/".$domain_name.".xml");
						$dialplan_public_xml = str_replace($original_domain_name, $domain_name, $dialplan_public_xml);
						file_put_contents($_SESSION['switch']['dialplan']['dir']."/public/".$domain_name.".xml", $dialplan_public_xml);
						unset($dialplan_public_xml);

					// update dialplan details
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/dialplan/app_config.php")){
							$sql = "select dialplan_detail_uuid, dialplan_detail_data from v_dialplan_details ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and dialplan_detail_data like '%".$original_domain_name."%' ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								// get current values
								$dialplan_detail_uuid = $row["dialplan_detail_uuid"];
								$dialplan_detail_data = $row["dialplan_detail_data"];
								// replace old domain name with new domain
								$dialplan_detail_data = str_replace($original_domain_name, $domain_name, $dialplan_detail_data);
								// update db record
								$sql = "update v_dialplan_details set ";
								$sql .= "dialplan_detail_data = '".$dialplan_detail_data."' ";
								$sql .= "where dialplan_detail_uuid = '".$dialplan_detail_uuid."' ";
								$sql .= "and domain_uuid = '".$domain_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($sql, $prep_statement, $result);
						}

					// update session domain name
						$_SESSION['domains'][$domain_uuid]['domain_name'] = $domain_name;

					// recreate dialplan and extension xml files
						if (is_readable($_SESSION['switch']['dialplan']['dir'])) {
							save_dialplan_xml();
						}
						if (is_readable($_SESSION['switch']['extensions']['dir'])) {
							require_once $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/resources/classes/extension.php";
							$extension = new extension;
							$extension->xml();
						}

					// if single-tenant and variables exist, update variables > domain value to match new domain
						if (count($_SESSION['domains']) == 1 && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/vars/")) {
							$sql = "update v_vars set ";
							$sql .= "var_value = '".$domain_name."' ";
							$sql .= "where var_name = 'domain' ";
							$db->exec(check_sql($sql));
							unset($sql);
						}
				}
			}

		//upgrade the domains
			if (permission_exists('upgrade_apps') || if_group("superadmin")) {
				require_once "core/upgrade/upgrade_domains.php";
			}

		//clear the domains session array to update it
			unset($_SESSION["domains"]);
			unset($_SESSION["domain_uuid"]);
			unset($_SESSION["domain_name"]);
			unset($_SESSION['domain']);
			unset($_SESSION['switch']);

		//redirect the browser
			if ($action == "update") {
				$_SESSION["message"] = $text['message-update'];
				if (!permission_exists('domain_add')) { //admin, updating own domain
					header("Location: domain_edit.php");
				}
				else {
					header("Location: domains.php"); //superadmin
				}
			}
			if ($action == "add") {
				$_SESSION["message"] = $text['message-add'];
				header("Location: domains.php");
			}
			return;
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form (admin won't have domain_add permissions, but domain_uuid will already be set above)
	if ((count($_GET) > 0 || (!permission_exists('domain_add') && $domain_uuid != '')) && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_domains ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_name = strtolower($row["domain_name"]);
			$domain_enabled = $row["domain_enabled"];
			$domain_description = $row["domain_description"];
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-domain-edit'];
	}
	if ($action == "add") {
		$document['title'] = $text['title-domain-add'];
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' valign='top' width='30%' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-domain-edit'];
	}
	if ($action == "add") {
		echo $text['header-domain-add'];
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	if (permission_exists('domain_add')) { //only for superadmin, not admin editing their own domain
		echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='domains.php'\" value='".$text['button-back']."'>\n";
	}
	if (permission_exists('domain_export')) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-export']."' onclick=\"window.location='".PROJECT_PATH."/app/domain_export/index.php?id=".$domain_uuid."'\" value='".$text['button-export']."'>\n";
	}
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "update") {
		echo $text['description-domain-edit'];
	}
	if ($action == "add") {
		echo $text['description-domain-add'];
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='domain_name' maxlength='255' value=\"".$domain_name."\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='domain_enabled'>\n";
	echo "		<option value='true' ".(($domain_enabled == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
	echo "		<option value='false' ".(($domain_enabled == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-domain_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='domain_description' maxlength='255' value=\"".$domain_description."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='domain_uuid' value='$domain_uuid'>\n";
	}
	echo "			<br />";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br /><br />";

	echo "</form>";

	if (permission_exists('domain_setting_edit') && $action == "update") {
		require "domain_settings.php";
	}

//include the footer
	require_once "resources/footer.php";
?>
