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
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/paging.php";

//download the message
	if (
		!empty($_REQUEST["action"]) && $_REQUEST["action"] == "download"
		&& !empty($_REQUEST["id"]) && is_numeric($_REQUEST["id"])
		&& !empty($_REQUEST["uuid"]) && is_uuid($_REQUEST["uuid"])
		&& !empty($_REQUEST["voicemail_uuid"]) && is_uuid($_REQUEST["voicemail_uuid"])
		) {
		//set domain uuid and domain name from session, if defined
		if (!empty($_SESSION['domain_uuid']) && is_uuid($_SESSION['domain_uuid']) && !empty($_SESSION['domain_name'])) {
			$domain_uuid = $_SESSION['domain_uuid'];
			$domain_name = $_SESSION['domain_name'];
		}
		//session not available (due to direct vm download using emailed link, or otherwise), set domain uuid and name from database
		else {
			$sql = "select d.domain_uuid, d.domain_name ";
			$sql .= "from v_voicemail_messages as vm ";
			$sql .= "left join v_domains as d on vm.domain_uuid = d.domain_uuid ";
			$sql .= "where vm.voicemail_message_uuid = :voicemail_message_uuid ";
			$sql .= "and vm.voicemail_uuid = :voicemail_uuid ";
			$sql .= "and vm.domain_uuid = d.domain_uuid ";
			$parameters['voicemail_message_uuid'] = $_REQUEST["uuid"];
			$parameters['voicemail_uuid'] = $_REQUEST["voicemail_uuid"];
			$database = new database;
			$result = $database->select($sql, $parameters, 'row');
			if ($result !== false) {
				$domain_uuid = $result['domain_uuid'];
				$domain_name = $result['domain_name'];
			}
		}
		//load settings
		$settings = new settings(['domain_uuid'=>$domain_uuid]);

		$voicemail = new voicemail(['settings'=>$settings]);
		$voicemail->domain_uuid = $domain_uuid;
		$voicemail->type = $_REQUEST['t'] ?? null;
		$voicemail->voicemail_id = $_REQUEST['id'];
		$voicemail->voicemail_uuid = $_REQUEST['voicemail_uuid'];
		$voicemail->voicemail_message_uuid = $_REQUEST['uuid'];
		if (isset($_REQUEST['intro'])) {
			if (!$voicemail->message_intro_download($domain_name)) {
				echo "unable to download voicemail intro";
			}
		}
		else {
			if (!$voicemail->message_download($domain_name)) {
				echo "unable to download voicemail";
			}
		}
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

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);
	$transcribe_enabled = $settings->get('transcribe', 'enabled', false);
	$transcribe_engine = $settings->get('transcribe', 'engine', '');

//set the back button url
	$_SESSION['back'][$_SERVER['PHP_SELF']] = !empty($_GET['back']) ? urldecode($_GET['back']) : ($_SESSION['back'][$_SERVER['PHP_SELF']] ?? PROJECT_PATH.'/app/voicemails/voicemails.php');

