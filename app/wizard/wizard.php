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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//include the class
	require_once "app/wizard/resources/classes/wizard.php";
	require_once "resources/check_auth.php";
//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('wizard_import') || permission_exists('extension_add') || permission_exists('contact_add') || permission_exists('device_add') || permission_exists('user_add') || permission_exists('contact_email_add') || permission_exists('voicemail_add') || permission_exists('extension_enabled')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}
	
//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get total extension count from the database, check limit, if defined
	if ($_SESSION['limit']['extensions']['numeric'] != '') {
		$sql = "select count(*) as num_rows from v_extensions where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			$total_extensions = $row['num_rows'];
		}
		unset($prep_statement, $row);
		if ($total_extensions >= $_SESSION['limit']['extensions']['numeric']) {
			$_SESSION['message_mood'] = 'negative';
			$_SESSION['message'] = $text['message-maximum_extensions'].' '.$_SESSION['limit']['extensions']['numeric'];
			header('Location: /app/extensions/extensions.php');
			return;
		}
	}

//get the http values and set them as php variables
	if (count($_POST) > 0) {
		//get the values from the HTTP POST and save them as PHP variables
			$extension = str_replace(' ','-',check_str($_POST["extension"]));
			$password = check_str($_POST["password"]);
			$password_confirm = check_str($_POST["password_confirm"]);
			$wizard_template_uuid = check_str($_POST["wizard_template_uuid"]);
			$username = check_str($_POST["username"]);
			$extension = check_str($_POST["extension"]);
			$contact_name_family = check_str($_POST["contact_name_family"]);
			$contact_name_given = check_str($_POST["contact_name_given"]);
			$voicemail_password = check_str($_POST["voicemail_password"]);
			$mac_address = check_str($_POST["device_mac_address"]);
			$device_template = check_str($_POST["device_template"]);
			$device_profile_uuid = check_str($_POST["device_profile_uuid"]);
			$override_caller_id_number_disabled = check_str($_POST["override_caller_id_number_disabled"]);
			$override_caller_id_number_enabled = check_str($_POST["override_caller_id_number_enabled"]);
			$override_caller_id_number = check_str($_POST["override_caller_id_number"]);
			$override_em_caller_id_number_disabled = check_str($_POST["override_em_caller_id_number_disabled"]);
			$override_em_caller_id_number_enabled = check_str($_POST["override_em_caller_id_number_enabled"]);
			$override_em_caller_id_number = check_str($_POST["override_em_caller_id_number"]);
			$user_email = check_str($_POST["user_email"]);
			$voicemail_password = check_str($_POST["voicemail_password"]);
			//normalize mac
			$device_mac_address =  wizard::normalize_mac($mac_address);
			
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the domain_uuid
			if (permission_exists('extension_domain')) {
				$domain_uuid = check_str($_POST["domain_uuid"]);
			}
			else {
				$domain_uuid = $_SESSION['domain_uuid'];
			}
		
		//check for all required data
			$msg = '';
			//check for Wizard Template
			if (strlen($wizard_template_uuid) == 0) { $msg .= $text['message-required'].$text['label-wizard_template']."<br>\n"; }
			//check extension length
			if (strlen($extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			//check user length
			if (strlen($username) == 0) { $msg .= $text['message-required'].$text['label-user']."<br>\n"; }	
			//check first name
			if (strlen($contact_name_given) == 0) { $msg .= $text['message-required'].$text['label-first_name']."<br>\n"; }	
			//check last name
			if (strlen($contact_name_family) == 0) { $msg .= $text['message-required'].$text['label-last_name']."<br>\n"; }				
			//check voicemail password
			if (strlen($voicemail_password) == 0) { $msg .= $text['message-required'].$text['label-voicemail_password']."<br>\n"; }				
			//check passwords
			if ($password != '' && $password != $password_confirm) { $msg .= $text['message-password_mismatch']."<br>\n"; }	
			if ($password == '') { $msg .= $text['message-password_blank']."<br>\n"; }	
			if (!check_password_strength($password, $text)) {
					unset($password,$password_confirm);
					header("Location: wizard.php");
				exit;
				
			}
			
			//set some variables in the where array for queries
			$where[0]['name'] = 'domain_uuid';
			$where[0]['operator'] = '=';
			$where[0]['value'] = $_SESSION["domain_uuid"];

			//check for duplicate extension in database
			$database = new database;
			$database->table = "v_extensions";
			$where[1]["name"] = "extension";
			$where[1]["operator"] = "=";
			$where[1]["value"] = "$extension";
			$database->where = $where;
			$result = $database->count();
			if ($result > 0) {
					$msg .= $text['message-warning'].$text['message-duplicate_extension']."<br>\n";
				}
			unset($result,$database);			
			
			//check for duplicate usernames
			$database = new database;
			$database->table = "v_users";
			$where[1]["name"] = "username";
			$where[1]["operator"] = "=";
			$where[1]["value"] = "$username";
			$database->where = $where;
			$result = $database->count();
			if ($result > 0) {
					$msg .= $text['message-warning'].$text['message-duplicate_user']."<br>\n";
				}
			unset($result,$database);

			//check duplicate voicemail
			$database = new database;
			$database->table = "v_voicemails";
			$where[1]["name"] = "voicemail_id";
			$where[1]["operator"] = "=";
			$where[1]["value"] = "$extension";
			$database->where = $where;
			$result = $database->count();
			if ($result > 0) {
					$msg .= $text['message-warning'].$text['message-duplicate_voicemail']."<br>\n";
				}
			unset($result,$database);			
			
			//check valid email
			if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
				$msg .= $text['message-warning'].$text['message-invalid_email']."<br>\n";
			}	
			//check valid mac
			if (!wizard::is_valid_mac($device_mac_address)) {
				$msg .= $text['message-warning'].$text['message-invalid_mac']."<br>\n";
			}	

			//check duplicate mac in the database across all domains
			$database = new database;
			$database->table = "v_devices";
			$where[1]["name"] = "device_mac_address";
			$where[1]["operator"] = "=";
			$where[1]["value"] = "$device_mac_address";
			$database->where = $where;
			$result = $database->count();
			if ($result > 0) {
					$msg .= $text['message-warning'].$text['message-duplicate_mac']."<br>\n";
				}
			unset($where,$result,$database);			
	}		
//display error msg if error found			
	if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
		require_once "resources/header.php";
		require_once "resources/persist_form_var.php";
		echo "<div align='center'>\n";
		echo "<table><tr><td>\n";
		echo $msg."<br />";
		//echo $override_caller_id_number_enabled."<br />";
		echo "</td></tr></table>\n";
		persistformvar($_POST);
		echo "</div>\n";
		require_once "resources/footer.php";
		return;
	}

