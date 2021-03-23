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
 Portions created by the Initial Developer are Copyright (C) 2008-2020
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
	if (permission_exists('domain_all') && permission_exists('domain_edit')) {
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
	if (!permission_exists('domain_add') || (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/") && !permission_exists('domain_all'))) {
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
		$domain_name = strtolower($_POST["domain_name"]);
		$domain_enabled = $_POST["domain_enabled"];
		$domain_description = $_POST["domain_description"];
	}

//process the data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the domain_uuid
			if ($action == "update" && $_POST["domain_uuid"]) {
				$domain_uuid = $_POST["domain_uuid"];
			}

		//delete the domain
			if (permission_exists('domain_delete')) {
				if ($_POST['action'] == 'delete' && is_uuid($domain_uuid)) {
					//prepare
						$array[0]['checked'] = 'true';
						$array[0]['uuid'] = $domain_uuid;
					//delete
						$obj = new domains;
						$obj->delete($array);
					//redirect
						header('Location: domains.php');
						exit;
				}
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: domains.php');
				exit;
			}

		//check for all required data
			$msg = '';
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
					$sql .= "where lower(domain_name) = :domain_name ";
					$parameters['domain_name'] = $domain_name;
					$database = new database;
					$num_rows = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);

					if ($num_rows == 0) {

						//add the domain name
							$domain_enabled = 'true';
							$domain_uuid = uuid();
							$array['domains'][0]['domain_uuid'] = $domain_uuid;
							$array['domains'][0]['domain_name'] = $domain_name;
							$array['domains'][0]['domain_enabled'] = $domain_enabled;
							$array['domains'][0]['domain_description'] = $domain_description;
							$database = new database;
							$database->app_name = 'domains';
							$database->app_uuid = '8b91605b-f6d2-42e6-a56d-5d1ded01bb44';
							$database->save($array);

						//add dialplans to the domain
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/dialplans/app_config.php")) {
								//import the dialplans
								$dialplan = new dialplan;
								$dialplan->import($array['domains']);
								unset($array);

								//add xml for each dialplan where the dialplan xml is empty
								$dialplans = new dialplan;
								$dialplans->source = "details";
								$dialplans->destination = "database";
								$dialplans->context = $domain_name;
								$dialplans->is_empty = "dialplan_xml";
								$array = $dialplans->xml();
							}
					}
					else {
						message::add($text['message-domain_exists'],'negative');
						header("Location: domains.php");
						exit;
					}
				}

				if ($action == "update" && permission_exists('domain_edit')) {

					//get original domain name
						$sql = "select domain_name from v_domains ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$original_domain_name = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

					//update domain name, description
						$array['domains'][0]['domain_uuid'] = $domain_uuid;
						$array['domains'][0]['domain_name'] = $domain_name;
						$array['domains'][0]['domain_enabled'] = $domain_enabled;
						$array['domains'][0]['domain_description'] = $domain_description;
						$database = new database;
						$database->app_name = 'domains';
						$database->app_uuid = '8b91605b-f6d2-42e6-a56d-5d1ded01bb44';
						$database->save($array);

					//add dialplans to the domain
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/dialplans/app_config.php")) {
							//import the dialplans
							$dialplan = new dialplan;
							$dialplan->import($array['domains']);
							unset($array);

							//add xml for each dialplan where the dialplan xml is empty
							$dialplans = new dialplan;
							$dialplans->source = "details";
							$dialplans->destination = "database";
							$dialplans->context = $domain_name;
							$dialplans->is_empty = "dialplan_xml";
							$array = $dialplans->xml();
						}

					if ($original_domain_name != $domain_name) {

						//update dialplans
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/dialplans/app_config.php")) {
								$sql = "update v_dialplans set ";
								$sql .= "dialplan_context = replace(dialplan_context, :domain_name_old, :domain_name_new), ";
								$sql .= "dialplan_xml = replace(dialplan_xml, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

								$sql = "update v_dialplan_details set ";
								$sql .= "dialplan_detail_data = replace(dialplan_detail_data, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update destinations
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/destinations/app_config.php")) {
								$sql = "update v_destinations set ";
								$sql .= "destination_data = replace(destination_data, :destination_data_old, :destination_data_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['destination_data_old'] = $original_domain_name;
								$parameters['destination_data_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update extensions (accountcode, user_context, dial_domain)
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/extensions/app_config.php")) {
								$sql = "update v_extensions set ";
								$sql .= "user_context = replace(user_context, :domain_name_old, :domain_name_new), ";
								$sql .= "accountcode = replace(accountcode, :domain_name_old, :domain_name_new), ";
								$sql .= "dial_domain = replace(dial_domain, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update ivr_menus (ivr_menu_context, ivr_menu_greet_long, ivr_menu_greet_short) and ivr_menu_options (ivr_menu_option_param)
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ivr_menus/app_config.php")) {
								$sql = "update v_ivr_menus set ";
								$sql .= "ivr_menu_context = replace(ivr_menu_context, :domain_name_old, :domain_name_new), ";
								$sql .= "ivr_menu_greet_long = replace(ivr_menu_greet_long, :domain_name_old, :domain_name_new), ";
								$sql .= "ivr_menu_greet_short = replace(ivr_menu_greet_short, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

								$sql = "update v_ivr_menu_options set ";
								$sql .= "ivr_menu_option_param = replace(ivr_menu_option_param, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update ring_groups (ring_group_context, ring_group_forward_destination, ring_group_timeout_data)
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ring_groups/app_config.php")) {
								$sql = "update v_ring_groups set ";
								$sql .= "ring_group_context = replace(ring_group_context, :domain_name_old, :domain_name_new), ";
								$sql .= "ring_group_forward_destination = replace(ring_group_forward_destination, :domain_name_old, :domain_name_new), ";
								$sql .= "ring_group_timeout_data = replace(ring_group_timeout_data, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update cdr records (domain_name, context)
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/xml_cdr/app_config.php")){
								$sql = "update v_xml_cdr set ";
								$sql .= "domain_name = :domain_name_new ";
								$sql .= "where domain_name = :domain_name_old ";
								$sql .= "and domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

								$sql = "update v_xml_cdr set ";
								$sql .= "context = replace(user_context, :context_old, :context_new), ";
								$sql .= "where context = :context_old ";
								$sql .= "and domain_uuid = :domain_uuid ";
								$parameters['context_old'] = $original_domain_name;
								$parameters['context_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update billing, if installed
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/billing/app_config.php")){
								$sql = "update v_billings set ";
								$sql .= "type_value = :type_value_new ";
								$sql .= "where type_value = :type_value_old ";
								$sql .= "and domain_uuid = :domain_uuid ";
								$parameters['type_value_old'] = $original_domain_name;
								$parameters['type_value_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update conference session recording paths
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/conference_centers/app_config.php")) {
								$sql = "update v_conference_sessions set ";
								$sql .= "recording = replace(recording, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update conference center greetings
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/conference_centers/app_config.php")) {
								$sql = "update v_conference_centers set ";
								$sql .= "conference_center_greeting = replace(conference_center_greeting, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update call center queue record templates
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_center/app_config.php")) {
								$sql = "update v_call_center_queues set ";
								$sql .= "queue_record_template = replace(queue_record_template, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update call center agent contacts
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_center/app_config.php")) {
								$sql = "update v_call_center_agents set ";
								$sql .= "agent_contact = replace(agent_contact, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update call flows data, alternate-data and contexts
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_flows/app_config.php")) {
								$sql = "update v_call_flows set ";
								$sql .= "call_flow_data = replace(call_flow_data, :domain_name_old, :domain_name_new), ";
								$sql .= "call_flow_alternate_data = replace(call_flow_alternate_data, :domain_name_old, :domain_name_new), ";
								$sql .= "call_flow_context = replace(call_flow_context, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update device lines server_address, server_address_primary, server_address_secondary, outbound_proxy_primary, outbound_proxy_secondary
							if (file_exists($_SERVER["PROJECT_ROOT"]."/app/devices/app_config.php")) {
								$sql = "update v_device_lines set ";
								$sql .= "server_address = replace(server_address, :domain_name_old, :domain_name_new), ";
								$sql .= "server_address_primary = replace(server_address_primary, :domain_name_old, :domain_name_new), ";
								$sql .= "server_address_secondary = replace(server_address_secondary, :domain_name_old, :domain_name_new), ";
								$sql .= "outbound_proxy_primary = replace(outbound_proxy_primary, :domain_name_old, :domain_name_new), ";
								$sql .= "outbound_proxy_secondary = replace(outbound_proxy_secondary, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//rename switch/storage/voicemail/default/[domain] (folder)
							if (isset($_SESSION['switch']['voicemail']['dir']) && file_exists($_SESSION['switch']['voicemail']['dir']."/default/".$original_domain_name)) {
								@rename($_SESSION['switch']['voicemail']['dir']."/default/".$original_domain_name, $_SESSION['switch']['voicemail']['dir']."/default/".$domain_name); // folder
							}

						//rename switch/storage/fax/[domain] (folder)
							if (isset($_SESSION['switch']['storage']['dir']) && file_exists($_SESSION['switch']['storage']['dir']."/fax/".$original_domain_name)) {
								@rename($_SESSION['switch']['storage']['dir']."/fax/".$original_domain_name, $_SESSION['switch']['storage']['dir']."/fax/".$domain_name); // folder
							}

						//rename switch/conf/dialplan/[domain] (folder/file)
							if (isset($_SESSION['switch']['dialplan']['dir'])) {
								if (file_exists($_SESSION['switch']['dialplan']['dir']."/".$original_domain_name)) {
									@rename($_SESSION['switch']['dialplan']['dir']."/".$original_domain_name, $_SESSION['switch']['dialplan']['dir']."/".$domain_name); // folder
								}
								if (file_exists($_SESSION['switch']['dialplan']['dir']."/".$original_domain_name.".xml")) {
									@rename($_SESSION['switch']['dialplan']['dir']."/".$original_domain_name.".xml", $_SESSION['switch']['dialplan']['dir']."/".$domain_name.".xml"); // file
								}
							}

						//rename switch/conf/dialplan/public/[domain] (folder/file)
							if (isset($_SESSION['switch']['dialplan']['dir'])) {
								if (file_exists($_SESSION['switch']['dialplan']['dir']."/public/".$original_domain_name)) {
									@rename($_SESSION['switch']['dialplan']['dir']."/public/".$original_domain_name, $_SESSION['switch']['dialplan']['dir']."/public/".$domain_name); // folder
								}
								if (file_exists($_SESSION['switch']['dialplan']['dir']."/public/".$original_domain_name.".xml")) {
									@rename($_SESSION['switch']['dialplan']['dir']."/public/".$original_domain_name.".xml", $_SESSION['switch']['dialplan']['dir']."/public/".$domain_name.".xml"); // file
								}
							}

						//rename switch/conf/directory/[domain] (folder/file)
							if (isset($_SESSION['switch']['extensions']['dir'])) {
								if (file_exists($_SESSION['switch']['extensions']['dir']."/".$original_domain_name)) {
									@rename($_SESSION['switch']['extensions']['dir']."/".$original_domain_name, $_SESSION['switch']['extensions']['dir']."/".$domain_name); // folder
								}
								if (file_exists($_SESSION['switch']['extensions']['dir']."/".$original_domain_name.".xml")) {
									@rename($_SESSION['switch']['extensions']['dir']."/".$original_domain_name.".xml", $_SESSION['switch']['extensions']['dir']."/".$domain_name.".xml"); // file
								}
							}

						//rename switch/recordings/[domain] (folder)
							if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'])) {
								$switch_recordings_dir = str_replace("/".$_SESSION["domain_name"], "", $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']);
								if (file_exists($switch_recordings_dir."/".$original_domain_name)) {
									@rename($switch_recordings_dir."/".$original_domain_name, $switch_recordings_dir."/".$domain_name); // folder
								}
							}

						//update dialplan, dialplan/public xml files
							$dialplan_xml = file_get_contents($_SESSION['switch']['dialplan']['dir']."/".$domain_name.".xml");
							$dialplan_xml = str_replace($original_domain_name, $domain_name, $dialplan_xml);
							file_put_contents($_SESSION['switch']['dialplan']['dir']."/".$domain_name.".xml", $dialplan_xml);
							unset($dialplan_xml);

							$dialplan_public_xml = file_get_contents($_SESSION['switch']['dialplan']['dir']."/public/".$domain_name.".xml");
							$dialplan_public_xml = str_replace($original_domain_name, $domain_name, $dialplan_public_xml);
							file_put_contents($_SESSION['switch']['dialplan']['dir']."/public/".$domain_name.".xml", $dialplan_public_xml);
							unset($dialplan_public_xml);

						//update session domain name
							$_SESSION['domains'][$domain_uuid]['domain_name'] = $domain_name;

						//recreate dialplan and extension xml files
							if (is_readable($_SESSION['switch']['extensions']['dir'])) {
								require_once $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/resources/classes/extension.php";
								$extension = new extension;
								$extension->xml();
							}

						//if single-tenant and variables exist, update variables > domain value to match new domain
							if (count($_SESSION['domains']) == 1 && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/vars/")) {
								$sql = "update v_vars set ";
								$sql .= "var_value = :var_value ";
								$sql .= "where var_name = 'domain' ";
								$parameters['var_value'] = $domain_name;
								$database = new database;
								$database->app_name = 'domains';
								$database->app_uuid = '8b91605b-f6d2-42e6-a56d-5d1ded01bb44';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}
					}
				}

			//clear the domains session array to update it
				unset($_SESSION["domains"]);
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
				exit;
			}
	}

//pre-populate the form (admin won't have domain_add permissions, but domain_uuid will already be set above)
	if ((count($_GET) > 0 || (!permission_exists('domain_add') && $domain_uuid != '')) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		$sql .= "domain_uuid, ";
		$sql .= "domain_name, ";
		$sql .= "cast(domain_enabled as text), ";
		$sql .= "domain_description ";
		$sql .= "from v_domains ";
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

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

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
		echo "	$(document).ready(function() {\n";
		echo "		$('#domain_setting_search').trigger('focus');\n";
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
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-domain']."</b>";
	}
	if ($action == "add") {
		echo "<b>".$text['header-domain-add']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";

	if (permission_exists('domain_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'domains.php']);
	}
	if ($action == "update" && permission_exists('domain_setting_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>$_SESSION['theme']['button_icon_settings'],'id'=>'btn_back','style'=>'margin-right: 2px;','link'=>PROJECT_PATH.'/core/domain_settings/domain_settings.php?id='.urlencode($domain_uuid)]);
	}
	if (permission_exists('domain_delete') && is_array($_SESSION['domains']) && @sizeof($_SESSION['domains']) > 1 && $domain_uuid != $_SESSION['domain_uuid']) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'onclick'=>"modal_open('modal-delete-domain','btn_delete_domain');"]);
	}
	if (permission_exists("domain_select") && is_array($_SESSION['domains']) && @sizeof($_SESSION['domains']) > 1) {
		echo "<select id='domains' class='formfld' style='width: auto;' onchange=\"window.location.href='?id=' + document.getElementById('domains').options[document.getElementById('domains').selectedIndex].value;\">\n";
		foreach ($_SESSION['domains'] as $domain) {
			$selected = $domain["domain_uuid"] == $domain_uuid ? "selected='selected'" : null;
			echo "	<option value='".escape($domain["domain_uuid"])."' ".$selected.">".escape($domain["domain_name"])."</option>\n";
		}
		echo "</select>";
	}
	if (permission_exists('domain_export')) {
		echo button::create(['type'=>'button','label'=>$text['button-export'],'icon'=>$_SESSION['theme']['button_icon_export'],'link'=>PROJECT_PATH."/app/domain_export/index.php?id=".urlencode($domain_uuid)]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 3px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('domain_delete') && is_array($_SESSION['domains']) && @sizeof($_SESSION['domains']) > 1 && $domain_uuid != $_SESSION['domain_uuid']) {
		echo modal::create(['id'=>'modal-delete-domain','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete_domain','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	if ($action == "update") {
		echo $text['description-domain-edit']."\n";
	}
	if ($action == "add") {
		echo $text['description-domain-add']."\n";
	}
	echo "<br /><br />\n";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";

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

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
