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
 Portions created by the Initial Developer are Copyright (C) 2008-2020
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/paging.php";

//download the message
	if (
		$_REQUEST["action"] == "download"
		&& is_numeric($_REQUEST["id"])
		&& is_uuid($_REQUEST["uuid"])
		&& is_uuid($_REQUEST["voicemail_uuid"])
		) {
		$voicemail = new voicemail;
		$voicemail->domain_uuid = $_SESSION['domain_uuid'];
		$voicemail->type = $_REQUEST['t'];
		$voicemail->voicemail_id = $_REQUEST['id'];
		$voicemail->voicemail_uuid = $_REQUEST['voicemail_uuid'];
		$voicemail->voicemail_message_uuid = $_REQUEST['uuid'];
		$result = $voicemail->message_download();
		unset($voicemail);
		exit;
	}

//include after download function
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('voicemail_message_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set the voicemail_uuid
	if (is_uuid($_REQUEST['id'])) {
		$voicemail_uuid = $_REQUEST['id'];
	}
	else if (is_numeric($_REQUEST['id'])) {
		$voicemail_id = $_REQUEST['id'];
	}

//get the http post data
	if (is_array($_POST['voicemail_messages'])) {
		$action = $_POST['action'];
		$voicemail_messages = $_POST['voicemail_messages'];
	}

//process the http post data by action
	if ($action != '' && is_array($voicemail_messages) && @sizeof($voicemail_messages) != 0) {

		//set the referrer
			$http_referer = parse_url($_SERVER["HTTP_REFERER"]);
			$referer_path = $http_referer['path'];
			$referer_query = $http_referer['query'];

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				if ($referer_path == PROJECT_PATH."/app/voicemails/voicemail_messages.php") {
					header('Location: voicemail_messages.php?'.$referer_query);
				}
				else {
					header('Location: voicemails.php');
				}
				exit;
			}

		//handle action
			switch ($action) {
				case 'toggle':
					if (is_array($voicemail_messages) && @sizeof($voicemail_messages) != 0) {
						$messages_toggled = 0;
						foreach ($voicemail_messages as $voicemail_message) {
							if ($voicemail_message['checked'] == 'true' && is_uuid($voicemail_message['uuid']) && is_uuid($voicemail_message['voicemail_uuid'])) {
								//delete voicemail message
									$voicemail = new voicemail;
									$voicemail->db = $db;
									$voicemail->domain_uuid = $_SESSION['domain_uuid'];
									$voicemail->voicemail_uuid = $voicemail_message['voicemail_uuid'];
									$voicemail->voicemail_message_uuid = $voicemail_message['uuid'];
									$voicemail->message_toggle();
									unset($voicemail);
								//increment counter
									$messages_toggled++;
							}
						}
						//set message
							if ($messages_toggled != 0) {
								message::add($text['message-toggle'].': '.$messages_toggled);
							}
					}
					break;
				case 'delete':
					if (permission_exists('voicemail_message_delete')) {
						if (is_array($voicemail_messages) && @sizeof($voicemail_messages) != 0) {
							$messages_deleted = 0;
							foreach ($voicemail_messages as $voicemail_message) {
								if ($voicemail_message['checked'] == 'true' && is_uuid($voicemail_message['uuid']) && is_uuid($voicemail_message['voicemail_uuid'])) {
									//delete voicemail message
										$voicemail = new voicemail;
										$voicemail->db = $db;
										$voicemail->domain_uuid = $_SESSION['domain_uuid'];
										$voicemail->voicemail_uuid = $voicemail_message['voicemail_uuid'];
										$voicemail->voicemail_message_uuid = $voicemail_message['uuid'];
										$voicemail->message_delete();
										unset($voicemail);
									//increment counter
										$messages_deleted++;
								}
							}
							//set message
								if ($messages_deleted != 0) {
									message::add($text['message-delete'].': '.$messages_deleted);
								}
						}
					}
					break;
			}

		//redirect the user
			if ($referer_path == PROJECT_PATH."/app/voicemails/voicemail_messages.php") {
				header('Location: voicemail_messages.php?'.$referer_query);
			}
			else {
				header('Location: voicemails.php');
			}
			exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the html values and set them as variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the voicemail
	$vm = new voicemail;
	$vm->domain_uuid = $_SESSION['domain_uuid'];
	if (is_uuid($voicemail_uuid)) {
		$vm->voicemail_uuid = $voicemail_uuid;
	}
	else if (is_numeric($voicemail_id)) {
		$vm->voicemail_id = $voicemail_id;
	}
	$vm->order_by = $order_by;
	$vm->order = $order;
	$voicemails = $vm->messages();

//count messages
	$new_messages = $num_rows = 0;
	if (is_array($voicemails) && @sizeof($voicemails) != 0) {
		foreach ($voicemails as $voicemail) {
			if (is_array($voicemail['messages'])) {
				$num_rows += sizeof($voicemail['messages']);
				foreach ($voicemail['messages'] as $message) {
					if ($message['message_status'] != 'saved') {
						$new_messages++;
					}
				}
			}
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-voicemail_messages'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-voicemail_messages']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if ($num_rows) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','collapse'=>'hide-xs','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('voicemail_message_delete') && $num_rows) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($num_rows) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('voicemail_message_delete') && $num_rows) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-voicemail_message']."\n";
	echo "<br /><br />\n";