//Gather data and insert into database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
		//get template values
		$sql = "select * from v_wizard_templates ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and wizard_template_uuid = '$wizard_template_uuid' ";
		$database = new database;
		$database->select($sql);
		$result = $database->result;
		foreach ($result as &$row) {
			$wizard_template_name = $row["wizard_template_name"];
			if ($override_em_caller_id_number_enabled == "false") {
				$emergency_caller_id_number = $row["emergency_caller_id_number"];
			} else {
				$emergency_caller_id_number = $override_em_caller_id_number;
			}
			if ($override_caller_id_number_enabled == "false") {
				$outbound_caller_id_number = $row["outbound_caller_id_number"];
			} else {
				$outbound_caller_id_number = $override_caller_id_number;
			}
			$call_group = $row["call_group"];
			$toll_allow = $row["toll_allow"];
			$hold_music = $row["hold_music"];
			$user_record = $row["user_record"];
			$call_timeout = $row["call_timeout"];
			$forward_user_not_registered_destination = $row["forward_user_not_registered_destination"];
			$forward_user_not_registered_enabled = $row["forward_user_not_registered_enabled"];
			$time_zone = $row["time_zone"];
			//$description = $row["description"];
			$group_uuid = $row["wizard_group_uuid"];
		}
		
		//set lots of variables
			$salt = uuid();
			$user_uuid = uuid();
			$accountcode = $_SESSION['domain_name'];
			$auth_id = generate_password();
			$call_screen_enabled = "false";
			$call_timeout = "30";
			$contact_email_uuid = uuid();
			$contact_nickname = $extension;
			$contact_type = "user";
			$contact_user_uuid = uuid();
			$contact_uuid = uuid();
			$context = $_SESSION['domain_name'];
			$description_ext = $contact_name_given . " " .$contact_name_family;
			$device_description = $contact_name_given . " " .$contact_name_family;
			$device_enabled = "true";
			$device_label = $extension;
			$device_line_enabled = "true";
			$device_line_uuid = uuid();
			$device_user_uuid = $user_uuid;
			$device_uuid = uuid();
			$directory_exten_visible = "true";
			$directory_full_name = $contact_name_given . " " .$contact_name_family;
			$directory_visible = 'true';
			$directory_exten_visible = 'true';
			$display_name = $extension;
			$domain_name = $_SESSION['domain_name'];
			$domain_uuid = $_SESSION['domain_uuid'];
			$effective_caller_id_name = $contact_name_given . " " .$contact_name_family;
			$effective_caller_id_number = $extension; 
			$email = $user_email;
			$email_label = "Work";
			$emergency_caller_id_name = $contact_name_given . " " .$contact_name_family;
			$extension_description = $contact_name_given . " " .$contact_name_family;
			$extension_enabled = "true";
			$extension_password = generate_password();
			$extension_user_uuid = uuid();
			$extension_uuid = uuid();
			$group_user_uuid = uuid();
			$hold_music = "local_stream://default";
			$limit_max = "5";
			$line_number = "1";
			$outbound_caller_id_name = $contact_name_given . " " .$contact_name_family;
			$password_encrypted = md5($salt.$password);
			$register_expires = $_SESSION['provision']['line_register_expires']['numeric'];
			$server_address = $_SESSION['domain_name'];
			$sip_port = $_SESSION['provision']['line_sip_port']['numeric'];
			$sip_transport = $_SESSION['provision']['line_sip_transport']['text'];
			$user_context = $_SESSION['domain_name'];
			$user_enabled = "true";
			$user_id = $extension;
			$user_setting_category = "domain";
			$user_setting_enabled = "true";
			$user_setting_name_code = "code";
			$user_setting_name_name = "name";
			$user_setting_subcategory_language = "language";
			$user_setting_subcategory_timezone = "time_zone";
			$user_setting_value_language = $_SESSION['domain']['language']['code'];
			$user_setting_value_timezone = $_SESSION['domain']['time_zone']['name'];
			$user_setting_uuid_language = uuid();
			$user_setting_uuid_timezone = uuid();
			$user_time_zone = $_SESSION['domain']['time_zone']['name'];
			$valid_mac =  wizard::normalize_mac($mac_address);
			$voicemail_description = $contact_name_given . " " .$contact_name_family;
			$voicemail_enabled = "true";
			$voicemail_file = "attach";
			$voicemail_id = $extension;
			$voicemail_local_after_email = "true";
			$voicemail_mail_to = $user_email;
			$voicemail_uuid = uuid();
		
			//lookup group_name
				$sql = "select group_name from v_groups ";
				$sql .= "where group_uuid = '".$group_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$return_value = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($return_value as &$row) {
					$group_name = $row["group_name"];
				}
		
			//build the array
			$i=0;
			//v_extensions
				$array["extensions"][$i]["domain_uuid"] = $domain_uuid;
				$array["extensions"][$i]["extension_uuid"] = $extension_uuid;
				$array["extensions"][$i]["extension"] = $extension;
				//if (permission_exists('number_alias')) {
				//	$array["extensions"][$i]["number_alias"] = $number_alias;
				//}
				$array["extensions"][$i]["password"] = $extension_password;
				$array["extensions"][$i]["accountcode"] = $accountcode;
				$array["extensions"][$i]["effective_caller_id_name"] = $effective_caller_id_name;
				$array["extensions"][$i]["effective_caller_id_number"] = $effective_caller_id_number;
				$array["extensions"][$i]["outbound_caller_id_name"] = $outbound_caller_id_name;
				$array["extensions"][$i]["outbound_caller_id_number"] = $outbound_caller_id_number;
				$array["extensions"][$i]["emergency_caller_id_name"] = $emergency_caller_id_name;
				$array["extensions"][$i]["emergency_caller_id_number"] = $emergency_caller_id_number;
				$array["extensions"][$i]["directory_full_name"] = $directory_full_name;
				$array["extensions"][$i]["directory_visible"] = $directory_visible;
				$array["extensions"][$i]["directory_exten_visible"] = $directory_exten_visible;
				$array["extensions"][$i]["limit_max"] = $limit_max;
				$array["extensions"][$i]["limit_destination"] = $limit_destination;
				$array["extensions"][$i]["user_context"] = $user_context;
				$array["extensions"][$i]["call_timeout"] = $call_timeout;
				$array["extensions"][$i]["call_group"] = $call_group;
				$array["extensions"][$i]["call_screen_enabled"] = $call_screen_enabled;
				//$array["extensions"][$i]["user_record"] = $user_record;
				$array["extensions"][$i]["hold_music"] = $hold_music;
				$array["extensions"][$i]["forward_user_not_registered_destination"] = $forward_user_not_registered_destination;
				$array["extensions"][$i]["forward_user_not_registered_enabled"] = $forward_user_not_registered_enabled;
				
				/*$array["extensions"][$i]["auth_acl"] = $auth_acl;
				$array["extensions"][$i]["cidr"] = $cidr;
				$array["extensions"][$i]["sip_force_contact"] = $sip_force_contact;
				if (strlen($sip_force_expires) > 0) {
					$array["extensions"][$i]["sip_force_expires"] = $sip_force_expires;
				}
				if (if_group("superadmin")) {
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
				if (permission_exists('extension_absolute_codec_string')) {
					$sql .= "absolute_codec_string, ";
				}
				if (permission_exists('extension_force_ping')) {
					$sql .= "force_ping, ";
				}
				if (permission_exists('extension_dial_string')) {
					$sql .= "dial_string, ";
				} */
				$array["extensions"][$i]["enabled"] = $extension_enabled;
				$array["extensions"][$i]["description"] = $extension_description;
			
			//insert into v_contacts
				$array["contacts"][$i]["contact_uuid"] = $contact_uuid;
				$array["contacts"][$i]["domain_uuid"] = $domain_uuid;
				$array["contacts"][$i]["contact_type"] = $contact_type;
				$array["contacts"][$i]["contact_name_given"] = $contact_name_given;
				$array["contacts"][$i]["contact_name_family"] = $contact_name_family;
				$array["contacts"][$i]["contact_nickname"] = $contact_nickname;

			//insert into v_contact_emails
				$array["contact_emails"][$i]["contact_email_uuid"] = $contact_email_uuid;
				$array["contact_emails"][$i]["domain_uuid"] = $domain_uuid;
				$array["contact_emails"][$i]["email_label"] = $email_label;
				$array["contact_emails"][$i]["email_address"] = $email;
				$array["contact_emails"][$i]["contact_uuid"] = $contact_uuid;

			//insert in v_users
				$array["users"][$i]["user_uuid"] = $user_uuid;
				$array["users"][$i]["domain_uuid"] = $domain_uuid;
				$array["users"][$i]["username"] = $username;
				$array["users"][$i]["contact_uuid"] = $contact_uuid;
				$array["users"][$i]["user_enabled"] = $user_enabled;
				$array["users"][$i]["password"] = $password_encrypted;
				$array["users"][$i]["salt"] = $salt;
				
			//insert in v_group_users
				$array["group_users"][$i]["group_user_uuid"] = $group_user_uuid;
				$array["group_users"][$i]["user_uuid"] = $user_uuid;
				$array["group_users"][$i]["domain_uuid"] = $domain_uuid;
				$array["group_users"][$i]["group_name"] = $group_name;
				$array["group_users"][$i]["group_uuid"] = $group_uuid;
			
			//insert in v_user_settings
				//Language
				$j=0;
				$array["user_settings"][$j]["user_setting_uuid"] = $user_setting_uuid_language;
				$array["user_settings"][$j]["user_uuid"] = $user_uuid;
				$array["user_settings"][$j]["domain_uuid"] = $domain_uuid;
				$array["user_settings"][$j]["user_setting_category"] = $user_setting_category;
				$array["user_settings"][$j]["user_setting_subcategory"] = $user_setting_subcategory_language;
				$array["user_settings"][$j]["user_setting_name"] = $user_setting_name_code;
				$array["user_settings"][$j]["user_setting_value"] = $user_setting_value_language;
				$array["user_settings"][$j]["user_setting_enabled"] = $user_setting_enabled;
				$j++;
				//Timezone
				$array["user_settings"][$j]["user_setting_uuid"] = $user_setting_uuid_timezone;
				$array["user_settings"][$j]["user_uuid"] = $user_uuid;
				$array["user_settings"][$j]["domain_uuid"] = $domain_uuid;
				$array["user_settings"][$j]["user_setting_category"] = $user_setting_category;
				$array["user_settings"][$j]["user_setting_subcategory"] = $user_setting_subcategory_timezone;
				$array["user_settings"][$j]["user_setting_name"] = $user_setting_name_name;
				$array["user_settings"][$j]["user_setting_value"] = $user_setting_value_timezone;
				$array["user_settings"][$j]["user_setting_enabled"] = $user_setting_enabled;				
		
			//insert in v_extension_users
				$array["extension_users"][$i]["extension_user_uuid"] = $extension_user_uuid;
				$array["extension_users"][$i]["domain_uuid"] = $domain_uuid;
				$array["extension_users"][$i]["extension_uuid"] = $extension_uuid;
				$array["extension_users"][$i]["user_uuid"] = $user_uuid;
				
			//insert in v_voicemails
				$array["voicemails"][$i]["voicemail_uuid"] = $voicemail_uuid;
				$array["voicemails"][$i]["voicemail_id"] = $voicemail_id;
				$array["voicemails"][$i]["domain_uuid"] = $domain_uuid;
				$array["voicemails"][$i]["voicemail_password"] = $voicemail_password;
				$array["voicemails"][$i]["voicemail_mail_to"] = $voicemail_mail_to;
				$array["voicemails"][$i]["voicemail_file"] = $voicemail_file;
				$array["voicemails"][$i]["voicemail_local_after_email"] = $voicemail_local_after_email;
				$array["voicemails"][$i]["voicemail_enabled"] = $voicemail_enabled;
				$array["voicemails"][$i]["voicemail_description"] = $voicemail_description;
			
			//insert in v_devices
				$array["devices"][$i]["device_uuid"] = $device_uuid;
				$array["devices"][$i]["domain_uuid"] = $domain_uuid;
				$array["devices"][$i]["device_profile_uuid"] = $device_profile_uuid;
				$array["devices"][$i]["device_mac_address"] = $device_mac_address;
				$array["devices"][$i]["device_label"] = $device_label;
				$array["devices"][$i]["device_vendor"] = $device_vendor;
				$array["devices"][$i]["device_enabled"] = $device_enabled;
				$array["devices"][$i]["device_template"] = $device_template;
				$array["devices"][$i]["device_description"] = $device_description;
				$array["devices"][$i]["device_user_uuid"] = $device_user_uuid;
				
			//insert in v_device_lines
				$array["device_lines"][$i]["device_line_uuid"] = $device_line_uuid;
				$array["device_lines"][$i]["domain_uuid"] = $domain_uuid;
				$array["device_lines"][$i]["device_uuid"] = $device_uuid;
				$array["device_lines"][$i]["line_number"] = $line_number;
				$array["device_lines"][$i]["server_address"] = $server_address;
				$array["device_lines"][$i]["display_name"] = $display_name;
				$array["device_lines"][$i]["user_id"] = $user_id;
				$array["device_lines"][$i]["auth_id"] = $auth_id;
				$array["device_lines"][$i]["password"] = $extension_password;
				$array["device_lines"][$i]["sip_port"] = $sip_port;
				$array["device_lines"][$i]["sip_transport"] = $sip_transport;
				$array["device_lines"][$i]["register_expires"] = $register_expires;
				$array["device_lines"][$i]["enabled"] = $enabled;

			//save to the datbase
				$database = new database;
				$database->app_name = 'wizard';
				$database->app_uuid = null;
				$database->save($array);
				$message = $database->message;
				//echo "<pre>".print_r($message, true)."<pre>\n";
				//exit;
			
			//synchronize configuration
				if (is_writable($_SESSION['switch']['extensions']['dir'])) {
					require_once "app/extensions/resources/classes/extension.php";
					$ext = new extension;
					$ext->xml();
					unset($ext);
				}

			//write the provision files
				if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/provision')) {
					require_once "app/provision/provision_write.php";
					$ext = new extension;
				}

			//clear the cache
				$cache = new cache;
				$cache->delete("directory:".$extension."@".$user_context);
				if (permission_exists('number_alias') && strlen($number_alias) > 0) {
					$cache->delete("directory:".$number_alias."@".$user_context);
				}

				$_SESSION["message"] = $text['message-add'];
				header("Location: wizard.php");
				return;
	}




	
