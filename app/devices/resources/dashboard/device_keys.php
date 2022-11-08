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
	Portions created by the Initial Developer are Copyright (C) 2016-2022
	the Initial Developer. All Rights Reserved.

*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/devices');

//connect to the database
	if (!isset($database)) {
		$database = new database;
	}

//get the vendor functions
	$sql = "select v.name as vendor_name, f.name, f.value ";
	$sql .= "from v_device_vendors as v, v_device_vendor_functions as f ";
	$sql .= "where v.device_vendor_uuid = f.device_vendor_uuid ";
	$sql .= "and f.device_vendor_function_uuid in ";
	$sql .= "(";
	$sql .= "	select device_vendor_function_uuid from v_device_vendor_function_groups ";
	$sql .= "	where device_vendor_function_uuid = f.device_vendor_function_uuid ";
	$sql .= "	and ( ";
	if (is_array($_SESSION['groups'])) {
		foreach($_SESSION['groups'] as $index => $row) {
			$sql_where_or[] = "group_name = :group_name_".$index;
			$parameters['group_name_'.$index] = $row['group_name'];
		}
		if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
			$sql .= implode(' or ', $sql_where_or);
		}
	}
	$sql .= "	) ";
	$sql .= ") ";
	$sql .= "and v.enabled = 'true' ";
	$sql .= "and f.enabled = 'true' ";
	$sql .= "order by v.name asc, f.name asc ";
	$vendor_functions = $database->select($sql, (is_array($parameters) ? $parameters : null), 'all');
	unset($sql, $sql_where_or, $parameters);

