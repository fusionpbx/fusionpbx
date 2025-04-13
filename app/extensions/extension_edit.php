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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('extension_add') || permission_exists('extension_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the domain and user UUIDs
	$domain_uuid = $domain_uuid ?? '';
	$user_uuid = $user_uuid ?? '';

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//return the first item if data type = array, returns value if data type = text 
	function get_first_item($value) {
	    return is_array($value) ? $value[0] : $value;
	}

//initialize the core objects
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
	$user_uuid = $_SESSION['user_uuid'] ?? '';
	$config = config::load();
	$database = database::new(['config' => $config]);
	$domain_name = $database->select('select domain_name from v_domains where domain_uuid = :domain_uuid', ['domain_uuid' => $domain_uuid], 'column');
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);

//set defaults
	$limit_extensions                     = $settings->get('limit', 'extensions', null);
	$limit_devices                        = $settings->get('limit', 'devices', null);
	$extension_limit_max                  = $settings->get('extension', 'limit_max', 5);
	$extension_call_timeout               = $settings->get('extension', 'call_timeout', 30);
	$extension_max_registrations          = $settings->get('extension', 'max_registrations', null);
	$extension_password_length            = $settings->get('extension', 'password_length', 20);       //set default to 20
	$extension_password_strength          = $settings->get('extension', 'password_strength', 4);      //set default to use numbers, Upper/Lowercase letters, special characters
	$extension_user_record_default        = $settings->get('extension', 'user_record_default', '');
	$provision_path                       = $settings->get('provision', 'path', '');
	$provision_line_label                 = $settings->get('provision','line_label', null);
	$provision_line_display_name          = $settings->get('provision','line_display_name', null);
	$provision_outbound_proxy_primary     = $settings->get('provision','outbound_proxy_primary', null);
	$provision_outbound_proxy_secondary   = $settings->get('provision','outbound_proxy_secondary', null);
	$provision_server_address_primary     = $settings->get('provision','server_address_primary', null);
	$provision_server_address_secondary   = $settings->get('provision','server_address_secondary', null);
	$provision_line_sip_port              = $settings->get('provision','line_sip_port', null);
	$provision_line_sip_transport         = $settings->get('provision','line_sip_transport', null);
	$provision_line_register_expires      = $settings->get('provision','line_register_expires', null);
	$theme_input_toggle_style             = $settings->get('theme','input_toggle_style', '');                       //set default to empty string
	$voicemail_password_length            = $settings->get('voicemail', 'password_length', 6);                      //set default to 6
	$voicemail_transcription_enabled_default = $settings->get('voicemail', 'transcription_enabled_default', false); //set default to false
	$voicemail_enabled_default               = $settings->get('voicemail', 'enabled_default', true);
	$switch_voicemail                        = $settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage/voicemail') . "/default/$domain_name";
	$switch_extensions                       = $settings->get('switch', 'extensions', '/etc/freeswitch/directory');
	$switch_sounds                           = $settings->get('switch', 'sounds', '/usr/share/freeswitch/sounds');
	$transcribe_enabled                      = $settings->get('transcribe', 'enabled', false);

//cast to integers if they have values
	if ($limit_extensions !== null) $limit_extensions = intval($limit_extensions);
	if ($limit_devices !== null) $limit_devices = intval($limit_devices);
	if ($extension_password_length !== null) $extension_password_length = intval($extension_password_length);
	if ($extension_max_registrations !== null) $extension_max_registrations = intval($extension_max_registrations);

//set the action as an add or an update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$extension_uuid = $_REQUEST["id"];
		$page = $_REQUEST['page'] ?? null;
	}
	else {
		$action = "add";
	}

//get total extension count from the database, check limit, if defined
	if ($action == 'add') {
		if ($limit_extensions > 0) {
			$sql = "select count(extension_uuid) ";
			$sql .= "from v_extensions ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$total_extensions = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

			if ($total_extensions >= $limit_extensions) {
				message::add($text['message-maximum_extensions'].' '.$limit_extensions, 'negative');
				header('Location: extensions.php'.(isset($page) && is_numeric($page) ? '?page='.$page : null));
				exit;
			}
		}
	}

//get the http values and set them as php variables
	if (!empty($_POST)) {

		//get the values from the HTTP POST and save them as PHP variables
			if ($action == 'add' || permission_exists("extension_extension")) {
				$extension = str_replace(' ','-',$_POST["extension"]);
			}
			else { //lookup extension based on submitted uuid
				$sql = "select extension from v_extensions where extension_uuid = :extension_uuid";
				$parameters['extension_uuid'] = $extension_uuid;
				$extension = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);
			}
			$number_alias = $_POST["number_alias"] ?? null;
			$password = $_POST["password"] ?? null;

			//server verification on account code
			$accountcode = $_POST["accountcode"];

			$effective_caller_id_name = $_POST["effective_caller_id_name"];
			$effective_caller_id_number = $_POST["effective_caller_id_number"];
			$outbound_caller_id_name = $_POST["outbound_caller_id_name"];
			$outbound_caller_id_number = $_POST["outbound_caller_id_number"];
			$emergency_caller_id_name = $_POST["emergency_caller_id_name"] ?? null;
			$emergency_caller_id_number = $_POST["emergency_caller_id_number"] ?? null;
			$directory_first_name = $_POST["directory_first_name"];
			$directory_last_name = $_POST["directory_last_name"];
			$directory_visible = $_POST["directory_visible"];
			$directory_exten_visible = $_POST["directory_exten_visible"];
			$max_registrations = $_POST["max_registrations"];
			$limit_max = $_POST["limit_max"];
			$limit_destination = $_POST["limit_destination"];
			//$device_uuid = $_POST["device_uuid"];
			//$device_line = $_POST["device_line"];
			$voicemail_password = $_POST["voicemail_password"];
			$voicemail_enabled = $_POST["voicemail_enabled"] ?? 'false';
			$voicemail_mail_to = $_POST["voicemail_mail_to"];
			$voicemail_transcription_enabled = $_POST["voicemail_transcription_enabled"];
			$voicemail_file = $_POST["voicemail_file"];
			$voicemail_local_after_email = $_POST["voicemail_local_after_email"];
			$user_context = $_POST["user_context"];
			$range = $_POST["range"] ?? null;
			$missed_call_app = $_POST["missed_call_app"];
			$missed_call_data = $_POST["missed_call_data"];
			$toll_allow = $_POST["toll_allow"];
			$call_timeout = $_POST["call_timeout"];
			$call_group = $_POST["call_group"];
			$call_screen_enabled = $_POST["call_screen_enabled"];
			$user_record = $_POST["user_record"];
			$hold_music = $_POST["hold_music"];
			$auth_acl = $_POST["auth_acl"];
			$cidr = $_POST["cidr"];
			$sip_force_contact = $_POST["sip_force_contact"];
			$sip_force_expires = $_POST["sip_force_expires"];
			$nibble_account = $_POST["nibble_account"] ?? null;
			$mwi_account = $_POST["mwi_account"];
			$sip_bypass_media = $_POST["sip_bypass_media"];
			$absolute_codec_string = $_POST["absolute_codec_string"];
			$force_ping = $_POST["force_ping"];
			$dial_string = $_POST["dial_string"];
			$extension_language = $_POST["extension_language"];
			$extension_type = $_POST["extension_type"];
			$enabled = $_POST["enabled"] ?? 'false';
			$description = $_POST["description"];

			//set defaults
			$extension_language = $extension_language ?? '';

			//outbound caller id number - only allow numeric and +
			if (!empty($outbound_caller_id_number)) {
				$outbound_caller_id_number = preg_replace('#[^\+0-9]#', '', $outbound_caller_id_number);
			}

			$voicemail_id = $extension;
			if (permission_exists('number_alias') && !empty($number_alias)) {
				$voicemail_id = $number_alias;
			}

			$cidrs = preg_split("/[\s,]+/", $cidr);
			$ips = array();
			foreach ($cidrs as $ipaddr){
				$cx = strpos($ipaddr, '/');
				if ($cx){
					$subnet = (int)(substr($ipaddr, $cx+1));
					$ipaddr = substr($ipaddr, 0, $cx);
				}
				else{
					$subnet = 32;
				}

				if(($addr = inet_pton($ipaddr)) !== false){
					$ips[] = $ipaddr.'/'.$subnet;
				}
			}
			$cidr = implode(',',$ips);

		//change toll allow delimiter
			$toll_allow = str_replace(',',':', $toll_allow);

		//set assigned user variables
			$user_uuid = $_POST["extension_users"][0]["user_uuid"] ?? null;

		//device provisioning variables
			if (!empty($_POST["devices"])) {

				//get the devices
				$sql = "select count(device_uuid) from v_devices ";
				$sql .= "where domain_uuid = :domain_uuid ";
				if (!permission_exists('device_all') && !permission_exists('device_domain_all')) {
					$sql .= "and device_user_uuid = :user_uuid ";
					$parameters['user_uuid'] = $user_uuid;
				}
				$parameters['domain_uuid'] = $domain_uuid;
				$total_devices = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

				foreach ($_POST["devices"] as $d => $device) {
					if (
						!empty($device["device_address"]) &&
						strtolower($device["device_address"]) == 'uuid' &&
						(
							$limit_devices === null ||
							$total_devices < $limit_devices
						)) {
						$device_address = strtolower(uuid());
					}
					else {
						$device_address = strtolower($device["device_address"]);
					}
					$device_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_address);
					$line_numbers[$d] = $device["line_number"];
					$device_addresses[$d] = $device_address;
					$device_templates[$d] = $device["device_template"];
				}
			}

		//get or set the device_uuid
			if (!empty($device_addresses) && is_array($device_addresses) && @sizeof($device_addresses) != 0) {
				foreach ($device_addresses as $d => $device_address) {
					$device_address = strtolower($device_address);
					$device_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_address);

					$sql = "select ";
					$sql .= "d1.device_uuid, ";
					$sql .= "d1.domain_uuid, ";
					$sql .= "d2.domain_name ";
					$sql .= "from ";
					$sql .= "v_devices as d1, ";
					$sql .= "v_domains as d2 ";
					$sql .= "where ";
					$sql .= "d1.domain_uuid = d2.domain_uuid and ";
					$sql .= "d1.device_address = :device_address ";
					$parameters['device_address'] = $device_address;
					$row = $database->select($sql, $parameters, 'row');
					if (is_array($row)) {
						if ($domain_uuid == $row['domain_uuid']) {
							$device_uuid = $row['device_uuid'];
							$device_domain_name = $row['device_domain_name'];
							$device_unique = true;
						}
						else {
							$device_domain_name = $row['device_domain_name'];
							$device_unique = false;
						}
					}
					else {
						$device_unique = true;
					}
					unset($sql, $parameters);

					$device_uuids[$d] = !empty($device_uuid) && is_uuid($device_uuid) ? $device_uuid : uuid();
				}
			}

	}

