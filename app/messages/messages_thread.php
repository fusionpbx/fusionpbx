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
	Portions created by the Initial Developer are Copyright (C) 2016-2018
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

//set refresh flag
	$refresh = $_GET['refresh'] == 'true' ? true : false;

//get from messages
	$since = date("Y-m-d H:i:s", strtotime("-24 hours"));
	$sql = "select * from v_messages ";
	$sql .= "where user_uuid = '".$_SESSION['user_uuid']."' ";
	$sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
	//$sql .= "and message_date >= '".$since."' ";
	$sql .= "and (message_from like '%".$number."' or message_to like '%".$number."') ";
	$sql .= "order by message_date asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$messages = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

//css styles
	echo "<style>\n";
	echo "	.message-bubble {\n";
	echo "		display: block;\n";
	echo "		padding: 10px;\n";
	echo "		border: 1px solid;\n";
	echo "		margin-bottom: 10px;\n";
	echo "	}\n";

	echo "	.message-bubble-em {\n";
	echo "		margin-right: 10%;\n";
	echo "		border-radius: 0px 20px 20px 20px;\n";
	echo "		border-color: #cffec7;\n";
	echo "		background-color: #ecffe9;\n";
	echo "	}\n";

	echo "	.message-bubble-me {\n";
	echo "		margin-left: 10%;\n";
	echo "		border-radius: 20px 20px 0px 20px;\n";
	echo "		border-color: #cbf0ff;\n";
	echo "		background-color: #e5f7ff;\n";
	echo "	}\n";

	echo "	.message-bubble-when {\n";
	echo "		font-size: 65%;\n";
	echo "		line-height: 60%;\n";
	echo "	}\n";
	echo "</style>\n";

	if (!$refresh) {
		echo "<div id='thread_messages' style='max-height: 400px; overflow: auto;'>\n";
	}

	//output messages
		if (is_array($messages) && sizeof($messages) != 0) {
			foreach ($messages as $message) {
				echo "<span class='message-bubble message-bubble-".($message['message_direction'] == 'inbound' ? 'em' : 'me')."'>";
				echo str_replace("\n",'<br />',$message['message_text'])."<br />\n";
				echo "<span class='message-bubble-when'>".format_when_local($message['message_date'])."</span>\n";
				echo "</span>\n";
				//parse from inbound message
				if ($message['message_direction'] == 'inbound') {
					$message_from = $message['message_to'];
				}
			}
			echo "	<span id='thread_bottom'></span>\n";
		}

	if (!$refresh) {
		echo "</div>\n";

		if (permission_exists('message_add')) {
			//output input form
			echo "<form id='message_compose'>\n";
			echo "<input type='hidden' name='message_type' value='sms'>\n";
			echo "<input type='hidden' name='message_from' value='".$message_from."'>\n";
			echo "<input type='hidden' name='message_to' value='".$number."'>\n";
			echo "<textarea class='formfld' id='message_text' name='message_text' style='width: 100%; max-width: 100%; height: 40px; border: 1px solid #cbcbcb; resize: vertical; padding: 5px 8px; margin-top: 10px;' placeholder=\"".$text['description-enter_response']."\"></textarea>";
			echo "<span style='position: relative;'>\n";
			echo "	<center>\n";
			echo "		<input type='reset' class='btn' style='float: left; margin-top: 15px;' value='".$text['button-clear']."' onclick=\"$('#message_text').focus();\">\n";
			echo "		<span id='thread_refresh_state'><img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' onclick=\"refresh_thread_stop('".$number."');\" alt=\"".$text['label-refresh_pause']."\" title=\"".$text['label-refresh_pause']."\"></span> ";
			echo "		<input type='submit' class='btn' style='float: right; margin-top: 15px;' value='".$text['button-send']."' title=\"".$text['label-ctrl_enter']."\">\n";
			echo "	</center>\n";
			echo "</span>\n";
			echo "</form>\n";

		//js to load messages for clicked number
			echo "<script>\n";
			//define form submit function
			echo "	$('#message_compose').submit(function(event) {\n";
			echo "		event.preventDefault();\n";
			echo "		$.ajax({\n";
			echo "			url: 'message_send.php',\n";
			echo "			type: 'POST',\n";
			echo "			data: $('#message_compose').serialize(),\n";
			echo "			success: function(){\n";
			echo "					document.getElementById('message_compose').reset();\n";
			echo "					if ($('#message_new_layer').is(':hidden')) {\n";
			echo "						$('#message_text').focus();\n";
			echo "					}\n";
			echo "					refresh_thread('".$number."','true');\n";
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