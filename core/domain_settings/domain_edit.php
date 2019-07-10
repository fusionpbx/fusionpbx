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
 Portions created by the Initial Developer are Copyright (C) 2008-2017
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
		if (is_uuid($_REQUEST["id"])) {
			$action = "update";
			$domain_uuid = $_REQUEST["id"];
		}
		else {
			$action = "add";
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$domain_name = $_POST["domain_name"];
		$domain_enabled = $_POST["domain_enabled"];
		$domain_description = $_POST["domain_description"];
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$domain_uuid = $_POST["domain_uuid"];
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
				$sql = "select count(*) from v_domains ";
				$sql .= "where domain_name = :domain_name ";
				$parameters['domain_name'] = $domain_name;
				$database = new database;
				$num_rows = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

				if ($num_rows == 0) {
					$array['domains'][0]['domain_uuid'] = uuid();
					$array['domains'][0]['domain_name'] = $domain_name;
					$array['domains'][0]['domain_enabled'] = $domain_enabled;
					$array['domains'][0]['domain_description'] = $domain_description;
					$database = new database;
					$database->app_name = 'domain_settings';
					$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
					$database->save($array);
					unset($array);
				}

			}

			if ($action == "update" && permission_exists('domain_edit')) {
				// get original domain name
				$sql = "select domain_name from v_domains ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
				$database = new database;
				$original_domain_name = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

				// update domain name, description
				$array['domains'][0]['domain_uuid'] = $domain_uuid;
				$array['domains'][0]['domain_name'] = $domain_name;
				$array['domains'][0]['domain_enabled'] = $domain_enabled;
				$array['domains'][0]['domain_description'] = $domain_description;
				$database = new database;
				$database->app_name = 'domain_settings';
				$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
				$database->save($array);
				unset($array);

				if ($original_domain_name != $domain_name) {

					// update dialplans
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/dialplans/app_config.php")){
							$sql = "update v_dialplans ";
							$sql .= "set dialplan_context = :dialplan_context_new ";
							$sql .= "where dialplan_context = :dialplan_context_old ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['dialplan_context_new'] = $domain_name;
							$parameters['dialplan_context_old'] = $original_domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);

							$sql = "update v_dialplans ";
							$sql .= "set dialplan_xml = replace(dialplan_xml, :dialplan_xml_old, :dialplan_xml_new); ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['dialplan_xml_old'] = $original_domain_name;
							$parameters['dialplan_xml_new'] = $domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);
						}

					// update destinations
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/destinations/app_config.php")){
							$sql = "update v_destinations ";
							$sql .= "set destination_data = replace(destination_data, :destination_data_old, :destination_data_new); ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['destination_data_old'] = $original_domain_name;
							$parameters['destination_data_new'] = $domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);
						}

					// update extensions (accountcode, user_context, dial_domain)
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/extensions/app_config.php")){
							$sql = "update v_extensions set ";
							$sql .= "accountcode = :account_code_new ";
							$sql .= "where accountcode = :account_code_old ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['account_code_new'] = $domain_name;
							$parameters['account_code_old'] = $original_domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);

							$sql = "update v_extensions set ";
							$sql .= "user_context = :user_context_new ";
							$sql .= "where user_context = :user_context_old ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['user_context_new'] = $domain_name;
							$parameters['user_context_old'] = $original_domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);

							$sql = "update v_extensions set ";
							$sql .= "dial_domain = :dial_domain_new ";
							$sql .= "where dial_domain = :dial_domain_old ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['dial_domain_new'] = $domain_name;
							$parameters['dial_domain_old'] = $original_domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);
						}

					// update cdr records (domain_name, context)
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/xml_cdr/app_config.php")){
							$sql = "update v_xml_cdr set ";
							$sql .= "domain_name = :domain_name_new ";
							$sql .= "where domain_name = :domain_name_old ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['domain_name_new'] = $domain_name;
							$parameters['domain_name_old'] = $original_domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);

							$sql = "update v_xml_cdr set ";
							$sql .= "context = :context_new ";
							$sql .= "where context = :context_old ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['context_new'] = $domain_name;
							$parameters['context_old'] = $original_domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);
						}

					// update billing, if installed
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/billing/app_config.php")){
							$sql = "update v_billings set ";
							$sql .= "type_value = :type_value_new ";
							$sql .= "where type_value = :type_value_old ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['type_value_new'] = $domain_name;
							$parameters['type_value_old'] = $original_domain_name;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);
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
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and recording like :recording ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['recording'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									// update db record
									$array['conference_sessions'][$index]['conference_session_uuid'] = $row["conference_session_uuid"];
									$array['conference_sessions'][$index]['recording'] = str_replace($original_domain_name, $domain_name, $row["recording"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('conference_session_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('conference_session_edit', 'temp');
								}
							}
							unset($result);
						}

					// update conference center greetings
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/conference_centers/app_config.php")){
							$sql = "select conference_center_uuid, conference_center_greeting from v_conference_centers ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and conference_center_greeting like :conference_center_greeting ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['conference_center_greeting'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									// update db record
									$array['conference_centers'][$index]['conference_center_uuid'] = $row["conference_center_uuid"];
									$array['conference_centers'][$index]['conference_center_greeting'] = str_replace($original_domain_name, $domain_name, $row["conference_center_greeting"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('conference_center_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('conference_center_edit', 'temp');
								}
							}
							unset($result);
						}

					// update ivr menu greetings
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ivr_menu/app_config.php")){
							$sql = "select ivr_menu_uuid, ivr_menu_greet_long, ivr_menu_greet_short from v_ivr_menus ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and ( ";
							$sql .= "ivr_menu_greet_long like :ivr_menu_greet_long or ";
							$sql .= "ivr_menu_greet_short like :ivr_menu_greet_short ";
							$sql .= ") ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['ivr_menu_greet_long'] = '%'.$original_domain_name.'%';
							$parameters['ivr_menu_greet_short'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									// update db record
									$array['ivr_menus'][$index]['ivr_menu_uuid'] = $row["ivr_menu_uuid"];
									$array['ivr_menus'][$index]['ivr_menu_greet_long'] = str_replace($original_domain_name, $domain_name, $row["ivr_menu_greet_long"]);
									$array['ivr_menus'][$index]['ivr_menu_greet_short'] = str_replace($original_domain_name, $domain_name, $row["ivr_menu_greet_short"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('ivr_menu_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('ivr_menu_edit', 'temp');
								}
							}
							unset($result);
						}

					// update ivr menu option parameters
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ivr_menu/app_config.php")){
							$sql = "select ivr_menu_option_uuid, ivr_menu_option_param from v_ivr_menu_options ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and ivr_menu_option_param like :ivr_menu_option_param ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['ivr_menu_option_param'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									// update db record
									$array['ivr_menu_options'][$index]['ivr_menu_option_uuid'] = $row["ivr_menu_option_uuid"];
									$array['ivr_menu_options'][$index]['ivr_menu_option_param'] = str_replace($original_domain_name, $domain_name, $row["ivr_menu_option_param"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('ivr_menu_option_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('ivr_menu_option_edit', 'temp');
								}
							}
							unset($result);
						}

					// update call center queue record templates
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_center/app_config.php")){
							$sql = "select call_center_queue_uuid, queue_record_template from v_call_center_queues ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and queue_record_template like :queue_record_template ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['queue_record_template'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									// update db record
									$array['call_center_queues'][$index]['call_center_queue_uuid'] = $row["call_center_queue_uuid"];
									$array['call_center_queues'][$index]['queue_record_template'] = str_replace($original_domain_name, $domain_name, $row["queue_record_template"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('call_center_queue_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('call_center_queue_edit', 'temp');
								}
							}
							unset($result);
						}

					// update call center agent contacts
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_center/app_config.php")){
							$sql = "select call_center_agent_uuid, agent_contact from v_call_center_agents ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and agent_contact like :agent_contact ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['agent_contact'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									// update db record
									$array['call_center_agents'][$index]['call_center_agent_uuid'] = $row["call_center_agent_uuid"];
									$array['call_center_agents'][$index]['agent_contact'] = str_replace($original_domain_name, $domain_name, $row["agent_contact"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('call_center_agent_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('call_center_agent_edit', 'temp');
								}
							}
							unset($result);
						}

					// update call flows data, alternate-data and contexts
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_flows/app_config.php")){
							$sql = "select call_flow_uuid, call_flow_data, call_flow_alternate_data, call_flow_context from v_call_flows ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and ( ";
							$sql .= "call_flow_data like :call_flow_data or ";
							$sql .= "call_flow_alternate_data like :call_flow_alternate_data or ";
							$sql .= "call_flow_context like :call_flow_context ";
							$sql .= ") ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['call_flow_data'] = '%'.$original_domain_name.'%';
							$parameters['call_flow_alternate_data'] = '%'.$original_domain_name.'%';
							$parameters['call_flow_context'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									// update db record
									$array['call_flows'][$index]['call_flow_uuid'] = $row["call_flow_uuid"];
									$array['call_flows'][$index]['call_flow_data'] = str_replace($original_domain_name, $domain_name, $row["call_flow_data"]);
									$array['call_flows'][$index]['call_flow_alternate_data'] = str_replace($original_domain_name, $domain_name, $row["call_flow_alternate_data"]);
									$array['call_flows'][$index]['call_flow_context'] = str_replace($original_domain_name, $domain_name, $row["call_flow_context"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('call_flow_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('call_flow_edit', 'temp');
								}
							}
							unset($result);
						}

					// update ring group context, forward destination, timeout data
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ring_groups/app_config.php")){
							$sql = "select ring_group_uuid, ring_group_context, ring_group_forward_destination, ring_group_timeout_data from v_ring_groups ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and ( ";
							$sql .= "ring_group_context like :ring_group_context or ";
							$sql .= "ring_group_forward_destination like :ring_group_forward_destination or ";
							$sql .= "ring_group_timeout_data like :ring_group_timeout_data ";
							$sql .= ") ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['ring_group_context'] = '%'.$original_domain_name.'%';
							$parameters['ring_group_forward_destination'] = '%'.$original_domain_name.'%';
							$parameters['ring_group_timeout_data'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
								foreach ($result as $index => &$row) {
									// update db record
									$array['ring_groups'][$index]['ring_group_uuid'] = $row["ring_group_uuid"];
									$array['ring_groups'][$index]['ring_group_context'] = str_replace($original_domain_name, $domain_name, $row["ring_group_context"]);
									$array['ring_groups'][$index]['ring_group_forward_destination'] = str_replace($original_domain_name, $domain_name, $row["ring_group_forward_destination"]);
									$array['ring_groups'][$index]['ring_group_timeout_data'] = str_replace($original_domain_name, $domain_name, $row["ring_group_timeout_data"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('ring_group_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('ring_group_edit', 'temp');
								}
							}
							unset($result);
						}

					// update device lines server address, outbound proxy
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/devices/app_config.php")){
							$sql = "select device_line_uuid, server_address, outbound_proxy_primary, outbound_proxy_secondary from v_device_lines ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and ( ";
							$sql .= "server_address like :server_address or ";
							$sql .= "outbound_proxy_primary like :outbound_proxy_primary or ";
							$sql .= "outbound_proxy_secondary like :outbound_proxy_secondary ";
							$sql .= ") ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['server_address'] = '%'.$original_domain_name.'%';
							$parameters['outbound_proxy_primary'] = '%'.$original_domain_name.'%';
							$parameters['outbound_proxy_secondary'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									// update db record
									$array['device_lines'][$index]['device_line_uuid'] = $row["device_line_uuid"];
									$array['device_lines'][$index]['server_address'] = str_replace($original_domain_name, $domain_name, $row["server_address"]);
									$array['device_lines'][$index]['outbound_proxy_primary'] = str_replace($original_domain_name, $domain_name, $row["outbound_proxy_primary"]);
									$array['device_lines'][$index]['outbound_proxy_secondary'] = str_replace($original_domain_name, $domain_name, $row["outbound_proxy_secondary"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('device_line_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('device_line_edit', 'temp');
								}
							}
							unset($result);
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
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/dialplans/app_config.php")){
							$sql = "select dialplan_detail_uuid, dialplan_detail_data from v_dialplan_details ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and dialplan_detail_data like :dialplan_detail_data ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['dialplan_detail_data'] = '%'.$original_domain_name.'%';
							$database = new database;
							$result = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							if (is_array($result) && sizeof($result) != 0) {
								foreach ($result as $index => &$row) {
									$array['dialplan_detail'][$index]['dialplan_detail_uuid'] = $row["dialplan_detail_uuid"];
									$array['dialplan_detail'][$index]['dialplan_detail_data'] = str_replace($original_domain_name, $domain_name, $row["dialplan_detail_data"]);
								}
								if (is_array($array) && sizeof($array) != 0) {
									$p = new permissions;
									$p->add('dialplan_detail_edit', 'temp');

									$database = new database;
									$database->app_name = 'domain_settings';
									$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
									$database->save($array);
									unset($array);

									$p->delete('dialplan_detail_edit', 'temp');
								}
							}
							unset($result);
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
							$sql .= "var_value = :var_value ";
							$sql .= "where var_name = 'domain' ";
							$parameters['var_value'] = $domain_name;
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->execute($sql, $parameters);
							unset($sql, $parameters);
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
				message::add($text['message-update']);
				if (!permission_exists('domain_add')) { //admin, updating own domain
					header("Location: domain_edit.php");
				}
				else {
					header("Location: domains.php"); //superadmin
				}
			}
			if ($action == "add") {
				message::add($text['message-add']);
				header("Location: domains.php");
			}
			return;
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form (admin won't have domain_add permissions, but domain_uuid will already be set above)
	if ((count($_GET) > 0 || (!permission_exists('domain_add') && $domain_uuid != '')) && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_domains ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$domain_name = strtolower($row["domain_name"]);
			$domain_enabled = $row["domain_enabled"];
			$domain_description = $row["domain_description"];
		}
		unset($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-domain-edit'];
	}
	if ($action == "add") {
		$document['title'] = $text['title-domain-add'];
	}

//copy settings javascript
	if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	var fade_speed = 400;\n";
		echo "	function show_domains() {\n";
		echo "		document.getElementById('action').value = 'copy';\n";
		echo "		$('#button_copy').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_back').fadeIn(fade_speed);\n";
		echo "			$('#target_domain_uuid').fadeIn(fade_speed);\n";
		echo "			$('#button_paste').fadeIn(fade_speed);\n";
		echo "		});";
		echo "	}";
		echo "	function hide_domains() {\n";
		echo "		document.getElementById('action').value = '';\n";
		echo "		$('#button_back').fadeOut(fade_speed);\n";
		echo "		$('#target_domain_uuid').fadeOut(fade_speed);\n";
		echo "		$('#button_paste').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_copy').fadeIn(fade_speed);\n";
		echo "			document.getElementById('target_domain_uuid').selectedIndex = 0;\n";
		echo "		});\n";
		echo "	}\n";
		echo "\n";
		echo "	$( document ).ready(function() {\n";
		echo "		$('#domain_setting_search').focus();\n";
		if ($search == '') {
			echo "		// scroll to previous category\n";
			echo "		var category_span_id;\n";
			echo "		var url = document.location.href;\n";
			echo "		var hashindex = url.indexOf('#');\n";
			echo "		if (hashindex == -1) { }\n";
			echo "		else {\n";
			echo "			category_span_id = url.substr(hashindex + 1);\n";
			echo "		}\n";
			echo "		if (category_span_id) {\n";
			echo "			$('#page').animate({scrollTop: $('#anchor_'+category_span_id).offset().top - 200}, 'slow');\n";
			echo "		}\n";
		}
		echo "	});\n";
		echo "</script>";
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
	//if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
	//	echo "		<input type='button' class='btn' id='button_copy' alt='".$text['button-copy']."' onclick='show_domains();' value='".$text['button-copy']."'>";
	//	echo "		<input type='button' class='btn' style='display: none;' id='button_back' alt='".$text['button-back']."' onclick='hide_domains();' value='".$text['button-back']."'> ";
	//	echo "		<select class='formfld' style='display: none; width: auto;' name='target_domain_uuid' id='target_domain_uuid'>\n";
	//	echo "			<option value=''>Select Domain...</option>\n";
	//	foreach ($_SESSION['domains'] as $domain) {
	//		echo "		<option value='".escape($domain["domain_uuid"])."'>".escape($domain["domain_name"])."</option>\n";
	//	}
	//	echo "		</select>\n";
	//	echo "		<input type='button' class='btn' id='button_paste' style='display: none;' alt='".$text['button-paste']."' value='".$text['button-paste']."' onclick=\"$('#frm').attr('action', 'domain_settings.php?search='+$('#domain_setting_search').val()).submit();\">";
	//}
	if (permission_exists('domain_export')) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-export']."' onclick=\"window.location='".PROJECT_PATH."/app/domain_export/index.php?id=".escape($domain_uuid)."'\" value='".$text['button-export']."'>\n";
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
	echo "	<input class='formfld' type='text' name='domain_name' maxlength='255' value=\"".escape($domain_name)."\">\n";
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
	echo "	<input class='formfld' type='text' name='domain_description' maxlength='255' value=\"".escape($domain_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>\n";
	}
	echo "			<br />";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br /><br />";

	echo "</form>";

	if ($action == "update" && permission_exists('domain_setting_view')) {
		require "domain_settings.php";
	}

//include the footer
	require_once "resources/footer.php";

?>
