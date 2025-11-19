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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/

class tones {

	/**
	 * declare private variables
	 */
	private $music_list;
	private $recordings_list;
	private $default_tone_label;
	private $database;

	/**
	 * Constructor for the class.
	 *
	 * This method initializes the object with setting_array and session data.
	 *
	 * @param array $setting_array An optional array of settings to override default values. Defaults to [].
	 */
	public function __construct(array $setting_array = []) {
		//add multi-lingual support
		$language = new text;
		$text = $language->get();

		//connect to the database
		$this->database = $setting_array['database'] ?? database::new();
	}

	/**
	 * Retrieves a list of tone names with their corresponding labels.
	 *
	 * This method fetches tone data from the database and formats it for display.
	 *
	 * @return array An array of tone names as keys and their labels as values. If no tones are found, an empty array
	 *               is returned.
	 */
	public function tones_list() {
		//get the tones
		$sql = "select * from v_vars ";
		$sql .= "where var_category = 'Tones' ";
		$sql .= "order by var_name asc ";
		$tones = $this->database->select($sql, null, 'all');
		if (!empty($tones)) {
			foreach ($tones as $tone) {
				$tone = $tone['var_name'];
				if (isset($text['label-' . $tone])) {
					$label = $text['label-' . $tone];
				} else {
					$label = $tone;
				}
				$tone_list[$tone] = $label;
			}
		}
		unset($sql, $tones, $tone);

		//return the tones
		return $tone_list ?? [];
	}
}
