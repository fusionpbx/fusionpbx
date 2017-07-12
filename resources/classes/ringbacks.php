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

if (!class_exists('ringbacks')) {
	class ringbacks {

		//define variables
		public $db;
		private $ringbacks;
		private $tones_list;
		private $default_ringback_label;
		
		//class constructor
		public function __construct() {
			//connect to the database if not connected
				if (!$this->db) {
					require_once "resources/classes/database.php";
					$database = new database;
					$database->connect();
					$this->db = $database->db;
				}

			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//get the ringback types
				$sql = "select * from v_vars ";
				$sql .= "where var_cat = 'Ringtones' ";
				$sql .= "order by var_name asc ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$ringbacks = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset ($prep_statement, $sql);
				foreach($ringbacks as $ringback) {
					$ringback = $ringback['var_name'];
					$label = $text['label-'.$ringback];
					if ($label == "") {
						$label = $ringback;
					}
					$ringback_list[$ringback] = $label;
				}
				$this->ringbacks = $ringback_list;
				unset($ringback_list);
			
			//get the default_ringback label
				/*
				$sql = "select * from v_vars where var_name = 'ringback' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetch();
				unset ($prep_statement, $sql);
				$default_ringback = (string) $result['var_value'];
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
				require_once "resources/classes/tones.php";
				$tones = new tones;
				$this->tones_list = $tones->tones_list();
			
		}

		public function select ($name, $selected) {
			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//start the select
				$select = "<select class='formfld' name='".$name."' id='".$name."' style='width: auto;'>\n";

			//music on hold
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/music_on_hold')) {
					require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
					$music_on_hold = new switch_music_on_hold;
					$select .= $music_on_hold->select_options($selected);
				}

			//recordings
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/recordings')) {
					require_once "app/recordings/resources/classes/switch_recordings.php";
					$recordings = new switch_recordings;
					$select .= $recordings->select_options($selected);
				}

			//ringbacks
				if (sizeof($this->ringbacks) > 0) {
					$selected_ringback = $selected;
					$selected_ringback = preg_replace('/\A\${/',"",$selected_ringback);
					$selected_ringback = preg_replace('/}\z/',"",$selected_ringback);
					$select .= "	<optgroup label='".$text['label-ringback']."'>";
					//$select .= "		<option value='default_ringback'".(($selected == "default_ringback") ? ' selected="selected"' : '').">".$text['label-default']." (".$this->default_ringback_label.")</option>\n";
					foreach($this->ringbacks as $ringback_value => $ringback_name) {
						$select .= "		<option value='\${".$ringback_value."}'".(($selected_ringback == $ringback_value) ? ' selected="selected"' : '').">".$ringback_name."</option>\n";
					}
					$select .= "	</optgroup>\n";
					unset($selected_ringback);
				}

			//tones
				if (sizeof($this->tones_list) > 0) {
					$selected_tone = $selected;
					$selected_tone = preg_replace('/\A\${/',"",$selected_tone);
					$selected_tone = preg_replace('/}\z/',"",$selected_tone);
					$select .= "	<optgroup label='".$text['label-tone']."'>";
					foreach($this->tones_list as $tone_value => $tone_name) {
						$select .= "		<option value='\${".$tone_value."}'".(($selected_tone == $tone_value) ? ' selected="selected"' : '').">".$tone_name."</option>\n";
					}
					$select .= "	</optgroup>\n";
					unset($selected_tone);
				}

			//end the select and return it
				$select .= "</select>\n";
				return $select;
		}
	}
}

?>
