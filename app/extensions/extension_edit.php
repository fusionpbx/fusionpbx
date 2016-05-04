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
	Copyright (C) 2008-2015 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
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

//detect billing app
	$billing_app_exists = file_exists($_SERVER["PROJECT_ROOT"]."/app/billing/app_config.php");
	if ($billing_app_exists) {
		require_once "app/billing/resources/functions/currency.php";
		require_once "app/billing/resources/functions/rating.php";
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$extension_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get total extension count from the database, check limit, if defined
	if ($action == 'add') {
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
				header('Location: extensions.php');
				return;
			}
		}
	}

//get the http values and set them as php variables
	if (count($_POST) > 0) {
		//get the values from the HTTP POST and save them as PHP variables
			$extension = str_replace(' ','-',check_str($_POST["extension"]));
			$number_alias = check_str($_POST["number_alias"]);
			$password = check_str($_POST["password"]);

			// server verification on account code
			if (if_group("superadmin")) {
				$accountcode = $_POST["accountcode"];
			}
			elseif (if_group("admin") && $billing_app_exists) {
				$sql_accountcode = "SELECT COUNT(*) as count FROM v_billings WHERE domain_uuid = '".$_SESSION['domain_uuid']."' AND type_value='".$_POST["accountcode"]."'";
				$prep_statement_accountcode = $db->prepare(check_sql($sql_accountcode));
				$prep_statement_accountcode->execute();
				$row_accountcode = $prep_statement_accountcode->fetch(PDO::FETCH_ASSOC);
				if ($row_accountcode['count'] > 0) {
					$accountcode = $_POST["accountcode"];
				}
				else {
					$accountcode = $_SESSION['domain_name'];
				}
				unset($sql_accountcode, $prep_statement_accountcode, $row_accountcode);
			}

			$effective_caller_id_name = check_str($_POST["effective_caller_id_name"]);
			$effective_caller_id_number = check_str($_POST["effective_caller_id_number"]);
			$outbound_caller_id_name = check_str($_POST["outbound_caller_id_name"]);
			$outbound_caller_id_number = check_str($_POST["outbound_caller_id_number"]);
			$emergency_caller_id_name = check_str($_POST["emergency_caller_id_name"]);
			$emergency_caller_id_number = check_str($_POST["emergency_caller_id_number"]);
			$directory_full_name = check_str($_POST["directory_full_name"]);
			$directory_visible = check_str($_POST["directory_visible"]);
			$directory_exten_visible = check_str($_POST["directory_exten_visible"]);
			$limit_max = check_str($_POST["limit_max"]);
			$limit_destination = check_str($_POST["limit_destination"]);
			$device_uuid = check_str($_POST["device_uuid"]);
			$device_line = check_str($_POST["device_line"]);
			$voicemail_password = check_str($_POST["voicemail_password"]);
			$voicemail_enabled = check_str($_POST["voicemail_enabled"]);
			$voicemail_mail_to = check_str($_POST["voicemail_mail_to"]);
			$voicemail_file = check_str($_POST["voicemail_file"]);
			$voicemail_local_after_email = check_str($_POST["voicemail_local_after_email"]);
			$user_context = check_str($_POST["user_context"]);
			$range = check_str($_POST["range"]);
			$autogen_users = check_str($_POST["autogen_users"]);
			$missed_call_app = check_str($_POST["missed_call_app"]);
			$missed_call_data = check_str($_POST["missed_call_data"]);
			$toll_allow = check_str($_POST["toll_allow"]);
			$call_timeout = check_str($_POST["call_timeout"]);
			$call_group = check_str($_POST["call_group"]);
			$call_screen_enabled = check_str($_POST["call_screen_enabled"]);
			$user_record = check_str($_POST["user_record"]);
			$hold_music = check_str($_POST["hold_music"]);
			$auth_acl = check_str($_POST["auth_acl"]);
			$cidr = check_str($_POST["cidr"]);
			$sip_force_contact = check_str($_POST["sip_force_contact"]);
			$sip_force_expires = check_str($_POST["sip_force_expires"]);
			$nibble_account = check_str($_POST["nibble_account"]);
			$mwi_account = check_str($_POST["mwi_account"]);
			$sip_bypass_media = check_str($_POST["sip_bypass_media"]);
			$absolute_codec_string = check_str($_POST["absolute_codec_string"]);
			$dial_string = check_str($_POST["dial_string"]);
			$enabled = check_str($_POST["enabled"]);
			$description = check_str($_POST["description"]);
	}

//delete the user from the v_extension_users
	if ($_REQUEST["delete_type"] == "user" && strlen($_REQUEST["delete_uuid"]) > 0 && permission_exists("extension_delete")) {
		//set the variables
			$extension_uuid = check_str($_REQUEST["id"]);
			$user_uuid = check_str($_REQUEST["delete_uuid"]);
		//delete the group from the users
			$sql = "delete from v_extension_users ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and extension_uuid = '".$extension_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
	}

//delete the line from the v_device_lines
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/devices')) {
		if ($_REQUEST["delete_type"] == "device_line" && strlen($_REQUEST["delete_uuid"]) > 0 && permission_exists("extension_delete")) {
			//set the variables
				$extension_uuid = check_str($_REQUEST["id"]);
				$device_line_uuid = check_str($_REQUEST["delete_uuid"]);
			//delete device_line
				$sql = "delete from v_device_lines ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and device_line_uuid = '$device_line_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);
		}
	}

//assign the extension to the user
	if (strlen($_REQUEST["user_uuid"]) > 0 && strlen($_REQUEST["id"]) > 0) {
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
			$sql_insert .= "'".$_SESSION['domain_uuid']."', ";
			$sql_insert .= "'".$extension_uuid."', ";
			$sql_insert .= "'".$user_uuid."' ";
			$sql_insert .= ")";
			$db->exec($sql_insert);
	}

