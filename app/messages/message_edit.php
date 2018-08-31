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

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('message_add') || permission_exists('message_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$message_uuid = check_str($_REQUEST["id"]);
		$id = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$message_uuid = check_str($_POST["message_uuid"]);
		//$user_uuid = check_str($_POST["user_uuid"]);
		$message_type = check_str($_POST["message_type"]);
		$message_direction = check_str($_POST["message_direction"]);
		$message_date = check_str($_POST["message_date"]);
		$message_from = check_str($_POST["message_from"]);
		$message_to = check_str($_POST["message_to"]);
		$message_text = check_str($_POST["message_text"]);
		$message_media_type = check_str($_POST["message_media_type"]);
		$message_media_url = check_str($_POST["message_media_url"]);
		$message_media_content = check_str($_POST["message_media_content"]);
		$message_json = check_str($_POST["message_json"]);
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$message_uuid = check_str($_POST["message_uuid"]);
			}

		//check for all required data
			$msg = '';
			//if (strlen($user_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-user_uuid']."<br>\n"; }
			if (strlen($message_type) == 0) { $msg .= $text['message-required']." ".$text['label-message_type']."<br>\n"; }
			if (strlen($message_direction) == 0) { $msg .= $text['message-required']." ".$text['label-message_direction']."<br>\n"; }
			if (strlen($message_date) == 0) { $msg .= $text['message-required']." ".$text['label-message_date']."<br>\n"; }
			if (strlen($message_from) == 0) { $msg .= $text['message-required']." ".$text['label-message_from']."<br>\n"; }
			if (strlen($message_to) == 0) { $msg .= $text['message-required']." ".$text['label-message_to']."<br>\n"; }
			//if (strlen($message_text) == 0) { $msg .= $text['message-required']." ".$text['label-message_text']."<br>\n"; }
			//if (strlen($message_media_type) == 0) { $msg .= $text['message-required']." ".$text['label-message_media_type']."<br>\n"; }
			//if (strlen($message_media_url) == 0) { $msg .= $text['message-required']." ".$text['label-message_media_url']."<br>\n"; }
			//if (strlen($message_media_content) == 0) { $msg .= $text['message-required']." ".$text['label-message_media_content']."<br>\n"; }
			//if (strlen($message_json) == 0) { $msg .= $text['message-required']." ".$text['label-message_json']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//set the domain_uuid
				$_POST["domain_uuid"] = $_SESSION["domain_uuid"];

		//add the message_uuid
			if (strlen($_POST["message_uuid"]) == 0) {
				$message_uuid = uuid();
				$_POST["message_uuid"] = $message_uuid;
			}

		//prepare the array
			$array['messages'][0] = $_POST;

		//save to the data
			$database = new database;
			$database->app_name = 'messages';
			$database->app_uuid = null;
			if (strlen($message_uuid) > 0) {
				$database->uuid($message_uuid);
			}
			$database->save($array);
			$message = $database->message;

		//debug info
			//echo "<pre>";
			//print_r($message);
			//echo "</pre>";
			//exit;

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: message_edit.php?id='.$message_uuid);
				return;
			}
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$message_uuid = check_str($_GET["id"]);
		$sql = "select * from v_messages ";
		$sql .= "where message_uuid = '$message_uuid' ";
		//$sql .= "and domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$user_uuid = $row["user_uuid"];
			$message_type = $row["message_type"];
			$message_direction = $row["message_direction"];
			$message_date = $row["message_date"];
			$message_from = $row["message_from"];
			$message_to = $row["message_to"];
			$message_text = $row["message_text"];
			$message_media_type = $row["message_media_type"];
			$message_media_url = $row["message_media_url"];
			$message_media_content = $row["message_media_content"];
			$message_json = $row["message_json"];
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//get the extensions
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and user_enabled = 'true' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

//get the users
	$sql = "SELECT user_uuid, username FROM v_users ";
	$sql .= "WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "ORDER by username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$users = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-message']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='messages.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-username']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='user_uuid'>\n";
	echo "		<option value=''></option>\n";
	foreach($users as $row) {
		if ($row['user_uuid'] == $user_uuid) { $selected = "selected='selected'"; } else { $selected = ''; }
		echo "		<option value='".escape($row['user_uuid'])."' $selected>".escape($row['username'])."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-username']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-message_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='message_type'>\n";
	echo "		<option value=''></option>\n";
	if ($message_type == "sms") {
		echo "		<option value='sms' selected='selected'>".$text['label-sms']."</option>\n";
	}
	else {
		echo "		<option value='sms'>".$text['label-sms']."</option>\n";
	}
	if ($message_type == "mms") {
		echo "		<option value='mms' selected='selected'>".$text['label-mms']."</option>\n";
	}
	else {
		echo "		<option value='mms'>".$text['label-mms']."</option>\n";
	}
	if ($message_type == "chat") {
		echo "		<option value='chat' selected='selected'>".$text['label-chat']."</option>\n";
	}
	else {
		echo "		<option value='chat'>".$text['label-chat']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-message_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-message_direction']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<select class='formfld' name='message_direction'>\n";
		echo "		<option value=''></option>\n";
		if ($message_direction == "inbound") {
			echo "		<option value='inbound' selected='selected'>".$text['label-inbound']."</option>\n";
		}
		else {
			echo "		<option value='inbound'>".$text['label-inbound']."</option>\n";
		}
		if ($message_direction == "outbound") {
			echo "		<option value='outbound' selected='selected'>".$text['label-outbound']."</option>\n";
		}
		else {
			echo "		<option value='outbound'>".$text['label-outbound']."</option>\n";
		}
		echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-message_direction']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-message_date']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='message_date' maxlength='255' value=\"".escape($message_date)."\">\n";
	echo "<br />\n";
	echo $text['description-message_date']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-message_from']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='message_from' maxlength='255' value=\"".escape($message_from)."\">\n";
	echo "<br />\n";
	echo $text['description-message_from']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-message_to']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='message_to' maxlength='255' value=\"".escape($message_to)."\">\n";
	echo "<br />\n";
	echo $text['description-message_to']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-message_text']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='message_text' maxlength='255' value=\"".escape($message_text)."\">\n";
	echo "<br />\n";
	echo $text['description-message_text']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	if (strlen($message_media_type) > 0) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	&nbsp;\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		$image_source = 'data: '.mime_content_type($message_media_type).';base64,'.$message_media_content;
		echo "<img src='".$image_source."' width='100%'>";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if ($_GET['debug'] == 'true') {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-message_media_type']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<input class='formfld' type='text' name='message_media_type' maxlength='255' value=\"".escape($message_media_type)."\">\n";
		echo "<br />\n";
		echo $text['description-message_media_type']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-message_media_url']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<input class='formfld' type='text' name='message_media_url' maxlength='255' value=\"".escape($message_media_url)."\">\n";
		echo "<br />\n";
		echo $text['description-message_media_url']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-message_media_content']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<input class='formfld' type='text' name='message_media_content' maxlength='255' value=\"".escape($message_media_content)."\">\n";
		echo "<br />\n";
		echo $text['description-message_media_content']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-message_json']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<input class='formfld' type='text' name='message_json' maxlength='255' value=\"".escape($message_json)."\">\n";
		echo "<br />\n";
		echo $text['description-message_json']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='message_uuid' value='".escape($message_uuid)."'>\n";
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
