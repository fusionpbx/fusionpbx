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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * Record a recording by phone.
 *
 * Adds a Call button that opens a dialog, calls the chosen extension and records
 * a new recording (or re-records an existing one) after the tone. When the
 * recording is saved it is added to the target select box and selected.
 *
 * Usage:
 *   echo recording_call::button('ivr_menu_greet_long');        //new recording, update the select box
 *   echo recording_call::button();                              //new recording, reload the page when saved
 *   echo recording_call::button('', $recording_uuid);           //re-record an existing recording
 *   echo recording_call::button('', '', ['type' => 'greeting', 'voicemail_id' => $voicemail_id]); //new voicemail greeting
 */
class recording_call {

	/**
	 * declare constant variables
	 */
	const app_name = 'recordings';
	const app_uuid = '83913217-c7a2-9e90-925d-a866eb40b60e';

	/**
	 * Whether the dialog has already been added to the page
	 *
	 * @var bool
	 */
	private static $modal_added = false;

	/**
	 * Check if the user is allowed to record by phone
	 *
	 * @param string $type recording or greeting
	 *
	 * @return bool
	 */
	public static function allowed(string $type = 'recording'): bool {
		if ($type == 'greeting') {
			return permission_exists('voicemail_greeting_call');
		}
		return permission_exists('recording_call') && (permission_exists('recording_add') || permission_exists('recording_edit'));
	}

	/**
	 * Get the extensions the user is allowed to call. Voicemail greetings recorded by a user
	 * without the voicemail_view permission can only call the extensions assigned to the user.
	 *
	 * @param string $type recording or greeting
	 *
	 * @return array extension_uuid, extension, number_alias, effective_caller_id_name, description
	 */
	public static function extensions(string $type = 'recording'): array {
		$database = database::new();
		$sql = "select e.extension_uuid, e.extension, e.number_alias, e.effective_caller_id_name, e.description ";
		$sql .= "from v_extensions as e ";
		$sql .= "where e.domain_uuid = :domain_uuid ";
		$sql .= "and e.enabled = 'true' ";
		if ($type == 'greeting' && !permission_exists('voicemail_view')) {
			$sql .= "and e.extension_uuid in (select u.extension_uuid from v_extension_users as u where u.domain_uuid = :domain_uuid and u.user_uuid = :user_uuid) ";
			$parameters['user_uuid'] = $_SESSION['user_uuid'] ?? '';
		}
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$extensions = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
		if (empty($extensions) || !is_array($extensions)) {
			return [];
		}
		usort($extensions, function ($a, $b) { return strnatcmp($a['extension'], $b['extension']); });
		return $extensions;
	}

	/**
	 * Clean a recording or greeting name, characters that are not safe in a channel variable are removed
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public static function name(string $name): string {
		$name = str_replace(['"', "'", '`', '{', '}', '[', ']', ',', '\\', '^'], '', trim($name));
		$name = preg_replace('/\s+/', ' ', $name);
		return mb_substr($name, 0, 255);
	}

	/**
	 * Get the recordings app text
	 *
	 * @return array
	 */
	private static function text(): array {
		$language = new text;
		return $language->get(null, 'app/recordings');
	}

	/**
	 * Create the Call button, the dialog is added once with the first button
	 *
	 * @param string $target         ID of the select box to add the new recording to, empty reloads the page
	 * @param string $recording_uuid UUID of an existing recording to re-record
	 * @param array  $options        Additional options: label (true shows the Call label, default is the icon only),
	 *                               full_path (select values use the full path), style, id,
	 *                               type (recording or greeting), voicemail_id (required for a greeting)
	 *
	 * @return string HTML for the button and the dialog
	 */
	public static function button(string $target = '', string $recording_uuid = '', array $options = []): string {
		$type = ($options['type'] ?? '') == 'greeting' ? 'greeting' : 'recording';
		if (!self::allowed($type)) {
			return '';
		}

		//only allow safe characters
		$target = preg_replace('/[^a-zA-Z0-9_\-]/', '', $target);
		$recording_uuid = is_uuid($recording_uuid) ? $recording_uuid : '';
		$voicemail_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($options['voicemail_id'] ?? ''));
		if ($type == 'greeting' && empty($voicemail_id)) {
			return '';
		}

		//get the text
		$text = self::text();

		//build the button
		$button = button::create([
			'type' => 'button',
			'label' => !empty($options['label']) ? $text['button-call'] : null,
			'title' => $text['title-recording_call'],
			'icon' => 'phone',
			'id' => $options['id'] ?? null,
			'style' => $options['style'] ?? null,
			'onclick' => "recording_call_open('".$target."', '".$recording_uuid."', ".(!empty($options['full_path']) ? 'true' : 'false').", '".$type."', '".$voicemail_id."');",
		]);

