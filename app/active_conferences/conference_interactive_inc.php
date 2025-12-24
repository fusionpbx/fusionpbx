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
	James Rose <james.o.rose@gmail.com>
	Tim Fry <tim@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('conference_interactive_view')) {
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

//create the conference list command using JSON
	$switch_cmd = "conference '".$conference_name."' json_list";

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
		$json_str = trim(event_socket::api($switch_cmd));
		$conference = null;
		$valid_json = false;

		if (!empty($json_str) && substr($json_str, -9) !== "not found") {
			$conferences = json_decode($json_str, true);
			// json_list returns an array of conferences, get the first one (should only be one when querying by name)
			if (is_array($conferences) && !empty($conferences)) {
				$conference = $conferences[0];
				$valid_json = true;
			}
		}

		if ($valid_json) {
			// conference_uuid is the session UUID, not uuid
			$session_uuid = $conference['conference_uuid'] ?? '';
			$member_count = (int)($conference['member_count'] ?? 0);
			$locked = $conference['locked'] === true;
			$recording = $conference['recording'] === true;
			$members = $conference['members'] ?? [];

			//get mute_all
			$mute_all = true;
			foreach ($members as $member) {
				$is_mod = $member['flags']['is_moderator'] ?? false;
				$speaks = $member['flags']['can_speak'] ?? false;
				if (!$is_mod && $speaks) {
					$mute_all = false;
					break;
				}
			}
		}

		echo "<div id='cmd_reponse'></div>\n";

		echo "<div style='float: right;'>\n";

		$recording_dir = $settings->get('switch', 'recordings').'/'.$_SESSION['domain_name'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
		$recording_name = '';
		if (!empty($recording_dir) && !empty($session_uuid)) {
			if (file_exists($recording_dir.'/'.$session_uuid.'.wav')) {
				$recording_name = $session_uuid.".wav";
			}
			else if (file_exists($recording_dir.'/'.$session_uuid.'.mp3')) {
				$recording_name = $session_uuid.".mp3";
			}
		}

		echo "<img src='resources/images/".($recording ? "recording.png" : "not_recording.png")."' style='width: 16px; height: 16px; border: none;' align='absmiddle' title=\"".$text['label-'.($recording ? 'recording' : 'not-recording')]."\">&nbsp;&nbsp;";

		if (permission_exists('conference_interactive_lock')) {
			if ($locked) {
				echo button::create(['type'=>'button','label'=>$text['label-unlock'],'icon'=>'unlock','collapse'=>'hide-xs','onclick'=>"conference_action('unlock');"]);
			}
			else {
				echo button::create(['type'=>'button','label'=>$text['label-lock'],'icon'=>'lock','collapse'=>'hide-xs','onclick'=>"conference_action('lock');"]);
			}
		}
		if (permission_exists('conference_interactive_mute')) {
			if ($mute_all) {
				echo button::create(['type'=>'button','label'=>$text['label-unmute-all'],'icon'=>'microphone','collapse'=>'hide-xs','onclick'=>"conference_action('unmute_all');"]);
			}
			else {
				echo button::create(['type'=>'button','label'=>$text['label-mute-all'],'icon'=>'microphone-slash','collapse'=>'hide-xs','onclick'=>"conference_action('mute_all');"]);
			}
		}
		if (permission_exists('conference_interactive_kick')) {
			echo button::create(['type'=>'button','label'=>$text['label-end-conference'],'icon'=>'stop','collapse'=>'hide-xs','onclick'=>"conference_action('kick_all');"]);
		}

		echo "</div>\n";
		echo "<strong>".$text['label-members'].": ".escape($member_count)."</strong>\n";
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
		if ($valid_json && !empty($members)) {
			foreach ($members as $member) {
				// Extract member data from JSON structure
				$id = (int)($member['id'] ?? 0);
				$record_path = $member['record_path'] ?? '';
				$uuid = $member['uuid'] ?? '';
				$caller_id_name = urldecode($member['caller_id_name'] ?? '');
				$caller_id_number = $member['caller_id_number'] ?? '';

				// Flags are actual booleans in JSON response
				$flags = $member['flags'] ?? [];
				$flag_can_hear = $flags['can_hear'] ?? false;
				$flag_can_speak = $flags['can_speak'] ?? false;
				$flag_talking = $flags['talking'] ?? false;
				$flag_has_video = $flags['has_video'] ?? false;
				$flag_has_floor = $flags['has_floor'] ?? false;
				$is_moderator = $flags['is_moderator'] ?? false;

				// Time values
				$last_talking = (int)($member['last_talking'] ?? 0);
				$join_time = (int)($member['join_time'] ?? 0);

				// Get hand raised status
				$switch_cmd = "uuid_getvar ".$uuid." hand_raised";
				$hand_raised = (trim(event_socket::api($switch_cmd)) == "true");

				// Format time values
				$join_time_formatted = sprintf('%02d:%02d:%02d', floor($join_time / 3600), floor(floor($join_time / 60) % 60), $join_time % 60);
				$last_talking_formatted = sprintf('%02d:%02d:%02d', floor($last_talking / 3600), floor(floor($last_talking / 60) % 60), $last_talking % 60);

				if (empty($record_path)) {
					if (permission_exists('conference_interactive_mute')) {
						$action_mute = $flag_can_speak ? 'mute' : 'unmute';
						$list_row_onclick = "onclick=\"conference_action('".$action_mute."', '".escape($id)."', '".escape($uuid)."');\"";
						$list_row_title = "title=\"".$text['message-click_to_'.$action_mute]."\"";
					}
					// Add data attributes for JavaScript to identify and update rows
					echo "<tr class='list-row' data-member-id='".escape($id)."' data-uuid='".escape($uuid)."' data-join-time='".escape($join_time)."' data-last-talking='".escape($last_talking)."'>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title.">";
					if ($is_moderator) {
						echo "<i class='fas fa-user-tie fa-fw' title=\"".$text['label-moderator']."\"></i>";
					}
					else {
						echo "<i class='fas fa-user fa-fw' title=\"".$text['label-participant']."\"></i>";
					}
					echo "</td>\n";
					$talking_icon = "<span class='talking-icon far fa-comment' style='font-size: 14px; margin: -2px 10px -2px 15px; visibility: ".($flag_talking ? 'visible' : 'hidden').";' align='absmiddle' title=\"".$text['label-talking']."\">";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='no-wrap'>".escape($caller_id_name).$talking_icon."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title.">".escape($caller_id_number)."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='hide-sm-dn join-time'>".escape($join_time_formatted)."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='hide-xs quiet-time'>".escape($last_talking_formatted)."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='hide-sm-dn'>".$text['label-'.($flag_has_floor ? 'yes' : 'no')]."</td>\n";
					$hand_raise_icon = "<i class='fas fa-hand-paper' style='font-size: 14px; margin: -2px 10px -2px 15px; visibility: ".($hand_raised ? 'visible' : 'hidden').";' align='absmiddle' title=\"".$text['label-hand_raised']."\">";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='hide-sm-dn'>".$text['label-'.($hand_raised ? 'yes' : 'no')]." ".$hand_raise_icon."</td>\n";
					echo "<td ".$list_row_onclick." ".$list_row_title." class='center'>";
					echo 	$flag_can_speak ? "<i class='fas fa-microphone fa-fw' title=\"".$text['label-speak']."\"></i>" : "<i class='fas fa-microphone-slash fa-fw' title=\"".$text['label-speak']."\"></i>";
					echo 	$flag_can_hear ? "<i class='fas fa-headphones fa-fw' title=\"".$text['label-hear']."\" style='margin-left: 10px;'></i>" : "<i class='fas fa-deaf fa-fw' title=\"".$text['label-hear']."\" style='margin-left: 10px;'></i>";
					if (permission_exists('conference_interactive_video')) {
						echo $flag_has_video ? "<i class='fas fa-video fa-fw' title=\"".$text['label-video']."\" style='margin-left: 10px;'></i>" : null;
					}
					echo "</td>\n";
					//energy
						if (permission_exists('conference_interactive_energy')) {
							echo "<td class='button center'>\n";
							echo button::create(['type'=>'button','title'=>$text['label-energy'],'icon'=>'plus','onclick'=>"conference_action('energy', '".escape($id)."', '', 'up');"]);
							echo button::create(['type'=>'button','title'=>$text['label-energy'],'icon'=>'minus','onclick'=>"conference_action('energy', '".escape($id)."', '', 'down');"]);
							echo "</td>\n";
						}
					//volume
						if (permission_exists('conference_interactive_volume')) {
							echo "<td class='button center'>\n";
							echo button::create(['type'=>'button','title'=>$text['label-volume'],'icon'=>'volume-down','onclick'=>"conference_action('volume_in', '".escape($id)."', '', 'down');"]);
							echo button::create(['type'=>'button','title'=>$text['label-volume'],'icon'=>'volume-up','onclick'=>"conference_action('volume_in', '".escape($id)."', '', 'up');"]);
							echo "</td>\n";
						}
					//gain
						if (permission_exists('conference_interactive_gain')) {
							echo "<td class='button center'>\n";
							echo button::create(['type'=>'button','title'=>$text['label-volume'],'icon'=>'sort-amount-down','onclick'=>"conference_action('volume_out', '".escape($id)."', '', 'down');"]);
							echo button::create(['type'=>'button','title'=>$text['label-volume'],'icon'=>'sort-amount-up','onclick'=>"conference_action('volume_out', '".escape($id)."', '', 'up');"]);
							echo "</td>\n";
						}
					echo "<td class='button right' style='padding-right: 0;'>\n";
					//mute and unmute
						if (permission_exists('conference_interactive_mute')) {
							if ($action_mute == "mute") { //mute
								echo button::create(['type'=>'button','label'=>$text['label-mute'],'icon'=>'microphone-slash','onclick'=>"conference_action('mute', '".escape($id)."', '".escape($uuid)."');"]);
							}
							else { //unmute
								echo button::create(['type'=>'button','label'=>$text['label-unmute'],'icon'=>'microphone','onclick'=>"conference_action('unmute', '".escape($id)."', '".escape($uuid)."');"]);
							}
						}
					//deaf and undeaf
						if (permission_exists('conference_interactive_deaf')) {
							if ($flag_can_hear) { //deaf
								echo button::create(['type'=>'button','label'=>$text['label-deaf'],'icon'=>'deaf','onclick'=>"conference_action('deaf', '".escape($id)."');"]);
							}
							else { //undeaf
								echo button::create(['type'=>'button','label'=>$text['label-undeaf'],'icon'=>'headphones','onclick'=>"conference_action('undeaf', '".escape($id)."');"]);
							}
						}
					//kick someone from the conference
						if (permission_exists('conference_interactive_kick')) {
							echo button::create(['type'=>'button','label'=>$text['label-kick'],'icon'=>'ban','onclick'=>"conference_action('kick', '".escape($id)."', '".escape($uuid)."');"]);
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
