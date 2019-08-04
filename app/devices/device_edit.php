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
	Copyright (C) 2008-2019 All Rights Reserved.

*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_add') || permission_exists('device_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//check for duplicates
	if ($_GET["check"] == 'duplicate') {
		//mac address
			if ($_GET["mac"] != '' && $_GET["mac"] != "000000000000") {
				$sql = "select ";
				$sql .= "d2.domain_name ";
				$sql .= "from ";
				$sql .= "v_devices as d1, ";
				$sql .= "v_domains as d2 ";
				$sql .= "where ";
				$sql .= "d1.domain_uuid = d2.domain_uuid and ";
				$sql .= "d1.device_mac_address = :device_mac_address ";
				if (is_uuid($_GET["device_uuid"])) {
					$sql .= " and d1.device_uuid <> :device_uuid ";
				}
				$parameters['device_mac_address'] = $_GET["mac"];
				$parameters['device_uuid'] = $_GET["device_uuid"];
				$database = new database;
				$domain_name = $database->select($sql, $parameters, 'column');
				if ($domain_name != '') {
					echo $text['message-duplicate'].(if_group("superadmin") && $_SESSION["domain_name"] != $domain_name ? ": ".$domain_name : null);
				}
				unset($sql, $parameters, $domain_name);
			}

		//username
			if ($_GET['username'] != '') {
				$sql = "select ";
				$sql .= "d2.domain_name, ";
				$sql .= "d1.device_mac_address ";
				$sql .= "from ";
				$sql .= "v_devices as d1, ";
				$sql .= "v_domains as d2 ";
				$sql .= "where ";
				$sql .= "d1.domain_uuid = d2.domain_uuid and ";
				$sql .= "d1.device_username = :device_username ";
				if (is_uuid($_GET['domain_uuid'])) {
					$sql .= "and d2.domain_uuid = :domain_uuid ";
				}
				if (is_uuid($_GET['device_uuid'])) {
					$sql .= "and d1.device_uuid <> :device_uuid ";
				}
				$parameters['device_username'] = $_GET["username"];
				$parameters['domain_uuid'] = $_GET["domain_uuid"];
				$parameters['device_uuid'] = $_GET["device_uuid"];
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0 && $row['domain_name'] != '') {
					echo $text['message-duplicate_username'].(if_group("superadmin") ? ": ".format_mac($row['device_mac_address']).($_SESSION["domain_name"] != $row["domain_name"] ? " (".$row["domain_name"].")" : null) : null);
				}
				unset($sql, $parameters, $row);
			}

		exit;
	}

//include the device class
	require_once "app/devices/resources/classes/device.php";

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$device_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get total device count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['devices']['numeric'] != '') {
			$sql = "select count(*) from v_devices where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$total_devices = $database->select($sql, $parameters, 'column');
			if ($total_devices >= $_SESSION['limit']['devices']['numeric']) {
				message::add($text['message-maximum_devices'].' '.$_SESSION['limit']['devices']['numeric'], 'negative');
				header('Location: devices.php');
				exit;
			}
			unset($sql, $parameters, $total_devices);
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//device mac address
			if (permission_exists('device_mac_address')) {
				$device_mac_address = $_POST["device_mac_address"];
				$device_mac_address = strtolower(preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address));
				$_POST["device_mac_address"] = $device_mac_address;
			}
			else {
				$sql = "select * from v_devices ";
				$sql .= "where device_uuid = :device_uuid ";
				$parameters['device_uuid'] = $device_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					$device_mac_address = $row["device_mac_address"];
					$_POST["device_mac_address"] = $device_mac_address;
				}
				unset($sql, $parameters, $row);
			}
		//get assigned user
			$device_user_uuid = $_POST["device_user_uuid"];
		//devices
			$device_label = $_POST["device_label"];
			$device_vendor = $_POST["device_vendor"];
			$device_uuid_alternate = $_POST["device_uuid_alternate"];
			$device_model = $_POST["device_model"];
			$device_firmware_version = $_POST["device_firmware_version"];
			$device_enabled = $_POST["device_enabled"];
			$device_template = $_POST["device_template"];
			$device_description = $_POST["device_description"];
		//lines
			$line_number = $_POST["line_number"];
			$server_address = $_POST["server_address"];
			$outbound_proxy_primary = $_POST["outbound_proxy_primary"];
			$outbound_proxy_secondary = $_POST["outbound_proxy_secondary"];
			$display_name = $_POST["display_name"];
			$user_id = $_POST["user_id"];
			$auth_id = $_POST["auth_id"];
			$password = $_POST["password"];
		//profile
			$device_profile_uuid = $_POST["device_profile_uuid"];
		//keys
			$device_key_category = $_POST["device_key_category"];
			$device_key_id = $_POST["device_key_id"];
			$device_key_type = $_POST["device_key_type"];
			$device_key_line = $_POST["device_key_line"];
			$device_key_value = $_POST["device_key_value"];
			$device_key_extension = $_POST["device_key_extension"];
			$device_key_label = $_POST["device_key_label"];
			$device_key_icon = $_POST["device_key_icon"];
		//settings
			//$device_setting_category = $_POST["device_setting_category"]);
			$device_setting_subcategory = $_POST["device_setting_subcategory"];
			//$device_setting_name = $_POST["device_setting_name"];
			$device_setting_value = $_POST["device_setting_value"];
			$device_setting_enabled = $_POST["device_setting_enabled"];
			$device_setting_description = $_POST["device_setting_description"];
	}

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		$device_vendor = device::get_vendor($device_mac_address);
	}

