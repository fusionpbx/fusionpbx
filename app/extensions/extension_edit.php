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
	Copyright (C) 2008-2018 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('extension_add') || permission_exists('extension_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$extension_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get total extension count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['extensions']['numeric'] != '') {
			$sql = "select count(*) ";
			$sql .= "from v_extensions ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$total_extensions = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

			if ($total_extensions >= $_SESSION['limit']['extensions']['numeric']) {
				message::add($text['message-maximum_extensions'].' '.$_SESSION['limit']['extensions']['numeric'], 'negative');
				header('Location: extensions.php');
				exit;
			}
		}
	}

//get the http values and set them as php variables
	if (count($_POST) > 0) {

		//get the values from the HTTP POST and save them as PHP variables
			$extension = str_replace(' ','-',$_POST["extension"]);
			$number_alias = $_POST["number_alias"];
			$password = $_POST["password"];

			// server verification on account code
			$accountcode = $_POST["accountcode"];

			$effective_caller_id_name = $_POST["effective_caller_id_name"];
			$effective_caller_id_number = $_POST["effective_caller_id_number"];
			$outbound_caller_id_name = $_POST["outbound_caller_id_name"];
			$outbound_caller_id_number = $_POST["outbound_caller_id_number"];
			$emergency_caller_id_name = $_POST["emergency_caller_id_name"];
			$emergency_caller_id_number = $_POST["emergency_caller_id_number"];
			$directory_first_name = $_POST["directory_first_name"];
			$directory_last_name = $_POST["directory_last_name"];
			$directory_visible = $_POST["directory_visible"];
			$directory_exten_visible = $_POST["directory_exten_visible"];
			$limit_max = $_POST["limit_max"];
			$limit_destination = $_POST["limit_destination"];
			$device_uuid = $_POST["device_uuid"];
			$device_line = $_POST["device_line"];
			$voicemail_password = $_POST["voicemail_password"];
			$voicemail_enabled = $_POST["voicemail_enabled"];
			$voicemail_mail_to = $_POST["voicemail_mail_to"];
			$voicemail_file = $_POST["voicemail_file"];
			$voicemail_local_after_email = $_POST["voicemail_local_after_email"];
			$user_context = $_POST["user_context"];
			$range = $_POST["range"];
			$autogen_users = $_POST["autogen_users"];
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
			$nibble_account = $_POST["nibble_account"];
			$mwi_account = $_POST["mwi_account"];
			$sip_bypass_media = $_POST["sip_bypass_media"];
			$absolute_codec_string = $_POST["absolute_codec_string"];
			$force_ping = $_POST["force_ping"];
			$dial_string = $_POST["dial_string"];
			$enabled = $_POST["enabled"];
			$description = $_POST["description"];

			$voicemail_id = $extension;
			if (permission_exists('number_alias') && strlen($number_alias) > 0) {
				$voicemail_id = $number_alias;
			}
			if (!is_numeric($voicemail_id)) {
				$voicemail_id = null;
			}
			
			//change toll allow delimiter
			$toll_allow = str_replace(',',':', $toll_allow);
	}

//delete the user from the v_extension_users
	if ($_REQUEST["delete_type"] == "user" && is_uuid($_REQUEST["delete_uuid"]) && permission_exists("extension_delete")) {
		//set the variables
			$extension_uuid = $_REQUEST["id"];
			$user_uuid = $_REQUEST["delete_uuid"];
		//delete the group from the users
			$array['extension_users'][0]['extension_uuid'] = $extension_uuid;
			$array['extension_users'][0]['user_uuid'] = $user_uuid;

			$p = new permissions;
			$p->add('extension_user_delete', 'temp');

			$database = new database;
			$database->app_name = 'extensions';
			$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
			$database->delete($array);
			unset($array);

			$p->delete('extension_user_delete', 'temp');
	}