//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-wizard'];

//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-wizard']."</b><br>\n";
	echo "	</td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo 				"<input type='button' class='btn' alt='".$text['button-import']."' onclick=\"window.location='wizard_import.php'\" value='".$text['button-import']."'>\n";
	echo 				"<input type='button' class='btn' alt='".$text['button-templates']."' onclick=\"window.location='wizard_templates.php'\" value='".$text['button-templates']."'>\n";
	echo 				"<input type='button' class='btn' value='".$text['button-save']."' onclick=\"document.getElementById('action').value = '".$text['button-save']."'; submit_form();\">\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "  </tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-wizard']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

//start
//show the content
	echo "<script>\n";
	echo "	function compare_passwords() {\n";
	echo "		if (document.getElementById('password') === document.activeElement || document.getElementById('password_confirm') === document.activeElement) {\n";
	echo "			if ($('#password').val() != '' || $('#password_confirm').val() != '') {\n";
	echo "				if ($('#password').val() != $('#password_confirm').val()) {\n";
	echo "					$('#password').removeClass('formfld_highlight_good');\n";
	echo "					$('#password_confirm').removeClass('formfld_highlight_good');\n";
	echo "					$('#password').addClass('formfld_highlight_bad');\n";
	echo "					$('#password_confirm').addClass('formfld_highlight_bad');\n";
	echo "				}\n";
	echo "				else {\n";
	echo "					$('#password').removeClass('formfld_highlight_bad');\n";
	echo "					$('#password_confirm').removeClass('formfld_highlight_bad');\n";
	echo "					$('#password').addClass('formfld_highlight_good');\n";
	echo "					$('#password_confirm').addClass('formfld_highlight_good');\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			$('#password').removeClass('formfld_highlight_bad');\n";
	echo "			$('#password_confirm').removeClass('formfld_highlight_bad');\n";
	echo "			$('#password').removeClass('formfld_highlight_good');\n";
	echo "			$('#password_confirm').removeClass('formfld_highlight_good');\n";
	echo "		}\n";
	echo "	}\n";

	$req['length'] = $_SESSION['security']['password_length']['numeric'];
	$req['number'] = ($_SESSION['security']['password_number']['boolean'] == 'true') ? true : false;
	$req['lowercase'] = ($_SESSION['security']['password_lowercase']['boolean'] == 'true') ? true : false;
	$req['uppercase'] = ($_SESSION['security']['password_uppercase']['boolean'] == 'true') ? true : false;
	$req['special'] = ($_SESSION['security']['password_special']['boolean'] == 'true') ? true : false;

	echo "	function check_password_strength(pwd) {\n";
	echo "		if ($('#password').val() != '' || $('#password_confirm').val() != '') {\n";
	echo "			var msg_errors = [];\n";
	if (is_numeric($req['length']) && $req['length'] != 0) {
		echo "		var re = /.{".$req['length'].",}/;\n"; //length
		echo "		if (!re.test(pwd)) { msg_errors.push('".$req['length']."+ ".$text['label-characters']."'); }\n";
	}
	if ($req['number']) {
		echo "		var re = /(?=.*[\d])/;\n";  //number
		echo "		if (!re.test(pwd)) { msg_errors.push('1+ ".$text['label-numbers']."'); }\n";
	}
	if ($req['lowercase']) {
		echo "		var re = /(?=.*[a-z])/;\n";  //lowercase
		echo "		if (!re.test(pwd)) { msg_errors.push('1+ ".$text['label-lowercase_letters']."'); }\n";
	}
	if ($req['uppercase']) {
		echo "		var re = /(?=.*[A-Z])/;\n";  //uppercase
		echo "		if (!re.test(pwd)) { msg_errors.push('1+ ".$text['label-uppercase_letters']."'); }\n";
	}
	if ($req['special']) {
		echo "		var re = /(?=.*[\W])/;\n";  //special
		echo "		if (!re.test(pwd)) { msg_errors.push('1+ ".$text['label-special_characters']."'); }\n";
	}
	echo "			if (msg_errors.length > 0) {\n";
	echo "				var msg = '".$text['message-password_requirements'].": ' + msg_errors.join(', ');\n";
	echo "				display_message(msg, 'negative', '6000');\n";
	echo "				return false;\n";
	echo "			}\n";
	echo "			else {\n";
	echo "				return true;\n";
	echo "			}\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			return true;\n";
	echo "		}\n";
	echo "	}\n";

	echo "	function show_strenth_meter() {\n";
	echo "		$('#pwstrength_progress').slideDown();\n";
	echo "	}\n";
	echo "</script>\n";

	echo "<form name='frm' id='frm' method='post'>\n";
	echo "<input type='hidden' name='action' id='action' value=''>\n";
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
//Wizard Template
	if (permission_exists('wizard_template_view')) {
		$sql = "select * from v_wizard_templates ";
		$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
		$sql .= "order by wizard_template_name asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);
		if ($result_count > 0) {
			echo "	<tr>";
			echo "		<td class='vncellreq' valign='top'>".$text['label-wizard_template']."</td>";
			echo "		<td class='vtable' align='left'>";
			echo "			<select class='formfld' id='wizard_template_uuid' name='wizard_template_uuid'>\n";
			echo "				<option value=''></option>\n";
			foreach($result as $row) {
				echo "			<option value='".$row['wizard_template_uuid']."' ".(($row['wizard_template_uuid'] == $wizard_template_uuid) ? "selected='selected'" : null).">".$row['wizard_template_name']." ".(($row['domain_uuid'] == '') ? "&nbsp;&nbsp;(".$text['select-global'].")" : null)."</option>\n";
			}
			echo "			</select>\n";
			echo "			<button type='button' class='btn btn-default list_control_icon' id='wizard_template_edit' onclick=\"if($('#wizard_template_uuid').val() != '') window.location='wizard_templates_edit.php?id='+$('#wizard_template_uuid').val();\"><span class='glyphicon glyphicon-pencil'></span></button>";
			echo "			<button type='button' class='btn btn-default list_control_icon' onclick=\"window.location='wizard_templates_edit.php'\"><span class='glyphicon glyphicon-plus'></span></button>";
			echo "			<br>".$text['description-wizard_template']."\n";
			echo "		</td>";
			echo "	</tr>";
		}
	}	
	//Username
	echo "	<tr>";
	echo "		<td width='30%' class='vncellreq' valign='top'>".$text['label-username']."</td>";
	echo "		<td width='70%' class='vtable'>";
	echo "		<input type='text' class='formfld' name='username' id='username' value='".$username."' required='required'>\n";
	echo "		</td>";
	echo "	</tr>";
	//Extension
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='extension' autocomplete='off' maxlength='255' value=\"$extension\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	//Firstname
	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-first_name']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='contact_name_given' value='".$contact_name_given."'></td>";
	echo "	</tr>";
	//Lastname
	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-last_name']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='contact_name_family' value='".$contact_name_family."'></td>";
	echo "	</tr>";
	//Email	
	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-email']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='user_email' value='".$user_email."'></td>";
	echo "	</tr>";
	//Password
	echo "	<tr>";
	echo "		<td class='vncellreq".(($action == 'add') ? 'req' : null)."' valign='top'>".$text['label-password']."</td>";
	echo "		<td class='vtable'>";
	echo "			<input style='display: none;' type='password'>";
	echo "			<input type='password' autocomplete='off' class='formfld' name='password' id='password' value='' onkeypress='show_strenth_meter();' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();' required='required'>";
	echo "			<div id='pwstrength_progress' class='pwstrength_progress'></div>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncellreq".(($action == 'add') ? 'req' : null)."' valign='top'>".$text['label-confirm_password']."</td>";
	echo "		<td class='vtable'>";
	echo "			<input type='password' autocomplete='off' class='formfld' name='password_confirm' id='password_confirm' value='' onfocus='compare_passwords();' required='required' onkeyup='compare_passwords();' onblur='compare_passwords();'>";
	echo "		</td>";
	echo "	</tr>";
	//Voicemail Password
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_password' id='password' autocomplete='off' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete='off' maxlength='50' value=\"$voicemail_password\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_password']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	//MAC Address
	echo "<tr>\n";
	echo "<td class='vncellreq' width='30%' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_mac_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' width='70%' align='left'>\n";
	if (permission_exists('device_mac_address')) {
		echo "	<input class='formfld' type='text' name='device_mac_address' id='device_mac_address' maxlength='255' value=\"$device_mac_address\"/>\n";
		echo "<br />\n";
		echo $text['description-device_mac_address']."\n";
	}
	else {
		echo $device_mac_address;
	}
	echo "	<div style='display: none;' id='duplicate_mac_response'></div>\n";
	echo "</td>\n";
	echo "</tr>\n";
	//Device Template	
