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

//initialize the database connection
	$database = database::new();

//set defaults
	$recording_name = '';
	$recording_message = '';
	$recording_description = '';
	$recording_uuid = '';
	$translate_enabled = false;
	$language_enabled = false;

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);
	$speech_enabled = $settings->get('speech', 'enabled', false);
	$speech_engine = $settings->get('speech', 'engine', '');
	$transcribe_enabled = $settings->get('transcribe', 'enabled', false);
	$transcribe_engine = $settings->get('transcribe', 'engine', '');

//add the speech object and get the voices and languages arrays
	if ($speech_enabled && !empty($speech_engine)) {
		$speech = new speech($settings);
		$voices = $speech->get_voices();
		//$speech_models = $speech->get_models();
		//$translate_enabled = $speech->get_translate_enabled();
		//$language_enabled = $speech->get_language_enabled();
		//$languages = $speech->get_languages();
	}

//add the transcribe object and get the languages arrays
	if ($transcribe_enabled && !empty($transcribe_engine)) {
		$transcribe = new transcribe($settings);
		//$transcribe_models = $transcribe->get_models();
		//$translate_enabled = $transcribe->get_translate_enabled();
		//$language_enabled = $transcribe->get_language_enabled();
		//$languages = $transcribe->get_languages();
	}

//get recording id
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$recording_uuid = $_REQUEST["id"];
		$action = 'update';
	}
	else {
		$action = 'add';
	}

//get the form value and set to php variables
	if (!empty($_POST)) {
		$recording_filename = $_POST["recording_filename"] ?? '';
		$recording_filename_original = $_POST["recording_filename_original"] ?? '';
		$recording_name = $_POST["recording_name"];
		$recording_model = $_POST["recording_model"];
		//$recording_language = $_POST["recording_language"];
		//$translate = $_POST["translate"];
		$recording_voice = $_POST["recording_voice"];
		$recording_message = $_POST["recording_message"];
		$recording_description = $_POST["recording_description"];

		//sanitize: recording_filename
		if (!empty($recording_filename)) {
			$recording_filename_ext = strtolower(pathinfo($recording_filename, PATHINFO_EXTENSION));
			if (!in_array($recording_filename_ext, ['wav','mp3','ogg'])) {
				$recording_filename = pathinfo($recording_filename, PATHINFO_FILENAME);
				$recording_filename = str_replace('.', '', $recording_filename);
			}
			$replace = ['\\', '|', '/', '..', "`", "'"];
			$recording_filename = str_replace($replace, '', $recording_filename);
			$recording_filename = str_replace(' ', '-', $recording_filename);
		}

		//sanitize: recording_filename_original
		if (!empty($recording_filename_original)) {
			$replace = ['\\', '|', '/', '..', "`", "'"];
			$recording_filename_original = str_replace($replace, '', $recording_filename_original);
			$recording_filename_original = str_replace(' ', '-', $recording_filename_original);
		}
	}