//delete the line from the v_device_lines
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/devices')) {
		if ($_REQUEST["delete_type"] == "device_line" && is_uuid($_REQUEST["delete_uuid"]) && permission_exists("extension_delete")) {
			//set the variables
				$device_line_uuid = $_REQUEST["delete_uuid"];
			//delete device_line
				$array['device_lines'][0]['device_line_uuid'] = $device_line_uuid;

				$p = new permissions;
				$p->add('device_line_delete', 'temp');

				$database = new database;
				$database->app_name = 'extensions';
				$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
				$database->delete($array);
				unset($array);

				$p->delete('device_line_delete', 'temp');
		}
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the domain_uuid
			$domain_uuid = permission_exists('extension_domain') ? $_POST["domain_uuid"] : $_SESSION['domain_uuid'];

		//check for all required data
			$msg = '';
			if (strlen($extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			if (permission_exists('extension_enabled')) {
				if (strlen($enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			}
			//if (strlen($description) == 0) { $msg .= $text['message-required']."Description<br>\n"; }
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

		//set the default user context
			if (permission_exists("extension_user_context")) {
				//allow a user assigned to super admin to change the user_context
			}
			else {
				//if the user_context was not set then set the default value
				$user_context = $_SESSION['domain_name'];
			}

		//prevent users from bypassing extension limit by using range
			if ($_SESSION['limit']['extensions']['numeric'] != '') {
				if ($total_extensions + $range > $_SESSION['limit']['extensions']['numeric']){
					$range = $_SESSION['limit']['extensions']['numeric'] - $total_extensions;
				}
			}

		//add or update the database
			if ($_POST["persistformvar"] != "true") {

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

				//add the user to the database
					$user_email = '';
					if ($_SESSION["user"]["unique"]["text"] != "global") {
						if ($autogen_users == "true") {
							$auto_user = $extension;
							for ($i=1; $i<=$range; $i++) {
								$user_last_name = $auto_user;
								$user_password = generate_password();
								user_add($auto_user, $user_password, $user_email);
								$generated_users[$i]['username'] = $auto_user;
								$generated_users[$i]['password'] = $user_password;
								$auto_user++;
							}
							unset($auto_user);
						}
					}

				//build the data array
					if (!isset($range)) { $range = 1; }
					for ($i=0; $i<$range; $i++) {

						//check if the extension exists
							if ($action == "add" && extension_exists($extension)) {
								//extension exists
							}
							else {

								//extension does not exist add it
									if ($action == "add" || $range > 1) {
										$extension_uuid = uuid();
										$voicemail_uuid = uuid();
										$password = generate_password();
									}

								//prepare the values
									//mwi account
										if (strlen($mwi_account) > 0) {
											if (strpos($mwi_account, '@') === false) {
												if (count($_SESSION["domains"]) > 1) {
													$mwi_account .= "@".$_SESSION['domain_name'];
												}
												else {
													$mwi_account .= "@\$\${domain}";
												}
											}
										}

								//enabled
									if (!permission_exists('extension_enabled')) {
										$enabled = "true";
									}

								//generate a password
									if ($action == "add" && strlen($password) == 0) {
										$password = generate_password();
									}
									if ($action == "update" && permission_exists('extension_password') && strlen($password) == 0) {
										$password = generate_password();
									}

								//create the data array
									$array["extensions"][$i]["domain_uuid"] = $domain_uuid;
									$array["extensions"][$i]["extension_uuid"] = $extension_uuid;
									$array["extensions"][$i]["extension"] = $extension;
									if (permission_exists('number_alias')) {
										$array["extensions"][$i]["number_alias"] = $number_alias;
									}
									if (strlen($password) > 0) {
										$array["extensions"][$i]["password"] = $password;
									}
									if (permission_exists('extension_accountcode')) {
										$array["extensions"][$i]["accountcode"] = $accountcode;
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
									$array["extensions"][$i]["directory_first_name"] = $directory_first_name;
									$array["extensions"][$i]["directory_last_name"] = $directory_last_name;
									$array["extensions"][$i]["directory_visible"] = $directory_visible;
									$array["extensions"][$i]["directory_exten_visible"] = $directory_exten_visible;
									$array["extensions"][$i]["limit_max"] = $limit_max;
									$array["extensions"][$i]["limit_destination"] = $limit_destination;
									$array["extensions"][$i]["user_context"] = $user_context;
									if (permission_exists('extension_missed_call')) {
										$array["extensions"][$i]["missed_call_app"] = $missed_call_app;
										$array["extensions"][$i]["missed_call_data"] = $missed_call_data;
									}
									if (permission_exists('extension_toll')) {
										$array["extensions"][$i]["toll_allow"] = $toll_allow;
									}
									if (strlen($call_timeout) > 0) {
										$array["extensions"][$i]["call_timeout"] = $call_timeout;
									}
									$array["extensions"][$i]["call_group"] = $call_group;
									$array["extensions"][$i]["call_screen_enabled"] = $call_screen_enabled;
									if (permission_exists('extension_user_record')) {
										$array["extensions"][$i]["user_record"] = $user_record;
									}
									$array["extensions"][$i]["hold_music"] = $hold_music;
									$array["extensions"][$i]["auth_acl"] = $auth_acl;
									if (permission_exists("extension_cidr")) {
										$array["extensions"][$i]["cidr"] = $cidr;
									}
									$array["extensions"][$i]["sip_force_contact"] = $sip_force_contact;
									$array["extensions"][$i]["sip_force_expires"] = $sip_force_expires;
									if (permission_exists('extension_nibble_account')) {
										if (strlen($nibble_account) > 0) {
											$array["extensions"][$i]["nibble_account"] = $nibble_account;
										}
									}
									if (strlen($mwi_account) > 0) {
										$array["extensions"][$i]["mwi_account"] = $mwi_account;
									}
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
									$array["extensions"][$i]["enabled"] = $enabled;
									$array["extensions"][$i]["description"] = $description;

							}

						//add or update voicemail
							if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
								//set the voicemail password
									if (strlen($voicemail_password) == 0) {
										$voicemail_password = generate_password($_SESSION['voicemail']['password_length']['numeric'], 1);
									}

								// build voicemail
									if ($voicemail_id !== NULL) {
										//get the voicemail_uuid
											$sql = "select voicemail_uuid from v_voicemails ";
											$sql .= "where voicemail_id = :voicemail_id ";
											$sql .= "and domain_uuid = :domain_uuid ";
											$parameters['voicemail_id'] = $voicemail_id;
											$parameters['domain_uuid'] = $domain_uuid;
											$database = new database;
											$row = $database->select($sql, $parameters, 'row');
											if (is_array($row) && @sizeof($row) != 0) {
												$voicemail_uuid = $row["voicemail_uuid"];
											}
											unset($sql, $parameters, $row);

										//if voicemail_uuid does not exist then get a new uuid
											if (!is_uuid($voicemail_uuid)) {
												$voicemail_uuid = uuid();
												$voicemail_tutorial = 'true';
											}

										//add the voicemail
											$array["voicemails"][$i]["domain_uuid"] = $domain_uuid;
											$array["voicemails"][$i]["voicemail_uuid"] = $voicemail_uuid;
											$array["voicemails"][$i]["voicemail_id"] = $voicemail_id;

											$array["voicemails"][$i]["voicemail_password"] = $voicemail_password;
											//$array["voicemails"][$i]["greeting_id"] = $greeting_id;
											//$array["voicemails"][$i]["voicemail_alternate_greet_id"] = $alternate_greet_id;
											$array["voicemails"][$i]["voicemail_mail_to"] = $voicemail_mail_to;
											//$array["voicemails"][$i]["voicemail_attach_file"] = $voicemail_attach_file;
											$array["voicemails"][$i]["voicemail_file"] = $voicemail_file;
											if (permission_exists('voicemail_local_after_email')) {
												$array["voicemails"][$i]["voicemail_local_after_email"] = $voicemail_local_after_email;
											}
											$array["voicemails"][$i]["voicemail_enabled"] = $voicemail_enabled;
											$array["voicemails"][$i]["voicemail_description"] = $description;
											$array["voicemails"][$i]["voicemail_tutorial"] = $voicemail_tutorial;
									}
							}

						//increment the extension number
							if ($action != "update") {
								$extension++;
								$voicemail_id = $extension;

								if (strlen($number_alias) > 0) {
									$number_alias++;
									$voicemail_id = $number_alias;
								}

								if (strlen($mwi_account) > 0) {
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
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$parameters['server_address'] = $_SESSION['domain_name'];
						$parameters['user_id'] = $extension;
						$database = new database;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}
				//update device key label
					if (strlen($effective_caller_id_name) > 0) {
						$sql = "update v_device_keys set ";
						$sql .= "device_key_label = :device_key_label ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and device_key_value = :device_key_value ";
						$parameters['device_key_label'] = $effective_caller_id_name;
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$parameters['device_key_value'] = $extension;
						$database = new database;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}
				//assign the user to the extension 
					if ($action == "update" && is_uuid($_POST["extension_users"][0]["user_uuid"])) {
						$array["extension_users"][0]["extension_user_uuid"] = uuid();
						$array["extension_users"][0]["domain_uuid"] = $_SESSION['domain_uuid'];
						$array["extension_users"][0]["user_uuid"] = $_POST["extension_users"][0]["user_uuid"];
						$array["extension_users"][0]["extension_uuid"] = $extension_uuid;
					}

				//assign the device to the extension 
					if ($action == "update" && strlen($_POST["devices"][0]["device_mac_address"]) > 0) {

						//set the variables
							$device_uuid = uuid();
							$device_line_uuid = uuid();
							$device_mac_address = $_POST["devices"][0]["device_mac_address"];
							$device_template = $_POST["devices"][0]["device_template"];
							$line_number = $_POST["devices"][0]["line_number"];

						//normalize the mac address
							$device_mac_address = strtolower($device_mac_address);
							$device_mac_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address);

						//get the device_uuid
							$sql = "select device_uuid from v_devices ";
							$sql .= "where device_mac_address = :device_mac_address ";
							$sql .= "and domain_uuid = :domain_uuid ";
							$parameters['device_mac_address'] = $device_mac_address;
							$parameters['domain_uuid'] = $domain_uuid;
							$database = new database;
							$row = $database->select($sql, $parameters, 'row');
							if (is_uuid($row['device_uuid'])) {
								$device_uuid = $row['device_uuid'];
							}
							unset($sql, $parameters);

						//set a default line number
							if (strlen($line_number) == 0) { $line_number = '1'; }

						//add the device and device lines to the array
							$array["devices"][0]["device_uuid"] = $device_uuid;
							$array["devices"][0]["domain_uuid"] = $_SESSION['domain_uuid'];
							$array["devices"][0]["device_mac_address"] = $device_mac_address;
							if (strlen($device_template) > 0) {
								$array["devices"][0]["device_template"] = $device_template;
							}
							$array["devices"][0]["device_enabled"] = "true";
							$array["devices"][0]["device_lines"][0]["device_uuid"] = $device_uuid;
							$array["devices"][0]["device_lines"][0]["device_line_uuid"] = $device_line_uuid;
							$array["devices"][0]["device_lines"][0]["domain_uuid"] = $_SESSION['domain_uuid'];
							$array["devices"][0]["device_lines"][0]["server_address"] = $_SESSION['domain_name'];
							$array["devices"][0]["device_lines"][0]["outbound_proxy_primary"] = $_SESSION['provision']['outbound_proxy_primary']['text'];
							$array["devices"][0]["device_lines"][0]["outbound_proxy_secondary"] = $_SESSION['provision']['outbound_proxy_secondary']['text'];
							$array["devices"][0]["device_lines"][0]["display_name"] = strlen($effective_caller_id_name) > 0 ? $effective_caller_id_name : $extension;
							$array["devices"][0]["device_lines"][0]["user_id"] = $extension;
							$array["devices"][0]["device_lines"][0]["auth_id"] = $extension;
							$array["devices"][0]["device_lines"][0]["password"] = $password;
							$array["devices"][0]["device_lines"][0]["line_number"] = $line_number;
							$array["devices"][0]["device_lines"][0]["sip_port"] = $_SESSION['provision']['line_sip_port']['numeric'];
							$array["devices"][0]["device_lines"][0]["sip_transport"] = $_SESSION['provision']['line_sip_transport']['text'];
							$array["devices"][0]["device_lines"][0]["register_expires"] = $_SESSION['provision']['line_register_expires']['numeric'];
							$array["devices"][0]["device_lines"][0]["enabled"] = "true";
					}

				//save to the data
					$database = new database;
					$database->app_name = 'extensions';
					$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
					$database->save($array);
					$message = $database->message;
					unset($array);

				//check the permissions
					if (permission_exists('extension_add') || permission_exists('extension_edit')) {

						//synchronize configuration
							if (is_writable($_SESSION['switch']['extensions']['dir'])) {
								require_once "app/extensions/resources/classes/extension.php";
								$ext = new extension;
								$ext->xml();
								unset($ext);
							}

						//write the provision files
							if (strlen($_SESSION['provision']['path']['text']) > 0) {
								if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/provision')) {
									$prov = new provision;
									$prov->domain_uuid = $domain_uuid;
									$response = $prov->write();
								}
							}

						//clear the cache
							$cache = new cache;
							$cache->delete("directory:".$extension."@".$user_context);
							if (permission_exists('number_alias') && strlen($number_alias) > 0) {
								$cache->delete("directory:".$number_alias."@".$user_context);
							}
					}

				//show the action and redirect the user
					if ($action == "add") {
							message::add($text['message-add']);
						//prepare for alternating the row style
							$c = 0;
							$row_style["0"] = "row_style0";
							$row_style["1"] = "row_style1";

						//show the action and redirect the user
							if (count($generated_users) == 0) {
								//action add
									header("Location: extension_edit.php?id=".$extension_uuid);
							}
							else {
								//auto-generate user with extension as login name
									require_once "resources/header.php";
									echo "<br />\n";
									echo "<div align='center'>\n";
									echo "	<table width='40%' border='0' cellpadding='0' cellspacing='0'>\n";
									echo "		<tr>\n";
									echo "			<td colspan='2'><strong>New User Accounts</strong></td>\n";
									echo "		</tr>\n";
									echo "		<tr>\n";
									echo "			<th>Username</th>\n";
									echo "			<th>Password</th>\n";
									echo "		</tr>\n";
									foreach ($generated_users as $tmp_user) {
										echo "		<tr>\n";
										echo "			<td valign='top' class='".$row_style[$c]."'>".$tmp_user['username']."</td>\n";
										echo "			<td valign='top' class='".$row_style[$c]."'>".$tmp_user['password']."</td>\n";
										echo "		</tr>\n";
									}
									$c = $c ? 0 : 1;
									echo "	</table>";
									echo "</div>\n";
									require_once "resources/footer.php";
							}
							exit;
					}
					if ($action == "update") {
						message::add($text['message-update']);
						header("Location: extension_edit.php?id=".$extension_uuid);
						return;
					}
			}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$extension_uuid = $_GET["id"];
		$sql = "select * from v_extensions ";
		$sql .= "where extension_uuid = :extension_uuid ";
		$parameters['extension_uuid'] = $extension_uuid;
		$database = new database;
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
			$enabled = $row["enabled"];
			$description = $row["description"];
		}
		unset($sql, $parameters, $row);

	//get the voicemail data
		if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
			//get the voicemails
				$sql = "select * from v_voicemails ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and voicemail_id = :voicemail_id ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['voicemail_id'] = is_numeric($number_alias) ? $number_alias : $extension;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					$voicemail_password = str_replace("#", "", $row["voicemail_password"]);
					$voicemail_mail_to = str_replace(" ", "", $row["voicemail_mail_to"]);
					$voicemail_file = $row["voicemail_file"];
					$voicemail_local_after_email = $row["voicemail_local_after_email"];
					$voicemail_enabled = $row["voicemail_enabled"];
					$voicemail_tutorial = $row["voicemail_tutorial"];
				}
				unset($sql, $parameters, $row);
		}

	}
	else {
		$voicemail_file = $_SESSION['voicemail']['voicemail_file']['text'];
		$voicemail_local_after_email = $_SESSION['voicemail']['keep_local']['boolean'];
	}

