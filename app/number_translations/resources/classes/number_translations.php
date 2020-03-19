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
 Portions created by the Initial Developer are Copyright (C) 2008-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the number translations class
if (!class_exists('number_translations')) {
	class number_translations {

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_field;
		private $toggle_values;

		/**
		 * declare public variables
		 */
		public $number_translation_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'number_translations';
				$this->app_uuid = '6ad54de6-4909-11e7-a919-92ebcb67fe33';
				$this->permission_prefix = 'number_translation_';
				$this->list_page = 'number_translations.php';
				$this->table = 'number_translations';
				$this->uuid_prefix = 'number_translation_';
				$this->toggle_field = 'number_translation_enabled';
				$this->toggle_values = ['true','false'];

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
		 * Check to see if the number translation already exists
		 */
		public function number_translation_exists($name) {
			$sql = "select count(*) from v_number_translations ";
			$sql .= "where number_translation_name = :number_translation_name ";
			$parameters['number_translation_name'] = $name;
			$database = new database;
			return $database->select($sql, $parameters, 'column') != 0 ? true : false;
			unset($sql, $parameters);
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
				else if (strlen($this->json) > 0) {
					//convert to an array
						$number_translation = json_decode($this->json, true);
				}
				else {
					throw new Exception("require either json or xml to import");
				}
			//check if the number_translation exists
				if (!$this->number_translation_exists($number_translation['@attributes']['name'])) {
					//begin insert array
						$x = 0;
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
								if (array_key_exists('@attributes', $row)) {
									$row = $row['@attributes'];
								}
								$array['number_translations'][$x]['number_translation_details'][$order]['number_translation_detail_regex'] = $row['regex'];
								$array['number_translations'][$x]['number_translation_details'][$order]['number_translation_detail_replace'] = $row['replace'];
								$array['number_translations'][$x]['number_translation_details'][$order]['number_translation_detail_order'] = $order;
								$order = $order + 5;
							}
						}
					//grant temporary permissions
						$p = new permissions;
						$p->add('number_translation_add', 'temp');
						$p->add('number_translation_detail_add', 'temp');
					//execute insert
						$database = new database;
						$database->app_name = 'number_translations';
						$database->app_uuid = '6ad54de6-4909-11e7-a919-92ebcb67fe33';
						$database->save($array);
						unset($array);
						if ($this->display_type == "text") {
							if ($database->message['code'] != '200') { 
								echo "number_translation:".$number_translation['@attributes']['name'].":	failed: ".$database->message['message']."\n";
							}
							else {
								echo "number_translation:".$number_translation['@attributes']['name'].":	added with ".(($order/5)-1)." entries\n";
							}
						}
					//revoke temporary permissions
						$p->delete('number_translation_add', 'temp');
						$p->delete('number_translation_detail_add', 'temp');
				}
				unset ($this->xml, $this->json);
		}
		
		/**
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array['number_translation_details'][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('number_translation_detail_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('number_translation_detail_delete', 'temp');

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		public function delete_details($records) {

			//assign private variables
				$this->permission_prefix = 'number_translation_detail_';
				$this->table = 'number_translation_details';
				$this->uuid_prefix = 'number_translation_detail_';

			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['number_translation_uuid'] = $this->number_translation_uuid;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

							}
							unset($records);
					}
			}
		}

		/**
		 * toggle records
		 */
		public function toggle($records) {
			if (permission_exists($this->permission_prefix.'edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get current toggle state
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
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
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
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
		 * copy records
		 */
		public function copy($records) {
			if (permission_exists($this->permission_prefix.'add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {

								//primary table
									$sql = "select * from v_".$this->table." ";
									$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										$y = 0;
										foreach ($rows as $x => $row) {
											$primary_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//overwrite
												$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $primary_uuid;
												$array[$this->table][$x]['number_translation_description'] = trim($row['number_translation_description'].' ('.$text['label-copy'].')');

											//nodes sub table
												$sql_2 = "select * from v_number_translation_details where number_translation_uuid = :number_translation_uuid";
												$parameters_2['number_translation_uuid'] = $row['number_translation_uuid'];
												$database = new database;
												$rows_2 = $database->select($sql_2, $parameters_2, 'all');
												if (is_array($rows_2) && @sizeof($rows_2) != 0) {
													foreach ($rows_2 as $row_2) {

														//copy data
															$array['number_translation_details'][$y] = $row_2;

														//overwrite
															$array['number_translation_details'][$y]['number_translation_detail_uuid'] = uuid();
															$array['number_translation_details'][$y]['number_translation_uuid'] = $primary_uuid;

														//increment
															$y++;

													}
												}
												unset($sql_2, $parameters_2, $rows_2, $row_2);
										}
									}
									unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('number_translation_detail_add', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('number_translation_detail_add', 'temp');

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		} //method

	} //class
}

/*
$obj = new number_translations;
$obj->delete();
*/

?>