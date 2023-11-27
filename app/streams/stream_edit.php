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
	Portions created by the Initial Developer are Copyright (C) 2018-2023
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('stream_add') || permission_exists('stream_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the defaults
	$stream_name = '';
	$stream_location = '';
	$stream_description = '';
	$stream_uuid = '';

//action add or update
	if (!empty($_REQUEST["id"])) {
		$action = "update";
		$stream_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$domain_uuid = $_POST['domain_uuid'];
		$stream_uuid = $_POST["stream_uuid"];
		$stream_name = $_POST["stream_name"];
		$stream_location = $_POST["stream_location"];
		$stream_enabled = $_POST["stream_enabled"] ?? 'false';
		$stream_description = $_POST["stream_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && empty($_POST["persistformvar"])) {

		//get the uuid from the POST
			if ($action == "update") {
				$stream_uuid = $_POST["stream_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: streams.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (empty($stream_name)) { $msg .= $text['message-required']." ".$text['label-stream_name']."<br>\n"; }
			if (empty($stream_location)) { $msg .= $text['message-required']." ".$text['label-stream_location']."<br>\n"; }
			if (empty($stream_enabled)) { $msg .= $text['message-required']." ".$text['label-stream_enabled']."<br>\n"; }
			//if (empty($domain_uuid)) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			//if (empty($stream_description)) { $msg .= $text['message-required']." ".$text['label-stream_description']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//add the stream_uuid
			if (empty($_POST["stream_uuid"])) {
				$stream_uuid = uuid();
			}

		//prepare the array
			$array['streams'][0]['stream_uuid'] = $stream_uuid;
			if (permission_exists('stream_all')) {
				$array['streams'][0]['domain_uuid'] = $domain_uuid;
			}
			elseif ($action == 'add') {
				$array['streams'][0]['domain_uuid'] = $_SESSION["domain_uuid"];
			}
			$array['streams'][0]['stream_name'] = $stream_name;
			$array['streams'][0]['stream_location'] = $stream_location;
			$array['streams'][0]['stream_enabled'] = $stream_enabled;
			$array['streams'][0]['stream_description'] = $stream_description;

		//save to the data
			$database = new database;
			$database->app_name = 'streams';
			$database->app_uuid = 'ffde6287-aa18-41fc-9a38-076d292e0a38';
			$database->save($array);
			$message = $database->message;

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: stream_edit.php?id='.urlencode($stream_uuid));
				return;
			}
	}

//pre-populate the form
	if (!empty($_GET) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$stream_uuid = $_GET["id"];
		$sql = "select * from v_streams ";
		$sql .= "where stream_uuid = :stream_uuid ";
		$parameters['stream_uuid'] = $stream_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$stream_name = $row["stream_name"];
			$stream_location = $row["stream_location"];
			$stream_enabled = $row["stream_enabled"];
			$stream_description = $row["stream_description"];
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	if (empty($stream_enabled)) { $stream_enabled = 'true'; }

//need stream_all permission to edit a global stream
	if (!permission_exists('stream_all') && $domain_uuid == null) {
		header('Location: streams.php');
		return;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-stream'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-stream']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'streams.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-stream_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='stream_name' maxlength='255' value=\"".escape($stream_name)."\">\n";
	echo "<br />\n";
	echo $text['description-stream_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-stream_location']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='stream_location' style='min-width: 250px; width: 50%;' maxlength='255' value=\"".escape($stream_location)."\">\n";
	echo "<br />\n";
	echo $text['description-stream_location']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-stream_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='stream_enabled' name='stream_enabled' value='true' ".($stream_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='stream_enabled' name='stream_enabled'>\n";
		echo "		<option value='true' ".($stream_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($stream_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-stream_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('stream_all')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain_uuid']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<select class='formfld' name='domain_uuid'>\n";
		if (empty($domain_uuid)) {
			echo "		<option value='' selected='selected'>".$text['label-global']."</option>\n";
		}
		else {
			echo "		<option value=''>".$text['label-global']."</option>\n";
		}
		foreach ($_SESSION['domains'] as $row) {
			if ($row['domain_uuid'] == $domain_uuid) {
				echo "		<option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
			}
			else {
				echo "		<option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
			}
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo !empty($text['description-domain_uuid'])."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-stream_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='stream_description' maxlength='255' value=\"".escape($stream_description)."\">\n";
	echo "<br />\n";
	echo $text['description-stream_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='stream_uuid' value='".escape($stream_uuid)."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
