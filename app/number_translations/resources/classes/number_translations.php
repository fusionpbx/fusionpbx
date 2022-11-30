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
	Portions created by the Initial Developer are Copyright (C) 2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Matthew Vale <github@mafoo.org>
*/

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('number_translations')) {
	class number_translations {

		public $db;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * Check to see if the number translation already exists
		 */
		public function number_translation_exists($name) {
			$sql = "select number_translation_uuid from v_number_translations ";
			$sql .= "where number_translation_name = '$name' ";
			$prep_statement = $this->db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result)) {
					return true;
				}
				else {
					return false;
				}
			}
			unset($sql, $prep_statement, $result);
		}

		/**
		 * Import the number translation rules from the resources directory
		 */
		public function import() {
			//get the xml from the number templates
				if (strlen($this->xml) > 0) {
					//convert the xml string to an xml object
						$xml = simplexml_load_string($this->xml);
					//convert to json
						$json = json_encode($xml);
					//convert to an array
						$number_translation = json_decode($json, true);
				}
				elseif (strlen($this->json) > 0) {
					//convert to an array
						$number_translation = json_decode($this->json, true);
				}
				else {
					throw new Exception("require either json or xml to import");
				}
			//check if the number_translation exists
				if (!$this->number_translation_exists($number_translation['@attributes']['name'])) {
						$permissions = new permissions;
						$permissions->add('number_translation_add', 'temp');
						$permissions->add('number_translation_detail_add', 'temp');
						$x=0;
						$array['number_translations'][$x]['number_translation_name'] = $number_translation['@attributes']['name'];
						$array['number_translations'][$x]['number_translation_enabled'] = "true";
						if (strlen($number_translation['@attributes']['enabled']) > 0) {
							$array['number_translations'][$x]['number_translation_enabled'] = $number_translation['@attributes']['enabled'];
						}
						$array['number_translations'][$x]['number_translation_description'] = $number_translation['@attributes']['description'];

					//loop through the condition array
						$order = 5;
						if (isset($number_translation['rule'])) {
							foreach ($number_translation['rule'] as &$row) {
								if(array_key_exists('@attributes', $row))
									$row = $row['@attributes'];
								$array['number_translations'][$x]['number_translation_details'][$order]['number_translation_detail_regex'] = $row['regex'];
								$array['number_translations'][$x]['number_translation_details'][$order]['number_translation_detail_replace'] = $row['replace'];
								$array['number_translations'][$x]['number_translation_details'][$order]['number_translation_detail_order'] = $order;
								$order = $order + 5;
							}
						}
						$database = new database;
						$database->app_name = 'number_translations';
						$database->app_uuid = '6ad54de6-4909-11e7-a919-92ebcb67fe33';
						$database->save($array);
						if ($this->display_type == "text") {
							if ($database->message['code'] != '200') { 
								echo "number_translation:".$number_translation['@attributes']['name'].":	failed: ".$database->message['message']."\n";
							}
							else {
								echo "number_translation:".$number_translation['@attributes']['name'].":	added with ".(($order/5)-1)." entries\n";
							}
						}
						$permissions->delete('number_translation_add', 'temp');
						$permissions->delete('number_translation_detail_add', 'temp');
				}
				unset ($this->xml, $this->json);
		}
		
		/**
		 * delete number_translations
		 */
		public function delete($number_translations) {
			if (permission_exists('number_translation_delete')) {

				//delete multiple number_translations
					if (is_array($number_translations)) {
						//get the action
							foreach($number_translations as $row) {
								if ($row['action'] == 'delete') {
									$action = 'delete';
									break;
								}
							}
						//delete the checked rows
							if ($action == 'delete') {
								foreach($number_translations as $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										$sql = "delete from v_number_translations ";
										$sql .= "where number_translation_uuid = '".$row['number_translation_uuid']."'; ";
										$this->db->query($sql);
										unset($sql);
									}
								}
								unset($number_translations);
							}
					}
			}
		} //end the delete function

	}  //end the class
}

/*
$obj = new number_translations;
$obj->delete();
*/

?>
