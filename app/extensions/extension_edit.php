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
	Copyright (C) 2008-2013 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('extension_add') || permission_exists('extension_edit')) {
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

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$extension_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http values and set them as php variables
	if (count($_POST) > 0) {
		//get the values from the HTTP POST and save them as PHP variables
			$extension = check_str($_POST["extension"]);
			$number_alias = check_str($_POST["number_alias"]);
			$password = check_str($_POST["password"]);

		//get the values from the HTTP POST and save them as PHP variables
			$accountcode = check_str($_POST["accountcode"]);
			$effective_caller_id_name = check_str($_POST["effective_caller_id_name"]);
			$effective_caller_id_number = check_str($_POST["effective_caller_id_number"]);
			$outbound_caller_id_name = check_str($_POST["outbound_caller_id_name"]);
			$outbound_caller_id_number = check_str($_POST["outbound_caller_id_number"]);
			$emergency_caller_id_number = check_str($_POST["emergency_caller_id_number"]);
			$directory_full_name = check_str($_POST["directory_full_name"]);
			$directory_visible = check_str($_POST["directory_visible"]);
			$directory_exten_visible = check_str($_POST["directory_exten_visible"]);
			$limit_max = check_str($_POST["limit_max"]);
			$limit_destination = check_str($_POST["limit_destination"]);
			$device_uuid = check_str($_POST["device_uuid"]);
			$device_line = check_str($_POST["device_line"]);
			$vm_password = check_str($_POST["vm_password"]);
			$vm_enabled = check_str($_POST["vm_enabled"]);
			$vm_mailto = check_str($_POST["vm_mailto"]);
			$vm_attach_file = check_str($_POST["vm_attach_file"]);
			$vm_keep_local_after_email = check_str($_POST["vm_keep_local_after_email"]);
			$user_context = check_str($_POST["user_context"]);
			$range = check_str($_POST["range"]);
			$autogen_users = check_str($_POST["autogen_users"]);
			$toll_allow = check_str($_POST["toll_allow"]);
			$call_timeout = check_str($_POST["call_timeout"]);
			$call_group = check_str($_POST["call_group"]);
			$hold_music = check_str($_POST["hold_music"]);
			$auth_acl = check_str($_POST["auth_acl"]);
			$cidr = check_str($_POST["cidr"]);
			$sip_force_contact = check_str($_POST["sip_force_contact"]);
			$sip_force_expires = check_str($_POST["sip_force_expires"]);
			$nibble_account = check_str($_POST["nibble_account"]);
			$mwi_account = check_str($_POST["mwi_account"]);
			$sip_bypass_media = check_str($_POST["sip_bypass_media"]);
			$dial_string = check_str($_POST["dial_string"]);
			$enabled = check_str($_POST["enabled"]);
			$description = check_str($_POST["description"]);
	}

//delete the user from the v_extension_users
	if ($_GET["a"] == "delete" && strlen($_REQUEST["user_uuid"]) > 0 && permission_exists("extension_delete")) {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$extension_uuid = check_str($_REQUEST["id"]);
		//delete the group from the users
			$sql = "delete from v_extension_users ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and extension_uuid = '".$extension_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
		//redirect the browser
			require_once "resources/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=extension_edit.php?id=$extension_uuid\">\n";
			echo "<div align='center'>".$text['message-delete']."</div>";
			require_once "resources/footer.php";
			return;
	}

//delete the line from the v_device_lines
	if ($_GET["a"] == "delete" && strlen($_REQUEST["device_line_uuid"]) > 0 && permission_exists("extension_delete")) {
		//set the variables
			$extension_uuid = check_str($_REQUEST["id"]);
			$device_line_uuid = check_str($_REQUEST["device_line_uuid"]);
		//delete device_line
			$sql = "delete from v_device_lines ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and device_line_uuid = '$device_line_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);
		//redirect the browser
			require_once "resources/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=extension_edit.php?id=$extension_uuid\">\n";
			echo "<div align='center'>".$text['message-delete']."</div>";
			require_once "resources/footer.php";
			return;
	}

//assign the extension to the user
	if (strlen($_REQUEST["user_uuid"]) > 0 && strlen($_REQUEST["id"]) > 0 && $_GET["a"] != "delete") {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$extension_uuid = check_str($_REQUEST["id"]);
		//assign the user to the extension
			$sql_insert = "insert into v_extension_users ";
			$sql_insert .= "(";
			$sql_insert .= "extension_user_uuid, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "extension_uuid, ";
			$sql_insert .= "user_uuid ";
			$sql_insert .= ")";
			$sql_insert .= "values ";
			$sql_insert .= "(";
			$sql_insert .= "'".uuid()."', ";
			$sql_insert .= "'$domain_uuid', ";
			$sql_insert .= "'".$extension_uuid."', ";
			$sql_insert .= "'".$user_uuid."' ";
			$sql_insert .= ")";
			$db->exec($sql_insert);
		//redirect the browser
			require_once "resources/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=extension_edit.php?id=$extension_uuid\">\n";
			echo "<div align='center'>".$text['message-add']."</div>";
			require_once "resources/footer.php";
			return;
	}

//assign the line to the device
	if (strlen($_REQUEST["device_mac_address"]) > 0 && strlen($_REQUEST["id"]) > 0 && $_GET["a"] != "delete") {
		//set the variables
			$extension_uuid = check_str($_REQUEST["id"]);
			$device_uuid= uuid();
			$device_line_uuid = uuid();
			$device_template = check_str($_REQUEST["device_template"]);
			$line_number = check_str($_REQUEST["line_number"]);
			$device_mac_address = check_str($_REQUEST["device_mac_address"]);
			$device_mac_address = strtolower($device_mac_address);
			$device_mac_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address);

		//add the device if it doesn't exist, if it does exist get the device_uuid
			$sql = "select device_uuid from v_devices ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and device_mac_address = '$device_mac_address' ";
			if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if (strlen($row['device_uuid']) > 0) {
					//device found
					$device_uuid = $row['device_uuid'];
				}
				else {
					//device not found
					$sql_insert = "insert into v_devices ";
					$sql_insert .= "(";
					$sql_insert .= "device_uuid, ";
					$sql_insert .= "domain_uuid, ";
					$sql_insert .= "device_mac_address, ";
					$sql_insert .= "device_template, ";
					$sql_insert .= "device_provision_enable ";
					$sql_insert .= ") ";
					$sql_insert .= "values ";
					$sql_insert .= "(";
					$sql_insert .= "'".$device_uuid."', ";
					$sql_insert .= "'".$_SESSION['domain_uuid']."', ";
					$sql_insert .= "'".$device_mac_address."', ";
					$sql_insert .= "'".$device_template."', ";
					$sql_insert .= "'true' ";
					$sql_insert .= ")";
					//echo $sql_insert."<br />\n";
					$db->exec($sql_insert);
				}
			}

		//assign the line to the device
			$sql_insert = "insert into v_device_lines ";
			$sql_insert .= "(";
			$sql_insert .= "device_uuid, ";
			$sql_insert .= "device_line_uuid, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "server_address, ";
			$sql_insert .= "user_id, ";
			$sql_insert .= "password, ";
			$sql_insert .= "line_number ";
			$sql_insert .= ") ";
			$sql_insert .= "values ";
			$sql_insert .= "(";
			$sql_insert .= "'".$device_uuid."', ";
			$sql_insert .= "'".$device_line_uuid."', ";
			$sql_insert .= "'".$_SESSION['domain_uuid']."', ";
			$sql_insert .= "'".$_SESSION['domain_name']."', ";
			$sql_insert .= "'".$extension."', ";
			$sql_insert .= "'".$password."', ";
			$sql_insert .= "'".$line_number."' ";
			$sql_insert .= ")";
			//echo $sql_insert."<br />\n";
			$db->exec($sql_insert);

		//redirect the browser
			require_once "resources/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=extension_edit.php?id=$extension_uuid\">\n";
			echo "<div align='center'>".$text['message-add']."</div>";
			require_once "resources/footer.php";
			return;
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$extension_uuid = check_str($_POST["extension_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
		if (strlen($extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
		//if (strlen($number_alias) == 0) { $msg .= $text['message-required']."Number Alias<br>\n"; }
		//if (strlen($vm_password) == 0) { $msg .= $text['message-required']."Voicemail Password<br>\n"; }
		//if (strlen($accountcode) == 0) { $msg .= $text['message-required']."Account Code<br>\n"; }
		//if (strlen($effective_caller_id_name) == 0) { $msg .= $text['message-required']."Effective Caller ID Name<br>\n"; }
		//if (strlen($effective_caller_id_number) == 0) { $msg .= $text['message-required']."Effective Caller ID Number<br>\n"; }
		//if (strlen($outbound_caller_id_name) == 0) { $msg .= $text['message-required']."Outbound Caller ID Name<br>\n"; }
		//if (strlen($outbound_caller_id_number) == 0) { $msg .= $text['message-required']."Outbound Caller ID Number<br>\n"; }
		//if (strlen($emergency_caller_id_number) == 0) { $msg .= $text['message-required']."Emergency Caller ID Number<br>\n"; }
		//if (strlen($directory_full_name) == 0) { $msg .= $text['message-required']."Directory Full Name<br>\n"; }
		//if (strlen($directory_visible) == 0) { $msg .= $text['message-required']."Directory Visible<br>\n"; }
		//if (strlen($directory_exten_visible) == 0) { $msg .= $text['message-required']."Directory Extension Visible<br>\n"; }
		//if (strlen($limit_max) == 0) { $msg .= $text['message-required']."Max Callsr<br>\n"; }
		//if (strlen($limit_destination) == 0) { $msg .= $text['message-required']."Transfer Destination Number<br>\n"; }
		//if (strlen($vm_mailto) == 0) { $msg .= $text['message-required']."Voicemail Mail To<br>\n"; }
		//if (strlen($vm_attach_file) == 0) { $msg .= $text['message-required']."Voicemail Attach File<br>\n"; }
		//if (strlen($vm_keep_local_after_email) == 0) { $msg .= $text['message-required']."VM Keep Local After Email<br>\n"; }
		//if (strlen($user_context) == 0) { $msg .= $text['message-required']."User Context<br>\n"; }
		//if (strlen($toll_allow) == 0) { $msg .= $text['message-required']."Toll Allow<br>\n"; }
		//if (strlen($call_group) == 0) { $msg .= $text['message-required']."Call Group<br>\n"; }
		//if (strlen($hold_music) == 0) { $msg .= $text['message-required']."Hold Music<br>\n"; }
		//if (strlen($auth_acl) == 0) { $msg .= $text['message-required']."Auth ACL<br>\n"; }
		//if (strlen($cidr) == 0) { $msg .= $text['message-required']."CIDR<br>\n"; }
		//if (strlen($sip_force_contact) == 0) { $msg .= $text['message-required']."SIP Force Contact<br>\n"; }
		//if (strlen($dial_string) == 0) { $msg .= $text['message-required']."Dial String<br>\n"; }
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
		if (if_group("superadmin")) {
			//allow a user assigned to super admin to change the user_context
		}
		else {
			//if the user_context was not set then set the default value
			if (strlen($user_context) == 0) {
				if (count($_SESSION["domains"]) > 1) {
					$user_context = $_SESSION['domain_name'];
				}
				else {
					$user_context = "default";
				}
			}
		}

	//add or update the database
	if ($_POST["persistformvar"] != "true") {
		//add the extension to the database
			if ($action == "add" && permission_exists('extension_add')) {
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

				for ($i=1; $i<=$range; $i++) {
					if (extension_exists($extension)) {
						//extension exists
					}
					else {
						//extension does not exist add it
							$extension_uuid = uuid();
							$password = generate_password();
							$sql = "insert into v_extensions ";
							$sql .= "(";
							$sql .= "domain_uuid, ";
							$sql .= "extension_uuid, ";
							$sql .= "extension, ";
							$sql .= "number_alias, ";
							$sql .= "password, ";
							//$sql .= "vm_password, ";
							$sql .= "accountcode, ";
							$sql .= "effective_caller_id_name, ";
							$sql .= "effective_caller_id_number, ";
							$sql .= "outbound_caller_id_name, ";
							$sql .= "outbound_caller_id_number, ";
							$sql .= "emergency_caller_id_number, ";
							$sql .= "directory_full_name, ";
							$sql .= "directory_visible, ";
							$sql .= "directory_exten_visible, ";
							$sql .= "limit_max, ";
							$sql .= "limit_destination, ";
							//$sql .= "vm_enabled, ";
							//$sql .= "vm_mailto, ";
							//$sql .= "vm_attach_file, ";
							//$sql .= "vm_keep_local_after_email, ";
							$sql .= "user_context, ";
							if (permission_exists('extension_toll')) {
								$sql .= "toll_allow, ";
							}
							if (strlen($call_timeout) > 0) {
								$sql .= "call_timeout, ";
							}
							$sql .= "call_group, ";
							$sql .= "hold_music, ";
							$sql .= "auth_acl, ";
							$sql .= "cidr, ";
							$sql .= "sip_force_contact, ";
							if (strlen($sip_force_expires) > 0) {
								$sql .= "sip_force_expires, ";
							}
							if (strlen($nibble_account) > 0) {
								$sql .= "nibble_account, ";
							}
							if (strlen($mwi_account) > 0) {
								$sql .= "mwi_account, ";
							}
							$sql .= "sip_bypass_media, ";
							$sql .= "dial_string, ";
							$sql .= "enabled, ";
							$sql .= "description ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'$domain_uuid', ";
							$sql .= "'$extension_uuid', ";
							$sql .= "'$extension', ";
							$sql .= "'$number_alias', ";
							$sql .= "'$password', ";
							//$sql .= "'user-choose', ";
							$sql .= "'$accountcode', ";
							$sql .= "'$effective_caller_id_name', ";
							$sql .= "'$effective_caller_id_number', ";
							$sql .= "'$outbound_caller_id_name', ";
							$sql .= "'$outbound_caller_id_number', ";
							$sql .= "'$emergency_caller_id_number', ";
							$sql .= "'$directory_full_name', ";
							$sql .= "'$directory_visible', ";
							$sql .= "'$directory_exten_visible', ";
							$sql .= "'$limit_max', ";
							$sql .= "'$limit_destination', ";
							//$sql .= "'$vm_enabled', ";
							//$sql .= "'$vm_mailto', ";
							//$sql .= "'$vm_attach_file', ";
							//$sql .= "'$vm_keep_local_after_email', ";
							$sql .= "'$user_context', ";
							if (permission_exists('extension_toll')) {
								$sql .= "'$toll_allow', ";
							}
							if (strlen($call_timeout) > 0) {
								$sql .= "'$call_timeout', ";
							}
							$sql .= "'$call_group', ";
							$sql .= "'$hold_music', ";
							$sql .= "'$auth_acl', ";
							$sql .= "'$cidr', ";
							$sql .= "'$sip_force_contact', ";
							if (strlen($sip_force_expires) > 0) {
								$sql .= "'$sip_force_expires', ";
							}
							if (strlen($nibble_account) > 0) {
								$sql .= "'$nibble_account', ";
							}
							if (strlen($mwi_account) > 0) {
								if (strpos($mwi_account, '@') === false) {
									if (count($_SESSION["domains"]) > 1) {
										$mwi_account .= "@".$_SESSION['domain_name'];
									}
									else {
										$mwi_account .= "@\$\${domain}";
									}
								}
								$sql .= "'$mwi_account', ";
							}
							$sql .= "'$sip_bypass_media', ";
							$sql .= "'$dial_string', ";
							if (permission_exists('extension_enabled')) {
								$sql .= "'$enabled', ";
							}
							else {
								$sql .= "'true', ";
							}
							$sql .= "'$description' ";
							$sql .= ")";
							$db->exec(check_sql($sql));
							unset($sql);
						}
					//set the voicemail password
						if (strlen($vm_password) == 0) {
							$vm_password = generate_password(9, 1);
						}
					//add or update voicemail
						require_once "app/extensions/resources/classes/extension.php";
						$ext = new extension;
						$ext->db = $db;
						$ext->domain_uuid = $domain_uuid;
						$ext->extension = $extension;
						$ext->number_alias = $number_alias;
						$ext->vm_password = $vm_password;
						$ext->vm_mailto = $vm_mailto;
						$ext->vm_attach_file = $vm_attach_file;
						$ext->vm_keep_local_after_email = $vm_keep_local_after_email;
						$ext->vm_enabled = $vm_enabled;
						$ext->description = $description;
						$ext->voicemail();
						unset($ext);
					//increment the extension number
						$extension++;
				}
			} //if ($action == "add")

		//update the database
			if ($action == "update" && permission_exists('extension_edit')) {
				//generate a password
					if (strlen($password) == 0) {
						$password = generate_password(12,4);
					}
				//set the voicemail password
					if (strlen($vm_password) == 0) {
						$vm_password = generate_password(9, 1);
					}
				//update extensions
					$sql = "update v_extensions set ";
					$sql .= "extension = '$extension', ";
					$sql .= "number_alias = '$number_alias', ";
					$sql .= "password = '$password', ";
					//$sql .= "vm_password = '$vm_password', ";
					$sql .= "accountcode = '$accountcode', ";
					$sql .= "effective_caller_id_name = '$effective_caller_id_name', ";
					$sql .= "effective_caller_id_number = '$effective_caller_id_number', ";
					$sql .= "outbound_caller_id_name = '$outbound_caller_id_name', ";
					$sql .= "outbound_caller_id_number = '$outbound_caller_id_number', ";
					$sql .= "emergency_caller_id_number = '$emergency_caller_id_number', ";
					$sql .= "directory_full_name = '$directory_full_name', ";
					$sql .= "directory_visible = '$directory_visible', ";
					$sql .= "directory_exten_visible = '$directory_exten_visible', ";
					$sql .= "limit_max = '$limit_max', ";
					$sql .= "limit_destination = '$limit_destination', ";
					//$sql .= "vm_enabled = '$vm_enabled', ";
					//$sql .= "vm_mailto = '$vm_mailto', ";
					//$sql .= "vm_attach_file = '$vm_attach_file', ";
					//$sql .= "vm_keep_local_after_email = '$vm_keep_local_after_email', ";
					$sql .= "user_context = '$user_context', ";
					if (permission_exists('extension_toll')) {
						$sql .= "toll_allow = '$toll_allow', ";
					}
					if (strlen($call_timeout) > 0) {
						$sql .= "call_timeout = '$call_timeout', ";
					}
					$sql .= "call_group = '$call_group', ";
					$sql .= "hold_music = '$hold_music', ";
					$sql .= "auth_acl = '$auth_acl', ";
					$sql .= "cidr = '$cidr', ";
					$sql .= "sip_force_contact = '$sip_force_contact', ";
					if (strlen($sip_force_expires) == 0) {
						$sql .= "sip_force_expires = null, ";
					}
					else {
						$sql .= "sip_force_expires = '$sip_force_expires', ";
					}
					if (strlen($nibble_account) == 0) {
						$sql .= "nibble_account = null, ";
					}
					else {
						$sql .= "nibble_account = '$nibble_account', ";
					}
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
					$sql .= "mwi_account = '$mwi_account', ";
					$sql .= "sip_bypass_media = '$sip_bypass_media', ";
					$sql .= "dial_string = '$dial_string', ";
					if (permission_exists('extension_enabled')) {
						$sql .= "enabled = '$enabled', ";
					}
					$sql .= "description = '$description' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and extension_uuid = '$extension_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);

				//add or update voicemail
					require_once "app/extensions/resources/classes/extension.php";
					$ext = new extension;
					$ext->db = $db;
					$ext->domain_uuid = $domain_uuid;
					$ext->extension = $extension;
					$ext->number_alias = $number_alias;
					$ext->vm_password = $vm_password;
					$ext->vm_mailto = $vm_mailto;
					$ext->vm_attach_file = $vm_attach_file;
					$ext->vm_keep_local_after_email = $vm_keep_local_after_email;
					$ext->vm_enabled = $vm_enabled;
					$ext->description = $description;
					$ext->voicemail();
					unset($ext);
			} //if ($action == "update")

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
//					require_once "app/provision/provision_write.php";
//					$ext = new extension;

				//delete extension from memcache
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_cmd = "memcache delete directory:".$extension."@".$user_context;
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}
			}

		//show the action and redirect the user
			if ($action == "add") {
				//prepare for alternating the row style
					$c = 0;
					$row_style["0"] = "row_style0";
					$row_style["1"] = "row_style1";

				//show the action and redirect the user
					require_once "resources/header.php";
					echo "<br />\n";
					echo "<div align='center'>\n";
					if (count($generated_users) == 0) {
						//action add
							echo "<meta http-equiv=\"refresh\" content=\"2;url=extensions.php\">\n";
							echo "	<table width='40%'>\n";
							echo "		<tr>\n";
							echo "			<th align='left'>".$text['message-message']."</th>\n";
							echo "		</tr>\n";
							echo "		<tr>\n";
							echo "			<td class='row_style1'><strong>".$text['message-add']."</strong></td>\n";
							echo "		</tr>\n";
							echo "	</table>\n";
							echo "	<br />\n";
					}
					else {
						//auto-generate user with extension as login name
							echo "	<table width='40%' border='0' cellpadding='0' cellspacing='0'>\n";
							echo "		<tr>\n";
							echo "			<td colspan='2'><strong>New User Accounts</strong></td>\n";
							echo "		</tr>\n";
							echo "		<tr>\n";
							echo "			<th>Username</th>\n";
							echo "			<th>Password</th>\n";
							echo "		</tr>\n";
							foreach($generated_users as $tmp_user){
								echo "		<tr>\n";
								echo "			<td valign='top' class='".$row_style[$c]."'>".$tmp_user['username']."</td>\n";
								echo "			<td valign='top' class='".$row_style[$c]."'>".$tmp_user['password']."</td>\n";
								echo "		</tr>\n";
							}
							if ($c==0) { $c=1; } else { $c=0; }
							echo "	</table>";
					}
					echo "</div>\n";
					require_once "resources/footer.php";
					return;
			}
			if ($action == "update") {
				require_once "resources/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=extensions.php\">\n";
				echo "<br />\n";
				echo "<div align='center'>\n";
				echo "	<table width='40%'>\n";
				echo "		<tr>\n";
				echo "			<th align='left'>".$text['message-message']."</th>\n";
				echo "		</tr>\n";
				echo "		<tr>\n";
				if ($action == "update") {
					echo "			<td class='row_style1'><strong>".$text['message-update']."</strong></td>\n";
				}
				else {
					echo "			<td class='row_style1'><strong>".$text['message-add']."</strong></td>\n";
				}
				echo "		</tr>\n";
				echo "	</table>\n";
				echo "<br />\n";
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$extension_uuid = $_GET["id"];
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and extension_uuid = '$extension_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$extension = $row["extension"];
			$number_alias = $row["number_alias"];
			$password = $row["password"];
			$accountcode = $row["accountcode"];
			$effective_caller_id_name = $row["effective_caller_id_name"];
			$effective_caller_id_number = $row["effective_caller_id_number"];
			$outbound_caller_id_name = $row["outbound_caller_id_name"];
			$outbound_caller_id_number = $row["outbound_caller_id_number"];
			$emergency_caller_id_number = $row["emergency_caller_id_number"];
			$directory_full_name = $row["directory_full_name"];
			$directory_visible = $row["directory_visible"];
			$directory_exten_visible = $row["directory_exten_visible"];
			$limit_max = $row["limit_max"];
			$limit_destination = $row["limit_destination"];
			//$vm_password = $row["vm_password"];
			//$vm_enabled = $row["vm_enabled"];
			//$vm_mailto = $row["vm_mailto"];
			//$vm_attach_file = $row["vm_attach_file"];
			//$vm_keep_local_after_email = $row["vm_keep_local_after_email"];
			$user_context = $row["user_context"];
			$toll_allow = $row["toll_allow"];
			$call_timeout = $row["call_timeout"];
			$call_group = $row["call_group"];
			$hold_music = $row["hold_music"];
			$auth_acl = $row["auth_acl"];
			$cidr = $row["cidr"];
			$sip_force_contact = $row["sip_force_contact"];
			$sip_force_expires = $row["sip_force_expires"];
			$nibble_account = $row["nibble_account"];
			$mwi_account = $row["mwi_account"];
			$sip_bypass_media = $row["sip_bypass_media"];
			$dial_string = $row["dial_string"];
			$enabled = $row["enabled"];
			$description = $row["description"];
		}
		unset ($prep_statement);
	}

//get the voicemail data
	$sql = "select * from v_voicemails ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (is_numeric($extension)) {
		$sql .= "and voicemail_id = '$extension' ";
	}
	else {
		$sql .= "and voicemail_id = '$number_alias' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$vm_password = $row["voicemail_password"];
		//$greeting_id = $row["greeting_id"];
		$vm_mailto = $row["voicemail_mail_to"];
		$vm_mailto = str_replace(" ", "", $vm_mailto);
		$vm_attach_file = $row["voicemail_attach_file"];
		$vm_keep_local_after_email = $row["voicemail_local_after_email"];
		$vm_enabled = $row["voicemail_enabled"];
	}
	unset ($prep_statement);

//clean the variables
	$vm_password = str_replace("#", "", $vm_password);
	$vm_mailto = str_replace(" ", "", $vm_mailto);

//set the defaults
	if (strlen($limit_max) == 0) { $limit_max = '5'; }
	if (strlen($call_timeout) == 0) { $call_timeout = '30'; }

//begin the page content
	require_once "resources/header.php";
	if ($action == "update") {
	$page["title"] = $text['title-extension-edit'];
	}
	else if ($action == "add") {
		$page["title"] = $text['title-extension-add'];
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
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "\n";
	echo "function hide_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"none\";\n";
	echo "}\n";
	echo "</script>";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-extension-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-extension-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='submit' class='btn' name='submit' value='".$text['button-save']."'>\n";
	if ($action != "add") {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"var new_ext = prompt('".$text['message_extension']."'); if (new_ext != null) { window.location='extension_copy.php?id=".$extension_uuid."&ext=' + new_ext; }\" value='".$text['button-copy']."'>\n";
	}
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='extensions.php'\" value='".$text['button-back']."'>\n";
	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-extension'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='extension' autocomplete='off' maxlength='255' value=\"$extension\">\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-number_alias'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='number_alias' autocomplete='off' maxlength='255' value=\"$number_alias\">\n";
	echo "<br />\n";
	echo $text['description-number_alias']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-password'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='password' name='password' id='password' onfocus=\"document.getElementById('show_password').innerHTML = '".$text['label-password'].": '+document.getElementById('password').value;\" autocomplete='off' maxlength='50' value=\"$password\">\n";
		echo "<br />\n";
		echo "<span onclick=\"document.getElementById('show_password').innerHTML = ''\">".$text['description-password']." </span><span id='show_password'></span>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if ($action == "add") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-range'].":\n";
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
		echo "    <option value='500'>750</option>\n";
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

	if ($action == "update") {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-user_list'].":</td>";
		echo "		<td class='vtable'>";

		echo "			<table width='52%'>\n";
		$sql = "SELECT u.username, e.user_uuid FROM v_extension_users as e, v_users as u ";
		$sql .= "where e.user_uuid = u.user_uuid  ";
		$sql .= "and u.user_enabled = 'true' ";
		$sql .= "and e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and e.extension_uuid = '".$extension_uuid."' ";
		$sql .= "order by u.username asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $field) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>".$field['username']."</td>\n";
			echo "				<td>\n";
			echo "					<a href='extension_edit.php?id=".$extension_uuid."&domain_uuid=".$_SESSION['domain_uuid']."&user_uuid=".$field['user_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			echo "				</td>\n";
			echo "			</tr>\n";
		}
		echo "			</table>\n";

		echo "			<br />\n";
		$sql = "SELECT * FROM v_users ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and user_enabled = 'true' ";
		$sql .= "order by username asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		echo "			<select name=\"user_uuid\" class='frm'>\n";
		echo "			<option value=\"\"></option>\n";
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $field) {
			echo "			<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
		}
		echo "			</select>";
		echo "			<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
		unset($sql, $result);
		echo "			<br>\n";
		echo "			".$text['description-user_list']."\n";
		echo "			<br />\n";
		echo "		</td>";
		echo "	</tr>";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-vm_password'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='password' name='vm_password' id='vm_password' onfocus=\"document.getElementById('show_vm_password').innerHTML = '".$text['label-password'].": '+document.getElementById('vm_password').value;\" maxlength='255' value='$vm_password'>\n";
	echo "<br />\n";
	echo "<span onclick=\"document.getElementById('show_vm_password').innerHTML = ''\">".$text['description-vm_password']." </span><span id='show_vm_password'></span>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-accountcode'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='accountcode' maxlength='255' value=\"$accountcode\">\n";
	echo "<br />\n";
	echo $text['description-accountcode']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-effective_caller_id_name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='effective_caller_id_name' maxlength='255' value=\"$effective_caller_id_name\">\n";
	echo "<br />\n";
	echo $text['description-effective_caller_id_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-effective_caller_id_number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='effective_caller_id_number' maxlength='255' value=\"$effective_caller_id_number\">\n";
	echo "<br />\n";
	echo $text['description-effective_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-outbound_caller_id_name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('outbound_caller_id_select')) {
		$sql = "select * from v_destinations ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and destination_type = 'inbound' ";
		$sql .= "order by destination_number asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if (count($result) > 0) {
			echo "	<select name='outbound_caller_id_name' id='outbound_caller_id_name' class='formfld'>\n";
			echo "	<option></option>\n";
			foreach ($result as &$row) {
				if ($outbound_caller_id_name == $row["destination_caller_id_name"]) {
					echo "		<option value='".$row["destination_caller_id_name"]."' selected='selected'>".$row["destination_caller_id_name"]."</option>\n";
				}
				else {
					echo "		<option value='".$row["destination_caller_id_name"]."'>".$row["destination_caller_id_name"]."</option>\n";
				}
			}
			echo "		</select>\n";
			echo "<br />\n";
			echo $text['description-outbound_caller_id_name-select']."\n";
		}
		else {
			echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
		}
		unset ($prep_statement);
	}
	else {
		echo "    <input class='formfld' type='text' name='outbound_caller_id_name' maxlength='255' value=\"$outbound_caller_id_name\">\n";
		echo "<br />\n";
		echo $text['description-outbound_caller_id_name-custom']."\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-outbound_caller_id_number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('outbound_caller_id_select')) {
		$sql = "select * from v_destinations ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and destination_type = 'inbound' ";
		$sql .= "order by destination_number asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if (count($result) > 0) {
			echo "	<select name='outbound_caller_id_number' id='outbound_caller_id_number' class='formfld'>\n";
			echo "	<option></option>\n";
			foreach ($result as &$row) {
				if ($outbound_caller_id_number == $row["destination_caller_id_number"]) {
					echo "		<option value='".$row["destination_caller_id_number"]."' selected='selected'>".$row["destination_caller_id_number"]."</option>\n";
				}
				else {
					echo "		<option value='".$row["destination_caller_id_number"]."'>".$row["destination_caller_id_number"]."</option>\n";
				}
			}
			echo "		</select>\n";
			echo "<br />\n";
			echo $text['description-outbound_caller_id_number-select']."\n";
		}
		else {
			echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
		}
		unset ($prep_statement);
	}
	else {
		echo "    <input class='formfld' type='text' name='outbound_caller_id_number' maxlength='255' value=\"$outbound_caller_id_number\">\n";
		echo "<br />\n";
		echo $text['description-outbound_caller_id_number-custom']."\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-emergency_caller_id_number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='emergency_caller_id_number' maxlength='255' value=\"$emergency_caller_id_number\">\n";
	echo "<br />\n";
	echo $text['description-emergency_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-directory_full_name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='directory_full_name' maxlength='255' value=\"$directory_full_name\">\n";
	echo "<br />\n";
	echo $text['description-directory_full_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-directory_visible'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='directory_visible'>\n";
	echo "    <option value=''></option>\n";
	if ($directory_visible == "true" || $directory_visible == "") {
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
	echo "    ".$text['label-directory_exten_visible'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='directory_exten_visible'>\n";
	echo "    <option value=''></option>\n";
	if ($directory_exten_visible == "true" || $directory_exten_visible == "") {
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
	echo "    ".$text['label-limit_max'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='limit_max' maxlength='255' value=\"$limit_max\">\n";
	echo "<br />\n";
	echo $text['description-limit_max']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-limit_destination'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='limit_destination' maxlength='255' value=\"$limit_destination\">\n";
	echo "<br />\n";
	echo $text['description-limit_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-provisioning'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		echo "		<table width='52%'>\n";
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
		//	echo "					<a href='device_line_edit.php?device_uuid=".$row['device_uuid']."&id=".$row['device_line_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
		//}
		//if (permission_exists('device_delete')) {
		//	echo "					<a href='device_line_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_line_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
		//}
		echo "				</td>\n";
		echo "			</tr>\n";

		$sql = "SELECT d.device_mac_address, d.device_template, d.device_description, l.device_line_uuid, l.device_uuid, l.line_number ";
		$sql .= "FROM v_device_lines as l, v_devices as d ";
		$sql .= "WHERE l.user_id = '".$extension."' ";
		$sql .= "AND l.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "AND l.device_uuid = d.device_uuid ";
		$sql .= "ORDER BY d.device_mac_address asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $row) {
			$device_mac_address = $row['device_mac_address'];
			$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);
			echo "		<tr>\n";
			echo "			<td class='vtable'>".$row['line_number']."</td>\n";
			echo "			<td class='vtable'><a href='/app/devices/device_edit.php?id=".$row['device_uuid']."'>".$device_mac_address."</a></td>\n";
			echo "			<td class='vtable'>".$row['device_template']."&nbsp;</td>\n";
			//echo "			<td class='vtable'>".$row['device_description']."&nbsp;</td>\n";
			echo "			<td>\n";
			echo "				<a href='extension_edit.php?id=".$extension_uuid."&device_line_uuid=".$row['device_line_uuid']."&domain_uuid=".$_SESSION['domain_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			echo "			</td>\n";
			echo "		</tr>\n";
		}

		echo "		<tr>\n";
		echo "		<td class='vtable'>";
		echo "			<select id='line_number' name='line_number' style='width: 50;' onchange=\"$onchange\" class='formfld'>\n";
		echo "			<option value=''></option>\n";
		echo "			<option value='1'>1</option>\n";
		echo "			<option value='2'>2</option>\n";
		echo "			<option value='3'>3</option>\n";
		echo "			<option value='4'>4</option>\n";
		echo "			<option value='5'>5</option>\n";
		echo "			<option value='6'>6</option>\n";
		echo "			<option value='7'>7</option>\n";
		echo "			<option value='8'>8</option>\n";
		echo "			<option value='9'>9</option>\n";
		echo "			<option value='10'>10</option>\n";
		echo "			<option value='11'>11</option>\n";
		echo "			<option value='12'>12</option>\n";
		echo "			<option value='13'>13</option>\n";
		echo "			<option value='14'>14</option>\n";
		echo "			<option value='15'>15</option>\n";
		echo "			<option value='16'>16</option>\n";
		echo "			<option value='17'>17</option>\n";
		echo "			<option value='18'>18</option>\n";
		echo "			<option value='19'>19</option>\n";
		echo "			<option value='20'>20</option>\n";
		echo "			<option value='21'>21</option>\n";
		echo "			<option value='22'>22</option>\n";
		echo "			<option value='23'>23</option>\n";
		echo "			<option value='24'>24</option>\n";
		echo "			<option value='25'>25</option>\n";
		echo "			<option value='26'>26</option>\n";
		echo "			<option value='27'>27</option>\n";
		echo "			<option value='28'>28</option>\n";
		echo "			<option value='29'>29</option>\n";
		echo "			<option value='30'>30</option>\n";
		echo "			<option value='31'>31</option>\n";
		echo "			<option value='32'>32</option>\n";
		echo "			<option value='50'>50</option>\n";
		echo "			<option value='100'>100</option>\n";
		echo "			<option value='120'>120</option>\n";
		echo "			<option value='150'>150</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";

		echo "		<td class='vtable'>";
		echo "			<table width='90%' border='0' cellpadding='1' cellspacing='0'>\n";
		echo "			<tr>\n";
		echo "			<td id=\"cell_device_mac_address_1\" width='80%' nowrap='nowrap'>\n";
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
			tb.value=obj.options[obj.selectedIndex].value;
			document.getElementById('btn_select_to_input_device_mac_address').style.visibility = 'hidden';
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.type='button';
			tbb.value='<';
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
		$sql = "SELECT * FROM v_devices ";
		$sql .= "WHERE domain_uuid = '".$_SESSION["domain_uuid"]."' ";
		$sql .= "ORDER BY device_mac_address asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		echo "				<select id=\"device_mac_address\" name=\"device_mac_address\" class='formfld' style='width: 80;' onchange='changeToInput_device_mac_address(this);this.style.visibility = \"hidden\";'>\n";
		echo "					<option value=''></option>\n";
		if (count($result) > 0) {
			foreach($result as $field) {
				if (strlen($field["device_mac_address"]) > 0) {
					if ($field_current_value == $field["device_mac_address"]) {
						echo "					<option value=\"".$field["device_mac_address"]."\" selected>".$field["device_mac_address"]."</option>\n";
					}
					else {
						echo "					<option value=\"".$field["device_mac_address"]."\">".$field["device_mac_address"]."  ".$field['device_model']." ".$field['device_description']."</option>\n";
					}
				}
			}
		}
		unset($sql, $result, $result_count);
		echo "				</select>\n";
		echo "				<input type='button' id='btn_select_to_input_device_mac_address' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_device_mac_address(document.getElementById(\"device_mac_address\"));this.style.visibility = \"hidden\";' value='<'>\n";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";

		echo "		</td>\n";
		echo "		<td class='vtable'>";
		echo "<select id='device_template' name='device_template' style='width: 90%' class='formfld'>\n";
		echo "<option value=''></option>\n";
		if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/".$_SESSION["domain_name"])) {
			$temp_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/".$_SESSION["domain_name"];
		} else {
			$temp_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision";
		}
		if($dh = opendir($temp_dir)) {
			while($dir = readdir($dh)) {
				if($file != "." && $dir != ".." && $dir[0] != '.') {
					if(is_dir($temp_dir . "/" . $dir)) {
						echo "<optgroup label='$dir'>";
						if($dh_sub = opendir($temp_dir.'/'.$dir)) {
							while($dir_sub = readdir($dh_sub)) {
								if($file_sub != '.' && $dir_sub != '..' && $dir_sub[0] != '.') {
									if(is_dir($temp_dir . '/' . $dir .'/'. $dir_sub)) {
										if ($device_template == $dir."/".$dir_sub) {
											echo "<option value='".$dir."/".$dir_sub."' selected='selected'>".$dir."/".$dir_sub."</option>\n";
										}
										else {
											echo "<option value='".$dir."/".$dir_sub."'>".$dir."/".$dir_sub."</option>\n";
										}
									}
								}
							}
							closedir($dh_sub);
						}
						echo "</optgroup>";
					}
				}
			}
			closedir($dh);
		}
		echo "</select>\n";
		echo "		</td>\n";
		echo "		<td>\n";
		echo "			<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
		echo "		</td>\n";
		echo "		</table>\n";
		echo "		<br />\n";
		echo $text['description-provisioning']."\n";

		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-vm_enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='vm_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($vm_enabled == "true" || $vm_enabled == "") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($vm_enabled == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-vm_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-vm_mailto'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='vm_mailto' maxlength='255' value=\"$vm_mailto\">\n";
	echo "<br />\n";
	echo $text['description-vm_mailto']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-vm_attach_file'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='vm_attach_file'>\n";
	echo "    <option value=''></option>\n";
	if ($vm_attach_file == "true") {
		echo "    <option value='true' selected >".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($vm_attach_file == "false") {
		echo "    <option value='false' selected >".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-vm_attach_file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-vm_keep_local_after_email'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='vm_keep_local_after_email'>\n";
	echo "    <option value=''></option>\n";
	if ($vm_keep_local_after_email == "true") {
		echo "    <option value='true' selected >".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($vm_keep_local_after_email == "false") {
		echo "    <option value='false' selected >".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-vm_keep_local_after_email']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('extension_toll')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-toll_allow'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='toll_allow' maxlength='255' value=\"$toll_allow\">\n";
		echo "<br />\n";
		echo $text['description-toll_allow']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_timeout'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_timeout' maxlength='255' value=\"$call_timeout\">\n";
	echo "<br />\n";
	echo $text['description-call_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_group'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_group' maxlength='255' value=\"$call_group\">\n";
	echo "<br />\n";
	echo $text['description-call_group']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-hold_music'].":\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
	$moh= new switch_music_on_hold;
	$moh->select_name = "hold_music";
	$moh->select_value = $hold_music;
	echo $moh->select();
	echo "	<br />\n";
	echo $text['description-hold_music']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (if_group("superadmin")) {
		if (strlen($user_context) == 0) {
			if (count($_SESSION["domains"]) > 1) {
				$user_context = $_SESSION['domain_name'];
			}
			else {
				$user_context = "default";
			}
		}
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-user_context'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='user_context' maxlength='255' value=\"$user_context\">\n";
		echo "<br />\n";
		echo $text['description-user_context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	//--- begin: show_advanced -----------------------
	echo "<tr>\n";
	echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap='nowrap'>\n";

	echo "	<div id=\"show_advanced_box\">\n";
	echo "		<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
	echo "		<tr>\n";
	echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">".$text['label-show_advanced']."</td>\n";
	echo "		<td width=\"70%\" class=\"vtable\">\n";
	echo "			<input type=\"button\" class='btn' onClick=\"show_advanced_config()\" value=\"".$text['button-advanced']."\"></input></a>\n";
	echo "		</td>\n";
	echo "		</tr>\n";
	echo "		</table>\n";
	echo "	</div>\n";

	echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-auth_acl'].":\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='auth_acl' maxlength='255' value=\"$auth_acl\">\n";
	echo "<br />\n";
	echo $text['description-auth_acl']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-cidr'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='cidr' maxlength='255' value=\"$cidr\">\n";
	echo "<br />\n";
	echo $text['description-cidr']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-sip_force_contact'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='sip_force_contact'>\n";
	echo "    <option value=''></option>\n";
	switch ($sip_force_contact) {
		case "NDLB-connectile-dysfunction" : 		$selected[1] = "selected='selected'"; 	break;
		case "NDLB-connectile-dysfunction-2.0" : 	$selected[2] = "selected='selected'"; 	break;
		case "NDLB-tls-connectile-dysfunction" : 	$selected[3] = "selected='selected'"; 	break;
	}
	echo "    <option value='NDLB-connectile-dysfunction' ".$selected[1].">Rewrite Contact IP and Port</option>\n";
	echo "    <option value='NDLB-connectile-dysfunction-2.0' ".$selected[2].">Rewrite Contact IP and Port 2.0</option>\n";
	echo "    <option value='NDLB-tls-connectile-dysfunction' ".$selected[3].">Rewrite Contact Port</option>\n";
	unset($selected);
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-sip_force_contact']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-sip_force_expires'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='sip_force_expires' maxlength='255' value=\"$sip_force_expires\">\n";
	echo "<br />\n";
	echo $text['description-sip_force_expires']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-nibble_account'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='nibble_account' maxlength='255' value=\"$nibble_account\">\n";
	echo "<br />\n";
	echo $text['description-nibble_account']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-mwi_account'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='mwi_account' maxlength='255' value=\"$mwi_account\">\n";
	echo "<br />\n";
	echo $text['description-mwi_account']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-sip_bypass_media'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='sip_bypass_media'>\n";
	echo "    <option value=''></option>\n";
	switch ($sip_bypass_media) {
		case "bypass-media" : 				$selected[1] = "selected='selected'"; 	break;
		case "bypass-media-after-bridge" : 	$selected[2] = "selected='selected'"; 	break;
		case "proxy-media" : 				$selected[3] = "selected='selected'"; 	break;
	}
	echo "    <option value='bypass-media' ".$selected[1].">Bypass Media</option>\n";
	echo "    <option value='bypass-media-after-bridge'".$selected[2].">Bypass Media After Bridge</option>\n";
	echo "    <option value='proxy-media'".$selected[3].">Proxy Media</option>\n";
	unset($selected);
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-sip_bypass_media']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-dial_string'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dial_string' maxlength='4096' value=\"$dial_string\">\n";
	echo "<br />\n";
	echo $text['description-dial_string']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	</table>\n";
	echo "	</div>";

	echo "</td>\n";
	echo "</tr>\n";
	//--- end: show_advanced -----------------------

	if (permission_exists('extension_enabled')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-enabled'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='enabled'>\n";
		echo "    <option value=''></option>\n";
		if ($enabled == "true" || strlen($enabled) == 0) {
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
	echo "    ".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea class='formfld' name='description' rows='4'>$description</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='extension_uuid' value='$extension_uuid'>\n";
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