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

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//add multi-lingual support
			$language = new text;
			$text = $language->get();

			//connect to the database
			if (empty($this->database)) {
				$this->database = database::new();
			}

		}

		public function tones_list() {
			//get the tones
			$sql = "select * from v_vars ";
			$sql .= "where var_category = 'Tones' ";
			$sql .= "order by var_name asc ";
			$tones = $this->database->select($sql, null, 'all');
			if (!empty($tones)) {
				foreach ($tones as $tone) {
					$tone = $tone['var_name'];
					if (isset($text['label-'.$tone])) {
						$label = $text['label-'.$tone];
					}
					else {
						$label = $tone;
					}
					$tone_list[$tone] = $label;
				}
			}
			unset($sql, $tones, $tone);

			//return the tones
			return $tone_list ?? '';
		}
	}