		return $button.self::modal($text, $type, $voicemail_id);
	}

	/**
	 * Create the dialog and the javascript, only once per page
	 *
	 * @param array  $text
	 * @param string $type         recording or greeting, sets the extensions that can be called
	 * @param string $voicemail_id the extension of this voicemail is selected by default
	 *
	 * @return string
	 */
	private static function modal(array $text, string $type, string $voicemail_id): string {
		if (self::$modal_added) {
			return '';
		}
		self::$modal_added = true;

		//get the extensions the user is allowed to call, the user's own extensions are listed first
		$database = database::new();
		$extensions = self::extensions($type);

		//get the settings
		$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid'], 'user_uuid' => $_SESSION['user_uuid'] ?? null]);

		$user_extensions = [];
		if (!empty($_SESSION['user']['extension']) && is_array($_SESSION['user']['extension'])) {
			foreach ($_SESSION['user']['extension'] as $row) {
				if (!empty($row['extension_uuid'])) {
					$user_extensions[] = $row['extension_uuid'];
				}
			}
		}
		$mine = $others = [];
		foreach ($extensions as $row) {
			if (in_array($row['extension_uuid'], $user_extensions)) {
				$mine[] = $row;
			}
			else {
				$others[] = $row;
			}
		}

		//create the token
		$object = new token;
		$token = $object->create('/app/recordings/recording_call.php');

		//build the extension options
		$extension_options = '';
		foreach (array_merge($mine, $others) as $row) {
			$label = $row['extension'];
			if (!empty($row['effective_caller_id_name'])) {
				$label .= ' - '.$row['effective_caller_id_name'];
			}
			else if (!empty($row['description'])) {
				$label .= ' - '.$row['description'];
			}
			$selected = !empty($voicemail_id) && ($row['extension'] == $voicemail_id || $row['number_alias'] == $voicemail_id) ? " selected='selected'" : '';
			$extension_options .= "<option value='".escape($row['extension_uuid'])."'".$selected.">".escape($label)."</option>\n";
		}

		//build the dialog body, inputs have no name so they are never submitted with the page form
		$body = "<div id='recording_call_form'>\n";
		$body .= "	<div id='recording_call_name_row'>\n";
		$body .= "		<strong>".$text['label-recording_name']."</strong><br />\n";
		$body .= "		<input type='text' class='formfld' id='recording_call_name' maxlength='255' style='width: 100%; max-width: 100%; margin-bottom: 15px;' onkeydown=\"if (event.key === 'Enter') { event.preventDefault(); recording_call_start(); }\">\n";
		$body .= "	</div>\n";
		$body .= "	<strong>".$text['label-recording_call_extension']."</strong><br />\n";
		$body .= "	<select class='formfld' id='recording_call_extension' style='width: 100%; max-width: 100%;'>\n";
		$body .= $extension_options;
		$body .= "	</select>\n";
		$body .= "	<div style='margin-top: 10px;'>".$text['description-recording_call']."</div>\n";
		$body .= "</div>\n";
		$body .= "<div id='recording_call_status' style='display: none; padding: 10px 0; font-weight: 600;'></div>\n";

		//build the dialog buttons
		$actions = button::create(['type'=>'button','label'=>$text['button-call'],'icon'=>'phone','id'=>'recording_call_btn_call','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"recording_call_start();"]);
		$actions .= button::create(['type'=>'button','label'=>$text['button-hangup'],'icon'=>'phone-slash','id'=>'recording_call_btn_hangup','style'=>'float: right; margin-left: 15px; display: none;','collapse'=>'never','onclick'=>"recording_call_hangup();"]);
		$actions .= button::create(['type'=>'button','label'=>$text['button-close'],'icon'=>$settings->get('theme', 'button_icon_cancel'),'id'=>'recording_call_btn_close','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"recording_call_close();"]);

		$html = modal::create([
			'id' => 'modal-recording_call',
			'title' => $text['title-recording_call'],
			'message' => $body,
			'actions' => $actions,
			'onclose' => 'recording_call_close();',
		]);

		//javascript text
		$js_text = [
			'calling' => $text['message-recording_call_ringing'],
			'answered' => $text['message-recording_call_answered'],
			'recording' => $text['message-recording_call_recording'],
			'review' => $text['message-recording_call_review'],
			'saved' => $text['message-recording_call_saved'],
			'deleted' => $text['message-recording_call_deleted'],
			'ended' => $text['message-recording_call_ended'],
			'failed' => $text['message-recording_call_failed'],
			'label_recordings' => $text['label-recordings'],
		];

		$html .= "<script>\n";
		$html .= "	var recording_call = {\n";
		$html .= "		url: '".PROJECT_PATH."/app/recordings/recording_call.php',\n";
		$html .= "		token_name: ".json_encode($token['name']).",\n";
		$html .= "		token_hash: ".json_encode($token['hash']).",\n";
		$html .= "		text: ".json_encode($js_text).",\n";
		$html .= "		target: '', recording_uuid: '', full_path: false, type: 'recording', voicemail_id: '', call_uuid: '', timer: null, saved: false\n";
		$html .= "	};\n";
		$html .= "</script>\n";
		$html .= "<script src='".PROJECT_PATH."/app/recordings/resources/javascript/recording_call.js'></script>\n";

		return $html;
	}

}
