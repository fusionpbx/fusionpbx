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
*/
require_once "root.php";
require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_profile_add') || permission_exists('device_profile_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$device_profile_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//echo "<textarea>"; print_r($_POST); echo "</textarea>"; exit;
		$device_profile_name = check_str($_POST["device_profile_name"]);
		$device_profile_enabled = check_str($_POST["device_profile_enabled"]);
		$device_profile_description = check_str($_POST["device_profile_description"]);
		$device_key_category = check_str($_POST["device_key_category"]);
		$device_key_id = check_str($_POST["device_key_id"]);
		$device_key_type = check_str($_POST["device_key_type"]);
		$device_key_line = check_str($_POST["device_key_line"]);
		$device_key_value = check_str($_POST["device_key_value"]);
		$device_key_extension = check_str($_POST["device_key_extension"]);
		$device_key_label = check_str($_POST["device_key_label"]);

		//allow the domain_uuid to be changed only with the device_profile_domain permission
		if (permission_exists('device_profile_domain')) {
			$domain_uuid = check_str($_POST["domain_uuid"]);
		}
		else {
			$_POST["domain_uuid"] = $_SESSION['domain_uuid'];
		}
	}

//add or update the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//check for all required data
			$msg = '';
			if (strlen($device_profile_name) == 0) { $msg .= $text['message-required'].$text['label-profile_name']."<br>\n"; }
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

				//set the default
					$save = true;

				//save the profile
					if ($save) {
						$orm = new orm;
						$orm->name('device_profiles');
						if (strlen($device_profile_uuid) > 0) {
							$orm->uuid($device_profile_uuid);
						}
						$orm->save($_POST);
						$response = $orm->message;
						if (strlen($response['uuid']) > 0) {
							$device_profile_uuid = $response['uuid'];
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
								header("Location: device_profile_edit.php?id=".$device_profile_uuid);
								exit;
						}
					}

			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$orm = new orm;
		$orm->name('device_profiles');
		$orm->uuid($device_profile_uuid);
		$result = $orm->find()->get();
		//$message = $orm->message;
		foreach ($result as &$row) {
			$device_profile_name = $row["device_profile_name"];
			$device_profile_domain_uuid = $row["domain_uuid"];
			$device_profile_enabled = $row["device_profile_enabled"];
			$device_profile_description = $row["device_profile_description"];
		}
		unset ($prep_statement);
	}

//set the sub array index
	$x = "999";

//get device keys
	$sql = "SELECT * FROM v_device_keys ";
	$sql .= "WHERE device_profile_uuid = '".$device_profile_uuid."' ";
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

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-profile'];

