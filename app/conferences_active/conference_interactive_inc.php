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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('conference_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get the http get or post and set it as php variables
	$conference_uuid = check_str($_REQUEST["c"]);

//replace the space with underscore
	$conference_name = $conference_uuid.'-'.$_SESSION['domain_name'];

//create the conference list command
	$switch_cmd = "conference '".$conference_name."' xml_list";

//connect to event socket, send the command and process the results
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		$msg = "<div align='center'>".$text['message-connection']."<br /></div>";
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
		//show the content
		$xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
		try {
			$xml = new SimpleXMLElement($xml_str);
		}
		catch(Exception $e) {
			//echo $e->getMessage();
		}
		//$name = $xml->conference['name'];
		$session_uuid = $xml->conference['uuid'];
		$member_count = $xml->conference['member-count'];
		$locked = $xml->conference['locked'];
		$recording = $xml->conference['recording'];
		if (strlen($member_count) == 0) {
			$member_count = 0;
		}

		//get mute_all
		$mute_all = "true";
		foreach ($xml->conference->members->member as $row) {
			if ($row->flags->is_moderator == "false") {
				if ($row->flags->can_speak == "true") {
					$mute_all = "false";
				}
			}
		}

		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		echo "<div id='cmd_reponse'>\n";
		echo "</div>\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<td colspan='3' >\n";
		echo "	<strong>\n";
		echo "		Members: ".$member_count."\n";
		echo "	</strong>\n";
		echo "</td>\n";
		echo "<td colspan='2'>\n";
		echo "	&nbsp;\n";
		echo "</td>\n";
		echo "<td colspan='7' align='right'>\n";

		$recording_dir = $_SESSION['switch']['recordings']['dir'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
		$recording_name = '';
		if (file_exists($recording_dir.'/'.$row['uuid'].'.wav')) {
			$recording_name = $session_uuid.".wav";
		}
		elseif (file_exists($recording_dir.'/'.$row['uuid'].'.mp3')) {
			$recording_name = $session_uuid.".mp3";
		}

		if ($recording == "true") {
			echo "	".$text['label-recording']." &nbsp;";
		}
		else {
			echo "	".$text['label-not-recording']." &nbsp;";
		}
		if (permission_exists('conference_active_lock')) {
			if ($locked == "true") {
				echo "		<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=unlock');\" value='".$text['label-unlock']."'>\n";
			}
			else {
				echo "		<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=lock');\" value='".$text['label-lock']."'>\n";
			}
		}

		if ($mute_all == "true") {
			echo "		<input type='button' class='btn' title=\"".$text['label-mute-all-alt']."\" onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=unmute+non_moderator');\" value='".$text['label-unmute-all']."'>\n";
		}
		else {
			echo "		<input type='button' class='btn' title=\"".$text['label-mute-all-alt']."\" onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=mute+non_moderator');\" value='".$text['label-mute-all']."'>\n";
		}

		echo "		<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=kick+all');\" value='".$text['label-end-conference']."'>\n";

		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr><td colspan='30'>&nbsp;</td></tr>\n";

		echo "<tr>\n";
		echo "<th>".$text['label-id']."</th>\n";
		//echo "<th>UUID</th>\n";
		echo "<th>".$text['label-cid-name']."</th>\n";
		echo "<th>".$text['label-cid-num']."</th>\n";
		echo "<th>".$text['label-moderator']."</th>\n";
		echo "<th>".$text['label-joined']."</th>\n";
		echo "<th>".$text['label-hear']."</th>\n";
		echo "<th>".$text['label-speak']."</th>\n";
		echo "<th>".$text['label-talking']."</th>\n";
		echo "<th>".$text['label-last-talk']."</th>\n";
		if (permission_exists('conference_active_video')) {
			echo "<th>".$text['label-video']."</th>\n";
		}
		echo "<th>".$text['label-floor']."</th>\n";
		echo "<th>".$text['label-tool']."</th>\n";
		echo "</tr>\n";

		foreach ($xml->conference->members->member as $row) {

			$id = $row->id;
			$record_path = $row->record_path;
			$flag_can_hear = $row->flags->can_hear;
			$flag_can_speak = $row->flags->can_speak;
			$flag_talking = $row->flags->talking;
			$last_talking = $row->last_talking;
			$join_time = $row->join_time;
			$flag_has_video = $row->flags->has_video;
			$flag_has_floor = $row->flags->has_floor;
			$is_moderator = $row->flags->is_moderator;
			$uuid = $row->uuid;
			$caller_id_name = $row->caller_id_name;
			$caller_id_name = urldecode($caller_id_name);
			$caller_id_number = $row->caller_id_number;

			//format the seconds
			$join_time_formatted = sprintf("%02s", floor($join_time/3600)).":".sprintf("%02s",floor($join_time/60)).":".sprintf("%02s",($join_time - (floor($join_time/60))*60));
			$last_talking_formatted = sprintf("%02s",floor($last_talking/3600)).":".sprintf("%02s",floor($last_talking/60)).":".sprintf("%02s",($last_talking - (floor($last_talking/60))*60));

			if (strlen($record_path) == 0) {
				echo "<tr>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$id</td>\n";
				//echo "<td valign='top' class='".$row_style[$c]."'>$uuid</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$caller_id_name</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>$caller_id_number</td>\n";
				if ($is_moderator == "true") {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-yes']."</td>\n";
				}
				else {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-no']."</td>\n";
				}
				echo "<td valign='top' class='".$row_style[$c]."'>".$join_time_formatted."</td>\n";
				if ($flag_can_hear == "true") {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-yes']."</td>\n";
				}
				else {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-no']."</td>\n";
				}
				if ($flag_can_speak == "true") {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-yes']."</td>\n";
				}
				else {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-no']."</td>\n";
				}
				if ($flag_talking == "true") {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-yes']."</td>\n";
				}
				else {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-no']."</td>\n";
				}
				echo "<td valign='top' class='".$row_style[$c]."'>$last_talking_formatted</td>\n";
				if (permission_exists('conference_active_video')) {
					if ($flag_has_video == "true") {
						echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-yes']."</td>\n";
					}
					else {
						echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-no']."</td>\n";
					}
				}
				if ($flag_has_floor == "true") {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-yes']."</td>\n";
				}
				else {
					echo "<td valign='top' class='".$row_style[$c]."'>".$text['label-no']."</td>\n";
				}
				echo "<td valign='top' class='".$row_style[$c]."' style='text-align:right;'>\n";
				//energy
					if (permission_exists('conference_active_energy')) {
						echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."&data=energy&id=".$id."');\" value='+".$text['label-energy']."'>\n";
						echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=energy&id=".$id."');\" value='-".$text['label-energy']."'>\n";
						//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."&data=energy&id=".$id."');\">+".$text['label-energy']."</a>&nbsp;\n";
						//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=energy&id=".$id."');\">-".$text['label-energy']."</a>&nbsp;\n";
					}
				//volume
					if (permission_exists('conference_active_volume')) {
						echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."%&data=volume_in&id=".$id."');\" value='+".$text['label-volume']."'>\n";
						echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=volume_in&id=".$id."');\" value='-".$text['label-volume']."'>\n";
						//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."%&data=volume_in&id=".$id."');\">+".$text['label-volume']."</a>&nbsp;\n";
						//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=volume_in&id=".$id."');\">-".$text['label-volume']."</a>&nbsp;\n";
					}
					if (permission_exists('conference_active_gain')) {
						echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."&data=volume_out&id=".$id."');\" value='+".$text['label-gain']."'>\n";
						echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=volume_out&id=".$id."');\" value='-".$text['label-gain']."'>\n";
						//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."&data=volume_out&id=".$id."');\">+".$text['label-gain']."</a>&nbsp;\n";
						//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=volume_out&id=".$id."');\">-".$text['label-gain']."</a>&nbsp;\n";
					}
				//mute and unmute
					if (permission_exists('conference_active_mute')) {
						if ($flag_can_speak == "true") {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=mute&id=".$id."');\" value='".$text['label-mute']."'>\n";
							//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('');\">".$text['label-mute']."</a>&nbsp;\n";
						}
						else {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=unmute&id=".$id."');\" value='".$text['label-unmute']."'>\n";
							//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=unmute&id=".$id."');\">".$text['label-unmute']."</a>&nbsp;\n";
						}
					}
				//deaf and undeaf
					if (permission_exists('conferences_active_deaf')) {
						if ($flag_can_hear == "true") {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=deaf&id=".$id."');\" value='".$text['label-deaf']."'>\n";
							//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=deaf&id=".$id."');\">".$text['label-deaf']."</a>&nbsp;\n";
						}
						else {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=undeaf&id=".$id."');\" value='".$text['label-undeaf']."'>\n";
							//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=undeaf&id=".$id."');\">".$text['label-undeaf']."</a>&nbsp;\n";
						}
					}
				//kick someone from the conference
					if (permission_exists('conference_active_kick')) {
						echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=kick&id=".$id."&uuid=".$uuid."');\" value='".$text['label-kick']."'>\n";
						//echo "	<a href='javascript:void(0);' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=kick&id=".$id."&uuid=".$uuid."');\">".$text['label-kick']."</a>&nbsp;\n";
					}
				echo "	&nbsp;";
				echo "</td>\n";
				echo "</tr>\n";
			}
			if ($c==0) { $c=1; } else { $c=0; }
		}
		echo "</table>\n";
	}
?>