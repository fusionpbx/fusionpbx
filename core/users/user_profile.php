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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the user uuid
	$user_uuid = $_SESSION['user_uuid'];

//set the action
	$action = 'edit';

//retrieve password requirements
	$required['length'] = $settings->get('users', 'password_length', 12);
	$required['number'] = $settings->get('users', 'password_number', false);
	$required['lowercase'] = $settings->get('users', 'password_lowercase', false);
	$required['uppercase'] = $settings->get('users', 'password_uppercase', false);
	$required['special'] = $settings->get('users', 'password_special', false);

//process the http post
	if (!empty($_POST)) {

		//get the HTTP values and set as variables
			$password = $_POST["password"];
			$password_confirm = $_POST["password_confirm"];
			$contact_name_given = $_POST['contact_name_given'];
			$contact_name_family = $_POST['contact_name_family'];
			$contact_email_uuid = $_POST['contact_email_uuid'];
			$user_email = $_POST["user_email"];
			$contact_phone_uuid = $_POST['contact_phone_uuid'];
			$phone_number = $_POST['phone_number'];
			$contact_address_uuid = $_POST['contact_address_uuid'];
			$address_locality = $_POST['address_locality'];
			$address_region = $_POST['address_region'];
			$address_country = $_POST['address_country'];
			$user_status = $_POST["user_status"] ?? '';
			$user_language = $_POST["user_language"];
			$user_time_zone = $_POST["user_time_zone"];
			$contact_attachment_uuid = $_POST['contact_attachment_uuid'] ?? '';
			$contact_attachment = $_FILES['contact_attachment'];

		//get the totp secret
			if (!empty($_SESSION['authentication']['methods']) && in_array('totp', $_SESSION['authentication']['methods'])) {
				$user_totp_secret = strtoupper($_POST["user_totp_secret"]);
			}

		//remove any phone number formatting
			$phone_number = preg_replace('{(?!^\+)[\D]}', '', $phone_number);

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: users.php');
				exit;
			}

		//validate the user status
			switch ($user_status) {
				case "Available" :
					break;
				case "Available (On Demand)" :
					break;
				case "On Break" :
					break;
				case "Do Not Disturb" :
					break;
				case "Logged Out" :
					break;
				default :
					$user_status = '';
			}

		//check required values
			//require the passwords to match
			if (!empty($password) && $password != $password_confirm) {
				message::add($text['message-password_mismatch'], 'negative', 7500);
			}

			//require passwords not allowed to be empty
			if ($action == 'add') {
				if (empty($password)) {
					message::add($text['message-password_blank'], 'negative', 7500);
				}
			}

			//require a value a valid email address format
			if (!valid_email($user_email)) {
				$invalid[] = $text['label-email'];
			}

			//require passwords with the defined required attributes: length, number, lower case, upper case, and special characters
			if (!empty($password)) {
				if (!empty($required['length']) && is_numeric($required['length']) && $required['length'] != 0) {
					if (strlen($password) < $required['length']) {
						$invalid[] = $text['label-characters'];
					}
				}
				if ($required['number']) {
					if (!preg_match('/(?=.*[\d])/', $password)) {
						$invalid[] = $text['label-numbers'];
					}
				}
				if ($required['lowercase']) {
					if (!preg_match('/(?=.*[a-z])/', $password)) {
						$invalid[] = $text['label-lowercase_letters'];
					}
				}
				if ($required['uppercase']) {
					if (!preg_match('/(?=.*[A-Z])/', $password)) {
						$invalid[] = $text['label-uppercase_letters'];
					}
				}
				if ($required['special']) {
					if (!preg_match('/(?=.*[\W])/', $password)) {
						$invalid[] = $text['label-special_characters'];
					}
				}
			}

		//set the contact_uuid
			$contact_uuid = $_SESSION['user']['contact_uuid'] ?? uuid();

		//set initial array indexes
			$i = $n = $x = $y = $c = 0;

		//save contact
			$array['contacts'][$c]['contact_uuid'] = $contact_uuid;
			$array['contacts'][$c]['domain_uuid'] = $domain_uuid;
			$array['contacts'][$c]['contact_type'] = 'user';
			$array['contacts'][$c]['contact_name_given'] = $contact_name_given ?? null;
			$array['contacts'][$c]['contact_name_family'] = $contact_name_family ?? null;
			$array['contacts'][$c]['contact_nickname'] = $_SESSION['username'];
			$c++;

		//save email
			$array['contact_emails'][$n]['contact_email_uuid'] = is_uuid($contact_email_uuid) ? $contact_email_uuid : uuid();
			$array['contact_emails'][$n]['contact_uuid'] = $contact_uuid;
			$array['contact_emails'][$n]['domain_uuid'] = $domain_uuid;
			$array['contact_emails'][$n]['email_address'] = $user_email;
			$array['contact_emails'][$n]['email_primary'] = 'true';
			$n++;

		//save phone
			if (!empty($phone_number)) {
				$array['contact_phones'][$y]['contact_phone_uuid'] = is_uuid($contact_phone_uuid) ? $contact_phone_uuid : uuid();
				$array['contact_phones'][$y]['contact_uuid'] = $contact_uuid;
				$array['contact_phones'][$y]['domain_uuid'] = $domain_uuid;
				$array['contact_phones'][$y]['phone_number'] = $phone_number;
				$array['contact_phones'][$y]['phone_primary'] = 'true';
				$y++;
			}

		//save address
			if (!empty($address_locality) || !empty($address_region) || !empty($address_country)) {
				$array['contact_addresses'][$y]['contact_address_uuid'] = is_uuid($contact_address_uuid) ? $contact_address_uuid : uuid();
				$array['contact_addresses'][$y]['contact_uuid'] = $contact_uuid;
				$array['contact_addresses'][$y]['domain_uuid'] = $domain_uuid;
				$array['contact_addresses'][$y]['address_locality'] = $address_locality ?? null;
				$array['contact_addresses'][$y]['address_region'] = $address_region ?? null;
				$array['contact_addresses'][$y]['address_country'] = $address_country ?? null;
				$array['contact_addresses'][$y]['address_primary'] = 'true';
				$y++;
			}

		//delete current profile photo (contact attachment)
			if (!empty($contact_attachment_uuid) && is_uuid($contact_attachment_uuid)) {
				$p = permissions::new();
				$p->add('contact_attachment_delete', 'temp');

				$array_delete['contact_attachments'][0]['contact_uuid'] = $contact_uuid;
				$array_delete['contact_attachments'][0]['domain_uuid'] = $domain_uuid;
				$array_delete['contact_attachments'][0]['contact_attachment_uuid'] = $contact_attachment_uuid;
				$database->delete($array_delete);
				unset($array_delete);

				$p->delete('contact_attachment_delete', 'temp');
			}

		//handle new profile photo
			else if (is_array($contact_attachment) && sizeof($contact_attachment) != 0 && $contact_attachment['error'] === 0) {
				$contact_attachment_extension = strtolower(pathinfo($contact_attachment['name'], PATHINFO_EXTENSION));
				if (in_array($contact_attachment_extension, ['jpg','jpeg','gif','png','webp'])) {

					//unflag others as primary
					$sql = "update v_contact_attachments set attachment_primary = false ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and contact_uuid = :contact_uuid ";
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['contact_uuid'] = $contact_uuid;
					$database->execute($sql, $parameters);
					unset($sql, $parameters);

					//get the attachment content
					$contact_attachment_content = file_get_contents($contact_attachment['tmp_name']);

					//create the image object from the content string
					$image = imagecreatefromstring($contact_attachment_content);

					//start output buffering to capture the image data
					ob_start();

					//output the image without the EXIF data
					switch ($contact_attachment_extension) {
						case 'png':
							imagealphablending($image, false);
							imagesavealpha($image, true);
							imagepng($image);
							break;
						case 'jpg':
						case 'jpeg':
							imagejpeg($image);
							break;
						case 'gif':
							imagesavealpha($image, true);
							imagegif($image);
							break;
						case 'webp':
							imagewebp($image);
							break;
					}

					//get the image from the buffer
					$contact_attachment_content = ob_get_contents();

					//end the buffering
					ob_end_clean();

					//free up the memory
					imagedestroy($image);

					//prepare the array
					$array['contact_attachments'][0]['contact_attachment_uuid'] = is_uuid($contact_attachment_uuid) ? $contact_attachment_uuid : uuid();
					$array['contact_attachments'][0]['domain_uuid'] = $domain_uuid;
					$array['contact_attachments'][0]['contact_uuid'] = $contact_uuid;
					$array['contact_attachments'][0]['attachment_primary'] = 'true';
					$array['contact_attachments'][0]['attachment_filename'] = $contact_attachment['name'];
					$array['contact_attachments'][0]['attachment_content'] = base64_encode($contact_attachment_content);
					if ($action == 'add') {
						$array['contact_attachments'][0]['attachment_uploaded_date'] = 'now()';
						$array['contact_attachments'][0]['attachment_uploaded_user_uuid'] = $_SESSION['user_uuid'];
					}
				}
			}
			else {
				unset($contact_attachment);
			}

		//return if error
			if (message::count() != 0 || !empty($invalid)) {
				if ($invalid) { message::add($text['message-required'].implode(', ', $invalid), 'negative', 7500); }
				persistent_form_values('store', $_POST);
				header("Location: user_profile.php");
				exit;
			}
			else {
				persistent_form_values('clear');
			}

		//check to see if user language is set
			$sql = "select user_setting_uuid, user_setting_value from v_user_settings ";
			$sql .= "where user_setting_category = 'domain' ";
			$sql .= "and user_setting_subcategory = 'language' ";
			$sql .= "and user_uuid = :user_uuid ";
			$parameters['user_uuid'] = $user_uuid;
			$row = $database->select($sql, $parameters, 'row');
			if (!empty($user_language) && (empty($row) || (!empty($row['user_setting_uuid']) && !is_uuid($row['user_setting_uuid'])))) {
				//add user setting to array for insert
					$array['user_settings'][$i]['user_setting_uuid'] = uuid();
					$array['user_settings'][$i]['user_uuid'] = $user_uuid;
					$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['user_settings'][$i]['user_setting_category'] = 'domain';
					$array['user_settings'][$i]['user_setting_subcategory'] = 'language';
					$array['user_settings'][$i]['user_setting_name'] = 'code';
					$array['user_settings'][$i]['user_setting_value'] = $user_language;
					$array['user_settings'][$i]['user_setting_enabled'] = 'true';
					$i++;
			}
			else {
				if (empty($row['user_setting_value']) || empty($user_language)) {
					$array_delete['user_settings'][0]['user_setting_category'] = 'domain';
					$array_delete['user_settings'][0]['user_setting_subcategory'] = 'language';
					$array_delete['user_settings'][0]['user_uuid'] = $user_uuid;

					$p = permissions::new();
					$p->add('user_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('user_setting_delete', 'temp');
				}
				if (!empty($user_language)) {
					//add user setting to array for update
					$array['user_settings'][$i]['user_setting_uuid'] = $row['user_setting_uuid'];
					$array['user_settings'][$i]['user_uuid'] = $user_uuid;
					$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['user_settings'][$i]['user_setting_category'] = 'domain';
					$array['user_settings'][$i]['user_setting_subcategory'] = 'language';
					$array['user_settings'][$i]['user_setting_name'] = 'code';
					$array['user_settings'][$i]['user_setting_value'] = $user_language;
					$array['user_settings'][$i]['user_setting_enabled'] = 'true';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//check to see if user time zone is set
			$sql = "select user_setting_uuid, user_setting_value from v_user_settings ";
			$sql .= "where user_setting_category = 'domain' ";
			$sql .= "and user_setting_subcategory = 'time_zone' ";
			$sql .= "and user_uuid = :user_uuid ";
			$parameters['user_uuid'] = $user_uuid;
			$row = $database->select($sql, $parameters, 'row');
			if (!empty($user_time_zone) && (empty($row) || (!empty($row['user_setting_uuid']) && !is_uuid($row['user_setting_uuid'])))) {
				//add user setting to array for insert
				$array['user_settings'][$i]['user_setting_uuid'] = uuid();
				$array['user_settings'][$i]['user_uuid'] = $user_uuid;
				$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
				$array['user_settings'][$i]['user_setting_category'] = 'domain';
				$array['user_settings'][$i]['user_setting_subcategory'] = 'time_zone';
				$array['user_settings'][$i]['user_setting_name'] = 'name';
				$array['user_settings'][$i]['user_setting_value'] = $user_time_zone;
				$array['user_settings'][$i]['user_setting_enabled'] = 'true';
				$i++;
			}
			else {
				if (empty($row['user_setting_value']) || empty($user_time_zone)) {
					$array_delete['user_settings'][0]['user_setting_category'] = 'domain';
					$array_delete['user_settings'][0]['user_setting_subcategory'] = 'time_zone';
					$array_delete['user_settings'][0]['user_uuid'] = $user_uuid;

					$p = permissions::new();
					$p->add('user_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('user_setting_delete', 'temp');
				}
				if (!empty($user_time_zone)) {
					//add user setting to array for update
					$array['user_settings'][$i]['user_setting_uuid'] = $row['user_setting_uuid'];
					$array['user_settings'][$i]['user_uuid'] = $user_uuid;
					$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['user_settings'][$i]['user_setting_category'] = 'domain';
					$array['user_settings'][$i]['user_setting_subcategory'] = 'time_zone';
					$array['user_settings'][$i]['user_setting_name'] = 'name';
					$array['user_settings'][$i]['user_setting_value'] = $user_time_zone;
					$array['user_settings'][$i]['user_setting_enabled'] = 'true';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//set the password hash cost
			$options = array('cost' => 10);

		//add user setting to array for update
			$array['users'][$x]['user_uuid'] = $user_uuid;

			if (!empty($password) && $password == $password_confirm) {
				//remove the session id files
				$sql = "select session_id from v_user_logs ";
				$sql .= "where user_uuid = :user_uuid ";
				$sql .= "and timestamp > NOW() - INTERVAL '4 hours' ";
				$parameters['user_uuid'] = $user_uuid;
				$user_logs = $database->select($sql, $parameters, 'all');
				foreach ($user_logs as $row) {
					if (preg_match('/^[a-zA-Z0-9,-]+$/', $row['session_id']) && file_exists(session_save_path() . "/sess_" . $row['session_id'])) {
						unlink(session_save_path() . "/sess_" . $row['session_id']);
					}
				}
				unset($sql, $parameters);

				//create a one way hash for the user password
				$array['users'][$x]['password'] = password_hash($password, PASSWORD_DEFAULT, $options);
				$array['users'][$x]['salt'] = null;
			}
			$array['users'][$x]['user_email'] = $user_email;
			$array['users'][$x]['user_status'] = $user_status;
			$array['users'][$x]['contact_uuid'] = $contact_uuid;
			if (!empty($_SESSION['authentication']['methods']) && in_array('totp', $_SESSION['authentication']['methods'])) {
				$array['users'][$x]['user_totp_secret'] = $user_totp_secret;
			}
			if ($action == 'add') {
				$array['users'][$x]['add_user'] = $_SESSION["user"]["username"];
				$array['users'][$x]['add_date'] = date("Y-m-d H:i:s.uO");
			}
			$x++;


		//add the user_edit permission
			$p = permissions::new();

			$p->add("user_edit", "temp");
			$p->add("user_setting_add", "temp");
			$p->add("user_setting_edit", "temp");
			$p->add('contact_add', 'temp');
			$p->add('contact_edit', 'temp');
			$p->add('contact_email_add', 'temp');
			$p->add('contact_email_edit', 'temp');
			$p->add('contact_phone_add', 'temp');
			$p->add('contact_phone_edit', 'temp');
			$p->add('contact_address_add', 'temp');
			$p->add('contact_address_edit', 'temp');
			$p->add("contact_attachment_add", "temp");
			$p->add("contact_attachment_edit", "temp");
			$p->add("contact_attachment_delete", "temp");

		//save the data
			$database->save($array);
			//$message = $database->message;

		//remove the temporary permission
			$p->delete("user_edit", "temp");
			$p->delete("user_setting_add", "temp");
			$p->delete("user_setting_edit", "temp");
			$p->delete("contact_add", "temp");
			$p->delete("contact_edit", "temp");
			$p->delete("contact_email_add", "temp");
			$p->delete("contact_email_edit", "temp");
			$p->delete("contact_phone_add", "temp");
			$p->delete("contact_phone_edit", "temp");
			$p->delete("contact_address_add", "temp");
			$p->delete("contact_address_edit", "temp");
			$p->delete("contact_attachment_add", "temp");
			$p->delete("contact_attachment_edit", "temp");
			$p->delete("contact_attachment_delete", "temp");

		//clear the menu
			unset($_SESSION["menu"]);

		//get settings based on the user
			$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid'], 'user_uuid' => $_SESSION['user_uuid']]);
			settings::clear_cache();

		//if call center installed
			if ($action == 'edit' && file_exists(dirname(__DIR__, 2)."/app/call_centers/app_config.php")) {
				//get the call center agent uuid
					$sql = "select call_center_agent_uuid from v_call_center_agents ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and user_uuid = :user_uuid ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$parameters['user_uuid'] = $user_uuid;
					$call_center_agent_uuid = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);

				//update the user_status
					if (isset($call_center_agent_uuid) && is_uuid($call_center_agent_uuid) && !empty($user_status)) {
						$esl = event_socket::create();
						$switch_cmd = "callcenter_config agent set status ".$call_center_agent_uuid." '".$user_status."'";
						$switch_result = event_socket::api($switch_cmd);
					}

				//update the user state
					if (isset($call_center_agent_uuid) && is_uuid($call_center_agent_uuid)) {
						$esl = event_socket::create();
						$cmd = "callcenter_config agent set state ".$call_center_agent_uuid." Waiting";
						$response = event_socket::api($cmd);
					}
			}

		//response message
			message::add($text['message-update'],'positive');

		//redirect
			header('Location: user_profile.php');
			exit;

	}

//populate form
	if (persistent_form_values('exists')) {
		//populate the form with values from session variable
			persistent_form_values('load');
		//clear, set $unsaved flag
			persistent_form_values('clear');
	}
	else {

		//populate the form with values from db
			$sql = "select ";
			$sql .= "u.domain_uuid, ";
			$sql .= "u.user_uuid, ";
			$sql .= "u.username, ";
			$sql .= "u.user_email, ";
			$sql .= "u.user_totp_secret, ";
			$sql .= "u.user_type, ";
			$sql .= "u.contact_uuid, ";
			$sql .= "u.user_enabled, ";
			$sql .= "u.user_status, ";
			$sql .= "c.contact_name_given, ";
			$sql .= "c.contact_name_family, ";
			$sql .= "ce.contact_email_uuid, ";
			$sql .= "cp.contact_phone_uuid, ";
			$sql .= "cp.phone_number, ";
			$sql .= "ca1.contact_address_uuid, ";
			$sql .= "ca1.address_locality, ";
			$sql .= "ca1.address_region, ";
			$sql .= "ca1.address_country, ";
			$sql .= "ca2.contact_attachment_uuid, ";
			$sql .= "ca2.attachment_filename, ";
			$sql .= "ca2.attachment_content ";
			$sql .= "from ";
			$sql .= "v_users as u ";
			$sql .= "left join v_contacts as c on u.contact_uuid = c.contact_uuid ";
			$sql .= "left join v_contact_emails as ce on u.contact_uuid = ce.contact_uuid and ce.email_primary = true ";
			$sql .= "left join v_contact_phones as cp on u.contact_uuid = cp.contact_uuid and cp.phone_primary = true ";
			$sql .= "left join v_contact_addresses as ca1 on u.contact_uuid = ca1.contact_uuid and ca1.address_primary = true ";
			$sql .= "left join v_contact_attachments as ca2 on u.contact_uuid = ca2.contact_uuid and ca2.attachment_primary = true ";
			$sql .= "where u.user_uuid = :user_uuid ";
			if (!permission_exists('user_all')) {
				$sql .= "and u.domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
			}
			$parameters['user_uuid'] = $user_uuid;
			// echo $sql; view_array($parameters);
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && sizeof($row) > 0) {
				$domain_uuid = $row["domain_uuid"];
				$user_uuid = $row["user_uuid"];
				$username = $row["username"];
				$user_email = $row["user_email"];
				$user_totp_secret = $row["user_totp_secret"];
				$user_type = $row["user_type"];
				$user_enabled = $row["user_enabled"];
				$user_status = $row["user_status"];
				$contact_uuid = $row["contact_uuid"];
				$contact_name_given = $row["contact_name_given"];
				$contact_name_family = $row["contact_name_family"];
				$contact_email_uuid = $row["contact_email_uuid"];
				$contact_phone_uuid = $row["contact_phone_uuid"];
				$phone_number = $row["phone_number"];
				$contact_address_uuid = $row["contact_address_uuid"];
				$address_locality = $row["address_locality"];
				$address_region = $row["address_region"];
				$address_country = $row["address_country"];
				$contact_attachment_uuid = $row["contact_attachment_uuid"];
				$attachment_filename = $row["attachment_filename"];
				$attachment_content = $row["attachment_content"];
			}
			unset($sql, $parameters, $row);

		//get all language codes from database
			$sql = "select * from v_languages order by language asc ";
			$languages = $database->select($sql, null, 'all');

		//get user settings
			$sql = "select * from v_user_settings ";
			$sql .= "where user_uuid = :user_uuid ";
			$sql .= "and user_setting_enabled = true ";
			$parameters['user_uuid'] = $user_uuid;
			$result = $database->select($sql, $parameters, 'all');
			if (is_array($result)) {
				foreach($result as $row) {
					$name = $row['user_setting_name'];
					$category = $row['user_setting_category'];
					$subcategory = $row['user_setting_subcategory'];
					if (empty($subcategory)) {
						//$$category[$name] = $row['domain_setting_value'];
						$user_settings[$category][$name] = $row['user_setting_value'];
					}
					else {
						$user_settings[$category][$subcategory][$name] = $row['user_setting_value'];
					}
				}
			}
			unset($sql, $parameters, $result, $row);
	}

//set the defaults
	if (empty($user_totp_secret)) { $user_totp_secret = ""; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_profile'];

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
	echo "	function show_strength_meter() {\n";
	echo "		$('#pwstrength_progress').slideDown();\n";
	echo "	}\n";
	echo "</script>\n";

	echo "<form name='frm' id='frm' method='post' enctype='multipart/form-data'>\n";
	echo "<input type='hidden' name='contact_email_uuid' value='".$contact_email_uuid."'>\n";
	echo "<input type='hidden' name='contact_phone_uuid' value='".$contact_phone_uuid."'>\n";
	echo "<input type='hidden' name='contact_address_uuid' value='".$contact_address_uuid."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-user_profile']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if (!empty($unsaved)) {
		echo "<div class='unsaved'>".$text['message-unsaved_changes']." <i class='fas fa-exclamation-triangle'></i></div>";
	}

	$button_margin = 'margin-left: 15px;';
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>'submit_form();']);

	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-user_profile']."\n";
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%' class='mb-4'>";

	echo "	<tr>";
	echo "		<td width='30%' class='vncellreq' valign='top'>".$text['label-username']."</td>";
	echo "		<td width='70%' class='vtable'>";
	echo "		".escape($username)."\n";
	echo "		</td>";
	echo "	</tr>";


	echo "	<tr>";
	echo "		<td class='vncell".(($action == 'add') ? 'req' : null)."' valign='top'>".$text['label-password']."</td>";
	echo "		<td class='vtable'>";
	echo "			<input type='password' style='display: none;' disabled='disabled'>"; //help defeat browser auto-fill
	echo "			<input type='password' autocomplete='new-password' class='formfld' name='password' id='password' value=\"".escape($password ?? null)."\" ".($action == 'add' ? "required='required'" : null)." onkeypress='show_strength_meter();' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'>";
	echo "			<div id='pwstrength_progress' class='pwstrength_progress'></div><br />\n";
	if ((!empty($required['length']) && is_numeric($required['length']) && $required['length'] != 0) || $required['number'] || $required['lowercase'] || $required['uppercase'] || $required['special']) {
		echo $text['label-required'].': ';
		if (is_numeric($required['length']) && $required['length'] != 0) {
			echo $required['length']." ".$text['label-characters'];
			if ($required['number'] || $required['lowercase'] || $required['uppercase'] || $required['special']) {
				echo " (";
			}
		}
		if ($required['number']) {
			$required_temp[] = $text['label-number'];
		}
		if ($required['lowercase']) {
			$required_temp[] = $text['label-lowercase'];
		}
		if ($required['uppercase']) {
			$required_temp[] = $text['label-uppercase'];
		}
		if ($required['special']) {
			$required_temp[] = $text['label-special'];
		}
		if (!empty($required_temp)) {
			echo implode(', ',$required_temp);
			if (is_numeric($required['length']) && $required['length'] != 0) {
				echo ")";
			}
		}
		unset($required_temp);
	}
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell".(($action == 'add') ? 'req' : null)."' valign='top'>".$text['label-confirm_password']."</td>";
	echo "		<td class='vtable'>";
	echo "			<input type='password' autocomplete='new-password' class='formfld' name='password_confirm' id='password_confirm' value=\"".escape($password_confirm ?? null)."\" ".($action == 'add' ? "required='required'" : null)." onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'><br />\n";
	echo "			".$text['message-green_border_passwords_match']."\n";
	echo "		</td>";
	echo "	</tr>";

	//user time based one time password secret
	if (!empty($_SESSION['authentication']['methods']) && in_array('totp', $_SESSION['authentication']['methods'])) {
		if (!empty($user_totp_secret) && !empty($username)) {
			$otpauth = "otpauth://totp/".$username."?secret=".$user_totp_secret."&issuer=".$_SESSION['domain_name'];

			require_once 'resources/qr_code/QRErrorCorrectLevel.php';
			require_once 'resources/qr_code/QRCode.php';
			require_once 'resources/qr_code/QRCodeImage.php';

			try {
				$code = new QRCode (- 1, QRErrorCorrectLevel::H);
				$code->addData($otpauth);
				$code->make();
				$img = new QRCodeImage ($code, $width=210, $height=210, $quality=50);
				$img->draw();
				$image = $img->getImage();
				$img->finish();
			}
			catch (Exception $error) {
				echo $error;
			}
		}
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-user_totp_secret']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' valign='top'>\n";
		echo "	<input type='hidden' class='formfld' style='width: 250px;' name='user_totp_secret' id='user_totp_secret' value=\"".escape($user_totp_secret)."\" >";
		if (empty($user_totp_secret)) {
			$base32 = new base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', FALSE, TRUE, TRUE);
			$user_totp_secret = $base32->encode(generate_password(20,3));
			echo button::create(['type'=>'button',
			'label'=>$text['button-setup'],
			'icon'=>'key',
			'onclick'=>"document.getElementById('user_totp_secret').value = '".$user_totp_secret."';
			document.getElementById('frm').submit();"]);
		}
		else {
			echo "	<div id='totp_qr' style='display:none;'>\n";
			echo "		".$user_totp_secret."<br />\n";
			echo "		<img src=\"data:image/jpeg;base64,".base64_encode($image)."\" style='margin-top: 0px; padding: 5px; background: white; max-width: 100%;'><br />\n";
			echo "		".$text['description-user_totp_qr_code']."<br /><br />\n";
			echo "	</div>\n";
			echo button::create(['type'=>'button',
			'label'=>$text['button-view'],
			'id'=>'button-totp_view',
			'icon'=>'key',
			'onclick'=>"document.getElementById('totp_qr').style.display = 'inline';
				document.getElementById('button-totp_hide').style.display = 'inline';
				document.getElementById('button-totp_disable').style.display = 'inline';
				document.getElementById('button-totp_view').style.display = 'none';"]);

			echo button::create(['type'=>'button',
			'label'=>$text['button-hide'],
			'id'=>'button-totp_hide',
			'icon'=>'key',
			'style'=>'display: none;',
			'onclick'=>"document.getElementById('totp_qr').style.display = 'none';
				document.getElementById('button-totp_hide').style.display = 'none';
				document.getElementById('button-totp_disable').style.display = 'none';
				document.getElementById('button-totp_view').style.display = 'inline';"]);

			echo button::create(['type'=>'button',
				'label'=>$text['button-disable'],
				'id'=>'button-totp_disable',
				'icon'=>'trash',
				'style'=>'display: none;',
				'onclick'=>"document.getElementById('user_totp_secret').value = '';
				document.getElementById('frm').submit();"]);
		}
		if (empty($user_totp_secret)) {
			echo "	<br />".$text['description-user_totp_secret']."<br />\n";
		}
		else {
			echo "	<br />".$text['description-user_totp_view']."<br />\n";
		}
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "</table>\n";

	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";

	echo "	<tr>";
	echo "		<td width='30%' class='vncell'>".$text['label-first_name']."</td>";
	echo "		<td widht='70%' class='vtable'><input type='text' class='formfld' name='contact_name_given' id='contact_name_given' required='required' value='".escape($contact_name_given)."'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-last_name']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='contact_name_family' id='contact_name_family' required='required' value='".escape($contact_name_family)."'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-email']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='user_email' value='".escape($user_email ?? '')."' required='required'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-phone']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='phone_number' id='phone_number' required='required' value='".escape(format_phone($phone_number ?? ''))."'></td>";
	echo "	</tr>";

	echo "	<tr>\n";
	echo "		<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-address_locality']."</td>\n";
	echo "		<td class='vtable' align='left'><input class='formfld' type='text' name='address_locality' maxlength='255' value=\"".escape($address_locality)."\"></td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "		<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-region']."</td>\n";
	echo "		<td class='vtable' align='left'><input class='formfld' type='text' name='address_region' maxlength='255' required='required' value=\"".escape($address_region)."\"></td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "		<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-address_country']."</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	$countries = get_countries($database);
	if (is_array($countries) && sizeof($countries) > 0) {
		echo "		<select class='formfld' name='address_country' id='address_country' required='required'>\n";
		echo "			<option value=''></option>\n";
		foreach ($countries as $country) {
			$selected = ($address_country == $country['iso_a3']) ? "selected='selected'" : null;
			echo "		<option value='".escape($country['iso_a3'])."' ".$selected.">".escape($country['country'])." (".escape($country['iso_a3']).")</option>\n";
		}
		echo "		</select>\n";
	}
	else {
		echo "		<input class='formfld' type='text' name='address_country' id='address_country' maxlength='255' value=\"".escape($address_country)."\" required='required'>\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-user_language']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='user_language' name='user_language' class='formfld' style=''>\n";
	echo "		<option value=''></option>\n";
	if (!empty($languages) && is_array($languages) && sizeof($languages) != 0) {
		foreach ($languages as $row) {
			$language_codes[$row["code"]] = $row["language"];
		}
	}
	unset($sql, $languages, $row);
	if (is_array($_SESSION['app']['languages']) && sizeof($_SESSION['app']['languages']) != 0) {
		foreach ($_SESSION['app']['languages'] as $code) {
			$selected = (isset($user_language) && $code == $user_language) || (isset($user_settings['domain']['language']['code']) && $code == $user_settings['domain']['language']['code']) ? "selected='selected'" : null;
			echo "	<option value='".$code."' ".$selected.">".escape($language_codes[$code] ?? $language_codes[explode('-', $code)[0]] ?? null)." [".escape($code ?? null)."]</option>\n";
		}
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-user_language']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-time_zone']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='user_time_zone' name='user_time_zone' class='formfld' style=''>\n";
	echo "		<option value=''></option>\n";
	//$list = DateTimeZone::listAbbreviations();
	$time_zone_identifiers = DateTimeZone::listIdentifiers();
	$previous_category = '';
	$x = 0;
	foreach ($time_zone_identifiers as $key => $row) {
		$time_zone = explode("/", $row);
		$category = $time_zone[0];
		if ($category != $previous_category) {
			if ($x > 0) {
				echo "		</optgroup>\n";
			}
			echo "		<optgroup label='".$category."'>\n";
		}
		$selected = (isset($user_time_zone) && $row == $user_time_zone) || (!empty($user_settings['domain']['time_zone']) && $row == $user_settings['domain']['time_zone']['name']) ? "selected='selected'" : null;
		echo "			<option value='".escape($row)."' ".$selected.">".escape($row)."</option>\n";
		$previous_category = $category;
		$x++;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-time_zone']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-status']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\">\n";
	echo "		<select id='user_status' name='user_status' class='formfld' style=''>\n";
	echo "			<option value=''></option>\n";
	echo "			<option value='Available' ".(($user_status == "Available") ? "selected='selected'" : null).">".$text['option-available']."</option>\n";
	echo "			<option value='Available (On Demand)' ".(($user_status == "Available (On Demand)") ? "selected='selected'" : null).">".$text['option-available_on_demand']."</option>\n";
	echo "			<option value='Logged Out' ".(($user_status == "Logged Out") ? "selected='selected'" : null).">".$text['option-logged_out']."</option>\n";
	echo "			<option value='On Break' ".(($user_status == "On Break") ? "selected='selected'" : null).">".$text['option-on_break']."</option>\n";
	echo "			<option value='Do Not Disturb' ".(($user_status == "Do Not Disturb") ? "selected='selected'" : null).">".$text['option-do_not_disturb']."</option>\n";
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-status']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-photo']."</td>\n";
	echo "	<td class='vtable' align='left'>";
	if (!empty($attachment_filename) && !empty($attachment_content)) {
		$attachment_type = strtolower(pathinfo($attachment_filename, PATHINFO_EXTENSION));
		echo "	<label class='mt-1' for='contact_attachment_uuid'><input type='checkbox' name='contact_attachment_uuid' id='contact_attachment_uuid' value='".$contact_attachment_uuid."'> ".$text['label-delete']."</label><br>\n";
		echo "	<img id='contact_attachment' style='width: 100%; max-width: 300px; height: auto; border-radius: ".$settings->get('theme', 'input_border_radius', '3px').";' src='data:image/".$attachment_type.";base64,".$attachment_content."'>\n";
	}
	else {
		echo "	<input class='formfld' type='file' name='contact_attachment'>\n";
	}
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "</table>";
	echo "</div>\n";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//hide password fields before submit
	echo "<script>\n";
	echo "	function submit_form() {\n";
	echo "		hide_password_fields();\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
