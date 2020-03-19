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
	Portions created by the Initial Developer are Copyright (C) 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * conference_controls class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
if (!class_exists('conference_controls')) {
	class conference_controls {

		/**
		 * declare private variables
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
		 * declare public variables
		 */
		public $conference_control_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'conference_controls';
				$this->app_uuid = 'e1ad84a2-79e1-450c-a5b1-7507a043e048';
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

			//assign the variables
				$this->name = 'conference_control';
				$this->table = 'conference_controls';
				$this->location = 'conference_controls.php';

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
										$array['conference_control_details'][$x][$this->name.'_uuid'] = $record['uuid'];
									}

								//increment the id
									$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('conference_control_detail_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('conference_control_detail_delete', 'temp');

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		public function delete_details($records) {

			//assign the variables
				$this->name = 'conference_control_detail';
				$this->table = 'conference_control_details';
				$this->location = 'conference_control_edit.php?id='.$this->conference_control_uuid;

			if (permission_exists($this->name.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/app/conference_controls/conference_control_details.php')) {
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

			//assign the variables
				$this->name = 'conference_control';
				$this->table = 'conference_controls';
				$this->toggle_field = 'control_enabled';
				$this->toggle_values = ['true','false'];
				$this->location = 'conference_controls.php';

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

		public function toggle_details($records) {

			//assign the variables
				$this->name = 'conference_control_detail';
				$this->table = 'conference_control_details';
				$this->toggle_field = 'control_enabled';
				$this->toggle_values = ['true','false'];
				$this->location = 'conference_control_edit.php?id='.$this->conference_control_uuid;

			if (permission_exists($this->name.'_edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/app/conference_controls/conference_control_details.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {
						//get current toggle state
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
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
							foreach ($states as $uuid => $state) {
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

			//assign the variables
				$this->name = 'conference_control';
				$this->table = 'conference_controls';
				$this->description_field = 'control_description';
				$this->location = 'conference_controls.php';

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

								//primary table
									$sql = "select * from v_".$this->table." ";
									$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										$y = 0;
										foreach ($rows as $x => $row) {
											$primary_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//add copy to the description
												$array[$this->table][$x][$this->name.'_uuid'] = $primary_uuid;
												$array[$this->table][$x][$this->description_field] = trim($row[$this->description_field]).' ('.$text['label-copy'].')';

											//details sub table
												$sql_2 = "select * from v_conference_control_details where conference_control_uuid = :conference_control_uuid";
												$parameters_2['conference_control_uuid'] = $row['conference_control_uuid'];
												$database = new database;
												$rows_2 = $database->select($sql_2, $parameters_2, 'all');
												if (is_array($rows_2) && @sizeof($rows_2) != 0) {
													foreach ($rows_2 as $row_2) {

														//copy data
															$array['conference_control_details'][$y] = $row_2;

														//overwrite
															$array['conference_control_details'][$y]['conference_control_detail_uuid'] = uuid();
															$array['conference_control_details'][$y]['conference_control_uuid'] = $primary_uuid;

														//increment
															$y++;

													}
												}
												unset($sql_2, $parameters_2, $rows_2, $row_2);										}
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