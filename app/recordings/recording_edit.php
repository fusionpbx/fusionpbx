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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('recording_add') || permission_exists('recording_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//add the settings object
	$settings = new settings(["domain_uuid" => $SESSION['domain_uuid']]);
	$speech_enabled = $settings->get('ai', 'speech_enabled');
	$transcribe_enabled = $settings->get('ai', 'transcribe_enabled');

//add the audio object and get the voices and languages arrays
	if ($speech_enabled == 'true' || $transcribe_enabled == 'true') {
		$ai = new ai($settings);
		$voices = $ai->get_voices();
		$translate_enabled = false;
		$language_enabled = false;
		//$translate_enabled = $ai->get_translate_enabled();
		//$language_enabled = $ai->get_language_enabled();
		//$languages = $ai->get_languages();
	}

//get recording id
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$recording_uuid = $_REQUEST["id"];
	}

//get the form value and set to php variables
	if (!empty($_POST)) {

		$recording_filename = $_POST["recording_filename"];
		$recording_filename_original = $_POST["recording_filename_original"];
		$recording_name = $_POST["recording_name"];
		$recording_voice = $_POST["recording_voice"];
		$recording_language = $_POST["recording_language"];
		//$translate = $_POST["translate"];
		$recording_message = $_POST["recording_message"];
		$recording_description = $_POST["recording_description"];
		//sanitize recording filename and name
		$recording_filename_ext = strtolower(pathinfo($recording_filename, PATHINFO_EXTENSION));
		if (!in_array($recording_filename_ext, ['wav','mp3','ogg'])) {
			$recording_filename = pathinfo($recording_filename, PATHINFO_FILENAME);
			$recording_filename = str_replace('.', '', $recording_filename);
		}
		$recording_filename = str_replace("\\", '', $recording_filename);
		$recording_filename = str_replace('/', '', $recording_filename);
		$recording_filename = str_replace('..', '', $recording_filename);
		$recording_filename = str_replace(' ', '_', $recording_filename);
		$recording_filename = str_replace("'", '', $recording_filename);
		$recording_name = str_replace("'", '', $recording_name);
	}

//process the HTTP POST
	if (!empty($_POST) && empty($_POST["persistformvar"])) {
		//get recording uuid to edit
		$recording_uuid = $_POST["recording_uuid"];

		//delete the recording
		if (permission_exists('recording_delete')) {
			if ($_POST['action'] == 'delete' && is_uuid($recording_uuid)) {
				//prepare
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $recording_uuid;

				//delete
				$obj = new switch_recordings;
				$obj->delete($array);

				//redirect
				header('Location: recordings.php');
				exit;
			}
		}

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: recordings.php');
			exit;
		}

		//check for all required data
		$msg = '';
		if (empty($recording_name)) { $msg .= $text['label-edit_recording']."<br>\n"; }
		//if (empty($recording_filename)) { $msg .= $text['label-edit_file']."<br>\n"; }
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

		//add the bridge_uuid
		if (empty($recording_uuid)) {
			$recording_uuid = uuid();
		}

		//set the default voice
		if (empty($recording_voice)) {
			$recording_voice = 'alloy';
		}

		//set the recording format
		if (empty($recording_format)) {
			$recording_format = 'wav';
		}

		//update the database
		if (empty($_POST["persistformvar"])) {
			if (permission_exists('recording_edit')) {
				//if file name is not the same then rename the file
				if ($recording_filename != $recording_filename_original) {
					rename($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename_original, $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename);
				}

				//build the setting object and get the recording path
				$recording_path = $settings->get('switch', 'recordings').'/'.$_SESSION['domain_name'];

				//create the file name
				if (empty($recording_filename)) {
					$recording_filename = $recording_name.'.'.$recording_format;
				}

				//text to audio - make a new audio file from the message
				if ($speech_enabled == 'true' && !empty($recording_voice) && !empty($recording_message)) {
					$ai->audio_path = $recording_path;
					$ai->audio_filename = $recording_filename;
					$ai->audio_format = $recording_format;
					$ai->audio_voice = $recording_voice;
					//$ai->audio_language = $recording_language;
					//$ai->audio_translate = $translate;
					$ai->audio_message = $recording_message;
					$ai->speech();
				}

				//audio to text - get the transcription from the audio file
				if ($transcribe_enabled == 'true' && empty($recording_message)) {
					$ai->audio_path = $recording_path;
					$ai->audio_filename = $recording_filename;
					$recording_message = $ai->transcribe();
				}

				//build array
				$array['recordings'][0]['domain_uuid'] = $domain_uuid;
				$array['recordings'][0]['recording_uuid'] = $recording_uuid;
				$array['recordings'][0]['recording_filename'] = $recording_filename;
				$array['recordings'][0]['recording_name'] = $recording_name;
				if ($speech_enabled == 'true' || $transcribe_enabled == 'true') {
					$array['recordings'][0]['recording_message'] = $recording_message;
				}
				$array['recordings'][0]['recording_description'] = $recording_description;

				//execute update
				$database = new database;
				$database->app_name = 'recordings';
				$database->app_uuid = '83913217-c7a2-9e90-925d-a866eb40b60e';
				$database->save($array);
				unset($array);

				//set message
				message::add($text['message-update']);

				//redirect
				header("Location: recordings.php");
				exit;
			}
		}
	}

