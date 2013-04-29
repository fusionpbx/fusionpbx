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
require_once "includes/require.php";

//check permissions
	require_once "includes/checkauth.php";
	if (permission_exists('device_add') || permission_exists('device_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$device_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http post values and set them to php variables
	if (count($_POST) > 0) {
		$device_mac_address = check_str($_POST["device_mac_address"]);
		$device_mac_address = strtolower($device_mac_address);
		$device_mac_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address);
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
	}

//use the mac address to find the vendor
	if (strlen($device_vendor) == 0) {
		switch (substr($device_mac_address, 0, 6)) {
		case "00085d":
			$device_vendor = "aastra";
			break;
		case "000e08":
			$device_vendor = "linksys";
			break;
		case "0004f2":
			$device_vendor = "polycom";
			break;
		case "00907a":
			$device_vendor = "polycom";
			break;
		case "001873":
			$device_vendor = "cisco";
			break;
		case "00045a":
			$device_vendor = "linksys";
			break;
		case "000625":
			$device_vendor = "linksys";
			break;
		case "001565":
			$device_vendor = "yealink";
			break;
		case "000413":
			$device_vendor = "snom";
		default:
			$device_vendor = "";
		}
	}

//add or update the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		$msg = '';
		if ($action == "update") {
			$device_uuid = check_str($_POST["device_uuid"]);
		}

		//check for all required data
			if (strlen($device_mac_address) == 0) { $msg .= "Please provide: MAC Address<br>\n"; }
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
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "includes/header.php";
				require_once "includes/persistformvar.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			}

		//add or update the database
			if ($_POST["persistformvar"] != "true") {
				if ($action == "add" && permission_exists('device_add')) {
					//sql add
						$device_uuid = uuid();
						$sql = "insert into v_devices ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "device_uuid, ";
						$sql .= "device_mac_address, ";
						$sql .= "device_label, ";
						$sql .= "device_vendor, ";
						$sql .= "device_model, ";
						$sql .= "device_firmware_version, ";
						$sql .= "device_provision_enable, ";
						$sql .= "device_template, ";
						$sql .= "device_username, ";
						$sql .= "device_password, ";
						$sql .= "device_time_zone, ";
						$sql .= "device_description ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'$domain_uuid', ";
						$sql .= "'$device_uuid', ";
						$sql .= "'$device_mac_address', ";
						$sql .= "'$device_label', ";
						$sql .= "'$device_vendor', ";
						$sql .= "'$device_model', ";
						$sql .= "'$device_firmware_version', ";
						$sql .= "'$device_provision_enable', ";
						$sql .= "'$device_template', ";
						$sql .= "'$device_username', ";
						$sql .= "'$device_password', ";
						$sql .= "'$device_time_zone', ";
						$sql .= "'$device_description' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);

					//write the provision files
						require_once "app/provision/provision_write.php";

					//redirect the user
						require_once "includes/header.php";
						echo "<meta http-equiv=\"refresh\" content=\"2;url=devices.php\">\n";
						echo "<div align='center'>\n";
						echo "Add Complete\n";
						echo "</div>\n";
						require_once "includes/footer.php";
						return;
				} //if ($action == "add")

				if ($action == "update" && permission_exists('device_edit')) {
					//sql update
						$sql = "update v_devices set ";
						$sql .= "device_mac_address = '$device_mac_address', ";
						$sql .= "device_label = '$device_label', ";
						$sql .= "device_vendor = '$device_vendor', ";
						$sql .= "device_model = '$device_model', ";
						$sql .= "device_firmware_version = '$device_firmware_version', ";
						$sql .= "device_provision_enable = '$device_provision_enable', ";
						$sql .= "device_template = '$device_template', ";
						$sql .= "device_username = '$device_username', ";
						$sql .= "device_password = '$device_password', ";
						$sql .= "device_time_zone = '$device_time_zone', ";
						$sql .= "device_description = '$device_description' ";
						$sql .= "where domain_uuid = '$domain_uuid' ";
						$sql .= "and device_uuid = '$device_uuid'";
						$db->exec(check_sql($sql));
						unset($sql);

					//write the provision files
						require_once "app/provision/provision_write.php";

					//redirect the user
						require_once "includes/header.php";
						echo "<meta http-equiv=\"refresh\" content=\"2;url=devices.php\">\n";
						echo "<div align='center'>\n";
						echo "Update Complete\n";
						echo "</div>\n";
						require_once "includes/footer.php";
						return;
				}
			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$device_uuid = $_GET["id"];
		$sql = "select * from v_devices ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and device_uuid = '$device_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
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

//begin the content
	require_once "includes/header.php";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>Device</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='devices.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "The following information is used to provision endpoints.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	MAC Address:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_mac_address' maxlength='255' value=\"$device_mac_address\">\n";
	echo "<br />\n";
	echo "Enter the MAC address.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Label:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_label' maxlength='255' value=\"$device_label\">\n";
	echo "<br />\n";
	echo "Enter the device label.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Template:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "<select id='device_template' name='device_template' class='formfld'>\n";
	echo "<option value=''></option>\n";
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$_SESSION["domain_name"])) {
		$temp_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$_SESSION["domain_name"];
	} else {
		$temp_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision";
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
	echo "Select a template.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Vendor:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_vendor' maxlength='255' value=\"$device_vendor\">\n";
	echo "<br />\n";
	echo "Enter the vendor name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Model:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_model' maxlength='255' value=\"$device_model\">\n";
	echo "<br />\n";
	echo "Enter the model number.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Firmware Version:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_firmware_version' maxlength='255' value=\"$device_firmware_version\">\n";
	echo "<br />\n";
	echo "Enter the firmware version.\n";
	echo "</td>\n";
	echo "</tr>\n";

	/*
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Username:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_username' maxlength='255' value=\"$device_username\">\n";
	echo "<br />\n";
	echo "Enter the username.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Password:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_password' maxlength='255' value=\"$device_password\">\n";
	echo "<br />\n";
	echo "Enter the password.\n";
	echo "</td>\n";
	echo "</tr>\n";
	*/

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='device_provision_enable'>\n";
	echo "    <option value=''></option>\n";
	if ($device_provision_enable == "true" || strlen($device_provision_enable) == 0) { 
		echo "    <option value='true' selected >true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($device_provision_enable == "false") { 
		echo "    <option value='false' selected >false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Enable or disable provisioning for this device.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Time Zone:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_time_zone' maxlength='255' value=\"$device_time_zone\">\n";
	echo "<br />\n";
	echo "Enter the time zone.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_description' maxlength='255' value=\"$device_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='device_uuid' value='$device_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "includes/footer.php";
?>