if (permission_exists('device_template')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_template']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		$device = new device;
		$template_dir = $device->get_template_dir();

		echo "<select id='device_template' name='device_template' class='formfld'>\n";
		echo "<option value=''></option>\n";

		if (is_dir($template_dir)) {
				$templates = scandir($template_dir);
				foreach($templates as $dir) {
					if($file != "." && $dir != ".." && $dir[0] != '.') {
						if(is_dir($template_dir . "/" . $dir)) {
							echo "<optgroup label='$dir'>";
							$dh_sub=$template_dir . "/" . $dir;
							if(is_dir($dh_sub)) {
								$templates_sub = scandir($dh_sub);
								foreach($templates_sub as $dir_sub) {
									if($file_sub != '.' && $dir_sub != '..' && $dir_sub[0] != '.') {
										if(is_dir($template_dir . '/' . $dir .'/'. $dir_sub)) {
											if ($device_template == $dir."/".$dir_sub) {
												echo "<option value='".$dir."/".$dir_sub."' selected='selected'>".$dir."/".$dir_sub."</option>\n";
											}
											else {
												echo "<option value='".$dir."/".$dir_sub."'>".$dir."/".$dir_sub."</option>\n";
											}
										}
									}
								}
							}
							echo "</optgroup>";
						}
					}
				}
			}
		echo "</select>\n";
		echo "<br />\n";
		echo $text['description-device_template']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	
//Device Profile
	if (permission_exists('device_profile_edit')) {
		//device profile
		$sql = "select * from v_device_profiles ";
		$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
		$sql .= "order by device_profile_name asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);
		if ($result_count > 0) {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>".$text['label-profile']."</td>";
			echo "		<td class='vtable' align='left'>";
			echo "			<select class='formfld' id='device_profile_uuid' name='device_profile_uuid'>\n";
			echo "				<option value=''></option>\n";
			foreach($result as $row) {
				echo "			<option value='".$row['device_profile_uuid']."' ".(($row['device_profile_uuid'] == $device_profile_uuid) ? "selected='selected'" : null).">".$row['device_profile_name']." ".(($row['domain_uuid'] == '') ? "&nbsp;&nbsp;(".$text['select-global'].")" : null)."</option>\n";
			}
			echo "			</select>\n";
			echo "			<button type='button' class='btn btn-default list_control_icon' id='device_profile_edit' onclick=\"if($('#device_profile_uuid').val() != '') window.location='device_profile_edit.php?id='+$('#device_profile_uuid').val();\"><span class='glyphicon glyphicon-pencil'></span></button>";
			echo "			<button type='button' class='btn btn-default list_control_icon' onclick=\"window.location='device_profile_edit.php'\"><span class='glyphicon glyphicon-plus'></span></button>";
			echo "			<br>".$text['description-profile2']."\n";
			echo "		</td>";
			echo "	</tr>";
		}
	}	
	//Override Template Caller-ID Number
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-override_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('override_caller_id_number').focus();";
	echo "	<label for='override_caller_id_number_disabled'><input type='radio' name='override_caller_id_number_enabled' id='override_caller_id_number_disabled' onclick=\"\" value='false' ".(($override_caller_id_number_enabled == "false" || $override_caller_id_number_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='override_caller_id_number_enabled'><input type='radio' name='override_caller_id_number_enabled' id='override_caller_id_number_enabled' onclick=\"$on_click\" value='true' ".(($override_caller_id_number_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='override_caller_id_number' id='override_caller_id_number' maxlength='255' placeholder=\"".$text['label-override_caller_id_number']."\" value=\"".$override_caller_id_number."\">\n";
	echo "	<br />".$text['description-override_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	//Override Template Emergency Caller-ID Number
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-override_em_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('override_em_caller_id_number').focus();";
	echo "	<label for='override_em_caller_id_number_disabled'><input type='radio' name='override_em_caller_id_number_enabled' id='override_em_caller_id_number_disabled' onclick=\"\" value='false' ".(($override_em_caller_id_number_enabled == "false" || $override_em_caller_id_number_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='override_em_caller_id_number_enabled'><input type='radio' name='override_em_caller_id_number_enabled' id='override_em_caller_id_number_enabled' onclick=\"$on_click\" value='true' ".(($override_em_caller_id_number_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='override_em_caller_id_number' id='override_em_caller_id_number' maxlength='255' placeholder=\"".$text['label-override_em_caller_id_number']."\" value=\"".$override_em_caller_id_number."\">\n";
	echo "	<br />".$text['description-override_em_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "		<td colspan='2' align='right'>";
	if ($action == 'edit') {
		echo "		<input type='hidden' name='id' value=\"$user_uuid\">";
		if (permission_exists("user_edit")) {
			echo "	<input type='hidden' name='username_old' value=\"$username\">";
		}
	}
	echo "			<br>";
	echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick=\"document.getElementById('action').value = '".$text['button-save']."'; if (check_password_strength(document.getElementById('password').value)) { submit_form(); }\">";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";



	echo "<script>\n";
	//capture enter key to submit form
		echo "	$(window).keypress(function(event){\n";
		echo "		if (event.which == 13) { submit_form(); }\n";
		echo "	});\n";
	// convert password fields to text
		echo "	function submit_form() {\n";
		echo "		$('input:password').css('visibility','hidden');\n";
		echo "		$('input:password').attr({type:'text'});\n";
		echo "		$('form#frm').submit();\n";
		echo "	}\n";
	echo "</script>\n";

	
	
//show the footer
	require_once "resources/footer.php";
?>