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
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('voicemail_greeting_edit')) {
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
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);
	$speech_enabled = $settings->get('speech', 'enabled', false);
	$speech_engine = $settings->get('speech', 'engine', '');
	$transcribe_enabled = $settings->get('transcribe', 'enabled', false);
	$transcribe_engine = $settings->get('transcribe', 'engine', '');
	$storage_type = $settings->get('voicemail', 'storage_type', '');

//set defaults
	$translate_enabled = false;
	$language_enabled = false;

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

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$voicemail_greeting_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
		$voicemail_greeting_uuid = uuid();
	}
	if (!empty($_REQUEST["voicemail_id"]) && is_numeric($_REQUEST["voicemail_id"])) {
		$voicemail_id = $_REQUEST["voicemail_id"];
	}

//get the form value and set to php variables
	if (!empty($_POST) && is_array($_POST)) {
		$greeting_id = $_POST["greeting_id"];
		$greeting_name = $_POST["greeting_name"];
		$greeting_voice = $_POST["greeting_voice"];
		//$greeting_model = $_POST["greeting_model"];
		$greeting_language = $_POST["greeting_language"] ?? null;
		//$translate = $_POST["translate"];
		$greeting_message = $_POST["greeting_message"];
		$greeting_description = $_POST["greeting_description"];

		//clean the name
		$greeting_name = str_replace("'", "", $greeting_name);
	}

