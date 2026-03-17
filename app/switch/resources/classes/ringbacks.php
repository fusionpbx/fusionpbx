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
	Portions created by the Initial Developer are Copyright (C) 2016-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/

class ringbacks {

	/**
	 * declare constant variables
	 */
	const app_name = 'ringbacks';
	const app_uuid = 'b63db353-e1c6-4401-8f10-101a6ee73b74';

	/**
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	public $domain_uuid;

	/**
	 * declare public variables
	 */
	public $ringtones_list;

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 *
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Settings object set in the constructor. Must be a settings object and cannot be null.
	 *
	 * @var settings Settings Object
	 */
	private $settings;

	/**
	 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in
	 * the session global array
	 *
	 * @var string
	 */
	private $user_uuid;

	/**
	 * Username set in the constructor. This can be passed in through the $settings_array associative array or set in
	 * the session global array
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Domain name set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	private $domain_name;

	/**
	 * declare private variables
	 */
	private $tones_list;
	private $music_list;
	private $recordings_list;
	private $default_ringback_label;
	private $streams;

	/**
	 * Initializes the object with setting array.
	 *
	 * @param array $setting_array An array containing settings for domain, user, and database connections. Defaults to
	 *                             an empty array.
	 *
	 * @return void
	 */
	public function __construct(array $setting_array = []) {
		//set domain and user UUIDs
		$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
		$this->domain_name = $setting_array['domain_name'] ?? $_SESSION['domain_name'] ?? '';
		$this->user_uuid   = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';
		$this->username    = $setting_array['username'] ?? $_SESSION['username'] ?? '';

		//set objects
		$this->database = $setting_array['database'] ?? database::new();

		//add multi-lingual support
		$language = new text;
		$text     = $language->get();

		//get the ringtones
		$sql       = "select * from v_vars ";
		$sql       .= "where var_category = 'Ringtones' ";
		$sql       .= "order by var_name asc ";
		$ringtones = $this->database->select($sql, null, 'all');
		if (!empty($ringtones)) {
			foreach ($ringtones as $ringtone) {
				$ringtone = $ringtone['var_name'];
				if (isset($text['label-' . $ringtone])) {
					$label = $text['label-' . $ringtone];
				} else {
					$label = $ringtone;
				}
				$ringtones_list[$ringtone] = $label;
			}
		}
		$this->ringtones_list = $ringtones_list ?? '';
		unset($sql, $ringtones, $ringtone, $ringtones_list);

		//get the default_ringback label
		/*
		$sql = "select * from v_vars where var_name = 'ringback' ";
		$row = $this->database->select($sql, null, 'row');
		unset($sql);
		$default_ringback = (string) $row['var_value'];
		$default_ringback = preg_replace('/\A\$\${/',"",$default_ringback);
		$default_ringback = preg_replace('/}\z/',"",$default_ringback);
		#$label = $text['label-'.$default_ringback];
		#if($label == "") {
			$label = $default_ringback;
		#}
		$this->default_ringback_label = $label;
		unset($results, $default_ringback, $label);
		*/

		//get the tones
		$tones            = new tones;
		$this->tones_list = $tones->tones_list();

		//get music on hold	and recordings
		if (is_dir(dirname(__DIR__, 4) . '/app/music_on_hold')) {
			$music            = new switch_music_on_hold;
			$this->music_list = $music->get();
		}
		if (is_dir(dirname(__DIR__, 4) . '/app/recordings')) {
			$recordings            = new switch_recordings;
			$this->recordings_list = $recordings->list_recordings();
		}

		if (is_dir(dirname(__DIR__, 4) . '/app/streams')) {
			$sql                       = "select * from v_streams ";
			$sql                       .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$sql                       .= "and stream_enabled = 'true' ";
			$sql                       .= "order by stream_name asc ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$streams                   = $this->database->select($sql, $parameters, 'all');
			$this->streams             = $streams;
			unset($sql, $parameters, $streams, $row);
		}
	}

