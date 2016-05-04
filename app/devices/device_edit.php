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

*/
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
				$sql .= "d1.device_mac_address = '".check_str($_GET["mac"])."' ";
				if ($_GET["device_uuid"] != '') {
					$sql .= " and d1.device_uuid <> '".check_str($_GET["device_uuid"])."' ";
				}
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['domain_name'] != '') {
						echo $text['message-duplicate'].((if_group("superadmin") && $_SESSION["domain_name"] != $row["domain_name"]) ? ": ".$row["domain_name"] : null);
					}
				}
				unset($prep_statement);
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
				$sql .= "d1.device_username = '".check_str($_GET["username"])."' ";
				if ($_GET['domain_uuid'] != '') {
					$sql .= "and d2.domain_uuid = '".check_str($_GET['domain_uuid'])."' ";
				}
				if ($_GET['device_uuid'] != '') {
					$sql .= "and d1.device_uuid <> '".check_str($_GET["device_uuid"])."' ";
				}
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['domain_name'] != '') {
						echo $text['message-duplicate_username'].((if_group("superadmin")) ? ": ".format_mac($row['device_mac_address']).(($_SESSION["domain_name"] != $row["domain_name"]) ? " (".$row["domain_name"].")" : null) : null);
					}
				}
				unset($prep_statement);
			}

		exit;
	}