//add or update the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//check for all required data
			$msg = '';
			//if (strlen($device_mac_address) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			//if (strlen($device_label) == 0) { $msg .= "Please provide: Label<br>\n"; }
			//if (strlen($device_vendor) == 0) { $msg .= "Please provide: Vendor<br>\n"; }
			//if (strlen($device_model) == 0) { $msg .= "Please provide: Model<br>\n"; }
			//if (strlen($device_firmware_version) == 0) { $msg .= "Please provide: Firmware Version<br>\n"; }
			//if (strlen($device_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
			//if (strlen($device_template) == 0) { $msg .= "Please provide: Template<br>\n"; }
			//if (strlen($device_username) == 0) { $msg .= "Please provide: Username<br>\n"; }
			//if (strlen($device_password) == 0) { $msg .= "Please provide: Password<br>\n"; }
			//if (strlen($device_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
			if (strlen($msg) > 0) {
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

		//add or update the database
			if ($_POST["persistformvar"] != "true") {
				//add domain_uuid to the array
					foreach ($_POST as $key => $value) {
						if (is_array($value)) {
							$y = 0;
							foreach ($value as $k => $v) {
								if (!isset($v["domain_uuid"])) {
									$_POST[$key][$y]["domain_uuid"] = $_POST["domain_uuid"];
								}
								$y++;
							}
						}
					}

				//array cleanup
					$x = 0;
					unset($_POST["target_file"]);
					unset($_POST["file_action"]);

					foreach ($_POST["device_lines"] as $row) {
						//unset the empty row
							if (strlen($row["line_number"]) == 0) {
								unset($_POST["device_lines"][$x]);
							}
						//unset device_detail_uuid if the field has no value
							if (strlen($row["device_line_uuid"]) == 0) {
								unset($_POST["device_lines"][$x]["device_line_uuid"]);
							}
						//increment the row
							$x++;
					}
					$x = 0;
					foreach ($_POST["device_keys"] as $row) {
						//unset the empty row
							if (strlen($row["device_key_category"]) == 0) {
								unset($_POST["device_keys"][$x]);
							}
						//unset device_detail_uuid if the field has no value
							if (!is_uuid($row["device_key_uuid"])) {
								unset($_POST["device_keys"][$x]["device_key_uuid"]);
							}
						//increment the row
							$x++;
					}
					$x = 0;
					foreach ($_POST["device_settings"] as $row) {
						//unset the empty row
							if (strlen($row["device_setting_subcategory"]) == 0) {
								unset($_POST["device_settings"][$x]);
							}
						//unset device_detail_uuid if the field has no value
							if (!is_uuid($row["device_setting_uuid"])) {
								unset($_POST["device_settings"][$x]["device_setting_uuid"]);
							}
						//increment the row
							$x++;
					}

				//set the default
					$save = true;

				//check to see if the mac address exists
					if ($action == "add") {
						if ($device_mac_address == "" || $device_mac_address == "000000000000") {
							//allow duplicates to be used as templaes
						}
						else {
							$save = true;
						}
					}
					else {
						$save = true;
					}

				//set the device_enabled_date
					if ($_POST["device_enabled"] == "true") {
 						$_POST["device_enabled_date"] = 'now()';
					}

				//prepare the array
					$array['devices'][] = $_POST;

				//save the device
					if ($save) {
						$database = new database;
						$database->app_name = 'devices';
						$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
						if (is_uuid($device_uuid)) {
							$database->uuid($device_uuid);
						}
						$database->save($array);
						$response = $database->message;
						if (is_uuid($response['uuid'])) {
							$device_uuid = $response['uuid'];
						}
					}

				//write the provision files
					if (strlen($_SESSION['provision']['path']['text']) > 0) {
						$prov = new provision;
						$prov->domain_uuid = $domain_uuid;
						$response = $prov->write();
					}

				//set the message
					if (!isset($_SESSION['message'])) {
						if ($save) {
							if ($action == "add") {
								//save the message to a session variable
									message::add($text['message-add']);
							}
							if ($action == "update") {
								//save the message to a session variable
									message::add($text['message-update']);
							}
							//redirect the browser
								header("Location: device_edit.php?id=$device_uuid");
								exit;
						}
					}

			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_devices ";
		$sql .= "where device_uuid = :device_uuid ";
		$parameters['device_uuid'] = $device_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$device_mac_address = $row["device_mac_address"];
			$device_provisioned_ip = $row["device_provisioned_ip"];
			$domain_uuid = $row["domain_uuid"];
			$device_label = $row["device_label"];
			//$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);
			$device_label = $row["device_label"];
			$device_user_uuid = $row["device_user_uuid"];
			$device_username = $row["device_username"];
			$device_password = $row["device_password"];
			$device_vendor = $row["device_vendor"];
			$device_uuid_alternate = $row["device_uuid_alternate"];
			$device_model = $row["device_model"];
			$device_firmware_version = $row["device_firmware_version"];
			$device_enabled = $row["device_enabled"];
			$device_template = $row["device_template"];
			$device_profile_uuid = $row["device_profile_uuid"];
			$device_description = $row["device_description"];
		}
		unset($sql, $parameters, $row);
	}

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		$template_array = explode("/", $device_template);
		$device_vendor = $template_array[0];
	}

//set the sub array index
	$x = "999";

//alternate device settings
	if (is_uuid($device_uuid_alternate)) {
		$sql = "select * from v_devices ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$sql .= "and device_uuid = :device_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['device_uuid'] = $device_uuid_alternate;
		$database = new database;
		$device_alternate = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get device lines
	$sql = "select * from v_device_lines ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by cast(line_number as int) asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_lines = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$device_lines[$x]['line_number'] = '';
	$device_lines[$x]['server_address'] = '';
	$device_lines[$x]['outbound_proxy_primary'] = $_SESSION['provision']['outbound_proxy_primary']['text'];
	$device_lines[$x]['outbound_proxy_secondary'] = $_SESSION['provision']['outbound_proxy_secondary']['text'];
	$device_lines[$x]['server_address_primary'] = $_SESSION['provision']['server_address_primary']['text'];
	$device_lines[$x]['server_address_secondary'] = $_SESSION['provision']['server_address_secondary']['text'];
	$device_lines[$x]['display_name'] = '';
	$device_lines[$x]['user_id'] = '';
	$device_lines[$x]['auth_id'] = '';
	$device_lines[$x]['password'] = '';
	$device_lines[$x]['shared_line'] = '';
	$device_lines[$x]['enabled'] = '';
	$device_lines[$x]['sip_port'] = $_SESSION['provision']['line_sip_port']['numeric'];
	$device_lines[$x]['sip_transport'] = $_SESSION['provision']['line_sip_transport']['text'];
	$device_lines[$x]['register_expires'] = $_SESSION['provision']['line_register_expires']['numeric'];

//get device keys
	$sql = "select * from v_device_keys ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by ";
	$sql .= "device_key_vendor asc, ";
	$sql .= "case device_key_category ";
	$sql .= "when 'line' then 1 ";
	$sql .= "when 'memory' then 2 ";
	$sql .= "when 'programmable' then 3 ";
	$sql .= "when 'expansion' then 4 ";
	$sql .= "when 'expansion-1' then 5 ";
	$sql .= "when 'expansion-2' then 6 ";
	$sql .= "when 'expansion-3' then 7 ";
	$sql .= "when 'expansion-4' then 8 ";
	$sql .= "when 'expansion-5' then 9 ";
	$sql .= "when 'expansion-6' then 10 ";
	$sql .= "else 100 end, ";
	$sql .= $db_type == "mysql" ? "device_key_id asc " : "cast(device_key_id as numeric) asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_keys = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$device_keys[$x]['device_key_category'] = '';
	$device_keys[$x]['device_key_id'] = '';
	$device_keys[$x]['device_key_type'] = '';
	$device_keys[$x]['device_key_line'] = '';
	$device_keys[$x]['device_key_value'] = '';
	$device_keys[$x]['device_key_extension'] = '';
	$device_keys[$x]['device_key_label'] = '';
	$device_keys[$x]['device_key_icon'] = '';

//get the device vendors
	$sql = "select name ";
	$sql .= "from v_device_vendors ";
	$sql .= "where enabled = 'true' ";
	$sql .= "order by name asc ";
	$database = new database;
	$device_vendors = $database->select($sql, null, 'all');
	unset($sql);

//get the vendor functions
	$sql = "select v.name as vendor_name, f.name, f.value ";
	$sql .= "from v_device_vendors as v, v_device_vendor_functions as f ";
	$sql .= "where v.device_vendor_uuid = f.device_vendor_uuid ";
	$sql .= "and v.enabled = 'true' ";
	$sql .= "and f.enabled = 'true' ";
	$sql .= "order by v.name asc, f.name asc ";
	$database = new database;
	$vendor_functions = $database->select($sql, null, 'all');
	unset($sql);

//get device settings
	$sql = "select * from v_device_settings ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by device_setting_subcategory asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$device_settings[$x]['device_setting_name'] = '';
	$device_settings[$x]['device_setting_value'] = '';
	$device_settings[$x]['enabled'] = '';
	$device_settings[$x]['device_setting_description'] = '';

//get the users
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		$device_vendor = device::get_vendor($device_mac_address);
	}

