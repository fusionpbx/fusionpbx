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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	if (!empty($_REQUEST["c"]) && is_numeric($_REQUEST["c"])) {
		$conference_id = $_REQUEST["c"];
	}
	elseif (!empty($_REQUEST["c"]) && is_uuid($_REQUEST["c"])) {
		$conference_id = $_REQUEST["c"];
	}
	else {
		//exit if the conference id is invalid
		exit;
	}

//replace the space with underscore
	$conference_name = $conference_id.'@'.$_SESSION['domain_name'];

//create the conference list command
	$switch_cmd = "conference '".$conference_name."' xml_list";

//connect to event socket, send the command and process the results
	$esl = event_socket::create();
	if (!$esl->is_connected()) {
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
		$xml_str = trim(event_socket::api($switch_cmd));
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
			if (empty($member_count)) {
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

		echo "<div id='cmd_reponse'></div>\n";

		echo "<div style='float: right;'>\n";

		$recording_dir = $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
		$recording_name = '';
		if (!empty($recording_dir) && !empty($row['uuid'])) {
			if (file_exists($recording_dir.'/'.$row['uuid'].'.wav')) {
				$recording_name = $session_uuid.".wav";
			}
			else if (file_exists($recording_dir.'/'.$row['uuid'].'.mp3')) {
				$recording_name = $session_uuid.".mp3";
			}
		}

		echo "<img src='resources/images/".(!empty($recording) && $recording == "true" ? "recording.png" : "not_recording.png")."' style='width: 16px; height: 16px; border: none;' align='absmiddle' title=\"".$text['label-'.(!empty($recording) && $recording == "true" ? 'recording' : 'not-recording')]."\">&nbsp;&nbsp;";

		if (permission_exists('conference_interactive_lock')) {
			if (!empty($locked) && $locked == 'true') {
				echo button::create(['type'=>'button','label'=>$text['label-unlock'],'icon'=>'unlock','collapse'=>'hide-xs','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=unlock');"]);
			}
			else {
				echo button::create(['type'=>'button','label'=>$text['label-lock'],'icon'=>'lock','collapse'=>'hide-xs','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=lock');"]);
			}
		}
		if (permission_exists('conference_interactive_mute')) {
			if (!empty($mute_all) && $mute_all == 'true') {
				echo button::create(['type'=>'button','label'=>$text['label-unmute-all'],'icon'=>'microphone','collapse'=>'hide-xs','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=unmute+non_moderator');"]);
			}
			else {
				echo button::create(['type'=>'button','label'=>$text['label-mute-all'],'icon'=>'microphone-slash','collapse'=>'hide-xs','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=mute+non_moderator');"]);
			}
		}
		echo button::create(['type'=>'button','label'=>$text['label-end-conference'],'icon'=>'stop','collapse'=>'hide-xs','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=kick+all');"]);

		echo "</div>\n";
		echo "<strong>".$text['label-members'].": ".escape($member_count ?? '')."</strong>\n";
		echo "<br /><br />\n";

		echo "<div class='card'>\n";
		echo "<table class='list'>\n";
		echo "<tr class='list-header'>\n";
		echo "<th width='1px'>&nbsp;</th>\n";
		echo "<th class='no-wrap'>".$text['label-cid-name']."</th>\n";
		echo "<th class='no-wrap'>".$text['label-cid-num']."</th>\n";
		echo "<th class='hide-sm-dn'>".$text['label-joined']."</th>\n";
		echo "<th class='hide-xs'>".$text['label-quiet']."</th>\n";
		echo "<th class='hide-sm-dn'>".$text['label-floor']."</th>\n";
		echo "<th class='hide-sm-dn'>".$text['label-hand_raised']."</th>\n";
		echo "<th class='center'>".$text['label-capabilities']."</th>\n";
		if (permission_exists('conference_interactive_energy')) {
			echo "<th class='center'>".$text['label-energy']."</th>\n";
		}
		if (permission_exists('conference_interactive_volume')) {
			echo "<th class='center'>".$text['label-volume']."</th>\n";
		}
		if (permission_exists('conference_interactive_gain')) {
			echo "<th class='center'>".$text['label-gain']."</th>\n";
		}
		echo "<th>&nbsp;</th>\n";
		echo "</tr>\n";
		if ($valid_xml && isset($xml->conference->members->member)) {
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
				$switch_cmd = "uuid_getvar ".$uuid. " hand_raised";
				$hand_raised = (trim(event_socket::api($switch_cmd)) == "true") ? "true" : "false";
				//format secondsfloor(floor($fifo_duration / 60) % 60)
				$join_time_formatted = sprintf('%02d:%02d:%02d', floor($join_time / 3600), floor(floor($join_time / 60) % 60), $join_time % 60);
				$last_talking_formatted = sprintf('%02d:%02d:%02d', floor($last_talking / 3600), floor(floor($last_talking / 60) % 60), $last_talking % 60);

				if (empty($record_path)) {
					if (permission_exists('conference_interactive_mute')) {
						$action_mute = ($flag_can_speak == "true") ? 'mute' : 'unmute';
						$list_row_onclick = "onclick=\"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=".$action_mute."&id=".urlencode($id)."');\"";
						$list_row_title = "title=\"".$text['message-click_to_'.$action_mute]."\"";
					}
					echo "<tr class='list-row'>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title.">";
					if ($is_moderator == 'true') {
						echo "<i class='fas fa-user-tie fa-fw' title=\"".$text['label-moderator']."\"></i>";
					}
					else {
						echo "<i class='fas fa-user fa-fw' title=\"".$text['label-participant']."\"></i>";
					}
					echo "</td>\n";
					$talking_icon = "<span class='far fa-comment' style='font-size: 14px; margin: -2px 10px -2px 15px; visibility: ".($flag_talking == "true" ? 'visible' : 'hidden').";' align='absmiddle' title=\"".$text['label-talking']."\">";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='no-wrap'>".escape(urldecode($caller_id_name)).$talking_icon."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title.">".escape(urldecode($caller_id_number))."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='hide-sm-dn'>".escape($join_time_formatted)."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='hide-xs'>".escape($last_talking_formatted)."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='hide-sm-dn'>".$text['label-'.(($flag_has_floor == "true") ? 'yes' : 'no')]."</td>\n";
					$hand_raise_icon = "<i class='fas fa-hand-paper' style='font-size: 14px; margin: -2px 10px -2px 15px; visibility: ".($hand_raised == "true" ? 'visible' : 'hidden').";' align='absmiddle' title=\"".$text['label-hand_raised']."\">";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='hide-sm-dn'>".$text['label-'.(($hand_raised == "true") ? 'yes' : 'no')]." ".$hand_raise_icon."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='center'>";
					echo 	($flag_can_speak == "true") ? "<i class='fas fa-microphone fa-fw' title=\"".$text['label-speak']."\"></i>" : "<i class='fas fa-microphone-slash fa-fw' title=\"".$text['label-speak']."\"></i>";
					echo 	($flag_can_hear == "true") ? "<i class='fas fa-headphones fa-fw' title=\"".$text['label-hear']."\" style='margin-left: 10px;'></i>" : "<i class='fas fa-deaf fa-fw' title=\"".$text['label-hear']."\" style='margin-left: 10px;'></i>";
					if (permission_exists('conference_interactive_video')) {
						echo ($flag_has_video == "true") ? "<i class='fas fa-video fa-fw' title=\"".$text['label-video']."\" style='margin-left: 10px;'></i>" : null;
					}
					echo "</td>\n";
					//energy
						if (permission_exists('conference_interactive_energy')) {
							echo "<td class='button center'>\n";
							echo button::create(['type'=>'button','title'=>$text['label-energy'],'icon'=>'plus','onclick'=>"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".urlencode($conference_name)."&data=energy&id=".urlencode($id)."');"]);
							echo button::create(['type'=>'button','title'=>$text['label-energy'],'icon'=>'minus','onclick'=>"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".urlencode($conference_name)."&data=energy&id=".urlencode($id)."');"]);
							echo "</td>\n";
						}
					//volume
						if (permission_exists('conference_interactive_volume')) {
							echo "<td class='button center'>\n";
							echo button::create(['type'=>'button','title'=>$text['label-volume'],'icon'=>'volume-down','onclick'=>"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".urlencode($conference_name)."&data=volume_in&id=".urlencode($id)."');"]);
							echo button::create(['type'=>'button','title'=>$text['label-volume'],'icon'=>'volume-up','onclick'=>"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".urlencode($conference_name)."&data=volume_in&id=".urlencode($id)."');"]);
							echo "</td>\n";
						}
					//gain
						if (permission_exists('conference_interactive_gain')) {
							echo "<td class='button center'>\n";
							echo button::create(['type'=>'button','title'=>$text['label-volume'],'icon'=>'sort-amount-down','onclick'=>"send_cmd('conference_exec.php?direction=down&cmd=conference&name=".urlencode($conference_name)."&data=volume_out&id=".urlencode($id)."');"]);
							echo button::create(['type'=>'button','title'=>$text['label-volume'],'icon'=>'sort-amount-up','onclick'=>"send_cmd('conference_exec.php?direction=up&cmd=conference&name=".urlencode($conference_name)."&data=volume_out&id=".urlencode($id)."');"]);
							echo "</td>\n";
						}
					echo "<td class='button right' style='padding-right: 0;'>\n";
					//mute and unmute
						if (permission_exists('conference_interactive_mute')) {
							if ($action_mute == "mute") { //mute
								echo button::create(['type'=>'button','label'=>$text['label-mute'],'icon'=>'microphone-slash','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=mute&id=".urlencode($id)."');"]);
							}
							else { //unmute
								echo button::create(['type'=>'button','label'=>$text['label-unmute'],'icon'=>'microphone','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=unmute&id=".urlencode($id)."&uuid=".escape($uuid)."');"]);
							}
						}
					//deaf and undeaf
						if (permission_exists('conference_interactive_deaf')) {
							if ($flag_can_hear == "true") { //deaf
								echo button::create(['type'=>'button','label'=>$text['label-deaf'],'icon'=>'deaf','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=deaf&id=".urlencode($id)."');"]);
							}
							else { //undeaf
								echo button::create(['type'=>'button','label'=>$text['label-undeaf'],'icon'=>'headphones','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=undeaf&id=".urlencode($id)."');"]);
							}
						}
					//kick someone from the conference
						if (permission_exists('conference_interactive_kick')) {
							echo button::create(['type'=>'button','label'=>$text['label-kick'],'icon'=>'ban','onclick'=>"send_cmd('conference_exec.php?cmd=conference&name=".urlencode($conference_name)."&data=kick&id=".urlencode($id)."&uuid=".escape($uuid)."');"]);
						}
					echo "</td>\n";
					echo "</tr>\n";
				}
			}
		}
		echo "</table>\n";
		echo "</div>\n";
		echo "<br /><br />";
	}

?>
