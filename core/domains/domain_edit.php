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
	if (!permission_exists('domain_all') && !permission_exists('domain_edit')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the defaults
	$domain_name = '';
	$domain_parent_uuid = '';
	$domain_description = '';

//the domains view holds the parent child relationship between domains, it is defined in
//core/domains/resources/database/views/domains.php and created by the schema update. when
//the view has not been created yet a user without domain_all is limited to their own domain.
	$domains_view_available = false;
	if ($database->select("select domain_uuid from view_domains limit 1 ", null, 'column') !== false) {
		$domains_view_available = true;
	}

//a user with domain_all manages every domain. a user without domain_all is limited to their
//own domain and the child domains below it in the parent child tree.
	$user_domain_uuid = $_SESSION['user']['domain_uuid'] ?? '';
	$domain_all_access = permission_exists('domain_all');
	$restrict_domains = !$domain_all_access && is_uuid($user_domain_uuid);

//get the domain uuids the user is allowed to manage
	$allowed_domain_uuids = [];
	if ($restrict_domains) {
		if ($domains_view_available) {
			$sql = "select domain_uuid from view_domains ";
			$sql .= "where :user_domain_uuid = any(domain_uuid_path) ";
			$result = $database->select($sql, ['user_domain_uuid' => $user_domain_uuid], 'all');
			if (is_array($result)) {
				$allowed_domain_uuids = array_column($result, 'domain_uuid');
			}
			unset($sql, $result);
		}
		else {
			$allowed_domain_uuids[] = $user_domain_uuid;
		}
	}

//a user that can add domains manages their own tree, any other user edits their own domain
	$domain_tree_access = !$domain_all_access && permission_exists('domain_add');

//action add or update
	if ($domain_all_access || $domain_tree_access) {
		if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
			$action = "update";
			$domain_uuid = $_REQUEST["id"];
		}
		else {
			$action = "add";
		}
	}
	else {
		//admin editing own domain/settings
		$domain_uuid = $_SESSION['domain_uuid'];
		$action = "update";
	}

//the domain must be in the domains the user is allowed to manage
	if ($action == "update" && $restrict_domains && !in_array($domain_uuid, $allowed_domain_uuids, true)) {
		echo "access denied";
		exit;
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$domain_name = strtolower(is_string($_POST["domain_name"] ?? null) ? $_POST["domain_name"] : '');
		$domain_parent_uuid = is_string($_POST["domain_parent_uuid"] ?? null) ? $_POST["domain_parent_uuid"] : '';
		$domain_enabled = is_string($_POST["domain_enabled"] ?? null) ? $_POST["domain_enabled"] : 'true';
		$domain_description = is_string($_POST["domain_description"] ?? null) ? $_POST["domain_description"] : '';
	}

// Set variables from http GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', is_string($_GET['order_by'] ?? null) ? $_GET['order_by'] : '');
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = is_string($_GET['search'] ?? null) ? $_GET['search'] : '';
	$show = is_string($_GET['show'] ?? null) ? $_GET['show'] : '';

