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
	Copyright (C) 2008-2013 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
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

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//devices
			$device_mac_address = check_str($_POST["device_mac_address"]);
			$device_mac_address = strtolower($device_mac_address);
			$_POST["device_mac_address"] = preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address);
			$device_label = check_str($_POST["device_label"]);
			$device_vendor = check_str($_POST["device_vendor"]);
			$device_model = check_str($_POST["device_model"]);
			$device_firmware_version = check_str($_POST["device_firmware_version"]);
			$device_provision_enable = check_str($_POST["device_provision_enable"]);
			$device_template = check_str($_POST["device_template"]);
			$device_username = check_str($_POST["device_username"]);
			$device_password = check_str($_POST["device_password"]);
			$device_time_zone = check_str($_POST["device_time_zone"]);
			$device_description = check_str($_POST["device_description"]);
		//lines
			$line_number = check_str($_POST["line_number"]);
			$server_address = check_str($_POST["server_address"]);
			$outbound_proxy = check_str($_POST["outbound_proxy"]);
			$display_name = check_str($_POST["display_name"]);
			$user_id = check_str($_POST["user_id"]);
			$auth_id = check_str($_POST["auth_id"]);
			$password = check_str($_POST["password"]);
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

		$msg = '';
		if ($action == "update") {
			$device_uuid = check_str($_POST["device_uuid"]);
		}

		//check for all required data
			if (strlen($device_mac_address) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			//if (strlen($device_label) == 0) { $msg .= "Please provide: Label<br>\n"; }
			//if (strlen($device_vendor) == 0) { $msg .= "Please provide: Vendor<br>\n"; }
			//if (strlen($device_model) == 0) { $msg .= "Please provide: Model<br>\n"; }
			//if (strlen($device_firmware_version) == 0) { $msg .= "Please provide: Firmware Version<br>\n"; }
			//if (strlen($device_provision_enable) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
			//if (strlen($device_template) == 0) { $msg .= "Please provide: Template<br>\n"; }
			//if (strlen($device_username) == 0) { $msg .= "Please provide: Username<br>\n"; }
			//if (strlen($device_password) == 0) { $msg .= "Please provide: Password<br>\n"; }
			//if (strlen($device_time_zone) == 0) { $msg .= "Please provide: Time Zone<br>\n"; }
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
				/*
				//remove the invalid characters from the extension name
					foreach ($_POST as $key => $value) {
						if ($key == "dialplan_name") {
							$dialplan_name = str_replace(" ", "_", $value);
							$dialplan_name = str_replace("/", "", $dialplan_name);
							$_POST["dialplan_name"] = $dialplan_name;
						}
					}
				*/
				//add domain_uuid to the array
					if (!isset($_POST["domain_uuid"])) {
						$_POST["domain_uuid"] = $_SESSION["domain_uuid"];
					}
					foreach ($_POST as $key => $value) {
						if (is_array($value)) {
							$y = 0;
							foreach ($value as $k => $v) {
								if (!isset($v["domain_uuid"])) {
									$_POST[$key][$y]["domain_uuid"] = $_SESSION["domain_uuid"];
								}
								$y++;
							}
						}
					}
				//array cleanup
					$x = 0;
					foreach ($_POST["device_lines"] as $row) {
						//
						//unset the empty row
							if (strlen($row["line_number"]) == 0) {
								unset($_POST["device_lines"][$x]);
							}
						//unset dialplan_detail_uuid if the field has no value
							if (strlen($row["device_line_uuid"]) == 0) {
								unset($_POST["device_lines"][$x]["device_line_uuid"]);
							}
						//increment the row
							$x++;
					}
					$x = 0;
					foreach ($_POST["device_keys"] as $row) {
						//unset the empty row
							if (strlen($row["device_key_id"]) == 0) {
								unset($_POST["device_keys"][$x]);
							}
						//unset dialplan_detail_uuid if the field has no value
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
						//unset dialplan_detail_uuid if the field has no value
							if (strlen($row["device_setting_uuid"]) == 0) {
								unset($_POST["device_settings"][$x]["device_setting_uuid"]);
							}
						//increment the row
							$x++;
					}

				//add or update the database
					if ($_POST["persistformvar"] != "true") {
						$orm = new orm;
						$orm->name('devices');
						if (strlen($device_uuid) > 0) {
							$orm->uuid($device_uuid);
						}

						$orm->save($_POST);
						$message = $orm->message;
					}

				//write the provision files
//					require_once "app/provision/provision_write.php";

				//set the message
					if ($action == "add") {
						$_SESSION['message'] = $text['message-add'];
					}
					if ($action == "update") {
						$_SESSION['message'] = $text['message-update'];
					}

			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$dialplan_uuid = check_str($_GET["id"]);
		$orm = new orm;
		$orm->name('devices');
		$orm->uuid($dialplan_uuid);
		$result = $orm->find()->get();
		//$message = $orm->message;
		foreach ($result as &$row) {
			$device_mac_address = $row["device_mac_address"];
			$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);
			$device_label = $row["device_label"];
			$device_vendor = $row["device_vendor"];
			$device_model = $row["device_model"];
			$device_firmware_version = $row["device_firmware_version"];
			$device_provision_enable = $row["device_provision_enable"];
			$device_template = $row["device_template"];
			$device_username = $row["device_username"];
			$device_password = $row["device_password"];
			$device_time_zone = $row["device_time_zone"];
			$device_description = $row["device_description"];
		}
		unset ($prep_statement);
	}

