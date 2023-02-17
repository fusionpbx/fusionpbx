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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_add') || permission_exists('device_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include the device class
	require_once "app/devices/resources/classes/device.php";

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$device_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get total device count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['devices']['numeric'] != '') {
			$sql = "select count(*) from v_devices where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$total_devices = $database->select($sql, $parameters, 'column');
			if ($total_devices >= $_SESSION['limit']['devices']['numeric']) {
				message::add($text['message-maximum_devices'].' '.$_SESSION['limit']['devices']['numeric'], 'negative');
				header('Location: devices.php');
				exit;
			}
			unset($sql, $parameters, $total_devices);
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($_POST['device_uuid'])) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $_POST['device_uuid'];

				switch ($_POST['action']) {
					case 'delete':
						if (permission_exists('device_delete')) {
							$obj = new device;
							$obj->delete($array);
						}
						break;
				}

				header('Location: devices.php');
				exit;
			}

		//device mac address
			if (permission_exists('device_mac_address')) {
				$device_mac_address = $_POST["device_mac_address"];
			}
			else {
				$sql = "select * from v_devices ";
				$sql .= "where device_uuid = :device_uuid ";
				$parameters['device_uuid'] = $device_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					$device_mac_address = $row["device_mac_address"];
				}
				unset($sql, $parameters, $row);
			}
		//devices
			$domain_uuid = $_POST["domain_uuid"];
			$device_uuid = $_POST["device_uuid"];
			//$device_provisioned_ip = $_POST["device_provisioned_ip"];
			$domain_uuid = $_POST["domain_uuid"];
			$device_label = $_POST["device_label"];
			$device_label = $_POST["device_label"];
			$device_user_uuid = $_POST["device_user_uuid"];
			$device_username = $_POST["device_username"];
			$device_password = $_POST["device_password"];
			$device_vendor = $_POST["device_vendor"];
			$device_location = $_POST["device_location"];
			$device_uuid_alternate = $_POST["device_uuid_alternate"];
			$device_model = $_POST["device_model"];
			$device_firmware_version = $_POST["device_firmware_version"];
			$device_enabled = $_POST["device_enabled"] ?: 'false';
			$device_template = $_POST["device_template"];
			$device_description = $_POST["device_description"];
		//lines
			$device_lines = $_POST["device_lines"];
			$device_lines_delete = $_POST["device_lines_delete"];
			//$line_number = $_POST["line_number"];
			//$server_address = $_POST["server_address"];
			//$outbound_proxy_primary = $_POST["outbound_proxy_primary"];
			//$outbound_proxy_secondary = $_POST["outbound_proxy_secondary"];
			//$label = $_POST["label"];
			//$display_name = $_POST["display_name"];
			//$user_id = $_POST["user_id"];
			//$auth_id = $_POST["auth_id"];
			//$password = $_POST["password"];
		//profile
			$device_profile_uuid = $_POST["device_profile_uuid"];
		//keys
			$device_keys = $_POST["device_keys"];
			$device_keys_delete = $_POST["device_keys_delete"];
			//$device_key_category = $_POST["device_key_category"];
			//$device_key_id = $_POST["device_key_id"];
			//$device_key_type = $_POST["device_key_type"];
			//$device_key_line = $_POST["device_key_line"];
			//$device_key_value = $_POST["device_key_value"];
			//$device_key_extension = $_POST["device_key_extension"];
			//$device_key_label = $_POST["device_key_label"];
			//$device_key_icon = $_POST["device_key_icon"];
		//settings
			$device_settings = $_POST["device_settings"];
			$device_settings_delete = $_POST["device_settings_delete"];
			//$device_setting_category = $_POST["device_setting_category"]);
			//$device_setting_subcategory = $_POST["device_setting_subcategory"];
			//$device_setting_name = $_POST["device_setting_name"];
			//$device_setting_value = $_POST["device_setting_value"];
			//$device_setting_enabled = $_POST["device_setting_enabled"];
			//$device_setting_description = $_POST["device_setting_description"];

		//normalize the mac address
			if (isset($device_mac_address) && strlen($device_mac_address) > 0) {
				$device_mac_address = strtolower($device_mac_address);
				$device_mac_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address);
			}
	}

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		$device_vendor = device::get_vendor($device_mac_address);
	}