//loop through the voicemail messages
	if (is_array($voicemails) && @sizeof($voicemails) != 0) {

		echo "<form id='form_list' method='post'>\n";
		echo "<input type='hidden' id='action' name='action' value=''>\n";

		echo "<table class='list'>\n";
		echo "<tr style='display: none;'><td></td></tr>\n"; // dummy row to adjust the alternating background color

		$x = 0;
		$previous_voicemail_id = '';
		foreach ($voicemails as $field) {
			if ($previous_voicemail_id != $field['voicemail_id']) {
				if ($previous_voicemail_id != '') {
					echo "<tr><td class='no-link' colspan='20'><br /><br /></td></tr>\n";
				}
				echo "<tr>\n";
				echo "	<td class='no-link' colspan='20' style='padding: 0;'>\n";

				echo "		<div class='action_bar sub'>\n";
				echo "			<div class='heading'><b>".$text['label-mailbox'].": ".$field['voicemail_id']." ".$field['voicemail_description']."</b></div>\n";
				echo "			<div class='actions'>\n";
				if (permission_exists('voicemail_greeting_view')) {
					echo button::create(['type'=>'button','label'=>$text['button-greetings'],'icon'=>'handshake','collapse'=>'hide-xs','link'=>PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$field['voicemail_id']."&back=".urlencode($_SERVER["REQUEST_URI"])]);
				}
				if (permission_exists('voicemail_edit')) {
					echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>'sliders-h','collapse'=>'hide-xs','link'=>'voicemail_edit.php?id='.urlencode($field['voicemail_uuid'])]);
				}
				echo "			</div>\n";
				echo "			<div style='clear: both;'></div>\n";
				echo "		</div>\n";

				echo "	</td>\n";
				echo "</tr>\n";

				echo "<tr class='list-header'>\n";
				$col_count = 0;
				if (permission_exists('voicemail_message_delete')) {
					echo "	<th class='checkbox'>\n";
					echo "		<input type='checkbox' id='checkbox_all_".$field['voicemail_id']."' name='checkbox_all' onclick=\"list_all_toggle('".$field['voicemail_id']."');\" ".(is_array($field['messages']) && @sizeof($field['messages']) > 0 ?: "style='visibility: hidden;'").">\n";
					echo "	</th>\n";
					$col_count++;
				}
				echo th_order_by('created_epoch', $text['label-received'], $order_by, $order, null, "class='pct-30'");
				$col_count++;
				echo th_order_by('caller_id_name', $text['label-caller_id_name'], $order_by, $order, null, "class='pct-20'");
				$col_count++;
				echo th_order_by('caller_id_number', $text['label-caller_id_number'], $order_by, $order, null, "class='hide-xs pct-15'");
				$col_count++;
				echo "<th class='center shrink'>".$text['label-tools']."</th>\n";
				$col_count++;
				echo th_order_by('message_length', $text['label-message_length'], $order_by, $order, null, "class='hide-xs right pct-15'");
				$col_count++;
				if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
					echo "<th class='right pct-15 hide-sm-dn'>".$text['label-message_size']."</th>\n";
					$col_count++;
				}
				echo "</tr>\n";
			}

			if (is_array($field['messages']) && @sizeof($field['messages']) > 0) {
				foreach ($field['messages'] as $row) {
					//responsive date
					$array = explode(' ', $row['created_date']);
					if ($array[0].' '.$array[1].' '.$array[2] == date('j M Y')) { //today
						$created_date = escape($array[3].' '.$array[4]); //only show time
					}
					else {
						$created_date = escape($array[0].' '.$array[1].' '.$array[2])." <span class='hide-xs' title=\"".escape($array[3].' '.$array[4])."\">".escape($array[3].' '.$array[4])."</span>";
					}

					//playback progress bar
					echo "<tr class='list-row' id='recording_progress_bar_".escape($row['voicemail_message_uuid'])."' style='display: none;'><td class='playback_progress_bar_background' style='padding: 0; border: none;' colspan='".$col_count."'><span class='playback_progress_bar' id='recording_progress_".escape($row['voicemail_message_uuid'])."'></span></td></tr>\n";
					echo "<tr style='display: none;'><td></td></tr>\n"; // dummy row to maintain alternating background color

					$bold = ($row['message_status'] == '' && $_REQUEST["uuid"] != $row['voicemail_message_uuid']) ? 'font-weight: bold;' : null;
					$list_row_url = "javascript:recording_play('".escape($row['voicemail_message_uuid'])."');";
					echo "<tr class='list-row' href=\"".$list_row_url."\">\n";
					echo "	<td class='checkbox'>\n";
					echo "		<input type='checkbox' name='voicemail_messages[$x][checked]' id='checkbox_".$x."' class='checkbox_".$field['voicemail_id']."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all_".$field['voicemail_id']."').checked = false; }\">\n";
					echo "		<input type='hidden' name='voicemail_messages[$x][uuid]' value='".escape($row['voicemail_message_uuid'])."' />\n";
					echo "		<input type='hidden' name='voicemail_messages[$x][voicemail_uuid]' value='".escape($row['voicemail_uuid'])."' />\n";
					echo "	</td>\n";
					echo "	<td class='no-wrap' style='".$bold."'>".$created_date."</td>\n";
					echo "	<td class='overflow' style='".$bold."'>".escape($row['caller_id_name'])."&nbsp;</td>\n";
					echo "	<td class='hide-xs' style='".$bold."'>".escape($row['caller_id_number'])."&nbsp;</td>\n";
					echo "	<td class='button center no-link no-wrap'>";
					echo 		"<audio id='recording_audio_".escape($row['voicemail_message_uuid'])."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".escape($row['voicemail_message_uuid'])."')\" onended=\"recording_reset('".escape($row['voicemail_message_uuid'])."');\" src='voicemail_messages.php?action=download&id=".urlencode($row['voicemail_id'])."&voicemail_uuid=".urlencode($row['voicemail_uuid'])."&uuid=".urlencode($row['voicemail_message_uuid'])."&r=".uuid()."'></audio>";
					echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.escape($row['voicemail_message_uuid']),'onclick'=>"recording_play('".escape($row['voicemail_message_uuid'])."');"]);
					echo button::create(['type'=>'button','title'=>$text['label-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'link'=>"voicemail_messages.php?action=download&id=".urlencode($row['voicemail_id'])."&voicemail_uuid=".escape($row['voicemail_uuid'])."&uuid=".escape($row['voicemail_message_uuid'])."&t=bin&r=".uuid(),'onclick'=>"$(this).closest('tr').children('td').css('font-weight','normal');"]);
					if ($_SESSION['voicemail']['transcribe_enabled']['boolean'] == 'true' && $row['message_transcription'] != '') {
						echo button::create(['type'=>'button','title'=>$text['label-transcription'],'icon'=>'quote-right','onclick'=>"document.getElementById('transcription_".$row['voicemail_message_uuid']."').style.display = document.getElementById('transcription_".$row['voicemail_message_uuid']."').style.display == 'none' ? 'table-row' : 'none'; this.blur(); return false;"]);
					}
					echo "	</td>\n";
					echo "	<td class='right no-wrap hide-xs' style='".$bold."'>".escape($row['message_length_label'])."</td>\n";
					if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
						echo "	<td class='right no-wrap hide-sm-dn' style='".$bold."'>".escape($row['file_size_label'])."</td>\n";
					}
					echo "</tr>\n";
					if ($_SESSION['voicemail']['transcribe_enabled']['boolean'] == 'true' && $row['message_transcription'] != '') {
						echo "<tr style='display: none;'><td></td></tr>\n"; // dummy row to maintain same background color for transcription row
						echo "<tr id='transcription_".$row['voicemail_message_uuid']."' class='list-row' style='display: none;'>\n";
						echo "	<td style='padding: 10px 20px 15px 20px;' colspan='".$col_count."'>\n";
						echo "		<strong style='display: inline-block; font-size: 90%; margin-bottom: 10px;'>".$text['label-transcription']."...</strong><br />\n";
						echo 		escape($row['message_transcription'])."\n";
						echo "	</td>\n";
						echo "</tr>\n";
					}
					$x++;
				}
				unset($row);
			}
			else {
				echo "<tr><td colspan='20' style='text-align: center; font-size: 90%; padding-top: 10px;'>".$text['message-messages_not_found']."<br /></td></tr>";
			}

			$previous_voicemail_id = $field['voicemail_id'];
		}
		echo "</table>\n";
		echo "<br />\n";
		echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
		echo "</form>\n";

	}
	else {
		echo "<br />".$text['message-messages_not_found']."<br /><br />";
	}
	echo "<br />";

//autoplay message
	if ($_REQUEST["action"] == "autoplay" && is_uuid($_REQUEST["uuid"])) {
		echo "<script>recording_play('".$_REQUEST["uuid"]."');</script>";
	}

//unbold new message rows when clicked/played/downloaded
	echo "<script>\n";
	echo "	$(document).ready(function() {\n";
	echo "		$('.list-row').each(function(i,e) {\n";
	echo "			$(e).children('td:not(.checkbox,.no-link)').on('click',function() {\n";
	echo "				$(this).closest('tr').children('td').css('font-weight','normal');\n";
	echo "			});\n";
	echo "			$(e).children('td').children('button').on('click',function() {\n";
	echo "				$(this).closest('tr').children('td').css('font-weight','normal');\n";
	echo "			});\n";
	echo "		});\n";
	echo "	});\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>