//get the device line info for provision button
	foreach($device_lines as $row) {
		if (strlen($row['user_id']) > 0) {
			$user_id = $row['user_id'];
		}
		if (strlen($row['server_address']) > 0) {
			$server_address = $row['server_address'];
		}
	}

//get the sip profile name
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		$command = "sofia_contact */".$user_id."@".$server_address;
		$contact_string = event_socket_request($fp, "api ".$command);
		if (substr($contact_string, 0, 5) == "sofia") {
			$contact_array = explode("/", $contact_string);
			$sip_profile_name = $contact_array[1];
		}
		else {
			$sip_profile_name = 'internal';
		}
	}

//show the header
	require_once "resources/header.php";

//javascript to change select to input and back again
	?>
	<script language="javascript">
		var objs;

		function change_to_input(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			//tb.setAttribute('id', 'ivr_menu_option_param');
			tb.setAttribute('style', 'width:175px;');
			tb.value=obj.options[obj.selectedIndex].value;
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.setAttribute('style', 'margin-left: 4px;');
			tbb.type='button';
			tbb.value=$("<div />").html('&#9665;').text();
			tbb.objs=[obj,tb,tbb];
			tbb.onclick=function(){ replace_param(this.objs); }
			obj.parentNode.insertBefore(tb,obj);
			obj.parentNode.insertBefore(tbb,obj);
			obj.parentNode.removeChild(obj);
			replace_param(this.objs);
		}

		function replace_param(obj){
			obj[2].parentNode.insertBefore(obj[0],obj[2]);
			obj[0].parentNode.removeChild(obj[1]);
			obj[0].parentNode.removeChild(obj[2]);
		}

	</script>

<?php

//select file download javascript
	if (permission_exists("device_files")) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	var fade_speed = 400;\n";
		echo "	function show_files() {\n";
		echo "		document.getElementById('file_action').value = 'files';\n";
		echo "		$('#button_back_location').fadeOut(fade_speed);\n";
		echo "		$('#button_files').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_back').fadeIn(fade_speed);\n";
		echo "			$('#target_file').fadeIn(fade_speed);\n";
		echo "			$('#button_download').fadeIn(fade_speed);\n";
		echo "		});";
		echo "	}";
		echo "	function hide_files() {\n";
		echo "		document.getElementById('file_action').value = '';\n";
		echo "		$('#button_download').fadeOut(fade_speed);\n";
		echo "		$('#target_file').fadeOut(fade_speed);\n";
		echo "		$('#button_back').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_files').fadeIn(fade_speed)\n";
		echo "			$('#button_back_location').fadeIn(fade_speed);\n";
		echo "		});";
		echo "		document.getElementById('target_file').selectedIndex = 0;\n";
		echo "	}\n";

		echo "	function download(d) {\n";
		echo "		if (d == '".$text['label-download']."') return;\n";
		if ($_SESSION['provision']['http_domain_filter']['boolean'] == "false") {
			$domain_name = $_SERVER["HTTP_HOST"];
		}
		else {
			$domain_name = $_SESSION['domain_name'];
		}

		if (!isset($_SERVER['HTTP_PROTOCOL'])) {
			$_SERVER['HTTP_PROTOCOL'] = 'http';
			if (isset($_SERVER['REQUEST_SCHEME'])) { $_SERVER['HTTP_PROTOCOL'] = $_SERVER['REQUEST_SCHEME']; }
			if ($_SERVER['HTTPS'] == 'on') { $_SERVER['HTTP_PROTOCOL'] = 'https'; }
			if ($_SERVER['SERVER_PORT'] == '443') { $_SERVER['HTTP_PROTOCOL'] = 'https'; }
		}
		echo "		window.location = '".$_SERVER['HTTP_PROTOCOL']."://".$domain_name.PROJECT_PATH."/app/provision/index.php?mac=".escape($device_mac_address)."&file=' + d + '&content_type=application/octet-stream';\n";
		echo "	}\n";

		echo "\n";
		echo "	$( document ).ready(function() {\n";
		echo "		$('#default_setting_search').focus();\n";
		if ($search == '') {
			echo "		// scroll to previous category\n";
			echo "		var category_span_id;\n";
			echo "		var url = document.location.href;\n";
			echo "		var hashindex = url.indexOf('#');\n";
			echo "		if (hashindex == -1) { }\n";
			echo "		else {\n";
			echo "			category_span_id = url.substr(hashindex + 1);\n";
			echo "		}\n";
			echo "		if (category_span_id) {\n";
			echo "			$('#page').animate({scrollTop: $('#anchor_'+category_span_id).offset().top - 200}, 'slow');\n";
			echo "		}\n";
		}
		echo "	});\n";
		echo "</script>";
	}

