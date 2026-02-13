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
	Portions created by the Initial Developer are Copyright (C) 2018-2025
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!(permission_exists('contact_add') || permission_exists('contact_edit'))) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the defaults
	$contact_organization = '';
	$contact_name_prefix = '';
	$contact_name_given = '';
	$contact_name_middle = '';
	$contact_name_family = '';
	$contact_name_suffix = '';
	$contact_nickname = '';
	$contact_title = '';
	$contact_role = '';
	$contact_category = '';
	$contact_note = '';
	$url_label = '';
	$relation_label = '';
	$contact_attachments = [];

//set from session variables
	$body_text_color = !empty($settings->get('theme', 'body_text_color')) ? $settings->get('theme', 'body_text_color') : 'false';

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$contact_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$contact_organization = $_POST["contact_organization"];
		$contact_name_prefix = $_POST["contact_name_prefix"];
		$contact_name_given = $_POST["contact_name_given"];
		$contact_name_middle = $_POST["contact_name_middle"];
		$contact_name_family = $_POST["contact_name_family"];
		$contact_name_suffix = $_POST["contact_name_suffix"];
		$contact_nickname = $_POST["contact_nickname"];

		$contact_type = $_POST["contact_type"];
		$contact_title = $_POST["contact_title"];
		$contact_role = $_POST["contact_role"];
		$contact_category = $_POST["contact_category"];
		$contact_time_zone = $_POST["contact_time_zone"];
		$contact_note = $_POST["contact_note"];

		$last_mod_date = $_POST["last_mod_date"] ?? null;
		$last_mod_user = $_POST["last_mod_user"] ?? null;

		//$contact_users = $_POST["contact_users"];
		//$contact_groups = $_POST["contact_groups"];
		$contact_user_uuid = $settings->get('contact', 'permissions', false) ? ($_POST["contact_user_uuid"] ?? $_SESSION["user_uuid"]) : ($contact_user_uuid = $_POST["contact_user_uuid"] ?? null);
		$contact_group_uuid = $_POST["contact_group_uuid"] ?? null;

		$contact_phones = $_POST["contact_phones"];
		$contact_addresses = $_POST["contact_addresses"];
		$contact_emails = $_POST["contact_emails"];
		$contact_urls = $_POST["contact_urls"];
		$contact_relations = $_POST["contact_relations"];
		$contact_settings = $_POST["contact_settings"];
		$contact_attachments = $_POST["contact_attachments"] ?? null;
		$contact_times = $_POST["contact_times"] ?? null;
		$contact_notes = $_POST["contact_notes"] ?? null;
	}