//delete the user from the v_extension_users
	if (!empty($_REQUEST["delete_type"]) && $_REQUEST["delete_type"] == "user" && is_uuid($_REQUEST["delete_uuid"]) && permission_exists("extension_delete")) {
		//set the variables
			$extension_uuid = $_REQUEST["id"];
			$user_uuid = $_REQUEST["delete_uuid"];

		//delete the group from the users
			$array['extension_users'][0]['extension_uuid'] = $extension_uuid;
			$array['extension_users'][0]['user_uuid'] = $user_uuid;

		//add temporary permission
			$p = permissions::new();
			$p->add('extension_user_delete', 'temp');

		//save the array
			$database->app_name = 'extensions';
			$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
			$database->delete($array);
			unset($array);

		//remove temporary permission
			$p->delete('extension_user_delete', 'temp');

		//redirect
			header("Location: extension_edit.php?id=".$extension_uuid);
			exit;
	}

//delete the line from the v_device_lines
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/devices')) {
		if (!empty($_REQUEST["delete_type"]) && $_REQUEST["delete_type"] == "device_line" && is_uuid($_REQUEST["delete_uuid"]) && permission_exists("extension_delete")) {
			//set the variables
				$device_line_uuid = $_REQUEST["delete_uuid"];

			//delete device_line
				$array['device_lines'][0]['device_line_uuid'] = $device_line_uuid;

			//add temporary permission
				$p = permissions::new();
				$p->add('device_line_delete', 'temp');

			//save the array
				$database->app_name = 'extensions';
				$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
				$database->delete($array);
				unset($array);

			//remove temporary permission
				$p->delete('device_line_delete', 'temp');

			//redirect
				header("Location: extension_edit.php?id=".$extension_uuid);
				exit;
		}
	}

