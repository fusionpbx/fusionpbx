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
	Portions created by the Initial Developer are Copyright (C) 2016-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('message_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get number of messages to load
	$number = preg_replace('{[\D]}', '', $_GET['number']);
	$contact_uuid = (is_uuid($_GET['contact_uuid'])) ? $_GET['contact_uuid'] : null;

//set refresh flag
	$refresh = $_GET['refresh'] == 'true' ? true : false;

//get messages
	if (isset($_SESSION['message']['display_last']['text']) && $_SESSION['message']['display_last']['text'] != '') {
		$array = explode(' ',$_SESSION['message']['display_last']['text']);
		if (is_array($array) && is_numeric($array[0]) && $array[0] > 0) {
			if ($array[1] == 'messages') {
				$limit = limit_offset($array[0], 0);
			}
			else {
				$since = "and message_date >= :message_date ";
				$parameters['message_date'] = date("Y-m-d H:i:s", strtotime('-'.$_SESSION['message']['display_last']['text']));
			}
		}
	}
	if ($limit == '' && $since == '') { $limit = limit_offset(25, 0); } //default (message count)
	$sql = "select ";
	$sql .= "message_uuid, ";
	$sql .= "domain_uuid, ";
	$sql .= "user_uuid, ";
	$sql .= "contact_uuid, ";
	$sql .= "message_type, ";
	$sql .= "message_direction, ";
	if ($_SESSION['domain']['time_zone']['name'] != '') {
		$sql .= "message_date at time zone :time_zone as message_date, ";
	}
	else {
		$sql .= "message_date, ";
	}
	$sql .= "message_from, ";
	$sql .= "message_to, ";
	$sql .= "message_text ";
	$sql .= "from v_messages ";
	$sql .= "where user_uuid = :user_uuid ";
	$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$sql .= $since;
	$sql .= "and (message_from like :message_number or message_to like :message_number) ";
	$sql .= "order by message_date desc ";
	$sql .= $limit;
	if ($_SESSION['domain']['time_zone']['name'] != '') {
		$parameters['time_zone'] = $_SESSION['domain']['time_zone']['name'];
	}
	$parameters['user_uuid'] = $_SESSION['user_uuid'];
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['message_number'] = '%'.$number;
	$database = new database;
	$messages = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	if (is_array($messages) && @sizeof($messages) != 0) {
		$messages = array_reverse($messages);

		//get media (if any)
			$sql = "select ";
			$sql .= "message_uuid, ";
			$sql .= "message_media_uuid, ";
			$sql .= "message_media_type, ";
			$sql .= "length(decode(message_media_content,'base64')) as message_media_size ";
			$sql .= "from v_message_media ";
			$sql .= "where user_uuid = :user_uuid ";
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$sql .= "and ( ";
			foreach ($messages as $index => $message) {
				$message_uuids[] = "message_uuid = :message_uuid_".$index;
				$parameters['message_uuid_'.$index] = $message['message_uuid'];
			}
			$sql .= implode(' or ', $message_uuids);
			$sql .= ") ";
			$sql .= "and message_media_type <> 'txt' ";
			$parameters['user_uuid'] = $_SESSION['user_uuid'];
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$rows = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters, $index);

		//prep media array
			if (is_array($rows) && @sizeof($rows) != 0) {
				foreach ($rows as $index => $row) {
					$message_media[$row['message_uuid']][$index]['uuid'] = $row['message_media_uuid'];
					$message_media[$row['message_uuid']][$index]['type'] = $row['message_media_type'];
					$message_media[$row['message_uuid']][$index]['size'] = $row['message_media_size'];
				}
			}
	}