//add or update the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: devices.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($device_mac_address) == 0) { $msg .= $text['message-required'].$text['label-device_mac_address']."<br>\n"; }
			//if (strlen($device_label) == 0) { $msg .= "Please provide: Label<br>\n"; }
			//if (strlen($device_vendor) == 0) { $msg .= "Please provide: Vendor<br>\n"; }
			//if (strlen($device_model) == 0) { $msg .= "Please provide: Model<br>\n"; }
			//if (strlen($device_firmware_version) == 0) { $msg .= "Please provide: Firmware Version<br>\n"; }
			//if (strlen($device_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
			//if (strlen($device_template) == 0) { $msg .= "Please provide: Template<br>\n"; }
			//if (strlen($device_username) == 0) { $msg .= "Please provide: Username<br>\n"; }
			//if (strlen($device_password) == 0) { $msg .= "Please provide: Password<br>\n"; }
			//if (strlen($device_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
			if (strlen($msg) > 0) {
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

		//check for duplicates
			if ($action == 'add' && $device_mac_address != "000000000000") {
				$sql = "select ";
				$sql .= "d2.domain_name ";
				$sql .= "from ";
				$sql .= "v_devices as d1, ";
				$sql .= "v_domains as d2 ";
				$sql .= "where ";
				$sql .= "d1.domain_uuid = d2.domain_uuid and ";
				$sql .= "d1.device_mac_address = :device_mac_address ";
				if (is_uuid($_GET["device_uuid"])) {
					$sql .= " and d1.device_uuid <> :device_uuid ";
				}
				$parameters['device_mac_address'] = $device_mac_address;
				$database = new database;
				$domain_name = $database->select($sql, $parameters, 'column');
				if ($domain_name != '') {
					$message = $text['message-duplicate'].(if_group("superadmin") && $_SESSION["domain_name"] != $domain_name ? ": ".$domain_name : null);
					message::add($message,'negative');
					header('Location: devices.php');
					exit;
				}
				unset($sql, $parameters, $domain_name);
			}

		//add or update the database
			if ($_POST["persistformvar"] != "true") {

				//set the device uuid
					if (!is_uuid($device_uuid)) {
						$device_uuid = uuid();
					}

				//prepare the array
					$array['devices'][0]['domain_uuid'] = $domain_uuid;
					$array['devices'][0]['device_uuid'] = $device_uuid;
					if (permission_exists('device_mac_address')) {
						$array['devices'][0]['device_mac_address'] = $device_mac_address;
					}
					//$array['devices'][0]['device_provisioned_ip'] = $device_provisioned_ip;
					if (permission_exists('device_label')) {
						$array['devices'][0]['device_label'] = $device_label;
					}
					if (permission_exists('device_user') && is_uuid($device_user_uuid)) {
						$array['devices'][0]['device_user_uuid'] = $device_user_uuid;
					}
					if (permission_exists('device_username_password')) {
						$array['devices'][0]['device_username'] = $device_username;
						$array['devices'][0]['device_password'] = $device_password;
					}
					if (permission_exists('device_vendor')) {
						$array['devices'][0]['device_vendor'] = $device_vendor;
					}
					if (permission_exists('device_location')) {
						$array['devices'][0]['device_location'] = $device_location;
					}
					if (permission_exists('device_alternate')) {
						$array['devices'][0]['device_uuid_alternate'] = is_uuid($device_uuid_alternate) ? $device_uuid_alternate : null;
					}
					if (permission_exists('device_model')) {
						$array['devices'][0]['device_model'] = $device_model;
					}
					if (permission_exists('device_firmware')) {
						$array['devices'][0]['device_firmware_version'] = $device_firmware_version;
					}
					if (permission_exists('device_enable')) {
						$array['devices'][0]['device_enabled'] = $device_enabled;
						$array['devices'][0]['device_enabled_date'] = 'now()';
					}
					if (permission_exists('device_template')) {
						$array['devices'][0]['device_template'] = $device_template;
					}
					if (permission_exists('device_profile_edit')) {
						$array['devices'][0]['device_profile_uuid'] = is_uuid($device_profile_uuid) ? $device_profile_uuid : null;
					}
					if (permission_exists('device_description')) {
						$array['devices'][0]['device_description'] = $device_description;
					}

					if (permission_exists('device_line_edit')) {
						$y = 0;
						foreach ($device_lines as $row) {
							if (strlen($row['line_number']) > 0) {
								$new_line = false;
								if (is_uuid($row["device_line_uuid"])) {
									$device_line_uuid = $row["device_line_uuid"];
								}
								else {
									$device_line_uuid = uuid();
									$new_line = true;
								}
								$array['devices'][0]['device_lines'][$y]['domain_uuid'] = $domain_uuid;
								$array['devices'][0]['device_lines'][$y]['device_uuid'] = $device_uuid;
								$array['devices'][0]['device_lines'][$y]['device_line_uuid'] = $device_line_uuid;
								$array['devices'][0]['device_lines'][$y]['line_number'] = $row["line_number"];
								$array['devices'][0]['device_lines'][$y]['server_address'] = $row["server_address"];
								if (permission_exists('device_line_outbound_proxy_primary')) {
									$array['devices'][0]['device_lines'][$y]['outbound_proxy_primary'] = $row["outbound_proxy_primary"];
								} else if ($new_line && isset($_SESSION['provision']['outbound_proxy_primary'])) {
									$array['devices'][0]['device_lines'][$y]['outbound_proxy_primary'] = $_SESSION['provision']['outbound_proxy_primary']['text'];
								}
								if (permission_exists('device_line_outbound_proxy_secondary')) {
									$array['devices'][0]['device_lines'][$y]['outbound_proxy_secondary'] = $row["outbound_proxy_secondary"];
								} else if ($new_line && isset($_SESSION['provision']['outbound_proxy_secondary'])) {
									$array['devices'][0]['device_lines'][$y]['outbound_proxy_secondary'] = $_SESSION['provision']['outbound_proxy_secondary']['text'];
								}
								if (permission_exists('device_line_server_address_primary')) {
									$array['devices'][0]['device_lines'][$y]['server_address_primary'] = $row["server_address_primary"];
								} else if ($new_line && isset($_SESSION['provision']['server_address_primary'])) {
									$array['devices'][0]['device_lines'][$y]['server_address_primary'] = $_SESSION['provision']['server_address_primary']['text'];
								}
								if (permission_exists('device_line_server_address_secondary')) {
									$array['devices'][0]['device_lines'][$y]['server_address_secondary'] = $row["server_address_secondary"];
								} else if ($new_line && isset($_SESSION['provision']['server_address_secondary'])) {
									$array['devices'][0]['device_lines'][$y]['server_address_secondary'] = $_SESSION['provision']['server_address_secondary']['text'];
								}
								if (permission_exists('device_line_label')) {
									$array['devices'][0]['device_lines'][$y]['label'] = $row["label"];
								}
								if (permission_exists('device_line_display_name')) {
									$array['devices'][0]['device_lines'][$y]['display_name'] = $row["display_name"];
								}
								$array['devices'][0]['device_lines'][$y]['user_id'] = $row["user_id"];
								if (permission_exists('device_line_auth_id')) {
									$array['devices'][0]['device_lines'][$y]['auth_id'] = $row["auth_id"];
								}
								if (permission_exists('device_line_password')) {
									$array['devices'][0]['device_lines'][$y]['password'] = $row["password"];
								}
								if (permission_exists('device_line_shared')) {
									$array['devices'][0]['device_lines'][$y]['shared_line'] = $row["shared_line"];
								}
								$array['devices'][0]['device_lines'][$y]['enabled'] = $row["enabled"];
								if (permission_exists('device_line_port')) {
									$array['devices'][0]['device_lines'][$y]['sip_port'] = $row["sip_port"];
								}
								else {
									if ($action == "add") {
										$array['devices'][0]['device_lines'][$y]['sip_port'] = $_SESSION['provision']['line_sip_port']['numeric'];
									}
								}
								if (permission_exists('device_line_transport')) {
									$array['devices'][0]['device_lines'][$y]['sip_transport'] = $row["sip_transport"];
								}
								else {
									if ($action == "add") {
										$array['devices'][0]['device_lines'][$y]['sip_transport'] = $_SESSION['provision']['line_sip_transport']['text'];
									}
								}
								if (permission_exists('device_line_register_expires')) {
									$array['devices'][0]['device_lines'][$y]['register_expires'] = $row["register_expires"];
								}
								else {
									if ($action == "add") {
										$array['devices'][0]['device_lines'][$y]['register_expires'] = $_SESSION['provision']['line_register_expires']['numeric'];
									}
								}
								$y++;
							}
						}
					}

					if (permission_exists('device_key_edit')) {
						$y = 0;
						foreach ($device_keys as $row) {
							if (strlen($row['device_key_category']) > 0) {
								if (is_uuid($row["device_key_uuid"])) {
									$device_key_uuid = $row["device_key_uuid"];
								}
								else {
									$device_key_uuid = uuid();
								}
								$array['devices'][0]['device_keys'][$y]['domain_uuid'] = $domain_uuid;
								$array['devices'][0]['device_keys'][$y]['device_uuid'] = $device_uuid;
								$array['devices'][0]['device_keys'][$y]['device_key_uuid'] = $device_key_uuid;
								$array['devices'][0]['device_keys'][$y]['device_key_category'] = $row["device_key_category"];
								$array['devices'][0]['device_keys'][$y]['device_key_vendor'] = $row["device_key_vendor"];
								if (permission_exists('device_key_id')) {
									$array['devices'][0]['device_keys'][$y]['device_key_id'] = $row["device_key_id"];
								}
								$array['devices'][0]['device_keys'][$y]['device_key_type'] = $row["device_key_type"];
								if (permission_exists('device_key_line')) {
									$array['devices'][0]['device_keys'][$y]['device_key_line'] = $row["device_key_line"];
								}
								$array['devices'][0]['device_keys'][$y]['device_key_value'] = $row["device_key_value"];
								if (permission_exists('device_key_extension')) {
									$array['devices'][0]['device_keys'][$y]['device_key_extension'] = $row["device_key_extension"];
								}
								//$array['devices'][0]['device_keys'][$y]['device_key_protected'] = $row["device_key_protected"];
								$array['devices'][0]['device_keys'][$y]['device_key_label'] = $row["device_key_label"];
								if (permission_exists('device_key_icon')) {
									$array['devices'][0]['device_keys'][$y]['device_key_icon'] = $row["device_key_icon"];
								}
								$y++;
							}
						}
					}

					if (permission_exists('device_setting_edit')) {
						$y = 0;
						foreach ($device_settings as $row) {
							if (strlen($row['device_setting_subcategory']) > 0) {
								if (is_uuid($row["device_setting_uuid"])) {
									$device_setting_uuid = $row["device_setting_uuid"];
								}
								else {
									$device_setting_uuid = uuid();
								}
								$array['devices'][0]['device_settings'][$y]['domain_uuid'] = $domain_uuid;
								$array['devices'][0]['device_settings'][$y]['device_uuid'] = $device_uuid;
								$array['devices'][0]['device_settings'][$y]['device_setting_uuid'] = $device_setting_uuid;
								$array['devices'][0]['device_settings'][$y]['device_setting_category'] = $row["device_setting_category"];
								$array['devices'][0]['device_settings'][$y]['device_setting_subcategory'] = $row["device_setting_subcategory"];
								$array['devices'][0]['device_settings'][$y]['device_setting_name'] = $row["device_setting_name"];
								$array['devices'][0]['device_settings'][$y]['device_setting_value'] = $row["device_setting_value"];
								$array['devices'][0]['device_settings'][$y]['device_setting_enabled'] = $row["device_setting_enabled"];
								$array['devices'][0]['device_settings'][$y]['device_setting_description'] = $row["device_setting_description"];
								$y++;
							}
						}
					}

				//save the device
					$database = new database;
					$database->app_name = 'devices';
					$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
					$database->save($array);

				//remove checked lines
					if (
						$action == 'update'
						&& permission_exists('device_line_delete')
						&& is_array($device_lines_delete)
						&& @sizeof($device_lines_delete) != 0
						) {
						$obj = new device;
						$obj->device_uuid = $device_uuid;
						$obj->delete_lines($device_lines_delete);
					}

				//remove checked keys
					if (
						$action == 'update'
						&& permission_exists('device_key_delete')
						&& is_array($device_keys_delete)
						&& @sizeof($device_keys_delete) != 0
						) {
						$obj = new device;
						$obj->device_uuid = $device_uuid;
						$obj->delete_keys($device_keys_delete);
					}

				//remove checked settings
					if (
						$action == 'update'
						&& permission_exists('device_setting_delete')
						&& is_array($device_settings_delete)
						&& @sizeof($device_settings_delete) != 0
						) {
						$obj = new device;
						$obj->device_uuid = $device_uuid;
						$obj->delete_settings($device_settings_delete);
					}

				//write the provision files
					if (strlen($_SESSION['provision']['path']['text']) > 0) {
						$prov = new provision;
						$prov->domain_uuid = $domain_uuid;
						$response = $prov->write();
					}

				//set the message
					if (isset($action)) {
						if ($action == "add") {
							//save the message to a session variable
							message::add($text['message-add']);
						}
						if ($action == "update") {
							//save the message to a session variable
							message::add($text['message-update']);
						}
						//redirect the browser
						header("Location: device_edit.php?id=".urlencode($device_uuid));
						exit;
					}

			}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_devices ";
		$sql .= "where device_uuid = :device_uuid ";
		$parameters['device_uuid'] = $device_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$device_mac_address = $row["device_mac_address"];
			$device_provisioned_ip = $row["device_provisioned_ip"];
			$domain_uuid = $row["domain_uuid"];
			$device_label = $row["device_label"];
			//$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);
			$device_label = $row["device_label"];
			$device_user_uuid = $row["device_user_uuid"];
			$device_username = $row["device_username"];
			$device_password = $row["device_password"];
			$device_vendor = $row["device_vendor"];
			$device_location = $row["device_location"];
			$device_uuid_alternate = $row["device_uuid_alternate"];
			$device_model = $row["device_model"];
			$device_firmware_version = $row["device_firmware_version"];
			$device_enabled = $row["device_enabled"];
			$device_template = $row["device_template"];
			$device_profile_uuid = $row["device_profile_uuid"];
			$device_description = $row["device_description"];
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	if (strlen($device_enabled) == 0) { $device_enabled = 'true'; }

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		//get the device vendor using the mac address
		$device_vendor = device::get_vendor($device_mac_address);
		
		//if the vendor was not found using the mac address use an alternative method
		if (strlen($device_vendor) == 0) {
			$template_array = explode("/", $device_template);
			$device_vendor = $template_array[0];
		}
	}