//set the voicemail_uuid
	if (!empty($_REQUEST['id']) && is_uuid($_REQUEST['id'])) {
		$voicemail_uuid = $_REQUEST['id'];
	}
	else if (!empty($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$voicemail_id = $_REQUEST['id'];
	}

//get the http post data
	if (!empty($_POST['voicemail_messages'])) {
		$action = $_POST['action'];
		$voicemail_messages = $_POST['voicemail_messages'];
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//process the http post data by action
	if (!empty($action) && !empty($voicemail_messages)) {

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
				case 'mark_saved':
					//transcribe voicemail message
						$voicemail = new voicemail;
						$voicemail->domain_uuid = $_SESSION['domain_uuid'];
						$voicemail->voicemail_uuid = $voicemail_messages[0]['voicemail_uuid'];
						$voicemail->voicemail_message_uuid = $voicemail_messages[0]['uuid'];
						$voicemail->message_saved();
					// no return, exit
					exit;
				case 'transcribe':
					if (permission_exists('voicemail_message_transcribe') && $transcribe_enabled && !empty($transcribe_engine) && is_array($voicemail_messages) && @sizeof($voicemail_messages) != 0) {
						$messages_transcribed = 0;
						foreach ($voicemail_messages as $voicemail_message) {
							if (!empty($voicemail_message['checked']) && $voicemail_message['checked'] == 'true' && is_uuid($voicemail_message['uuid']) && is_uuid($voicemail_message['voicemail_uuid'])) {
								//transcribe voicemail message
									$voicemail = new voicemail;
									$voicemail->domain_uuid = $_SESSION['domain_uuid'];
									$voicemail->voicemail_uuid = $voicemail_message['voicemail_uuid'];
									$voicemail->voicemail_message_uuid = $voicemail_message['uuid'];
									$result = $voicemail->message_transcribe();
									unset($voicemail);
								//increment counter
									if ($result == true) {
										$messages_transcribed++;
									}
							}
						}
						//set message
							if ($messages_transcribed != 0) {
								message::add($text['message-audio_transcribed'].': '.$messages_transcribed);
							}
					}
					break;
				case 'toggle':
					if (is_array($voicemail_messages) && @sizeof($voicemail_messages) != 0) {
						$messages_toggled = 0;
						foreach ($voicemail_messages as $voicemail_message) {
							if (!empty($voicemail_message['checked']) && $voicemail_message['checked'] == 'true' && is_uuid($voicemail_message['uuid']) && is_uuid($voicemail_message['voicemail_uuid'])) {
								//toggle voicemail message
									$voicemail = new voicemail;
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
				case 'resend':
					if (is_array($voicemail_messages) && @sizeof($voicemail_messages) != 0) {
						$messages_resent = 0;
						foreach ($voicemail_messages as $voicemail_message) {
							if (!empty($voicemail_message['checked']) && $voicemail_message['checked'] == 'true' && is_uuid($voicemail_message['uuid']) && is_uuid($voicemail_message['voicemail_uuid'])) {
								//resend (email) voicemail message
									$voicemail = new voicemail;
									$voicemail->domain_uuid = $_SESSION['domain_uuid'];
									$voicemail->voicemail_uuid = $voicemail_message['voicemail_uuid'];
									$voicemail->voicemail_message_uuid = $voicemail_message['uuid'];
									$voicemail->message_resend();
									unset($voicemail);
								//increment counter
									$messages_resent++;
							}
						}
						//set message
							if ($messages_resent != 0) {
								message::add($text['message-emails_resent'].': '.$messages_resent);
							}
					}
					break;
				case 'delete':
					if (permission_exists('voicemail_message_delete')) {
						if (is_array($voicemail_messages) && @sizeof($voicemail_messages) != 0) {
							$messages_deleted = 0;
							foreach ($voicemail_messages as $voicemail_message) {
								if (!empty($voicemail_message['checked']) && $voicemail_message['checked'] == 'true' && is_uuid($voicemail_message['uuid']) && is_uuid($voicemail_message['voicemail_uuid'])) {
									//delete voicemail message
										$voicemail = new voicemail;
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

//get the html values and set them as variables
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//prepare to page the results
	$vm = new voicemail;
	$vm->domain_uuid = $_SESSION['domain_uuid'];
	if (!empty($voicemail_uuid) && is_uuid($voicemail_uuid)) {
		$vm->voicemail_uuid = $voicemail_uuid;
	}
	else if (!empty($voicemail_id) && is_numeric($voicemail_id)) {
		$vm->voicemail_id = $voicemail_id;
	}
	$voicemails = $vm->messages();

	$num_rows = 0;
	if (!empty($voicemails) && is_array($voicemails)) {
		foreach ($voicemails as $voicemail) {
			if (!empty($voicemail['messages']) && is_array($voicemail['messages'])) {
				$num_rows += @sizeof($voicemail['messages']);
			}
		}
	}
	$total_rows = $num_rows;

//prepare to page the results
	$rows_per_page = $_SESSION['domain']['paging']['numeric'] != '' ? $_SESSION['domain']['paging']['numeric'] : 50;
	$page = empty($_GET['page']) ? 0 : $_GET['page'];
	$param = 'id='.urlencode($_REQUEST['id']).'&back='.$_SESSION['back'][$_SERVER['PHP_SELF']];
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;
	unset($num_rows);

//get the voicemail
	$vm->order_by = $order_by;
	$vm->order = $order;
	$vm->offset = $offset;

//count messages and detect if any transcriptions available
	$new_messages = $num_rows = 0;
	$transcriptions_exists = false;
	if (!empty($voicemails) && is_array($voicemails)) {
		foreach ($voicemails as $voicemail) {
			if (!empty($voicemail['messages']) && is_array($voicemail['messages'])) {
				$num_rows += sizeof($voicemail['messages']);
				foreach ($voicemail['messages'] as $message) {
					if ($message['message_status'] != 'saved') {
						$new_messages++;
					}
					if ($transcribe_enabled && !empty($transcribe_engine) && !empty($message['message_transcription'])) {
						$transcriptions_exists = true;
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
	echo "	<div class='heading'><b>".$text['title-voicemail_messages']."</b><div class='count'>".number_format($total_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','link'=>$_SESSION['back'][$_SERVER['PHP_SELF']]]);
	$margin_left = false;
	if (permission_exists('voicemail_message_transcribe') && $transcribe_enabled && !empty($transcribe_engine) && $num_rows) {
		echo button::create(['type'=>'button','label'=>$text['button-transcribe'],'icon'=>'quote-right','id'=>'btn_transcribe','name'=>'btn_transcribe','collapse'=>'hide-xs','style'=>'display: none; margin-left: 15px;','onclick'=>"list_action_set('transcribe'); list_form_submit('form_list');"]);
		$margin_left = true;
	}
	if ($num_rows) {
		echo button::create(['type'=>'button','label'=>$text['button-resend'],'icon'=>$settings->get('theme', 'button_icon_email'),'id'=>'btn_resend','name'=>'btn_resend','collapse'=>'hide-xs','style'=>'display: none;'.(!$margin_left ? 'margin-left: 15px;' : null),'onclick'=>"modal_open('modal-resend','btn_resend');"]);
		$margin_left = true;
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','collapse'=>'hide-xs','style'=>'display: none;'.(!$margin_left ? 'margin-left: 15px;' : null),'onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
		$margin_left = true;
	}
	if (permission_exists('voicemail_message_delete') && $num_rows) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','collapse'=>'hide-xs','style'=>'display: none;'.(!$margin_left ? 'margin-left: 15px;' : null),'onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if (!empty($paging_controls_mini)) {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($num_rows) {
		echo modal::create([
			'id'=>'modal-resend',
			'title'=>$text['modal_title-resend'],
			'message'=>$text['modal_message-resend'],
			'actions'=>
				button::create(['type'=>'button','label'=>$text['button-cancel'],'icon'=>$settings->get('theme', 'button_icon_cancel'),'collapse'=>'hide-xs','onclick'=>'modal_close();']).
				button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','collapse'=>'never','style'=>'float: right;','onclick'=>"modal_close(); list_action_set('resend'); list_form_submit('form_list');"])
			]);
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

		echo "<div class='card'>\n";
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
					echo "		<input type='checkbox' id='checkbox_all_".$field['voicemail_id']."' name='checkbox_all' onclick=\"list_all_toggle('".$field['voicemail_id']."'); checkbox_on_change(this);\" ".(is_array($field['messages']) && @sizeof($field['messages']) > 0 ?: "style='visibility: hidden;'").">\n";
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
				if (empty($_SESSION['voicemail']['storage_type']['text']) || $_SESSION['voicemail']['storage_type']['text'] != 'base64') {
					echo "<th class='right pct-15 hide-sm-dn'>".$text['label-message_size']."</th>\n";
					$col_count++;
				}
				echo "</tr>\n";
			}

			if (is_array($field['messages']) && @sizeof($field['messages']) > 0) {
				foreach ($field['messages'] as $row) {
					//set voicemail messages as bold if unread and normal font weight if read
					$bold = (empty($row['message_status'])) ? 'font-weight: bold;' : null;

					//set the list row url as a variable
					$list_row_url = "javascript:recording_play('".escape($row['voicemail_message_uuid'])."','".$row['voicemail_id'].'|'.$row['voicemail_uuid']."','message');";

					//playback progress bar
					echo "<tr class='list-row' id='recording_progress_bar_".escape($row['voicemail_message_uuid'])."' style='display: none;' onclick=\"recording_seek(event,'".escape($row['voicemail_message_uuid'])."')\"><td id='playback_progress_bar_background_".escape($row['voicemail_message_uuid'])."' class='playback_progress_bar_background' style='padding: 0; border: none;' colspan='".$col_count."'><span class='playback_progress_bar' id='recording_progress_".escape($row['voicemail_message_uuid'])."'></span></td></tr>\n";
					echo "<tr style='display: none;'><td></td></tr>\n"; // dummy row to maintain alternating background color

					echo "<tr class='list-row' href=\"".$list_row_url."\">\n";
					echo "	<td class='checkbox'>\n";
					echo "		<input type='checkbox' name='voicemail_messages[$x][checked]' id='checkbox_".$x."' class='checkbox_".$field['voicemail_id']."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all_".$field['voicemail_id']."').checked = false; } checkbox_on_change(this);\">\n";
					echo "		<input type='hidden' name='voicemail_messages[$x][uuid]' value='".escape($row['voicemail_message_uuid'])."' />\n";
					echo "		<input type='hidden' name='voicemail_messages[$x][voicemail_uuid]' value='".escape($row['voicemail_uuid'])."' />\n";
					echo "	</td>\n";
					echo "	<td class='no-wrap' style='".$bold."'>".escape($row['created_date_formatted'])." ".escape($row['created_time_formatted'])."</td>\n";
					echo "	<td class='overflow' style='".$bold."'>".escape($row['caller_id_name'])."&nbsp;</td>\n";
					echo "	<td class='hide-xs' style='".$bold."'>".escape($row['caller_id_number'])."&nbsp;</td>\n";
					echo "	<td class='button center no-link no-wrap'>";
					echo 		"<audio id='recording_audio_".escape($row['voicemail_message_uuid'])."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".escape($row['voicemail_message_uuid'])."')\" onended=\"recording_reset('".escape($row['voicemail_message_uuid'])."');\" src='voicemail_messages.php?action=download&id=".urlencode($row['voicemail_id'])."&voicemail_uuid=".urlencode($row['voicemail_uuid'])."&uuid=".urlencode($row['voicemail_message_uuid'])."&r=".uuid()."'></audio>";
					if (
						($_SESSION['voicemail']['storage_type']['text'] == 'base64' && !empty($row['message_intro_base64'])) ||
						file_exists($_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domain_name'].'/'.$field['voicemail_id'].'/intro_'.$row['voicemail_message_uuid'].'.wav') ||
						file_exists($_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domain_name'].'/'.$field['voicemail_id'].'/intro_'.$row['voicemail_message_uuid'].'.mp3')
						) {
						echo 	"<audio id='recording_audio_intro_".escape($row['voicemail_message_uuid'])."' style='display: none;' preload='none' onended=\"recording_reset('intro_".escape($row['voicemail_message_uuid'])."');\" src='voicemail_messages.php?action=download&id=".urlencode($row['voicemail_id'])."&voicemail_uuid=".urlencode($row['voicemail_uuid'])."&uuid=".urlencode($row['voicemail_message_uuid'])."&intro&r=".uuid()."'></audio>";
						echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'].' '.$text['label-introduction'],'icon'=>$settings->get('theme', 'button_icon_comment'),'id'=>'recording_button_intro_'.escape($row['voicemail_message_uuid']),'onclick'=>"recording_play('intro_".escape($row['voicemail_message_uuid'])."','".$row['voicemail_id'].'|'.$row['voicemail_uuid']."','message_intro');"]);
					}
					echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'].' '.$text['label-message'],'icon'=>$settings->get('theme', 'button_icon_play'),'id'=>'recording_button_'.escape($row['voicemail_message_uuid']),'onclick'=>"recording_play('".escape($row['voicemail_message_uuid'])."','".$row['voicemail_id'].'|'.$row['voicemail_uuid']."','message');"]);
					echo button::create(['type'=>'button','title'=>$text['label-download'],'icon'=>$settings->get('theme', 'button_icon_download'),'link'=>"voicemail_messages.php?action=download&id=".urlencode($row['voicemail_id'])."&voicemail_uuid=".escape($row['voicemail_uuid'])."&uuid=".escape($row['voicemail_message_uuid'])."&t=bin&r=".uuid(),'onclick'=>"$(this).closest('tr').children('td').css('font-weight','normal');"]);
					if (!empty($row['message_transcription']) || ($transcribe_enabled && !empty($transcribe_engine) && $transcriptions_exists === true)) {
						echo button::create(['type'=>'button','title'=>$text['label-transcription'],'icon'=>'quote-right','style'=>(empty($row['message_transcription']) ? 'visibility:hidden;' : null),'onclick'=>(!empty($bold) ? "mark_saved('".$row['voicemail_message_uuid']."', '".$row['voicemail_uuid']."');" : null)."document.getElementById('transcription_".$row['voicemail_message_uuid']."').style.display = document.getElementById('transcription_".$row['voicemail_message_uuid']."').style.display == 'none' ? 'table-row' : 'none'; this.blur(); return false;"]);
					}
					echo "	</td>\n";
					echo "	<td class='right no-wrap hide-xs' style='".$bold."'>".escape($row['message_length_label'])."</td>\n";
					if (empty($_SESSION['voicemail']['storage_type']['text']) || $_SESSION['voicemail']['storage_type']['text'] != 'base64') {
						echo "	<td class='right no-wrap hide-sm-dn' style='".$bold."'>".escape($row['file_size_label'])."</td>\n";
					}
					echo "</tr>\n";
					if (!empty($row['message_transcription']) || ($transcribe_enabled && !empty($transcribe_engine) && $transcriptions_exists === true)) {
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
		echo "</div>\n";
		echo "<br />\n";
		echo "<div align='center'>".$paging_controls."</div>\n";
		echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
		echo "</form>\n";

	}
	else {
		echo "<br />".$text['message-messages_not_found']."<br /><br />";
	}
	echo "<br />";

//autoplay message
	if (!empty($_REQUEST["action"]) && $_REQUEST["action"] == "autoplay" && !empty($_REQUEST["uuid"]) && is_uuid($_REQUEST["uuid"])) {
		echo "<script>recording_play('".$_REQUEST["uuid"]."','".$_REQUEST['vm']."|".$_REQUEST['id']."','message');</script>";
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

//if viewing transcription, mark message as read/saved
	if ($transcriptions_exists) {
		echo "<script>\n";
		echo "	function mark_saved(voicemail_message_uuid, voicemail_uuid) {\n";
		echo "		const url = '".basename($_SERVER['PHP_SELF'])."';\n";
		echo "		const params = { action: 'mark_saved', voicemail_messages: { 0: { uuid: voicemail_message_uuid, voicemail_uuid: voicemail_uuid, }, }, '".$token['name']."': '".$token['hash']."', };\n";
		echo "		$.post(url, params);\n";
		echo "	}\n";
		echo "</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>