//process the user data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//set the domain_uuid
			if (permission_exists('extension_domain') && is_uuid($_POST["domain_uuid"])) {
				$domain_uuid = $_POST["domain_uuid"];
			}
			else {
				$domain_uuid = $domain_uuid;
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: extensions.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (empty($extension)) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			if (permission_exists('extension_enabled')) {
				if (empty($enabled)) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			}
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

		//prevent users from bypassing extension limit by using range
			if (isset($total_extensions) && ($total_extensions ?? 0) + $range > $limit_extensions) {
				$range = $limit_extensions - $total_extensions;
			}

		//add or update the database
			if (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true") {

				//prep missed call values for db insert/update
					switch ($missed_call_app) {
						case 'email':
							$missed_call_data = str_replace(';',',',$missed_call_data);
							$missed_call_data = str_replace(' ','',$missed_call_data);
							if (substr_count($missed_call_data, ',') > 0) {
								$missed_call_data_array = explode(',', $missed_call_data);
								foreach ($missed_call_data_array as $array_index => $email_address) {
									if (!valid_email($email_address)) { unset($missed_call_data_array[$array_index]); }
								}
								//echo "<pre>".print_r($missed_call_data_array, true)."</pre><br><br>";
								if (sizeof($missed_call_data_array) > 0) {
									$missed_call_data = implode(',', $missed_call_data_array);
								}
								else {
									unset($missed_call_app, $missed_call_data);
								}
								//echo "Multiple Emails = ".$missed_call_data;
							}
							else {
								//echo "Single Email = ".$missed_call_data."<br>";
								if (!valid_email($missed_call_data)) {
									//echo "Invalid Email<br><br>";
									unset($missed_call_app, $missed_call_data);
								}
							}
							break;
						case 'text':
							$missed_call_data = str_replace('-','',$missed_call_data);
							$missed_call_data = str_replace('.','',$missed_call_data);
							$missed_call_data = str_replace('(','',$missed_call_data);
							$missed_call_data = str_replace(')','',$missed_call_data);
							$missed_call_data = str_replace(' ','',$missed_call_data);
							if (!is_numeric($missed_call_data)) { unset($missed_call_app, $missed_call_data); }
							break;
					}

				//build the data array
					if (!isset($range)) { $range = 1; }
					$j = 0;
					for ($i=0; $i<$range; $i++) {

						//check if the extension exists
							if ($action == "add" && extension_exists($extension)) {
								//extension exists
							}
							else {
								//password permission not assigned get the password from the database
									if ($action == "update" && !permission_exists('extension_password')) {
										$sql = "select password from v_extensions ";
										$sql .= "where extension_uuid = :extension_uuid ";
										$sql .= "and domain_uuid = :domain_uuid ";
										$parameters['domain_uuid'] = $domain_uuid;
										$parameters['extension_uuid'] = $extension_uuid;
										$row = $database->select($sql, $parameters, 'row');
										if (is_array($row) && @sizeof($row) != 0) {
											$password = $row["password"];
										}
										unset($sql, $parameters, $row);
									}

								//get the password length and strength
									$password_length = $extension_password_length;
									$password_strength = $extension_password_strength;

								//extension does not exist add it
									if ($action == "add" || $range > 1) {
										$extension_uuid = uuid();
										$voicemail_uuid = uuid();
										$password = generate_password($password_length, $password_strength);
									}

								//prepare the values for mwi account
										if (!empty($mwi_account) && strpos($mwi_account, '@') === false) {
											$mwi_account .= "@".$domain_name;
										}

								//generate a password
									if ($action == "add" && empty($password)) {
										$password = generate_password($password_length, $password_strength);
									}
									if ($action == "update" && permission_exists('extension_password') && empty($password)) {
										$password = generate_password($password_length, $password_strength);
									}

								//seperate the language components into language, dialect and voice
									$language_array = explode("/",$extension_language);
									$extension_language = $language_array[0] ?? 'en';
									$extension_dialect = $language_array[1] ?? 'us';
									$extension_voice = $language_array[2] ?? 'callie';

								//create the data array
									$array["extensions"][$i]["domain_uuid"] = $domain_uuid;
									$array["extensions"][$i]["extension_uuid"] = $extension_uuid;
									$array["extensions"][$i]["extension"] = $extension;
									if (permission_exists('number_alias')) {
										$array["extensions"][$i]["number_alias"] = $number_alias;
									}
									if (!empty($password)) {
										$array["extensions"][$i]["password"] = $password;
									}
									if (permission_exists('extension_accountcode')) {
										$array["extensions"][$i]["accountcode"] = $accountcode;
									}
									else {
										if ($action == "add") {
											$array["extensions"][$i]["accountcode"] = get_accountcode();
										}
									}
									if (permission_exists("effective_caller_id_name")) {
										$array["extensions"][$i]["effective_caller_id_name"] = $effective_caller_id_name;
									}
									if (permission_exists("effective_caller_id_number")) {
										$array["extensions"][$i]["effective_caller_id_number"] = $effective_caller_id_number;
									}
									if (permission_exists("outbound_caller_id_name")) {
										$array["extensions"][$i]["outbound_caller_id_name"] = $outbound_caller_id_name;
									}
									if (permission_exists("outbound_caller_id_number")) {
										$array["extensions"][$i]["outbound_caller_id_number"] = $outbound_caller_id_number;
									}
									if (permission_exists("emergency_caller_id_name")) {
										$array["extensions"][$i]["emergency_caller_id_name"] = $emergency_caller_id_name;
									}
									if (permission_exists("emergency_caller_id_number")) {
										$array["extensions"][$i]["emergency_caller_id_number"] = $emergency_caller_id_number;
									}
									if (permission_exists("extension_directory")) {
										$array["extensions"][$i]["directory_first_name"] = $directory_first_name;
										$array["extensions"][$i]["directory_last_name"] = $directory_last_name;
										$array["extensions"][$i]["directory_visible"] = $directory_visible;
										$array["extensions"][$i]["directory_exten_visible"] = $directory_exten_visible;
									}
									if (permission_exists("extension_max_registrations")) {
										$array["extensions"][$i]["max_registrations"] = $max_registrations;
									}
									else {
										if ($action == "add") {
											$array["extensions"][$i]["max_registrations"] = $extension_max_registrations;
										}
									}
									if (permission_exists("extension_limit")) {
										$array["extensions"][$i]["limit_max"] = $limit_max;
										$array["extensions"][$i]["limit_destination"] = $limit_destination;
									}
									if (permission_exists("extension_user_context")) {
										$array["extensions"][$i]["user_context"] = $user_context;
									}
									else {
										if ($action == "add") {
											$array["extensions"][$i]["user_context"] = $domain_name;
										}
									}
									if (permission_exists('extension_missed_call')) {
										$array["extensions"][$i]["missed_call_app"] = $missed_call_app;
										$array["extensions"][$i]["missed_call_data"] = $missed_call_data;
									}
									if (permission_exists('extension_toll')) {
										$array["extensions"][$i]["toll_allow"] = $toll_allow;
									}
									if (!empty($call_timeout)) {
										$array["extensions"][$i]["call_timeout"] = $call_timeout;
									}
									if (permission_exists("extension_call_group")) {
										$array["extensions"][$i]["call_group"] = $call_group;
									}
									$array["extensions"][$i]["call_screen_enabled"] = $call_screen_enabled;
									if (permission_exists('extension_user_record')) {
										$array["extensions"][$i]["user_record"] = $user_record;
									}
									if (permission_exists('extension_hold_music')) {
										$array["extensions"][$i]["hold_music"] = $hold_music;
									}
									if (permission_exists("extension_advanced")) {
										$array["extensions"][$i]["auth_acl"] = $auth_acl;
										if (permission_exists("extension_cidr")) {
											$array["extensions"][$i]["cidr"] = $cidr;
										}
										$array["extensions"][$i]["sip_force_contact"] = $sip_force_contact;
										$array["extensions"][$i]["sip_force_expires"] = $sip_force_expires;
										if (permission_exists('extension_nibble_account')) {
											if (!empty($nibble_account)) {
												$array["extensions"][$i]["nibble_account"] = $nibble_account;
											}
										}
										$array["extensions"][$i]["mwi_account"] = $mwi_account;
										$array["extensions"][$i]["sip_bypass_media"] = $sip_bypass_media;
										if (permission_exists('extension_absolute_codec_string')) {
											$array["extensions"][$i]["absolute_codec_string"] = $absolute_codec_string;
										}
										if (permission_exists('extension_force_ping')) {
											$array["extensions"][$i]["force_ping"] = $force_ping;
										}
										if (permission_exists('extension_dial_string')) {
											$array["extensions"][$i]["dial_string"] = $dial_string;
										}
									}
									if (permission_exists('extension_language')) {
										$array['extensions'][0]["extension_language"] = $extension_language;
										$array['extensions'][0]["extension_dialect"] = $extension_dialect;
										$array['extensions'][0]["extension_voice"] = $extension_voice;
									}
									if (permission_exists('extension_type')) {
										$array["extensions"][$i]["extension_type"] = $extension_type;
									}
									if (permission_exists('extension_enabled')) {
										$array["extensions"][$i]["enabled"] = $enabled;
									}
									$array["extensions"][$i]["description"] = $description;

								//assign the user to the extension
									if (is_uuid($user_uuid)) {
										$array["extension_users"][$i]["extension_user_uuid"] = uuid();
										$array["extension_users"][$i]["domain_uuid"] = $domain_uuid;
										$array["extension_users"][$i]["user_uuid"] = $user_uuid;
										$array["extension_users"][$i]["extension_uuid"] = $extension_uuid;
									}

								//assign the device to the extension(s)
									if (is_array($device_addresses) && @sizeof($device_addresses) != 0) {
										foreach ($device_addresses as $d => $device_address) {
											if (!empty($device_address)) {
												//get the device vendor
												if (isset($device_templates[$d])) {
													//use the the template to get the vendor
													$template_array = explode("/", $device_templates[$d]);
													$device_vendor = $template_array[0];
												}
												else {
													//use the device address to get the vendor
													$device_vendor = device::get_vendor($device_address);
												}

												//determine the name
												if (!empty($effective_caller_id_name)) {
													$name = $effective_caller_id_name;
												}
												elseif (strlen($directory_first_name) > 0 && !empty($directory_last_name)) {
													$name = $directory_first_name.' '.$directory_last_name;
												}
												elseif (!empty($directory_first_name)) {
													$name = $directory_first_name;
												}
												elseif (!empty($directory_first_name)) {
													$name = $directory_first_name.' '.$directory_last_name;
												}
												else {
													$name = '';
												}

												//get the dislplay label
												if ($provision_line_label == 'auto') {
													$line_label = $extension;
												}
												else {
													$line_label = $provision_line_label;
													$line_label = str_replace("\${name}", $name, $line_label);
													$line_label = str_replace("\${effective_caller_id_name}", $effective_caller_id_name, $line_label);
													$line_label = str_replace("\${caller_id_name}", $effective_caller_id_name, $line_label);
													$line_label = str_replace("\${first_name}", $directory_first_name, $line_label);
													$line_label = str_replace("\${last_name}", $directory_last_name, $line_label);
													$line_label = str_replace("\${user_id}", $extension, $line_label);
													$line_label = str_replace("\${auth_id}", $extension, $line_label);
													$line_label = str_replace("\${extension}", $extension, $line_label);
													$line_label = str_replace("\${description}", $description, $line_label);
												}

												//get the dislplay name
												if ($provision_line_display_name == 'auto') {
													$line_display_name = $name;
												}
												else {
													$line_display_name = $provision_line_display_name;
													$line_display_name = str_replace("\${name}", $name, $line_display_name);
													$line_display_name = str_replace("\${effective_caller_id_name}", $effective_caller_id_name, $line_display_name);
													$line_display_name = str_replace("\${caller_id_name}", $effective_caller_id_name, $line_display_name);
													$line_display_name = str_replace("\${first_name}", $directory_first_name, $line_display_name);
													$line_display_name = str_replace("\${last_name}", $directory_last_name, $line_display_name);
													$line_display_name = str_replace("\${user_id}", $extension, $line_display_name);
													$line_display_name = str_replace("\${auth_id}", $extension, $line_display_name);
													$line_display_name = str_replace("\${extension}", $extension, $line_display_name);
													$line_display_name = str_replace("\${description}", $description, $line_display_name);
												}

												//build the devices array
												if (($device_unique && $device_mac_address != '000000000000') || $device_mac_address == '000000000000') {
													$array["devices"][$j]["device_uuid"] = $device_uuids[$d];
													$array["devices"][$j]["domain_uuid"] = $domain_uuid;
													$array["devices"][$j]["device_address"] = $device_address;
													$array["devices"][$j]["device_label"] = $extension;
													if (!empty($device_vendor)) {
														$array["devices"][$j]["device_vendor"] = $device_vendor;
													}
													if (!empty($device_templates[$d])) {
														$array["devices"][$j]["device_template"] = $device_templates[$d];
													}
													$array["devices"][$j]["device_enabled"] = "true";
													$array["devices"][$j]["device_lines"][0]["device_uuid"] = $device_uuids[$d];
													$array["devices"][$j]["device_lines"][0]["device_line_uuid"] = uuid();
													$array["devices"][$j]["device_lines"][0]["domain_uuid"] = $domain_uuid;
													$array["devices"][$j]["device_lines"][0]["server_address"] = $domain_name;

													$array["devices"][$j]["device_lines"][0]["outbound_proxy_primary"] = get_first_item($provision_outbound_proxy_primary);
													$array["devices"][$j]["device_lines"][0]["outbound_proxy_secondary"] = get_first_item($provision_outbound_proxy_secondary);
													$array["devices"][$j]["device_lines"][0]["server_address_primary"] = get_first_item($provision_server_address_primary);
													$array["devices"][$j]["device_lines"][0]["server_address_secondary"] = get_first_item($provision_server_address_secondary);
													$array["devices"][$j]["device_lines"][0]["label"] = $line_label;
													$array["devices"][$j]["device_lines"][0]["display_name"] = $line_display_name;
													$array["devices"][$j]["device_lines"][0]["user_id"] = $extension;
													$array["devices"][$j]["device_lines"][0]["auth_id"] = $extension;
													$array["devices"][$j]["device_lines"][0]["password"] = $password;
													$array["devices"][$j]["device_lines"][0]["line_number"] = is_numeric($line_numbers[$d]) ? $line_numbers[$d] : '1';
													if ($provision_line_sip_port !== null) $array["devices"][$j]["device_lines"][0]["sip_port"] = $provision_line_sip_port;
													if ($provision_line_sip_transport !== null) $array["devices"][$j]["device_lines"][0]["sip_transport"] = $provision_line_sip_transport;
													if ($provision_line_register_expires !== null) $array["devices"][$j]["device_lines"][0]["register_expires"] = $provision_line_register_expires;
													$array["devices"][$j]["device_lines"][0]["enabled"] = "true";
												}
												else {
													//send a message to the user the device is not unique
													$message = $text['message-duplicate'].(if_group("superadmin") && $_SESSION["domain_name"] != $device_domain_name ? ": ".$device_domain_name : null);
													message::add($message,'negative');
												}
												
												//increment
												$j++;
											}
										}
									}

							}

						//add or update voicemail
							if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
								//set the voicemail password
									if (empty($voicemail_password)) {
										$voicemail_password = generate_password($voicemail_password_length, 1);
									}

								//add  the voicemail to the array
									if ($voicemail_id !== NULL) {
										//get the voicemail_uuid
											$sql = "select voicemail_uuid from v_voicemails ";
											$sql .= "where voicemail_id = :voicemail_id ";
											$sql .= "and domain_uuid = :domain_uuid ";
											$parameters['voicemail_id'] = $voicemail_id;
											$parameters['domain_uuid'] = $domain_uuid;
											$row = $database->select($sql, $parameters, 'row');
											if (is_array($row) && @sizeof($row) != 0) {
												$voicemail_uuid = $row["voicemail_uuid"];
											}
											unset($sql, $parameters, $row);

										//if voicemail_uuid does not exist then get a new uuid
											if (!is_uuid($voicemail_uuid)) {
												$voicemail_uuid = uuid();
												$voicemail_tutorial = 'true';
												//if adding a mailbox and don't have the transcription permission, set the default transcribe behavior
												if (!permission_exists('voicemail_transcription_enabled')) {
													$voicemail_transcription_enabled = $voicemail_transcription_enabled_default;
												}
											}

										//add the voicemail to the array
											$array["voicemails"][$i]["domain_uuid"] = $domain_uuid;
											$array["voicemails"][$i]["voicemail_uuid"] = $voicemail_uuid;
											$array["voicemails"][$i]["voicemail_id"] = $voicemail_id;

											$array["voicemails"][$i]["voicemail_password"] = $voicemail_password;
											//$array["voicemails"][$i]["greeting_id"] = $greeting_id;
											//$array["voicemails"][$i]["voicemail_alternate_greet_id"] = $alternate_greet_id;
											$array["voicemails"][$i]["voicemail_mail_to"] = $voicemail_mail_to;
											//$array["voicemails"][$i]["voicemail_attach_file"] = $voicemail_attach_file;
											if (permission_exists('voicemail_file')) {
												$array["voicemails"][$i]["voicemail_file"] = $voicemail_file;
											}
											if (permission_exists('voicemail_local_after_email')) {
												$array["voicemails"][$i]["voicemail_local_after_email"] = $voicemail_local_after_email;
											}
											$array["voicemails"][$i]["voicemail_transcription_enabled"] = $voicemail_transcription_enabled;
											$array["voicemails"][$i]["voicemail_tutorial"] = $voicemail_tutorial ?? null;
											$array["voicemails"][$i]["voicemail_enabled"] = $voicemail_enabled;
											$array["voicemails"][$i]["voicemail_description"] = $description;

										//make sure the voicemail directory exists
											if (!file_exists($switch_voicemail.'/'.$voicemail_id)) {
												mkdir($switch_voicemail."/".$voicemail_id, 0770, true);
											}

									}
							}

						//increment the extension number
							if ($action != "update") {
								$extension++;
								$voicemail_id = $extension;

								if (!empty($number_alias)) {
									$number_alias++;
									$voicemail_id = $number_alias;
								}

								if (!empty($mwi_account)) {
									$mwi_account_array = explode('@', $mwi_account);
									$mwi_account_array[0]++;
									$mwi_account = implode('@', $mwi_account_array);
									unset($mwi_account_array);
								}
							}
					}

				//update devices having extension assigned to line(s) with new password
					if ($action == "update" && $range == 1 && permission_exists('extension_password')) {
						$sql = "update v_device_lines set ";
						$sql .= "password = :password ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and server_address = :server_address ";
						$sql .= "and user_id = :user_id ";
						$parameters['password'] = $password;
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['server_address'] = $domain_name;
						$parameters['user_id'] = $extension;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}

				//update device key label
					if (!empty($effective_caller_id_name)) {
						$sql = "update v_device_keys set ";
						$sql .= "device_key_label = :device_key_label ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and device_key_value = :device_key_value ";
						$parameters['device_key_label'] = $effective_caller_id_name;
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['device_key_value'] = $extension;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}

				//save to the data
					$database->app_name = 'extensions';
					$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
					$message = $database->save($array);
					unset($array);

				//reload the access control list
					if (permission_exists("extension_cidr")) {
						$event_socket = event_socket::create();
						if ($event_socket->is_connected()) { event_socket::api("reloadacl"); }
					}

				//check the permissions
					if (permission_exists('extension_add') || permission_exists('extension_edit')) {

						//synchronize configuration
							if (is_writable($switch_extensions)) {
								$ext = new extension;
								$ext->xml();
								unset($ext);
							}

						//write the provision files
							if (!empty($provision_path) && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/provision')) {
								$prov = new provision;
								$prov->domain_uuid = $domain_uuid;
								$response = $prov->write();
							}

						//clear the cache
							if (!permission_exists("extension_user_context") && $action == "update") {
								$sql = "select user_context from v_extensions ";
								$sql .= "where extension_uuid = :extension_uuid ";
								$parameters['extension_uuid'] = $extension_uuid;
								$user_context = $database->select($sql, $parameters, 'column');
							}
							$cache = new cache;
							$cache->delete("directory:".$extension."@".$user_context);
							if (permission_exists('number_alias') && !empty($number_alias)) {
								$cache->delete("directory:".$number_alias."@".$user_context);
							}

						//clear the destinations session array
							if (isset($_SESSION['destinations']['array'])) {
								unset($_SESSION['destinations']['array']);
							}

					}

				//set the message and redirect
					if ($action == "add") {
						message::add($text['message-add']);
					}
					if ($action == "update") {
						message::add($text['message-update']);
					}
					if ($range > 1) {
						header("Location: extensions.php");
					}
					else {
						header("Location: extension_edit.php?id=".$extension_uuid.(isset($page) && is_numeric($page) ? '&page='.$page : null));
					}
					exit;
			}
	}

