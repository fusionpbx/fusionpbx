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
	Portions created by the Initial Developer are Copyright (C) 2015-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/functions/object_to_array.php";
require_once "resources/functions/parse_message.php";

//get accounts to monitor
$sql = "select d.domain_name, f.* ";
$sql .= "from v_fax as f, v_domains as d ";
$sql .= "where fax_email_connection_host <> '' ";
$sql .= "and f.domain_uuid = d.domain_uuid ";
$sql .= "and fax_email_connection_host is not null ";
$sql .= "and fax_email_outbound_subject_tag is not null ";
$database = database::new();
$result = $database->select($sql, null, 'all');
unset($sql);

function arr_to_map(&$arr){
	if (!empty($arr)){
		$map = Array();
		foreach ($arr as $val){
			$map[$val] = true;
		}
		return $map;
	}
	return false;
}

if (!empty($result) && @sizeof($result) != 0) {

	foreach ($result as $row) {

		//get fax server and account connection details
		$fax_uuid = $row["fax_uuid"];
		$domain_uuid = $row["domain_uuid"];
		$domain_name = $row["domain_name"];
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
		$fax_email_outbound_authorized_senders = strtolower($row["fax_email_outbound_authorized_senders"]);
		$fax_accountcode = $row["accountcode"];
		$fax_toll_allow = $row["fax_toll_allow"];

		//send the domain name to the command line output
		if (!empty($fax_email_connection_host) && !empty($fax_email_connection_username)) {
			echo "Domain Name: ".$domain_name."\n";
		}

		//get the cover font
		$fax_cover_font_default =$settings->get('fax','cover_font');
		$fax_cover_font = $settings->get('fax','cover_font');
		if(empty($fax_cover_font)) {
			$fax_cover_font = $fax_cover_font_default;
		}
		
		//get the allowed file extensions
		$fax_allowed_extension_default = arr_to_map($settings->get('fax','allowed_extension'));
		if($fax_allowed_extension_default == false){
			$tmp = array('.pdf', '.tiff', '.tif');
			$fax_allowed_extension_default = arr_to_map($tmp);
		}
		$fax_allowed_extension = arr_to_map($settings->get('fax','allowed_extension'));
		if($fax_allowed_extension == false) {
			$fax_allowed_extension = $fax_allowed_extension_default;
		}

		//set needed variables
		$fax_page_size = $settings->get('fax','page_size');
		$fax_resolution = $settings->get('fax','resolution');
		$fax_header = $settings->get('fax','cover_header');
		$fax_footer = $settings->get('fax','cover_footer');
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

		//send more log information
		echo "   Connection: ".$fax_email_connection."\n";
		echo "   Required Subject: [".$fax_email_outbound_subject_tag."]\n";

		//get emails from imap
		if ($emails = imap_search($connection, "SUBJECT \"[".$fax_email_outbound_subject_tag."]\"", SE_UID)) {

			//get authorized sender(s)
			if (substr_count($fax_email_outbound_authorized_senders, ',') > 0) {
				$authorized_senders = explode(',', $fax_email_outbound_authorized_senders);
			}
			else {
				$authorized_senders[] = $fax_email_outbound_authorized_senders;
			}

			//sort the emails as oldest first
			sort($emails); 
			
			//send information to the console
			echo "   Number of Emails: ".count($emails)."\n";

			//process each email
			foreach ($emails as $email_id) {
				//get email meta data
				$metadata = object_to_array(imap_fetch_overview($connection, $email_id, FT_UID));
				//print_r($metadata);

				//format from address
				//$tmp = object_to_array(imap_rfc822_parse_adrlist($metadata[0]['from'], null));
				//$metadata[0]['from'] = strtolower($tmp[0]['mailbox']."@".$tmp[0]['host']);
				//$sender_email = $metadata[0]['from'];
	
				//get the sender email address	
				if (strstr($metadata[0]['from'], '<') && strstr($metadata[0]['from'], '>')) {
					$sender_email = preg_match('/^.*<(.+)>/', $metadata[0]['from'], $matches) ? strtolower($matches[1]) : '';
				}
				else {
					$sender_email = strtolower($metadata[0]['from']);
				}
 
				//check sender
				$sender_domain = explode('@', $sender_email)[1];
				$sender_authorized = in_array($sender_email, $authorized_senders) || in_array($sender_domain, $authorized_senders) ? true : false;
				if ($sender_authorized) {
					//debug info
					echo "   Sender Authorized: ".$sender_email."\n";

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
					print('   Attachments:' . "\n");
					foreach ($message['attachments'] as $attachment){
						print('   ' . $attachment['type'] . ' - ' . $attachment['name'] . ': ' . $attachment['size'] . ' disposition: ' . $attachment['disposition'] . "\n");
					}
					print('   Messages:' . "\n");
					foreach ($message['messages'] as $msg){
						print('   ' . $msg['type'] . ' - ' . $msg['size'] . "\n");
						// print($msg['data']);
						// print("\n--------------------------------------------------------\n");
					}

					foreach ($message['messages'] as $msg){
						if(($msg['size'] > 0)) {
							$fax_message = $msg['data'];
							break;
						}
					}

					if ($fax_message != '') {
						$fax_message = strip_tags($fax_message);
						$fax_message = html_entity_decode($fax_message);
						$fax_message = str_replace("\r\n\r\n", "\r\n", $fax_message);
					}

					// set fax directory (used for pdf creation - cover and/or attachments)
					$fax_dir = $settings->get('switch','storage').'/fax'.(($domain_name != '') ? '/'.$domain_name : null);

					//handle attachments (if any)
					$emailed_files = Array();
					$attachments = $message['attachments'];
					if (sizeof($attachments) > 0) {
						foreach ($attachments as $attachment) {
							//get the fax file extension
							$fax_file_extension = pathinfo($attachment['name'], PATHINFO_EXTENSION);

							//block unknown files
							if ($fax_file_extension == '') {continue; }

							//block unauthorized files
							if (!$fax_allowed_extension['.' . $fax_file_extension]) { continue; }

							//support only attachments
							//if($attachment['disposition'] != 'attachment'){ continue; } 

							//store attachment in local fax temp folder
							$uuid_filename = uuid();
							$local_filepath = $fax_dir.'/'.$fax_extension.'/temp/'.$uuid_filename."-".$attachment['name'];
							file_put_contents($local_filepath, $attachment['data']);

							//load files array with attachments
							$emailed_files['error'][] = 0;
							$emailed_files['size'][] = $attachment['size'];
							$emailed_files['tmp_name'][] = $uuid_filename."-".$attachment['name'];
							$emailed_files['name'][] = $uuid_filename."-".$attachment['name'];
						}
					}

					//Debug print
					print('   FAX Message:' . "\n");
					print('   Message Length: ' . strlen($fax_message) . "\n");
					if (isset($emailed_files['name'])) {
						print('   FAX Files [' . sizeof($emailed_files['name']) . ']:' . "\n");
						for($i = 0; $i < sizeof($emailed_files['name']);++$i){
							print('   ' . $emailed_files['name'][$i] . ' - ' . $emailed_files['size'][$i] . "\n");
						}
					}

					//send fax
					$cwd = getcwd();
					require "fax_send.php";
					if($cwd){
						chdir($cwd);
					}

					//reset variables
					unset($fax_numbers);

					//delete email
					if (imap_delete($connection, $email_id, FT_UID)) {
						imap_expunge($connection);
					}
				}
				else {
					//unauthorized sender
				}
			}
			unset($authorized_senders);
		}

		//close account connection
		imap_close($connection);

		//add a line feed
		echo "\n";
	}
}

?>