	/**
	 * Checks if a given value is valid.
	 *
	 * @param mixed $value The value to check for validity
	 *
	 * @return bool True if the value is valid, false otherwise
	 */
	public function valid($value) {
		foreach ($this->ringtones_list as $ringtone_value => $ringtone_name) {
			if ($value == "\${" . $ringtone_value . "}") {
				return true;
			}
		}

		foreach ($this->tones_list as $tone_value => $tone_name) {
			if ($value == "\${" . $tone_value . "}") {
				return true;
			}
		}

		foreach ($this->music_list as $row) {
			$name = '';
			if (!empty($row['domain_uuid'])) {
				$name = $row['domain_name'] . '/';
			}
			$name .= $row['music_on_hold_name'];
			if ($value == "local_stream://" . $name) {
				return true;
			}
		}

		foreach ($this->recordings_list as $recording_value => $recording_name) {
			if ($value == $recording_value) {
				return true;
			}
		}

		foreach ($this->streams as $row) {
			if ($value == $row['stream_location']) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generates a HTML <select> element based on given name and selected value.
	 *
	 * @param string $name     The name of the select element
	 * @param mixed  $selected The currently selected value for the select element
	 *
	 * @return string A fully formed HTML <select> element with options populated from various lists (music, recordings, streams, ringtones, tones)
	 */
	public function select($name, $selected) {
		//add multi-lingual support
		$language = new text;
		$text     = $language->get();

		//start the select
		$select = "<select class='formfld' name='" . $name . "' id='" . $name . "' style='width: auto;'>\n";
		$select .= "		<option value=''></option>\n";

		//music list
		if (!empty($this->music_list)) {
			$select        .= "	<optgroup label='" . $text['label-music_on_hold'] . "'>\n";
			$previous_name = '';
			foreach ($this->music_list as $row) {
				if ($previous_name != $row['music_on_hold_name']) {
					$name = '';
					if (!empty($row['domain_uuid'])) {
						$name = $row['domain_name'] . '/';
					}
					$name   .= $row['music_on_hold_name'];
					$select .= "		<option value='local_stream://" . $name . "' " . (($selected == "local_stream://" . $name) ? 'selected="selected"' : null) . ">" . $row['music_on_hold_name'] . "</option>\n";
				}
				$previous_name = $row['music_on_hold_name'];
			}
			$select .= "	</optgroup>\n";
		}

		//recordings
		if (!empty($this->recordings_list)) {
			$select .= "	<optgroup label='" . $text['label-recordings'] . "'>";
			foreach ($this->recordings_list as $recording_value => $recording_name) {
				$select .= "		<option value='" . $recording_value . "' " . (($selected == $recording_value) ? 'selected="selected"' : null) . ">" . $recording_name . "</option>\n";
			}
			$select .= "	</optgroup>\n";
		}

		//streams
		if (!empty($this->streams)) {
			$select .= "	<optgroup label='" . $text['label-streams'] . "'>";
			foreach ($this->streams as $row) {
				$select .= "		<option value='" . $row['stream_location'] . "' " . (($selected == $row['stream_location']) ? 'selected="selected"' : null) . ">" . $row['stream_name'] . "</option>\n";
			}
			$select .= "	</optgroup>\n";
		}

		//ringtones
		if (!empty($this->ringtones_list)) {
			$selected_ringtone = $selected;
			$selected_ringtone = preg_replace('/\A\${/', "", $selected_ringtone ?? '');
			$selected_ringtone = preg_replace('/}\z/', "", $selected_ringtone);
			$select            .= "	<optgroup label='" . $text['label-ringtones'] . "'>";
			//$select .= "		<option value='default_ringtones'".(($selected == "default_ringback") ? ' selected="selected"' : '').">".$text['label-default']." (".$this->default_ringtone_label.")</option>\n";
			foreach ($this->ringtones_list as $ringtone_value => $ringtone_name) {
				$select .= "		<option value='\${" . $ringtone_value . "}'" . (($selected_ringtone == $ringtone_value) ? ' selected="selected"' : null) . ">" . $ringtone_name . "</option>\n";
			}
			//add silence option
			$select .= "		<option value='silence'>Silence</option>\n";
			$select .= "	</optgroup>\n";
			unset($selected_ringtone);
		}

		//tones
		if (!empty($this->tones_list)) {
			$selected_tone = $selected;
			$selected_tone = preg_replace('/\A\${/', "", $selected_tone ?? '');
			$selected_tone = preg_replace('/}\z/', "", $selected_tone);
			$select        .= "	<optgroup label='" . $text['label-tones'] . "'>";
			foreach ($this->tones_list as $tone_value => $tone_name) {
				$select .= "		<option value='\${" . $tone_value . "}'" . (($selected_tone == $tone_value) ? ' selected="selected"' : null) . ">" . $tone_name . "</option>\n";
			}
			$select .= "	</optgroup>\n";
			unset($selected_tone);
		}

		//end the select and return it
		$select .= "</select>\n";
		return $select;
	}
}