//assign the line to the device
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/devices')) {
		if (strlen($_REQUEST["device_mac_address"]) > 0 && strlen($_REQUEST["id"]) > 0) {

			//set the variables
				$extension_uuid = check_str($_REQUEST["id"]);
				$device_uuid= uuid();
				$device_line_uuid = uuid();
				$device_template = check_str($_REQUEST["device_template"]);
				$line_number = check_str($_REQUEST["line_number"]);
				$device_mac_address = check_str($_REQUEST["device_mac_address"]);
				$device_mac_address = strtolower($device_mac_address);
				$device_mac_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address);

			//set a default line number
				if (strlen($line_number) == 0) { $line_number = '1'; }

			//add the device if it doesn't exist, if it does exist get the device_uuid
				$sql = "select device_uuid from v_devices ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and device_mac_address = '$device_mac_address' ";
				if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if (strlen($row['device_uuid']) > 0) {
						//device found get the device_uuid
							$device_uuid = $row['device_uuid'];

						//update device template
							if (strlen($device_template) > 0) {
								$sql = "update v_devices set ";
								$sql .= "device_template = '$device_template' ";
								$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
								$sql .= "and device_uuid = '$device_uuid'";
								$db->exec(check_sql($sql));
								unset($sql);
							}
					}
					else {
						//device not found
							$sql_insert = "insert into v_devices ";
							$sql_insert .= "(";
							$sql_insert .= "device_uuid, ";
							$sql_insert .= "domain_uuid, ";
							$sql_insert .= "device_mac_address, ";
							$sql_insert .= "device_template, ";
							$sql_insert .= "device_enabled ";
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
				$sql_insert .= "display_name, ";
				$sql_insert .= "user_id, ";
				$sql_insert .= "auth_id, ";
				$sql_insert .= "password, ";
				$sql_insert .= "line_number, ";
				$sql_insert .= "sip_port, ";
				$sql_insert .= "sip_transport, ";
				$sql_insert .= "register_expires, ";
				$sql_insert .= "enabled ";
				$sql_insert .= ") ";
				$sql_insert .= "values ";
				$sql_insert .= "(";
				$sql_insert .= "'".$device_uuid."', ";
				$sql_insert .= "'".$device_line_uuid."', ";
				$sql_insert .= "'".$_SESSION['domain_uuid']."', ";
				$sql_insert .= "'".$_SESSION['domain_name']."', ";
				$sql_insert .= "'".$extension."', ";
				$sql_insert .= "'".$extension."', ";
				$sql_insert .= "'".$extension."', ";
				$sql_insert .= "'".$password."', ";
				$sql_insert .= "'".$line_number."', ";
				$sql_insert .= "'".$_SESSION['provision']['line_sip_port']['numeric']."', ";
				$sql_insert .= "'".$_SESSION['provision']['line_sip_transport']['text']."', ";
				$sql_insert .= "'".$_SESSION['provision']['line_register_expires']['numeric']."', ";
				$sql_insert .= "'true' ";
				$sql_insert .= ")";
				//echo $sql_insert."<br />\n";
				$db->exec($sql_insert);
		}
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	//get the id
		if ($action == "update") {
			$extension_uuid = check_str($_POST["extension_uuid"]);
		}

	//set the domain_uuid
		if (permission_exists('extension_domain')) {
			$domain_uuid = check_str($_POST["domain_uuid"]);
		}
		else {
			$domain_uuid = $_SESSION['domain_uuid'];
		}

	//check for all required data
		$msg = '';
		if (strlen($extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
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
		if (permission_exists("extension_user_context")) {
			//allow a user assigned to super admin to change the user_context
		}
		else {
			//if the user_context was not set then set the default value
			if (strlen($user_context) == 0) {
				$user_context = $_SESSION['domain_name'];
			}
		}

	//prevent users from bypassing extension limit by using range
	if ($_SESSION['limit']['extensions']['numeric'] != '') {
		if ($total_extensions + $range > $_SESSION['limit']['extensions']['numeric']){
			$range = $_SESSION['limit']['extensions']['numeric'] - $total_extensions;
		}
	}

	//add or update the database
	if ($_POST["persistformvar"] != "true") {
		//prep missed call values for db insert/update
			switch ($missed_call_app) {
				case 'email':
					$missed_call_data = str_replace(';',',',$missed_call_data);
					$missed_call_data = str_replace(' ','',$missed_call_data);
					if (substr_count($missed_call_data, ',') > 0) {
						$missed_call_data_array = explode(',', $missed_call_data);
						foreach ($missed_call_data_array as $array_index => $email_address) {
							if (!valid_email($email_address)) { unset($missed_call_data_array[$array_index]); }
						}
						//echo "<pre>".print_r($missed_call_data_array, true)."</pre><br><br>";
						if (sizeof($missed_call_data_array) > 0) {
							$missed_call_data = implode(',', $missed_call_data_array);
						}
						else {
							unset($missed_call_app, $missed_call_data);
						}
						//echo "Multiple Emails = ".$missed_call_data;
					}
					else {
						//echo "Single Email = ".$missed_call_data."<br>";
						if (!valid_email($missed_call_data)) {
							//echo "Invalid Email<br><br>";
							unset($missed_call_app, $missed_call_data);
						}
					}
					break;
				case 'text':
					$missed_call_data = str_replace('-','',$missed_call_data);
					$missed_call_data = str_replace('.','',$missed_call_data);
					$missed_call_data = str_replace('(','',$missed_call_data);
					$missed_call_data = str_replace(')','',$missed_call_data);
					$missed_call_data = str_replace(' ','',$missed_call_data);
					if (!is_numeric($missed_call_data)) { unset($missed_call_app, $missed_call_data); }
					break;
			}

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

				$j = 0;
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
							if (if_group("superadmin") || (if_group("admin") && $billing_app_exists)) {
								$sql .= "accountcode, ";
							}
							$sql .= "effective_caller_id_name, ";
							$sql .= "effective_caller_id_number, ";
							$sql .= "outbound_caller_id_name, ";
							$sql .= "outbound_caller_id_number, ";
							$sql .= "emergency_caller_id_name, ";
							$sql .= "emergency_caller_id_number, ";
							$sql .= "directory_full_name, ";
							$sql .= "directory_visible, ";
							$sql .= "directory_exten_visible, ";
							$sql .= "limit_max, ";
							$sql .= "limit_destination, ";
							$sql .= "user_context, ";
							if (permission_exists('extension_missed_call')) {
								$sql .= "missed_call_app, ";
								$sql .= "missed_call_data, ";
							}
							if (permission_exists('extension_toll')) {
								$sql .= "toll_allow, ";
							}
							if (strlen($call_timeout) > 0) {
								$sql .= "call_timeout, ";
							}
							$sql .= "call_group, ";
							$sql .= "call_screen_enabled, ";
							$sql .= "user_record, ";
							$sql .= "hold_music, ";
							$sql .= "auth_acl, ";
							$sql .= "cidr, ";
							$sql .= "sip_force_contact, ";
							if (strlen($sip_force_expires) > 0) {
								$sql .= "sip_force_expires, ";
							}
							if (if_group("superadmin")) {
								if (strlen($nibble_account) > 0) {
									$sql .= "nibble_account, ";
								}
							}
							if (strlen($mwi_account) > 0) {
								$sql .= "mwi_account, ";
							}
							$sql .= "sip_bypass_media, ";
							if (permission_exists('extension_absolute_codec_string')) {
								$sql .= "absolute_codec_string, ";
							}
							if (permission_exists('extension_dial_string')) {
								$sql .= "dial_string, ";
							}
							$sql .= "enabled, ";
							$sql .= "description ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".$domain_uuid."', ";
							$sql .= "'$extension_uuid', ";
							$sql .= "'$extension', ";
							$sql .= "'$number_alias', ";
							$sql .= "'$password', ";
							if (if_group("superadmin") || (if_group("admin") && $billing_app_exists)) {
								$sql .= "'$accountcode', ";
							}
							$sql .= "'$effective_caller_id_name', ";
							$sql .= "'$effective_caller_id_number', ";
							$sql .= "'$outbound_caller_id_name', ";
							$sql .= "'$outbound_caller_id_number', ";
							$sql .= "'$emergency_caller_id_name', ";
							$sql .= "'$emergency_caller_id_number', ";
							$sql .= "'$directory_full_name', ";
							$sql .= "'$directory_visible', ";
							$sql .= "'$directory_exten_visible', ";
							$sql .= "'$limit_max', ";
							$sql .= "'$limit_destination', ";
							$sql .= "'$user_context', ";
							if (permission_exists('extension_missed_call')) {
								$sql .= "'$missed_call_app', ";
								$sql .= "'$missed_call_data', ";
							}
							if (permission_exists('extension_toll')) {
								$sql .= "'$toll_allow', ";
							}
							if (strlen($call_timeout) > 0) {
								$sql .= "'$call_timeout', ";
							}
							$sql .= "'$call_group', ";
							$sql .= "'$call_screen_enabled', ";
							$sql .= "'$user_record', ";
							$sql .= "'$hold_music', ";
							$sql .= "'$auth_acl', ";
							$sql .= "'$cidr', ";
							$sql .= "'$sip_force_contact', ";
							if (strlen($sip_force_expires) > 0) {
								$sql .= "'$sip_force_expires', ";
							}
							if (if_group("superadmin")) {
								if (strlen($nibble_account) > 0) {
									$sql .= "'$nibble_account', ";
								}
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
							if (permission_exists('extension_absolute_codec_string')) {
								$sql .= "'$absolute_codec_string', ";
							}
							if (permission_exists('extension_dial_string')) {
								$sql .= "'$dial_string', ";
							}
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
							$j++;
						}

					//add or update voicemail
						if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
							//set the voicemail password
								if (strlen($voicemail_password) == 0) {
									$voicemail_password = generate_password(9, 1);
								}
							//voicemail class
								$ext = new extension;
								$ext->db = $db;
								$ext->domain_uuid = $domain_uuid;
								$ext->extension = $extension;
								$ext->number_alias = $number_alias;
								$ext->voicemail_password = $voicemail_password;
								$ext->voicemail_mail_to = $voicemail_mail_to;
								$ext->voicemail_file = $voicemail_file;
								$ext->voicemail_local_after_email = $voicemail_local_after_email;
								$ext->voicemail_enabled = $voicemail_enabled;
								$ext->description = $description;
								$ext->voicemail();
								unset($ext);
						}
					//increment the extension number
						$extension++;
				}

				if ($billing_app_exists) {
					// Let's bill $j has the number of extensions to bill
					$db2 = new database;
					$db2->sql = "SELECT currency, billing_uuid, balance FROM v_billings WHERE type_value='$destination_accountcode'";
					$db2->result = $db2->execute();
					$default_currency = (strlen($_SESSION['billing']['currency']['text'])?$_SESSION['billing']['currency']['text']:'USD');
					$billing_currency = (strlen($db2->result[0]['currency'])?$db2->result[0]['currency']:$default_currency);
					$billing_uuid = $db2->result[0]['billing_uuid'];
					$balance = $db2->result[0]['balance'];
					unset($db2->sql, $db2->result);

					$default_extension_pricing = (strlen($_SESSION['billing']['extension.pricing']['numeric'])?$_SESSION['billing']['extension.pricing']['numeric']:'0');
					$total_price = $default_extension_pricing * $j;
					$total_price_current_currency = currency_convert($total_price,$billing_currency,$default_currency);
					$balance -= $total_price_current_currency;

					$db2->sql = "UPDATE v_billings SET balance = $balance, old_balance = $balance WHERE type_value='$destination_accountcode'";
					$db2->result = $db2->execute();
					unset($db2->sql, $db2->result);

					$billing_invoice_uuid = uuid();
					$user_uuid = check_str($_SESSION['user_uuid']);
					$settled=1;
					$mc_gross = -1 * $total_price_current_currency;
					$post_payload = serialize($_POST);
					$db2->sql = "INSERT INTO v_billing_invoices (billing_invoice_uuid, billing_uuid, payer_uuid, billing_payment_date, settled, amount, debt, post_payload,plugin_used, domain_uuid) VALUES ('$billing_invoice_uuid', '$billing_uuid', '$user_uuid', NOW(), $settled, $mc_gross, $balance, '$post_payload', '$j extension(s) created', '".$_SESSION['domain_uuid']."' )";
					$db2->result = $db2->execute();
					unset($db2->sql, $db2->result);
				}
			} //if ($action == "add")

		//update the database
			if ($action == "update" && permission_exists('extension_edit')) {
				//generate a password
					if (strlen($password) == 0) {
						$password = generate_password();
					}
				//set the voicemail password
					if (strlen($voicemail_password) == 0) {
						$voicemail_password = generate_password(9, 1);
					}
				//update extensions
					$sql = "update v_extensions set ";
					if (permission_exists('extension_domain')) {
						$sql .= "domain_uuid = '$domain_uuid', ";
					}
					$sql .= "extension = '$extension', ";
					$sql .= "number_alias = '$number_alias', ";
					if (permission_exists('extension_password')) {
						$sql .= "password = '$password', ";
					}
					if (if_group("superadmin") || (if_group("admin") && $billing_app_exists)) {
						$sql .= "accountcode = '$accountcode', ";
					}
					$sql .= "effective_caller_id_name = '$effective_caller_id_name', ";
					$sql .= "effective_caller_id_number = '$effective_caller_id_number', ";
					$sql .= "outbound_caller_id_name = '$outbound_caller_id_name', ";
					$sql .= "outbound_caller_id_number = '$outbound_caller_id_number', ";
					$sql .= "emergency_caller_id_name = '$emergency_caller_id_name', ";
					$sql .= "emergency_caller_id_number = '$emergency_caller_id_number', ";
					$sql .= "directory_full_name = '$directory_full_name', ";
					$sql .= "directory_visible = '$directory_visible', ";
					$sql .= "directory_exten_visible = '$directory_exten_visible', ";
					$sql .= "limit_max = '$limit_max', ";
					$sql .= "limit_destination = '$limit_destination', ";
					if (permission_exists("extension_user_context")) {
						$sql .= "user_context = '$user_context', ";
					}
					if (permission_exists('extension_missed_call')) {
						$sql .= "missed_call_app = '$missed_call_app', ";
						$sql .= "missed_call_data = '$missed_call_data', ";
					}
					if (permission_exists('extension_toll')) {
						$sql .= "toll_allow = '$toll_allow', ";
					}
					if (strlen($call_timeout) > 0) {
						$sql .= "call_timeout = '$call_timeout', ";
					}
					$sql .= "call_group = '$call_group', ";
					$sql .= "call_screen_enabled = '$call_screen_enabled', ";
					$sql .= "user_record = '$user_record', ";
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
					if (if_group("superadmin")) {
						if (strlen($nibble_account) == 0) {
							$sql .= "nibble_account = null, ";
						}
						else {
							$sql .= "nibble_account = '$nibble_account', ";
						}
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
					if (permission_exists('extension_absolute_codec_string')) {
						$sql .= "absolute_codec_string = '$absolute_codec_string', ";
					}
					if (permission_exists('extension_dial_string')) {
						$sql .= "dial_string = '$dial_string', ";
					}
					if (permission_exists('extension_enabled')) {
						$sql .= "enabled = '$enabled', ";
					}
					$sql .= "description = '$description' ";
					$sql .= "where extension_uuid = '$extension_uuid'  ";
					if (!permission_exists('extension_domain')) {
						$sql .= "and domain_uuid = '".$domain_uuid."'  ";
					}
					$db->exec(check_sql($sql));
					unset($sql);

				//add or update voicemail
					if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
						require_once "app/extensions/resources/classes/extension.php";
						$ext = new extension;
						$ext->db = $db;
						$ext->domain_uuid = $domain_uuid;
						$ext->extension = $extension;
						$ext->number_alias = $number_alias;
						$ext->voicemail_password = $voicemail_password;
						$ext->voicemail_mail_to = $voicemail_mail_to;
						$ext->voicemail_file = $voicemail_file;
						$ext->voicemail_local_after_email = $voicemail_local_after_email;
						$ext->voicemail_enabled = $voicemail_enabled;
						$ext->description = $description;
						$ext->voicemail();
						unset($ext);
					}

				//update devices having extension assigned to line(s) with new password
					$sql = "update v_device_lines set ";
					$sql .= "password = '".$password."' ";
					$sql .= "where domain_uuid = '".$domain_uuid."' ";
					$sql .= "and server_address = '".$_SESSION['domain_name']."' ";
					$sql .= "and user_id = '".$extension."' ";
					$db->exec(check_sql($sql));
					unset($sql);

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
					if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/provision')) {
						require_once "app/provision/provision_write.php";
						$ext = new extension;
					}

				//clear the cache
					$cache = new cache;
					$cache->delete("directory:".$extension."@".$user_context);
					if (strlen($number_alias) > 0) {
						$cache->delete("directory:".$number_alias."@".$user_context);
					}
			}

		//show the action and redirect the user
			if ($action == "add") {
				//prepare for alternating the row style
					$c = 0;
					$row_style["0"] = "row_style0";
					$row_style["1"] = "row_style1";

				//show the action and redirect the user
					if (count($generated_users) == 0) {
						//action add
							$_SESSION["message"] = $text['message-add'];
							header("Location: extension_edit.php?id=".$extension_uuid);
					}
					else {
						//auto-generate user with extension as login name
							require_once "resources/header.php";
							echo "<br />\n";
							echo "<div align='center'>\n";
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
							echo "</div>\n";
							require_once "resources/footer.php";
					}
					return;
			}
			if ($action == "update") {
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				else {
					$_SESSION["message"] = $text['message-add'];
				}
				header("Location: extension_edit.php?id=".$extension_uuid);
				return;
			}
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$extension_uuid = check_str($_GET["id"]);
		$sql = "select * from v_extensions ";
		$sql .= "where extension_uuid = '".$extension_uuid."' ";
		$sql .= "and domain_uuid = '".$domain_uuid."' ";
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
			$emergency_caller_id_name = $row["emergency_caller_id_name"];
			$emergency_caller_id_number = $row["emergency_caller_id_number"];
			$directory_full_name = $row["directory_full_name"];
			$directory_visible = $row["directory_visible"];
			$directory_exten_visible = $row["directory_exten_visible"];
			$limit_max = $row["limit_max"];
			$limit_destination = $row["limit_destination"];
			$user_context = $row["user_context"];
			$missed_call_app = $row["missed_call_app"];
			$missed_call_data = $row["missed_call_data"];
			$toll_allow = $row["toll_allow"];
			$call_timeout = $row["call_timeout"];
			$call_group = $row["call_group"];
			$call_screen_enabled = $row["call_screen_enabled"];
			$user_record = $row["user_record"];
			$hold_music = $row["hold_music"];
			$auth_acl = $row["auth_acl"];
			$cidr = $row["cidr"];
			$sip_force_contact = $row["sip_force_contact"];
			$sip_force_expires = $row["sip_force_expires"];
			$nibble_account = $row["nibble_account"];
			$mwi_account = $row["mwi_account"];
			$sip_bypass_media = $row["sip_bypass_media"];
			$absolute_codec_string = $row["absolute_codec_string"];
			$dial_string = $row["dial_string"];
			$enabled = $row["enabled"];
			$description = $row["description"];
		}
		unset ($prep_statement);

	//get the voicemail data
		if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
			//get the voicemails
				$sql = "select * from v_voicemails ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and voicemail_id = '".((is_numeric($number_alias)) ? $number_alias : $extension)."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$voicemail_password = $row["voicemail_password"];
					$voicemail_mail_to = $row["voicemail_mail_to"];
					$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);
					$voicemail_file = $row["voicemail_file"];
					$voicemail_local_after_email = $row["voicemail_local_after_email"];
					$voicemail_enabled = $row["voicemail_enabled"];
				}
				unset ($prep_statement);
			//clean the variables
				$voicemail_password = str_replace("#", "", $voicemail_password);
				$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);
		}

	}
	else {
		$voicemail_file = $_SESSION['voicemail']['voicemail_file']['text'];
		$voicemail_local_after_email = $_SESSION['voicemail']['keep_local']['boolean'];
	}

