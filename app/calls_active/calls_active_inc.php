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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
include "app_languages.php";
if (permission_exists('call_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];                
	}

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

		//set the alternating color for each row
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

		//show the results
			echo "<div id='cmd_reponse'>\n";
			echo "</div>\n";

			echo "<table width='100%' border='0' cellpadding='3' cellspacing='0'>\n";
			echo "<tr>\n";
			//echo "<th>ID</th>\n";
			//echo "<th>UUID</th>\n";
			//echo "<th>Dir</th>\n";
			echo "<th>".$text['label-profile']."</th>\n";
			echo "<th>".$text['label-created']."</th>\n";
			//echo "<th>Created Epoch</th>\n";
			//echo "<th>Name</th>\n";
			echo "<th>".$text['label-number']."</th>\n";
			//echo "<th>State</th>\n";
			echo "<th>".$text['label-cid-name']."</th>\n";
			echo "<th>".$text['label-cid-number']."</th>\n";
			//echo "<th>IP Addr</th>\n";
			echo "<th>".$text['label-destination']."</th>\n";
			echo "<th>".$text['label-app']."</th>\n";
			//echo "<th>Dialplan</th>\n";
			//echo "<th>Context</th>\n";
			echo "<th>".$text['label-codec']."</th>\n";
			//echo "<th>Read Rate</th>\n";
			//echo "<th>Write Codec</th>\n";
			//echo "<th>Write Rate</th>\n";
			echo "<th>".$text['label-secure']."</th>\n";
			echo "<th>".$text['label-opt']."</th>\n";
			echo "</tr>\n";

			foreach ($results["rows"] as $row) {
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

				echo "<tr>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$id &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$uuid &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$direction &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$sip_profile &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$created &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$created_epoch &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$name &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$tmp_number."&nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$state &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$cid_name &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$cid_num &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$ip_addr &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$dest &nbsp;</td>\n";
				if (strlen($application) > 0) {
					echo "<td valign='top' class='".$row_style[$c]."'>".$application.":".$application_data." &nbsp;</td>\n";
				}
				else {
					echo "<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
				}
				//echo "<td valign='top' class='".$row_style[$c]."'>$dialplan &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$context &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$read_codec:$read_rate / $write_codec:$write_rate &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$read_rate &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$write_codec &nbsp;</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$write_rate &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$secure &nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."' style='text-align:center;'>\n";
				//transfer
					echo "	<a href='javascript:void(0);' onMouseover=\"document.getElementById('form_label').innerHTML='<strong>".$text['label-transfer']."</strong>';\" onclick=\"send_cmd('calls_exec.php?cmd='+get_transfer_cmd(escape('$uuid')));\">".$text['label-transfer']."</a>&nbsp;\n";
				//park
					echo "	<a href='javascript:void(0);' onclick=\"send_cmd('calls_exec.php?cmd='+get_park_cmd(escape('$uuid')));\">".$text['label-park']."</a>&nbsp;\n";
				//hangup
					echo "	<a href='javascript:void(0);' onclick=\"confirm_response = confirm('".$text['confirm-hangup']."');if (confirm_response){send_cmd('calls_exec.php?cmd=uuid_kill%20'+(escape('$uuid')));}\">".$text['label-hangup']."</a>&nbsp;\n";
				//record start/stop
					$tmp_dir = $_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d");
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
				echo "	&nbsp;";
				echo "</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
	}
?>