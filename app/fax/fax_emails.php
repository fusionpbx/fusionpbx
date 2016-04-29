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
	Portions created by the Initial Developer are Copyright (C) 2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

include "root.php";
require_once "resources/require.php";
require_once "resources/functions/object_to_array.php";
require_once "resources/functions/parse_message.php";
require_once "resources/classes/text.php";

//get accounts to monitor
$sql = "select * from v_fax ";
$sql .= "where fax_email_connection_host <> '' ";
$sql .= "and fax_email_connection_host is not null ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
unset($sql, $prep_statement);

function arr_to_map(&$arr){
	if(is_array($arr)){
		$map = Array();
		foreach($arr as &$val){
			$map[$val] = true;
		}
		return $map;
	}
	return false;
}

if (sizeof($result) != 0) {

	//load default settings
	$default_settings = load_default_settings();

	//get event socket connection parameters
	$sql = "select event_socket_ip_address, event_socket_port, event_socket_password from v_settings";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$record = $prep_statement->fetch(PDO::FETCH_NAMED);
	$event_socket['ip_address'] = $record['event_socket_ip_address'];
	$event_socket['port'] = $record['event_socket_port'];
	$event_socket['password'] = $record['event_socket_password'];
	unset($sql, $prep_statement, $record);

	$fax_send_mode_default = $_SESSION['fax']['send_mode']['text'];
	if(strlen($fax_send_mode_default) == 0){
		$fax_send_mode_default = 'direct';
	}
	$fax_cover_font_default = $_SESSION['fax']['cover_font']['text'];

	$fax_allowed_extension_default = arr_to_map($_SESSION['fax']['allowed_extension']);
	if($fax_allowed_extension_default == false){
		$tmp = Array('.pdf', '.tiff', '.tif');
		$fax_allowed_extension_default = arr_to_map($tmp);
	}

	foreach ($result as $row) {
		//get fax server and account connection details
		$fax_uuid = $row["fax_uuid"];
		$domain_uuid = $row["domain_uuid"];
		$fax_extension = $row["fax_extension"];
		$fax_email = $row["fax_email"];
		$fax_pin_number = $row["fax_pin_number"];
		$fax_caller_id_name = $row["fax_caller_id_name"];
		$fax_caller_id_number = $row["fax_caller_id_number"];
		$fax_email_connection_type = $row["fax_email_connection_type"];
		$fax_email_connection_host = $row["fax_email_connection_host"];
		$fax_email_connection_port = $row["fax_email_connection_port"];
		$fax_email_connection_security = $row["fax_email_connection_security"];
		$fax_email_connection_validate = $row["fax_email_connection_validate"];
		$fax_email_connection_username = $row["fax_email_connection_username"];
		$fax_email_connection_password = $row["fax_email_connection_password"];
		$fax_email_connection_mailbox = $row["fax_email_connection_mailbox"];
		$fax_email_outbound_subject_tag = $row["fax_email_outbound_subject_tag"];
		$fax_email_outbound_authorized_senders = $row["fax_email_outbound_authorized_senders"];
		$fax_send_greeting = $row["fax_send_greeting"];

		//load default settings, then domain settings over top
		unset($_SESSION);
		$_SESSION = $default_settings;
		load_domain_settings($domain_uuid);

		$fax_send_mode = $_SESSION['fax']['send_mode']['text'];
		if(strlen($fax_send_mode) == 0){
			$fax_send_mode = $fax_send_mode_default;
		}

		$fax_cover_font = $_SESSION['fax']['cover_font']['text'];
		if(strlen($fax_cover_font) == 0){
			$fax_cover_font = $fax_cover_font_default;
		}

		$fax_allowed_extension = arr_to_map($_SESSION['fax']['allowed_extension']);
		if($fax_allowed_extension == false){
			$fax_allowed_extension = $fax_allowed_extension_default;
		}

		//load event socket connection parameters
		$_SESSION['event_socket_ip_address'] = $event_socket['ip_address'];
		$_SESSION['event_socket_port'] = $event_socket['port'];
		$_SESSION['event_socket_password'] = $event_socket['password'];

		//get domain name, set local and session variables
		$sql = "select domain_name from v_domains where domain_uuid = '".$domain_uuid."'";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$record = $prep_statement->fetch(PDO::FETCH_NAMED);
		$domain_name = $record['domain_name'];
		$_SESSION['domain_name'] = $record['domain_name'];
		$_SESSION['domain_uuid'] = $domain_uuid;
		unset($sql, $prep_statement, $record);

		//set needed variables
		$fax_page_size = $_SESSION['fax']['page_size']['text'];
		$fax_resolution = $_SESSION['fax']['resolution']['text'];
		$fax_header = $_SESSION['fax']['cover_header']['text'];
		$fax_footer = $_SESSION['fax']['cover_footer']['text'];
		$fax_sender = $fax_caller_id_name;

		//open account connection
		$fax_email_connection = "{".$fax_email_connection_host.":".$fax_email_connection_port."/".$fax_email_connection_type;
		$fax_email_connection .= ($fax_email_connection_security != '') ? "/".$fax_email_connection_security : "/notls";
		$fax_email_connection .= "/".(($fax_email_connection_validate == 'false') ? "no" : null)."validate-cert";
		$fax_email_connection .= "}".$fax_email_connection_mailbox;
		if (!$connection = imap_open($fax_email_connection, $fax_email_connection_username, $fax_email_connection_password)) {
			print_r(imap_errors());
			continue; // try next account
		}

		//get emails
		if ($emails = imap_search($connection, "SUBJECT \"[".$fax_email_outbound_subject_tag."]\"", SE_UID)) {

			//get authorized sender(s)
			if (substr_count($fax_email_outbound_authorized_senders, ',') > 0) {
				$authorized_senders = explode(',', $fax_email_outbound_authorized_senders);
			}
			else {
				$authorized_senders[] = $fax_email_outbound_authorized_senders;
			}

			sort($emails); // oldest first
			foreach ($emails as $email_id) {
				$metadata = object_to_array(imap_fetch_overview($connection, $email_id, FT_UID));

				//format from address
				$tmp = object_to_array(imap_rfc822_parse_adrlist($metadata[0]['from'], null));
				$metadata[0]['from'] = $tmp[0]['mailbox']."@".$tmp[0]['host'];

				//check sender
				$sender_authorized = false;
				foreach ($authorized_senders as $authorized_sender) {
					if (substr_count($metadata[0]['from'], $authorized_sender) > 0) { $sender_authorized = true; }
				}

				if ($sender_authorized) {

					//add multi-lingual support
					$language = new text;
					$text = $language->get();

					//sent sender address (used in api call)
					$mailto_address_user = $metadata[0]['from'];

					//parse recipient fax number(s)
					$fax_subject = $metadata[0]['subject'];
					$tmp = explode(']', $fax_subject); //closing bracket of subject tag
					$tmp = $tmp[1];
					$tmp = str_replace(':', ',', $tmp);
					$tmp = str_replace(';', ',', $tmp);
					$tmp = str_replace('|', ',', $tmp);
					if (substr_count($tmp, ',') > 0) {
						$fax_numbers = explode(',', $tmp);
					}
					else {
						$fax_numbers[] = $tmp;
					}
					unset($fax_subject); //clear so not on cover page

					$message = parse_message($connection, $email_id, FT_UID);

					//get email body (if any) for cover page
					$fax_message = '';

					//Debug print
					print('attachments:' . "\n");
					foreach($message['attachments'] as &$attachment){
						print(' - ' . $attachment['type'] . ' - ' . $attachment['name'] . ': ' . $attachment['size'] . ' disposition: ' . $attachment['disposition'] . "\n");
					}
					print('messages:' . "\n");
					foreach($message['messages'] as &$msg){
						print(' - ' . $msg['type'] . ' - ' . $msg['size'] . "\n");
						// print($msg['data']);
						// print("\n--------------------------------------------------------\n");
					}

					foreach($message['messages'] as &$msg){
						if(($msg['size'] > 0) && ($msg['type'] == 'text/plain')) {
							$fax_message = $msg['data'];
							break;
						}
					}

					if ($fax_message != '') {
						$fax_message = strip_tags($fax_message);
						$fax_message = str_replace("\r\n\r\n", "\r\n", $fax_message);
					}

					// set fax directory (used for pdf creation - cover and/or attachments)
					$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax'.(($domain_name != '') ? '/'.$domain_name : null);

					//handle attachments (if any)
					$emailed_files = Array();
					$attachments = $message['attachments'];
					if (sizeof($attachments) > 0) {
						foreach ($attachments as &$attachment) {
							$fax_file_extension = pathinfo($attachment['name'], PATHINFO_EXTENSION);

							//block unknown files
								if ($fax_file_extension == '') {continue; }
							//block unauthorized files
								if (!$fax_allowed_extension['.' . $fax_file_extension]) { continue; } 
							//support only attachments
								if($attachment['disposition'] != 'attachment'){ continue; } 

							//store attachment in local fax temp folder
								$local_filepath = $fax_dir.'/'.$fax_extension.'/temp/'.$attachment['name'];
								file_put_contents($local_filepath, $attachment['data']);

							//load files array with attachments
								$emailed_files['error'][] = 0;
								$emailed_files['size'][] = $attachment['size'];
								$emailed_files['tmp_name'][] = $attachment['name'];
								$emailed_files['name'][] = $attachment['name'];
						}
					}

					//Debug print
					print('***********************' . "\n");
					print('fax message:' . "\n");
					print(' - length: ' . strlen($fax_message) . "\n");
					print('fax files [' . sizeof($emailed_files['name']) . ']:' . "\n");
					for($i = 0; $i < sizeof($emailed_files['name']);++$i){
						print(' - ' . $emailed_files['name'][$i] . ' - ' . $emailed_files['size'][$i] . "\n");
					}
					print('***********************' . "\n");

					//send fax
					$cwd = getcwd();
					$included = true;
					require("fax_send.php");
					if($cwd){
						chdir($cwd);
					}

					//reset variables
					unset($fax_numbers);
				}

				//delete email
				if (imap_delete($connection, $email_id, FT_UID)) {
					imap_expunge($connection);
				}
			}
			unset($authorized_senders);
		}

		//close account connection
		imap_close($connection);
	}
}