//get the device lines
	$sql = "SELECT d.device_mac_address, d.device_template, d.device_description, l.device_line_uuid, l.device_uuid, l.line_number ";
	$sql .= "FROM v_device_lines as l, v_devices as d ";
	$sql .= "WHERE (l.user_id = '".$extension."' or l.user_id = '".$number_alias."')";
	$sql .= "AND l.domain_uuid = '".$domain_uuid."' ";
	$sql .= "AND l.device_uuid = d.device_uuid ";
	$sql .= "ORDER BY l.line_number, d.device_mac_address asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_lines = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset($sql, $prep_statement);

//get the devices
	$sql = "SELECT * FROM v_devices ";
	$sql .= "WHERE domain_uuid = '".$domain_uuid."' ";
	$sql .= "ORDER BY device_mac_address asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$devices = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset($sql, $prep_statement);

//get assigned users
	$sql = "SELECT u.username, e.user_uuid FROM v_extension_users as e, v_users as u ";
	$sql .= "where e.user_uuid = u.user_uuid  ";
	$sql .= "and u.user_enabled = 'true' ";
	$sql .= "and e.domain_uuid = '".$domain_uuid."' ";
	$sql .= "and e.extension_uuid = '".$extension_uuid."' ";
	$sql .= "order by u.username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$assigned_users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($assigned_users as $field) {
		$assigned_user_uuids[] = $field['user_uuid'];
	}
	unset($sql, $prep_statement);

