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
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
if (if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}


//add multi-lingual support
$language = new text;
$text = $language->get();

if(!empty($_FILES['fileToUpload']['name'])) {
 	$file_data = fopen($_FILES['fileToUpload']['tmp_name'], 'r');

 	$column = fgetcsv($file_data);

 	while($row = fgetcsv($file_data)) {
	  	$row_data[] = array(
	   		'extension'			=>		$row[0],
			'line'				=>		$row[1],
			'mac'				=>		$row[2],
	  	);
 	}


 	foreach ($row_data as $akey => $mainArray) {
 		$extension_number 	=	$mainArray['extension'];
 		$line_number		=	$mainArray['line'];
 		$mac_address		=	$mainArray['mac'];
		
		/************************************************************
			v_extension add entry
		**************************************************************/
		$extension_uuid 	= uuid();
		$voicemail_uuid 	= uuid();
		$hotel_room_uuid 	= uuid();
		$device_line_uuid 	= uuid();
		$str = 'abc$%123456xyz';
		$password  			= str_shuffle($str);
		$domain_uuid 		= $_SESSION['domain_uuid'];
		$domain_name		= $_SESSION['domain_name'];
		$vpassword = '1234';


		$sql = "SELECT extension FROM v_extensions ";
		$sql .= "WHERE extension = '".check_str($extension_number)."' ";
		$sql .= "AND domain_uuid = '".check_str($domain_uuid)."'";

		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$extension_result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

		// die(print_r($result));
		
		if ($extension_result == null) {
			// $extension_column = array('extension_uuid', 'domain_uuid', 'extension', 'number_alias', 'password', 'accountcode');
			// $extension_column = array('extension_uuid', 
			// 	'domain_uuid', 
			// 	'extension', 
			// 	'number_alias', 
			// 	'password', 
			// 	'accountcode', 
			// 	'effective_caller_id_name', 
			// 	'effective_caller_id_number', 
			// 	'outbound_caller_id_name', 
			// 	'outbound_caller_id_number', 
			// 	'emergency_caller_id_name', 
			// 	'emergency_caller_id_number', 
			// 	'directory_full_name', 
			// 	'directory_visible', 
			// 	'directory_exten_visible', 
			// 	'limit_max', 
			// 	'limit_destination', 
			// 	'missed_call_app', 
			// 	'missed_call_data', 
			// 	'user_context', 
			// 	'toll_allow', 
			// 	'call_timeout', 
			// 	'call_group', 
			// 	'call_screen_enabled', 
			// 	'user_record', 
			// 	'hold_music', 
			// 	'auth_acl', 
			// 	'cidr', 
			// 	'sip_force_contact', 
			// 	'nibble_account', 
			// 	'sip_force_expires', 
			// 	'mwi_account', 
			// 	'sip_bypass_media', 
			// 	'unique_id', 
			// 	'dial_string', 
			// 	'dial_user', 
			// 	'dial_domain', 
			// 	'do_not_disturb', 
			// 	'forward_all_destination', 
			// 	'forward_all_enabled', 
			// 	'forward_busy_destination', 
			// 	'forward_busy_enabled', 
			// 	'forward_no_answer_destination', 
			// 	'forward_no_answer_enabled', 
			// 	'forward_user_not_registered_destination', 
			// 	'forward_user_not_registered_enabled',
			// 	'follow_me_uuid', 
			// 	'enabled', 
			// 	'description', 
			// 	'forward_caller_id_uuid', 
			// 	'absolute_codec_string',
			// 	'force_ping'
				
			// );

			$sql = "INSERT INTO v_extensions "; 
			$sql .= "(";
			// $sql .= implode(", ", $extension_column);
			$sql .= "extension_uuid, domain_uuid, extension, number_alias, password, accountcode, effective_caller_id_name, effective_caller_id_number, outbound_caller_id_name, outbound_caller_id_number, emergency_caller_id_name, emergency_caller_id_number, directory_full_name, directory_visible, directory_exten_visible, limit_max, limit_destination, missed_call_app, missed_call_data, user_context, toll_allow, call_timeout, call_group, call_screen_enabled, user_record, hold_music, auth_acl, cidr, sip_force_contact, nibble_account, sip_force_expires, mwi_account, sip_bypass_media, unique_id, dial_string, dial_user, dial_domain, do_not_disturb, forward_all_destination, forward_all_enabled, forward_busy_destination, forward_busy_enabled, forward_no_answer_destination, forward_no_answer_enabled, forward_user_not_registered_destination, forward_user_not_registered_enabled, follow_me_uuid, enabled, description, forward_caller_id_uuid, absolute_codec_string, force_ping";

			$sql .= ") VALUES ";  
			$sql .= "(";
			$sql .= "'$extension_uuid', '$domain_uuid', '$extension_number', '$extension_number', '$password', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'true', 'true', 5, 'error/user_busy', NULL, NULL, '$domain_name', NULL, 30, NULL, 'false', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,  NULL, NULL, 'true', NULL, NULL, NULL, NULL";
			$sql .= ")";


			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();									
			}
			unset($prep_statement, $sql);	


			/************************************************************
				v_voicemail add entry
			**************************************************************/
				$extension_column = array('domain_uuid', 'voicemail_uuid', 'voicemail_id', 'voicemail_password');

				$sql = "INSERT INTO v_voicemails "; 
				$sql .= "(";
				$sql .= implode(", ", $extension_column);
				$sql .= ") VALUES ";  
				$sql .= "(";
				$sql .= "'$domain_uuid','$voicemail_uuid','$extension_number','$vpassword'";
				$sql .= ")";

				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();									
				}
				unset($prep_statement, $sql);

				/************************************************************
					v_hotel_rooms add entry
				**************************************************************/
				$extension_column = array('hotel_room_uuid', 'domain_uuid', 'extension_number', 'room_number','guest_room');

					$sql = "INSERT INTO v_hotel_rooms "; 
					$sql .= "(";
					$sql .= implode(", ", $extension_column);
					$sql .= ") VALUES ";  
					$sql .= "(";
					$sql .= "'$hotel_room_uuid','$domain_uuid','$extension_number','$extension_number','yes'";
					$sql .= ")";
					
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();									
				}
				unset($prep_statement, $sql);

				/************************************************************
					v_hotel_rooms_dashboard add entry
				**************************************************************/
				$hotelRoom_dashboard_uuid = uuid();
				$sql = "INSERT INTO v_hotel_room_dashboard 
						(hotel_room_dashboard_uuid, domain_uuid, room_number, dnd, paid_calls) 
						VALUES 
						('".$hotelRoom_dashboard_uuid."', '".$domain_uuid."', '".$extension_number."', 'No', 'No')";

				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();									
				}
				unset($prep_statement, $sql);

				/************************************************************
					v_device_lines add entry
				**************************************************************/
				$sql = "SELECT device_uuid FROM v_devices ";
				$sql .= "WHERE device_mac_address = '".check_str($mac_address)."' ";
				// $sql .= "AND domain_uuid = '".check_str($domain_uuid)."'";

				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				
				if ($result != null) {
					$device_uuid_id = $result[0]['device_uuid'];
			

					$sql = "SELECT * FROM v_device_lines ";
					$sql .= "WHERE device_uuid = '".check_str($device_uuid_id)."' && user_id = '".check_str($extension_number)."' && auth_id = '".check_str($extension_number)."'";
					
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$device_line_result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					
					// die($device_line_result);
					if ($device_line_result == null) {
						
						$extension_column = array('domain_uuid', 'device_line_uuid', 'device_uuid', 'line_number','server_address','outbound_proxy_primary','outbound_proxy_secondary','display_name','user_id','auth_id','password','sip_port','sip_transport','register_expires','enabled');

							$sql = "INSERT INTO v_device_lines "; 
							$sql .= "(";
							$sql .= implode(", ", $extension_column);
							$sql .= ") VALUES ";  
							$sql .= "(";
							$sql .= "'$domain_uuid','$device_line_uuid','$device_uuid_id','$line_number','".$_SESSION['domain_name']."',NULL,NULL,'$extension_number','$extension_number','$extension_number','$password','5060','tcp','80','true'";
							$sql .= ")";

						$prep_statement = $db->prepare($sql);
						if ($prep_statement) {
							$prep_statement->execute();									
						}
						unset($prep_statement, $sql);	
					}
				}
			
		}
		

		
	



	}
	
	header("location:extensions.php");
}

?>
