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
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/
include "root.php";

//define the switch_recordings class
	class switch_recordings {

		private $recordings_list;
		private $domain_uuid;
		private $db;

		public function __construct($domain_uuid) {
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
			$this->domain_uuid = ( strlen($domain_uuid) > 0 ? $domain_uuid : $_SESSION['domain_uuid'] );
			$this->recordings_list = $this->list_recordings();
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function list_recordings() {
			$sql = "select recording_uuid, recording_filename, recording_base64 from v_recordings ";
			$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
			$prep_statement = $this->db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$recordings[$_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name']."/".$row['recording_filename']] = $row['recording_filename'];
			}
			unset ($prep_statement);
			return $recordings;
		}

		public function select_options($selected) {
			//add multi-lingual support
				$language = new text;
				$text = $language->get();
				
			//get the recordings
				if (sizeof($this->recordings_list) > 0) {
					$options .= "	<optgroup label='".$text['label-recordings']."'>";
					foreach($this->recordings_list as $recording_value => $recording_name){
						$options .= "		<option value='".$recording_value."' ".(($optionsed == $recording_value) ? 'selected="selected"' : null).">".$recording_name."</option>\n";
					}
					$options .= "	</optgroup>\n";
				}
			//return the options
				return $options;
		}
	
	}

?>