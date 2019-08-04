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
	Copyright (C) 2008-2016 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$device_profile_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//echo "<textarea>"; print_r($_POST); echo "</textarea>"; exit;
		$device_profile_name = $_POST["device_profile_name"];
		$device_profile_enabled = $_POST["device_profile_enabled"];
		$device_profile_description = $_POST["device_profile_description"];
		$device_key_category = $_POST["device_key_category"];
		$device_key_id = $_POST["device_key_id"];
		$device_key_type = $_POST["device_key_type"];
		$device_key_line = $_POST["device_key_line"];
		$device_key_value = $_POST["device_key_value"];
		$device_key_extension = $_POST["device_key_extension"];
		$device_key_label = $_POST["device_key_label"];
                $device_key_icon = $_POST["device_key_icon"];
		
		//$device_setting_category = $_POST["device_setting_category"];
		$device_setting_subcategory = $_POST["device_setting_subcategory"];
		//$device_setting_name = $_POST["device_setting_name"];
		$device_setting_value = $_POST["device_setting_value"];
		$device_setting_enabled = $_POST["device_setting_enabled"];
		$device_setting_description = $_POST["device_setting_description"];
		
		//allow the domain_uuid to be changed only with the device_profile_domain permission
		if (permission_exists('device_profile_domain')) {
			$domain_uuid = $_POST["domain_uuid"];
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

				//prepare the array
					$array['device_profiles'][] = $_POST;

				//set the default
					$save = true;

				//save the profile
					if ($save) {
						$database = new database;
						$database->app_name = 'devices';
						$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
						if (strlen($device_profile_uuid) > 0) {
							$database->uuid($device_profile_uuid);
						}
						$database->save($array);
						$response = $database->message;
						if (strlen($response['uuid']) > 0) {
							$device_profile_uuid = $response['uuid'];
						}
						unset($array);
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
								header("Location: device_profile_edit.php?id=".$device_profile_uuid);
								exit;
						}
					}

			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_device_profiles ";
		$sql .= "where device_profile_uuid = :device_profile_uuid ";
		$parameters['device_profile_uuid'] = $device_profile_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$device_profile_name = $row["device_profile_name"];
			$device_profile_domain_uuid = $row["domain_uuid"];
			$device_profile_enabled = $row["device_profile_enabled"];
			$device_profile_description = $row["device_profile_description"];
		}
		unset($sql, $parameters, $row);
	}

//set the sub array index
	$x = "999";

//get device keys
	$sql = "select * from v_device_keys ";
	$sql .= "where device_profile_uuid = :device_profile_uuid ";
	$sql .= "order by ";
	$sql .= "device_key_vendor asc, ";
	$sql .= "case device_key_category ";
	$sql .= "when 'line' then 1 ";
	$sql .= "when 'memory' then 2 ";
	$sql .= "when 'programmable' then 3 ";
	$sql .= "when 'expansion' then 4 ";
	$sql .= "when 'expansion-1' then 5 ";
	$sql .= "when 'expansion-2' then 6 ";
	$sql .= "else 100 end, ";
	$sql .= $db_type == "mysql" ? "device_key_id asc " : "cast(device_key_id as numeric) asc ";
	$parameters['device_profile_uuid'] = $device_profile_uuid;
	$database = new database;
	$device_keys = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$device_keys[$x]['device_key_category'] = '';
	$device_keys[$x]['device_key_id'] = '';
	$device_keys[$x]['device_key_type'] = '';
	$device_keys[$x]['device_key_line'] = '';
	$device_keys[$x]['device_key_value'] = '';
	$device_keys[$x]['device_key_extension'] = '';
	$device_keys[$x]['device_key_protected'] = '';
	$device_keys[$x]['device_key_label'] = '';
	$device_keys[$x]['device_key_icon'] = '';

//get the vendors
	$sql = "select * ";
	$sql .= "from v_device_vendors as v ";
	$sql .= "where enabled = 'true' ";
	$sql .= "order by name asc ";
	$database = new database;
	$vendors = $database->select($sql, null, 'all');
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

//get the vendor count
	$vendor_count = 0;
	foreach($device_keys as $row) {
		if ($previous_vendor != $row['device_key_vendor']) {
			$previous_vendor = $row['device_key_vendor'];
			$vendor_count++;
		}
	}