//add the QR code
	if (permission_exists("device_line_password") && $device_template == "grandstream/wave") {
		//set the mode
		if (isset($_SESSION['theme']['qr_image'])) {
			if (strlen($_SESSION['theme']['qr_image']) > 0) {
				$mode = '4';
			}
			else {
				$mode = '0';
			}
		}
		else {
			$mode = '4';
		}

		//get the device line settings
		$row = $device_lines[0];

		//set the outbound proxy settings
		if (strlen($row['outbound_proxy_primary']) == 0) {
			$outbound_proxy_primary = $row['server_address'];
		}
		else {
			$outbound_proxy_primary = $row['outbound_proxy_primary'];
		}
		$outbound_proxy_secondary = $row['outbound_proxy_secondary'];

		//build the xml
		$xml =  "<?xml version='1.0' encoding='utf-8'?>";
		$xml .= "<AccountConfig version='1'>";
		$xml .= "<Account>";
		$xml .= "<RegisterServer>".$row['server_address']."</RegisterServer>";
		$xml .= "<OutboundServer>".$outbound_proxy_primary.":".$row['sip_port']."</OutboundServer>";
		$xml .= "<SecOutboundServer>".$outbound_proxy_secondary.":".$row['sip_port']."</SecOutboundServer>";
		$xml .= "<UserID>".$row['user_id']."</UserID>";
		$xml .= "<AuthID>".$row['auth_id']."</AuthID>";
		$xml .= "<AuthPass>".$row['password']."</AuthPass>";
		$xml .= "<AccountName>".$row['user_id']."</AccountName>";
		$xml .= "<DisplayName>".$row['display_name']."</DisplayName>";
		$xml .= "<Dialplan>{x+|*x+|*++}</Dialplan>";
		$xml .= "<RandomPort>0</RandomPort>";
		$xml .= "<Voicemail>*97</Voicemail>";
		$xml .= "</Account>";
		$xml .= "</AccountConfig>";

		//qr code generation
		$_GET['type'] = "text";
		echo "<input type='hidden' id='qr_card' value=\"".escape($xml)."\">";
		echo "<style>";
		echo "	#qr_code_container {";
		echo "		z-index: 999999; ";
		echo "		position: absolute; ";
		echo "		left: 0px; ";
		echo "		top: 0px; ";
		echo "		right: 0px; ";
		echo "		bottom: 0px; ";
		echo "		text-align: center; ";
		echo "		vertical-align: middle;";
		echo "	}";
		echo "	#qr_code {";
		echo "		display: block; ";
		echo "		width: 650px; ";
		echo "		height: 650px; ";
		echo "		-webkit-box-shadow: 0px 1px 20px #888; ";
		echo "		-moz-box-shadow: 0px 1px 20px #888; ";
		echo "		box-shadow: 0px 1px 20px #888;";
		echo "	}";
		echo "</style>";
		echo "<script src='".PROJECT_PATH."/resources/jquery/jquery.qrcode-0.8.0.min.js'></script>";
		echo "<script language='JavaScript' type='text/javascript'>";
		echo "	$(document).ready(function() {";
		echo "		$(window).load(function() {";
		echo "			$('#qr_code').qrcode({ ";
		echo "				render: 'canvas', ";
		echo "				minVersion: 6, ";
		echo "				maxVersion: 40, ";
		echo "				ecLevel: 'H', ";
		echo "				size: 650, ";
		echo "				radius: 0.2, ";
		echo "				quiet: 6, ";
		echo "				background: '#fff', ";
		echo "				mode: ".$mode.", ";
		echo "				mSize: 0.2, ";
		echo "				mPosX: 0.5, ";
		echo "				mPosY: 0.5, ";
		echo "				image: $('#img-buffer')[0], ";
		echo "				text: document.getElementById('qr_card').value ";
		echo "			});";
		echo "		});";
		echo "	});";
		echo "</script>";
		if (isset($_SESSION['theme']['qr_image'])) {
			echo "<img id='img-buffer' src='".$_SESSION["theme"]["qr_image"]["text"]."' style='display: none;'>";
		}
		else {
			echo "<img id='img-buffer' src='".PROJECT_PATH."/themes/".$_SESSION["domain"]["template"]["name"]."/images/qr_code.png' style='display: none;'>";
		}
	}

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input type='hidden' name='file_action' id='file_action' value='' />\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' nowrap='nowrap' valign='top'>";
	echo "	<b>".$text['header-device']."</b>";
	echo "</td>\n";
	echo "<td align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' id='button_back_location' name='' alt='".$text['button-back']."' onclick=\"window.location='devices.php'\" value='".$text['button-back']."'/>\n";
	if (permission_exists("device_line_password") && $device_template == "grandstream/wave") {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-qr_code']."' onclick=\"$('#qr_code_container').fadeIn(400);\" value='".$text['button-qr_code']."'>\n";
	}
	echo "	<input type='button' class='btn' value='".$text['button-provision']."' onclick=\"document.location.href='".PROJECT_PATH."/app/devices/cmd.php?cmd=check_sync&profile=".escape($sip_profile_name)."&user=".escape($user_id)."@".escape($server_address)."&domain=".$server_address."&agent=".escape($device_vendor)."';\">&nbsp;\n";
	if (permission_exists("device_files")) {
		//get the template directory
			$prov = new provision;
			$prov->domain_uuid = $domain_uuid;
			$template_dir = $prov->template_dir;
			$files = glob($template_dir.'/'.$device_template.'/*');
		//add file buttons and the file list
			echo "		<input type='button' class='btn' id='button_files' name='' alt='".$text['button-files']."' onclick='show_files();' value='".$text['button-files']."'/>";
			echo "		<input type='button' class='btn' style='display: none;' id='button_back' name='' alt='".$text['button-back']."' onclick='hide_files();' value='".$text['button-back']."'/> ";
			echo "		<select class='formfld' style='display: none; width: auto;' name='target_file' id='target_file' onchange='download(this.value)'>\n";
			echo "			<option value=''>".$text['label-download']."</option>\n";
			foreach ($files as $file) {
				//format the mac address and
					$format = new provision();
					$mac = $format->format_mac($device_mac_address, $device_vendor);
				//render the file name
					$file_name = str_replace("{\$mac}", $mac, basename($file));
				//add the select option
					echo "		<option value='".basename($file)."'>".$file_name."</option>\n";
			}
			echo "		</select>\n";
			//echo "		<input type='button' class='btn' id='button_download' style='display: none;' alt='".$text['button-download']."' value='".$text['button-download']."' onclick='document.forms.frm.submit();'>";
	}

	if (permission_exists('device_add') && $action != "add") {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"var new_mac = prompt('".$text['message_device']."'); if (new_mac != null) { window.location='device_copy.php?id=".escape($device_uuid)."&mac=' + new_mac; }\" value='".$text['button-copy']."'/>\n";
	}
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'/>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	".$text['description-device'];
	echo "	<br><br>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td class='vncell' width='30%' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_mac_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' width='70%' align='left'>\n";
	if (permission_exists('device_mac_address')) {
		echo "	<input class='formfld' type='text' name='device_mac_address' id='device_mac_address' maxlength='255' value=\"".escape($device_mac_address)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_mac_address']."\n";
	}
	else {
		echo escape($device_mac_address);
	}
	echo "	<div style='display: none;' id='duplicate_mac_response'></div>\n";
	echo " ".escape($device_provisioned_ip)."(<a href='http://".escape($device_provisioned_ip)."' target='_blank'>http</a>|<a href='https://".escape($device_provisioned_ip)."' target='_blank'>https</a>)\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('device_label')) {
		echo "	<input class='formfld' type='text' name='device_label' maxlength='255' value=\"".escape($device_label)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_label']."\n";
	}
	else {
		echo escape($device_label);
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('device_template')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_template']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		$device = new device;
		$template_dir = $device->get_template_dir();
		echo "	<select id='device_template' name='device_template' class='formfld'>\n";
		echo "		<option value=''></option>\n";
		if (is_dir($template_dir) && is_array($device_vendors)) {
			foreach($device_vendors as $row) {
				echo "		<optgroup label='".escape($row["name"])."'>\n";
				$templates = scandir($template_dir.'/'.$row["name"]);
				foreach($templates as $dir) {
					if ($file != "." && $dir != ".." && $dir[0] != '.') {
						if (is_dir($template_dir . '/' . $row["name"] .'/'. $dir)) {
							if ($device_template == $row["name"]."/".$dir) {
								echo "			<option value='".escape($row["name"])."/".escape($dir)."' selected='selected'>".escape($row["name"])."/".escape($dir)."</option>\n";
							}
							else {
								echo "			<option value='".escape($row["name"])."/".escape($dir)."'>".$row["name"]."/".escape($dir)."</option>\n";
							}
						}
					}
				}
				echo "		</optgroup>\n";
			}
		}
		echo "	</select>\n";
		echo "	<br />\n";
		echo "	".$text['description-device_template']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_line_view')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-lines']."</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table width='80%' border='0'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-line']."</td>\n";
		if (permission_exists('device_line_server_address')) {
			echo "				<td class='vtable'>".$text['label-server_address']."</td>\n";
		}
		if (permission_exists('device_line_server_address_primary')) {
			echo "				<td class='vtable'>".$text['label-server_address_primary']."</td>\n";
		}
		if (permission_exists('device_line_server_address_secondary')) {
			echo "				<td class='vtable'>".$text['label-server_address_secondary']."</td>\n";
		}
		if (permission_exists('device_line_outbound_proxy_primary')) {
			echo "				<td class='vtable'>".$text['label-outbound_proxy_primary']."</td>\n";
		}
		if (permission_exists('device_line_outbound_proxy_secondary')) {
			echo "				<td class='vtable'>".$text['label-outbound_proxy_secondary']."</td>\n";
		}
		echo "				<td class='vtable'>".$text['label-display_name']."</td>\n";
		echo "				<td class='vtable'>".$text['label-user_id']."</td>\n";
		if (permission_exists('device_line_auth_id')) {
			echo "				<td class='vtable'>".$text['label-auth_id']."</td>\n";
		}
		if (permission_exists('device_line_password')) {
			echo "				<td class='vtable'>".$text['label-password']."</td>\n";
		}
		echo "				<td class='vtable'>".$text['label-sip_port']."</td>\n";
		if (permission_exists('device_line_transport')) {
			echo "				<td class='vtable'>".$text['label-sip_transport']."</td>\n";
		}
		if (permission_exists('device_line_register_expires')) {
			echo "				<td class='vtable'>".$text['label-register_expires']."</td>\n";
		}
		if (permission_exists('device_line_shared')) {
			echo "				<td class='vtable'>".$text['label-shared_line']."</td>\n";
		}
		echo "				<td class='vtable'>".$text['label-enabled']."</td>\n";
		echo "				<td>&nbsp;</td>\n";
		echo "			</tr>\n";

		$x = 0;
		foreach($device_lines as $row) {

			//set the defaults
				if (!permission_exists('device_line_server_address')) {
					if (strlen($row['server_address']) == 0) { $row['server_address'] = $_SESSION['domain_name']; }
				}
				if (strlen($row['sip_transport']) == 0) { $row['sip_transport'] = $_SESSION['provision']['line_sip_transport']['text']; }
				if (strlen($row['sip_port']) == 0) { $row['sip_port'] = $_SESSION['provision']['line_sip_port']['numeric']; }
				if (strlen($row['register_expires']) == 0) { $row['register_expires'] = $_SESSION['provision']['line_register_expires']['numeric']; }

			//determine whether to hide the element
				if (!is_uuid($device_line_uuid)) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}
			//add the primary key uuid
				if (is_uuid($row['device_line_uuid'])) {
					echo "	<input name='device_lines[".$x."][device_line_uuid]' type='hidden' value=\"".escape($row['device_line_uuid'])."\"/>\n";
				}
			//show each row in the array
				echo "			<tr>\n";
				echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
				$selected = "selected=\"selected\" ";
				echo "				<select class='formfld' style='width: 45px;' name='device_lines[".$x."][line_number]'>\n";
				echo "				<option value=''></option>\n";
			        for ($n = 1; $n <=99; $n++) {
                                        echo "                          <option value='$n' ".($row['line_number'] == "$n" ? $selected:"").">$n</option>\n";
                                }
				echo "				</select>\n";
				echo "			</td>\n";

				if (permission_exists('device_line_server_address')) {
					echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
					echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][server_address]' maxlength='255' value=\"".escape($row['server_address'])."\"/>\n";
					echo "			</td>\n";
				}
				else {
					echo "				<input type='hidden' name='device_lines[".$x."][server_address]' value=\"".escape($row['server_address'])."\"/>\n";
				}

				if (permission_exists('device_line_server_address_primary')) {
					echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
					echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][server_address_primary]' maxlength='255' value=\"".escape($row['server_address_primary'])."\"/>\n";
					echo "			</td>\n";
				}
				if (permission_exists('device_line_server_address_secondary')) {
					echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
					echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][server_address_secondary]' maxlength='255' value=\"".escape($row['server_address_secondary'])."\"/>\n";
					echo "			</td>\n";
				}

				if (permission_exists('device_line_outbound_proxy_primary')) {
					if (permission_exists('device_line_outbound_proxy_secondary')) {
						$placeholder_label = $text['label-primary'];
					}
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 65px;' type='text' name='device_lines[".$x."][outbound_proxy_primary]' placeholder=\"".escape($placeholder_label)."\" maxlength='255' value=\"".escape($row['outbound_proxy_primary'])."\"/>\n";
					echo "			</td>\n";
					unset($placeholder_label);
				}
				
				if (permission_exists('device_line_outbound_proxy_secondary')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 65px;' type='text' name='device_lines[".$x."][outbound_proxy_secondary]' placeholder=\"".$text['label-secondary']."\" maxlength='255' value=\"".escape($row['outbound_proxy_secondary'])."\"/>\n";
					echo "			</td>\n";
				}

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][display_name]' maxlength='255' value=\"".escape($row['display_name'])."\"/>\n";
				echo "			</td>\n";

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][user_id]' maxlength='255' autocomplete=\"new-password\" value=\"".escape($row['user_id'])."\"/>\n";
				echo "			</td>\n";

				if (permission_exists('device_line_auth_id')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][auth_id]' maxlength='255' autocomplete=\"new-password\" value=\"".escape($row['auth_id'])."\"/>\n";
					echo "				<input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
					echo "			</td>\n";
				}

				if (permission_exists('device_line_password')) {
					echo "			<td align='left'>\n";
					echo "				<input type='password' style='display: none;' disabled='disabled'>"; //help defeat browser auto-fill
					echo "				<input class='formfld' style='width:75px;' type='password' name='device_lines[".$x."][password]' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete=\"off\" maxlength='255' value=\"".escape($row['password'])."\"/>\n";
					echo "			</td>\n";
				}

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][sip_port]' maxlength='255' value=\"".escape($row['sip_port'])."\"/>\n";
				echo "			</td>\n";

				if (permission_exists('device_line_transport')) {
					echo "			<td align='left'>\n";
					echo "				<select class='formfld' style='width: 50px;' name='device_lines[".$x."][sip_transport]'>\n";
					echo "					<option value='tcp' ".(($row['sip_transport'] == 'tcp') ? "selected" : null).">TCP</option>\n";
					echo "					<option value='udp' ".(($row['sip_transport'] == 'udp') ? "selected" : null).">UDP</option>\n";
					echo "					<option value='tls' ".(($row['sip_transport'] == 'tls') ? "selected" : null).">TLS</option>\n";
					echo "					<option value='dns srv' ".(($row['sip_transport'] == 'dns srv') ? "selected" : null).">DNS SRV</option>\n";
					echo "				</select>\n";
					echo "			</td>\n";
				}
				else {
					echo "				<input type='hidden' name='device_lines[".$x."][sip_transport]' value=\"".escape($row['sip_transport'])."\" />\n";
				}

				if (permission_exists('device_line_register_expires')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][register_expires]' maxlength='255' value=\"".escape($row['register_expires'])."\"/>\n";
					echo "			</td>\n";
				}
				else {
					echo "				<input type='hidden' name='device_lines[".$x."][register_expires]' value=\"".escape($row['register_expires'])."\"/>\n";
				}

				if (permission_exists('device_line_shared')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 50px;' type='text' name='device_lines[".$x."][shared_line]' maxlength='255' value=\"".escape($row['shared_line'])."\"/>\n";
					echo "			</td>\n";
				}
				else {
					echo "				<input type='hidden' name='device_lines[".$x."][shared_line]' value=\"".escape($row['shared_line'])."\"/>\n";
				}

				echo "			<td align='left'>\n";
				echo "				<select class='formfld' name='device_lines[".$x."][enabled]'>\n";
				echo "					<option value='true' ".(($row['enabled'] == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
				echo "					<option value='false' ".(($row['enabled'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
				echo "				</select>\n";
				echo "			</td>\n";

			echo "				<td>\n";
			if (is_uuid($row['device_line_uuid'])) {
				if (permission_exists('device_delete')) {
					echo "					<a href='device_line_delete.php?device_uuid=".escape($row['device_uuid'])."&id=".escape($row['device_line_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
				}
			}
			echo "				</td>\n";
			echo "			</tr>\n";
			$x++;
		}
		echo "			</table>\n";
		if (strlen($text['description-lines']) > 0) {
			echo "			<br>".$text['description-lines']."\n";
		}
		echo "		</td>";
		echo "	</tr>";
	}

	if (permission_exists('device_profile_edit')) {
		//device profile
		$sql = "select * from v_device_profiles ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$sql .= "order by device_profile_name asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>".$text['label-profile']."</td>";
			echo "		<td class='vtable' align='left'>";
			echo "			<select class='formfld' id='device_profile_uuid' name='device_profile_uuid'>\n";
			echo "				<option value=''></option>\n";
			foreach($result as $row) {
				echo "			<option value='".escape($row['device_profile_uuid'])."' ".(($row['device_profile_uuid'] == $device_profile_uuid) ? "selected='selected'" : null).">".escape($row['device_profile_name'])." ".(($row['domain_uuid'] == '') ? "&nbsp;&nbsp;(".$text['select-global'].")" : null)."</option>\n";
			}
			echo "			</select>\n";
			echo "			<button type='button' class='btn btn-default list_control_icon' id='device_profile_edit' onclick=\"if($('#device_profile_uuid').val() != '') window.location='device_profile_edit.php?id='+$('#device_profile_uuid').val();\"><span class='glyphicon glyphicon-pencil'></span></button>";
			echo "			<button type='button' class='btn btn-default list_control_icon' onclick=\"window.location='device_profile_edit.php'\"><span class='glyphicon glyphicon-plus'></span></button>";
			echo "			<br>".$text['description-profile2']."\n";
			echo "		</td>";
			echo "	</tr>";
		}
		unset($sql, $parameters, $result);
	}

	if (permission_exists('device_key_edit')) {
		$vendor_count = 0;
		foreach($device_keys as $row) {
			if ($previous_vendor != $row['device_key_vendor']) {
				$previous_vendor = $row['device_key_vendor'];
				$vendor_count++;
			}
		}

		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-keys']."</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table border='0' cellpadding='0' cellspacing='3'>\n";
		if ($vendor_count == 0) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>".$text['label-device_key_category']."</td>\n";
			echo "				<td class='vtable'>".$text['label-device_key_id']."</td>\n";
			echo "				<td class='vtable'>".$text['label-device_key_type']."</td>\n";
			echo "				<td class='vtable'>".$text['label-device_key_line']."</td>\n";
			echo "				<td class='vtable'>".$text['label-device_key_value']."</td>\n";
			if (permission_exists('device_key_extension')) {
				echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
			}
			echo "				<td class='vtable'>".$text['label-device_key_label']."</td>\n";
			echo "				<td class='vtable'>".$text['label-device_key_icon']."</td>\n";
			echo "				<td>&nbsp;</td>\n";
			echo "			</tr>\n";
		}

		$x = 0;
		foreach($device_keys as $row) {
			//set the column names
				if ($previous_device_key_vendor != $row['device_key_vendor']) {
					echo "			<tr>\n";
					echo "				<td class='vtable'>".$text['label-device_key_category']."</td>\n";
					echo "				<td class='vtable'>".$text['label-device_key_id']."</td>\n";
					if ($vendor_count > 1 && strlen($row['device_key_vendor']) > 0) {
						echo "				<td class='vtable'>".ucwords($row['device_key_vendor'])."</td>\n";
					} else {
						echo "				<td class='vtable'>".$text['label-device_key_type']."</td>\n";
					}
					echo "				<td class='vtable'>".$text['label-device_key_line']."</td>\n";
					echo "				<td class='vtable'>".$text['label-device_key_value']."</td>\n";
					if (permission_exists('device_key_extension')) {
						echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
					}
					echo "				<td class='vtable'>".$text['label-device_key_label']."</td>\n";
					echo "				<td class='vtable'>".$text['label-device_key_icon']."</td>\n";
					echo "				<td>&nbsp;</td>\n";
					echo "			</tr>\n";
				}
			//determine whether to hide the element
				if (!is_uuid($device_key_uuid)) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}
			//add the primary key uuid
				if (is_uuid($row['device_key_uuid'])) {
					echo "	<input name='device_keys[".$x."][device_key_uuid]' type='hidden' value=\"".escape($row['device_key_uuid'])."\"/>\n";
				}
			//show all the rows in the array
				echo "			<tr>\n";
				echo "<td valign='top' align='left' nowrap='nowrap'>\n";
				echo "	<select class='formfld' name='device_keys[".$x."][device_key_category]'>\n";
				echo "	<option value=''></option>\n";
				if ($row['device_key_category'] == "line") {
					echo "	<option value='line' selected='selected'>".$text['label-line']."</option>\n";
				}
				else {
					echo "	<option value='line'>".$text['label-line']."</option>\n";
				}
				if ($row['device_key_vendor'] !== "polycom") {
					if ($row['device_key_category'] == "memory") {
						echo "	<option value='memory' selected='selected'>".$text['label-memory']."</option>\n";
					}
					else {
						echo "	<option value='memory'>".$text['label-memory']."</option>\n";
					}
				}
				if ($row['device_key_category'] == "programmable") {
					echo "	<option value='programmable' selected='selected'>".$text['label-programmable']."</option>\n";
				}
				else {
					echo "	<option value='programmable'>".$text['label-programmable']."</option>\n";
				}
				if ($row['device_key_vendor'] !== "polycom") {
					if (strlen($device_vendor) == 0) {
						if ($row['device_key_category'] == "expansion") {
							echo "	<option value='expansion' selected='selected'>".$text['label-expansion']."</option>\n";
						}
						else {
							echo "	<option value='expansion'>".$text['label-expansion']."</option>\n";
						}
						if ($row['device_key_category'] == "expansion-2") {
							echo "	<option value='expansion-2' selected='selected'>".$text['label-expansion']." 2</option>\n";
						}
						else {
							echo "	<option value='expansion-2'>".$text['label-expansion']." 2</option>\n";
						}
					}
					else {
						if (strtolower($device_vendor) == "cisco" or strtolower($row['device_key_vendor']) == "yealink") {
							if ($row['device_key_category'] == "expansion-1" || $row['device_key_category'] == "expansion") {
								echo "	<option value='expansion-1' selected='selected'>".$text['label-expansion']." 1</option>\n";
							}
							else {
								echo "	<option value='expansion-1'>".$text['label-expansion']." 1</option>\n";
							}
							if ($row['device_key_category'] == "expansion-2") {
								echo "	<option value='expansion-2' selected='selected'>".$text['label-expansion']." 2</option>\n";
							}
							else {
								echo "	<option value='expansion-2'>".$text['label-expansion']." 2</option>\n";
							}
							if ($row['device_key_category'] == "expansion-3") {
								echo "	<option value='expansion-3' selected='selected'>".$text['label-expansion']." 3</option>\n";
							}
							else {
								echo "	<option value='expansion-3'>".$text['label-expansion']." 3</option>\n";
							}
							if ($row['device_key_category'] == "expansion-4") {
								echo "	<option value='expansion-4' selected='selected'>".$text['label-expansion']." 4</option>\n";
							}
							else {
								echo "	<option value='expansion-4'>".$text['label-expansion']." 4</option>\n";
							}
							if ($row['device_key_category'] == "expansion-5") {
								echo "	<option value='expansion-5' selected='selected'>".$text['label-expansion']." 5</option>\n";
							}
							else {
								echo "	<option value='expansion-5'>".$text['label-expansion']." 5</option>\n";
							}
							if ($row['device_key_category'] == "expansion-6") {
								echo "	<option value='expansion-6' selected='selected'>".$text['label-expansion']." 6</option>\n";
							}
							else {
								echo "	<option value='expansion-6'>".$text['label-expansion']." 6</option>\n";
							}
						}
						else {
							if ($row['device_key_category'] == "expansion") {
								echo "	<option value='expansion' selected='selected'>".$text['label-expansion']."</option>\n";
							}
							else {
								echo "	<option value='expansion'>".$text['label-expansion']."</option>\n";
							}
						}
					}
				}
				echo "	</select>\n";
				echo "</td>\n";

				echo "<td valign='top' align='left' nowrap='nowrap'>\n";
				$selected = "selected='selected'";
				echo "	<select class='formfld' name='device_keys[".$x."][device_key_id]'>\n";
				echo "	<option value=''></option>\n";
				for ($i = 1; $i <= 255; $i++) {
					echo "	<option value='$i' ".($row['device_key_id'] == $i ? "selected":null).">$i</option>\n";
				}
				echo "	</select>\n";
				echo "</td>\n";

				echo "<td align='left' nowrap='nowrap'>\n";
				//echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_type]' style='width: 120px;' maxlength='255' value=\"$row['device_key_type']\">\n";
				if (strlen($row['device_key_vendor']) > 0) {
					$device_key_vendor = $row['device_key_vendor'];
				}
				else {
					$device_key_vendor = $device_vendor;
				}
				?>
				<input class='formfld' type='hidden' id='key_vendor_<?php echo $x; ?>' name='device_keys[<?php echo $x; ?>][device_key_vendor]' value="<?php echo $device_key_vendor; ?>"/>
				<?php
				echo "<select class='formfld' name='device_keys[".$x."][device_key_type]' id='key_type_".$x."'>\n";
				echo "	<option value=''></option>\n";
				$previous_vendor = '';
				$i=0;
				foreach ($vendor_functions as $function) {
					if (strlen($row['device_key_vendor']) == 0 && $function['vendor_name'] != $previous_vendor) {
						if ($i > 0) { echo "	</optgroup>\n"; }
						echo "	<optgroup label='".ucwords($function['vendor_name'])."'>\n";
					}
					$selected = '';
					if (strtolower($row['device_key_vendor']) == $function['vendor_name'] && $row['device_key_type'] == $function['value']) {
						$selected = "selected='selected'";
					}
					if (strlen($row['device_key_vendor']) == 0) {
						echo "		<option value='".$function['value']."' vendor='".$function['vendor_name']."' $selected >".$text['label-'.$function['name']]."</option>\n";
					}
					if (strlen($row['device_key_vendor']) > 0 && strtolower($row['device_key_vendor']) == $function['vendor_name']) {
						echo "		<option value='".$function['value']."' vendor='".$function['vendor_name']."' $selected >".$text['label-'.$function['name']]."</option>\n";
					}
					$previous_vendor = $function['vendor_name'];
					$i++;
				}
				if (strlen($row['device_key_vendor']) == 0) {
					echo "	</optgroup>\n";
				}
				echo "</select>\n";
				echo "</td>\n";
				echo "<td valign='top' align='left' nowrap='nowrap'>\n";
				echo "	<select class='formfld' name='device_keys[".$x."][device_key_line]'>\n";
				echo "		<option value=''></option>\n";
				for ($l = 0; $l <= 12; $l++) {
					echo "	<option value='".$l."' ".(($row['device_key_line'] == $l) ? "selected='selected'" : null).">".$l."</option>\n";
				}
				echo "	</select>\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_value]' style='width: 120px;' maxlength='255' value=\"".escape($row['device_key_value'])."\"/>\n";
				echo "</td>\n";

				if (permission_exists('device_key_extension')) {
					echo "<td align='left'>\n";
					echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_extension]' style='width: 75px;' maxlength='255' value=\"".escape($row['device_key_extension'])."\"/>\n";
					echo "</td>\n";
				}

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_label]' style='width: 75px;' maxlength='255' value=\"".escape($row['device_key_label'])."\"/>\n";
				echo "</td>\n";
				
                                echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_icon]' style='width: 75px;' maxlength='255' value=\"".escape($row['device_key_icon'])."\"/>\n";
				echo "</td>\n";

				//echo "			<td align='left'>\n";
				//echo "				<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'/>\n";
				//echo "			</td>\n";
				echo "				<td nowrap='nowrap'>\n";
				if (is_uuid($row['device_key_uuid'])) {
					if (permission_exists('device_key_delete')) {
						echo "					<a href='device_key_delete.php?device_uuid=".escape($row['device_uuid'])."&id=".escape($row['device_key_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
					}
				}
				echo "				</td>\n";
				echo "			</tr>\n";
			//set the previous vendor
				$previous_device_key_vendor = $row['device_key_vendor'];
			//increment the array key
				$x++;
		}
		echo "			</table>\n";
		if (strlen($text['description-keys']) > 0) {
			echo "			<br>".$text['description-keys']."\n";
		}
		echo "		</td>";
		echo "	</tr>";
	}

//device settings
	if (permission_exists('device_setting_edit')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-settings']."</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table border='0' cellpadding='0' cellspacing='3'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_name']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_value']."</td>\n";
		echo "				<td class='vtable'>".$text['label-enabled']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_description']."</td>\n";
		echo "				<td>&nbsp;</td>\n";
		echo "			</tr>\n";

		$x = 0;
		foreach($device_settings as $row) {
			//determine whether to hide the element
				if (!is_uuid($device_setting_uuid)) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}
			//add the primary key uuid
				if (is_uuid($row['device_setting_uuid'])) {
					echo "	<input name='device_settings[".$x."][device_setting_uuid]' type='hidden' value=\"".escape($row['device_setting_uuid'])."\"/>\n";
				}

			//show alls rows in the array
				echo "<tr>\n";
				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_subcategory]' style='width: 120px;' maxlength='255' value=\"".escape($row['device_setting_subcategory'])."\"/>\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_value]' style='width: 120px;' maxlength='255' value=\"".escape($row['device_setting_value'])."\"/>\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "    <select class='formfld' name='device_settings[".$x."][device_setting_enabled]' style='width: 90px;'>\n";
				echo "    <option value=''></option>\n";
				if ($row['device_setting_enabled'] == "true") {
					echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
				}
				else {
					echo "    <option value='true'>".$text['label-true']."</option>\n";
				}
				if ($row['device_setting_enabled'] == "false") {
					echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
				}
				else {
					echo "    <option value='false'>".$text['label-false']."</option>\n";
				}
				echo "    </select>\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_description]' style='width: 150px;' maxlength='255' value=\"".escape($row['device_setting_description'])."\"/>\n";
				echo "</td>\n";

				if (strlen($text['description-settings']) > 0) {
					echo "			<br>".$text['description-settings']."\n";
				}
				echo "		</td>";

				echo "				<td>\n";
				if (is_uuid($row['device_setting_uuid'])) {
					if (permission_exists('device_edit')) {
						echo "					<a href='device_setting_edit.php?device_uuid=".escape($row['device_uuid'])."&id=".escape($row['device_setting_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
					}
					if (permission_exists('device_delete')) {
						echo "					<a href='device_setting_delete.php?device_uuid=".escape($row['device_uuid'])."&id=".escape($row['device_setting_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
					}
				}
				echo "				</td>\n";
				echo "			</tr>\n";
				$x++;
			}
			/*
			echo "			<td align='left'>\n";
			echo "				<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
			*/
			echo "			</table>\n";
			echo "			</td>\n";
			echo "			</tr>\n";
	}

	if (permission_exists('device_user')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-user']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "			<select name=\"device_user_uuid\" class='formfld' style='width: auto;'>\n";
		echo "			<option value=\"\"></option>\n";
		foreach($users as $field) {
			if ($field['user_uuid'] == $device_user_uuid) { $selected = "selected='selected'"; } else { $selected = ''; }
			echo "			<option value='".escape($field['user_uuid'])."' $selected>".escape($field['username'])."</option>\n";
		}
		echo "			</select>";
		unset($users);
		echo "			<br>\n";
		echo "			".$text['description-user']."\n";
	}

	if (permission_exists('device_username_password')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_username' id='device_username' autocomplete=\"new-password\" maxlength='255' placeholder=\"".$text['label-device_username']."\" value=\"".escape($device_username)."\"/>\n";
		echo "	<input class='formfld' type='password' name='device_password' id='device_password' autocomplete=\"new-password\" onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' placeholder=\"".$text['label-device_password']."\" value=\"".escape($device_password)."\"/>\n";
		echo "	<div style='display: none;' id='duplicate_username_response'></div>\n";
		echo "<br />\n";
		echo $text['description-device']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_alternate') && is_uuid($device_uuid_alternate)) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_uuid_alternate']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' nowrap='nowrap'>\n";
		$label = $device_alternate[0]['device_label'];
		if (strlen($label) == 0) { $label = $device_alternate[0]['device_description']; }
		if (strlen($label) == 0) { $label = $device_alternate[0]['device_mac_address']; }
		echo "	<table>\n";
		echo "	<tr>\n";
		echo "		<td><a href='?id=".escape($device_uuid_alternate)."' id='device_uuid_alternate_link'>".escape($label)."</a><input class='formfld' type='hidden' name='device_uuid_alternate' id='device_uuid_alternate' maxlength='255' value=\"".escape($device_uuid_alternate)."\" />&nbsp;</td>";
		echo "		<td><a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('device_uuid_alternate').value = '';  document.getElementById('device_uuid_alternate_link').hidden = 'true'; submit_form(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a></td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		unset($label);
		echo $text['description-device_uuid_alternate']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_vendor')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_vendor']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_vendor' maxlength='255' value=\"".escape($device_vendor)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_vendor']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_model')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_model']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_model' maxlength='255' value=\"".escape($device_model)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_model']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_firmware')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_firmware_version']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_firmware_version' maxlength='255' value=\"".escape($device_firmware_version)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_firmware_version']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='domain_uuid' id='domain_uuid'>\n";
		if (!is_uuid($domain_uuid)) {
			echo "    <option value='' selected='selected'>".$text['select-global']."</option>\n";
		}
		else {
			echo "    <option value=''>".$text['select-global']."</option>\n";
		}
		foreach ($_SESSION['domains'] as $row) {
			if ($row['domain_uuid'] == $domain_uuid) {
				echo "    <option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
			}
			else {
				echo "    <option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
			}
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else {
		echo "	<input type='hidden' name='domain_uuid' id='domain_uuid' value=\"".$_SESSION['domain_uuid']."\"/>\n";
	}

	if (permission_exists('device_enable')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='device_enabled'>\n";
		if ($device_enabled == "true" || strlen($device_enabled) == 0) {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($device_enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-device_enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('device_description')) {
		echo "	<input class='formfld' type='text' name='device_description' maxlength='255' value=\"".escape($device_description)."\"/>\n";
		echo "<br />\n";
		echo $text['description-device_description']."\n";
	}
	else {
		echo escape($device_description)."\n";
	}

	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='device_uuid' value='".escape($device_uuid)."'/>\n";
	}
	echo "			<br>";
	echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'/>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	echo "<script>\n";
	echo "	$(window).load(function(event){\n";
	// triger initial onchage to set button state
	echo "      $('#device_profile_uuid').trigger('change')";
	echo "	});\n";
	//capture enter key to submit form
	echo "	$(window).keypress(function(event){\n";
	echo "		if (event.which == 13) { submit_form(); }\n";
	echo "	});\n";
	// capture device selection events
	echo "  $('#device_profile_uuid').change(function(event){ \n";
	echo "      if (this.value == '') {\$('#device_profile_edit').hide()} else {\$('#device_profile_edit').show()} \n";
	echo "	}); \n";
	// convert password fields to
	echo "	function submit_form() {\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "	function submit_form_2() {\n";
	echo "		$('input:password').css('visibility','hidden');\n";
	echo "		$('input:password').attr({type:'text'});\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//show the footer
	require_once "resources/footer.php";

?>
