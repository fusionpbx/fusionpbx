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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_active_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the session settings
	$domain_uuid = $_SESSION['domain_uuid'];
	$domain_name = $_SESSION['domain_name'];
	$user_uuid = $_SESSION['user_uuid'];
	$gateways = $_SESSION['gateways'];
	$user = $_SESSION['user'];

//initialize the settings object
	$settings = new settings(["domain_uuid" => $domain_uuid, "user_uuid" => $user_uuid]);

//get the settings
	$template_name = $settings->get('domain', 'template', 'default');
	$theme_button_icon_back = $settings->get('theme', 'button_icon_back', '');
	$theme_button_icon_all = $settings->get('theme', 'button_icon_all', '');

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the HTTP values and set as variables
	$show = trim($_REQUEST["show"] ?? '');
	if ($show != "all") { $show = ''; }

//include theme config for button images
	include_once("themes/".$template_name."/config.php");

//set the command
	$switch_cmd = 'show channels as json';

//create the event socket connection
	$event_socket = event_socket::create();

//send the event socket command and get the array
	if ($event_socket->is_connected()) {
		$json = trim(event_socket::api($switch_cmd));
		$results = json_decode($json, "true");
	}

//build a new array with domain_name
	$rows = array();
	if (isset($results["rows"])) {
		foreach ($results["rows"] as $row) {
			//get the domain
				if (!empty($row['context']) && $row['context'] != "public" && $row['context'] != "default") {
					if (substr_count($row['context'], '@') > 0) {
						$row['domain_name'] = explode('@', $row['context'])[1];
					}
					else {
						$row['domain_name'] = $row['context'];
					}
				}
				else if (substr_count($row['presence_id'], '@') > 0) {
					$row['domain_name'] = explode('@', $row['presence_id'])[1];
				}
			//add the row to the array
				if (($show == 'all' && permission_exists('call_active_all'))) {
					$rows[] = $row;
				}
				elseif ($row['domain_name'] == $domain_name) {
					$rows[] = $row;
				}
		}
		unset($results);
	}
	$num_rows = @sizeof($rows);

//if the connnection is available then run it and return the results
	if (!$event_socket) {
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
		return;
	}

//add the style
	echo "<style>\n";
	echo "	/* Small screens: Hide columns with class 'hide-small' */\n";
	echo "	@media (max-width: 600px) {\n";
	echo "		.hide-small {\n";
	echo "			display: none;\n";
	echo "		}\n";
	echo "	}\n";
	echo "\n";
	echo "	/* Medium screens: Hide columns with class 'hide-medium' */\n";
	echo	"@media (max-width: 1023px) and (min-width: 601px) {\n";
	echo "		.hide-medium {\n";
	echo "			display: none;\n";
	echo "		}\n";
	echo "	}\n";
	echo "\n";
	echo "</style>\n";