//pre-populate the form
	if (!empty($_GET) && empty($_POST["persistformvar"])) {
		$recording_uuid = $_GET["id"];
		$sql = "select recording_name, recording_filename, recording_message, recording_description ";
		$sql .= "from v_recordings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and recording_uuid = :recording_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['recording_uuid'] = $recording_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$recording_filename = $row["recording_filename"];
			$recording_name = $row["recording_name"];
			$recording_message = $row["recording_message"];
			$recording_description = $row["recording_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-edit'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-edit']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'recordings.php']);
	if (permission_exists('recording_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('recording_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-recording_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='recording_name' maxlength='255' value=\"".escape($recording_name)."\">\n";
	echo "<br />\n";
	echo $text['description-recording']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (!empty($_REQUEST["id"])) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-file_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='recording_filename' maxlength='255' value=\"".escape($recording_filename)."\">\n";
		echo "    <input type='hidden' name='recording_filename_original' value=\"".escape($recording_filename)."\">\n";
		echo "<br />\n";
		echo $text['description-file_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if ($speech_enabled == 'true' || $transcribe_enabled == 'true') {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-voice']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (!empty($voices)) {
			echo "	<select class='formfld' name='recording_voice'>\n";
			echo "		<option value=''></option>\n";
			foreach($voices as $voice) {
				echo "		<option value='".escape($voice)."' ".(($voice == $recording_voice) ? "selected='selected'" : null).">".escape($voice)."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "		<input class='formfld' type='text' name='recording_voice' maxlength='255' value=\"".escape($recording_voice)."\">\n";
		}
		echo "<br />\n";
		echo $text['description-voice']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if ($language_enabled) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "    ".$text['label-language']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			if (!empty($languages)) {
				sort($languages);
				echo "	<select class='formfld' name='recording_language'>\n";
				echo "		<option value=''></option>\n";
				foreach($languages as $language) {
					echo "		<option value='".escape($language)."' ".(($language == $recording_language) ? "selected='selected'" : null).">".escape($language)."</option>\n";
				}
				echo "	</select>\n";
			}
			else {
				echo "		<input class='formfld' type='text' name='recording_language' maxlength='255' value=\"".escape($recording_language)."\">\n";
			}
			echo "<br />\n";
			echo $text['description-languages']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		if ($translate_enabled) {
			echo "<tr>\n";
			echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-translate']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
				echo "	<label class='switch'>\n";
				echo "		<input type='checkbox' id='translate' name='translate' value='true' ".($translate == 'true' ? "checked='checked'" : null).">\n";
				echo "		<span class='slider'></span>\n";
				echo "	</label>\n";
			}
			else {
				echo "	<select class='formfld' id='translate' name='translate'>\n";
				echo "		<option value='true' ".($translate == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
				echo "		<option value='false' ".($translate == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
				echo "	</select>\n";
			}
			echo "<br />\n";
			echo $text['description-translate']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-message']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <textarea class='formfld' name='recording_message' style='width: 300px; height: 150px;'>".escape($recording_message)."</textarea>\n";
		echo "<br />\n";
		echo $text['description-message']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='recording_description' maxlength='255' value=\"".escape($recording_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='recording_uuid' value='".escape($recording_uuid)."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>