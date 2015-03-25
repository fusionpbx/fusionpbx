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
	James Rose <james.o.rose@gmail.com>
*/

include "root.php";
require_once "resources/require.php";
require_once "resources/functions/object_to_array.php";
require_once "resources/functions/parse_attachments.php";

//get accounts to monitor
$sql = "select * from v_fax ";
$sql .= "where fax_email_connection_host <> '' ";
$sql .= "and fax_email_connection_host is not null ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
unset($sql, $prep_statement);

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

		//load default settings, then domain settings over top
		unset($_SESSION);
		$_SESSION = $default_settings;
		load_domain_settings($domain_uuid);

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
					foreach ($fax_numbers as $index => $fax_number) {
						$fax_numbers[$index] = preg_replace("~[^0-9]~", "", $fax_number);
						if ($fax_numbers[$index] == '') { unset($fax_numbers[$index]); }
					}
					unset($fax_subject); //clear so not on cover page

					//get email body (if any) for cover page
					$fax_message = imap_fetchbody($connection, $email_id, '1.1', FT_UID);
					$fax_message = strip_tags($fax_message);
					$fax_message = trim($fax_message);
					if ($fax_message == '') {
						$fax_message = imap_fetchbody($connection, $email_id, '1', FT_UID);
						$fax_message = strip_tags($fax_message);
						$fax_message = trim($fax_message);
					}
					$fax_message = str_replace("\r\n\r\n","\r\n", $fax_message);

					// set fax directory (used for pdf creation - cover and/or attachments)
					$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax'.(($domain_name != '') ? '/'.$domain_name : null);

					//handle attachments (if any)
					$attachments = parse_attachments($connection, $email_id, FT_UID);
					if (sizeof($attachments) > 0) {
						$disallowed_file_extensions = explode(',','sh,ssh,so,dll,exe,bat,vbs,zip,rar,z,tar,tbz,tgz,gz');
						foreach ($attachments as $attachment['num'] => $attachment) {
							$fax_file_extension = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
							if (in_array($fax_file_extension, $disallowed_file_extensions) || $fax_file_extension == '') { continue; } //block unauthorized files

							//store attachment in local fax temp folder
							$local_filepath = $fax_dir.'/'.$fax_extension.'/temp/'.$attachment['filename'];
							file_put_contents($local_filepath, $attachment['attachment']);

							//load files array with attachments
							$emailed_files['error'][$attachment['num']] = 0;
							$emailed_files['size'][$attachment['num']] = $attachment['size'];
							$emailed_files['tmp_name'][$attachment['num']] = $attachment['filename'];
							$emailed_files['name'][$attachment['num']] = $attachment['filename'];
						}
					}

					//send fax
					$included = true;
					require("fax_send.php");

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