//process the HTTP POST
	if (!empty($_POST) && empty($_POST["persistformvar"])) {
		//get recording uuid to edit
		$recording_uuid = $_POST["recording_uuid"] ?? '';

		//delete the recording
		if (permission_exists('recording_delete')) {
			if (!empty($_POST['action']) && $_POST['action'] == 'delete' && is_uuid($recording_uuid)) {
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

		//add the recording_uuid
		if (empty($recording_uuid)) {
			$recording_uuid = uuid();
		}

		//set the default value
		//if (empty($recording_model)) {
		//	$recording_model = $settings->get('speech', 'model', '');
		//}

		//update the database
		if (empty($_POST["persistformvar"])) {
			if (permission_exists('recording_edit')) {

				//set the recording format for approved types
				if (!in_array($recording_extension, ['mp3', 'wav'], true)) {
					//default to wav
					$recording_extension = 'wav';
				}

				//build the setting object and get the recording path
				$recording_path = $settings->get('switch', 'recordings').'/'.$_SESSION['domain_name'];

				//if file name is not the same then rename the file
				if (!empty($recording_filename) && !empty($recording_filename_original)
					&& file_exists($recording_path.'/'.$recording_filename_original)
					&& $recording_filename != $recording_filename_original) {
					rename($recording_path.'/'.$recording_filename_original, $recording_path.'/'.$recording_filename);
				}

				//create the file name
				if (empty($recording_filename) && !empty($recording_name)) {
					// Replace invalid characters with underscore
					$recording_filename = preg_replace('#[^a-zA-Z0-9_\-]#', '_', $recording_name);
				}

				//make sure the filename ends with the approved extension
				if (!str_ends_with($recording_filename, ".$recording_extension")) {
					$recording_filename .= ".$recording_extension";
				}

				//determine whether to create the recording
				$create_recording = false;
				if ($speech_enabled && !empty($recording_voice) && !empty($recording_message)) {
					if ($action == 'add') {
						$create_recording = true;
					}
					if ($action == 'update' && $_POST["create_recording"] == 'true') {
						$create_recording = true;
					}
				}

				//text to audio - make a new audio file from the message
				if ($create_recording) {
					$speech->audio_path = $recording_path;
					$speech->audio_filename = $recording_filename;
					$speech->audio_format = $recording_extension;
					//$speech->audio_model = $recording_model ?? '';
					$speech->audio_voice = $recording_voice;
					//$speech->audio_language = $recording_language;
					//$speech->audio_translate = $translate;
					$speech->audio_message = $recording_message;
					$speech->speech();

					//fix invalid riff & data header lengths in generated wave file
					if ($speech_engine == 'openai') {
						$recording_filename_temp = str_replace('.'.$recording_extension, '.tmp.'.$recording_extension, $recording_filename);
						if (file_exists($recording_path.'/'.$recording_filename)) {
							exec('sox --ignore-length '.escapeshellarg($recording_path.'/'.$recording_filename).' '.escapeshellarg($recording_path.'/'.$recording_filename_temp));
						}
						if (file_exists($recording_path.'/'.$recording_filename_temp)) {
							exec('rm -f '.escapeshellarg($recording_path.'/'.$recording_filename).' && mv '.escapeshellarg($recording_path.'/'.$recording_filename_temp).' '.escapeshellarg($recording_path.'/'.$recording_filename));
						}
						unset($recording_filename_temp);
					}
				}

				//audio to text - get the transcription from the audio file
				if ($transcribe_enabled && empty($recording_message)) {
					$transcribe->audio_path = $recording_path;
					$transcribe->audio_filename = $recording_filename;
					$recording_message = $transcribe->transcribe();
				}

				//build array
				$array['recordings'][0]['domain_uuid'] = $domain_uuid;
				$array['recordings'][0]['recording_uuid'] = $recording_uuid;
				$array['recordings'][0]['recording_filename'] = $recording_filename;
				$array['recordings'][0]['recording_name'] = $recording_name;
				if ($settings->get('recordings', 'storage_type', '') == 'base64'
					&& file_exists($recording_path.'/'.$recording_filename)) {
					$array['recordings'][0]['recording_base64'] = base64_encode(file_get_contents($recording_path.'/'.$recording_filename));
				}
				if ($speech_enabled || $transcribe_enabled) {
					$array['recordings'][0]['recording_voice'] = $recording_voice;
					$array['recordings'][0]['recording_message'] = $recording_message;
				}
				$array['recordings'][0]['recording_description'] = $recording_description;

				//execute update
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
		$sql = "select recording_name, recording_filename, ";
		$sql .= "recording_voice, recording_message, recording_description ";
		$sql .= "from v_recordings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and recording_uuid = :recording_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['recording_uuid'] = $recording_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$recording_filename = $row["recording_filename"];
			$recording_name = $row["recording_name"];
			$recording_voice = $row["recording_voice"];
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
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'recordings.php']);
	if (permission_exists('recording_delete') && !empty($recording_uuid) && is_uuid($recording_uuid)) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('recording_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<div class='card'>\n";
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

	if ($speech_enabled || $transcribe_enabled) {
		//models
		if (!empty($models)) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "    ".$text['label-model']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<select class='formfld' name='recording_model'>\n";
			echo "		<option value=''></option>\n";
			foreach ($models as $model_id => $model_name) {
				echo "		<option value='".escape($model_id)."' ".($model_id == $recording_model ? "selected='selected'" : '').">".escape($model_name)."</option>\n";
			}
			echo "	</select>\n";
			echo "<br />\n";
			echo $text['description-model']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		else {
			echo "<input class='formfld' type='hidden' name='recording_model' maxlength='255' value=''>\n";
		}

		//voices
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-voice']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (!empty($voices)) {
			echo "	<select class='formfld' name='recording_voice'>\n";
			echo "		<option value=''></option>\n";
			foreach ($voices as $key => $voice) {
				$recording_voice_selected = (!empty($recording_voice) && $key == $recording_voice) ? "selected='selected'" : null;
				echo "		<option value='".escape($key)."' $recording_voice_selected>".escape(ucwords($voice))."</option>\n";
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
				foreach ($languages as $language) {
					echo "		<option value='".escape($language)."' ".(!empty($recording_language) && $language == $recording_language ? "selected='selected'" : null).">".escape($language)."</option>\n";
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
				echo "		<input type='checkbox' id='translate' name='translate' value='true' ".(!empty($translate) && $translate == 'true' ? "checked='checked'" : null).">\n";
				echo "		<span class='slider'></span>\n";
				echo "	</label>\n";
			}
			else {
				echo "	<select class='formfld' id='translate' name='translate'>\n";
				echo "		<option value='true'>".$text['option-true']."</option>\n";
				echo "		<option value='false' ".(!empty($translate) && $translate == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
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
		echo "    <textarea class='formfld' name='recording_message' style='width: 300px; height: 150px;'>".escape_textarea($recording_message)."</textarea>\n";
		echo "<br />\n";
		echo $text['description-message']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if ($action == 'update') {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-create_recording']."\n";
			echo "</td>\n";
			echo "<td class='vtable' style='position: relative;' align='left'>\n";
			if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
				echo "	<label class='switch'>\n";
				echo "		<input type='checkbox' id='create_recording' name='create_recording' value='true' ".(!empty($create_recording) && $create_recording == 'true' ? "checked='checked'" : null).">\n";
				echo "		<span class='slider'></span>\n";
				echo "	</label>\n";
			}
			else {
				echo "	<select class='formfld' id='create_recording' name='create_recording'>\n";
				echo "		<option value='true'>".$text['option-true']."</option>\n";
				echo "		<option value='false' selected='selected'>".$text['option-false']."</option>\n";
				echo "	</select>\n";
			}
			echo "<br />\n";
			echo $text['description-create_recording']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
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
	echo "</div>\n";
	echo "<br /><br />";

	if (!empty($recording_uuid) && is_uuid($recording_uuid)) {
		echo "<input type='hidden' name='recording_uuid' value='".escape($recording_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