// Build the query string
	$url_params = [];
	if (!empty($page)) {
		$url_params['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$url_params['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$url_params['order'] = $order;
	}
	if (!empty($search)) {
		$url_params['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('domain_all')) {
		$url_params['show'] = $show;
	}
	$query_string = http_build_query($url_params);

//process the data
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: domains.php'.($query_string ? '?'.$query_string : ''));
				exit;
			}

		//get the domain_uuid
			if ($action == "update" && $domain_all_access && !empty($_POST["domain_uuid"]) && is_uuid($_POST["domain_uuid"])) {
				$domain_uuid = $_POST["domain_uuid"];
			}

		//delete the domain
			if (permission_exists('domain_delete') && !empty($_POST['action']) && $_POST['action'] == 'delete' && is_uuid($domain_uuid)) {
				//prepare
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $domain_uuid;

				//delete
				$obj = new domains;
				$obj->delete($array);

				//reset the cache
				$cache = new cache;
				$cache->flush();

				//redirect
				header('Location: domains.php'.($query_string ? '?'.$query_string : ''));
				exit;
			}

		//check for all required data
			$msg = '';
			if (empty($domain_name)) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			//if (empty($domain_description)) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }

		//check the domain name, it is used in file and directory paths
			if (!empty($domain_name) && !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $domain_name)) {
				$msg .= $text['message-domain_name_invalid']."<br>\n";
			}

		//validate the parent domain, it must exist and be manageable by the user
			if (!empty($domain_parent_uuid)) {
				if (!is_uuid($domain_parent_uuid)) {
					$msg .= $text['message-parent_invalid']."<br>\n";
				}
				else {
					$sql = "select count(*) from v_domains ";
					$sql .= "where domain_uuid = :domain_parent_uuid ";
					$parameters['domain_parent_uuid'] = $domain_parent_uuid;
					if ($database->select($sql, $parameters, 'column') == 0) {
						$msg .= $text['message-parent_invalid']."<br>\n";
					}
					unset($sql, $parameters);

					if ($restrict_domains && !in_array($domain_parent_uuid, $allowed_domain_uuids, true)) {
						$msg .= ($text['message-access_denied'] ?? 'Access denied')."<br>\n";
					}

					//a domain can not be its own parent or the parent of its own parent
						if ($action == 'update' && $domain_parent_uuid == $domain_uuid) {
							$msg .= $text['message-parent_invalid']."<br>\n";
						}
						elseif ($action == 'update' && $domains_view_available) {
							$sql = "select count(*) from view_domains ";
							$sql .= "where domain_uuid = :domain_parent_uuid ";
							$sql .= "and :domain_uuid = any(domain_uuid_path) ";
							$parameters['domain_parent_uuid'] = $domain_parent_uuid;
							$parameters['domain_uuid'] = $domain_uuid;
							if ($database->select($sql, $parameters, 'column') > 0) {
								$msg .= $text['message-parent_invalid']."<br>\n";
							}
							unset($sql, $parameters);
						}
				}
			}

		//a domain added by a user without domain_all must be below a parent domain
			if ($action == 'add' && $restrict_domains && empty($domain_parent_uuid)) {
				$msg .= $text['message-parent_required']."<br>\n";
			}

		//check for a duplicate domain name
			if ($action == 'add') {
				$sql = "select count(*) from v_domains ";
				$sql .= "where domain_name = :domain_name ";
				$parameters['domain_name'] = $domain_name;
				$num_rows = $database->select($sql, $parameters, 'column');
				if ($num_rows > 0) {
					$msg .= $text['message-duplicate']."<br>\n";
				}
				unset($sql, $parameters, $num_rows);
			}

		//show the message if it's not empty
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

		//add or update the database
			if (empty($_POST["persistformvar"])) {

				//add a domain to the database
				if ($action == "add" && permission_exists('domain_add')) {
					$sql = "select count(domain_uuid) from v_domains ";
					$sql .= "where lower(domain_name) = :domain_name ";
					$parameters['domain_name'] = $domain_name;
					$num_rows = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);

					if ($num_rows == 0) {

						//add the domain uuid
						$domain_uuid = uuid();

						//build the domain array
						$array['domains'][0]['domain_uuid'] = $domain_uuid;
						$array['domains'][0]['domain_name'] = $domain_name;
						$array['domains'][0]['domain_enabled'] = $domain_enabled;
						if (!empty($domain_parent_uuid)) {
							$array['domains'][0]['domain_parent_uuid'] = $domain_parent_uuid;
						}
						$array['domains'][0]['domain_description'] = $domain_description;

						//create a copy of the domain array as the database save method empties the array that we still need.
						$domain_array = $array;

						//add the new domain
						$database->save($array);

						//add dialplans to the domain
						if (file_exists(dirname(__DIR__, 2)."/app/dialplans/app_config.php")) {
							//import the dialplans
							$dialplan = new dialplan;
							$dialplan->import($domain_array['domains']);
							unset($array);

							//add xml for each dialplan where the dialplan xml is empty
							$dialplans = new dialplan;
							$dialplans->source = "details";
							$dialplans->destination = "database";
							$dialplans->context = $domain_name;
							$dialplans->is_empty = "dialplan_xml";
							$array = $dialplans->xml();
						}

						//create the recordings directory for the new domain.
						if (!empty($settings->get('switch', 'recordings')) && !empty($settings->get('switch', 'recordings'))) {
							if (!file_exists($settings->get('switch', 'recordings')."/".$domain_name)) {
								mkdir($settings->get('switch', 'recordings')."/".$domain_name, 0770);
							}
						}

						//create the voicemail directory for the new domain.
						if (!empty($settings->get('switch', 'voicemail')) && !empty($settings->get('switch', 'voicemail'))) {
							if (!file_exists($settings->get('switch', 'voicemail')."/default/".$domain_name)) {
								mkdir($settings->get('switch', 'voicemail')."/default/".$domain_name, 0770);
							}
						}

					}
					else {
						message::add($text['message-domain_exists'],'negative');
						header("Location: domains.php".($query_string ? '?'.$query_string : ''));
						exit;
					}
				}

				//update the domain
				if ($action == "update" && permission_exists('domain_edit')) {

					//get original domain name
						$sql = "select domain_name from v_domains ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$original_domain_name = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

					//update domain name, parent and description
						$array['domains'][0]['domain_uuid'] = $domain_uuid;
						$array['domains'][0]['domain_name'] = $domain_name;
						$array['domains'][0]['domain_enabled'] = $domain_enabled;
						if ($domain_all_access) {
							//an empty parent clears the parent, a user without domain_all can only
							//move a domain to another parent within their own tree
							$array['domains'][0]['domain_parent_uuid'] = !empty($domain_parent_uuid) ? $domain_parent_uuid : null;
						}
						elseif (!empty($domain_parent_uuid)) {
							$array['domains'][0]['domain_parent_uuid'] = $domain_parent_uuid;
						}
						$array['domains'][0]['domain_description'] = $domain_description;
						$database->save($array);

					//add dialplans to the domain
						if (file_exists(dirname(__DIR__, 2)."/app/dialplans/app_config.php")) {
							//import the dialplans
							$dialplan = new dialplan;
							$dialplan->import($array['domains'] ?? null);
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
							if (file_exists(dirname(__DIR__, 2)."/app/dialplans/app_config.php")) {
								$sql = "update v_dialplans set ";
								$sql .= "dialplan_context = replace(dialplan_context, :domain_name_old, :domain_name_new), ";
								$sql .= "dialplan_xml = replace(dialplan_xml, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

								$sql = "update v_dialplan_details set ";
								$sql .= "dialplan_detail_data = replace(dialplan_detail_data, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update destinations
							if (file_exists(dirname(__DIR__, 2)."/app/destinations/app_config.php")) {
								$sql = "update v_destinations set ";
								$sql .= "destination_data = replace(destination_data, :destination_data_old, :destination_data_new), ";
								$sql .= "destination_conditions = replace(destination_conditions::text, :destination_data_old, :destination_data_new)::json, ";
								$sql .= "destination_actions = replace(destination_actions::text, :destination_data_old, :destination_data_new)::json ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['destination_data_old'] = $original_domain_name;
								$parameters['destination_data_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update extensions (accountcode, user_context, dial_domain)
							if (file_exists(dirname(__DIR__, 2)."/app/extensions/app_config.php")) {
								$sql = "update v_extensions set ";
								$sql .= "user_context = replace(user_context, :domain_name_old, :domain_name_new), ";
								$sql .= "accountcode = replace(accountcode, :domain_name_old, :domain_name_new), ";
								$sql .= "dial_domain = replace(dial_domain, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update ivr_menus (ivr_menu_context, ivr_menu_greet_long, ivr_menu_greet_short) and ivr_menu_options (ivr_menu_option_param)
							if (file_exists(dirname(__DIR__, 2)."/app/ivr_menus/app_config.php")) {
								$sql = "update v_ivr_menus set ";
								$sql .= "ivr_menu_context = replace(ivr_menu_context, :domain_name_old, :domain_name_new), ";
								$sql .= "ivr_menu_greet_long = replace(ivr_menu_greet_long, :domain_name_old, :domain_name_new), ";
								$sql .= "ivr_menu_greet_short = replace(ivr_menu_greet_short, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

								$sql = "update v_ivr_menu_options set ";
								$sql .= "ivr_menu_option_param = replace(ivr_menu_option_param, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update ring_groups (ring_group_context, ring_group_forward_destination, ring_group_timeout_data)
							if (file_exists(dirname(__DIR__, 2)."/app/ring_groups/app_config.php")) {
								$sql = "update v_ring_groups set ";
								$sql .= "ring_group_context = replace(ring_group_context, :domain_name_old, :domain_name_new), ";
								$sql .= "ring_group_forward_destination = replace(ring_group_forward_destination, :domain_name_old, :domain_name_new), ";
								$sql .= "ring_group_timeout_data = replace(ring_group_timeout_data, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update cdr records (domain_name, context)
							if (file_exists(dirname(__DIR__, 2)."/app/xml_cdr/app_config.php")){
								$sql = "update v_xml_cdr set ";
								$sql .= "domain_name = :domain_name_new ";
								$sql .= "where domain_name = :domain_name_old ";
								$sql .= "and domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

								$sql = "update v_xml_cdr set ";
								$sql .= "context = replace(user_context, :context_old, :context_new), ";
								$sql .= "where context = :context_old ";
								$sql .= "and domain_uuid = :domain_uuid ";
								$parameters['context_old'] = $original_domain_name;
								$parameters['context_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update conference session recording paths
							if (file_exists(dirname(__DIR__, 2)."/app/conference_centers/app_config.php")) {
								$sql = "update v_conference_sessions set ";
								$sql .= "recording = replace(recording, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update conference center greetings
							if (file_exists(dirname(__DIR__, 2)."/app/conference_centers/app_config.php")) {
								$sql = "update v_conference_centers set ";
								$sql .= "conference_center_greeting = replace(conference_center_greeting, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update call center queue record templates
							if (file_exists(dirname(__DIR__, 2)."/app/call_centers/app_config.php")) {
								$sql = "update v_call_center_queues set ";
								$sql .= "queue_record_template = replace(queue_record_template, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update call center agent contacts
							if (file_exists(dirname(__DIR__, 2)."/app/call_centers/app_config.php")) {
								$sql = "update v_call_center_agents set ";
								$sql .= "agent_contact = replace(agent_contact, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update call flows data, alternate-data and contexts
							if (file_exists(dirname(__DIR__, 2)."/app/call_flows/app_config.php")) {
								$sql = "update v_call_flows set ";
								$sql .= "call_flow_data = replace(call_flow_data, :domain_name_old, :domain_name_new), ";
								$sql .= "call_flow_alternate_data = replace(call_flow_alternate_data, :domain_name_old, :domain_name_new), ";
								$sql .= "call_flow_context = replace(call_flow_context, :domain_name_old, :domain_name_new) ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$parameters['domain_name_old'] = $original_domain_name;
								$parameters['domain_name_new'] = $domain_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//update device lines server_address, server_address_primary, server_address_secondary, outbound_proxy_primary, outbound_proxy_secondary
							if (file_exists(dirname(__DIR__, 2)."/app/devices/app_config.php")) {
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
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

						//rename switch/storage/voicemail/default/[domain] (folder)
							if (!empty($settings->get('switch', 'voicemail')) && file_exists($settings->get('switch', 'voicemail')."/default/".$original_domain_name)) {
								@rename($settings->get('switch', 'voicemail')."/default/".$original_domain_name, $settings->get('switch', 'voicemail')."/default/".$domain_name); // folder
							}

						//rename switch/storage/fax/[domain] (folder)
							if (!empty($settings->get('switch', 'storage')) && file_exists($settings->get('switch', 'storage')."/fax/".$original_domain_name)) {
								@rename($settings->get('switch', 'storage')."/fax/".$original_domain_name, $settings->get('switch', 'storage')."/fax/".$domain_name); // folder
							}

						//rename switch/conf/dialplan/[domain] (folder/file)
							if (!empty($settings->get('switch', 'dialplan'))) {
								if (file_exists($settings->get('switch', 'dialplan')."/".$original_domain_name)) {
									@rename($settings->get('switch', 'dialplan')."/".$original_domain_name, $settings->get('switch', 'dialplan')."/".$domain_name); // folder
								}
								if (file_exists($settings->get('switch', 'dialplan')."/".$original_domain_name.".xml")) {
									@rename($settings->get('switch', 'dialplan')."/".$original_domain_name.".xml", $settings->get('switch', 'dialplan')."/".$domain_name.".xml"); // file
								}
							}

						//rename switch/conf/dialplan/public/[domain] (folder/file)
							if (!empty($settings->get('switch', 'dialplan'))) {
								if (file_exists($settings->get('switch', 'dialplan')."/public/".$original_domain_name)) {
									@rename($settings->get('switch', 'dialplan')."/public/".$original_domain_name, $settings->get('switch', 'dialplan')."/public/".$domain_name); // folder
								}
								if (file_exists($settings->get('switch', 'dialplan')."/public/".$original_domain_name.".xml")) {
									@rename($settings->get('switch', 'dialplan')."/public/".$original_domain_name.".xml", $settings->get('switch', 'dialplan')."/public/".$domain_name.".xml"); // file
								}
							}

						//rename switch/conf/directory/[domain] (folder/file)
							if (!empty($settings->get('switch', 'extensions'))) {
								if (file_exists($settings->get('switch', 'extensions')."/".$original_domain_name)) {
									@rename($settings->get('switch', 'extensions')."/".$original_domain_name, $settings->get('switch', 'extensions')."/".$domain_name); // folder
								}
								if (file_exists($settings->get('switch', 'extensions')."/".$original_domain_name.".xml")) {
									@rename($settings->get('switch', 'extensions')."/".$original_domain_name.".xml", $settings->get('switch', 'extensions')."/".$domain_name.".xml"); // file
								}
							}

						//rename switch/recordings/[domain] (folder)
							if (file_exists($settings->get('switch', 'recordings')."/".$_SESSION['domain_name'])) {
								$switch_recordings_dir = str_replace("/".$_SESSION["domain_name"], "", $settings->get('switch', 'recordings')."/".$_SESSION['domain_name']);
								if (file_exists($switch_recordings_dir."/".$original_domain_name)) {
									@rename($switch_recordings_dir."/".$original_domain_name, $switch_recordings_dir."/".$domain_name); // folder
								}
							}

						//update dialplan, dialplan/public xml files
							$dialplan_xml = file_get_contents($settings->get('switch', 'dialplan')."/".$domain_name.".xml");
							$dialplan_xml = str_replace($original_domain_name, $domain_name, $dialplan_xml);
							file_put_contents($settings->get('switch', 'dialplan')."/".$domain_name.".xml", $dialplan_xml);
							unset($dialplan_xml);

							$dialplan_public_xml = file_get_contents($settings->get('switch', 'dialplan')."/public/".$domain_name.".xml");
							$dialplan_public_xml = str_replace($original_domain_name, $domain_name, $dialplan_public_xml);
							file_put_contents($settings->get('switch', 'dialplan')."/public/".$domain_name.".xml", $dialplan_public_xml);
							unset($dialplan_public_xml);

						//update session domain name
							$_SESSION['domains'][$domain_uuid]['domain_name'] = $domain_name;

						//recreate dialplan and extension xml files
							if (is_readable($settings->get('switch', 'extensions'))) {
								$extension = new extension;
								$extension->xml();
							}

						//if single-tenant and variables exist, update variables > domain value to match new domain
							if (count($_SESSION['domains']) == 1 && file_exists(dirname(__DIR__, 2)."/app/vars/")) {
								$sql = "update v_vars set ";
								$sql .= "var_value = :var_value ";
								$sql .= "where var_name = 'domain' ";
								$parameters['var_value'] = $domain_name;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}
					}
				}

			//clear the cache
				$cache = new cache;
				$response = $cache->flush();

			//clear the domains session array to update it
				unset($_SESSION["domains"]);
				unset($_SESSION['switch']);

			//redirect the browser
				if ($action == "update") {
					message::add($text['message-update']);
					if (!permission_exists('domain_add') || $restrict_domains) { //admin, updating own domain or a domain in their tree
						header("Location: domain_edit.php".($query_string ? '?'.$query_string : ''));
					}
					else {
						header("Location: domains.php".($query_string ? '?'.$query_string : '')); //superadmin
					}
				}
				if ($action == "add") {
					message::add($text['message-add']);
					header("Location: domains.php".($query_string ? '?'.$query_string : ''));
				}
				exit;
			}
	}

//pre-populate the form (admin won't have domain_add permissions, but domain_uuid will already be set above)
	if ((!empty($_GET) || (!permission_exists('domain_add') && !empty($domain_uuid))) && empty($_POST["persistformvar"])) {
		$sql = "select ";
		$sql .= "domain_uuid, ";
		$sql .= "domain_name, ";
		$sql .= "domain_parent_uuid, ";
		$sql .= "domain_enabled, ";
		$sql .= "domain_description ";
		$sql .= "from v_domains ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$domain_name = strtolower($row["domain_name"]);
			$domain_parent_uuid = !empty($row["domain_parent_uuid"]) ? $row["domain_parent_uuid"] : '';
			$domain_enabled = $row["domain_enabled"];
			$domain_description = $row["domain_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the parent domain options. a domain can not be its own parent nor a descendant of
//itself, and a user without domain_all is limited to the domains they manage.
	$parent_domain_options = [];
	if ($domain_all_access || $domain_tree_access) {
		$sql = "select domain_uuid, domain_parent_uuid, domain_name from v_domains ";
		if ($restrict_domains && !empty($allowed_domain_uuids)) {
			$sql .= "where domain_uuid in (" . implode(',', array_fill(0, count($allowed_domain_uuids), '?')) . ") ";
			$parameters = $allowed_domain_uuids;
		}
		elseif ($restrict_domains) {
			$sql .= "where 1 != 1 ";
			$parameters = [];
		}
		$sql .= "order by domain_name asc ";
		$domain_rows = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);

		//build the parent child map
		$domains_by_uuid = [];
		$children_by_uuid = [];
		if (is_array($domain_rows)) {
			foreach ($domain_rows as $domain_row) {
				$domains_by_uuid[$domain_row['domain_uuid']] = $domain_row;
				$children_by_uuid[$domain_row['domain_parent_uuid'] ?? ''][] = $domain_row['domain_uuid'];
			}
		}

		//when editing a domain, exclude itself and everything below it, they can not be its parent
		$excluded_domain_uuids = [];
		if ($action == 'update' && !empty($domain_uuid)) {
			$excluded_domain_uuids[] = $domain_uuid;
			for ($i = 0; $i < count($excluded_domain_uuids); $i++) {
				foreach ($children_by_uuid[$excluded_domain_uuids[$i]] ?? [] as $child_uuid) {
					if (!in_array($child_uuid, $excluded_domain_uuids, true)) {
						$excluded_domain_uuids[] = $child_uuid;
					}
				}
			}
		}

		//list each domain directly after its parent and indent it by its depth in the tree.
		//a domain whose parent is not part of the list is treated as a root.
			$root_domain_uuids = [];
			foreach ($domains_by_uuid as $uuid => $domain_row) {
				if (!isset($domains_by_uuid[$domain_row['domain_parent_uuid'] ?? ''])) {
					$root_domain_uuids[] = $uuid;
				}
			}

			//walk the tree from each root, reversing keeps the siblings in name order
			$stack = [];
			foreach (array_reverse($root_domain_uuids) as $uuid) {
				$stack[] = ['uuid' => $uuid, 'depth' => 0];
			}
			while (!empty($stack)) {
				$item = array_pop($stack);
				if (!in_array($item['uuid'], $excluded_domain_uuids, true)) {
					$parent_domain_options[$item['uuid']] = ['name' => $domains_by_uuid[$item['uuid']]['domain_name'], 'depth' => $item['depth']];
				}
				foreach (array_reverse($children_by_uuid[$item['uuid']] ?? []) as $child_uuid) {
					$stack[] = ['uuid' => $child_uuid, 'depth' => $item['depth'] + 1];
				}
			}

			//include any domain not reached from a root so the options stay complete should
			//the data ever hold a parent child loop
			foreach ($domains_by_uuid as $uuid => $domain_row) {
				if (!isset($parent_domain_options[$uuid]) && !in_array($uuid, $excluded_domain_uuids, true)) {
					$parent_domain_options[$uuid] = ['name' => $domain_row['domain_name'], 'depth' => 0];
				}
			}
			unset($root_domain_uuids, $stack, $item, $child_uuid);
		unset($domain_rows, $domains_by_uuid, $children_by_uuid, $excluded_domain_uuids);
	}

//set the defaults
	$domain_enabled = $domain_enabled ?? true;

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
	if (permission_exists("domain_select") && permission_exists("domain_setting_add") && !empty($_SESSION['domains']) && count($_SESSION['domains']) > 1) {
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
		if (!empty($search)) {
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

	if (permission_exists('domain_add') || $restrict_domains) {
		echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'domains.php'.($query_string ? '?'.$query_string : '')]);
	}
	if ($action == "update" && permission_exists('domain_setting_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>$settings->get('theme', 'button_icon_settings'),'id'=>'btn_back','style'=>'margin-right: 2px;','link'=>PROJECT_PATH.'/core/domain_settings/domain_settings.php?id='.urlencode($domain_uuid)]);
	}
	if (permission_exists('domain_delete') && is_array($_SESSION['domains']) && @sizeof($_SESSION['domains']) > 1 && $domain_uuid != $_SESSION['domain_uuid']) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'onclick'=>"modal_open('modal-delete-domain','btn_delete_domain');"]);
	}
	//limit the domain selector to the domains the user manages
	$selector_domains = $_SESSION['domains'];
	if ($restrict_domains && is_array($selector_domains)) {
		foreach ($selector_domains as $key => $value) {
			if (!in_array($value['domain_uuid'] ?? '', $allowed_domain_uuids, true)) {
				unset($selector_domains[$key]);
			}
		}
	}

	if (permission_exists("domain_select") && is_array($selector_domains) && @sizeof($selector_domains) > 1) {
		echo "<select id='domains' class='formfld' style='width: auto;' onchange=\"window.location.href='?id=' + document.getElementById('domains').options[document.getElementById('domains').selectedIndex].value;\">\n";
		foreach ($selector_domains as $domain) {
			$selected = $domain["domain_uuid"] == $domain_uuid ? "selected='selected'" : null;
			echo "	<option value='".escape($domain["domain_uuid"])."' ".$selected.">".escape($domain["domain_name"])."</option>\n";
		}
		echo "</select>";
	}
	if (permission_exists('domain_export')) {
		echo button::create(['type'=>'button','label'=>$text['button-export'],'icon'=>$settings->get('theme', 'button_icon_export'),'link'=>PROJECT_PATH."/app/domain_export/index.php?id=".urlencode($domain_uuid)]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','style'=>'margin-left: 3px;']);
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

	echo "<div class='card'>\n";
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

	if ($domain_all_access || $domain_tree_access) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-parent_domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' style='min-width: 150px;' name='domain_parent_uuid' id='domain_parent_uuid'>\n";
		if (!$restrict_domains) {
			echo "		<option value='' ".(empty($domain_parent_uuid) ? "selected='selected'" : null).">".$text['label-no_parent']."</option>\n";
		}
		foreach ($parent_domain_options as $uuid => $option) {
			echo "		<option value='".escape($uuid)."'".($domain_parent_uuid == $uuid ? " selected='selected'" : null).">".str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $option['depth']).escape($option['name'])."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-parent_domain']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='domain_enabled' name='domain_enabled'>\n";
	echo "		<option value='true' ".($domain_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($domain_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
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
	echo "</div>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
