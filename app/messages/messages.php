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

//get (from) destinations
	$sql = "select destination_number from v_destinations ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "and destination_enabled = 'true' ";
	$sql .= "order by destination_number asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$rows = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//view_array($rows);
	if (is_array($rows) && sizeof($rows)) {
		foreach ($rows as $row) {
			$destinations[] = $row['destination_number'];
		}
	}
	unset ($prep_statement, $sql, $row, $record);

//additional includes
	require_once "resources/header.php";

//styles
	echo "<style>\n";

	echo "	#message_new_layer {\n";
	echo "		z-index: 999999;\n";
	echo "		position: absolute;\n";
	echo "		left: 0px;\n";
	echo "		top: 0px;\n";
	echo "		right: 0px;\n";
	echo "		bottom: 0px;\n";
	echo "		text-align: center;\n";
	echo "		vertical-align: middle;\n";
	echo "	}\n";

	echo "	#message_new_container {\n";
	echo "		display: block;\n";
	echo "		background-color: #fff;\n";
	echo "		padding: 20px 30px;\n";
	if (http_user_agent('mobile')) {
		echo "	margin: 0;\n";
	}
	else {
		echo "	margin: auto 30%;\n";
	}
	echo "		text-align: left;\n";
	echo "		-webkit-box-shadow: 0px 1px 20px #888;\n";
	echo "		-moz-box-shadow: 0px 1px 20px #888;\n";
	echo "		box-shadow: 0px 1px 20px #888;\n";
	echo "	}\n";

	echo "	#message_media_layer {\n";
	echo "		z-index: 999999;\n";
	echo "		position: absolute;\n";
	echo "		left: 0px;\n";
	echo "		top: 0px;\n";
	echo "		right: 0px;\n";
	echo "		bottom: 0px;\n";
	echo "		text-align: center;\n";
	echo "		vertical-align: middle;\n";
	echo "	}\n";

	echo "</style>\n";

//new message layer
	echo "<div id='message_new_layer' style='display: none;'>\n";
	echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>\n";
	echo "		<tr>\n";
	echo "			<td align='center' valign='middle'>\n";
	echo "				<form id='message_new'>\n";
	echo "				<input type='hidden' name='message_type' value='sms'>\n";
	echo "				<span id='message_new_container'>\n";
	echo "					<b>".$text['label-new_message']."</b><br /><br />\n";
	echo "					<table width='100%'>\n";
	echo "						<tr>\n";
	echo "							<td class='vncell'>".$text['label-message_from']."</td>\n";
	echo "							<td class='vtable'>\n";
	if (is_array($destinations) && sizeof($destinations) != 0) {
		echo "							<select class='formfld' name='message_from' id='message_new_from' onchange=\" $('#message_new_to').focus();\">\n";
		foreach ($destinations as $destination) {
			echo "							<option value='".$destination."'>".format_phone($destination)."</option>\n";
		}
		echo "							</select>\n";
	}
	else {
		echo "							<input type='text' class='formfld' name='message_from' id='message_new_from'>\n";
	}
	echo "							</td>\n";
	echo "						</tr>\n";
	echo "						<tr>\n";
	echo "							<td class='vncell'>".$text['label-message_to']."</td>\n";
	echo "							<td class='vtable'>\n";
	echo "								<input type='text' class='formfld' name='message_to' id='message_new_to'>\n";
	echo "							</td>\n";
	echo "						</tr>\n";
	echo "						<tr>\n";
	echo "							<td class='vncell'>".$text['label-message_text']."</td>\n";
	echo "							<td class='vtable'>\n";
	echo "								<textarea class='formfld' style='width: 100%; height: 80px;' name='message_text' name='message_new_text'></textarea>\n";
	echo "							</td>\n";
	echo "						</tr>\n";
	/*
	echo "						<tr>\n";
	echo "							<td class='vncell'>".$text['label-message_media']."</td>\n";
	echo "							<td class='vtable'>\n";
	echo "								<input type='file' class='formfld' name='message_media' id='message_new_media'>\n";
	echo "							</td>\n";
	echo "						</tr>\n";
	*/
	echo "					</table>\n";
	echo "					<center>\n";
	echo "						<input type='reset' class='btn' style='float: left; margin-top: 15px;' value='".$text['button-clear']."' onclick=\"$('#message_new').reset();\">\n";
	echo "						<input type='button' class='btn' style='margin-top: 15px;' value='".$text['button-close']."' onclick=\"$('#message_new_layer').fadeOut(200);\">\n";
	echo "						<input type='submit' class='btn' style='float: right; margin-top: 15px;' value='".$text['button-send']."'>\n";
	echo "					</center>\n";
	echo "				</span>\n";
	echo "				</form>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</div>\n";