//include the device class
	require_once "app/devices/resources/classes/device.php";

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$device_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get total device count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['devices']['numeric'] != '') {
			$sql = "select count(*) as num_rows from v_devices where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				$total_devices = $row['num_rows'];
			}
			unset($prep_statement, $row);
			if ($total_devices >= $_SESSION['limit']['devices']['numeric']) {
				$_SESSION['message_mood'] = 'negative';
				$_SESSION['message'] = $text['message-maximum_devices'].' '.$_SESSION['limit']['devices']['numeric'];
				header('Location: devices.php');
				return;
			}
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//device mac address
			if (permission_exists('device_mac_address')) {
				$device_mac_address = check_str($_POST["device_mac_address"]);
				$device_mac_address = strtolower(preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address));
				$_POST["device_mac_address"] = $device_mac_address;
			}
			else {
				$orm = new orm;
				$orm->name('devices');
				$orm->uuid($device_uuid);
				$result = $orm->find()->get();
				//$message = $orm->message;
				foreach ($result as &$row) {
					$device_mac_address = $row["device_mac_address"];
					$_POST["device_mac_address"] = $device_mac_address;
				}
				unset ($prep_statement);
			}
		//devices
			$device_label = check_str($_POST["device_label"]);
			$device_vendor = check_str($_POST["device_vendor"]);
			$device_uuid_alternate = check_str($_POST["device_uuid_alternate"]);
			$device_model = check_str($_POST["device_model"]);
			$device_firmware_version = check_str($_POST["device_firmware_version"]);
			$device_enabled = check_str($_POST["device_enabled"]);
			$device_template = check_str($_POST["device_template"]);
			$device_description = check_str($_POST["device_description"]);
		//lines
			$line_number = check_str($_POST["line_number"]);
			$server_address = check_str($_POST["server_address"]);
			$outbound_proxy = check_str($_POST["outbound_proxy"]);
			$display_name = check_str($_POST["display_name"]);
			$user_id = check_str($_POST["user_id"]);
			$auth_id = check_str($_POST["auth_id"]);
			$password = check_str($_POST["password"]);
		//profile
			$device_profile_uuid = check_str($_POST["device_profile_uuid"]);
		//keys
			$device_key_category = check_str($_POST["device_key_category"]);
			$device_key_id = check_str($_POST["device_key_id"]);
			$device_key_type = check_str($_POST["device_key_type"]);
			$device_key_line = check_str($_POST["device_key_line"]);
			$device_key_value = check_str($_POST["device_key_value"]);
			$device_key_extension = check_str($_POST["device_key_extension"]);
			$device_key_label = check_str($_POST["device_key_label"]);
		//settings
			//$device_setting_category = check_str($_POST["device_setting_category"]);
			$device_setting_subcategory = check_str($_POST["device_setting_subcategory"]);
			//$device_setting_name = check_str($_POST["device_setting_name"]);
			$device_setting_value = check_str($_POST["device_setting_value"]);
			$device_setting_enabled = check_str($_POST["device_setting_enabled"]);
			$device_setting_description = check_str($_POST["device_setting_description"]);
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
					//unset($_POST["autocomplete"]);
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
							if (strlen($row["device_key_uuid"]) == 0) {
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
							if (strlen($row["device_setting_uuid"]) == 0) {
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

				//save the device
					if ($save) {
						$orm = new orm;
						$orm->name('devices');
						if (strlen($device_uuid) > 0) {
							$orm->uuid($device_uuid);
						}
						$orm->save($_POST);
						$response = $orm->message;
						if (strlen($response['uuid']) > 0) {
							$device_uuid = $response['uuid'];
						}
					}

				//write the provision files
					if (strlen($_SESSION['provision']['path']['text']) > 0) {
						require_once "app/provision/provision_write.php";
					}

				//set the message
					if (!isset($_SESSION['message'])) {
						if ($save) {
							if ($action == "add") {
								//save the message to a session variable
									$_SESSION['message'] = $text['message-add'];
							}
							if ($action == "update") {
								//save the message to a session variable
									$_SESSION['message'] = $text['message-update'];
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
		$orm = new orm;
		$orm->name('devices');
		$orm->uuid($device_uuid);
		$result = $orm->find()->get();
		//$message = $orm->message;
		foreach ($result as &$row) {
			$device_mac_address = $row["device_mac_address"];
			$domain_uuid = $row["domain_uuid"];
			$device_label = $row["device_label"];
			//$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);
			$device_label = $row["device_label"];
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
		unset ($prep_statement);
	}

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		$template_array = explode("/", $device_template);
		$device_vendor = $template_array[0];
	}

//set the sub array index
	$x = "999";

//alternate device settings
	if (strlen($device_uuid_alternate) > 0) {
		$sql = "select * from v_devices ";
		$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
		$sql .= "and device_uuid = '$device_uuid_alternate' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$device_alternate = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);
	}

//get device lines
	$sql = "SELECT * FROM v_device_lines ";
	$sql .= "where device_uuid = '".$device_uuid."' ";
	$sql .= "order by line_number asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_lines = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$device_lines[$x]['line_number'] = '';
	$device_lines[$x]['server_address'] = '';
	$device_lines[$x]['outbound_proxy'] = '';
	$device_lines[$x]['display_name'] = '';
	$device_lines[$x]['user_id'] = '';
	$device_lines[$x]['auth_id'] = '';
	$device_lines[$x]['password'] = '';
	$device_lines[$x]['enabled'] = '';
	$device_lines[$x]['sip_port'] = $_SESSION['provision']['line_sip_port']['numeric'];
	$device_lines[$x]['sip_transport'] = $_SESSION['provision']['line_sip_transport']['text'];
	$device_lines[$x]['register_expires'] = $_SESSION['provision']['line_register_expires']['numeric'];

//get device keys
	$sql = "SELECT * FROM v_device_keys ";
	$sql .= "WHERE device_uuid = '".$device_uuid."' ";
	$sql .= "ORDER by ";
	$sql .= "device_key_vendor asc, ";
	$sql .= "CASE device_key_category ";
	$sql .= "WHEN 'line' THEN 1 ";
	$sql .= "WHEN 'memory' THEN 2 ";
	$sql .= "WHEN 'programmable' THEN 3 ";
	$sql .= "WHEN 'expansion' THEN 4 ";
	$sql .= "ELSE 100 END, ";
	if ($db_type == "mysql") {
		$sql .= "device_key_id asc ";
	}
	else {
		$sql .= "cast(device_key_id as numeric) asc ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_keys = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$device_keys[$x]['device_key_category'] = '';
	$device_keys[$x]['device_key_id'] = '';
	$device_keys[$x]['device_key_type'] = '';
	$device_keys[$x]['device_key_line'] = '';
	$device_keys[$x]['device_key_value'] = '';
	$device_keys[$x]['device_key_extension'] = '';
	$device_keys[$x]['device_key_label'] = '';

//get device settings
	$sql = "SELECT * FROM v_device_settings ";
	$sql .= "WHERE device_uuid = '".$device_uuid."' ";
	$sql .= "ORDER by device_setting_subcategory asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$device_settings[$x]['device_setting_name'] = '';
	$device_settings[$x]['device_setting_value'] = '';
	$device_settings[$x]['enabled'] = '';
	$device_settings[$x]['device_setting_description'] = '';

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		$device_vendor = device::get_vendor($device_mac_address);
	}

//show the header
	require_once "resources/header.php";

//javascript to change select to input and back again
	?><script language="javascript">
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

		function check_duplicates() {
			//check mac
			var mac_addr = document.getElementById('device_mac_address').value;
			$("#duplicate_mac_response").load("device_edit.php?check=duplicate&mac="+mac_addr+"&device_uuid=<?php echo $device_uuid;?>", function() {
				var duplicate_mac = false;

				if ($("#duplicate_mac_response").html() != '') {
					$('#device_mac_address').addClass('formfld_highlight_bad');
					display_message($("#duplicate_mac_response").html(), 'negative'<?php if (if_group("superadmin")) { echo ', 3000'; } ?>);
					duplicate_mac = true;
				}
				else {
					$("#duplicate_mac_response").html('');
					$('#device_mac_address').removeClass('formfld_highlight_bad');
					duplicate_mac = false;
				}

				//check username
				if (duplicate_mac == false) {
					var username = document.getElementById('device_username').value;
					var domain_uuid = document.getElementById('domain_uuid').value;
					if (username != '') {
						$("#duplicate_username_response").load("device_edit.php?check=duplicate&username="+username+"&domain_uuid="+domain_uuid+"&device_uuid=<?php echo $device_uuid;?>", function() {
							var duplicate_username = false;

							if ($("#duplicate_username_response").html() != '') {
								$('#device_username').addClass('formfld_highlight_bad');
								display_message($("#duplicate_username_response").html(), 'negative'<?php if (if_group("superadmin")) { echo ', 3000'; } ?>);
								duplicate_username = true;
							}
							else {
								$("#duplicate_username_response").html('');
								$('#device_username').removeClass('formfld_highlight_bad');
								duplicate_username = false;
								submit_form_2();
							}
						});
					}
					else {
						submit_form_2();
					}
				}
			});
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
		if ($_SESSION['provision']['http_domain_filter']['text'] == "false") {
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
		echo "		window.location = '".$_SERVER['HTTP_PROTOCOL']."://".$domain_name."/app/provision?mac=".$device_mac_address."&file=' + d + '&content_type=application/octet-stream';\n";
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

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input type='hidden' name='file_action' id='file_action' value=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' nowrap='nowrap' valign='top'>";
	echo "	<b>".$text['header-device']."</b>";
	echo "	<br><br>";
	echo "	".$text['description-device'];
	echo "	<br><br>";
	echo "</td>\n";
	echo "<td align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' id='button_back_location' name='' alt='".$text['button-back']."' onclick=\"window.location='devices.php'\" value='".$text['button-back']."'>\n";
	if (permission_exists("device_files")) {
		//get the template directory
			$prov = new provision;
			$prov->domain_uuid = $domain_uuid;
			$template_dir = $prov->template_dir;
			$files = glob($template_dir.'/'.$device_template.'/*');
		//add file buttons and the file list
			echo "		<input type='button' class='btn' id='button_files' name='' alt='".$text['button-files']."' onclick='show_files();' value='".$text['button-files']."'>";
			echo "		<input type='button' class='btn' style='display: none;' id='button_back' name='' alt='".$text['button-back']."' onclick='hide_files();' value='".$text['button-back']."'> ";
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
		echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"var new_mac = prompt('".$text['message_device']."'); if (new_mac != null) { window.location='device_copy.php?id=".$device_uuid."&mac=' + new_mac; }\" value='".$text['button-copy']."'>\n";
	}
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
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
		echo "	<input class='formfld' type='text' name='device_mac_address' id='device_mac_address' maxlength='255' value=\"$device_mac_address\">\n";
		echo "<br />\n";
		echo $text['description-device_mac_address']."\n";
	}
	else {
		echo $device_mac_address;
	}
	echo "	<div style='display: none;' id='duplicate_mac_response'></div>\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('device_label')) {
		echo "	<input class='formfld' type='text' name='device_label' maxlength='255' value=\"$device_label\">\n";
		echo "<br />\n";
		echo $text['description-device_label']."\n";
	}
	else {
		echo $device_label;
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

	if (permission_exists('device_line_view')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-lines']."</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='3'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-line']."</td>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-server_address']."</td>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-outbound_proxy']."</td>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-display_name']."</td>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-user_id']."</td>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-auth_id']."</td>\n";
		if (permission_exists('device_line_password')) {
			echo "				<td class='vtable' nowrap='nowrap'>".$text['label-password']."</td>\n";
		}
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-sip_port']."</td>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-sip_transport']."</td>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-register_expires']."</td>\n";
		echo "				<td class='vtable' nowrap='nowrap'>".$text['label-enabled']."</td>\n";
		echo "				<td>&nbsp;</td>\n";
		echo "			</tr>\n";

		$x = 0;
		foreach($device_lines as $row) {
			//determine whether to hide the element
				if (strlen($device_line_uuid) == 0) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}
			//add the primary key uuid
				if (strlen($row['device_line_uuid']) > 0) {
					echo "	<input name='device_lines[".$x."][device_line_uuid]' type='hidden' value=\"".$row['device_line_uuid']."\">\n";
				}
			//show each row in the array
				echo "			<tr>\n";
				echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
				$selected = "selected=\"selected\" ";
				echo "				<select class='formfld' style='width: 45px;' name='device_lines[".$x."][line_number]'>\n";
				echo "				<option value=''></option>\n";
				echo "				<option value='1' ".($row['line_number'] == "1" ? $selected:"").">1</option>\n";
				echo "				<option value='2' ".($row['line_number'] == "2" ? $selected:"").">2</option>\n";
				echo "				<option value='3' ".($row['line_number'] == "3" ? $selected:"").">3</option>\n";
				echo "				<option value='4' ".($row['line_number'] == "4" ? $selected:"").">4</option>\n";
				echo "				<option value='5' ".($row['line_number'] == "5" ? $selected:"").">5</option>\n";
				echo "				<option value='6' ".($row['line_number'] == "6" ? $selected:"").">6</option>\n";
				echo "				<option value='7' ".($row['line_number'] == "7" ? $selected:"").">7</option>\n";
				echo "				<option value='8' ".($row['line_number'] == "8" ? $selected:"").">8</option>\n";
				echo "				<option value='9' ".($row['line_number'] == "9" ? $selected:"").">9</option>\n";
				echo "				<option value='10' ".($row['line_number'] == "10" ? $selected:"").">10</option>\n";
				echo "				<option value='11' ".($row['line_number'] == "11" ? $selected:"").">11</option>\n";
				echo "				<option value='12' ".($row['line_number'] == "12" ? $selected:"").">12</option>\n";
				echo "				</select>\n";
				echo "			</td>\n";

				echo "			<td valign='top' align='left' nowrap='nowrap'>\n";
				echo "				<input class='formfld' style='width: 125px;' type='text' name='device_lines[".$x."][server_address]' maxlength='255' value=\"".$row['server_address']."\">\n";
				echo "			</td>\n";

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 125px;' type='text' name='device_lines[".$x."][outbound_proxy]' maxlength='255' value=\"".$row['outbound_proxy']."\">\n";
				echo "			</td>\n";

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 95px;' type='text' name='device_lines[".$x."][display_name]' maxlength='255' value=\"".$row['display_name']."\">\n";
				echo "			</td>\n";

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][user_id]' maxlength='255' value=\"".$row['user_id']."\">\n";
				echo "			</td>\n";

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][auth_id]' maxlength='255' value=\"".$row['auth_id']."\">\n";
				echo "			</td>\n";

				if (permission_exists('device_line_password')) {
					echo "			<td align='left'>\n";
					echo "				<input class='formfld' style='width: 90px;' type='password' name='device_lines[".$x."][password]' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete=\"off\" maxlength='255' value=\"".$row['password']."\">\n";
					echo "			</td>\n";
				}

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][sip_port]' maxlength='255' value=\"".$row['sip_port']."\">\n";
				echo "			</td>\n";

				echo "			<td align='left'>\n";
				echo "				<select class='formfld' style='width: 60px;' name='device_lines[".$x."][sip_transport]'>\n";
				echo "					<option value='tcp' ".(($row['sip_transport'] == 'tcp') ? "selected" : null).">TCP</option>\n";
				echo "					<option value='udp' ".(($row['sip_transport'] == 'udp') ? "selected" : null).">UDP</option>\n";
				echo "					<option value='tls' ".(($row['sip_transport'] == 'tls') ? "selected" : null).">TLS</option>\n";
				echo "					<option value='dns srv' ".(($row['sip_transport'] == 'dns srv') ? "selected" : null).">DNS SRV</option>\n";
				echo "				</select>\n";
				echo "			</td>\n";

				echo "			<td align='left'>\n";
				echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][register_expires]' maxlength='255' value=\"".$row['register_expires']."\">\n";
				echo "			</td>\n";

				echo "			<td align='left'>\n";
				echo "				<select class='formfld' name='device_lines[".$x."][enabled]'>\n";
				echo "					<option value='true' ".(($row['enabled'] == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
				echo "					<option value='false' ".(($row['enabled'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
				echo "				</select>\n";
				echo "			</td>\n";

			echo "				<td>\n";
			if (strlen($row['device_line_uuid']) > 0) {
				if (permission_exists('device_delete')) {
					echo "					<a href='device_line_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_line_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
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
			echo "			<select class='formfld' name='device_profile_uuid'>\n";
			echo "				<option value=''></option>\n";
			foreach($result as $row) {
				echo "			<option value='".$row['device_profile_uuid']."' ".(($row['device_profile_uuid'] == $device_profile_uuid) ? "selected='selected'" : null).">".$row['device_profile_name']." ".(($row['domain_uuid'] == '') ? "&nbsp;&nbsp;(".$text['select-global'].")" : null)."</option>\n";
			}
			echo "			</select>\n";
			echo "			<br>".$text['description-profile2']."\n";
			echo "		</td>";
			echo "	</tr>";
		}
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
			echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
			echo "				<td class='vtable'>".$text['label-device_key_label']."</td>\n";
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
					echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
					echo "				<td class='vtable'>".$text['label-device_key_label']."</td>\n";
					echo "				<td>&nbsp;</td>\n";
					echo "			</tr>\n";
				}
			//determine whether to hide the element
				if (strlen($device_key_uuid) == 0) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}
			//add the primary key uuid
				if (strlen($row['device_key_uuid']) > 0) {
					echo "	<input name='device_keys[".$x."][device_key_uuid]' type='hidden' value=\"".$row['device_key_uuid']."\">\n";
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
				if ($row['device_key_category'] == "memory") {
					echo "	<option value='memory' selected='selected'>".$text['label-memory']."</option>\n";
				}
				else {
					echo "	<option value='memory'>".$text['label-memory']."</option>\n";
				}
				if ($row['device_key_category'] == "programmable") {
					echo "	<option value='programmable' selected='selected'>".$text['label-programmable']."</option>\n";
				}
				else {
					echo "	<option value='programmable'>".$text['label-programmable']."</option>\n";
				}
				if (strlen($device_vendor) == 0) {
					if ($row['device_key_category'] == "expansion") {
						echo "	<option value='expansion' selected='selected'>".$text['label-expansion']."</option>\n";
					}
					else {
						echo "	<option value='expansion'>".$text['label-expansion']."</option>\n";
					}
				}
				else {
					if (strtolower($device_vendor) == "cisco") {
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
				echo "	</select>\n";
				echo "</td>\n";

				echo "<td valign='top' align='left' nowrap='nowrap'>\n";
				$selected = "selected='selected'";
				echo "	<select class='formfld' name='device_keys[".$x."][device_key_id]'>\n";
				echo "	<option value=''></option>\n";
				$i = 1;
				while ($i < 100) {
					echo "	<option value='$i' ".($row['device_key_id'] == $i ? $selected:"").">$i</option>\n";
					$i++;
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
				<input class='formfld' type='hidden' id='key_vendor_<?php echo $x; ?>' name='device_keys[<?php echo $x; ?>][device_key_vendor]' value="<?php echo $device_key_vendor; ?>">
				<?php $selected = "selected='selected'"; ?>
				<?php $found = false; ?>
				<select class='formfld' name='device_keys[<?php echo $x; ?>][device_key_type]' id='key_type_<?php echo $x; ?>' onchange="document.getElementById('key_vendor_<?php echo $x; ?>').value=document.getElementById('key_type_<?php echo $x; ?>').options[document.getElementById('key_type_<?php echo $x; ?>').selectedIndex].parentNode.label.toLowerCase();" >
				<option value=''></option>
				<?php
				if (strtolower($device_vendor) == "aastra" || strlen($device_vendor) == 0 || strlen($device_username) > 0) {
					echo "<optgroup label='Aastra'>";
					?>
					<option value='blf' <?php if ($row['device_key_type'] == "blf") { echo $selected;$found=true; } ?>><?php echo $text['label-blf'] ?></option>
					<option value='blfxfer' <?php if ($row['device_key_type'] == "blfxfer") { echo $selected;$found=true; } ?>><?php echo $text['label-blf_xfer'] ?></option>
					<option value='callers' <?php if ($row['device_key_type'] == "callers") { echo $selected;$found=true; } ?>><?php echo $text['label-callers'] ?></option>

					<option value='dnd' <?php if ($row['device_key_type'] == "dnd") { echo $selected;$found=true; } ?>><?php echo $text['label-dnd'] ?></option>
					<option value='speeddial' <?php if ($row['device_key_type'] == "speeddial") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial'] ?></option>
					<option value='xfer' <?php if ($row['device_key_type'] == "xfer") { echo $selected;$found=true; } ?>><?php echo $text['label-xfer'] ?></option>

					<?php
					if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "cisco" || strlen($device_vendor) == 0 || strlen($device_username) > 0) {
					echo "<optgroup label='Cisco'>";
					?>
					<option value='blf' <?php if ($row['device_key_type'] == "blf") { echo $selected;$found=true; } ?>><?php echo $text['label-blf'] ?></option>
					<option value='line' <?php if ($row['device_key_type'] == "line") { echo $selected;$found=true; } ?>><?php echo $text['label-line'] ?></option>
					<option value='disabled' <?php if ($row['device_key_type'] == "disabled") { echo $selected;$found=true; } ?>><?php echo $text['label-disabled'] ?></option>
					<?php
					if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "grandstream" || strlen($device_vendor) == 0 || strlen($device_username) > 0) {
					echo "<optgroup label='Grandstream'>";
					?>
					<option value='line' <?php if ($row['device_key_type'] == "line") { echo $selected;$found=true; } ?>><?php echo $text['label-line'] ?></option>
					<option value='shared line' <?php if ($row['device_key_type'] == "shared line") { echo $selected;$found=true; } ?>><?php echo $text['label-shared_line'] ?></option>
					<option value='speed dial' <?php if ($row['device_key_type'] == "speed dial") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial'] ?></option>
					<option value='blf' <?php if ($row['device_key_type'] == "blf") { echo $selected;$found=true; } ?>><?php echo $text['label-blf'] ?></option>
					<option value='presence watcher' <?php if ($row['device_key_type'] == "presence watcher") { echo $selected;$found=true; } ?>><?php echo $text['label-presence_watcher'] ?></option>
					<option value='eventlist blf' <?php if ($row['device_key_type'] == "eventlist blf") { echo $selected;$found=true; } ?>><?php echo $text['label-eventlist_blf'] ?></option>
					<option value='speed dial active' <?php if ($row['device_key_type'] == "speed dial active") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial_active'] ?></option>
					<option value='dial dtmf' <?php if ($row['device_key_type'] == "dial dtmf") { echo $selected;$found=true; } ?>><?php echo $text['label-dial_dtmf'] ?></option>
					<option value='voicemail' <?php if ($row['device_key_type'] == "voicemail") { echo $selected;$found=true; } ?>><?php echo $text['label-voicemail'] ?></option>
					<option value='call return' <?php if ($row['device_key_type'] == "call return") { echo $selected;$found=true; } ?>><?php echo $text['label-call_return'] ?></option>
					<option value='transfer' <?php if ($row['device_key_type'] == "transfer") { echo $selected;$found=true; } ?>><?php echo $text['label-transfer'] ?></option>
					<option value='call park' <?php if ($row['device_key_type'] == "call park") { echo $selected;$found=true; } ?>><?php echo $text['label-call_park'] ?></option>
					<option value='intercom' <?php if ($row['device_key_type'] == "intercom") { echo $selected;$found=true; } ?>><?php echo $text['label-intercom'] ?></option>
					<option value='ldap search' <?php if ($row['device_key_type'] == "ldap search") { echo $selected;$found=true; } ?>><?php echo $text['label-ldap_search'] ?></option>
					<?php
					if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "mitel" || strlen($device_vendor) == 0 || strlen($device_username) > 0) {
					echo "<optgroup label='Mitel'>";
					?>
					<option value='0' <?php if ($row['device_key_type'] == "0") { echo $selected;$found=true; } ?>><?php echo $text['label-not_programmed'] ?></option>
					<option value='1' <?php if ($row['device_key_type'] == "1") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial'] ?></option>
					<option value='5' <?php if ($row['device_key_type'] == "5") { echo $selected;$found=true; } ?>><?php echo $text['label-shared_line'] ?></option>
					<option value='6' <?php if ($row['device_key_type'] == "6") { echo $selected;$found=true; } ?>><?php echo $text['label-line'] ?></option>
					<option value='2' <?php if ($row['device_key_type'] == "2") { echo $selected;$found=true; } ?>><?php echo $text['label-call_log'] ?></option>
					<option value='15' <?php if ($row['device_key_type'] == "15") { echo $selected;$found=true; } ?>><?php echo $text['label-phone_book'] ?></option>
					<option value='16' <?php if ($row['device_key_type'] == "16") { echo $selected;$found=true; } ?>><?php echo $text['label-forward'] ?></option>
					<option value='17' <?php if ($row['device_key_type'] == "17") { echo $selected;$found=true; } ?>><?php echo $text['label-dnd'] ?></option>
					<option value='3' <?php if ($row['device_key_type'] == "3") { echo $selected;$found=true; } ?>><?php echo $text['label-advisory_message'] ?></option>
					<option value='18' <?php if ($row['device_key_type'] == "18") { echo $selected;$found=true; } ?>><?php echo $text['label-pc_application'] ?></option>
					<option value='4' <?php if ($row['device_key_type'] == "4") { echo $selected;$found=true; } ?>><?php echo $text['label-headset_on_off'] ?></option>
					<option value='19' <?php if ($row['device_key_type'] == "19") { echo $selected;$found=true; } ?>><?php echo $text['label-rss_feed'] ?></option>
					<option value='27' <?php if ($row['device_key_type'] == "27") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial_blf'] ?></option>
					<option value='19' <?php if ($row['device_key_type'] == "19") { echo $selected;$found=true; } ?>><?php echo $text['label-url'] ?></option>
					<!--
					0 - not programmed
					1 - speed dial
					2 - callLog
					3 - advisoryMsg (on/off)
					4 - headset(on/off)
					5 - shared line
					6 - Line 1
					7 - Line 2
					8 - Line 3
					9 - Line 4
					10 - Line 5
					11 - Line 6
					12 - Line 7
					13 - Line 8
					15 - phonebook
					16 - call forwarding
					17 - do not disturb
					18 - PC Application
					19 - RSS Feed URL / Branding /Notes
					21 - Superkey (5304 set only)
					22 - Redial key (5304 set only)
					23 - Hold key (5304 set only)
					24 - Trans/Conf key (5304 set only)
					25 - Message key (5304 set only)
					26 - Cancel key (5304 set only)
					27 - Speed Dial & BLF

					Mitel web interface shows html_application
					-->
					<?php
					if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "polycom" || strlen($device_vendor) == 0 || strlen($device_username) > 0) {
					echo "<optgroup label='Polycom'>";
					?>
					<option value='line' <?php if ($row['device_key_type'] == "line") { echo $selected;$found=true; } ?>><?php echo $text['label-line'] ?></option>
					<option value='automata' <?php if ($row['device_key_type'] == "automata") { echo $selected;$found=true; } ?>><?php echo $text['label-automata'] ?></option>
					<option value='normal' <?php if ($row['device_key_type'] == "normal") { echo $selected;$found=true; } ?>><?php echo $text['label-normal'] ?></option>
					<option value='Messages' <?php if ($row['device_key_type'] == "Messages") { echo $selected;$found=true; } ?>><?php echo $text['label-messages'] ?></option>
					<option value='MicMute' <?php if ($row['device_key_type'] == "MicMute") { echo $selected;$found=true; } ?>><?php echo $text['label-micmute'] ?></option>
					<option value='Redial' <?php if ($row['device_key_type'] == "Redial") { echo $selected;$found=true; } ?>><?php echo $text['label-redial'] ?></option>
					<option value='Null' <?php if ($row['device_key_type'] == "Null") { echo $selected;$found=true; } ?>><?php echo $text['label-null'] ?></option>
					<option value='SpeedDial' <?php if ($row['device_key_type'] == "SpeedDial") { echo $selected;$found=true; } ?>><?php echo $text['label-speeddial'] ?></option>
					<option value='SpeedDialMenu' <?php if ($row['device_key_type'] == "SpeedDialMenu") { echo $selected;$found=true; } ?>><?php echo $text['label-speeddialmenu'] ?></option>
					<option value='URL' <?php if ($row['device_key_type'] == "URL") { echo $selected;$found=true; } ?>><?php echo $text['label-url'] ?></option>
					<?php
					if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "snom" || strlen($device_vendor) == 0 || strlen($device_username) > 0) {
					echo "<optgroup label='Snom'>";
					?>
					<option value='none' <?php if ($row['device_key_type'] == "none") { echo $selected;$found=true; } ?>><?php echo $text['label-none'] ?></option>
					<option value='url' <?php if ($row['device_key_type'] == "url") { echo $selected;$found=true; } ?>><?php echo $text['label-action_url'] ?></option>
					<option value='auto_answer' <?php if ($row['device_key_type'] == "auto_answer") { echo $selected;$found=true; } ?>><?php echo $text['label-auto_answer'] ?></option>
					<option value='blf' <?php if ($row['device_key_type'] == "blf") { echo $selected;$found=true; } ?>><?php echo $text['label-blf'] ?></option>
					<option value='button' <?php if ($row['device_key_type'] == "button") { echo $selected;$found=true; } ?>><?php echo $text['label-button'] ?></option>
					<option value='call_agent' <?php if ($row['device_key_type'] == "call_agent") { echo $selected;$found=true; } ?>><?php echo $text['label-call_agent'] ?></option>
					<option value='conference' <?php if ($row['device_key_type'] == "conference") { echo $selected;$found=true; } ?>><?php echo $text['label-conference'] ?></option>
					<option value='dtmf' <?php if ($row['device_key_type'] == "dtmf") { echo $selected;$found=true; } ?>><?php echo $text['label-dtmf'] ?></option>
					<option value='dest' <?php if ($row['device_key_type'] == "dest") { echo $selected;$found=true; } ?>><?php echo $text['label-extension'] ?></option>
					<option value='redirect' <?php if ($row['device_key_type'] == "redirect") { echo $selected;$found=true; } ?>><?php echo $text['label-redirect'] ?></option>
					<option value='icom' <?php if ($row['device_key_type'] == "icom") { echo $selected;$found=true; } ?>><?php echo $text['label-intercom'] ?></option>
					<option value='ivr' <?php if ($row['device_key_type'] == "ivr") { echo $selected;$found=true; } ?>><?php echo $text['label-ivr'] ?></option>
					<option value='keyevent' <?php if ($row['device_key_type'] == "keyevent") { echo $selected;$found=true; } ?>><?php echo $text['label-key_event'] ?></option>
					<option value='line' <?php if ($row['device_key_type'] == "line") { echo $selected;$found=true; } ?>><?php echo $text['label-line'] ?></option>
					<option value='multicast' <?php if ($row['device_key_type'] == "multicast") { echo $selected;$found=true; } ?>><?php echo $text['label-multicast_page'] ?></option>
					<option value='orbit' <?php if ($row['device_key_type'] == "orbit") { echo $selected;$found=true; } ?>><?php echo $text['label-orbit'] ?></option>
					<option value='presence' <?php if ($row['device_key_type'] == "presence") { echo $selected;$found=true; } ?>><?php echo $text['label-presence'] ?></option>
					<option value='p2t' <?php if ($row['device_key_type'] == "p2t") { echo $selected;$found=true; } ?>><?php echo $text['label-p2t'] ?></option>
					<option value='mult' <?php if ($row['device_key_type'] == "mult") { echo $selected;$found=true; } ?>><?php echo $text['label-shared_line'] ?></option>
					<option value='speed' <?php if ($row['device_key_type'] == "speed") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial'] ?></option>
					<option value='transfer' <?php if ($row['device_key_type'] == "transfer") { echo $selected;$found=true; } ?>><?php echo $text['label-transfer'] ?></option>
					<option value='recorder' <?php if ($row['device_key_type'] == "recorder") { echo $selected;$found=true; } ?>><?php echo $text['label-record'] ?></option>
					<?php
					if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "yealink" || strlen($device_vendor) == 0 || strlen($device_username) > 0) {
					echo "<optgroup label='Yealink'>";
					?>
					<option value='0' <?php if ($row['device_key_type'] == "0") { echo $selected;$found=true; } ?>><?php echo $text['label-na'] ?></option>
					<option value='15' <?php if ($row['device_key_type'] == "15") { echo $selected;$found=true; } ?>><?php echo $text['label-line'] ?></option>
					<option value='1' <?php if ($row['device_key_type'] == "1") { echo $selected;$found=true; } ?>><?php echo $text['label-conference'] ?></option>
					<option value='2' <?php if ($row['device_key_type'] == "2") { echo $selected;$found=true; } ?>><?php echo $text['label-forward'] ?></option>
					<option value='3' <?php if ($row['device_key_type'] == "3") { echo $selected;$found=true; } ?>><?php echo $text['label-transfer'] ?></option>
					<option value='4' <?php if ($row['device_key_type'] == "4") { echo $selected;$found=true; } ?>><?php echo $text['label-hold'] ?></option>
					<option value='5' <?php if ($row['device_key_type'] == "5") { echo $selected;$found=true; } ?>><?php echo $text['label-dnd'] ?></option>
					<option value='6' <?php if ($row['device_key_type'] == "6") { echo $selected;$found=true; } ?>><?php echo $text['label-redial'] ?></option>
					<option value='7' <?php if ($row['device_key_type'] == "7") { echo $selected;$found=true; } ?>><?php echo $text['label-call_return'] ?></option>
					<option value='8' <?php if ($row['device_key_type'] == "8") { echo $selected;$found=true; } ?>><?php echo $text['label-sms'] ?></option>
					<option value='9' <?php if ($row['device_key_type'] == "9") { echo $selected;$found=true; } ?>><?php echo $text['label-call_pickup'] ?></option>
					<option value='10' <?php if ($row['device_key_type'] == "10") { echo $selected;$found=true; } ?>><?php echo $text['label-call_park'] ?></option>
					<option value='11' <?php if ($row['device_key_type'] == "11") { echo $selected;$found=true; } ?>><?php echo $text['label-dtmf'] ?></option>
					<option value='12' <?php if ($row['device_key_type'] == "12") { echo $selected;$found=true; } ?>><?php echo $text['label-voicemail'] ?></option>
					<option value='13' <?php if ($row['device_key_type'] == "13") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial'] ?></option>
					<option value='14' <?php if ($row['device_key_type'] == "14") { echo $selected;$found=true; } ?>><?php echo $text['label-intercom'] ?></option>
					<option value='16' <?php if ($row['device_key_type'] == "16") { echo $selected;$found=true; } ?>><?php echo $text['label-blf'] ?></option>
					<option value='17' <?php if ($row['device_key_type'] == "17") { echo $selected;$found=true; } ?>><?php echo $text['label-url'] ?></option>
					<option value='19' <?php if ($row['device_key_type'] == "19") { echo $selected;$found=true; } ?>><?php echo $text['label-public_hold'] ?></option>
					<option value='20' <?php if ($row['device_key_type'] == "20") { echo $selected;$found=true; } ?>><?php echo $text['label-private'] ?></option>
					<option value='21' <?php if ($row['device_key_type'] == "21") { echo $selected;$found=true; } ?>><?php echo $text['label-shared_line'] ?></option>
					<option value='22' <?php if ($row['device_key_type'] == "22") { echo $selected;$found=true; } ?>><?php echo $text['label-xml_group'] ?></option>
					<option value='23' <?php if ($row['device_key_type'] == "23") { echo $selected;$found=true; } ?>><?php echo $text['label-group_pickup'] ?></option>
					<option value='24' <?php if ($row['device_key_type'] == "24") { echo $selected;$found=true; } ?>><?php echo $text['label-paging'] ?></option>
					<option value='25' <?php if ($row['device_key_type'] == "25") { echo $selected;$found=true; } ?>><?php echo $text['label-record'] ?></option>
					<option value='27' <?php if ($row['device_key_type'] == "27") { echo $selected;$found=true; } ?>><?php echo $text['label-xml_browser'] ?></option>
					<option value='28' <?php if ($row['device_key_type'] == "28") { echo $selected;$found=true; } ?>><?php echo $text['label-history'] ?></option>
					<option value='29' <?php if ($row['device_key_type'] == "29") { echo $selected;$found=true; } ?>><?php echo $text['label-directory'] ?></option>
					<option value='30' <?php if ($row['device_key_type'] == "30") { echo $selected;$found=true; } ?>><?php echo $text['label-menu'] ?></option>
					<option value='32' <?php if ($row['device_key_type'] == "32") { echo $selected;$found=true; } ?>><?php echo $text['label-new_sms'] ?></option>
					<option value='33' <?php if ($row['device_key_type'] == "33") { echo $selected;$found=true; } ?>><?php echo $text['label-status'] ?></option>
					<option value='34' <?php if ($row['device_key_type'] == "34") { echo $selected;$found=true; } ?>><?php echo $text['label-hot_desking'] ?></option>
					<option value='35' <?php if ($row['device_key_type'] == "35") { echo $selected;$found=true; } ?>><?php echo $text['label-url_record'] ?></option>
					<option value='38' <?php if ($row['device_key_type'] == "38") { echo $selected;$found=true; } ?>><?php echo $text['label-ldap'] ?></option>
					<option value='39' <?php if ($row['device_key_type'] == "39") { echo $selected;$found=true; } ?>><?php echo $text['label-blf_list'] ?></option>
					<option value='40' <?php if ($row['device_key_type'] == "40") { echo $selected;$found=true; } ?>><?php echo $text['label-prefix'] ?></option>
					<option value='41' <?php if ($row['device_key_type'] == "41") { echo $selected;$found=true; } ?>><?php echo $text['label-zero_sp_touch'] ?></option>
					<option value='42' <?php if ($row['device_key_type'] == "42") { echo $selected;$found=true; } ?>><?php echo $text['label-acd'] ?></option>
					<option value='43' <?php if ($row['device_key_type'] == "43") { echo $selected;$found=true; } ?>><?php echo $text['label-local_phonebook'] ?></option>
					<option value='44' <?php if ($row['device_key_type'] == "44") { echo $selected;$found=true; } ?>><?php echo $text['label-broadsoft_phonebook'] ?></option>
					<option value='45' <?php if ($row['device_key_type'] == "45") { echo $selected;$found=true; } ?>><?php echo $text['label-local_group'] ?></option>
					<option value='46' <?php if ($row['device_key_type'] == "46") { echo $selected;$found=true; } ?>><?php echo $text['label-broadsoft_group'] ?></option>
					<option value='47' <?php if ($row['device_key_type'] == "47") { echo $selected;$found=true; } ?>><?php echo $text['label-xml_phonebook'] ?></option>
					<option value='48' <?php if ($row['device_key_type'] == "48") { echo $selected;$found=true; } ?>><?php echo $text['label-switch_account_up'] ?></option>
					<option value='49' <?php if ($row['device_key_type'] == "49") { echo $selected;$found=true; } ?>><?php echo $text['label-switch_account_down'] ?></option>
					<option value='50' <?php if ($row['device_key_type'] == "50") { echo $selected;$found=true; } ?>><?php echo $text['label-keypad_lock'] ?></option>
					<?php
					if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
				}
				?>
				</select>

				<?php
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
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_value]' style='width: 120px;' maxlength='255' value=\"".$row['device_key_value']."\">\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_extension]' style='width: 120px;' maxlength='255' value=\"".$row['device_key_extension']."\">\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_label]' style='width: 150px;' maxlength='255' value=\"".$row['device_key_label']."\">\n";
				echo "</td>\n";

				//echo "			<td align='left'>\n";
				//echo "				<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
				//echo "			</td>\n";
				echo "				<td nowrap='nowrap'>\n";
				if (strlen($row['device_key_uuid']) > 0) {
					if (permission_exists('device_key_delete')) {
						echo "					<a href='device_key_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_key_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
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
				if (strlen($device_setting_uuid) == 0) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}
			//add the primary key uuid
				if (strlen($row['device_setting_uuid']) > 0) {
					echo "	<input name='device_settings[".$x."][device_setting_uuid]' type='hidden' value=\"".$row['device_setting_uuid']."\">\n";
				}

			//show alls rows in the array
				echo "<tr>\n";
				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_subcategory]' style='width: 120px;' maxlength='255' value=\"".$row['device_setting_subcategory']."\">\n";
				echo "</td>\n";

				echo "<td align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_value]' style='width: 120px;' maxlength='255' value=\"".$row['device_setting_value']."\">\n";
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
				echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_description]' style='width: 150px;' maxlength='255' value=\"".$row['device_setting_description']."\">\n";
				echo "</td>\n";

				if (strlen($text['description-settings']) > 0) {
					echo "			<br>".$text['description-settings']."\n";
				}
				echo "		</td>";

				echo "				<td>\n";
				if (strlen($row['device_setting_uuid']) > 0) {
					if (permission_exists('device_edit')) {
						echo "					<a href='device_setting_edit.php?device_uuid=".$row['device_uuid']."&id=".$row['device_setting_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
					}
					if (permission_exists('device_delete')) {
						echo "					<a href='device_setting_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_setting_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
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

	if (permission_exists('device_username_password')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_username' id='device_username' autocomplete=\"off\" maxlength='255' placeholder=\"".$text['label-device_username']."\" value=\"$device_username\">\n";
		echo "	<input class='formfld' type='password' name='device_password' id='device_password' autocomplete=\"off\" onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' placeholder=\"".$text['label-device_password']."\" value=\"$device_password\">\n";
		echo "	<div style='display: none;' id='duplicate_username_response'></div>\n";
		echo "<br />\n";
		echo $text['description-device']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('device_alternate')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-device_uuid_alternate']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' nowrap='nowrap'>\n";
		if (strlen($device_uuid_alternate) == 0) {
			echo "	<input class='formfld' type='text' name='device_uuid_alternate' id='device_uuid_alternate' maxlength='255' value=\"$device_uuid_alternate\">";
		}
		else {
			$label = $device_alternate[0]['device_label'];
			if (strlen($label) == 0) { $label = $device_alternate[0]['device_description']; }
			if (strlen($label) == 0) { $label = $device_alternate[0]['device_mac_address']; }
			echo "	<table>\n";
			echo "	<tr>\n";
			echo "		<td><a href='?id=$device_uuid_alternate' id='device_uuid_alternate_link'>$label</a><input class='formfld' type='hidden' name='device_uuid_alternate' id='device_uuid_alternate' maxlength='255' value=\"$device_uuid_alternate\">&nbsp;</td>";
			echo "		<td><a href='#' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('device_uuid_alternate').value = '';  document.getElementById('device_uuid_alternate_link').hidden = 'true'; submit_form(); }\" alt='".$text['button-delete']."'>$v_link_label_delete</a></td>\n";
			echo "	</tr>\n";
			echo "	</table>\n";
			unset($label);
		}
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
		echo "	<input class='formfld' type='text' name='device_vendor' maxlength='255' value=\"$device_vendor\">\n";
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
		echo "	<input class='formfld' type='text' name='device_model' maxlength='255' value=\"$device_model\">\n";
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
		echo "	<input class='formfld' type='text' name='device_firmware_version' maxlength='255' value=\"$device_firmware_version\">\n";
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
		if (strlen($domain_uuid) == 0) {
			echo "    <option value='' selected='selected'>".$text['select-global']."</option>\n";
		}
		else {
			echo "    <option value=''>".$text['select-global']."</option>\n";
		}
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
	} else {
		echo "	<input type='hidden' name='domain_uuid' id='domain_uuid' value=\"".$_SESSION['domain_uuid']."\">\n";
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
		echo "	<input class='formfld' type='text' name='device_description' maxlength='255' value=\"$device_description\">\n";
		echo "<br />\n";
		echo $text['description-device_description']."\n";
	}
	else {
		echo $device_description."\n";
	}

	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='device_uuid' value='$device_uuid'>\n";
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
	echo "		check_duplicates();\n";
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