//set the sub array index
	$x = "999";

//alternate device settings
	if (is_uuid($device_uuid_alternate)) {
		$sql = "select * from v_devices ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$sql .= "and device_uuid = :device_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['device_uuid'] = $device_uuid_alternate;
		$database = new database;
		$device_alternate = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get device lines
	$sql = "select * from v_device_lines ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by cast(line_number as int) asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_lines = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$device_lines[$x]['line_number'] = '';
	$device_lines[$x]['server_address'] = '';
	$device_lines[$x]['outbound_proxy_primary'] = $_SESSION['provision']['outbound_proxy_primary']['text'];
	$device_lines[$x]['outbound_proxy_secondary'] = $_SESSION['provision']['outbound_proxy_secondary']['text'];
	$device_lines[$x]['server_address_primary'] = $_SESSION['provision']['server_address_primary']['text'];
	$device_lines[$x]['server_address_secondary'] = $_SESSION['provision']['server_address_secondary']['text'];
	$device_lines[$x]['label'] = '';
	$device_lines[$x]['display_name'] = '';
	$device_lines[$x]['user_id'] = '';
	$device_lines[$x]['auth_id'] = '';
	$device_lines[$x]['password'] = '';
	$device_lines[$x]['shared_line'] = '';
	$device_lines[$x]['enabled'] = '';
	$device_lines[$x]['sip_port'] = $_SESSION['provision']['line_sip_port']['numeric'];
	$device_lines[$x]['sip_transport'] = $_SESSION['provision']['line_sip_transport']['text'];
	$device_lines[$x]['register_expires'] = $_SESSION['provision']['line_register_expires']['numeric'];

//get device keys
	$sql = "select * from v_device_keys ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by ";
	$sql .= "device_key_vendor asc, ";
	$sql .= "case device_key_category ";
	$sql .= "when 'line' then 1 ";
	$sql .= "when 'memory' then 2 ";
	$sql .= "when 'programmable' then 3 ";
	$sql .= "when 'expansion' then 4 ";
	$sql .= "when 'expansion-1' then 5 ";
	$sql .= "when 'expansion-2' then 6 ";
	$sql .= "when 'expansion-3' then 7 ";
	$sql .= "when 'expansion-4' then 8 ";
	$sql .= "when 'expansion-5' then 9 ";
	$sql .= "when 'expansion-6' then 10 ";
	$sql .= "else 100 end, ";
	$sql .= $db_type == "mysql" ? "device_key_id asc " : "cast(device_key_id as numeric) asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_keys = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$device_keys[$x]['device_key_category'] = '';
	$device_keys[$x]['device_key_id'] = '';
	$device_keys[$x]['device_key_type'] = '';
	$device_keys[$x]['device_key_line'] = '';
	$device_keys[$x]['device_key_value'] = '';
	$device_keys[$x]['device_key_extension'] = '';
	$device_keys[$x]['device_key_label'] = '';
	$device_keys[$x]['device_key_icon'] = '';

//get the device vendors
	$sql = "select name ";
	$sql .= "from v_device_vendors ";
	$sql .= "where enabled = 'true' ";
	$sql .= "order by name asc ";
	$database = new database;
	$device_vendors = $database->select($sql, null, 'all');
	unset($sql);

//get the vendor functions
	$sql = "select v.name as vendor_name, f.name, f.value ";
	$sql .= "from v_device_vendors as v, v_device_vendor_functions as f ";
	$sql .= "where v.device_vendor_uuid = f.device_vendor_uuid ";
	$sql .= "and v.enabled = 'true' ";
	$sql .= "and f.enabled = 'true' ";
	$sql .= "order by v.name asc, f.name asc ";
	$database = new database;
	$vendor_functions = $database->select($sql, null, 'all');
	unset($sql);

//get device settings
	$sql = "select * from v_device_settings ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by device_setting_subcategory asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$device_settings[$x]['device_setting_name'] = '';
	$device_settings[$x]['device_setting_value'] = '';
	$device_settings[$x]['enabled'] = '';
	$device_settings[$x]['device_setting_description'] = '';

//get the users
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		$device_vendor = device::get_vendor($device_mac_address);
	}

