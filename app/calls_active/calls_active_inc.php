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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
		//send the event socket command
			$json = trim(event_socket_request($fp, 'api '.$switch_cmd));
		//set the array
			$results = json_decode($json, "true");

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

			$rows = array();
			if (isset($results["rows"])) foreach ($results["rows"] as &$row) {
				//determine show all
					if (!($show == 'all' && permission_exists('call_active_all'))) {
						$foreign_call = true;
						foreach ($row as $key => $value) {
							if (substr_count($value, $_SESSION['domain_name']) > 0) { $foreign_call = false; }
						}
						if ($foreign_call) { continue; }
					}
				// append call
					$rows[] = $row;
			}

			echo "<b>".$text['title']." (" . count($rows) . ")"."</b>";
			echo "<br><br>\n";
			echo $text['description']."\n";
			echo "<br><br>\n";

		//set the alternating color for each row
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

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
					if (if_group("superadmin") && isset($_REQUEST['debug'])) {
						echo "<tr><td colspan='20'><pre>".print_r($row, true)."</pre></td></tr>";
					}

				//get the sip profile
					$name_array = explode("/", $name);
					$sip_profile = $name_array[1];
					$sip_uri = $name_array[2];

				//get the number
					$temp_array = explode("@", $sip_uri);
					$tmp_number = $temp_array[0];
					$tmp_number = str_replace("sip:", "", $tmp_number);

				//get the domain
					if ($show == 'all') {
						if (substr_count($presence_id, '@') > 0) {
							$presence_id_array = explode('@', $presence_id);
							$domain_name = $presence_id_array[1];
						}
						else if ($context != '') {
							if (substr_count($context, '@') > 0) {
								$context_array = explode('@', $context);
								$domain_name = $context_array[1];
							}
							else {
								$domain_name = $context;
							}
						}
					}

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

				echo "<tr>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$sip_profile."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$created."&nbsp;</td>\n";
				if ($show == 'all') {
					echo "<td valign='top' class='".$row_style[$c]."'>".$domain_name."&nbsp;</td>\n";
				}
				echo "<td valign='top' class='".$row_style[$c]."'>".$tmp_number."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$cid_name."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$cid_num."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$dest."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".((strlen($application) > 0) ? $application.":".$application_data : null)."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$read_codec.":".$read_rate." / ".$write_codec.":".$write_rate."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$secure."&nbsp;</td>\n";
				echo "<td class='list_control_icons' style='width: 25px; text-align: left;'><a href='javascript:void(0);' alt='".$text['label-hangup']."' onclick=\"hangup(escape('".$uuid."'));\">".$v_link_label_delete."</a></td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
	}

/*
// deprecated features for this page

	//park
		echo "	<a href='javascript:void(0);' onclick=\"send_cmd('calls_exec.php?cmd='+get_park_cmd(escape('$uuid'), '".$tmp_domain."'));\">".$text['label-park']."</a>&nbsp;\n";
	//record start/stop
		$tmp_dir = $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/archive/".date("Y")."/".date("M")."/".date("d");
		mkdir($tmp_dir, 0777, true);
		$tmp_file = $tmp_dir."/".$uuid.".wav";
		if (file_exists($tmp_file)) {
			//stop
			echo "	<a href='javascript:void(0);' style='color: #444444;' onclick=\"send_cmd('calls_exec.php?cmd='+get_record_cmd(escape('$uuid'), 'active_calls_', escape('$cid_num'))+'&uuid='+escape('$uuid')+'&action=record&action2=stop&prefix=active_calls_&name='+escape('$cid_num'));\">".$text['label-stop']."</a>&nbsp;\n";
		}
		else {
			//start
			echo "	<a href='javascript:void(0);' style='color: #444444;' onclick=\"send_cmd('calls_exec.php?cmd='+get_record_cmd(escape('$uuid'), 'active_calls_', escape('$cid_num'))+'&uuid='+escape('$uuid')+'&action=record&action2=start&prefix=active_calls_');\">".$text['label-start']."</a>&nbsp;\n";
		}
*/
?>