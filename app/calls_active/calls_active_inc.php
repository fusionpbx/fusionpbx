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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_active_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the HTTP values and set as variables
	$show = trim($_REQUEST["show"]);
	if ($show != "all") { $show = ''; }

//include theme config for button images
	include_once("themes/".$_SESSION['domain']['template']['name']."/config.php");

//set the command
	$switch_cmd = 'show channels as json';

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//send the event socket command and get the array
	if ($fp) {
		$json = trim(event_socket_request($fp, 'api '.$switch_cmd));
		$results = json_decode($json, "true");
	}

//build a new array with domain_name
	$rows = array();
	if (isset($results["rows"])) {
		foreach ($results["rows"] as &$row) {
			//get the domain
				if (strlen($row['context']) > 0 && $row['context'] != "public" && $row['context'] != "default") {
					if (substr_count($row['context'], '@') > 0) {
						$context_array = explode('@', $row['context']);
						$row['domain_name'] = $context_array[1];
					}
					else {
						$row['domain_name'] = $row['context'];
					}
				}
				else if (substr_count($row['presence_id'], '@') > 0) {
					$presence_id_array = explode('@', $row['presence_id']);
					$row['domain_name'] = $presence_id_array[1];
				}
			//add the row to the array
				if (($show == 'all' && permission_exists('call_active_all'))) {
					$rows[] = $row;
				}
				else {
					if ($row['domain_name'] == $_SESSION['domain_name']) {
						$rows[] = $row;
					}
				}
		}
		unset($results);
	}
	$num_rows = @sizeof($rows);


//if the connnection is available then run it and return the results
	if (!$fp) {

		$msg = "<div align='center'>".$text['confirm-socket']."<br /></div>";
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>".$text['label-message']."</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";

	}
	else {

		//create token
			$object = new token;
			$token = $object->create('/app/calls_active/calls_active_inc.php');

		//show content
			echo "<div class='action_bar' id='action_bar'>\n";
			echo "	<div class='heading'><b>".$text['title']." (".$num_rows.")</b></div>\n";
			echo "	<div class='actions'>\n";
			echo "		<span id='refresh_state'>".button::create(['type'=>'button','title'=>$text['label-refresh_pause'],'icon'=>'sync-alt fa-spin','onclick'=>'refresh_stop()'])."</span>";
			if (permission_exists('call_active_hangup') && $rows) {
				echo button::create(['type'=>'button','label'=>$text['label-hangup'],'icon'=>'phone-slash','id'=>'btn_delete','onclick'=>"refresh_stop(); modal_open('modal-hangup','btn_hangup');"]);
			}
			if (permission_exists('call_active_all')) {
				if ($show == "all") {
					echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'link'=>'calls_active.php','onmouseover'=>'refresh_stop()','onmouseout'=>'refresh_start()']);
				}
				else {
					echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'calls_active.php?show=all','onmouseover'=>'refresh_stop()','onmouseout'=>'refresh_start()']);
				}
			}
			echo "	</div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			if (permission_exists('call_active_hangup') && $rows) {
				echo modal::create(['id'=>'modal-hangup','type'=>'general','message'=>$text['confirm-hangups'],'actions'=>button::create(['type'=>'button','label'=>$text['label-hangup'],'icon'=>'check','id'=>'btn_hangup','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('hangup'); list_form_submit('form_list');"])]);
			}

			echo $text['description']."\n";
			echo "<br /><br />\n";

		//show the results
			echo "<div id='cmd_reponse'></div>\n";

			echo "<form id='form_list' method='post' action='calls_exec.php'>\n";
			echo "<input type='hidden' id='action' name='action' value=''>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('call_active_hangup')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='if (this.checked) { refresh_stop(); } else { refresh_start(); } list_all_toggle();' ".($rows ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "	<th>".$text['label-profile']."</th>\n";
			echo "	<th>".$text['label-created']."</th>\n";
			if ($show == 'all') {
				echo "	<th>".$text['label-domain']."</th>\n";
			}
			echo "	<th>".$text['label-number']."</th>\n";
			echo "	<th>".$text['label-cid-name']."</th>\n";
			echo "	<th>".$text['label-cid-number']."</th>\n";
			echo "	<th>".$text['label-destination']."</th>\n";
			echo "	<th>".$text['label-app']."</th>\n";
			echo "	<th>".$text['label-codec']."</th>\n";
			echo "	<th>".$text['label-secure']."</th>\n";
			if (permission_exists('call_active_hangup')) {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($rows)) {
				$x = 0;
				foreach ($rows as &$row) {

					//set the php variables
						foreach ($row as $key => $value) {
							$$key = $value;
						}

					//get the sip profile
						$name_array = explode("/", $name);
						$sip_profile = $name_array[1];
						$sip_uri = $name_array[2];

					//get the number
						$temp_array = explode("@", $sip_uri);
						$tmp_number = $temp_array[0];
						$tmp_number = str_replace("sip:", "", $tmp_number);

					//remove the '+' because it breaks the call recording
						$cid_num = str_replace("+", "", $cid_num);

					//replace gateway uuid with name
						if (is_array($_SESSION['gateways']) && sizeof($_SESSION['gateways']) > 0) {
							foreach ($_SESSION['gateways'] as $gateway_uuid => $gateway_name) {
								$application_data = str_replace($gateway_uuid, $gateway_name, $application_data);
							}
						}

					// reduce too long app data
						if(strlen($application_data) > 512) {
							$application_data = substr($application_data, 0, 512) . '...';
						}

					//send the html
						echo "<tr class='list-row'>\n";
						if (permission_exists('call_active_hangup')) {
							echo "	<td class='checkbox'>\n";
							echo "		<input type='checkbox' name='calls[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (this.checked) { refresh_stop(); } else { document.getElementById('checkbox_all').checked = false; }\">\n";
							echo "		<input type='hidden' name='calls[$x][uuid]' value='".escape($uuid)."' />\n";
							echo "	</td>\n";
						}
						echo "	<td>".escape($sip_profile)."&nbsp;</td>\n";
						echo "	<td>".escape($created)."&nbsp;</td>\n";
						if ($show == 'all') {
							echo "	<td>".escape($domain_name)."&nbsp;</td>\n";
						}
						echo "	<td>".escape($tmp_number)."&nbsp;</td>\n";
						echo "	<td>".escape($cid_name)."&nbsp;</td>\n";
						echo "	<td>".escape($cid_num)."&nbsp;</td>\n";
						echo "	<td>".escape($dest)."&nbsp;</td>\n";
						echo "	<td>".(strlen($application) > 0 ? escape($application).":".escape($application_data) : null)."&nbsp;</td>\n";
						echo "	<td>".escape($read_codec).":".escape($read_rate)." / ".escape($write_codec).":".escape($write_rate)."&nbsp;</td>\n";
						echo "	<td>".escape($secure)."&nbsp;</td>\n";
						if (permission_exists('call_active_hangup')) {
							echo "	<td class='action-button'>";
							echo button::create(['type'=>'button','title'=>$text['label-hangup'],'icon'=>'phone-slash','onclick'=>"if (confirm('".$text['confirm-hangup']."')) { list_self_check('checkbox_".$x."'); list_action_set('hangup'); list_form_submit('form_list'); } else { this.blur(); return false; }",'onmouseover'=>'refresh_stop()','onmouseout'=>'refresh_start()']);
							echo "	</td>\n";
						}
						echo "</tr>\n";

					//increment counter
						$x++;
				}
				unset($rows);
			}

			echo "</table>\n";

			echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

			echo "</form>\n";

	}

?>
