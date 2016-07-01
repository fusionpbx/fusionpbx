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
		private $music_list;
		private $recordings_list;
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
				$sql .= "where var_cat = 'Defaults' ";
				$sql .= "and var_name LIKE '%-ring' ";
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

			//get music on hold	and recordings
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/music_on_hold')) {
					require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
					$music = new switch_music_on_hold;
					$this->music_list = $music->get();
				}
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/recordings')) {
					require_once "app/recordings/resources/classes/switch_recordings.php";
					$recordings = new switch_recordings;
					$this->recordings_list = $recordings->list_recordings();
				}
		}

		public function select ($name, $selected) {
			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//start the select
				$select = "<select class='formfld' name='".$name."' id='".$name."' style='width: auto;'>\n";

			//music list
				if (count($this->music_list) > 0) {
					$select .= "	<optgroup label='".$text['label-music_on_hold']."'>\n";
					$previous_name = '';
					foreach($this->music_list as $row) {
						if ($previous_name != $row['music_on_hold_name']) {
							$name = '';
							if (strlen($row['domain_uuid']) > 0) {
								$name = $row['domain_name'].'/';	
							}
							$name .= $row['music_on_hold_name'];
							$select .= "		<option value='local_stream://".$name."' ".(($selected == "local_stream://".$name) ? 'selected="selected"' : null).">".$row['music_on_hold_name']."</option>\n";
						}
						$previous_name = $row['music_on_hold_name'];
					}
					$select .= "	</optgroup>\n";
				}

			//recordings
				if (sizeof($this->recordings_list) > 0) {
					$select .= "	<optgroup label='".$text['label-recordings']."'>";
					foreach($this->recordings_list as $recording_value => $recording_name){
						$select .= "		<option value='".$recording_value."' ".(($selected == $recording_value) ? 'selected="selected"' : '').">".$recording_name."</option>\n";
					}
					$select .= "	</optgroup>\n";
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

			//end the select and return it
				$select .= "</select>\n";
				return $select;
		}
	}
}

?>