//get the device lines
	$sql = "select d.device_mac_address, d.device_template, d.device_description, l.device_line_uuid, l.device_uuid, l.line_number ";
	$sql .= "from v_device_lines as l, v_devices as d ";
	$sql .= "where (l.user_id = :user_id_1 or l.user_id = :user_id_2)";
	$sql .= "and l.domain_uuid = :domain_uuid ";
	$sql .= "and l.device_uuid = d.device_uuid ";
	$sql .= "order by l.line_number, d.device_mac_address asc ";
	$parameters['user_id_1'] = $extension;
	$parameters['user_id_2'] = $number_alias;
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$device_lines = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the devices
	$sql = "select * from v_devices ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by device_mac_address asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$devices = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the device vendors
	$sql = "select name ";
	$sql .= "from v_device_vendors ";
	$sql .= "where enabled = 'true' ";
	$sql .= "order by name asc ";
	$database = new database;
	$device_vendors = $database->select($sql, null, 'all');
	unset($sql);

//get assigned users
	if (is_uuid($extension_uuid)) {
		$sql = "select u.username, e.user_uuid ";
		$sql .= "from v_extension_users as e, v_users as u ";
		$sql .= "where e.user_uuid = u.user_uuid  ";
		$sql .= "and u.user_enabled = 'true' ";
		$sql .= "and e.domain_uuid = :domain_uuid ";
		$sql .= "and e.extension_uuid = :extension_uuid ";
		$sql .= "order by u.username asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['extension_uuid'] = $extension_uuid;
		$database = new database;
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
	if (is_array($assigned_user_uuids) && @sizeof($assigned_user_uuids) != 0) {
		foreach ($assigned_user_uuids as $index => $assigned_user_uuid) {
			$sql .= "and user_uuid <> :user_uuid_".$index." ";
			$parameters['user_uuid_'.$index] = $assigned_user_uuid;
		}
	}
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $assigned_user_uuids, $assigned_user_uuid);