//get device lines
	$sql = "SELECT * FROM v_device_lines ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and device_uuid = '".$device_uuid."' ";
	$sql .= "order by line_number asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_lines = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//get device keys
	$sql = "SELECT * FROM v_device_keys ";
	$sql .= "WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "AND device_uuid = '".$device_uuid."' ";
	$sql .= "ORDER by ";
	$sql .= "CASE device_key_category ";
	$sql .= "WHEN 'line' THEN 1 ";
	$sql .= "WHEN 'memort' THEN 2 ";
	$sql .= "WHEN 'programmable' THEN 3 ";
	$sql .= "WHEN 'expansion' THEN 4 ";
	$sql .= "ELSE 100 END, ";
	$sql .= "device_key_id asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_keys = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//get device settings
	$sql = "SELECT * FROM v_device_settings ";
	$sql .= "WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "AND device_uuid = '".$device_uuid."' ";
	$sql .= "ORDER by device_setting_subcategory asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//use the mac address to get the vendor
	if (strlen($device_vendor) == 0) {
		$device_vendor = device::get_vendor($device_mac_address);
	}

//name='dialplan_details[".$x."][dialplan_detail_type]'

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
			tbb.type='button';
			tbb.value='<';
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

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-device']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='devices.php'\" value='".$text['button-back']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo $text['description-device']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_mac_address'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_mac_address' maxlength='255' value=\"$device_mac_address\">\n";
	echo "<br />\n";
	echo $text['description-device_mac_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_label'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_label' maxlength='255' value=\"$device_label\">\n";
	echo "<br />\n";
	echo $text['description-device_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-device_template'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select id='device_template' name='device_template' class='formfld'>\n";
	echo "<option value=''></option>\n";
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/".$_SESSION["domain_name"])) {
		$temp_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/".$_SESSION["domain_name"];
	} else {
		$temp_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision";
	}
	if($dh = opendir($temp_dir)) {
		while($dir = readdir($dh)) {
			if($file != "." && $dir != ".." && $dir[0] != '.') {
				if(is_dir($temp_dir . "/" . $dir)) {
					echo "<optgroup label='$dir'>";
					if($dh_sub = opendir($temp_dir.'/'.$dir)) {
						while($dir_sub = readdir($dh_sub)) {
							if($file_sub != '.' && $dir_sub != '..' && $dir_sub[0] != '.') {
								if(is_dir($temp_dir . '/' . $dir .'/'. $dir_sub)) {
									if ($device_template == $dir."/".$dir_sub) {
										echo "<option value='".$dir."/".$dir_sub."' selected='selected'>".$dir."/".$dir_sub."</option>\n";
									}
									else {
										echo "<option value='".$dir."/".$dir_sub."'>".$dir."/".$dir_sub."</option>\n";
									}
								}
							}
						}
						closedir($dh_sub);
					}
					echo "</optgroup>";
				}
			}
		}
		closedir($dh);
	}
	echo "</select>\n";
	echo "<br />\n";
	echo $text['description-device_template']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-lines'].":</td>";
	echo "		<td class='vtable' align='left'>";
	echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "			<tr>\n";
	echo "				<td class='vtable'>".$text['label-line']."</td>\n";
	echo "				<td class='vtable'>".$text['label-server_address']."</td>\n";
	echo "				<td class='vtable'>".$text['label-outbound_proxy']."</td>\n";
	echo "				<td class='vtable'>".$text['label-display_name']."</td>\n";
	echo "				<td class='vtable'>".$text['label-user_id']."</td>\n";
	echo "				<td class='vtable'>".$text['label-auth_id']."</td>\n";
	echo "				<td class='vtable'>".$text['label-password']."</td>\n";
	echo "				<td>&nbsp;</td>\n";
	echo "			</tr>\n";

	$x = 0;
	foreach($device_lines as $row) {
		//if (strlen($row['line_number']) == 0) { $row['line_number'] = "1"; }
		echo "			<tr>\n";
		echo "				<td class='vtable'>\n";
		echo "					".$row['line_number']."&nbsp;\n";
		echo "				</td>\n";
		echo "				<td class='vtable'>\n";
		echo "					".$row['server_address']."&nbsp;\n";
		echo "				</td>\n";
		echo "				<td class='vtable'>\n";
		echo "					".$row['outbound_proxy']."&nbsp;\n";
		echo "				</td>\n";
		echo "				<td class='vtable'>\n";
		echo "					".$row['display_name']."&nbsp;\n";
		echo "				</td>\n";
		echo "				<td class='vtable'>\n";
		echo "					".$row['user_id']."&nbsp;\n";
		echo "				</td>\n";
		echo "				<td class='vtable'>\n";
		echo "					".$row['auth_id']."&nbsp;\n";
		echo "				</td>\n";
		echo "				<td class='vtable'>\n";
		echo "					******** &nbsp;\n"; //$row['password']
		echo "				</td>\n";
		echo "				<td>\n";
		if (permission_exists('device_edit')) {
			echo "					<a href='device_line_edit.php?device_uuid=".$row['device_uuid']."&id=".$row['device_line_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
		}
		if (permission_exists('device_delete')) {
			echo "					<a href='device_line_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_line_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
		}
		echo "				</td>\n";
		echo "			</tr>\n";
		$x++;
	}
//device lines
	$x = 0;
	echo "			<tr>\n";
	echo "			<td class='vtable' valign='top' align='left' nowrap='nowrap'>\n";
	echo "				<select class='formfld' style='width: 45px;' name='device_lines[".$x."][line_number]'>\n";
	echo "				<option value=''></option>\n";
	echo "				<option value='1'>1</option>\n";
	echo "				<option value='2'>2</option>\n";
	echo "				<option value='3'>3</option>\n";
	echo "				<option value='4'>4</option>\n";
	echo "				<option value='5'>5</option>\n";
	echo "				<option value='6'>6</option>\n";
	echo "				<option value='7'>7</option>\n";
	echo "				<option value='8'>8</option>\n";
	echo "				<option value='9'>9</option>\n";
	echo "				<option value='10'>10</option>\n";
	echo "				<option value='11'>11</option>\n";
	echo "				<option value='12'>12</option>\n";
	echo "				</select>\n";
	echo "			</td>\n";

	echo "			<td class='vtable' valign='top' align='left' nowrap='nowrap'>\n";
	echo "				<input class='formfld' style='width: 125px;' type='text' name='device_lines[".$x."][server_address]' maxlength='255' value=\"$server_address\">\n";
	echo "			</td>\n";

	echo "			<td class='vtable' align='left'>\n";
	echo "				<input class='formfld' style='width: 125px;' type='text' name='device_lines[".$x."][outbound_proxy]' maxlength='255' value=\"$outbound_proxy\">\n";
	echo "			</td>\n";

	echo "			<td class='vtable' align='left'>\n";
	echo "				<input class='formfld' style='width: 95px;' type='text' name='device_lines[".$x."][display_name]' maxlength='255' value=\"$display_name\">\n";
	echo "			</td>\n";

	echo "			<td class='vtable' align='left'>\n";
	echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][user_id]' maxlength='255' value=\"$user_id\">\n";
	echo "			</td>\n";

	echo "			<td class='vtable' align='left'>\n";
	echo "				<input class='formfld' style='width: 75px;' type='text' name='device_lines[".$x."][auth_id]' maxlength='255' value=\"$auth_id\">\n";
	echo "			</td>\n";

	echo "			<td class='vtable' align='left'>\n";
	echo "				<input class='formfld' style='width: 90px;' type='text' name='device_lines[".$x."][password]' maxlength='255' value=\"$password\">\n";
	echo "			</td>\n";

	echo "			<td class='vtable' align='left'>\n";
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "			</td>\n";
	echo "			</tr>\n";
	echo "			</table>\n";
	if (strlen($text['description-lines']) > 0) {
		echo "			<br>".$text['description-lines']."\n";
	}
	echo "		</td>";
	echo "	</tr>";

	if (permission_exists('device_key_add') || permission_exists('device_key_edit')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-keys'].":</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
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

		$x = 0;
		foreach($device_keys as $row) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_key_category']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_key_id']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_key_type']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_key_line']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_key_value']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_key_extension']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_key_label']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td>\n";
			if (permission_exists('device_key_edit')) {
				echo "					<a href='device_key_edit.php?device_uuid=".$row['device_uuid']."&id=".$row['device_key_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('device_key_delete')) {
				echo "					<a href='device_key_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_key_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			}
			echo "				</td>\n";
			echo "			</tr>\n";
			$x++;
		}

		$x = 0;
		echo "<tr>\n";
		echo "<td class='vtable' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	<select class='formfld' style='width:auto;' name='device_keys[".$x."][device_key_category]'>\n";
		echo "	<option value=''></option>\n";
		if ($device_key_category == "line") { 
			echo "	<option value='line' selected='selected'>".$text['label-line']."</option>\n";
		}
		else {
			echo "	<option value='line'>".$text['label-line']."</option>\n";
		}
		if ($device_key_category == "memory") { 
			echo "	<option value='memory' selected='selected'>".$text['label-memory']."</option>\n";
		}
		else {
			echo "	<option value='memory'>".$text['label-memory']."</option>\n";
		}
		if ($device_key_category == "programmable") { 
			echo "	<option value='programmable' selected='selected'>".$text['label-programmable']."</option>\n";
		}
		else {
			echo "	<option value='programmable'>".$text['label-programmable']."</option>\n";
		}
		if ($device_key_category == "expansion") { 
			echo "	<option value='expansion' selected='selected'>".$text['label-expansion']."</option>\n";
		}
		else {
			echo "	<option value='expansion'>".$text['label-expansion']."</option>\n";
		}
		echo "	</select>\n";
		echo "</td>\n";

		echo "<td class='vtable' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	<select class='formfld' style='width:auto;' name='device_keys[".$x."][device_key_id]'>\n";
		echo "	<option value=''></option>\n";
		echo "	<option value='1'>1</option>\n";
		echo "	<option value='2'>2</option>\n";
		echo "	<option value='3'>3</option>\n";
		echo "	<option value='4'>4</option>\n";
		echo "	<option value='5'>5</option>\n";
		echo "	<option value='6'>6</option>\n";
		echo "	<option value='7'>7</option>\n";
		echo "	<option value='8'>8</option>\n";
		echo "	<option value='9'>9</option>\n";
		echo "	<option value='10'>10</option>\n";
		echo "	<option value='11'>11</option>\n";
		echo "	<option value='12'>12</option>\n";
		echo "	</select>\n";
		echo "</td>\n";

		echo "<td class='vtable' align='left'>\n";
		//echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_type]' style='width: 120px;' maxlength='255' value=\"$device_key_type\">\n";
		?>

		<?php $selected = "selected='selected'"; ?>
		<?php $found = false; ?>
		<select class='formfld' style='width:80px;' name='device_keys[<?php echo $x; ?>][device_key_type]'>
		<option value=''></option>
		<optgroup label='Cisco'>
			<option value='line' <?php if ($device_key_type == "0") { echo $selected;$found=true; } ?>>line</option>
			<option value='disabled' <?php if ($device_key_type == "0") { echo $selected;$found=true; } ?>>disabled</option>
		</optgroup>
		<optgroup label='Yealink'>
			<option value='0' <?php if ($device_key_type == "0") { echo $selected;$found=true; } ?>>0-N/A(default for memory key)</option>
			<option value='1' <?php if ($device_key_type == "1") { echo $selected;$found=true; } ?>>1-Conference</option>
			<option value='2' <?php if ($device_key_type == "2") { echo $selected;$found=true; } ?>>2-Forward</option>
			<option value='3' <?php if ($device_key_type == "3") { echo $selected;$found=true; } ?>>3-Transfer</option>
			<option value='4' <?php if ($device_key_type == "4") { echo $selected;$found=true; } ?>>4-Hold</option>
			<option value='5' <?php if ($device_key_type == "5") { echo $selected;$found=true; } ?>>5-DND</option>
			<option value='6' <?php if ($device_key_type == "6") { echo $selected;$found=true; } ?>>6-Redial</option>
			<option value='7' <?php if ($device_key_type == "7") { echo $selected;$found=true; } ?>>7-Call Return</option>
			<option value='8' <?php if ($device_key_type == "8") { echo $selected;$found=true; } ?>>8-SMS</option>
			<option value='9' <?php if ($device_key_type == "9") { echo $selected;$found=true; } ?>>9-Call Pickup</option>
			<option value='10' <?php if ($device_key_type == "10") { echo $selected;$found=true; } ?>>10-Call Park</option>
			<option value='11' <?php if ($device_key_type == "11") { echo $selected;$found=true; } ?>>11-DTMF</option>
			<option value='12' <?php if ($device_key_type == "12") { echo $selected;$found=true; } ?>>12-Voicemail</option>
			<option value='13' <?php if ($device_key_type == "13") { echo $selected;$found=true; } ?>>13-SpeedDial</option>
			<option value='14' <?php if ($device_key_type == "14") { echo $selected;$found=true; } ?>>14-Intercom</option>
			<option value='15' <?php if ($device_key_type == "15") { echo $selected;$found=true; } ?>>15-Line(default for line key)</option>
			<option value='16' <?php if ($device_key_type == "16") { echo $selected;$found=true; } ?>>16-BLF</option>
			<option value='17' <?php if ($device_key_type == "17") { echo $selected;$found=true; } ?>>17-URL</option>
			<option value='19' <?php if ($device_key_type == "19") { echo $selected;$found=true; } ?>>19-Public Hold</option>
			<option value='20' <?php if ($device_key_type == "20") { echo $selected;$found=true; } ?>>20-Private</option>
			<option value='21' <?php if ($device_key_type == "21") { echo $selected;$found=true; } ?>>21-Shared Line</option>
			<option value='22' <?php if ($device_key_type == "22") { echo $selected;$found=true; } ?>>22-XML Group</option>
			<option value='23' <?php if ($device_key_type == "23") { echo $selected;$found=true; } ?>>23-Group Pickup</option>
			<option value='24' <?php if ($device_key_type == "24") { echo $selected;$found=true; } ?>>24-Paging</option>
			<option value='25' <?php if ($device_key_type == "25") { echo $selected;$found=true; } ?>>25-Record</option>
			<option value='27' <?php if ($device_key_type == "27") { echo $selected;$found=true; } ?>>27-XML Browser</option>
			<option value='28' <?php if ($device_key_type == "28") { echo $selected;$found=true; } ?>>28-History</option>
			<option value='29' <?php if ($device_key_type == "29") { echo $selected;$found=true; } ?>>29-Directory</option>
			<option value='30' <?php if ($device_key_type == "30") { echo $selected;$found=true; } ?>>30-Menu</option>
			<option value='32' <?php if ($device_key_type == "32") { echo $selected;$found=true; } ?>>32-New SMS</option>
			<option value='33' <?php if ($device_key_type == "33") { echo $selected;$found=true; } ?>>33-Status</option>
			<option value='34' <?php if ($device_key_type == "34") { echo $selected;$found=true; } ?>>34-Hot Desking</option>
			<option value='35' <?php if ($device_key_type == "35") { echo $selected;$found=true; } ?>>35-URL Record</option>
			<option value='38' <?php if ($device_key_type == "38") { echo $selected;$found=true; } ?>>38-LDAP</option>
			<option value='39' <?php if ($device_key_type == "39") { echo $selected;$found=true; } ?>>39-BLF List</option>
			<option value='40' <?php if ($device_key_type == "40") { echo $selected;$found=true; } ?>>40-Prefix</option>
			<option value='41' <?php if ($device_key_type == "41") { echo $selected;$found=true; } ?>>41-Zero-Sp-Touch</option>
			<option value='42' <?php if ($device_key_type == "42") { echo $selected;$found=true; } ?>>42-ACD</option>
			<option value='43' <?php if ($device_key_type == "43") { echo $selected;$found=true; } ?>>43-Local Phonebook</option>
			<option value='44' <?php if ($device_key_type == "44") { echo $selected;$found=true; } ?>>44-Broadsoft Phonebook</option>
			<option value='45' <?php if ($device_key_type == "45") { echo $selected;$found=true; } ?>>45-Local Group</option>
			<option value='46' <?php if ($device_key_type == "46") { echo $selected;$found=true; } ?>>46-Broadsoft Group</option>
			<option value='47' <?php if ($device_key_type == "47") { echo $selected;$found=true; } ?>>47-XML Phonebook</option>
			<option value='48' <?php if ($device_key_type == "48") { echo $selected;$found=true; } ?>>48-Switch Account Up</option>
			<option value='49' <?php if ($device_key_type == "49") { echo $selected;$found=true; } ?>>49-Switch Account Down</option>
			<option value='50' <?php if ($device_key_type == "50") { echo $selected;$found=true; } ?>>50-Keypad Lock</option>
		</optgroup>
		<optgroup label='Other'>
			<option value='line' <?php if ($device_key_type == "line") { echo $selected;$found=true; } ?>>line</option>
			<option value='other'>other</option>
		<?php
			if (!$found) {
				echo "<option value='".$device_key_type."'>".$device_key_type."</option>\n";
			}
		?>
		</optgroup>
		</select>

		<?php
		echo "</td>\n";

		echo "<td class='vtable' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	<select class='formfld' style='width: 45px;' name='device_keys[".$x."][device_key_line]'>\n";
		echo "	<option value=''></option>\n";
		echo "	<option value='0'>0</option>\n";
		echo "	<option value='1'>1</option>\n";
		echo "	<option value='2'>2</option>\n";
		echo "	<option value='3'>3</option>\n";
		echo "	<option value='4'>4</option>\n";
		echo "	<option value='5'>5</option>\n";
		echo "	<option value='6'>6</option>\n";
		echo "	<option value='7'>7</option>\n";
		echo "	<option value='8'>8</option>\n";
		echo "	<option value='9'>9</option>\n";
		echo "	<option value='10'>10</option>\n";
		echo "	<option value='11'>11</option>\n";
		echo "	<option value='12'>12</option>\n";
		echo "	</select>\n";
		echo "</td>\n";

		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_value]' style='width: 120px;' maxlength='255' value=\"$device_key_value\">\n";
		echo "</td>\n";

		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_extension]' style='width: 120px;' maxlength='255' value=\"$device_key_extension\">\n";
		echo "</td>\n";

		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_label]' style='width: 150px;' maxlength='255' value=\"$device_key_label\">\n";
		echo "</td>\n";

		echo "			<td class='vtable' align='left'>\n";
		echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
		echo "			</td>\n";
		echo "			</tr>\n";
		echo "			</table>\n";
		if (strlen($text['description-keys']) > 0) {
			echo "			<br>".$text['description-keys']."\n";
		}
		echo "		</td>";
		echo "	</tr>";
	}