//message media layer
	echo "<div id='message_media_layer' style='display: none;'></div>\n";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-messages']."</b><br><br></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='button' class='btn' name='' alt='".$text['label-new_message']."' onclick=\"$('#message_new_layer').fadeIn(200); unload_thread();\" value='".$text['label-new_message']."'>\n";
	/*
	if (permission_exists('message_all')) {
		if ($_GET['show'] == 'all') {
			echo "				<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "				<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='messages.php?show=all';\">\n";
		}
	}
	*/
	echo "				<a href='messages_log.php'><input type='button' class='btn' alt=\"".$text['label-log']."\" value=\"".$text['label-log']."\"></a>\n";
	/*
	echo "				<input type='text' class='txt' style='width: 150px; margin-left: 15px;' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	*/
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<th width='25%'>".$text['label-contacts']."</th>\n";
	echo "		<th width='75%'>".$text['label-messages']."</th>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td id='contacts' valign='top'>...</td>\n";
	echo "		<td id='thread' valign='top' style='border-color: #c5d1e5; border-style: solid; border-width: 0 1px 1px 1px; padding: 15px;'>...</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br /><br />";

	//js to load messages for clicked number
	echo "<script>\n";

	echo "	var contacts_refresh = 10000;\n";
	echo "	var thread_refresh = 5000;\n";
	echo "	var timer_contacts;\n";
	echo "	var timer_thread;\n";

	echo "	function refresh_contacts() {\n";
	echo "		clearTimeout(timer_contacts);\n";
	echo "		$('#contacts').load('messages_contacts.php', function(){\n";
	echo "			timer_contacts = setTimeout(refresh_contacts, contacts_refresh);\n";
	echo "		});\n";
	echo "	}\n";

	echo "	function load_thread(number) {\n";
	echo "		clearTimeout(timer_thread);\n";
	echo "		$('#thread').load('messages_thread.php?number=' + encodeURIComponent(number), function(){\n";
	echo "			$('#thread_messages').scrollTop(Number.MAX_SAFE_INTEGER);\n"; //chrome
	echo "			$('span#thread_bottom')[0].scrollIntoView(true);\n"; //others
					//note: the order of the above two lines matters!
	if (!http_user_agent('mobile')) {
		echo "		if ($('#message_new_layer').is(':hidden')) {\n";
		echo "			$('#message_text').focus();\n";
		echo "		}\n";
	}
	echo "			timer_thread = setTimeout(refresh_thread_start, thread_refresh, number);\n";
	echo "		});\n";
	echo "	}\n";

	echo "	function unload_thread() {\n";
	echo "		clearTimeout(timer_thread);\n";
	echo "		$('#thread').html('...');\n";
	echo "	}\n";

	echo "	function refresh_thread(number, onsent) {\n";
	echo "		$('#thread_messages').load('messages_thread.php?refresh=true&number=' + encodeURIComponent(number), function(){\n";
	echo "			$('#thread_messages').scrollTop(Number.MAX_SAFE_INTEGER);\n"; //chrome
	echo "			$('span#thread_bottom')[0].scrollIntoView(true);\n"; //others
					//note: the order of the above two lines matters!
	if (!http_user_agent('mobile')) {
		echo "		if ($('#message_new_layer').is(':hidden')) {\n";
		echo "			$('#message_text').focus();\n";
		echo "		}\n";
	}
	echo "			if (onsent != 'true') {\n";
	echo "				timer_thread = setTimeout(refresh_thread, thread_refresh, number);\n";
	echo "			}\n";
	echo "		});\n";
	echo "	}\n";

	//refresh controls
	echo "	function refresh_contacts_stop() {\n";
	echo "		clearTimeout(timer_contacts);\n";
	echo "		document.getElementById('contacts_refresh_state').innerHTML = \"<img src='resources/images/refresh_paused.png' style='width: 16px; height: 16px; border: none; margin-top: 1px; cursor: pointer;' onclick='refresh_contacts_start();' alt='".$text['label-refresh_enable']."' title='".$text['label-refresh_enable']."'>\";\n";
	echo "	}\n";

	echo "	function refresh_contacts_start() {\n";
	echo "		if (document.getElementById('contacts_refresh_state')) {\n";
	echo "			document.getElementById('contacts_refresh_state').innerHTML = \"<img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' onclick='refresh_contacts_stop();' alt='".$text['label-refresh_pause']."' title='".$text['label-refresh_pause']."'>\";\n";
	echo "			refresh_contacts();\n";
	echo "		}\n";
	echo "	}\n";

	echo "	function refresh_thread_stop(number) {\n";
	echo "		clearTimeout(timer_thread);\n";
	echo "		document.getElementById('thread_refresh_state').innerHTML = \"<img src='resources/images/refresh_paused.png' style='width: 16px; height: 16px; border: none; margin-top: 1px; cursor: pointer;' onclick='refresh_thread_start(\" + number + \");' alt='".$text['label-refresh_enable']."' title='".$text['label-refresh_enable']."'>\";\n";
	echo "	}\n";

	echo "	function refresh_thread_start(number) {\n";
	echo "		if (document.getElementById('thread_refresh_state')) {\n";
	echo "			document.getElementById('thread_refresh_state').innerHTML = \"<img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' onclick='refresh_thread_stop(\" + number + \");' alt='".$text['label-refresh_pause']."' title='".$text['label-refresh_pause']."'>\";\n";
	echo "			refresh_thread(number);\n";
	echo "		}\n";
	echo "	}\n";

	//define form submit function
	echo "	$('#message_new').submit(function(event) {\n";
	echo "		event.preventDefault();\n";
	echo "		$.ajax({\n";
	echo "			url: 'message_send.php',\n";
	echo "			type: 'POST',\n";
	echo "			data: $('#message_new').serialize(),\n";
	echo "			success: function(){\n";
	echo "					document.getElementById('message_new').reset();\n";
	echo "					$('#message_new_layer').fadeOut(400);\n";
	echo "					refresh_contacts();\n";
	echo "				}\n";
	echo "		});\n";
	echo "	});\n";

	//open message media in layer
	echo "	function display_media(id, src) {\n";
	echo "		$('#message_media_layer').load('message_media.php?id=' + id + '&src=' + src + '&action=display', function(){\n";
	echo "			$('#message_media_layer').fadeIn(200);\n";
	echo "		});\n";
	echo "	}\n";

	echo "	refresh_contacts();\n";

	echo "</script>\n";

	unset($messages, $message, $numbers, $number);

//include the footer
	require_once "resources/footer.php";

?>