//add or update the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//add or update the database
			if ($_POST["persistformvar"] != "true") {

				//validate the token
					$token = new token;
					if (!$token->validate('/app/devices/resources/dashboard/device_keys.php') && !$token->validate('login')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.PROJECT_PATH."/core/dashboard/");
						exit;
					}

				//get device
					$sql = "select device_uuid, device_profile_uuid from v_devices ";
					$sql .= "where device_user_uuid = :device_user_uuid ";
					$parameters['device_user_uuid'] = $_SESSION['user_uuid'];
					$row = $database->select($sql, $parameters, 'row');
					if (is_array($row) && @sizeof($row) != 0) {
						$device_uuid = $row['device_uuid'];
						$device_profile_uuid = $row['device_profile_uuid'];
					}
					unset($sql, $parameters, $row);

				//get device profile keys
					if (is_uuid($device_profile_uuid)) {
						$sql = "select * from v_device_keys ";
						$sql .= "where device_profile_uuid = :device_profile_uuid ";
						$parameters['device_profile_uuid'] = $device_profile_uuid;
						$device_profile_keys = $database->select($sql, $parameters, 'all');
						unset($sql, $parameters);
					}

				//get device keys
					if (is_uuid($device_uuid)) {
						$sql = "select * from v_device_keys ";
						$sql .= "where device_uuid = :device_uuid ";
						$parameters['device_uuid'] = $device_uuid;
						$device_keys = $database->select($sql, $parameters, 'all');
						unset($sql, $parameters);
					}

				//create a list of protected keys - device keys
					if (is_array($device_keys) && @sizeof($device_keys) != 0) {
						foreach($device_keys as $row) {
							//determine if the key is allowed
								$device_key_authorized = false;
								foreach($vendor_functions as $function) {
									if ($function['vendor_name'] == $row['device_key_vendor'] && $function['value'] == $row['device_key_type']) {
										$device_key_authorized = true;
									}
								}
							//add the protected keys
								if (!$device_key_authorized) {
									$protected_keys[$row['device_key_id']] = 'true';
								}
							//add to protected
								if ($row['device_key_protected'] == "true") {
									$protected_keys[$row['device_key_id']] = 'true';
								}
						}
					}

				//create a list of protected keys - device proile keys
					if (is_array($device_profile_keys)) {
						foreach($device_profile_keys as $row) {
							//determine if the key is allowed
								$device_key_authorized = false;
								if (is_array($vendor_functions)) {
									foreach($vendor_functions as $function) {
										if ($function['vendor_name'] == $row['device_key_vendor'] && $function['value'] == $row['device_key_type']) {
											$device_key_authorized = true;
										}
									}
								}
							//add the protected keys
								if (!$device_key_authorized) {
									$protected_keys[$row['device_key_id']] = 'true';
								}
						}
					}

				//remove the keys the user is not allowed to edit based on the authorized vendor keys
					$x=0;
					if (is_array($_POST['device_keys'])) {
						foreach($_POST['device_keys'] as $row) {
							//loop through the authorized vendor functions
								if ($protected_keys[$row['device_key_id']] == "true") {
									unset($_POST['device_keys'][$x]);
								}
							//increment the row id
								$x++;
						}
					}

				//add or update the device keys
					if (is_array($_POST['device_keys'])) {
						foreach ($_POST['device_keys'] as &$row) {

							//validate the data
								$save = true;
								//if (!is_uuid($row["device_key_uuid"])) { $save = false; }
								if (isset($row["device_key_id"])) {
									if (!is_numeric($row["device_key_id"])) { $save = false; echo $row["device_key_id"]." id "; }
								}
								if (strlen($row["device_key_type"]) > 25) { $save = false; echo "type "; }
								if (strlen($row["device_key_value"]) > 25) { $save = false; echo "value "; }
								if (strlen($row["device_key_label"]) > 25) { $save = false; echo "label "; }
								if (strlen($row["device_key_icon"]) > 25) { $save = false; echo "icon "; }

							//escape characters in the string
								$device_uuid = $row["device_uuid"];
								$device_key_uuid = $row["device_key_uuid"];
								$device_key_id = $row["device_key_id"];
								$device_key_type = $row["device_key_type"];
								$device_key_line = $row["device_key_line"];
								$device_key_value = $row["device_key_value"];
								$device_key_label = $row["device_key_label"];
								$device_key_icon = $row["device_key_icon"];
								$device_key_category = $row["device_key_category"];
								$device_key_vendor = $row["device_key_vendor"];

							//process the profile keys
								if (strlen($row["device_profile_uuid"]) > 0) {
									//get the profile key settings from the array
										foreach ($device_profile_keys as &$field) {
											if ($device_key_uuid == $field["device_key_uuid"]) {
												$database = $field;
												break;
											}
										}
									//determine what to do with the profile key
										if ($device_key_id == $database["device_key_id"]
											&& $device_key_value == $database["device_key_value"]
											&& $device_key_label == $database["device_key_label"]
											&& $device_key_icon == $database["device_key_icon"]) {
												//profile key unchanged don't save
												$save = false;
										}
										else {
											//profile key has changed remove save the settings to the device
											$device_key_uuid = '';
										}
								}

							//sql add or update
								if (!is_uuid($device_key_uuid)) {
									if (permission_exists('device_key_add') && strlen($device_key_type) > 0 && strlen($device_key_value) > 0) {

										//if the device_uuid is not in the array then get the device_uuid from the database
											if (strlen($device_uuid) == 0) {
												$sql = "select device_uuid from v_devices ";
												$sql .= "where device_user_uuid = :device_user_uuid ";
												$parameters['device_user_uuid'] = $_SESSION['user_uuid'];
												$device_uuid = $database->select($sql, $parameters, 'column');
												unset($sql, $parameters);
											}

										//insert the keys
											$device_key_uuid = uuid();
											$array['device_keys'][0]['device_key_uuid'] = $device_key_uuid;
											$array['device_keys'][0]['device_uuid'] = $device_uuid;
											$array['device_keys'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
											$array['device_keys'][0]['device_key_id'] = $device_key_id;
											$array['device_keys'][0]['device_key_type'] = $device_key_type;
											$array['device_keys'][0]['device_key_line'] = $device_key_line;
											$array['device_keys'][0]['device_key_value'] = $device_key_value;
											$array['device_keys'][0]['device_key_label'] = $device_key_label;
											if (permission_exists('device_key_icon')) {
												$array['device_keys'][0]['device_key_icon'] = $device_key_icon;
											}
											$array['device_keys'][0]['device_key_category'] = $device_key_category;
											$array['device_keys'][0]['device_key_vendor'] = $device_key_vendor;

										//action add or update
											$action = "add";
									}
								}
								else {
									//action add or update
										$action = "update";

									//update the device keys
										$array['device_keys'][0]['device_key_uuid'] = $device_key_uuid;
										$array['device_keys'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
										if (permission_exists('device_key_id')) {
											$array['device_keys'][0]['device_key_id'] = $device_key_id;
										}
										$array['device_keys'][0]['device_key_type'] = $device_key_type;
										$array['device_keys'][0]['device_key_value'] = $device_key_value;
										$array['device_keys'][0]['device_key_label'] = $device_key_label;
										if (permission_exists('device_key_icon')) {
											$array['device_keys'][0]['device_key_icon'] = $device_key_icon;
										}
								}
								if ($save) {
									//add the temporary permissions
										$p = new permissions;
										$p->add('device_keys_add', 'temp');
										$p->add('device_key_edit', 'temp');

									//save the changes
										$database->app_name = 'devices';
										$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
										$database->save($array);

									//remove the temporary permissions
										$p->delete('device_keys_add', 'temp');
										$p->delete('device_key_edit', 'temp');
								}
								unset($array);
						}
					}

				//write the provision files
					if (strlen($_SESSION['provision']['path']['text']) > 0) {
						$prov = new provision;
						$prov->domain_uuid = $domain_uuid;
						$response = $prov->write();
					}

				//set the message
					message::add($text["message-".$action]);

				//redirect the browser
					header("Location: /core/dashboard/");
					exit;

			}
	}