//get the device line info for provision button
	foreach($device_lines as $row) {
		if (strlen($row['user_id']) > 0) {
			$user_id = $row['user_id'];
		}
		if (strlen($row['server_address']) > 0) {
			$server_address = $row['server_address'];
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-device'];
	require_once "resources/header.php";

//select file download javascript
	if (permission_exists("device_files")) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	var fade_speed = 400;\n";
		echo "	function show_files() {\n";
		echo "		document.getElementById('file_action').value = 'files';\n";
		echo "		$('#button_files').fadeOut(fade_speed, function() {\n";
		echo "			$('#target_file').fadeIn(fade_speed);\n";
		echo "			$('#button_download').fadeIn(fade_speed);\n";
		echo "		});";
		echo "	}";
		echo "	function hide_files() {\n";
		echo "		document.getElementById('file_action').value = '';\n";
		echo "		$('#button_download').fadeOut(fade_speed);\n";
		echo "		$('#target_file').fadeOut(fade_speed);\n";
		echo "		document.getElementById('target_file').selectedIndex = 0;\n";
		echo "	}\n";

		echo "	function download(d) {\n";
		echo "		if (d == '".$text['label-download']."') return;\n";
		if ($_SESSION['provision']['http_domain_filter']['boolean'] == "false") {
			$domain_name = $_SERVER["HTTP_HOST"];
		}
		else {
			$domain_name = $_SESSION['domain_name'];
		}

		if (!isset($_SERVER['HTTP_PROTOCOL'])) {
			$_SERVER['HTTP_PROTOCOL'] = 'http';
			if (isset($_SERVER['REQUEST_SCHEME'])) { $_SERVER['HTTP_PROTOCOL'] = $_SERVER['REQUEST_SCHEME']; }
			if ($_SERVER['HTTPS'] == 'on') { $_SERVER['HTTP_PROTOCOL'] = 'https'; }
			if ($_SERVER['SERVER_PORT'] == '443') { $_SERVER['HTTP_PROTOCOL'] = 'https'; }
		}
		echo "		window.location = '".$_SERVER['HTTP_PROTOCOL']."://".$domain_name.PROJECT_PATH."/app/provision/index.php?mac=".escape($device_mac_address)."&file=' + d + '&content_type=application/octet-stream';\n";
		echo "	}\n";

		echo "\n";
		echo "	$( document ).ready(function() {\n";
		echo "		$('#default_setting_search').trigger('focus');\n";
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

//add the QR code
	if (permission_exists("device_line_password") && $device_template == "grandstream/wave") {
		//set the mode
		if (isset($_SESSION['theme']['qr_image'])) {
			if (strlen($_SESSION['theme']['qr_image']) > 0) {
				$mode = '4';
			}
			else {
				$mode = '0';
			}
		}
		else {
			$mode = '4';
		}

		//get the device line settings
		$row = $device_lines[0];

		//set the outbound proxy settings
		if (strlen($row['outbound_proxy_primary']) == 0) {
			$outbound_proxy_primary = $row['server_address'];
		}
		else {
			$outbound_proxy_primary = $row['outbound_proxy_primary'];
		}
		$outbound_proxy_secondary = $row['outbound_proxy_secondary'];

		//build the xml
		$xml = "<?xml version='1.0' encoding='utf-8'?>";
		$xml .= "<AccountConfig version='1'>";
		$xml .= "<Account>";
		$xml .= "<RegisterServer>".$row['server_address']."</RegisterServer>";
		$xml .= "<OutboundServer>".$outbound_proxy_primary.":".$row['sip_port']."</OutboundServer>";
		$xml .= "<SecOutboundServer>".$outbound_proxy_secondary.":".$row['sip_port']."</SecOutboundServer>";
		$xml .= "<UserID>".$row['user_id']."</UserID>";
		$xml .= "<AuthID>".$row['auth_id']."</AuthID>";
		$xml .= "<AuthPass>".$row['password']."</AuthPass>";
		$xml .= "<AccountName>".$row['user_id']."</AccountName>";
		$xml .= "<DisplayName>".$row['display_name']."</DisplayName>";
		$xml .= "<Dialplan>{x+|*x+|*++}</Dialplan>";
		$xml .= "<RandomPort>0</RandomPort>";
		$xml .= "<Voicemail>*97</Voicemail>";
		$xml .= "</Account>";
		$xml .= "</AccountConfig>";

		//qr code generation
		$_GET['type'] = "text";
		echo "<input type='hidden' id='qr_card' value=\"".escape($xml)."\">";
		echo "<style>";
		echo "	#qr_code_container {";
		echo "		z-index: 999999; ";
		echo "		position: absolute; ";
		echo "		left: 0px; ";
		echo "		top: 0px; ";
		echo "		right: 0px; ";
		echo "		bottom: 0px; ";
		echo "		text-align: center; ";
		echo "		vertical-align: middle;";
		echo "	}";
		echo "	#qr_code {";
		echo "		display: block; ";
		echo "		width: 650px; ";
		echo "		height: 650px; ";
		echo "		-webkit-box-shadow: 0px 1px 20px #888; ";
		echo "		-moz-box-shadow: 0px 1px 20px #888; ";
		echo "		box-shadow: 0px 1px 20px #888;";
		echo "	}";
		echo "</style>";
		echo "<script src='".PROJECT_PATH."/resources/jquery/jquery-qrcode.min.js'></script>";
		echo "<script language='JavaScript' type='text/javascript'>";
		echo "	$(document).ready(function() {";
		echo "		$(window).on('load', function() {";
		echo "			$('#qr_code').qrcode({ ";
		echo "				render: 'canvas', ";
		echo "				minVersion: 6, ";
		echo "				maxVersion: 40, ";
		echo "				ecLevel: 'H', ";
		echo "				size: 650, ";
		echo "				radius: 0.2, ";
		echo "				quiet: 6, ";
		echo "				background: '#fff', ";
		echo "				mode: ".$mode.", ";
		echo "				mSize: 0.2, ";
		echo "				mPosX: 0.5, ";
		echo "				mPosY: 0.5, ";
		echo "				image: $('#img-buffer')[0], ";
		echo "				text: document.getElementById('qr_card').value ";
		echo "			});";
		echo "		});";
		echo "	});";
		echo "</script>";
		if (isset($_SESSION['theme']['qr_image'])) {
			echo "<img id='img-buffer' src='".$_SESSION["theme"]["qr_image"]["text"]."' style='display: none;'>";
		}
		else {
			echo "<img id='img-buffer' src='".PROJECT_PATH."/themes/".$_SESSION["domain"]["template"]["name"]."/images/qr_code.png' style='display: none;'>";
		}
	}

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";
	echo "<input type='hidden' name='file_action' id='file_action' value='' />\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-device']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'devices.php']);
	if ($action == 'update') {
		$button_margin = 'margin-left: 15px;';
		if (permission_exists("device_line_password") && $device_template == "grandstream/wave") {
			echo button::create(['type'=>'button','label'=>$text['button-qr_code'],'icon'=>'qrcode','style'=>$button_margin,'onclick'=>"$('#qr_code_container').fadeIn(400);"]);
			unset($button_margin);
		}
		echo button::create(['type'=>'button','label'=>$text['button-provision'],'icon'=>'fax','style'=>$button_margin,'link'=>PROJECT_PATH."/app/devices/cmd.php?cmd=check_sync"."&user=".urlencode($user_id)."&domain=".urlencode($server_address)."&agent=".urlencode($device_vendor)]);
		unset($button_margin);
		if (permission_exists("device_files")) {
			//get the template directory
				$prov = new provision;
				$prov->domain_uuid = $domain_uuid;
				$template_dir = $prov->template_dir;
				$files = glob($template_dir.'/'.$device_template.'/*');
			//add file buttons and the file list
				echo button::create(['type'=>'button','id'=>'button_files','label'=>$text['button-files'],'icon'=>$_SESSION['theme']['button_icon_download'],'onclick'=>'show_files()']);
				echo 		"<select class='formfld' style='display: none; width: auto;' name='target_file' id='target_file' onchange='download(this.value)'>\n";
				echo "			<option value=''>".$text['label-download']."</option>\n";
				foreach ($files as $file) {
					//format the mac address and
						$format = new provision();
						$mac = $format->format_mac($device_mac_address, $device_vendor);
					//render the file name
						$file_name = str_replace("{\$mac}", $mac, basename($file));
					//add the select option
						echo "		<option value='".basename($file)."'>".$file_name."</option>\n";
				}
				echo "		</select>";
		}
		if (permission_exists('device_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','onclick'=>"modal_open('modal-copy','new_mac_address');"]);
		}
		if (
			permission_exists('device_delete') ||
			permission_exists('device_line_delete') ||
			permission_exists('device_key_delete') ||
			permission_exists('device_setting_delete')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>'submit_form();']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('device_add')) {
		echo modal::create([
			'id'=>'modal-copy',
			'type'=>'general',
			'message'=>$text['message_device']."...<br /><br /><input class='formfld modal-input' data-continue='btn_copy' style='font-family: monospace;' type='text' id='new_mac_address' maxlength='17' placeholder='FF-FF-FF-FF-FF-FF'>",
			'actions'=>button::create([
				'type'=>'button',
				'label'=>$text['button-continue'],
				'icon'=>'check',
				'id'=>'btn_copy',
				'style'=>'float: right; margin-left: 15px;',
				'collapse'=>'never',
				'onclick'=>"modal_close(); if (document.getElementById('new_mac_address').value != '') { window.location='device_copy.php?id=".urlencode($device_uuid)."&mac=' + document.getElementById('new_mac_address').value; }"
				]),
			'onclose'=>"document.getElementById('new_mac_address').value = '';",
			]);
	}
	if (
		permission_exists('device_delete') ||
		permission_exists('device_line_delete') ||
		permission_exists('device_key_delete') ||
		permission_exists('device_setting_delete')
		) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo $text['description-device']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncell' width='30%' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_mac_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' width='70%' align='left'>\n";
	if (permission_exists('device_mac_address')) {
		echo "	<input class='formfld' type='text' name='device_mac_address' id='device_mac_address' maxlength='255' value=\"".escape($device_mac_address)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_mac_address']."\n";
	}
	else {
		echo escape($device_mac_address);
	}
	echo "	<div style='display: none;' id='duplicate_mac_response'></div>\n";
	echo " ".escape($device_provisioned_ip)."(<a href='http://".escape($device_provisioned_ip)."' target='_blank'>http</a>|<a href='https://".escape($device_provisioned_ip)."' target='_blank'>https</a>)\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('device_label')) {
		echo "	<input class='formfld' type='text' name='device_label' maxlength='255' value=\"".escape($device_label)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_label']."\n";
	}
	else {
		echo escape($device_label);
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('device_template')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_template']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "<div class='template_select_container'>";
		$device = new device;
		$template_dir = $device->get_template_dir();
		echo "	<select id='device_template' name='device_template' class='formfld' style='float: left;'>\n";
		echo "		<option value=''></option>\n";
		if (is_dir($template_dir) && @is_array($device_vendors)) {
			foreach ($device_vendors as $row) {
				echo "		<optgroup label='".escape($row["name"])."'>\n";
				if (file_exists($template_dir.'/'.$row["name"])) {
					$templates = scandir($template_dir.'/'.$row["name"]);
					if (is_array($templates) && @sizeof($templates) != 0) {
						foreach ($templates as $dir) {
							if ($file != "." && $dir != ".." && $dir[0] != '.') {
								if (is_dir($template_dir . '/' . $row["name"] .'/'. $dir)) {
									if ($device_template == $row["name"]."/".$dir) {
										echo "			<option value='".escape($row["name"])."/".escape($dir)."' selected='selected'>".escape($row["name"])."/".escape($dir)."</option>\n";
										$current_device = escape($dir);
										$current_device_path = $template_dir . '/' . $row["name"];
									}
									else {
										echo "			<option value='".escape($row["name"])."/".escape($dir)."'>".$row["name"]."/".escape($dir)."</option>\n";
									}
								}
							}
						}
					}
				}
				echo "		</optgroup>\n";
			}
		}
		echo "	</select>\n";
		echo "	<span style='float: left; clear: left;'";
		echo "	<br />\n";
		echo "	".$text['description-device_template']."\n";
		echo "	</span>";
		echo "</div>";
		echo "
		<style>
			.template_select_container {
				display: block;
				width: auto;
				float: left;
			}

			.device_image {
				max-width: 280px;
			}

			.device_image > img {
				position: relative;
				max-height: 170px;
				border-radius: 1px;
				transition: transform .6s;
				z-index: 2;
			}

			.device_image > img:hover {
				cursor: zoom-in;
			}

			.device_image >img:active {
				transform: scale(3);
				box-shadow: 0 0 10px #ccc;
			}
		</style>
		";

		$device_image_path = $current_device_path . "/";
		$device_image_name = $current_device . ".jpg";
		$device_image_full = $device_image_path . "/" . $current_device . "/" . $device_image_name;

		if (file_exists($device_image_full))
			{
				$device_image = base64_encode(file_get_contents($device_image_full));

		echo "<div class='device_image'>\n";
		echo "<img src='data:image/jpg;base64," . $device_image . "' title='$current_device'>";
		echo "</div>";
		}
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_line_edit')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-lines']."</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table width='100%' border='0'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-line']."</td>\n";
		if (permission_exists('device_line_server_address')) {
			echo "				<td class='vtable'>".$text['label-server_address']."</td>\n";
		}
		if (permission_exists('device_line_server_address_primary')) {
			echo "				<td class='vtable'>".$text['label-server_address_primary']."</td>\n";
		}
		if (permission_exists('device_line_server_address_secondary')) {
			echo "				<td class='vtable'>".$text['label-server_address_secondary']."</td>\n";
		}
		if (permission_exists('device_line_outbound_proxy_primary')) {
			echo "				<td class='vtable'>".$text['label-outbound_proxy_primary']."</td>\n";
		}
		if (permission_exists('device_line_outbound_proxy_secondary')) {
			echo "				<td class='vtable'>".$text['label-outbound_proxy_secondary']."</td>\n";
		}
		if (permission_exists('device_line_label')) {
			echo "				<td class='vtable'>".$text['label-label']."</td>\n";
		}
		if (permission_exists('device_line_display_name')) {
			echo "				<td class='vtable'>".$text['label-display_name']."</td>\n";
		}
		echo "				<td class='vtable'>".$text['label-user_id']."</td>\n";
		if (permission_exists('device_line_auth_id')) {
			echo "				<td class='vtable'>".$text['label-auth_id']."</td>\n";
		}
		if (permission_exists('device_line_password')) {
			echo "				<td class='vtable'>".$text['label-password']."</td>\n";
		}
		if (permission_exists('device_line_port')) {
			echo "				<td class='vtable'>".$text['label-sip_port']."</td>\n";
		}
		if (permission_exists('device_line_transport')) {
			echo "				<td class='vtable'>".$text['label-sip_transport']."</td>\n";
		}
		if (permission_exists('device_line_register_expires')) {
			echo "				<td class='vtable'>".$text['label-register_expires']."</td>\n";
		}
		if (permission_exists('device_line_shared')) {
			echo "				<td class='vtable'>".$text['label-shared_line']."</td>\n";
		}
		echo "				<td class='vtable'>".$text['label-enabled']."</td>\n";
		if (is_array($device_lines) && @sizeof($device_lines) > 1 && permission_exists('device_line_delete')) {
			echo "				<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_lines', 'delete_toggle_lines');\" onmouseout=\"swap_display('delete_label_lines', 'delete_toggle_lines');\">\n";
			echo "					<span id='delete_label_lines'>".$text['label-delete']."</span>\n";
			echo "					<span id='delete_toggle_lines'><input type='checkbox' id='checkbox_all_lines' name='checkbox_all' onclick=\"edit_all_toggle('lines');\"></span>\n";
			echo "				</td>\n";
		}
		echo "			</tr>\n";

		$x = 0;
		foreach($device_lines as $row) {

			//set the defaults
				if (!permission_exists('device_line_server_address')) {
					if (strlen($row['server_address']) == 0) { $row['server_address'] = $_SESSION['domain_name']; }
				}
				if (strlen($row['sip_transport']) == 0) { $row['sip_transport'] = $_SESSION['provision']['line_sip_transport']['text']; }
				if (strlen($row['sip_port']) == 0) { $row['sip_port'] = $_SESSION['provision']['line_sip_port']['numeric']; }
				if (strlen($row['register_expires']) == 0) { $row['register_expires'] = $_SESSION['provision']['line_register_expires']['numeric']; }

			//determine whether to hide the element
				if (!is_uuid($device_line_uuid)) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}

			//add the primary key uuid
				if (is_uuid($row['device_line_uuid'])) {
					echo "	<input name='device_lines[".$x."][device_line_uuid]' type='hidden' value=\"".escape($row['device_line_uuid'])."\"/>\n";
				}

			//show each row in the array
				echo "			<tr>\n";
				echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
				$selected = "selected=\"selected\" ";
				echo "				<select class='formfld' name='device_lines[".$x."][line_number]'>\n";
				echo "				<option value=''></option>\n";
				for ($n = 1; $n <=99; $n++) {
        		            echo "					<option value='$n' ".($row['line_number'] == "$n" ? $selected:"").">$n</option>\n";
				}
				echo "				</select>\n";
				echo "			</td>\n";

				if (permission_exists('device_line_server_address')) {
					echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
					echo "				<input class='formfld' style='min-width: 100px; width: 100%;' type='text' name='device_lines[".$x."][server_address]' maxlength='255' value=\"".escape($row['server_address'])."\"/>\n";
					echo "			</td>\n";
				}
				else {
					echo "			<input type='hidden' name='device_lines[".$x."][server_address]' value=\"".escape($row['server_address'])."\"/>\n";
				}

				if (permission_exists('device_line_server_address_primary')) {
					echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
					if (isset($_SESSION['provision']['server_address_primary']) && !isset($_SESSION['provision']['server_address_primary']['text'])) {
						echo "				<select class='formfld' style='width: 75px;' name='device_lines[".$x."][server_address_primary]'>\n";
						echo "					<option value=''></option>\n";
						foreach($_SESSION['provision']['server_address_primary'] as $field) {
							echo "					<option value='".$field."' ".(($row['server_address_primary'] == $field) ? "selected" : null).">".$field."</option>\n";
						}
						echo "				</select>\n";
					}
					else {
						echo "				<input class='formfld' style='width: 100px; width: 100%;' type='text' name='device_lines[".$x."][server_address_primary]' maxlength='255' value=\"".escape($row['server_address_primary'])."\"/>\n";
					}
					echo "			</td>\n";
				}

				if (permission_exists('device_line_server_address_secondary')) {
					echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
					if (isset($_SESSION['provision']['server_address_secondary']) && !isset($_SESSION['provision']['server_address_secondary']['text'])) {
						echo "				<select class='formfld' style='width: 75px;' name='device_lines[".$x."][server_address_secondary]'>\n";
						echo "					<option value=''></option>\n";
						foreach($_SESSION['provision']['server_address_secondary'] as $field) {
							echo "					<option value='".$field."' ".(($row['server_address_secondary'] == $field) ? "selected" : null).">".$field."</option>\n";
						}
						echo "				</select>\n";
					}
					else {
						echo "				<input class='formfld' style='width: 100px; width: 100%;' type='text' name='device_lines[".$x."][server_address_secondary]' maxlength='255' value=\"".escape($row['server_address_secondary'])."\"/>\n";
					}
					echo "			</td>\n";
				}

				if (permission_exists('device_line_outbound_proxy_primary')) {
					echo "			<td align='left'>\n";
					if (isset($_SESSION['provision']['outbound_proxy_primary']) && !isset($_SESSION['provision']['outbound_proxy_primary']['text'])) {
						echo "				<select class='formfld' style='width: 75px;' name='device_lines[".$x."][outbound_proxy_primary]'>\n";
						echo "					<option value=''></option>\n";
						foreach($_SESSION['provision']['outbound_proxy_primary'] as $field) {
							echo "					<option value='".$field."' ".(($row['outbound_proxy_primary'] == $field) ? "selected" : null).">".$field."</option>\n";
						}
						echo "				</select>\n";
					}
					else {
						echo "				<input class='formfld' style='width: 65px;' type='text' name='device_lines[".$x."][outbound_proxy_primary]' placeholder=\"".escape($text['label-primary'])."\" maxlength='255' value=\"".escape($row['outbound_proxy_primary'])."\"/>\n";
					}
					echo "			</td>\n";
				}

				if (permission_exists('device_line_outbound_proxy_secondary')) {
					echo "			<td align='left'>\n";
					if (isset($_SESSION['provision']['outbound_proxy_secondary']) && !isset($_SESSION['provision']['outbound_proxy_secondary']['text'])) {
						echo "				<select class='formfld' style='width: 75px;' name='device_lines[".$x."][outbound_proxy_secondary]'>\n";
						echo "					<option value=''></option>\n";
						foreach($_SESSION['provision']['outbound_proxy_secondary'] as $field) {
							echo "					<option value='".$field."' ".(($row['outbound_proxy_secondary'] == $field) ? "selected" : null).">".$field."</option>\n";
						}
						echo "				</select>\n";
					}
					else {
						echo "				<input class='formfld' style='width: 65px;' type='text' name='device_lines[".$x."][outbound_proxy_secondary]' placeholder=\"".escape($text['label-secondary'])."\" maxlength='255' value=\"".escape($row['outbound_proxy_secondary'])."\"/>\n";
					}
					echo "			</td>\n";
				}
				if (permission_exists('device_line_label')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='min-width: 75px; width: 100%;' type='text' name='device_lines[".$x."][label]' maxlength='255' value=\"".escape($row['label'])."\"/>\n";
					echo "			</td>\n";
				}
				if (permission_exists('device_line_display_name')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='min-width: 75px; width: 100%;' type='text' name='device_lines[".$x."][display_name]' maxlength='255' value=\"".escape($row['display_name'])."\"/>\n";
					echo "			</td>\n";
				}

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='min-width: 50px; width: 100%; max-width: 80px;' type='text' name='device_lines[".$x."][user_id]' maxlength='255' autocomplete=\"new-password\" value=\"".escape($row['user_id'])."\"/>\n";
				echo "			</td>\n";

				if (permission_exists('device_line_auth_id')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='min-width: 50px; width: 100%; max-width: 80px;' type='text' name='device_lines[".$x."][auth_id]' maxlength='255' autocomplete=\"new-password\" value=\"".escape($row['auth_id'])."\"/>\n";
					echo "				<input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
					echo "			</td>\n";
				}

				if (permission_exists('device_line_password')) {
					echo "			<td align='left'>\n";
					echo "				<input type='password' style='display: none;' disabled='disabled'>"; //help defeat browser auto-fill
					echo "				<input class='formfld' style='min-width: 75px; width: 100%;' type='password' name='device_lines[".$x."][password]' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete=\"off\" maxlength='255' value=\"".escape($row['password'])."\"/>\n";
					echo "			</td>\n";
				}

				if (permission_exists('device_line_port')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][sip_port]' maxlength='255' value=\"".escape($row['sip_port'])."\"/>\n";
					echo "			</td>\n";
				}

				if (permission_exists('device_line_transport')) {
					echo "			<td align='left'>\n";
					echo "				<select class='formfld' style='width: 75px;' name='device_lines[".$x."][sip_transport]'>\n";
					echo "					<option value='tcp' ".(($row['sip_transport'] == 'tcp') ? "selected" : null).">TCP</option>\n";
					echo "					<option value='udp' ".(($row['sip_transport'] == 'udp') ? "selected" : null).">UDP</option>\n";
					echo "					<option value='tls' ".(($row['sip_transport'] == 'tls') ? "selected" : null).">TLS</option>\n";
					echo "					<option value='dns srv' ".(($row['sip_transport'] == 'dns srv') ? "selected" : null).">DNS SRV</option>\n";
					echo "				</select>\n";
					echo "			</td>\n";
				}
				else {
					echo "				<input type='hidden' name='device_lines[".$x."][sip_transport]' value=\"".escape($row['sip_transport'])."\" />\n";
				}

				if (permission_exists('device_line_register_expires')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][register_expires]' maxlength='255' value=\"".escape($row['register_expires'])."\"/>\n";
					echo "			</td>\n";
				}
				else {
					echo "				<input type='hidden' name='device_lines[".$x."][register_expires]' value=\"".escape($row['register_expires'])."\"/>\n";
				}

				if (permission_exists('device_line_shared')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][shared_line]' maxlength='255' value=\"".escape($row['shared_line'])."\"/>\n";
					echo "			</td>\n";
				}
				else {
					echo "				<input type='hidden' name='device_lines[".$x."][shared_line]' value=\"".escape($row['shared_line'])."\"/>\n";
				}

				echo "			<td align='left'>\n";
				echo "				<select class='formfld' name='device_lines[".$x."][enabled]'>\n";
				echo "					<option value='true' ".(($row['enabled'] == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
				echo "					<option value='false' ".(($row['enabled'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
				echo "				</select>\n";
				echo "			</td>\n";

				if (is_array($device_lines) && @sizeof($device_lines) > 1 && permission_exists('device_line_delete') && is_uuid($row['device_line_uuid'])) {
					echo "			<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
					echo "				<input type='checkbox' name='device_lines_delete[".$x."][checked]' value='true' class='chk_delete checkbox_lines' onclick=\"edit_delete_action('lines');\">\n";
					echo "				<input type='hidden' name='device_lines_delete[".$x."][uuid]' value='".escape($row['device_line_uuid'])."' />\n";
					echo "			<td>\n";
				}

				echo "</tr>\n";
			//increment counter
				$x++;
		}
		echo "			</table>\n";
		if (strlen($text['description-lines']) > 0) {
			echo "			<br>".$text['description-lines']."\n";
		}
		echo "		</td>";
		echo "	</tr>";
	}

	if (permission_exists('device_profile_edit')) {
		//device profile
		$sql = "select * from v_device_profiles ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$sql .= "order by device_profile_name asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>".$text['label-profile']."</td>";
			echo "		<td class='vtable' align='left'>";
			echo "			<select class='formfld' id='device_profile_uuid' name='device_profile_uuid'>\n";
			echo "				<option value=''></option>\n";
			foreach($result as $row) {
				echo "			<option value='".escape($row['device_profile_uuid'])."' ".(($row['device_profile_uuid'] == $device_profile_uuid) ? "selected='selected'" : null).">".escape($row['device_profile_name'])." ".(($row['domain_uuid'] == '') ? "&nbsp;&nbsp;(".$text['select-global'].")" : null)."</option>\n";
			}
			echo "			</select>\n";
			echo "			<button type='button' class='btn btn-default list_control_icon' id='device_profile_edit' onclick=\"if($('#device_profile_uuid').val() != '') window.location='device_profile_edit.php?id='+$('#device_profile_uuid').val();\"><span class='fas fa-pencil-alt'></span></button>";
			echo "			<button type='button' class='btn btn-default list_control_icon' onclick=\"window.location='device_profile_edit.php'\"><span class='fas fa-plus'></span></button>";
			echo "			<br>".$text['description-profile2']."\n";
			echo "		</td>";
			echo "	</tr>";
		}
		unset($sql, $parameters, $result);
	}

	if (permission_exists('device_key_edit')) {
		$vendor_count = 0;
		foreach($device_keys as $row) {
			if ($previous_vendor != $row['device_key_vendor']) {
				$previous_vendor = $row['device_key_vendor'];
				$vendor_count++;
			}
		}

		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-keys']."</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table border='0' cellpadding='0' cellspacing='3'>\n";
		if ($vendor_count == 0) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>".$text['label-device_key_category']."</td>\n";
			if (permission_exists('device_key_id')) {
				echo "				<td class='vtable'>".$text['label-device_key_id']."</td>\n";
			}
			echo "				<td class='vtable'>".$text['label-device_key_type']."</td>\n";
			if (permission_exists('device_key_line')) {
				echo "				<td class='vtable'>".$text['label-device_key_line']."</td>\n";
			}
			echo "				<td class='vtable'>".$text['label-device_key_value']."</td>\n";
			if (permission_exists('device_key_extension')) {
				echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
			}
			echo "				<td class='vtable'>".$text['label-device_key_label']."</td>\n";
			if (permission_exists('device_key_icon')) {
				echo "				<td class='vtable'>".$text['label-device_key_icon']."</td>\n";
			}
			if (is_array($device_keys) && @sizeof($device_keys) > 1 && permission_exists('device_key_delete')) {
				echo "				<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_keys', 'delete_toggle_keys');\" onmouseout=\"swap_display('delete_label_keys', 'delete_toggle_keys');\">\n";
				echo "					<span id='delete_label_keys'>".$text['label-delete']."</span>\n";
				echo "					<span id='delete_toggle_keys'><input type='checkbox' id='checkbox_all_keys' name='checkbox_all' onclick=\"edit_all_toggle('keys');\"></span>\n";
				echo "				</td>\n";
			}
			echo "			</tr>\n";
		}

		$x = 0;
		foreach($device_keys as $row) {
			//set the column names
				if ($previous_device_key_vendor != $row['device_key_vendor']) {
					echo "			<tr>\n";
					echo "				<td class='vtable'>".$text['label-device_key_category']."</td>\n";
					if (permission_exists('device_key_id')) {
						echo "				<td class='vtable'>".$text['label-device_key_id']."</td>\n";
					}
					if ($vendor_count > 1 && strlen($row['device_key_vendor']) > 0) {
						echo "				<td class='vtable'>".ucwords($row['device_key_vendor'])."</td>\n";
					} else {
						echo "				<td class='vtable'>".$text['label-device_key_type']."</td>\n";
					}
					if (permission_exists('device_key_line')) {
						echo "				<td class='vtable'>".$text['label-device_key_line']."</td>\n";
					}
					echo "				<td class='vtable'>".$text['label-device_key_value']."</td>\n";
					if (permission_exists('device_key_extension')) {
						echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
					}
					echo "				<td class='vtable'>".$text['label-device_key_label']."</td>\n";
					if (permission_exists('device_key_icon')) {
						echo "				<td class='vtable'>".$text['label-device_key_icon']."</td>\n";
					}
					if (is_array($device_keys) && @sizeof($device_keys) > 1 && permission_exists('device_key_delete')) {
						echo "				<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_keys', 'delete_toggle_keys');\" onmouseout=\"swap_display('delete_label_keys', 'delete_toggle_keys');\">\n";
						echo "					<span id='delete_label_keys'>".$text['label-delete']."</span>\n";
						echo "					<span id='delete_toggle_keys'><input type='checkbox' id='checkbox_all_keys' name='checkbox_all' onclick=\"edit_all_toggle('keys');\"></span>\n";
						echo "				</td>\n";
					}
					echo "			</tr>\n";
				}
			//determine whether to hide the element
				if (!is_uuid($device_key_uuid)) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}
			//add the primary key uuid
				if (is_uuid($row['device_key_uuid'])) {
					echo "	<input name='device_keys[".$x."][device_key_uuid]' type='hidden' value=\"".escape($row['device_key_uuid'])."\"/>\n";
				}
			//show all the rows in the array
				echo "			<tr>\n";
				echo "<td valign='top' align='left' nowrap='nowrap'>\n";
				echo "	<select class='formfld' name='device_keys[".$x."][device_key_category]'>\n";
				echo "	<option value=''></option>\n";
				if ($row['device_key_category'] == "line") {
					echo "	<option value='line' selected='selected'>".$text['label-line']."</option>\n";
				}
				else {
					echo "	<option value='line'>".$text['label-line']."</option>\n";
				}
				if ($row['device_key_vendor'] !== "polycom") {
					if ($row['device_key_category'] == "memory") {
						echo "	<option value='memory' selected='selected'>".$text['label-memory']."</option>\n";
					}
					else {
						echo "	<option value='memory'>".$text['label-memory']."</option>\n";
					}
				}
				if ($row['device_key_category'] == "programmable") {
					echo "	<option value='programmable' selected='selected'>".$text['label-programmable']."</option>\n";
				}
				else {
					echo "	<option value='programmable'>".$text['label-programmable']."</option>\n";
				}
				if ($row['device_key_vendor'] !== "polycom") {
					if (strlen($device_vendor) == 0) {
						if ($row['device_key_category'] == "expansion") {
							echo "	<option value='expansion' selected='selected'>".$text['label-expansion']." 1</option>\n";
						}
						else {
							echo "	<option value='expansion'>".$text['label-expansion']." 1</option>\n";
						}
						if ($row['device_key_category'] == "expansion-2") {
							echo "	<option value='expansion-2' selected='selected'>".$text['label-expansion']." 2</option>\n";
						}
						else {
							echo "	<option value='expansion-2'>".$text['label-expansion']." 2</option>\n";
						}
						if ($row['device_key_category'] == "expansion-3") {
							echo "	<option value='expansion-3' selected='selected'>".$text['label-expansion']." 3</option>\n";
						}
						else {
							echo "	<option value='expansion-3'>".$text['label-expansion']." 3</option>\n";
						}
						if ($row['device_key_category'] == "expansion-4") {
							echo "	<option value='expansion-4' selected='selected'>".$text['label-expansion']." 4</option>\n";
						}
						else {
							echo "	<option value='expansion-4'>".$text['label-expansion']." 4</option>\n";
						}
						if ($row['device_key_category'] == "expansion-5") {
							echo "	<option value='expansion-5' selected='selected'>".$text['label-expansion']." 5</option>\n";
						}
						else {
							echo "	<option value='expansion-5'>".$text['label-expansion']." 5</option>\n";
						}
						if ($row['device_key_category'] == "expansion-6") {
							echo "	<option value='expansion-6' selected='selected'>".$text['label-expansion']." 6</option>\n";
						}
						else {
							echo "	<option value='expansion-6'>".$text['label-expansion']." 6</option>\n";
						}
					}
					else {
						if (strtolower($device_vendor) == "cisco" or strtolower($row['device_key_vendor']) == "yealink") {
							if ($row['device_key_category'] == "expansion-1" || $row['device_key_category'] == "expansion") {
								echo "	<option value='expansion-1' selected='selected'>".$text['label-expansion']." 1</option>\n";
							}
							else {
								echo "	<option value='expansion-1'>".$text['label-expansion']." 1</option>\n";
							}
							if ($row['device_key_category'] == "expansion-2") {
								echo "	<option value='expansion-2' selected='selected'>".$text['label-expansion']." 2</option>\n";
							}
							else {
								echo "	<option value='expansion-2'>".$text['label-expansion']." 2</option>\n";
							}
							if ($row['device_key_category'] == "expansion-3") {
								echo "	<option value='expansion-3' selected='selected'>".$text['label-expansion']." 3</option>\n";
							}
							else {
								echo "	<option value='expansion-3'>".$text['label-expansion']." 3</option>\n";
							}
							if ($row['device_key_category'] == "expansion-4") {
								echo "	<option value='expansion-4' selected='selected'>".$text['label-expansion']." 4</option>\n";
							}
							else {
								echo "	<option value='expansion-4'>".$text['label-expansion']." 4</option>\n";
							}
							if ($row['device_key_category'] == "expansion-5") {
								echo "	<option value='expansion-5' selected='selected'>".$text['label-expansion']." 5</option>\n";
							}
							else {
								echo "	<option value='expansion-5'>".$text['label-expansion']." 5</option>\n";
							}
							if ($row['device_key_category'] == "expansion-6") {
								echo "	<option value='expansion-6' selected='selected'>".$text['label-expansion']." 6</option>\n";
							}
							else {
								echo "	<option value='expansion-6'>".$text['label-expansion']." 6</option>\n";
							}
						}
						else {
							if ($row['device_key_category'] == "expansion") {
								echo "	<option value='expansion' selected='selected'>".$text['label-expansion']."</option>\n";
							}
							else {
								echo "	<option value='expansion'>".$text['label-expansion']."</option>\n";
							}
						}
					}
				}
				echo "	</select>\n";
				echo "</td>\n";

				if (permission_exists('device_key_id')) {
					echo "<td valign='top' align='left' nowrap='nowrap'>\n";
					$selected = "selected='selected'";
					echo "	<select class='formfld' name='device_keys[".$x."][device_key_id]'>\n";
					echo "	<option value=''></option>\n";
					for ($i = 1; $i <= 255; $i++) {
						echo "	<option value='$i' ".($row['device_key_id'] == $i ? "selected":null).">$i</option>\n";
					}
					echo "	</select>\n";
					echo "</td>\n";
				}

				echo "<td align='left' nowrap='nowrap'>\n";
				//echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_type]' style='width: 120px;' maxlength='255' value=\"$row['device_key_type']\">\n";
				if (strlen($row['device_key_vendor']) > 0) {
					$device_key_vendor = $row['device_key_vendor'];
				}
				else {
					$device_key_vendor = $device_vendor;
				}

				echo "<input type='hidden' id='key_vendor_".$x."' name='device_keys[".$x."][device_key_vendor]' value=\"".$device_key_vendor."\" />\n";

				echo "<select class='formfld' name='device_keys[".$x."][device_key_type]' id='key_type_".$x."'>\n";
				echo "	<option value=''></option>\n";
				$previous_vendor = '';
				$i=0;
				foreach ($vendor_functions as $function) {
					if (strlen($row['device_key_vendor']) == 0 && $function['vendor_name'] != $previous_vendor) {
						if ($i > 0) { echo "	</optgroup>\n"; }
						echo "	<optgroup label='".ucwords($function['vendor_name'])."'>\n";
					}
					$selected = '';
					if (strtolower($row['device_key_vendor']) == $function['vendor_name'] && $row['device_key_type'] == $function['value']) {
						$selected = "selected='selected'";
					}
					if (strlen($row['device_key_vendor']) == 0) {
						echo "		<option value='".$function['value']."' vendor='".$function['vendor_name']."' $selected >".$text['label-'.$function['name']]."</option>\n";
					}
					if (strlen($row['device_key_vendor']) > 0 && strtolower($row['device_key_vendor']) == $function['vendor_name']) {
						echo "		<option value='".$function['value']."' vendor='".$function['vendor_name']."' $selected >".$text['label-'.$function['name']]."</option>\n";
					}
					$previous_vendor = $function['vendor_name'];
					$i++;
				}
				if (strlen($row['device_key_vendor']) == 0) {
					echo "	</optgroup>\n";
				}
				echo "</select>\n";
				echo "</td>\n";
				if (permission_exists('device_key_line')) {
					echo "<td valign='top' align='left' nowrap='nowrap'>\n";
					echo "	<select class='formfld' name='device_keys[".$x."][device_key_line]'>\n";
					echo "		<option value=''></option>\n";
					for ($l = 0; $l <= 99; $l++) {
						echo "	<option value='".$l."' ".(($row['device_key_line'] == $l) ? "selected='selected'" : null).">".$l."</option>\n";
					}
					echo "	</select>\n";
					echo "</td>\n";
				}

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_value]' style='width: 120px;' maxlength='255' value=\"".escape($row['device_key_value'])."\"/>\n";
				echo "</td>\n";

				if (permission_exists('device_key_extension')) {
					echo "<td align='left'>\n";
					echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_extension]' style='width: 75px;' maxlength='255' value=\"".escape($row['device_key_extension'])."\"/>\n";
					echo "</td>\n";
				}

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_label]' style='width: 75px;' maxlength='255' value=\"".escape($row['device_key_label'])."\"/>\n";
				echo "</td>\n";

				if (permission_exists('device_key_icon')) {
					echo "<td align='left'>\n";
					echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_icon]' style='width: 75px;' maxlength='255' value=\"".escape($row['device_key_icon'])."\"/>\n";
					echo "</td>\n";
				}

				if (is_array($device_keys) && @sizeof($device_keys) > 1 && permission_exists('device_key_delete')) {
					if (is_uuid($row['device_key_uuid'])) {
						echo "				<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
						echo "					<input type='checkbox' name='device_keys_delete[".$x."][checked]' value='true' class='chk_delete checkbox_keys' onclick=\"edit_delete_action('keys');\">\n";
						echo "					<input type='hidden' name='device_keys_delete[".$x."][uuid]' value='".escape($row['device_key_uuid'])."' />\n";
					}
					else {
						echo "				<td>\n";
					}
				}
				echo "				</td>\n";
				echo "			</tr>\n";
			//set the previous vendor
				$previous_device_key_vendor = $row['device_key_vendor'];
			//increment the array key
				$x++;
		}
		echo "			</table>\n";
		if (strlen($text['description-keys']) > 0) {
			echo "			<br>".$text['description-keys']."\n";
		}
		echo "		</td>";
		echo "	</tr>";
	}