//css styles
	echo "<style>\n";
	echo "	.message-bubble {\n";
	echo "		display: table;\n";
	echo "		padding: 10px;\n";
	echo "		border: 1px solid;\n";
	echo "		margin-bottom: 10px;\n";
	echo "		}\n";

	echo "	.message-bubble-em {\n";
	echo "		margin-right: 30%;\n";
	echo "		border-radius: 0 20px 20px 20px;\n";
	echo "		border-color: #cffec7;\n";
	echo "		background-color: #ecffe9;\n";
	echo "		clear: both;\n";
	echo "		}\n";

	echo "	.message-bubble-me {\n";
	echo "		float: right;\n";
	echo "		margin-left: 30%;\n";
	echo "		border-radius: 20px 20px 0 20px;\n";
	echo "		border-color: #cbf0ff;\n";
	echo "		background-color: #e5f7ff;\n";
	echo "		clear: both;\n";
	echo "		}\n";

	echo "	img.message-bubble-image-em {\n";
	echo "		width: 100px;\n";
	echo "		height: auto;\n";
	echo "		border-radius: 0 11px 11px 11px;\n";
	echo "		border: 1px solid #cffec7;\n";
	echo "		}\n";

	echo "	img.message-bubble-image-me {\n";
	echo "		width: 100px;\n";
	echo "		height: auto;\n";
	echo "		border-radius: 11px 11px 0 11px;\n";
	echo "		border: 1px solid #cbf0ff;\n";
	echo "		}\n";

	echo "	div.message-bubble-image-em {\n";
	echo "		float: left;\n";
	echo "		margin-right: 15px;\n";
	echo "		text-align: left;\n";
	echo "		}\n";

	echo "	div.message-bubble-image-me {\n";
	echo "		float: right;\n";
	echo "		margin-left: 15px;\n";
	echo "		text-align: right;\n";
	echo "		}\n";

	echo "	.message-text {\n";
	echo "		padding-bottom: 5px;\n";
	echo "		font-size: 90%;\n";
	echo "		}\n";

	echo "	.message-bubble-when {\n";
	echo "		font-size: 71%;\n";
	echo "		font-style: italic;\n";
	echo "		}\n";

	echo "	.message-media-link-em {\n";
	echo "		display: inline-block;\n";
	echo "		margin: 5px 10px 5px 0;\n";
	echo "		padding: 8px;\n";
	echo "		background: #cffec7;\n";
	echo "		border-radius: 7px;\n";
	echo "		text-align: center;\n";
	echo "		}\n";

	echo "	.message-media-link-me {\n";
	echo "		display: inline-block;\n";
	echo "		margin: 5px 10px 5px 0;\n";
	echo "		padding: 8px;\n";
	echo "		background: #cbf0ff;\n";
	echo "		border-radius: 7px;\n";
	echo "		text-align: center;\n";
	echo "		}\n";

	echo "</style>\n";

	if (!$refresh) {
		echo "<div id='thread_messages' style='min-height: 300px; overflow: auto; padding-right: 15px;'>\n";
	}

	//output messages
		if (is_array($messages) && @sizeof($messages) != 0) {
			foreach ($messages as $message) {
				//parse from message
				if ($message['message_direction'] == 'inbound') {
					$message_from = $message['message_to'];
					$media_source = format_phone($message['message_from']);
				}
				if ($message['message_direction'] == 'outbound') {
					$message_from = $message['message_from'];
					$media_source = format_phone($message['message_to']);
				}

				//message bubble
					echo "<span class='message-bubble message-bubble-".($message['message_direction'] == 'inbound' ? 'em' : 'me')."'>";
						//contact image em
							if ($message['message_direction'] == 'inbound') {
								if (is_array($_SESSION['tmp']['messages']['contact_em'][$contact_uuid]) && @sizeof($_SESSION['tmp']['messages']['contact_em'][$contact_uuid]) != 0) {
									echo "<div class='message-bubble-image-em'>\n";
									echo "	<img class='message-bubble-image-em'><br />\n";
									echo "</div>\n";
								}
							}
						//contact image me
							else {
								if (is_array($_SESSION['tmp']['messages']['contact_me']) && @sizeof($_SESSION['tmp']['messages']['contact_me']) != 0) {
									echo "<div class='message-bubble-image-me'>\n";
									echo "	<img class='message-bubble-image-me'><br />\n";
									echo "</div>\n";
								}
							}
						echo "<div style='display: table;'>\n";
						//message
							if ($message['message_text'] != '') {
								echo "<div class='message-text'>".str_replace("\n",'<br />',escape($message['message_text']))."</div>\n";
							}
						//attachments
							if (is_array($message_media[$message['message_uuid']]) && @sizeof($message_media[$message['message_uuid']]) != 0) {

								foreach ($message_media[$message['message_uuid']] as $media) {
									if ($media['type'] != 'txt') {
										if ($media['type'] == 'jpg' || $media['type'] == 'jpeg' || $media['type'] == 'gif' || $media['type'] == 'png') {
											echo "<a href='#' onclick=\"display_media('".$media['uuid']."','".$media_source."');\" class='message-media-link-".($message['message_direction'] == 'inbound' ? 'em' : 'me')."'>";
										}
										else {
											echo "<a href='message_media.php?id=".$media['uuid']."&src=".$media_source."&action=download' class='message-media-link-".($message['message_direction'] == 'inbound' ? 'em' : 'me')."'>";
										}
										echo "<img src='resources/images/attachment.png' style='width: 16px; height: 16px; border: none; margin-right: 10px;'>";
										echo "<span style='font-size: 85%; white-space: nowrap;'>".strtoupper($media['type']).' &middot; '.strtoupper(byte_convert($media['size']))."</span>";
										echo "</a>\n";
									}
								}
								echo "<br />\n";
							}
						//message when
							echo "<span class='message-bubble-when'>".(date('m-d-Y') != format_when_local($message['message_date'],'d') ? format_when_local($message['message_date']) : format_when_local($message['message_date'],'t'))."</span>\n";
						echo "</div>\n";
					echo "</span>\n";
			}
			echo "<span id='thread_bottom'></span>\n";
		}

		echo "<script>\n";
		//set current contact
			echo "	$('#contact_current_number').val('".$number."');\n";
		//set bubble contact images from src images
			echo "	$('img.message-bubble-image-em').attr('src', $('img#src_message-bubble-image-em_".$contact_uuid."').attr('src'));\n";
			echo "	$('img.message-bubble-image-me').attr('src', $('img#src_message-bubble-image-me').attr('src'));\n";
		echo "</script>\n";

	if (!$refresh) {
		echo "</div>\n";

		if (permission_exists('message_add')) {
			//output input form
			echo "<form id='message_compose' method='post' enctype='multipart/form-data' action='message_send.php'>\n";
			echo "<input type='hidden' name='message_from' value='".$message_from."'>\n";
			echo "<input type='hidden' name='message_to' value='".$number."'>\n";
			echo "<textarea class='formfld' id='message_text' name='message_text' style='width: 100%; max-width: 100%; min-height: 55px; border: 1px solid #cbcbcb; resize: vertical; padding: 5px 8px; margin-top: 10px; margin-bottom: 5px;' placeholder=\"".$text['description-enter_response']."\"></textarea>";
			echo "<table cellpadding='0' cellspacing='0' border='0' width='100%' style='margin-top: 5px;'>\n";
			echo "	<tr>\n";
			echo "		<td><img src='resources/images/attachment.png' style='min-width: 20px; height: 20px; border: none; padding-right: 5px;'></td>\n";
			echo "		<td width='100%'><input type='file' class='formfld' multiple='multiple' name='message_media[]' id='message_new_media'></td>\n";
			echo "	</td>\n";
			echo "</table>\n";
			echo "<table cellpadding='0' cellspacing='0' border='0' width='100%' style='margin-top: 15px;'>\n";
			echo "	<tr>\n";
			echo "		<td align='left' width='50%'>";
			echo button::create(['label'=>$text['button-clear'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'reset','onclick'=>"$('#message_text').trigger('focus');"]);
			echo "		</td>\n";
			echo "		<td align='center'><span id='thread_refresh_state'><img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; cursor: pointer;' onclick=\"refresh_thread_stop('".$number."','".$contact_uuid."');\" alt=\"".$text['label-refresh_pause']."\" title=\"".$text['label-refresh_pause']."\"></span></td>\n";
			echo "		<td align='right' width='50%'>";
			echo button::create(['type'=>'submit','label'=>$text['button-send'],'title'=>$text['label-ctrl_enter'],'icon'=>'paper-plane']);
			echo "		</td>\n";
			echo "	</td>\n";
			echo "</table>\n";
			echo "</form>\n";

		//js to load messages for clicked number
			echo "<script>\n";
			//define form submit function
			echo "	$('#message_compose').submit(function(event) {\n";
			echo "		event.preventDefault();\n";
			echo "		$.ajax({\n";
			echo "			url: $(this).attr('action'),\n";
			echo "			type: $(this).attr('method'),\n";
			echo "			data: new FormData(this),\n";
			echo "			processData: false,\n";
			echo "			contentType: false,\n";
			echo "			cache: false,\n";
			echo "			success: function(){\n";
			echo "					document.getElementById('message_compose').reset();\n";
			if (!http_user_agent('mobile')) {
				echo "				if ($('#message_new_layer').is(':hidden')) {\n";
				echo "					$('#message_text').trigger('focus');\n";
				echo "				}\n";
			}
			echo "					refresh_thread('".$number."', '".$contact_uuid."', 'true');\n";
			echo "				}\n";
			echo "		});\n";
			echo "	});\n";
			//enable ctrl+enter to send
			echo "	$('#message_text').keydown(function (event) {\n";
			echo "		if ((event.keyCode == 10 || event.keyCode == 13) && event.ctrlKey) {\n";
			echo "			$('#message_compose').submit();\n";
			echo "		}\n";
			echo "	});\n";

			echo "</script>\n";
		}
	}

?>