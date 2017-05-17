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
 KonradSC <konrd@yahoo.com>
 */

//includes
	require_once "root.php";
	require_once "resources/require.php";

//include the device class
	require_once "app/wizard/resources/classes/wizard.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('wizard_import') && $_GET['import_file'] != '') {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//built in str_getcsv requires PHP 5.3 or higher, this function can be used to reproduct the functionality but requirs PHP 5.1.0 or higher
	if(!function_exists('str_getcsv')) {
		function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
			$fp = fopen("php://memory", 'r+');
			fputs($fp, $input);
			rewind($fp);
			$data = fgetcsv($fp, null, $delimiter, $enclosure); // $escape only got added in 5.3.0
			fclose($fp);
			return $data;
		}
	}

//set the max php execution time
	ini_set(max_execution_time,7200);

	$file = check_str($_GET["import_file"]);
	
//get the contents of the csv file	
	$handle = @fopen($_SESSION['server']['temp']['dir']."/". $file, "r");
	if ($handle) {
		//read the csv file into an array
		$csv = array();
		$header = null;
		$x = 0;
		echo "file is open";
		while(($row = fgetcsv($handle)) !== false){
		    if($header === null){
		        $header = $row;
		        continue;
		    }
		
		    $newRow = array();
		    for($i = 0; $i<count($row); $i++){
		        $newRow[$header[$i]] = $row[$i];
		        //$newRow[Line] = $x + 1;
		    }
		
		    $csv[] = $newRow;
		    $x++;
		}
	}
					
	if (!feof($handle)) {
		echo "Error: Unable to open the file.\n";
	}
	fclose($handle);

	//cycle through the rows
	foreach ($csv as $key => $csv_row) {
		//set the variables
			$salt = uuid();
			$password = $csv_row['user_password'];
			$user_uuid = uuid();
			$accountcode = $_SESSION['domain_name'];
			$auth_id = generate_password();
			$call_screen_enabled = "false";
			$call_timeout = "30";
			$contact_email_uuid = uuid();
			$contact_name_given = $csv_row['first_name'];
			$contact_name_family = $csv_row['last_name'];
			$contact_nickname = $csv_row['extension'];
			$contact_type = "user";
			$contact_user_uuid = uuid();
			$contact_uuid = uuid();
			$context = $_SESSION['domain_name'];
			$device_description = $contact_name_given . " " .$contact_name_family;
			$device_enabled = "true";
			$device_label = $csv_row['extension'];
			$device_line_enabled = "true";
			$device_line_uuid = uuid();
			$device_mac_address = $csv_row['mac_address'];
			$device_profile_name = $csv_row['device_profile'];
			$device_template = $csv_row['device_template'];
			$device_user_uuid = $user_uuid;
			$device_uuid = uuid();
			$directory_exten_visible = "true";
			$directory_full_name = $contact_name_given . " " .$contact_name_family;
			$directory_visible = 'true';
			$directory_exten_visible = 'true';
			$display_name = $csv_row['extension'];
			$domain_name = $_SESSION['domain_name'];
			$domain_uuid = $_SESSION['domain_uuid'];
			$effective_caller_id_name = $contact_name_given . " " .$contact_name_family;
			$effective_caller_id_number = $csv_row['extension']; 
			$email = $csv_row['email'];
			$email_label = "Work";
			$emergency_caller_id_name = $contact_name_given . " " .$contact_name_family;
			$emergency_caller_id_number = $csv_row['em_caller_id_num'];
			$extension = $csv_row['extension'];
			$extension_description = $contact_name_given . " " .$contact_name_family;
			$extension_enabled = "true";
			$extension_password = generate_password();
			$extension_user_uuid = uuid();
			$extension_uuid = uuid();
			$group_user_uuid = uuid();
			$limit_max = "5";
			$line_number = "1";
			$outbound_caller_id_name = $contact_name_given . " " .$contact_name_family;
			$outbound_caller_id_number = $csv_row['caller_id_num'];
			$password_encrypted = md5($salt.$password);
			$register_expires = $_SESSION['provision']['line_register_expires']['numeric'];
			$server_address = $_SESSION['domain_name'];
			$sip_port = $_SESSION['provision']['line_sip_port']['numeric'];
			$sip_transport = $_SESSION['provision']['line_sip_transport']['text'];
			$user_context = $_SESSION['domain_name'];
			$user_enabled = "true";
			$user_id = $csv_row['extension'];
			$user_password = $csv_row['user_password'];
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
			$username = $csv_row['username'];
			$valid_mac =  wizard::normalize_mac($mac_address);
			$voicemail_description = $contact_name_given . " " .$contact_name_family;
			$voicemail_enabled = "true";
			$voicemail_file = "attach";
			$voicemail_id = $csv_row['extension'];
			$voicemail_local_after_email = "true";
			$voicemail_mail_to = $csv_row['email'];
			$voicemail_password = $csv_row['vm_password'];
			$voicemail_uuid = uuid();
			$wizard_template_name = $csv_row['wizard_template'];
	
		//get template values
			$sql = "select * from v_wizard_templates ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and wizard_template_name = '$wizard_template_name' ";
			$database = new database;
			$database->select($sql);
			$result = $database->result;
			foreach ($result as &$row) {
				$wizard_template_name = $row["wizard_template_name"];
				if ($emergency_caller_id_number == '' ) {
					$emergency_caller_id_number = $row["emergency_caller_id_number"];
				} 
				if ($outbound_caller_id_number == '') {
					$outbound_caller_id_number = $row["outbound_caller_id_number"];
				}
				$call_group = $row["call_group"];
				$toll_allow = $row["toll_allow"];
				$hold_music = $row["hold_music"];
				$user_record = $row["user_record"];
				$call_timeout = $row["call_timeout"];
				$forward_user_not_registered_destination = $row["forward_user_not_registered_destination"];
				$forward_user_not_registered_enabled = $row["forward_user_not_registered_enabled"];
				$time_zone = $row["time_zone"];
				$group_uuid = $row["wizard_group_uuid"];
			}

	//lookup group_name
			$sql = "select group_name from v_groups ";
			$sql .= "where group_uuid = '".$group_uuid."' ";
			$database = new database;
			$database->select($sql);
			$result = $database->result;			
			foreach ($result as &$row) {
				$group_name = $row["group_name"];
			} 
			unset($database,$sql,$result);
			
		//lookup device_profile_uuid
			$sql = "select device_profile_uuid from v_device_profiles ";
			$sql .= "where device_profile_name = '".$device_profile_name."' and domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$database = new database;
			$database->select($sql);
			$result = $database->result;
			foreach ($result as &$row) {
				$device_profile_uuid = $row["device_profile_uuid"];
			}
			unset($database,$sql,$result);
		
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
				$array["extensions"][$i]["user_record"] = $user_record;
				$array["extensions"][$i]["hold_music"] = $hold_music;
				$array["extensions"][$i]["forward_user_not_registered_destination"] = $forward_user_not_registered_destination;
				$array["extensions"][$i]["forward_user_not_registered_enabled"] = $forward_user_not_registered_enabled;
				$array["extensions"][$i]["enabled"] = $extension_enabled;
				$array["extensions"][$i]["toll_allow"] = $toll_allow;
				
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
			unset($database,$array,$i);
	
		//end of loop for one line of csv
	}


//synchronize the xml config
	save_dialplan_xml();
//clear the cache
	$cache = new cache;


//show the header
	require_once "resources/header.php";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-wizard_import_success']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='wizard_import.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "	".$text['message-input_sucess']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

//show the valid input results
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
	echo "<tr>\n";
	//$first_row = array();
	$first_row = $csv[0];
	foreach ($first_row as $key =>$row) {
		echo "	<th>".$key."</th>\n";
	}
	echo "</tr>\n";
	
	foreach ($csv as $key => $csv_row) {
		echo "<tr>\n";
		
		foreach ($csv_row as $csv_item){
			echo "	<td style='text-align:left' class='vncell' valign='top' align='right'>\n";
			echo 		$csv_item ."&nbsp;\n";
			echo "	</td>\n";
		}
		//echo "	</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	unset($csv);
	require_once "resources/footer.php";

?>