//pre-populate the form
	if (!empty($_GET) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$extension_uuid = $_GET["id"];
		$sql = "select * from v_extensions ";
		$sql .= "where extension_uuid = :extension_uuid ";
		$parameters['extension_uuid'] = $extension_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$extension = $row["extension"];
			$number_alias = $row["number_alias"];
			$password = $row["password"];
			$accountcode = $row["accountcode"];
			$effective_caller_id_name = $row["effective_caller_id_name"];
			$effective_caller_id_number = $row["effective_caller_id_number"];
			$outbound_caller_id_name = $row["outbound_caller_id_name"];
			$outbound_caller_id_number = $row["outbound_caller_id_number"];
			$emergency_caller_id_name = $row["emergency_caller_id_name"];
			$emergency_caller_id_number = $row["emergency_caller_id_number"];
			$directory_first_name = $row["directory_first_name"];
			$directory_last_name = $row["directory_last_name"];
			$directory_visible = $row["directory_visible"];
			$directory_exten_visible = $row["directory_exten_visible"];
			$max_registrations = $row["max_registrations"];
			$limit_max = $row["limit_max"];
			$limit_destination = $row["limit_destination"];
			$user_context = $row["user_context"];
			$missed_call_app = $row["missed_call_app"];
			$missed_call_data = $row["missed_call_data"];
			$toll_allow = $row["toll_allow"];
			$call_timeout = $row["call_timeout"];
			$call_group = $row["call_group"];
			$call_screen_enabled = $row["call_screen_enabled"];
			$user_record = $row["user_record"];
			$hold_music = $row["hold_music"];
			$auth_acl = $row["auth_acl"];
			$cidr = $row["cidr"];
			$sip_force_contact = $row["sip_force_contact"];
			$sip_force_expires = $row["sip_force_expires"];
			$nibble_account = $row["nibble_account"];
			$mwi_account = $row["mwi_account"];
			$sip_bypass_media = $row["sip_bypass_media"];
			$absolute_codec_string = $row["absolute_codec_string"];
			$force_ping = $row["force_ping"];
			$dial_string = $row["dial_string"];
			$extension_language = $row["extension_language"];
			$extension_voice = $row["extension_voice"];
			$extension_dialect = $row["extension_dialect"];
			$extension_type = $row["extension_type"];
			$enabled = $row["enabled"];
			$description = $row["description"];
		}
		unset($sql, $parameters, $row);

	//outbound caller id number - only allow numeric and +
		if (!empty($outbound_caller_id_number)) {
			$outbound_caller_id_number = preg_replace('#[^\+0-9]#', '', $outbound_caller_id_number);
		}

	//get the voicemail data
		if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
			$sql = "select * from v_voicemails ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and voicemail_id = :voicemail_id ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['voicemail_id'] = is_numeric($number_alias) ? $number_alias : $extension;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$voicemail_password = str_replace("#", "", $row["voicemail_password"] ?? '');
				$voicemail_mail_to = str_replace(" ", "", $row["voicemail_mail_to"] ?? '');
				$voicemail_transcription_enabled = $row["voicemail_transcription_enabled"];
				$voicemail_tutorial = $row["voicemail_tutorial"];
				$voicemail_file = $row["voicemail_file"];
				$voicemail_local_after_email = $row["voicemail_local_after_email"];
				$voicemail_enabled = $row["voicemail_enabled"];
			}
			unset($sql, $parameters, $row);
		}

	}
	else {
		$voicemail_file = $settings->get('voicemail', 'voicemail_file', 'attach');
		$voicemail_local_after_email = $settings->get('voicemail','keep_local', true);
	}

//get the device lines
	$sql = "select d.device_address, d.device_template, d.device_description, l.device_line_uuid, l.device_uuid, l.line_number ";
	$sql .= "from v_device_lines as l, v_devices as d ";
	$sql .= "where (l.user_id = :user_id_1 or l.user_id = :user_id_2)";
	$sql .= "and l.password = :password ";
	$sql .= "and l.domain_uuid = :domain_uuid ";
	$sql .= "and l.device_uuid = d.device_uuid ";
	$sql .= "order by l.line_number, d.device_address asc ";
	$parameters['user_id_1'] = $extension ?? null;
	$parameters['user_id_2'] = $number_alias ?? null;
	$parameters['password'] = $password ?? null;
	$parameters['domain_uuid'] = $domain_uuid;
	$device_lines = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the devices
	$sql = "select * from v_devices ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if (!permission_exists('device_all') && !permission_exists('device_domain_all')) {
		$sql .= "and device_user_uuid = :user_uuid ";
		$parameters['user_uuid'] = $user_uuid;
	}
	$sql .= "order by device_address asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$devices = $database->select($sql, $parameters, 'all');
	if (!empty($devices) && is_array($devices)) {
		$total_devices = @sizeof($devices);
	}
	unset($sql, $parameters);

//get the device vendors
	$sql = "select name ";
	$sql .= "from v_device_vendors ";
	$sql .= "where enabled = 'true' ";
	$sql .= "order by name asc ";
	$device_vendors = $database->select($sql, null, 'all');
	unset($sql);