//process the user data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//debug info
			//view_array($_POST, true);

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: contacts.php');
				exit;
			}

		//process the http post data by submitted action
			if (!empty($_POST['action'])) {

				//prepare the array(s)
				$x = 0;
				if (!empty($_POST['contact_users']) && is_array($_POST['contact_users']) && @sizeof($_POST['contact_users']) != 0) {
					foreach ($_POST['contact_users'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_users'][]['contact_user_uuid'] = $row['contact_user_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_groups']) && is_array($_POST['contact_groups']) && @sizeof($_POST['contact_groups']) != 0) {
					foreach ($_POST['contact_groups'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_groups'][]['contact_group_uuid'] = $row['contact_group_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_phones']) && is_array($_POST['contact_phones']) && @sizeof($_POST['contact_phones']) != 0) {
					foreach ($_POST['contact_phones'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_phones'][]['contact_phone_uuid'] = $row['contact_phone_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_addresses']) && is_array($_POST['contact_addresses']) && @sizeof($_POST['contact_addresses']) != 0) {
					foreach ($_POST['contact_addresses'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_addresses'][]['contact_address_uuid'] = $row['contact_address_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_emails']) && is_array($_POST['contact_emails']) && @sizeof($_POST['contact_emails']) != 0) {
					foreach ($_POST['contact_emails'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_emails'][]['contact_email_uuid'] = $row['contact_email_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_urls']) && is_array($_POST['contact_urls']) && @sizeof($_POST['contact_urls']) != 0) {
					foreach ($_POST['contact_urls'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_urls'][]['contact_url_uuid'] = $row['contact_url_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_relations']) && is_array($_POST['contact_relations']) && @sizeof($_POST['contact_relations']) != 0) {
					foreach ($_POST['contact_relations'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_relations'][]['contact_relation_uuid'] = $row['contact_relation_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_settings']) && is_array($_POST['contact_settings']) && @sizeof($_POST['contact_settings']) != 0) {
					foreach ($_POST['contact_settings'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_settings'][]['contact_setting_uuid'] = $row['contact_setting_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_attachments']) && is_array($_POST['contact_attachments']) && @sizeof($_POST['contact_attachments']) != 0) {
					foreach ($_POST['contact_attachments'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_attachments'][]['contact_attachment_uuid'] = $row['contact_attachment_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_times']) && is_array($_POST['contact_times']) && @sizeof($_POST['contact_times']) != 0) {
					foreach ($_POST['contact_times'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_times'][]['contact_time_uuid'] = $row['contact_time_uuid'];
							$x++;
						}
					}
				}

				$x = 0;
				if (!empty($_POST['contact_notes']) && is_array($_POST['contact_notes']) && @sizeof($_POST['contact_notes']) != 0) {
					foreach ($_POST['contact_notes'] as $row) {
						if (!empty($row['contact_uuid']) && is_uuid($row['contact_uuid']) && !empty($row['checked']) && $row['checked'] === 'true') {
							$array['contacts'][$x]['checked'] = $row['checked'];
							$array['contacts'][$x]['contact_notes'][]['contact_note_uuid'] = $row['contact_note_uuid'];
							$x++;
						}
					}
				}

				//send the array to the database class
				if (!empty($array)) {
					switch ($_POST['action']) {
						case 'copy':
							if (permission_exists('contact_add')) {
								$database->copy($array);
							}
							break;
						case 'delete':
							if (permission_exists('contact_delete')) {
								$database->delete($array);
							}
							break;
						case 'toggle':
							if (permission_exists('contact_update')) {
								$database->toggle($array);
							}
							break;
					}
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: contact_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			//if (empty($contact_type)) { $msg .= $text['message-required']." ".$text['label-contact_type']."<br>\n"; }
			//if (empty($contact_title)) { $msg .= $text['message-required']." ".$text['label-contact_title']."<br>\n"; }
			//if (empty($contact_role)) { $msg .= $text['message-required']." ".$text['label-contact_role']."<br>\n"; }
			//if (empty($contact_category)) { $msg .= $text['message-required']." ".$text['label-contact_category']."<br>\n"; }
			//if (empty($contact_organization)) { $msg .= $text['message-required']." ".$text['label-contact_organization']."<br>\n"; }
			//if (empty($contact_name_prefix)) { $msg .= $text['message-required']." ".$text['label-contact_name_prefix']."<br>\n"; }
			//if (empty($contact_name_given)) { $msg .= $text['message-required']." ".$text['label-contact_name_given']."<br>\n"; }
			//if (empty($contact_name_middle)) { $msg .= $text['message-required']." ".$text['label-contact_name_middle']."<br>\n"; }
			//if (empty($contact_name_family)) { $msg .= $text['message-required']." ".$text['label-contact_name_family']."<br>\n"; }
			//if (empty($contact_name_suffix)) { $msg .= $text['message-required']." ".$text['label-contact_name_suffix']."<br>\n"; }
			//if (empty($contact_nickname)) { $msg .= $text['message-required']." ".$text['label-contact_nickname']."<br>\n"; }
			//if (empty($contact_time_zone)) { $msg .= $text['message-required']." ".$text['label-contact_time_zone']."<br>\n"; }
			//if (empty($last_mod_date)) { $msg .= $text['message-required']." ".$text['label-last_mod_date']."<br>\n"; }
			//if (empty($last_mod_user)) { $msg .= $text['message-required']." ".$text['label-last_mod_user']."<br>\n"; }
			//if (empty($contact_phones)) { $msg .= $text['message-required']." ".$text['label-contact_phones']."<br>\n"; }
			//if (empty($contact_addresses)) { $msg .= $text['message-required']." ".$text['label-contact_addresses']."<br>\n"; }
			//if (empty($contact_emails)) { $msg .= $text['message-required']." ".$text['label-contact_emails']."<br>\n"; }
			//if (empty($contact_urls)) { $msg .= $text['message-required']." ".$text['label-contact_urls']."<br>\n"; }
			//if (empty($contact_settings)) { $msg .= $text['message-required']." ".$text['label-contact_settings']."<br>\n"; }
			//if (empty($contact_user_uuid)) { $msg .= $text['message-required']." ".$text['label-contact_user_uuid']."<br>\n"; }
			//if (empty($contact_group_uuid)) { $msg .= $text['message-required']." ".$text['label-contact_group_uuid']."<br>\n"; }
			//if (empty($contact_note)) { $msg .= $text['message-required']." ".$text['label-contact_note']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//add the contact_uuid
			if (empty($_POST["contact_uuid"])) {
				$contact_uuid = uuid();
			}

		//prepare the array
			$array['contacts'][0]['contact_uuid'] = $contact_uuid;
			$array['contacts'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['contacts'][0]['contact_type'] = $contact_type;
			$array['contacts'][0]['contact_title'] = $contact_title;
			$array['contacts'][0]['contact_role'] = $contact_role;
			$array['contacts'][0]['contact_category'] = $contact_category;
			$array['contacts'][0]['contact_organization'] = $contact_organization;
			$array['contacts'][0]['contact_name_prefix'] = $contact_name_prefix;
			$array['contacts'][0]['contact_name_given'] = $contact_name_given;
			$array['contacts'][0]['contact_name_middle'] = $contact_name_middle;
			$array['contacts'][0]['contact_name_family'] = $contact_name_family;
			$array['contacts'][0]['contact_name_suffix'] = $contact_name_suffix;
			$array['contacts'][0]['contact_nickname'] = $contact_nickname;
			$array['contacts'][0]['contact_time_zone'] = $contact_time_zone;
			$array['contacts'][0]['last_mod_date'] = "now()";
			$array['contacts'][0]['last_mod_user'] = $_SESSION['user_uuid'];
			$array['contacts'][0]['contact_note'] = $contact_note;

			$y = 0;
			if (isset($contact_user_uuid)) {
				$sql = "select contact_uuid from v_contact_users ";
				$sql .= "where contact_uuid = :contact_uuid ";
				$sql .= "and user_uuid = :user_uuid ";
				$parameters['contact_uuid'] = $contact_uuid;
				$parameters['user_uuid'] = $contact_user_uuid;
				$users = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);

				if (is_array($users) === false || count($users) === 0)
				{
						$array['contacts'][0]['contact_users'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contacts'][0]['contact_users'][$y]['contact_user_uuid'] = uuid();
						$array['contacts'][0]['contact_users'][$y]['contact_uuid'] = $contact_uuid;
						$array['contacts'][0]['contact_users'][$y]['user_uuid'] = $contact_user_uuid;
						$y++;
				}
			}

			$y = 0;
			if (isset($contact_group_uuid)) {
				$array['contacts'][0]['contact_groups'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
				$array['contacts'][0]['contact_groups'][$y]['contact_group_uuid'] = uuid();
				$array['contacts'][0]['contact_groups'][$y]['contact_uuid'] = $contact_uuid;
				$array['contacts'][0]['contact_groups'][$y]['group_uuid'] = $contact_group_uuid;
				$y++;
			}

			$y = 0;
			if (!empty($contact_phones)) {
				foreach ($contact_phones as $row) {
					if (!empty($row['phone_number'])) {

						//if speed dial number already exists, empty before save
						if (!empty($row["phone_speed_dial"])) {
							$phone_speed_dial_exists = false;
							if (is_numeric($row["phone_speed_dial"])) {
								$sql = "select count(contact_phone_uuid) ";
								$sql .= "from v_contact_phones ";
								$sql .= "where phone_speed_dial = :phone_speed_dial ";
								$sql .= "and domain_uuid = :domain_uuid ";
								if ($action == "update" && is_uuid($row["contact_phone_uuid"])) {
									$sql .= "and contact_phone_uuid <> :contact_phone_uuid ";
									$parameters['contact_phone_uuid'] = $row["contact_phone_uuid"];
								}
								$parameters['phone_speed_dial'] = $row["phone_speed_dial"];
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								if (!empty($database->execute($sql, $parameters, 'column'))) {
									$phone_speed_dial_exists = true;
								}
								unset($sql, $parameters);
							}
							if (!is_numeric($row["phone_speed_dial"]) || $phone_speed_dial_exists) {
								message::add($text['message-speed_dial_exists'],'negative');
								unset($row["phone_speed_dial"]);
							}
						}

						$array['contacts'][0]['contact_phones'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contacts'][0]['contact_phones'][$y]['contact_uuid'] = $contact_uuid;
						$array['contacts'][0]['contact_phones'][$y]['contact_phone_uuid'] = $row["contact_phone_uuid"];
						$array['contacts'][0]['contact_phones'][$y]['phone_label'] = $row["phone_label"];
						$array['contacts'][0]['contact_phones'][$y]['phone_type_voice'] = $row["phone_type_voice"] ?? null;
						$array['contacts'][0]['contact_phones'][$y]['phone_type_fax'] = $row["phone_type_fax"] ?? null;
						$array['contacts'][0]['contact_phones'][$y]['phone_type_video'] = $row["phone_type_video"] ?? null;
						$array['contacts'][0]['contact_phones'][$y]['phone_type_text'] = $row["phone_type_text"] ?? null;
						$array['contacts'][0]['contact_phones'][$y]['phone_speed_dial'] = $row["phone_speed_dial"];
						$array['contacts'][0]['contact_phones'][$y]['phone_country_code'] = $row["phone_country_code"];
						$array['contacts'][0]['contact_phones'][$y]['phone_number'] = $row["phone_number"];
						$array['contacts'][0]['contact_phones'][$y]['phone_extension'] = $row["phone_extension"];
						$array['contacts'][0]['contact_phones'][$y]['phone_primary'] = $row["phone_primary"];
						$array['contacts'][0]['contact_phones'][$y]['phone_description'] = $row["phone_description"];

						//clear the cache
						if (!empty($row["phone_speed_dial"])) {
							$cache = new cache;
							$cache->delete("app.dialplan.speed_dial.".$row["phone_speed_dial"]."@".$_SESSION['domain_name']);
							$cache->delete("app:caller_id:lookup:domain_name:" . $_SESSION['domain_name'] . ":" . $row["phone_number"]);
							if (!empty($row["phone_country_code"])) {
								$cache->delete("app:caller_id:lookup:domain_name:" . $_SESSION['domain_name'] . ":" . $row["phone_country_code"] . $row["phone_number"]);
								$cache->delete("app:caller_id:lookup:domain_name:" . $_SESSION['domain_name'] . ":+" . $row["phone_country_code"] . $row["phone_number"]);
							}
						}

						//increment the row id
						$y++;
					}
				}
			}

			$y = 0;
			if (!empty($contact_addresses)) {
				foreach ($contact_addresses as $row) {
					if (!empty($row['address_street'])) {
						$array['contacts'][0]['contact_addresses'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contacts'][0]['contact_addresses'][$y]['contact_uuid'] = $contact_uuid;
						$array['contacts'][0]['contact_addresses'][$y]['contact_address_uuid'] = $row["contact_address_uuid"];
						$array['contacts'][0]['contact_addresses'][$y]['address_label'] = $row["address_label"];
						$array['contacts'][0]['contact_addresses'][$y]['address_type'] = $row["address_type"];
						$array['contacts'][0]['contact_addresses'][$y]['address_street'] = $row["address_street"];
						$array['contacts'][0]['contact_addresses'][$y]['address_extended'] = $row["address_extended"];
						if (permission_exists('address_community')) {
							$array['contacts'][0]['contact_addresses'][$y]['address_community'] = $row["address_community"];
						}
						$array['contacts'][0]['contact_addresses'][$y]['address_locality'] = $row["address_locality"];
						$array['contacts'][0]['contact_addresses'][$y]['address_region'] = $row["address_region"];
						$array['contacts'][0]['contact_addresses'][$y]['address_postal_code'] = $row["address_postal_code"];
						$array['contacts'][0]['contact_addresses'][$y]['address_country'] = $row["address_country"];
						if (permission_exists('address_latitude')) {
							$array['contacts'][0]['contact_addresses'][$y]['address_latitude'] = $row["address_latitude"];
						}
						if (permission_exists('address_longitude')) {
							$array['contacts'][0]['contact_addresses'][$y]['address_longitude'] = $row["address_longitude"];
						}
						$array['contacts'][0]['contact_addresses'][$y]['address_primary'] = $row["address_primary"];
						$array['contacts'][0]['contact_addresses'][$y]['address_description'] = $row["address_description"];
						$y++;
					}
				}
			}

			$y = 0;
			if (!empty($contact_emails)) {
				foreach ($contact_emails as $row) {
					if (!empty($row['email_address'])) {
						$array['contacts'][0]['contact_emails'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contacts'][0]['contact_emails'][$y]['contact_uuid'] = $contact_uuid;
						$array['contacts'][0]['contact_emails'][$y]['contact_email_uuid'] = $row["contact_email_uuid"];
						$array['contacts'][0]['contact_emails'][$y]['email_label'] = $row["email_label"];
						$array['contacts'][0]['contact_emails'][$y]['email_address'] = $row["email_address"];
						$array['contacts'][0]['contact_emails'][$y]['email_primary'] = $row["email_primary"];
						$array['contacts'][0]['contact_emails'][$y]['email_description'] = $row["email_description"];
						$y++;
					}
				}

			}

			$y = 0;
			if (!empty($contact_urls)) {
				foreach ($contact_urls as $row) {
					if (!empty($row['url_address'])) {
						$array['contacts'][0]['contact_urls'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contacts'][0]['contact_urls'][$y]['contact_uuid'] = $contact_uuid;
						$array['contacts'][0]['contact_urls'][$y]['contact_url_uuid'] = $row["contact_url_uuid"];
						$array['contacts'][0]['contact_urls'][$y]['url_type'] = $row["url_type"] ?? '';
						$array['contacts'][0]['contact_urls'][$y]['url_label'] = $row["url_label"];
						$array['contacts'][0]['contact_urls'][$y]['url_address'] = $row["url_address"];
						$array['contacts'][0]['contact_urls'][$y]['url_primary'] = $row["url_primary"];
						$array['contacts'][0]['contact_urls'][$y]['url_description'] = $row["url_description"];
						$y++;
					}
				}
			}

			$y = 0;
			if (!empty($contact_relations)) {
				foreach ($contact_relations as $row) {
					if (!empty($row['contact_relation_uuid'])) {
						$array['contacts'][0]['contact_relations'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contacts'][0]['contact_relations'][$y]['contact_uuid'] = $contact_uuid;
						$array['contacts'][0]['contact_relations'][$y]['contact_relation_uuid'] = $row["contact_relation_uuid"];
						$array['contacts'][0]['contact_relations'][$y]['relation_label'] = $row["relation_label"];
						$array['contacts'][0]['contact_relations'][$y]['relation_contact_uuid'] = $row["relation_contact_uuid"];
						$y++;
					}
				}
			}

			$y = 0;
			if (!empty($contact_settings)) {
				foreach ($contact_settings as $row) {
					if (strlen($row['contact_setting_category']) > 0 && strlen($row['contact_setting_subcategory']) > 0 && !empty($row['contact_setting_name'])) {
						$array['contacts'][0]['contact_settings'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contacts'][0]['contact_settings'][$y]['contact_uuid'] = $contact_uuid;
						$array['contacts'][0]['contact_settings'][$y]['contact_setting_uuid'] = $row["contact_setting_uuid"];
						$array['contacts'][0]['contact_settings'][$y]['contact_setting_category'] = $row["contact_setting_category"];
						$array['contacts'][0]['contact_settings'][$y]['contact_setting_subcategory'] = $row["contact_setting_subcategory"];
						$array['contacts'][0]['contact_settings'][$y]['contact_setting_name'] = $row["contact_setting_name"];
						$array['contacts'][0]['contact_settings'][$y]['contact_setting_value'] = $row["contact_setting_value"];
						$array['contacts'][0]['contact_settings'][$y]['contact_setting_order'] = $row["contact_setting_order"];
						$array['contacts'][0]['contact_settings'][$y]['contact_setting_enabled'] = $row["contact_setting_enabled"];
						$array['contacts'][0]['contact_settings'][$y]['contact_setting_description'] = $row["contact_setting_description"];
						$y++;
					}
				}
			}

			$y = 0;
			if (!empty($contact_attachments)) {
				foreach ($contact_attachments as $row) {
					$array['contacts'][0]['contact_attachments'][$y]['contact_attachment_uuid'] = $row["contact_attachment_uuid"];
					$array['contacts'][0]['contact_attachments'][$y]['domain_uuid'] = $row["domain_uuid"];
					$array['contacts'][0]['contact_attachments'][$y]['contact_uuid'] = $row["contact_uuid"];
					$array['contacts'][0]['contact_attachments'][$y]['attachment_primary'] = $row["attachment_primary"];
					//$array['contacts'][0]['contact_attachments'][$y]['attachment_filename'] = $row["attachment_filename"];
					//$array['contacts'][0]['contact_attachments'][$y]['attachment_content'] = $row["attachment_content"];
					$array['contacts'][0]['contact_attachments'][$y]['attachment_description'] = $row["attachment_description"] ?? null;
					//$array['contacts'][0]['contact_attachments'][$y]['attachment_uploaded_date'] = $row["attachment_uploaded_date"];
					//$array['contacts'][0]['contact_attachments'][$y]['attachment_uploaded_user_uuid'] = $row["attachment_uploaded_user_uuid"];
					//$array['contacts'][0]['contact_attachments'][$y]['attachment_size'] = $row["attachment_size"];
					$y++;
				}
			}

			$y = 0;
			if (!empty($contact_times)) {
				foreach ($contact_times as $row) {
					if (!empty($row['time_start'])) {
						$array['contacts'][0]['contact_times'][$y]['contact_time_uuid'] = $row["contact_time_uuid"];
						$array['contacts'][0]['contact_times'][$y]['domain_uuid'] = $row["domain_uuid"];
						$array['contacts'][0]['contact_times'][$y]['contact_uuid'] = $row["contact_uuid"];
						$array['contacts'][0]['contact_times'][$y]['time_start'] = $row["time_start"];
						$array['contacts'][0]['contact_times'][$y]['time_stop'] = $row["time_stop"];
						$array['contacts'][0]['contact_times'][$y]['time_description'] = $row["time_description"];
						$y++;
					}
				}
			}

			$y = 0;
			if (!empty($contact_notes)) {
				foreach ($contact_notes as $row) {
					if (!empty($row['contact_note'])) {
						$array['contacts'][0]['contact_notes'][$y]['contact_note_uuid'] = $row["contact_note_uuid"];
						$array['contacts'][0]['contact_notes'][$y]['domain_uuid'] = $row["domain_uuid"];
						$array['contacts'][0]['contact_notes'][$y]['contact_uuid'] = $row["contact_uuid"];
						$array['contacts'][0]['contact_notes'][$y]['contact_note'] = $row["contact_note"];
						$array['contacts'][0]['contact_notes'][$y]['last_mod_date'] = 'now()';
						$array['contacts'][0]['contact_notes'][$y]['last_mod_user'] = $_SESSION['username'];
						$y++;
					}
				}
			}

		//save the data
			if (!empty($array)) {
				//add the permission object
				$p = permissions::new();
				$p->add('contact_add', 'temp');
				$p->add('contact_phone_add', 'temp');
				$p->add('contact_address_add', 'temp');
				$p->add('contact_user_add', 'temp');
				$p->add('contact_group_add', 'temp');

				$database->save($array);
				//$message = $database->message;
				unset($array);

				$p->delete('contact_add', 'temp');
				$p->delete('contact_phone_add', 'temp');
				$p->delete('contact_address_add', 'temp');
				$p->delete('contact_user_add', 'temp');
				$p->delete('contact_group_add', 'temp');
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: contacts.php');
				header('Location: contact_edit.php?id='.urlencode($contact_uuid ?? null));
				return;
			}
	}

//set the time zone
	date_default_timezone_set($settings->get('domain', 'time_zone', date_default_timezone_get()));

//set the time format options: 12h, 24h
	if ($settings->get('domain', 'time_format') == '24h') {
		$time_format = 'H:i:s';
	}
	else {
		$time_format = 'h:i:s a';
	}

//pre-populate the form
	if (!empty($_GET) && empty($_POST["persistformvar"])) {
		$sql = "select * from v_contacts ";
		$sql .= "where contact_uuid = :contact_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid;
		$row = $database->select($sql, $parameters ?? null, 'row');
		if (!empty($row)) {
			$contact_organization = $row["contact_organization"];
			$contact_name_prefix = $row["contact_name_prefix"];
			$contact_name_given = $row["contact_name_given"];
			$contact_name_middle = $row["contact_name_middle"];
			$contact_name_family = $row["contact_name_family"];
			$contact_name_suffix = $row["contact_name_suffix"];
			$contact_nickname = $row["contact_nickname"];

			$contact_type = $row["contact_type"];
			$contact_title = $row["contact_title"];
			$contact_role = $row["contact_role"];
			$contact_category = $row["contact_category"];
			$contact_time_zone = $row["contact_time_zone"];
			$contact_note = $row["contact_note"];

			$last_mod_date = $row["last_mod_date"];
			$last_mod_user = $row["last_mod_user"];

			//$contact_phones = $row["contact_phones"];
			//$contact_addresses = $row["contact_addresses"];
			//$contact_emails = $row["contact_emails"];
			//$contact_urls = $row["contact_urls"];
			//$contact_settings = $row["contact_settings"];
			//$contact_user_uuid = $row["contact_user_uuid"];

			$contact_user_uuid = $row["contact_user_uuid"] ?? '';
			$contact_group_uuid = $row["contact_group_uuid"] ?? '';

		}
		unset($sql, $parameters, $row);
	}

//get the users array
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//determine if contact assigned to a user
	if (!empty($users)) {
		foreach ($users as $user) {
			if (!empty($contact_uuid) && $user['contact_uuid'] == $contact_uuid) {
				$contact_user_uuid = $user['user_uuid'];
				break;
			}
		}
	}

//get the users assigned to this contact
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select c.domain_uuid, c.contact_uuid, u.username, u.user_uuid, a.contact_user_uuid ";
		$sql .= "from v_contacts as c, v_users as u, v_contact_users as a ";
		$sql .= "where c.contact_uuid = :contact_uuid ";
		$sql .= "and c.domain_uuid = :domain_uuid ";
		$sql .= "and u.user_uuid = a.user_uuid ";
		$sql .= "and c.contact_uuid = a.contact_uuid ";
		$sql .= "order by u.username asc ";
		$parameters['contact_uuid'] = $contact_uuid;
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_users_assigned = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the assigned groups of this contact
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select g.*, cg.contact_group_uuid ";
		$sql .= "from v_groups as g, v_contact_groups as cg ";
		$sql .= "where cg.group_uuid = g.group_uuid ";
		$sql .= "and cg.domain_uuid = :domain_uuid ";
		$sql .= "and cg.contact_uuid = :contact_uuid ";
		$sql .= "and cg.group_uuid <> :group_uuid ";
		$sql .= "order by g.group_name asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_uuid'] = $contact_uuid;
		$parameters['group_uuid'] = $_SESSION["user_uuid"];
		$contact_groups_assigned = $database->select($sql, $parameters, 'all');
		if (!empty($contact_groups_assigned)) {
			foreach ($contact_groups_assigned as $field) {
				$contact_groups[] = "'".$field['group_uuid']."'";
			}
		}
		unset($sql, $parameters);
	}

//get the available groups to this contact
	$sql = "select group_uuid, group_name from v_groups ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	if (!empty($contact_groups)) {
		$sql .= "and group_uuid not in (".implode(',', $contact_groups).") ";
	}
	$sql .= "order by group_name asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$contact_groups_available = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $contact_groups);

//get the child data
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select * from v_contact_phones ";
		$sql .= "where contact_uuid = :contact_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid;
		$contact_phones = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $contact_phone_uuid
	if (empty($contact_phone_uuid)) {
		$contact_phone_uuid = uuid();
	}

//add an empty row
	if (empty($contact_phones) || count($contact_phones) == 0) {
		$x = !empty($contact_phones) ? count($contact_phones) : 0;
		$contact_phones[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_phones[$x]['contact_uuid'] = $contact_uuid ?? null;
		$contact_phones[$x]['contact_phone_uuid'] = uuid();
		$contact_phones[$x]['phone_label'] = '';
		$contact_phones[$x]['phone_type_voice'] = '';
		$contact_phones[$x]['phone_type_fax'] = '';
		$contact_phones[$x]['phone_type_video'] = '';
		$contact_phones[$x]['phone_type_text'] = '';
		$contact_phones[$x]['phone_speed_dial'] = '';
		$contact_phones[$x]['phone_country_code'] = '';
		$contact_phones[$x]['phone_number'] = '';
		$contact_phones[$x]['phone_extension'] = '';
		$contact_phones[$x]['phone_primary'] = false;
		$contact_phones[$x]['phone_description'] = '';
	}

//get the child data
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select * from v_contact_addresses ";
		$sql .= "where contact_uuid = :contact_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$sql .= "order by address_street asc";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid;
		$contact_addresses = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $contact_address_uuid
	if (empty($contact_address_uuid)) {
		$contact_address_uuid = uuid();
	}

//add an empty row
	if (empty($contact_addresses) || count($contact_addresses) == 0) {
		$x = !empty($contact_addresses) ? count($contact_addresses) : 0;
		$contact_addresses[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_addresses[$x]['contact_uuid'] = $contact_uuid ?? null;
		$contact_addresses[$x]['contact_address_uuid'] = uuid();
		$contact_addresses[$x]['address_label'] = '';
		$contact_addresses[$x]['address_type'] = '';
		$contact_addresses[$x]['address_street'] = '';
		$contact_addresses[$x]['address_extended'] = '';
		$contact_addresses[$x]['address_community'] = '';
		$contact_addresses[$x]['address_locality'] = '';
		$contact_addresses[$x]['address_region'] = '';
		$contact_addresses[$x]['address_postal_code'] = '';
		$contact_addresses[$x]['address_country'] = '';
		$contact_addresses[$x]['address_latitude'] = '';
		$contact_addresses[$x]['address_longitude'] = '';
		$contact_addresses[$x]['address_primary'] = false;
		$contact_addresses[$x]['address_description'] = '';
	}

//get the child data
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select * from v_contact_emails ";
		$sql .= "where contact_uuid = :contact_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid;
		$contact_emails = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $contact_email_uuid
	if (empty($contact_email_uuid)) {
		$contact_email_uuid = uuid();
	}

//add an empty row
	if (empty($contact_emails) || count($contact_emails) == 0) {
		$x = !empty($contact_emails) ? count($contact_emails) : 0;
		$contact_emails[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_emails[$x]['contact_uuid'] = $contact_uuid ?? null;
		$contact_emails[$x]['contact_email_uuid'] = uuid();
		$contact_emails[$x]['email_label'] = '';
		$contact_emails[$x]['email_address'] = '';
		$contact_emails[$x]['email_primary'] = false;
		$contact_emails[$x]['email_description'] = '';
	}

//get the child data
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select * from v_contact_urls ";
		$sql .= "where contact_uuid = :contact_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$sql .= "order by url_address asc";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid;
		$contact_urls = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $contact_url_uuid
	if (empty($contact_url_uuid)) {
		$contact_url_uuid = uuid();
	}

//add an empty row
	if (empty($contact_urls) || count($contact_urls) == 0) {
		$x = !empty($contact_urls) ? count($contact_urls) : 0;
		$contact_urls[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_urls[$x]['contact_uuid'] = $contact_uuid ?? null;
		$contact_urls[$x]['contact_url_uuid'] = uuid();
		$contact_urls[$x]['url_type'] = '';
		$contact_urls[$x]['url_label'] = '';
		$contact_urls[$x]['url_address'] = '';
		$contact_urls[$x]['url_primary'] = false;
		$contact_urls[$x]['url_description'] = '';
	}

//get the child data
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select * from v_contact_relations ";
		$sql .= "where contact_uuid = :contact_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid ?? null;
		$contact_relations = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $contact_setting_uuid
	if (empty($contact_relation_uuid)) {
		$contact_relation_uuid = uuid();
	}

//add an empty row
	if (empty($contact_relations) || count($contact_relations) == 0) {
		$x = !empty($contact_relations) ? count($contact_relations) : 0;
		$contact_relations[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_relations[$x]['contact_uuid'] = $contact_uuid ?? null;
		$contact_relations[$x]['contact_relation_uuid'] = uuid();
		$contact_relations[$x]['relation_label'] = '';
		$contact_relations[$x]['relation_contact_uuid'] = '';
	}

//get the child data
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select * from v_contact_settings ";
		$sql .= "where contact_uuid = :contact_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid;
		$contact_settings = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $contact_setting_uuid
	if (empty($contact_setting_uuid)) {
		$contact_setting_uuid = uuid();
	}

//add an empty row
	if (empty($contact_settings) || count($contact_settings) == 0) {
		$x = !empty($contact_settings) ? count($contact_settings) : 0;
		$contact_settings[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_settings[$x]['contact_uuid'] = $contact_uuid ?? null;
		$contact_settings[$x]['contact_setting_uuid'] = uuid();
		$contact_settings[$x]['contact_setting_category'] = '';
		$contact_settings[$x]['contact_setting_subcategory'] = '';
		$contact_settings[$x]['contact_setting_name'] = '';
		$contact_settings[$x]['contact_setting_value'] = '';
		$contact_settings[$x]['contact_setting_order'] = '';
		$contact_settings[$x]['contact_setting_enabled'] = true;
		$contact_settings[$x]['contact_setting_description'] = '';
	}

//get the contact attachments
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select *, length(decode(attachment_content,'base64')) as attachment_size from v_contact_attachments ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_uuid = :contact_uuid ";
		$sql .= "order by attachment_primary desc, attachment_filename asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_uuid'] = $contact_uuid;
		$contact_attachments = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the child data
	if (!empty($contact_uuid) && is_uuid($contact_uuid)) {
		$sql = "select * from v_contact_times ";
		$sql .= "where contact_uuid = :contact_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid;
		$contact_times = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $contact_time_uuid
	if (empty($contact_time_uuid)) {
		$contact_time_uuid = uuid();
	}

//add an empty row
	if (empty($contact_times)) {
		$x = !empty($contact_times) ? count($contact_times) : 0;
		$contact_times[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_times[$x]['contact_uuid'] = $contact_uuid ?? null;
		$contact_times[$x]['contact_time_uuid'] = uuid();
	}

//get the contact notes
	$sql = "select * from v_contact_notes ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by last_mod_date desc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_uuid ?? null;
	$contact_notes = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row
	if (empty($contact_times)) {
		$x = !empty($contact_times) ? count($contact_times) : 0;
		$contact_times[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$contact_times[$x]['contact_uuid'] = $contact_uuid ?? null;
		$contact_times[$x]['contact_time_uuid'] = uuid();
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-contact-edit'];
	require_once "resources/header.php";

?>

<script type="text/javascript">
	function get_contacts(element_id, id, search) {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				//create a handle for the contact select object
				select = document.getElementById(element_id);

				//remove current options
				while (select.options.length > 0) {
					select.remove(0);
				}

				//add an empty row
				//select.add(new Option('', ''));

				//add new options from the json results
				obj = JSON.parse(this.responseText);
				for (var i=0; i < obj.length; i++) {
					select.add(new Option(obj[i].name, obj[i].id));
				}
			}
		};
		if (search) {
			xhttp.open("GET", "/core/contacts/contact_json.php?search="+search, true);
		}
		else {
			xhttp.open("GET", "/core/contacts/contact_json.php", true);
		}
		xhttp.send();
	}
</script>

<?php

//determine qr branding
	if (!empty($settings->get('theme', 'qr_brand_image')) && $settings->get('theme', 'qr_brand_type') == 'image') {
		echo "<img id='img-buffer' style='display: none;' src='".$settings->get('theme', 'qr_brand_image')."'>";
		$qr_option = "image: $('#img-buffer')[0],";
		$qr_mode = '4';
		$qr_size = '0.2';
	}
	elseif (empty($settings->get('theme', 'qr_brand_image')) && !empty($settings->get('theme', 'qr_brand_type')) && $settings->get('theme', 'qr_brand_type') == 'image') {
		$qr_option = '';
		$qr_mode = '3';
		$qr_size = '0';
	}
	elseif (!empty($settings->get('theme', 'qr_brand_image')) && $settings->get('theme', 'qr_brand_type') == 'text') {
		$qr_option = 'label: "'.$settings->get('theme', 'qr_brand_text').'"';
		$qr_mode = '2';
		$qr_size = '0.05';
	}
	elseif (!empty($settings->get('theme', 'qr_brand_image')) && $settings->get('theme', 'qr_brand_type') == 'none') {
		$qr_option = '';
		$qr_mode = '3';
		$qr_size = '0';
	}
	else {
		echo "<img id='img-buffer' style='display: none;' src='".PROJECT_PATH."/themes/".$settings->get('domain', 'template', 'default')."/images/qr_code.png'>";
		$qr_option = "image: $('#img-buffer')[0],";
		$qr_mode = '4';
		$qr_size = '0.2';
	}

//qr code generation
	$_GET['type'] = "text";
	$qr_vcard = true;
	include "contacts_vcard.php";
	echo "<input type='hidden' id='qr_vcard' value=\"".$qr_vcard."\">";
	echo "<style>";
	echo "	#qr_code_container {";
	echo "		z-index: 999999; ";
	echo "		position: fixed; ";
	echo "		left: 0; ";
	echo "		top: 0; ";
	echo "		right: 0; ";
	echo "		bottom: 0; ";
	echo "		text-align: center; ";
	echo "		vertical-align: middle;";
	echo "	}";
	echo "	#qr_code {";
	echo "		display: block; ";
	echo "		width: 100%; ";
	echo "		max-width: 650px; ";
	echo "		height: auto; ";
	echo "		-webkit-box-shadow: 0px 1px 20px #888; ";
	echo "		-moz-box-shadow: 0px 1px 20px #888; ";
	echo "		box-shadow: 0px 1px 20px #888;";
	echo "	}";
	echo "	#qr_code > img {";
	echo "		width: 100%; ";
	echo "		max-width: 650px; ";
	echo "		height: auto; ";
	echo "	}";
	echo "</style>";
	echo "<script src='".PROJECT_PATH."/resources/jquery/jquery-qrcode.min.js'></script>";
	echo "<script language='JavaScript' type='text/javascript'>";
	echo "	$(document).ready(function() {";
	echo "		$('#qr_code').qrcode({ ";
	echo "			render: 'image', ";
	echo "			minVersion: 6, ";
	echo "			maxVersion: 40, ";
	echo "			ecLevel: 'H', ";
	echo "			size: 650, ";
	echo "			radius: 0.2, ";
	echo "			quiet: 6, ";
	echo "			background: '#fff', ";
	echo "			mode: ".$qr_mode.", ";
	echo "			mSize: ".$qr_size.", ";
	echo "			mPosX: 0.5, ";
	echo "			mPosY: 0.5, ";
	echo "			text: document.getElementById('qr_vcard').value, ";
	echo "			".$qr_option;
	echo "		});";
	echo "	});";
	echo "</script>";

//styles and attachment viewing layer
	if (permission_exists('contact_attachment_view') && !empty($contact_attachments) && is_array($contact_attachments)) {
		echo "<style>\n";
		echo "	#contact_attachment_layer {\n";
		echo "		z-index: 999999;\n";
		echo "		position: fixed;\n";
		echo "		left: 0px;\n";
		echo "		top: 0px;\n";
		echo "		right: 0px;\n";
		echo "		bottom: 0px;\n";
		echo "		text-align: center;\n";
		echo "		vertical-align: middle;\n";
		echo "	}\n";
		echo "</style>\n";
		echo "<div id='contact_attachment_layer' style='display: none;'></div>\n";
	}

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='contact_uuid' value='".escape($contact_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-contact-edit']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'contact_view.php?id='.$contact_uuid]);
	if ($action == 'update') {
		if (permission_exists('contact_phone_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('contact_phone_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}

	//add edit
	if (isset($id)) {
		if (permission_exists('contact_time_add')) {
			//detect timer state (and start time)
			$sql = "select ";
			$sql .= "time_start ";
			$sql .= "from v_contact_times ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and user_uuid = :user_uuid ";
			$sql .= "and contact_uuid = :contact_uuid ";
			$sql .= "and time_start is not null ";
			$sql .= "and time_stop is null ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
			$parameters['contact_uuid'] = $contact_uuid;
			$time_start = $database->select($sql, $parameters, 'column');
			$btn_style = $time_start ? 'color: #fff; background-color: #3693df; background-image: none;' : null;
			unset($sql, $parameters);
			echo button::create(['type'=>'button','label'=>$text['button-timer'],'icon'=>'clock','style'=>$btn_style,'title'=>$time_start,'collapse'=>'hide-sm-dn','onclick'=>"window.open('contact_timer.php?domain_uuid=".urlencode($domain_uuid)."&contact_uuid=".urlencode($contact_uuid)."','contact_time_".escape($contact_uuid)."','width=300, height=375, top=30, left='+(screen.width - 350)+', menubar=no, scrollbars=no, status=no, toolbar=no, resizable=no');"]);
		}

		echo button::create(['type'=>'button','label'=>$text['button-qr_code'],'icon'=>'qrcode','collapse'=>'hide-sm-dn','onclick'=>"$('#qr_code_container').fadeIn(400);"]);
		echo button::create(['type'=>'button','label'=>$text['button-vcard'],'icon'=>'address-card','collapse'=>'hide-sm-dn','link'=>'contacts_vcard.php?id='.urlencode($contact_uuid ?? null).'&type=download']);
	}
	//add edit
	//if (isset($id)) {
		//echo button::create(['type'=>'button','label'=>$text['button-notes'],'icon'=>'','collapse'=>'hide-xs','style'=>'margin-right: 0px;','link'=>"contact_notes.php?id=$id"]);
	//}

	//add user
	if (isset($id) && permission_exists('user_edit')) {
		echo button::create(['type'=>'button','label'=>$text['button-user'],'icon'=>'user','collapse'=>'hide-sm-dn','link'=>'../../core/users/user_edit.php?id='.urlencode($contact_user_uuid ?? '')]);
	}
	if (
		$action == "update" && (
		permission_exists('contact_phone_add') ||
		permission_exists('contact_address_add') ||
		permission_exists('contact_email_add') ||
		permission_exists('contact_url_add') ||
		permission_exists('contact_relation_add') ||
		permission_exists('contact_note_add') ||
		permission_exists('contact_time_add') ||
		permission_exists('contact_setting_add') ||
		permission_exists('contact_attachment_add')
		)) {
		echo 		"<select class='formfld' style='width: auto; margin-left: 15px;' id='select_add' onchange=\"document.location.href='contact_' + (this.options[this.selectedIndex].value) + '_edit.php?contact_uuid=".urlencode($contact_uuid ?? null)."';\">\n";
		echo "			<option value=''>".$text['button-add']."...</option>\n";
		if (permission_exists('contact_phone_add')) { echo "<option value='phone'>".$text['label-phone_number']."</option>\n"; }
		if (permission_exists('contact_address_add')) { echo "<option value='address'>".$text['label-address_address']."</option>\n"; }
		if (permission_exists('contact_email_add')) { echo "<option value='email'>".$text['label-email']."</option>\n"; }
		if (permission_exists('contact_url_add')) { echo "<option value='url'>".$text['label-url']."</option>\n"; }
		if (permission_exists('contact_relation_add')) { echo "<option value='relation'>".$text['label-contact_relation_label']."</option>\n"; }
		if (permission_exists('contact_note_add')) { echo "<option value='note'>".$text['label-contact_note']."</option>\n"; }
		if (permission_exists('contact_time_add')) { echo "<option value='time'>".$text['label-time_time']."</option>\n"; }
		if (permission_exists('contact_setting_add')) { echo "<option value='setting'>".$text['label-setting']."</option>\n"; }
		if (permission_exists('contact_attachment_add')) { echo "<option value='attachment'>".$text['label-attachment']."</option>\n"; }
		echo "		</select>";
	}
	if (
		$action == "update" && (
		permission_exists('contact_delete') ||
		permission_exists('contact_user_delete') ||
		permission_exists('contact_group_delete') ||
		permission_exists('contact_phone_delete') ||
		permission_exists('contact_address_delete') ||
		permission_exists('contact_email_delete') ||
		permission_exists('contact_url_delete') ||
		permission_exists('contact_relation_delete') ||
		permission_exists('contact_note_delete') ||
		permission_exists('contact_time_delete') ||
		permission_exists('contact_setting_delete') ||
		permission_exists('contact_attachment_delete')
		)) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'name'=>'btn_delete','collapse'=>'hide-sm-dn','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'style'=>'margin-left: 15px;','id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-contact-edit']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('contact_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('contact_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

?>

<style>
* {
	box-sizing: border-box;
	}

input[type=text], select, textarea {
	width: 100%;
	padding: 12px;
	border: 1px solid #ccc;
	border-radius: 4px;
	resize: vertical;
	}

label {
	padding: 12px 12px 12px 0;
	display: inline-block;
	}

input[type=submit] {
	background-color: #4CAF50;
	color: white;
	padding: 12px 20px;
	border: none;
	border-radius: 100px;
	cursor: pointer;
	float: right;
	}

input[type=submit]:hover {
	background-color: #45a049;
	}

option:first-child {
	color: #ccc;
	}

.container {
	border-radius: 5px;
	background-color: #f2f2f2;
	padding: 20px;
	}

.col-25 {
	float: left;
	width: 25%;
	margin-top: 6px;
	}

.col-75 {
	float: left;
	width: 75%;
	margin-top: 6px;
	}

/* clear floats after columns */
.row:after {
	content: "";
	display: table;
	clear: both;
	}

/* xs */
@media screen and (max-width: 600px) {
	div.form_grid {
		width: 100%;
	}

	div.form_set {
		width: 100% !important;
		padding: 20px;
	}
	}

/* sm+ */
@media screen and (min-width: 601px) {
	div.form_grid {
		width: calc(100% + 20px);
		}

	div.form_set {
		width: calc(100% - 20px);
		padding: 20px;
		}
	}

div.card {
	margin-bottom: 20px;
	}

/* xs - make the two columns stack on top of each other instead of next to each other */
@media screen and (max-width: 600px) {
	.col-25, .col-75, input[type=submit] {
		width: 100%;
		margin-top: 0;
		}
	}

@media screen and (max-width: 941px) {
	.empty_row {
		display: none;
		}
	}
</style>

<?php

echo "<div class='form_grid'>\n";

echo "	<div class='form_set card'>\n";
echo "		<div class='heading'>\n";
echo "			<b>".$text['label-name']."</b>\n";
echo "		</div>\n";
echo "		<div style='clear: both;'></div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_organization']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<input class='formfld' type='text' name='contact_organization' placeholder='' maxlength='255' value='".escape($contact_organization)."'>\n";
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_name_prefix']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<input class='formfld' type='text' name='contact_name_prefix' placeholder='' maxlength='255' value='".escape($contact_name_prefix)."'>\n";
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_name_given']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<input class='formfld' type='text' name='contact_name_given' placeholder='' maxlength='255' value='".escape($contact_name_given)."'>\n";
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_name_middle']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<input class='formfld' type='text' name='contact_name_middle' placeholder='' maxlength='255' value='".escape($contact_name_middle)."'>\n";
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_name_family']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<input class='formfld' type='text' name='contact_name_family' placeholder='' maxlength='255' value='".escape($contact_name_family)."'>\n";
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_name_suffix']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<input class='formfld' type='text' name='contact_name_suffix' placeholder='' maxlength='255' value='".escape($contact_name_suffix)."'>\n";
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_nickname']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<input class='formfld' type='text' name='contact_nickname' placeholder='' maxlength='255' value='".escape($contact_nickname)."'>\n";
echo "		</div>\n";

echo "		<div class='label empty_row' style='grid-row: 10 / span 99;'>\n";
echo "			&nbsp;\n";
echo "		</div>\n";
echo "		<div class='field empty_row' style='grid-row: 10 / span 99;'>\n";
echo "			&nbsp;\n";
echo "		</div>\n";
echo "	</div>\n";

echo "	<div class='form_set card'>\n";
echo "		<div class='heading'>\n";
echo "			<b>".$text['option-other']."</b>\n";
echo "		</div>\n";
echo "		<div style='clear: both;'></div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_type']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
if (!empty($_SESSION["contact"]["type"])) {
	sort($_SESSION["contact"]["type"]);
	echo "	<select class='formfld' name='contact_type'>\n";
	echo "		<option value=''></option>\n";
	foreach($_SESSION["contact"]["type"] as $type) {
		echo "		<option value='".escape($type)."' ".(($type == $contact_type) ? "selected='selected'" : null).">".escape($type)."</option>\n";
	}
	echo "	</select>\n";
}
else {
	echo "	<select class='formfld' name='contact_type'>\n";
	echo "		<option value=''></option>\n";
	echo "		<option value='customer' ".(!empty($contact_type) && $contact_type == "customer" ? "selected='selected'" : null).">".$text['option-contact_type_customer']."</option>\n";
	echo "		<option value='contractor' ".(!empty($contact_type) && $contact_type == "contractor" ? "selected='selected'" : null).">".$text['option-contact_type_contractor']."</option>\n";
	echo "		<option value='friend' ".(!empty($contact_type) && $contact_type == "friend" ? "selected='selected'" : null).">".$text['option-contact_type_friend']."</option>\n";
	echo "		<option value='lead' ".(!empty($contact_type) && $contact_type == "lead" ? "selected='selected'" : null).">".$text['option-contact_type_lead']."</option>\n";
	echo "		<option value='member' ".(!empty($contact_type) && $contact_type == "member" ? "selected='selected'" : null).">".$text['option-contact_type_member']."</option>\n";
	echo "		<option value='family' ".(!empty($contact_type) && $contact_type == "family" ? "selected='selected'" : null).">".$text['option-contact_type_family']."</option>\n";
	echo "		<option value='subscriber' ".(!empty($contact_type) && $contact_type == "subscriber" ? "selected='selected'" : null).">".$text['option-contact_type_subscriber']."</option>\n";
	echo "		<option value='supplier' ".(!empty($contact_type) && $contact_type == "supplier" ? "selected='selected'" : null).">".$text['option-contact_type_supplier']."</option>\n";
	echo "		<option value='provider' ".(!empty($contact_type) && $contact_type == "provider" ? "selected='selected'" : null).">".$text['option-contact_type_provider']."</option>\n";
	echo "		<option value='user' ".(!empty($contact_type) && $contact_type == "user" ? "selected='selected'" : null).">".$text['option-contact_type_user']."</option>\n";
	echo "		<option value='volunteer' ".(!empty($contact_type) && $contact_type == "volunteer" ? "selected='selected'" : null).">".$text['option-contact_type_volunteer']."</option>\n";
	echo "	</select>\n";
}
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_title']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
if (!empty($_SESSION['contact']['contact_title'])) {
	echo "		<select class='formfld' name='contact_title'>\n";
	echo "			<option value=''></option>\n";
	sort($_SESSION['contact']['contact_title']);
	foreach($_SESSION['contact']['contact_title'] as $row) {
		echo "			<option value='".escape($row)."' ".(($row == $contact_title) ? "selected='selected'" : null).">".escape(ucwords($row))."</option>\n";
	}
	echo "		</select>\n";
}
else {
	echo "		<input class='formfld' type='text' name='contact_title' placeholder='' maxlength='255' value='".escape($contact_title)."'>\n";
}
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_role']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
if (!empty($_SESSION['contact']['contact_role'])) {
	echo "		<select class='formfld' name='contact_role'>\n";
	echo "			<option value=''>".$text['label-contact_category']."</option>\n";
	sort($_SESSION['contact']['contact_role']);
	foreach($_SESSION['contact']['contact_role'] as $row) {
		echo "			<option value='".escape($row)."' ".(($row == $contact_role) ? "selected='selected'" : null).">".escape(ucwords($row))."</option>\n";
	}
	echo "		</select>\n";
}
else {
	echo "		<input class='formfld' type='text' name='contact_role'  placeholder='' maxlength='255' value='".escape($contact_role)."'>\n";
}
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_category']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
if (!empty($_SESSION['contact']['contact_category'])) {
	echo "	<select class='formfld' name='contact_category'>\n";
	echo "		<option value=''></option>\n";
	sort($_SESSION['contact']['contact_category']);
	foreach($_SESSION['contact']['contact_category'] as $row) {
		echo "		<option value='".escape($row)."' ".(($row == $contact_category) ? "selected='selected'" : null).">".escape(ucwords($row))."</option>\n";
	}
	echo "	</select>\n";
}
else {
	echo "	<input class='formfld' type='text' name='contact_category' placeholder='' maxlength='255' value='".escape($contact_category)."'>\n";
}
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_time_zone']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<select class='formfld' id='contact_time_zone' name='contact_time_zone' style=''>\n";
echo "				<option value=''></option>\n";
//$list = DateTimeZone::listAbbreviations();
$time_zone_identifiers = DateTimeZone::listIdentifiers();
$previous_category = '';
$x = 0;
foreach ($time_zone_identifiers as $key => $val) {
	$time_zone = explode("/", $val);
	$category = $time_zone[0];
	if ($category != $previous_category) {
		if ($x > 0) {
			echo "			</optgroup>\n";
		}
		echo "			<optgroup label='".$category."'>\n";
	}
	if (!empty($val)) {
		$time_zone_offset = get_time_zone_offset($val)/3600;
		$time_zone_offset_hours = floor($time_zone_offset);
		$time_zone_offset_minutes = ($time_zone_offset - $time_zone_offset_hours) * 60;
		$time_zone_offset_minutes = number_pad($time_zone_offset_minutes, 2);
		if ($time_zone_offset > 0) {
			$time_zone_offset_hours = number_pad($time_zone_offset_hours, 2);
			$time_zone_offset_hours = "+".$time_zone_offset_hours;
		}
		else {
			$time_zone_offset_hours = str_replace("-", "", $time_zone_offset_hours);
			$time_zone_offset_hours = "-".number_pad($time_zone_offset_hours, 2);
		}
	}
	echo "				<option value='".$val."' ".(!empty($contact_time_zone) && $val == $contact_time_zone ? "selected='selected'" : null).">(UTC ".$time_zone_offset_hours.":".$time_zone_offset_minutes.") ".$val."</option>\n";
	$previous_category = $category;
	$x++;
}
echo "			</select>\n";
echo "		</div>\n";

echo "		<div class='label'>\n";
echo "			".$text['label-contact_note']."\n";
echo "		</div>\n";
echo "		<div class='field no-wrap'>\n";
echo "			<textarea class='formfld' style='width: 100%; height: 100%;' name='contact_note'>".$contact_note."</textarea>\n";
echo "		</div>\n";

echo "		<div class='label empty_row' style='grid-row: 8 / span 99;'>\n";
echo "			&nbsp;\n";
echo "		</div>\n";
echo "		<div class='field empty_row' style='grid-row: 8 / span 99;'>\n";
echo "			&nbsp;\n";
echo "		</div>\n";

echo "	</div>\n";
unset($contact_note);

if ($settings->get('contact', 'permissions', false)) {
	if (permission_exists('contact_user_view') || permission_exists('contact_group_view')) {
		echo "	<div class='form_set card'>\n";
		echo "		<div class='heading'>\n";
		echo "			<b>".$text['label-permissions']."</b>\n";
		echo "		</div>\n";
		echo "		<div style='clear: both;'></div>\n";

		if (permission_exists('contact_user_edit')) {
			echo "		<div class='label' valign='top'>".$text['label-users']."</div>\n";
			echo "		<div class='field no-wrap' align='left'>";
			if ($action == "update" && !empty($contact_users_assigned)) {
				echo "			<div class='vtable'>".$text['label-username']."\n";
				if ($contact_users_assigned && permission_exists('contact_user_delete')) {
					//echo "			<div class='edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_users', 'delete_toggle_users');\" onmouseout=\"swap_display('delete_label_users', 'delete_toggle_users');\">\n";
					echo "			<div style='float: right;'\">\n";
					echo "				<span>".$text['label-delete']."</span>\n";
					//echo "				<span id='delete_label_users'>".$text['label-delete']."</span>\n";
					//echo "				<span id='delete_toggle_users'><input type='checkbox' id='checkbox_all_users' name='checkbox_all' onclick=\"edit_all_toggle('users');\"></span>\n";
					echo "			</div>\n";
				}
				echo "		</div>\n";
				foreach ($contact_users_assigned as $x => $field) {
					echo "		<div class='vtable'>".escape($field['username'])."\n";
					if ($contact_users_assigned && permission_exists('contact_user_delete')) {
						if (is_uuid($field['contact_user_uuid'])) {
							echo "		<div style='text-align: center; padding-bottom: 3px; float: right; margin-right: 10px;'>\n";
							//echo "			<input type='checkbox' name='contact_users_delete[".$x."][checked]' value='true' class='chk_delete checkbox_users' onclick=\"edit_delete_action('users');\">\n";
							//echo "			<input type='hidden' name='contact_users_delete[".$x."][uuid]' value='".escape($field['contact_user_uuid'])."' />\n";
							echo "			<input type='checkbox' name='contact_users[".$x."][checked]' value='true' class='chk_delete checkbox_users' onclick=\"edit_delete_action('users');\">\n";
							echo "			<input type='hidden' name='contact_users[".$x."][uuid]' value='".escape($field['contact_user_uuid'])."' />\n";
							echo "			<input type='hidden' name='contact_users[$x][domain_uuid]' value=\"".escape($_SESSION['domain_uuid'])."\">\n";
							echo "			<input type='hidden' name='contact_users[$x][contact_user_uuid]' value='".escape($field['contact_user_uuid'])."' />\n";
							echo "			<input type='hidden' name='contact_users[$x][contact_uuid]' value='".escape($field['contact_uuid'])."' />\n";
						}
						else {
							echo "		<div>\n";
						}
						echo "	</div>\n";
					}
					echo "	</div>\n";
				}
			}
			if (permission_exists('contact_user_add')) {
				echo "		<div class='vtable' style='border-bottom: none;'>\n";
				echo "			<select name='contact_user_uuid' class='formfld' style='width: 112px;'>\n";
				echo "				<option value=''></option>\n";
				foreach ($users as $field) {
					if (!empty($contact_users_assigned) && in_array($field['user_uuid'], array_column($contact_users_assigned, 'user_uuid'))) { continue; } //skip users already assigned
					echo "					<option value='".escape($field['user_uuid'])."'>".escape($field['username'])."</option>\n";
				}
				echo "			</select>\n";
				if ($action == "update") {
					echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add')]);
				}
				unset($users);
				echo "		</div>\n";
			}
			echo "			".$text['description-users']."\n";
			echo "		</div>\n";
		}

		if (permission_exists('contact_group_view')) {
			echo "	<div class='label'>".$text['label-groups']."</div>";
			echo "	<div class='field no-wrap'>";
			if (!empty($contact_groups_assigned)) {
				echo "		<div class='vtable'>".$text['label-group']."\n";
				if ($contact_groups_assigned && permission_exists('contact_group_delete')) {
					//echo "		<div class='edit_delete_checkbox_all' style='float: right;' onmouseover=\"swap_display('delete_label_groups', 'delete_toggle_groups');\" onmouseout=\"swap_display('delete_label_groups', 'delete_toggle_groups');\">\n";
					echo "		<div style='float: right;'\">\n";
					echo "			<span>".$text['label-delete']."</span>\n";
					//echo "			<span id='delete_label_groups'>".$text['label-delete']."</span>\n";
					//echo "			<span id='delete_toggle_groups' style='margin-right: 10px;'><input type='checkbox' id='checkbox_all_groups' name='checkbox_all' onclick=\"edit_all_toggle('groups');\"></span>\n";
					echo "		</div>\n";
				}
				echo "		</div>\n";
				foreach ($contact_groups_assigned as $x => $field) {
					if (!empty($field['group_name'])) {
						echo "		<div class='vtable'>".escape($field['group_name'])."\n";
						if (permission_exists('contact_group_delete')) {
							if (is_uuid($field['contact_group_uuid'])) {
								echo "		<div style='text-align: center; padding-bottom: 3px; float: right; margin-right: 10px;'>";
								//echo "			<input type='checkbox' name='contact_groups_delete[".$x."][checked]' value='true' class='chk_delete checkbox_groups' onclick=\"edit_delete_action('groups');\">\n";
								//echo "			<input type='hidden' name='contact_groups_delete[".$x."][uuid]' value='".escape($field['contact_group_uuid'])."' />\n";
								echo "			<input type='checkbox' name='contact_groups[".$x."][checked]' value='true' class='chk_delete checkbox_groups' onclick=\"edit_delete_action('groups');\">\n";
								echo "			<input type='hidden' name='contact_groups[".$x."][uuid]' value='".escape($field['contact_group_uuid'])."' />\n";
								echo "			<input type='hidden' name='contact_groups[$x][domain_uuid]' value=\"".escape($_SESSION['domain_uuid'])."\">\n";
								echo "			<input type='hidden' name='contact_groups[$x][contact_group_uuid]' value='".escape($field['contact_group_uuid'])."' />\n";
								echo "			<input type='hidden' name='contact_groups[$x][contact_uuid]' value='".escape($contact_uuid)."' />\n";
							}
							else {
								echo "		<div>";
							}
							echo "		</div>\n";
						}
						echo "		</div>\n";
					}
				}
			}

			if (permission_exists('contact_group_add')) {
				if (!empty($contact_groups_available)) {
					echo "	<div class='vtable' style='border-bottom: none;'>\n";
					echo "		<select name='contact_group_uuid' class='formfld' style='width: 112px;'>\n";
					echo "			<option value=''></option>\n";
					foreach ($contact_groups_available as $field) {
						if ($field['group_name'] == "superadmin" && !if_group("superadmin")) { continue; }	//only show superadmin group to superadmins
						if ($field['group_name'] == "admin" && (!if_group("superadmin") && !if_group("admin"))) { continue; }	//only show admin group to admins
						echo "			<option value='".escape($field['group_uuid'])."'>".escape($field['group_name'])."</option>\n";
					}
					echo "		</select>";
					if ($action == "update") {
						echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add')]);
					}
					echo "	</div>\n";
				}
			}
			echo "		".$text['description-groups']."\n";
			echo "	</div>\n";
		}

		echo "		<div class='label empty_row' style='grid-row: 4 / span 99;'>\n";
		echo "			&nbsp;\n";
		echo "		</div>\n";
		echo "		<div class='field empty_row' style='grid-row: 4 / span 99;'>\n";
		echo "		</div>\n";

		echo "	</div>\n";
	}
}

if (permission_exists('contact_phone_view')) {

	echo "<script type=\"text/javascript\">\n";
	echo "function send_cmd(url) {\n";
	echo "	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari\n";
	echo "		xmlhttp=new XMLHttpRequest();\n";
	echo "	}\n";
	echo "	else {// code for IE6, IE5\n";
	echo "		xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
	echo "	}\n";
	echo "	xmlhttp.open(\"GET\",url,true);\n";
	echo "	xmlhttp.send(null);\n";
	echo "	document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
	echo "}\n";
	echo "</script>\n";

	$x = 0;
	foreach($contact_phones as $row) {
		echo "	<div class='form_set card'>\n";
		echo "		<div class='heading'>\n";
		echo "			<b style='float: left;'>".$text['label-phone_numbers']."</b>\n";
		if (permission_exists('contact_phone_delete')) {
			echo "			<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
			echo "				<input type='checkbox' name='contact_phones[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_phones' value='true' onclick=\"edit_delete_action('phones');\">\n";
			echo "				<input type='hidden' name='contact_phones[$x][uuid]' value='".escape($row['contact_phone_uuid'])."' />\n";
			echo "			</div>\n";
		}
		echo "			<div class='button no-link' style='float: left; margin-top: 1px; margin-left: 8px;'>\n";
		echo "				<a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php?caller_id_number=".urlencode($row['phone_number'] ?? '')."'>\n";
		echo "					<i class='fas fa-search fa-fw' style='color: ".$body_text_color."; float: left; margin-top: 7px; margin-left: 3px; margin-right: 3px;' title=\"".$text['button-cdr']."\"></i>\n";
		echo "				</a>\n";

		$call = "send_cmd('";
		$call .= PROJECT_PATH."/app/click_to_call/click_to_call.php";
		$call .= "?src_cid_name=".urlencode($row['phone_number'] ?? '');
		$call .= "&src_cid_number=".urlencode($row['phone_number'] ?? '');
		$call .= "&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'] ?? '');
		$call .= "&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'] ?? '');
		$call .= "&src=".urlencode($_SESSION['user']['extension'][0]['user'] ?? '');
		$call .= "&dest=".urlencode($row['phone_number'] ?? '');
		$call .= "&rec=false";
		$call .= "&ringback=us-ring";
		$call .= "&auto_answer=true";
		$call .= "');";

		echo "				<a href='' onclick=\"".$call."\">\n";
		echo "					<i class='fas fa-phone fa-fw' style='color: ".$body_text_color."; float: left; margin-top: 7px; margin-left: 7px;' title=\"".urlencode($row['phone_number'] ?? '')."\"></i>\n";
		echo "				</a>\n";

		echo "			</div>\n";
		echo "		</div>\n";
		echo "		<div style='clear: both;'>\n";
		if ($row['phone_primary'] === true) {
			echo "		<i class='fa-solid fa-star fa-sm' style='color: ".$body_text_color."; float: right; margin-top: 7px; margin-left: 8px;' title=\"".$text['label-primary']."\"></i>\n";
		}
		echo "		</div>\n";

		echo "		<input type='hidden' name='contact_phones[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
		echo "		<input type='hidden' name='contact_phones[$x][contact_uuid]' value=\"".escape($row["contact_uuid"])."\">\n";
		echo "		<input type='hidden' name='contact_phones[$x][contact_phone_uuid]' value=\"".escape($row["contact_phone_uuid"])."\">\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-phone_label']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<select class='formfld' name='contact_phones[$x][phone_label]' style=''>\n";
		echo "				<option value=''></option>\n";
		if ($row['phone_label'] == "work") {
			echo "				<option value='work' selected='selected'>".$text['option-work']."</option>\n";
		}
		else {
			echo "				<option value='work'>".$text['option-work']."</option>\n";
		}
		if ($row['phone_label'] == "home") {
			echo "				<option value='home' selected='selected'>".$text['option-home']."</option>\n";
		}
		else {
			echo "				<option value='home'>".$text['option-home']."</option>\n";
		}
		if ($row['phone_label'] == "mobile") {
			echo "				<option value='mobile' selected='selected'>".$text['option-mobile']."</option>\n";
		}
		else {
			echo "				<option value='mobile'>".$text['option-mobile']."</option>\n";
		}
		if ($row['phone_label'] == "main") {
			echo "				<option value='main' selected='selected'>".$text['option-main']."</option>\n";
		}
		else {
			echo "				<option value='main'>".$text['option-main']."</option>\n";
		}
		if ($row['phone_label'] == "billing") {
			echo "				<option value='billing' selected='selected'>".$text['option-billing']."</option>\n";
		}
		else {
			echo "				<option value='billing'>".$text['option-billing']."</option>\n";
		}
		if ($row['phone_label'] == "fax") {
			echo "				<option value='fax' selected='selected'>".$text['option-fax']."</option>\n";
		}
		else {
			echo "				<option value='fax'>".$text['option-fax']."</option>\n";
		}
		if ($row['phone_label'] == "voicemail") {
			echo "				<option value='voicemail' selected='selected'>".$text['option-voicemail']."</option>\n";
		}
		else {
			echo "				<option value='voicemail'>".$text['option-voicemail']."</option>\n";
		}
		if ($row['phone_label'] == "text") {
			echo "				<option value='text' selected='selected'>".$text['option-text']."</option>\n";
		}
		else {
			echo "				<option value='text'>".$text['option-text']."</option>\n";
		}
		if ($row['phone_label'] == "other") {
			echo "				<option value='other' selected='selected'>".$text['option-other']."</option>\n";
		}
		else {
			echo "				<option value='other'>".$text['option-other']."</option>\n";
		}
		echo "			</select>\n";
		//echo 				$text['description-phone_label']."\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-phone_type']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap' style='display: flex; flex-wrap: wrap; gap: 3px;'>\n";
		echo "			<label style='padding-top: 2px; margin: 0;'><input type='checkbox' name='contact_phones[$x][phone_type_voice]' id='phone_type_voice' value='1' ".(($row['phone_type_voice']) ? "checked='checked'" : null)."> ".$text['label-voice']."</label>\n";
		echo "			<label style='padding-top: 2px; margin: 0;'><input type='checkbox' name='contact_phones[$x][phone_type_fax]' id='phone_type_fax' value='1' ".(($row['phone_type_fax']) ? "checked='checked'" : null)."> ".$text['label-fax']."</label>\n";
		echo "			<label style='padding-top: 2px; margin: 0;'><input type='checkbox' name='contact_phones[$x][phone_type_video]' id='phone_type_video' value='1' ".(($row['phone_type_video']) ? "checked='checked'" : null)."> ".$text['label-video']."</label>\n";
		echo "			<label style='padding-top: 2px; margin: 0;'><input type='checkbox' name='contact_phones[$x][phone_type_text]' id='phone_type_text' value='1' ".(($row['phone_type_text']) ? "checked='checked'" : null)."> ".$text['label-text']."</label>\n";
		echo "			<br />\n";
		//echo 			$text['description-phone_type']."\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-phone_speed_dial']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_phones[$x][phone_speed_dial]' placeholder='' maxlength='255' style='' value=\"".escape($row["phone_speed_dial"])."\">\n";
		//echo 				$text['description-phone_extension']."\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-phone_country_code']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_phones[$x][phone_country_code]' placeholder='' maxlength='6' style='' value=\"".escape($row["phone_country_code"])."\">\n";
		//echo 				$text['description-phone_country_code']."\n";
		echo "		</div>\n";

		echo "		<div class='label required'>\n";
		echo "			".$text['label-phone_number']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_phones[$x][phone_number]' placeholder='' style=''  maxlength='255' style='max-width:90px;' value=\"".escape($row["phone_number"])."\">\n";
		//echo 				$text['description-phone_speed_dial']."\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-phone_extension']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_phones[$x][phone_extension]' placeholder='' style='' maxlength='255' value=\"".escape($row["phone_extension"])."\">\n";
		//echo 				$text['description-phone_number']."\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-primary']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		if ($input_toggle_style_switch) {
			echo "			<span class='switch'>\n";
		}
		echo "				<select class='formfld' id='phone_primary' name='contact_phones[$x][phone_primary]' style='width: auto;'>\n";
		echo "					<option value='true' ".($row['phone_primary'] == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "					<option value='false' ".($row['phone_primary'] == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "				</select>\n";
		if ($input_toggle_style_switch) {
			echo "				<span class='slider'></span>\n";
			echo "			</span>\n";
		}
		//echo 				$text['description-phone_primary']."\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-phone_description']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_phones[$x][phone_description]' placeholder='' maxlength='255' value=\"".escape($row["phone_description"])."\">\n";
		//echo 				$text['description-phone_description']."\n";
		echo "		</div>\n";

		echo "		<div class='label empty_row' style='grid-row: 10 / span 99;'>\n";
		echo "			&nbsp;\n";
		echo "		</div>\n";
		echo "		<div class='field empty_row' style='grid-row: 10 / span 99;'>\n";
		echo "			&nbsp;\n";
		echo "		</div>\n";

		//if (!empty($contact_phones) && @sizeof($contact_phones) > 1 && permission_exists('contact_phone_delete')) {
		//	echo "		<div class='label'>\n";
		//	echo "			".$text['label-action']."\n";
		//	echo "		</div>\n";
		//	echo "		<div class='field no-wrap'>\n";
		//	//echo "			<span id='delete_label_details'>".$text['label-action']."</span>\n";
		//	echo "			<span id='delete_toggle_details'>\n";
		//	//echo "				<input type='checkbox' name='contact_phones[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
		//	echo "				<input type='checkbox' id='checkbox_all_details' name='checkbox_all' onclick=\"edit_all_toggle('details'); checkbox_on_change(this);\">\n";
		//	echo "			</span>\n";
		//	echo "		</div>\n";
		//}

		echo "	</div>\n";
		$x++;
	}
}

if (permission_exists('contact_address_view')) {
	$x = 0;
	foreach($contact_addresses as $row) {
		echo "	<div class='form_set card'>\n";
		echo "		<div class='heading'>\n";
		echo "			<b style='float: left;'>".$text['label-addresses']."</b>\n";
		if (permission_exists('contact_address_delete')) {
			echo "			<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
			echo "				<input type='checkbox' name='contact_addresses[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_addresses' value='true' onclick=\"edit_delete_action('addresses');\">\n";
			echo "				<input type='hidden' name='contact_addresses[$x][uuid]' value='".escape($row['contact_address_uuid'])."' />\n";
			echo "			</div>\n";
		}
		echo "			<div class='button no-link' style='float: left; margin-top: 1px; margin-left: 8px;'>\n";
		$map_query = $row['address_street']." ".$row['address_extended'].", ".$row['address_locality'].", ".$row['address_region'].", ".$row['address_region'].", ".$row['address_postal_code'];
		echo " 				<a href=\"http://maps.google.com/maps?q=".urlencode($map_query)."&hl=en\" target=\"_blank\">";
		echo " 					<img src='resources/images/icon_gmaps.png' style='width: 17px; height: 17px;' alt='".$text['label-google_map']."' title='".$text['label-google_map']."'>";
		echo " 				</a>\n";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "		<div style='clear: both;'>\n";
		if ($row['address_primary'] === true) {
			echo "		<i class='fa-solid fa-star fa-sm' style='color: ".$body_text_color."; float: right; margin-top: 7px; margin-left: 8px;' title=\"".$text['label-primary']."\"></i>\n";
		}
		echo "		</div>\n";

		echo "		<input type='hidden' name='contact_addresses[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
		echo "		<input type='hidden' name='contact_addresses[$x][contact_uuid]' value=\"".escape($row["contact_uuid"])."\">\n";
		echo "		<input type='hidden' name='contact_addresses[$x][contact_address_uuid]' value=\"".escape($row["contact_address_uuid"])."\">\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-address_label']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<select class='formfld' name='contact_addresses[$x][address_label]'>\n";
		echo "				<option value=''></option>\n";
		if ($row['address_label'] == "work") {
			echo "				<option value='work' selected='selected'>".$text['option-work']."</option>\n";
		}
		else {
			echo "				<option value='work'>".$text['option-work']."</option>\n";
		}
		if ($row['address_label'] == "home") {
			echo "				<option value='home' selected='selected'>".$text['option-home']."</option>\n";
		}
		else {
			echo "				<option value='home'>".$text['option-home']."</option>\n";
		}
		if ($row['address_label'] == "mobile") {
			echo "				<option value='mobile' selected='selected'>".$text['option-mobile']."</option>\n";
		}
		else {
			echo "				<option value='mobile'>".$text['option-mobile']."</option>\n";
		}
		if ($row['address_label'] == "main") {
			echo "				<option value='main' selected='selected'>".$text['option-main']."</option>\n";
		}
		else {
			echo "				<option value='main'>".$text['option-main']."</option>\n";
		}
		if ($row['address_label'] == "billing") {
			echo "				<option value='billing' selected='selected'>".$text['option-billing']."</option>\n";
		}
		else {
			echo "				<option value='billing'>".$text['option-billing']."</option>\n";
		}

		if ($row['address_label'] == "fax") {
			echo "				<option value='fax' selected='selected'>".$text['option-fax']."</option>\n";
		}
		else {
			echo "				<option value='fax'>".$text['option-fax']."</option>\n";
		}
		if ($row['address_label'] == "pager") {
			echo "				<option value='pager' selected='selected'>".$text['option-pager']."</option>\n";
		}
		else {
			echo "				<option value='pager'>".$text['option-pager']."</option>\n";
		}
		if ($row['address_label'] == "voicemail") {
			echo "				<option value='voicemail' selected='selected'>".$text['option-voicemail']."</option>\n";
		}
		else {
			echo "				<option value='voicemail'>".$text['option-voicemail']."</option>\n";
		}
		if ($row['address_label'] == "text") {
			echo "				<option value='text' selected='selected'>".$text['option-text']."</option>\n";
		}
		else {
			echo "				<option value='text'>".$text['option-text']."</option>\n";
		}
		echo "			</select>\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-address_type']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<select class='formfld' name='contact_addresses[$x][address_type]'>\n";
		echo "				<option value=''></option>\n";
		if ($row['address_type'] == "work") {
			echo "				<option value='work' selected='selected'>".$text['option-work']."</option>\n";
		}
		else {
			echo "				<option value='work'>".$text['option-work']."</option>\n";
		}
		if ($row['address_type'] == "home") {
			echo "				<option value='home' selected='selected'>".$text['option-home']."</option>\n";
		}
		else {
			echo "				<option value='home'>".$text['option-home']."</option>\n";
		}
		if ($row['address_type'] == "domestic") {
			echo "				<option value='domestic' selected='selected'>".$text['option-dom']."</option>\n";
		}
		else {
			echo "				<option value='domestic'>".$text['option-dom']."</option>\n";
		}
		if ($row['address_type'] == "international") {
			echo "				<option value='international' selected='selected'>".$text['option-intl']."</option>\n";
		}
		else {
			echo "				<option value='international'>".$text['option-intl']."</option>\n";
		}
		if ($row['address_type'] == "postal") {
			echo "				<option value='postal' selected='selected'>".$text['option-postal']."</option>\n";
		}
		else {
			echo "				<option value='postal'>".$text['option-postal']."</option>\n";
		}
		if ($row['address_type'] == "parcel") {
			echo "				<option value='parcel' selected='selected'>".$text['option-parcel']."</option>\n";
		}
		else {
			echo "				<option value='parcel'>".$text['option-parcel']."</option>\n";
		}
		if ($row['address_type'] == "preferred") {
			echo "				<option value='preferred' selected='selected'>".$text['option-pref']."</option>\n";
		}
		else {
			echo "				<option value='preferred'>".$text['option-pref']."</option>\n";
		}
		echo "			</select>\n";
		echo "		</div>\n";

		echo "		<div class='label required'>\n";
		echo "			".$text['label-address_address']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_street]' placeholder='".$text['label-address_address']." 1' maxlength='255' value=\"".escape($row["address_street"])."\"><br />\n";
		echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_extended]' placeholder='".$text['label-address_address']." 2' maxlength='255' value=\"".escape($row["address_extended"])."\">\n";
		echo "		</div>\n";

		if (permission_exists('address_community')) {
			echo "		<div class='label'>\n";
			echo "			".$text['label-address_community']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_community]' placeholder='' maxlength='255' value=\"".escape($row["address_community"])."\">\n";
			echo "		</div>\n";
		}

		echo "		<div class='label'>\n";
		echo "			".$text['label-address_locality']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_locality]' placeholder='' maxlength='255' value=\"".escape($row["address_locality"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-address_region']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_region]' placeholder='' maxlength='255' value=\"".escape($row["address_region"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-address_postal_code']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_postal_code]' placeholder='' maxlength='255' value=\"".escape($row["address_postal_code"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-address_country']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_country]' placeholder='' maxlength='255' value=\"".escape($row["address_country"])."\">\n";
		echo "		</div>\n";

		if (permission_exists('address_latitude')) {
			echo "		<div class='label'>\n";
			echo "			".$text['label-address_latitude']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_latitude]' placeholder='".escape($text['label-address_latitude'])."' maxlength='255' value=\"".escape($row["address_latitude"])."\">\n";
			echo "		</div>\n";
		}

		if (permission_exists('address_longitude')) {
			echo "		<div class='label'>\n";
			echo "			".$text['label-address_longitude']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_longitude]' placeholder='".escape($text['label-address_longitude'])."' maxlength='255' value=\"".escape($row["address_longitude"])."\">\n";
			echo "		</div>\n";
		}

		echo "		<div class='label'>\n";
		echo "			".$text['label-primary']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		if ($input_toggle_style_switch) {
			echo "			<span class='switch'>\n";
		}
		echo "				<select class='formfld' id='address_primary' name='contact_addresses[$x][address_primary]' style='width: auto;'>\n";
		echo "					<option value='true' ".($row['address_primary'] == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "					<option value='false' ".($row['address_primary'] == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "				</select>\n";
		if ($input_toggle_style_switch) {
			echo "				<span class='slider'></span>\n";
			echo "			</span>\n";
		}
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-address_description']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<input class='formfld' type='text' name='contact_addresses[$x][address_description]' placeholder='' maxlength='255' value=\"".escape($row["address_description"])."\">\n";
		echo "		</div>\n";

		//if (!empty($contact_addresses) && @sizeof($contact_addresses) > 1 && permission_exists('contact_address_delete')) {
		//	if (is_uuid($row['contact_address_uuid'])) {
		//		echo "		<div class='label'>\n";
		//		echo "			".$text['label-action']."\n";
		//		echo "		</div>\n";
		//		echo "		<div class='field no-wrap'>\n";
		//		echo "			<input type='checkbox' name='contact_addresses[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
		//		echo "		</div>\n";
		//	}
		//}

		echo "	</div>\n";
		$x++;
	}
}

if (permission_exists('contact_email_view')) {
	$x = 0;
	foreach($contact_emails as $row) {
		echo "	<div class='form_set card'>\n";
		echo "		<div class='heading'>\n";
		echo "			<b style='float: left;'>".$text['label-emails']."</b>\n";
		if (permission_exists('contact_email_delete')) {
			echo "			<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
			echo "				<input type='checkbox' name='contact_emails[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_emails' value='true' onclick=\"edit_delete_action('emails');\">\n";
			echo "				<input type='hidden' name='contact_emails[$x][uuid]' value='".escape($row['contact_email_uuid'])."' />\n";
			echo "			</div>\n";
		}
		echo "			<div class='button no-link' style='float: left; margin-top: 1px; margin-left: 8px;'>\n";
		echo "				<a href='mailto:".escape($row['email_address'])."'>\n";
		echo "					<i class='fas fa-envelope fa-fw' style='color: ".$body_text_color."; float: left; margin-top: 5px; margin-left: 3px;' title=\"".escape($row["email_label"])."\"></i>\n";
		echo "				</a>\n";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "		<div style='clear: both;'>\n";
		if ($row['email_primary'] === true) {
			echo "		<i class='fa-solid fa-star fa-sm' style='color: ".$body_text_color."; float: right; margin-top: 7px; margin-left: 8px;' title=\"".$text['label-primary']."\"></i>\n";
		}
		echo "		</div>\n";

		echo "		<input type='hidden' name='contact_emails[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
		echo "		<input type='hidden' name='contact_emails[$x][contact_uuid]' value=\"".escape($row["contact_uuid"])."\">\n";
		echo "		<input type='hidden' name='contact_emails[$x][contact_email_uuid]' value=\"".escape($row["contact_email_uuid"])."\">\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_label']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_emails[$x][email_label]' placeholder='".escape($text['label-email_label'])."' maxlength='255' value=\"".escape($row["email_label"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label required'>\n";
		echo "			".$text['label-email_address']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_emails[$x][email_address]' placeholder='".escape($text['label-email_address'])."' maxlength='255' value=\"".escape($row["email_address"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-primary']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		if ($input_toggle_style_switch) {
			echo "			<span class='switch'>\n";
		}
		echo "				<select class='formfld' id='email_primary' name='contact_emails[$x][email_primary]' style='width: auto;'>\n";
		echo "					<option value='true' ".($row['email_primary'] == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "					<option value='false' ".($row['email_primary'] == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "				</select>\n";
		if ($input_toggle_style_switch) {
			echo "				<span class='slider'></span>\n";
			echo "			</span>\n";
		}
		//echo "				<br />\n";
		//echo 				$text['description-email_primary']."\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_description']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_emails[$x][email_description]' placeholder='' maxlength='255' value=\"".escape($row["email_description"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label empty_row' style='grid-row: 6 / span 99;'>\n";
		echo "			&nbsp;\n";
		echo "		</div>\n";
		echo "		<div class='field empty_row' style='grid-row: 6 / span 99;'>\n";
		echo "		</div>\n";

		//if (!empty($contact_emails) && @sizeof($contact_emails) > 1 && permission_exists('contact_email_delete')) {
		//	if (is_uuid($row['contact_email_uuid'])) {
		//		echo "		<div class='label'>\n";
		//		echo "			".$text['label-action']."\n";
		//		echo "		</div>\n";
		//		echo "		<div class='field no-wrap'>\n";
		//		echo "			<input type='checkbox' name='contact_emails[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
		//		echo "		</div>\n";
		//	}
		//}
		echo "	</div>\n";
		$x++;
	}
}

if (permission_exists('contact_url_view')) {
	$x = 0;
	foreach($contact_urls as $row) {
		echo "	<div class='form_set card'>\n";
		echo "		<div class='heading'>\n";
		echo "			<b style='float: left;'>".$text['label-contact_url']."</b>\n";
		if (permission_exists('contact_url_delete')) {
			echo "			<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
			echo "				<input type='checkbox' name='contact_urls[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_urls' value='true' onclick=\"edit_delete_action('urls');\">\n";
			echo "				<input type='hidden' name='contact_urls[$x][uuid]' value='".escape($row['contact_url_uuid'])."' />\n";
			echo "			</div>\n";
		}
		echo "			<div class='button no-link' style='float: left; margin-top: 1px; margin-left: 8px;'>\n";
		echo "				<a href='".escape($row['url_address'])."' target='_blank'>\n";
		echo "					<span class='fas fa-link fa-fw' style='color: ".$body_text_color."; float: left; margin-top: 7px; margin-left: 3px;' title=\"".str_replace("http://", "", str_replace("https://", "", escape($row['url_address'])))."\"></span>\n";
		echo "				</a>\n";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "		<div style='clear: both;'>\n";
		if ($row['url_primary'] === true) {
			echo "		<i class='fa-solid fa-star fa-sm' style='color: ".$body_text_color."; float: right; margin-top: 7px; margin-left: 8px;' title=\"".$text['label-primary']."\"></i>\n";
		}
		echo "		</div>\n";
		echo "			<input type='hidden' name='contact_urls[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
		echo "			<input type='hidden' name='contact_urls[$x][contact_uuid]' value=\"".escape($row["contact_uuid"])."\">\n";
		echo "			<input type='hidden' name='contact_urls[$x][contact_url_uuid]' value=\"".escape($row["contact_url_uuid"])."\">\n";
		//echo "			<td class='formfld'>\n";
		//echo "			<div class='label'>\n";
		//echo "				".$text['label-url_type']."\n";
		//echo "			</div>\n";
		//echo "			<div class='field no-wrap'>\n";
		//echo "					<input class='formfld' type='text' name='contact_urls[$x][url_type]' placeholder='".escape($text['label-url_type'])."' maxlength='255' value=\"".escape($row["url_type"])."\">\n";
		//echo "			</div>\n";
		echo "			<div class='label'>\n";
		echo "				".$text['label-url_label']."\n";
		echo "			</div>\n";
		echo "			<div class='field no-wrap'>\n";;

		//if there are no custom labels add defaults
		if (!empty($_SESSION["contact"]["url_label"])) {
			$contact_url_labels = $_SESSION["contact"]["url_label"];
		}
		else {
			$contact_url_labels[] = $text['option-work'];
			$contact_url_labels[] = $text['option-personal'];
			$contact_url_labels[] = $text['option-other'];
		}
		sort($contact_url_labels);
		foreach($contact_url_labels as $label) {
			$url_label_options[] = "<option value='".$label."' ".(($label == $row['url_label']) ? "selected='selected'" : null).">".$label."</option>";
		}
		$url_label_found = (in_array($url_label, $contact_url_labels)) ? true : false;

		echo "				<select class='formfld' ".((!empty($url_label) && !$url_label_found) ? "style='display: none;'" : "style='width: auto;'")." name='contact_urls[$x][url_label]' id='url_label' onchange=\"getElementById('url_label_custom').value='';\">\n";
		echo "					<option value=''></option>\n";

		echo 					(!empty($url_label_options)) ? implode("\n", $url_label_options) : null;
		echo "				</select>\n";
		echo "				<input type='text' class='formfld' ".((empty($url_label) || $url_label_found) ? "style='display: none;'" : null)." name='url_label_custom' id='url_label_custom' value=\"".((!$url_label_found) ? htmlentities($url_label ?? '') : null)."\">\n";
		//echo "				<input type='button' id='btn_toggle_label' class='btn' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle_custom('url_label');\">\n";
		echo "			</div>\n";

		echo "			<div class='label required'>\n";
		echo "				".$text['label-url_address']."\n";
		echo "			</div>\n";
		echo "			<div class='field no-wrap'>\n";

		echo "					<input class='formfld' type='text' name='contact_urls[$x][url_address]' placeholder='http://...' maxlength='255' value=\"".escape($row["url_address"])."\">\n";
		echo "			</div>\n";

		echo "			<div class='label'>\n";
		echo "				".$text['label-primary']."\n";
		echo "			</div>\n";
		echo "			<div class='field no-wrap'>\n";
		if ($input_toggle_style_switch) {
			echo "			<span class='switch'>\n";
		}
		echo "				<select class='formfld' id='url_primary' name='contact_urls[$x][url_primary]' style='width: auto;'>\n";
		echo "					<option value='true' ".($row['url_primary'] == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "					<option value='false' ".($row['url_primary'] == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "				</select>\n";
		if ($input_toggle_style_switch) {
			echo "				<span class='slider'></span>\n";
			echo "			</span>\n";
		}
		echo "			</div>\n";
		echo "			<div class='label'>\n";
		echo "				".$text['label-url_description']."\n";
		echo "			</div>\n";
		echo "			<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_urls[$x][url_description]' placeholder='' maxlength='255' value=\"".escape($row["url_description"])."\">\n";
		echo "			</div>\n";
		// if (!empty($contact_urls) && @sizeof($contact_urls) > 1 && permission_exists('contact_url_delete')) {
		// 	if (is_uuid($row['contact_url_uuid'])) {
		// 		echo "			<input type='checkbox' name='contact_urls[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
		// 	}
		// }
		//echo "			<br />\n";
		//echo "			".$text['description-contact_organization']."\n";
		echo "			<div class='label empty_row' style='grid-row: 6 / span 99;'>\n";
		echo "				&nbsp;\n";
		echo "			</div>\n";
		echo "			<div class='field empty_row' style='grid-row: 6 / span 99;'>\n";
		echo "			</div>\n";
		echo "	</div>\n";
		$x++;
	}
}

if (permission_exists('contact_relation_view')) {
	if (!empty($contact_relations)) {

		$x = 0;
		foreach($contact_relations as $row) {

			//get contact details and contact_name
			$sql = "select contact_uuid, contact_organization, contact_name_given, contact_name_family, contact_nickname ";
			$sql .= "from v_contacts ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and contact_uuid <> :contact_uuid ";
			$sql .= "order by contact_organization desc, contact_name_given asc, contact_name_family asc ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['contact_uuid'] = $row['contact_uuid'];
			$contacts = $database->select($sql, $parameters, 'all');
			if (!empty($contacts) && is_uuid($row['relation_contact_uuid'])) {
				foreach($contacts as $field) {
					if ($field['contact_uuid'] == $row['relation_contact_uuid']) {
						$name = array();
						if (!empty($field['contact_organization'])) { $name[] = $field['contact_organization']; }
						if (!empty($field['contact_name_family'])) { $name[] = $field['contact_name_family']; }
						if (!empty($field['contact_name_given'])) { $name[] = $field['contact_name_given']; }
						if (empty($field['contact_name_family']) && empty($field['contact_name_given']) && !empty($field['contact_nickname'])) { $name[] = $field['contact_nickname']; }
						$contact_name = implode(', ', $name);
						break;
					}
				}
			}

			echo "	<div class='form_set card'>\n";
			echo "		<div class='heading'>\n";
			echo "			<b style='float: left;'>".$text['label-contact_relation_label']."</b>\n";
			if (permission_exists('contact_relation_delete')) {
				echo "			<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
				echo "				<input type='checkbox' name='contact_relations[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_relations' value='true' onclick=\"edit_delete_action('relations');\">\n";
				echo "				<input type='hidden' name='contact_relations[$x][uuid]' value='".escape($row['contact_relation_uuid'])."' />\n";
				echo "				<input type='hidden' name='contact_relations[$x][contact_relation_uuid]' value='".escape($row['contact_relation_uuid'])."' />\n";
				echo "				<input type='hidden' name='contact_relations[$x][contact_uuid]' value='".escape($row['contact_uuid'])."' />\n";
				echo "			</div>\n";
			}
			echo "			<div class='button no-link' style='float: left; margin-top: 1px; margin-left: 8px;'>\n";
			echo "				<a href='contact_edit.php?id=".escape($row['relation_contact_uuid'])."' target='_blank'>\n";
			echo "					<span class='fas fa-user-group' style='color: ".$body_text_color."; float: left; margin-top: 7px; margin-left: 3px;'></span>\n";
			echo "				</a>\n";
			echo "			</div>\n";
			echo "		</div>\n";
			echo "		<div style='clear: both;'></div>\n";

			echo "		<div class='label required'>\n";
			echo "			".$text['label-contact_relation_label']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";


			//if there are no custom labels add defaults
			if (!empty($_SESSION["contact"]["relation_label"])) {
				$relation_labels = $_SESSION["contact"]["url_label"];
			}
			else {
				$relation_labels[] = $text['label-contact_relation_option_parent'];
				$relation_labels[] = $text['label-contact_relation_option_child'];
				$relation_labels[] = $text['label-contact_relation_option_employee'];
				$relation_labels[] = $text['label-contact_relation_option_member'];
				$relation_labels[] = $text['label-contact_relation_option_associate'];
				$relation_labels[] = $text['label-contact_relation_option_other'];
			}
			sort($relation_labels);
			foreach($relation_labels as $label) {
				$relation_label_options[] = "<option value='".escape($label)."' ".(($label == $row['relation_label']) ? "selected='selected'" : null).">".escape($label)."</option>";
			}
			$relation_label_found = (in_array($relation_label, $relation_labels)) ? true : false;
			echo "			<select class='formfld' ".((!empty($relation_label) && !$relation_label_found) ? "style='display: none;'" : "style='auto;'")." name='contact_relations[$x][relation_label]' id='relation_label' onchange=\"getElementById('relation_label_custom').value='';\">\n";
			echo "				<option value=''></option>\n";
			echo 		(!empty($relation_label_options)) ? implode("\n", $relation_label_options) : null;
			echo "			</select>\n";
			//echo "			<input type='text' class='formfld' ".((empty($relation_label) || $relation_label_found) ? "style='display: none;'" : null)." name='contact_relations[$x][relation_label_custom]' id='relation_label_custom' value=\"".((!$relation_label_found) ? htmlentities($relation_label ?? '') : null)."\">\n";
			//echo "			<input type='button' id='btn_toggle_label' class='btn' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle_custom('relation_label');\">\n";
			//echo "			<br />\n";
			//echo 				$text['description-relation_label']."\n";
			echo "		</div>\n";

			echo "		<div class='label required'>\n";
			echo "			".$text['label-contact_relation_contact']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<div id='contacts' class='field no-wrap' style=\"width: auto; display: inline;\">\n";
			echo "				<input class=\"formfld\" type=\"text\" name=\"contact_search\" placeholder=\"".$text['label-search']."\" style=\"width: calc(30% - 5px);\" onkeyup=\"get_contacts('contact_select_".$x."', 'contact_uuid', this.value);\" maxlength=\"255\" value=\"\">\n";
			echo "				<select class='formfld' style=\"width: 70%;\" id=\"contact_select_".$x."\" name=\"contact_relations[".$x."][relation_contact_uuid]\" >\n";
			echo "					<option value='".escape($row['relation_contact_uuid'])."'>".escape($contact_name ?? '')."</option>\n";
			echo "				</select>\n";
			echo "			</div>\n";
			echo "		</div>\n";

			echo "		<div class='label empty_row' style='grid-row: 4 / span 99;'>\n";
			echo "			&nbsp;\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap empty_row' style='grid-row: 4 / span 99;'>\n";
			echo "		</div>\n";

			echo "	</div>\n";
			$x++;
		}
	}
}

if (permission_exists('contact_setting_view')) {
	$x = 0;
	foreach($contact_settings as $row) {
		echo "	<div class='form_set card'>\n";
		echo "		<div class='heading'>\n";
		echo "			<b style='float: left;'>".$text['label-contact_settings']."</b>\n";
		if (permission_exists('contact_setting_delete')) {
			echo "			<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
			echo "				<input type='checkbox' name='contact_settings[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_settings' value='true' onclick=\"edit_delete_action('settings');\">\n";
			echo "				<input type='hidden' name='contact_settings[$x][uuid]' value='".escape($row['contact_setting_uuid'])."' />\n";
			echo "			</div>\n";
		}
		echo "		</div>\n";
		echo "		<div style='clear: both;'></div>\n";

		echo "		<input type='hidden' name='contact_settings[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
		echo "		<input type='hidden' name='contact_settings[$x][contact_uuid]' value=\"".escape($row["contact_uuid"])."\">\n";
		echo "		<input type='hidden' name='contact_settings[$x][contact_setting_uuid]' value=\"".escape($row["contact_setting_uuid"])."\">\n";

		echo "		<div class='label required'>\n";
		echo "			".$text['label-contact_setting_category']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_settings[$x][contact_setting_category]'  placeholder='' maxlength='255' value=\"".escape($row["contact_setting_category"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label required'>\n";
		echo "			".$text['label-contact_setting_subcategory']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_settings[$x][contact_setting_subcategory]' placeholder='' maxlength='255' value=\"".escape($row["contact_setting_subcategory"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label required'>\n";
		echo "			".$text['label-name']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_settings[$x][contact_setting_name]' placeholder='' maxlength='255' value=\"".escape($row["contact_setting_name"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-contact_setting_value']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_settings[$x][contact_setting_value]' placeholder='' maxlength='255' value=\"".escape($row["contact_setting_value"])."\">\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-order']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<select name='contact_settings[$x][contact_setting_order]' class='formfld'>\n";
		echo "				<option value=''></option>\n";
		for ($i = 0; $i <=999; $i++) {
			echo "			<option value='".str_pad($i, 3, '0', STR_PAD_LEFT)."' ".(isset($row["contact_setting_order"]) && $i == $row["contact_setting_order"] ? "selected='selected'" : null).">".str_pad($i, 3, '0', STR_PAD_LEFT)."</option>\n";
		}
		echo "			</select>\n";
		echo "		</div>\n";

		echo "		<div class='label required'>\n";
		echo "			".$text['label-enabled']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		if ($input_toggle_style_switch) {
			echo "	<span class='switch'>\n";
		}
		echo "		<select class='formfld' id='contact_setting_enabled' name='contact_settings[$x][contact_setting_enabled]' style='width: auto;'>\n";
		echo "			<option value='true' ".($row['contact_setting_enabled'] == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "			<option value='false' ".($row['contact_setting_enabled'] == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "		</select>\n";
		if ($input_toggle_style_switch) {
			echo "		<span class='slider'></span>\n";
			echo "	</span>\n";
		}
		echo "		</div>\n";
		echo "		<div class='label'>\n";
		echo "			".$text['label-description']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "				<input class='formfld' type='text' name='contact_settings[$x][contact_setting_description]' placeholder='".escape($text['label-contact_setting_description'] ?? '')."' maxlength='255' value=\"".escape($row["contact_setting_description"])."\">\n";
		echo "		</div>\n";

		// echo "		<div class='label empty_row' style='grid-row: 9 / span 99;'>\n";
		// echo "			&nbsp;\n";
		// echo "		</div>\n";
		// echo "		<div class='field no-wrap empty_row' style='grid-row: 9 / span 99;'>\n";
		// echo "		</div>\n";

		echo "	</div>\n";
		$x++;
	}
}

if (permission_exists('contact_attachment_view')) {
	$x = 0;
	foreach($contact_attachments as $row) {
		$attachment_type = strtolower(pathinfo($row['attachment_filename'], PATHINFO_EXTENSION));
		$attachment_type_label = $attachment_type == 'jpg' || $attachment_type == 'jpeg' || $attachment_type == 'gif' || $attachment_type == 'png' ? $text['label-image'] : $text['label-file'];
		echo "<div class='form_set card'>\n";
		echo "	<div class='heading'>\n";
		echo " 		<b style='float: left;'>".$text['label-attachments']."</b>\n";
		if (permission_exists('contact_attachment_delete')) {
			echo "		<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
			echo "			<input type='checkbox' name='contact_attachments[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_attachments' value='true' onclick=\"edit_delete_action('attachments');\">\n";
			echo "			<input type='hidden' name='contact_attachments[$x][uuid]' value='".escape($row['contact_attachment_uuid'])."' />\n";
			echo "			<input type='hidden' name='contact_attachments[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
			echo "			<input type='hidden' name='contact_attachments[$x][contact_attachment_uuid]' value='".escape($row['contact_attachment_uuid'])."' />\n";
			echo "			<input type='hidden' name='contact_attachments[$x][contact_uuid]' value='".escape($row['contact_uuid'])."' />\n";
			echo "		</div>\n";
		}
		echo "	</div>\n";
		echo "		<div style='clear: both;'>\n";
		if ($row['attachment_primary'] === true) {
			echo "		<i class='fa-solid fa-star fa-sm' style='color: ".$body_text_color."; float: right; margin-top: 7px; margin-left: 8px;' title=\"".$text['label-primary']."\"></i>\n";
		}
		echo "		</div>\n";

		//script
		echo "<script>\n";
		echo "	function display_attachment(id) {\n";
		echo "		$('#contact_attachment_layer').load('contact_attachment.php?id=' + id + '&action=display', function(){\n";
		echo "			$('#contact_attachment_layer').fadeIn(200);\n";
		echo "		});\n";
		echo "	}\n";
		echo "</script>\n";

		echo "	<div class='label'>\n";
		echo "		".$text['label-attachment']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap'>\n";
		$attachment_type = strtolower(pathinfo($row['attachment_filename'], PATHINFO_EXTENSION));
		//if ($action == 'update') {
			echo "<input type='hidden' name='attachment_filename' value=\"".escape($row['attachment_filename'])."\">\n";
			if ($attachment_type == 'jpg' || $attachment_type == 'jpeg' || $attachment_type == 'gif' || $attachment_type == 'png') {
				echo "<img src='data:image/".$attachment_type.";base64,".escape($row['attachment_content'])."' style='border: none; cursor: pointer; width: 100%; height: auto;' onclick=\"display_attachment('".escape($row['contact_attachment_uuid'])."');\">";
			}
			else {
				echo "<a href='contact_attachment.php?id=".escape($row['contact_attachment_uuid'])."&action=download' style='font-size: 120%;'>".escape($row['attachment_filename'])."</a>";
			}
		//}
		//else {
		//	$allowed_attachment_types = json_decode($settings->get('contact', 'allowed_attachment_types'), true);
		//	echo "	<input type='file' class='formfld' name='attachment' id='attachment' accept='.".implode(',.',array_keys($allowed_attachment_types))."'>\n";
		//	echo "	<span style='display: inline-block; margin-top: 5px; font-size: 80%;'>".strtoupper(implode(', ', array_keys($allowed_attachment_types)))."</span>";
		//}
		echo "	</div>\n";

		echo "	<div class='label'>\n";
		echo "		".$text['label-attachment_filename']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap'>\n";
		echo "		<a href='contact_attachment.php?id=".escape($row['contact_attachment_uuid'])."&action=download' style='font-size: 120%;'>".escape($row['attachment_filename'])."</a>";
		echo "	</div>\n";

		echo "	<div class='label'>\n";
		echo "		".$text['label-attachment_size']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap'>\n";
		echo 		strtoupper(byte_convert($row['attachment_size']))."\n";
		echo "	</div>\n";

		echo "	<div class='label'>\n";
		echo "		".$text['label-primary']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap'>\n";
		if ($input_toggle_style_switch) {
			echo "	<span class='switch'>\n";
		}
		echo "		<select class='formfld' id='attachment_primary' name='contact_attachments[$x][attachment_primary]' style='width: auto;'>\n";
		echo "			<option value='false' ".($row['attachment_primary'] == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "			<option value='true' ".($row['attachment_primary'] == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		</select>\n";
		if ($input_toggle_style_switch) {
			echo "		<span class='slider'></span>\n";
			echo "	</span>\n";
		}
		echo "	</div>\n";

		echo "	<div class='label'>\n";
		echo "		".$text['label-description']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap'>\n";
		echo "		<input class='formfld' type='text' name='contact_attachments[$x][attachment_description]' maxlength='255' value=\"".escape($row['attachment_description'])."\">\n";
		echo "	</div>\n";

		echo "	<div class='label empty_row' style='grid-row: 9 / span 99;'>\n";
		echo "		&nbsp;\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap empty_row' style='grid-row: 9 / span 99;'>\n";
		echo "	</div>\n";

		echo "</div>\n";
		$x++;
	}
}

if (permission_exists('contact_time_view')) {
	$x = 0;
	foreach ($contact_times as $row) {
		echo "<div class='form_set card'>\n";
		echo "	<div class='heading'>\n";
		echo " 		<b style='float: left;'>".$text['header_contact_times']."</b>\n";
		if (permission_exists('contact_time_delete')) {
			echo "		<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
			echo "			<input type='checkbox' name='contact_times[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_times' value='true' onclick=\"edit_delete_action('times');\">\n";
			echo "			<input type='hidden' name='contact_times[$x][uuid]' value='".escape($row['contact_time_uuid'])."' />\n";
			echo "			<input type='hidden' name='contact_times[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
			echo "			<input type='hidden' name='contact_times[$x][contact_time_uuid]' value='".escape($row['contact_time_uuid'])."' />\n";
			echo "			<input type='hidden' name='contact_times[$x][contact_uuid]' value='".escape($row['contact_uuid'])."' />\n";
			echo "		</div>\n";
		}
		echo "	</div>\n";
		echo "	<div style='clear: both;'></div>\n";

		echo "	<div class='label required'>\n";
		echo "		".$text['label-time_start']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap'>\n";
		echo "		<input class='formfld datetimesecpicker' data-toggle='datetimepicker' data-target='#time_start_$x' type='text' name='contact_times[$x][time_start]' id='time_start_$x' style='".($settings->get('domain', 'time_format') == '24h' ? 'min-width: 125px; width: 125px;' : 'min-width: 145px; width: 145px;')."' value='".escape($row["time_start"])."' onblur=\"$(this).datetimepicker('hide');\">\n";
		echo "	</div>\n";

		echo "	<div class='label'>\n";
		echo "		".$text['label-time_stop']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap'>\n";
		echo "		<input class='formfld datetimesecpicker' data-toggle='datetimepicker' data-target='#time_stop_$x' type='text' name='contact_times[$x][time_stop]' id='time_stop_$x' style='".($settings->get('domain', 'time_format') == '24h' ? 'min-width: 125px; width: 125px;' : 'min-width: 145px; width: 145px;')."' value='".escape($row["time_stop"])."' onblur=\"$(this).datetimepicker('hide');\">\n";
		echo "	</div>\n";

		echo "	<div class='label'>\n";
		echo "		".$text['label-time_description']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap'>\n";
		echo "  	<textarea class='formfld' type='text' name='contact_times[$x][time_description]' id='time_description' style='width: 100%; height: 100%;'>".escape($row["time_description"])."</textarea>\n";
		echo "	</div>\n";

		echo "	<div class='label empty_row' style='grid-row: 5 / span 99;'>\n";
		echo "		&nbsp;\n";
		echo "	</div>\n";
		echo "	<div class='field empty_row' style='grid-row: 5 / span 99;'>\n";
		echo "	</div>\n";

		echo "</div>\n";
		$x++;
	}
	unset($contact_times);
}

if (permission_exists('contact_note_view')) {
	$x = 0;
	foreach($contact_notes as $row) {
		$contact_note = $row['contact_note'];
		if (!empty($contact_note)) {
			$contact_note = htmlspecialchars($contact_note, ENT_QUOTES, 'UTF-8');
		}
		if (permission_exists('contact_note_add')) {
			$list_row_url = "contact_note_edit.php?contact_uuid=".escape($row['contact_uuid'])."&id=".escape($row['contact_note_uuid']);
		}

		echo "<div class='form_set card'>\n";
		echo "	<div class='heading'>\n";
		echo "		<b style='float: left;'>".$text['label-contact_notes']."</b>\n";
		if (permission_exists('contact_note_delete')) {
			echo "		<div class='checkbox' style='float: left; margin-top: 3px; margin-left: 8px;'>\n";
			echo "			<input type='checkbox' name='contact_notes[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_notes' value='true' onclick=\"edit_delete_action('notes');\">\n";
			echo "			<input type='hidden' name='contact_notes[$x][uuid]' value='".escape($row['contact_note_uuid'])."' />\n";
			echo "			<input type='hidden' name='contact_notes[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
			echo "			<input type='hidden' name='contact_notes[$x][contact_note_uuid]' value='".escape($row['contact_note_uuid'])."' />\n";
			echo "			<input type='hidden' name='contact_notes[$x][contact_uuid]' value='".escape($row['contact_uuid'])."' />\n";
			echo "		</div>\n";
		}
		echo "	</div>\n";
		echo "	<div style='clear: both;'></div>\n";

		echo "	<div class='label required'>\n";
		echo "		".$text['label-contact_note']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap' style='float: left;'>\n";
		echo "  	<textarea class='formfld' name=\"contact_notes[$x][contact_note]\" style='min-width: 100%; height: 275px;'>".$contact_note."</textarea>\n";
		echo "	</div>\n";

		echo "	<div class='label'>\n";
		echo "		".$text['label-note_user']."\n";
		echo "	</div>\n";
		echo "	<div class='field no-wrap' style='margin-top: 2px;'>\n";
		echo "		<div class='description'><strong>".escape($row['last_mod_user'])."</strong>: ".date("j M Y @ ".$time_format, strtotime($row['last_mod_date']))."</div>\n";
		echo "	</div>\n";

		echo "	<div class='label empty_row' style='grid-row: 4 / span 99;'>\n";
		echo "		&nbsp;\n";
		echo "	</div>\n";
		echo "	<div class='field empty_row' style='grid-row: 4 / span 99;'>\n";
		echo "	</div>\n";

		echo "</div>\n";
		$x++;
	}
	unset($contact_notes);
}

//close the grid
	echo "</div>\n";
	echo "<br /><br />";

//end the form
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
