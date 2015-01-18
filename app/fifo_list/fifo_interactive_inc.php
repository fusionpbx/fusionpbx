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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('active_queue_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set variables
	$fifo_name = trim($_REQUEST["c"]);

//if not the user is not a member of the superadmin then restrict to viewing their own domain
	if (!if_group("superadmin")) {
		if (stripos($fifo_name, $_SESSION['domain_name']) === false) {
			echo "access denied";
			exit;
		}
	}

//prepare and send the api command over event socket
	$switch_cmd = 'fifo list_verbose '.$fifo_name.'';
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		$msg = "<div align='center'>Connection to Event Socket failed.<br /></div>";
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>Message</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}
	else {
		//send the api command over event socket
			$xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));

		//parse the response as xml
			try {
				$xml = new SimpleXMLElement($xml_str);
			}
			catch(Exception $e) {
				//echo $e->getMessage();
			}

		//set variables from the xml
			//$name = $xml->conference['name'];
			//$member_count = $xml->conference['member-count'];
			//$locked = $xml->conference['locked'];

		//set the alternating row styles
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

		//response div tag
			echo "<div id='cmd_reponse'>\n";
			echo "</div>\n";

		//show the content
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			/*
			echo "<tr>\n";
			echo "<td >\n";
			//echo "	<strong>Count: $member_count</strong>\n";
			echo "</td>\n";
			echo "<td colspan='7'>\n";
			echo "	&nbsp;\n";
			echo "</td>\n";
			echo "<td colspan='1' align='right'>\n";
			echo "	<strong>Queues Tools:</strong> \n";
			echo "	<a href='javascript:void(0);' onclick=\"record_count++;send_cmd('v_conference_exec.php?cmd=conference%20".$conference_name." record recordings/conference_".$conference_name."-'+document.getElementById('time_stamp').innerHTML+'_'+record_count+'.wav');\">Start Record</a>&nbsp;\n";
			echo "	<a href='javascript:void(0);' onclick=\"send_cmd('v_conference_exec.php?cmd=conference%20".$conference_name." norecord recordings/conference_".$conference_name."-'+document.getElementById('time_stamp').innerHTML+'_'+record_count+'.wav');\">Stop Record</a>&nbsp;\n";
			if ($locked == "true") {
				echo "	<a href='javascript:void(0);' onclick=\"send_cmd('v_conference_exec.php?cmd=conference%20".$conference_name." unlock');\">Unlock</a>&nbsp;\n";
			}
			else {
				echo "	<a href='javascript:void(0);' onclick=\"send_cmd('v_conference_exec.php?cmd=conference%20".$conference_name." lock');\">Lock</a>&nbsp;\n";
			}
			echo "</td>\n";
			echo "</tr>\n";
			*/

			echo "<tr>\n";
			echo "<th>".$text['label-username']."</th>\n";
			echo "<th>".$text['label-caller_id_name']."</th>\n";
			echo "<th>".$text['label-caller_id_number']."</th>\n";
			echo "<th>".$text['label-language']."</th>\n";
			echo "<th>".$text['label-destination_number']."</th>\n";
			echo "<th>".$text['label-position']."</th>\n";
			echo "<th>".$text['label-priority']."</th>\n";
			echo "<th>".$text['label-status']."</th>\n";
			echo "<th>".$text['label-duration']."</th>\n";
			echo "</tr>\n";

			foreach ($xml->fifo->callers->caller as $row) {
				/*
				$username = $row->caller_profile->username;
				$dialplan = $row->caller_profile->dialplan;
				$caller_id_name = urldecode($row->caller_profile->caller_id_name);
				$caller_id_number = $row->caller_profile->caller_id_number;
				$ani = $row->caller_profile->ani;
				$aniii = $row->caller_profile->aniii;
				$network_addr = $row->caller_profile->network_addr;
				$destination_number = $row->destination_number->rdnis;
				$rdnis = $row->caller_profile->rdnis;
				$uuid = $row->caller_profile->uuid;
				$source = $row->caller_profile->source;
				$context = $row->caller_profile->context;
				$chan_name = $row->caller_profile->chan_name;
				$default_language = $row->variables->default_language;
				$fifo_position = $row->variables->fifo_position;
				$fifo_priority = $row->variables->fifo_priority;
				$fifo_status = $row->variables->fifo_status;
				$fifo_timestamp = urldecode($row->variables->fifo_timestamp);
				$fifo_time = strtotime($fifo_timestamp);
				$fifo_duration = time() - $fifo_time;
				$fifo_duration_formatted = str_pad(intval(intval($fifo_duration/3600)),2,"0",STR_PAD_LEFT).":" . str_pad(intval(($fifo_duration / 60) % 60),2,"0",STR_PAD_LEFT).":" . str_pad(intval($fifo_duration % 60),2,"0",STR_PAD_LEFT) ;
				*/

				$username = $row->cdr->callflow->caller_profile->username;
				$dialplan = $row->cdr->callflow->caller_profile->dialplan;
				$caller_id_name = urldecode($row->cdr->callflow->caller_profile->caller_id_name);
				$caller_id_number = $row->cdr->callflow->caller_profile->caller_id_number;
				$ani = $row->cdr->callflow->caller_profile->ani;
				$aniii = $row->cdr->callflow->caller_profile->aniii;
				$network_addr = $row->cdr->callflow->caller_profile->network_addr;
				$destination_number = $row->cdr->callflow->caller_profile->destination_number;
				$rdnis = $row->cdr->callflow->caller_profile->rdnis;
				$uuid = $row->cdr->callflow->caller_profile->uuid;
				$source = $row->cdr->callflow->caller_profile->source;
				$context = $row->cdr->callflow->caller_profile->context;
				$chan_name = $row->cdr->callflow->caller_profile->chan_name;
				$default_language = $row->cdr->variables->default_language;
				$fifo_position = $row->cdr->variables->fifo_position;
				$fifo_priority = $row->cdr->variables->fifo_priority;
				$fifo_status = $row->cdr->variables->fifo_status;
				$fifo_timestamp = urldecode($row->cdr->variables->fifo_timestamp);
				$fifo_time = strtotime($fifo_timestamp);
				$fifo_duration = time() - $fifo_time;
				$fifo_duration_formatted = str_pad(intval(intval($fifo_duration/3600)),2,"0",STR_PAD_LEFT).":" . str_pad(intval(($fifo_duration / 60) % 60),2,"0",STR_PAD_LEFT).":" . str_pad(intval($fifo_duration % 60),2,"0",STR_PAD_LEFT) ;

				echo "<tr>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$username &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$caller_id_name &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$caller_id_number &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$default_language &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$destination_number &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$fifo_position &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$fifo_priority &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$fifo_status &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$fifo_duration_formatted &nbsp;</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			}
			echo "</table>\n";
		}
?>
