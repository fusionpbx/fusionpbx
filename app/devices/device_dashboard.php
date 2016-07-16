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
	Copyright (C) 2016 All Rights Reserved.

*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_key_add') || permission_exists('device_key_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/devices');

//include the device class
	//require_once "app/devices/resources/classes/device.php";

//add or update the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//add or update the database
			if ($_POST["persistformvar"] != "true") {

				//add or update the device keys
					foreach ($_POST['device_keys'] as &$row) {

						//validate the data
							$valid_data = true;
							//if (!is_uuid($row["device_key_uuid"])) { $valid_data = false; }
							if (isset($row["device_key_id"])) {
								if (!is_numeric($row["device_key_id"])) { $valid_data = false; echo $row["device_key_id"]." id "; }
							}
							if (strlen($row["device_key_type"]) > 25) { $valid_data = false; echo "type "; }
							if (strlen($row["device_key_value"]) > 25) { $valid_data = false; echo "value "; }
							if (strlen($row["device_key_label"]) > 25) { $valid_data = false; echo "label "; }

						//escape characters in the string
							$device_uuid = check_str($row["device_uuid"]);
							$device_key_uuid = check_str($row["device_key_uuid"]);
							$device_key_id = check_str($row["device_key_id"]);
							$device_key_type = check_str($row["device_key_type"]);
							$device_key_line = check_str($row["device_key_line"]);
							$device_key_value = check_str($row["device_key_value"]);
							$device_key_label = check_str($row["device_key_label"]);
							$device_key_category = check_str($row["device_key_category"]);
							$device_key_vendor = check_str($row["device_key_vendor"]);

						//sql update
							if (strlen($device_key_uuid) == 0) {
								if (permission_exists('device_key_add') && strlen($device_key_type) > 0 && strlen($device_key_value) > 0) {

									//create the primary keys
										$device_key_uuid = uuid();

									//insert the keys
										$sql = "insert into v_device_keys ";
										$sql .= "(";
										$sql .= "domain_uuid, ";
										$sql .= "device_key_uuid, ";
										$sql .= "device_uuid, ";
										$sql .= "device_key_id, ";
										$sql .= "device_key_type, ";
										$sql .= "device_key_line, ";
										$sql .= "device_key_value, ";
										$sql .= "device_key_label, ";
										$sql .= "device_key_category, ";
										$sql .= "device_key_vendor ";
										$sql .= ") ";
										$sql .= "VALUES (";
										$sql .= "'".$_SESSION['domain_uuid']."', ";
										$sql .= "'".$device_key_uuid."', ";
										$sql .= "'".$device_uuid."', ";
										$sql .= "'".$device_key_id."', ";
										$sql .= "'".$device_key_type."', ";
										$sql .= "'".$device_key_line."', ";
										$sql .= "'".$device_key_value."', ";
										$sql .= "'".$device_key_label."', ";
										$sql .= "'".$device_key_category."', ";
										$sql .= "'".$device_key_vendor."' ";
										$sql .= ");";
										//echo $sql;

									//action add or update
										$action = "add";
								}
							}
							else {
								//action add or update
									$action = "update";

								//update the device keys
									$sql = "update v_device_keys set ";
									if (permission_exists('device_key_id')) {
										$sql .= "device_key_id = '".$device_key_id."', ";
									}
									$sql .= "device_key_type = '".$device_key_type."', ";
									$sql .= "device_key_value = '".$device_key_value."', ";
									$sql .= "device_key_label = '".$device_key_label."' ";
									$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
									$sql .= "and device_key_uuid = '".$device_key_uuid."'; ";
							}
							if ($valid_data) {
								$db->exec(check_sql($sql));
								//echo "valid: ".$sql."\n";
							}
							else {
								//echo "invalid: ".$sql."\n";
							}
					}

				//write the provision files
					//if (strlen($_SESSION['provision']['path']['text']) > 0) {
						//require_once "app/provision/provision_write.php";
					//}

				//set the message
					if (!isset($_SESSION['message'])) {
						//set the message
							if ($action == "add") {
								//save the message to a session variable
									$_SESSION['message'] = $text['message-add'];
							}
							if ($action == "update") {
								//save the message to a session variable
									$_SESSION['message'] = $text['message-update'];
							}
						//redirect the browser
							header("Location: /core/user_settings/user_dashboard.php");
							exit;
					}

			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

//set the sub array index
	$x = "999";

//get device
	$sql = "SELECT device_uuid, device_profile_uuid FROM v_devices ";
	$sql .= "WHERE device_user_uuid = '".$_SESSION['user_uuid']."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$row = $prep_statement->fetch(PDO::FETCH_NAMED);
	$device_uuid = $row['device_uuid'];
	$device_profile_uuid = $row['device_profile_uuid'];
	unset($row);

//get device lines
	$sql = "SELECT * from v_device_lines ";
	$sql .= "WHERE device_uuid = '".$device_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_lines = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//get the user
	foreach ($device_lines as $row) {
		if ($_SESSION['domain_name'] == $row['server_address']) {
			$user_id = $row['user_id'];
			$server_address = $row['server_address'];
			break;
		}
	}

//set the sip profile name
	$sip_profile_name = 'internal';

//get device keys in the right order where device keys are listed after the profile keys
	$sql = "SELECT * FROM v_device_keys ";
	$sql .= "WHERE (";
	$sql .= "device_uuid = '".$device_uuid."' ";
	if (strlen($device_profile_uuid) > 0) {
		$sql .= "or device_profile_uuid = '".$device_profile_uuid."' ";
	}
	$sql .= ") ";
	$sql .= "ORDER BY ";
	$sql .= "device_key_vendor ASC, ";
	$sql .= "CASE device_key_category ";
	$sql .= "WHEN 'line' THEN 1 ";
	$sql .= "WHEN 'memory' THEN 2 ";
	$sql .= "WHEN 'programmable' THEN 3 ";
	$sql .= "WHEN 'expansion' THEN 4 ";
	$sql .= "ELSE 100 END, ";
	if ($db_type == "mysql") {
		$sql .= "device_key_id ASC ";
	}
	else {
		$sql .= "CAST(device_key_id as numeric) ASC, ";
	}
	$sql .= "CASE WHEN device_uuid IS NULL THEN 0 ELSE 1 END ASC ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$keys = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset($sql,$prep_statement);

//override profile keys with device keys
	foreach($keys as $row) {
		$id = $row['device_key_id'];
		$device_keys[$id] = $row;
		if (is_uuid($row['device_profile_uuid'])) {
			$device_keys[$id]['device_key_owner'] = "profile";
		}
		else {
			$device_keys[$id]['device_key_owner'] = "device";
		}
	}
	unset($keys);

//get the vendor count and last and device information
	$vendor_count = 0;
	foreach($device_keys as $row) {
		if ($previous_vendor != $row['device_key_vendor']) {
			$previous_vendor = $row['device_key_vendor'];
			$device_uuid = $row['device_uuid'];
			$device_key_vendor = $row['device_key_vendor'];
			$device_key_id = $row['device_key_id'];
			$device_key_line = $row['device_key_line'];
			$device_key_category = $row['device_key_category'];
			$vendor_count++;
		}
	}

//add a new key
	if (permission_exists('device_key_add')) {
		$device_keys[$x]['device_key_category'] = $device_key_category;
		$device_keys[$x]['device_key_id'] = '';
		$device_keys[$x]['device_uuid'] = $device_uuid;
		$device_keys[$x]['device_key_vendor'] = $device_key_vendor;
		$device_keys[$x]['device_key_type'] = '';
		$device_keys[$x]['device_key_line'] = '';
		$device_keys[$x]['device_key_value'] = '';
		$device_keys[$x]['device_key_extension'] = '';
		$device_keys[$x]['device_key_label'] = '';
	}

//show the header
	//require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action='/app/devices/device_dashboard.php'>\n";

	echo "	<div style='float: left;'>";
	echo "		<b>".$text['title-device_keys']."</b><br />";
	if (!$is_included) {
		echo "	".$text['description-device_keys']."<br />";
	}
	echo "	<br />";
	echo "	</div>\n";

	echo "	<div style='float: right;'>";
	echo "	</div>\n";

	echo "<div style='float: right;'>\n";
	echo "	<input type='button' class='btn' value='".$text['button-apply']."' onclick=\"document.location.href='".PROJECT_PATH."/app/devices/cmd.php?cmd=check_sync&profile=".$sip_profile_name."&user=".$user_id."@".$server_address."&domain=".$server_address."&agent=".$device_key_vendor."';\">&nbsp;\n";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</div>\n";

	if (permission_exists('device_key_edit')) {
		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		$x = 0;
		foreach($device_keys as $row) {
			//set the variables
				$device_key_vendor = $row['device_key_vendor'];
				$device_vendor = $row['device_key_vendor'];
			//set the column names
				if ($previous_device_key_vendor != $row['device_key_vendor']) {
					echo "			<tr>\n";
					//echo "				<td class='vtable'>".$text['label-device_key_category']."</td>\n";
					echo "				<th>".$text['label-device_key_id']."</th>\n";
					if (strlen($row['device_key_vendor']) > 0) {
						echo "				<th>".ucwords($row['device_key_vendor'])."</th>\n";
					} else {
						echo "				<th>".$text['label-device_key_type']."</th>\n";
					}
					//echo "				<td class='row_style".$c."'>".$text['label-device_key_line']."</td>\n";
					echo "				<th>".$text['label-device_key_value']."</th>\n";
					//echo "				<td class='row_style".$c."'>".$text['label-device_key_extension']."</td>\n";
					echo "				<th>".$text['label-device_key_label']."</th>\n";
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
				/*
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
				*/

				echo "<td class='row_style".$c." row_style_slim' valign='top' nowrap='nowrap'>\n";
				if (permission_exists('device_key_id') || permission_exists('device_key_add')) {
					$selected = "selected='selected'";
					echo "	<select class='formfld' name='device_keys[".$x."][device_key_id]'>\n";
					echo "	<option value=''></option>\n";
					$i = 1;
					while ($i < 100) {
						echo "	<option value='$i' ".($row['device_key_id'] == $i ? $selected:"").">$i</option>\n";
						$i++;
					}
					echo "	</select>\n";
				}
				else {
					echo "&nbsp;&nbsp;".$row['device_key_id'];
				}
				echo "</td>\n";

				echo "<td class='row_style".$c." row_style_slim' nowrap='nowrap'>\n";
				//echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_type]' style='width: 120px;' maxlength='255' value=\"$row['device_key_type']\">\n";

				?>

				<input class='formfld' type='hidden' id='key_vendor_<?php echo $x; ?>' name='device_keys[<?php echo $x; ?>][device_key_vendor]' value="<?php echo $device_key_vendor; ?>">
				<input class='formfld' type='hidden' id='key_category_<?php echo $x; ?>' name='device_keys[<?php echo $x; ?>][device_key_category]' value="<?php echo $device_key_category; ?>">
				<input class='formfld' type='hidden' id='key_uuid_<?php echo $x; ?>' name='device_keys[<?php echo $x; ?>][device_uuid]' value="<?php echo $device_uuid; ?>">
				<input class='formfld' type='hidden' id='key_key_line_<?php echo $x; ?>' name='device_keys[<?php echo $x; ?>][device_key_line]' value="<?php echo $device_key_line; ?>">

				<?php $selected = "selected='selected'"; ?>
				<?php $found = false; ?>
				<select class='formfld' style='width: 95px;' name='device_keys[<?php echo $x; ?>][device_key_type]' id='key_type_<?php echo $x; ?>' onchange="document.getElementById('key_vendor_<?php echo $x; ?>').value=document.getElementById('key_type_<?php echo $x; ?>').options[document.getElementById('key_type_<?php echo $x; ?>').selectedIndex].parentNode.label.toLowerCase();" >
				<option value=''></option>
				<?php
				if (strtolower($device_vendor) == "aastra" || strlen($vendor_count) > 1) {
					if ($vendor_count > 1) { echo "<optgroup label='Aastra'>"; }
					?>
					<option value='blf' <?php if ($row['device_key_type'] == "blf") { echo $selected;$found=true; } ?>><?php echo $text['label-blf'] ?></option>
					<option value='blfxfer' <?php if ($row['device_key_type'] == "blfxfer") { echo $selected;$found=true; } ?>><?php echo $text['label-blf_xfer'] ?></option>
					<option value='callers' <?php if ($row['device_key_type'] == "callers") { echo $selected;$found=true; } ?>><?php echo $text['label-callers'] ?></option>

					<option value='dnd' <?php if ($row['device_key_type'] == "dnd") { echo $selected;$found=true; } ?>><?php echo $text['label-dnd'] ?></option>
					<option value='speeddial' <?php if ($row['device_key_type'] == "speeddial") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial'] ?></option>
					<option value='xfer' <?php if ($row['device_key_type'] == "xfer") { echo $selected;$found=true; } ?>><?php echo $text['label-xfer'] ?></option>

					<?php
					if (strlen($vendor_count) > 1) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "cisco" || strlen($vendor_count) > 1) {
					if ($vendor_count > 1) { echo "<optgroup label='Cisco'>"; }
					?>
					<option value='blf' <?php if ($row['device_key_type'] == "blf") { echo $selected;$found=true; } ?>><?php echo $text['label-blf'] ?></option>
					<option value='line' <?php if ($row['device_key_type'] == "line") { echo $selected;$found=true; } ?>><?php echo $text['label-line'] ?></option>
					<option value='disabled' <?php if ($row['device_key_type'] == "disabled") { echo $selected;$found=true; } ?>><?php echo $text['label-disabled'] ?></option>
					<?php
					if (strlen($vendor_count) > 1) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "grandstream" || strlen($vendor_count) > 1) {
					if ($vendor_count > 1) { echo "<optgroup label='Grandstream'>"; }
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
					if (strlen($vendor_count) > 1) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "mitel" || strlen($vendor_count) > 1) {
					if ($vendor_count > 1) { echo "<optgroup label='Mitel'>"; }
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
					if (strlen($vendor_count) > 1) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "polycom" || strlen($vendor_count) > 1) {
					if ($vendor_count > 1) { echo "<optgroup label='Polycom'>"; }
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
					if (strlen($vendor_count) > 1) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "snom" || strlen($vendor_count) > 1) {
					if ($vendor_count > 1) { echo "<optgroup label='Snom'>"; }
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
					if (strlen($vendor_count) > 1) { echo "</optgroup>"; }
				}
				if (strtolower($device_vendor) == "yealink" || strlen($vendor_count) > 1) {
					if ($vendor_count > 1) { echo "<optgroup label='Yealink'>"; }
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
					if (strlen($vendor_count) > 1) { echo "</optgroup>"; }
				}
				?>
				</select>

				<?php
				//echo "</td>\n";
				//echo "<td valign='top' align='left' nowrap='nowrap'>\n";
				//echo "	<select class='formfld' name='device_keys[".$x."][device_key_line]'>\n";
				//echo "		<option value=''></option>\n";
				//for ($l = 0; $l <= 12; $l++) {
					//echo "	<option value='".$l."' ".(($row['device_key_line'] == $l) ? "selected='selected'" : null).">".$l."</option>\n";
				//}
				//echo "	</select>\n";
				//echo "</td>\n";

				echo "<td class='row_style".$c." row_style_slim'>\n";
				echo "	<input class='formfld' style='min-width: 50px; max-width: 100px;' type='text' name='device_keys[".$x."][device_key_value]' maxlength='255' value=\"".$row['device_key_value']."\">\n";
				echo "</td>\n";

				//echo "<td align='left'>\n";
				//echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_extension]' style='width: 120px;' maxlength='255' value=\"".$row['device_key_extension']."\">\n";
				//echo "</td>\n";

				echo "<td class='row_style".$c." row_style_slim'>\n";
				echo "	<input class='formfld' style='min-width: 50px; max-width: 100px;' type='text' name='device_keys[".$x."][device_key_label]' maxlength='255' value=\"".$row['device_key_label']."\">\n";
				echo "</td>\n";

				//echo "			<td align='left'>\n";
				//echo "				<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
				//echo "			</td>\n";
				//echo "				<td nowrap='nowrap'>\n";
				//if (strlen($row['device_key_uuid']) > 0) {
				//	if (permission_exists('device_key_delete')) {
				//		echo "					<a href='device_key_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_key_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
				//	}
				//}
				//echo "				</td>\n";

				echo "			</tr>\n";
			//set the previous vendor
				$previous_device_key_vendor = $row['device_key_vendor'];
			//increment the array key
				$x++;
			//alternate the value
				$c = ($c) ? 0 : 1;
		}
		echo "			</table>\n";
		//if (strlen($text['description-keys']) > 0) {
		//	echo "			<br>".$text['description-keys']."\n";
		//}
	}

	echo "</form>";

//show the footer
	//require_once "resources/footer.php";

?>