//functions used above
function load_default_settings() {
	global $db;

	$sql = "select * from v_default_settings ";
	$sql .= "where default_setting_enabled = 'true' ";
	try {
		$prep_statement = $db->prepare($sql . " order by default_setting_order asc ");
		$prep_statement->execute();
	}
	catch(PDOException $e) {
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
	}
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//load the settings into an array
	foreach ($result as $row) {
		$name = $row['default_setting_name'];
		$category = $row['default_setting_category'];
		$subcategory = $row['default_setting_subcategory'];
		if (strlen($subcategory) == 0) {
			if ($name == "array") {
				$settings[$category][] = $row['default_setting_value'];
			}
			else {
				$settings[$category][$name] = $row['default_setting_value'];
			}
		} else {
			if ($name == "array") {
				$settings[$category][$subcategory][] = $row['default_setting_value'];
			}
			else {
				$settings[$category][$subcategory][$name] = $row['default_setting_value'];
				$settings[$category][$subcategory][$name] = $row['default_setting_value'];
			}
		}
	}
	return $settings;
}

function load_domain_settings($domain_uuid) {
	global $db;

	if ($domain_uuid) {
		$sql = "select * from v_domain_settings ";
		$sql .= "where domain_uuid = '" . $domain_uuid . "' ";
		$sql .= "and domain_setting_enabled = 'true' ";
		try {
			$prep_statement = $db->prepare($sql . " order by domain_setting_order asc ");
			$prep_statement->execute();
		}
		catch(PDOException $e) {
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
		}
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		//unset the arrays that domains are overriding
		foreach ($result as $row) {
			$name = $row['domain_setting_name'];
			$category = $row['domain_setting_category'];
			$subcategory = $row['domain_setting_subcategory'];
			if ($name == "array") {
				unset($_SESSION[$category][$subcategory]);
			}
		}
		//set the settings as a session
		foreach ($result as $row) {
			$name = $row['domain_setting_name'];
			$category = $row['domain_setting_category'];
			$subcategory = $row['domain_setting_subcategory'];
			if (strlen($subcategory) == 0) {
				//$$category[$name] = $row['domain_setting_value'];
				if ($name == "array") {
					$_SESSION[$category][] = $row['domain_setting_value'];
				}
				else {
					$_SESSION[$category][$name] = $row['domain_setting_value'];
				}
			} else {
				//$$category[$subcategory][$name] = $row['domain_setting_value'];
				if ($name == "array") {
					$_SESSION[$category][$subcategory][] = $row['domain_setting_value'];
				}
				else {
					$_SESSION[$category][$subcategory][$name] = $row['domain_setting_value'];
				}
			}
		}
	}
}

?>