//get device settings
	$sql = "select * from v_device_settings ";
	$sql .= "where device_profile_uuid = :device_profile_uuid ";
	$sql .= "order by device_setting_subcategory asc ";
	$parameters['device_profile_uuid'] = $device_profile_uuid;
	$database = new database;
	$device_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$device_settings[$x]['device_setting_name'] = '';
	$device_settings[$x]['device_setting_value'] = '';
	$device_settings[$x]['enabled'] = '';
	$device_settings[$x]['device_setting_description'] = '';

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
	echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"window.location='device_profile_copy.php?id=".escape($device_profile_uuid)."'\" value='".$text['button-copy']."'>\n";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_profile_name' maxlength='255' value=\"".escape($device_profile_name)."\">\n";
	echo "<br />\n";
	echo $text['description-profile_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-keys']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "			<table border='0' cellpadding='0' cellspacing='3'>\n";
	if ($vendor_count == 0) {
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$text['label-device_key_category']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_key_id']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_vendor']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_key_type']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_key_line']."</td>\n";
		echo "				<td class='vtable'>".$text['label-device_key_value']."</td>\n";
		if (permission_exists('device_key_extension')) {
			echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
		}
		if (permission_exists('device_key_protected')) {
			echo "				<td class='vtable'>".$text['label-device_key_protected']."</td>\n";
		}
		echo "				<td class='vtable'>".$text['label-device_key_label']."</td>\n";
		echo "				<td>&nbsp;</td>\n";
                echo "				<td class='vtable'>".$text['label-device_key_icon']."</td>\n";
		echo "				<td>&nbsp;</td>\n";
		echo "			</tr>\n";
	}

	$x = 0;
	foreach($device_keys as $row) {

		//set the device vendor
			$device_vendor = $row['device_key_vendor'];

		//get the device key vendor from the key type
			foreach ($vendor_functions as $function) {
				if ($row['device_key_vendor'] == $function['vendor_name'] && $row['device_key_type'] == $function['value']) {
					$device_key_vendor = $function['vendor_name'];
				}
			}

		//set the column names
			if ($previous_device_key_vendor != $row['device_key_vendor']) {
				echo "			<tr>\n";
				echo "				<td class='vtable'>".$text['label-device_key_category']."</td>\n";
				echo "				<td class='vtable'>".$text['label-device_key_id']."</td>\n";
				echo "				<td class='vtable'>".$text['label-device_vendor']."</td>\n";
				echo "				<td class='vtable'>".$text['label-device_key_type']."</td>\n";
				echo "				<td class='vtable'>".$text['label-device_key_line']."</td>\n";
				echo "				<td class='vtable'>".$text['label-device_key_value']."</td>\n";
				if (permission_exists('device_key_extension')) {
					echo "				<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
				}
				if (permission_exists('device_key_protected')) {
					echo "				<td class='vtable'>".$text['label-device_key_protected']."</td>\n";
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
				echo "	<input name='device_keys[".$x."][device_key_uuid]' type='hidden' value=\"".escape($row['device_key_uuid'])."\">\n";
			}
			else {
				echo "	<input name='device_keys[".$x."][device_key_uuid]' type='hidden' value=\"".uuid()."\">\n";
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
				if (strlen($row['device_key_vendor']) == 0) {
					if ($row['device_key_category'] == "expansion") {
						echo "	<option value='expansion' selected='selected'>".$text['label-expansion']." 1</option>\n";
					}
					else {
						echo "	<option value='expansion'>".$text['label-expansion']." 1</option>\n";
					}
					if ($row['device_key_category'] == "expansion-2") {
						echo "	<option value='expansion-2' selected='selected'>".$text['label-expansion']." 2</option>\n";
					}
					else {
						echo "	<option value='expansion-2'>".$text['label-expansion']." 2</option>\n";
					}
				}
				else {
					if (strtolower($row['device_key_vendor']) == "cisco" or strtolower($row['device_key_vendor']) == "yealink") {
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
			}
			echo "	</select>\n";
			echo "</td>\n";

			echo "<td valign='top' align='left' nowrap='nowrap'>\n";
			echo "	<select class='formfld' name='device_keys[".$x."][device_key_id]'>\n";
			echo "	<option value=''></option>\n";
			for ($i = 1; $i <= 255; $i++) {
				echo "	<option value='$i' ".($row['device_key_id'] == $i ? "selected":null).">$i</option>\n";
			}
			echo "	</select>\n";
			echo "</td>\n";

			echo "<td align='left' nowrap='nowrap'>\n";
			echo "<select class='formfld' name='device_keys[".$x."][device_key_vendor]' id='key_vendor_".$x."'>\n";
			echo "	<option value=''></option>\n";
			foreach ($vendors as $vendor) {
				$selected = '';
				if ($row['device_key_vendor'] == $vendor['name']) {
					$selected = "selected='selected'";
				}
				if (strlen($vendor['name']) > 0) {
					echo "		<option value='".escape($vendor['name'])."' $selected >".escape(ucwords($vendor['name']))."</option>\n";
				}
			}
			echo "</select>\n";
			echo "</td>\n";

			echo "<td align='left' nowrap='nowrap'>\n";
			echo "<select class='formfld' name='device_keys[".$x."][device_key_type]' id='key_type_".$x."'>\n";
			echo "	<option value=''></option>\n";
			$previous_vendor = '';
			$i=0;
			foreach ($vendor_functions as $function) {
				if (strlen($row['device_key_vendor']) == 0 && $function['vendor_name'] != $previous_vendor) {
					if ($i > 0) { echo "	</optgroup>\n"; }
					echo "	<optgroup label='".escape(ucwords($function['vendor_name']))."'>\n";
				}
				$selected = '';
				if ($row['device_key_vendor'] == $function['vendor_name'] && $row['device_key_type'] == $function['value']) {
					$selected = "selected='selected'";
				}
				if (strlen($row['device_key_vendor']) == 0) {
					echo "		<option value='".escape($function['value'])."' vendor='".escape($function['vendor_name'])."' $selected >".$text['label-'.escape($function['name'])]."</option>\n";
				}
				if (strlen($row['device_key_vendor']) > 0 && $row['device_key_vendor'] == $function['vendor_name']) {
					echo "		<option value='".escape($function['value'])."' vendor='".escape($function['vendor_name'])."' $selected >".$text['label-'.escape($function['name'])]."</option>\n";
					
				}
				$previous_vendor = $function['vendor_name'];
				$i++;
				
			}
			if (strlen($row['device_key_vendor']) == 0) {
				echo "	</optgroup>\n";
			}
			echo "</select>\n";

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
			echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_value]' style='width: 120px;' maxlength='255' value=\"".escape($row['device_key_value'])."\">\n";
			echo "</td>\n";

			if (permission_exists('device_key_extension')) {
				echo "<td class='' align='left'>\n";
				echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_extension]' style='width: 120px;' maxlength='255' value=\"".escape($row['device_key_extension'])."\">\n";
				echo "</td>\n";
			}

			if (permission_exists('device_key_protected')) {
				echo "			<td align='left'>\n";
				echo "				<select class='formfld' name='device_keys[".$x."][device_key_protected]'>\n";
				echo "					<option value=''>\n";
				echo "					<option value='true' ".(($row['device_key_protected'] == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
				echo "					<option value='false' ".(($row['device_key_protected'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
				echo "				</select>\n";
				echo "			</td>\n";
			}

			echo "<td class='' align='left'>\n";
			echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_label]' style='width: 150px;' maxlength='255' value=\"".escape($row['device_key_label'])."\">\n";
			echo "</td>\n";
			
			echo "<td class='' align='left'>\n";
			echo "	<input class='formfld' type='text' name='device_keys[".$x."][device_key_icon]' style='width: 150px;' maxlength='255' value=\"".escape($row['device_key_icon'])."\">\n";
			echo "</td>\n";

			echo "<td nowrap='nowrap'>\n";
			if (is_uuid($row['device_key_uuid'])) {
				if (permission_exists('device_key_delete')) {
					echo "					<a href='device_key_delete.php?device_profile_uuid=".escape($row['device_profile_uuid'])."&id=".escape($row['device_key_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
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

//device settings
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
				echo "					<a href='device_setting_delete.php?device_profile_uuid=".escape($row['device_profile_uuid'])."&id=".escape($row['device_setting_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
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

	if (permission_exists('device_profile_domain')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-profile_domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='domain_uuid'>\n";
		if ($action == "update") {
			echo "	<option value='' ".(!is_uuid($device_profile_domain_uuid) ? "selected='selected'" : null).">".$text['select-global']."</option>\n";
			foreach ($_SESSION['domains'] as $dom) {
				echo "<option value='".escape($dom['domain_uuid'])."' ".(($device_profile_domain_uuid == $dom['domain_uuid']) ? "selected='selected'" : null).">".escape($dom['domain_name'])."</option>\n";
			}
		}
		else {
			echo "	<option value=''>".$text['select-global']."</option>\n";
			foreach ($_SESSION['domains'] as $dom) {
				echo "<option value='".escape($dom['domain_uuid'])."' ".(($domain_uuid == $dom['domain_uuid']) ? "selected='selected'" : null).">".escape($dom['domain_name'])."</option>\n";
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
	echo "	<input class='formfld' type='text' name='device_profile_description' maxlength='255' value=\"".escape($device_profile_description)."\">\n";
	echo "<br />\n";
	echo $text['description-profile_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='device_profile_uuid' value='".escape($device_profile_uuid)."'>\n";
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