//get assigned users
	if (!empty($extension_uuid) && is_uuid($extension_uuid)) {
		$sql = "select u.username, e.user_uuid ";
		$sql .= "from v_extension_users as e, v_users as u ";
		$sql .= "where e.user_uuid = u.user_uuid  ";
		$sql .= "and u.user_enabled = 'true' ";
		$sql .= "and e.domain_uuid = :domain_uuid ";
		$sql .= "and e.extension_uuid = :extension_uuid ";
		$sql .= "order by u.username asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['extension_uuid'] = $extension_uuid;
		$assigned_users = $database->select($sql, $parameters, 'all');
		if (is_array($assigned_users) && @sizeof($assigned_users) != 0) {
			foreach($assigned_users as $row) {
				$assigned_user_uuids[] = $row['user_uuid'];
			}
		}
		unset($sql, $parameters, $row);
	}

//get the users
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if (!empty($assigned_user_uuids) && is_array($assigned_user_uuids) && @sizeof($assigned_user_uuids) != 0) {
		foreach ($assigned_user_uuids as $index => $assigned_user_uuid) {
			$sql .= "and user_uuid <> :user_uuid_".$index." ";
			$parameters['user_uuid_'.$index] = $assigned_user_uuid;
		}
	}
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $assigned_user_uuids, $assigned_user_uuid);

