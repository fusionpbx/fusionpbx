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
	Portions created by the Initial Developer are Copyright (C) 2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * extension_settings class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
if (!class_exists('extension_settings')) {
	class extension_settings {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		private $name;
		private $table;
		private $toggle_field;
		private $toggle_values;
		private $description_field;
		private $location;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'extension_settings';
				$this->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
				$this->name = 'extension_setting';
				$this->table = 'extension_settings';
				$this->toggle_field = 'extension_setting_enabled';
				$this->toggle_values = ['true','false'];
				$this->description_field = 'extension_setting_description';
				$this->location = 'extension_settings.php';
		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * delete rows from the database
		 */
		public function delete($records) {
			if (permission_exists($this->name.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								//add to the array
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$array[$this->table][$x][$this->name.'_uuid'] = $record['uuid'];
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									}

								//increment the id
									$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);
								
								//clear the cache	
									$sql = "select extension, number_alias, user_context from v_extensions ";
									$sql .= "where extension_uuid = :extension_uuid ";
									$parameters['extension_uuid'] = $this->extension_uuid;
									$database = new database;
									$extension = $database->select($sql, $parameters, 'row');
									$cache = new cache;
									$cache->delete("directory:".$extension["extension"]."@".$extension["user_context"]);
									$cache->delete("directory:".$extension["number_alias"]."@".$extension["user_context"]);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * toggle a field between two values
		 */
		public function toggle($records) {
			if (permission_exists($this->name.'_edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {
						//get current toggle state
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								//create the array
									$array[$this->table][$x][$this->name.'_uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

								//increment the id
									$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}
			}
		}

		/**
		 * copy rows from the database
		 */
		public function copy($records) {
			if (permission_exists($this->name.'_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create the array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select * from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									$x = 0;
									foreach ($rows as $row) {
										//copy data
											$array[$this->table][$x] = $row;

										//add copy to the description
											$array[$this->table][$x][$this->name.'_uuid'] = uuid();
											$array[$this->table][$x][$this->description_field] = trim($row[$this->description_field]).' ('.$text['label-copy'].')';

										//increment the id
											$x++;
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);
							}
							unset($records);
					}
			}
		}

	}
}

?>