//get the users
	$sql = "SELECT * FROM v_users ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	if (isset($assigned_user_uuids)) foreach($assigned_user_uuids as $assigned_user_uuid) {
		$sql .= "and user_uuid <> '".$assigned_user_uuid."' ";
	}
	unset($assigned_user_uuids);
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset($sql, $prep_statement);

//get the destinations
	$sql = "select * from v_destinations ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "and destination_type = 'inbound' ";
	$sql .= "order by destination_number asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$destinations = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	unset ($sql, $prep_statement);

//set the defaults
	if (strlen($limit_max) == 0) { $limit_max = '5'; }
	if (strlen($call_timeout) == 0) { $call_timeout = '30'; }
	if (strlen($call_screen_enabled) == 0) { $call_screen_enabled = 'false'; }

//begin the page content
	require_once "resources/header.php";
	if ($action == "update") {
	$document['title'] = $text['title-extension-edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-extension-add'];
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
	echo "	$('#show_advanced_box').slideToggle();\n";
	echo "	$('#show_advanced').slideToggle();\n";
	echo "}\n";
	echo "\n";
	echo "function copy_extension() {\n";
	echo "	var new_ext = prompt('".$text['message-extension']."');\n";
	echo "	if (new_ext != null) {\n";
	echo "		if (!isNaN(new_ext)) {\n";
	echo "			document.location.href='extension_copy.php?id=".$extension_uuid."&ext=' + new_ext;\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			var new_number_alias = prompt('".$text['message-number_alias']."');\n";
	echo "			if (new_number_alias != null) {\n";
	echo "				if (!isNaN(new_number_alias)) {\n";
	echo "					document.location.href='extension_copy.php?id=".$extension_uuid."&ext=' + new_ext + '&alias=' + new_number_alias;\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "}\n";
	echo "</script>";

	echo "<form method='post' name='frm' id='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpdding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-extension-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-extension-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='extensions.php'\" value='".$text['button-back']."'>\n";
	if ($action == 'update' && (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb'))) {
		echo "	<input type='button' class='btn' alt='".$text['button-call_routing']."' onclick=\"window.location='../calls/call_edit.php?id=".$extension_uuid."';\" value='".$text['button-call_routing']."'>\n";
	}
	if ($action == "update") {
		echo "	<input type='button' class='btn' alt='".$text['button-copy']."' onclick=\"copy_extension();\" value='".$text['button-copy']."'>\n";
	}
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	// Billing
	if ($billing_app_exists) {
		if ($action == "add" && permission_exists('extension_add')) {		// only when adding
			echo "<tr>\n";
			echo "<td colspan='2' width='30%' nowrap='nowrap' align='left' valign='top'>\n";
			echo "    <center>".$text['label-billing_warning']."</center>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
	}
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

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-number_alias']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='number' name='number_alias' autocomplete='off' maxlength='255' min='0' step='1' value=\"$number_alias\">\n";
	echo "<br />\n";
	echo $text['description-number_alias']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('extension_password') && $action == "update") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-password']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='password' name='password' id='password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"$password\">\n";
		echo "    <br />\n";
		echo "    ".$text['description-password']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if ($action == "add") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-range']."\n";
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
		echo "    <option value='750'>750</option>\n";
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
		echo "		<td class='vncell' valign='top'>".$text['label-user_list']."</td>";
		echo "		<td class='vtable'>";
		if (count($assigned_users) > 0) {
			echo "		<table width='30%'>\n";
			foreach($assigned_users as $field) {
				echo "		<tr>\n";
				echo "			<td class='vtable'><a href='/core/users/usersupdate.php?id=".$field['user_uuid']."'>".$field['username']."</a></td>\n";
				echo "			<td>\n";
				echo "				<a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('delete_type').value = 'user'; document.getElementById('delete_uuid').value = '".$field['user_uuid']."'; submit_form(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a>\n";
				//echo "				<a href='extension_edit.php?id=".$extension_uuid."&domain_uuid=".$_SESSION['domain_uuid']."&user_uuid=".$field['user_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
				echo "			</td>\n";
				echo "		</tr>\n";
			}
			echo "		</table>\n";
			echo "		<br />\n";
		}

		echo "			<select name='user_uuid' id='user_uuid' class='formfld' style='width: auto;'>\n";
		echo "			<option value=''></option>\n";
		foreach($users as $field) {
			echo "			<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
		}
		echo "			</select>";
		echo "			<input type='button' class='btn' value=\"".$text['button-add']."\" onclick='submit_form();'>\n";

		echo "			<br>\n";
		echo "			".$text['description-user_list']."\n";
		echo "			<br />\n";
		echo "		</td>";
		echo "	</tr>";
	}

	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_password']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='password' name='voicemail_password' id='voicemail_password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' value='$voicemail_password'>\n";
		echo "    <br />\n";
		echo "    ".$text['description-voicemail_password']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if ($action == "update") {
		if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/devices')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-provisioning']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "		<input type='hidden' name='device_line_uuid' id='device_line_uuid' value=''>";
			echo "		<table>\n";
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
			foreach($device_lines as $row) {
				$device_mac_address = $row['device_mac_address'];
				$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);
				echo "		<tr>\n";
				echo "			<td class='vtable'>".$row['line_number']."</td>\n";
				echo "			<td class='vtable'><a href='".PROJECT_PATH."/app/devices/device_edit.php?id=".$row['device_uuid']."'>".$device_mac_address."</a></td>\n";
				echo "			<td class='vtable'>".$row['device_template']."&nbsp;</td>\n";
				//echo "			<td class='vtable'>".$row['device_description']."&nbsp;</td>\n";
				echo "			<td>\n";
				echo "				<a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('delete_type').value = 'device_line'; document.getElementById('delete_uuid').value = '".$row['device_line_uuid']."'; submit_form(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a>\n";
				echo "			</td>\n";
				echo "		</tr>\n";
			}

			echo "		<tr>\n";
			echo "		<td class='vtable'>";
			echo "			<select id='line_number' name='line_number' class='formfld' style='width: auto;' onchange=\"$onchange\">\n";
			echo "			<option value=''></option>\n";
			for ($n = 1; $n <=30; $n++) {
				echo "		<option value='".$n."'>".$n."</option>\n";
			}
			echo "			</select>\n";
			echo "		</td>\n";

			echo "		<td class='vtable'>";
			echo "			<table border='0' cellpadding='1' cellspacing='0'>\n";
			echo "			<tr>\n";
			echo "			<td id=\"cell_device_mac_address_1\" nowrap='nowrap'>\n";
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
				tb.setAttribute('pattern', '^([0-9A-Fa-f]{2}[:-]?){5}([0-9A-Fa-f]{2})$');
				tb.value=obj.options[obj.selectedIndex].value;
				document.getElementById('btn_select_to_input_device_mac_address').style.visibility = 'hidden';
				tbb=document.createElement('INPUT');
				tbb.setAttribute('class', 'btn');
				tbb.setAttribute('style', 'margin-left: 4px;');
				tbb.type='button';
				tbb.value=$("<div />").html('&#9665;').text();
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
			echo "				<select id=\"device_mac_address\" name=\"device_mac_address\" class='formfld' style='width: 180px;' onchange='changeToInput_device_mac_address(this);this.style.visibility = \"hidden\";'>\n";
			echo "					<option value=''></option>\n";
			if (count($devices) > 0) {
				foreach($devices as $field) {
					if (strlen($field["device_mac_address"]) > 0) {
						if ($field_current_value == $field["device_mac_address"]) {
							echo "					<option value=\"".$field["device_mac_address"]."\" selected=\"selected\">".$field["device_mac_address"]."</option>\n";
						}
						else {
							echo "					<option value=\"".$field["device_mac_address"]."\">".$field["device_mac_address"]."  ".$field['device_model']." ".$field['device_description']."</option>\n";
						}
					}
				}
			}
			echo "				</select>\n";
			echo "				<input type='button' id='btn_select_to_input_device_mac_address' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_device_mac_address(document.getElementById(\"device_mac_address\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
			echo "	</td>\n";
			echo "	</tr>\n";
			echo "	</table>\n";

			echo "		</td>\n";
			echo "		<td class='vtable'>";
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
			echo "		</td>\n";
			echo "		<td>\n";
			echo "			<input type='button' class='btn' value=\"".$text['button-add']."\" onclick='submit_form();'>\n";
			echo "		</td>\n";
			echo "		</table>\n";
			echo "		<br />\n";
			echo $text['description-provisioning']."\n";

			echo "</td>\n";
			echo "</tr>\n";
		}
	}

	if (if_group("superadmin") || (if_group("admin") && $billing_app_exists)) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-accountcode']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if ($billing_app_exists) {
			$sql_accountcode = "SELECT type_value FROM v_billings WHERE domain_uuid = '".$domain_uuid."'";
			echo "<select name='accountcode' id='accountcode' class='formfld'>\n";
			$prep_statement_accountcode = $db->prepare(check_sql($sql_accountcode));
			$prep_statement_accountcode->execute();
			$result_accountcode = $prep_statement_accountcode->fetchAll(PDO::FETCH_NAMED);
			foreach ($result_accountcode as &$row_accountcode) {
				$selected = '';
				if (($action == "add") && ($row_accountcode['type_value'] == $_SESSION['domain_name'])){
					$selected='selected="selected"';
				}
				elseif ($row_accountcode['type_value'] == $accountcode){
					$selected='selected="selected"';
				}
				echo "<option value=\"".$row_accountcode['type_value']."\" $selected>".$row_accountcode['type_value']."</option>\n";
			}
			unset($sql_accountcode, $prep_statement_accountcode, $result_accountcode);
			echo "</select>";
		}
		else {
			if ($action == "add") { $accountcode = $_SESSION['domain_name']; }
			echo "<input class='formfld' type='text' name='accountcode' maxlength='255' value=\"".$accountcode."\">\n";
		}
		echo "<br />\n";
		echo $text['description-accountcode']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-effective_caller_id_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='effective_caller_id_name' maxlength='255' value=\"$effective_caller_id_name\">\n";
	echo "<br />\n";
	echo $text['description-effective_caller_id_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-effective_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='effective_caller_id_number' min='0' step='1' maxlength='255' value=\"$effective_caller_id_number\">\n";
	echo "<br />\n";
	echo $text['description-effective_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-outbound_caller_id_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('outbound_caller_id_select')) {
		if (count($destinations) > 0) {
			echo "	<select name='outbound_caller_id_name' id='outbound_caller_id_name' class='formfld'>\n";
			echo "	<option value=''></option>\n";
			foreach ($destinations as &$row) {
				$tmp = $row["destination_caller_id_name"];
				if(strlen($tmp) == 0){
					$tmp = $row["destination_description"];
				}
				if(strlen($tmp) > 0){
					if ($outbound_caller_id_name == $tmp) {
						echo "		<option value='".$tmp."' selected='selected'>".$tmp."</option>\n";
					}
					else {
						echo "		<option value='".$tmp."'>".$tmp."</option>\n";
					}
				}
			}
			echo "		</select>\n";
			echo "<br />\n";
			echo $text['description-outbound_caller_id_name-select']."\n";
		}
		else {
			echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
		}
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
	echo "    ".$text['label-outbound_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('outbound_caller_id_select')) {
		if (count($destinations) > 0) {
			echo "	<select name='outbound_caller_id_number' id='outbound_caller_id_number' class='formfld'>\n";
			echo "	<option value=''></option>\n";
			foreach ($destinations as &$row) {
				$tmp = $row["destination_caller_id_number"];
				if(strlen($tmp) == 0){
					$tmp = $row["destination_number"];
				}
				if(strlen($tmp) > 0){
					if ($outbound_caller_id_number == $tmp) {
						echo "		<option value='".$tmp."' selected='selected'>".$tmp."</option>\n";
					}
					else {
						echo "		<option value='".$tmp."'>".$tmp."</option>\n";
					}
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
		echo "    <input class='formfld' type='text' name='outbound_caller_id_number' maxlength='255' min='0' step='1' value=\"$outbound_caller_id_number\">\n";
		echo "<br />\n";
		echo $text['description-outbound_caller_id_number-custom']."\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-emergency_caller_id_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='emergency_caller_id_name' maxlength='255' value=\"$emergency_caller_id_name\">\n";
	echo "<br />\n";
	echo $text['description-emergency_caller_id_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-emergency_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='emergency_caller_id_number' maxlength='255' min='0' step='1' value=\"$emergency_caller_id_number\">\n";
	echo "<br />\n";
	echo $text['description-emergency_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-directory_full_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='directory_full_name' maxlength='255' value=\"$directory_full_name\">\n";
	echo "<br />\n";
	echo $text['description-directory_full_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-directory_visible']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='directory_visible'>\n";
	if ($directory_visible == "true") {
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
	echo "    ".$text['label-directory_exten_visible']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='directory_exten_visible'>\n";
	if ($directory_exten_visible == "true") {
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
	echo "    ".$text['label-limit_max']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='number' name='limit_max' maxlength='255' min='0' step='1' value=\"$limit_max\">\n";
	echo "<br />\n";
	echo $text['description-limit_max']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-limit_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='limit_destination' maxlength='255' value=\"$limit_destination\">\n";
	echo "<br />\n";
	echo $text['description-limit_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='voicemail_enabled'>\n";
		if ($voicemail_enabled == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($voicemail_enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-voicemail_enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_mail_to']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='voicemail_mail_to' maxlength='255' value=\"$voicemail_mail_to\">\n";
		echo "<br />\n";
		echo $text['description-voicemail_mail_to']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_file']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='voicemail_file' id='voicemail_file' onchange=\"if (this.selectedIndex != 2) { document.getElementById('voicemail_local_after_email').selectedIndex = 0; }\">\n";
		echo "    	<option value='' ".(($voicemail_file == "listen") ? "selected='selected'" : null).">".$text['option-voicemail_file_listen']."</option>\n";
		echo "    	<option value='link' ".(($voicemail_file == "link") ? "selected='selected'" : null).">".$text['option-voicemail_file_link']."</option>\n";
		echo "    	<option value='attach' ".(($voicemail_file == "attach") ? "selected='selected'" : null).">".$text['option-voicemail_file_attach']."</option>\n";
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-voicemail_file']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-voicemail_local_after_email']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='voicemail_local_after_email' id='voicemail_local_after_email' onchange=\"if (this.selectedIndex == 1) { document.getElementById('voicemail_file').selectedIndex = 2; }\">\n";
		echo "    	<option value='true' ".(($voicemail_local_after_email == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "    	<option value='false' ".(($voicemail_local_after_email == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-voicemail_local_after_email']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_missed_call')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-missed_call']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='missed_call_app' id='missed_call_app' onchange=\"if (this.selectedIndex != 0) { document.getElementById('missed_call_data').style.display = ''; document.getElementById('missed_call_data').focus(); } else { document.getElementById('missed_call_data').style.display='none'; }\">\n";
		echo "		<option value=''></option>\n";
		echo "    	<option value='email' ".(($missed_call_app == "email" && $missed_call_data != '') ? "selected='selected'" : null).">".$text['label-email']."</option>\n";
		//echo "    	<option value='text' ".(($missed_call_app == "text" && $missed_call_data != '') ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		//echo "    	<option value='url' ".(($missed_call_app == "url" && $missed_call_data != '') ? "selected='selected'" : null).">".$text['label-url']."</option>\n";
		echo "    </select>\n";
		$missed_call_data = ($missed_call_app == 'text') ? format_phone($missed_call_data) : $missed_call_data;
		echo "    <input class='formfld' type='text' name='missed_call_data' id='missed_call_data' maxlength='255' value=\"$missed_call_data\" style='min-width: 200px; width: 200px; ".(($missed_call_app == '' || $missed_call_data == '') ? "display: none;" : null)."'>\n";
		echo "<br />\n";
		echo $text['description-missed_call']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_toll')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-toll_allow']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION['toll allow']['name'])) {
			echo "	<select class='formfld' name='toll_allow'>\n";
			echo "		<option value=''></option>\n";
			foreach ($_SESSION['toll allow']['name'] as $name) {
				if ($_SESSION['call group']['name'] == $call_group) {
					echo "		<option value='$name' selected='selected'>$name</option>\n";
				}
				else {
					echo "		<option value='$name'>$name</option>\n";
				}
			}
			echo "	</select>\n";
		}
		else {
			echo "    <input class='formfld' type='text' name='toll_allow' maxlength='255' value=\"$toll_allow\">\n";
		}
		echo "<br />\n";
		echo $text['description-toll_allow']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='call_timeout' maxlength='255' min='1' step='1' value=\"$call_timeout\">\n";
	echo "<br />\n";
	echo $text['description-call_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_group']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (is_array($_SESSION['call group']['name'])) {
		echo "	<select class='formfld' name='call_group'>\n";
		echo "		<option value=''></option>\n";
		foreach ($_SESSION['call group']['name'] as $name) {
			if ($_SESSION['call group']['name'] == $call_group) {
				echo "		<option value='$name' selected='selected'>$name</option>\n";
			}
			else {
				echo "		<option value='$name'>$name</option>\n";
			}
		}
		echo "	</select>\n";
	} else {
		echo "	<input class='formfld' type='text' name='call_group' maxlength='255' value=\"$call_group\">\n";
	}
	echo "<br />\n";
	echo $text['description-call_group']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('extension_call_screen')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-call_screen_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='call_screen_enabled'>\n";
		if ($call_screen_enabled == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($call_screen_enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-call_screen_enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_user_record')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-user_record']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='user_record'>\n";
		echo "    <option value=''>".$text['label-user_record_none']."</option>\n";
		if ($user_record == "all") {
			echo "    <option value='all' selected='selected'>".$text['label-user_record_all']."</option>\n";
		}
		else {
			echo "    <option value='all'>".$text['label-user_record_all']."</option>\n";
		}
		if ($user_record == "local") {
			echo "    <option value='local' selected='selected'>".$text['label-user_record_local']."</option>\n";
		}
		else {
			echo "    <option value='local'>".$text['label-user_record_local']."</option>\n";
		}
		if ($user_record == "inbound") {
			echo "    <option value='inbound' selected='selected'>".$text['label-user_record_inbound']."</option>\n";
		}
		else {
			echo "    <option value='inbound'>".$text['label-user_record_inbound']."</option>\n";
		}
		if ($user_record == "outbound") {
			echo "    <option value='outbound' selected='selected'>".$text['label-user_record_outbound']."</option>\n";
		}
		else {
			echo "    <option value='outbound'>".$text['label-user_record_outbound']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-user_record']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/music_on_hold')) {
		echo "<tr>\n";
		echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-hold_music']."\n";
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
	}

	if (if_group("superadmin")) {
		if (strlen($user_context) == 0) {
			$user_context = $_SESSION['domain_name'];
		}
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-user_context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='user_context' maxlength='255' value=\"$user_context\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-user_context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	//--- begin: show_advanced -----------------------

	echo "<tr>\n";
	echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

	echo "	<div id=\"show_advanced_box\">\n";
	echo "		<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "		<tr>\n";
	echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">&nbsp;</td>\n";
	echo "		<td width=\"70%\" class=\"vtable\">\n";
	echo "			<input type=\"button\" class=\"btn\" onClick=\"show_advanced_config()\" value=\"".$text['button-advanced']."\"></input>\n";
	echo "		</td>\n";
	echo "		</tr>\n";
	echo "		</table>\n";
	echo "	</div>\n";

	echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-auth_acl']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "   <input class='formfld' type='text' name='auth_acl' maxlength='255' value=\"$auth_acl\">\n";
	echo "   <br />\n";
	echo $text['description-auth_acl']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-cidr']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='cidr' maxlength='255' value=\"$cidr\">\n";
	echo "<br />\n";
	echo $text['description-cidr']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-sip_force_contact']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='sip_force_contact'>\n";
	echo "    <option value=''></option>\n";
	switch ($sip_force_contact) {
		case "NDLB-connectile-dysfunction" : 		$selected[1] = "selected='selected'"; 	break;
		case "NDLB-connectile-dysfunction-2.0" : 	$selected[2] = "selected='selected'"; 	break;
		case "NDLB-tls-connectile-dysfunction" : 	$selected[3] = "selected='selected'"; 	break;
	}
	echo "    <option value='NDLB-connectile-dysfunction' ".$selected[1].">".$text['label-rewrite_contact_ip_and_port']."</option>\n";
	echo "    <option value='NDLB-connectile-dysfunction-2.0' ".$selected[2].">".$text['label-rewrite_contact_ip_and_port_2']."</option>\n";
	echo "    <option value='NDLB-tls-connectile-dysfunction' ".$selected[3].">".$text['label-rewrite_tls_contact_port']."</option>\n";
	unset($selected);
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-sip_force_contact']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-sip_force_expires']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='number' name='sip_force_expires' maxlength='255' min='1' step='1' value=\"$sip_force_expires\">\n";
	echo "<br />\n";
	echo $text['description-sip_force_expires']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (if_group("superadmin")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-nibble_account']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='nibble_account' maxlength='255' value=\"$nibble_account\">\n";
		echo "<br />\n";
		echo $text['description-nibble_account']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-mwi_account']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='mwi_account' maxlength='255' value=\"$mwi_account\">\n";
	echo "<br />\n";
	echo $text['description-mwi_account']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-sip_bypass_media']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='sip_bypass_media'>\n";
	echo "    <option value=''></option>\n";
	switch ($sip_bypass_media) {
		case "bypass-media" : 				$selected[1] = "selected='selected'"; 	break;
		case "bypass-media-after-bridge" : 	$selected[2] = "selected='selected'"; 	break;
		case "proxy-media" : 				$selected[3] = "selected='selected'"; 	break;
	}
	echo "    <option value='bypass-media' ".$selected[1].">".$text['label-bypass_media']."</option>\n";
	echo "    <option value='bypass-media-after-bridge'".$selected[2].">".$text['label-bypass_media_after_bridge']."</option>\n";
	echo "    <option value='proxy-media'".$selected[3].">".$text['label-proxy_media']."</option>\n";
	unset($selected);
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-sip_bypass_media']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('extension_absolute_codec_string')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-absolute_codec_string']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='absolute_codec_string' maxlength='255' value=\"$absolute_codec_string\">\n";
		echo "<br />\n";
		echo $text['description-absolute_codec_string']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='domain_uuid'>\n";
		foreach ($_SESSION['domains'] as $row) {
			if ($row['domain_uuid'] == $domain_uuid) {
				echo "    <option value='".$row['domain_uuid']."' selected='selected'>".$row['domain_name']."</option>\n";
			}
			else {
				echo "    <option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
			}
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('extension_dial_string')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-dial_string']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='dial_string' maxlength='4096' value=\"$dial_string\">\n";
		echo "<br />\n";
		echo $text['description-dial_string']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "	</table>\n";
	echo "	</div>";

	echo "</td>\n";
	echo "</tr>\n";

	//--- end: show_advanced -----------------------

	if (permission_exists('extension_enabled')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='enabled'>\n";
		if ($enabled == "true") {
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
	echo "    ".$text['label-description']."\n";
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
		echo "		<input type='hidden' name='extension_uuid' value='".$extension_uuid."'>\n";
		echo "		<input type='hidden' name='id' id='id' value='".$extension_uuid."'>";
		if (!permission_exists('extension_domain')) {
			echo "		<input type='hidden' name='domain_uuid' id='domain_uuid' value='".$_SESSION['domain_uuid']."'>";
		}
		echo "		<input type='hidden' name='delete_type' id='delete_type' value=''>";
		echo "		<input type='hidden' name='delete_uuid' id='delete_uuid' value=''>";
	}
	echo "			<br>";
	echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	echo "<script>\n";
//capture enter key to submit form
	echo "	$(window).keypress(function(event){\n";
	echo "		if (event.which == 13) { submit_form(); }\n";
	echo "	});\n";
// convert password fields to
	echo "	function submit_form() {\n";
	echo "		$('input:password').css('visibility','hidden');\n";
	echo "		$('input:password').attr({type:'text'});\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