//get the destinations
	$sql = "select * from v_destinations ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and destination_type = 'inbound' ";
	$sql .= "order by destination_number asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$destinations = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the emergency destinations
	if (permission_exists('emergency_caller_id_select')) {
		$sql = "select * from v_destinations ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and destination_type = 'inbound' ";
		$sql .= "and destination_type_emergency = 1 ";
		$sql .= "order by destination_number asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$emergency_destinations = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//change toll allow delimiter
	$toll_allow = str_replace(':',',', $toll_allow ?? '');

//get installed languages
	$language_paths = glob($switch_sounds."/*/*/*");
	foreach ($language_paths as $key => $path) {
		$path = str_replace($switch_sounds.'/', "", $path);
		$path_array = explode('/', $path);
		if (count($path_array) <> 3 || strlen($path_array[0]) <> 2 || strlen($path_array[1]) <> 2) {
			unset($language_paths[$key]);
		}
		$language_paths[$key] = str_replace($switch_sounds."/","",$language_paths[$key] ?? '');
		if (empty($language_paths[$key])) {
			unset($language_paths[$key]);
		}
	}

//set the defaults
	if (empty($user_context)) { $user_context = $domain_name; }
	if (empty($max_registrations)) { $max_registrations = $extension_max_registrations ?? ''; }
	if (empty($accountcode)) { $accountcode = get_accountcode(); }
	if (empty($limit_max)) { $limit_max = $extension_limit_max; }
	if (empty($limit_destination)) { $limit_destination = '!USER_BUSY'; }
	if (empty($call_timeout)) { $call_timeout = $extension_call_timeout; }
	if (empty($call_screen_enabled)) { $call_screen_enabled = 'false'; }
	if (empty($user_record)) { $user_record = $extension_user_record_default; }
	if (empty($voicemail_transcription_enabled)) { $voicemail_transcription_enabled = $voicemail_transcription_enabled_default; }
	if (empty($voicemail_enabled)) { $voicemail_enabled = $voicemail_enabled_default; }
	if (empty($enabled)) { $enabled = 'true'; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//set the back button
	$_SESSION['call_forward_back'] = $_SERVER['PHP_SELF']  . "?id=$extension_uuid";

//begin the page content
	require_once "resources/header.php";
	if ($action == "update") {
	$document['title'] = $text['title-extension-edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-extension-add'];
	}

	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	if (permission_exists('extension_advanced')) {
		echo "function show_advanced_config() {\n";
		echo "	$('#show_advanced_box').slideToggle();\n";
		echo "	$('#show_advanced').slideToggle();\n";
		echo "}\n";
		echo "\n";
	}
	echo "function copy_extension() {\n";
	echo "	var new_ext = prompt('".$text['message-extension']."');\n";
	echo "	if (new_ext != null) {\n";
	echo "		if (!isNaN(new_ext)) {\n";
	echo "			document.location.href='extension_copy.php?id=".escape($extension_uuid ?? '')."&ext=' + new_ext".(!empty($page) && is_numeric($page) ? " + '&page=".$page."'" : null).";\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			var new_number_alias = prompt('".$text['message-number_alias']."');\n";
	echo "			if (new_number_alias != null) {\n";
	echo "				if (!isNaN(new_number_alias)) {\n";
	echo "					document.location.href='extension_copy.php?id=".escape($extension_uuid ?? '')."&ext=' + new_ext + '&alias=' + new_number_alias".(!empty($page) && is_numeric($page) ? " + '&page=".$page."'" : null).";\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "}\n";
	echo "</script>";

	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['header-extension-add']."</b>";
	}
	if ($action == "update") {
		echo "<b>".$text['header-extension-edit']."</b>";
	}
	echo 	"</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','link'=>'extensions.php'.(isset($page) && is_numeric($page) ? '?page='.$page : null)]);
	if ($action == 'update') {
		$button_margin = 'margin-left: 15px;';
		if (permission_exists('xml_cdr_view')) {
			echo button::create(['type'=>'button','label'=>$text['button-cdr'],'icon'=>'info-circle','style'=>($button_margin ?? ''),'link'=>'../xml_cdr/xml_cdr.php?extension_uuid='.urlencode($extension_uuid)]);
			unset($button_margin);
		}
		if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
			echo button::create(['type'=>'button','label'=>$text['button-call_forward'],'icon'=>'diagram-project','style'=>($button_margin ?? ''),'link'=>'../call_forward/call_forward_edit.php?id='.urlencode($extension_uuid)]);
			unset($button_margin);
		}
		if (permission_exists('extension_setting_view')) {
			echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>$settings->get('theme', 'button_icon_settings'),'id'=>'btn_settings','style'=>'','link'=>PROJECT_PATH.'/app/extension_settings/extension_settings.php?id='.urlencode($extension_uuid)]);
		}
		if (permission_exists('extension_copy')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','style'=>'margin-left: 15px;','onclick'=>"copy_extension();"]);
		}

	}
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>'submit_form();']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if ($action == "add" || permission_exists("extension_extension")) {
		echo "    <input class='formfld' type='text' name='extension' autocomplete='new-password' maxlength='255' value=\"".escape($extension ?? '')."\" required='required' placeholder=\"".$settings->get('extension','extension_range','')."\">\n";
		echo "    <input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
		echo "<br />\n";
		echo $text['description-extension']."\n";
	}
	else {
		echo escape($extension);
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('number_alias')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-number_alias']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='number' name='number_alias' autocomplete='new-password' maxlength='255' min='0' step='1' value=\"".escape($number_alias ?? '')."\">\n";
		echo "    <input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
		echo "<br />\n";
		echo $text['description-number_alias']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_password') && $action == "update") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-password']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input type='password' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
		echo "    <input class='formfld password' type='password' name='password' id='password' autocomplete='new-password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"".escape($password ?? '')."\">\n";
		echo "    <br />\n";
		echo "    ".$text['description-password']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if ($action == "add") {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-range']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='range'>\n";
		echo "    <option value='1'>1</option>\n";
		echo "    <option value='2'>2</option>\n";
		echo "    <option value='3'>3</option>\n";
		echo "    <option value='4'>4</option>\n";
		echo "    <option value='5'>5</option>\n";
		echo "    <option value='6'>6</option>\n";
		echo "    <option value='7'>7</option>\n";
		echo "    <option value='8'>8</option>\n";
		echo "    <option value='9'>9</option>\n";
		echo "    <option value='10'>10</option>\n";
		echo "    <option value='15'>15</option>\n";
		echo "    <option value='20'>20</option>\n";
		echo "    <option value='25'>25</option>\n";
		echo "    <option value='30'>30</option>\n";
		echo "    <option value='35'>35</option>\n";
		echo "    <option value='40'>40</option>\n";
		echo "    <option value='45'>45</option>\n";
		echo "    <option value='50'>50</option>\n";
		echo "    <option value='75'>75</option>\n";
		echo "    <option value='100'>100</option>\n";
		echo "    <option value='150'>150</option>\n";
		echo "    <option value='200'>200</option>\n";
		echo "    <option value='250'>250</option>\n";
		echo "    <option value='500'>500</option>\n";
		echo "    <option value='750'>750</option>\n";
		echo "    <option value='1000'>1000</option>\n";
		echo "    <option value='5000'>5000</option>\n";
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-range']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_user_edit')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".($action == "update" ? $text['label-users'] : $text['label-user'])."</td>";
		echo "		<td class='vtable'>";
		if (!empty($assigned_users) && is_array($assigned_users) && @sizeof($assigned_users) != 0 && $action == "update") {
			echo "		<table width='30%'>\n";
			foreach($assigned_users as $field) {
				echo "		<tr>\n";
				echo "			<td class='vtable'><a href='/core/users/user_edit.php?id=".escape($field['user_uuid'])."'>".escape($field['username'])."</a></td>\n";
				echo "			<td>\n";
				echo "				<a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('delete_type').value = 'user'; document.getElementById('delete_uuid').value = '".$field['user_uuid']."'; document.getElementById('frm').submit(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a>\n";
				echo "			</td>\n";
				echo "		</tr>\n";
			}
			echo "		</table>\n";
			echo "		<br />\n";
		}
		if (is_array($users) && @sizeof($users) != 0) {
			echo "			<select name='extension_users[0][user_uuid]' id='user_uuid' class='formfld' style='width: auto;'>\n";
			echo "			<option value=''></option>\n";
			foreach($users as $field) {
				echo "			<option value='".escape($field['user_uuid'])."'>".escape($field['username'])."</option>\n";
			}
			echo "			</select>";
			if ($action == "update") {
				echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add')]);
			}
			echo "			<br>\n";
		}
		echo "			".$text['description-user_list']."\n";
		echo "			<br />\n";
		echo "		</td>";
		echo "	</tr>";
	}

	if (permission_exists('voicemail_edit') && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_password']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input type='password' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
		echo "    <input class='formfld' type='password' name='voicemail_password' id='voicemail_password' autocomplete='new-password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value='".escape($voicemail_password ?? '')."'>\n";
		echo "    <br />\n";
		echo "    ".$text['description-voicemail_password']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_accountcode')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "    ".$text['label-accountcode']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <input class='formfld' type='text' name='accountcode' id='accountcode' maxlength='255' value='".escape($accountcode)."'>\n";
			echo "    <br />\n";
			echo "    ".$text['description-accountcode']."\n";
			echo "</td>\n";
			echo "</tr>\n";
	}

	if (permission_exists('device_edit') && (empty($extension_type) || $extension_type != 'virtual')) {
		if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/devices')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-provisioning']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "		<input type='hidden' name='device_line_uuid' id='device_line_uuid' value=''>";
			echo "		<table cellpadding='0' cellspacing='0'>\n";
			echo "			<tr>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$text['label-line']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$text['label-device_address']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable' style='padding-left: 8px;'>\n";
			echo "					".$text['label-device_template']."&nbsp;\n";
			echo "				</td>\n";
			if ($action == 'update') {
				echo "				<td>&nbsp;</td>\n";
			}
			echo "			</tr>\n";
			if ($action == 'update') {
				if (is_array($device_lines) && @sizeof($device_lines) != 0) {
					foreach ($device_lines as $row) {
						$device_address = format_device_address($row['device_address']);
						echo "		<tr>\n";
						echo "			<td class='vtable'>".escape($row['line_number'])."</td>\n";
						echo "			<td class='vtable'><a href='".PROJECT_PATH."/app/devices/device_edit.php?id=".escape($row['device_uuid'])."'>".escape($device_address)."</a></td>\n";
						echo "			<td class='vtable' style='padding-left: 10px;'>".escape($row['device_template'])."&nbsp;</td>\n";
						//echo "			<td class='vtable'>".$row['device_description']."&nbsp;</td>\n";
						echo "			<td>\n";
						echo "				<a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('delete_type').value = 'device_line'; document.getElementById('delete_uuid').value = '".escape($row['device_line_uuid'])."'; document.getElementById('frm').submit(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a>\n";
						echo "			</td>\n";
						echo "		</tr>\n";
					}
				}
			}
			for ($d = 0; $d <= 4; $d++) {
				echo "		<tr>\n";
				echo "		<td ".($action == 'edit' ? "class='vtable'" : null).">";
				echo "			<select id='line_number' name='devices[".$d."][line_number]' class='formfld' style='width: auto;' onchange=\"".escape($onchange ?? '')."\">\n";
				echo "			<option value=''></option>\n";
				for ($n = 1; $n <=99; $n++) {
					echo "		<option value='".escape($n)."'>".escape($n)."</option>\n";
				}
				echo "			</select>\n";
				echo "		</td>\n";

				echo "		<td ".($action == 'edit' ? "class='vtable'" : null).">";
				echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
				echo "				<tr>\n";
				echo "					<td nowrap='nowrap'>\n";
				?>
				<script>
				var Objs;
				function changeToInput_device_address_<?php echo $d; ?>(obj){
					tb=document.createElement('INPUT');
					tb.type='text';
					tb.name=obj.name;
					tb.className='formfld';
					tb.setAttribute('id', 'device_address_<?php echo $d; ?>');
					tb.setAttribute('style', 'width: 250px;');
					tb.value=obj.options[obj.selectedIndex].value;
					document.getElementById('btn_select_to_input_device_address_<?php echo $d; ?>').style.display = 'none';
					tbb=document.createElement('INPUT');
					tbb.setAttribute('class', 'btn');
					tbb.setAttribute('style', 'margin-left: 4px;');
					tbb.type='button';
					tbb.value=$("<div />").html('&#9665;').text();
					tbb.objs=[obj,tb,tbb];
					tbb.onclick=function(){ replace_device_address_<?php echo $d; ?>(this.objs); }
					obj.parentNode.insertBefore(tb,obj);
					obj.parentNode.insertBefore(tbb,obj);
					obj.parentNode.removeChild(obj);
					replace_device_address_<?php echo $d; ?>(this.objs);
				}

				function replace_device_address_<?php echo $d; ?>(obj){
					obj[2].parentNode.insertBefore(obj[0],obj[2]);
					obj[0].parentNode.removeChild(obj[1]);
					obj[0].parentNode.removeChild(obj[2]);
					document.getElementById('btn_select_to_input_device_address_<?php echo $d; ?>').style.display = 'inline';
				}
				</script>
				<?php
				echo "						<select id='device_address_".$d."' name='devices[".$d."][device_address]' class='formfld' style='width: 250px;' onchange=\"changeToInput_device_address_".$d."(this); this.style.visibility='hidden';\">\n";
				echo "							<option value=''></option>\n";
				if (is_array($devices) && @sizeof($devices) != 0) {
					foreach ($devices as $field) {
						if (!empty($field["device_address"])) {
							$selected = !empty($field_current_value) && $field_current_value == $field["device_address"] ? "selected='selected'" : null;
							echo "							<option value='".escape($field["device_address"])."' ".$selected.">".escape(format_device_address($field["device_address"])).(!empty($field['device_model']) || !empty($field['device_description']) ? " - ".escape($field['device_model'])." ".escape($field['device_description']) : null)."</option>\n";
						}
					}
				}
				if (permission_exists('device_address_uuid') && ($limit_devices === null || $total_devices < $limit_devices)) {
					echo "							<option disabled='disabled'></option>\n";
					echo "							<option value='UUID'>".$text['label-generate']."</option>\n";
				}
				echo "						</select>\n";
				echo "						<input type='button' id='btn_select_to_input_device_address_".$d."' class='btn' alt='".$text['button-back']."' onclick=\"changeToInput_device_address_".$d."(document.getElementById('device_address_".$d."')); this.style.visibility='hidden';\" value='&#9665;'>\n";
				echo "					</td>\n";
				echo "				</tr>\n";
				echo "			</table>\n";

				echo "		</td>\n";
				echo "		<td ".($action == 'edit' ? "class='vtable'" : null)." style='padding-left: 5px;'>";
				$device = new device;
				$template_dir = $device->get_template_dir();
				echo "			<select id='device_template' name='devices[".$d."][device_template]' class='formfld'>\n";
				echo "				<option value=''></option>\n";
				if (is_dir($template_dir) && is_array($device_vendors)) {
					foreach($device_vendors as $row) {
						echo "			<optgroup label='".escape($row["name"])."'>\n";
						if (is_dir($template_dir.'/'.$row["name"])) {
							$templates = scandir($template_dir.'/'.$row["name"]);
							foreach($templates as $dir) {
								if (!empty($dir) && $dir != "." && $dir != ".." && $dir[0] != '.' && !empty($template_dir) && is_dir($template_dir.'/'.$row["name"].'/'.$dir)) {
									$selected = !empty($device_template) && $device_template == $row["name"]."/".$dir ? "selected='selected'" : null;
									echo "				<option value='".escape($row["name"])."/".escape($dir)."' ".$selected.">".escape($row["name"])."/".escape($dir)."</option>\n";
								}
							}
						}
						echo "			</optgroup>\n";
					}
				}
				echo "			</select>\n";
				echo "		</td>\n";
				if (is_array($device_lines) && @sizeof($device_lines) != 0) {
					echo "		<td>\n";
					echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add')]);
					echo "		</td>\n";
				}
				echo "	</tr>\n";

				break; //show one empty row whether adding or editing
			}
			echo "		</table>\n";
			echo "		<br />\n";
			echo $text['description-provisioning']."\n";

			echo "</td>\n";
			echo "</tr>\n";
		}
	}

	if (permission_exists("effective_caller_id_name")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-effective_caller_id_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='effective_caller_id_name' maxlength='255' value=\"".escape($effective_caller_id_name ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-effective_caller_id_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("effective_caller_id_number")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-effective_caller_id_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='effective_caller_id_number' min='0' step='1' maxlength='255' value=\"".escape($effective_caller_id_number ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-effective_caller_id_number']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("outbound_caller_id_name")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-outbound_caller_id_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (permission_exists('outbound_caller_id_select')) {
			if (!empty($destinations)) {
				echo "	<select name='outbound_caller_id_name' id='outbound_caller_id_name' class='formfld'>\n";
				echo "	<option value=''></option>\n";
				foreach ($destinations as $row) {
					if(!empty($row["destination_caller_id_name"])){
						if (!empty($outbound_caller_id_name) && $row["destination_caller_id_name"] == $outbound_caller_id_name) {
							echo "		<option value='".escape($row["destination_caller_id_name"])."' selected='selected'>".escape($row["destination_caller_id_name"])."</option>\n";
						}
						else {
							echo "		<option value='".escape($row["destination_caller_id_name"])."'>".escape($row["destination_caller_id_name"])."</option>\n";
						}
					}
				}
				echo "		</select>\n";
				echo "<br />\n";
				echo $text['description-outbound_caller_id_name-select']."\n";
			}
			else {
				echo "	<input type='button' class='btn' name='' alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
			}
		}
		else {
			echo "    <input class='formfld' type='text' name='outbound_caller_id_name' maxlength='255' value=\"".escape($outbound_caller_id_name ?? '')."\">\n";
			echo "<br />\n";
			echo $text['description-outbound_caller_id_name-custom']."\n";
		}
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("outbound_caller_id_number")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-outbound_caller_id_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (permission_exists('outbound_caller_id_select')) {
			if (!empty($destinations)) {
				echo "	<select name='outbound_caller_id_number' id='outbound_caller_id_number' class='formfld'>\n";
				echo "	<option value=''></option>\n";
				foreach ($destinations as $row) {
					$tmp = $row["destination_caller_id_number"];
					if(empty($tmp)){
						$tmp = $row["destination_number"];
					}
					if(!empty($tmp)){
						if (!empty($outbound_caller_id_number) && $outbound_caller_id_number == $tmp) {
							echo "		<option value='".escape($tmp)."' selected='selected'>".escape($tmp)."</option>\n";
						}
						else {
							echo "		<option value='".escape($tmp)."'>".escape($tmp)."</option>\n";
						}
					}
				}
				echo "		</select>\n";
				echo "<br />\n";
				echo $text['description-outbound_caller_id_number-select']."\n";
			}
			else {
				echo "	<input type='submit' class='btn' name='' alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
			}
		}
		else {
			echo "    <input class='formfld' type='text' name='outbound_caller_id_number' maxlength='255' min='0' step='1' value=\"".escape($outbound_caller_id_number ?? '')."\">\n";
			echo "<br />\n";
			echo $text['description-outbound_caller_id_number-custom']."\n";
		}
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("emergency_caller_id_name")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-emergency_caller_id_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (permission_exists('emergency_caller_id_select')) {
			if (!empty($emergency_destinations)) {
				echo "	<select name='emergency_caller_id_name' id='emergency_caller_id_name' class='formfld'>\n";
				echo "		<option value=''></option>\n";
				foreach ($emergency_destinations as $row) {
					$tmp = $row["destination_caller_id_name"];
					if(empty($tmp)){
						$tmp = $row["destination_description"];
					}
					if(!empty($tmp)){
						if ($emergency_caller_id_name == $tmp) {
							echo "		<option value='".escape($tmp)."' selected='selected'>".escape($tmp)."</option>\n";
						}
						else {
							echo "		<option value='".escape($tmp)."'>".escape($tmp)."</option>\n";
						}
					}
				}
				echo "	</select>\n";
			}
			else {
				echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
			}
		}
		else {
			echo "	<input class='formfld' type='text' name='emergency_caller_id_name' maxlength='255' value=\"".escape($emergency_caller_id_name ?? '')."\">\n";
		}
		echo "<br />\n";
		if (permission_exists('outbound_caller_id_select') && count($destinations) > 0) {
			echo $text['description-emergency_caller_id_name-select']."\n";
		}
		else {
			echo $text['description-emergency_caller_id_name']."\n";
		}
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("emergency_caller_id_number")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-emergency_caller_id_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (permission_exists('emergency_caller_id_select')) {
			if (!empty($emergency_destinations)) {
				echo "	<select name='emergency_caller_id_number' id='emergency_caller_id_number' class='formfld'>\n";
				if (permission_exists('emergency_caller_id_select_empty')) {
					echo "		<option value=''></option>\n";
				}
				foreach ($emergency_destinations as $row) {
					$tmp = $row["destination_caller_id_number"];
					if(empty($tmp)){
						$tmp = $row["destination_number"];
					}
					if(!empty($tmp)){
						if ($emergency_caller_id_number == $tmp) {
							echo "		<option value='".escape($tmp)."' selected='selected'>".escape($tmp)."</option>\n";
						}
						else {
							echo "		<option value='".escape($tmp)."'>".escape($tmp)."</option>\n";
						}
					}
				}
				echo "		</select>\n";
			}
			else {
				echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
			}
		}
		else {
			echo "    <input class='formfld' type='text' name='emergency_caller_id_number' maxlength='255' min='0' step='1' value=\"".escape($emergency_caller_id_number ?? '')."\">\n";
		}
		echo "<br />\n";
		if (permission_exists('emergency_caller_id_select') && !empty($emergency_destinations)){
			echo $text['description-emergency_caller_id_number-select']."\n";
		}
		elseif (permission_exists('outbound_caller_id_select') && !empty($destinations)) {
			echo $text['description-emergency_caller_id_number-select']."\n";
		}
		else {
			echo $text['description-emergency_caller_id_number']."\n";
		}
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("extension_directory")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-directory_full_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='directory_first_name' maxlength='255' value=\"".escape($directory_first_name ?? '')."\">\n";
		echo "    <input class='formfld' type='text' name='directory_last_name' maxlength='255' value=\"".escape($directory_last_name ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-directory_full_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-directory_visible']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='directory_visible'>\n";
		if (!empty($directory_visible) && $directory_visible == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if (!empty($directory_visible) && $directory_visible == "false") {
			echo "    <option value='false' selected >".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-directory_visible']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-directory_exten_visible']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='directory_exten_visible'>\n";
		if (!empty($directory_exten_visible) && $directory_exten_visible == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if (!empty($directory_exten_visible) && $directory_exten_visible == "false") {
			echo "    <option value='false' selected >".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-directory_exten_visible']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("extension_max_registrations")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-max_registrations']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='max_registrations' maxlength='255' value=\"".escape($max_registrations ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-max_registrations']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("extension_limit")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-limit_max']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='limit_max' maxlength='255' value=\"".escape($limit_max ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-limit_max']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-limit_destination']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='limit_destination' maxlength='255' value=\"".escape($limit_destination ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-limit_destination']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('voicemail_edit') && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='voicemail_enabled'>\n";
		echo "    <option value=''></option>\n";
		if ($voicemail_enabled == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($voicemail_enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-voicemail_enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_mail_to']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='voicemail_mail_to' maxlength='1024' value=\"".escape($voicemail_mail_to ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-voicemail_mail_to']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists('voicemail_transcription_enabled') && $transcribe_enabled) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-voicemail_transcription_enabled']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<select class='formfld' name='voicemail_transcription_enabled' id='voicemail_transcription_enabled'>\n";
			echo "    <option value=''></option>\n";
			echo "    	<option value='true' ".(($voicemail_transcription_enabled == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
			echo "    	<option value='false' ".(($voicemail_transcription_enabled == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
			echo "	</select>\n";
			echo "<br />\n";
			echo $text['description-voicemail_transcription_enabled']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		if (permission_exists('voicemail_file')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "    ".$text['label-voicemail_file']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='voicemail_file' id='voicemail_file' onchange=\"if (this.selectedIndex != 2) { document.getElementById('voicemail_local_after_email').selectedIndex = 0; }\">\n";
			echo "    	<option value=''>".$text['option-voicemail_file_listen']."</option>\n";
			echo "    	<option value='link' ".(($voicemail_file == "link") ? "selected='selected'" : null).">".$text['option-voicemail_file_link']."</option>\n";
			echo "    	<option value='attach' ".(($voicemail_file == "attach") ? "selected='selected'" : null).">".$text['option-voicemail_file_attach']."</option>\n";
			echo "    </select>\n";
			echo "<br />\n";
			echo $text['description-voicemail_file']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		if (permission_exists('voicemail_local_after_email')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "    ".$text['label-voicemail_local_after_email']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='voicemail_local_after_email' id='voicemail_local_after_email' onchange=\"if (this.selectedIndex == 1) { document.getElementById('voicemail_file').selectedIndex = 2; }\">\n";
			echo "    	<option value='true' ".(($voicemail_local_after_email == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
			echo "    	<option value='false' ".(($voicemail_local_after_email == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
			echo "    </select>\n";
			echo "<br />\n";
			echo $text['description-voicemail_local_after_email']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
	}

	if (permission_exists('extension_missed_call')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-missed_call']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='missed_call_app' id='missed_call_app' onchange=\"if (this.selectedIndex != 0) { document.getElementById('missed_call_data').style.display = ''; document.getElementById('missed_call_data').focus(); } else { document.getElementById('missed_call_data').style.display='none'; }\">\n";
		echo "		<option value=''></option>\n";
		echo "    	<option value='email' ".((!empty($missed_call_app) && $missed_call_app == "email" && !empty($missed_call_data) && !empty($missed_call_data)) ? "selected='selected'" : null).">".$text['label-email']."</option>\n";
		//echo "    	<option value='text' ".(($missed_call_app == "text" && !empty($missed_call_data)) ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		//echo "    	<option value='url' ".(($missed_call_app == "url" && !empty($missed_call_data)) ? "selected='selected'" : null).">".$text['label-url']."</option>\n";
		echo "    </select>\n";
		$missed_call_data = !empty($missed_call_app) && $missed_call_app == 'text' ? format_phone($missed_call_data ?? '') : $missed_call_data ?? '';
		echo "    <input class='formfld' type='text' name='missed_call_data' id='missed_call_data' maxlength='255' value=\"".escape($missed_call_data ?? '')."\" style='min-width: 200px; width: 200px; ".((empty($missed_call_app) || empty($missed_call_data)) ? "display: none;" : null)."'>\n";
		echo "<br />\n";
		echo $text['description-missed_call']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_toll')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-toll_allow']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (!empty($_SESSION['toll allow']['name']) && is_array($_SESSION['toll allow']['name'])) {
			echo "	<select class='formfld' name='toll_allow' id='toll_allow'>\n";
			echo "		<option value=''></option>\n";
			foreach ($_SESSION['toll allow']['name'] as $name) {
				if ($name == $toll_allow) {
					echo "		<option value='".escape($name)."' selected='selected'>".escape($name)."</option>\n";
				}
				else {
					echo "		<option value='".escape($name)."'>".escape($name)."</option>\n";
				}
			}
			echo "	</select>\n";
		}
		else {
			echo "    <input class='formfld' type='text' name='toll_allow' maxlength='255' value=\"".escape($toll_allow ?? '')."\">\n";
		}
		echo "<br />\n";
		echo $text['description-toll_allow']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='call_timeout' maxlength='255' min='1' step='1' value=\"".escape($call_timeout ?? '')."\">\n";
	echo "<br />\n";
	echo $text['description-call_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists("extension_call_group")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-call_group']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (!empty($settings->get('call_group', 'name')) && is_array($settings->get('call_group', 'name'))) {
			echo "	<select class='formfld' name='call_group'>\n";
			echo "		<option value=''></option>\n";
			foreach ($settings->get('call_group', 'name') as $name) {
				if ($name == $call_group) {
					echo "		<option value='".escape($name)."' selected='selected'>".escape($name)."</option>\n";
				}
				else {
					echo "		<option value='".escape($name)."'>".escape($name)."</option>\n";
				}
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' type='text' name='call_group' maxlength='255' value=\"".escape($call_group ?? '')."\">\n";
		}
		echo "<br />\n";
		echo $text['description-call_group']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_call_screen')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-call_screen_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='call_screen_enabled'>\n";
		echo "    <option value=''></option>\n";
		if ($call_screen_enabled == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($call_screen_enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-call_screen_enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_user_record')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-user_record']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='user_record'>\n";
		echo "    <option value=''>".$text['label-user_record_none']."</option>\n";
		if ($user_record == "all") {
			echo "    <option value='all' selected='selected'>".$text['label-user_record_all']."</option>\n";
		}
		else {
			echo "    <option value='all'>".$text['label-user_record_all']."</option>\n";
		}
		if ($user_record == "local") {
			echo "    <option value='local' selected='selected'>".$text['label-user_record_local']."</option>\n";
		}
		else {
			echo "    <option value='local'>".$text['label-user_record_local']."</option>\n";
		}
		if ($user_record == "inbound") {
			echo "    <option value='inbound' selected='selected'>".$text['label-user_record_inbound']."</option>\n";
		}
		else {
			echo "    <option value='inbound'>".$text['label-user_record_inbound']."</option>\n";
		}
		if ($user_record == "outbound") {
			echo "    <option value='outbound' selected='selected'>".$text['label-user_record_outbound']."</option>\n";
		}
		else {
			echo "    <option value='outbound'>".$text['label-user_record_outbound']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-user_record']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/music_on_hold') && permission_exists('extension_hold_music')) {
		echo "<tr>\n";
		echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-hold_music']."\n";
		echo "</td>\n";
		echo "<td width=\"70%\" class='vtable' align='left'>\n";
		$options = '';
		$moh = new switch_music_on_hold;
		echo $moh->select('hold_music', $hold_music ?? '', $options);
		echo "	<br />\n";
		echo $text['description-hold_music']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_language')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-language']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <select class='formfld' type='text' name='extension_language'>\n";
		echo "		<option></option>\n";
		if (!empty($extension_language) && !empty($extension_dialect) && !empty($extension_voice)) {
			$language_formatted = $extension_language."-".$extension_dialect." ".$extension_voice;
			echo "		<option value='".escape($extension_language.'/'.$extension_dialect.'/'.$extension_voice)."' selected='selected'>".escape($language_formatted)."</option>\n";
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
	}

	if (permission_exists('extension_type')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-extension_type']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='extension_type' id='extension_type'>\n";
		echo "		<option value=''></option>\n";
		echo "		<option value='default' ".(($extension_type == "default") ? "selected='selected'" : null).">".$text['label-default']."</option>\n";
		echo "		<option value='virtual' ".(($extension_type == "virtual") ? "selected='selected'" : null).">".$text['label-virtual']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-extension_type']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='domain_uuid'>\n";
		foreach ($_SESSION['domains'] as $row) {
			if ($row['domain_uuid'] == $domain_uuid) {
				echo "    <option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
			}
			else {
				echo "    <option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
			}
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("extension_user_context")) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-user_context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='user_context' maxlength='255' value=\"".escape($user_context ?? '')."\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-user_context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	//--- begin: show_advanced -----------------------

	if (permission_exists("extension_advanced")) {
		echo "<tr>\n";
		echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

		echo "	<div id=\"show_advanced_box\">\n";
		echo "		<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "		<tr>\n";
		echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">&nbsp;</td>\n";
		echo "		<td width=\"70%\" class=\"vtable\">\n";
		echo button::create(['type'=>'button','label'=>$text['button-advanced'],'icon'=>'tools','onclick'=>'show_advanced_config();']);
		echo "		</td>\n";
		echo "		</tr>\n";
		echo "		</table>\n";
		echo "	</div>\n";

		echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
		echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";

		echo "<tr>\n";
		echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-auth_acl']."\n";
		echo "</td>\n";
		echo "<td width=\"70%\" class='vtable' align='left'>\n";
		echo "   <input class='formfld' type='text' name='auth_acl' maxlength='255' value=\"".escape($auth_acl ?? '')."\">\n";
		echo "   <br />\n";
		echo $text['description-auth_acl']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists("extension_cidr")) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "    ".$text['label-cidr']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <input class='formfld' type='text' name='cidr' maxlength='255' value=\"".escape($cidr ?? '')."\">\n";
			echo "<br />\n";
			echo $text['description-cidr']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-sip_force_contact']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='sip_force_contact'>\n";
		echo "    <option value=''></option>\n";
		switch ($sip_force_contact ?? null) {
			case "NDLB-connectile-dysfunction": 		$selected[1] = "selected='selected'"; 	break;
			case "NDLB-connectile-dysfunction-2.0": 	$selected[2] = "selected='selected'"; 	break;
			case "NDLB-tls-connectile-dysfunction": 	$selected[3] = "selected='selected'"; 	break;
		}
		echo "    <option value='NDLB-connectile-dysfunction' ".($selected[1] ?? '').">".$text['label-rewrite_contact_ip_and_port']."</option>\n";
		echo "    <option value='NDLB-connectile-dysfunction-2.0' ".($selected[2] ?? '').">".$text['label-rewrite_contact_ip_and_port_2']."</option>\n";
		echo "    <option value='NDLB-tls-connectile-dysfunction' ".($selected[3] ?? '').">".$text['label-rewrite_tls_contact_port']."</option>\n";
		unset($selected);
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-sip_force_contact']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-sip_force_expires']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='number' name='sip_force_expires' maxlength='255' min='1' step='1' value=\"".escape($sip_force_expires ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-sip_force_expires']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists('extension_nibble_account')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "    ".$text['label-nibble_account']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <input class='formfld' type='text' name='nibble_account' maxlength='255' value=\"".escape($nibble_account ?? '')."\">\n";
			echo "<br />\n";
			echo $text['description-nibble_account']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-mwi_account']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='mwi_account' maxlength='255' value=\"".escape($mwi_account ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-mwi_account']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-sip_bypass_media']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='sip_bypass_media'>\n";
		echo "    <option value=''></option>\n";
		switch ($sip_bypass_media ?? null) {
			case "bypass-media" : 				$selected[1] = "selected='selected'"; 	break;
			case "bypass-media-after-bridge" : 	$selected[2] = "selected='selected'"; 	break;
			case "proxy-media" : 				$selected[3] = "selected='selected'"; 	break;
		}
		echo "    <option value='bypass-media' ".($selected[1] ?? '').">".$text['label-bypass_media']."</option>\n";
		echo "    <option value='bypass-media-after-bridge'".($selected[2] ?? '').">".$text['label-bypass_media_after_bridge']."</option>\n";
		echo "    <option value='proxy-media'".($selected[3] ?? '').">".$text['label-proxy_media']."</option>\n";
		unset($selected);
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-sip_bypass_media']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists('extension_absolute_codec_string')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "    ".$text['label-absolute_codec_string']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <input class='formfld' type='text' name='absolute_codec_string' maxlength='255' value=\"".escape($absolute_codec_string ?? '')."\">\n";
			echo "<br />\n";
			echo $text['description-absolute_codec_string']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		if (permission_exists('extension_force_ping')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "    ".$text['label-force_ping']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='force_ping'>\n";
			echo "    	<option value=''></option>\n";
			echo "    	<option value='true' ".(!empty($force_ping) && $force_ping == "true" ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
			echo "    	<option value='false' ".(!empty($force_ping) && $force_ping == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
			echo "    </select>\n";
			echo "<br />\n";
			echo $text['description-force_ping']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		if (permission_exists('extension_dial_string')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "    ".$text['label-dial_string']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <input class='formfld' type='text' name='dial_string' maxlength='4096' value=\"".escape($dial_string ?? '')."\">\n";
			echo "<br />\n";
			echo $text['description-dial_string']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "	</table>\n";
		echo "	</div>";

		echo "</td>\n";
		echo "</tr>\n";

	}
	//--- end: show_advanced -----------------------

	if (permission_exists('extension_enabled')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (substr($settings->get('theme', 'input_toggle_style'), 0, 6) == 'switch') {
			echo "	<label class='switch'>\n";
			echo "		<input type='checkbox' id='enabled' name='enabled' value='true' ".($enabled == 'true' ? "checked='checked'" : null).">\n";
			echo "		<span class='slider'></span>\n";
			echo "	</label>\n";
		}
		else {
			echo "	<select class='formfld' id='enabled' name='enabled'>\n";
			echo "		<option value='true' ".($enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
			echo "		<option value='false' ".($enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
			echo "	</select>\n";
		}
		echo "<br />\n";
		echo $text['description-enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input type='text' class='formfld' name='description' value=\"".escape($description ?? '')."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>\n";
	echo "<br><br>";

	if (isset($page) && is_numeric($page)) {
		echo "<input type='hidden' name='page' value='".$page."'>\n";
	}
	if ($action == "update") {
		echo "<input type='hidden' name='extension_uuid' value='".escape($extension_uuid)."'>\n";
		echo "<input type='hidden' name='id' id='id' value='".escape($extension_uuid)."'>";
		if (!permission_exists('extension_domain')) {
			echo "<input type='hidden' name='domain_uuid' id='domain_uuid' value='".$domain_uuid."'>";
		}
		echo "<input type='hidden' name='delete_type' id='delete_type' value=''>";
		echo "<input type='hidden' name='delete_uuid' id='delete_uuid' value=''>";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";
	echo "<script>\n";

//hide password fields before submit
	echo "	function submit_form() {\n";
	echo "		hide_password_fields();\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