//device settings
	if (permission_exists('device_setting_add')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-settings'].":</td>";
		echo "		<td class='vtable' align='left'>";
		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_name']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_value']."</td>\n";
		echo "				<td class='vtable'>".$text['label-enabled']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_setting_description']."</td>\n";
		echo "				<td>&nbsp;</td>\n";
		echo "			</tr>\n";

		$x = 0;
		foreach($device_settings as $row) {
			//if (strlen($row['line_number']) == 0) { $row['line_number'] = "1"; }
			echo "			<tr>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_setting_subcategory']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_setting_value']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_setting_enabled']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$row['device_setting_description']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td>\n";
			if (permission_exists('device_edit')) {
				echo "					<a href='device_setting_edit.php?device_uuid=".$row['device_uuid']."&id=".$row['device_setting_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('device_delete')) {
				echo "					<a href='device_setting_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_setting_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			}
			echo "				</td>\n";
			echo "			</tr>\n";
			$x++;
		}

		$x = 0;
		echo "<tr>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_subcategory]' style='width: 120px;' maxlength='255' value=\"$device_setting_subcategory\">\n";
		echo "</td>\n";

		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_value]' style='width: 120px;' maxlength='255' value=\"$device_setting_value\">\n";
		echo "</td>\n";

		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='device_settings[".$x."][device_setting_enabled]' style='width: 90px;'>\n";
		echo "    <option value=''></option>\n";
		if ($device_setting_enabled == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($device_setting_enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "</td>\n";

		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='device_settings[".$x."][device_setting_description]' style='width: 150px;' maxlength='255' value=\"$device_setting_description\">\n";
		echo "</td>\n";

		echo "			<td class='vtable' align='left'>\n";
		echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
		echo "			</td>\n";
		echo "			</tr>\n";
		echo "			</table>\n";
		if (strlen($text['description-settings']) > 0) {
			echo "			<br>".$text['description-settings']."\n";
		}
		echo "		</td>";
		echo "	</tr>";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_vendor'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_vendor' maxlength='255' value=\"$device_vendor\">\n";
	echo "<br />\n";
	echo $text['description-device_vendor']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_model'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_model' maxlength='255' value=\"$device_model\">\n";
	echo "<br />\n";
	echo $text['description-device_model']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_firmware_version'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_firmware_version' maxlength='255' value=\"$device_firmware_version\">\n";
	echo "<br />\n";
	echo $text['description-device_firmware_version']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	/*
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_username'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_username' maxlength='255' value=\"$device_username\">\n";
	echo "<br />\n";
	echo $text['description-device_username']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_password'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_password' maxlength='255' value=\"$device_password\">\n";
	echo "<br />\n";
	echo $text['description-device_password']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	*/

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_provision_enable'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='device_provision_enable'>\n";
	echo "    <option value=''></option>\n";
	if ($device_provision_enable == "true" || strlen($device_provision_enable) == 0) { 
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($device_provision_enable == "false") { 
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-device_provision_enable']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_time_zone'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_time_zone' maxlength='255' value=\"$device_time_zone\">\n";
	echo "<br />\n";
	echo $text['description-device_time_zone']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_description' maxlength='255' value=\"$device_description\">\n";
	echo "<br />\n";
	echo $text['description-device_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='device_uuid' value='$device_uuid'>\n";
	}
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "resources/footer.php";
?>