//get the destinations
	$sql = "select * from v_destinations ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and destination_type = 'inbound' ";
	$sql .= "order by destination_number asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$destinations = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//change toll allow delimiter
	$toll_allow = str_replace(':',',', $toll_allow);

//set the defaults
	if (strlen($user_context) == 0) { $user_context = $_SESSION['domain_name']; }
	if (strlen($limit_max) == 0) { $limit_max = '5'; }
	if (strlen($limit_destination) == 0) { $limit_destination = 'error/user_busy'; }
	if (strlen($call_timeout) == 0) { $call_timeout = '30'; }
	if (strlen($call_screen_enabled) == 0) { $call_screen_enabled = 'false'; }

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
	echo "function show_advanced_config() {\n";
	echo "	$('#show_advanced_box').slideToggle();\n";
	echo "	$('#show_advanced').slideToggle();\n";
	echo "}\n";
	echo "\n";
	echo "function copy_extension() {\n";
	echo "	var new_ext = prompt('".$text['message-extension']."');\n";
	echo "	if (new_ext != null) {\n";
	echo "		if (!isNaN(new_ext)) {\n";
	echo "			document.location.href='extension_copy.php?id=".escape($extension_uuid)."&ext=' + new_ext;\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			var new_number_alias = prompt('".$text['message-number_alias']."');\n";
	echo "			if (new_number_alias != null) {\n";
	echo "				if (!isNaN(new_number_alias)) {\n";
	echo "					document.location.href='extension_copy.php?id=".escape($extension_uuid)."&ext=' + new_ext + '&alias=' + new_number_alias;\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "}\n";
	echo "</script>";

	echo "<form method='post' name='frm' id='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpdding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-extension-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-extension-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='extensions.php'\" value='".$text['button-back']."'>\n";
	if ($action == 'update' && (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb'))) {
		echo "	<input type='button' class='btn' alt='".$text['button-call_routing']."' onclick=\"window.location='../calls/call_edit.php?id=".escape($extension_uuid)."';\" value='".$text['button-call_routing']."'>\n";
	}
	if ($action == "update" && permission_exists('extension_copy')) {
		echo "	<input type='button' class='btn' alt='".$text['button-copy']."' onclick=\"copy_extension();\" value='".$text['button-copy']."'>\n";
	}
	echo "	<input type='submit' class='btn' value='".$text['button-save']."' onclick=''>\n";
	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='extension' autocomplete='new-password' maxlength='255' value=\"".escape($extension)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('number_alias')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-number_alias']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='number' name='number_alias' autocomplete='new-password' maxlength='255' min='0' step='1' value=\"".escape($number_alias)."\">\n";
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
		echo "    <input class='formfld' type='password' name='password' id='password' autocomplete='new-password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"".escape($password)."\">\n";
		echo "    <br />\n";
		echo "    ".$text['description-password']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if ($action == "add") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
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
		echo $text['description-range']."<br />\n";
		if ($_SESSION["user"]["unique"]["text"] != "global") {
			echo "<input type=\"checkbox\" name=\"autogen_users\" value=\"true\"> ".$text['checkbox-range']."<br>\n";
		}
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_user_edit') && $action == "update") {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-users']."</td>";
		echo "		<td class='vtable'>";
		if (count($assigned_users) > 0) {
			echo "		<table width='30%'>\n";
			foreach($assigned_users as $field) {
				echo "		<tr>\n";
				echo "			<td class='vtable'><a href='/core/users/user_edit.php?id=".escape($field['user_uuid'])."'>".escape($field['username'])."</a></td>\n";
				echo "			<td>\n";
				echo "				<a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('delete_type').value = 'user'; document.getElementById('delete_uuid').value = '".$field['user_uuid']."'; document.getElementById('frm').submit(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a>\n";
				//echo "				<a href='extension_edit.php?id=".escape($extension_uuid)."&domain_uuid=".$_SESSION['domain_uuid']."&user_uuid=".escape($field['user_uuid'])."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
				echo "			</td>\n";
				echo "		</tr>\n";
			}
			echo "		</table>\n";
			echo "		<br />\n";
		}

		echo "			<select name='extension_users[0][user_uuid]' id='user_uuid' class='formfld' style='width: auto;'>\n";
		echo "			<option value=''></option>\n";
		foreach($users as $field) {
			echo "			<option value='".escape($field['user_uuid'])."'>".escape($field['username'])."</option>\n";
		}
		echo "			</select>";
		echo "			<input type='submit' class='btn' value=\"".$text['button-add']."\" onclick=''>\n";

		echo "			<br>\n";
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
		echo "    <input class='formfld' type='password' name='voicemail_password' id='voicemail_password' autocomplete='new-password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value='".escape($voicemail_password)."'>\n";
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

	if (permission_exists('device_edit') && $action == "update") {
		if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/devices')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-provisioning']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "		<input type='hidden' name='device_line_uuid' id='device_line_uuid' value=''>";
			echo "		<table>\n";
			echo "			<tr>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$text['label-line']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$text['label-device_mac_address']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$text['label-device_template']."&nbsp;\n";
			echo "				</td>\n";

			echo "				<td>\n";
			//if (permission_exists('device_edit')) {
			//	echo "					<a href='device_line_edit.php?device_uuid=".escape($row['device_uuid'])."&id=".escape($row['device_line_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
			//}
			//if (permission_exists('device_delete')) {
			//	echo "					<a href='device_line_delete.php?device_uuid=".escape($row['device_uuid'])."&id=".escape($row['device_line_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			//}
			echo "				</td>\n";
			echo "			</tr>\n";
			foreach($device_lines as $row) {
				$device_mac_address = $row['device_mac_address'];
				$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);
				echo "		<tr>\n";
				echo "			<td class='vtable'>".escape($row['line_number'])."</td>\n";
				echo "			<td class='vtable'><a href='".PROJECT_PATH."/app/devices/device_edit.php?id=".escape($row['device_uuid'])."'>".escape($device_mac_address)."</a></td>\n";
				echo "			<td class='vtable'>".escape($row['device_template'])."&nbsp;</td>\n";
				//echo "			<td class='vtable'>".$row['device_description']."&nbsp;</td>\n";
				echo "			<td>\n";
				echo "				<a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('delete_type').value = 'device_line'; document.getElementById('delete_uuid').value = '".escape($row['device_line_uuid'])."'; document.getElementById('frm').submit(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a>\n";
				echo "			</td>\n";
				echo "		</tr>\n";
			}

			echo "		<tr>\n";
			echo "		<td class='vtable'>";
			echo "			<select id='line_number' name='devices[0][line_number]' class='formfld' style='width: auto;' onchange=\"".escape($onchange)."\">\n";
			echo "			<option value=''></option>\n";
			for ($n = 1; $n <=99; $n++) {
				echo "		<option value='".escape($n)."'>".escape($n)."</option>\n";
			}
			echo "			</select>\n";
			echo "		</td>\n";

			echo "		<td class='vtable'>";
			echo "			<table border='0' cellpadding='1' cellspacing='0'>\n";
			echo "			<tr>\n";
			echo "			<td id=\"cell_device_mac_address_1\" nowrap='nowrap'>\n";
			?>
			<script>
			var Objs;
			function changeToInput_device_mac_address(obj){
				tb=document.createElement('INPUT');
				tb.type='text';
				tb.name=obj.name;
				tb.className='formfld';
				tb.setAttribute('id', 'device_mac_address');
				tb.setAttribute('style', 'width: 80%;');
				tb.setAttribute('pattern', '^([0-9A-Fa-f]{2}[:-]?){5}([0-9A-Fa-f]{2})$');
				tb.value=obj.options[obj.selectedIndex].value;
				document.getElementById('btn_select_to_input_device_mac_address').style.visibility = 'hidden';
				tbb=document.createElement('INPUT');
				tbb.setAttribute('class', 'btn');
				tbb.setAttribute('style', 'margin-left: 4px;');
				tbb.type='button';
				tbb.value=$("<div />").html('&#9665;').text();
				tbb.objs=[obj,tb,tbb];
				tbb.onclick=function(){ replace_device_mac_address(this.objs); }
				obj.parentNode.insertBefore(tb,obj);
				obj.parentNode.insertBefore(tbb,obj);
				obj.parentNode.removeChild(obj);
				replace_device_mac_address(this.objs);
			}

			function replace_device_mac_address(obj){
				obj[2].parentNode.insertBefore(obj[0],obj[2]);
				obj[0].parentNode.removeChild(obj[1]);
				obj[0].parentNode.removeChild(obj[2]);
				document.getElementById('btn_select_to_input_device_mac_address').style.visibility = 'visible';
			}
			</script>
			<?php
			echo "				<select id=\"device_mac_address\" name=\"devices[0][device_mac_address]\" class='formfld' style='width: 180px;' onchange='changeToInput_device_mac_address(this);this.style.visibility = \"hidden\";'>\n";
			echo "					<option value=''></option>\n";
			if (count($devices) > 0) {
				foreach($devices as $field) {
					if (strlen($field["device_mac_address"]) > 0) {
						if ($field_current_value == $field["device_mac_address"]) {
							echo "					<option value=\"".escape($field["device_mac_address"])."\" selected=\"selected\">".escape($field["device_mac_address"])."</option>\n";
						}
						else {
							echo "					<option value=\"".escape($field["device_mac_address"])."\">".escape($field["device_mac_address"])."  ".escape($field['device_model'])." ".escape($field['device_description'])."</option>\n";
						}
					}
				}
			}
			echo "				</select>\n";
			echo "				<input type='button' id='btn_select_to_input_device_mac_address' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_device_mac_address(document.getElementById(\"device_mac_address\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
			echo "	</td>\n";
			echo "	</tr>\n";
			echo "	</table>\n";

			echo "		</td>\n";
			echo "		<td class='vtable'>";
			$device = new device;
			$template_dir = $device->get_template_dir();
			echo "<select id='device_template' name='devices[0][device_template]' class='formfld'>\n";
			echo "		<option value=''></option>\n";
			if (is_dir($template_dir) && is_array($device_vendors)) {
				foreach($device_vendors as $row) {
					echo "		<optgroup label='".escape($row["name"])."'>\n";
					$templates = scandir($template_dir.'/'.$row["name"]);
					foreach($templates as $dir) {
						if ($file != "." && $dir != ".." && $dir[0] != '.') {
							if (is_dir($template_dir . '/' . $row["name"] .'/'. $dir)) {
								if ($device_template == $row["name"]."/".$dir) {
									echo "			<option value='".escape($row["name"])."/".escape($dir)."' selected='selected'>".escape($row["name"])."/".escape($dir)."</option>\n";
								}
								else {
									echo "			<option value='".escape($row["name"])."/".escape($dir)."'>".$row["name"]."/".escape($dir)."</option>\n";
								}
							}
						}
					}
					echo "		</optgroup>\n";
				}
			}
			echo "</select>\n";
			echo "		</td>\n";
			echo "		<td>\n";
			echo "			<input type='submit' class='btn' value=\"".$text['button-add']."\" onclick=''>\n";
			echo "		</td>\n";
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
		echo "    <input class='formfld' type='text' name='effective_caller_id_name' maxlength='255' value=\"".escape($effective_caller_id_name)."\">\n";
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
		echo "    <input class='formfld' type='text' name='effective_caller_id_number' min='0' step='1' maxlength='255' value=\"".escape($effective_caller_id_number)."\">\n";
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
			if (count($destinations) > 0) {
				echo "	<select name='outbound_caller_id_name' id='outbound_caller_id_name' class='formfld'>\n";
				echo "	<option value=''></option>\n";
				foreach ($destinations as &$row) {
					$tmp = $row["destination_caller_id_name"];
					if(strlen($tmp) == 0){
						$tmp = $row["destination_description"];
					}
					if(strlen($tmp) > 0){
						if ($outbound_caller_id_name == $tmp) {
							echo "		<option value='".escape($tmp)."' selected='selected'>".escape($tmp)."</option>\n";
						}
						else {
							echo "		<option value='".escape($tmp)."'>".escape($tmp)."</option>\n";
						}
					}
				}
				echo "		</select>\n";
				echo "<br />\n";
				echo $text['description-outbound_caller_id_name-select']."\n";
			}
			else {
				echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
			}
		}
		else {
			echo "    <input class='formfld' type='text' name='outbound_caller_id_name' maxlength='255' value=\"".escape($outbound_caller_id_name)."\">\n";
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
			if (count($destinations) > 0) {
				echo "	<select name='outbound_caller_id_number' id='outbound_caller_id_number' class='formfld'>\n";
				echo "	<option value=''></option>\n";
				foreach ($destinations as &$row) {
					$tmp = $row["destination_caller_id_number"];
					if(strlen($tmp) == 0){
						$tmp = $row["destination_number"];
					}
					if(strlen($tmp) > 0){
						if ($outbound_caller_id_number == $tmp) {
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
				echo "	<input type=\"submit\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
			}
		}
		else {
			echo "    <input class='formfld' type='text' name='outbound_caller_id_number' maxlength='255' min='0' step='1' value=\"".escape($outbound_caller_id_number)."\">\n";
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
		if (permission_exists('outbound_caller_id_select')) {
			if (count($destinations) > 0) {
				echo "	<select name='emergency_caller_id_name' id='emergency_caller_id_name' class='formfld'>\n";
				echo "	<option value=''></option>\n";
				foreach ($destinations as &$row) {
					$tmp = $row["destination_caller_id_name"];
					if(strlen($tmp) == 0){
						$tmp = $row["destination_description"];
					}
					if(strlen($tmp) > 0){
						if ($emergency_caller_id_name == $tmp) {
							echo "		<option value='".escape($tmp)."' selected='selected'>".escape($tmp)."</option>\n";
						}
						else {
							echo "		<option value='".escape($tmp)."'>".escape($tmp)."</option>\n";
						}
					}
				}
				echo "		</select>\n";
				echo "<br />\n";
				echo $text['description-outbound_caller_id_name-select']."\n";
			}
			else {
				echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
			}
		}
		else {
			echo "    <input class='formfld' type='text' name='emergency_caller_id_name' maxlength='255' value=\"".escape($emergency_caller_id_name)."\">\n";
		}
		echo "<br />\n";
		echo $text['description-emergency_caller_id_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("emergency_caller_id_number")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-emergency_caller_id_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (permission_exists('outbound_caller_id_select')) {
			if (count($destinations) > 0) {
				echo "	<select name='emergency_caller_id_number' id='emergency_caller_id_number' class='formfld'>\n";
				echo "	<option value=''></option>\n";
				foreach ($destinations as &$row) {
					$tmp = $row["destination_caller_id_number"];
					if(strlen($tmp) == 0){
						$tmp = $row["destination_description"];
					}
					if(strlen($tmp) > 0){
						if ($emergency_caller_id_name == $tmp) {
							echo "		<option value='".escape($tmp)."' selected='selected'>".escape($tmp)."</option>\n";
						}
						else {
							echo "		<option value='".escape($tmp)."'>".escape($tmp)."</option>\n";
						}
					}
				}
				echo "		</select>\n";
				echo "<br />\n";
				echo $text['description-outbound_caller_id_name-select']."\n";
			}
			else {
				echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
			}
		}
		else {
			echo "    <input class='formfld' type='text' name='emergency_caller_id_number' maxlength='255' min='0' step='1' value=\"".escape($emergency_caller_id_number)."\">\n";
		}
		echo "<br />\n";
		echo $text['description-emergency_caller_id_number']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-directory_full_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='directory_first_name' maxlength='255' value=\"".escape($directory_first_name)."\">\n";
	echo "    <input class='formfld' type='text' name='directory_last_name' maxlength='255' value=\"".escape($directory_last_name)."\">\n";
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
	if ($directory_visible == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($directory_visible == "false") {
		echo "    <option value='false' selected >".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
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
	if ($directory_exten_visible == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($directory_exten_visible == "false") {
		echo "    <option value='false' selected >".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "<br />\n";
	echo $text['description-directory_exten_visible']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-limit_max']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='limit_max' maxlength='255' value=\"".escape($limit_max)."\">\n";
	echo "<br />\n";
	echo $text['description-limit_max']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-limit_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='limit_destination' maxlength='255' value=\"".escape($limit_destination)."\">\n";
	echo "<br />\n";
	echo $text['description-limit_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('voicemail_edit') && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='voicemail_enabled'>\n";
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
		echo "    <input class='formfld' type='text' name='voicemail_mail_to' maxlength='255' value=\"".escape($voicemail_mail_to)."\">\n";
		echo "<br />\n";
		echo $text['description-voicemail_mail_to']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_file']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='voicemail_file' id='voicemail_file' onchange=\"if (this.selectedIndex != 2) { document.getElementById('voicemail_local_after_email').selectedIndex = 0; }\">\n";
		echo "    	<option value='' ".(($voicemail_file == "listen") ? "selected='selected'" : null).">".$text['option-voicemail_file_listen']."</option>\n";
		echo "    	<option value='link' ".(($voicemail_file == "link") ? "selected='selected'" : null).">".$text['option-voicemail_file_link']."</option>\n";
		echo "    	<option value='attach' ".(($voicemail_file == "attach") ? "selected='selected'" : null).">".$text['option-voicemail_file_attach']."</option>\n";
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-voicemail_file']."\n";
		echo "</td>\n";
		echo "</tr>\n";

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
		echo "    	<option value='email' ".(($missed_call_app == "email" && $missed_call_data != '') ? "selected='selected'" : null).">".$text['label-email']."</option>\n";
		//echo "    	<option value='text' ".(($missed_call_app == "text" && $missed_call_data != '') ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		//echo "    	<option value='url' ".(($missed_call_app == "url" && $missed_call_data != '') ? "selected='selected'" : null).">".$text['label-url']."</option>\n";
		echo "    </select>\n";
		$missed_call_data = ($missed_call_app == 'text') ? format_phone($missed_call_data) : $missed_call_data;
		echo "    <input class='formfld' type='text' name='missed_call_data' id='missed_call_data' maxlength='255' value=\"".escape($missed_call_data)."\" style='min-width: 200px; width: 200px; ".(($missed_call_app == '' || $missed_call_data == '') ? "display: none;" : null)."'>\n";
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
		if (is_array($_SESSION['toll allow']['name'])) {
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
			echo "    <input class='formfld' type='text' name='toll_allow' maxlength='255' value=\"".escape($toll_allow)."\">\n";
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
	echo "	<input class='formfld' type='number' name='call_timeout' maxlength='255' min='1' step='1' value=\"".escape($call_timeout)."\">\n";
	echo "<br />\n";
	echo $text['description-call_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_group']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (is_array($_SESSION['call group']['name'])) {
		echo "	<select class='formfld' name='call_group'>\n";
		echo "		<option value=''></option>\n";
		foreach ($_SESSION['call group']['name'] as $name) {
			if ($name == $call_group) {
				echo "		<option value='".escape($name)."' selected='selected'>".escape($name)."</option>\n";
			}
			else {
				echo "		<option value='".escape($name)."'>".escape($name)."</option>\n";
			}
		}
		echo "	</select>\n";
	} else {
		echo "	<input class='formfld' type='text' name='call_group' maxlength='255' value=\"".escape($call_group)."\">\n";
	}
	echo "<br />\n";
	echo $text['description-call_group']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('extension_call_screen')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-call_screen_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='call_screen_enabled'>\n";
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

	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/music_on_hold')) {
		echo "<tr>\n";
		echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-hold_music']."\n";
		echo "</td>\n";
		echo "<td width=\"70%\" class='vtable' align='left'>\n";
		require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
		$options = '';
		$moh = new switch_music_on_hold;
		echo $moh->select('hold_music', $hold_music, $options);
		echo "	<br />\n";
		echo $text['description-hold_music']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("extension_user_context")) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-user_context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='user_context' maxlength='255' value=\"".escape($user_context)."\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-user_context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	//--- begin: show_advanced -----------------------

	echo "<tr>\n";
	echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

	echo "	<div id=\"show_advanced_box\">\n";
	echo "		<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "		<tr>\n";
	echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">&nbsp;</td>\n";
	echo "		<td width=\"70%\" class=\"vtable\">\n";
	echo "			<input type=\"button\" class=\"btn\" onClick=\"show_advanced_config()\" value=\"".$text['button-advanced']."\"></input>\n";
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
	echo "   <input class='formfld' type='text' name='auth_acl' maxlength='255' value=\"".escape($auth_acl)."\">\n";
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
		echo "    <input class='formfld' type='text' name='cidr' maxlength='255' value=\"".escape($cidr)."\">\n";
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
	switch ($sip_force_contact) {
		case "NDLB-connectile-dysfunction": 		$selected[1] = "selected='selected'"; 	break;
		case "NDLB-connectile-dysfunction-2.0": 	$selected[2] = "selected='selected'"; 	break;
		case "NDLB-tls-connectile-dysfunction": 	$selected[3] = "selected='selected'"; 	break;
	}
	echo "    <option value='NDLB-connectile-dysfunction' ".$selected[1].">".$text['label-rewrite_contact_ip_and_port']."</option>\n";
	echo "    <option value='NDLB-connectile-dysfunction-2.0' ".$selected[2].">".$text['label-rewrite_contact_ip_and_port_2']."</option>\n";
	echo "    <option value='NDLB-tls-connectile-dysfunction' ".$selected[3].">".$text['label-rewrite_tls_contact_port']."</option>\n";
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
	echo "    <input class='formfld' type='number' name='sip_force_expires' maxlength='255' min='1' step='1' value=\"".escape($sip_force_expires)."\">\n";
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
		echo "    <input class='formfld' type='text' name='nibble_account' maxlength='255' value=\"".escape($nibble_account)."\">\n";
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
	echo "    <input class='formfld' type='text' name='mwi_account' maxlength='255' value=\"".escape($mwi_account)."\">\n";
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
	switch ($sip_bypass_media) {
		case "bypass-media" : 				$selected[1] = "selected='selected'"; 	break;
		case "bypass-media-after-bridge" : 	$selected[2] = "selected='selected'"; 	break;
		case "proxy-media" : 				$selected[3] = "selected='selected'"; 	break;
	}
	echo "    <option value='bypass-media' ".$selected[1].">".$text['label-bypass_media']."</option>\n";
	echo "    <option value='bypass-media-after-bridge'".$selected[2].">".$text['label-bypass_media_after_bridge']."</option>\n";
	echo "    <option value='proxy-media'".$selected[3].">".$text['label-proxy_media']."</option>\n";
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
		echo "    <input class='formfld' type='text' name='absolute_codec_string' maxlength='255' value=\"".escape($absolute_codec_string)."\">\n";
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
		if ($force_ping == "") {
			echo "    <option value='' selected='selected'></option>\n";
		}
		else {
			echo "    <option value=''></option>\n";
		}
		if ($force_ping == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($force_ping == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-force_ping']."\n";
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

	if (permission_exists('extension_dial_string')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-dial_string']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='dial_string' maxlength='4096' value=\"".escape($dial_string)."\">\n";
		echo "<br />\n";
		echo $text['description-dial_string']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "	</table>\n";
	echo "	</div>";

	echo "</td>\n";
	echo "</tr>\n";

	//--- end: show_advanced -----------------------

	if (permission_exists('extension_enabled')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='enabled'>\n";
		if ($enabled == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
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
	echo "    <textarea class='formfld' name='description' rows='4'>".escape($description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='extension_uuid' value='".escape($extension_uuid)."'>\n";
		echo "		<input type='hidden' name='id' id='id' value='".escape($extension_uuid)."'>";
		if (!permission_exists('extension_domain')) {
			echo "		<input type='hidden' name='domain_uuid' id='domain_uuid' value='".$_SESSION['domain_uuid']."'>";
		}
		echo "		<input type='hidden' name='delete_type' id='delete_type' value=''>";
		echo "		<input type='hidden' name='delete_uuid' id='delete_uuid' value=''>";
	}
	echo "			<br>";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."' onclick=''>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