if (!empty($_POST) && empty($_POST["persistformvar"])) {

	//delete the voicemail greeting
		if (permission_exists('voicemail_greeting_delete')) {
			if (!empty($_POST['action']) && $_POST['action'] == 'delete' && is_uuid($voicemail_greeting_uuid)) {
				//prepare
					$array[0]['checked'] = 'true';
					$array[0]['uuid'] = $voicemail_greeting_uuid;
				//delete
					$obj = new voicemail_greetings;
					$obj->voicemail_id = $voicemail_id;
					$obj->delete($array);
				//redirect
					header("Location: voicemail_greetings.php?id=".$voicemail_id);
					exit;
			}
		}

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: ../voicemails/voicemails.php');
			exit;
		}

	//check for all required data
		$msg = '';
		if (empty($greeting_name)) { $msg .= "".$text['confirm-name']."<br>\n"; }
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

	//update the database
	if ((empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true") && permission_exists('voicemail_greeting_edit')) {

		//get current vm greeting ids for mailbox
		$sql = "select greeting_id ";
		$sql .= "from v_voicemail_greetings where domain_uuid = :domain_uuid ";
		$sql .= "and voicemail_id = :voicemail_id ";
		$sql .= "order by greeting_id asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['voicemail_id'] = $voicemail_id;
		$database = new database;
		$rows = $database->select($sql, $parameters, 'all');
		$greeting_ids = array();
		if (!empty($rows) && is_array($rows)) {
			foreach ($rows as $row) {
				$greeting_ids[] = $row['greeting_id'];
			}
		}
		unset($sql, $parameters);

		//set the recording format
		$greeting_format = $greeting_format ?? 'wav';

		//build the setting object and get the recording path
		$greeting_path = $settings->get('switch', 'voicemail').'/default/'.$_SESSION['domain_name'].'/'.$voicemail_id.'/';

		if ($action == 'add') {
			//find the next available greeting id
			$greeting_id = 0;
			for ($i = 1; $i <= 9; $i++) {
				if (!in_array($i, $greeting_ids) && !file_exists($greeting_path.'greeting_'.$i.'.'.$greeting_format)) {
					$greeting_id = $i;
					break;
				}
			}
		}

		if (!empty($greeting_id)) {
			//set file name
			$greeting_filename = 'greeting_'.$greeting_id.'.'.$greeting_format;

			//text to audio - make a new audio file from the message
			if ($speech_enabled && !empty($greeting_voice) && !empty($greeting_message)) {
				$speech->audio_path = $greeting_path;
				$speech->audio_filename = $greeting_filename;
				$speech->audio_format = $greeting_format;
				//$speech->audio_model = $greeting_model ?? '';
				$speech->audio_voice = $greeting_voice;
				//$speech->audio_language = $greeting_language;
				//$speech->audio_translate = $translate;
				$speech->audio_message = $greeting_message;
				$speech->speech();

				//fix invalid riff & data header lengths in generated wave file
				if ($speech_engine == 'openai') {
					$greeting_filename_temp = str_replace('.'.$greeting_format, '.tmp.'.$greeting_format, $greeting_filename);
					exec('sox --ignore-length '.$greeting_path.$greeting_filename.' '.$greeting_path.$greeting_filename_temp);
					if (file_exists($greeting_path.$greeting_filename_temp)) {
						exec('rm -f '.$greeting_path.$greeting_filename.' && mv '.$greeting_path.$greeting_filename_temp.' '.$greeting_path.$greeting_filename);
					}
					unset($greeting_filename_temp);
				}
			}

			//audio to text - get the transcription from the audio file
			if ($transcribe_enabled && empty($greeting_voice) && empty($greeting_message)) {
				$transcribe->audio_path = $greeting_path;
				$transcribe->audio_filename = $greeting_filename;
				$greeting_message = $transcribe->transcribe();
			}

			//if base64 is enabled base64
			if ($storage_type == 'base64' && file_exists($greeting_path.'/'.$greeting_filename)) {
				$greeting_base64 = base64_encode(file_get_contents($greeting_path.'/'.$greeting_filename));
			}

			//build data array
			$array['voicemail_greetings'][0]['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
			$array['voicemail_greetings'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['voicemail_greetings'][0]['voicemail_id'] = $voicemail_id;
			$array['voicemail_greetings'][0]['greeting_id'] = $greeting_id;
			$array['voicemail_greetings'][0]['greeting_name'] = $greeting_name;
			$array['voicemail_greetings'][0]['greeting_message'] = $greeting_message;
			$array['voicemail_greetings'][0]['greeting_filename'] = $greeting_filename;
			$array['voicemail_greetings'][0]['greeting_base64'] = $greeting_base64;
			$array['voicemail_greetings'][0]['greeting_description'] = $greeting_description;

			//execute query
			$database = new database;
			$database->app_name = 'voicemail_greetings';
			$database->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
			$database->save($array);
			unset($array);

			//set message
			message::add($text['message-'.($action == 'add' ? 'greeting_created' : 'update')]);

		}

		//redirect
			header("Location: voicemail_greetings.php?id=".$voicemail_id);
			exit;
	}
}

//pre-populate the form
	if (
		$action == 'update' &&
		!empty($voicemail_greeting_uuid) && is_uuid($voicemail_greeting_uuid) &&
		(empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")
		) {
		$sql = "select * from v_voicemail_greetings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and voicemail_greeting_uuid = :voicemail_greeting_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$greeting_id = $row["greeting_id"];
			$greeting_name = $row["greeting_name"];
			$greeting_message = $row["greeting_message"];
			$greeting_description = $row["greeting_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['label-'.($action == 'update' ? 'edit' : 'add')];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['label-'.($action == 'update' ? 'edit' : 'add')]."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','collapse'=>'hide-xs','link'=>'voicemail_greetings.php?id='.urlencode($voicemail_id)]);
 	if (permission_exists('voicemail_greeting_delete') && $action == 'update') {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'name'=>'btn_delete','collapse'=>'hide-xs','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('voicemail_greeting_delete') && $action == 'update') {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='greeting_name' maxlength='255' value=\"".escape($greeting_name ?? '')."\">\n";
	echo "<br />\n";
	echo "".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($speech_enabled || $transcribe_enabled) {
		//models
		if (!empty($models) && is_array($models)) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "    ".$text['label-model']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<select class='formfld' name='greeting_model'>\n";
			echo "		<option value=''></option>\n";
			foreach ($models as $model_id => $model_name) {
				echo "		<option value='".escape($model_id)."' ".($model_id == $greeting_model ? "selected='selected'" : '').">".escape($model_name)."</option>\n";
			}
			echo "	</select>\n";
			echo "<br />\n";
			echo $text['description-model']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
// 		else {
// 			echo "<input class='formfld' type='hidden' name='greeting_model' maxlength='255' value=''>\n";
// 		}

		//voices
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-voice']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (!empty($voices) && is_array($voices)) {
			echo "	<select class='formfld' name='greeting_voice'>\n";
			echo "		<option value=''></option>\n";
			foreach ($voices as $key => $voice) {
				echo "		<option value='".escape(gettype($key) === "integer" ? $voice : $key)."' ".(!empty($greeting_voice) && $voice == $greeting_voice ? "selected='selected'" : null).">".escape(ucwords($voice))."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "		<input class='formfld' type='text' name='greeting_voice' maxlength='255' value=\"".escape($greeting_voice ?? '')."\">\n";
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
			if (!empty($languages) && is_array($languages)) {
				sort($languages);
				echo "	<select class='formfld' name='greeting_language'>\n";
				echo "		<option value=''></option>\n";
				foreach ($languages as $language) {
					echo "		<option value='".escape($language)."' ".($language == $greeting_language ? "selected='selected'" : null).">".escape($language)."</option>\n";
				}
				echo "	</select>\n";
			}
			else {
				echo "		<input class='formfld' type='text' name='greeting_language' maxlength='255' value=\"".escape($greeting_language ?? '')."\">\n";
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
		echo "    <textarea class='formfld' name='greeting_message' style='width: 300px; height: 150px;'>".escape($greeting_message ?? '')."</textarea>\n";
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
	echo "    <input class='formfld' type='text' name='greeting_description' maxlength='255' value=\"".escape($greeting_description ?? '')."\">\n";
	echo "<br />\n";
	echo "".$text['description-info']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>\n";
	echo "<br /><br />";

	if ($action == 'update' && !empty($voicemail_greeting_uuid) && is_uuid($voicemail_greeting_uuid)) {
		echo "<input type='hidden' name='voicemail_greeting_uuid' value='".escape($voicemail_greeting_uuid)."'>\n";
		echo "<input type='hidden' name='greeting_id' value='".escape($greeting_id ?? '')."'>\n";
	}
	echo "<input type='hidden' name='voicemail_id' value='".escape($voicemail_id)."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