//show the results
	echo "<div id='cmd_reponse'></div>\n";

	echo "<form id='form_list' method='post' action='calls_exec.php'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

	echo "<div class='card'>\n";
	echo "	<table id='calls_active' class='list'>\n";
	echo "	<tr class='list-header'>\n";
	if (permission_exists('call_active_hangup')) {
		echo "		<th class='checkbox'>\n";
		echo "			<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='if (this.checked) { refresh_stop(); } else { refresh_start(); } list_all_toggle();' ".(empty($rows) ? "style='visibility: hidden;'" : null).">\n";
		echo "		</th>\n";
	}
	if (permission_exists('call_active_profile')) {
		echo "		<th class='hide-small'>".$text['label-profile']."</th>\n";
	}
	echo "		<th>".$text['label-duration']."</th>\n";
	if ($show == 'all') {
		echo "		<th>".$text['label-domain']."</th>\n";
	}
	echo "		<th class='hide-small'>".$text['label-cid-name']."</th>\n";
	echo "		<th>".$text['label-cid-number']."</th>\n";
	echo "		<th>".$text['label-destination']."</th>\n";
	if (permission_exists('call_active_application')) {
		echo "		<th class='hide-small hide-medium'>".$text['label-app']."</th>\n";
	}
	if (permission_exists('call_active_codec')) {
		echo "		<th class='hide-small hide-medium'>".$text['label-codec']."</th>\n";
	}
	if (permission_exists('call_active_secure')) {
		echo "		<th class='hide-small hide-medium'>".$text['label-secure']."</th>\n";
	}
	if (permission_exists('call_active_eavesdrop') || permission_exists('call_active_hangup')) {
		echo "		<th>&nbsp;</th>\n";
	}
	echo "	</tr>\n";

	if (is_array($rows)) {
		$x = 0;
		foreach ($rows as $row) {

			//set the php variables
				foreach ($row as $key => $value) {
					$$key = $value;
				}

			//get the sip profile
				$name_array = explode("/", $name);
				$sip_profile = $name_array[1];
				$sip_uri = $name_array[2];

			//get the number
				//$temp_array = explode("@", $sip_uri);
				//$tmp_number = $temp_array[0];
				//$tmp_number = str_replace("sip:", "", $tmp_number);

			//remove the '+' because it breaks the call recording
				$cid_num = str_replace("+", "", $cid_num);

			//replace gateway uuid with name
				if (is_array($gateways) && sizeof($gateways) > 0) {
					foreach ($gateways as $gateway_uuid => $gateway_name) {
						$application_data = str_replace($gateway_uuid, $gateway_name, $application_data);
					}
				}

			//calculate elapsed seconds
				$elapsed_seconds = time() - $created_epoch;

			//convert seconds to hours, minutes, and seconds
				$hours = floor($elapsed_seconds / 3600);
				$minutes = floor(($elapsed_seconds % 3600) / 60);
				$seconds = $elapsed_seconds % 60;

			//format the elapsed time as HH:MM:SS
				$elapsed_time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

			//reduce too long app data
				if(strlen($application_data) > 80) {
					$application_data = substr($application_data, 0, 80) . '...';
				}

			//send the html
				echo "	<tr class='list-row'>\n";
				if (permission_exists('call_active_hangup')) {
					echo "		<td class='checkbox'>\n";
					echo "			<input type='checkbox' name='calls[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (this.checked) { refresh_stop(); } else { document.getElementById('checkbox_all').checked = false; }\">\n";
					echo "			<input type='hidden' name='calls[$x][uuid]' value='".escape($uuid)."' />\n";
					echo "		</td>\n";
				}
				if (permission_exists('call_active_profile')) {
					echo "		<td class='hide-small'>".escape($sip_profile)."&nbsp;</td>\n";
				}
				//echo "		<td>".escape($created)."&nbsp;</td>\n";
				echo "		<td>".escape($elapsed_time)."</td>\n";
				if ($show == 'all') {
					echo "		<td>".escape($domain_name)."&nbsp;</td>\n";
				}
				//echo "		<td>".escape($tmp_number)."&nbsp;</td>\n";
				echo "		<td class='hide-small'>".escape($cid_name)."&nbsp;</td>\n";
				echo "		<td>".escape($cid_num)."&nbsp;</td>\n";
				echo "		<td>".escape($dest)."&nbsp;</td>\n";
				if (permission_exists('call_active_application')) {
					echo "		<td class='hide-small hide-medium' style='max-width: 200px; word-wrap: break-word;'>".(!empty($application) ? escape($application).":".escape($application_data) : null)."&nbsp;</td>\n";
				}
				if (permission_exists('call_active_codec')) {
					echo "		<td class='hide-small hide-medium'>".escape($read_codec).":".escape($read_rate)." / ".escape($write_codec).":".escape($write_rate)."&nbsp;</td>\n";
				}
				if (permission_exists('call_active_secure')) {
					echo "		<td class='hide-small hide-medium'>".escape($secure)."&nbsp;</td>\n";
				}
				if (permission_exists('call_active_eavesdrop') || permission_exists('call_active_hangup')) {
					echo "		<td class='button right' style='padding-right: 0;'>\n";
					//eavesdrop
					if (permission_exists('call_active_eavesdrop') && $callstate == 'ACTIVE' && !empty($user['extensions']) && !in_array($cid_num, $user['extensions'])) {
						echo button::create(['type'=>'button','label'=>$text['label-eavesdrop'],'icon'=>'headphones','collapse'=>'hide-lg-dn','onclick'=>"if (confirm('".$text['confirm-eavesdrop']."')) { eavesdrop_call('".escape($cid_num)."','".escape($uuid)."'); } else { this.blur(); return false; }",'onmouseover'=>'refresh_stop()','onmouseout'=>'refresh_start()']);
					}
					//hangup
					if (permission_exists('call_active_hangup')) {
						echo button::create(['type'=>'button','label'=>$text['label-hangup'],'icon'=>'phone-slash','collapse'=>'hide-lg-dn','onclick'=>"if (confirm('".$text['confirm-hangup']."')) { list_self_check('checkbox_".$x."'); list_action_set('hangup'); list_form_submit('form_list'); } else { this.blur(); return false; }",'onmouseover'=>'refresh_stop()','onmouseout'=>'refresh_start()']);
					}
					echo "	</td>\n";
				}
				echo "	</tr>\n";

			//unset the domain name
				unset($domain_name);

			//increment counter
				$x++;
		}
	}

	echo "	</table>\n";
	echo "</div>\n";
	echo "<input type='hidden' name='".$_SESSION['app']['calls_active']['token']['name']."' value='".$_SESSION['app']['calls_active']['token']['hash']."'>\n";
	echo "</form>\n";

?>