//javascript to change select to input and back again
	?><script language="javascript">
		var objs;

		function change_to_input(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			tb.setAttribute('style', 'width: 175px;');
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
//show the content
	echo "<form method='post' name='frm' id='frm' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'>";
	echo "	<b>".$text['header-profile']."</b>";
	echo "	<br><br>";
	echo "	".$text['description-profile'];
	echo "	<br><br>";
	echo "</td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='device_profiles.php'\" value='".$text['button-back']."'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"window.location='device_profile_copy.php'\" value='".$text['button-copy']."'>\n";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_profile_name' maxlength='255' value=\"".$device_profile_name."\">\n";
	echo "<br />\n";
	echo $text['description-profile_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

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
			echo "<tr>\n";
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
			echo "	<select class='formfld' name='device_keys[".$x."][device_key_id]'>\n";
			echo "	<option value=''></option>\n";
			for ($i = 1; $i <= 99; $i++) {
				echo "	<option value='".$i."' ".(($row['device_key_id'] == $i) ? "selected='selected'" : null).">".$i."</option>\n";
			}
			echo "	</select>\n";
			echo "</td>\n";

			echo "<td align='left' nowrap='nowrap'>\n";
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
			<select class='formfld' style='width:80px;' name='device_keys[<?php echo $x; ?>][device_key_type]' id='key_type_<?php echo $x; ?>' onchange="document.getElementById('key_vendor_<?php echo $x; ?>').value=document.getElementById('key_type_<?php echo $x; ?>').options[document.getElementById('key_type_<?php echo $x; ?>').selectedIndex].parentNode.label.toLowerCase();">
			<option value=''></option>
			<?php
			if (strtolower($device_vendor) == "aastra" || strlen($device_vendor) == 0) {
				if (strlen($device_vendor) == 0) { echo "<optgroup label='Aastra'>"; }
				?>
				<option value='blf' <?php if ($row['device_key_type'] == "blf") { echo $selected;$found=true; } ?>><?php echo $text['label-blf'] ?></option>
				<option value='blfxfer' <?php if ($row['device_key_type'] == "blfxfer") { echo $selected;$found=true; } ?>><?php echo $text['label-blf_xfer'] ?></option>
				<option value='dnd' <?php if ($row['device_key_type'] == "dnd") { echo $selected;$found=true; } ?>><?php echo $text['label-dnd'] ?></option>
				<option value='speeddial' <?php if ($row['device_key_type'] == "speeddial") { echo $selected;$found=true; } ?>><?php echo $text['label-speed_dial'] ?></option>
				<?php
				if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
			}
			if (strtolower($device_vendor) == "cisco" || strlen($device_vendor) == 0) {
				if (strlen($device_vendor) == 0) { echo "<optgroup label='Cisco'>"; }
				?>
				<option value='line' <?php if ($row['device_key_type'] == "line") { echo $selected;$found=true; } ?>><?php echo $text['label-line'] ?></option>
				<option value='disabled' <?php if ($row['device_key_type'] == "disabled") { echo $selected;$found=true; } ?>><?php echo $text['label-disabled'] ?></option>
				<?php
				if (strlen($device_vendor) == 0) { echo "</optgroup>"; }
			}
			if (strtolower($device_vendor) == "grandstream" || strlen($device_vendor) == 0) {
				if (strlen($device_vendor) == 0) { echo "<optgroup label='Grandstream'>"; }
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
			if (strtolower($device_vendor) == "polycom" || strlen($device_vendor) == 0) {
				if (strlen($device_vendor) == 0) { echo "<optgroup label='Polycom'>"; }
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
			if (strtolower($device_vendor) == "snom" || strlen($device_vendor) == 0) {
				if (strlen($device_vendor) == 0) { echo "<optgroup label='Snom'>"; }
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
			if (strtolower($device_vendor) == "yealink" || strlen($device_vendor) == 0) {
				if (strlen($device_vendor) == 0) { echo "<optgroup label='Yealink'>"; }
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
			echo "<td class='' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	<select class='formfld' style='width: 45px;' name='device_keys[".$x."][device_key_line]'>\n";
			echo "		<option value=''></option>\n";
			for ($l = 0; $l <= 12; $l++) {
				echo "	<option value='".$l."' ".(($row['device_key_line'] == $l) ? "selected='selected'" : null).">".$l."</option>\n";
			}
			echo "	</select>\n";
			echo "</td>\n";

			echo "<td class='' align='left'>\n";
			echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_value]' style='width: 120px;' maxlength='255' value=\"".$row['device_key_value']."\">\n";
			echo "</td>\n";

			echo "<td class='' align='left'>\n";
			echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_extension]' style='width: 120px;' maxlength='255' value=\"".$row['device_key_extension']."\">\n";
			echo "</td>\n";

			echo "<td class='' align='left'>\n";
			echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_label]' style='width: 150px;' maxlength='255' value=\"".$row['device_key_label']."\">\n";
			echo "</td>\n";

			echo "<td nowrap='nowrap'>\n";
			if (strlen($row['device_key_uuid']) > 0) {
				if (permission_exists('device_key_delete')) {
					echo "					<a href='device_key_delete.php?device_profile_uuid=".$row['device_profile_uuid']."&id=".$row['device_key_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
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

	if (permission_exists('device_profile_domain')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-profile_domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='domain_uuid'>\n";
		if ($action == "update") {
			echo "	<option value='' ".(($device_profile_domain_uuid == '') ? "selected='selected'" : null).">".$text['select-global']."</option>\n";
			foreach ($_SESSION['domains'] as $dom) {
				echo "<option value='".$dom['domain_uuid']."' ".(($device_profile_domain_uuid == $dom['domain_uuid']) ? "selected='selected'" : null).">".$dom['domain_name']."</option>\n";
			}
		}
		else {
			echo "	<option value=''>".$text['select-global']."</option>\n";
			foreach ($_SESSION['domains'] as $dom) {
				echo "<option value='".$dom['domain_uuid']."' ".(($domain_uuid == $dom['domain_uuid']) ? "selected='selected'" : null).">".$dom['domain_name']."</option>\n";
			}
		}
		echo "	</select>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_profile_enabled'>\n";
	echo "		<option value='true'>".$text['label-true']."</option>\n";
	echo "		<option value='false' ".(($device_profile_enable == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-profile_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_profile_description' maxlength='255' value=\"".$device_profile_description."\">\n";
	echo "<br />\n";
	echo $text['description-profile_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='device_profile_uuid' value='".$device_profile_uuid."'>\n";
	}
	echo "		<br>";
	echo "		<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "	</td>\n";
	echo "</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//show the footer
	require_once "resources/footer.php";
?>