//set the sub array index
	$x = "999";

//get the device
	$sql = "select device_uuid, device_profile_uuid from v_devices ";
	$sql .= "where device_user_uuid = :device_user_uuid ";
	$parameters['device_user_uuid'] = $_SESSION['user_uuid'];
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0) {
		$device_uuid = $row['device_uuid'];
		$device_profile_uuid = $row['device_profile_uuid'];
	}
	unset($sql, $parameters, $row);

//get device lines
	if (is_uuid($device_uuid)) {
		$sql = "select * from v_device_lines ";
		$sql .= "where device_uuid = :device_uuid ";
		$parameters['device_uuid'] = $device_uuid;
		$device_lines = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the user
	if (is_array($device_lines)) {
		foreach ($device_lines as $row) {
			if ($_SESSION['domain_name'] == $row['server_address']) {
				$user_id = $row['user_id'];
				$server_address = $row['server_address'];
				break;
			}
		}
	}

//set the sip profile name
	$sip_profile_name = 'internal';

//get the device keys in the right order where device keys are listed after the profile keys
	if (is_uuid($device_uuid)) {
		$sql = "select * from v_device_keys ";
		$sql .= "where (";
		$sql .= "device_uuid = :device_uuid ";
		$sql .= is_uuid($device_profile_uuid) ? "or device_profile_uuid = :device_profile_uuid " : null;
		$sql .= ") ";
		$sql .= "order by ";
		$sql .= "device_key_vendor asc, ";
		$sql .= "case device_key_category ";
		$sql .= "when 'line' then 1 ";
		$sql .= "when 'memory' then 2 ";
		$sql .= "when 'programmable' then 3 ";
		$sql .= "when 'expansion' then 4 ";
		$sql .= "else 100 end, ";
		$sql .= $db_type == "mysql" ? "device_key_id asc " : "cast(device_key_id as numeric) asc, ";
		$sql .= "case when device_uuid is null then 0 else 1 end asc ";
		$parameters['device_uuid'] = $device_uuid;
		if (is_uuid($device_profile_uuid)) {
			$parameters['device_profile_uuid'] = $device_profile_uuid;
		}
		$keys = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//override profile keys with device keys
	if (is_array($keys) && @sizeof($keys) != 0) {
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
	}

//get the vendor count and last and device information
	if (is_array($device_keys) && @sizeof($device_keys) != 0) {
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
		$device_keys[$x]['device_key_icon'] = '';
	}

//remove the keys the user is not allowed to edit based on the authorized vendor keys
	if (is_array($device_keys) && @sizeof($device_keys) != 0) {
		foreach($device_keys as $row) {
			//loop through the authorized vendor functions
				$device_key_authorized = false;
				if (is_array($vendor_functions)) {
					foreach($vendor_functions as $function) {
						if (strlen($row['device_key_type'] == 0)) {
							$device_key_authorized = true;
						}
						else {
							if ($function['vendor_name'] == $row['device_key_vendor'] && $function['value'] == $row['device_key_type']) {
								$device_key_authorized = true;
							}
						}
					}
				}
			//unset vendor functions the is not allowed to edit
				if (!$device_key_authorized) {
					unset($device_keys[$row['device_key_id']]);
				}
			//hide protected keys
				if ($row['device_key_protected'] == "true") {
					unset($device_keys[$row['device_key_id']]);
				}
		}
	}

//create token
	$object = new token;
	$token = $object->create('/app/devices/resources/dashboard/device_keys.php');

//show the content
	echo "<div class='action_bar sub'>\n";
	echo "	<div class='heading'><b>".$text['title-device_keys']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-apply'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>false,'onclick'=>"document.location.href='".PROJECT_PATH."/app/devices/cmd.php?cmd=check_sync&profile=".$sip_profile_name."&user=".$user_id."@".$server_address."&domain=".$server_address."&agent=".$device_key_vendor."';"]);
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>false,'onclick'=>"list_form_submit('form_list_device_keys');"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (!$is_included) {
		echo $text['description-device_keys']."\n";
		echo "<br /><br />\n";
	}

	echo "<form method='post' name='frm' id='form_list_device_keys' action=''>\n";
	echo "<table class='list'>\n";
	$x = 0;
	if (is_array($device_keys) && @sizeof($device_keys) != 0) {
		foreach ($device_keys as $row) {

			//set the variables
				$device_key_vendor = $row['device_key_vendor'];
				$device_vendor = $row['device_key_vendor'];

			//set the column names
				if ($previous_device_key_vendor != $row['device_key_vendor'] || $row['device_key_vendor'] == '') {
					echo "	<tr class='list-header'>\n";
					echo "		<th class='shrink'>".$text['label-device_key_id']."</th>\n";
					if (strlen($row['device_key_vendor']) > 0) {
						echo "		<th>".ucwords($row['device_key_vendor'])."</th>\n";
					}
					else {
						echo "		<th>".$text['label-device_key_type']."</th>\n";
					}
					echo "		<th>".$text['label-device_key_value']."</th>\n";
					echo "		<th>".$text['label-device_key_label']."</th>\n";
					if (permission_exists('device_key_icon')) {
						echo "		<th class='shrink'>".$text['label-device_key_icon']."</th>\n";
					}
					echo "	</tr>\n";
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

			//output form rows
				echo "	<tr class='list-row'>\n";
				echo "		<td class='input'>\n";
				if (permission_exists('device_key_id') || permission_exists('device_key_add')) {
					$selected = "selected='selected'";
					echo "			<select class='formfld' name='device_keys[".$x."][device_key_id]'>\n";
					echo "				<option value=''></option>\n";
					$i = 1;
					while ($i < 100) {
						echo "				<option value='$i' ".($row['device_key_id'] == $i ? $selected:"").">$i</option>\n";
						$i++;
					}
					echo "			</select>\n";
				}
				else {
					echo "&nbsp;&nbsp;".$row['device_key_id'];
				}
				echo "		</td>\n";

				echo "		<td class='input'>\n";
				echo "			<input class='formfld' type='hidden' id='key_vendor_".$x."' name='device_keys[".$x."][device_key_vendor]' value=\"".$device_key_vendor."\">\n";
				echo "			<input class='formfld' type='hidden' id='key_category_".$x."' name='device_keys[".$x."][device_key_category]' value=\"".$device_key_category."\">\n";
				echo "			<input class='formfld' type='hidden' id='key_uuid_".$x."' name='device_keys[".$x."][device_uuid]' value=\"".$device_uuid."\">\n";
				echo "			<input class='formfld' type='hidden' id='key_key_line_".$x."' name='device_keys[".$x."][device_key_line]' value=\"".$device_key_line."\">\n";
				echo "			<select class='formfld' name='device_keys[".$x."][device_key_type]' id='key_type_".$x."'>\n";
				echo "				<option value=''></option>\n";
				$previous_vendor = '';
				$i = 0;
				if (is_array($vendor_functions)) {
					foreach ($vendor_functions as $function) {
						if (strlen($row['device_key_vendor']) == 0 && $function['vendor_name'] != $previous_vendor) {
							if ($i > 0) {
								echo "				</optgroup>\n";
							}
							echo "				<optgroup label='".ucwords($function['vendor_name'])."'>\n";
						}
						$selected = '';
						if ($row['device_key_vendor'] == $function['vendor_name'] && $row['device_key_type'] == $function['value']) {
							$selected = "selected='selected'";
						}
						if (strlen($row['device_key_vendor']) == 0) {
							echo "					<option value='".$function['value']."' $selected >".$text['label-'.$function['name']]."</option>\n";
						}
						if (strlen($row['device_key_vendor']) > 0 && $row['device_key_vendor'] == $function['vendor_name']) {
							echo "					<option value='".$function['value']."' $selected >".$text['label-'.$function['name']]."</option>\n";
						}
						$previous_vendor = $function['vendor_name'];
						$i++;
					}
				}
				if (strlen($row['device_key_vendor']) == 0) {
					echo "				</optgroup>\n";
				}
				echo "			</select>\n";
				echo "		</td>\n";
				echo "		<td class='input'>\n";
				echo "			<input class='formfld' style='min-width: 50px; max-width: 100px;' type='text' name='device_keys[".$x."][device_key_value]' maxlength='255' value=\"".$row['device_key_value']."\">\n";
				echo "		</td>\n";
				echo "		<td class='input'>\n";
				echo "			<input class='formfld' style='min-width: 50px; max-width: 100px;' type='text' name='device_keys[".$x."][device_key_label]' maxlength='255' value=\"".$row['device_key_label']."\">\n";
				echo "			<input type='hidden' name='device_keys[".$x."][device_profile_uuid]' value=\"".$row['device_profile_uuid']."\">\n";
				echo "		</td>\n";
				if (permission_exists('device_key_icon')) {
					echo "		<td class='input'>\n";
					echo "			<input class='formfld' style='min-width: 50px; max-width: 100px;' type='text' name='device_keys[".$x."][device_key_icon]' maxlength='255' value=\"".$row['device_key_icon']."\">\n";
					echo "		</td>\n";
				}
				echo "	</tr>\n";

			//set the previous vendor
				$previous_device_key_vendor = $row['device_key_vendor'];

			//increment the array key
				$x++;
		}
	}
	echo "</table>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>";

?>
