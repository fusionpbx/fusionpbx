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
if (permission_exists('conference_interactive_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

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
		if (substr($xml_str, -9) == "not found") {
			$valid_xml = false;
		}
		else {
			$valid_xml = true;
		}
		if ($valid_xml) {
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
		}
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		echo "<div id='cmd_reponse'></div>\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "	<td>";
		echo "		<strong style='color: #000;'>".$text['label-members'].": ".$member_count."</strong>\n";
		echo "	</td>\n";
		echo "<td align='right'>\n";

		$recording_dir = $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
		$recording_name = '';
		if (file_exists($recording_dir.'/'.$row['uuid'].'.wav')) {
			$recording_name = $session_uuid.".wav";
		}
		elseif (file_exists($recording_dir.'/'.$row['uuid'].'.mp3')) {
			$recording_name = $session_uuid.".mp3";
		}

		echo "<img src='resources/images/".(($recording == "true") ? "recording.png" : "not_recording.png")."' style='width: 16px; height: 16px; border: none;' align='absmiddle' title=\"".$text['label-'.(($recording == "true") ? 'recording' : 'not-recording')]."\">&nbsp;&nbsp;";

		if (permission_exists('conference_interactive_lock')) {
			$action_locked = ($locked == "true") ? 'unlock' : 'lock';
			echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=".$action_locked."');\" value='".$text['label-'.$action_locked]."'>\n";
		}
		if (permission_exists('conference_interactive_mute')) {
			$action_mute_all = ($mute_all == "true") ? 'unmute' : 'mute';
			echo "	<input type='button' class='btn' title=\"".$text['label-mute-all-alt']."\" onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=".$action_mute_all."+non_moderator');\" value='".$text['label-'.$action_mute_all.'-all']."'>\n";
		}
		echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=kick+all');\" value='".$text['label-end-conference']."'>\n";

		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<br />\n";

		echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th width='1'>&nbsp;</th>\n";
		echo "<th>".$text['label-cid-name']."</th>\n";
		echo "<th>".$text['label-cid-num']."</th>\n";
		echo "<th>".$text['label-capabilities']."</th>\n";
		echo "<th>".$text['label-joined']."</th>\n";
		echo "<th>".$text['label-quiet']."</th>\n";
		echo "<th>".$text['label-floor']."</th>\n";
		echo "<th>&nbsp;</th>\n";
		echo "</tr>\n";

		if ($valid_xml) {
			if (isset($xml->conference->members->member)) foreach ($xml->conference->members->member as $row) {
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
	
				//format seconds
				$join_time_formatted = sprintf('%02d:%02d:%02d', ($join_time/3600), ($join_time/60%60), $join_time%60);
				$last_talking_formatted = sprintf('%02d:%02d:%02d', ($last_talking/3600), ($last_talking/60%60), $last_talking%60);
	
				if (strlen($record_path) == 0) {
					if (permission_exists('conference_interactive_mute')) {
						$action_mute = ($flag_can_speak == "true") ? 'mute' : 'unmute';
						$td_onclick = "onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=".$action_mute."&id=".$id."');\"";
						$td_title = "title=\"".$text['message-click_to_'.$action_mute]."\"";
					}
					echo "<tr>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick." ".$td_title." style='padding: 4px 6px;'><img src='resources/images/".(($is_moderator == "true") ? 'moderator' : 'participant').".png' style='width: 16px; height: 16px; border: none;' title=\"".$text['label-'.(($is_moderator == "true") ? 'moderator' : 'participant')]."\"></td>\n";
					$talking_icon = ($flag_talking == "true") ? "<img src='resources/images/talking.png' style='width: 16px; height: 16px; border: none; margin: -2px 10px -2px 15px;' align='absmiddle' title=\"".$text['label-talking']."\">" : null;
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick." ".$td_title.">".$caller_id_name.$talking_icon."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick." ".$td_title.">".$caller_id_number."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick." ".$td_title." style='padding-top: 5px;'>";
					echo 	($flag_can_hear == "true") ? "<img src='resources/images/hear.png' style='width: 16px; height: 16px; border: none; margin: 0px 4px -2px 0px;' align='absmiddle' title=\"".$text['label-hear']."\">" : null;
					echo 	($flag_can_speak == "true") ? "<img src='resources/images/speak.png' style='width: 16px; height: 16px; border: none; margin: 0px 6px -2px 0px;' align='absmiddle' title=\"".$text['label-speak']."\">" : null;
					if (permission_exists('conference_interactive_video')) {
						echo ($flag_has_video == "true") ? "<img src='resources/images/video.png' style='width: 16px; height: 16px; border: none; margin: 0px 4px -2px 0px;' align='absmiddle' title=\"".$text['label-video']."\">" : null;
					}
					echo "</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick." ".$td_title.">".$join_time_formatted."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick." ".$td_title.">".$last_talking_formatted."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick." ".$td_title.">".$text['label-'.(($flag_has_floor == "true") ? 'yes' : 'no')]."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' style='text-align: right; padding: 1px 2px; white-space: nowrap;'>\n";
					//energy
						if (permission_exists('conference_interactive_energy')) {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."&data=energy&id=".$id."');\" value='+".$text['label-energy']."'>\n";
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=energy&id=".$id."');\" value='-".$text['label-energy']."'>\n";
						}
					//volume
						if (permission_exists('conference_interactive_volume')) {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."%&data=volume_in&id=".$id."');\" value='+".$text['label-volume']."'>\n";
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=volume_in&id=".$id."');\" value='-".$text['label-volume']."'>\n";
						}
						if (permission_exists('conference_interactive_gain')) {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".$conference_name."&data=volume_out&id=".$id."');\" value='+".$text['label-gain']."'>\n";
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".$conference_name."&data=volume_out&id=".$id."');\" value='-".$text['label-gain']."'>\n";
						}
					//mute and unmute
						if (permission_exists('conference_interactive_mute')) {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=".$action_mute."&id=".$id."');\" value='".$text['label-'.$action_mute]."'>\n";
						}
					//deaf and undeaf
						if (permission_exists('conference_interactive_deaf')) {
							$action_deaf = ($flag_can_hear == "true") ? 'deaf' : 'undeaf';
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=".$action_deaf."&id=".$id."');\" value='".$text['label-'.$action_deaf]."'>\n";
						}
					//kick someone from the conference
						if (permission_exists('conference_interactive_kick')) {
							echo "	<input type='button' class='btn' onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".$conference_name."&data=kick&id=".$id."&uuid=".$uuid."');\" value='".$text['label-kick']."'>\n";
						}
					echo "</td>\n";
					echo "</tr>\n";
				}
				$c = ($c == 0) ? 1 : 0;
			}
		}
		echo "</table>\n";
		echo "<br /><br />";
	}
?>