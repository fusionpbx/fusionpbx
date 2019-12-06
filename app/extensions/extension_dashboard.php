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
	Portions created by the Initial Developer are Copyright (C) 2017 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";


//check permissions
	if (permission_exists('extension_caller_id')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/extensions');

//add or update the database
	if (is_array($_POST['extensions']) && @sizeof($_POST['extensions']) != 0) {

		//validate the token
			$token = new token;
			if (!$token->validate('/app/extensions/extension_dashboard.php')) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: /core/user_settings/user_dashboard.php');
				exit;
			}

		//build a new array to make sure it only contains what the user is allowed to change
			$x=0;
			foreach ($_POST['extensions'] as $row) {
				//loop through the extensions
					$found = false;
					foreach ($_SESSION['user']['extension'] as $field) {
						if ($field['extension_uuid'] == $row['extension_uuid']) {
							//set as found
								$found = true;
						}
					}

				//build the array on what is allowed.
					if ($found) {
						if (permission_exists('outbound_caller_id_select')) {
							$caller_id = explode('@', $row['outbound_caller_id']);
							$outbound_caller_id_name = $caller_id[0];
							$outbound_caller_id_number = $caller_id[1];
						}
						else {
							$outbound_caller_id_name = $row['outbound_caller_id_name'];
							$outbound_caller_id_number = $row['outbound_caller_id_number'];
						}
						$array['extensions'][$x]['extension_uuid'] = $row['extension_uuid'];
						$array['extensions'][$x]['outbound_caller_id_name'] = $outbound_caller_id_name;
						if (is_numeric($outbound_caller_id_number)) {
							$array['extensions'][$x]['outbound_caller_id_number'] = $outbound_caller_id_number;
						}
					}

				//increment the row id
					$x++;
			}

		//add the dialplan permission
			$p = new permissions;
			$p->add("extension_edit", "temp");

		//save to the data
			$database = new database;
			$database->app_name = 'extensions';
			$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
			$database->save($array);
			$message = $database->message;

		//update the session array
			foreach ($array['extensions'] as $row) {
					$x=0;
					foreach ($_SESSION['user']['extension'] as $field) {
						if ($field['extension_uuid'] == $row['extension_uuid']) {
							$_SESSION['user']['extension'][$x]['outbound_caller_id_name'] = $row['outbound_caller_id_name'];
							$_SESSION['user']['extension'][$x]['outbound_caller_id_number'] = $row['outbound_caller_id_number'];
						}
						$x++;
					}
			}

		//remove the temporary permission
			$p->delete("extension_edit", "temp");

		//clear the cache
			$cache = new cache;
			foreach($_SESSION['user']['extension'] as $field) {
				$cache->delete("directory:".$field['destination']."@".$field['user_context']);
			}

		//set the message
			message::add($text['message-update']);

		//redirect the browser
			header("Location: /core/user_settings/user_dashboard.php");
			exit;
	}

//set the sub array index
	$x = "999";

//get the extensions
	$extensions = $_SESSION['user']['extension'];

//get the destinations
	$sql = "select destination_caller_id_name, destination_caller_id_number from v_destinations ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and destination_type = 'inbound' ";
	$sql .= "order by destination_caller_id_name asc, destination_caller_id_number asc";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$destinations = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/app/extensions/extension_dashboard.php');

//show the content
	echo "<div class='action_bar sub'>\n";
	echo "	<div class='heading'><b>".$text['label-caller_id_number']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>false,'onclick'=>"list_form_submit('form_list_extension_caller_id');"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<form id='form_list_extension_caller_id' method='post' action='".PROJECT_PATH."/app/extensions/extension_dashboard.php'>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>".$text['label-extension']."</th>\n";
	echo "	<th>".$text['label-caller_id']."</th>\n";
	if (!permission_exists('outbound_caller_id_select')) {
		echo "	<th>".$text['label-destination']."</th>\n";
	}
	echo "	<th class='hide-sm-dn pct-30'>".$text['label-description']."</th>\n";
	echo "</tr>\n";

	if (is_array($extensions) && @sizeof($extensions) != 0) {
		$x = 0;
		foreach ($extensions as $row) {
			//determine whether to hide the element
				if (is_uuid($device_key_uuid)) {
					$element['hidden'] = false;
					$element['visibility'] = "visibility:visible;";
				}
				else {
					$element['hidden'] = true;
					$element['visibility'] = "visibility:hidden;";
				}

			echo "<tr class='list-row'>\n";
			echo "	<td>\n";
			echo "		".$row['destination'];
			if (is_uuid($row['extension_uuid'])) {
				echo "		<input name='extensions[".$x."][extension_uuid]' type='hidden' value=\"".escape($row['extension_uuid'])."\">\n";
			}
			echo "	</td>\n";
			if (permission_exists('outbound_caller_id_select')) {
				//caller id select
				echo "	<td class='input'>\n";
				if (count($destinations) > 0) {
					echo "		<select name='extensions[".$x."][outbound_caller_id]' id='outbound_caller_id_number' class='formfld'>\n";
					echo "			<option value=''></option>\n";
					foreach ($destinations as &$field) {
						if(strlen($field['destination_caller_id_number']) > 0) {
							if ($row['outbound_caller_id_number'] == $field['destination_caller_id_number']) {
								echo "			<option value='".escape($field['destination_caller_id_name'])."@".escape($field['destination_caller_id_number'])."' selected='selected'>".escape($field['destination_caller_id_name'])." ".escape($field['destination_caller_id_number'])."</option>\n";
							}
							else {
								echo "			<option value='".escape($field['destination_caller_id_name'])."@".escape($field['destination_caller_id_number'])."'>".escape($field['destination_caller_id_name'])." ".escape($field['destination_caller_id_number'])."</option>\n";
							}
						}
					}
					echo "		</select>\n";
				}
				echo "	</td>\n";
			}
			else {
				//caller id name and number inputs
				echo "	<td class='input'>\n";
				echo "		<input class='formfld' style='min-width: 50px; max-width: 100px;' type='text' name='extensions[".$x."][outbound_caller_id_name]' maxlength='255' value=\"".escape($row['outbound_caller_id_name'])."\">\n";
				echo "	</td>\n";
				echo "	<td class='input'>\n";
				echo "		<input class='formfld' style='min-width: 50px; max-width: 100px;' type='text' name='extensions[".$x."][outbound_caller_id_number]' maxlength='255' value=\"".$row['outbound_caller_id_number']."\">\n";
				echo "	</td>\n";
			}
			echo "	<td class='description overflow hide-sm-dn'>\n";
			echo "		".$row['description'];
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
		}
	}
	unset($extensions);

	echo "</table>\n";
	echo "<br />\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

?>