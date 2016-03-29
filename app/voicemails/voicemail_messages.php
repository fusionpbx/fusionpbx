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
 Portions created by the Initial Developer are Copyright (C) 2008-2015
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
if (!(check_str($_REQUEST["action"]) == "download" && check_str($_REQUEST["src"]) == "email")) {
	require_once "resources/check_auth.php";
	if (permission_exists('voicemail_message_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the voicemail_uuid
	if (strlen($_REQUEST["id"]) > 0) {
		$voicemail_uuid = check_str($_REQUEST["id"]);
	}

//required class
	require_once "app/voicemails/resources/classes/voicemail.php";

//download the message
	if (check_str($_REQUEST["action"]) == "download") {
		$voicemail_message_uuid = check_str($_REQUEST["uuid"]);
		$voicemail_id = check_str($_REQUEST["id"]);
		$voicemail_uuid = check_str($_REQUEST["voicemail_uuid"]);
		if ($voicemail_message_uuid != '' && $voicemail_id != '' && $voicemail_uuid != '') {
			$voicemail = new voicemail;
			$voicemail->db = $db;
			$voicemail->domain_uuid = $_SESSION['domain_uuid'];
			$voicemail->voicemail_id = $voicemail_id;
			$voicemail->voicemail_uuid = $voicemail_uuid;
			$voicemail->voicemail_message_uuid = $voicemail_message_uuid;
			$result = $voicemail->message_download();
			unset($voicemail);
		}
		exit;
	}

//get the html values and set them as variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//get the voicemail
	$vm = new voicemail;
	$vm->db = $db;
	$vm->domain_uuid = $_SESSION['domain_uuid'];
	$vm->voicemail_uuid = $voicemail_uuid;
	$vm->order_by = $order_by;
	$vm->order = $order;
	$voicemails = $vm->messages();

//additional includes
	$document['title'] = $text['title-voicemail_messages'];
	require_once "resources/header.php";
	require_once "resources/paging.php";

//show the content
	echo "<b>".$text['title-voicemail_messages']."</b>";
	echo "<br><br>";
	echo $text['description-voicemail_message'];
	echo "<br><br>";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//loop through the voicemail messages
	if (count($voicemails) > 0) {

		echo "<form name='frm' id='frm' method='post' action=''>\n";

		echo "<br />";
		echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

		$previous_voicemail_id = '';
		foreach($voicemails as $field) {
			if ($previous_voicemail_id != $field['voicemail_id']) {
				if ($previous_voicemail_id != '') {
					echo "<tr><td colspan='20'><br /><br /><br /></td></tr>\n";
				}
				echo "	<td colspan='4' align='left' valign='top'>\n";
				echo "		<b>".$text['label-mailbox'].": ".$field['voicemail_id']." </b><br />&nbsp;\n";
				echo "	</td>\n";
				echo "	<td colspan='".(($_SESSION['voicemail']['storage_type']['text'] != 'base64') ? 3 : 2)."' valign='bottom' align='right'>\n";
				echo "		<input type='button' class='btn' alt='".$text['button-toggle']."' onclick=\"$('#frm').attr('action', 'voicemail_message_toggle.php').submit();\" value='".$text['button-toggle']."'>\n";
				if (permission_exists('voicemail_greeting_view')) {
					echo "	<input type='button' class='btn' alt='".$text['button-greetings']."' onclick=\"document.location.href='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$field['voicemail_id']."'\" value='".$text['button-greetings']."'>\n";
				}
				if (permission_exists('voicemail_edit')) {
					echo "	<input type='button' class='btn' alt='".$text['button-settings']."' onclick=\"document.location.href='voicemail_edit.php?id=".$field['voicemail_uuid']."'\" value='".$text['button-settings']."'>\n";
				}
				echo "		<br /><br />";
				echo "	</td>\n";
				echo "	<td>&nbsp;</td>\n";
				echo "</tr>\n";

				if (count($field['messages']) > 0) {
					echo "<tr>\n";
					if (permission_exists('voicemail_message_delete')) {
						echo "<th style='width: 30px; text-align: center; padding: 0px;'><input type='checkbox' id='chk_all_".$field['voicemail_id']."' onchange=\"(this.checked) ? check('all', '".$field['voicemail_id']."') : check('none', '".$field['voicemail_id']."');\"></th>";
					}
					echo th_order_by('created_epoch', $text['label-created_epoch'], $order_by, $order);
					echo th_order_by('caller_id_name', $text['label-caller_id_name'], $order_by, $order);
					echo th_order_by('caller_id_number', $text['label-caller_id_number'], $order_by, $order);
					echo "<th>".$text['label-tools']."</th>\n";
					echo th_order_by('message_length', $text['label-message_length'], $order_by, $order, null, "style='text-align: right;'");
					if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
						echo "<th style='text-align: right;'>".$text['label-message_size']."</th>\n";
					}
					if (permission_exists('voicemail_message_delete')) {
						echo "<td class='list_control_icons' style='width: 25px;'>";
						echo 	"<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { $('#frm').attr('action', 'voicemail_message_delete.php').submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
						echo "</td>";
					}
					echo "</tr>\n";
				}
			}

			if (count($field['messages']) > 0) {
				foreach($field['messages'] as &$row) {
					$style = ($row['message_status'] == '' && $_REQUEST["uuid"] != $row['voicemail_message_uuid']) ? "font-weight: bold;" : null;

					//playback progress bar
					echo "<tr id='recording_progress_bar_".$row['voicemail_message_uuid']."' style='display: none;'><td colspan='".((permission_exists('voicemail_message_delete')) ? 7 : 6)."' class='".$row_style[$c]."' style='padding: 0px; border: none;'><span class='playback_progress_bar' id='recording_progress_".$row['voicemail_message_uuid']."'></span></td></tr>\n";

					$tr_link = "href=\"javascript:recording_play('".$row['voicemail_message_uuid']."');\"";
					echo "<tr ".$tr_link.">\n";
					if (permission_exists('voicemail_message_delete')) {
						echo "	<td valign='top' class='".$row_style[$c]." tr_checkbox tr_link_void' style='text-align: center; vertical-align: middle; padding: 0px;'>";
						echo "		<input type='checkbox' name='voicemail_messages[".$row['voicemail_uuid']."][]' id='checkbox_".$row['voicemail_message_uuid']."' value='".$row['voicemail_message_uuid']."' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$row['voicemail_id']."').checked = false; }\">";
						echo "	</td>";
						$vm_msg_ids[$row['voicemail_id']][] = 'checkbox_'.$row['voicemail_message_uuid'];
					}
					echo "	<td valign='top' class='".$row_style[$c]."' style=\"".$style."\" nowrap='nowrap'>".$row['created_date']."</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."' style=\"".$style."\">".$row['caller_id_name']."&nbsp;</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."' style=\"".$style."\">".$row['caller_id_number']."&nbsp;</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]." row_style_slim tr_link_void' onclick=\"$(this).closest('tr').children('td').css('font-weight','normal');\">";
						$recording_file_path = $file;
						$recording_file_name = strtolower(pathinfo($recording_file_path, PATHINFO_BASENAME));
						$recording_file_ext = pathinfo($recording_file_name, PATHINFO_EXTENSION);
						switch ($recording_file_ext) {
							case "wav" : $recording_type = "audio/wav"; break;
							case "mp3" : $recording_type = "audio/mpeg"; break;
							case "ogg" : $recording_type = "audio/ogg"; break;
						}
						echo "<audio id='recording_audio_".$row['voicemail_message_uuid']."' style='display: none;' ontimeupdate=\"update_progress('".$row['voicemail_message_uuid']."')\" preload='none' onended=\"recording_reset('".$row['voicemail_message_uuid']."');\" src=\"voicemail_messages.php?action=download&id=".$row['voicemail_id']."&voicemail_uuid=".$row['voicemail_uuid']."&uuid=".$row['voicemail_message_uuid']."\" type='".$recording_type."'></audio>";
						echo "<a id='recording_button_".$row['voicemail_message_uuid']."' onclick=\"recording_play('".$row['voicemail_message_uuid']."');\" title='".$text['label-play']." / ".$text['label-pause']."'>".$v_link_label_play."</a>";
						echo "<a href=\"voicemail_messages.php?action=download&t=bin&id=".$row['voicemail_id']."&voicemail_uuid=".$row['voicemail_uuid']."&uuid=".$row['voicemail_message_uuid']."\" title='".$text['label-download']."'>".$v_link_label_download."</a>";
					echo "	</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."' style=\"".$style." text-align: right;\">".$row['message_length_label']."&nbsp;</td>\n";
					if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
						echo "	<td valign='top' class='".$row_style[$c]."' style=\"".$style." text-align: right;\" nowrap='nowrap'>".$row['file_size_label']."</td>\n";
					}
					if (permission_exists('voicemail_message_delete')) {
						echo "	<td class='list_control_icon' style='width: 25px;'>";
						echo 		"<a href='voicemail_message_delete.php?voicemail_messages[".$row['voicemail_uuid']."][]=".$row['voicemail_message_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
			}
			else {
				echo "<tr><td colspan='20'>".$text['message-messages_not_found']."<br /></td></tr>";
			}//end foreach
			unset($row);

			$previous_voicemail_id = $field['voicemail_id'];
			unset($sql, $result, $result_count);
		}
		echo "</table>";
		echo "<br /><br />";

		echo "</form>";

	}
	else {
		echo "<br />".$text['message-messages_not_found']."<br /><br />";
	}
	echo "<br />";

//autoplay message
	if (check_str($_REQUEST["action"]) == "autoplay" && check_str($_REQUEST["uuid"]) != '') {
		echo "<script>recording_play('".check_str($_REQUEST["uuid"])."');</script>";
	}

//check or uncheck all voicemail checkboxes
	if (sizeof($vm_msg_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what, voicemail_id) {\n";
		foreach ($vm_msg_ids as $voicemail_id => $checkbox_ids) {
			echo "if (voicemail_id == '".$voicemail_id."') {\n";
			foreach ($checkbox_ids as $index => $checkbox_id) {
				echo "document.getElementById('".$checkbox_id."').checked = (what == 'all') ? true : false;\n";
			}
			echo "}\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

//$(this).children('td:not(.tr_link_void)').css('font-weight','normal');
?>

<script language="JavaScript" type="text/javascript">
	$(document).ready(function() {
		$('.tr_hover tr').each(function(i,e) {
		  $(e).children('td:not(.list_control_icon,.list_control_icons,.tr_checkbox)').click(function() {
			 $(this).closest('tr').children('td').css('font-weight','normal');
		  });
		});
	});
</script>

<?php

//include the footer
	require_once "resources/footer.php";
?>