//device settings
	if (permission_exists('device_setting_edit')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-settings']."</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table border='0' cellpadding='0' cellspacing='3'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_name']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_value']."</td>\n";
		echo "				<td class='vtable'>".$text['label-enabled']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_description']."</td>\n";
		if (is_array($device_settings) && @sizeof($device_settings) > 1 && permission_exists('device_setting_delete')) {
			echo "				<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_settings', 'delete_toggle_settings');\" onmouseout=\"swap_display('delete_label_settings', 'delete_toggle_settings');\">\n";
			echo "					<span id='delete_label_settings'>".$text['label-delete']."</span>\n";
			echo "					<span id='delete_toggle_settings'><input type='checkbox' id='checkbox_all_settings' name='checkbox_all' onclick=\"edit_all_toggle('settings');\"></span>\n";
			echo "				</td>\n";
		}
		echo "			</tr>\n";

		$x = 0;
		foreach($device_settings as $row) {
			//determine whether to hide the element
				if (!is_uuid($device_setting_uuid)) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}
			//add the primary key uuid
				if (is_uuid($row['device_setting_uuid'])) {
					echo "	<input name='device_settings[".$x."][device_setting_uuid]' type='hidden' value=\"".escape($row['device_setting_uuid'])."\"/>\n";
				}

			//show alls rows in the array
				echo "<tr>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_subcategory]' style='width: 120px;' maxlength='255' value=\"".escape($row['device_setting_subcategory'])."\"/>\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_value]' style='width: 120px;' maxlength='255' value=\"".escape($row['device_setting_value'])."\"/>\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "  <select class='formfld' name='device_settings[".$x."][device_setting_enabled]' style='width: 90px;'>\n";
				echo "  	<option value='true'>".$text['label-true']."</option>\n";
				echo "  	<option value='false' ".($row['device_setting_enabled'] == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
				echo "  </select>\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_description]' style='width: 150px;' maxlength='255' value=\"".escape($row['device_setting_description'])."\"/>\n";
				echo "</td>\n";

				if (is_array($device_settings) && @sizeof($device_settings) > 1 && permission_exists('device_setting_delete')) {
					if (is_uuid($row['device_setting_uuid'])) {
						echo "<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
						echo "	<input type='checkbox' name='device_settings_delete[".$x."][checked]' value='true' class='chk_delete checkbox_settings' onclick=\"edit_delete_action('settings');\">\n";
						echo "	<input type='hidden' name='device_settings_delete[".$x."][uuid]' value='".escape($row['device_setting_uuid'])."' />\n";
					}
					else {
						echo "<td>\n";
					}
					echo "</td>\n";
				}

				echo "</tr>\n";
				$x++;
			}

			echo "</table>\n";

			if (strlen($text['description-settings']) > 0) {
				echo "<br>".$text['description-settings']."\n";
			}

			echo "</td>\n";
			echo "</tr>\n";
	}

	if (permission_exists('device_user')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-user']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "			<select name=\"device_user_uuid\" class='formfld' style='width: auto;'>\n";
		echo "			<option value=\"\"></option>\n";
		foreach($users as $field) {
			if ($field['user_uuid'] == $device_user_uuid) { $selected = "selected='selected'"; } else { $selected = ''; }
			echo "			<option value='".escape($field['user_uuid'])."' $selected>".escape($field['username'])."</option>\n";
		}
		echo "			</select>";
		unset($users);
		echo "			<br>\n";
		echo "			".$text['description-user']."\n";
	}

	if (permission_exists('device_username_password')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_username' id='device_username' autocomplete=\"new-password\" maxlength='255' placeholder=\"".$text['label-device_username']."\" value=\"".escape($device_username)."\"/>\n";
		echo "	<input class='formfld' type='password' name='device_password' id='device_password' autocomplete=\"new-password\" onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' placeholder=\"".$text['label-device_password']."\" value=\"".escape($device_password)."\"/>\n";
		echo "	<div style='display: none;' id='duplicate_username_response'></div>\n";
		echo "<br />\n";
		echo $text['description-device']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_alternate') && is_uuid($device_uuid_alternate)) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_uuid_alternate']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' nowrap='nowrap'>\n";
		$label = $device_alternate[0]['device_label'];
		if (strlen($label) == 0) { $label = $device_alternate[0]['device_description']; }
		if (strlen($label) == 0) { $label = $device_alternate[0]['device_mac_address']; }
		echo "	<table>\n";
		echo "	<tr>\n";
		echo "		<td><a href='?id=".escape($device_uuid_alternate)."' id='device_uuid_alternate_link'>".escape($label)."</a><input class='formfld' type='hidden' name='device_uuid_alternate' id='device_uuid_alternate' maxlength='255' value=\"".escape($device_uuid_alternate)."\" />&nbsp;</td>";
		echo "		<td><a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('device_uuid_alternate').value = ''; document.getElementById('device_uuid_alternate_link').hidden = 'true'; submit_form(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a></td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		unset($label);
		echo $text['description-device_uuid_alternate']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_vendor')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_vendor']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_vendor' maxlength='255' value=\"".escape($device_vendor)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_vendor']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_location')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_location']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_location' maxlength='255' value=\"".escape($device_location)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_location']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_model')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_model']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_model' maxlength='255' value=\"".escape($device_model)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_model']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_firmware')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_firmware_version']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_firmware_version' maxlength='255' value=\"".escape($device_firmware_version)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_firmware_version']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <select class='formfld' name='domain_uuid' id='domain_uuid'>\n";
		if (!is_uuid($domain_uuid)) {
			echo "  <option value='' selected='selected'>".$text['select-global']."</option>\n";
		}
		else {
			echo "  <option value=''>".$text['select-global']."</option>\n";
		}
		foreach ($_SESSION['domains'] as $row) {
			if ($row['domain_uuid'] == $domain_uuid) {
				echo "  <option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
			}
			else {
				echo "  <option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
			}
		}
		echo "  </select>\n";
		echo "<br />\n";
		echo $text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else {
		echo "	<input type='hidden' name='domain_uuid' id='domain_uuid' value=\"".$_SESSION['domain_uuid']."\"/>\n";
	}

	if (permission_exists('device_enable')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
			echo "	<label class='switch'>\n";
			echo "		<input type='checkbox' id='device_enabled' name='device_enabled' value='true' ".($device_enabled == 'true' ? "checked='checked'" : null).">\n";
			echo "		<span class='slider'></span>\n";
			echo "	</label>\n";
		}
		else {
			echo "	<select class='formfld' id='device_enabled' name='device_enabled'>\n";
			echo "		<option value='true' ".($device_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
			echo "		<option value='false' ".($device_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
			echo "	</select>\n";
		}
		echo "<br />\n";
		echo $text['description-device_enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('device_description')) {
		echo "	<input class='formfld' type='text' name='device_description' maxlength='255' value=\"".escape($device_description)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_description']."\n";
	}
	else {
		echo escape($device_description)."\n";
	}

	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='device_uuid' value='".escape($device_uuid)."'/>\n";
	}
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	echo "<script>\n";
	// trigger initial onchage to set button state
	echo "	$(window).on('load', function(event){\n";
	echo "		$('#device_profile_uuid').trigger('change')";
	echo "	});\n";
	//capture device selection events
	echo "	$('#device_profile_uuid').on('change',function(event){ \n";
	echo "		if (this.value == '') {\$('#device_profile_edit').hide()} else {\$('#device_profile_edit').show()} \n";
	echo "	}); \n";
	//hide password fields before submit
	echo "	function submit_form() {\n";
	echo "		hide_password_fields();\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//show the footer
	require_once "resources/footer.php";

?>
