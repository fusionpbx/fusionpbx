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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
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
				if (strlen($row['context']) > 0 and $row['context'] != "public") {
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
					$row['domain_name'] = $presence_id_array[1].' '.__line__.' '.$row['presence_id'];
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


//set the alternating color for each row
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

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
		//define js function call var
			$onhover_pause_refresh = " onmouseover='refresh_stop();' onmouseout='refresh_start();'";

		//show content
			echo "<table cellpadding='0' cellspacing='0' border='0' align='right'>";
			echo "	<tr>";
			echo "		<td valign='middle' nowrap='nowrap' style='padding-right: 15px' id='refresh_state'>";
			echo "			<img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' onclick='refresh_stop();' alt=\"".$text['label-refresh_pause']."\" title=\"".$text['label-refresh_pause']."\">";
			echo "		</td>";
			echo "		<td valign='top' nowrap='nowrap'>";
			if (permission_exists('call_active_all')) {
				if ($show == "all") {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"document.location='calls_active.php';\" value='".$text['button-back']."' ".$onhover_pause_refresh.">\n";
				}
				else {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-show_all']."' onclick=\"document.location='calls_active.php?show=all';\" value='".$text['button-show_all']."' ".$onhover_pause_refresh.">\n";
				}
			}
			echo "		</td>";
			echo "	</tr>";
			echo "</table>";

			echo "<b>".$text['title']." (" . count($rows) . ")"."</b>";
			echo "<br><br>\n";
			echo $text['description']."\n";
			echo "<br><br>\n";

		//show the results
			echo "<div id='cmd_reponse'></div>\n";

			echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<th>".$text['label-profile']."</th>\n";
			echo "<th>".$text['label-created']."</th>\n";
			if ($show == 'all') {
				echo "<th>".$text['label-domain']."</th>\n";
			}
			echo "<th>".$text['label-number']."</th>\n";
			echo "<th>".$text['label-cid-name']."</th>\n";
			echo "<th>".$text['label-cid-number']."</th>\n";
			echo "<th>".$text['label-destination']."</th>\n";
			echo "<th>".$text['label-app']."</th>\n";
			echo "<th>".$text['label-codec']."</th>\n";
			echo "<th>".$text['label-secure']."</th>\n";
			echo "<td class='list_control_icon'></td>\n";
			echo "</tr>\n";

			foreach ($rows as &$row) {
				//set the php variables
					foreach ($row as $key => $value) {
						$$key = $value;
					}
					//if (if_group("superadmin") && isset($_REQUEST['debug'])) {
					//	echo "<tr><td colspan='20'><pre>".print_r(escape($row), true)."</pre></td></tr>";
					//}

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
					if (sizeof($_SESSION['gateways']) > 0) {
						foreach ($_SESSION['gateways'] as $gateway_uuid => $gateway_name) {
							$application_data = str_replace($gateway_uuid, $gateway_name, $application_data);
						}
					}

				// reduce too long app data
					if(strlen($application_data) > 512) {
						$application_data = substr($application_data, 0, 512) . ' <b>...</b>';
					}

				//send the html
					echo "<tr>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>".escape($sip_profile)."&nbsp;</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>".escape($created)."&nbsp;</td>\n";
					if ($show == 'all') {
						echo "<td valign='top' class='".$row_style[$c]."'>".escape($domain_name)."&nbsp;</td>\n";
					}
					echo "<td valign='top' class='".$row_style[$c]."'>".escape($tmp_number)."&nbsp;</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>".escape($cid_name)."&nbsp;</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>".escape($cid_num)."&nbsp;</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>".escape($dest)."&nbsp;</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>".((strlen($application) > 0) ? escape($application).":".escape($application_data) : null)."&nbsp;</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>".escape($read_codec).":".escape($read_rate)." / ".escape($write_codec).":".escape($write_rate)."&nbsp;</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>".escape($secure)."&nbsp;</td>\n";
					echo "<td class='list_control_icons' style='width: 25px; text-align: left;'><a href='javascript:void(0);' alt='".$text['label-hangup']."' onclick=\"hangup('".escape($uuid)."');\">".$v_link_label_delete."</a></td>\n";
					echo "</tr>\n";

				//alternate the row style
					$c = ($c) ? 0 : 1;
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
	}

?>
